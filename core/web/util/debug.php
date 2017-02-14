<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Debug_EweiShopV2Page extends WebPage {

    function main() {
        global $_W,$_GPC;
        $orderid = intval($_GPC['orderid']);
        dump(p('commission')->calculate($orderid));
    }

}