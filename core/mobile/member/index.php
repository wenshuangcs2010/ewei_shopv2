<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends MobileLoginPage {

	function main() {

		global $_W, $_GPC;

        $this->diypage('member');
        $shopset = m('common')->getSysset(array('shop','wap'));
        $followed = m('user')->followed($_W['openid']);

         mc_oauth_userinfo();

		//会员信息
		$member = m('member')->getMember($_W['openid'], true);
		//会员等级
		$level = m('member')->getLevel($_W['openid']);

		 
		//是否开启积分兑换
		$open_creditshop = p('creditshop') && $_W['shopset']['creditshop']['centeropen'];
		
		//统计
		$params = array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']);
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		if ($merch_plugin && $merch_data['is_openmerch'])
		{
			$statics = array(
				'order_0'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and status=0 and isparent=0 and uniacid=:uniacid limit 1',$params),
				'order_1'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and status=1 and isparent=0 and refundid=0 and uniacid=:uniacid limit 1',$params),
				'order_2'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and status=2 and isparent=0 and refundid=0 and uniacid=:uniacid limit 1',$params),
				'order_4'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and refundstate=1 and isparent=0 and uniacid=:uniacid limit 1',$params),
				'cart'=>pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_member_cart') . ' where uniacid=:uniacid and openid=:openid and deleted=0 ', $params),
				'favorite'=>pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_favorite') . ' where uniacid=:uniacid and openid=:openid and deleted=0 ', $params)
			);
		}
		else
		{
			$statics = array(
				'order_0'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and ismr=0 and status=0 and isparent=0 and uniacid=:uniacid and isparent=0 limit 1',$params),
				'order_1'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and ismr=0 and status=1 and isparent=0 and refundid=0 and uniacid=:uniacid and isparent=0 limit 1',$params),
				'order_2'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and ismr=0 and status=2 and isparent=0 and refundid=0 and uniacid=:uniacid and isparent=0 limit 1',$params),
				'order_4'=>  pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where openid=:openid and ismr=0 and refundstate=1 and isparent=0 and uniacid=:uniacid and isparent=0 limit 1',$params),
				'cart'=>pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_member_cart') . ' where uniacid=:uniacid and openid=:openid and deleted=0 and selected = 1', $params),
				'favorite'=>$merch_plugin && $merch_data['is_openmerch'] ? pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_favorite') . ' where uniacid=:uniacid and openid=:openid and deleted=0 and `type`=0', $params) : pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_favorite') . ' where uniacid=:uniacid and openid=:openid and deleted=0', $params),
			);
		}
		
		//优惠券
		$hascoupon = false;
		$hascouponcenter = false;
		
		$plugin_coupon = com('coupon');
		if($plugin_coupon){
			
			$time = time();
			$sql = "select count(*) from ".tablename('ewei_shop_coupon_data')." d";
			$sql.=" left join ".tablename('ewei_shop_coupon')." c on d.couponid = c.id";
			$sql.=" where d.openid=:openid and d.uniacid=:uniacid and  d.used=0 "; //类型+最低消费+示使用
			$sql.=" and (   (c.timelimit = 0 and ( c.timedays=0 or c.timedays*86400 + d.gettime >=unix_timestamp() ) )  or  (c.timelimit =1 and c.timestart<={$time} && c.timeend>={$time})) order by d.gettime desc"; //有效期

			$statics['coupon'] = pdo_fetchcolumn($sql,array(':openid'=>$_W['openid'],':uniacid'=>$_W['uniacid']));

			$statics['newcoupon'] = pdo_fetchcolumn("SELECT 1  FROM ".tablename('ewei_shop_coupon_data')." where openid=:openid and uniacid=:uniacid and  isnew=1", array(':openid'=>$_W['openid'],':uniacid'=>$_W['uniacid']));

			$pcset = $_W['shopset']['coupon'];
			if(empty($pcset['closemember'])){
				$hascoupon = true;
			}
			if(empty($pcset['closecenter'])){
				$hascouponcenter = true;
			}
		}

		//股东
		$hasglobonus = false;
		$plugin_globonus = p('globonus');
		if($plugin_globonus){
			$plugin_globonus_set = $plugin_globonus->getSet();
			$hasglobonus = !empty($plugin_globonus_set['open']) && !empty($plugin_globonus_set['openmembercenter']);
		}

		//联合创始人
		$hasauthor = false;
		$plugin_author = p('author');
		if($plugin_author){
			$plugin_author_set = $plugin_author->getSet();
			$hasauthor = !empty($plugin_author_set['open']) && !empty($plugin_author_set['openmembercenter']);
		}

		//区域代理
		$hasabonus = false;
		$plugin_abonus = p('abonus');
		if($plugin_abonus){
			$plugin_abonus_set = $plugin_abonus->getSet();
			$hasabonus = !empty($plugin_abonus_set['open']) && !empty($plugin_abonus_set['openmembercenter']);
		}

		$hasqa = false;
		$plugin_qa = p('qa');
		if($plugin_qa){
			$plugin_qa_set = $plugin_qa->getSet();
			if(!empty($plugin_qa_set['showmember'])){
				$hasqa = true;
			}
		}

		$hassign = false;
		$com_sign = p('sign');
		if($com_sign){
			$com_sign_set = $com_sign->getSet();
			if(!empty($com_sign_set['iscenter']) && !empty($com_sign_set['isopen'])){
				$hassign = empty($_W['shopset']['trade']['credittext']) ? "积分" : $_W['shopset']['trade']['credittext'];
				$hassign .= empty($com_sign_set['textsign']) ? "签到" : $com_sign_set['textsign'];
			}
		}

		$wapset = m('common')->getSysset('wap');
		
		include $this->template();
	}

}
