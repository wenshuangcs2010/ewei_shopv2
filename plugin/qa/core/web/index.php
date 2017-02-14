<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage {

	function main(){
		if (cv('qa.question')) {
			header('location: ' . webUrl('qa/question'));
		} else if (cv('qa.category')) {
			header('location: ' . webUrl('qa/category'));
		} else if (cv('qa.set')) {
			header('location: ' . webUrl('qa/set'));
		}else {
			header('location: ' . webUrl());
		}
		exit;
	}

}
