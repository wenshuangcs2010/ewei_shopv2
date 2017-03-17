<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once(EWEI_SHOPV2_CORE_WEB . 'orderpay/order/wxpaydata.php');
require_once(EWEI_SHOPV2_CORE_WEB . 'orderpay/order/wxpayapi.php');
require_once(EWEI_SHOPV2_TAX_CORE . 'tax_core.php');
class orderpay_weixin_EweiShopV2Page extends WebPage {
	//企业付款
	function orderpay(){
		global $_W, $_GPC;
		$service=new Tax();
		$order_id=$_GPC['order_id'];
		if(empty($order_id)){
			$order_list=pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_order")." WHERE uniacid=:uniacid and depotid <>0 
and status=1 and paystatus=0",array(":uniacid"=>$_W['uniacid']));
			$dis_amout=0;
			//var_dump($order_list);
			foreach($order_list as $order_info){
				$temp_dis_amout=$service->dis_order_tax($order_info['id']);
				//var_dump($temp_dis_amout);
				if($temp_dis_amout==0){
					continue;
				}
				$dis_amout+=$temp_dis_amout;
					//echo $dis_amout;
				$order_id.=$order_info['id'].",";
			}
			$order_id=rtrim($order_id, ",");
		}
		if(is_numeric($order_id)){
			$order=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE uniacid=:uniacid and depotid <>0 
and status=1 and paystatus=0 and id=:id",array(":uniacid"=>$_W['uniacid'],':id'=>$order_id));
			if(empty($order)){
				$msg="订单错误请和管理员联系";
				show_json(0,array('message'=>$msg));
			}
			$dis_amout=$service->dis_order_tax($order_id);
			if($dis_amout==0){
				$msg="代理金额错误".$dis_amout;
				show_json(0,array('message'=>$msg));
			}
		}
		if(empty($order_id)){
			$msg="所有订单已经被支付";
			  show_json(0,array('message'=>$msg));
		}
		$sql="SELECT * FROM ".tablename("ewei_order_pay")." WHERE uniacid=:uniacid and pay_fee=:pay_fee and order_ids=:order_ids and status=0";
		$orderpay_order=pdo_fetch($sql,array(":uniacid"=>$_W['uniacid'],":pay_fee"=>$dis_amout*100,":order_ids"=>$order_id));
		$dispayorder_sn=$service->createNO("order_pay","id","dis");//生成订单号
		$openid=pdo_fetchcolumn("select openid FROM ".tablename("ewei_shop_resellerlevel")." where Accountsid=:uniacid ",array("uniacid"=>$_W['uniacid']));
        if(empty($openid)){
        	$msg="没有设置支付账号,请和管理员联系";
		    show_json(0,array('message'=>$msg));
        }
		if(empty($orderpay_order)){
			$datarr=array(
	            	'order_ids'=>$order_id,
	            	'uniacid'=>$_W['uniacid'],
	            	'pay_fee'=>$dis_amout*100,
	            	'order_sn'=>$dispayorder_sn,
	            	'pay_code'=>0,
	            	'pay_name'=>"微信",
	            	'openid'=>$openid,
	            	'pay_message'=>$order_info['desc'],
	                'transaction_id'=>$returnRes['payment_no'],
	                'status'=>0,
	                'paytype'=>1,//企业付款
	                'pay_times'=>$_W['timestamp'],
	                'pay_message'=>"代理商手动付款",
	        );
			pdo_insert("ewei_order_pay",$datarr);
			$orderpay_id=pdo_insertid();
		}else{
			$datarr=array(
	            	'order_ids'=>$order_id,
	            	'pay_fee'=>$dis_amout*100,
	            	'order_sn'=>$dispayorder_sn,
	        );
	        pdo_update("ewei_order_pay",$datarr,array("id"=>$orderpay_order['id']));
	        $orderpay_id=$orderpay_order['id'];
		}
		$ret=$service->dispayweixin($orderpay_id,$_W['uniacid']);
		if($ret['ret']!=0){

			 show_json(0,array('message'=>$ret['messsage']));
		}
		if($ret['ret']==0){
			 show_json(1,array('message'=>$ret['messsage']));
		}
	}
	function testorder(){
		$order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:id",array(":id"=>196));
		$ret=m("order")->declareOrder($order_info);
		var_dump($ret);
	}
}