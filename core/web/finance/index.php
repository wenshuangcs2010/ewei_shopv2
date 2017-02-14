<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage {

	function main() {
		if(cv('finance.recharge.view')){
			header('location: '.webUrl('finance/log/recharge'));
		} else if(cv('finance.withdraw.view')){
			header('location: '.webUrl('finance/log/withdraw'));
		} else if(cv('finance.downloadbill')){
			header('location: '.webUrl('finance/downloadbill'));
		}else{
			header('location: '.webUrl());
		}

	}
}