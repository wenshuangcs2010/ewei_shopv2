<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Notice_EweiShopV2Model {

	protected function getUrl($do, $query = null) {

		$url = mobileUrl($do, $query, true);
		if (strexists($url, '/addons/ewei_shopv2/')) {
			$url = str_replace("/addons/ewei_shopv2/", '/', $url);
		}
		if (strexists($url, '/core/mobile/order/')) {
			$url = str_replace("/core/mobile/order/", '/', $url);
		}
		return $url;
	}
	/**
	 * 拼团发送订单通知
	 * @param type $message_type
	 * @param type $order
	 */
	public function sendTeamMessage($orderid = '0', $delRefund = false)
	{
		global $_W;
		$orderid = intval($orderid);
		if (empty($orderid)) {
			return;
		}
		$order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where id=:id limit 1', array(':id' => $orderid));
		if (empty($order)) {
			return;
		}
		$openid = $order['openid'];
		if(intval($order['teamid'])){
			$url = $this->getUrl('groups/team/detail', array('orderid' => $orderid,'teamid'=>intval($order['teamid'])));
		}else{
			$url = $this->getUrl('groups/orders/detail', array('orderid' => $orderid));
		}
		$order_goods = pdo_fetch('select * from ' . tablename('ewei_shop_groups_goods') . ' where uniacid=:uniacid and id=:id ', array(':uniacid' => $_W['uniacid'], ':id' => intval($order['goodid'])));
		$goodsprice = !empty($order['is_team'])?number_format($order_goods['groupsprice'],2):number_format($order_goods['singleprice'],2);
		$price = number_format($order['price'] - $order['creditmoney'] + $order['freight'],2);
		$goods = ' (单价: ¥' .$goodsprice. '元 数量: 1 总价: ¥' . $order['price']."元); ";
		$orderpricestr = ' ¥' .$price. '元 (包含运费: ¥' . $order['freight'] . '元，积分抵扣: ¥'.$order['creditmoney'].'元)';
		$member = m('member')->getMember($openid);

		$datas= array(
			array("name" => "商城名称", "value" => $_W['shopset']['shop']['name']),
			array("name" => "粉丝昵称", "value" => $member['nickname']),
			array("name" => '订单号', "value" =>$order['orderno']),
			array("name" => '订单金额', "value" =>  $order['price'] - $order['creditmoney'] + $order['freight']),
			array("name" => '运费', "value" =>$order['freight']),
			array("name" => '商品详情', "value" =>$goods),
			array("name" => '快递公司', "value" =>$order['expresscom']),
			array("name" => '快递单号', "value" =>$order['expresssn']),
			/*array("name" => '购买者姓名', "value" =>$buyerinfo_name),
			array("name" => '购买者电话', "value" =>$buyerinfo_mobile),
			array("name" => '收货地址', "value" =>$addressinfo),*/
			array("name" => '下单时间', "value" =>date('Y-m-d H:i',$order['createtime'])),
			array("name" => '支付时间', "value" =>date('Y-m-d H:i',$order['paytime'])),
			array("name" => '发货时间', "value" =>date('Y-m-d H:i',$order['sendtime'])),
			array("name" => '收货时间', "value" =>date('Y-m-d H:i',$order['finishtime'])),
		);

		$usernotice = unserialize($member['noticeset']);
		if (!is_array($usernotice)) {
			$usernotice = array();
		}
		$set = $set = m('common')->getSysset();//
		$shop = $set['shop'];
		$tm = $set['notice'];
		if($delRefund==true){
			//买家退款通知
			$refundtype = '';
			if ($order['pay_type']=='credit') {
				$refundtype = ', 已经退回您的余额账户，请留意查收！';
			} else if ($order['pay_type'] == 'wechat') {
				$refundtype = ', 已经退回您的对应支付渠道（如银行卡，微信钱包等, 具体到账时间请您查看微信支付通知)，请留意查收！';
			} else {
				$refundtype = ', 请联系客服进行退款事项！';
			}
			$msg = array(
				'first' => array('value' => "您的订单已经完成退款！", "color" => "#4a5077"),
				'keyword1' => array('title' => '退款金额', 'value' => '¥' . $price . '元', "color" => "#4a5077"),
				'keyword2' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
				'keyword3' => array('title' => '订单编号', 'value' => $order['orderno'], "color" => "#4a5077"),
				'remark' => array('value' => "退款金额 ¥" . $price . "{$refundtype}\r\n 【" . $shop['name'] . "】期待您再次购物！", "color" => "#4a5077")
			);

			$this->sendGroupsNotice(array(
				"openid" => $openid,
				'tag' => 'groups_refund',
				'default' => $msg,
				'datas' => $datas
			));
		}else{
			if ($order['status'] == 1) {
				//
				// {{first.DATA}}
				//店铺：{{keyword1.DATA}}
				//下单时间：{{keyword2.DATA}}
				//商品：{{keyword3.DATA}}
				//金额：{{keyword4.DATA}}
				//{{remark.DATA}}
				if($order['success'] == 1){
					//拼团成功通知
					$order = pdo_fetchall('select * from ' . tablename('ewei_shop_groups_order') . ' where teamid = :teamid and success = 1 and status = 1 ', array(':teamid' => $order['teamid']));
					$remark = "您参加的拼团已经成功，我们将尽快为您配送~~";
					foreach($order as $key => $value){
						$msg = array(
							'first' => array('value' => "您参加的拼团已经成功组团！", "color" => "#4a5077"),
							'keyword1' => array('title' => '订单号', 'value' => $value['orderno'], "color" => "#4a5077"),
							'keyword2' => array('title' => '时间', 'value' => date('Y-m-d H:i',$value['paytime']), "color" => "#4a5077"),
							'keyword3' => array('title' => '商品', 'value' => $order_goods['title'], "color" => "#4a5077"),
							'remark' => array('value' => $remark, "color" => "#4a5077")
						);
						$this->sendGroupsNotice(array(
							"openid" => $value['openid'],
							'tag' => 'groups_success',
							'default' => $msg,
							'datas' => $datas
						));
					}
					//商家通知openid
					$tm = m('common')->getSysset('notice');
					$remarkteam = "拼团成功了，准备发货";
					$msgteam = array(
						'first' => array('value' => "拼团已经成功组团！", "color" => "#4a5077"),
						'keyword1' => array('title' => '商品信息', 'value' => $goods, "color" => "#4a5077"),
						'keyword2' => array('title' => '付款金额', 'value' => $orderpricestr, "color" => "#4a5077"),
						'keyword3' => array('title' => '预计发货时间', 'value' => date('Y-m-d H:i:s', time()), "color" => "#4a5077"),
						'remark' => array('value' => $remarkteam, "color" => "#4a5077")
					);
					$business = explode(",", $tm['openid']);
					foreach($business as $value){
						$this->sendGroupsNotice(array(
							"openid" => $value,
							'tag' => 'groups_teamsend',
							'default' => $msgteam,
							'datas' => $datas
						));
					}

				}elseif($order['success'] == -1){
					//拼团失败通知
					$order = pdo_fetchall('select * from ' . tablename('ewei_shop_groups_order') . ' where teamid = :teamid and success = -1 and status = 1 ', array(':teamid' => $order['teamid']));
					$remark = "很抱歉，您所在的拼团为能成功组团，系统会在24小时之内自动退款。如有疑问请联系卖家，谢谢您的参与！";
					$msg = array(
						'first' => array('value' => "您参加的拼团组团失败！", "color" => "#4a5077"),
						'keyword1' => array('title' => '店铺', 'value' => $shop['name'], "color" => "#4a5077"),
						'keyword2' => array('title' => '通知时间', 'value' => date('Y-m-d H:i:s', time()), "color" => "#4a5077"),
						'keyword3' => array('title' => '商品', 'value' => $order_goods['title'], "color" => "#4a5077"),
						'remark' => array('value' => $remark, "color" => "#4a5077")
					);
					foreach($order as $key => $value){
						$this->sendGroupsNotice(array(
							"openid" => $value['openid'],
							'tag' => 'groups_error',
							'default' => $msg,
							'datas' => $datas
						));
					}
				}elseif($order['success'] == 0){
					//买家付款通知
					if (!empty($order['addressid'])) { //快递
						if($order['is_team']){
							$remark = "\r\n您的订单我们已经收到，请耐心等待其他团员付款~~";
						}else{
							$remark = "\r\n您的订单我们已经收到，我们将尽快配送~~";
						}
					}
					$msg = array(
						'first' => array('value' => "您的订单已提交成功！", "color" => "#4a5077"),
						'keyword1' => array('title' => '店铺', 'value' => $shop['name'], "color" => "#4a5077"),
						'keyword2' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
						'keyword3' => array('title' => '商品', 'value' => $goods, "color" => "#4a5077"),
						'keyword4' => array('title' => '金额', 'value' => $orderpricestr, "color" => "#4a5077"),
						'remark' => array('value' => $remark, "color" => "#4a5077")
					);

					$this->sendGroupsNotice(array(
						"openid" => $openid,
						'tag' => 'groups_pay',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas
					));
					if(!$order['is_team']){
						//商家通知openid
						$tm = m('common')->getSysset('notice');
						$remarkteam = "单购订单成功了，准备发货";
						$msgteam = array(
							'first' => array('value' => "单购订单成功了！", "color" => "#4a5077"),
							'keyword1' => array('title' => '商品信息', 'value' => $goods, "color" => "#4a5077"),
							'keyword2' => array('title' => '付款金额', 'value' => $orderpricestr, "color" => "#4a5077"),
							'keyword3' => array('title' => '预计发货时间', 'value' => date('Y-m-d H:i:s', time()), "color" => "#4a5077"),
							'remark' => array('value' => $remarkteam, "color" => "#4a5077")
						);
						$business = explode(",", $tm['openid']);
						foreach($business as $value){
							$this->sendGroupsNotice(array(
								"openid" => $value,
								'tag' => 'groups_teamsend',
								'default' => $msgteam,
								'datas' => $datas
							));
						}
					}

				}
			}elseif($order['status'] == 2){
				//买家发货通知
				if (!empty($order['addressid'])) { //快递
					$remark = "您的订单已发货，请注意查收！";
				}
				$msg = array(
					'first' => array('value' => "您的订单已发货！", "color" => "#4a5077"),
					'keyword1' => array('title' => '店铺', 'value' => $shop['name'], "color" => "#4a5077"),
					'keyword2' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
					'keyword3' => array('title' => '商品', 'value' => $order_goods['title'], "color" => "#4a5077"),
					'keyword4' => array('title' => '快递公司', 'value' => $order['expresscom'], "color" => "#4a5077"),
					'keyword5' => array('title' => '快递单号', 'value' => $order['expresssn'], "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);

				$this->sendGroupsNotice(array(
					"openid" => $openid,
					'tag' => 'groups_send',
					'default' => $msg,
					'datas' => $datas
				));
			}
		}
	}
	/*
	 * 拼团发送模板消息
	 * */
	public function sendGroupsNotice(array $params) {

		global $_W, $_GPC;
		$tag = isset($params['tag']) ? $params['tag'] : '';

		$touser = isset($params['openid']) ? $params['openid'] : '';
		if (empty($touser)) {
			return;
		}

		$tm = $_W['shopset']['notice'];
		if(empty($tm)) {
			$tm = m('common')->getSysset('notice');
		}
		$templateid = $tm['is_advanced'] ? $tm[$tag . "_template"] : $tm[$tag];
		$default_message = isset($params['default']) ? $params['default'] : array();
		$url = isset($params['url']) ? $params['url'] : '';
		$account = isset($params['account']) ? $params['account'] : m('common')->getAccount();
		$datas = isset($params['datas']) ? $params['datas'] : array();
		$advanced_message = false;

		if ($tm['is_advanced']) {

			if(!empty($tm[$tag.'_close_advanced'])){
				//关闭提醒
				return;
			}
			//高级模式
			if (!empty($templateid)) {
				$advanced_template = pdo_fetch('select * from ' . tablename('ewei_shop_member_message_template') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $templateid, ':uniacid' => $_W['uniacid']));

				if (!empty($advanced_template)) {
					$advanced_message = array(
						'first' => array('value' => $this->replaceTemplate($advanced_template['first'], $datas), 'color' => $advanced_template['firstcolor']),
						'remark' => array('value' => $this->replaceTemplate($advanced_template['remark'], $datas), 'color' => $advanced_template['remarkcolor'])
					);

					$data = iunserializer($advanced_template['data']);
					foreach ($data as $d) {
						$advanced_message[$d['keywords']] = array('value' => $this->replaceTemplate($d['value'], $datas), 'color' => $d['color']);
					}


					//高级模板消息
					$ret = m('message')->sendTplNotice($touser, $advanced_template['template_id'], $advanced_message, $url, $account);

					if (is_error($ret)) {
						//高级客服消息
						$ret = m('message')->sendCustomNotice($touser, $advanced_message, $url, $account);
						if (is_error($ret)) {
							//默认客服消息
							$ret = m('message')->sendCustomNotice($touser, $advanced_message, $url, $account);

						}
					}
				} else {
					//默认客服消息
					m('message')->sendCustomNotice($touser, $default_message, $url, $account);
				}
			} else {
				//默认客服消息
				m('message')->sendCustomNotice($touser, $default_message, $url, $account);
			}
		} else {
			if(!empty($tm[$tag.'_close_normal'])){
				//关闭提醒
				return;
			}
			//默认模板消息
			$ret = m('message')->sendTplNotice($touser, $templateid, $default_message, $url, $account);
			if (is_error($ret)) {
				//默认客服消息
				m('message')->sendCustomNotice($touser, $default_message, $url, $account);
			}
		}
	}
	/**
	 * 发送订单通知
	 * @param type $message_type
	 * @param type $order
	 */
	public function sendOrderMessage($orderid = '0', $delRefund = false) {
		global $_W;

		
		if (empty($orderid)) {
			return;
		}

		$order = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where id=:id limit 1', array(':id' => $orderid));
		if (empty($order)) {
			return;
		}
		$openid = $order['openid'];
		$url = $this->getUrl('order/detail', array('id' => $orderid));

        $param = array();
        $param[':uniacid'] = $_W['uniacid'];

        if ($order['isparent'] == 1) {
            $scondition = " og.parentorderid=:parentorderid";
            $param[':parentorderid'] = $orderid;
        } else {
            $scondition = " og.orderid=:orderid";
            $param[':orderid'] = $orderid;
        }

		$order_goods = pdo_fetchall("select g.id,g.title,og.realprice,og.total,og.price,og.optionname as optiontitle,g.noticeopenid,g.noticetype from " . tablename('ewei_shop_order_goods') . " og "
			. " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
			. " where $scondition and og.uniacid=:uniacid ", $param);
		$goods = '';
		foreach ($order_goods as $og) {
			$goods.="" . $og['title'] . '( ';
			if (!empty($og['optiontitle'])) {
				$goods.=" 规格: " . $og['optiontitle'];
			}
			$goods.=' 单价: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['realprice'] . "); ";
		}
		$orderpricestr = ' 订单总价: ' . $order['price'] . '(包含运费:' . $order['dispatchprice'] . ')';
		$member = m('member')->getMember($openid);
		
		$carrier = false;
		
		//门店
		$store = false;
		if (!empty($order['storeid'])) {
            if ($order['merchid'] > 0) {
                $store = pdo_fetch('select * from ' . tablename('ewei_shop_merch_store') . " where id=:id and uniacid=:uniacid and merchid = :merchid limit 1", array(':id' => $order['storeid'], ':uniacid' => $_W['uniacid'], ':merchid' => $order['merchid']));
            } else {
                $store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $order['storeid'], ':uniacid' => $_W['uniacid']));
            }
		}
		
		//购买者
		$buyerinfo = '';
		$buyerinfo_name = "";
		$buyerinfo_mobile = "";
		$addressinfo = '';
		if (!empty($order['address'])) {

			$address = iunserializer($order['address_send']);
			if (!is_array($address)) {
				$address = iunserializer($order['address']);
				if (!is_array($address)) {
					$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and uniacid=:uniacid limit 1'
						, array(':id' => $order['addressid'], ':uniacid' => $_W['uniacid']));
				}
			}
			if (!empty($address)) {
				$addressinfo = $address["province"] . $address["city"] . $address["area"] . " " . $address["address"];
				$buyerinfo = "收件人: " . $address["realname"] . "\n联系电话: " . $address["mobile"] . "\n收货地址: " . $addressinfo;
				$buyerinfo_name = $address['realname'];
				$buyerinfo_mobile = $address['mobile'];
			}
		} else {
			$carrier = iunserializer($order["carrier"]);
			if (is_array($carrier)) {
				$buyerinfo = "联系人: " . $carrier["carrier_realname"] . "\n联系电话: " . $carrier["carrier_mobile"];
				
				$buyerinfo_name = $carrier['carrier_realname'];
				$buyerinfo_mobile = $carrier['carrier_mobile'];
				
			}
		}
		$datas= array(
			array("name" => "商城名称", "value" => $_W['shopset']['shop']['name']),
			array("name" => "粉丝昵称", "value" => $member['nickname']),
			array("name" => '订单号', "value" =>$order['ordersn']),
			array("name" => '订单金额', "value" =>  $order['price']),
			array("name" => '运费', "value" =>$order['dispatchprice']),
			array("name" => '商品详情', "value" =>$goods),
			array("name" => '快递公司', "value" =>$order['expresscom']),
			array("name" => '快递单号', "value" =>$order['expresssn']),
			array("name" => '购买者姓名', "value" =>$buyerinfo_name),
			array("name" => '购买者电话', "value" =>$buyerinfo_mobile),
			array("name" => '收货地址', "value" =>$addressinfo),
			array("name" => '下单时间', "value" =>date('Y-m-d H:i',$order['createtime'])),
			array("name" => '支付时间', "value" =>date('Y-m-d H:i',$order['paytime'])),
			array("name" => '发货时间', "value" =>date('Y-m-d H:i',$order['sendtime'])),
			array("name" => '收货时间', "value" =>date('Y-m-d H:i',$order['finishtime'])),
			array("name" => '门店', "value" =>!empty($store)?$store['storename']:''),
			array("name" => '门店地址', "value" =>!empty($store)?$store['address']:''),
			array("name" => '门店联系人', "value" =>!empty($store)?$store['realname']."/".$store['mobile']:''),
			array("name" => '门店营业时间', "value" =>!empty($store)?(empty($store['saletime']) ? '全天' : $store['saletime']):''),
			array("name" => '虚拟物品自动发货内容', "value" =>$order['virtualsend_info']),
			array("name" => '虚拟卡密自动发货内容', "value" =>$order['virtual_str']),
			array("name" => '自提码', "value" =>$order['verifycode']),
		);
		
		$usernotice = unserialize($member['noticeset']);
		if (!is_array($usernotice)) {
			$usernotice = array();
		}
		$set = m('common')->getSysset();

		$shop = $set['shop'];
		$tm = $set['notice'];
		if(!empty($order['merchid']) && p('merch'))
		{
			$merch_tm = p('merch')->getSet('notice',$order['merchid']);
			$tm['openid'] = $merch_tm['openid'];
		}
		
		if ($delRefund) {
			
			 
			$r_type = array('0' => '退款', '1' => '退货退款', '2' => '换货');

			//退款的申请
			if (!empty($order['refundid'])) {
				$refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $order['refundid']));
				if (empty($refund)) {
					return;
				}
				
				
				$datas[]= array('name'=>'售后类型','value'=>$r_type[$refund['rtype']]);
				$datas[]= array('name'=>'申请金额','value'=>$refund['rtype'] == 3?"-":$refund['applyprice']);
				$datas[]= array('name'=>'退款金额','value'=>$refund['price']);
				$datas[]= array('name'=>'换货快递公司','value'=>$refund['rexpresscom'] );
				$datas[]= array('name'=>'换货快递单号','value'=>$refund['rexpresssn'] );
			 
				if (empty($refund['status'])) {
				
					//退款申请
					//{{first.DATA}}
					//
                                                 //退款金额：{{orderProductPrice.DATA}}
					//商品详情：{{orderProductName.DATA}}
					//订单编号：{{orderName.DATA}}
					//{{remark.DATA}}
					if(!empty($usernotice['refund'])){
						return;
					}
 
					$msg = array(
						'first' => array('value' => "您的" . $r_type[$refund['rtype']] . "申请已经提交！", "color" => "#4a5077"),
						'orderProductPrice' => array('title' => '退款金额', 'value' => $refund['rtype'] == 3 ? "-" : ('¥' . $refund['applyprice'] . '元'), "color" => "#4a5077"),
						'orderProductName' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'orderName' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'remark' => array('value' => "\r\n等待商家确认" . $r_type[$refund['rtype']] . "信息！", "color" => "#4a5077"),
					);
					
					$this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'refund',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas
					));
					// 短信通知
					com_run('sms::callsms', array('tag' => 'refund', 'datas' => $datas, 'mobile' =>$member['mobile']));
				} else if ($refund['status'] == 3) {
					
					//通过退换货申请
					if(!empty($usernotice['refunding'])){
						return;
					}
					//{{first.DATA}}
					//订单编号：{{keyword1.DATA}}
					//当前进度：{{keyword2.DATA}}
					//商品名称：{{keyword3.DATA}}
					//退款金额：{{keyword4.DATA}}
					//{{remark.DATA}}
					$refundaddress = iunserializer($refund['refundaddress']);
					$refundaddressinfo = $refundaddress['province'] . ' ' . $refundaddress['city'] . ' ' . $refundaddress['area'] . ' ' . $refundaddress['address'] . " 收件人: " . $refundaddress['name'] . ' (' . $refundaddress['mobile'] . ')(' . $refundaddress['tel'] . ') ';
					$refund_address = "退货地址: " . $refundaddressinfo;
                                              $datas[]= array('name'=>'退货地址','value'=>$refundaddressinfo);
											  
					$msg = array(
						'first' => array('value' => "您的" . $r_type[$refund['rtype']] . "申请已经通过！", "color" => "#4a5077"),
						'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'keyword2' => array('title' => '当前进度', 'value' => "您的" . $r_type[$refund['rtype']] . "申请已经通过！", "color" => "#4a5077"),
						'keyword3' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'keyword4' => array('title' => '退款金额', 'value' => $refund['rtype'] == 3 ? "-" : ('¥' . $refund['applyprice'] . '元'), "color" => "#4a5077"),
						'remark' => array('value' => "\r\n请您根据商家提供的退货地址将商品寄回！" . $refund_address . "", "color" => "#4a5077"),
					);
					
					$this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'refunding',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas
					));
					// 短信通知
					com_run('sms::callsms', array('tag' => 'refunding', 'datas' => $datas, 'mobile' =>$member['mobile']));
 
				} else if ($refund['status'] == 5) {
					
					//换货物品已经发货
                                             if(!empty($usernotice['refunding'])){
						return;
					}
					if (!empty($order['address'])) {

						$address = iunserializer($order['address_send']);
						if (!is_array($address)) {
							$address = iunserializer($order['address']);
							if (!is_array($address)) {
								$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and uniacid=:uniacid limit 1'
									, array(':id' => $order['addressid'], ':uniacid' => $_W['uniacid']));
							}
						}
					}

					if (empty($address)) {
						return;
					}
					
					$msg = array(
						'first' => array('value' => "您的换货物品已经发货！", "color" => "#4a5077"),
						'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'keyword2' => array('title' => '当前进度', 'value' => "您的换货物品已经发货！快递公司: {$refund['rexpresscom']} 快递单号: {$refund['rexpresssn']}", "color" => "#4a5077"),
						'keyword3' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'keyword4' => array('title' => '退款金额', 'value' => $refund['rtype'] == 3 ? "-" : ('¥' . $refund['applyprice'] . '元'), "color" => "#4a5077"),
						'remark' => array('value' => "\r\n我们正加速送到您的手上，请您耐心等候。", "color" => "#4a5077")
					);
					$this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'refunding',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas
					));
					// 短信通知
					com_run('sms::callsms', array('tag' => 'refunding', 'datas' => $datas, 'mobile' =>$member['mobile']));
				} else if ($refund['status'] == 1) {
					
					if(!empty($usernotice['refund1'])){
						return;
					}
					
					//                {{first.DATA}}
					//
                                             //                退款金额：{{orderProductPrice.DATA}}
					//                商品详情：{{orderProductName.DATA}}
					//                订单编号：{{orderName.DATA}}
					//                {{remark.DATA}}
					if ($refund['rtype'] == 2) {
						$msg = array(
							'first' => array('value' => "您的订单已经完成换货！", "color" => "#4a5077"),
							'orderProductPrice' => array('title' => '退款金额', 'value' => '-', "color" => "#4a5077"),
							'orderProductName' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
							'orderName' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
							'remark' => array('value' => "\r\n 换货成功！【" . $shop['name'] . "】期待您再次购物！", "color" => "#4a5077")
						);
					} else {
						$refundtype = '';
						if (empty($refund['refundtype'])) {
							$refundtype = ', 已经退回您的余额账户，请留意查收！';
						} else if ($refund['refundtype'] == 1) {
							$refundtype = ', 已经退回您的对应支付渠道（如银行卡，微信钱包等, 具体到账时间请您查看微信支付通知)，请留意查收！';
						} else {
							$refundtype = ', 请联系客服进行退款事项！';
						}
						$msg = array(
							'first' => array('value' => "您的订单已经完成退款！", "color" => "#4a5077"),
							'orderProductPrice' => array('title' => '退款金额', 'value' => '¥' . $refund['price'] . '元', "color" => "#4a5077"),
							'orderProductName' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
							'orderName' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
							'remark' => array('value' => "\r\n 退款金额 ¥" . $refund['price'] . "{$refundtype}\r\n 【" . $shop['name'] . "】期待您再次购物！", "color" => "#4a5077")
						);
					}
                                                  $this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'refund1',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas
					));
					// 短信通知
					com_run('sms::callsms', array('tag' => 'refund1', 'datas' => $datas, 'mobile' =>$member['mobile']));
				} elseif ($refund['status'] == -1) {
					
					  if(!empty($usernotice['refund2'])){
						return;
					}
					//                  {{first.DATA}}
					//
                                             //退款金额：{{orderProductPrice.DATA}}
					//商品详情：{{orderProductName.DATA}}
					//订单编号：{{orderName.DATA}}
					//{{remark.DATA}}

					$remark = "\n驳回原因: " . $refund['reply'];
					if (!empty($shop['phone'])) {
						$remark.="\n客服电话:  " . $shop['phone'];
					}
					$msg = array(
						'first' => array('value' => "您的" . $r_type[$refund['rtype']] . "申请被商家驳回，可与商家协商沟通！", "color" => "#4a5077"),
						'orderProductPrice' => array('title' => '退款金额', 'value' => '¥' . $refund['price'] . '元', "color" => "#4a5077"),
						'orderProductName' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'orderName' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'remark' => array('value' => $remark, "color" => "#4a5077")
					);
                                                 $this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'refund2',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas,
						'mobile'=>$buyerinfo_mobile
					));
					// 短信通知
					com_run('sms::callsms', array('tag' => 'refund2', 'datas' => $datas, 'mobile' =>$member['mobile']));
				}
				
			}
			return;
		}


		if ($order['status'] == -1) {

			if(!empty($usernotice['cancel'])){
				return;
			}
			//订单取消
			// {{first.DATA}} 
			//订单金额：{{orderProductPrice.DATA}} 
			//商品详情：{{orderProductName.DATA}} 
			//收货信息：{{orderAddress.DATA}} 
			//订单编号：{{orderName.DATA}} 
			//{{remark.DATA}}

			if (!empty($order['addressid'])) {
				$orderAddress = array('title' => '收货信息', 'value' => '收货地址: ' . $address['province'] . ' ' . $address['city'] . ' ' . $address['area'] . ' ' . $address['address'] . ' 收件人: ' . $address['realname'] . ' 联系电话: ' . $address['mobile'], "color" => "#4a5077");
			} else if (!empty($order['dispatchtype'])) {
				$orderAddress = array('title' => '收货信息', 'value' => '自提地点: ' . $store['address'] . ' 联系人: ' . $store['realname'] . ' 联系电话: ' . $store['mobile'], "color" => "#4a5077");
			} else {
				$orderAddress = array('title' => '收货信息', 'value' => ' 联系人: ' . $carrier['carrier_realname'] . ' 联系电话: ' . $carrier['carrier_mobile'], "color" => "#4a5077");
			}

			$msg = array(
				'first' => array('value' => "您的订单已取消!", "color" => "#4a5077"),
				'orderProductPrice' => array('title' => '订单金额', 'value' => '¥' . $order['price'] . '元(含运费' . $order['dispatchprice'] . '元)', "color" => "#4a5077"),
				'orderProductName' => array('title' => '商品详情', 'value' => $goods, "color" => "#4a5077"),
				'orderAddress' => $orderAddress,
				'orderName' => array('title' => '订单编号', 'value' => $order['ordersn'], "color" => "#4a5077"),
				'remark' => array('value' => "\r\n【" . $shop['name'] . "】欢迎您的再次购物！", "color" => "#4a5077")
			);
			 $this->sendNotice(array(
				"openid" => $openid,
				'tag' => 'cancel',
				'default' => $msg,
				'url' => $url,
				'datas' => $datas,
				'mobile'=>$buyerinfo_mobile
			  ));
			// 短信通知
			com_run('sms::callsms', array('tag' => 'cancel', 'datas' => $datas, 'mobile' =>$member['mobile']));
			 
		} else if ($order['status'] == 0) {

			//商家通知
			//
            //{{first.DATA}}
			//时间：{{keyword1.DATA}}
			//商品名称：{{keyword2.DATA}}
			//订单号：{{keyword3.DATA}}
			//{{remark.DATA}}
			$newtype = explode(',', $tm['newtype']);

			if (is_array($newtype) && in_array('0', $newtype) ) {
				$remark = "\n订单下单成功,请到后台查看!";
				if (!empty($buyerinfo)) {
					$remark.="\r\n下单者信息:\n" . $buyerinfo;
				}
				$msg = array(
					'first' => array('value' => "订单下单通知!", "color" => "#4a5077"),
					'keyword1' => array('title' => '时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
					'keyword2' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
					'keyword3' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);
				$account = m('common')->getAccount();
				if (!empty($tm['openid'])) {
					$openids = explode(',', $tm['openid']);
					foreach ($openids as $tmopenid) {
						if (empty($tmopenid)) {
							continue;
						}
						
						$this->sendNotice(array(
							"openid" => $tmopenid,
							'tag' => 'saler_submit',
							'default' => $msg,
							'datas' => $datas
						));

//						 
//						if (!empty($tm['new'])) {
//							m('message')->sendTplNotice($tmopenid, $tm['new'], $msg, '', $account);
//						} else {
//							m('message')->sendCustomNotice($tmopenid, $msg, '', $account);
//						}
					}
				}
			}
			// 短信通知
			if (!empty($tm['mobile']) && empty($tm['saler_submit_close_sms'])) {
				$datas[] = array('name'=>'通知类型', 'value'=>'创建订单');
				$mobiles = explode(',', $tm['mobile']);
				foreach ($mobiles as $mobile) {
					if (empty($mobile)) {
						continue;
					}
					com_run('sms::callsms', array('tag'=>'saler_submit', 'datas'=>$datas, 'mobile'=>$mobile));
				}
			}


			$remark = "\r\n商品已经下单，请及时备货，谢谢!";
			if (!empty($buyerinfo)) {
				$remark.="\r\n下单者信息:\n" . $buyerinfo;
			}

			foreach ($order_goods as $og) {
				if (!empty($og['noticeopenid'])) {
					$noticetype = explode(',', $og['noticetype']);

					if (empty($og['noticetype']) || (is_array($noticetype) && in_array('0', $noticetype) )) {
						$goodstr = $og['title'] . '( ';
						if (!empty($og['optiontitle'])) {
							$goodstr.=" 规格: " . $og['optiontitle'];
						}
						$goodstr.=' 单价: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['realprice'] . "); ";

						$msg = array(
							'first' => array('value' => "商品下单通知!", "color" => "#4a5077"),
							'keyword1' => array('title' => '时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
							'keyword2' => array('title' => '商品名称', 'value' => $goodstr, "color" => "#4a5077"),
							'keyword3' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
							'remark' => array('value' => $remark, "color" => "#4a5077")
						);
						
						$datas[] = array('name'=>'单品详情','value'=>$goodstr);
						$this->sendNotice(array(
							"openid" => $og['noticeopenid'],
							'tag' => 'saler_submit',
							'default' => $msg,
							'datas' => $datas
						));
//						
//						if (!empty($tm['new'])) {
//							m('message')->sendTplNotice($og['noticeopenid'], $tm['new'], $msg, '', $account);
//						} else {
//							m('message')->sendCustomNotice($og['noticeopenid'], $msg, '', $account);
//						}
					}
				}
			}

			if(empty($usernotice['submit'])){
				//买家通知
				//
				// {{first.DATA}}
				//店铺：{{keyword1.DATA}}
				//下单时间：{{keyword2.DATA}}
				//商品：{{keyword3.DATA}}
				//金额：{{keyword4.DATA}}
				//{{remark.DATA}}
				if (!empty($order['addressid'])) { //快递
					$remark = "\r\n您的订单我们已经收到，支付后我们将尽快配送~~";
				} else if (!empty($order['isverify'])) { //核销
					$remark = "\r\n您的订单我们已经收到，支付后您就可以到店使用了~~";
				} else if (!empty($order['virtual'])) { //卡密
					$remark = "\r\n您的订单我们已经收到，支付后系统将会自动发货~~";
				} else if (!empty($order['dispatchtype'])) { //自提
					$remark = "\r\n您的订单我们已经收到，支付后您就可以到自提点提货物了~~";
				} else if (!empty($order['isvirtualsend'])) { //虚拟物品自动发货
					$remark = "\r\n您的订单我们已经收到，支付后系统会自动发货~~";
				} else {
					$remark = "\r\n您的订单我们已经收到~~";
				}
				$msg = array(
					'first' => array('value' => "您的订单已提交成功！", "color" => "#4a5077"),
					'keyword1' => array('title' => '店铺', 'value' => $shop['name'], "color" => "#4a5077"),
					'keyword2' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
					'keyword3' => array('title' => '商品', 'value' => $goods, "color" => "#4a5077"),
					'keyword4' => array('title' => '金额', 'value' => '¥' . $order['price'] . '元(含运费' . $order['dispatchprice'] . '元)', "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);
				$this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'submit',
						'default' => $msg,
						'url' => $url,
						'datas' => $datas
			         ));

				// 短信通知
				com_run('sms::callsms', array('tag'=>'submit', 'datas'=>$datas, 'mobile'=>$member['mobile']));
			}
			 
		} else if ($order['status'] == 1) {

			//商家通知
			$newtype = explode(',', $tm['newtype']);
			if  (is_array($newtype) && in_array('1', $newtype) ) {
				$remark = "\n订单已经下单支付，请及时备货，谢谢!";
				if (!empty($buyerinfo)) {
					$remark.="\r\n购买者信息:\n" . $buyerinfo;
				}

				$msg = array(
					'first' => array('value' => "订单下单支付通知!", "color" => "#4a5077"),
					'keyword1' => array('title' => '时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
					'keyword2' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
					'keyword3' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);
				$account = m('common')->getAccount();
				if (!empty($tm['openid'])) {
					$openids = explode(',', $tm['openid']);
					foreach ($openids as $tmopenid) {
						if (empty($tmopenid)) {
							continue;
						}
						$this->sendNotice(array(
							"openid" => $tmopenid,
							'tag' => 'saler_pay',
							'default' => $msg,
							'datas' => $datas
						));
						
//						if (!empty($tm['new'])) {
//							m('message')->sendTplNotice($tmopenid, $tm['new'], $msg, '', $account);
//						} else {
//							m('message')->sendCustomNotice($tmopenid, $msg, '', $account);
//						}
					}
				}
			}
			// 短信通知
			if(!empty($tm['mobile']) && empty($tm['saler_pay_close_sms'])){
				$mobiles = explode(',', $tm['mobile']);
				foreach ($mobiles as $mobile) {
					if (empty($mobile)) {
						continue;
					}
					com_run('sms::callsms', array('tag' => 'saler_pay', 'datas' => $datas, 'mobile' => $mobile));
				}
			}

			$remark = "\r\n商品已经下单支付，请及时备货，谢谢!";
			if (!empty($buyerinfo)) {
				$remark.="\r\n购买者信息:\n" . $buyerinfo;
			}
			foreach ($order_goods as $og) {
				// if (!empty($og['noticeopenid']) && !empty($og['noticetype'])) {
				$noticetype = explode(',', $og['noticetype']);
				if ($og['noticetype'] == '1' || (is_array($noticetype) && in_array('1', $noticetype) )) {

					$goodstr = $og['title'] . '( ';
					if (!empty($og['optiontitle'])) {
						$goodstr.=" 规格: " . $og['optiontitle'];
					}
					$goodstr.=' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . "); ";

					$msg = array(
						'first' => array('value' => "商品下单支付通知!", "color" => "#4a5077"),
						'keyword1' => array('title' => '时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
						'keyword2' => array('title' => '商品名称', 'value' => $goodstr, "color" => "#4a5077"),
						'keyword3' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'remark' => array('value' => $remark, "color" => "#4a5077")
					);
					$datas[] = array('name'=>'单品详情','value'=>$goodstr);
					$this->sendNotice(array(
							"openid" => $og['noticeopenid'],
							'tag' => 'saler_pay',
							'default' => $msg,
							'datas' => $datas
						));
					
//					if (!empty($tm['new'])) {
//						m('message')->sendTplNotice($og['noticeopenid'], $tm['new'], $msg, '', $account);
//					} else {
//						m('message')->sendCustomNotice($og['noticeopenid'], $msg, '', $account);
//					}
				}
			}



			//支付成功通知
			// {{first.DATA}}
			//订单：{{keyword1.DATA}}
			//支付状态：{{keyword2.DATA}}
			//支付日期：{{keyword3.DATA}}
			//商户：{{keyword4.DATA}}
			//金额：{{keyword5.DATA}}
			//{{remark.DATA}}
			if(empty($usernotice['pay'])){
				$remark = "\r\n【" . $shop['name'] . "】欢迎您的再次购物！";
				if ($order['isverify']) {
					//核销单
					$remark = "\r\n点击订单详情查看可消费门店, 【" . $shop['name'] . "】欢迎您的再次购物！";
				} else if ($order['dispatchtype']) {
					//快递单
					$remark = "\r\n您可以到选择的自提点进行取货了,【" . $shop['name'] . "】欢迎您的再次购物！";
				}
				$msg = array(
					'first' => array('value' => "您已支付成功订单！", "color" => "#4a5077"),
					'keyword1' => array('title' => '订单', 'value' => $order['ordersn'], "color" => "#4a5077"),
					'keyword2' => array('title' => '支付状态', 'value' => '支付成功', "color" => "#4a5077"),
					'keyword3' => array('title' => '支付日期', 'value' => date('Y-m-d H:i:s', $order['paytime']), "color" => "#4a5077"),
					'keyword4' => array('title' => '商户', 'value' => $shop['name'], "color" => "#4a5077"),
					'keyword5' => array('title' => '金额', 'value' => '¥' . $order['price'] . '元(含运费' . $order['dispatchprice'] . '元)', "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);
				$this->sendNotice(array(
								"openid" => $openid,
								'tag' => 'pay',
								'default' => $msg,
								'datas' => $datas
				));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'pay', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
//			
// 
//			if (!empty($tm['pay']) && empty($usernotice['pay'])) {
//				m('message')->sendTplNotice($openid, $tm['pay'], $msg, $pay_detailurl);
//			} else if (empty($usernotice['pay'])) {
//				m('message')->sendCustomNotice($openid, $msg, $pay_detailurl);
//			}

			if ($order['dispatchtype'] == 1 && empty($order['isverify'])) {
				if(!empty($usernotice['carrier'])){
					return;
				}
				//自提
				//
                // {{first.DATA}}
				//自提码：{{keyword1.DATA}}
				//商品详情：{{keyword2.DATA}}
				//提货地址：{{keyword3.DATA}}
				//提货时间：{{keyword4.DATA}}
				//{{remark.DATA}}
				if (!$carrier || !$store) {
					return;
				}
				$msg = array(
					'first' => array('value' => "自提订单提交成功!", "color" => "#4a5077"),
					'keyword1' => array('title' => '自提码', 'value' => $order['verifycode'], "color" => "#4a5077"),
					'keyword2' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
					'keyword3' => array('title' => '提货地址', 'value' => $store['address'], "color" => "#4a5077"),
					'keyword4' => array('title' => '提货时间', 'value' => $store['saletime'], "color" => "#4a5077"),
					'remark' => array('value' => "\r\n请您到选择的自提点进行取货, 自提联系人: " . $store['realname'] . ' 联系电话: ' . $store['mobile'], "color" => "#4a5077")
				);
	                             $this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'carrier',
						'default' => $msg,
						'datas' => $datas
			           ));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'carrier', 'datas' => $datas, 'mobile' => $member['mobile']));

//				if (!empty($tm['carrier']) && empty($usernotice['carrier'])) {
//					m('message')->sendTplNotice($openid, $tm['carrier'], $msg, $detailurl);
//				} else if (empty($usernotice['carrier'])) {
//					m('message')->sendCustomNotice($openid, $msg, $detailurl);
//				}
			}
		} else if ($order['status'] == 2) {

			//发货

			if (empty($order['dispatchtype'])) {
				
				if(!empty($usernotice['send'])){
					return;
				}
				
				//快递
				//
                //发货通知
				//{{first.DATA}}
				//订单内容：{{keyword1.DATA}}
				//物流服务：{{keyword2.DATA}}
				//快递单号：{{keyword3.DATA}}
				//收货信息：{{keyword4.DATA}}
				//{{remark.DATA}}

				if (empty($address)) {
					return;
				}

				$msg = array(
					'first' => array('value' => "您的宝贝已经发货！", "color" => "#4a5077"),
					'keyword1' => array('title' => '订单内容', 'value' => "【" . $order['ordersn'] . "】" . $goods . $orderpricestr, "color" => "#4a5077"),
					'keyword2' => array('title' => '物流服务', 'value' => $order['expresscom'], "color" => "#4a5077"),
					'keyword3' => array('title' => '快递单号', 'value' => $order['expresssn'], "color" => "#4a5077"),
					'keyword4' => array('title' => '收货信息', 'value' => "地址: " . $address['province'] . ' ' . $address['city'] . ' ' . $address['area'] . ' ' . $address['address'] . "收件人: " . $address['realname'] . ' (' . $address['mobile'] . ') ', "color" => "#4a5077"),
					'remark' => array('value' => "\r\n我们正加速送到您的手上，请您耐心等候。", "color" => "#4a5077")
				);
				
				$this->sendNotice(array(
						"openid" => $openid,
						'tag' => 'send',
						'default' => $msg,
						'datas' => $datas
			         ));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'send', 'datas' => $datas, 'mobile' => $member['mobile']));
//				   
//				if (!empty($tm['send']) && empty($usernotice['send'])) {
//					m('message')->sendTplNotice($openid, $tm['send'], $msg, $detailurl);
//				} else if (empty($usernotice['send'])) {
//
//					m('message')->sendCustomNotice($openid, $msg, $detailurl);
//				}
			}
		} else if ($order['status'] == 3) {
			$pv = com('virtual');
			if ($pv && !empty($order['virtual'])) {
				//虚拟卡密自动发货
				//                {{first.DATA}}
				//商品名称：{{keyword1.DATA}}
				//订单号：{{keyword2.DATA}}
				//订单金额：{{keyword3.DATA}}
				//卡密信息：{{keyword4.DATA}}
				//{{remark.DATA}}
				if(empty($usernotice['virtualsend'])){
					$virtual_str = "\n" . $buyerinfo . "\n" . $order['virtual_str'];

					$msg = array(
						'first' => array('value' => "您购物的物品已自动发货!", "color" => "#4a5077"),
						'keyword1' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
                        'keyword2' => array('title' => '订单号', 'value' => "【" . $order['ordersn'] . "】", "color" => "#4a5077"),
                        'keyword3' => array('title' => '订单金额', 'value' => '¥' . $order['price'] . '元', "color" => "#4a5077"),
						'keyword4' => array('title' => '卡密信息', 'value' => $virtual_str, "color" => "#4a5077"),
						'remark' => array('title' => '', 'value' => "\r\n【" . $shop['name'] . '】感谢您的支持与厚爱，欢迎您的再次购物！', "color" => "#4a5077")
					);
	//
	//				if (!empty($pvset['tm']['send']) && empty($usernotice['finish'])) {
	//					m('message')->sendTplNotice($openid, $pvset['tm']['send'], $msg, $detailurl);
	//				} else if (empty($usernotice['finish'])) {
	//					m('message')->sendCustomNotice($openid, $msg, $detailurl);
	//				}
					$this->sendNotice(array(
							"openid" => $openid,
							'tag' => 'virtualsend',
							'default' => $msg,
							'datas' => $datas
						 ));
					// 短信通知
					com_run('sms::callsms', array('tag' => 'virtualsend', 'datas' => $datas, 'mobile' => $member['mobile']));
				}
						
				//商家通知
				$first = "买家购买的商品已经自动发货!";
				$remark = "\r\n发货信息:" . $virtual_str;

				$newtype = explode(',', $tm['newtype']);
				if (is_array($newtype) && in_array('2', $newtype) ) {

					$msg = array(
						'first' => array('value' => $first, "color" => "#4a5077"),
						'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'keyword2' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
						'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
						'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
						'remark' => array('title' => '', 'value' => $remark, "color" => "#4a5077")
					);

					$account = m('common')->getAccount();
					if (!empty($tm['openid'])) {
						$openids = explode(',', $tm['openid']);
						foreach ($openids as $tmopenid) {
							if (empty($tmopenid)) {
								continue;
							}
							$this->sendNotice(array(
								"openid" => $tmopenid,
								'tag' => 'saler_finish',
								'default' => $msg,
								'datas' => $datas
							 ));

//							if (!empty($tm['finish'])) {
//								m('message')->sendTplNotice($tmopenid, $tm['finish'], $msg, '', $account);
//							} else {
//								m('message')->sendCustomNotice($tmopenid, $msg, '', $account);
//							}
						}
					}
				}
				// 短信通知
				if (!empty($tm['mobile']) && empty($tm['saler_finish_close_sms'])) {
					$mobiles = explode(',', $tm['mobile']);
					foreach ($mobiles as $mobile) {
						if (empty($mobile)) {
							continue;
						}
						com_run('sms::callsms', array('tag'=>'saler_finish', 'datas' => $datas, 'mobile' => $mobile));
					}
				}

				foreach ($order_goods as $og) {
					// if (!empty($og['noticeopenid']) && !empty($og['noticetype'])) {
					$noticetype = explode(',', $og['noticetype']);
					if ($og['noticetype'] == '2' || (is_array($noticetype) && in_array('2', $noticetype) )) {

						$goodstr = $og['title'] . '( ';
						if (!empty($og['optiontitle'])) {
							$goodstr.=" 规格: " . $og['optiontitle'];
						}
						$goodstr.=' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . "); ";

						$msg = array(
							'first' => array('value' => $first, "color" => "#4a5077"),
							'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
							'keyword2' => array('title' => '商品名称', 'value' => $goodstr, "color" => "#4a5077"),
							'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
							'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
							'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
							'remark' => array('title' => '', 'value' => $remark, "color" => "#4a5077")
						);
						
						$datas[] = array('name'=>'单品详情','value'=>$goodstr);
						$this->sendNotice(array(
								"openid" => $og['noticeopenid'],
								'tag' => 'saler_finish',
								'default' => $msg,
								'datas' => $datas
						 ));

//						if (!empty($tm['finish'])) {
//							m('message')->sendTplNotice($og['noticeopenid'], $tm['finish'], $msg, '', $account);
//						} else {
//							m('message')->sendCustomNotice($og['noticeopenid'], $msg, '', $account);
//						}
					}
				}
            } else if ($order['isvirtualsend']) { //虚拟物品自动发货

                if(empty($usernotice['virtualsend'])){
                    //虚拟物品自动发货
                    //                {{first.DATA}}
                    //商品名称：{{keyword1.DATA}}
                    //订单号：{{keyword2.DATA}}
                    //订单金额：{{keyword3.DATA}}
                    //卡密信息：{{keyword4.DATA}}
                    //{{remark.DATA}}
                    $virtual_str =  $order['virtualsend_info']. "\n" . $buyerinfo;
                    $msg = array(
                        'first' => array('value' => "您购物的物品已自动发货!", "color" => "#4a5077"),
                        'keyword1' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
                        'keyword2' => array('title' => '订单号', 'value' => "【" . $order['ordersn'] . "】", "color" => "#4a5077"),
                        'keyword3' => array('title' => '订单金额', 'value' => '¥' . $order['price'] . '元', "color" => "#4a5077"),
                        'keyword4' => array('title' => '卡密信息', 'value' => $virtual_str, "color" => "#4a5077"),
                        'remark' => array('title' => '', 'value' => "\r\n【" . $shop['name'] . '】感谢您的支持与厚爱，欢迎您的再次购物！', "color" => "#4a5077")
                    );

					$this->sendNotice(array(
								"openid" =>$openid,
								'tag' => 'virtualsend',
								'default' => $msg,
								'datas' => $datas
					 ));
				   com_run('sms::callsms', array('tag' => 'virtualsend', 'datas' => $datas, 'mobile' => $member['mobile']));
			   }

				//商家通知
				$first = "买家购买的商品已经自动发货!";
				$remark = "\r\n发货信息:" . $virtual_str;

				$newtype = explode(',', $tm['newtype']);
				if (is_array($newtype) && in_array('2', $newtype)) {

					$msg = array(
						'first' => array('value' => $first, "color" => "#4a5077"),
						'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'keyword2' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
						'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
						'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
						'remark' => array('title' => '', 'value' => $remark, "color" => "#4a5077")
					);

					$account = m('common')->getAccount();
					if (!empty($tm['openid'])) {
						$openids = explode(',', $tm['openid']);
						foreach ($openids as $tmopenid) {
							if (empty($tmopenid)) {
								continue;
							}
							$this->sendNotice(array(
									"openid" =>$tmopenid,
									'tag' => 'saler_finish',
									'default' => $msg,
									'datas' => $datas
						         ));
//							
//							if (!empty($tm['finish'])) {
//								m('message')->sendTplNotice($tmopenid, $tm['finish'], $msg, '', $account);
//							} else {
//								m('message')->sendCustomNotice($tmopenid, $msg, '', $account);
//							}
						}
					}
				}
				// 短信通知
				if (!empty($tm['mobile']) && empty($tm['saler_finish_close_sms'])) {
					$mobiles = explode(',', $tm['mobile']);
					foreach ($mobiles as $mobile) {
						if (empty($mobile)) {
							continue;
						}
						com_run('sms::callsms', array('tag' => 'saler_finish', 'datas' => $datas, 'mobile' => $mobile));
					}
				}

				foreach ($order_goods as $og) {

					$noticetype = explode(',', $og['noticetype']);
					if ($og['noticetype'] == '2' || (is_array($noticetype) && in_array('2', $noticetype) )) {

						$goodstr = $og['title'] . '( ';
						if (!empty($og['optiontitle'])) {
							$goodstr.=" 规格: " . $og['optiontitle'];
						}
						$goodstr.=' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . "); ";

						$msg = array(
							'first' => array('value' => $first, "color" => "#4a5077"),
							'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
							'keyword2' => array('title' => '商品名称', 'value' => $goodstr, "color" => "#4a5077"),
							'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
							'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
							'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
							'remark' => array('title' => '', 'value' => $remark, "color" => "#4a5077")
						);
						
						 $datas[] = array('name'=>'单品详情','value'=>$goodstr);
					          $this->sendNotice(array(
							"openid" => $og['noticeopenid'],
							'tag' => 'finish',
							'default' => $msg,
							'datas' => $datas
						));
//
//						if (!empty($tm['finish'])) {
//							m('message')->sendTplNotice($og['noticeopenid'], $tm['finish'], $msg, '', $account);
//						} else {
//							m('message')->sendCustomNotice($og['noticeopenid'], $msg, '', $account);
//						}
					}
				}
			} else {

				if(!empty($usernotice['finish'])){
					   return;
				}
				
				//收货通知
				//{{first.DATA}}
				//订单号：{{keyword1.DATA}}
				//商品名称：{{keyword2.DATA}}
				//下单时间：{{keyword3.DATA}}
				//发货时间：{{keyword4.DATA}}
				//确认收货时间：{{keyword5.DATA}}
				//{{remark.DATA}}

				$first = "亲, 您购买的宝贝已经确认收货!";
				if ($order['isverify'] == 1) {
					$first = "亲, 您购买的宝贝已经确认使用!";
				}

				$msg = array(
					'first' => array('value' => $first, "color" => "#4a5077"),
					'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
					'keyword2' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
					'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
					'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
					'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
					'remark' => array('title' => '', 'value' => "\r\n【" . $shop['name'] . '】感谢您的支持与厚爱，欢迎您的再次购物！', "color" => "#4a5077")
				);
				
			         $this->sendNotice(array(
							"openid" =>$openid,
							'tag' => 'finish',
							'default' => $msg,
							'datas' => $datas
				  ));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'finish', 'datas' => $datas, 'mobile' => $member['mobile']));

//				if (!empty($tm['finish']) && empty($usernotice['finish'])) {
//
//					m('message')->sendTplNotice($openid, $tm['finish'], $msg, $detailurl);
//				} else if (empty($usernotice['finish'])) {
//					m('message')->sendCustomNotice($openid, $msg, $detailurl);
//				}

				//商家通知

				$first = "买家购买的商品已经确认收货!";
				if ($order['isverify'] == 1) {
					$first = "买家购买的商品已经确认核销!";
				}
				$remark = "";
				if (!empty($buyerinfo)) {
					$remark = "\r\n购买者信息:\n" . $buyerinfo;
				}

/**/
				$newtype = explode(',', $tm['newtype']);

				if (is_array($newtype) && in_array('2', $newtype) ) {
					
					$msg = array(
						'first' => array('value' => $first, "color" => "#4a5077"),
						'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
						'keyword2' => array('title' => '商品名称', 'value' => $goods . $orderpricestr, "color" => "#4a5077"),
						'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
						'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
						'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
						'remark' => array('title' => '', 'value' => $remark, "color" => "#4a5077")
					);

					$account = m('common')->getAccount();
					if (!empty($tm['openid'])) {
						$openids = explode(',', $tm['openid']);
						foreach ($openids as $tmopenid) {
							if (empty($tmopenid)) {
								continue;
							}
							 $this->sendNotice(array(
								"openid" => $tmopenid,
								'tag' => 'saler_finish',
								'default' => $msg,
								'datas' => $datas
							 ));
							 
//							if (!empty($tm['finish'])) {
//								m('message')->sendTplNotice($tmopenid, $tm['finish'], $msg, '', $account);
//							} else {
//								m('message')->sendCustomNotice($tmopenid, $msg, '', $account);
//							}
						}
					}
				}
				// 短信通知
				if (!empty($tm['mobile']) && empty($tm['saler_finish_close_sms'])) {
					$mobiles = explode(',', $tm['mobile']);
					foreach ($mobiles as $mobile) {
						if (empty($mobile)) {
							continue;
						}
						com_run('sms::callsms', array('tag' => 'saler_finish', 'datas' => $datas, 'mobile' => $mobile));
					}
				}

				foreach ($order_goods as $og) {
					// if (!empty($og['noticeopenid']) && !empty($og['noticetype'])) {
					$noticetype = explode(',', $og['noticetype']);
					if ($og['noticetype'] == '2' || (is_array($noticetype) && in_array('2', $noticetype) )) {

						$goodstr = $og['title'] . '( ';
						if (!empty($og['optiontitle'])) {
							$goodstr.=" 规格: " . $og['optiontitle'];
						}
						$goodstr.=' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . "); ";

						$msg = array(
							'first' => array('value' => $first, "color" => "#4a5077"),
							'keyword1' => array('title' => '订单号', 'value' => $order['ordersn'], "color" => "#4a5077"),
							'keyword2' => array('title' => '商品名称', 'value' => $goodstr, "color" => "#4a5077"),
							'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
							'keyword4' => array('title' => '发货时间', 'value' => date('Y-m-d H:i:s', $order['sendtime']), "color" => "#4a5077"),
							'keyword5' => array('title' => '确认收货时间', 'value' => date('Y-m-d H:i:s', $order['finishtime']), "color" => "#4a5077"),
							'remark' => array('title' => '', 'value' => $remark, "color" => "#4a5077")
						);
						
						$datas[] = array('name'=>'单品详情','value'=>$goodstr);
						 $this->sendNotice(array(
							"openid" => $og['noticeopenid'],
							'tag' => 'saler_finish',
							'default' => $msg,
							'datas' => $datas
				                  ));

//						if (!empty($tm['finish'])) {
//							m('message')->sendTplNotice($og['noticeopenid'], $tm['finish'], $msg, '', $account);
//						} else {
//							m('message')->sendCustomNotice($og['noticeopenid'], $msg, '', $account);
//						}
					}
				}
			}
		}
	}

	/**
	 * 会员升级提醒
	 * @param type $openid
	 * @param type $oldlevel
	 * @param type $level
	 * @return type
	 */
	public function sendMemberUpgradeMessage($openid = '', $oldlevel = null, $level = null) {
		global $_W, $_GPC;
		$member = m('member')->getMember($openid);
		$detailurl = $this->getUrl('member');
		$usernotice = unserialize($member['noticeset']);
		if (!is_array($usernotice)) {
			$usernotice = array();
		}
		if (!empty($usernotice['upgrade'])) {
			//用户关闭
			return;
		}
		if (!$level) {
			$level = m('member')->getLevel($openid);
		}
		$oldlevelname = empty($oldlevel['levelname']) ? '普通会员' : $oldlevel['levelname'];
		$message = array(
			'first' => array('value' => "亲爱的" . $member['nickname'] . ', 恭喜您成功升级！', "color" => "#4a5077"),
			'keyword1' => array('title' => '任务名称', 'value' => '会员升级', "color" => "#4a5077"),
			'keyword2' => array('title' => '通知类型', 'value' => '您会员等级从 ' . $oldlevelname . ' 升级为 ' . $level['levelname'] . ', 特此通知!', "color" => "#4a5077"),
			'remark' => array('value' => "\r\n您即可享有" . $level['levelname'] . '的专属优惠及服务！', "color" => "#4a5077")
		);
		$datas = array(
			array("name" => "商城名称", "value" => $_W['shopset']['shop']['name']),
			array("name" => "粉丝昵称", "value" => $member['nickname']),
			array("name" => "旧等级", "value" => $oldlevelname),
			array("name" => "新等级", "value" => $level['levelname']),
		);
		$this->sendNotice(array(
			"openid" => $openid,
			'tag' => 'upgrade',
			'default' => $message,
			'url' => $detailurl,
			'datas' =>$datas
		));
		// 短信通知
		com_run('sms::callsms', array('tag' => 'upgrade', 'datas' => $datas, 'mobile' => $member['mobile']));
	}

	/**
	 * 会员充值提现消息
	 * @param type $openid
	 * @param type $oldlevel
	 * @param type $level
	 * @return type
	 */
	public function sendMemberLogMessage($log_id = '',$channel = 0) {
		global $_W, $_GPC;
		$log_info = pdo_fetch('select * from ' . tablename('ewei_shop_member_log') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $log_id, ':uniacid' => $_W['uniacid']));
		$member = m('member')->getMember($log_info['openid']);
		$usernotice = unserialize($member['noticeset']);
		if (!is_array($usernotice)) {
			$usernotice = array();
		}
		$account = m('common')->getAccount();
		if (!$account) {
			return;
		}
		$datas = array(
			array("name" => "商城名称", "value" => $_W['shopset']['shop']['name']),
			array("name" => "粉丝昵称", "value" => $member['nickname'])
		);

		$log_info['gives'] = floatval($log_info['gives']);
		$log_info['money'] = floatval($log_info['money']);

		//充值成功
		if ($log_info['type'] == 0) {


			$type = "后台充值";
            if ($channel === 1){$type = "兑换券";}
			if ($log_info['rechargetype'] == 'wechat') {
				$type = "微信支付";
			} else if ($log_info['rechargetype'] == 'alipay') {
				$type = "支付宝";
			}
			$datas[] = array('name' => '支付方式', 'value' => $type);
			$datas[] = array('name' => '充值金额', 'value' => $log_info['money']);
			$datas[] = array('name' => '充值时间', 'value' => date('Y-m-d H:i', $log_info['createtime']));
			$datas[] = array('name' => '赠送金额', 'value' => $log_info['gives']);
			$datas[] = array('name' => '到帐金额', 'value' => $log_info['money'] + $log_info['gives']);
			$datas[] = array('name' => '实际到账', 'value' => $log_info['money'] + $log_info['gives']);
			$datas[] = array('name' => '退款金额', 'value' => $log_info['money'] + $log_info['gives']);

			//模版消息id：echo $tm['recharge_ok'];
			//{{first.DATA}}
			//充值金额:{{money.DATA}}
			//充值方式:{{product.DATA}}
			///{{remark.DATA}} 
			if ($log_info['status'] == 1) {

				if (!empty($usernotice['recharge_ok'])) {
					return;
				}

				$money = '¥' . $log_info['money'] . '元';
				if ($log_info['gives'] > 0) {
					$totalmoney = $log_info['money'] + $log_info['gives'];
					$money.="，系统赠送" . $log_info['gives'] . '元，合计:' . $totalmoney . '元';
				}
				$message = array(
					'first' => array('value' => "恭喜您充值成功!", "color" => "#4a5077"),
					'money' => array('title' => '充值金额', 'value' => '¥' . $log_info['money'] . '元', "color" => "#4a5077"),
					'product' => array('title' => '充值方式', 'value' => $type, "color" => "#4a5077"),
					'remark' => array('value' => "\r\n谢谢您对我们的支持！", "color" => "#4a5077")
				);

				$this->sendNotice(array(
					"openid" => $log_info['openid'],
					'tag' => 'recharge_ok',
					'default' => $message,
					'url' => $this->getUrl('member'),
					'datas' => $datas
				));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'recharge_ok', 'datas' => $datas, 'mobile' => $member['mobile']));
			} else if ($log_info['status'] == 3) {

				if (!empty($usernotice['recharge_fund'])) {
					return;
				}
				$message = array(
					'first' => array('value' => "充值退款成功!", "color" => "#4a5077"),
					'reason' => array('title' => '退款原因', 'value' => '【' . $_W['shopset']['shop']['name'] . '】充值退款', "color" => "#4a5077"),
					'refund' => array('title' => '退款金额', 'value' => '¥' . $log_info['money'] . '元', "color" => "#4a5077"),
					'remark' => array('value' => "\r\n退款成功，请注意查收! 谢谢您对我们的支持！", "color" => "#4a5077")
				);
				$this->sendNotice(array(
					"openid" => $log_info['openid'],
					'tag' => 'recharge_refund',
					'default' => $message,
					'url' => $this->getUrl('member'),
					'datas' => $datas
				));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'recharge_refund', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
		}
		//提现申请
		else if ($log_info['type'] == 1) {

			$datas[] = array('name' => '提现金额', 'value' => $log_info['money']);
			$datas[] = array('name' => '提现时间', 'value' => date('Y-m-d H:i', $log_info['createtime']));

            if ($log_info['deductionmoney'] == 0) {
                $realmoeny = $log_info['money'];
            } else {
                $realmoeny = $log_info['realmoney'];
            }

            //申请提醒
			if ($log_info['status'] == 0) {

				if (!empty($usernotice['withdraw'])) {
					return;
				}

				/*
				 * {{first.DATA}}
				  提现金额:{{money.DATA}}
				  提现时间:{{timet.DATA}}
				  {{remark.DATA}}
				 */
				$message = array(
					'first' => array('value' => "提现申请已经成功提交!", "color" => "#4a5077"),
					'money' => array('title' => '提现金额/到账金额', 'value' => '¥' . $log_info['money'] . '元/¥' . $realmoeny . '元', "color" => "#4a5077"),
					'timet' => array('title' => '提现时间', 'value' => date('Y-m-d H:i:s', $log_info['createtime']), "color" => "#4a5077"),
					'remark' => array('value' => "\r\n请等待我们的审核并打款！", "color" => "#4a5077")
				);

				$this->sendNotice(array(
					"openid" => $log_info['openid'],
					'tag' => 'withdraw',
					'default' => $message,
					'url' => $this->getUrl('member/log', array('type' => 1)),
					'datas' => $datas
				));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'withdraw', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
			//成功提醒
			else if ($log_info['status'] == 1) {

				if (!empty($usernotice['withdraw_ok'])) {
					return;
				}

				/* echo $tm['withdraw_ok'];
				 * {{first.DATA}}
				  提现金额:{{money.DATA}}
				  提现时间:{{timet.DATA}}
				  {{remark.DATA}}
				 */
				$message = array(
					'first' => array('value' => "恭喜您成功提现!", "color" => "#4a5077"),
					'money' => array('title' => '提现金额/到账金额', 'value' => '¥' . $log_info['money'] . '元/¥' . $realmoeny . '元', "color" => "#4a5077"),
					'timet' => array('title' => '提现时间', 'value' => date('Y-m-d H:i:s', $log_info['createtime']), "color" => "#4a5077"),
					'remark' => array('value' => "\r\n感谢您的支持！", "color" => "#4a5077")
				);
				$this->sendNotice(array(
					"openid" => $log_info['openid'],
					'tag' => 'withdraw_ok',
					'default' => $message,
					'url' => $this->getUrl('member/log', array('type' => 1)),
					'datas' => $datas
				));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'withdraw_ok', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
			//失败提醒
			else if ($log_info['status'] == -1) {

				if (!empty($usernotice['withdraw_fail'])) {
					return;
				}


				/* echo $tm['withdraw_fail'];
				 * {{first.DATA}}
				  提现金额:{{money.DATA}}
				  提现时间:{{time.DATA}}
				  {{remark.DATA}}
				 */
				$message = array(
					'first' => array('value' => "抱歉，提现申请审核失败!", "color" => "#4a5077"),
					'money' => array('title' => '提现金额/到账金额',  'value' => '¥' . $log_info['money'] . '元/¥' . $realmoeny . '元', "color" => "#4a5077"),
					'timet' => array('title' => '提现时间', 'value' => date('Y-m-d H:i:s', $log_info['createtime']), "color" => "#4a5077"),
					'remark' => array('value' => "\r\n有疑问请联系客服，谢谢您的支持！", "color" => "#4a5077")
				);

				$this->sendNotice(array(
					"openid" => $log_info['openid'],
					'tag' => 'withdraw_fail',
					'default' => $message,
					'url' => $this->getUrl('member/log', array('type' => 1)),
					'datas' => $datas
				));
				// 短信通知
				com_run('sms::callsms', array('tag' => 'withdraw_fail', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
		}
	}

	public function sendNotice(array $params) {
		global $_W, $_GPC;

		$tag = isset($params['tag']) ? $params['tag'] : '';
		$touser = isset($params['openid']) ? $params['openid'] : '';
		if (empty($touser)) {
			return;
		}
		$tm = $_W['shopset']['notice'];
		if(empty($tm)) {
			$tm = m('common')->getSysset('notice');
		}

		$tm_temp = $tm[$tag . "_template"];
		$tm_tag = $tm[$tag];

		if(($tag=='saler_submit' || $tag=='saler_pay' || $tag=='saler_finish')){
			$tm_tag = $tm['new'];
		}
		/*
		if(($tag=='saler_submit' || $tag=='saler_pay' || $tag=='saler_finish') && empty($tm_temp)){
			$tm_temp = $tm['new'];
		}*/

		$templateid = $tm['is_advanced'] ? $tm_temp : $tm_tag;

		$default_message = isset($params['default']) ? $params['default'] : array();
		$url = isset($params['url']) ? $params['url'] : '';
		$account = isset($params['account']) ? $params['account'] : m('common')->getAccount();
		$datas = isset($params['datas']) ? $params['datas'] : array();
		$advanced_message = false;

		if ($tm['is_advanced']) {

			if(!empty($tm[$tag.'_close_advanced'])){
				//关闭提醒
				return;
			}
			//高级模式
			if (!empty($templateid)) {
				$advanced_template = pdo_fetch('select * from ' . tablename('ewei_shop_member_message_template') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $templateid, ':uniacid' => $_W['uniacid']));
				if (!empty($advanced_template)) {
					$advanced_message = array(
						'first' => array('value' => $this->replaceTemplate($advanced_template['first'], $datas), 'color' => $advanced_template['firstcolor']),
						'remark' => array('value' => $this->replaceTemplate($advanced_template['remark'], $datas), 'color' => $advanced_template['remarkcolor'])
					);
					$data = iunserializer($advanced_template['data']);
					foreach ($data as $d) {
						$advanced_message[$d['keywords']] = array('value' => $this->replaceTemplate($d['value'], $datas), 'color' => $d['color']);
					}
					//高级模板消息
					$ret = m('message')->sendTplNotice($touser, $advanced_template['template_id'], $advanced_message, $url, $account);
					if (is_error($ret)) {
						//高级客服消息
						$ret = m('message')->sendCustomNotice($touser, $advanced_message, $url, $account);
						if (is_error($ret)) {
							//默认客服消息
							$ret = m('message')->sendCustomNotice($touser, $advanced_message, $url, $account);
						}
					}
				} else {
					//默认客服消息
					m('message')->sendCustomNotice($touser, $default_message, $url, $account);
				}
			} else {
				//默认客服消息
				m('message')->sendCustomNotice($touser, $default_message, $url, $account);
			}
		} else {
			if(!empty($tm[$tag.'_close_normal'])){
				//关闭提醒
				return;
			}
			//默认模板消息
			$ret = m('message')->sendTplNotice($touser, $templateid, $default_message, $url, $account);

			if (is_error($ret)) {
				//默认客服消息
				m('message')->sendCustomNotice($touser, $default_message, $url, $account);
			}
		}
	}

	protected function replaceTemplate($str, $datas = array()) {
		foreach ($datas as $d) {
			$str = str_replace("[" . $d['name'] . "]", $d['value'], $str);
		}
		return $str;
	}

    public function sendMessage($openid,$params,$type)
    {
        global $_W;
        if (empty($openid)){
            return false;
        }
        $member = m('member')->getMember($openid);
        if($type=='orderstatus'){
            $datas = array(
                array('name'=>'粉丝昵称','value'=>$member['nickname']),
                array('name'=>'修改时间','value'=>time()),
                array('name'=>'订单编号','value'=>$params['ordersn']),
                array('name'=>'原收货地址','value'=>$params['olddata']),
                array('name'=>'新收货地址','value'=>$params['data']),
                array('name'=>'订单原价格','value'=>$params['olddata']),
                array('name'=>'订单新价格','value'=>$params['data'])
            );

            $msg = array(
                'first' => array('value' => $params['title'] . " 修改提醒！", "color" => "#4a5077"),
                'OrderSn' => array('title' => '订单编号', 'value' => $params['ordersn'], "color" => "#4a5077"),
                'OrderStatus' => array('title' => '订单状态', 'value' => "已修改", "color" => "#4a5077"),
                'remark' => array('value' => "\r\n原收货地址 : ".$params['olddata']."\r\n新收货地址 : ".$params['data'], "color" => "#4a5077"),
            );
            if ($params['type'] == '1'){
                $msg['remark'] = array('value' => "\r\n订单原价格 : ".$params['olddata']."元,\r\n订单新价格 : ".$params['data']."元.","color" => "#4a5077");
            }

            $this->sendNotice(array(
                "openid" => $openid,
                'tag' => 'orderstatus',
                'default' => $msg,
                'url' => $params['url'],
                'datas' => $datas
            ));
            // 短信通知
            com_run('sms::callsms', array('tag' => 'orderstatus', 'datas' => $datas, 'mobile' =>$member['mobile']));
        }
	}

}
