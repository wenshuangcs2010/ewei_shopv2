<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Common_EweiShopV2Model {

	public function getSetData($uniacid = 0) {
		global $_W;
		if (empty($uniacid)) {
			$uniacid = $_W['uniacid'];
		}
		$set = m('cache')->getArray('sysset', $uniacid);

		if (empty($set)) {
			$set = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $uniacid));
			if (empty($set)) {
				$set = array();
			}
			m('cache')->set('sysset', $set, $uniacid);
		}
		return $set;
	}

	/**
	 * 获取配置
	 */
	public function getSysset($key = '', $uniacid = 0) {
		global $_W, $_GPC;

		$set = $this->getSetData($uniacid);

		$allset = iunserializer($set['sets']);

		$retsets = array();
		if (!empty($key)) {
			if (is_array($key)) {
				foreach ($key as $k) {
					$retsets[$k] = isset($allset[$k]) ? $allset[$k] : array();
				}
			} else {
				$retsets = isset($allset[$key]) ? $allset[$key] : array();
			}
			return $retsets;
		} else {
			return $allset;
		}
	}

	public function getPluginset($key = '', $uniacid = 0) {
		global $_W, $_GPC;
		$set = $this->getSetData($uniacid);
		$allset = iunserializer($set['plugins']);

		$retsets = array();
		if (!empty($key)) {
			if (is_array($key)) {
				foreach ($key as $k) {
					$retsets[$k] = isset($allset[$k]) ? $allset[$k] : array();
				}
			} else {
				$retsets = isset($allset[$key]) ? $allset[$key] : array();
			}

			return $retsets;
		} else {
			return $allset;
		}
	}

	public function updateSysset($values, $uniacid = 0) {
		global $_W, $_GPC;
		if (empty($uniacid)) {
			$uniacid = $_W['uniacid'];
		}

		$setdata = pdo_fetch("select id, sets from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $uniacid));
		if (empty($setdata)) {
			pdo_insert('ewei_shop_sysset', array('sets' => iserializer($values), 'uniacid' => $uniacid));
		} else {
			$sets = iunserializer($setdata['sets']);
            $sets = is_array($sets) ? $sets : array();
			foreach ($values as $key => $value) {
				foreach ($value as $k => $v) {
					$sets[$key][$k] = $v;
				}
			}

			pdo_update('ewei_shop_sysset', array('sets' => iserializer($sets)), array('id' => $setdata['id']));
		}
		$setdata = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $uniacid));
		m('cache')->set('sysset', $setdata, $uniacid);
		$this->setGlobalSet();
	}

	public function updatePluginset($values, $uniacid = 0) {
		global $_W, $_GPC;
		if (empty($uniacid)) {
			$uniacid = $_W['uniacid'];
		}

		$setdata = pdo_fetch("select id, plugins from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $uniacid));

		if (empty($setdata)) {
			pdo_insert('ewei_shop_sysset', array('plugins' => iserializer($values), 'uniacid' => $uniacid));
		} else {

			$plugins = iunserializer($setdata['plugins']);
        
			foreach ($values as $key => $value) {

				foreach ($value as $k => $v) {
					$plugins[$key][$k] = $v;
				}
    
			}

			pdo_update('ewei_shop_sysset', array('plugins' => iserializer($plugins)), array('id' => $setdata['id']));
		}
		$setdata = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $uniacid));
		m('cache')->set('sysset', $setdata, $uniacid);
		$this->setGlobalSet();
	}

	function setGlobalSet() {

		$sysset = $this->getSysset();
        $sysset = is_array($sysset) ? $sysset : array();
		$pluginset = $this->getPluginset();
		if (is_array($pluginset)) {
			foreach ($pluginset as $k => $v) {
				$sysset[$k] = $v;
			}
		}
		m('cache')->set('globalset', $sysset);
		return $sysset;
	}

	//$type 0 购物 1 充值
	public function alipay_build($params, $alipay = array(), $type = 0, $openid = '') {
		global $_W;
		$tid = $params['tid'];
		$set = array();
		$set['service'] = 'alipay.wap.create.direct.pay.by.user';
		$set['partner'] = $alipay['partner'];
		$set['_input_charset'] = 'utf-8';
		$set['sign_type'] = 'MD5';
        if (empty($type)) {
            //购物
            $set['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/alipay/notify.php";
            $set['return_url'] = mobileUrl('order/pay_alipay/complete', array('openid' => $openid, 'fromwechat'=>is_weixin()?1:0), true);
        }else if($type==20){
			$set['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/alipay/notify.php";
			$set['return_url'] = mobileUrl('creditshop/detail/creditshop_complete', array('openid' => $openid, 'fromwechat'=>is_weixin()?1:0), true);
		}else if($type==21){
			$set['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/alipay/notify.php";
			$set['return_url'] = mobileUrl('creditshop/log/dispatch_complete', array('openid' => $openid, 'fromwechat'=>is_weixin()?1:0), true);
		} else {
            $set['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/alipay/notify.php";
            $set['return_url'] = mobileUrl('order/pay_alipay/recharge_complete', array('openid' => $openid, 'fromwechat'=>is_weixin()?1:0), true);
        }
		$set['out_trade_no'] = $tid;
		$set['subject'] = $params['title'];
		$set['total_fee'] = $params['fee'];
		$set['seller_id'] = $alipay['account'];
		if(!is_weixin()) {
			$set['app_pay'] = 'Y';
		}
		$set['payment_type'] = 1;
		$set['body'] = $_W['uniacid'] . ':' . $type;
		$prepares = array();
		foreach ($set as $key => $value) {
			if ($key != 'sign' && $key != 'sign_type') {
				$prepares[] = "{$key}={$value}";
			}
		}
		sort($prepares);
		$string = implode($prepares, '&');
		$string .= $alipay['secret'];
		$set['sign'] = md5($string);
		return array('url' => ALIPAY_GATEWAY . '?' . http_build_query($set, '', '&'));
	}

	public function publicAliPay($params = array(),$return = null)
    {
        $public = array(
            'app_id' => $params['app_id'],
            'method' => $params['method'],
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        );
        if(!empty($params['return_url'])){
            $public['return_url'] = $params['return_url'];
        }
        if(!empty($params['app_auth_token'])){
            $public['app_auth_token'] = $params['app_auth_token'];
        }
        if(!empty($params['notify_url'])){
            $public['notify_url'] = $params['notify_url'];
        }
        if(!empty($params['biz_content'])){
            $public['biz_content'] = is_array($params['biz_content']) ? json_encode($params['biz_content']) : $params['biz_content'];
        }

        ksort($public);
        $string1 = '';
        foreach ($public as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $string1 = rtrim($string1,'&');
        $pkeyid = openssl_pkey_get_private($this->chackKey($params['privatekey'],false));
        if ($pkeyid===false){
            return error(-1,'提供的私钥格式不对');
        }
        $signature = '';
        //调用openssl内置签名方法，生成签名$signature
        openssl_sign($string1, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
        //释放资源
        openssl_free_key($pkeyid);
        $signature = base64_encode($signature);
        $public['sign'] = $signature;
        load()->func('communication');
        $url = EWEI_SHOPV2_DEBUG ? 'https://openapi.alipaydev.com/gateway.do' : 'https://openapi.alipay.com/gateway.do';
        if ($return !== null){
            return $public;
        }
        $response = ihttp_post($url, $public);
        $result = json_decode(iconv('GBK',"UTF-8//IGNORE",$response['content']),true);
        return $result;
    }

    public function chackKey($key,$public = true)
    {
        if (empty($key)){
            return $key;
        }
        if ($public){
            if (strexists($key,'-----BEGIN PUBLIC KEY-----')){
                $key = str_replace(array("-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----"), '',$key);
            }
            $head_end = "-----BEGIN PUBLIC KEY-----\n{key}\n-----END PUBLIC KEY-----";
        }else{
            if (strexists($key,'-----BEGIN RSA PRIVATE KEY-----')){
                $key = str_replace(array("-----BEGIN RSA PRIVATE KEY-----", "-----END RSA PRIVATE KEY-----"), '',$key);
            }
            $head_end = "-----BEGIN RSA PRIVATE KEY-----\n{key}\n-----END RSA PRIVATE KEY-----";
        }
        $key = str_replace(array("\r\n", "\r", "\n"), '',trim($key));
        $key=wordwrap($key,64,"\n",true);
        return str_replace("{key}",$key,$head_end);
    }

    /**
     *
     * 支付宝条码支付
     * @param $params  = array('out_trade_no' => 订单号,'auth_code' => 支付授权码,'total_amount' => 0.01 支付金额,'subject' => '标题','body' => '内容',);
     * @param $config = array('app_id' => ,'seller_id'=>'','privatekey' => "",'publickey' => "",'alipublickey' => "");
     * @return array|mixed
     */
    public function AliPayBarcode($params,$config)
    {
        global $_W;
        $biz_content = array();
        $biz_content['out_trade_no'] = $params['out_trade_no'];
        $biz_content['scene'] = 'bar_code';
        $biz_content['auth_code'] = $params['auth_code'];
        $biz_content['seller_id'] = $config['seller_id'];
        $biz_content['total_amount'] = $params['total_amount'];
        $biz_content['subject'] = $params['subject'];
        $biz_content['body'] = $params['body'];
        $biz_content['timeout_express'] = '90m';
        $biz_content = array_filter($biz_content);
        $config['method'] = 'alipay.trade.pay';
        $config['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/alipay/notify.php";
        $config['biz_content'] = json_encode($biz_content);
        $result = $this->publicAliPay($config);
        if (is_error($result)){
            return $result;
        }

        $key = str_replace('.','_',$config['method']).'_response';

        if ($result[$key]['code'] == '10000'){
            return $result[$key];
        }else{
            return error($result[$key]['code'],$result[$key]['msg'].":".$result[$key]['sub_msg']);
        }
    }

    /**
     *
     * 支付宝条码支付
     * @param $params  = array('out_trade_no' => 订单号,'seller_id'=>'','total_amount' => 0.01 支付金额,'subject' => '标题','body' => '内容',);
     * @param $config = array('app_id' => ,'seller_id'=>,'privatekey' => "",'publickey' => "",'alipublickey' => "");
     * @return array|mixed
     */
    public function AliPayWap($params,$config)
    {
        global $_W;
        $biz_content = array();
        $biz_content['out_trade_no'] = $params['out_trade_no'];
        $biz_content['seller_id'] = $config['seller_id'];
        $biz_content['total_amount'] = $params['total_amount'];
        $biz_content['subject'] = $params['subject'];
        $biz_content['body'] = $params['body'];
        $biz_content['product_code'] = 'QUICK_WAP_PAY';
        $biz_content['timeout_express'] = '90m';
        $biz_content = array_filter($biz_content);
        $config['method'] = 'alipay.trade.wap.pay';
        $config['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/alipay/notify.php";
        $config['biz_content'] = json_encode($biz_content);
        $result = $this->publicAliPay($config,1);
        return $result;
    }

    /**
     * 支付宝订单查询
     * @param $out_trade_no 订单号码
     * @param $config array('app_id' => ,'privatekey' => "",'publickey' => "",'alipublickey' => "");
     * @return array|mixed
     */
    public function AliPayQuery($out_trade_no,$config)
    {
        $biz_content = array();
        $biz_content['out_trade_no'] = $out_trade_no;
        $config['method'] = 'alipay.trade.query';
        $config['biz_content'] = json_encode($biz_content);
        $result = $this->publicAliPay($config);
        if (is_error($result)){
            return $result;
        }

        $key = str_replace('.','_',$config['method']).'_response';

        if ($result[$key]['code'] == '10000' && $result[$key]['trade_status'] == 'TRADE_SUCCESS'){
            return $result[$key];
        }else{
            if (!empty($result[$key]['trade_status']) && $result[$key]['trade_status'] == 'TRADE_CLOSED'){
                return error($result[$key]['code'],"该订单已经关闭或者已经退款");
            }
            return error($result[$key]['code'],$result[$key]['msg'].":".$result[$key]['sub_msg']);
        }
    }

    /**
     * 支付宝订单查询
     * @param $out_trade_no 订单号码
     * @param $config array('app_id' => ,'privatekey' => "",'publickey' => "",'alipublickey' => "");
     * @return array|mixed
     */
    public function AliPayRefundQuery($out_trade_no,$config)
    {
        $biz_content = array();
        $biz_content['out_trade_no'] = $out_trade_no;
        $biz_content['out_request_no'] = $out_trade_no;
        $config['method'] = 'alipay.trade.fastpay.refund.query';
        $config['biz_content'] = json_encode($biz_content);
        $result = $this->publicAliPay($config);
        if (is_error($result)){
            return $result;
        }
        $key = str_replace('.','_',$config['method']).'_response';

        if ($result[$key]['code'] == '10000' && $result[$key]['msg'] == 'Success'){
            return $result[$key];
        }else{
            return error($result[$key]['code'],$result[$key]['msg'].":".$result[$key]['sub_msg']);
        }
    }

    /**
     * 支付宝订单查询
     * @param $app_auth_token 啦啦啦
     * @param $config array('app_id' => ,'privatekey' => "",'publickey' => "",'alipublickey' => "");
     * @return array|mixed
     */
    public function AlipayOpenAuthTokenAppRequest($app_code,$config)
    {
        $biz_content = array();
        $biz_content['grant_type'] = 'authorization_code';
        $biz_content['code'] = $app_code;
        $config['method'] = 'alipay.open.auth.token.app';
        $config['biz_content'] = json_encode($biz_content);
        $result = $this->publicAliPay($config);

        if (is_error($result)){
            return $result;
        }
        $key = str_replace('.','_',$config['method']).'_response';

        if ($result[$key]['code'] == '10000' && $result[$key]['msg'] == 'Success'){
            return $result[$key];
        }else{
            return error($result[$key]['code'],$result[$key]['msg'].":".$result[$key]['sub_msg']);
        }
    }

    public function AlipayOpenAuthTokenAppQueryRequest($app_auth_token,$config)
    {
        $biz_content = array();
        $biz_content['app_auth_token'] = $app_auth_token;
        $config['method'] = 'alipay.open.auth.token.app.query';
        $config['biz_content'] = json_encode($biz_content);
        $result = $this->publicAliPay($config);

        if (is_error($result)){
            return $result;
        }
        $key = str_replace('.','_',$config['method']).'_response';

        if ($result[$key]['code'] == '10000' && $result[$key]['msg'] == 'Success'){
            return $result[$key];
        }else{
            return error($result[$key]['code'],$result[$key]['msg'].":".$result[$key]['sub_msg']);
        }
    }

    /**
     * 微信支付
     * @param $params
     * @param $wechat
     * @param int $type
     * @return array
     */
	function wechat_build($params, $wechat, $type = 0) {
		global $_W;
		$set = m('common')->getSysset('pay');
		$sec = m('common')->getSec();
		$sec = iunserializer($sec['sec']);
		if (!empty($set['weixin_sub'])){
			$wechat = array(
				'appid'=>$sec['appid_sub'],
				'mch_id'=>$sec['mchid_sub'],
				'sub_appid'=>!empty($sec['sub_appid_sub']) ? $sec['sub_appid_sub'] : '',
				'sub_mch_id'=>$sec['sub_mchid_sub'],
				'apikey' => $sec['apikey_sub']
			);
			$params['openid'] = isset($params['user']) ? $params['user'] : $_W['openid'];
			return $this->wechat_child_build($params,$wechat,$type);
		}
		load()->func('communication');
		if (empty($wechat['version']) && !empty($wechat['signkey'])) {
			$wechat['version'] = 1;
		}
		$wOpt = array();
		if ($wechat['version'] == 1) {
			$wOpt['appId'] = $wechat['appid'];
			$wOpt['timeStamp'] = TIMESTAMP . "";
			$wOpt['nonceStr'] = random(32);
			$package = array();
			$package['bank_type'] = 'WX';
			$package['body'] = urlencode($params['title']);
			$package['attach'] = $_W['uniacid'] . ':' . $type;
			$package['partner'] = $wechat['partner'];
			$package['device_info'] = "ewei_shopv2";
			$package['out_trade_no'] = $params['tid'];
			$package['total_fee'] = $params['fee'] * 100;
			$package['fee_type'] = '1';
			// $package['notify_url'] = $_W['siteroot'] . 'payment/wechat/notify.php';
			$package['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/wechat/notify.php";
			$package['spbill_create_ip'] = CLIENT_IP;
			//$package['time_start'] = date('YmdHis', TIMESTAMP);
			//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
			$package['input_charset'] = 'UTF-8';
			ksort($package);
			$string1 = '';
			foreach ($package as $key => $v) {
				if (empty($v)) {
					continue;
				}
				$string1 .= "{$key}={$v}&";
			}
			$string1 .= "key={$wechat['key']}";
			$sign = strtoupper(md5($string1));

			$string2 = '';
			foreach ($package as $key => $v) {
				$v = urlencode($v);
				$string2 .= "{$key}={$v}&";
			}
			$string2 .= "sign={$sign}";
			$wOpt['package'] = $string2;

			$string = '';
			$keys = array('appId', 'timeStamp', 'nonceStr', 'package', 'appKey');
			sort($keys);
			foreach ($keys as $key) {
				$v = $wOpt[$key];
				if ($key == 'appKey') {
					$v = $wechat['signkey'];
				}
				$key = strtolower($key);
				$string .= "{$key}={$v}&";
			}
			$string = rtrim($string, '&');

			$wOpt['signType'] = 'SHA1';
			$wOpt['paySign'] = sha1($string);
			return $wOpt;
		} else {
			$package = array();
			$package['appid'] = $wechat['appid'];
			$package['mch_id'] = $wechat['mchid'];
			$package['nonce_str'] = random(32);
			$package['body'] = $params['title'];
			$package['device_info'] = "ewei_shopv2";
			$package['attach'] = $_W['uniacid'] . ':' . $type;
			$package['out_trade_no'] = $params['tid'];
			$package['total_fee'] = $params['fee'] * 100;
			$package['spbill_create_ip'] = CLIENT_IP;
			if ( !empty($params["goods_tag"]) )
				$package['goods_tag'] = $params['goods_tag'];  //商品标记
			//$package['time_start'] = date('YmdHis', TIMESTAMP);
			//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
			// $package['notify_url'] = $_W['siteroot'] . 'payment/wechat/notify.php';
			$package['notify_url'] = $_W['siteroot'] . "addons/ewei_shopv2/payment/wechat/notify.php";
			$package['trade_type'] = 'JSAPI';
			$package['openid'] = empty($params['openid']) ? $_W['openid'] : $params['openid'];
			ksort($package, SORT_STRING);
			$string1 = '';
			foreach ($package as $key => $v) {
				if (empty($v)) {

					continue;
				}

				$string1 .= "{$key}={$v}&";
			}
			$string1 .= "key={$wechat['signkey']}";
			$package['sign'] = strtoupper(md5($string1));
			$dat = array2xml($package);
			$response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
			if (is_error($response)) {
				return $response;
			}
			$xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
			if (strval($xml->return_code) == 'FAIL') {
				return error(-1, strval($xml->return_msg));
			}
			if (strval($xml->result_code) == 'FAIL') {
				return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
			}
			$prepayid = $xml->prepay_id;
			$wOpt['appId'] = $wechat['appid'];
			$wOpt['timeStamp'] = TIMESTAMP.'';
			$wOpt['nonceStr'] = random(32);
			$wOpt['package'] = 'prepay_id=' . $prepayid;
			$wOpt['signType'] = 'MD5';
			ksort($wOpt, SORT_STRING);
			$string = '';
			foreach ($wOpt as $key => $v) {
				$string .= "{$key}={$v}&";
			}
			$string .= "key={$wechat['signkey']}";
			$wOpt['paySign'] = strtoupper(md5($string));
			return $wOpt;
		}
	}

	function wechat_child_build($params, $wechat, $type = 0) {
		global $_W;
		load()->func('communication');
		$package = array();
		$package['appid'] = $wechat['appid'];
		$package['mch_id'] = $wechat['mch_id'];
		$package['sub_mch_id'] = $wechat['sub_mch_id'];  //子商户的mchid
		$package['nonce_str'] = random(32);
		$package['body'] = $params['title'];
		$package['device_info'] = isset($params['device_info'])?"ewei_shopv2:".$params['device_info']:"ewei_shopv2";
		$package['attach'] = (isset($params['uniacid']) ? $params['uniacid'] : $_W['uniacid']) . ':' . $type;
		$package['out_trade_no'] = $params['tid'];
		$package['total_fee'] = $params['fee'] * 100;
		$package['spbill_create_ip'] = CLIENT_IP;
		$package['product_id'] = $params['goods_id'];
		if ( !empty($params["goods_tag"]) )
			$package['goods_tag'] = $params['goods_tag'];  //商品标记，代金券或立减优惠功能的参数
		$package['time_start'] = date('YmdHis', TIMESTAMP);
		$package['time_expire'] = date('YmdHis', TIMESTAMP + 3600);
		$package['notify_url'] = empty($params['notify_url']) ? $_W['siteroot'] . "addons/ewei_shopv2/payment/wechat/notify.php" : $params['notify_url'];
		$package['trade_type'] = 'JSAPI';

		if ( !empty($wechat["sub_appid"]) ){
			$package['sub_appid'] = $wechat['sub_appid'];  //子商户appid
			$package['sub_openid'] = $params['openid'];  //子商户所对应的openid
		}else{
			$package['openid'] = $params['openid'];
		}
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			}
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key={$wechat['apikey']}";
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
		$response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
		if (is_error($response)) {
			return $response;
		}
		$xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return error(-1, strval($xml->return_msg));
		}
		if (strval($xml->result_code) == 'FAIL') {
			return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
		}
		libxml_disable_entity_loader(true);
		$prepayid = $xml->prepay_id;
		$wOpt = array(
			'appId'=> $wechat['appid'],
			'timeStamp'=> TIMESTAMP.'',
			'nonceStr'=> random(32),
			'package'=> 'prepay_id=' . $prepayid,
			'signType'=> 'MD5'
		);
		ksort($wOpt, SORT_STRING);
		$string = '';
		foreach ($wOpt as $key => $v) {
			$string .= "{$key}={$v}&";
		}
		$string .= "key={$wechat['apikey']}";
		$wOpt['paySign'] = strtoupper(md5($string));
		return $wOpt;
	}

	function wechat_native_build($params, $wechat, $type = 0,$diy = null) {
		global $_W;
        if ($diy === null){
            $set = m('common')->getSysset('pay');
            $sec = m('common')->getSec();
            $sec = iunserializer($sec['sec']);
            if (!empty($set['weixin_jie_sub'])){
                $wechat = array(
                    'appid'=>$sec['appid_jie_sub'],
                    'mch_id'=>$sec['mchid_jie_sub'],
                    'sub_appid'=>!empty($sec['sub_appid_jie_sub']) ? $sec['sub_appid_jie_sub'] : '',
                    'sub_mch_id'=>$sec['sub_mchid_jie_sub'],
                    'apikey' => $sec['apikey_jie_sub']
                );
                return $this->wechat_native_child_build($params,$wechat,$type);
            }
        }
        if (!empty($params['openid'])){
            $wechat['version'] = 2;
            $wechat['signkey'] = $wechat['apikey'];
            $wechat['mch_id'] = $wechat['mchid'];
            return $this->wechat_build($params,$wechat,$type);
        }
		$package = array();
		$package['appid'] = $wechat['appid'];
		$package['mch_id'] = $wechat['mchid'];
		$package['nonce_str'] = random(32);
		$package['body'] = $params['title'];
		$package['device_info'] = isset($params['device_info'])?"ewei_shopv2:".$params['device_info']:"ewei_shopv2";
		$package['attach'] = (isset($params['uniacid']) ? $params['uniacid'] : $_W['uniacid']) . ':' . $type;
		$package['out_trade_no'] = $params['tid'];
		$package['total_fee'] = $params['fee'] * 100;
		$package['spbill_create_ip'] = CLIENT_IP;
		$package['product_id'] = $params['tid'];
		if ( !empty($params["goods_tag"]) )
			$package['goods_tag'] = $params['goods_tag'];  //商品标记，代金券或立减优惠功能的参数
		$package['time_start'] = date('YmdHis', TIMESTAMP);
		$package['time_expire'] = date('YmdHis', TIMESTAMP + 3600);
		$package['notify_url'] = empty($params['notify_url']) ? $_W['siteroot'] . "addons/ewei_shopv2/payment/wechat/notify.php" : $params['notify_url'];
		$package['trade_type'] = 'NATIVE';
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			}
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key={$wechat['apikey']}";
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
		if (is_error($response)) {
			return $response;
		}
        libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return error(-1, strval($xml->return_msg));
		}
		if (strval($xml->result_code) == 'FAIL') {
			return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
		}
		$result = json_decode(json_encode($xml), true);
		return $result;
	}

	function wechat_native_child_build($params, $wechat, $type = 0) {
		global $_W;
        if (!empty($params['openid'])){
            return $this->wechat_child_build($params,$wechat,$type);
        }
		$package = array();
		$package['appid'] = $wechat['appid'];
		if ( !empty($wechat["sub_appid"]) )
			$package['sub_appid'] = $wechat['sub_appid'];  //子商户appid
		$package['mch_id'] = $wechat['mch_id'];
		$package['sub_mch_id'] = $wechat['sub_mch_id'];
		$package['nonce_str'] = random(32);
		$package['body'] = $params['title'];
		$package['device_info'] = isset($params['device_info'])?"ewei_shopv2:".$params['device_info']:"ewei_shopv2";
		$package['attach'] = (isset($params['uniacid']) ? $params['uniacid'] : $_W['uniacid']) . ':' . $type;
		$package['out_trade_no'] = $params['tid'];
		$package['total_fee'] = $params['fee'] * 100;
		$package['spbill_create_ip'] = CLIENT_IP;
		$package['product_id'] = $params['tid'];
		if ( !empty($params["goods_tag"]) )
			$package['goods_tag'] = $params['goods_tag'];  //商品标记，代金券或立减优惠功能的参数
		$package['time_start'] = date('YmdHis', TIMESTAMP);
		$package['time_expire'] = date('YmdHis', TIMESTAMP + 3600);
		$package['notify_url'] = empty($params['notify_url']) ? $_W['siteroot'] . "addons/ewei_shopv2/payment/wechat/notify.php" : $params['notify_url'];
		$package['trade_type'] = 'NATIVE';

		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			}
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key={$wechat['apikey']}";
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
		if (is_error($response)) {
			return $response;
		}
        libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return error(-1, strval($xml->return_msg));
		}
		if (strval($xml->result_code) == 'FAIL') {
			return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
		}
		$result = json_decode(json_encode($xml), true);
		return $result;
	}

	function authCodeToOpenid($auth_code,$wechat)
    {
        $package = array();
        $package['appid'] = $wechat['appid'];
        $package['mch_id'] = $wechat['mch_id'];
        $package['auth_code'] = $auth_code;
        $package['nonce_str'] = random(32);
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key={$wechat['apikey']}";
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_post('https://api.mch.weixin.qq.com/tools/authcodetoopenid', $dat);
        if (is_error($response)) {
            return $response;
        }
        libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if (strval($xml->return_code) == 'FAIL') {
            return error(-1, strval($xml->return_msg));
        }
        if (strval($xml->result_code) == 'FAIL') {
            return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
        }
        $result = json_decode(json_encode($xml), true);
        return $result;
    }

    /**
     * @param $params
     * $params = array(
            'openid'=>'',  openid
            'tid'=>'',订单编号
            'send_name'=>'', 发送红包的人  或者  红包事件
            'money'=>'', 发送红包金额 最低 1元
            'wishing'=>'', 祝福语  : 感谢您关注
            'act_name'=>'  ',  参与活动名臣
            'remark'=>'', 备注信息 居然是必填!!!
        );
     * @param $wechat
     *$wechat = array(
            'appid' => '', appid
            'mchid' => '', mchid
            'apikey' => '',apikey
            'certs' => array(
                'cert'=>'',证书内容
                'key'=>'',证书内容
                'root'=>'' 证书内容
            )
        );
     * @return array|bool
     */
    public function sendredpack($params,$wechat)
    {
        global $_W;
        $package = array();
        $package['wxappid'] = $wechat['appid'];
        $package['mch_id'] = $wechat['mchid'];
        $package['mch_billno'] = $params['tid'];
        $package['send_name'] = $params['send_name'];
        $package['nonce_str'] = random(32);
        $package['re_openid'] = $params['openid'];
        $package['total_amount'] = $params['money']*100; //付款金额 单位分
        $package['total_num'] = 1; //发放总人数 单位分
        $package['wishing'] = isset($params['wishing']) ? $params['wishing'] : '恭喜发财,大吉大利'; //祝福语
        $package['client_ip'] = CLIENT_IP;
        $package['act_name'] = $params['act_name']; //活动名称
        $package['remark'] = isset($params['remark']) ? $params['remark'] : '暂无备注'; //备注信息
        $package['scene_id'] = isset($params['scene_id']) ? $params['scene_id'] : 'PRODUCT_1'; //备注信息

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';

        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key=" . $wechat['apikey'];
        $package['sign'] = strtoupper(md5($string1));

        $xml = array2xml($package);
        $extras = array();

        $errmsg = "未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!";
        if (is_array($wechat['certs'])) {

            if (empty($wechat['certs']['cert']) || empty($wechat['certs']['key']) || empty($wechat['certs']['root'])) {
                if ($_W['ispost']) {
                    show_json(0, array('message' => $errmsg));
                }
                show_message($errmsg, '', 'error');
            }

            $certfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($certfile, $wechat['certs']['cert']);
            $keyfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($keyfile, $wechat['certs']['key']);
            $rootfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($rootfile, $wechat['certs']['root']);

            $extras['CURLOPT_SSLCERT'] = $certfile;
            $extras['CURLOPT_SSLKEY'] = $keyfile;
            $extras['CURLOPT_CAINFO'] = $rootfile;
        } else {
            if ($_W['ispost']) {
                show_json(0, array('message' => $errmsg));
            }
            show_message($errmsg, '', 'error');
        }

        load()->func('communication');
        $resp = ihttp_request($url, $xml, $extras);
        @unlink($certfile);
        @unlink($keyfile);
        @unlink($rootfile);
        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            if ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS') {
                return true;
            } else {
                if ($arr['return_msg'] == $arr['err_code_des']) {
                    $error = $arr['return_msg'];
                } else {
                    $error = $arr['return_msg']. " | " . $arr['err_code_des'];
                }
                return error(-2, $error);
            }
        }
    }

    /**
     * @param $params
     * @param $wechat
     * @param int $type
     * @return array|mixed
     *
     * $params = array(
        'title' => '刷卡消费',
        'tid' => '',
        'fee' => 0.01,
        'auth_code' => '',
        );

        $wechat = array(
        'appid' => '',
        'mchid' => '',
        'apikey' => '',
        );
     */

    function wechat_micropay_build($params, $wechat, $type = 0) {
        global $_W;
        load()->func('communication');
        $package = array();
        $package['appid'] = $wechat['appid'];
        $package['mch_id'] = $wechat['mch_id'];
        $package['nonce_str'] = random(32);
        $package['body'] = $params['title'];
        $package['device_info'] = isset($params['device_info'])?"ewei_shopv2:".$params['device_info']:"ewei_shopv2";
        $package['attach'] = (isset($params['uniacid']) ? $params['uniacid'] : $_W['uniacid']) . ':' . $type;
        $package['out_trade_no'] = $params['tid'];
        $package['total_fee'] = $params['fee'] * 100;
        $package['spbill_create_ip'] = CLIENT_IP;
        $package['auth_code'] = $params['auth_code'];
        if (!empty($wechat['sub_mch_id'])){
            $package['sub_mch_id'] = $wechat['sub_mch_id'];
        }
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key={$wechat['apikey']}";
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/micropay', $dat);
        if (is_error($response)) {
            return $response;
        }
        $xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode($xml), true);
        if ($result['return_code'] == 'FAIL') {
            return error(-1, $result['return_msg']);
        }
        if ($result['result_code'] == 'FAIL') {
            return error(-2, $result['err_code'] . ': ' . $result['err_code_des']);
        }
        return $result;
    }

    /**
     * @param $out_trade_no
     * @param $money
     * @param $wechat = array(
                        'appid' => '',
                        'mchid' => '',
                        'apikey' => '',
                        );
     * @param bool $app
     * @return array|mixed
     */
    public function wechat_order_query($out_trade_no,$money = 0,$wechat)
    {
        $package = array();
        $package['appid'] = $wechat['appid'];
        $package['mch_id'] = $wechat['mch_id'];
        $package['nonce_str'] = random(32);
        $package['out_trade_no'] = $out_trade_no;
        if (!empty($wechat['sub_mch_id'])){
            $package['sub_mch_id'] = $wechat['sub_mch_id'];
        }
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key={$wechat['apikey']}";
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);
        load()->func('communication');
        $response = ihttp_request('https://api.mch.weixin.qq.com/pay/orderquery', $dat);
        if (is_error($response)) {
            return $response;
        }
        $xml = simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
        if (strval($xml->return_code) == 'FAIL') {
            return error(-1, strval($xml->return_msg));
        }
        if (strval($xml->result_code) == 'FAIL') {
            return error(-2, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
        }
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode($xml), true);
        if($result['total_fee']!=$money*100 && $money != 0){
            //判断金额
            return error(-1, '金额出错');
        }
        return $result;
    }

	//获取微信account
	public function getAccount() {
		global $_W;
		load()->model('account');
		if (!empty($_W['acid'])) {
			return WeAccount::create($_W['acid']);
		} else {
			$acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE `uniacid`=:uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
			return WeAccount::create($acid);
		}
		return false;
	}
