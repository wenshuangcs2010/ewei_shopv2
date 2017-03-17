<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends MobilePage {
	function main(){
		global $_W, $_GPC;
		$storelist=pdo_fetchall("SELECT * from ".tablename("ewei_shop_store")." where uniacid=:uniacid and status=1",array(":uniacid"=>$_W['uniacid']));
		   $storelist = set_medias($storelist, 'logo');
		//var_dump($storelist);
		include $this->template();
	}
}