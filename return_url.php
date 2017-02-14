<?php
require '../../framework/bootstrap.inc.php';
require '../../addons/ewei_shopv2/defines.php';
require '../../addons/ewei_shopv2/dispage.php';
require '../../addons/ewei_shopv2/core/inc/functions.php';
require '../../addons/ewei_shopv2/core/inc/plugin_model.php';
require '../../addons/ewei_shopv2/core/inc/com_model.php';
require "../../addons/ewei_shopv2/core_inc/orderpay/paybase.php";

$out_trade_no = $_GET['out_trade_no'];
$orderinfo=pdo_fetch("SELECT * from ".tablename("ewei_shop_order_dispay")." where order_sn=:out_trade_no ",array(":out_trade_no"=>$out_trade_no));
if(empty($orderinfo)){
	echo "支付失败";
	die();
}else{
	$_W['uniacid']=$orderinfo['uniacid'];
	$disInfo=Dispage::getDisInfo($_W['uniacid']);
}
if(empty($disInfo)){
	echo "支付失败";
	die();
}
if($disInfo['secondpaytype']==1){
	$config=array("shengpay_account"=>"10114414",'shengpay_key'=>"zjcof85779533hjs");
	$payment=paybase::getPayment("shengpay",$config);

}
if($disInfo['secondpaytype']==2){
	load()->model('payment');
    $setting = uni_setting(DIS_ACCOUNT, array('payment'));

	if (is_array($setting['payment']['alipay']) && $setting['payment']['alipay']['switch']) {
	$options = $setting['payment']['alipay'];
	$config=array(
		'partner'=>$options['partner'],
		'key'=>$options['secret'],
		);
	}
	$payment=paybase::getPayment("alipay",$config);
	$verify_result = $payment->verifyReturn();
	if($verify_result) {
		$out_trade_no = $_GET['out_trade_no'];
		$trade_no = $_GET['trade_no'];
		$trade_status = $_GET['trade_status'];
	$bool=1;
	}
	else {
	    $bool=0;
	}
}
$url="http://".$_SERVER['HTTP_HOST']."/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=order.orderpay.orderpay.order_return&d={$bool}";
header("Location: {$url}");
?>