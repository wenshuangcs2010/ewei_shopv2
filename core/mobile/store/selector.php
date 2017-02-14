<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Selector_EweiShopV2Page extends MobilePage {

    function  main(){

        global $_W, $_GPC;

        $ids = trim($_GPC['ids']);
        $type = intval($_GPC['type']);
        $merchid = intval($_GPC['merchid']);

        $condition = '';

        if(!empty($ids)){
            $condition =  " and id in({$ids})";
        }
        // type=1 自提  type=2 核销
        if($type==1){
            $condition .= " and type in(1,3) ";
        }
        elseif ($type==2){
            $condition .= " and type in(2,3) ";
        }

        if ($merchid > 0) {
            $list = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 '. $condition .' order by displayorder desc,id desc', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
        } else {
            $list = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1 '. $condition .' order by displayorder desc,id desc', array(':uniacid' => $_W['uniacid']));

        }
        include $this->template();


    }


}
