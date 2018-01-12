<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");
class OmsApi_EweiShopV2Page extends WebPage
{

	function main(){
		global $_W,$_GPC;
		$orderid=$_GPC['id'];
		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		
		$sendorder=new SendOmsapi();
		if(!empty($order['cnbuyers_order_sn'])){
			$ret=$sendorder->selectOrderShipping($order['depotid'],$order['ordersn']);
		
			if($ret['error']!=0){
				show_json(0,$ret['message']);
			}
    		$data = array();
        	$data['status'] = 2;
        	$data['express'] = $ret['data']['shipping_code'];
        	$data['expresscom'] = $ret['data']['shipping_name'];
        	$data['expresssn'] = $ret['data']['invoice_no'];
        	$data['sendtime'] = time();

        	pdo_update('ewei_shop_order', $data, array('id' => $order['id']));
        	m('notice')->sendOrderMessage($order['id']);
        	plog('order.op.send', "订单发货 ID: {$order['id']} 订单号: {$order['ordersn']} <br/>快递公司: {$shipinfo['shipping_name']} 快递单号: {$shipinfo['invoice_no']}");
        	show_json(1,$ret['message']);
		}
		$ret=m("kjb2c")->sendOmsorder($orderid);
		if($ret['status']==0){
			show_json(0,$ret['msg']);
		}else{
			show_json(1,$ret['msg']);
		}

	
	}
	
}