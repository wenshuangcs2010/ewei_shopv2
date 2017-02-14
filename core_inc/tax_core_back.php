<?php
	class Tax{
		/**
		 * [core_tax description]
		 * @param  [type]  $goods_price           申报价格
		 * @param  integer $vat_rate        增值税率
		 * @param  integer $consumption_tax 消费税率
		 * @param  integer $tariff          关税税率
		 * @return [type]                   ARRAY
		 */
		public function core_tax($goods_price,$vat_rate=0,$consumption_tax=0,$tariff=0){
			//法定关税税额
			$charge_tariff=$goods_price*$tariff;
			//法定消费税额
			$charge_consumption_tax=$goods_price/(1-$consumption_tax)*$consumption_tax;
			//法定增值税税额
			$charge_rate=($goods_price+$charge_consumption_tax)*$vat_rate;
			//var_dump($charge_rate);
			//应征消费税额
			$draft_consumption_tax=$charge_consumption_tax*0.7;
			//应征增值税税额
			$draft_rate=$charge_rate*0.7;
			//综合税
			$consolidated_tax=($charge_consumption_tax+$charge_rate+$charge_tariff)*0.7;
			return array('charge_tariff'=>$charge_tariff,'consumption_tax'=>$draft_consumption_tax,'rate'=>$draft_rate,'consolidated'=>$consolidated_tax);
		}
		/**
		 * 获取分摊运费后的价格
		 * @param  [type] $goods_price  申报价格
		 * @param  [type] $goods_total  出售商品总价
		 * @param  [type] $shipping_fee 出售商品运费
		 * @param  [type] $quantity     购买的数量
		 * @return [type]              Array
		 */
		public function get_dprice($goods_price,$goods_total,$shipping_fee,$quantity){
			$split_shipping_fee=$goods_price/$goods_total*$shipping_fee;//每件商品拆分的运费
			$split_shipping_fee=$this->get_decimal_price($split_shipping_fee);
			return array('dprice'=>$goods_price+$split_shipping_fee,"split_shipping_fee"=>$split_shipping_fee);
		}
		/**
		 * 逆推申报价格
		 * @param  [type] $goods_price     售价
		 * @param  [type] $vat_rate        增值税率
		 * @param  [type] $consumption_tax 消费税率
		 * @return [type]                  INt
		 */
		private function get_taxprice($goods_price,$vat_rate,$consumption_tax){
			$caounts=0.7*($consumption_tax+$vat_rate)/(1-$consumption_tax);//综合税税率
			$price=$goods_price/$caounts;
			return $this->get_decimal_price($price);
		}

		
		//计算 申报单价
		function  get_cartgoods(&$allgoods,$shping_fee,$isdis=false){
			$before_tax_price=0;
			foreach($allgoods as &$goods){
				$goods_tax=pdo_fetch("select vat_rate,consumption_tax FROM ".tablename("ewei_shop_goods")." WHERE id=:id",array(":id"=>$goods['goodsid']));
				$goods['tax']=$this->get_compositetaxrate($goods_tax['vat_rate'],$goods_tax['consumption_tax']);
				$goods['vat_rate']=$goods_tax['vat_rate'];
				$goods['consumption_tax']=$goods_tax['consumption_tax'];
				$goods['dprice']=$goods['marketprice']/(1+$goods['tax']);

				if($isdis){
					$goods['dprice']=$goods['disprice']/(1+$goods['tax']);
				}
				$before_tax_price+=$goods['dprice']*$goods['total'];
				$result["$goods[tax]"][]=$goods;//将相同税率的数组归纳在一起
			}
			unset($goods);
			//var_dump($before_tax_price);
			foreach($result as $k=> $v){
				$goods_amount=0;
				foreach($v as $goods){
						$goods_amount+=$goods['dprice']*$goods['total'];//每种税率申报商品总价
				}
				$tax_all[$k]=array("ratio"=>$goods_amount/$before_tax_price,'goods_amount'=>$goods_amount);//求出每种税率所占全部金额的比例
			}
			foreach ($tax_all as $key => $value) {
				$test+=(1+$key)*$value['ratio'];
			}
			$shengbao_shping_fee=$shping_fee/$test;//申报总运费
			foreach ($tax_all as $key => $value) {
				$shping_fee_tax[$key]=$shengbao_shping_fee*$value['ratio'];//每种税率分配的总运费
			}
			foreach($allgoods as &$goods){
				if($shping_fee_tax["$goods[tax]"]==0){
					$shping_fee_tax["$goods[tax]"]=1;
				}
				$goods["shipping_fee"]=$goods['dprice']/$tax_all["$goods[tax]"]['goods_amount']*$shping_fee_tax["$goods[tax]"];//每个申报商品分配的税前运费
			}
			unset($goods);
			return array("depostfee"=>$shengbao_shping_fee,"degoods_amount"=>$before_tax_price);
		}
		//拼团计算申报运费和申报单价
		function group_tax_price($price,$tax_tate,$tax_consumption,$shipping_fee=0){
			$zhtax=$this->get_compositetaxrate($tax_tate,$tax_consumption);
			$dprice=$price/(1+$zhtax);//申报单价
			$dpostfee=$shipping_fee/(1+$zhtax);//申报运费
			$core_tax=$this->core_tax($dprice+$dpostfee,$tax_tate,$tax_consumption);//税费
			return array(
				'dprice'=>$dprice,
				'dpostfee'=>$dpostfee,
				'tax_core'=>$core_tax,
				);
		}
		/**
		 * 获取四舍五入的价格 //默认4位小数
		 * @var [type]
		 */
		private function get_decimal_price($price,$number=4){
			return round($price, $number);
		}

		/**
		 * 	计算综合税率
		 */
		public function get_compositetaxrate($vat_rate,$consumption_tax){
			 $tax_tax= ($vat_rate+$consumption_tax)/(1-$consumption_tax)*0.7;
			 return $this->get_decimal_price($tax_tax,4);
		}
		/**
		 * 计算代理商应该缴费的总金额
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public function dis_order_tax($order_id){
			 global $_W;
			$order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:order_id",array(":order_id"=>$order_id));

			if(empty($order_info)){
				return false;
			}

			//获取用户代理级别
			$resellerid = pdo_fetchcolumn("SELECT resellerid FROM ".tablename('ewei_shop_resellerlevel')." WHERE Accountsid=:accountsid",array(":accountsid"=>$order_info['uniacid']));
			

			$goodsList=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." WHERE orderid=:order_id",array(":order_id"=>$order_id));
			$dispriceamount=$this->get_order_goods($goodsList,$resellerid);//加入代理价
			if($dispriceamount==0){ //商品不是代理商品 
				return 0;
			}
			 $member = m('member')->getMember($order_info['openid'], true);
			 
			    $address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $_W['uniacid'], ':openid' => $order_info['openid'], ':id' => $order_info['addressid']));

			 foreach($goodsList as $goods){
			 	$sql="SELECT * FROM ".tablename("ewei_shop_goods")." where id=:id";
			 	$disgoods=pdo_fetch($sql, array(':id' =>$goods['goodsid']));
			 	$disgoods['total']=$goods['total'];
            	$goodsprice []= $disgoods;
			 }
		
			$dispatchprice=m('order')->getOrderDispatchPrice($goodsprice, $member, $address, false, 0,$order_info['depotid'],true);

			

				//var_dump($order_info['depotid']);
			return $this->get_dis_payamount($goodsList,$dispatchprice,$order_info,$dispriceamount);
		}
		/**
		 * 获取代理价
		 * @param  [type] $goodsList  订单中的商品
		 * @param  [type] $resellerid 代理等级
		 * @return [type]             [description]
		 */
		private function get_order_goods(&$goodsList,$resellerid){
			foreach ($goodsList as $key => &$value) {
				//var_dump($value);
				$goods=pdo_fetch("select * from ".tablename("ewei_shop_goods")." WHERE id=:goods_id",array(":goods_id"=>$value['goodsid']));
				if($goods['disgoods_id']==0){
					return 0;
				}else{
					$disgoods=pdo_fetch("select * from ".tablename("ewei_shop_goods")." WHERE id=:goods_id",array(":goods_id"=>$goods['disgoods_id']));
					$dispricelist=unserialize($disgoods['disprice']);
					$value['disprice']=$dispricelist[$resellerid];//获取代理价格
					$value['disgoods_id']=$goods['disgoods_id'];
					$dispriceamount+=$dispricelist[$resellerid]*$value['total'];//获取代理总价
				}
			}
			unset($value);
			return $dispriceamount;
		}
		/**
		 * 代理商应缴费金额
		 * @param  [type] $goodsList      [description]
		 * @param  [type] $shipping_fee   [description]
		 * @param  [type] $orderinfo      [description]
		 * @param  [type] $dispriceamount [description]
		 * @return [type]                 [description]
		 */
		private function get_dis_payamount($goodsList,$shipping_fee,$orderinfo,$dispriceamount){
			if($orderinfo['price']<=$dispriceamount){
				return $dispriceamount+$shipping_fee;
			}
			
			$depostfee=$this->get_cartgoods($goodsList,$shipping_fee,true);//计算代理价的税和其他
		
			$distax_rate_amount=0;
			$distax_consumption_amount=0;
			foreach($goodsList as $goods){
				$disprice=$goods['dprice']+$goods['shipping_fee'];
				//var_dump($goods['shipping_fee']);
				$tax=$this->core_tax($disprice,$goods['vat_rate'],$goods['consumption_tax']);

				$distax_rate_amount+=$tax['rate']*$goods['total']; //代理价 增值税
				$distax_consumption_amount+=$tax['consumption_tax']*$goods['total'];//代理价 消费税
			}

			//计算用户税总额
			$usertax_amount=$orderinfo['tax_consumption']+$orderinfo['tax_rate'];
			

			//代理价税总额
			$dis_tax_amount=$distax_rate_amount+$distax_consumption_amount;

			//echo $shipping_fee;
			//代理需要缴费税额
			$pay_tax_amount=$usertax_amount-$dis_tax_amount;
		    return $this->get_decimal_price($pay_tax_amount+$dispriceamount+$shipping_fee,2);

		}
		/**
		 * 判断当前订单是否是代理订单
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public function get_order_dis($order_id){
			global $_W; 
			$goodsList=pdo_fetchall("select * from ".tablename("ewei_shop_order_goods")." WHERE orderid=:order_id",array(":order_id"=>$order_id));

			foreach($goodsList as $value){
				$goods=pdo_fetch("select * from ".tablename("ewei_shop_goods")." WHERE id=:goods_id",array(":goods_id"=>$value['goodsid']));
				
				if($goods['disgoods_id']==0){
					return false;
				}

			}
			return true;
		}


	//没有公众号的代理商添加一条代理记录
	public function set_dis_price($order_id){
		global $_W;
		$chargeratio=0.006;
		$order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:id",array(":id"=>$order_id));
		$dispriceamount=$this->dis_order_tax($order_id);
		WeUtility::logging('disaccount',"order_id:".$order_id.",uniacid:".$uniacid."order_sn:".$orderinfo['ordersn'].'dispriceamount:'.$dispriceamount);
		$disprice=$order_info['price']-$dispriceamount;
		if($disprice==0){
			return false;
		}
		$charge=$disprice*$chargeratio;
		$charge=$this->get_decimal_price($charge,2);
		$data=array(
			'price'=>$disprice,
			'charge'=>$charge,
			'uniacid'=>$order_info['uniacid'],
			'type'=>1,//收入
			'order_sn'=>$order_info['ordersn'],
			'status'=>0,//冻结金额 无法提现
			'add_times'=>$_W['timestamp']
			);
		pdo_insert("ewei_shop_dis_price",$data);
	}
	//用户确认收货后更新代理收入表
	public function update_disprice($order_id,$status=1){
		$order_sn=pdo_fetchcolumn("SELECT ordersn FROM ".tablename("ewei_shop_order")." WHERE id=:id",array(":id"=>$order_id));
		$dis_price_order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_dis_price")." WHERE order_sn=:order_sn",array(":order_sn"=>$order_sn));
		if(!empty($dis_price_order_info)){
			pdo_update("ewei_shop_dis_price",array("status"=>$status),array("id"=>$dis_price_order_info['id']));
		}
	}
	//公众号佣金发放
	public function wxCommission($shop_dis_price_id){
		require_once(EWEI_SHOPV2_TAX_CORE . 'wxpaymentdis.php');
		$dis_price_order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_dis_price")." WHERE id=:id",array(":id"=>$shop_dis_price_id));
		if(empty($dis_price_order_info)){
			 return error(-2, "错误订单");
		}
		if($dis_price_order_info['status']==0){
			return error(-2, "当前佣金无法结算");
		}


	}
	//创建一条代理商提现申请记录
	/**
	 * [depositsamount description]
	 * @param  [type] $amount 提现金额
	 * @return [type]         [description]
	 */
	public function depositsamount($amount){
		global $_W;
		//检查用户是否有这么多的提现金额
		$disProfitAmount=pdo_fetchcolumn("SELECT sum(price) FROM ".tablename("ewei_shop_dis_price")." WHERE uniacid=:uniacid and status=1",array(":uniacid"=>$_W['uniacid']));
		if($amount>$disProfitAmount){
			return false;
		}
		$order_sn=$this->createNO("order_pay",'order_sn',"TX");
		$orderdata=array(
			'uniacid'=>$_W['uniacid'],
			'pay_fee'=>$amount*100,
			'status'=>0,
			'pay_code'=>0,
			'pay_name'=>'微信',
			'order_sn'=>$order_sn,
			'paytype'=>1,
			);
		pdo_insert("ewei_shop_dis_price",$orderdata);//添加一条代理商提现记录
		$data=array(
			'price'=>-$amount,
			'uniacid'=>$_W['uniacid'],
			'type'=>0,//提现
			'status'=>0,
			'order_sn'=>$order_sn,
			'add_times'=>$_W['timestamp'],
			);
		pdo_insert("ewei_shop_dis_price",$data);//添加一条提现记录
	}


	public function createNO($table, $field, $prefix) {

		$billno = date('YmdHis') . random(6, true);
		while (1) {
			$count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_' . $table) . " where {$field}=:billno limit 1", array(':billno' => $billno));
			if ($count <= 0) {
				break;
			}
			$billno = date('YmdHis') . random(6, true);
		}
		return $prefix . $billno;
	}
	public function dispayweixin($order_id,$uniacid){
		$ret=array("ret"=>0,"messsage"=>"");
		
		WeUtility::logging('pay',"order_id:".$order_id.",uniacid:".$uniacid."___##########");
		$order=pdo_fetch("SELECT * FROM ".tablename("ewei_order_pay")." WHERE id=:id",array(":id"=>$order_id));
		if(empty($order)){
			WeUtility::logging('pay',"订单错误##########");
			$ret['ret']=-1;
			$ret['messsage']="订单错误";
			return $ret;
		}

        //获取设置的OPENID
        $openid=pdo_fetchcolumn("select openid FROM ".tablename("ewei_shop_resellerlevel")." where Accountsid=:uniacid ",array("uniacid"=>$uniacid));
        if(empty($openid)){
        	WeUtility::logging('pay',"未设置OPENID##########");
        	$ret['ret']=-2;
			$ret['messsage']="未设置收款OPENID";
			return $ret;
        }
        $payfee=$order['pay_fee'];
       	if($payfee<100){
       		WeUtility::logging('pay',"支付金额错误##########".$payfee);
       		$ret['ret']=-3;
			$ret['messsage']="支付金额错误不能低于1块,现支付金额".$payfee/100;
       	}
        $order_info=array(
            'order_sn'=>$order['order_sn'],
            'desc'=>"代理商自动扣款",
            'openid'=>$openid,
            'pay_fee'=>$payfee,
        );

        WeUtility::logging('pay', var_export($order_info, true));
        $returnRes=$this->pay_weixin($order_info,$uniacid);
 		
        if(isset($returnRes['errno'])){
        	WeUtility::logging('pay', var_export($returnRes, true).$returnRes['errno']);
            $ret['ret']=-4;
			$ret['messsage']=$returnRes['message'];

			return $ret;
        }else{
            $datarr=array(
            	'pay_code'=>0,
            	'pay_name'=>"微信",
            	'openid'=>$openid,
            	'pay_message'=>$order_info['desc'],
                'transaction_id'=>$returnRes['payment_no'],
                'status'=>1,
                'paytype'=>1,
                'pay_times'=>$_W['timestamp'],
                );
            pdo_update("ewei_order_pay",$datarr,array("id"=>$order_id));
            $order_arrary=array(
				'paystatus'=>1,
				'out_order_sn'=>$order['order_sn'],
				);
            if(is_numeric($order['order_ids'])){
            	pdo_update("ewei_shop_order",$order_arrary,array("id"=>$order['order_ids']));
            	 $order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:id",array(":id"=>$order['order_ids']));
            	 m("order")->declareOrder($order_info);

				
            }else{
            	foreach($order['order_ids'] as $order_id){
					pdo_update("ewei_shop_order",$order_arrary,array("id"=>$order_id));
					$order_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:id",array(":id"=>$order_id));
					m("order")->declareOrder($order_info);
            	}
            }
            $ret['ret']=0;
			$ret['messsage']="支付成功";
			return $ret;
        }
	}
	function pay_weixin($order_info,$uniacid){
		require_once(EWEI_SHOPV2_TAX_CORE . 'wxpaymentdis.php');
		load()->model('payment');
        $setting = uni_setting($uniacid, array('payment'));
        $account_api = WeAccount::create($uniacid);
        $jssdkconfig = $account_api->getJssdkConfig();
        $input=new wxpaymentData();
        $input->SetAppid($jssdkconfig['appId']);
        $input->SetMchid($setting['payment']['wechat']['mchid']);
        $input->SetNonce_str(wxpaymentpayApi::getNonceStr());
        $input->SetPartner_trade_no($order_info['order_sn']);
        $input->SetDesc($order_info['desc']);
        $input->SetUniacid($uniacid);
        $input->SetOpenid($order_info['openid']);
        $input->SetAmount($order_info['pay_fee']);
        $wxdisprice=new wxdisprice();
        $xml=wxdisprice::init($input,$msg);
        $returnRes=wxdisprice::post_ssh_curl($xml,$uniacid);
        return $returnRes;
	}
	//order_id ewei_shop_order
	function disPayOrderNotOrderid($order_id,$uniacid){
		$ret=array("ret"=>0,"messsage"=>"");
		$openid=pdo_fetchcolumn("select openid FROM ".tablename("ewei_shop_resellerlevel")." where Accountsid=:uniacid ",array("uniacid"=>$uniacid));
        if(empty($openid)){
        	WeUtility::logging('pay',"未设置OPENID##########");
        	$ret['ret']=-1;
			$ret['messsage']="未设置收款OPENID";
			return $ret;
        }
        $order=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:id",array(":id"=>$order_id));
        if(empty($order)){
        	$ret['ret']=-3;
			$ret['messsage']="订单错误";
			return $ret;
        }
        if($order['pay_status']==1){
        	$ret['ret']=-2;
			$ret['messsage']="已经完成收款";
			return $ret;
        }
        $dis_amout=$this->dis_order_tax($order_id);//获取需要支付的代理金额
        $dispayorder_sn=$this->createNO("order_pay","id","dis");//生成订单号
        $pay_fee=$dis_amout*100;
      	$order_info=array(
            'order_sn'=>$dispayorder_sn,
            'desc'=>"代理商自动扣款",
            'openid'=>$openid,
            'pay_fee'=>$pay_fee,
        );
        $returnRes=$this->pay_weixin($order_info,$uniacid);
         if(isset($returnRes['errno'])){
        	WeUtility::logging('pay', var_export($returnRes, true).$returnRes['errno']);
            $ret['ret']=-4;
			$ret['messsage']=$returnRes['message'];
			return $ret;
        }else{
            $datarr=array(
            	'pay_fee'=>$pay_fee,
            	'order_sn'=>$dispayorder_sn,
            	'uniacid'=>$uniacid,
            	'order_ids'=>$order_id,
            	'pay_code'=>0,
            	'pay_name'=>"微信",
            	'openid'=>$openid,
            	'pay_message'=>$order_info['desc'],
                'transaction_id'=>$returnRes['payment_no'],
                'status'=>1,
                'paytype'=>1,
                'pay_times'=>time(),
                );
            pdo_insert("ewei_order_pay",$datarr);
            $order_arrary=array(
				'paystatus'=>1,
				'out_order_sn'=>$dispayorder_sn,
				);
			pdo_update("ewei_shop_order",$order_arrary,array("id"=>$order_id));
            $ret['ret']=0;
			$ret['messsage']="支付成功";
			return $ret;
        }
	}



	/**
	 * 计算订单的税
	 * @return [type] [description] $save=1 更新订单信息
	 */
	public  function tax_rate($orderid,$type=0,$save=1){
		$orderinfo=Dispage::get_order($orderid,$type);
		$orderinfo=$this->core_tax_order($orderinfo,$type);
		if($save==1){
			$ordergoods=$orderinfo['ordergoods'];
			foreach($ordergoods as $goods){
				$data=array(
					'dprice'=>$goods['dprice'],
					'pricetaxrate'=>$goods['pricetaxrate'],
					'taxconsumption'=>$goods['taxconsumption'],
					'shipping_fee'=>$goods['shipping_fee'],
					);
				$this->updateOrderGoods($data,$goods['id'],$type);
			}
			$order=$orderinfo['order'];
			$data=array(
				'dpostfee'=>$order['dpot_fee'],
				'tax_rate'=>$order['tax_rate'],
				'tax_consumption'=>$order['tax_consumption'],
				);
			$this->updateOrder($data,$order['id'],$type);
		}
		return $orderinfo;
	}


	private function core_tax_order($orderinfo,$type=0){
		$alldeduct=0;
		$shipping_fee=0;
		$ordergoods=array();
		$order=array();
		switch ($type) {
			default:
				$order=$orderinfo['order'];
				$goodsprice=$order['goodsprice'];//商品总值
				$shipping_fee=$order['shipping_fee'];//订单运费
				$orderamout=$order['price'];//实付费
				$alldeduct=$order['alldeduct'];//总优惠
				$ordergoods=$this->splitOrderGoods($orderinfo['ordergoods'],$goodsprice,$alldeduct);

				$shengbao_shping_fee=$this->core_tax_shipping_fee($ordergoods,$shipping_fee);//申报总运费
				$order['dpot_fee']=$shengbao_shping_fee;
				$taxall=0;
				$tax_rate=0;
				$tax_consumption=0;
				foreach($ordergoods as &$goods){
					$core_tax=$this->core_tax($goods['dprice']+$goods['shipping_fee'],$goods['vat_rate'],$goods['consumption_tax']);//税费
					$goods['pricetaxrate']=$core_tax['rate']*$goods['total'];
					$tax_consumption+=$core_tax['consumption_tax']*$goods['total'];
					$tax_rate+=$core_tax['rate']*$goods['total'];
					$goods['taxconsumption']=$core_tax['consumption_tax']*$goods['total'];
					$goods['shipping_fee']=$goods['shipping_fee']*$goods['total'];
					$taxall+=$core_tax['consolidated']*$goods['total'];
				}
				$order['tax_rate']=$tax_rate;
				$order['tax_consumption']=$tax_consumption;
				$order['taxall']=$taxall;
				unset($goods);
			break;
		}
		$orderinfo['order']=$order;
		$orderinfo['ordergoods']=$ordergoods;
		return $orderinfo;

	}
	private function updateOrderGoods($data,$id,$type=0){

		switch ($type) {
			case 1:

			break;
			default:
				pdo_update("ewei_shop_order_goods",$data,array("id"=>$id));
				break;
		}
	}
	private function updateOrder($data,$id,$type=0){
		switch ($type) {
			case 1:

			break;
			default:
				pdo_update("ewei_shop_order",$data,array("id"=>$id));
				break;
		}
	}
	/**
	 * [splitOrderGoods 必要参数 disgoods_id,goodsid,isdis
	 * @param  [type] $ordergoods [description]
	 * @return [type]             [description]
	 */
	private function  splitOrderGoods($ordergoods,$goodsprice,$alldeduct=0){
		foreach ($ordergoods as &$goods) {
			$price=0;
			if($goods['isdis']){
				$tax=$this->get_rate($goods['disgoods_id']);
			}else{
				$tax=$this->get_rate($goods['goodsid']);//找到当前税率
			}
			$goods['vat_rate']=$tax['vat_rate'];
			$goods['consumption_tax']=$tax['consumption_tax'];
			$goods['tax']=$this->get_compositetaxrate($tax['vat_rate'],$tax['consumption_tax']);//总税率
			$price=$this->get_price($goods['unitprice'],$goods['total'],$alldeduct,$goodsprice);
			$goods['dprice']=$this->get_goodsdprice($price,$goods['tax']);
		}
		unset($goods);
		return $ordergoods;
	}
	/**
	 * 获取商品的税率
	 * @param  [type] $goodsid [description]
	 * @return [type]          [description]
	 */
	private function get_rate($goodsid){

		$goods=pdo_fetch("SELECT disgoods_id,consumption_tax,vat_rate from ".tablename("ewei_shop_goods")." where id=:id",array(":id"=>$goodsid));
		if($goods['disgoods_id']>0){
			return pdo_fetch("SELECT consumption_tax,vat_rate from ".tablename("ewei_shop_goods")." where id=:id",array(":id"=>$goods['disgoods_id']));
		}else{
			return pdo_fetch("SELECT consumption_tax,vat_rate from ".tablename("ewei_shop_goods")." where id=:id",array(":id"=>$goodsid));
		}

	}
	/**
	 * 获取商品优惠后的单价
	 * @return [type] [description]
	 */
	private function get_price($unitprice,$total,$alldeduct,$goodsprice){
		return $unitprice-$unitprice*$total/$goodsprice*$alldeduct/$total;
	}
	 /**
	  * 申报运费计算
	  * @param  [type] $ordergoods   isdis,dprice,total
	  * @param  [type] $shipping_fee [description]
	  * @return [type]               [description]
	  */
	private function core_tax_shipping_fee(&$ordergoods,$shipping_fee){
		$goodsdata=array();
		$before_tax_price=0;
		$test=0;
		foreach($ordergoods as $goods){
			$before_tax_price+=$goods['dprice']*$goods['total'];
			$result["$goods[tax]"][]=$goods;
		}
		foreach($result as $k=> $v){
				$goods_amount=0;
				foreach($v as $goods){
						$goods_amount+=$goods['dprice']*$goods['total'];//每种税率申报商品总价
				}
				$tax_all[$k]=array("ratio"=>$goods_amount/$before_tax_price,'goods_amount'=>$goods_amount);//求出每种税率所占全部金额的比例
		}
		foreach ($tax_all as $key => $value) {
			$test+=(1+$key)*$value['ratio'];
		}
		$shengbao_shping_fee=$shipping_fee/$test;//申报总运费
		foreach ($tax_all as $key => $value) {
			$shping_fee_tax[$key]=$shengbao_shping_fee*$value['ratio'];//每种税率分配的总运费
		}

		foreach($ordergoods as &$goods){
			if($shping_fee_tax["$goods[tax]"]==0){
				$shping_fee_tax["$goods[tax]"]=1;
			}
			$goods["shipping_fee"]=$goods['dprice']/$tax_all["$goods[tax]"]['goods_amount']*$shping_fee_tax["$goods[tax]"];//每个申报商品分配的税前运费
		}
		unset($goods);
		return $shengbao_shping_fee;
	}
	/**
	 * 获取申报单价
	 * @param  [type] $price  [description]
	 * @param  [type] $alltax [description]
	 * @return [type]         [description]
	 */
	private function get_goodsdprice($price,$alltax){
		$dprice=$price/(1+$alltax);
		return $dprice;
	}

	public function get_dis_order_amount($orderid,$type=0){
		$dff_fee=0;
		switch ($type) {
			default:
			$order=pdo_fetch("select dispatchprice,tax_rate,tax_consumption,price from ".tablename("ewei_shop_order")." where id=:orderid",array(":orderid"=>$orderid));
			$array=$this->get_dis_order_goods($orderid,$order['dispatchprice'],$type);
			$oldtaxall=	$order['tax_rate']+$order['tax_consumption'];
			if($array['alltax']>0){
				$dff_fee=$oldtaxall-$array['alltax'];//差额税
			}
			$disprice=$array['goodsprice']+$order['dispatchprice'];//代理总价
			if($disprice<$order['price']){
				$disprice+=$dff_fee;
			}
			$data=array("disorderamount"=>$disprice,'dff_fee'=>$dff_fee);
			pdo_update("ewei_shop_order",$data,array("id"=>$orderid));
			return $disprice;
		}
		
	}

	private function get_dis_order_goods($orderid,$dispatchprice,$type=0){
		switch ($type) {
			default:
			$ordergoods=pdo_fetchall("select goodsid,disprice,total from ".tablename("ewei_shop_order_goods")." where orderid=:orderid",array(":orderid"=>$orderid));
				break;
		}
		foreach($ordergoods as &$goods){
			$tax=$this->get_rate($goods['goodsid']);
			$goods['vat_rate']=$tax['vat_rate'];
			$goods['consumption_tax']=$tax['consumption_tax'];
			$goods['tax']=$this->get_compositetaxrate($tax['vat_rate'],$tax['consumption_tax']);//总税率
			$goods['dprice']=$this->get_goodsdprice($goods['disprice'],$goods['tax']);
		}
		unset($goods);
		$consolidated=0;
		$this->core_tax_shipping_fee($ordergoods,$dispatchprice);
		$goodsprice=0;
		foreach ($ordergoods as $goods) {
			$core_tax=$this->core_tax($goods['dprice']+$goods['shipping_fee'],$goods['vat_rate'],$goods['consumption_tax']);//税费
			$consolidated+=$core_tax['consolidated']*$goods['total'];
			$goodsprice+=$goods['disprice']*$goods['total'];
		}
		return array("goodsprice"=>$goodsprice,'alltax'=>$consolidated);
	}


	public function get_rate_all($goodslist,$shipping_fee){
		foreach($goodslist as &$goods){
			
		}
	}
}

?>