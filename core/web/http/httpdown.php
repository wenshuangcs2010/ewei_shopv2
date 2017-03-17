<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Httpdown_EweiShopV2Page extends WebPage {

	function main(){
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$return=m("httpUtil")->updateGoods($goods['goodssn'],$goods['id']);
		if($return){
			show_json(1,"更新成功");
		}
		show_json(0,'更新失败');
	}
}