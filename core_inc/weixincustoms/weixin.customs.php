<?php

/**
 *    微信报关接口
 *
 *    @author    Garbin
 *    @usage    none
 */

class Weixincustoms
{
    /* 网关 */
    var $_gateway   =   'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';
	var $_sign_type = 'MD5';
	var $_input_charset = 'utf-8';
	//var $_cacert = getcwd().'\\cacert.pem';
	var $_transport = 'http';
	var $_format = "xml";
	var $_v = "2.0";
	var $private_key_path = 'key/rsa_private_key.pem';
	var $ali_public_key_path = 'key/alipay_public_key.pem';
    /**
     *    获取提交表单
     *
     *    @author    Garbin
     *    @param     array $order_info  待支付的订单信息，必须包含总费用及唯一外部交易号
     *    @return    array
     */
    function get_submitform($order_info,$api_config){
		//必填		
		//构造要请求的参数数组，无需改动
		$parameter = array(
				"mch_id" => $api_config['weixin_partner'],
				"appid" => $api_config['weixin_APPID'],
				"out_trade_no" => $order_info['out_trade_no'], //订单号
				"transaction_id"	=> $order_info['transaction_id'], //交易流水号
				"customs"	=> $order_info['customs'],//报关地点
				"mch_customs_no" => $order_info['mch_customs_no'],
		);

// 		if($order_info['cert_id'] && $order_info['name']){
// 			$parameter['cert_id'] = $order_info['cert_id'];
// 			$parameter['name'] = $order_info['name'];
// 		}
		
		//待请求参数数组
		$para_temp = $this->buildRequestPara($parameter,$api_config['weixin_APPKEY']);
		$xml = '<xml>';
		$xml.='
	   <appid>'. $para_temp['appid'] .'</appid>
	   <customs>'. $para_temp['customs'] .'</customs>
	   <mch_customs_no>'. $para_temp['mch_customs_no'] .'</mch_customs_no>
	   <mch_id>'. $para_temp['mch_id'] .'</mch_id>
	   <out_trade_no>'. $para_temp['out_trade_no'] .'</out_trade_no>
	   <sign>'. $para_temp['sign'] .'</sign>
	   <transaction_id>'. $para_temp['transaction_id'] .'</transaction_id>';
		
// 		if($para_temp['cert_id'] && $para_temp['name']){
// 			$xml.='<cert_id>'. $para_temp['cert_id'] .'</cert_id>';
// 			$xml.='<name>'. $para_temp['name'] .'</name>';
// 		}
		
		$xml.='</xml>';
		
		$res_msg =  $this->curl_get($xml);
		return (array)$res_msg;
// 		$obj = simplexml_load_string($res_msg);
// 		$str = serialize($obj);
// 		$str = str_replace('O:16:"SimpleXMLElement"', 'a', $str);
// 		$arrstr = unserialize($str);
// 		return $arrstr; 
    }
   
	
	/**
     * 报关生成要请求的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	function buildRequestPara($para_temp, $aip_key) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);
		//生成签名结果
		$mysign = strtoupper($this->buildRequestMysign($para_sort,$aip_key));
	
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->_sign_type));
		return $para_sort;
	}
	
	/**
	 * 报关
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	function buildRequestMysign($para_sort,$api_key) {
		//把数组所有元素，按照"参数=参数值"的模式用"&"字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);

		$mysign = "";
		switch (strtoupper(trim($this->_sign_type))) {
			case "MD5" :
				$mysign = $this->md5Sign($prestr, $api_key);
				break;
			default :
				$mysign = "";
		}
		return $mysign;
	}
	
	
	
	/**
	 * 报关
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	function paraFilter($para) {
		$para_filter = array();
		while (list ($key, $val) = each ($para)) {
			if($key == "sign" || $key == "sign_type" || $val == "")continue;
			else	$para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}
	
	/**
	 * 对数组排序
	 * @param $para 排序前的数组
	 * return 排序后的数组
	 */
	function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}
	
	
	/**
	 * 把数组所有元素，按照"参数=参数值"的模式用"&"字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
		
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		
		return $arg;
	}
	
	/**
	 * 签名字符串
	 * @param $prestr 需要签名的字符串
	 * @param $key 私钥
	 * return 签名结果
	 */
	function md5Sign($prestr, $key) {
		$prestr = $prestr .'&key='. $key;
		return md5($prestr);
	}
	
	//curl GET 请求
// 	function curl_get($get_url){
// 		$ch = curl_init();
// 		curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded','Connection: close' ,'Cache-Control: no-cache' ,'Accept-Language: zh-cn'));
// 		curl_setopt ($ch, CURLOPT_TIMEOUT, 20);
// 		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)");
// 		curl_setopt ($ch, CURLOPT_HEADER,0);
// 		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0);
// 		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt($ch, CURLOPT_POST, 1);
// 		curl_setopt ($ch, CURLOPT_URL,$get_url);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
// 		curl_setopt($ch, CURLOPT_HTTPGET, 1);
// 		$res  = curl_exec($ch);
// 		curl_close($ch);
// 		return $res;
// 	}
	
	/* 退货状态结束 */
	
	/**
	 * 请求API数据
	 * @param 请求API数据 $posturl
	 * @return unknown
	 */
	function curl_get($posturl){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->_gateway);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $posturl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$response = curl_exec($curl);
		curl_close($curl);
		return simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
// 		$stdclassobject = simplexml_load_string($response);
// 		$_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
// 		if(!empty($_array)){
// 			foreach ($_array as $key => $value){
// 				$value = (is_array($value) || is_object($value)) ? get_object_vars($value) : $value;
// 				$return_data[$key] = $value;
// 			}
// 		}
// 		print_R($return_data);die;
// 		return $return_data;
	}
	
}

?>