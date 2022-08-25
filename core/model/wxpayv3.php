<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Wxpayv3_EweiShopV2Model
{

    const KEY_LENGTH_BYTE = 32;
    const AUTH_TAG_LENGTH_BYTE = 16;
    public $apiv3url="https://api.mch.weixin.qq.com";
    /**
     * 获取商户证书支付信息
     * @return mixed
     */
    public function get_shop_mchSerialNo($uniacid){
        $sec = m('common')->getSec($uniacid);
        $sec = iunserializer($sec['sec']);
        $setting = uni_setting($uniacid, array('payment'));
        $wechat = $setting['payment']['wechat'];
        $apiclient_key=$sec['key'];
        $apiclient_cert=$sec['cert'];
        return array(
            'merchantCertificateSerial'=>$sec['merchantCertificateSerial'],
            'mchid'=>$wechat['mchid'],
            'apiclient_key'=>$apiclient_key,
            'apiclient_cert'=>$apiclient_cert,
            'wechat'=>$wechat,
            'setting'=>$setting,
            'sec'=>$sec,
        );
    }
    //资金解冻
    public function unfreeze($transaction_id){
        $url="/profitsharing/orders/unfreeze";
        $params=array(
            ''
        );
    }

    /**
     * 获取最大分账金额
     * @param $transaction_id
     */
    public function get_unfree_amount($transaction_id){
       $url= "/v3/profitsharing/transactions/{$transaction_id}/amounts";
       $http_response=$this->_requist($url,'','',true);

       $content=json_decode($http_response['content'],true);

       return $content;
    }

    public function get_acount_info(){
        return  pdo_fetch('SELECT `key`,`secret` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>DIS_ACCOUNT));
    }

    /**
     * 添加分账接收方
     */
    public function addreceivers($uniacid,$wx_receivers_type,$account,$accountname,$relation_type){
        $url="/v3/profitsharing/receivers/add";

        $acount_info =$this->get_acount_info();

        $prams=array(
            'appid'=>$acount_info['key'],
            'type'=>$wx_receivers_type,
            'account'=>$account,
            'name'=>$this->getEncrypt($accountname),
            'relation_type'=>$relation_type,
        );
        $data=$this->get_redis_certificates();
        $header=array(
            'Content-Type'=>"application/json",
            'Wechatpay-Serial'=>$data['serial_no']
        );
        $response= $this->_requist($url,json_encode($prams),$header,true);
        $content=$response['content'];
        $content=json_decode($content,true);
        if(isset($content['code'])){
            return $content['message'];
        }
        return true;
    }

    public function checkunfreeOrder($order_id){
       $data_tem= pdo_fetch('select * from '.tablename("ewei_shop_unfreeze_order")." where order_id=:order_id",array(":order_id"=>$order_id));
       if(empty($data_tem)){
           return true;
       }else{
           if($data_tem['unfreeze_status']==1){
               return "分账正在进行中";
           }
           if($data_tem['unfreeze_status']==2){
               return "分账已完成";
           }
           if($data_tem['unfreeze_status']==-1){
               return $data_tem['paymsg'];
           }
         return $data_tem;
       }

    }

    public function profitsharing($order_id,$transaction_id){
        $data_tem= pdo_fetch('select * from '.tablename("ewei_shop_unfreeze_order")." where order_id=:order_id",array(":order_id"=>$order_id));
        $uniacid=DIS_ACCOUNT;

        $ret=$this->get_unfree_amount($transaction_id);
        $unsplit_amount=$ret['unsplit_amount'];
        if($unsplit_amount<$data_tem['amount']){
            pdo_update('ewei_shop_unfreeze_order',array("paymsg"=>"可分账金额{$unsplit_amount}不足",'unfreeze_status'=>-1),array("id"=>$data_tem['id']));
        }
        $receivers[]=array(
            'type'=>$data_tem['type'],
            'account'=>$data_tem['account'],
            'name'=>$this->getEncrypt($data_tem['name']) ,
            'amount'=>intval($data_tem['amount']),
            'description'=>$data_tem['description'],
        );
        $params=array(
            'appid'=>$data_tem['appid'],
            'transaction_id'=>$transaction_id,
            'out_order_no'=> $data_tem['out_order_no'],
            'receivers'=>$receivers,
            'unfreeze_unsplit'=>true,
        );
        $url="/v3/profitsharing/orders";
        $data=$this->get_redis_certificates();
        $header=array(
            'Content-Type'=>"application/json",
            'Wechatpay-Serial'=>$data['serial_no']
        );

        $response= $this->_requist($url,json_encode($params),$header,true);
        $content=json_decode($response['content'],true);

        if(isset($content['code'])){
            pdo_update("ewei_shop_unfreeze_order",array("unfreeze_status"=>-1,'paymsg'=> $content['message']),array('id'=>$data_tem['id']));
            return $content['message'];
        }
        pdo_update("ewei_shop_unfreeze_order",array("unfreeze_status"=>2),array('id'=>$data_tem['id']));
        pdo_update('ewei_shop_order',array("paystatus"=>2),array("id"=>$order_id));

        return true;
    }


    //请求分账
    public function createOrder($order_id,$transaction_id){
        $order= pdo_fetch("select * from ".tablename("ewei_shop_order").'where id=:id',array(":id"=>$order_id));
        $disInfo=Dispage::getDisInfo($order['uniacid']);
        $amount=$order['price']-$order['disorderamount'];
        if($amount<0){
            pdo_update("order",array("cnbuyers_order_sn"=>"分账金额{$amount},出现异常无法进行下一步"));
            //解冻金额

            return false;
        }
        $acount_info =$this->get_acount_info();
        $params=array(
            'appid'=>$acount_info['key'],
            'transaction_id'=>$transaction_id,
            'order_id'=>$order_id,
            'out_order_no'=> m('common')->createNO('unfreeze_order', 'out_order_no', 'FZ'),
            'type'=>$disInfo['wx_receivers_type'],
            'account'=>$disInfo['account'],
            'name'=>$disInfo['accountname'],
            'amount'=>$amount*100,
            'description'=>'利润分账',
        );
        pdo_insert("ewei_shop_unfreeze_order",$params);

    }


    /**
     * 获取微信平台证书
     */
    public function get_certificates(){
       $response= $this->_requist("/v3/certificates",'','',true);
       $content=$response['content'];
       $content=json_decode($content,true);
       $certificate=end($content['data']);
        $ciphertext=$certificate['encrypt_certificate']['ciphertext'];//证书内容
        $associatedData=$certificate['encrypt_certificate']['associated_data'];//
        $nonceStr=$certificate['encrypt_certificate']['nonce'];//
        $ciphertext=$this->aesdecryptToString($associatedData,$nonceStr,$ciphertext);
        $installdata=array(
           'certificate'=>$ciphertext,
           'serial_no'=>$certificate['serial_no'],
        );
       $data= pdo_fetch("select * from ".tablename("ewei_shop_certificates")." where uniacid=:uniacid",array(":uniacid"=>DIS_ACCOUNT));
       if($data){
           pdo_update('ewei_shop_certificates',$installdata,array("id"=>$data['id']));
       }else{
           $installdata['uniacid']=DIS_ACCOUNT;
           pdo_insert('ewei_shop_certificates',$installdata);
       }

       return $installdata;
    }

    public function  aesdecryptToString($associatedData,$nonceStr,$ciphertext){
        $setting = uni_setting(DIS_ACCOUNT, array('payment'));
        $wechat = $setting['payment']['wechat'];
        $apiv3key=$wechat['apiv3key'];
        $ciphertext = base64_decode($ciphertext);
        if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
            return false;
        }
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && \sodium_crypto_aead_aes256gcm_is_available()) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $apiv3key);
		}
        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') && \Sodium\crypto_aead_aes256gcm_is_available()) {
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $apiv3key);
		}
        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);

            return \openssl_decrypt($ctext, 'aes-256-gcm', $apiv3key, \OPENSSL_RAW_DATA, $nonceStr,
				$authTag, $associatedData);
		}

        throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }



    public function get_signPrams($url,$postdata){
        return array(
            'http_method'=>empty($postdata) ? "GET" :"POST",
            'canonical_url'=>$url,
            'timestamp'=>time(),
            'nonce'=>random(16,false),
            'body'=>$postdata,
        );
    }

    public function get_redis_certificates(){
        $reids=redis();
        $key=DIS_ACCOUNT."certificate";
        $data=$reids->get($key);
        if(empty($data)){
            $data=$this->get_certificates();
            $reids->set($key,json_encode($data),86400);
        }else{
            $data=json_decode($data,true);
        }
        return $data;
    }

    /**
     * 微信证书加密敏感信息
     */
    public function getEncrypt($str){
        $data=$this->get_redis_certificates();
        openssl_public_encrypt($str, $encrypted, $data['certificate'], OPENSSL_PKCS1_OAEP_PADDING);
        $sign = base64_encode($encrypted);
       return $sign;
    }

    public function _requist($url,$postdata='',$extras=array(),$verify=false){
        load()->func('communication');

        if($verify){
            $cert= $this->get_shop_mchSerialNo(DIS_ACCOUNT);
            $pkeyid= openssl_get_privatekey($cert['apiclient_key']);
            $sign_prarms=$this->get_signPrams($url,$postdata);
            $serial_no=$cert['merchantCertificateSerial'];
            $nonce=$sign_prarms['nonce'];
            $timestamp=$sign_prarms['timestamp'];
            $message = $sign_prarms['http_method']."\n".
                $url."\n".
                $timestamp."\n".
                $nonce."\n".
                $sign_prarms['body']."\n";
            openssl_sign($message, $raw_sign,$pkeyid, 'sha256WithRSAEncryption');
            $sign = base64_encode($raw_sign);
            openssl_free_key($pkeyid);
            $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',

                $cert['mchid'], $nonce, $timestamp, $serial_no, $sign);
           if(empty($extras)){
               $extras=array();
           }
            $extras=array_merge($extras,array(
                'Authorization'=>"WECHATPAY2-SHA256-RSA2048 {$token}"
            ));
        }

        $resp = ihttp_request($this->apiv3url.$url, $postdata, $extras);
        return $resp;
    }



}