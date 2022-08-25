<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
header("Content-type: text/html; charset=utf-8");
require_once EWEI_SHOPV2_TAX_CORE. '/customs/customs.php';
require_once EWEI_SHOPV2_TAX_CORE. '/declare/declare.php';
require_once EWEI_SHOPV2_TAX_CORE. '/orderpay/paybase.php';
class Kjb2c_EweiShopV2Model {
	function to_customs($param,$config,$paytype="wx"){
		$coustoms=customs::getObject($paytype,$config);
		$retdata=(array)$coustoms->to_customs($param,$config);
		//var_Dump($retdata);
		return $retdata;
	}
	//实名身份验证
	function isRealname($realname,$idno){
		global $_W,$_GPC;
		require_once EWEI_SHOPV2_TAX_CORE. 'orderpay/parmentUtil.php';
        $parment=new ParmentUtil();
        $retdata=$parment->isRealName($realname,$idno,$_W['openid']);
        return $retdata;
	}
	function _shenfupay($order){
		require_once EWEI_SHOPV2_TAX_CORE. '/orderpay/parmentUtil.php';
        $parment=new ParmentUtil();
        $ret=$parment->_transPay($order);
        if($ret['Code']==0){
        	$updatedata['if_customs_z']=1;
        	$updatedata['zhuan_status']=1;
        	pdo_update("ewei_shop_order",$updatedata,array("id"=>$order['id']));
        	if($order['deductcredit2']>0){
                $order['price']=$order['price']+$order['deductcredit2'];
            }
        	$zporderdata=array(
        		'orderid'=>$order['id'],
        		'order_sn'=>$order['ordersn'],
        		'pay_fee'=>$order['price'],
        		'paymentno'=>$ret['RespBody']['SerialNo'],
        		'add_time'=>time(),
        		'imid'=>$order['imid'],
        		'realname'=>$order['realname'],
        		'pay_time'=>time(),
        		'pay_code'=>'shenfupay',
        		);
        	pdo_insert("ewei_shop_zpay_log",$zporderdata);
        	return array("ret"=>1,"msg"=>'');
        }else{
        	return array("ret"=>0,"msg"=>$ret['Message']);
        }
	}
	function to_customs_new($orderid){
		global $_W,$_GPC;
			$order=pdo_fetch("SELECT zhuan_status,paymentno,uniacid,if_customs_z,isdisorder,isborrow,ordersn,paytype,price,depotid,deductcredit2 from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		$customs=m("kjb2c")->check_if_customs($order['depotid']);
		if(!$customs){
			show_json(0,"订单无需报关");
		}
        if($order['deductcredit2']>0){
            return error(-1,"余额暂时不报关支付单");
        }
		$depot=m("kjb2c")->get_depot($order['depotid']);

		 $params=array(
		 	'out_trade_no'=>$order['ordersn'],
		 	'transaction_id'=>$order['paymentno'],
		 	'customs'=>$customs,
		 	'mch_customs_no'=>$depot['customs_code'],
		 	);
//		 if($order['if_customs_z']==1 && $order['zhuan_status']==0){
//		 	 $smtorderinfo = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id limit 1'
//            , array(':id' => $orderid));
//		 	$bool=$this->_shenfupay($smtorderinfo);
//
//		 	show_json(1,"报关成功");
//		 }
		if($order['paytype']==21){
		 	load()->model('payment');
            $uniacid=$order['uniacid'];
		 	$jearray=Dispage::getDisaccountArray($uniacid);

		 	if(in_array($uniacid, $jearray) && $order['isdisorder']==1 && $order['isborrow']==1){
                 $uniacid=DIS_ACCOUNT;
                 //$params['order_sn']=$params['out_trade_no']."_borrow";
                 $params['out_trade_no']=$params['out_trade_no']."_borrow";

            }
            $depot=$this->get_depot($order['depotid']);
            $params['customs']=$depot['wx_customs_place'];

        	$setting = uni_setting($uniacid, array('payment'));
        	if (is_array($setting['payment']['wechat']) && $setting['payment']['wechat']['switch']) {
        		 $APPID = pdo_fetchcolumn('SELECT `key` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>$uniacid));
                    $config=array(
                    	"appid"=>$APPID,
                    	'mch_id'=>$setting['payment']['wechat']['mchid'],
                    	'apikey'=>$setting['payment']['wechat']['apikey'],
                    	);
                    $paytype="wx";
            }else{
            	return array("paytype"=>0,'retrundata'=>"微信支付已经关闭请重新开启");
            }

		}elseif($order['paytype']==22){
			load()->model('payment');
			$paytype="alipay";
			$uniacid=$_W['uniacid'];
			$setting = uni_setting($uniacid, array('payment'));
			if (is_array($setting['payment']['alipay']) && $setting['payment']['wechat']['switch']) {
				$config=array(
					'partner'=>$setting['payment']['alipay']['partner'],
					'key'=>$setting['payment']['alipay']['secret']
					);
			}
			$params=array(
				"order_sn" => $order['ordersn'],
				"customs_place" => $customs,//报关地点
				"trade_num"	=>$order['paymentno'],
				"amount" =>$order['price'],
				'customs'=>$customs,
				'merchant_customs_name'=>$depot['customs_name'],
				'mch_customs_no'=>$depot['customs_code'],
				);
		}
//		if( $order['if_customs_z']==1 && $order['zhuan_status']==1 ){
//			$sporder=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_zpay_log")." where order_sn=:ordersn",array(":ordersn"=>$order['ordersn']));
//			if($sporder['pay_code']=="shenfupay"){
//				$params = array(
//				"order_sn" => $order['ordersn'],
//				"customs_place" => $customs,//报关地点
//				"businessMode" => 'BONDED',
//				"trade_num"	=>$sporder['paymentno'],
//				"amount" =>$sporder['pay_fee'],
//				"shipping_fee"	=> 0,
//				"amount_tariff"	=> 0,
//				"memo" => '',
//				'mch_customs_no'=>$depot['customs_code'],
//				);
//			}
//
//			$config=array();
//			$paytype=$sporder['pay_code'];
//		}
		if(empty($paytype)){
			return array("paytype"=>0,'retrundata'=>"特殊支付方式请联系管理员");
		}

		$coustoms=customs::getObject($paytype,$config);


		$retrundata=(array)$coustoms->to_customs($params);
//        if($order['ordersn']=="SHCG2021071115030165"){
//            var_dump($coustoms->get_values());
//            die();
//        }
        if(isset($retrundata['is_success'])){
            $response=(array)$retrundata['response'];
            $response=(array)$response['alipay'];
            pdo_update("ewei_shop_pay_request",array("verDept"=>$response['ver_dept']),array("order_sn"=>$order['ordersn'],'pay_type'=>22));
        }
        if(isset($retrundata['return_code'])){
            $ver_dept=3;
            if($retrundata['verify_department']=='UNIONPAY'){
                $verDept=1;
            }elseif($retrundata['verify_department']=='NETSUNION'){
                $verDept=2;
            }
            pdo_update("ewei_shop_pay_request",array("verDept"=>$verDept),array("order_sn"=>$order['ordersn'],'pay_type'=>21));
        }

		return array("paytype"=>$paytype,'retrundata'=>$retrundata);
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

		//WeUtility::logging('to_declareparams1', var_export("进入申报", true));
		$depotid=0;
		if($order_table=="ewei_shop_order"){
			$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
			//WeUtility::logging('to_declareorder', var_export($order, true));
			$depotid=$order['depotid'];
			$order_goodssql="SELECT og.*,g.disgoods_id,g.dispatchid,g.unit,g.weight,g.title from "
						.tablename("ewei_shop_order_goods")." as og "
						."LEFT JOIN ".tablename("ewei_shop_goods")." as g on og.goodsid=g.id "
						." where orderid=:orderid";
			$order_goods=pdo_fetchall($order_goodssql,array(":orderid"=>$orderid));
			//WeUtility::logging('to_declareorder_goods', var_export($order_goods, true));
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
			//WeUtility::logging('to_declareparams', var_export($order, true));
			if($order['if_customs_z']==1 && $order['zhuan_status']==1){
				$sporder=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_zpay_log")." where order_sn=:ordersn",array(":ordersn"=>$order['ordersn']));
				$order['paytype']=23;
				$order['paymentno']=$sporder['paymentno'];
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
				return array("status"=>0,"msg"=>"自建商品无法申报");
			}

			$openid=$order['openid'];
			$order['ordersn']=$order['orderno'];
			$order['weight']=$Weight;
			$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $order['uniacid'], ':openid' => $openid, ':id' => $order['addressid']));
			$address=serialize($address);
			$order['address']=$address;
			$order['dpostfee']=$order['depostfee'];
			$order['realname']=$order['srealname'];
			$order['tax_rate']=empty($order['vat_rate']) ?0 : $order['vat_rate'];
			$order['tax_consumption']=empty($order['consumption_tax']) ?0 : $order['consumption_tax'];
			if($order['pay_type']=="wechat"){
				$order['paytype']=21;
			}
			$order['price']=$order['price']+$order['freight'];
			//$order['dpostfee']="00";
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
			
			return array("status"=>0,"msg"=>"订单无须申报");
		}
		$customs=$this->check_if_customs($depotid);
		if(empty($customs)){
			return array("status"=>0,"msg"=>"申报地址错误");
		}
		$declare=DeclareCore::getObject($customs,$depot);
		$declare->to_order_declare($order,$expressname,$order_goods);
		$response=$declare->init();
		$stdclassobject =@simplexml_load_string($response[0],null, LIBXML_NOCDATA);
		$_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
         if(!empty($_array)){
            foreach ($_array as $key => $value){
                $value = (is_array($value) || is_object($value)) ? get_object_vars($value) : $value;
                $return_data[$key] = $value;
            }
        }
        
        if($return_data['Header']['Result'] == 'F'){
        	return array("status"=>0,'msg'=>$return_data['Header']['ResultMsg']);
        }else{
        	$rest=pdo_update($order_table, array('mftno' => $return_data['Header']['MftNo']), array('id' => $orderid));
        	WeUtility::logging('to_declare', var_export($return_data, true)."order_id:".$orderid);
      
        	WeUtility::logging('to_declare', var_export($rest, true)."更新结果");
        }
        return array("status"=>1,'msg'=>"ok");
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
		//获取一下申报单
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
		global $_W;
		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		$disInfo=Dispage::getDisInfo($uniacid);
		
		 if($disInfo['secondpaytype']==0 && $disInfo['autoretainage']==1 && $disInfo['secondpay']==1){//需要二次支付和自动付款
		 	
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
                if($returncode['status']==0){
                	 $ret=m("kjb2c")->sendOmsorder($orderid);
                    //m("kjb2c")->to_declare($orderid);
                    WeUtility::logging('代理支付推单', var_export($ret, true));
                }
                if($uniacid==26){
					WeUtility::logging('代理支付状态', var_export($returncode, true));
				}
               
                return $returncode;
		 	}

		 }
	}
	function cnec_jh_cancel($depotid,$mftno){
		$depot=Dispage::getDepot($depotid);
		if($depot['if_declare']!=1){
			show_json(0,"订单无须申报");
		}
		$customs=$this->check_if_customs($depotid);
		$declare=DeclareCore::getObject($customs,$depot);
		return $declare->cnec_jh_cancel($mftno);
	}
	function sendOmsorder($orderid,$type="ewei_shop_order"){
		require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");
		$order=pdo_fetch("SELECT * from ".tablename($type)." where id=:id",array(":id"=>$orderid));
		if(!empty($order['cnbuyers_order_sn'])){
			return array("status"=>0,"msg"=>"重复");
		}
		$sendorder=new SendOmsapi();
		if($type=="ewei_shop_order"){
			
				$sql="SELECT og.*,g.disgoods_id from ".tablename("ewei_shop_order_goods")." as og".
			" LEFT JOIN ".tablename("ewei_shop_goods")." as g ON og.goodsid=g.id".

			" where og.orderid=:orderid";
			$ordergoods=pdo_fetchall($sql,array(":orderid"=>$orderid));
			foreach($ordergoods as $goods){
					$dispatchid=$goods['dispatchid'];
					$Weight+=$goods['weight']*$goods['total'];
					if($order['dispatchid']==0){
						$dispatch_data = m('dispatch')->getDefaultDispatch(0,$goods['disgoods_id'],$goods['goodsid']);//wsq
					}else{
						$dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid'],$goods['disgoods_id']);//wsq
					}
				}
			$order['express']=$dispatch_data['express'];
		}
		if($type=="ewei_shop_groups_order"){
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
				return array("status"=>0,"msg"=>"自建商品无法推单");
			}
			$openid=$order['openid'];
			$order['ordersn']=$order['orderno'];
			$order['weight']=$Weight;
			$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $order['uniacid'], ':openid' => $openid, ':id' => $order['addressid']));
			$order['realName']=
			$order['address']=serialize($address);
			$order['paytype']="T";
			$order['realname']=$order['srealname'];
			$order['tax_rate']=empty($order['vat_rate']) ?0 : $order['vat_rate'];
			$order['tax_consumption']=empty($order['consumption_tax']) ?0 : $order['consumption_tax'];
			if($order['pay_type']=="wechat"){
				$order['paytype']=21;
			}
			$order['dispatchprice']=$order['freight'];
			$order['goodsprice']=$order['price'];
			$order['price']=$order['price']+$order['freight'];
			//$order['dpostfee']="00";
			$expressname=pdo_fetchcolumn("select `name` from ".tablename("ewei_shop_express")." where express=:express",array(":express"=>$dispatch_data['express']));
			$ordergoods[]=array(
				'goodssn'=>$order['goodssn'],
				'title'=>$order['title'],
				'total'=>$order['goodsnum'],
				'unit'=>$order['units'],
				'price'=>($order['price']-$order['freight'])/$order['goodsnum'],
				'realprice'=>($order['price']-$order['freight'])/$order['goodsnum'],
				);
			
		}

		$ret=$sendorder->sendorder($order,$ordergoods);

		if(empty($ret)){
			return array("status"=>1,"msg"=>"error");
		}

		if($ret['error']==0){
			$order_sn='';
			foreach ($ret['data'] as $key => $value) {
				$order_sn.=$value['order_sn']."|";
			}
			pdo_update($type,array("cnbuyers_order_sn"=>$order_sn),array("id"=>$order['id']));
			return array("status"=>1,"msg"=>"ok");
		}else{
			return array("status"=>1,"msg"=>$ret['message']);
		}

	}

	function getOrderinvoice_no($depotid,$ordersn){
		require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");
		$sendorder=new SendOmsapi();
		$ret=$sendorder->selectOrderShipping($depotid,$ordersn);
		
		return $ret;
	}
	function sendOrder($orderid){
		require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendorder.php");
		$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		if(empty($order) || !empty($order['cnbuyers_order_sn'])){
			return false;
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
		$sendorder->init_out_goods($ordergoods,$order['uniacid'],$order['isdisorder']);
		$data=$sendorder->iHttpPost();
		if(isset($data['errorcode'])){
			return false;
		}
		$redata=$sendorder->datadeencrypt($data[0]);
		$data=(array)json_decode($redata);
		pdo_update("ewei_shop_order",array("cnbuyers_order_sn"=>$data['order_sn']),array("id"=>$order['id']));
	}
}