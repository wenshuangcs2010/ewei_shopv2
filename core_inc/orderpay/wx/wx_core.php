<?php
	class wxData extends paybase{
		protected $values = array();
		
		//微信分配的公众账号ID（企业号corpid即为此appId）
		function setAppid($appid){
			$this->values['mch_appid']=$appid;
		}


		//微信支付分配的商户号
		function setMchid($mchid){
			$this->values['mchid']=$mchid;
		}
		//随机字符串，不长于32位
		function setNonce_str($nonce_str){
			$this->values['nonce_str']=$nonce_str;
		}
		//签名
		function setSign($key){
			$this->values['sign']=$this->MakeSign($key);
		}
		//商户订单号，需保持唯一性
		function setPartner_trade_no($partner_trade_no){
			$this->values['partner_trade_no']=$partner_trade_no;
		}
		//商户appid下，某用户的openid
		function setOpenid($openid){
			$this->values['openid']=$openid;
		}
		function setCheck_name($check_name="NO_CHECK"){
			$this->values['check_name']=$check_name;
		}
		function setRe_user_name($re_user_name=""){
			$this->values['re_user_name']=$re_user_name;
		}
		//企业付款金额，单位为分
		function setAmount($amount){
			$this->values['amount']=$amount;
		}
		//企业付款操作说明信息。必填
		function setDesc($desc="利润"){
			$this->values['desc']=$desc;
		}
		//调用接口的机器Ip地址
		function setSpbill_create_ip($spbill_create_ip=""){
			if(empty($spbill_create_ip)){
				$spbill_create_ip=$this->getClientIP();
			}
			$this->values['spbill_create_ip']=$spbill_create_ip;
		}
		function get_values(){
			return $this->values;
		}
		public function isAppidSet(){
			return array_key_exists('mch_appid', $this->values);
		}
		public function isMchidSet(){
			return array_key_exists('mchid', $this->values);
		}
		public function isNonce_strSet(){
			return array_key_exists('nonce_str', $this->values);
		}
		public function isPartner_trade_noSet(){
			return array_key_exists('partner_trade_no', $this->values);
		}
		public function isOpenidSet(){
			return array_key_exists('openid', $this->values);
		}
		public function isAmountSet(){
			return array_key_exists('amount', $this->values);
		}


		public function MakeSign($key)
		{
			//签名步骤一：按字典序排序参数
			ksort($this->values);
			$string = $this->ToUrlParams();
			//签名步骤二：在string后加入KEY
			$string = $string . "&key=".$key;
			//签名步骤三：MD5加密
			$string = md5($string);
			//签名步骤四：所有字符转为大写
			$result = strtoupper($string);
			return $result;
		}
	/**
	 * 格式化参数格式化成url参数
	 */
	public function ToUrlParams()
	{
		$buff = "";
		foreach ($this->values as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	/**
	 * 输出xml字符
	 * @throws WxPayException
	**/
	public function ToXml()
	{
		if(!is_array($this->values) || count($this->values) <= 0)
		{
    		throw new WxPayException("数组数据异常！");
    	}
    	
    	$xml = "<xml>";
    	foreach ($this->values as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    		}
        }
        $xml.="</xml>";
        return $xml; 
	}

	public  function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}

}


class wx_api{
	var $URLAPI="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
	public static function  init($input,&$meg,$key){
		if(!$input->isAppidSet()){
			$meg="APPID没有设置";
			return false;
		}
		if(!$input->isMchidSet()){
			$meg="商户号没有设置";
			return false;
		}
		if(!$input->isPartner_trade_noSet()){
			$meg="订单号没有设置";
			return false;
		}
		if(!$input->isOpenidSet()){
			$meg="opeind没有设置";
			return false;
		}
		if(!$input->isAmountSet()){
			$meg="金额没有设置";
			return false;
		}
		if(!array_key_exists("spbill_create_ip", $input->get_values())){
			$input->setSpbill_create_ip();
		}
		if(!array_key_exists("check_name", $input->get_values())){
			$input->setCheck_name();
			$input->setRe_user_name();
		}
		if(!array_key_exists("desc", $input->get_values())){
			$input->setDesc();
		}
		$input->setSign($key);
		return $input->ToXml();
	}

	public static function post_ssh_curl($xml,$uniacid=DIS_ACCOUNT){
		$sec = m('common')->getSec($uniacid);

		$certs = iunserializer($sec['sec']);
		if (is_array($certs)) {
			if (empty($certs['cert']) || empty($certs['key']) || empty($certs['root'])) {
				return error(-2, '未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!');
				}
				$certfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
				file_put_contents($certfile, $certs['cert']);

				$keyfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
				file_put_contents($keyfile, $certs['key']);
				$rootfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
				file_put_contents($rootfile, $certs['root']);
				$extras['CURLOPT_SSLCERT'] = $certfile;
				$extras['CURLOPT_SSLKEY'] = $keyfile;
				$extras['CURLOPT_CAINFO'] = $rootfile;
		}else{
			return error(-2, '未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!');
		}
		load()->func('communication');
		$ss=new self();
		//$url=$this->apiUrl;
	    $resp = ihttp_request($ss->URLAPI, $xml, $extras);
	    @unlink($certfile);
		@unlink($keyfile);
		@unlink($rootfile);
		if (is_error($resp)) {
			return error(-2, $resp['message']);
		}
		//var_dump($resp);
		$arr = (array) simplexml_load_string($resp['content'],null, LIBXML_NOCDATA);
		if($arr['result_code']=="FAIL"){
			return error(-2, $arr['return_msg']);
			//return false;
		}
	    return $arr;
	}
}
?>