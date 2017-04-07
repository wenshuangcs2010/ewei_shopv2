<?php

class alipay extends customs{

	var $params=array();
	var $config=array();
	var $_input_charset = 'utf-8';
	var $_sign_type = 'MD5';
	var $_gateway   =   'https://mapi.alipay.com/gateway.do?';
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
				"service" => "alipay.acquire.customs",
				"partner" => $this->config['partner'],
				"out_request_no" => $params['order_sn'], //订单号
				"trade_no"	=> $params['trade_num'], //支付宝交易流水号
				"merchant_customs_code"	=> $params['mch_customs_no'], //商户海关备案编码
				"merchant_customs_name" => $params['merchant_customs_name'],//商户海关备案名称
				"amount"	=> $params['amount'],//报关金额
				"customs_place"	=> $params['customs'],//报关地点
				"_input_charset"	=> trim(strtolower($this->_input_charset))
		);
		$para_temp = $this->buildRequestPara($parameter,$this->config['key']);
		var_dump($para_temp);
		die();
		return $this->curl_get($xml);
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
		$prestr = $this->createLinkstring($para_sort);
		//生成签名结果
		$mysign = $this->md5Sign($prestr, $api_key);
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->_sign_type));
		return $para_sort;
	}
}