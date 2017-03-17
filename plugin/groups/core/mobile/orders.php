<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Orders_EweiShopV2Page extends PluginMobileLoginPage {

	function main() {
		global $_W,$_GPC;
		$openid = $_W['openid'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);
		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}
		//分享
		$this->model->groupsShare();
		include $this->template();
	}
	/*订单详情*/
	function detail(){
		global $_W,$_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['orderid']);
		$teamid = intval($_GPC['teamid']);
		$condition = " and openid=:openid  and uniacid=:uniacid and id = :orderid and teamid = :teamid ";
		$order = pdo_fetch("select * from " . tablename('ewei_shop_groups_order') . "
				where openid=:openid  and uniacid=:uniacid and id = :orderid and teamid = :teamid order by createtime desc ",array(
			':uniacid' => $uniacid,
			':openid' => $openid,
			':orderid' => $orderid,
			':teamid' => $teamid
		));
		//商品信息
		$good = pdo_fetch("select * from " . tablename('ewei_shop_groups_goods') . '
					where id = :id and status = :status and uniacid = :uniacid and deleted = 0 order by displayorder desc', array(':id' => $order['goodid'],':uniacid' => $uniacid,':status' => 1));
		//是否支持核销
		if(!empty($order['isverify'])){
			//核销单 所有核销门店
			$storeids = array();
			$merchid = 0;
			if (!empty($good['storeids'])) {
				$merchid = $good['merchid'];
				$storeids = array_merge(explode(',', $good['storeids']), $storeids);
			}
			if (empty($storeids)) {
				//门店加入支持核销的判断
				if ($merchid > 0) {
					$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
				} else {
					$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
				}
			} else {
				if ($merchid > 0) {
					$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
				} else {
					$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
				}
			}
			//核销次数
			$verifytotal = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_verify') . " where orderid = :orderid and openid = :openid and uniacid = :uniacid and verifycode = :verifycode ",
				array(':orderid'=>$order['id'],':openid'=>$order['openid'],':uniacid'=>$order['uniacid'],':verifycode'=>$order['verifycode']));
			if($order['verifytype']==0){
				$verify = pdo_fetch("select isverify from ". tablename('ewei_shop_groups_verify') ." where orderid = :orderid and openid = :openid and uniacid = :uniacid and verifycode = :verifycode ",
					array(':orderid'=>$order['id'],':openid'=>$order['openid'],':uniacid'=>$order['uniacid'],':verifycode'=>$order['verifycode']));
			}
			$verifynum = $order["verifynum"] - $verifytotal;
			if($verifynum<0){
				$verifynum = 0;
			}
		}else{
			//收货地址
			$address = false;
			if (!empty($order['addressid'])) {
				$address = iunserializer($order['address']);
				if (!is_array($address)) {
					$address = pdo_fetch('select * from  ' . tablename('ewei_shop_member_address') . ' where id=:id limit 1', array(':id' => $order['addressid']));
				}
			}

		}

		//联系人
		$carrier = @iunserializer($order['carrier']);
		if (!is_array($carrier) || empty($carrier)) {
			$carrier = false;
		}
		//分享
		$this->model->groupsShare();
		include $this->template();
	}
	/*
	 * 查看物流
	 * */
	function express() {
		global $_W, $_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['id']);

		if (empty($orderid)) {
			header('location: ' . mobileUrl('groups/orders'));
			exit;
		}
		$order = pdo_fetch("select * from " . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
			, array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
		if (empty($order)) {
			header('location: ' . mobileUrl('groups/order'));
			exit;
		}
		if (empty($order['addressid'])) {
			$this->message('订单非快递单，无法查看物流信息!');
		}
		if ($order['status'] < 2) {
			$this->message('订单未发货，无法查看物流信息!');
		}
		//商品信息
		$goods = pdo_fetch("select *  from " . tablename('ewei_shop_groups_goods') . "  where id=:id and uniacid=:uniacid ", array(':uniacid' => $uniacid, ':id' => $order['goodid']));
		$expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);

		include $this->template();
	}
	/**
	 * 取消订单
	 * @global type $_W
	 * @global type $_GPC
	 */
	function cancel() {
		global $_W, $_GPC;
		try{
			$orderid = intval($_GPC['id']);
			$order = pdo_fetch("select id,orderno,openid,status,credit,teamid,groupnum,creditmoney,price,freight,pay_type,discount,success from " . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
				, array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
			$total = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_order') . "  where teamid = :teamid  "
				,array(':teamid'=>$order['teamid']));
			if (empty($order)) {
				show_json(0, '订单未找到');
			}
			if ($order['status'] != 0) {
				show_json(0, '订单不能取消');
			}
			pdo_update('ewei_shop_groups_order', array('status' => -1, 'canceltime' => time()), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
			//模板消息
			p('groups')->sendTeamMessage($orderid);
			show_json(1);
		}catch(Exception $e){
			throw new $e->getMessage();
		}
	}
	/**
	 * 删除订单
	 * @global type $_W
	 * @global type $_GPC
	 */
	function delete() {
		global $_W, $_GPC;

		//删除订单
		$orderid = intval($_GPC['id']);
		$order = pdo_fetch("select id,status from " . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
			, array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
		if (empty($order)) {
			show_json(0, '订单未找到!');
		}
		if ($order['status'] != 3 && $order['status'] != -1) {
			show_json(0, '无法删除');
		}

		pdo_update('ewei_shop_groups_order', array('deleted' => 1), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
		show_json(1);
	}
	/*
	 * 订单列表
	 * */
	function get_list(){
		global $_W, $_GPC;
		$list = array();
		$openid = $_W['openid'];
		load()->model('mc');
		$uid = mc_openid2uid($openid);
		if (empty($uid)) {
			mc_oauth_userinfo($openid);
		}
		$uniacid =$_W['uniacid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 5;
		$status = $_GPC['status'];
		if($status == 0){
			$tab_all = true;
			$condition = " and o.openid=:openid  and o.uniacid=:uniacid and o.deleted = :deleted ";
			$params = array(
				':uniacid' => $uniacid,
				':openid' => $openid,
				':deleted' => 0
			);
		}else{
			$condition = " and o.openid=:openid  and o.uniacid=:uniacid and o.status = :status and o.deleted = :deleted  ";
			$params = array(
				':uniacid' => $uniacid,
				':openid' => $openid,
				':deleted' => 0
			);
			if($status == 1){
				$tab0 = true;
				$params[':status'] = 0;
			}elseif($status == 2){
				$tab1 = true;
				$condition = " and o.openid=:openid  and o.uniacid=:uniacid and o.deleted = :deleted and o.status = :status and (o.is_team = 0 or o.success = 1) ";
				$params[':status'] = 1;
			}elseif($status == 3){
				$tab2 = true;
				$params[':status'] = 2;
			}elseif($status == 4){
				$tab3 = true;
				$params[':status'] = 3;
			}
		}
		$orders = pdo_fetchall("select o.id,o.orderno,o.createtime,o.price,o.freight,o.creditmoney,o.goodid,o.teamid,o.status,o.is_team,o.success,o.teamid,o.openid,
				g.title,g.thumb,g.units,g.goodsnum,g.groupsprice,g.singleprice,o.verifynum,o.verifytype,o.isverify,o.uniacid,o.verifycode
				from " . tablename('ewei_shop_groups_order') . " as o
				left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
				where 1 {$condition} order by o.createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_order') . " as o where 1 {$condition}", $params);
		foreach ($orders as $key => $value) {
			$verifytotal = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_verify') . " where orderid = :orderid and openid = :openid and uniacid = :uniacid and verifycode = :verifycode ",
				array(':orderid'=>$value['id'],':openid'=>$value['openid'],':uniacid'=>$value['uniacid'],':verifycode'=>$value['verifycode']));
			if(!$verifytotal){
				$verifytotal = 0;
			}
			$orders[$key]['vnum'] = $value["verifynum"] - intval($verifytotal);
			$orders[$key]['amount'] = $value['price'] + $value['freight'] - $value['creditmoney'];
			$statuscss = "text-cancel";
			switch ($value['status']) {
				case "-1":
					$status = "已取消";
					break;
				case "0":
					$status = "待付款";
					$statuscss = "text-cancel";
					break;
				case "1":
					if($value['is_team']==0 || $value['success']==1){
						$status = "待发货";
						$statuscss = "text-warning";
					}else{
						$status = "已付款";
						$statuscss = "text-success";
					}
					break;
				case "2":
					$status = "待收货";
					$statuscss = "text-danger";
					break;
				case "3":
					$status = "已完成";
					$statuscss = "text-success";
					break;
			}
			$orders[$key]['statusstr'] = $status;
			$orders[$key]['statuscss'] = $statuscss;
		}
		$orders = set_medias($orders, 'thumb');
		show_json(1,array('list'=>$orders,'pagesize'=>$psize,'total'=>$total));
	}
	/*
	 * 确认订单
	 * */
	function confirm(){
		global $_W, $_GPC;
		try{
			$openid = $_W['openid'];
			$uniacid = $_W['uniacid'];
			load()->model('mc');
			$uid = mc_openid2uid($openid);
			if (empty($uid)) {
				mc_oauth_userinfo($openid);
			}
			//是否为核销单
			$isverify = false;
			$goodid = intval($_GPC['id']);
			$type = $_GPC['type'];
			$heads = intval($_GPC['heads']);
			$teamid = intval($_GPC['teamid']);
			//会员
			$member = m('member')->getMember($openid, true);
			$credit = array(); //积分抵扣
			//商品详情
			$goods = pdo_fetch("select * from " . tablename('ewei_shop_groups_goods') . '
				where id = :id and uniacid = :uniacid and deleted = 0 order by displayorder desc',
				array(':id' => $goodid,':uniacid' => $uniacid));
			if($goods['stock']<=0){
				throw new Exception('您选择的商品已经下架，请浏览其他商品或联系商家！');
			}

			//购买是否关注公众号
			$follow = m("user")->followed($openid);
			if(!empty($goods['followneed']) && !$follow && is_weixin()){
				$followtext = empty($goods['followtext']) ? "如果您想要购买此商品，需要您关注我们的公众号，点击【确定】关注后再来购买吧~" : $goods['followtext'];
				$followurl = empty($goods['followurl']) ? $_W['shopset']['share']['followurl'] : $goods['followurl'];
				$this->message($followtext,$followurl,'error');
			}

			$ordernum = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_order') . " as o
			where openid = :openid and status >= :status and goodid = :goodid and uniacid = :uniacid ",
				array(':openid'=>$openid,':status'=>0,':goodid'=>$goodid,':uniacid'=>$uniacid));
			if(!empty($goods['purchaselimit']) && $goods['purchaselimit']<=$ordernum){
				throw new Exception('您已到达此商品购买上限，请浏览其他商品或联系商家！');
			}
			$order = pdo_fetch("select * from " . tablename('ewei_shop_groups_order') . '
					where goodid = :goodid and status >= 0 and is_team = 1 and openid = :openid and uniacid = :uniacid and success = 0 and deleted = 0 ',
				array(':goodid' => $goodid,':openid'=>$openid,':uniacid' => $uniacid));
			if($order && $order['status']== 0){
				throw new Exception('您的订单已存在，请尽快完成支付！');
			}
			if($order && $order['status']== 1){
				throw new Exception('您已经参与了该团，请等待拼团结束后再进行购买！');
			}
			if($order && $ordernum >= $order['groupnum']){
				throw new Exception('该团人数已达上限，请浏览其他商品或联系商家！');
			}
			if(!empty($teamid)){
				$orders = pdo_fetchall("select * from " . tablename('ewei_shop_groups_order') . '
					where teamid = :teamid and uniacid = :uniacid ',
					array(':teamid'=>$teamid,':uniacid' => $uniacid));
				foreach($orders as $key => $value){
					if($orders && $value['success']== -1){
						throw new Exception('该活动已过期，请浏览其他商品或联系商家！');
					}
					if($orders && $value['success']==1){
						throw new Exception('该活动已结束，请浏览其他商品或联系商家！');
					}
				}

				$num = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_groups_order') . " as o where teamid = :teamid and status > :status and goodid = :goodid and uniacid = :uniacid ",
					array(':teamid'=>$teamid,':status'=>0,':goodid'=>$goods['id'],':uniacid'=>$uniacid));
				if($num==$goods['groupnum']){
					throw new Exception('该活动已成功组团，请浏览其他商品或联系商家！');
				}
			}


			if($type=='groups'){
				$goodsprice = $goods['groupsprice'];
				$price = $goods['groupsprice'];
				$groupnum = intval($goods['groupnum']);//团购人数
				$is_team = 1;
			}elseif($type=='single'){
				$goodsprice = $goods['singleprice'];
				$price = $goods['singleprice'];
				$groupnum = 1;
				$is_team = 0;
				$teamid = 0;
			}
			//团长优惠设置
			$set = pdo_fetch("select discount,headstype,headsmoney,headsdiscount from ".tablename('ewei_shop_groups_set')."
					where uniacid = :uniacid ", array(':uniacid' => $uniacid));
			if(!empty($set['discount']) && $heads == 1){
				if(!empty($goods['discount'])){//商品单独设置团长优惠
					if(empty($goods['headstype'])){//优惠金额

					}else{//优惠折扣
						$goods['headsmoney'] = $goods['groupsprice'] - number_format($goods['groupsprice']*$goods['headsdiscount']/100,2);
					}
				}else{//统一团长优惠
					if(empty($set['headstype'])){//优惠金额
						$goods['headsmoney'] = $set['headsmoney'];
					}else{//优惠折扣
						$goods['headsmoney'] = $goods['groupsprice'] - number_format($goods['groupsprice']*$set['headsdiscount']/100,2);
					}
					$goods['headstype'] = $set['headstype'];
					$goods['headsdiscount'] = $set['headsdiscount'];
				}
				if($goods['headsmoney']>$goods['groupsprice']){
					$goods['headsmoney'] = $goods['groupsprice'];
				}
				$price = $price - $goods['headsmoney'];
				if($price<0){
					$price = 0;
				}
			}else{
				$goods['headsmoney'] = 0;
			}
			//是否支持核销
			if(!empty($goods['isverify'])){
				$isverify = true;
				$goods['freight'] = 0;
				//核销单 所有核销门店
				$storeids = array();
				$merchid = 0;
				if (!empty($goods['storeids'])) {
					$merchid = $goods['merchid'];
					$storeids = array_merge(explode(',', $goods['storeids']), $storeids);
				}
				if (empty($storeids)) {
					//门店加入支持核销的判断
					if ($merchid > 0) {
						$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
					} else {
						$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
					}
				} else {
					if ($merchid > 0) {
						$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
					} else {
						$stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
					}
				}
				$verifycode = "PT".random(8, true);
				while (1) {
					$count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_groups_order') . ' where verifycode=:verifycode and uniacid=:uniacid limit 1', array(':verifycode' => $verifycode, ':uniacid' => $_W['uniacid']));
					if ($count <= 0) {
						break;
					}
					$verifycode = "PT".random(8, true);
				}
				$verifynum = !empty($goods['verifytype'])?$verifynum = $goods['verifynum']:1;
			}else{
				if(empty($_GPC['aid'])){
					//默认地址
				$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . '
				where openid=:openid and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1'
					, array(':uniacid' => $uniacid, ':openid' => $openid));
				}else{
				$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $uniacid, ':openid' => $openid, ':id' => $_GPC['aid']));
				}

			}
			/*积分抵扣*/
			$creditdeduct = pdo_fetch("SELECT creditdeduct,groupsdeduct,credit,groupsmoney FROM" . tablename('ewei_shop_groups_set') .  "WHERE uniacid = :uniacid ",array(':uniacid'=>$uniacid));
			if(intval($creditdeduct['creditdeduct'])){//是否开启积分抵扣
				/*判断是否使用拼团积分抵扣比例*/
				if(intval($creditdeduct['groupsdeduct'])){
					//商品最多抵扣金额,
					if($goods['deduct']>0){
						$credit['deductprice'] = round((intval($member['credit1']) * $creditdeduct['groupsmoney']), 2);
						if($credit['deductprice'] >= $price){//抵扣金额、团购金额
							$credit['deductprice'] = $price;
						}
						if($credit['deductprice'] >= $goods['deduct']){//抵扣金额、商品最多抵扣金额
							$credit['deductprice'] = $goods['deduct'];
						}
						$credit['credit'] = floor($credit['deductprice'] / $creditdeduct['groupsmoney']);
						if($credit['credit']<1){
							$credit['credit'] = 0;
							$credit['deductprice'] = 0;
						}
						$credit['deductprice'] = $credit['credit'] * $creditdeduct['groupsmoney'];
					}else{
						$credit['deductprice'] = 0;
					}
				}else{
					/*人人商城积分抵扣比例*/
					$sys_data = m('common')->getPluginset('sale');
					//商品最多抵扣金额,
					if($goods['deduct']>0){
						$credit['deductprice'] = round((intval($member['credit1']) * $sys_data['money']), 2);
						if($credit['deductprice'] >= $price){
							$credit['deductprice'] = $price;
						}
						if($credit['deductprice'] >= $goods['deduct']){
							$credit['deductprice'] = $goods['deduct'];
						}
						$credit['credit'] = floor($credit['deductprice'] / $sys_data['money']);
						if($credit['credit']<1){
							$credit['credit'] = 0;
							$credit['deductprice'] = 0;
						}
						$credit['deductprice'] = $credit['credit'] * $sys_data['money'];
					}else{
						$credit['deductprice'] = 0;
					}
				}
			}
			$sql="SELECT * FROM ".tablename("ewei_shop_goods")." WHERE id=:gid";
			$goods_info=pdo_fetch($sql,array(":gid"=>$goods['gid']));
			//var_dump($goods_info);
			$goods['weight']=$goods_info['weight'];
            $depotid=$goods['depotid']=$goods_info['depotid'];
            $goods['disgoods_id']=$goods_info['disgoods_id'];

            $dispatch_price=p('groups')->group_dispatch_price($goods,$address);
          	//var_dump($dispatch_price);
			//生成订单号
			$ordersn = m('common')->createNO('groups_order', 'orderno', 'PT');
			if ($_W['ispost']) {
				if(empty($_GPC['aid']) && !$isverify){
					header('location: '.mobileUrl('groups/address/post'));
					exit;
				}
				if($isverify){
					if(empty($_GPC['realname']) || empty($_GPC['mobile'])){
						throw new Exception('联系人或联系电话不能为空！');
					}
				}
				$consumption_tax=0;
				$rate=0;
				$consolidated=0;
				$dff_fee=0;
				$disamount=0;
				$depostfee=0;
				$dprice=0;
				$disprice=0;
				$ordergoods=p('groups')->group_tax($price,$dispatch_price,$goodid);//正常订单

				$depostfee=$ordergoods['depostfee'];
				$disordergoods=p('groups')->get_disprice($dispatch_price,$goodid);//代理订单
				foreach($ordergoods['order_goods'] as $goods){
					$consumption_tax=$goods['tax']['consumption_tax']*$goods['total'];
					$rate=$goods['tax']['rate']*$goods['total'];
					$consolidated=$goods['tax']['consolidated']*$goods['total'];
					$dprice=$goods['dprice'];
				}
				if(!empty($disordergoods)){
					foreach($disordergoods['order_goods'] as $goods){
						$disconsolidated=$goods['tax']['consolidated']*$goods['total'];
						$disprice=$goods['price'];
					}
					$disamount=$disordergoods['disamount'];
					$dff_fee=$consolidated-$disconsolidated;//差额计算
					
					if($dff_fee>0){
						$disamount=$disamount+$dispatch_price+$dff_fee;
					}else{
						$disamount=$disamount+$dispatch_price;
					}
				}
				$data = array(
					'uniacid' => $_W['uniacid'],
					'groupnum' => $groupnum,
					'openid' => $openid,
					'paytime' => '',//支付成功时间
					'orderno' => $ordersn,
					'credit' => intval($_GPC['isdeduct']) ? $_GPC['credit'] : 0 ,
					'creditmoney' => intval($_GPC['isdeduct']) ? $_GPC['creditmoney'] : 0  ,
					'price' => $price ,
					'freight' => $dispatch_price,
					'status' => 0,//订单状态，-1取消状态，0普通状态，1为已付款，2为已发货，3为成功
					'goodid' => $goodid,
					'teamid'=>$teamid,
					'is_team' => $is_team,
					'heads' => $heads,
					'discount' => !empty($heads)?$goods['headsmoney']:0,
					'addressid' => intval($_GPC['aid']),
					'message' => trim($_GPC['message']),
					'realname' => $isverify?trim($_GPC['realname']):'',
					'mobile' => $isverify?trim($_GPC['mobile']):'',
					'endtime' => $goods['endtime'],
					'isverify' => intval($goods['isverify']),
					'verifytype' => intval($goods['verifytype']),
					'verifycode' => !empty($verifycode)?$verifycode:0,
					'verifynum' => !empty($verifynum)?$verifynum:1,
					'createtime' => TIMESTAMP,
					'disgoods_id'=>$goods_info['disgoods_id'],
					'consumption_tax'=>$consumption_tax,
					'vat_rate'=>$rate,
					'dff_fee'=>$dff_fee,
					'disamount'=>$disamount,
					'depostfee'=>$depostfee,
					'dprice'=>$dprice,
					'disprice'=>$disprice,
					'depotid'=>$depotid,
					'isdisorder'=>empty($disordergoods) ? 0:1,
				);
				$order_insert = pdo_insert('ewei_shop_groups_order', $data);
				if(!$order_insert){
					throw new Exception('生成订单失败！');
				}
				$orderid = pdo_insertid();
				if(empty($teamid) && $type=='groups'){
					pdo_update('ewei_shop_groups_order',array('teamid' => $orderid), array('id' => $orderid));
				}
				$order = pdo_fetch("select * from " . tablename('ewei_shop_groups_order') . '
						where id = :id and uniacid = :uniacid ',array(':id' => $orderid,':uniacid' => $uniacid));
				header("location: " .  MobileUrl('groups/pay', array('teamid' => empty($teamid) ? $order["teamid"] : $teamid,'orderid'=>$orderid)));
			}
			//分享
			$this->model->groupsShare();
			include $this->template();
		}catch(Exception $e){
			$content = $e->getMessage();
			include $this->template('groups/error');
		}
	}

	function dispatch(){
		global $_W, $_GPC;
		$aid=$_GPC['aid'];
		$openid = $_W['openid'];
		$goodid=$_GPC['goodsid'];
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_groups_goods') . '
				where id = :id and uniacid = :uniacid and deleted = 0 order by displayorder desc',
				array(':id' => $goodid,':uniacid' => $_W['uniacid']));
		$goods_info=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_goods")." WHERE id=:gid",array(":gid"=>$goods['gid']));
			$goods['weight']=$goods_info['weight'];
            $goods['depotid']=$goods_info['depotid'];
            $goods['disgoods_id']=$goods_info['disgoods_id'];
        $address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $_W['uniacid'], ':openid' => $openid, ':id' => $aid));
        $dispatch_price=p('groups')->group_dispatch_price($goods,$address);
        echo json_encode($dispatch_price);
        exit;
	}
	/**
	 * 确认收货
	 * @global type $_W
	 * @global type $_GPC
	 */
	function finish() {

		global $_W, $_GPC;
		$orderid = intval($_GPC['id']);
		$order = pdo_fetch("select * from " . tablename('ewei_shop_groups_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
			, array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
		if (empty($order)) {
			show_json(0, '订单未找到');
		}
		if ($order['status'] != 2) {
			show_json(0, '订单不能确认收货');
		}
		if ($order['refundstate'] > 0 && !empty($order['refundid'])) {

			$change_refund = array();
			$change_refund['refundstatus'] = -2;
			$change_refund['refundtime'] = time();
			pdo_update('ewei_shop_groups_order_refund', $change_refund, array('id' => $order['refundid'], 'uniacid' => $_W['uniacid']));
		}

		pdo_update('ewei_shop_groups_order', array('status' => 3, 'finishtime' => time(), 'refundstate' => 0), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));

		//模板消息
		p('groups')->sendTeamMessage($orderid);

		show_json(1);
	}
}
