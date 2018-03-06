<?php
require EWEI_SHOPV2_TAX_CORE. '/orderpay/paybase.php';
require_once(EWEI_SHOPV2_TAX_CORE . 'tax_core.php');
class orderpay_EweiShopV2Page extends WebPage {

	function main(){
		global $_W, $_GPC;
		$paymentcode=array(
			'0'=>"wx",
			'1'=>"shengpay",
			'2'=>'alipay',
			);
		$type=empty($_GPC['type']) ?0:intval($_GPC['type']);
		$disInfo=Dispage::getDisInfo($_W['uniacid']);
		//$tax=new Tax();
		$orderid=$_GPC['order_id'];
		//$payfee=$tax->get_dis_order_amount($orderid,$type);
		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		$payfee=$order['disorderamount'];
		$order_sn=Dispage::createNO("shop_order_dispay","id","dis");//生成订单号
		$orderinfo=pdo_fetch("SELECT * from ".tablename("ewei_shop_order_dispay")." where order_id=:orderid ",array(":orderid"=>$orderid));

		if(!empty($disInfo)){
			if($disInfo['secondpaytype']==0){
				if($orderinfo['status']==2){
					show_json(0,"订单已经支付");
				}
				if($payfee==0){
					show_json(0,"代理订单错误");
				}
				$order=array(
		            'order_sn'=>$order_sn,
		            'desc'=>"代理商自动扣款",
		            'pay_fee'=>$payfee*100,
	        	);
	        	$orderpaydata=array(
		        		'order_sn'=>$order_sn,
		        		'pay_fee'=>$payfee*100,
		        		'status'=>1,
		        		'order_id'=>$orderid,
		        		'pay_code'=>"wx",
		        		'openid'=>$disInfo['openid'],
		        		'uniacid'=>$_W['uniacid'],
		        		'pay_type'=>0,
		        		'create_time'=>$_W['timestamp'],
		        		'order_table'=>"ewei_shop_order",
		        		'pay_message'=>"代理商手动扣款",
	        		);
	        	//if(empty($orderinfo)){
	        		pdo_insert("ewei_shop_order_dispay",$orderpaydata);
	        	//}else{
	        	//	pdo_update("ewei_shop_order_dispay",$orderpaydata,array("order_id"=>$orderid));
	        	//}
	        	
				load()->model('payment');
	 			 $setting = uni_setting($_W['uniacid'], array('payment'));
	 			 if (is_array($setting['payment']['wechat'])){
	 			 	    $options = $setting['payment']['wechat'];
                        $options['appid'] = $_W['account']['key'];
                        $options['secret'] = $_W['account']['secret'];
                        $config=array(
	 			 			'appId'=>$_W['account']['key'],
	 			 			'mchid'=>$options['mchid'],
	 			 			'key'=>$options['apikey'],
	 			 			'openid'=>$disInfo['openid'],
	 			 		);
	 			 }else{
	 			 	show_json(0,"微信支付未配置");
	 			 }
			}
			
			if($disInfo['secondpaytype']==1){
				if($orderinfo['status']==2){
					show_json(0,"订单已经支付");
				}
				if($payfee==0){
					$this->message("代理订单错误");
				}
				$order=array(
        			'out_trade_no'=>$order_sn,
        			'subject'=>"微分销代理支付",
        			'total_fee'=>$payfee,
        			'body'=>"微分销代理支付",
        			);
        		$orderpaydata=array(
		        		'order_sn'=>$order_sn,
		        		'pay_fee'=>$payfee*100,
		        		'status'=>1,
		        		'order_id'=>$orderid,
		        		'pay_code'=>"shengpay",
		        		'openid'=>"",
		        		'uniacid'=>$_W['uniacid'],
		        		'create_time'=>$_W['timestamp'],
		        		'pay_type'=>2,
		        		'order_table'=>"ewei_shop_order",
	        		);
	        	//if(empty($orderinfo)){
	        		pdo_insert("ewei_shop_order_dispay",$orderpaydata);
	        	//}else{
	        		//pdo_update("ewei_shop_order_dispay",$orderpaydata,array("order_id"=>$orderid));
	        	//}
	        	$config=array("shengpay_account"=>"10114414",'shengpay_key'=>"zjcof85779533hjs");

			}
			if($disInfo['secondpaytype']==2){
				if($orderinfo['status']==2){
					show_json(0,"订单已经支付");
				}
				if($payfee==0){
					$this->message("代理订单错误");
				}
				//获取主站支付宝支付配置
				load()->model('payment');
        		$setting = uni_setting(DIS_ACCOUNT, array('payment'));

        		if (is_array($setting['payment']['alipay']) && $setting['payment']['alipay']['switch']) {
        			 $options = $setting['payment']['alipay'];
        			 $config=array(
        			 	'partner'=>$options['partner'],
        			 	'key'=>$options['secret'],
        			 	);
        			 //var_dump($options);
        			 
        		}
        		$order=array(
        			'out_trade_no'=>$order_sn,
        			'subject'=>"微分销代理支付",
        			'total_fee'=>$payfee,
        			'body'=>"微分销代理支付",
        			);
        		$orderpaydata=array(
		        		'order_sn'=>$order_sn,
		        		'pay_fee'=>$payfee*100,
		        		'status'=>1,
		        		'create_time'=>$_W['timestamp'],
		        		'order_id'=>$orderid,
		        		'pay_code'=>"alipay",
		        		'openid'=>"",
		        		'uniacid'=>$_W['uniacid'],
		        		'pay_type'=>1,
		        		'order_table'=>"ewei_shop_order",
	        		);
	        	//if(empty($orderinfo)){
	        		pdo_insert("ewei_shop_order_dispay",$orderpaydata);
	        	//}else{
	        		//pdo_update("ewei_shop_order_dispay",$orderpaydata,array("order_id"=>$orderid));
	        	//}
			}
			$payment=paybase::getPayment($paymentcode[$disInfo['secondpaytype']],$config);
			$retrundata=$payment->buildRequestForm($order);
			if($retrundata['status']==0){
				$ret=m("kjb2c")->sendOmsorder($orderid);
                   
                WeUtility::logging('代理支付推单', var_export($ret, true));
				show_json(1,"ok");
			}else{
				show_json(0,$retrundata['message']);
			}
			
		}else{
			show_json(0,"未设置代理结算");
		}
	}
	function order_return(){
		global $_W, $_GPC;
		include $this->template();
	}
}