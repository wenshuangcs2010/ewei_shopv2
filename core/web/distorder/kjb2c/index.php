<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once EWEI_SHOPV2_TAX_CORE. '/declare/NINGBO.declare.php';
class Index_EweiShopV2Page extends WebPage
{

	function to_customs(){
		 global $_W,$_GPC;
		 $orderid=$_GPC['id'];
		 $order=pdo_fetch("SELECT paymentno,uniacid,ordersn,paytype,price,depotid from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		
		$customs=m("kjb2c")->check_if_customs($order['depotid']);
		if(!$customs){
			show_json(0,"订单无需报关");
		}
		$_W['uniacid']=$order['uniacid'];
		 $params=array(
		 	'out_trade_no'=>$order['ordersn'],
		 	'transaction_id'=>$order['paymentno'],
		 	'customs'=>$customs,
		 	);

		 if($order['paytype']==21){
		 	load()->model('payment');
        	$setting = uni_setting($_W['uniacid'], array('payment'));
        	if (is_array($setting['payment']['wechat']) && $setting['payment']['wechat']['switch']) {
        		 $APPID = pdo_fetchcolumn('SELECT `key` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));
                    $config=array(
                    	"appid"=>$APPID,
                    	'mch_id'=>$setting['payment']['wechat']['mchid'],
                    	'apikey'=>$setting['payment']['wechat']['apikey'],
                    	);
                  
                $retrundata=m("kjb2c")->to_customs($params,$config,'wx');
                if($retrundata['return_code']=="FAIL"){
                	show_json(0,$retrundata['return_msg']);
                }else{
                	show_json(1,$retrundata['return_msg']);
                }
                if(isset($retrundata['errno'])){
		 			show_json(0,$retrundata['message']);
		 		}
            }else{
            	show_json(0,"微信支付已经关闭请重新开启");
            }
		}else{
			show_json(0,"特殊支付方式请联系管理员");
		}
	}
	function to_declare(){
		global $_W,$_GPC;
		$orderid=$_GPC['id'];
		m("kjb2c")->to_declare($orderid);
	}
}