<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';

class Pay_EweiShopV2Page extends AppMobilePage
{

    function main()
    {
        global $_W, $_GPC;
        $openid = $_W['openid'];

        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);
        $orderid = intval($_GPC['id']);

        if (empty($orderid)) {
            app_error(AppError::$ParamsError);
        }
        $order = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
        if (empty($order)) {
            app_error(AppError::$OrderNotFound);
            exit;
        }
        if ($order['status'] == -1) {
            app_error(AppError::$OrderCannotPay);
        } else if ($order['status'] >= 1) {
            app_error(AppError::$OrderAlreadyPay);
        }

        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'ewei_shopv2', ':tid' => $order['ordersn']));
        if (!empty($log) && $log['status'] != '0') {
            app_error(AppError::$OrderAlreadyPay);
        }

        if (!empty($log) && $log['status'] == '0') {
            pdo_delete('core_paylog', array('plid' => $log['plid']));
            $log = null;
        }


        if (empty($log)) {
            $log = array(
                'uniacid' => $uniacid,
                'openid' => $member['uid'],
                'module' => "ewei_shopv2",
                'tid' => $order['ordersn'],
                'fee' => $order['price'],
                'status' => 0,
            );
            pdo_insert('core_paylog', $log);
            $plid = pdo_insertid();
        }

        $set = m('common')->getSysset(array('shop', 'pay'));
        $set['pay']['weixin'] = !empty($set['pay']['weixin_sub']) ? 1 : $set['pay']['weixin'];
        $set['pay']['weixin_jie'] = !empty($set['pay']['weixin_jie_sub']) ? 1 : $set['pay']['weixin_jie'];
        $param_title = $set['shop']['name'] . "订单";

        //是否可以余额支付
        $credit = array('success' => false);
        if (isset($set['pay']) && $set['pay']['credit'] == 1) {
            $credit = array(
                'success' => true,
                'current' => $member['credit2']
            );
        }

        //支付参数
        load()->model('payment');
        $setting = uni_setting($_W['uniacid'], array('payment'));

        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);

        //微信
        $wechat = array('success' => false);
        $jie = intval($_GPC['jie']);
        if (is_weixin()) {
            //微信环境

            $params = array();
            $params['tid'] = $log['tid'];
            if (!empty($order['ordersn2'])) {
                $var = sprintf("%02d", $order['ordersn2']);
                $params['tid'] .= "GJ" . $var;
            }
            $params['user'] = $openid;
            $params['fee'] = $order['price'];
            $params['title'] = $param_title;

            if (isset($set['pay']) && $set['pay']['weixin'] == 1 && $jie !== 1) {
                //如果开启微信支付
                $options = array();
                if (is_array($setting['payment']['wechat']) && $setting['payment']['wechat']['switch']) {
                    load()->model('payment');
                    $setting = uni_setting($_W['uniacid'], array('payment'));
                    if (is_array($setting['payment'])) {
                        $options = $setting['payment']['wechat'];
                        $options['appid'] = $_W['account']['key'];
                        $options['secret'] = $_W['account']['secret'];
                    }
                }
                $wechat = m('common')->wechat_build($params, $options, 0);
                if (!is_error($wechat)) {
                    $wechat['success'] = true;
                    $wechat['weixin'] = true;
                }
            }

            if ((isset($set['pay']) && $set['pay']['weixin_jie'] == 1 && !$wechat['success']) || $jie === 1) {
                //如果开启微信支付

                if (!empty($order['ordersn2'])) {
                    $params['tid'] = $params['tid'] . '_B';
                } else {
                    $params['tid'] = $params['tid'] . '_borrow';
                }

                $options = array();
                $options['appid'] = $sec['appid'];
                $options['mchid'] = $sec['mchid'];
                $options['apikey'] = $sec['apikey'];

                if (!empty($set['pay']['weixin_jie_sub']) && !empty($sec['sub_secret_jie_sub'])){
                    $wxuser = m('member')->wxuser($sec['sub_appid_jie_sub'],$sec['sub_secret_jie_sub']);
                    $params['openid'] = $wxuser['openid'];
                }elseif(!empty($sec['secret'])){
                    $wxuser = m('member')->wxuser($sec['appid'],$sec['secret']);
                    $params['openid'] = $wxuser['openid'];
                }

                $wechat = m('common')->wechat_native_build($params, $options, 0);
                if (!is_error($wechat)) {
                    $wechat['success'] = true;
                    if (!empty($params['openid'])){
                        $wechat['weixin'] = true;
                    }else{
                        $wechat['weixin_jie'] = true;
                    }
                }
            }
            $wechat['jie'] = $jie;
        }

        //支付宝
        $alipay = array('success' => false);
        if (isset($set['pay']) && $set['pay']['alipay'] == 1) {
            //如果开启支付宝
            if (is_array($setting['payment']['alipay']) && $setting['payment']['alipay']['switch']) {

                $params = array();
                $params['tid'] = $log['tid'];
                $params['user'] = $_W['openid'];
                $params['fee'] = $order['price'];
                $params['title'] = $param_title;

                load()->func('communication');
                load()->model('payment');
                $setting = uni_setting($_W['uniacid'], array('payment'));
                if (is_array($setting['payment'])) {
                    $options = $setting['payment']['alipay'];
                    $alipay = m('common')->alipay_build($params, $options, 0, $_W['openid']);
                    if (!empty($alipay['url'])) {
                        $alipay['url'] = urlencode($alipay['url']);
                        $alipay['success'] = true;
                    }
                }
            }
        }
        //货到付款
        $cash = array('success' => $order['cash'] == 1 && isset($set['pay']) && $set['pay']['cash'] == 1 && $order['isverify'] == 0 && $order['isvirtual'] == 0);

        $payinfo = array(
            'orderid' => $orderid,
            'credit' => $credit,
            'alipay' => $alipay,
            'wechat' => $wechat,
            'cash' => $cash,
            'money' => $order['price']
        );
