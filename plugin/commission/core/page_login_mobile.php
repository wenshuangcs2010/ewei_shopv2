<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class CommissionMobileLoginPage extends PluginMobileLoginPage {

	public function __construct() {
		parent::__construct();
		
		global $_W, $_GPC;
 
		if ($_W['action'] != 'register' && $_W['action'] != 'myshop' && $_W['action'] != 'share') {
			$member = m('member')->getMember($_W['openid']);
			if ($member['isagent'] != 1 || $member['status'] != 1) {
				header('location:' . mobileUrl('commission/register'));
				exit;
			}
		}
	}
//	public function footerMenus() {
//		global $_W, $_GPC;
//		include $this->template('commission/_menu');
//	}

}
