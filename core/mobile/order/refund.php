<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Refund_EweiShopV2Page extends MobileLoginPage {

    protected function globalData() {
        global $_W, $_GPC;
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];
        $orderid = intval($_GPC['id']);
        $order = pdo_fetch("select id,status,price,refundid,goodsprice,dispatchprice,deductprice,deductcredit2,finishtime,isverify,`virtual`,refundstate,merchid from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
        if (empty($order)) {
            if (!$_W['isajax']) {
                header('location: ' . mobileUrl('order'));
                exit;
            } else {
                show_json(0, '订单未找到');
            }
        }

        $_err = '';
        if ($order['status'] == 0) {
            $_err = '订单未付款，不能申请退款!';
        } else {
            if ($order['status'] == 3) {
                if (!empty($order['virtual']) || $order['isverify'] == 1) {
                    $_err = '此订单不允许退款!';
                } else {
                    if ($order['refundstate'] == 0) {
                        //申请退款
                        $tradeset = m('common')->getSysset('trade');
                        $refunddays = intval($tradeset['refunddays']);
                        if ($refunddays > 0) {
                            $days = intval((time() - $order['finishtime']) / 3600 / 24);
                            if ($days > $refunddays) {
                                $_err = '订单完成已超过 ' . $refunddays . ' 天, 无法发起退款申请!';
                            }
                        } else {
                            $_err = '订单完成, 无法申请退款!';
                        }
                    }
                }
            }
        }

        if (!empty($_err)) {
            if ($_W['isajax']) {
                show_json(0, $_err);
            } else {
                $this->message($_err, '', 'error');
            }
        }


        //订单不能退货商品

        /*********************************************************************/
        $order['cannotrefund'] = false;

        if($order['status']==2){
            $goods = pdo_fetchall("select og.goodsid, og.price, og.total, og.optionname, g.cannotrefund, g.thumb, g.title from".tablename("ewei_shop_order_goods") ." og left join ".tablename("ewei_shop_goods")." g on g.id=og.goodsid where og.orderid=".$order['id']);
            if(!empty($goods)){
                foreach ($goods as $g){
                    if($g['cannotrefund']==1){
                        $order['cannotrefund'] = true;
                        break;
                    }
                }
            }
        }

        if($order['cannotrefund']){
            show_json(0, "此订单不可退换货");
        }



        //应该退的钱 在线支付的+积分抵扣的+余额抵扣的(运费包含在在线支付或余额里）
        $order['refundprice'] = $order['price'] + $order['deductcredit2'];
        if ($order['status'] >= 2) {
            //如果发货，扣除运费
            $order['refundprice']-= $order['dispatchprice'];
        }
        $order['refundprice'] = round($order['refundprice'],2);

        return array('uniacid' => $uniacid, 'openid' => $_W['openid'], 'orderid' => $orderid, 'order' => $order, 'refundid' => $order['refundid']);
    }

    function main() {

        global $_W, $_GPC;
        extract($this->globalData());
        if ( $order['status'] == '-1')
            $this->message('请不要重复提交!','','error');
        $refund = false;
        $imgnum = 0;
        if ($order['refundstate'] > 0) {
            if (!empty($refundid)) {
                $refund = pdo_fetch("select * from " . tablename('ewei_shop_order_refund') . ' where id=:id and uniacid=:uniacid and orderid=:orderid limit 1'
                    , array(':id' => $refundid, ':uniacid' => $uniacid, ':orderid' => $orderid));
                if (!empty($refund['refundaddress'])) {
                    $refund['refundaddress'] = iunserializer($refund['refundaddress']);
                }
            }
            if (!empty($refund['imgs'])) {
                $refund['imgs'] = iunserializer($refund['imgs']);
            }
        }

        if (empty($refund)) {
            $show_price =round( $order['refundprice'],2);
        } else {
            $show_price = round($refund['applyprice'],2);
        }

        $express_list = m('express')->getExpressList();

        include $this->template();
    }

    //提交
    function submit() {

        global $_W, $_GPC;
        extract($this->globalData());
        if ( $order['status'] == '-1')
            show_json(0, '订单已经处理完毕!');
        $price = trim($_GPC['price']);
        $rtype = intval($_GPC['rtype']);
        if ($rtype != 2) {
            if (empty($price) && $order['deductprice'] == 0) {
                show_json(0, '退款金额不能为0元');
            }
            if ($price > $order['refundprice']) {
                show_json(0, '退款金额不能超过' . $order['refundprice'] . '元');
            }
        }
        $refund = array(
            'uniacid' => $uniacid,
            'merchid' => $order['merchid'],
            'applyprice' => $price,
            'rtype' => $rtype,
            'reason' => trim($_GPC['reason']),
            'content' => trim($_GPC['content']),
            'imgs' => iserializer($_GPC['images'])
        );

        if ($refund['rtype'] == 2) {
            $refundstate = 2;
        } else {
            $refundstate = 1;
        }
        if ($order['refundstate'] == 0) {
            //新建一条退款申请
            $refund['createtime'] = time();
            $refund['orderid'] = $orderid;
            $refund['orderprice'] = $order['refundprice'];
            $refund['refundno'] = m('common')->createNO('order_refund', 'refundno', 'SR');
            pdo_insert('ewei_shop_order_refund', $refund);
            $refundid = pdo_insertid();
            pdo_update('ewei_shop_order', array('refundid' => $refundid, 'refundstate' => $refundstate), array('id' => $orderid, 'uniacid' => $uniacid));
        } else {
            //修改退款申请
            pdo_update('ewei_shop_order', array('refundstate' => $refundstate), array('id' => $orderid, 'uniacid' => $uniacid));
            pdo_update('ewei_shop_order_refund', $refund, array('id' => $refundid, 'uniacid' => $uniacid));
        }
        //模板消息
        m('notice')->sendOrderMessage($orderid, true);
        show_json(1);
    }

    //取消
    function cancel() {

        global $_W, $_GPC;
        extract($this->globalData());
        $change_refund = array();
        $change_refund['status'] = -2;
        $change_refund['refundtime'] = time();
        pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $refundid, 'uniacid' => $uniacid));
        pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $orderid, 'uniacid' => $uniacid));
        show_json(1);
    }

    //填写快递单号
    function express() {

        global $_W, $_GPC;
        extract($this->globalData());
        if (empty($refundid)) {
            show_json(0, '参数错误!');
        }
        if (empty($_GPC['expresssn'])) {
            show_json(0, '请填写快递单号');
        }
        $refund = array(
            'status'=>4,
            'express'=>trim($_GPC['express']),
            'expresscom'=>trim($_GPC['expresscom']),
            'expresssn'=>trim($_GPC['expresssn']),
            'sendtime'=>time()
        );
        pdo_update('ewei_shop_order_refund', $refund, array('id' => $refundid, 'uniacid' => $uniacid));
        show_json(1);
    }

    //收到换货商品
    function receive(){

        global $_W, $_GPC;
        extract($this->globalData());
        $refundid = intval($_GPC['refundid']);
        $refund =  pdo_fetch("select * from " . tablename('ewei_shop_order_refund') . ' where id=:id and uniacid=:uniacid and orderid=:orderid limit 1'
            , array(':id' => $refundid, ':uniacid' => $uniacid, ':orderid' => $orderid));
        if (empty($refund)) {
            show_json(0, '换货申请未找到!');
        }

        $time = time();
        $refund_data = array();
        $refund_data['status'] = 1;
        $refund_data['refundtime'] = $time;
        pdo_update('ewei_shop_order_refund', $refund_data, array('id'=>$refundid, 'uniacid' => $uniacid));

        $order_data = array();
        $order_data['refundstate'] = 0;
        $order_data['status'] = -1;
        $order_data['refundtime'] = $time;
        pdo_update('ewei_shop_order', $order_data, array('id'=>$orderid, 'uniacid' => $uniacid));
        show_json(1);

    }

    //查询商家重新发货快递
    function refundexpress() {

        global $_W, $_GPC;
        extract($this->globalData());

        $express = trim($_GPC['express']);
        $expresssn = trim($_GPC['expresssn']);
        $expresscom = trim($_GPC['expresscom']);
        $expresslist = m('util')->getExpressList($express, $expresssn);

        include $this->template('order/refundexpress');
    }
}