//
//        if(is_h5app()){
//            $payinfo = array(
//                'wechat' => !empty($sec['app_wechat']['merchname']) && !empty($set['pay']['app_wechat']) && !empty($sec['app_wechat']['appid']) && !empty($sec['app_wechat']['appsecret']) && !empty($sec['app_wechat']['merchid']) && !empty($sec['app_wechat']['apikey']) && $order['price']>0 ? true : false,
//                'alipay' => !empty($set['pay']['app_alipay']) && !empty($sec['app_alipay']['public_key']) ? true : false,
//                'mcname' => $sec['app_wechat']['merchname'],
//                'aliname' => empty($_W['shopset']['shop']['name']) ? $sec['app_wechat']['merchname'] : $_W['shopset']['shop']['name'],
//                'ordersn' => $log['tid'],
//                'money' => $order['price'],
//                'attach' => $_W['uniacid'] . ":0",
//                'type' => 0,
//
//                'orderid'=>$orderid,
//                'credit'=>$credit,
//                'cash'=>$cash
//            );
//        }


        app_json(array(
            'order' => array(
                'id' => $order['id'],
                'ordersn' => $order['ordersn'],
                'price' => $order['price'],
            ),
            'credit' => $credit,
            'alipay' => $alipay,
            'wechat' => $wechat,
        ));


    }


    function complete()
    {

        global $_W, $_GPC;
        $orderid = intval($_GPC['id']);
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];

