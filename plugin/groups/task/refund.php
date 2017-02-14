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
$sets = pdo_fetchall('select uniacid,refund from ' . tablename('ewei_shop_groups_set'));
foreach ($sets as $key => $value) {
    global $_W, $_GPC,$_S;
    $_W['uniacid'] = $value['uniacid'];
    $shopset = $_S['shop'];
    $_W['uniacid'] = $value['uniacid'];
    if (empty($_W['uniacid'])) {
        continue;
    }
    $hours = intval($value['refund']);                    //拼团失败X小时
    if ($hours <= 0) {
        //不自动退款
        continue;
    }
    $times = $hours * 60 * 60;
    $orders = pdo_fetchall("select id,orderno,openid,credit,creditmoney,price,freight,status,pay_type,teamid,apppay from " . tablename('ewei_shop_groups_order') . "
            where  uniacid={$_W['uniacid']} and status = 1 and success = -1 and refundtime = 0 and canceltime + {$times} <= ".time()." ");

    foreach ($orders as $k => $val) {
        //退款金额
        $realprice = $val['price'] - $val['creditmoney'] + $val['freight'];
        //购物积分
        $credits = $val['credit'];
        if ($val['pay_type'] == 'credit') {

            //余额支付，直接返回余额
            $result = m('member')->setCredit($val['openid'], 'credit2', $realprice, array(0, $shopset['name'] . "退款: {$realprice}元 订单号: " . $val['orderno']));
        } else if ($val['pay_type'] == 'wechat') {
            //微信支付，走退款 接口
            //直接退还扣除减掉余额抵扣
            $realprice = round($realprice, 2);

            $result = m('finance')->refund($val['openid'], $val['orderno'], $val['orderno'], floatval($realprice) * 100, $realprice * 100, !empty($order['apppay']) ? true : false);
            $refundtype = 2;
        } else {
            //其他支付方式，走微信企业付款
            if ($realprice < 1) {
                show_json(0,'退款金额必须大于1元，才能使用微信企业付款退款!');
            }

            $result = m('finance')->pay($val['openid'], 1, $realprice * 100, $val['orderno'], $shopset['name'] . "退款: {$realprice}元 订单号: " . $val['orderno']);
            $refundtype = 1;
        }
        //返还抵扣积分
        if ($credits > 0) {
            m('member')->setCredit($val['openid'], 'credit1', $credits, array('0', $shopset['name'] . "购物返还抵扣积分 积分: {$val['credit']} 抵扣金额: {$val['creditmoney']} 订单号: {$val['orderno']}"));
        }
        //模板消息
        /*p('groups')->sendTeamMessage($val['id'], true);*/
        //更新订单退款状态
        pdo_update('ewei_shop_groups_order', array('refundstate' => 0, 'status' => -1, 'refundtime' => time()), array('id' => $val['id'], 'uniacid' => $_W['uniacid']));

        //更新实际销量
        $sales = pdo_fetch("select id,sales,stock from ".tablename('ewei_shop_groups_goods')." where id = :id and uniacid = :uniacid ",array(':id'=>$val['goodid'], ':uniacid' => $uniacid));

        pdo_update('ewei_shop_groups_goods', array('sales' => $sales['sales'] - 1,'stock'=>$sales['stock'] + 1), array('id' => $sales['id'], 'uniacid' => $uniacid));

        plog('groups.task.refund', "订单退款 ID: {$val['id']} 订单号: {$val['orderno']}");
    }
}




