<?php

abstract class customs{
	abstract  function to_customs($params);
	protected  $_gateway;

	public function __construct(){

	}
	public static function getObject($type,$config){
		require_once(EWEI_SHOPV2_TAX_CORE. '/customs/'.$type.'.customs.php');
		$classname=new $type($config);
		return $classname;
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
	 * 报关
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	public function paraFilter($para) {
		$para_filter = array();
		while (list ($key, $val) = each ($para)) {
			if($key == "sign" || $key == "sign_type" || $val == "")continue;
			else	$para_filter[$key] = $para[$key];
		}
		return $para_filter;
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
	function createXml($ar, $xml) {
	    foreach($ar as $k=>$v) {
	        if(is_array($v)) {
	            $x = $xml->addChild($k);
	            createXml($v, $x);
	        }else $xml->addChild($k, $v);
	    }
	}
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
	}
	//curl  请求
	/**
	 * 请求API数据
	 * @param 请求API数据 $posturl
	 * @return unknown
	 */
	function _curl_post($api, $posturl){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $api);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $posturl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$response = curl_exec($curl);
		curl_close($curl);
		$return_data = json_decode($response);
		return $return_data;
	}
}