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
		  $order=pdo_fetch("SELECT zhuan_status,paymentno,if_customs_z,ordersn,paytype,price,depotid from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		
		$customs=m("kjb2c")->check_if_customs($order['depotid']);
		if(!$customs){
			show_json(0,"订单无需报关");
		}
		$_W['uniacid']=$order['uniacid'];
		$depot=m("kjb2c")->get_depot($order['depotid']);
		 $params=array(
		 	'out_trade_no'=>$order['ordersn'],
		 	'transaction_id'=>$order['paymentno'],
		 	'customs'=>$customs,
		 	'mch_customs_no'=>$depot['customs_code'],
		 	);
		  if( $order['if_customs_z']==1 && $order['zhuan_status']==1 ){
			$sporder=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_zpay_log")." where order_sn=:ordersn",array(":ordersn"=>$order['ordersn']));
			if($sporder['pay_code']=="shenfupay"){
				$params = array(
				"order_sn" => $order['ordersn'],
				"customs_place" => $customs,//报关地点
				"businessMode" => 'BONDED',
				"trade_num"	=>$sporder['paymentno'],
				"amount" =>$sporder['pay_fee'],
				"shipping_fee"	=> 0,
				"amount_tariff"	=> 0,
				"memo" => '',
				'mch_customs_no'=>$depot['customs_code'],
				);
			}
			$retrundata=m("kjb2c")->to_customs($params,array(),$sporder['pay_code']);
			show_json(0,$retrundata['message']);
		}
		 if($order['paytype']==21){
		 	load()->model('payment');
        	$jearray=Dispage::getDisaccountArray();
		 	$uniacid=$_W['uniacid'];
		 	if(in_array($_W['uniacid'], $jearray) && $order['isdisorder']==1){
                 $uniacid=DIS_ACCOUNT;
            }
        	$setting = uni_setting($uniacid, array('payment'));
        	if (is_array($setting['payment']['wechat']) && $setting['payment']['wechat']['switch']) {
        		 $APPID = pdo_fetchcolumn('SELECT `key` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>$uniacid));
                    $config=array(
                    	"appid"=>$APPID,
                    	'mch_id'=>$setting['payment']['wechat']['mchid'],
                    	'apikey'=>$setting['payment']['wechat']['apikey'],
                    	);
                $retrundata=m("kjb2c")->to_customs($params,$config,'wx');

                if($retrundata['return_code']=="FAIL"){
                	show_json(0,$retrundata['return_msg']);
                }else{
                	if($retrundata['result_code']=="FAIL"){
                		show_json(0,$retrundata['err_code_des']);
                	}
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