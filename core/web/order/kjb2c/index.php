<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
header("Content-type: text/html; charset=utf-8");
require_once EWEI_SHOPV2_TAX_CORE. '/declare/NINGBO.declare.php';
class Index_EweiShopV2Page extends WebPage
{

	function index(){
		show_json(0,"111111");
	}
	function to_customs(){
		 global $_W,$_GPC;
		 $orderid=$_GPC['id'];

		 $returndata=m("kjb2c")->to_customs_new($orderid);

		 $paytype=$returndata['paytype'];

		 $returndata=$returndata['retrundata'];


		 switch ($paytype) {
			case 'wx':

			if($returndata['result_code']=="FAIL"){
                	show_json(0,$returndata['err_code_des']);
             }else{
                	if($returndata['result_code']=="FAIL"){
						show_json(0,$returndata['err_code_des']);
                	}
					show_json(1,$returndata['return_msg']);
                }
                if(isset($retrundata['errno'])){
		 			show_json(0,$returndata['message']);
		 		}
				break;
			case 'shenfupay':
				show_json(0,$returndata['message']);
				break;

			case "alipay":
				if($returndata['is_success']=="F"){
					show_json(0,"失败");
				}else{


					$response=(array)$returndata['response'];
					$alipay=(array)$response['alipay'];
					show_json(1,$alipay['result_code']);
				}
				break;
			default:
				show_json(0,"未知错误");
				break;
		}
	}
	function to_declare(){
		global $_W,$_GPC;
		$orderid=$_GPC['id'];
		$ret=m("kjb2c")->to_declare($orderid);
		if($ret['status']==1){
			show_json(1,'ok');
		}else{
			show_json(0,$ret['msg']);
		}
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
			
			$expresscom=$LogisticsName;
			$expresssn=$LogisticsNo;
			if($expresscom=="北仑军通"){
				$express="yuantong";
				$expresscom="圆通速递";
			}else{
				$express="shunfeng";
				$expresscom="顺丰速运";
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
		$ret=m("kjb2c")->_shenfupay($item);
		if($ret['ret']==1){
			show_json(1,"ok");
		}else{
			show_json(0,$ret['msg']);
		}
	}
}