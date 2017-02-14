<?php
class wx extends customs{
	var $params=array();
	var $config=array();
	var $_gateway   =   'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';
	public function __construct($config){
		$this->config=$config;
		
	}
	//参数组装
	private function init($params){
		$this->params['appid']=$this->config['appid'];
		$this->params['mch_id']=$this->config['mch_id'];
		$this->params['customs']=$params['customs'];
		$this->params['mch_customs_no']=$params['mch_customs_no'];
		$this->params['out_trade_no']=$params['out_trade_no'];
		$this->params['transaction_id']=$params['transaction_id'];
	}

	
	public function to_customs($params){
		$this->init($params);
		$xml=$this->getXml($meg);
		if(!empty($meg)){
			return error(-1,$meg);
		}
		//var_dump($this->config['apikey']);
		//var_Dump($xml);
		//die();
		return $this->curl_get($xml);
	}

	public function get_values(){
		return $this->params;
	}
	private function setSig(){
		$params=$this->paraFilter($this->params);
		$para_sort=$this->argSort($params);
		$prestr = $this->createLinkstring($para_sort);
		$sign= $this->md5Sign($prestr, $this->config['apikey']);
		$this->params['sign']=strtoupper($sign);
	}
	function getXml(&$msg){
		if(!$this->isAppid()){
			$msg="APPID未设置";
			return false;
		}
		if(!$this->isMchid()){
			$msg="商户缺失";
			return false;
		}
		if(!$this->isCustoms()){
			$msg="报关地址未设置";
			return false;
		}
		if(!$this->isOut_trade_no()){
			$msg="商户订单号缺失";
			return false;
		}
		if(!$this->isTransaction_id()){
			$msg="未找到支付单号";
			return false;
		}
		$this->setSig();
		$xml = simplexml_load_string('<request />');
		$this->createXml($this->params, $xml);
		return  $xml->saveXML();
	}

	function isAppid(){
		return array_key_exists('appid', $this->params);
	}
	function isMchid(){
		return array_key_exists('mch_id', $this->params);
	}
	function isCustoms(){
		return array_key_exists('customs', $this->params);
	}
	function isOut_trade_no(){
		return array_key_exists('out_trade_no', $this->params);
	}
	function isTransaction_id(){
		return array_key_exists('transaction_id', $this->params);
	}
}
?>