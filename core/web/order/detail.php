<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Detail_EweiShopV2Page extends WebPage {

    function main() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        $p = p('commission');
        $sql="SELECT o.*,de.if_declare,de.if_customs FROM " . tablename('ewei_shop_order') . " as o "
        . " left join " . tablename('ewei_shop_depot') . " as de on de.id =o.depotid "
        ." WHERE o.id = :id and o.uniacid=:uniacid";

        $item = pdo_fetch($sql, array(':id' => $id, ':uniacid' => $_W['uniacid']));
        //var_dmp();
        $item['statusvalue'] = $item['status'];
        $item['paytypevalue'] = $item['paytype'];
        $secndpay=pdo_fetch("select * from ".tablename("ewei_shop_order_dispay")." where order_id=:orderid and status=2 order by id desc",array(":orderid"=>$item['id']));
        $shopset = m('common')->getSysset('shop');
        if (empty($item)) {

            $this->message("抱歉，订单不存在!", referer(), "error");
        }

        if ($_W['ispost']) {
            pdo_update(
                'ewei_shop_order', array(
                'remark' => trim($_GPC['remark']),
            ), array('id' => $item['id'], 'uniacid' => $_W['uniacid'])
            );
            plog('order.op.remarksaler', "订单保存备注  ID: {$item['id']} 订单号: {$item['ordersn']}");
            $this->message('订单备注保存成功！', webUrl('order', array('op' => 'detail', 'id' => $item['id'])), 'success');
        }

        $member = m('member')->getMember($item['openid']);
        $dispatch = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_dispatch') . " WHERE id = :id and uniacid=:uniacid and merchid=0", array(':id' => $item['dispatchid'], ':uniacid' => $_W['uniacid']));
        if (empty($item['addressid'])) {
            $user = unserialize($item['carrier']);
        } else {

            $user = iunserializer($item['address']);

            if (!is_array($user)) {
                $user = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $item['addressid'], ':uniacid' => $_W['uniacid']));
            }
            $address_info = $user['address'];
            $user['address'] = $user['province'] . ' ' . $user['city'] . ' ' . $user['area'] . ' ' . $user['address'];
            $item['addressdata'] = array(
                'realname' => $user['realname'],
                'mobile' => $user['mobile'],
                'address' => $user['address'],
            );
        }

        $refund = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order_refund') . " WHERE orderid = :orderid and uniacid=:uniacid order by id desc", array(':orderid' => $item['id'], ':uniacid' => $_W['uniacid']));

        $diyformfields = "";

        if (p('diyform')) {
            $diyformfields = ",o.diyformfields,o.diyformdata";
        }
        //wsq
        $goods = pdo_fetchall("SELECT g.*, o.pricetaxrate,o.taxconsumption,o.goodssn as option_goodssn, o.productsn as option_productsn,o.total,g.type,o.optionname,o.optionid,o.price as orderprice,o.realprice,o.changeprice,o.oldprice,o.commission1,o.commission2,o.commission3,o.commissions,o.seckill,o.seckill_taskid,o.seckill_roomid{$diyformfields} FROM " . tablename('ewei_shop_order_goods') .
            " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id "
            . " WHERE o.orderid=:orderid and o.uniacid=:uniacid", array(':orderid' => $id, ':uniacid' => $_W['uniacid']));
        $is_merch = false;
        foreach ($goods as &$r) {
            $r['seckill_task'] = false;
            if($r['seckill']){

                $r['seckill_task']  = plugin_run('seckill::getTaskInfo',$r['seckill_taskid']);
                $r['seckill_room']  = plugin_run('seckill::getRoomInfo',$r['seckill_taskid'],$r['seckill_roomid']);
            }

            if (!empty($r['option_goodssn'])) {
                $r['goodssn'] = $r['option_goodssn'];
            }
            if (!empty($r['option_productsn'])) {
                $r['productsn'] = $r['option_productsn'];
            }
            $r['marketprice'] = $r['orderprice']/$r['total'];
            if (p('diyform')) {
                $r['diyformfields'] = iunserializer($r['diyformfields']);
                $r['diyformdata'] = iunserializer($r['diyformdata']);
            }
            if (!empty($r['merchid'])){
                $is_merch = true;
            }
        }
        unset($r);

        $item['goods'] = $goods;
        $agents = array();
        if ($p) {
            $agents = $p->getAgents($id);

            $m1 = isset($agents[0]) ? $agents[0] : false;
            $m2 = isset($agents[1]) ? $agents[1] : false;
            $m3 = isset($agents[2]) ? $agents[2] : false;
            $commission1 = 0;
            $commission2 = 0;
            $commission3 = 0;
            foreach ($goods as &$og) {

                $oc1 = 0;
                $oc2 = 0;
                $oc3 = 0;
                $commissions = iunserializer($og['commissions']);
                if (!empty($m1)) {
                    if (is_array($commissions)) {
                        $oc1 = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
                    } else {
                        $c1 = iunserializer($og['commission1']);
                        $l1 = $p->getLevel($m1['openid']);
                        $oc1 = isset($c1['level' . $l1['id']]) ? $c1['level' . $l1['id']] : $c1['default'];
                    }
                    $og['oc1'] = $oc1;
                    $commission1+= $oc1;
                }
                if (!empty($m2)) {
                    if (is_array($commissions)) {
                        $oc2 = isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
                    } else {
                        $c2 = iunserializer($og['commission2']);
                        $l2 = $p->getLevel($m2['openid']);
                        $oc2 = isset($c2['level' . $l2['id']]) ? $c2['level' . $l2['id']] : $c2['default'];
                    }
                    $og['oc2'] = $oc2;
                    $commission2+= $oc2;
                }
                if (!empty($m3)) {
                    if (is_array($commissions)) {
                        $oc3 = isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
                    } else {
                        $c3 = iunserializer($og['commission3']);
                        $l3 = $p->getLevel($m3['openid']);
                        $oc3 = isset($c3['level' . $l3['id']]) ? $c3['level' . $l3['id']] : $c3['default'];
                    }
                    $og['oc3'] = $oc3;
                    $commission3+= $oc3;
                }
            }
            unset($og);

            $commission_array = array(0 => $commission1, 1 => $commission2, 2 => $commission3);
            foreach ($agents as $key => $value) {
                $agents[$key]['commission'] = $commission_array[$key];
                if ($key > 2) {
                    unset($agents[$key]);
                }
            }
        }
        $condition = " o.uniacid=:uniacid and o.deleted=0";
        $paras = array(':uniacid' => $_W['uniacid']);
        $totals = array();

        $coupon = false;
        if (com('coupon') && !empty($item['couponid'])) {
            $coupon = com('coupon')->getCouponByDataID($item['couponid']);
        }

        $order_fields = false;
        $order_data = false;
        if (p('diyform')) {
            $diyform_set = p('diyform')->getSet();
            foreach ($goods as $g) {
                if (!empty($g['diyformdata'])) {
                    break;
                }
            }
            //订单统一模板
            if (!empty($item['diyformid'])) {
                $orderdiyformid = $item['diyformid'];
                if (!empty($orderdiyformid)) {
                    $order_fields = iunserializer($item['diyformfields']);
                    $order_data = iunserializer($item['diyformdata']);
                }
            }
        }
        if (com('verify')) {
            $verifyinfo = iunserializer($item['verifyinfo']);

            if (!empty($item['verifyopenid'])) {
                $saler = m('member')->getMember($item['verifyopenid']);
                $saler['salername'] = pdo_fetchcolumn('select salername from ' . tablename('ewei_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1 ', array(':uniacid' => $_W['uniacid'], ':openid' => $item['verifyopenid']));
            }
            if (!empty($item['verifystoreid'])) {
                $store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:storeid limit 1 ', array(':storeid' => $item['verifystoreid']));
            }

            //核销
            if ($item['isverify']) {
                if (is_array($verifyinfo)) {
                    if (empty($item['dispatchtype']))
                    {
                        foreach ($verifyinfo as &$v) {
                            if ($v['verified'] || $item['verifytype']==1) {
                                $v['storename'] = pdo_fetchcolumn('select storename from ' . tablename('ewei_shop_store') . " where id=:id limit 1", array(':id' => $v['verifystoreid']));
                                if (empty($v['storename'])) {
                                    $v['storename'] = "总店";
                                }
                                $v['nickname'] = pdo_fetchcolumn('select nickname from ' . tablename('ewei_shop_member') . " where openid=:openid and uniacid=:uniacid limit 1", array(':openid' => $v['verifyopenid'], ':uniacid' => $_W['uniacid']));
                                $v['salername'] = pdo_fetchcolumn('select salername from ' . tablename('ewei_shop_saler') . " where openid=:openid and uniacid=:uniacid limit 1", array(':openid' => $v['verifyopenid'], ':uniacid' => $_W['uniacid']));
                            }
                        }
                        unset($v);
                    }

                }
            }
        }
        load()->func('tpl');
        include $this->template();
        exit;
    }

}
