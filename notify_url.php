<?php
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);
require '../../framework/bootstrap.inc.php';
require '../../addons/ewei_shopv2/defines.php';
require '../../addons/ewei_shopv2/core/inc/functions.php';
require '../../addons/ewei_shopv2/core/inc/plugin_model.php';
require '../../addons/ewei_shopv2/core/inc/com_model.php';
require "../../addons/ewei_shopv2/core_inc/orderpay/paybase.php";
require "../../addons/ewei_shopv2/core_inc/orderpay/alipay/alipay_payment.php";
//计算得出通知验证结果
//获取主站支付宝支付配置
load()->model('payment');
$setting = uni_setting(DIS_ACCOUNT, array('payment'));
//WeUtility::logging('pay', var_export($_POST,true).'aa');
if (is_array($setting['payment']['alipay']) && $setting['payment']['alipay']['switch']) {
    $options = $setting['payment']['alipay'];
    $config=array(
        'partner'=>$options['partner'],
        'key'=>$options['secret'],
        'cacert'=>"../../addons/ewei_shopv2/core_inc/orderpay/alipay/cacert.pem",
	);
}

//WeUtility::logging('PAY_NOTIFY_URL', var_export($_pos,true).'post_1');
//WeUtility::logging('PAY_NOTIFY_URL', var_export($GLOBALS['HTTP_RAW_POST_DATA']).'post_2');
$alipayNotify = new Alipay_payment($config);
$verify_result = $alipayNotify->verifyNotify();
if($verify_result) {//验证成功
	$out_trade_no = $_POST['out_trade_no'];
	//支付宝交易号
	$trade_no = $_POST['trade_no'];
	//交易状态
	$trade_status = $_POST['trade_status'];
	$seller_id=$_POST['seller_id'];
    if($trade_status == 'TRADE_FINISHED') {
		if($config['partner']==$seller_id){
			m('kjb2c')->alpay_disorder($_POST,$out_trade_no);
		}
    }
    else if ($trade_status == 'TRADE_SUCCESS') {
		if($config['partner']==$seller_id){
			m('kjb2c')->alpay_disorder($_POST,$out_trade_no);
		}
    }
    echo "success";
}
else {
    echo "fail";
}
?>