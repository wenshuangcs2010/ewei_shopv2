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

$config=array("shengpay_account"=>"10114414",'shengpay_key'=>"zjcof85779533hjs");
$payment=paybase::getPayment("shengpay",$config);

WeUtility::logging('PAY_NOTIFY_URL', var_export($_POST,true).'post_1');