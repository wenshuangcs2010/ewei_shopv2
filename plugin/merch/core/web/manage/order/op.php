<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'merch/core/inc/page_merch.php';
class Op_EweiShopV2Page extends MerchWebPage {

    function delete() {

        global $_W, $_GPC;
        $status = intval($_GPC['status']);
        $orderid = intval($_GPC['id']);

        pdo_update('ewei_shop_order', array('deleted' => 1), array('id' => $orderid, 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));
        plog('order.op.delete', "订单删除 ID: {$orderid}");
        show_json(1, webUrl('order', array('status' => $status)));
    }

    protected function opData() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id = :id and uniacid=:uniacid and merchid = :merchid", array(':id' => $id, ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));
        if (empty($item)) {
            if ($_W['isajax']) {
                show_json(0, "未找到订单!");
            }
            $this->message('未找到订单!', '', 'error');
        }
        return array('id' => $id, 'item' => $item);
    }

    function changeprice() {

        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if ($_W['ispost']) {
            if ($item['parentid'] > 0) {
                $parent_order = array();
                $parent_order['id'] = $item['parentid'];
            }

            $changegoodsprice = $_GPC['changegoodsprice'];
            if (!is_array($changegoodsprice)) {
                show_json(0, '未找到改价内容!');
            }
            $changeprice = 0;
            foreach ($changegoodsprice as $ogid => $change) {
                $changeprice+=floatval($change);
            }

            $dispatchprice = floatval($_GPC['changedispatchprice']);
            if ($dispatchprice < 0) {
                $dispatchprice = 0;
            }
            $orderprice = $item['price'] + $changeprice;
            $changedispatchprice = 0;
            if ($dispatchprice != $item['dispatchprice']) {
                //修改了运费
                $changedispatchprice = $dispatchprice - $item['dispatchprice'];
                $orderprice+=$changedispatchprice;
            }

            if ($orderprice < 0) {
                show_json(0, "订单实际支付价格不能小于0元!");
            }
            foreach ($changegoodsprice as $ogid => $change) {
                $og = pdo_fetch('select price,realprice from ' . tablename('ewei_shop_order_goods') . ' where id=:ogid and uniacid=:uniacid and merchid = :merchid limit 1', array(':ogid' => $ogid, ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));
                if (!empty($og)) {
                    $realprice = $og['realprice'] + $change;
                    if ($realprice < 0) {
                        show_json(0, '单个商品不能优惠到负数');
                    }
                }
            }
            $ordersn2 = $item['ordersn2'] + 1;
            if ($ordersn2 > 99) {
                show_json(0, '超过改价次数限额');
            }


            $orderupdate = array();
            if ($orderprice != $item['price']) {
                //订单价格变化
                $orderupdate['price'] = $orderprice;
                $orderupdate['ordersn2'] = $item['ordersn2'] + 1;

                if ($item['parentid'] > 0) {
                    $parent_order['price_change'] = $orderprice - $item['price'];
                }
            }
            //订单的价格变化值
            $orderupdate['changeprice'] = $item['changeprice'] + $changeprice;

            if ($dispatchprice != $item['dispatchprice']) {
                //运费变化
                $orderupdate['dispatchprice'] = $dispatchprice; //这次的运费变化
                $orderupdate['changedispatchprice'] = $item['changedispatchprice'] + $changedispatchprice; //

                if ($item['parentid'] > 0) {
                    $parent_order['dispatch_change'] = $changedispatchprice;
                }
            }

            if (!empty($orderupdate)) {
                pdo_update('ewei_shop_order', $orderupdate, array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));
            }

            if ($item['parentid'] > 0) {
                if (!empty($parent_order)) {
                    m('order')->changeParentOrderPrice($parent_order);
                }
            }

            //修改商品价
            foreach ($changegoodsprice as $ogid => $change) {
                $og = pdo_fetch('select price,realprice,changeprice from ' . tablename('ewei_shop_order_goods') . ' where id=:ogid and uniacid=:uniacid and merchid = :merchid limit 1', array(':ogid' => $ogid, ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));
                if (!empty($og)) {
                    $realprice = $og['realprice'] + $change; //这次的变化
                    $changeprice = $og['changeprice'] + $change; //累计的变化
                    pdo_update('ewei_shop_order_goods', array('realprice' => $realprice, 'changeprice' => $changeprice), array('id' => $ogid));
                }
            }

            //修改商品佣金
            if (abs($changeprice) > 0) {
                $pluginc = p('commission');
                if ($pluginc) {
                    $pluginc->calculate($item['id'], true);
                }
            }
            plog('order.op.changeprice', "订单号： {$item['ordersn']} <br/> 价格： {$item['price']} -> {$orderprice}");
            show_json(1);
        }
        //订单商品
        $order_goods = pdo_fetchall('select og.id,g.title,g.thumb,g.goodssn,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, og.total,og.price,og.optionname as optiontitle, og.realprice,og.oldprice from ' . tablename('ewei_shop_order_goods') . ' og '
            . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid '
            . ' where og.uniacid=:uniacid and og.merchid = :merchid and og.orderid=:orderid ', array(':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid'], ':orderid' => $item['id']));

        if (empty($item['addressid'])) {
            $user = unserialize($item['carrier']);
            $item['addressdata'] = array(
                'realname' => $user['carrier_realname'],
                'mobile' => $user['carrier_mobile']
            );
        } else {

            $user = iunserializer($item['address']);
            if (!is_array($user)) {
                //readytodo
                $user = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $item['addressid'], ':uniacid' => $_W['uniacid']));
            }
            $user['address'] = $user['province'] . ' ' . $user['city'] . ' ' . $user['area'] . ' ' . $user['address'];

            $item['addressdata'] = array(
                'realname' => $user['realname'],
                'mobile' => $user['mobile'],
                'address' => $user['address'],
            );
        }
        include $this->template();
    }

    function pay($a = array(), $b = array()) {

        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        //多商户不能后台付款
        show_json(1);

        if ($item['status'] > 1) {
            show_json(0, '订单已付款，不需重复付款！');
        }
        if (!empty($item['virtual']) && c('virtual')) {
            //虚拟物品自动发货
            c('virtual')->pay($item);
        } else {
            //确认付款先改状态，再设置库存
            pdo_update('ewei_shop_order', array(
                'status' => 1,
                'paytype' => 11,
                'paytime' => time()
                //,'remark' => $_GPC['remark']
            ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));

            //设置库存,增加积分
            m('order')->setStocksAndCredits($item['id'], 1);

            //模板消息
            m('notice')->sendOrderMessage($item['id']);


            //优惠券返利
            if (com('coupon') && !empty($item['couponid'])) {
                com('coupon')->backConsumeCoupon($item['id']); //后台确认付款
            }


            //分销检测
            if (p('commission')) {
                p('commission')->checkOrderPay($item['id']);
            }
        }
        plog('order.op.pay', "订单确认付款 ID: {$item['id']} 订单号: {$item['ordersn']}");
        show_json(1);
    }

    function close() {
        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if ($item['status'] == -1) {
            show_json(0, '订单已关闭，无需重复关闭！');
        } else if ($item['status'] >= 1) {
            show_json(0, '订单已付款，不能关闭！');
        }
        if ($_W['ispost']) {

            if ($item['parentid'] > 0) {
                //多商户中的子订单不能关闭
                show_json(1);
            }

            if (!empty($item['transid'])) {
                //changeWechatSend($item['ordersn'], 0, $_GPC['reson']);
            }
            $time = time();
            if ($item['refundstate'] > 0 && !empty($item['refundid'])) {

                $change_refund = array();
                $change_refund['status'] = -1;
                $change_refund['refundtime'] = $time;
                pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $item['refundid'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));
            }

            //返还抵扣积分
            if ($item['deductcredit'] > 0) {
                m('member')->setCredit($item['openid'], 'credit1', $item['deductcredit'], array('0', $_W['shopset']['shop']['name'] . "购物返还抵扣积分 积分: {$item['deductcredit']} 抵扣金额: {$item['deductprice']} 订单号: {$item['ordersn']}"));
            }

            //返还抵扣余额
            m('order')->setDeductCredit2($item);

            //退还优惠券
            if (com('coupon') && !empty($item['couponid'])) {
                com('coupon')->returnConsumeCoupon($item['id']); //后台关闭订单
            }
            m('order')->setStocksAndCredits($item['id'], 2);

            pdo_update('ewei_shop_order', array('status' => -1, 'refundstate' => 0, 'canceltime' => $time, 'remarkclose' => $_GPC['remark']), array('id' => $item['id'], 'uniacid' => $_W['uniacid']));

            plog('order.op.close', "订单关闭 ID: {$item['id']} 订单号: {$item['ordersn']}");
            show_json(1);
        }
        include $this->template();
    }

    function paycancel() {

        global $_W, $_GPC;

        $opdata = $this->opData();
        extract($opdata);
        if ($item['status'] != 1) {
            show_json(0, '订单未付款，不需取消！');
        }
        if ($_W['ispost']) {

            //先设置库存，再更改状态,
            m('order')->setStocksAndCredits($item['id'], 2);

            pdo_update('ewei_shop_order', array(
                'status' => 0,
                'cancelpaytime' => time()
            ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));

            plog('order.op.paycancel', "订单取消付款 ID: {$item['id']} 订单号: {$item['ordersn']}");
            show_json(1);
        }
    }

    function finish() {
        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        pdo_update('ewei_shop_order', array(
            'status' => 3,
            'finishtime' => time()
        ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));

        //会员升级
        m('member')->upgradeLevel($item['openid']);

        //余额赠送
        m('order')->setGiveBalance($item['id'], 1);

        //模板消息
        m('notice')->sendOrderMessage($item['id']);

        //优惠券返利
        if (!empty($item['couponid'])) {
            m('coupon')->backConsumeCoupon($item['id']); //后台收货
        }
        //分销检测
        if (p('commission')) {
            p('commission')->checkOrderFinish($item['id']);
        }
        plog('order.op.finish', "订单完成 ID: {$item['id']} 订单号: {$item['ordersn']}");
        show_json(1);
    }

    function fetchcancel() {

        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if ($item['status'] != 3) {
            show_json(0, '订单未取货，不需取消！');
        }
        pdo_update(
            'ewei_shop_order', array(
            'status' => 1,
            'finishtime' => 0
        ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid'])
        );
        plog('order.op.fetchcancel', "订单取消取货 ID: {$item['id']} 订单号: {$item['ordersn']}");
        show_json(1);
    }

    function sendcancel() {
        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);
        if ($item['status'] != 2) {
            show_json(0, '订单未发货，不需取消发货！');
        }

        if ($_W['ispost']) {

            if (!empty($item['transid'])) {
                //changeWechatSend($item['ordersn'], 0, $_GPC['cancelreson']);
            }
            $remark = trim($_GPC['remark']);
            if(!empty($item['remarksend'])){
                $remark = $item['remarksend']."\r\n".$remark;
            }
            pdo_update(
                'ewei_shop_order', array(
                'status' => 1,
                'sendtime' => 0,
                'remarksend'=>$remark
            ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid'])
            );
            plog('order.op.sendcancel', "订单取消发货 ID: {$item['id']} 订单号: {$item['ordersn']} 原因: {$remark}");
            show_json(1);
        }
        include $this->template();
    }

    function fetch() {

        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if ($item['status'] != 1) {
            message('订单未付款，无法确认取货！');
        }
        $time = time();
        $d = array(
            'status' => 3,
            'sendtime' => $time,
            'finishtime' => $time
        );

        if ($item['isverify'] == 1) {
            $d['verified'] = 1;
            $d['verifytime'] = $time;
            $d['verifyopenid'] = "";
        }
        pdo_update(
            'ewei_shop_order', $d, array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid'])
        );

        //取消退款状态
        if (!empty($item['refundid'])) {
            $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $item['refundid']));
            if (!empty($refund)) {
                pdo_update('ewei_shop_order_refund', array('status' => -1), array('id' => $item['refundid']));
                pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id']));
            }
        }

        //余额赠送
        m('order')->setGiveBalance($item['id'], 1);

        //会员升级
        m('member')->upgradeLevel($item['openid']);

        //模板消息
        m('notice')->sendOrderMessage($item['id']);

        //分销佣金
        if (p('commission')) {
            p('commission')->checkOrderFinish($item['id']);
        }
        plog('order.op.fetch', "订单确认取货 ID: {$item['id']} 订单号: {$item['ordersn']}");
        show_json(1);
    }

    function send() {

        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if (empty($item['addressid'])) {
            show_json(0, '无收货地址，无法发货！');
        }
        if ($item['paytype'] != 3) {
            if ($item['status'] != 1) {
                show_json(0, '订单未付款，无法发货！');
            }
        }
        if ($_W['ispost']) {
            if (!empty($_GPC['isexpress']) && empty($_GPC['expresssn'])) {
                show_json(0, '请输入快递单号！');
            }
            if (!empty($item['transid'])) {
                //changeWechatSend($item['ordersn'], 1);
            }

            $time = time();
            pdo_update(
                'ewei_shop_order', array(
                'status' => 2,
                'express' => trim($_GPC['express']),
                'expresscom' => trim($_GPC['expresscom']),
                'expresssn' => trim($_GPC['expresssn']),
                'sendtime' => $time
            ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid'])
            );
            //取消退款状态
            if (!empty($item['refundid'])) {
                $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $item['refundid']));
                if (!empty($refund)) {
                    pdo_update('ewei_shop_order_refund', array('status' => -1, 'endtime' => $time), array('id' => $item['refundid']));
                    pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id']));
                }
            }
            //模板消息
            m('notice')->sendOrderMessage($item['id']);
            plog('order.op.send', "订单发货 ID: {$item['id']} 订单号: {$item['ordersn']} <br/>快递公司: {$_GPC['expresscom']} 快递单号: {$_GPC['expresssn']}");
            show_json(1);
        }
        $address = iunserializer($item['address']);
        if (!is_array($address)) {
            //readytodo
            $address = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $item['addressid'], ':uniacid' => $_W['uniacid']));
        }
        $express_list = m('express')->getExpressList();

        include $this->template();
    }

    function remarksaler() {
        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if ($_W['ispost']) {
            pdo_update('ewei_shop_order', array('remarksaler' => $_GPC['remark']), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));
            plog('order.op.remarksaler', "订单备注 ID: {$item['id']} 订单号: {$item['ordersn']} 备注内容: " . $_GPC['remark']);
            show_json(1);
        }
        include $this->template();
    }

    function changeexpress() {
        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        $edit_flag = 1;
        if ($_W['ispost']) {

            $express = $_GPC['express'];
            $expresscom = $_GPC['expresscom'];
            $expresssn = trim($_GPC['expresssn']);

            if (empty($id)) {
                $ret = "参数错误！";
                show_json(0, $ret);
            }

            if (!empty($expresssn)) {
                $change_data = array();
                $change_data['express'] = $express;
                $change_data['expresscom'] = $expresscom;
                $change_data['expresssn'] = $expresssn;

                pdo_update('ewei_shop_order', $change_data, array('id' => $id, 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));

                plog('order.op.changeexpress', "修改快递状态 ID: {$item['id']} 订单号: {$item['ordersn']} 快递公司: {$expresscom} 快递单号: {$expresssn}");

                show_json(1);
            } else {
                show_json(0, "请填写快递单号！");
            }
        }

        $address = iunserializer($item['address']);
        if (!is_array($address)) {
            $address = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $item['addressid'], ':uniacid' => $_W['uniacid']));
        }

        $express_list = m('express')->getExpressList();

        include $this->template('order/op/send');
    }

    function changeaddress() {
        global $_W, $_GPC;
        $opdata = $this->opData();
        extract($opdata);

        if (empty($item['addressid'])) {
            $user = unserialize($item['carrier']);
        } else {
            $user = iunserializer($item['address']);

            if (!is_array($user)) {
                //readytodo
                $user = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $item['addressid'], ':uniacid' => $_W['uniacid']));
            }
            $address_info = $user['address'];
            $user['address'] = $user['province'] . ' ' . $user['city'] . ' ' . $user['area'] . ' ' . $user['address'];
            $item['addressdata'] =$oldaddress = array(
                'realname' => $user['realname'],
                'mobile' => $user['mobile'],
                'address' => $user['address'],
            );
        }

        if ($_W['ispost']) {
            $realname = $_GPC['realname'];
            $mobile = $_GPC['mobile'];
            $province = $_GPC['province'];
            $city = $_GPC['city'];
            $area = $_GPC['area'];
            $address = trim($_GPC['address']);

            if (!empty($id)) {
                if (empty($realname)) {
                    $ret = "请填写收件人姓名！";
                    show_json(0, $ret);
                }

                if (empty($mobile)) {
                    $ret = "请填写收件人手机！";
                    show_json(0, $ret);
                }

                if ($province == '请选择省份') {
                    $ret = "请选择省份！";
                    show_json(0, $ret);
                }

                if (empty($address)) {
                    $ret = "请填写详细地址！";
                    show_json(0, $ret);
                }

                $item = pdo_fetch("SELECT id, ordersn, address FROM " . tablename('ewei_shop_order') . " WHERE id = :id and uniacid=:uniacid and merchid = :merchid", array(':id' => $id, ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));
                $address_array = iunserializer($item['address']);

                $address_array['realname'] = $realname;
                $address_array['mobile'] = $mobile;
                $address_array['province'] = $province;
                $address_array['city'] = $city;
                $address_array['area'] = $area;
                $address_array['address'] = $address;
                $address_array = iserializer($address_array);

                pdo_update('ewei_shop_order', array('address' => $address_array), array('id' => $id, 'uniacid' => $_W['uniacid'], 'merchid' => $_W['merchid']));

                plog('order.op.changeaddress', "修改收货地址 ID: {$item['id']} 订单号: {$item['ordersn']} <br>原地址: 收件人: {$oldaddress['realname']} 手机号: {$oldaddress['mobile']} 收件地址: {$oldaddress['address']}<br>新地址: 收件人: {$realname} 手机号: {$mobile} 收件地址: {$province} {$city} {$area} {$address}");

                show_json(1);
            }
        }
        include $this->template();
    }
}
