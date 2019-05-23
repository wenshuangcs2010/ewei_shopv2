<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Pay_EweiShopV2Page extends MobileLoginPage
{

    function main()
    {
        global $_W, $_GPC;
        $openid = $_W['openid'];

        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);
        $orderid = intval($_GPC['id']);

        $og_array = m('order')->checkOrderGoods($orderid);
        if (!empty($og_array['flag'])) {
            $this->message($og_array['msg'], '', 'error');
        }

        if (empty($orderid)) {
            header('location: ' . mobileUrl('order'));
            exit;
        }
        $order = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
        if (empty($order)) {
            header('location: ' . mobileUrl('order'));
            exit;
        }
        if ($order['status'] == -1) {
            header('location: ' . mobileUrl('order/detail', array('id' => $order['id'])));
            exit;
        } else if ($order['status'] >= 1) {
            header('location: ' . mobileUrl('order/detail', array('id' => $order['id'])));
            exit;
        }

        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'ewei_shopv2', ':tid' => $order['ordersn']));
        if (!empty($log) && $log['status'] != '0') {
            header('location: ' . mobileUrl('order/detail', array('id' => $order['id'])));
            exit;
        }

        //秒杀商品
        $seckill_goods = pdo_fetchall('select goodsid,optionid,seckill from  ' . tablename('ewei_shop_order_goods') . ' where orderid=:orderid and uniacid=:uniacid and seckill=1 ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));


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
        $depot_info=pdo_fetch("select * from ".tablename("ewei_shop_depot")." where id=:depotid",array(":depotid"=>$order['depotid']));
        if (isset($set['pay']) && $set['pay']['credit'] == 1 && $depot_info['isusebalance']==0) {
        //if (isset($set['pay']) && $set['pay']['credit'] == 1) {
            $credit = array(
                'success' => true,
                'current' => $member['credit2']
            );
        }
        //临时特殊控制

        //支付参数
        load()->model('payment');
        $setting = uni_setting($_W['uniacid'], array('payment'));

        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        //检查用户所在分组
        $fcard=array('success' => false);
        $item=m("unit")->checkMember($member['groupid']);
        if($item){
            $isok=m("unit")->checkOrder($openid,$item,$order);
           
            if($isok && is_weixin()){
                $fcard['success']=true;
                $fcard['cardname']=$item['unitname'];
            }
        }
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
            $jearray=Dispage::getDisaccountArray();
            if(in_array($_W['uniacid'], $jearray) && $order['isdisorder']==1){
                $jie = 1;
            }
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
//                if($_W['openid']=="oIeNnwzHrT6vXpiIUss3l5lt_W2w"){
//                    var_dump($options);
//                die();
//                }
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
                if(in_array($_W['uniacid'], $jearray) && $order['isdisorder']==1){
                    load()->model('payment');
                    $setting = uni_setting(DIS_ACCOUNT, array('payment'));
                    if (is_array($setting['payment'])) {
                         $jieweipay = $setting['payment']['wechat'];
                    }
                    $APPID = pdo_fetchcolumn('SELECT `key` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>DIS_ACCOUNT));
                    $secret = pdo_fetchcolumn('SELECT `secret` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>DIS_ACCOUNT));
                    $sec['appid']=$APPID;
                    $sec['secret']=$secret;
                    $sec['sub_appid_jie_sub']=$APPID;
                    $set['pay']['weixin_jie_sub']=1;
                    $sec['sub_secret_jie_sub']=$secret;
                    $options['appid'] = $sec['appid'];
                    $options['mchid'] = $jieweipay['mchid'];
                    $options['apikey'] = $jieweipay['apikey'];
                }
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

       // WeUtility::logging('aaa', var_export($wechat,true));
        $alipay = array('success' => false);
          
            //支付宝
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
                            //if($_W['openid']=="oIeNnwzHrT6vXpiIUss3l5lt_W2w"){

                               // die();
                           // }


                            $alipay['url'] = urlencode($alipay['url']);
                            $alipay['success'] = true;
                        }
                    }
                }
            }
            //货到付款
            $cash = array('success' => $order['cash'] == 1 && isset($set['pay']) && $set['pay']['cash'] == 1 && $order['isverify'] == 0 && $order['isvirtual'] == 0);
        if(empty($seckill_goods)){

        } else{
            $cash = array('success' => false);
        }

        $payinfo = array(
            'orderid' => $orderid,
            'credit' => $credit,
            'alipay' => $alipay,
            'wechat' => $wechat,
            'fcard' => $fcard,
            'cash' => $cash,
            'money' => $order['price']
        );

        if (is_h5app()) {
            $payinfo = array(
                'wechat' => !empty($sec['app_wechat']['merchname']) && !empty($set['pay']['app_wechat']) && !empty($sec['app_wechat']['appid']) && !empty($sec['app_wechat']['appsecret']) && !empty($sec['app_wechat']['merchid']) && !empty($sec['app_wechat']['apikey']) && $order['price'] > 0 ? true : false,
                'alipay' => !empty($set['pay']['app_alipay']) && !empty($sec['app_alipay']['public_key']) ? true : false,
                'mcname' => $sec['app_wechat']['merchname'],
                'aliname' => empty($_W['shopset']['shop']['name']) ? $sec['app_wechat']['merchname'] : $_W['shopset']['shop']['name'],
                'ordersn' => $log['tid'],
                'money' => $order['price'],
                'attach' => $_W['uniacid'] . ":0",
                'type' => 0,

                'orderid' => $orderid,
                'credit' => $credit,
                'cash' => $cash
            );
        }

        if (p('seckill') ) {

            foreach ($seckill_goods as $data) {
                plugin_run("seckill::getSeckill", $data['goodsid'], $data['optionid'], true, $_W['openid']);
            }
        }
        //var_dUMP($payinfo);
        include $this->template();


    }

    function orderstatus()
    {
        global $_W, $_GPC;
        $uniacid = $_W['uniacid'];
        $orderid = intval($_GPC['id']);
        $order = pdo_fetch("select status from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid));
        if ($order['status'] >= 1) {
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            show_json(1);
        }
        show_json(0);
    }

    function complete()
    {

        global $_W, $_GPC;
        $orderid = intval($_GPC['id']);
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];

        if (is_h5app() && empty($orderid)) {
            $ordersn = $_GPC['ordersn'];
            $orderid = pdo_fetchcolumn("select id from " . tablename('ewei_shop_order') . ' where ordersn=:ordersn and uniacid=:uniacid and openid=:openid limit 1', array(':ordersn' => $ordersn, ':uniacid' => $uniacid, ':openid' => $openid));
        }

        if (empty($orderid)) {
            if ($_W['ispost']) {
                show_json(0, '参数错误');
            } else {
                $this->message('参数错误', mobileUrl('order'));
            }
        }

        $set = m('common')->getSysset(array('shop', 'pay'));
        $set['pay']['weixin'] = !empty($set['pay']['weixin_sub']) ? 1 : $set['pay']['weixin'];
        $set['pay']['weixin_jie'] = !empty($set['pay']['weixin_jie_sub']) ? 1 : $set['pay']['weixin_jie'];
        $member = m('member')->getMember($openid, true);

        $order = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));

        //套餐订单
        if ($order['ispackage'] > 0) {
            $package = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_package') . " WHERE uniacid = " . $uniacid . " and id = " . $order['packageid'] . " ");
            if (empty($package)) {
                show_json(0, '未找到套餐！');
            }
            if ($package['starttime'] > time()) {
                show_json(0, '套餐活动未开始，请耐心等待！');
            }
            if ($package['endtime'] < time()) {
                show_json(0, '套餐活动已结束，谢谢您的关注，请您浏览其他套餐或商品！');
            }
        }


        if (empty($order)) {
            if ($_W['ispost']) {
                show_json(0, '订单未找到');
            } else {
                $this->message('订单未找到', mobileUrl('order'));
            }
        }

        $type = $_GPC['type'];

        if (!in_array($type, array('wechat', 'alipay', 'credit', 'cash','fcard'))) {
            if ($_W['ispost']) {
                show_json(0, '未找到支付方式');
            } else {
                $this->message('未找到支付方式', mobileUrl('order'));
            }
        }


        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $uniacid, ':module' => 'ewei_shopv2', ':tid' => $order['ordersn']));
        if (empty($log)) {
            if ($_W['ispost']) {
                show_json(0, '支付出错,请重试!');
            } else {
                $this->message('支付出错,请重试!', mobileUrl('order'));
            }
        }

        $order_goods = pdo_fetchall('select og.id,g.title, og.goodsid,og.optionid,g.total as stock,og.total as buycount,g.status,g.deleted,g.maxbuy,g.usermaxbuy,g.istime,g.timestart,g.timeend,g.buylevels,g.buygroups,g.totalcnf,og.seckill from  ' . tablename('ewei_shop_order_goods') . ' og '
            . ' left join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id '
            . ' where og.orderid=:orderid and og.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));


        foreach ($order_goods as $data) {
            if (empty($data['status']) || !empty($data['deleted'])) {

                if ($_W['ispost']) {
                    show_json(0, $data['title'] . '<br/> 已下架!');
                } else {
                    $this->message($data['title'] . '<br/> 已下架!', mobileUrl('order'));
                }
            }

            $unit = empty($data['unit']) ? '件' : $data['unit'];


            $seckillinfo = plugin_run("seckill::getSeckill", $data['goodsid'], $data['optionid'], true, $_W['openid']);

            if ($data['seckill']) {
                //是秒杀的商品
                if (empty($seckillinfo) || $seckillinfo['status'] != 0 || time() > $seckillinfo['endtime']) {
                    if ($_W['ispost']) {
                        show_json(0, $data['title'] . '<br/> 秒杀已结束，无法支付!');
                    } else {
                        $this->message($data['title'] . '<br/> 秒杀已结束，无法支付!', mobileUrl('order'));
                    }
                }
            }

            if ($seckillinfo && $seckillinfo['status'] == 0) {
                //如果是秒杀，不判断任何条件

            } else {


                //最低购买
                if ($data['minbuy'] > 0) {
                    if ($data['buycount'] < $data['minbuy']) {
                        if ($_W['ispost']) {
                            show_json(0, $data['title'] . '<br/> ' . $data['min'] . $unit . "起售!", mobileUrl('order'));
                        } else {
                            $this->message($data['title'] . '<br/> ' . $data['min'] . $unit . "起售!", mobileUrl('order'));
                        }
                    }
                }

                //一次购买
                if ($data['maxbuy'] > 0) {
                    if ($data['buycount'] > $data['maxbuy']) {
                        if ($_W['ispost']) {
                            show_json(0, $data['title'] . '<br/> 一次限购 ' . $data['maxbuy'] . $unit . "!");
                        } else {
                            $this->message($data['title'] . '<br/> 一次限购 ' . $data['maxbuy'] . $unit . "!", mobileUrl('order'));
                        }
                    }
                }
                //总购买量
                if ($data['usermaxbuy'] > 0) {
                    $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
                        . ' where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $data['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));
                    if ($order_goodscount >= $data['usermaxbuy']) {
                        if ($_W['ispost']) {
                            show_json(0, $data['title'] . '<br/> 最多限购 ' . $data['usermaxbuy'] . $unit);
                        } else {
                            $this->message($data['title'] . '<br/> 最多限购 ' . $data['usermaxbuy'] . $unit, mobileUrl('order'));
                        }
                    }
                }

                //判断限时购
                if ($data['istime'] == 1) {
                    if (time() < $data['timestart']) {
                        if ($_W['ispost']) {
                            show_json(0, $data['title'] . '<br/> 限购时间未到!');
                        } else {
                            $this->message($data['title'] . '<br/> 限购时间未到!', mobileUrl('order'));
                        }
                    }
                    if (time() > $data['timeend']) {
                        if ($_W['ispost']) {
                            show_json(0, $data['title'] . '<br/> 限购时间已过!');
                        } else {
                            $this->message($data['title'] . '<br/> 限购时间已过!', mobileUrl('order'));
                        }
                    }
                }
                //判断会员权限
                if ($data['buylevels'] != '') {
                    $buylevels = explode(',', $data['buylevels']);
                    if (!in_array($member['level'], $buylevels)) {
                        if ($_W['ispost']) {
                            show_json(0, '您的会员等级无法购买<br/>' . $data['title'] . '!');
                        } else {
                            $this->message('您的会员等级无法购买<br/>' . $data['title'] . '!', mobileUrl('order'));
                        }
                    }
                }

                //会员组权限
                if ($data['buygroups'] != '') {
                    $buygroups = explode(',', $data['buygroups']);
                    if (!in_array($member['groupid'], $buygroups)) {
                        if ($_W['ispost']) {
                            show_json(0, '您所在会员组无法购买<br/>' . $data['title'] . '!');
                        } else {
                            $this->message('您所在会员组无法购买<br/>' . $data['title'] . '!', mobileUrl('order'));
                        }
                    }
                }
            }
            if ($data['totalcnf'] == 1) {
                if (!empty($data['optionid'])) {
                    $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,stock,`virtual` from ' . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $data['goodsid'], ':id' => $data['optionid']));
                    if (!empty($option)) {
                        if ($option['stock'] != -1) {
                            if (empty($option['stock'])) {
                                if ($_W['ispost']) {
                                    show_json(0, $data['title'] . "<br/>" . $option['title'] . " 库存不足!");
                                } else {
                                    $this->message($data['title'] . "<br/>" . $option['title'] . " 库存不足!", mobileUrl('order'));
                                }
                            }
                        }
                    }
                } else {
                    if ($data['stock'] != -1) {
                        if (empty($data['stock'])) {
                            if ($_W['ispost']) {
                                show_json(0, $data['title'] . "<br/>库存不足!");
                            } else {
                                $this->message($data['title'] . "<br/>库存不足!", mobileUrl('order'));
                            }
                        }
                    }
                }
            }
        }


        //货到付款
        if ($type == 'cash') {

            //判断是否开启货到付款
            if (empty($set['pay']['cash'])) {
                if ($_W['ispost']) {
                    show_json(0, '未开启货到付款!');
                } else {
                    $this->message("未开启货到付款", mobileUrl('order'));
                }
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
            $ret['paytype'] = 3;
            $ret['uniacid'] = $_W['uniacid'];
            $pay_result = m('order')->payResult($ret);
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            if ($_W['ispost']) {
                show_json(1, array('result'=>$pay_result));
            } else {
                header("location:" . mobileUrl('order/pay/success', array('id' => $order['id'],'result'=>$pay_result)));
            }
        }


        $ps = array();
        $ps['tid'] = $log['tid'];
        $ps['user'] = $openid;
        $ps['fee'] = $log['fee'];
        $ps['title'] = $log['title'];
        if($type == 'fcard'){
            //检查是否可以使用饭卡支付
            $fcard=array('success' => false);
            $item=m("unit")->checkMember($member['groupid']);
            if($item){
                $isok=m("unit")->checkOrder($openid,$item,$order);
                if($isok && is_weixin()){
                    $fcard['success']=true;
                    $fcard['cardname']=$item['unitname'];
                }
            }
           
            if(!$fcard['success'] && empty($member['cardnumber'])){
                if ($_W['ispost']) {
                    show_json(0, '饭卡支付条件不足!请和管理员联系');
                } else {
                    $this->message("饭卡支付条件不足!请和管理员联系", mobileUrl('order'));
                }
            }
            //检查订单是否已经支付
            $sql="SELECT * FROM ".tablename("ewei_shop_unit_pay_log")." where orderid=:orderid and uniacid=:uniacid";
            $fcarditem=pdo_fetch($sql,array(":orderid"=>$order['id'],":uniacid"=>$_W['uniacid']));
            if(empty($fcarditem)){
                 $fcardlog=array(
                    'unitid'=>$item['id'],
                    'uniacid'=>$_W['uniacid'],
                    'openid'=>$openid,
                    'cardnumber'=>$member['cardnumber'],
                    'price'=>$order['price'],
                    'addtime'=>time(),
                    'orderid'=>$order['id'],
                    'msg'=>"订单支付",
                    'times'=>time(),
                    'status'=>1,
                );
                pdo_insert("ewei_shop_unit_pay_log",$fcardlog);
            }else{
                $fcardlog=array(
                    'unitid'=>$item['id'],
                    'uniacid'=>$_W['uniacid'],
                    'openid'=>$openid,
                    'cardnumber'=>$member['cardnumber'],
                    'price'=>$order['price'],
                    'addtime'=>time(),
                    'times'=>time(),
                    'orderid'=>$order['id'],
                    'msg'=>"订单支付",
                    'status'=>1,
                );
                pdo_update("ewei_shop_unit_pay_log",$fcardlog,array("id"=>$fcarditem['id']));
            }
            $record = array();
            $record['status'] = '1';
            $record['type'] = 'fcard';
            pdo_update('core_paylog', $record, array('plid' => $log['plid']));
            m('order')->setOrderPayType($order['id'], 1);
            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = $log['type'];
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['weid'] = $log['weid'];
            $ret['paytype'] = 8;
            $ret['uniacid'] = $log['uniacid'];
            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            $pay_result = m('order')->payResult($ret);

            if ($_W['ispost']) {
                show_json(1, array('result'=>$pay_result));
            } else {
                header("location:" . mobileUrl('order/pay/success', array('id' => $order['id'],'result'=>$pay_result)));
            }
        }
        //余额支付
        if ($type == 'credit') {

            //判断是否开启余额支付
            if (empty($set['pay']['credit']) && $ps['fee'] > 0) {
                if ($_W['ispost']) {
                    show_json(0, '未开启余额支付!');
                } else {
                    $this->message("未开启余额支付", mobileUrl('order'));
                }
            }

            if ($ps['fee'] < 0) {
                if ($_W['ispost']) {
                    show_json(0, "金额错误");
                } else {
                    $this->message("金额错误", mobileUrl('order'));
                }
            }

            $credits = m('member')->getCredit($openid, 'credit2');
            if ($credits < $ps['fee']) {
                if ($_W['ispost']) {
                    show_json(0, "余额不足,请充值");
                } else {
                    $this->message("余额不足,请充值", mobileUrl('order'));
                }
            }
            $fee = floatval($ps['fee']);

            $result = m('member')->setCredit($openid, 'credit2', -$fee, array($_W['member']['uid'], $_W['shopset']['shop']['name'] . '消费' . $fee));
            if (is_error($result)) {
                if ($_W['ispost']) {
                    show_json(0, $result['message']);
                } else {
                    $this->message($result['message'], mobileUrl('order'));
                }
            }
            $record = array();
            $record['status'] = '1';
            $record['type'] = 'cash';
            pdo_update('core_paylog', $record, array('plid' => $log['plid']));

            m('order')->setOrderPayType($order['id'], 1);

            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = $log['type'];
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['weid'] = $log['weid'];
            $ret['paytype'] = 1;
            $ret['uniacid'] = $log['uniacid'];

            @session_start();
            $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;
            $pay_result = m('order')->payResult($ret);

            if ($_W['ispost']) {
                //var_dump($pay_result);
                show_json(1, array('result'=>$pay_result));
            } else {
                header("location:" . mobileUrl('order/pay/success', array('id' => $order['id'],'result'=>$pay_result)));
            }
        } else if ($type == 'wechat') {

            //判断是否开启微信支付
            if (!is_weixin() && empty($_W['shopset']['wap']['open'])) {
                if ($_W['ispost']) {
                    show_json(0, is_h5app() ? "APP正在维护" : '非微信环境!');
                } else {
                    $this->message(is_h5app() ? "APP正在维护" : '非微信环境!', mobileUrl('order'));
                }
            }
            if (((empty($set['pay']['weixin']) && empty($set['pay']['weixin_jie'])) && is_weixin()) || (empty($set['pay']['app_wechat']) && is_h5app())) {
                if ($_W['ispost']) {
                    show_json(0, '未开启微信支付!');
                } else {
                    $this->message('未开启微信支付!', mobileUrl('order'));
                }
            }

            $ordersn = $order['ordersn'];

            if (!empty($order['ordersn2'])) {
                $ordersn .= "GJ" . sprintf("%02d", $order['ordersn2']);
            }

            $payquery = m('finance')->isWeixinPay($ordersn, $order['price'], is_h5app() ? true : false);
            $payquery_jie = m('finance')->isWeixinPayBorrow($ordersn, $order['price']);

            if (!is_error($payquery) || !is_error($payquery_jie)) {

                //微信支付
                $record = array();
                $record['status'] = '1';
                $record['type'] = 'wechat';
                pdo_update('core_paylog', $record, array('plid' => $log['plid']));

                m('order')->setOrderPayType($order['id'], 21);
                if (is_h5app()) {
                    pdo_update('ewei_shop_order', array('apppay' => 1), array('id' => $order['id']));
                }

                $ret = array();
                $ret['result'] = 'success';
                $ret['type'] = 'wechat';
                $ret['from'] = 'return';
                $ret['tid'] = $log['tid'];
                $ret['user'] = $log['openid'];
                $ret['fee'] = $log['fee'];
                $ret['weid'] = $log['weid'];
                $ret['uniacid'] = $log['uniacid'];
                $ret['paytype'] = 21;
                $ret['deduct'] = intval($_GPC['deduct']) == 1;
                $pay_result = m('order')->payResult($ret);
                @session_start();
                $_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"] = 1;

                if ($_W['ispost']) {
                    show_json(1, array('result'=>$pay_result));
                } else {
                    header("location:" . mobileUrl('order/pay/success', array('id' => $order['id'],'result'=>$pay_result)));
                }
                exit;
            }
            if ($_W['ispost']) {
                show_json(0, '支付出错,请重试!');
            } else {
                $this->message('支付出错,请重试!', mobileUrl('order'));
            }
        }
    }

    /*
    function alipay_complete() {
        global $_GPC, $_W;

        $set = m('common')->getSysset(array('shop', 'pay'));

        //判断是否开启支付宝支付

        $tid = $_GPC['out_trade_no'];

        if(is_h5app()){
            $sec = m('common')->getSec();
            $sec =iunserializer($sec['sec']);
            $public_key = $sec['app_alipay']['public_key'];
            
            if(empty($set['pay']['app_alipay']) || empty($public_key)){
                $this->message('支付出现错误，请重试(1)!', mobileUrl('order'));
            }

            $alidata = base64_decode($_GET['alidata']);
            $alidata = json_decode($alidata, true);
            $alisign = m('finance')->RSAVerify($alidata, $public_key, false);

            $tid = $this->str($alidata['out_trade_no']);
            
            if($alisign==0){
                $this->message('支付出现错误，请重试(2)!', mobileUrl('order'));
            }

        }else{

            if(empty($set['pay']['alipay']) && is_weixin()){
                $this->message('未开启支付宝支付!', mobileUrl('order'));
            }
            if (!m('finance')->isAlipayNotify($_GET)) {
                $this->message('支付出现错误，请重试!', mobileUrl('order'));
            }

        }


        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':tid' => $tid));

        if (empty($log)) {
            $this->message('支付出现错误，请重试(3)!', mobileUrl('order'));
        }

        if(is_h5app()){
            $alidatafee = $this->str($alidata['total_fee']);
            $alidatastatus = $this->str($alidata['success']);
            if($log['fee']!=$alidatafee || !$alidatastatus){
                $this->message('支付出现错误，请重试(4)!', mobileUrl('order'));
            }
        }

        if ($log['status'] != 1) {
            //支付宝支付
            $record = array();
            $record['status'] = '1';
            $record['type'] = 'alipay';
            pdo_update('core_paylog', $record, array('plid' => $log['plid']));

            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = 'alipay';
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['weid'] = $log['weid'];
            $ret['uniacid'] = $log['uniacid'];
            m('order')->payResult($ret);
        }
        //取orderid
        $orderid = pdo_fetchcolumn('select id from ' . tablename('ewei_shop_order') . ' where ordersn=:ordersn and uniacid=:uniacid', array(':ordersn' => $log['tid'], ':uniacid' => $_W['uniacid']));

        if (!empty($orderid))  {
            m('order')->setOrderPayType($orderid, 22);
            if(is_h5app()){
                pdo_update('ewei_shop_order', array('apppay' => 1), array('id' => $orderid ));
            }
        }

        $url = mobileUrl('order/detail', array('id' => $orderid),true);
        die("<script>top.window.location.href='{$url}'</script>");
    }*/

    function success()
    {
        @session_start();
        if (!isset($_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"])) {
            header('location: ' . mobileUrl('order'));
            exit;
        }
        unset($_SESSION[EWEI_SHOPV2_PREFIX . "_order_pay_complete"]);

        global $_W, $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);
        $orderid = intval($_GPC['id']);

        if (empty($orderid)) {
            $this->message('参数错误', mobileUrl('order'), 'error');
        }
        $order = pdo_fetch("select * from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
            , array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
        $depotinfo=Dispage::getDepot($order['depotid']);
        if($order['paytype']==1){
            //余额支付伪造支付单
            pdo_update("ewei_shop_order",array("paymentno"=>"123456789"),array("id"=>$order['id']));
        }
        if($order['paytype']==1 && $order['status']==1 && $depotinfo['if_declare']==1){//余额付款的时候
            //检查是否要申报
            //如果不是跳转
            $order['if_customs_z']=1;
        }//如果用户用了余额去抵扣
        if($order['deductcredit2']> 0 && $depotinfo['if_declare']==1 && $order['status']==1){
            $order['if_customs_z']=1;
        }
        //如果是走盛付通转账  通用转账流程
        if($order['paytype']==1 && $order['if_customs_z']==1 && $order['zhuan_status']==0){//正常订单
            //必须转账的 //覆盖原有支付单
            pdo_update("ewei_shop_order",array("if_customs_z"=>1),array("id"=>$orderid));
        }

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

        include $this->template();

    }

    protected function str($str)
    {
        $str = str_replace('"', '', $str);
        $str = str_replace("'", '', $str);
        return $str;
    }

    function check()
    {

        global $_W, $_GPC;
        $orderid = intval($_GPC['id']);

        $og_array = m('order')->checkOrderGoods($orderid);
        if (!empty($og_array['flag'])) {
            show_json(0, $og_array['msg']);
        }
        show_json(1);
    }

    function message($msg, $redirect = '', $type = '')
    {
        global $_W;
        $title = "";
        $buttontext = "";
        $message = $msg;
        if (is_array($msg)) {
            $message = isset($msg['message']) ? $msg['message'] : '';
            $title = isset($msg['title']) ? $msg['title'] : '';
            $buttontext = isset($msg['buttontext']) ? $msg['buttontext'] : '';
        }
        if (empty($redirect)) {
            $redirect = 'javascript:history.back(-1);';
        } elseif ($redirect == 'close') {
            $redirect = 'javascript:WeixinJSBridge.call("closeWindow")';
        }
        include $this->template('_message');
        exit;
    }

}
