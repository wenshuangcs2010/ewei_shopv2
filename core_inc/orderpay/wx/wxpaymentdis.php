<?php


class wxpaymentData{



	//var $values=array();
	 function __construct() {
		global $_W;
		$this->values['spbill_create_ip']=$_W['clientip'];
	}
	//微信分配的公众账号ID（企业号corpid即为此appId）
	public function SetAppid($value){
		$this->values['mch_appid']=$value;
	}
	//微信支付分配的商户号
	public function SetMchid($value){
		$this->values['mchid']=$value;
	}
	//随机字符串，不长于32位
	public function SetNonce_str($value){
		$this->values['nonce_str']=$value;
	}
	//签名
	public function SetSign(){
		$this->values['sign']=$this->MakeSign();
	}
	//商户订单号，需保持唯一性
	public function SetPartner_trade_no($value){
		$this->values['partner_trade_no']=$value;
	}
	//商户appid下，某用户的openid
	public function SetOpenid($openid){
		$this->values['openid']=$openid;
	}
	/**
	 * NO_CHECK：不校验真实姓名 
	 * FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账） 
	 * OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
	 */
	public function SetCheck_name($check_name="NO_CHECK"){
		//NO_CHECK 
		$this->values['check_name']=$check_name;//不校验真实姓名
	}
	//收款用户真实姓名。 如果check_name设置为FORCE_CHECK或OPTION_CHECK，则必填用户真实姓名
	public function SetRe_user_name($re_user_name=""){
		$this->values['re_user_name']=$re_user_name;
	}
	//企业付款金额，单位为分
	public function SetAmount($amount){
		$this->values['amount']=$amount;
	}
	//企业付款操作说明信息。必填。
	public function SetDesc($desc="利润"){
		$this->values['desc']=$desc;
	}
	public function get_values(){
		return $this->values;
	}
	public function SetUniacid($uniacid=DIS_ACCOUNT){
		$this->values['uniacid']=$uniacid;
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


	/**
	 * 生成签名
	 * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
	 */
	public function MakeSign()
	{
		
		 $setting = uni_setting($this->values['uniacid'], array('payment'));
		 unset($this->values['uniacid']);
		//签名步骤一：按字典序排序参数
		ksort($this->values);
		$string = $this->ToUrlParams();
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".$setting['payment']['wechat']['apikey'];
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
		if(!is_array($this->values) 
			|| count($this->values) <= 0)
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


}
class wxpaymentpayApi{
	
			/**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	public static function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}
}

class wxdisprice extends wxpaymentData{
	var  $apiUrl="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";

	public static function  init($input,&$meg="OK"){
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
		if(!array_key_exists("check_name", $input->get_values())){
			$input->SetCheck_name();
			$input->SetRe_user_name();
		}
		if(!array_key_exists("desc", $input->get_values())){
			$this->SetDesc();
		}
		//var_dump($input->get_values());
		$input->SetSign();
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
	    $resp = ihttp_request($ss->apiUrl, $xml, $extras);
	    @unlink($certfile);
		@unlink($keyfile);
		@unlink($rootfile);
		if (is_error($resp)) {
			return error(-2, $resp['message']);
		}
		
		$arr = (array) simplexml_load_string($resp['content'],null, LIBXML_NOCDATA);
		if($arr['result_code']=="FAIL"){
			return error(-2, $arr['return_msg']);
			//return false;
		}
	    return $arr;
	}

}

?>