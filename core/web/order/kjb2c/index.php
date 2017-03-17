<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once EWEI_SHOPV2_TAX_CORE. '/declare/NINGBO.declare.php';
class Index_EweiShopV2Page extends WebPage
{

	function index(){
		show_json(0,"111111");
	}
	function to_customs(){
		 global $_W,$_GPC;
		 $orderid=$_GPC['id'];
		 $order=pdo_fetch("SELECT zhuan_status,paymentno,if_customs_z,ordersn,paytype,price,depotid from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		
		$customs=m("kjb2c")->check_if_customs($order['depotid']);
		if(!$customs){
			show_json(0,"订单无需报关");
		}
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


	function send_order(){
		global $_W,$_GPC;
		$bool=false;
		$orderid=$_GPC['id'];
		$item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id = :id", array(':id' =>$orderid));
        if (empty($item)) {
            show_json(0, "未找到订单!");
        }
        if (empty($item['addressid'])) {
            show_json(0, '无收货地址，无法发货！');
        }
        if ($item['paytype'] != 3) {
            if ($item['status'] != 1) {
                show_json(0, '订单未付款，无法发货！');
            }
        }
		$rerundata=m("kjb2c")->send_order($orderid);

		$mft=(array)$rerundata['Body']['Mft'];
		$MftInfos=(array)$mft['MftInfos'];
		$MftInfo=(array)$MftInfos['MftInfo'];
		//var_dump($mft);
		$array=array(0=>array('Status'=>0,'Result'=>$mft['CheckMsg']));
		foreach ($MftInfo as $key=>$value) {
				$newval=(array)$value;
				if(!empty($newval)){
					$array[$key]=$newval;
				}
				if($newval['Status']==22){
					$bool=ture;
					$LogisticsName=$mft['LogisticsName'];
					$LogisticsNo=$mft['LogisticsNo'];
				}
		}
		//var_dump($mft);
		//var_dump($MftInfos);
		//var_dump($MftInfo);
		//die();
		if($bool){

			$change_data = array();
			$express=$LogisticsName;
			$expresscom=$LogisticsName;
			$expresssn=$LogisticsNo;
			if($express=="北仑军通"){
				$express="yuantong";
				$expresscom="圆通速递";
			}
			$change_data['express'] = $express;
			$change_data['expresscom'] = $expresscom;
			$change_data['expresssn'] = $expresssn;
			$change_data['status']=2;
			$change_data['sendtime'] = time();
			pdo_update('ewei_shop_order', $change_data, array('id' => $orderid));
			//取消退款状态
			if (!empty($item['refundid'])) {
	            $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $item['refundid']));
	            if (!empty($refund)) {
	            pdo_update('ewei_shop_order_refund', array('status' => -1, 'endtime' => $time), array('id' => $item['refundid']));
	            pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id']));
	                }
	        }

	        if ($item['paytype'] == 3) {
	                //处理订单库存
	             m('order')->setStocksAndCredits($item['id'], 1);
	        }
	        m('notice')->sendOrderMessage($item['id']);
            plog('order.op.send', "订单发货 ID: {$item['id']} 订单号: {$item['ordersn']} <br/>快递公司: {$_GPC['expresscom']} 快递单号: {$_GPC['expresssn']}");
			//show_json(1,"已发货");
		}
		include $this->template("order/message");
	}

	function to_zhuanz(){
		global $_W,$_GPC;
		$orderid=$_GPC['id'];
		$item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id = :id", array(':id' =>$orderid));
		if($item['zhuan_status']==1){
			show_json(0,'已经支付请到支付机构查看');
		}
		
		if(empty($item['realname'])|| empty($item['imid'])){
			show_json(0,'未找到身份证信息,请注意');
		}
		$order_sn=$item['ordersn']
		$pay_fee=$item['price'];
		$realname=$item['realname'];
		$imid=$item['imid'];
		$data=array(
			'pay_fee'=>$item['price'],
			'realname'=>$item['realname'],
			'imid'=>$item['imid'],
			'order_sn'=>$order_sn,
			'orderid'=>$orderid,
			'add_time'=>time(),
			);
		pdo_insert("ewei_shop_zpay_log",$data);
		require EWEI_SHOPV2_TAX_CORE. '/Transfer/Transfer.php';
        $payment=Transfer::getPayment("shenfupay");
        pdo_update("ewei_shop_order",array("zhuan_status"=>1),array("id"=>$orderid));
	}
}