<?php
/*

 * 人人商城V2

 * 

 * @author ewei 狸小狐 QQ:22185157 

 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Verify_EweiShopV2Page extends PluginWebPage {
	function main() {
		global $_W, $_GPC;
		$verify = $_GPC['verify'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = " and o.uniacid=:uniacid and o.isverify = 1 ";
		$params = array(':uniacid' => $_W['uniacid']);

		if($verify == 'normal'){//未核销
			$condition .= " and o.status = 1 ";
		}elseif($verify == 'over'){//已核销
			$condition .= " and o.status = 3 ";
		}elseif($verify == 'cancel'){//已取消
			$condition .= " and o.status <= 0 ";
		}
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		$searchtime = trim($_GPC['searchtime']);

		if(!empty($searchtime)){
			$condition.=' and o.'.$searchtime.'time > '.strtotime($_GPC['time']['start']).' and o.'.$searchtime.'time < '.strtotime($_GPC['time']['end']).' ';
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
		}
		if(!empty($_GPC['paytype'])){
			$_GPC['paytype'] = trim($_GPC['paytype']);
			$condition.=' and o.pay_type = :paytype';
			$params[':paytype'] = $_GPC['paytype'];
		}
		//
		if (!empty($_GPC['searchfield']) && !empty($_GPC['keyword'])) {
			$searchfield = trim(strtolower($_GPC['searchfield']));
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$params[':keyword'] = $_GPC['keyword'];

			$sqlcondition = '';
			if ($searchfield == 'orderno') {
				$condition .= " AND locate(:keyword,o.orderno)>0 ";
			} else if ($searchfield == 'member') {
				$condition .= " AND (locate(:keyword,m.realname)>0 or locate(:keyword,m.mobile)>0 or locate(:keyword,m.nickname)>0)";
			} else if ($searchfield == 'goodstitle') {
				$condition .= " and locate(:keyword,g.title)>0 ";
			} else if ($searchfield == 'goodssn') {
				$condition .=  " and locate(:keyword,g.goodssn)>0 ";
			} else if ($searchfield == 'saler') {
				$condition .= " AND (locate(:keyword,sm.realname)>0 or locate(:keyword,sm.mobile)>0 or locate(:keyword,sm.nickname)>0 or locate(:keyword,s.salername)>0 )";
			} else if ($searchfield == 'store') {
				$condition .= " AND (locate(:keyword,store.storename)>0)";
			}
		}
		if (empty($_GPC['export'])) {
			$page = "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		}
		$list = pdo_fetchall("SELECT o.*,v.verifier,v.storeid as vstoreid,g.title,g.category,g.thumb,g.groupsprice,g.singleprice,g.price as gprice,g.goodssn,c.name,m.nickname,m.id as mid,m.realname as mrealname,m.mobile as mmobile,
				a.realname as arealname,a.mobile as amobile,a.province as aprovince ,a.city as acity,a.area as aarea,a.address as aaddress,sm.id as salerid,sm.nickname as salernickname,s.salername
				FROM " . tablename('ewei_shop_groups_order') . " as o
				left join " . tablename('ewei_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid
				left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
				left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
				left join " . tablename('ewei_shop_member_address') . " a on a.id=o.addressid
				right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
				left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid
				left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid
				left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid=o.uniacid
				WHERE 1 {$condition} group by o.id  ORDER BY o.createtime DESC " .$page, $params);
		foreach($list as $key => $value){
			//收货地址
			$list[$key]['addressdata'] = array(
				'realname' => $value['realname'],
				'mobile' => $value['mobile'],
			);
		}
		$num = pdo_fetchall("SELECT o.* FROM " . tablename('ewei_shop_groups_order') . " as o
				left join " . tablename('ewei_shop_groups_verify') . " as v on v.orderid = o.id and v.uniacid=o.uniacid
				left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
				left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid and m.uniacid =  o.uniacid
				left join " . tablename('ewei_shop_member_address') . " a on a.id=o.addressid
				right join ".tablename('ewei_shop_groups_category')." as c on c.id = g.category
				left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid
				left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid
				left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid=o.uniacid
				WHERE 1 {$condition} group by o.id  ORDER BY o.createtime DESC ", $params);
		$total = count($num);
		$pager = pagination($total, $pindex, $psize);
		$paytype = array(
			'credit'=>'余额支付',
			'wechat'=>'微信支付',
			'other'=>'其他支付',
		);
		$paystatus = array(
			'0' => '未付款',
			'1' => '已付款',
			'2' => '待收货',
			'3' => '已完成',
			'-1' => '已取消',
			'4' => '待发货',
		);
		//导出Excel

		if ($_GPC['export'] == 1) {
			plog('groups.order.export', "导出订单");

			$columns = array(
				array('title' => '订单编号', 'field' => 'orderno', 'width' => 24),
				array('title' => '粉丝昵称', 'field' => 'nickname', 'width' => 12),
				array('title' => '会员姓名', 'field' => 'mrealname', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 30),
				array('title' => '会员手机手机号', 'field' => 'mmobile', 'width' => 15),
				array('title' => '收货姓名(或自提人)', 'field' => 'arealname', 'width' => 15),
				array('title' => '联系电话', 'field' => 'amobile', 'width' => 12),
				array('title' => '商品名称', 'field' => 'title', 'width' => 30),
				array('title' => '商品编码', 'field' => 'goodssn', 'width' => 15),
				array('title' => '团购价', 'field' => 'groupsprice', 'width' => 12),
				array('title' => '单购价', 'field' => 'singleprice', 'width' => 12),
				array('title' => '原价', 'field' => 'price', 'width' => 12),
				array('title' => '商品数量', 'field' => 'goods_total', 'width' => 15),
				array('title' => '商品小计', 'field' => 'goodsprice', 'width' => 12),
				array('title' => '积分抵扣', 'field' => 'credit', 'width' => 12),
				array('title' => '积分抵扣金额', 'field' => 'creditmoney', 'width' => 12),
				array('title' => '运费', 'field' => 'freight', 'width' => 12),
				array('title' => '应收款', 'field' => 'amount', 'width' => 12),
				array('title' => '支付方式', 'field' => 'pay_type', 'width' => 12),
				//array('title' => '配送方式', 'field' => 'dispatchname', 'width' => 12),
				array('title' => '状态', 'field' => 'status', 'width' => 12),
				array('title' => '下单时间', 'field' => 'createtime', 'width' => 24),
				array('title' => '付款时间', 'field' => 'paytime', 'width' => 24),
				array('title' => '完成时间', 'field' => 'finishtime', 'width' => 24),
				array('title' => '核销员', 'field' => 'salerinfo', 'width' => 24),
				array('title' => '核销门店', 'field' => 'storeinfo', 'width' => 36),
				array('title' => '订单备注', 'field' => 'remark', 'width' => 36),
			);
			$exportlist = array();
			foreach ($list as $key => $value) {
				$r['salerinfo'] = "";
				$r['storeinfo'] = "";
				$verify = pdo_fetchall("select sm.id as salerid,sm.nickname as salernickname,s.salername,store.storename from ".tablename('ewei_shop_groups_verify')." as v
					left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid=v.uniacid
					left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid=s.uniacid
					left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid=v.uniacid
					where v.orderid = :orderid and v.uniacid = :uniacid ",
					array(':orderid'=>$value['id'],':uniacid'=>$_W['uniacid']));
				$vcount = count($verify)-1;
				foreach($verify as $k => $val){
					$r['salerinfo'] .= "[" . $val['salerid'] . "]" . $val['salername'] . "(" . $val['salernickname'] . ")";
					$r['storeinfo'] .= $val['storename'];
					if($k != $vcount){
						$r['salerinfo'] .= "\r\n";
						$r['storeinfo'] .= "\r\n";
					}else{
						$r['salerinfo'] .= "";
						$r['storeinfo'] .= "";
					}
				}

				$r['orderno'] = $value['orderno'];
				$r['nickname'] = str_replace('=', "", $value['nickname']);
				$r['mrealname'] = $value['mrealname'];
				$r['openid'] = $value['openid'];
				$r['mmobile'] = $value['mmobile'];
				$r['arealname'] = $value['realname'];
				$r['amobile'] = $value['mobile'];
				$r['pay_type'] = $paytype[''.$value['pay_type'].''];
				//$r['dispatchname'] = $value['mrealname'];
				$r['freight'] = $value['freight'];
				$r['groupsprice'] = $value['groupsprice'];
				$r['singleprice'] = $value['singleprice'];
				$r['price'] = $value['price'];
				$r['credit'] = !empty($value['credit'])?"-" . $value['credit']:0;
				$r['creditmoney'] = !empty($value['creditmoney'])?"-" . $value['creditmoney']:0;
				$r['goodsprice'] = $value['groupsprice'] * 1;
				$r['status'] = $value['status']==1 && $value['status']==1 ? $paystatus['4'] : $paystatus[''.$value['status'].''] ;
				$r['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
				$r['paytime'] = !empty($value['paytime']) ? date('Y-m-d H:i:s', $value['paytime']) : '';
				$r['finishtime'] = !empty($value['finishtime']) ? date('Y-m-d H:i:s', $value['finishtime']) : '';
				$r['expresscom'] = $value['expresscom'];
				$r['expresssn'] = $value['expresssn'];
				$r['amount'] = $value['groupsprice'] * 1 - $value['creditmoney'] + $value['freight'] ;
				$r['remark'] = $value['remark'];
				$r['title'] = $value['title'];
				$r['goodssn'] = $value['goodssn'];
				$r['goods_total'] = 1;
				$exportlist[] = $r;
			}
			unset($r);
			m('excel')->export($exportlist, array(
				"title" => "核销订单-" . date('Y-m-d-H-i', time()),
				"columns" => $columns
			));
		}
		include $this->template();
	}
	/*
	 * 确认核销
	 * */
	function fetch() {
		global $_W, $_GPC;
		$opdata = $this->opData();
		extract($opdata);

		if ($item['status'] != 1) {
			message('订单未付款，无法确认取货！');
		}
		$time = time();
		$d = array(
			'status' => 3,
			'sendtime' => $time,
			'finishtime' => $time
		);

		if ($item['isverify'] == 1) {
			$d['verified'] = 1;
			$d['verifytime'] = $time;
			$d['verifyopenid'] = "";
		}
		pdo_update(
			'ewei_shop_order', $d, array('id' => $item['id'], 'uniacid' => $_W['uniacid'])
		);

		//取消退款状态
		if (!empty($item['refundid'])) {
			$refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $item['refundid']));
			if (!empty($refund)) {
				pdo_update('ewei_shop_order_refund', array('status' => -1), array('id' => $item['refundid']));
				pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id']));
			}
		}
		//模板消息
		/*p('groups')->sendTeamMessage($item['id']);*/

		plog('groups.verify.fetch', "订单确认取货 ID: {$item['id']} 订单号: {$item['orderno']}");
		show_json(1);
	}
	/*
	 * 订单详情
	 * */
	function detail(){
		global $_W, $_GPC;
		$status = $_GPC['status'];
		$orderid = intval($_GPC['orderid']);
		$params = array(':orderid' => $orderid);
		$order = pdo_fetch("SELECT o.*,g.title,g.category,g.groupsprice,g.singleprice,g.thumb,g.id as gid FROM " . tablename('ewei_shop_groups_order') . " as o
				left join ".tablename('ewei_shop_groups_goods')." as g on g.id = o.goodid
				WHERE o.id = :orderid ", $params);
		$order = set_medias($order, 'thumb');
		$member = m('member')->getMember($order['openid'], true);
		$verifyinfo = pdo_fetchall("select v.*,sm.id as salerid,sm.nickname as salernickname,s.salername,store.storename from ".tablename('ewei_shop_groups_verify')." as v
					left join " . tablename('ewei_shop_saler') . " s on s.openid = v.verifier and s.uniacid = v.uniacid
					left join " . tablename('ewei_shop_member') . " sm on sm.openid = s.openid and sm.uniacid = s.uniacid
					left join " . tablename('ewei_shop_store') . " store on store.id = v.storeid and store.uniacid = v.uniacid
					where v.uniacid = :uniacid and v.orderid = :orderid ",
					array(':orderid'=>$orderid,':uniacid'=>$order['uniacid']));
		if($order['verifytype']==0){
			$verify = pdo_fetch("select * from ".tablename('ewei_shop_groups_verify')." where orderid = ".$order['id']." ");
			if (!empty($verify['verifier'])) {
				$saler = m('member')->getMember($verify['verifier']);
				$saler['salername'] = pdo_fetchcolumn('select salername from ' . tablename('ewei_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1 ',
						array(':uniacid' => $_W['uniacid'], ':openid' => $verify['verifier']));
			}
			if (!empty($verify['storeid'])) {
				$store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:storeid limit 1 ', array(':storeid' => $verify['storeid']));
			}
		}
		include $this->template();
	}
	/*
	 * 订单信息
	 * */
	protected function opData() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_groups_order') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $id, ':uniacid' => $_W['uniacid']));
		if (empty($item)) {
			if ($_W['isajax']) {
				show_json(0, "未找到订单!");
			}
			$this->message('未找到订单!', '', 'error');
		}
		return array('id' => $id, 'item' => $item);
	}
}

