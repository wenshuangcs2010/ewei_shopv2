<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Map_EweiShopV2Page extends MobilePage {

    function  main(){
        global $_W, $_GPC;
        $id =intval($_GPC['id']);
        $merchid =intval($_GPC['merchid']);
        if ($merchid > 0) {
            $store = pdo_fetch('select * from ' . tablename('ewei_shop_merch_store') . ' where id=:id and uniacid=:uniacid and merchid=:merchid', array(':id'=>$id, ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
        } else {
            $store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:id and uniacid=:uniacid', array(':id'=>$id, ':uniacid' => $_W['uniacid']));
        }

        $store['logo'] = empty($store['logo'])?$_W['shopset']['shop']['logo']:$store['logo'];
        include $this->template();
    }


}
