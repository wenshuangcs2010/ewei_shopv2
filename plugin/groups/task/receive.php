<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
error_reporting(0);
require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/ewei_shopv2/defines.php';
require '../../../../../addons/ewei_shopv2/core/inc/functions.php';
require '../../../../../addons/ewei_shopv2/core/inc/plugin_model.php';
global $_W, $_GPC;

ignore_user_abort(); //忽略关闭浏览器
set_time_limit(0); //永远执行

$sets = pdo_fetchall('select uniacid,receive from ' . tablename('ewei_shop_groups_set'));
foreach ($sets as $set) {

    $_W['uniacid'] = $set['uniacid'];
    if (empty($_W['uniacid'])) {
        continue;
    }

    $days = intval($set['receive']);
    if($days<=0){ //不自动收货
        continue;
    }
    $daytimes = 86400 * $days;
    $p = p('groups');
    $pcoupon = com('coupon');

    $orders = pdo_fetchall("select id from " . tablename('ewei_shop_groups_order') . " where uniacid={$_W['uniacid']} and status=2 and sendtime + {$daytimes} <=unix_timestamp() ",array(),'id');
    if (!empty($orders)) {
        $orderkeys = array_keys($orders);

        $orderids = implode(",", $orderkeys);
        if (!empty($orderids)) {
            pdo_query("update " . tablename('ewei_shop_groups_order') . ' set status=3,finishtime=' . time() . ' where id in (' . $orderids . ')');
        }
    }
}




