<?php


require 'wx_core.php';
class Wx_payment extends paybase{
	var $config=array();
	

	function __construct($alipay_config){
		$this->config = $alipay_config;
	}
    function Wx_payment($config) {
    	$this->__construct($config);
    }
    function buildRequestForm($order){
    	$wxdata=new wxData();
    	$wxdata->setAppid($this->config['appId']);
    	$wxdata->setMchid($this->config['mchid']);
    	$wxdata->setNonce_str($wxdata->getNonceStr());
    	$wxdata->setPartner_trade_no($order['order_sn']);
    	$wxdata->setOpenid($this->config['openid']);
    	$wxdata->setAmount($order['pay_fee']);
    	$wxdata->setDesc($order['desc']);
    	$xml=wx_api::init($wxdata,$meg,$this->config['key']);
    	if(empty($meg)){
    		$returndata=wx_api::post_ssh_curl($xml,$_W['uniacid']);
    		if(isset($returndata['errno'])){
                return array('status'=>-1,'message'=>$returndata['message']);
    			//show_json(0,$returndata['message']);
    		}else{
                $this->updateorder($order['order_sn'],$returndata['payment_no']);
                return array('status'=>0,'message'=>"ok");
            }
              return array('status'=>-1,'message'=>"未知错误");
            //m("kjb2c")->to_declare($orderid);
    		//show_json(1,array('url' => referer(),'message' => "OK"));
    	}
    }

}