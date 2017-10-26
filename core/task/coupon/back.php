<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */

require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/ewei_shopv2/defines.php';
require '../../../../../addons/ewei_shopv2/core/inc/functions.php';
global $_W, $_GPC;
ignore_user_abort(); //忽略关闭浏览器
set_time_limit(0); //永远执行
$p = com('coupon');
$sets = pdo_fetchall('select uniacid from ' . tablename('ewei_shop_sysset'));
foreach ($sets as $set) {

    $_W['uniacid'] = $set['uniacid'];
    if (empty($_W['uniacid'])) {
        continue;
    }
    $trade = m('common')->getSysset('trade', $_W['uniacid']);

    $days = intval($trade['refunddays']);
    $daytimes = 86400 * $days;


    $orders = pdo_fetchall("select id,couponid from " . tablename('ewei_shop_order') . " where  uniacid={$_W['uniacid']} and status=3 and isparent=0 and couponid<>0 and finishtime + {$daytimes} <=unix_timestamp() ");
    if (!empty($orders)) {
        if ($p) {
            foreach ($orders as $o) {
                //优惠券自动返利
                if (!empty($o['couponid'])) {
                    $p->backConsumeCoupon($o['id']); //自动关闭订单
                }
            }
        }
    }
}




