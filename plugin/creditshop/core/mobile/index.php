<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN .'creditshop/core/page_mobile.php';
class Index_EweiShopV2Page extends CreditshopMobilePage {

	function main() {
		global $_W, $_GPC;

        $this->diyPage('creditshop');

		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$shop = m('common') -> getSysset('shop');

		//广告
		$advs = pdo_fetchall("select id,advname,link,thumb from " . tablename('ewei_shop_creditshop_adv') . ' where uniacid=:uniacid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$advs = set_medias($advs, 'thumb');
		//当前积分
		$credit = m('member') -> getCredit($openid, 'credit1');
		//分类
		$category = pdo_fetchall("select id,name,thumb,isrecommand from " . tablename('ewei_shop_creditshop_category') . ' where uniacid=:uniacid and  enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$category = set_medias($category, 'thumb');
		//积分抽奖
		$lotterydraws = pdo_fetchall("select id, title, subtitle, credit, money, thumb,`type`,price from " . tablename('ewei_shop_creditshop_goods') . '
				where uniacid=:uniacid and isrecommand = 1 and `type` = 1 and  status=1 and deleted=0 order by displayorder,id desc limit 4', array(':uniacid' => $uniacid));
		$lotterydraws = set_medias($lotterydraws, 'thumb');
		is_array($lotterydraws)?$lotterydraws:$lotterydraws = array();
		foreach($lotterydraws as $key => $value){
			if((intval($value['money'])-$value['money'])==0){
				$lotterydraws[$key]['money'] = intval($value['money']);
			}
		}

		//积分兑换
		$exchanges = pdo_fetchall("select id, title,goodstype, subtitle, credit, money, thumb,`type` from " . tablename('ewei_shop_creditshop_goods') . '
				where uniacid=:uniacid and isrecommand = 1 and goodstype = 0 and `type` = 0 and  status=1 and deleted=0 order by displayorder,id desc limit 4', array(':uniacid' => $uniacid));
		$exchanges = set_medias($exchanges, 'thumb');
		is_array($exchanges)?$exchanges:$exchanges = array();
		foreach($exchanges as $key => $value){
			if((intval($value['money'])-$value['money'])==0){
				$exchanges[$key]['money'] = intval($value['money']);
			}
		}
		//优惠券兑换
		$coupons = pdo_fetchall("select id, title, subtitle, credit, money, thumb,`type` from " . tablename('ewei_shop_creditshop_goods') . '
				where uniacid=:uniacid and isrecommand = 1 and goodstype = 1 and `type` = 0 and  status=1 and deleted=0 order by displayorder,id desc limit 4', array(':uniacid' => $uniacid));
		$coupons = set_medias($coupons, 'thumb');
		is_array($coupons)?$coupons:$coupons = array();
		foreach($coupons as $key => $value){
			if((intval($value['money'])-$value['money'])==0){
				$coupons[$key]['money'] = intval($value['money']);
			}
		}
		//余额兑换
		$balances = pdo_fetchall("select id, title, subtitle, credit, money, thumb,`type` from " . tablename('ewei_shop_creditshop_goods') . '
				where uniacid=:uniacid and isrecommand = 1 and goodstype = 2 and `type` = 0 and  status=1 and deleted=0 order by displayorder,id desc limit 4', array(':uniacid' => $uniacid));
		$balances = set_medias($balances, 'thumb');
		is_array($balances)?$balances:$balances = array();
		foreach($balances as $key => $value){
			if((intval($value['money'])-$value['money'])==0){
				$balances[$key]['money'] = intval($value['money']);
			}
		}
		//红包兑换
		$redbags = pdo_fetchall("select id, title, subtitle, credit, money, thumb,`type` from " . tablename('ewei_shop_creditshop_goods') . '
				where uniacid=:uniacid and isrecommand = 1 and goodstype = 3 and `type` = 0 and  status=1 and deleted=0 order by displayorder,id desc limit 4', array(':uniacid' => $uniacid));
		$redbags = set_medias($redbags, 'thumb');
		is_array($redbags)?$redbags:$redbags = array();
		foreach($redbags as $key => $value){
			if((intval($value['money'])-$value['money'])==0){
				$redbags[$key]['money'] = intval($value['money']);
			}
		}

	/* 分享 *************/
		$member = m('member') -> getMember($openid);
		$_W['shopshare'] = array(
			'title' => $this -> set['share_title'], 
			'imgUrl' => tomedia($this -> set['share_icon']), 
			'link' => mobileUrl('creditshop', array(), true), 
			'desc' => $this -> set['share_desc']
		);
		$com = p('commission');
		if ($com) {
			$cset = $com -> getSet();
			if (!empty($cset)) {
				if ($member['isagent'] == 1 && $member['status'] == 1) {
					$_W['shopshare']['link'] = mobileUrl('creditshop', array('mid' => $member['id']), true);
					if (empty($cset['become_reg']) && (empty($member['realname']) || empty($member['mobile']))) {
						$trigger = true;
					}
				} else if (!empty($_GPC['mid'])) {
					$_W['shopshare']['link'] = mobileUrl('creditshop/detail', array('mid' => $_GPC['mid']), true);
				}
			}
		}

		include $this -> template();
	}

}