//
//	public function shareAddress($url = '') {
//		global $_W, $_GPC;
//		$appid = $_W['account']['key'];
//		$secret = $_W['account']['secret'];
//		load()->func('communication');
//		$url = $_W['siteroot'] . "app/index.php?" . $_SERVER['QUERY_STRING'];
//		if (empty($_GPC['code'])) {
//			$oauth2_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
//			header("location: $oauth2_url");
//			exit();
//		}
//
//		$code = $_GPC['code'];
//		$token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
//		$resp = ihttp_get($token_url);
//
//
//		$token = @json_decode($resp['content'], true);
//		if (empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['openid'])) {
//			return false;
//		}
//		$account = $this->getAccount();
//		echo json_encode($_W['account']);
//		echo "<br/>";
//		exit($token['access_token']);
//
//		$package = array(
//			"appid" => $appid,
//			"url" => $url,
//			'timestamp' => time() . "",
//			'noncestr' => random(8, true) . "",
//			'accesstoken' => $token['access_token']
//		);
//		ksort($package, SORT_STRING);
//		$addrSigns = array();
//		foreach ($package as $k => $v) {
//			$addrSigns[] = "{$k}={$v}";
//		}
//		$string = implode('&', $addrSigns);
//		$addrSign = strtolower(sha1(trim($string)));
//		$data = array(
//			"appId" => $appid,
//			"scope" => "jsapi_address",
//			"signType" => "sha1",
//			"addrSign" => $addrSign,
//			"timeStamp" => $package['timestamp'],
//			"nonceStr" => $package['noncestr']
//		);
//		return $data;
//	}

	//生成单号
	public function createNO($table, $field, $prefix) {

		$billno = date('YmdHis') . random(2, true);
		while (1) {
			$count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_' . $table) . " where {$field}=:billno limit 1", array(':billno' => $billno));
			if ($count <= 0) {
				break;
			}
			$billno = date('YmdHis') . random(2, true);
		}
		return $prefix . $billno;
	}

	public function html_images($detail = '',$enforceQiniu = false) {

		$detail = htmlspecialchars_decode($detail);
		preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]?))[\\\'|\\\"].*?[\/]?>/", $detail, $imgs);
		$images = array();
		if (isset($imgs[1])) {

			foreach ($imgs[1] as $img) {
				$im = array(
					"old" => $img,
					"new" => save_media($img,$enforceQiniu)
				);
				$images[] = $im;
			}
		}
		foreach ($images as $img) {
			$detail = str_replace($img['old'], $img['new'], $detail);
		}
		return $detail;
	}

    public function html_to_images($detail = '') {

        $detail = htmlspecialchars_decode($detail);
        preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]?))[\\\'|\\\"].*?[\/]?>/", $detail, $imgs);
        $images = array();
        if (isset($imgs[1])) {

            foreach ($imgs[1] as $img) {
                $im = array(
                    "old" => $img,
                    "new" => tomedia($img)
                );
                $images[] = $im;
            }
        }
        foreach ($images as $img) {
            $detail = str_replace($img['old'], $img['new'], $detail);
        }
        return $detail;
    }

	public function array_images($arr) {

		foreach ($arr as &$a) {
			$a = save_media($a);
		}
		unset($a);
		return $arr;
	}

	public function getSec($uniacid = 0) {
		global $_W;
		if (empty($uniacid)) {
			$uniacid = $_W['uniacid'];
		}
		$set = pdo_fetch("select sec from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $uniacid));
		if (empty($set)) {
			$set = array();
		}
		return $set;
	}

	public function paylog($log = '') {
		global $_W;
		$openpaylog = m('cache')->getString('paylog', 'global');
		if (!empty($openpaylog)) {
			$path = IA_ROOT . "/addons/ewei_shopv2/data/paylog/" . $_W['uniacid'] . "/" . date('Ymd');
			if (!is_dir($path)) {
				load()->func('file');
				@mkdirs($path, '0777');
			}
			$file = $path . "/" . date('H') . '.log';
			file_put_contents($file, $log, FILE_APPEND);
		}
	}

	public function getAreas()
	{
		$file = IA_ROOT . "/addons/ewei_shopv2/static/js/dist/area/Area.xml";
		$file_str = file_get_contents($file);
		$areas = json_decode(json_encode(simplexml_load_string($file_str)), true);
		return $areas;
	}

	public function getWechats() {
		return pdo_fetchall("SELECT  a.uniacid,a.name FROM " . tablename('ewei_shop_sysset') . " s  "
			. " left join " . tablename('account_wechats') . " a on a.uniacid = s.uniacid"
			. " left join " . tablename('account') . " acc on acc.uniacid = a.uniacid where acc.isdeleted=0 GROUP BY uniacid");
	}

	public function getCopyright($ismanage = false) {
		global $_W;
		$copyrights = m('cache')->getArray('systemcopyright', 'global');
		if (!is_array($copyrights)) {
			$copyrights = pdo_fetchall('select *  from ' . tablename('ewei_shop_system_copyright'));
			m('cache')->set('systemcopyright', $copyrights, 'global');
		}

		$copyright = false;
		foreach ($copyrights as $cr) {
			if ($cr['uniacid'] == $_W['uniacid']) {
				if ($ismanage && $cr['ismanage'] == 1) {
					$copyright = $cr;
					break;
				}
				if (!$ismanage && $cr['ismanage'] == 0) {
					$copyright = $cr;
					break;
				}
			}
		}
		if (!$copyright) {
			foreach ($copyrights as $cr) {
				if ($cr['uniacid'] == -1) {
					if ($ismanage && $cr['ismanage'] == 1) {
						$copyright = $cr;
						break;
					}
					if (!$ismanage && $cr['ismanage'] == 0) {
						$copyright = $cr;
						break;
					}
				}
			}
		}


		return $copyright;
	}

	public function keyExist($key = '') {
		global $_W;
		if (empty($key)) {
			return;
		}
		$keyword = pdo_fetch("SELECT * FROM " . tablename('rule_keyword') . " WHERE content=:content and uniacid=:uniacid limit 1 ", array(':content' => trim($key), ':uniacid' => $_W['uniacid']));
		if (!empty($keyword)) {
			$rule = pdo_fetch("SELECT * FROM " . tablename('rule') . " WHERE id=:id and uniacid=:uniacid limit 1 ", array(':id' => $keyword['rid'], ':uniacid' => $_W['uniacid']));
			if (!empty($rule)) {
				return $rule;
			}
		}
	}

	// 生成静态
	public function createStaticFile($url, $regen = false) {
		global $_W;

		if (empty($url)) {
			return;
		}
		$url = preg_replace('/(&|\?)mid=[^&]+/', "", $url);
		$cache = md5($url) . "_html";
		$content = m('cache')->getString($cache);
		if (empty($content) || $regen) {
			load()->func('communication');
			$resp = ihttp_request($url,array('site'=>'createStaticFile'));
			$content = $resp['content'];
			m('cache')->set($cache, $content);
		}
		return $content;
	}

	public function delrule($rids)
	{
		if(!is_array($rids)) {
			$rids = array($rids);
		}
		foreach($rids as $rid) {
			$rid = intval($rid);
			load()->model('reply');
			$reply = reply_single($rid);
			if (pdo_delete('rule', array('id' => $rid))) {
				pdo_delete('rule_keyword', array('rid' => $rid));
				pdo_delete('stat_rule', array('rid' => $rid));
				pdo_delete('stat_keyword', array('rid' => $rid));
				$module = WeUtility::createModule($reply['module']);
				if (method_exists($module, 'ruleDeleted')) {
					$module->ruleDeleted($rid);
				}
			}
		}
	}

    public function deleteFile($attachment,$fileDelete = false)
    {
        global $_W;
        $attachment = trim($attachment);
        if(empty($attachment)){
            return false;
        }
        $media = pdo_get('core_attachment', array('uniacid' => $_W['uniacid'], 'attachment' => $attachment));
        if(empty($media)) {
            return false;
        }
        if(empty($_W['isfounder']) && $_W['role'] != 'manager') {
            return false;
        }
        if ($fileDelete){
            load()->func('file');
            if (!empty($_W['setting']['remote']['type'])) {
                $status = file_remote_delete($media['attachment']);
            } else {
                $status = file_delete($media['attachment']);
            }
            if(is_error($status)) {
                exit($status['message']);
            }
        }
        pdo_delete('core_attachment', array('uniacid' => $_W['uniacid'], 'id' => $media['id']));
        return true;
	}

}
