<?php
header("Content-type: text/html; charset=utf-8");
class alipay extends customs{

	var $params=array();
	var $config=array();
	var $_input_charset = 'utf-8';
	var $_sign_type = 'MD5';
	var $_gateway   =   'https://mapi.alipay.com/gateway.do';
	public function __construct($config){
		$this->config=$config;
	}
	public function to_customs($params){
		$this->params['mch_customs_no']=$params['mch_customs_no'];
		$this->params['merchant_customs_name']=$params['merchant_customs_name'];
		$this->params['amount']=$params['amount'];
		$this->params['customs']=$params['customs'];
		$this->params['out_trade_no']=$params['out_trade_no'];
		$this->params['order_sn']=$params['order_sn'];
		$parameter = array(
			"service"               => "alipay.acquire.customs",
			"partner"               => trim($this->config['partner']),
			"out_request_no"        => $params['order_sn'],
			"trade_no"              => $params['trade_num'],
			"merchant_customs_code" => $params['mch_customs_no'],
			"merchant_customs_name" => $params['merchant_customs_name'],
			"amount"                => $params['amount'],
			"customs_place"         => $params['customs'],
			"_input_charset"        => trim(strtolower($this->_input_charset))
		);

		$para_temp = $this->buildRequestPara($parameter,$this->config['key']);
		$url = $this->_gateway."?";
		$i = 1;
		foreach($para_temp as $k => $v){
			if($i == 1){
				$url .= $k.'='.$v;
			}else{
				$url .= '&'.$k.'='.$v;
			}
			$i++;
		}

		$res_msg=$this->curl_get($url);
		$res_msg=(array)simplexml_load_string($res_msg);
		
		return $res_msg;
	}
	function parse_xml_to_array($xmlstr,$loopTag){
    $args = explode('</'.$loopTag.'>',$xmlstr);
    $returns = array();
    if($args){
        $reg = '/<(＼w+)[^>]*>([＼x00-＼xFF]*)<＼/＼1>/';
        foreach($args as $item){
            $item = str_replace('<'.$loopTag.'>','',$item);
            if(preg_match_all($reg, $item, $matches)) {
               if(isset($matches[1]) && isset($matches[2])){
                   $returns[] = array_combine($matches[1],$matches[2]);
               }
            }
        }
    }
    unset($args);
    return $returns;
	}
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
	/**
	 * 请求API数据
	 * @param 请求API数据 $posturl
	 * @return unknown
	 */
	function _curl_post($api, $posturl){
		$curl = curl_init();
		//$headers[]="content-type: application/x-www-form-urlencoded;charset=UTF-8";
		curl_setopt($curl, CURLOPT_URL, $api);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded','Connection: close' ,'Cache-Control: no-cache' ,'Accept-Language: zh-cn'));
		curl_setopt ($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)");
		//curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $posturl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
	function md5Sign($prestr, $key) {
		$prestr = $prestr . $key;
		return md5($prestr);
	}
	/**
     * 支付宝报关生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	function buildRequestPara($para_temp, $api_key) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);
		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		$prestr = $this->createLinkstring($para_sort);
		//var_dump($prestr);
		//生成签名结果
		$mysign = $this->md5Sign($prestr, $api_key);
		//签名结果与签名方式加入请求提交参数组中
		$para_temp['sign'] = $mysign;
		//var_dump($para_temp['sign']);
		$para_temp['sign_type'] = strtoupper(trim($this->_sign_type));
		return $para_temp;
	}
}