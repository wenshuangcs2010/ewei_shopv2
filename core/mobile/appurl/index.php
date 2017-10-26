<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Index_EweiShopV2Page extends MobilePage{
	var $userinfo=array();
	function main(){
		global $_GPC;
		$userinfo=$_GPC['userInfo'];
		
		$userinfo=json_decode($userinfo);
		var_dump($userinfo);
		echo json_encode(array("data"=>$userinfo));
	}
}