<?php

class MONTERUtil{
	public $gwUrl = 'http://61.145.229.29:9006/MWGate/wmgw.asmx?wsdl';
    public $password = '';
    public $username = '';
	 public function __construct($username,$password) {
	 	$this->password=$password;
	 	$this->username=$username;
	 }
	 public function send($mobile = '', $msg = ''){
	 	$soapAction="http://tempuri.org/MongateSendSubmit";
	 	$iMobiCount=1;
	 	if(is_array($mobile)){
	 		$iMobiCount=count($mobile);
	 	}
	 	$postData=array(
	 		'userId'=>$this->username,
    		'password'=>$this->password,
    		'pszMobis'=>$mobile,
    		'pszMsg'=>$msg,
    		'iMobiCount'=>$iMobiCount,
    		'pszSubPort'=>"*",
    		'MsgId'=>"17179869184",
	 		);
	 	$returndata=$this->soap_send($soapAction,$postData,"MongateSendSubmit");
	 	return $returndata;
	 }


	 /**
     * 余额查询
     */
    function getBalance() {
    	
    	$soapAction="http://tempuri.org/MongateQueryBalance";
    	$postData=array(
    		'userId'=>$this->username,
    		'password'=>$this->password,
    		);
    	
    	$returndata=$this->soap_send($soapAction,$postData,"MongateQueryBalance");
    	//$returndata=json_decode($returndata,true);
    	return $returndata['MongateQueryBalanceResult'];
    }
    private function soap_send($soapAction,$postData,$soapcallname){
    	include_once EWEI_SHOPV2_TAX_CORE.'lib/nusoap.php';
    	$client = new nusoap_client($this->gwUrl,"wsdl");
    	$client->soap_defencoding = 'UTF-8';
        $client->xml_encoding = 'UTF-8';
        $transResponse = $client->call($soapcallname, $postData,'http://tempuri.org',$soapAction); //以数组形式传递params
        return $transResponse;
    }
}