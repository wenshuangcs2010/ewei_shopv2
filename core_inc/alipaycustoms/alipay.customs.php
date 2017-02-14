<?php

/**
 *    支付宝报关接口
 *
 *    @author    Garbin
 *    @usage    none
 */

class Alipaycustoms
{
    /* 支付宝网关 */
    var $_gateway   =   'https://mapi.alipay.com/gateway.do?';
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
				"service" => "alipay.acquire.customs",
				"partner" => $api_config['partner'],
				"out_request_no" => $order_info['order_sn'], //订单号
				"trade_no"	=> $order_info['trade_num'], //支付宝交易流水号
				"merchant_customs_code"	=> $order_info['merchant_customs_code'], //商户海关备案编码
				"merchant_customs_name" => $order_info['merchant_customs_name'],//商户海关备案名称
				"amount"	=> $order_info['amount'],//报关金额
				"customs_place"	=> $order_info['customs_place'],//报关地点
				"_input_charset"	=> trim(strtolower($this->_input_charset))
		);
		//待请求参数数组
		$para_temp = $this->buildRequestPara($parameter,$api_config['aip_key']);
		$url = $this->_gateway;
		$i = 1;
		foreach($para_temp as $k => $v){
			if($i == 1){
				$url .= $k.'='.$v;
			}else{
				$url .= '&'.$k.'='.$v;
			}			
			$i++;
		}
		$res_msg =  $this->curl_get($url);
		$obj = simplexml_load_string($res_msg);
		$str = serialize($obj);
		$str = str_replace('O:16:"SimpleXMLElement"', 'a', $str);
		$arrstr = unserialize($str);
		return $arrstr;
    }
   
	
	/**
     * 支付宝报关生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	function buildRequestPara($para_temp, $aip_key) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort,$aip_key);
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->_sign_type));
		return $para_sort;
	}
	
	/**
	 * 支付宝报关
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
			case "RSA" :
				$mysign = $this->rsaSign($prestr, getcwd().'/includes/payments/wsalipay/'.$this->private_key_path);
				break;
			case "0001" :
				$mysign = $this->rsaSign($prestr, getcwd().'/includes/payments/wsalipay/'.$this->private_key_path);
				break;
			default :
				$mysign = "";
		}
		return $mysign;
	}
	
	
	
	/**
	 * 支付宝报关
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
		$prestr = $prestr . $key;
		return md5($prestr);
	}
	
	//curl GET 请求
	function curl_get($get_url){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded','Connection: close' ,'Cache-Control: no-cache' ,'Accept-Language: zh-cn'));
		curl_setopt ($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)");
		curl_setopt ($ch, CURLOPT_HEADER,0);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt ($ch, CURLOPT_URL,$get_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		$res  = curl_exec($ch);
		curl_close($ch);
		return $res;
	}
}

?>