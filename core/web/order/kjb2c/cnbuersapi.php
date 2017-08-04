<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Cnbuersapi_EweiShopV2Page extends WebPage
{
	function main(){
	require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendorder.php");
		global $_W,$_GPC;
		$orderid=$_GPC['id'];

		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		if(!empty($order['cnbuyers_order_sn'])){
			$shipinfo=m("cnbuyerdb")->getOrderinvon($order['cnbuyers_order_sn']);
			if(!empty($shipinfo['invoice_no'])){
				$data = array();
            	$data['status'] = 2;
            	$data['express'] = $shipinfo['com_code'];
            	$data['expresscom'] = $shipinfo['shipping_name'];
            	$data['expresssn'] = $shipinfo['invoice_no'];
            	$data['sendtime'] = time();
            	pdo_update('ewei_shop_order', $data, array('id' => $order['id']));
            	m('notice')->sendOrderMessage($order['id']);
            	plog('order.op.send', "订单发货 ID: {$order['id']} 订单号: {$order['ordersn']} <br/>快递公司: {$shipinfo['shipping_name']} 快递单号: {$shipinfo['invoice_no']}");
            	show_json(1,"订单成功发货");
			}
			show_json(0,"订单已经推送请勿重复推送");
		}
		if(empty($order)){
			show_json(0,"订单错误");
		}
		load()->model('payment');
        $setting = uni_setting($order['uniacid'], array('payment'));
		$depot=pdo_fetch("select * from ".tablename("ewei_shop_depot")." where id=:id",array(":id"=>$order['depotid']));
		$order['address']=unserialize($order['address']);
		$ordergoods=pdo_fetchall("SELECT * from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(":orderid"=>$orderid));
		$sendorder=new Sendorder();
		$sendorder->init($order);

		$sendorder->params['shipping_id']=$depot['cnbuyershoping_id'];
		$sendorder->params['account_id']=$setting['payment']['wechat']['mchid'];
		$sendorder->init_out_goods($ordergoods);
		$data=$sendorder->iHttpPost();
		if(isset($data['errorcode'])){
			show_json(0,$data['errmsg']);
		}
		
		$redata=$sendorder->datadeencrypt($data[0]);
		$data=(array)json_decode($redata);
		pdo_update("ewei_shop_order",array("cnbuyers_order_sn"=>$data['order_sn']),array("id"=>$order['id']));
		show_json(1,"推送成功");
		
	}
}