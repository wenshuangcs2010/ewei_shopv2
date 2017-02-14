<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require_once IA_ROOT . '/addons/ewei_shopv2/version.php';
require_once IA_ROOT . '/addons/ewei_shopv2/defines.php';
require_once EWEI_SHOPV2_INC . 'functions.php';
class Ewei_shopv2ModuleSite extends WeModuleSite {

	public function getMenus(){
		global $_W;
		return array(
				array(
					'title' => '管理后台',
					'icon'=>'fa fa-shopping-cart',
					'url'=> webUrl()
				)
		);
	}
	public function doWebWeb() {
		m('route')->run();
	}
	public function doMobileMobile() {
		m('route')->run(false);
	}
	public function payResult($params) {
		return m('order')->payResult($params);
	}
}
