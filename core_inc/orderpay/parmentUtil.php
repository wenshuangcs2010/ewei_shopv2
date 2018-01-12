<?php
/**
* 
*/
class ParmentUtil
{
	
	function __construct()
	{
		# code...
	}
 	var $_relanmeApi="https://mgw.shengpay.com/auc-web/api/authentication/idcard_two_apply";
	function isRealName($realname,$idno,$openid=""){
		$realname=addslashes(trim($realname));
		$idcardno=addslashes(trim($idno));

        $retdate=m("cnbuyerdb")->getRelanme($realname,$idcardno);
        if(!empty($retdate)){
			return $retdate;
        }
        $idnocount=m("cnbuyerdb")->getimidList($idcardno);
       
        if($idnocount!=0 && $idnocount>5){
            return array('isverify'=>-2,'responseMessage'=>"身份证号码被锁定");
        }
        $merchantOrderNo="T".time();
        $returndata=$this->realeName($merchantOrderNo,$realname,$idcardno);
        if($returndata===false){
        	return false;
        }
        if($returndata['responseCode']==2){
        	return false;
        }
        if($returndata['responseCode']=="BUSINESS_EXCEPTION"){
        	return false;
        }
        $data=array("realname"=>$realname,"idno"=>$idcardno,'isverify'=>$returndata['responseCode'],'merchantOrderNo'=>$merchantOrderNo,'responseMessage'=>$returndata['responseMessage']);
		$ret=m("cnbuyerdb")->insertRelanme($data);
		if($ret){
			return $data;
		}
		return false;
    }
    function realeName($merchantOrderNo,$realname,$idno){
        $this->payer_config=array("shengpay_account"=>10114414,'shengpay_key'=>"zjcof85779533hjs");
        $merchantNo=$this->payer_config['shengpay_account'];
        $md5_salt=$this->payer_config['shengpay_key'];
        $bodyData['signType']="MD5";
        $bodyData['merchantNo']=$merchantNo;
        $bodyData['charset']="UTF-8";
        $bodyData['requestTime']=date('YmdHis');
        $bodyData['merchantOrderNo'] = $merchantOrderNo;
        $bodyData['trueName'] = $realname;
        $bodyData['identityNo'] = $idno;
        $data[] = "Content-Type:application/x-www-form-urlencoded;charset=utf-8";
        $data[] = "signType:MD5";
        $data[] = "signMsg:".strtoupper(md5(http_build_query($bodyData).$md5_salt));
        $option['header'] = $data;
        $return_code=$this->_curl_php_post($this->_relanmeApi,"POST",$bodyData,$option);
        $return_code=json_decode($return_code,true);
        return $return_code;
    }
    /**
     * 请求API数据
     * @param 请求API数据 $posturl
     * @return unknown
     */
    function _curl_php_post($url, $method = 'GET', $data_fields = array(), $option = array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        if (!empty($option['header'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $option['header']);
        }
        if (!empty($option['useragent'])) {
            curl_setopt($ch, CURLOPT_USERAGENT, $option['useragent']);
        }
        if (!empty($option['referer'])) {
            curl_setopt($ch, CURLOPT_REFERER, $option['referer']);
        }
        if (isset($option['cookiejar']) && file_exists($option['cookiejar'])) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $option['cookiejar']);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $option['cookiejar']);
        }
        if (!empty($option['cookie'])) {
            curl_setopt($ch, CURLOPT_COOKIE, $option['cookie']);
        }
        if (!empty($option['proxy'])) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($ch, CURLOPT_PROXY, $option['proxy']);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }

        if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        // 设置查询数据
        switch ($method) {
        case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data_fields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_fields));
        }
        break;
        case 'GET':
        default:
            // 如果为GET方法，将$data_fields转换成查询字符串后附加到$url后面
            $join_char = strpos($url, '?') === false ? '?' : '&';
            $url .= $join_char . http_build_query($data_fields);
            break;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        if (parse_url($url, PHP_URL_SCHEME) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $_time_start = microtime(true);
        $response = curl_exec($ch);

        if ($method == 'POST') {
            $action = "POST $url with " . json_encode($data_fields);
        } else {
            $action = "GET $url";
        }

        $http_info = curl_getinfo($ch);

        $log_attrs = array('total_time', 'namelookup_time', 'connect_time', 'pretransfer_time', 'starttransfer_time');
        $log_info = array();
        foreach ($log_attrs as $key) {
            $log_info[$key] = $http_info[$key];
        }

        if ($errno = curl_errno($ch)) {
            $error = curl_error($ch);
            $log_str = "Error at $action (errno:$errno, error:$error)";
            return false;
        }
        $last_url = $http_info['url'];
        $http_code = $http_info['http_code'];
        $url_log = $url!=$last_url ? ", url:$last_url" : '';
        if ($http_code != 200) {
            $log_str = "Error at HTTP_STATUS $url (http_code:$http_code {$url_log})";
            return false;
        }
        curl_close($ch);
        unset($ch);
        return $response;
    }
    //默认为1.0
        var $version='1.0';
        //Transfer-转账，Billing-代扣
        var $InterfaceType='Billing';

        var $_Payeskey = 'zjcof85779533hjs';
        //接口编号
        var $AppId;
        var $_customs_api_trans = 'http://mas.shengpay.com/api-acquire-channel/services/trans';
        //转账请求
        var $ReqBody=array(
            'Amount'=>'',
            'Currency'=>'Rmb',
            'MerchantOrderId'=>'',
            'Payer'=>array('MemberId'=>'','MemberIdType'=>"PtId"),
            'ToPayer'=>array('MemberId'=>'10114414@sfb.mer','MemberIdType'=>"PtId"),
            'Ext'=>'',
            );
        //调用方机器名
        var $MachineName="202.107.203.24";
        var $_Machine="www.cnbuyers.cn";
        //商户号
        var $MerchantNo='10114414';
        //摘要信息
        var $Summary="宁波畅购天下";
        //签名类型
        var $SignType="2";
        //签名串
        var $Mac;
        //扩展字段
        var $_customs_api   =  'http://mas.shengpay.com/api-acquire-channel/services/trans?wsdl';  //代发代扣网关
        private function soap_clice(){
            if(!get_extension_funcs("soap")){
                return false;
            }
         
            ini_set('soap.wsdl_cache_enabled', 0);
            try {
                $options = array(
                    'trace'=>true,
                    'cache_wsdl'=>WSDL_CACHE_NONE,
                    'soap_version'=> SOAP_1_1
                );
                $client = new SoapClient($this->_customs_api,$options);
               // $client = new SoapClient(null,array('location'=>$this->$_customs_api_trans,'uri' => 'trans'));
                $client->soap_defencoding = 'UTF-8';
                $client->xml_encoding = 'UTF-8';
                $param = array(
                    'Version' =>$this->version,
                    'InterfaceType' =>$this->InterfaceType,
                    'AppId' =>$this->AppId,
                    'MerchantNo' =>$this->MerchantNo,
                    'ReqBody' =>$this->ReqBody,
                    'MachineName' =>$this->_Machine,
                    'Summary' =>$this->Summary,
                    'SignType' =>$this->SignType,
                    'Mac' =>$this->Mac,
                    );
                //$transResponse = $client->__soapCall('Transfer', array(array('request'=>$param)),array('location' =>$this->_customs_api_trans)); //以数组形式传递params
                //var_dump($transResponse);
                //die();
                $respTrans_s = $transResponse->TransferResult;
                return $respTrans_s;
            } catch (SOAPFault $e) {
                print $e;
            }
        }
        function test(){
            require_once EWEI_SHOPV2_TAX_CORE. 'lib/nusoap.php';
            $client = new soapclient ('http://www.webxml.com.cn/WebServices/WeatherWebService.asmx?wsdl','wsdl');
            $client->soap_defencoding='UTF-8';
            $client->decode_utf8=false;
            $client->xml_encoding='UTF-8';
            $proxy = $client->getProxy();
            $sq = $proxy->getSupportCity(array('byProvinceName'=>'浙江'));
            // 参数转为数组形式传递
           // $paras = array ('byProvinceName ' => '' );
            // 目标方法没有参数时，可省略后面的参数
            //$result = $client->call('getSupportCity',$paras);
            // 检查错误，获取返回值
            if (! $err = $proxy->getError()) {
                echo " 返回结果： ".var_dump($sq);
            } else {
                echo " 调用出错： ".$err;
            }  
        }


         private function nosoap_clice(){
            //$this->test();
            //die();
            require_once EWEI_SHOPV2_TAX_CORE. 'lib/nusoap.php';
                $client = new SoapClient($this->_customs_api,"wsdl");
                $client->soap_defencoding = 'UTF-8';
                $client->decode_utf8 = false;
                $client->xml_encoding = 'UTF-8';
                $client->xml_encoding = 'UTF-8';
                $client->debugLevel=1;
                $param = array(
                    'Version' =>$this->version,
                    'InterfaceType' =>$this->InterfaceType,
                    'AppId' =>$this->AppId,
                    'MerchantNo' =>$this->MerchantNo,
                    'ReqBody' =>$this->ReqBody,
                    'MachineName' =>$this->_Machine,
                    'Summary' =>$this->Summary,
                    'SignType' =>$this->SignType,
                    'Mac' =>$this->Mac,
                    );
                //dump($param);
                //die();
                $transResponse = $client->call('Transfer',array("request"=>$param),$this->_customs_api_trans); //以数组形式传递params
                if ($err = $client->getError ()) {
                      return false;
                }
                $respTrans_s = $transResponse['TransferResult'];
                return $respTrans_s;
        }
        private function getSignStr(){
            $signMessage=$this->version."|".
                $this->InterfaceType."|".
                $this->MerchantNo."|".
                $this->AppId."|".
                $this->ReqBody['Amount']."|".
                strtoupper($this->ReqBody['Currency'])."|".
                $this->ReqBody['MerchantOrderId']."|".
                $this->ReqBody['Payer']['MemberId']."|".
                strtoupper($this->ReqBody['Payer']['MemberIdType'])."|".
                $this->ReqBody['ToPayer']['MemberId']."|".
                strtoupper($this->ReqBody['ToPayer']['MemberIdType'])."|".
                $this->_Machine."|".
                $this->Summary.
                $this->_Payeskey;
                //rdump($signMessage);
            $sign= mb_convert_encoding($signMessage, 'gbk', 'utf-8');
            return strtoupper(md5($sign));
        }

        /**
         * [_transPay description]
         * @param  [type] $order_info     订单
         * @param  [type] $Payer_MemberId 付款方 盛付通账号
         * @return [type]                 [description]
         */
        function _transPay($order_info,$Payer_MemberId="10114402"){
            if($order_info['deductcredit2']>0){
                $order_info['price']=$order_info['price']+$order_info['deductcredit2'];
            }
            //var_dump($order_info);
            $this->ReqBody['Amount']=sprintf("%.2f", $order_info['price']);
            $this->ReqBody['MerchantOrderId']=$order_info['ordersn'];
            $this->ReqBody['Payer']['MemberId']=$Payer_MemberId."@sfb.mer";
            $address=unserialize($order_info['address']);
            $Ext=array(
                "Kvp"=>array(
                    array("Key"=>"invokeIp","Value"=>$this->MachineName),
                    array("Key"=>"idNo","Value"=>$order_info['imid']),
                    array("Key"=>"realName","Value"=>$order_info['realname']),
                    array("Key"=>"mobile","Value"=>$address['mobile']),
                    ),
                );
            $this->ReqBody['Ext']=$Ext;
            $this->Mac=$this->getSignStr();
            return $this->nosoap_clice();
            //return $this->_curl_cnbuyerapi_pay($order_info);
        }
        private function _curl_cnbuyerapi_pay($order_info){
            $appid="ueNzpS61848";
            $appscrice="4b6c573fa9d74159";
            $address=unserialize($order_info['address']);
            $order=array(
                'order_sn'=>$order_info['ordersn'],
                'im_id'=>$order_info['imid'],
                'real_name'=>$order_info['realname'],
                'order_amount'=>$order_info['price'],
                'phone_mob'=>$address['mobile'],
                );
            ksort($order);
            $order['sig']=strtoupper(md5(http_build_query($order).$appscrice));
            $url="www.cnbuyers.cn/index.php?app=webService&act=we7_sheng_pay&app_id={$appid}";
            $retdate=$this->_curl_php_post($url,"POST",$order);
            dump($retdate);
        }

}