<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Op_EweiShopV2Page extends MobileLoginPage {

    /**
     * 取消订单
     * @global type $_W
     * @global type $_GPC
     */
    function cancel() {

        global $_W, $_GPC;
        $orderid = intval($_GPC['id']);
        $order = pdo_fetch("select id,ordersn,openid,status,deductcredit,deductcredit2,deductprice,couponid from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
        if (empty($order)) {
            show_json(0, '订单未找到');
        }
        if($order['status'] > 0){
            show_json(0, '订单已支付，不能取消!');
        }
        if($order['status'] < 0){
            show_json(0, '订单已经取消!');
        }

        //处理订单库存及用户积分情况(赠送积分)
        m('order')->setStocksAndCredits($orderid, 2);


        //返还抵扣积分
        if ($order['deductprice'] > 0) {
            m('member')->setCredit($order['openid'], 'credit1', $order['deductcredit'], array('0', $_W['shopset']['shop']['name'] . "购物返还抵扣积分 积分: {$order['deductcredit']} 抵扣金额: {$order['deductprice']} 订单号: {$order['ordersn']}"));
        }

        //返还抵扣余额
        m('order')->setDeductCredit2($order);

        //退还优惠券
        if (com('coupon') && !empty($order['couponid'])) {
            com('coupon')->returnConsumeCoupon($orderid); //手机关闭订单
        }
        pdo_update('ewei_shop_order', array('status' => -1, 'canceltime' => time(), 'closereason' => trim($_GPC['remark'])), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));

        //模板消息
        m('notice')->sendOrderMessage($orderid);

        show_json(1);
    }

    /**
     * 确认收货
     * @global type $_W
     * @global type $_GPC
     */
    function finish() {

        global $_W, $_GPC;
        $orderid = intval($_GPC['id']);
        $order = pdo_fetch("select id,status,openid,couponid,refundstate,refundid from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
        if (empty($order)) {
            show_json(0, '订单未找到');
        }
        if ($order['status'] != 2) {
            show_json(0, '订单不能确认收货');
        }
        if ($order['refundstate'] > 0 && !empty($order['refundid'])) {

            $change_refund = array();
            $change_refund['status'] = -2;
            $change_refund['refundtime'] = time();
            pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $order['refundid'], 'uniacid' => $_W['uniacid']));
        }

        pdo_update('ewei_shop_order', array('status' => 3, 'finishtime' => time(), 'refundstate' => 0), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));

        //会员升级
        m('member')->upgradeLevel($order['openid']);

        //余额赠送
        m('order')->setGiveBalance($orderid, 1);

        //发送赠送优惠券
        if (com('coupon')) {
            $refurnid = com('coupon')->sendcouponsbytask($orderid); //订单支付
        }

        //优惠券返利
        if (com('coupon') && !empty($order['couponid'])) {
            com('coupon')->backConsumeCoupon($orderid); //手机收货
        }

        //模板消息
        m('notice')->sendOrderMessage($orderid);

        //打印机打印
        com_run('printer::sendOrderMessage',$orderid);

        //分销检测
        if (p('commission')) {
            p('commission')->checkOrderFinish($orderid);
        }

        show_json(1);
    }

    /**
     * 删除或恢复订单
     * @global type $_W
     * @global type $_GPC
     */
    function delete() {
        global $_W, $_GPC;

        //删除订单
        $orderid = intval($_GPC['id']);
        $userdeleted = intval($_GPC['userdeleted']);

        $order = pdo_fetch("select id,status,refundstate,refundid from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
        if (empty($order)) {
            show_json(0, '订单未找到!');
        }

        if ($userdeleted == 0) {
            if ($order['status'] != 3) {
                show_json(0, '无法恢复');
            }
        } else {
            if ($order['status'] != 3 && $order['status'] != -1) {
                show_json(0, '无法删除');
            }

            if ($order['refundstate'] > 0 && !empty($order['refundid'])) {

                $change_refund = array();
                $change_refund['status'] = -2;
                $change_refund['refundtime'] = time();
                pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $order['refundid'], 'uniacid' => $_W['uniacid']));
            }
        }

        pdo_update('ewei_shop_order', array('userdeleted' => $userdeleted, 'refundstate' => 0), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
        show_json(1);
    }

}
