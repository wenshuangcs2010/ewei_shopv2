<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class GlobonusMobilePage extends PluginMobilePage {

	public function __construct() {
		parent::__construct();
		
		global $_W, $_GPC;
 
		if ($_W['action'] != 'register' && $_W['action'] != 'myshop' && $_W['action'] != 'share') {
			$member = m('member')->getMember($_W['openid']);

			if (empty($member['isagent']) || empty($member['status'])) {
				header("location: " . mobileUrl('commission/register'));
				exit;
			}

			if (empty($member['ispartner']) || empty($member['partnerstatus'])) {
				header("location: " . mobileUrl('globonus/register'));
				exit;
			}
		}
	}
	public function footerMenus($diymenuid = NULL) {
		global $_W, $_GPC;
		include $this->template('globonus/_menu');
	}

}