//        if(is_h5app() && empty($orderid)){
//            $ordersn = $_GPC['ordersn'];
//            $orderid = pdo_fetchcolumn("select id from " . tablename('ewei_shop_order') . ' where ordersn=:ordersn and uniacid=:uniacid and openid=:openid limit 1', array(':ordersn' => $ordersn, ':uniacid' => $uniacid, ':openid' => $openid));
//        }

        if (empty($orderid)) {
            app_error(AppError::$ParamsError);
        }

        $set = m('common')->getSysset(array('shop', 'pay'));
        $set['pay']['weixin'] = !empty($set['pay']['weixin_sub']) ? 1 : $set['pay']['weixin'];
        $set['pay']['weixin_jie'] = !empty($set['pay']['weixin_jie_sub']) ? 1 : $set['pay']['weixin_jie'];
        $member = m('member')->getMember($openid, true);

        $order = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));

        if (empty($order)) {
            app_error(AppError::$OrderNotFound); 
        }
        if (!empty($order['status'])) {
            app_error(AppError::$OrderAlreadyPay);
        }


        $type = $_GPC['type'];

        if (!in_array($type, array('wechat', 'alipay', 'credit', 'cash'))) {
            app_error(AppError::$OrderPayNoPayType);
        }


        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'ewei_shopv2', ':tid' => $order['ordersn']));
        if (empty($log)) {
           app_error(AppError::$OrderPayFail);
        }

        $order_goods = pdo_fetchall('select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf from  ' . tablename('ewei_shop_order_goods') . ' og '
            . ' left join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id '
            . ' where og.orderid=:orderid and og.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));


        foreach ($order_goods as $data) {
            if (empty($data['status']) || !empty($data['deleted'])) {
                app_error(AppError::$OrderPayFail, $data['title'] . '<br/> 已下架!');
            }

            $unit = empty($data['unit']) ? '件' : $data['unit'];

            //最低购买
            if ($data['minbuy'] > 0) {
                if ($data['buycount'] < $data['minbuy']) {
                    app_error(AppError::$OrderCreateMinBuyLimit, $data['title'] . '<br/> ' . $data['min'] . $unit . "起售!");
                }
            }

            //一次购买
            if ($data['maxbuy'] > 0) {
                if ($data['buycount'] > $data['maxbuy']) {
                    app_error(AppError::$OrderCreateOneBuyLimit, $data['title'] . '<br/> 一次限购 ' . $data['maxbuy'] . $unit . "!");
                }
            }
            //总购买量
            if ($data['usermaxbuy'] > 0) {
                $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('ewei_shop_order_goods') . ' og '
                    . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
                    . ' where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $data['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));
                if ($order_goodscount >= $data['usermaxbuy']) {
                    app_error(AppError::$OrderCreateMaxBuyLimit, $data['title'] . '<br/> 最多限购 ' . $data['usermaxbuy'] . $unit);
                }
            }

            //判断限时购
            if ($data['istime'] == 1) {
                if (time() < $data['timestart']) {
                    app_error(AppError::$OrderCreateTimeNotStart, $data['title'] . '<br/> 限购时间未到!');
                }
                if (time() > $data['timeend']) {
                    app_error(AppError::$OrderCreateTimeEnd, $data['title'] . '<br/> 限购时间已过!');
                }
            }
            //判断会员权限
            if ($data['buylevels'] != '') {
                $buylevels = explode(',', $data['buylevels']);
                if (!in_array($member['level'], $buylevels)) {
                    app_error(AppError::$OrderCreateMemberLevelLimit, '您的会员等级无法购买<br/>' . $data['title'] . '!');
                }
            }

            //会员组权限
            if ($data['buygroups'] != '') {
                $buygroups = explode(',', $data['buygroups']);
                if (!in_array($member['groupid'], $buygroups)) {
                    app_error(AppError::$OrderCreateMemberGroupLimit, '您所在会员组无法购买<br/>' . $data['title'] . '!');
                }
            }

            if ($data['totalcnf'] == 1) {
                if (!empty($data['optionid'])) {
                    $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,stock,`virtual` from ' . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $data['goodsid'], ':id' => $data['optionid']));
                    if (!empty($option)) {
                        if ($option['stock'] != -1) {
                            if (empty($option['stock'])) {
                                app_error(AppError::$OrderCreateStockError, $data['title'] . "<br/>" . $option['title'] . " 库存不足!");
                            }
                        }
                    }
                } else {
                    if ($data['stock'] != -1) {
                        if (empty($data['stock'])) {
                            app_error(AppError::$OrderCreateStockError, $data['title'] . "<br/>" . $option['title'] . " 库存不足!");
                        }
                    }
                }
            }
        }


        //货到付款
        if ($type == 'cash') {

            //判断是否开启货到付款
            if (empty($set['pay']['cash'])) {
                app_error(AppError::$OrderPayFail, "未开启货到付款");
            }

            m('order')->setOrderPayType($order['id'], 3);

            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = 'cash';
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $order['openid'];
            $ret['fee'] = $order['price'];
            $ret['weid'] = $_W['uniacid'];
            $ret['uniacid'] = $_W['uniacid'];
            $pay_result = m('order')->payResult($ret);


            $this->success($orderid);
        }


        $ps = array();
        $ps['tid'] = $log['tid'];
        $ps['user'] = $openid;
        $ps['fee'] = $log['fee'];
        $ps['title'] = $log['title'];


        //余额支付
        if ($type == 'credit') {


            //判断是否开启余额支付
            if (empty($set['pay']['credit']) && $ps['fee'] > 0) {
                app_error(AppError::$OrderPayFail, "未开启余额支付");
            }

            if ($ps['fee'] < 0) {
                app_error(AppError::$OrderPayFail, "金额错误");
            }

            $credits = m('member')->getCredit($openid, 'credit2');

            if ($credits < $ps['fee']) {

                app_error(AppError::$OrderPayFail, "余额不足,请充值");
            }
            $fee = floatval($ps['fee']);

            $shopset = m('common')->getSysset('shop');
            $result = m('member')->setCredit($openid, 'credit2', -$fee, array($_W['member']['uid'], $shopset['name']. 'APP 消费' . $fee));
            if (is_error($result)) {
                app_error(AppError::$OrderPayFail, $result['message']);
            }
            $record = array();
            $record['status'] = '1';
            $record['type'] = 'cash';
            pdo_update('core_paylog', $record, array('plid' => $log['plid']));


            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = $log['type'];
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['weid'] = $log['weid'];
            $ret['uniacid'] = $log['uniacid'];
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            $pay_result = m('order')->payResult($ret);

            m('order')->setOrderPayType($order['id'], 1);

            $this->success($orderid);

        } else if ($type == 'wechat') {

            //判断是否开启微信支付
//            if (!is_weixin() && empty($_W['shopset']['wap']['open'])) {
//                if($_W['ispost']) {
//                    show_json(0, is_h5app() ? "APP正在维护" : '非微信环境!');
//                }else{
//                    $this->message(is_h5app() ? "APP正在维护" : '非微信环境!', mobileUrl('order'));
//                }
//            }
            if ((empty($set['pay']['weixin']) && is_weixin()) || (empty($_W['shopset']['wap']['payment']['wechat']) && is_h5app())) {

                app_error(AppError::$OrderPayFail, "未开启微信支付");

            }

            $ordersn = $order['ordersn'];

            if (!empty($order['ordersn2'])) {
                $ordersn .= "GJ" . sprintf("%02d", $order['ordersn2']);
            }
            $payquery = m('finance')->isWeixinPay($ordersn, $order['price'], is_h5app() ? true : false);

            if (!is_error($payquery)) {

                //微信支付
                $record = array();
                $record['status'] = '1';
                $record['type'] = 'wechat';
                pdo_update('core_paylog', $record, array('plid' => $log['plid']));

                $ret = array();
                $ret['result'] = 'success';
                $ret['type'] = 'wechat';
                $ret['from'] = 'return';
                $ret['tid'] = $log['tid'];
                $ret['user'] = $log['openid'];
                $ret['fee'] = $log['fee'];
                $ret['weid'] = $log['weid'];
                $ret['uniacid'] = $log['uniacid'];
                $ret['deduct'] = intval($_GPC['deduct']) == 1;
                $pay_result = m('order')->payResult($ret);
                @session_start();
                $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;

                m('order')->setOrderPayType($order['id'], 21);

                //if(is_h5app()){
                pdo_update('ewei_shop_order', array('apppay' => 2), array('id' => $order['id']));
                //}

                $this->success($orderid);
            }
            app_error(AppError::$OrderPayFail);
        }
    }


    protected function success($orderid)
    {

        global $_W, $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);

        if (empty($orderid)) {
            app_error(AppError::$ParamsError);
        }

        $order = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
        $merchid = $order['merchid'];
        //商品
        $goods = pdo_fetchall("select og.goodsid,og.price,g.title,g.thumb,og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,g.storeids from " . tablename('ewei_shop_order_goods') . " og "
            . " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
            . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(':uniacid' => $uniacid, ':orderid' => $orderid));

        //地址
        $address = false;
        if (!empty($order['addressid'])) {
            $address = iunserializer($order['address']);
            if (!is_array($address)) {
                $address = pdo_fetch('select * from  ' . tablename('ewei_shop_member_address') . ' where id=:id limit 1', array(':id' => $order['addressid']));
            }
        }

        //联系人
        $carrier = @iunserializer($order['carrier']);
        if (!is_array($carrier) || empty($carrier)) {
            $carrier = false;
        }

        //自提点
        $store = false;
        if (!empty($order['storeid'])) {
            if ($merchid > 0) {
                $store = pdo_fetch('select * from  ' . tablename('ewei_shop_merch_store') . ' where id=:id limit 1', array(':id' => $order['storeid']));
            } else {
                $store = pdo_fetch('select * from  ' . tablename('ewei_shop_store') . ' where id=:id limit 1', array(':id' => $order['storeid']));
            }
        }

        //核销门店
        $stores = false;
        if ($order['isverify']) {
            //核销单
            $storeids = array();
            foreach ($goods as $g) {
                if (!empty($g['storeids'])) {
                    $storeids = array_merge(explode(',', $g['storeids']), $storeids);
                }
            }
            if (empty($storeids)) {
                //全部门店
                if ($merchid > 0) {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                } else {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
                }
            } else {
                if ($merchid > 0) {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                } else {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
                }
            }
        }

        $text = "";

        if (!empty($address)) {
            $text = "您的包裹整装待发";
        }

        if (!empty($order['dispatchtype']) && empty($order['isverify'])) {
            $text = "您可以到您选择的自提点取货了";
        }
        if (!empty($order['isverify'])) {
            $text = "您可以到适用门店去使用了";
        }
        if (!empty($order['virtual'])) {
            $text = "您购买的商品已自动发货";
        }

        if (!empty($order['isvirtual']) && empty($order['virtual'])) {
            if (!empty($order['isvirtualsend'])) {
                $text = "您购买的商品已自动发货";
            } else {
                $text = "您已经支付成功";
            }
        }

        app_json(array(

            'order' => array(
                'id' => $orderid,
                'isverify' => $order['isverify'],
                'virtual' => $order['virtual'],
                'isvirtual' => $order['isvirtual'],
                'isvirtualsend' => $order['isvirtualsend'],
                'status' => $order['paytype'] == 3 ? "订单提交支付" : "订单支付成功",
                'text'=>$text

            ),
            'paytype'=>$order['paytype']==3?'需到付':'实付金额',
            'carrier'=>$carrier,
            'address' => $address,
            'stores'=>$stores,
            'store'=>$store
        ));

    }

    protected function str($str)
    {
        $str = str_replace('"', '', $str);
        $str = str_replace("'", '', $str);
        return $str;
    }
}
