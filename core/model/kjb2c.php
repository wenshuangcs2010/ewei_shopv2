<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once EWEI_SHOPV2_TAX_CORE. '/customs/customs.php';
require_once EWEI_SHOPV2_TAX_CORE. '/declare/declare.php';
class Kjb2c_EweiShopV2Model {
	function to_customs($param,$config,$paytype="wx"){
		$coustoms=customs::getObject($paytype,$config);
		$retdata=(array)$coustoms->to_customs($param,$config);
		//var_Dump($retdata);
		return $retdata;
	}
	function get_depot($id){
		return pdo_fetch("SELECT * from ".tablename("ewei_shop_depot")." where id=:id",array(":id"=>$id));
	}
	function check_if_customs($id){
		$depot=$this->get_depot($id);
		if(!empty($depot)){
			if($depot['if_customs']==1){
				return $depot['customs_place'];
			}
		}
		return false;
	}
	function to_declare($orderid,$order_table="ewei_shop_order"){
		$depotid=0;
		if($order_table=="ewei_shop_order"){
			$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
			$depotid=$order['depotid'];
			$order_goodssql="SELECT og.*,g.disgoods_id,g.dispatchid,g.unit,g.weight,g.title from "
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
			$order['tax_rate']=empty($order['tax_rate']) ?0 : $order['tax_rate'];
			$order['tax_consumption']=empty($order['tax_consumption']) ?0 : $order['tax_consumption'];
			$order['weight']=$Weight;
			$expressname=pdo_fetchcolumn("select `name` from ".tablename("ewei_shop_express")." where express=:express",array(":express"=>$dispatch_data['express']));
		}
		if($order_table=="ewei_shop_groups_order"){
			$sql="SELECT o.*,g.goodssn,g.title,g.goodsnum,g.units,g.gid from ".tablename("ewei_shop_groups_order")." as o "
			."left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid"
			." where o.id=:id ";
			$order=pdo_fetch($sql,array(":id"=>$orderid));
			$depotid=$order['depotid'];
			if($order['dispatchid']==0){
					$dispatch_data = m('dispatch')->getDefaultDispatch(0,$order['disgoods_id'],$order['gid']);//wsq
				}else{
					$dispatch_data = m('dispatch')->getOneDispatch($order['dispatchid'],$goods['disgoods_id']);//wsq
				}
			$Weight=0;
			if($order['gid']>0){
				$Weight=pdo_fetchcolumn("SELECT weight from ".tablename("ewei_shop_goods")."where id=:id",array(":id"=>$order['gid']));

				$Weight=$Weight*$order['goodsnum'];
			}else{
				show_json(0,"自建商品无法申报");
			}

			$openid=$order['openid'];
			$order['ordersn']=$order['orderno'];
			$order['weight']=$Weight;
			$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $order['uniacid'], ':openid' => $openid, ':id' => $order['addressid']));
			$address=serialize($address);
			$order['address']=$address;
			$order['dpostfee']=$order['depostfee'];
			$order['tax_rate']=empty($order['vat_rate']) ?0 : $order['vat_rate'];
			$order['tax_consumption']=empty($order['consumption_tax']) ?0 : $order['consumption_tax'];
			//var_Dump($order['tax_rate']);
			//die();
			$expressname=pdo_fetchcolumn("select `name` from ".tablename("ewei_shop_express")." where express=:express",array(":express"=>$dispatch_data['express']));
			$order_goods[]=array(
				'goodssn'=>$order['goodssn'],
				'title'=>$order['title'],
				'total'=>$order['goodsnum'],
				'unit'=>$order['units'],
				'dprice'=>$order['dprice'],
				);
			//var_dump($expressname);
			//die();
		}
		if($expressname=="圆通速递"){
			$expressname="北仑军通";
		}
		$depot=Dispage::getDepot($depotid);
		if($depot['if_declare']!=1){
			show_json(0,"订单无须申报");
		}
		$customs=$this->check_if_customs($depotid);
		if(empty($customs)){
			show_json(0,"申报地址错误");
		}
		$declare=DeclareCore::getObject($customs,$depot);
		$declare->to_order_declare($order,$expressname,$order_goods);
		$response=$declare->init();
		$stdclassobject =simplexml_load_string($response[0],null, LIBXML_NOCDATA);
		$_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
         if(!empty($_array)){
            foreach ($_array as $key => $value){
                $value = (is_array($value) || is_object($value)) ? get_object_vars($value) : $value;
                $return_data[$key] = $value;
            }
        }
       
        if($return_data['Header']['Result'] == 'F'){
               	show_json(0,$return_data['Header']['ResultMsg']);
        }else{
        	pdo_update($order_table, array('mftno' => $return_data['Header']['MftNo']), array('id' => $orderid));
        }
        
        show_json(1,"ok");
	}
	function get_groups_order_goods($gid){
		$sql="SELECT * FROM ".tablename("ewei_shop_goods")." WHERE id=:gid";
		$goods_info=pdo_fetch($sql,array(":gid"=>$goods['gid']));
		$goods['weight']=$goods_info['weight'];
        $goods['depotid']=$goods_info['depotid'];
        $goods['disgoods_id']=$goods_info['disgoods_id'];
	}
	function alpay_disorder($post,$trade_no){
		$dispayorder=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order_dispay")." where order_sn=:order_sn",array(":order_sn"=>$trade_no));
		//echo $trade_no;
		if(empty($dispayorder)){
			//echo "not dispayorder";
			return false;
		}
		if($dispayorder['status']==2){
			//echo "已经支付";
			return false;
		}
		$total_fee=$post['total_fee']*100;
		$ordertotal_fee=$dispayorder['pay_fee'];
		if($ordertotal_fee!=$total_fee){
			return false;
		}
		$dispaydata=array("status"=>2,'paymentno'=>$post['trade_no'],'pay_time'=>time());
		if($dispayorder['status']==0){
			$dispaydata['pay_message']="过期订单被支付";
		}
		pdo_update("ewei_shop_order_dispay",$dispaydata,array('id'=>$dispayorder['id']));
		$orderdata=array(
			'paystatus'=>2,
			);
		pdo_update($dispayorder['order_table'],$orderdata,array('id'=>$dispayorder['order_id']));
		 m("kjb2c")->to_declare($dispayorder['order_id']);
	}


	function send_order($orderid,$order_table="ewei_shop_order"){
	
		$order=pdo_fetch("SELECT * FROM ".tablename($order_table)." where id=:id",array(":id"=>$orderid));
		//var_dump($order);
		$depot=Dispage::getDepot($order['depotid']);
		if($depot['if_declare']!=1){
			show_json(0,'当前订单无需申报');
		}
		$customs=$this->check_if_customs($order['depotid']);
		if(empty($customs)){
			show_json(0,"申报地址错误");
		}
		$declare=DeclareCore::getObject($customs,$depot);
		$response=$declare->cnec_jh_decl_byorder($order['mftno']);
		$stdclassobject =simplexml_load_string($response[0],null, LIBXML_NOCDATA);
		$_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
         if(!empty($_array)){
            foreach ($_array as $key => $value){
                $value = (is_array($value) || is_object($value)) ? get_object_vars($value) : $value;
                $return_data[$key] = $value;
            }
        }
        return $return_data;
	}

	//wx代理支付
	function pay_disorder_wx($orderid,$uniacid){
		
		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		$depot=m("kjb2c")->get_depot($order['depotid']);
		 if($depot['secondpaytype']==0 && $depot['autoretainage']==1 && $depot['secondpay']==1){//需要二次支付和自动付款
		 	$payfee=$order['disorderamount'];
		 	$disorder_sn=Dispage::createNO("shop_order_dispay","id","dis");//生成订单号
		 	$orderinfo=pdo_fetch("SELECT * from ".tablename("ewei_shop_order_dispay")." where order_id=:orderid ",array(":orderid"=>$orderid));
		 	if($orderinfo['status']!=2 && $payfee!=0){
		 		$orderpay=array(
                    'order_sn'=>$disorder_sn,
                    'desc'=>"代理商自动扣款",
                    'pay_fee'=>$payfee*100,
                );
                $disInfo=Dispage::getDisInfo($uniacid);
                $orderpaydata=array(
                    'order_sn'=>$disorder_sn,
                    'pay_fee'=>$payfee*100,
                    'status'=>1,
                    'order_id'=>$orderid,
                    'pay_code'=>"wx",
                    'openid'=>$disInfo['openid'],
                    'uniacid'=>$uniacid,
                    'pay_type'=>0,
                    'create_time'=>$_W['timestamp'],
                    'order_table'=>"ewei_shop_order",
                    'pay_message'=>"代理商自动扣款",
                );
                pdo_insert("ewei_shop_order_dispay",$orderpaydata);
                load()->model('payment');
                $setting = uni_setting($uniacid, array('payment'));
                $options = $setting['payment']['wechat'];
                $uniacidAPP = pdo_fetch('SELECT `key`,`secret` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>$uniacid));
                $options['appid']=$uniacidAPP['key'];
                $options['secret'] = $uniacidAPP['secret'];
                $config=array(
                    'appId'=>$options['appid'],
                    'mchid'=>$options['mchid'],
                    'key'=>$options['apikey'],
                    'openid'=>$disInfo['openid'],
                   );
                $payment=paybase::getPayment('wx',$config);
                $returncode=$payment->buildRequestForm($orderpay);
                WeUtility::logging('支付结果', var_export($returncode, true));
                if($returncode['status']==0){
                    m("kjb2c")->to_declare($orderid);
                }
		 	}

		 }
	}
}