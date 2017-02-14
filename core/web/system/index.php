<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends SystemPage {

	 
	function main() {
		header("Location:".webUrl('system/plugin'));
		exit;
		include $this->template();
	}
}
