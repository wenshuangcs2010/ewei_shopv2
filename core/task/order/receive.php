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

$sets = pdo_fetchall('select uniacid from ' . tablename('ewei_shop_sysset'));
foreach ($sets as $set) {

    $_W['uniacid'] = $set['uniacid'];
    if (empty($_W['uniacid'])) {
        continue;
    }

    $trade = m('common')->getSysset('trade', $_W['uniacid']);
    $days = intval($trade['receive']);

    $p = p('commission');
    $pcoupon = com('coupon');

    $orders = pdo_fetchall("select id,couponid,openid,isparent,sendtime from " . tablename('ewei_shop_order') . " where uniacid={$_W['uniacid']} and status=2 ", array(), 'id');

    if (!empty($orders)) {

        foreach ($orders as $orderid => $order) {
            $result = goodsReceive($order, $days);
            if (!$result) {
                //不自动收货
                continue;
            }

            pdo_query("update " . tablename('ewei_shop_order') . ' set status=3,finishtime=' . time() . ' where id='. $orderid );

            //多商户父订单跳过
            if ($order['isparent'] == 1) {
                continue;
            }
            //会员升级
            m('member')->upgradeLevel($order['openid']);
            //余额赠送
            m('order')->setGiveBalance($orderid, 1);
            //模板消息
            m('notice')->sendOrderMessage($orderid);




            //优惠券返利
            if ($pcoupon) {
                //发送赠送优惠卷
                com('coupon')->sendcouponsbytask($item['id']); //订单支付

                if (!empty($order['couponid'])) {
                    $pcoupon->backConsumeCoupon($order['id']); //自动收货
                }
            }
            //分销检测
            if ($p) {
                $p->checkOrderFinish($orderid);
            }
        }
    }
}

function goodsReceive($order, $sysday=0){
    $days = array();

    $goods = pdo_fetchall("select og.goodsid, g.autoreceive from".tablename("ewei_shop_order_goods") ." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid where og.orderid=".$order['id']);

    foreach ($goods as $i=>$g){
        $days[] = $g['autoreceive'];
    }

    $day = max($days);
    if($day<0){
        return false;
    }
    elseif($day==0){
        if($sysday<=0){
            return false;
        }
        $day = $sysday;
    }

    $daytimes = 86400 * $day;

    if($order['sendtime']+$daytimes<=time()){
        return true;
    }

    return false;
}



