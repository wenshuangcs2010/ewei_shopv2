<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
error_reporting(0);
require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/ewei_shopv2/defines.php';
require '../../../../../addons/ewei_shopv2/core/inc/functions.php';
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

    $days = intval($trade['closeorder']);
    if ($days <= 0) {
        //不自动关闭订单
        continue;
    }

    $daytimes = 86400 * $days;
    $orders = pdo_fetchall("select id,openid,deductcredit2,ordersn,isparent,deductcredit,deductprice from " . tablename('ewei_shop_order') . " where  uniacid={$_W['uniacid']} and status=0 and paytype<>3  and createtime + {$daytimes} <=unix_timestamp() ");

    $p = com('coupon');
    foreach ($orders as $o) {
        $onew = pdo_fetch('select status,isparent from ' . tablename('ewei_shop_order') . " where id=:id and status=0 and paytype<>3  and createtime + {$daytimes} <=unix_timestamp()  limit 1", array(':id' => $o['id']));
        if(!empty($onew) && $onew['status']==0){

            //多商户父订单跳过
            if ($o['isparent'] == 0) {
                if ($p) {
                    //退还优惠券
                    if (!empty($o['couponid'])) {
                        $p->returnConsumeCoupon($o['id']); //自动关闭订单
                    }
                }

                //处理订单库存及用户积分情况
                m('order')->setStocksAndCredits($o['id'], 2);

                //返还抵扣余额
                m('order')->setDeductCredit2($o);

                //返还抵扣积分
                if ($o['deductprice'] > 0) {
                    m('member')->setCredit($o['openid'], 'credit1', $o['deductcredit'], array('0', $_W['shopset']['shop']['name'] . "自动关闭订单返还抵扣积分 积分: {$o['deductcredit']} 抵扣金额: {$o['deductprice']} 订单号: {$o['ordersn']}"));
                }
            }

            pdo_query("update " . tablename('ewei_shop_order') . ' set status=-1,canceltime=' . time() . ' where id=' . $o['id']);
        }
    }
}




