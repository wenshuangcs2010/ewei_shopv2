<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once EWEI_SHOPV2_TAX_CORE. '/declare/ningbo.declare.php';
class Index_EweiShopV2Page extends WebPage
{

	function index(){
		show_json(0,"111111");
	}
	function to_customs(){
		 global $_W,$_GPC;
		 $orderid=$_GPC['id'];
		 $order=pdo_fetch("SELECT paymentno,ordersn,paytype,price,depotid from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		
		$customs=m("kjb2c")->check_if_customs($order['depotid']);
		if(!$customs){
			show_json(0,"订单无需报关");
		}
		 $params=array(
		 	'out_trade_no'=>$order['ordersn'],
		 	'transaction_id'=>$order['paymentno'],
		 	'customs'=>$customs,
		 	);
		 if($order['paytype']==21){
		 	load()->model('payment');
        	$setting = uni_setting($_W['uniacid'], array('payment'));
        	if (is_array($setting['payment']['wechat']) && $setting['payment']['wechat']['switch']) {
                    $config=array(
                    	"appid"=>$_W['account']['key'],
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
		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		if(empty($order)){
			show_json(0,"订单不存在");
		}
		$depot=Dispage::getDepot($order['depotid']);
		if($depot['if_declare']!=1){
			show_json(0,'当前订单无需申报');
		}
		$order_goodssql="SELECT og.*,g.disgoods_id,g.dispatchid,g.weight,g.title from "
						.tablename("ewei_shop_order_goods")." as og "
						."LEFT JOIN ".tablename("ewei_shop_goods")." as g on og.goodsid=g.id "
						." where orderid=:orderid";
		$order_goods=pdo_fetchall($order_goodssql,array(":orderid"=>$orderid));
		$Weight=0;
		foreach($order_goods as $goods){
			$dispatchid=$goods['dispatchid'];
			$Weight+=$goods['weight']*$goods['total'];
			if($order['dispatchid']==0){
				$dispatch_data = m('dispatch')->getDefaultDispatch(0,$goods['disgoods_id'],$goods['goodsid']);//wsq
			}else{
				$dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid'],$goods['disgoods_id']);//wsq
			}
		}
		$expressname=pdo_fetchcolumn("select `name` from ".tablename("ewei_shop_express")." where express=:express",array(":express"=>$dispatch_data['express']));
		if($expressname=="圆通速递"){
			$expressname="北仑军通";
		}
		$config=array(
			'_OrgName'=>$depot['orgname'],
			'_OrgUser'=>$depot['rrguser'],
			'_OrderShop'=>$depot['ordershop'],
			'_Orgkey'=>$depot['orgkey'],
			'_CustomsCode'=>$depot['customs_code'],
			'_OrderFrom'=>$depot['orderfrom'],
			'_OTOCode'=>"",
			'_api'=>$depot['api_url'],
		);
		$customs_place=$depot['customs_place'];
		$calssname=$customs_place."_Api";
		$declare_data=new $calssname($config);
		$declare_data->to_order_declare($order,$expressname,$order_goods);
		$response=$declare_data->init();
		//var_dump($returndata);
		$stdclassobject =simplexml_load_string($response[0],null, LIBXML_NOCDATA);
		 $_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
         if(!empty($_array)){
            foreach ($_array as $key => $value){
                $value = (is_array($value) || is_object($value)) ? get_object_vars($value) : $value;
                $return_data[$key] = $value;
            }
        }
		if($return_data['Header']['Result']=="F"){
			show_json(0,$return_data['Header']['ResultMsg']);
		}
		//m("kjb2c")->to_declare($orderid);
	}
}