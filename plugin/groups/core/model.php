<?php
/*

 * 人人商城

 *

 * @author ewei 狸小狐 QQ:22185157

 */

if (!defined('IN_IA')) {

	exit('Access Denied');

}
class GroupsModel extends PluginModel {

	/*public function exhelper(){
		$plugins = pdo_fetch("select status from ".tablename('ewei_shop_plugin')." where identity = :identity ",array(':identity'=>'exhelper'));
		if($plugins['status']==1){
			$exhelper = 1;
		}else{
			$exhelper = 0;
		}
	}*/

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

	public function orderstest(){
		global $_W;
		$uniacid = $_W['uniacid'];
		//检测未付款订单
		$sql= "SELECT * FROM".tablename('ewei_shop_groups_order')."where uniacid = :uniacid and status = 0 ";
		$params= array('uniacid'=> $uniacid);
		$allorders = pdo_fetchall($sql, $params);
		if($allorders){
			//24小时未付款订单自动取消
			foreach($allorders as $key => $value){
				$hours = $value['endtime'];//
				$time = time();
				$date = date('Y-m-d H:i:s',$value['createtime']); //订单创建时间
				$endtime = date('Y-m-d H:i:s',strtotime(" $date + $hours hour"));

				$date1 = date('Y-m-d H:i:s',$time); /*当前时间*/
				$lasttime2 = strtotime($endtime)-strtotime($date1);//剩余时间（秒数）

				if($lasttime2 < 0){
					pdo_update('ewei_shop_groups_order', array('status'=>-1), array('id' => $value['id']));
				}
			}
		}
		//检测拼团中的订单是否过期
		$sql1 = "SELECT * FROM".tablename('ewei_shop_groups_order')."where uniacid = :uniacid and heads = 1 and status = 1 and success = 0 ";
		$allteam = pdo_fetchall($sql1, $params);
		if($allteam){
			foreach($allteam as $key => $value){
				$total = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_order') . "  where uniacid = :uniacid and teamid = :teamid and heads = :heads and status = :status and success = :success ",
					array(':uniacid'=>$uniacid,':heads'=>1,':teamid'=>$value['teamid'],':status'=>1,':success'=>0));
				if($value['groupnum'] == $total){
					//是否满足拼团人数
					pdo_update('ewei_shop_groups_order', array('success'=>1), array('teamid' => $value['teamid']));
				}else{
					//检测拼团结束时间
					$hours = $value['endtime'];
					$time = time();
					$date = date('Y-m-d H:i:s',$value['starttime']); //团长开团时间
					$endtime = date('Y-m-d H:i:s',strtotime(" $date + $hours hour"));

					$date1 = date('Y-m-d H:i:s',$time); /*当前时间*/
					$lasttime2 = strtotime($endtime)-strtotime($date1);//剩余时间（秒数）

					if($lasttime2 < 0){
						pdo_update('ewei_shop_groups_order', array('success'=>-1,'canceltime'=>strtotime($endtime)), array('teamid' => $value['teamid']));
					}
				}
			}
		}
	}
	/**
	 * 支付成功
	 * @global type $_W
	 * @param type $params
	 */
	public function payResult($orderno,$type, $app=false) {

		global $_W;
		$uniacid = $_W['uniacid'];

		$log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_groups_paylog') . '
		 WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'groups', ':tid' => $orderno));

		$order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where  orderno =:orderno and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':orderno' => $orderno));
		if($order['status']>0){
			return true;
		}
		$openid = $order['openid'];
		$order_goods = pdo_fetch('select * from  '. tablename('ewei_shop_groups_goods') . '
					where id = :id and uniacid=:uniacid ', array(':uniacid' => $uniacid, ':id' => $order['goodid']));
		//积分
		$result = m('member')->setCredit($openid, 'credit1', -$order['credit'], array($_W['member']['uid'], $_W['shopset']['shop']['name'].'消费' . $order['credit'].'积分'));
		if (is_error($result)) {
			return $result['message'];
			exit;
		}

		$record = array();
		$record['status'] = '1';
		$record['type'] = $type;

		$params = array(
			':teamid'=>$order['teamid'],
			':uniacid' => $uniacid,
			':success' => 0,
			':status' => 1
		);
		pdo_update('ewei_shop_groups_order', array('pay_type' => $type,'status'=>1,'paytime'=>TIMESTAMP,'starttime'=>TIMESTAMP, 'apppay'=>$app?1:0), array('orderno' => $orderno));

		//模板消息
		$this->sendTeamMessage($order['id']);
		if(!empty($order['is_team'])){
			$total = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_order') . " as o where status = :status and teamid = :teamid and uniacid = :uniacid and success = :success ", $params);
			if($order['groupnum']==$total){
				pdo_update('ewei_shop_groups_order', array('success'=>1), array('teamid' => $order['teamid'],'status'=>1,'uniacid' => $uniacid));
				pdo_update('ewei_shop_groups_order', array('success'=>-1,'status'=>-1,'canceltime'=>time()), array('teamid' => $order['teamid'],'status'=>0,'uniacid' => $uniacid));
				$this->sendTeamMessage($order['id']);
			}
		}

		$stock = intval($order_goods['stock'] - 1);
		$sales = intval($order_goods['sales']) + 1;
		$teamnum = intval($order_goods['teamnum']) + 1;
		pdo_update('ewei_shop_groups_goods', array('stock' => $stock,'sales'=>$sales,'teamnum'=>$teamnum), array('id' => $order_goods['id']));
		return true;
	}
	/*
	 *订单tab列表数量
	 * */
	public function getTotals() {
		global $_W;
		$paras = array(':uniacid' => $_W['uniacid']);
		//订单
		$totals['all'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid ", $paras);
		$totals['status1'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.status = 1 and (o.success = 1 or o.is_team = 0) ", $paras);
		$totals['status2'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.status=2 ", $paras);
		$totals['status3'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.status = 0 ", $paras);
		$totals['status4'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.status = 3 ", $paras);
		$totals['status5'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.status = -1 ", $paras);
		//代理订单
		$totals['disall'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid != :uniacid ", array(':uniacid' => DIS_ACCOUNT));
		$totals['disstatus1'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid != :uniacid and o.status = 1 and (o.success = 1 or o.is_team = 0) ", array(':uniacid' => DIS_ACCOUNT));
		$totals['disstatus2'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid != :uniacid and o.status=2 ", array(':uniacid' => DIS_ACCOUNT));
		$totals['disstatus3'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid != :uniacid and o.status = 0 ", array(':uniacid' => DIS_ACCOUNT));
		$totals['disstatus4'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid != :uniacid and o.status = 3 ", array(':uniacid' => DIS_ACCOUNT));
		$totals['disstatus5'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid != :uniacid and o.status = -1 ", array(':uniacid' => DIS_ACCOUNT));
		//拼团
		$totals['team1'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.heads = 1 and o.paytime > 0 and is_team = 1 and o.success = 1 ", $paras);
		$totals['team2'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.heads = 1 and o.paytime > 0 and is_team = 1 and o.success = 0 ", $paras);
		$totals['team3'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.heads = 1 and o.paytime > 0 and is_team = 1 and o.success = -1 ", $paras);
		$totals['allteam'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " o
			 WHERE o.uniacid = :uniacid and o.heads = 1 and o.paytime > 0 and is_team = 1 ", $paras);
		//维权
		$totals['refund1'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order_refund') . " as ore
			left join ".tablename('ewei_shop_groups_order')." as o on o.id = ore.orderid
			right join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
			right join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
			left join " . tablename('ewei_shop_member_address') . " a on a.id=ore.refundaddressid
			right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
			WHERE ore.uniacid = :uniacid AND o.refundstate > 0 and o.refundid != 0 and ore.refundstatus >= 0 ", $paras);
		$totals['refund2'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order_refund') . " as ore
			left join ".tablename('ewei_shop_groups_order')." as o on o.id = ore.orderid
			right join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
			right join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
			left join " . tablename('ewei_shop_member_address') . " a on a.id=ore.refundaddressid
			right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
			WHERE ore.uniacid = :uniacid AND (o.refundtime != 0 or ore.refundstatus < 0) ", $paras);
		//核销
		$totals['verify1'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " as o
			left join " . tablename('ewei_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid
			left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
			left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
			left join " . tablename('ewei_shop_member_address') . " a on a.id=o.addressid
			right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
			left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid
			left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid
			left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid=o.uniacid
			WHERE o.uniacid=:uniacid and o.isverify = 1 and o.status =  1 ", $paras);
		$totals['verify2'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " as o
			left join " . tablename('ewei_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid
			left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
			left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
			left join " . tablename('ewei_shop_member_address') . " a on a.id=o.addressid
			right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
			left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid
			left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid
			left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid=o.uniacid
			WHERE o.uniacid=:uniacid and o.isverify = 1 and o.status = 3 ", $paras);
		$totals['verify3'] = pdo_fetchcolumn(
			'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_order') . " as o
			left join " . tablename('ewei_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid
			left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
			left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
			left join " . tablename('ewei_shop_member_address') . " a on a.id=o.addressid
			right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
			left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid
			left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid
			left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid=o.uniacid
			WHERE o.uniacid=:uniacid and o.isverify = 1 and o.status <= 0 ", $paras);
		return $totals;
	}
	/*
	 * 分享
	 * */
	public function groupsShare(){
		global $_W;
		$uniacid = $_W['uniacid'];
		$share = pdo_fetch("select share_title,share_icon,share_desc,share_url from " . tablename('ewei_shop_groups_set') . ' where uniacid=:uniacid ', array(':uniacid' => $uniacid));
		$myid = m('member')->getMid();
		$set = $_W['shopset'];
		$_W['shopshare'] = array(
			'title' => !empty($share['share_title']) ? $share['share_title'] : $set['shop']['name'],
			'imgUrl' => !empty($share['share_icon']) ? tomedia($share['share_icon']) : tomedia($set['shop']['logo']),
			'desc' => !empty($share['share_desc']) ? $share['share_desc'] : $set['shop']['description'] ,
			'link' => !empty($share['share_url']) ? $share['share_url']: mobileUrl('groups',array('shareid' => $myid), true),
		);
	}
	/**
	 * 拼团发送订单通知
	 * @param type $message_type
	 * @param type $order
	 */
	public function sendTeamMessage($orderid = '0', $delRefund = false)
	{
		global $_W;
		$uniacid = $_W['uniacid'];
		$orderid = intval($orderid);
		if (empty($orderid)) {
			return;
		}
		$order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where uniacid = :uniacid and id=:id limit 1', array(':uniacid'=>$uniacid,':id' => $orderid));
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
		$goods = "待发货商品--".$order_goods['title'];
		$goods2 = $order_goods['title'];
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
			$order_refund = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order_refund') . ' where uniacid=:uniacid and id=:id ', array(':uniacid' => $_W['uniacid'], ':id' => intval($order['refundid'])));
			$refundtype = '';
			if ($order['pay_type']=='credit') {
				$refundtype = ', 已经退回您的余额账户，请留意查收！';
			} else if ($order['pay_type'] == 'wechat') {
				$refundtype = ', 已经退回您的对应支付渠道（如银行卡，微信钱包等, 具体到账时间请您查看微信支付通知)，请留意查收！';
			}
			if($order_refund['refundtype']==2){
				$refundtype = ', 请联系客服进行退款事项！';
			}
			$applyprice = !empty($order_refund['applyprice'])?$order_refund['applyprice']:$order['price']-$order['creditmoney']+$order['freight'];
			if($order_refund['refundstatus']==0){
				//申请退款商家通知
				$tm = m('common')->getSysset('notice');
				$msgteam = array(
					'first' => array('value' => "您有一条申请退款的订单！", "color" => "#4a5077"),
					'keyword1' => array('title' => '企业名称', 'value' => $shop['name'], "color" => "#4a5077"),
					'keyword2' => array('title' => '订单编号', 'value' => "订单编号：".$order['orderno'].",维权编号：".$order_refund['refundno'], "color" => "#4a5077")
				);
				if(!empty($tm['openid'])){
					$openids = explode(",", $tm['openid']);
					foreach($openids as $value){
						$this->sendGroupsNotice(array(
							"openid" => $value,
							'tag' => 'groups_teamsend',
							'default' => $msgteam,
							'datas' => $datas
						));
					}
				}
			}elseif($order_refund['refundstatus']==-1) {
				//驳回退款通知
				$msg = array(
					'first' => array('value' => "您的退款订单已经被驳回", "color" => "#4a5077"),
					'keyword1' => array('title' => '订单编号', 'value' => $order['orderno'], "color" => "#4a5077"),
					'keyword2' => array('title' => '维权编号', 'value' => $order_refund['refundno'], "color" => "#4a5077"),
					'keyword3' => array('title' => '驳回原因', 'value' => $order_refund['reply'], "color" => "#4a5077")
				);

				$this->sendGroupsNotice(array(
					"openid" => $openid,
					'tag' => 'groups_refund',
					'default' => $msg,
					'datas' => $datas
				));
			}elseif($order_refund['refundstatus']==1){
				//订单完成退款通知
				$msg = array(
					'first' => array('value' => "您的订单已经完成退款！", "color" => "#4a5077"),
					'keyword1' => array('title' => '退款金额', 'value' => '¥' .$applyprice. '元', "color" => "#4a5077"),
					'keyword2' => array('title' => '商品详情', 'value' => $goods2, "color" => "#4a5077"),
					'keyword3' => array('title' => '订单编号', 'value' => $order['orderno'], "color" => "#4a5077"),
					'remark' => array('value' => "退款金额 ¥" . $applyprice . "{$refundtype}\r\n 期待您再次购物！", "color" => "#4a5077")
				);

				$this->sendGroupsNotice(array(
					"openid" => $openid,
					'tag' => 'groups_refund',
					'default' => $msg,
					'datas' => $datas
				));
			}
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
							'keyword1' => array('title' => '订单编号', 'value' => $value['orderno'], "color" => "#4a5077"),
							'keyword2' => array('title' => '通知时间', 'value' => date('Y-m-d H:i',time()), "color" => "#4a5077"),
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
						'keyword1' => array('title' => '企业名称', 'value' => $shop['name'], "color" => "#4a5077"),
						'keyword2' => array('title' => '摘要', 'value' => $goods, "color" => "#4a5077"),
						'remark' => array('value' => $remarkteam, "color" => "#4a5077")
					);
					if(!empty($tm['openid'])){
						$openids = explode(",", $tm['openid']);
						foreach($openids as $value){
							$this->sendGroupsNotice(array(
								"openid" => $value,
								'tag' => 'groups_teamsend',
								'default' => $msgteam,
								'datas' => $datas
							));
						}
					}

				}elseif($order['success'] == -1){
					//拼团失败通知
					$order = pdo_fetchall('select * from ' . tablename('ewei_shop_groups_order') . ' where teamid = :teamid and success = -1 and status = 1 ', array(':teamid' => $order['teamid']));
					$remark = "很抱歉，您所在的拼团未能成功组团，系统会在24小时之内自动退款。如有疑问请联系卖家，谢谢您的参与！";
					foreach($order as $key => $value){
						$msg = array(
							'first' => array('value' => "您参加的拼团组团失败！", "color" => "#4a5077"),
							'keyword1' => array('title' => '订单编号', 'value' => $value['orderno'], "color" => "#4a5077"),
							'keyword2' => array('title' => '通知时间', 'value' => date('Y-m-d H:i:s', time()), "color" => "#4a5077"),
							'remark' => array('value' => $remark, "color" => "#4a5077")
						);
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
						'keyword1' => array('title' => '订单编号', 'value' => $order['orderno'], "color" => "#4a5077"),
						'keyword2' => array('title' => '消费金额', 'value' => $orderpricestr, "color" => "#4a5077"),
						'keyword3' => array('title' => '消费门店', 'value' => $shop['name'], "color" => "#4a5077"),
						'keyword4' => array('title' => '消费时间', 'value' => date('Y-m-d H:i:s', $order['createtime']), "color" => "#4a5077"),
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
							'keyword1' => array('title' => '企业名称', 'value' => $shop['name'], "color" => "#4a5077"),
							'keyword2' => array('title' => '摘要', 'value' => $goods, "color" => "#4a5077"),
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
					'keyword1' => array('title' => '订单编号', 'value' =>$order['orderno'], "color" => "#4a5077"),
					'keyword2' => array('title' => '物流公司', 'value' => $order['expresscom'], "color" => "#4a5077"),
					'keyword3' => array('title' => '物流单号', 'value' => $order['expresssn'], "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);

				$this->sendGroupsNotice(array(
					"openid" => $openid,
					'tag' => 'groups_send',
					'default' => $msg,
					'datas' => $datas
				));
			}elseif($order['status'] == 3){
				//买家收货通知
				if (!empty($order['addressid'])) { //快递
					$remark = "您的订单已收货成功！";
				}
				$msg = array(
					'first' => array('value' => "订单已收货！", "color" => "#4a5077"),
					'keyword1' => array('title' => '订单编号', 'value' =>$order['orderno'], "color" => "#4a5077"),
					'keyword2' => array('title' => '物流公司', 'value' => $order['expresscom'], "color" => "#4a5077"),
					'keyword3' => array('title' => '物流单号', 'value' => $order['expresssn'], "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);

				$this->sendGroupsNotice(array(
					"openid" => $openid,
					'tag' => 'groups_send',
					'default' => $msg,
					'datas' => $datas
				));
			}elseif($order['status'] == -1){
				//订单取消通知
				if (!empty($order['addressid'])) { //快递
					$remark = "您的订单已取消！";
				}
				$msg = array(
					'first' => array('value' => "订单已取消！", "color" => "#4a5077"),
					'keyword1' => array('title' => '订单编号', 'value' => $order['orderno'], "color" => "#4a5077"),
					'keyword2' => array('title' => '通知时间', 'value' => date('Y-m-d H:i:s', time()), "color" => "#4a5077"),
					'remark' => array('value' => $remark, "color" => "#4a5077")
				);
				$this->sendGroupsNotice(array(
					"openid" => $openid,
					'tag' => 'groups_error',
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
	protected function replaceTemplate($str, $datas = array()) {
		foreach ($datas as $d) {
			$str = str_replace("[" . $d['name'] . "]", $d['value'], $str);
		}
		return $str;
	}
	/*
	 * 拼团核销
	 * */
	public function allow($orderid, $times = 0,$verifycode = '',$openid = '') {

		global $_W, $_GPC;
		if(empty($openid)){
			$openid = $_W['openid'];
		}

		$uniacid = $_W['uniacid'];
		$store = false; //当前门店
		$merchid = 0;


		$lastverifys = 0; //剩余核销次数
		$verifyinfo = false; //核销码信息
		if ($times <= 0) { //按次核销 需要核销的次数
			$times = 1;
		}
		//多商户
		/*$merch_plugin = p('merch');*/

		$saler = pdo_fetch('select * from ' . tablename('ewei_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1', array(
			':uniacid' => $_W['uniacid'], ':openid' => $openid
		));

		/*if (empty($saler) && $merch_plugin) {
			$saler = pdo_fetch('select * from ' . tablename('ewei_shop_merch_saler') . ' where openid=:openid and uniacid=:uniacid limit 1', array(
				':uniacid' => $_W['uniacid'], ':openid' => $openid
			));
		}*/

		if (empty($saler)) {
			return error(-1, '无核销权限!');
		} else {
			$merchid = $saler['merchid'];
		}

		$order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid  limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
		if (empty($order)) {
			return error(-1, "订单不存在!");
		}
		if (empty($order['isverify'])) {
			return error(-1, "订单无需核销!");
		}
		if(!empty($order['is_team'])){
			if($order['status'] <= 0 || $order['success'] <= 0){
				return error(-1, "此订单未满足核销条件!");
			}
		}
		if(empty($order['is_team']) && $order['status'] <= 0){
			return error(-1, "此订单未满足核销条件!");
		}

		$goods = pdo_fetch("select * from " . tablename('ewei_shop_groups_goods') . "
			where uniacid=:uniacid and id = :goodid ",
			array(':uniacid' => $uniacid, ':goodid' => $order['goodid']));
		if (empty($goods)) {
			return error(-1, '订单异常!');
		}

		if ($order['isverify']) {

			$storeids = array();
			if (!empty($goods['storeids'])) {
				$storeids = explode(',', $goods['storeids']);
			}

			if (!empty($storeids)) {
				//全部门店
				if (!empty($saler['storeid'])) {
					if (!in_array($saler['storeid'], $storeids)) {
						return error(-1, '您无此门店的核销权限!');
					}
				}
			}

			if ($order['verifytype'] == 0) {
				//按订单核销
				$verifynum = pdo_fetchcolumn("select COUNT(1) from ".tablename('ewei_shop_groups_verify')." where uniacid = :uniacid and orderid = :orderid ",
					array(':uniacid'=>$uniacid,':orderid'=>$orderid));
				if($verifynum >= $order['verifynum']){
					return error(-1, "此订单已完成核销！");
				}
			} else if ($order['verifytype'] == 1) {
				//按次核销
				$verifynum = pdo_fetchcolumn("select COUNT(1) from ".tablename('ewei_shop_groups_verify')." where uniacid = :uniacid and orderid = :orderid ",
					array(':uniacid'=>$uniacid,':orderid'=>$orderid));
				if($verifynum >= $order['verifynum']){
					return error(-1, "此订单已完成核销！");
				}
				$lastverifys = $order['verifynum'] - $verifynum;
				if($lastverifys < 0 && !empty($order['verifytype']) ){
					return error(-1, "此订单最多核销 ".$order['verifynum']." 次!");
				}
			}
			if (!empty($saler['storeid'])) {
				if ($merchid > 0) {
					$store = pdo_fetch('select * from ' . tablename('ewei_shop_merch_store') . ' where id=:id and uniacid=:uniacid and merchid = :merchid limit 1', array(':id' => $saler['storeid'], ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));

				} else {
					$store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $saler['storeid'], ':uniacid' => $_W['uniacid']));
				}
			}
		}
		$carrier = unserialize($order['carrier']);
		return array('order' => $order,
			'store' => $store,
			'saler' => $saler,
			'lastverifys' => $lastverifys,
			'goods' => $goods,
			'verifyinfo' => $verifyinfo,
			'carrier' => $carrier
		);
	}

	public function verify($orderid = 0, $times = 0,$verifycode = '',$openid = '') {


		global $_W, $_GPC;
		$uniacid = $_W['uniacid'];
		$current_time = time();
		if(empty($openid)){
			$openid =$_W['openid'];
		}
		$data = $this->allow($orderid, $times,$openid);
		if (is_error($data)) {
			return;
		}
		extract($data);
		$order = pdo_fetch('select * from ' . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid  limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));

		if ($order['isverify']) {
			if ($order['verifytype'] == 0) {
				pdo_update('ewei_shop_groups_order',array('status'=>3,'finishtime'=>time(),'sendtime' => $current_time),array('id' => $order['id']));
				$data = array(
					'uniacid'=>$uniacid,
					'openid'=>$order['openid'],
					'orderid'=>$orderid,
					'verifycode'=>$order['verifycode'],
					'storeid'=>$saler['storeid'],
					'verifier'=>$openid,
					'isverify'=>1,
					'verifytime'=>time()
				);
				pdo_insert('ewei_shop_groups_verify', $data);
			} else if ($order['verifytype'] == 1) {
				//按次核销
				if ($order['status'] != 3) {
					pdo_update('ewei_shop_groups_order',array('status'=>3,'finishtime'=>time(),'sendtime' => $current_time),array('id' => $order['id']));
					/*m('notice')->sendOrderMessage($orderid);*/
					/*if (p('commission')) {
						p('commission')->checkOrderFinish($orderid);
					}*/
				}
				$verifyinfo = iunserializer($order['verifyinfo']);
				//核销记录
				for ($i = 1; $i <= $times; $i++) {
					$data = array(
						'uniacid'=>$uniacid,
						'openid'=>$order['openid'],
						'orderid'=>$orderid,
						'verifycode'=>$order['verifycode'],
						'storeid'=>$saler['storeid'],
						'verifier'=>$openid,
						'isverify'=>1,
						'verifytime'=>time()
					);
					pdo_insert('ewei_shop_groups_verify', $data);
				}
			}
		}

		return true;
	}
	/*
	 * 拼团快递打印
	 * */
	public function tempData($type){
		global $_W, $_GPC;

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' uniacid = :uniacid and type=:type ';
		$params = array(':uniacid' => $_W['uniacid'], ':type'=>$type);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND expressname LIKE :expressname';
			$params[':expressname'] = '%' . trim($_GPC['keyword']) . '%';
		}

		$sql = 'SELECT id,expressname,expresscom,isdefault FROM ' . tablename('ewei_shop_exhelper_express') . " where  1 and {$condition} ORDER BY isdefault desc, id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_exhelper_express') . " where 1 and {$condition}", $params);
		$pager = pagination($total, $pindex, $psize);

		return array(
			'list' => $list,
			'total' => $total,
			'pager' => $pager,
			'type' => $type
		);
	}

	public function setDefault($id,$type){
		global $_W;
		$item = pdo_fetch("SELECT id,expressname,type FROM " . tablename('ewei_shop_exhelper_express') . " WHERE id=:id and type=:type AND uniacid=:uniacid" ,array(":id"=>$id, ':type'=>$type,":uniacid"=>$_W['uniacid']));
		if (!empty($item)) {
			pdo_update('ewei_shop_exhelper_express', array('isdefault'=>0), array('type'=>$type,'uniacid'=>$_W['uniacid']));
			pdo_update('ewei_shop_exhelper_express',array('isdefault'=>1),array('id'=>$id));
			if($type==1){
				plog('exhelper.temp.express.setdefault', "设置默认快递单 ID: {$item['id']}， 模板名称: {$item['expressname']} ");
			}
			elseif($type==2){
				plog('exhelper.temp.invoice.setdefault', "设置默认发货单 ID: {$item['id']}， 模板名称: {$item['expressname']} ");
			}
		}
	}

	public function tempDelete($id,$type){
		global $_W;
		$items = pdo_fetchall("SELECT id,expressname FROM " . tablename('ewei_shop_exhelper_express') . " WHERE id in( $id ) and type=:type and uniacid=:uniacid ", array(':type'=>$type, ':uniacid'=>$_W['uniacid']));
		foreach ($items as $item) {
			pdo_delete('ewei_shop_exhelper_express', array('id' => $item['id'], 'uniacid' => $_W['uniacid']));

			if($type==1){
				plog('groups.exhelper.expressdelete', "删除 快递助手 快递单模板 ID: {$item['id']}， 模板名称: {$item['expressname']} ");
			}
			elseif($type==2){
				plog('groups.exhelper.invoicedelete', "删除 快递助手 发货单模板 ID: {$item['id']}， 模板名称: {$item['expressname']} ");
			}

		}
	}
	public function getTemp(){
		global $_W, $_GPC;

		// 查询发件人模板
		$temp_sender = pdo_fetchall("SELECT id,isdefault,sendername,sendertel FROM " . tablename('ewei_shop_exhelper_senduser') . " WHERE uniacid=:uniacid order by isdefault desc ", array(':uniacid' => $_W['uniacid']));
		// 查询 快递单模板
		$temp_express = pdo_fetchall("SELECT id,type,isdefault,expressname FROM " . tablename('ewei_shop_exhelper_express') . " WHERE type=1 and uniacid=:uniacid order by isdefault desc ", array(':uniacid' => $_W['uniacid']));
		// 查询发货单模板
		$temp_invoice = pdo_fetchall("SELECT id,type,isdefault,expressname FROM " . tablename('ewei_shop_exhelper_express') . " WHERE type=2 and uniacid=:uniacid order by isdefault desc ", array(':uniacid' => $_W['uniacid']));

		return array(
			'temp_sender' => $temp_sender,
			'temp_express' => $temp_express,
			'temp_invoice' => $temp_invoice
		);
	}

	    //拼团运费的计算
    function group_dispatch_price($goods,$address){
    	if(empty($goods['dispatchid'])){
    		$dispatch_data = m('dispatch')->getDefaultDispatch(0,$goods['disgoods_id'],$goods['gid']);//wsq
    	}else{
    		$dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid'],$goods['disgoods_id']);//wsq
    	}

       
        //var_Dump($dispatch_data);
        $dispatch_array = array();
                if (!empty($dispatch_data)) {
                        //配送区域
                        $areas = unserialize($dispatch_data['areas']);

                        if ($dispatch_data['calculatetype'] == 1) {
                            //按件计费
                            $param = $goods['goodsnum'];
                        } else {
                            //按重量计费
                            $param = $goods['weight'] * $goods['goodsnum'];
                        }

                        $dkey = $dispatch_data['id'];
                        if (array_key_exists($dkey, $dispatch_array)) {
                            $dispatch_array[$dkey]['param'] += $param;
                        } else {
                            $dispatch_array[$dkey]['data'] = $dispatch_data;
                            $dispatch_array[$dkey]['param'] = $param;
                        }
                    }
        if (!empty($dispatch_array)) {
            foreach ($dispatch_array as $k => $v) {
                $dispatch_data = $dispatch_array[$k]['data'];
                $param = $dispatch_array[$k]['param'];
                $areas = unserialize($dispatch_data['areas']);
               // var_dump($address);
               // die();
                if (!empty($address)) {
                    //用户有默认地址
                    $dispatch_price += m('dispatch')->getCityDispatchPrice($areas, $address['city'], $param, $dispatch_data);

                } else if (!empty($member['city'])) {
                    //设置了城市需要判断区域设置
                    $dispatch_price = +m('dispatch')->getCityDispatchPrice($areas, $member['city'], $param, $dispatch_data);
                } else {
                    //如果会员还未设置城市 ，默认邮费
                    $dispatch_price = +m('dispatch')->getDispatchPrice($param, $dispatch_data);
                }
            }
        } 
        return $dispatch_price;
    }


    function group_tax($price,$dispatch_price,$goodsid){
    	require_once EWEI_SHOPV2_TAX_CORE. '/tax_core.php';
		global $_W;
    	$goods = pdo_fetch("select * from " . tablename('ewei_shop_groups_goods') . '
				where id = :id  and deleted = 0 order by displayorder desc',
				array(':id' => $goodsid));
    
    	if(empty($goods)){
    		return false;
    	}
  		if($goods['gid']==0){
  			return false;
  		}
    	$goods_info=pdo_fetch("SELECT * from ".tablename("ewei_shop_goods")." where id=:id",array("id"=>$goods['gid']));
    	$out_goods[]=array(
    		"price"=>$price/$goods["goodsnum"],
    		'total'=>$goods["goodsnum"],
    		'vat_rate'=>$goods_info['vat_rate'],
    		'consumption_tax'=>$goods_info['consumption_tax'],
    		);
    	$tax=new Taxcore();
    	//订单正常
    	$retrundata=$tax->get_dprice_order($out_goods,$dispatch_price,$price);
    	$out_goods=$retrundata['order_goods'];
    	$depostfee=$retrundata['depostfee'];//总运费
    	$out_goods=$tax->get_tax($out_goods);
    	//$tax->get_depostfee($out_goods,$dispatch_price);
		return array("depostfee"=>$depostfee,'order_goods'=>$out_goods);
    }

    function get_disprice($dispatch_price,$goodsid){
    	require_once EWEI_SHOPV2_TAX_CORE. '/tax_core.php';;
		global $_W;
    	$goods = pdo_fetch("select * from " . tablename('ewei_shop_groups_goods') . '
				where id = :id  and deleted = 0 order by displayorder desc',
				array(':id' => $goodsid));
    
    	if(empty($goods)){
    		return false;
    	}
  		if($goods['gid']==0){
  			return false;
  		}
    	$goods_info=pdo_fetch("SELECT * from ".tablename("ewei_shop_goods")." where id=:id",array("id"=>$goods['gid']));
    	$type=Dispage::get_disType($goods_info['disgoods_id'],$_W['uniacid']);
    	
    	if($type){
			$disprice=Dispage::get_disprice($goods_info['id'],$_W['uniacid']);
			$out_goods[]=array(
    			"price"=>$disprice,
    			'total'=>$goods["goodsnum"],
    			'vat_rate'=>$goods_info['vat_rate'],
    			'consumption_tax'=>$goods_info['consumption_tax'],
    		);
    		$dispriceamount=$disprice*$goods["goodsnum"];
	    	$tax=new Taxcore();
	    	//订单正常
	    	$retrundata=$tax->get_dprice_order($out_goods,$dispatch_price,$dispriceamount);
	    	$out_goods=$retrundata['order_goods'];
	    	$depostfee=$retrundata['depostfee'];//总运费
	    	$out_goods=$tax->get_tax($out_goods);
	    	//$tax->get_depostfee($out_goods,$dispatch_price);
			return array("depostfee"=>$depostfee,'order_goods'=>$out_goods,'disamount'=>$dispriceamount);
    	}
    	return 0;
    }
}

