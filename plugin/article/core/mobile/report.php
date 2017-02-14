<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Report_EweiShopV2Page extends PluginMobileLoginPage {

    function main() {
    	global $_W,$_GPC;
		
		$aid = intval($_GPC['aid']);
		
		include $this->template();
	}
	
	function post() {
    	global $_W,$_GPC;
		$aid = intval($_GPC['aid']);
        $cate = trim($_GPC['cate']);
        $content = trim($_GPC['content']);
        $mid = m('member')->getMid();
        $openid = $_W['openid'];
		if(!empty($aid) && !empty($cate) && !empty($content) && !empty($aid) && !empty($openid)){
			$insert = array('mid'=>$mid, 'openid'=>$openid, 'aid'=>$aid, 'cate'=>$cate, 'cons'=>$content,'uniacid'=>$_W['uniacid']);
			pdo_insert('ewei_shop_article_report', $insert);
			show_json(1);
		}
		show_json(0);
	}
}