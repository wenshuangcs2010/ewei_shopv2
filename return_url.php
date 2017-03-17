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
}


//计算得出通知验证结果
//$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $payment->verifyReturn();
if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代码
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

	//商户订单号
	$out_trade_no = $_GET['out_trade_no'];

	//支付宝交易号
	$trade_no = $_GET['trade_no'];

	//交易状态
	$trade_status = $_GET['trade_status'];


    if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
    }
    else {
      echo "trade_status=".$_GET['trade_status'];
    }
		
	$bool=1;

	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    $bool=0;
}
$url="http://".$_SERVER['HTTP_HOST']."/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=order.orderpay.orderpay.order_return&d={$bool}";
header("Location: {$url}");
?>