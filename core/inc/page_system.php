<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}


class SystemPage extends WebPage {

	public function __construct() {
	 
		global $_W;
		define("IS_EWEI_SHOPV2_SYSTEM",true);
		$routes = explode(".", $_W['routes']);
		$_W['current_menu'] = isset($routes[1])?$routes[1]:'';
		// if(!$_W['isfounder']){
		// 	$this->message('您无权访问');
		// }
	}
	
}
