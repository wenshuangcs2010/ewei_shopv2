<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Order_EweiShopV2Model
{

    /**
     * 支付成功
     * @global type $_W
     * @param type $params
     */
    public function payResult($params)
    {
        global $_W;
        $fee = intval($params['fee']);
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
      
        $ordersn = $params['tid'];
        $order = pdo_fetch('select id,ordersn,depotid,zhuan_status,realname,imid,isdisorder,disorderamount, price,openid,dispatchtype,addressid,carrier,status,isverify,deductcredit2,`virtual`,isvirtual,couponid,isvirtualsend,isparent,paytype,merchid,agentid,createtime,buyagainprice from ' . tablename('ewei_shop_order') . ' where  ordersn=:ordersn and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':ordersn' => $ordersn));

        $orderid = $order['id'];

        if ($params['from'] == 'return') {

            //秒杀
            $seckill_result = plugin_run('seckill::setOrderPay', $order['id']);

            if($seckill_result=='refund'){
                return 'seckill_refund';
            }

            $address = false;
            if (empty($order['dispatchtype'])) {
                $address = pdo_fetch('select realname,mobile,address from ' . tablename('ewei_shop_member_address') . ' where id=:id limit 1', array(':id' => $order['addressid']));
            }

            $carrier = false;
            if ($order['dispatchtype'] == 1 || $order['isvirtual'] == 1) {
                $carrier = unserialize($order['carrier']);
            }

            if ($params['type'] == 'cash') {

                if ($order['isparent'] == 1) {
                    $change_data = array();
                    $change_data['merchshow'] = 1;
                    $change_data['paymentno'] =$params['paymentno'];
                    //订单状态
                    pdo_update('ewei_shop_order', $change_data, array('id' => $orderid));

                    //处理子订单状态
                    $this->setChildOrderPayResult($order, 0, 0);
                }
                return true;
            } else {
                if ($order['status'] == 0) {
                    if (!empty($order['virtual']) && com('virtual')) {

                        return com('virtual')->pay($order);
                    } else if ($order['isvirtualsend']) {
                        return $this->payVirtualSend($order['id']);
                    } else {
                        $time = time();
                        $change_data = array();
                        $change_data['status'] = 1;
                        $change_data['paytime'] = $time;
                        $change_data['paymentno'] =$params['paymentno'];//wsq
                        if ($order['isparent'] == 1) {
                            $change_data['merchshow'] = 1;
                        }
                        //订单状态
                        pdo_update('ewei_shop_order', $change_data, array('id' => $orderid));
                        if ($order['isparent'] == 1) {
                            //处理子订单状态
                            $this->setChildOrderPayResult($order, $time, 1);
                        }
                        //处理积分与库存
                        
                        $this->setStocksAndCredits($orderid, 1);
                        $customs=m("kjb2c")->check_if_customs($order['depotid']);
                       
                        if($customs){


                            // if($order['if_customs_z']==1){//盛付通 处理
                            //     //自动转账的订单
                            //     if($order['zhuan_status']!=1){
                            //         $order_sn=$order['ordersn'];
                            //         $data=array(
                            //             'pay_fee'=>$order['price'],
                            //             'realname'=>$order['realname'],
                            //             'imid'=>$order['imid'],
                            //             'order_sn'=>$order_sn,
                            //             'orderid'=>$orderid,
                            //             'add_time'=>time(),
                            //             );
                            //         pdo_insert("ewei_shop_zpay_log",$data);
                            //         require EWEI_SHOPV2_TAX_CORE. '/Transfer/Transfer.php';
                            //         $payment=Transfer::getPayment("shenfupay");
                            //     }
                            //     $params['paytype']=37;
                            // }
                            $depot=m("kjb2c")->get_depot($order['depotid']);
                            $customsparams=array(
                                'out_trade_no'=>$order['ordersn'],
                                'transaction_id'=>$params['paymentno'],
                                'customs'=>$customs,
                                'mch_customs_no'=>$depot['customs_code'],
                            );
                            $jearray=Dispage::getDisaccountArray();
                            if($params['paytype']==21){
                                load()->model('payment');
                                $uniacid=$_W['uniacid'];
                                if(in_array($_W['uniacid'], $jearray) && $order['isdisorder']==1){
                                    $uniacid=DIS_ACCOUNT;
                                    $customsparams['out_trade_no']=$order['ordersn']."_borrow";
                                }
                                $setting = uni_setting($uniacid, array('payment'));
                                if (is_array($setting['payment']['wechat']) && $setting['payment']['wechat']['switch']) {
                                    $APPID = pdo_fetchcolumn('SELECT `key` FROM '.tablename('account_wechats')." WHERE uniacid=:uniacid",array(':uniacid'=>$uniacid));
                                        $config=array(
                                            "appid"=>$APPID,
                                            'mch_id'=>$setting['payment']['wechat']['mchid'],
                                            'apikey'=>$setting['payment']['wechat']['apikey'],
                                            );
                                
                                    $returndatatemp=m("kjb2c")->to_customs($customsparams,$config,'wx');
                                    WeUtility::logging('自动报关结果', var_export($returndatatemp, true));
                                }
                                if($depot['if_declare']==1 && $order['isdisorder']==0){
                                    m("kjb2c")->to_declare($orderid);
                                }
                                 if($order['isdisorder']==1 && $depot['if_declare']==1){//代理订单要申报无需二次支付的订单
                                    $disInfo=Dispage::getDisInfo($_W['uniacid']);
                                    if($disInfo['secondpay']==0){
                                         m("kjb2c")->to_declare($orderid);
                                    }
                                 }
                            }elseif($params['paytype']==22){

                            }elseif($params['paytype']==37){

                            }
                        }
                        if($order['isdisorder']==1){
                             m('kjb2c')->pay_disorder_wx($orderid,$_W['uniacid']);
                        }

                        //发送赠送优惠券
                        if (com('coupon')) {
                            com('coupon')->sendcouponsbytask($order['id']); //订单支付
                        }

                        //优惠券返利
                        if (com('coupon') && !empty($order['couponid'])) {
                            com('coupon')->backConsumeCoupon($order['id']); //订单支付
                        }
                        //模板消息
                        m('notice')->sendOrderMessage($orderid);

                        //打印机打印
                        com_run('printer::sendOrderMessage', $orderid);

                        //分销商
                        if (p('commission')) {
                            p('commission')->checkOrderPay($order['id']);
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * 子订单支付成功
     * @global type $_W
     * @param type $order
     * @param type $time
     */
    function setChildOrderPayResult($order, $time, $type)
    {

        global $_W;

        $orderid = $order['id'];
        $list = $this->getChildOrder($orderid);

        if (!empty($list)) {
            $change_data = array();
            if ($type == 1) {
                $change_data['status'] = 1;
                $change_data['paytime'] = $time;
            }
            $change_data['merchshow'] = 0;

            foreach ($list as $k => $v) {
                //订单状态
                if ($v['status'] == 0) {
                    pdo_update('ewei_shop_order', $change_data, array('id' => $v['id']));
                }
            }
        }
    }

    /**
     * 设置订单支付方式
     * @global type $_W
     * @param type $orderid
     * @param type $paytype
     */
    function setOrderPayType($orderid, $paytype)
    {

        global $_W;

        pdo_update('ewei_shop_order', array('paytype' => $paytype), array('id' => $orderid));
        if (!empty($orderid)) {
            pdo_update('ewei_shop_order', array('paytype' => $paytype), array('parentid' => $orderid));
        }
    }

    /**
     * 获取子订单
     * @global type $_W
     * @param type $orderid
     */
    function getChildOrder($orderid)
    {

        global $_W;

        $list = pdo_fetchall('select id,ordersn,status,finishtime,couponid  from ' . tablename('ewei_shop_order') . ' where  parentid=:parentid and uniacid=:uniacid', array(':parentid' => $orderid, ':uniacid' => $_W['uniacid']));
        return $list;
    }


    /**
     * 虚拟商品自动发货
     * @param int $orderid
     * @return bool?
     */
    function payVirtualSend($orderid = 0) {

        global $_W, $_GPC;

        $order = pdo_fetch('select id,ordersn, price,openid,dispatchtype,addressid,carrier,status,isverify,deductcredit2,`virtual`,isvirtual,couponid from ' . tablename('ewei_shop_order') . ' where  id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $orderid));
        $order_goods = pdo_fetch("select g.virtualsend,g.virtualsendcontent from " . tablename('ewei_shop_order_goods') . " og "
            . " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
            . " where og.orderid=:orderid and og.uniacid=:uniacid limit 1", array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));
        $time = time();

        //自动完成
        pdo_update('ewei_shop_order', array('virtualsend_info' => $order_goods['virtualsendcontent'], 'status' => '3', 'paytime' => $time, 'sendtime' => $time, 'finishtime' => $time), array('id' => $orderid));


        //处理余额抵扣,下单时余额抵扣已经扣除,这里无需再执行
        /*if ($order['deductcredit2'] > 0) {
            $shopset = m('common')->getSysset('shop');
            m('member')->setCredit($order['openid'], 'credit2', -$order['deductcredit2'], array(0, $shopset['name'] . "余额抵扣: {$order['deductcredit2']} 订单号: " . $order['ordersn']));
        }*/

        //处理积分与库存
        $this->setStocksAndCredits($orderid, 1);

        //会员升级
        m('member')->upgradeLevel($order['openid']);

        //余额赠送
        m('order')->setGiveBalance($orderid, 1);

        //发送赠送优惠券
        if (com('coupon')) {
            com('coupon')->sendcouponsbytask($order['id']); //订单支付
        }

        //优惠券返利
        if (com('coupon') && !empty($order['couponid'])) {
            com('coupon')->backConsumeCoupon($order['id']); //订单支付
        }
        //模板消息
        m('notice')->sendOrderMessage($orderid);

        //分销商
        if (p('commission')) {
            //付款后
            p('commission')->checkOrderPay($order['id']);
            //自动完成后
            p('commission')->checkOrderFinish($order['id']);
        }
        return true;
    }

    /**
     * 计算订单中商品累计赠送的积分
     * @param type $order
     */
    function getGoodsCredit($goods)
    {
        global $_W;

        $credits = 0;

        foreach ($goods as $g) {
            //积分累计
            $gcredit = trim($g['credit']);
            if (!empty($gcredit)) {
                if (strexists($gcredit, '%')) {
                    //按比例计算
                    $credits += intval(floatval(str_replace('%', '', $gcredit)) / 100 * $g['realprice']);
                } else {
                    //按固定值计算
                    $credits += intval($g['credit']) * $g['total'];
                }
            }
        }
        return $credits;
    }


    /**
     * 返还抵扣的余额
     * @param type $order
     */
    function setDeductCredit2($order)
    {
        global $_W;

        if ($order['deductcredit2'] > 0) {
            m('member')->setCredit($order['openid'], 'credit2', $order['deductcredit2'], array('0', $_W['shopset']['shop']['name'] . "购物返还抵扣余额 余额: {$order['deductcredit2']} 订单号: {$order['ordersn']}"));
        }
    }


    /**
     * 处理赠送余额情况
     * @param type $orderid
     * @param type $type 1 订单完成 2 售后
     */
    function setGiveBalance($orderid = '', $type = 0)
    {
        global $_W;
        $order = pdo_fetch('select id,ordersn,price,openid,dispatchtype,addressid,carrier,status from ' . tablename('ewei_shop_order') . ' where id=:id limit 1', array(':id' => $orderid));
        $goods = pdo_fetchall("select og.goodsid,og.total,g.totalcnf,og.realprice,g.money,og.optionid,g.total as goodstotal,og.optionid,g.sales,g.salesreal from " . tablename('ewei_shop_order_goods') . " og "
            . " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
            . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));

        $balance = 0;

        foreach ($goods as $g) {
            //余额累计
            $gbalance = trim($g['money']);
            if (!empty($gbalance)) {
                if (strexists($gbalance, '%')) {
                    //按比例计算
                    $balance += intval(floatval(str_replace('%', '', $gbalance)) / 100 * $g['realprice']);
                } else {
                    //按固定值计算
                    $balance += intval($g['money']) * $g['total'];
                }
            }
        }

        //用户余额
        if ($balance > 0) {
            $shopset = m('common')->getSysset('shop');

            if ($type == 1) {
                //订单完成赠送余额
                if ($order['status'] == 3) {
                    m('member')->setCredit($order['openid'], 'credit2', $balance, array(0, $shopset['name'] . '购物赠送余额 订单号: ' . $order['ordersn']));
                }
            } elseif ($type == 2) {
                //订单售后,扣除赠送的余额
                if ($order['status'] >= 1) {
                    m('member')->setCredit($order['openid'], 'credit2', -$balance, array(0, $shopset['name'] . '购物取消订单扣除赠送余额 订单号: ' . $order['ordersn']));
                }
            }
        }
    }


    /**
     * //处理订单库存及用户积分情况(赠送积分)
     * @param type $orderid
     * @param type $type 0 下单 1 支付 2 取消
     */
    function setStocksAndCredits($orderid = '', $type = 0)
    {

        global $_W;
        $order = pdo_fetch('select id,ordersn,uniacid,price,openid,dispatchtype,addressid,carrier,status,isparent,paytype from ' . tablename('ewei_shop_order') . ' where id=:id limit 1', array(':id' => $orderid));

        $param = array();
        $uniacid=$order['uniacid'];
        $param[':uniacid'] = $uniacid;

        if ($order['isparent'] == 1) {
            $condition = " og.parentorderid=:parentorderid";
            $param[':parentorderid'] = $orderid;
        } else {
            $condition = " og.orderid=:orderid";
            $param[':orderid'] = $orderid;
        }

        $goods = pdo_fetchall("select og.goodsid,og.total,g.totalcnf,og.realprice,g.credit,og.optionid,g.total as goodstotal,og.optionid,g.sales,g.salesreal,g.goodssn from " . tablename('ewei_shop_order_goods') . " og "
            . " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
            . " where $condition and og.uniacid=:uniacid ", $param);

        $credits = 0;
        foreach ($goods as $g) {
            $stocktype = 0; //0 不设置库存情况 -1 减少 1 增加
            if ($type == 0) {
                //如果是下单
                if ($g['totalcnf'] == 0) {
                    //少库存
                    $stocktype = -1;
                }
            } else if ($type == 1) {
                if ($g['totalcnf'] == 1) {
                    //少库存
                    $stocktype = -1;
                }
            } else if ($type == 2) {
                //取消订单
                if ($order['status'] >= 1) {
                    //如果已付款，并且是付款减库存
                    if ($g['totalcnf'] == 1) {
                        //加库存
                        $stocktype = 1;
                    }
                } else {
                    //未付款，并且是下单减库存
                    if ($g['totalcnf'] == 0) {
                        //加库存
                        $stocktype = 1;
                    }
                }
            }
            if (!empty($stocktype)) {
                if (!empty($g['optionid'])) {
                    //减少规格库存
                    $option = m('goods')->getOption($g['goodsid'], $g['optionid']);
                    if (!empty($option) && $option['stock'] != -1) {
                        $stock = -1;
                        if ($stocktype == 1) {
                            //增加库存
                            $stock = $option['stock'] + $g['total'];
                        } else if ($stocktype == -1) {
                            //减少库存
                            $stock = $option['stock'] - $g['total'];
                            $stock <= 0 && $stock = 0;
                        }
                        if ($stock != -1) {
                            pdo_update('ewei_shop_goods_option', array('stock' => $stock), array('uniacid' => $uniacid, 'goodsid' => $g['goodsid'], 'id' => $g['optionid']));
                            if($option['disoptionid']>0){
                                $sql="SELECT id from ".tablename("ewei_shop_goods_option")." where disoptionid=:disoptionid";
                                $optionlist=pdo_fetchall($sql,array(":disoptionid"=>$option['disoptionid']));
                                foreach ($optionlist as $value) {
                                     pdo_update('ewei_shop_goods_option', array('stock' => $stock), array('id' => $value));//代理库存更新
                                }
                                pdo_update('ewei_shop_goods_option', array('stock' => $stock), array('id' => $option['disoptionid']));//主库存更新
                            }
                        }
                    }
                }
                if (!empty($g['goodstotal']) && $g['goodstotal'] != -1) {
                    //减少商品总库存
                    $totalstock = -1;
                    if ($stocktype == 1) {
                        //增加库存
                        $totalstock = $g['goodstotal'] + $g['total'];
                    } else if ($stocktype == -1) {
                        //减少库存
                        $totalstock = $g['goodstotal'] - $g['total'];
                        $totalstock <= 0 && $totalstock = 0;
                    }
                    if ($totalstock != -1) {
                        pdo_update('ewei_shop_goods', array('total' => $totalstock), array('uniacid' => $uniacid, 'id' => $g['goodsid']));
                        m("order")->updatestock($g['goodssn'],$totalstock);//代理库存更新

                    }
                }
            }

            //积分累计
            $gcredit = trim($g['credit']);
            if (!empty($gcredit)) {
                if (strexists($gcredit, '%')) {
                    //按比例计算
                    $credits += intval(floatval(str_replace('%', '', $gcredit)) / 100 * $g['realprice']);
                } else {
                    //按固定值计算
                    $credits += intval($g['credit']) * $g['total'];
                }
            }

            if ($type == 0) {
                //虚拟销量只要是拍下就加 || 如果是付款减库存,则付款才加销量
                if ($g['totalcnf'] != 1) {
                    pdo_update('ewei_shop_goods', array('sales' => $g['sales'] + $g['total']), array('uniacid' => $uniacid, 'id' => $g['goodsid']));
                }
            } elseif ($type == 1) {
                //真实销量付款才加
                if ($order['status'] >= 1) {
                    if ($g['totalcnf'] != 1) {
                        pdo_update('ewei_shop_goods', array('sales' => $g['sales'] - $g['total']), array('uniacid' => $uniacid, 'id' => $g['goodsid']));
                    }
                    //实际销量
                    $salesreal = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid '
                        . ' where og.goodsid=:goodsid and o.status>=1 and o.uniacid=:uniacid limit 1', array(':goodsid' => $g['goodsid'], ':uniacid' => $uniacid));
                    pdo_update('ewei_shop_goods', array('salesreal' => $salesreal), array('id' => $g['goodsid']));
                }
            }
        }

        //用户积分
        if ($credits > 0) {
            $shopset = m('common')->getSysset('shop');

            if ($type == 1) {

                //支付增加积分
                m('member')->setCredit($order['openid'], 'credit1', $credits, array(0, $shopset['name'] . '购物积分 订单号: ' . $order['ordersn']));
            } elseif ($type == 2) {
                //减少积分，只有付款了才减少
                if ($order['status'] >= 1) {
                    m('member')->setCredit($order['openid'], 'credit1', -$credits, array(0, $shopset['name'] . '购物取消订单扣除积分 订单号: ' . $order['ordersn']));
                }
            }
        }

        //积分活动订单送积分
        if ($type == 1) {
            //支付增加积分
            com_run('sale::getCredit1',$order['openid'],$order['price'],$order['paytype'],1);
        } elseif ($type == 2) {
            //减少积分，只有付款了才减少
            if ($order['status'] >= 1) {
                com_run('sale::getCredit1',$order['openid'],$order['price'],$order['paytype'],1,1);
            }
        }
    }

    //代理库存更新
    function updatestock($goods_sn,$stock){
        $goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where goodssn=:goodssn and uniacid=:uniacid limit 1", array(':goodssn' => $goods_sn, ':uniacid' => DIS_ACCOUNT));
        if(empty($goods)){
            return false;
        }
        $sql="select id from " . tablename('ewei_shop_goods') . " where disgoods_id=:disgoods_id";
        $disgoodslist=pdo_fetchall($sql,array("disgoods_id"=>$goods['id']));
        if(empty($disgoodslist)){
            return false;
        }
        $sql="update ".tablename("ewei_shop_goods")." SET total=:total";
        foreach ($disgoodslist as $v) {
           $t[]=$v['id'];
        }
        $ids=implode(",",$t);
        $sql.=" where id in ($ids)";
        pdo_query($sql,array(":total"=>$stock));
    }
    function getTotals($merch = 0)
    {
        global $_W;

        $paras = array(':uniacid' => $_W['uniacid']);
        $merch = intval($merch);
        $condition = ' and isparent=0';
        if ($merch < 0) {
            $condition .= ' and merchid=0';
        }
        $totals['all'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0 and deleted=0", $paras);
        $totals['status_1'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0 and status=-1 and refundtime=0 and deleted=0", $paras);
        $totals['status0'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0  and status=0 and paytype<>3 and deleted=0", $paras);
        $totals['status1'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0  and ( status=1 or ( status=0 and paytype=3) ) and deleted=0", $paras);
        $totals['status2'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0  and status=2 and deleted=0", $paras);
        $totals['status3'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0  and status=3 and deleted=0", $paras);
        $totals['status4'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0  and refundstate>0 and refundid<>0 and deleted=0", $paras);
     
        $totals['status5'] = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_order') . ""
            . " WHERE uniacid = :uniacid {$condition} and ismr=0 and refundtime<>0 and deleted=0", $paras);
        return $totals;
    }

    function getFormartDiscountPrice($isd, $gprice, $gtotal = 1)
    {
        $price = $gprice;
        if (!empty($isd)) {
            if (strexists($isd, '%')) {
                //促销折扣
                $dd = floatval(str_replace('%', '', $isd));

                if ($dd > 0 && $dd < 100) {
                    $price = round($dd / 100 * $gprice, 2);
                }
            } else if (floatval($isd) > 0) {
                //促销价格
                $price = round(floatval($isd * $gtotal), 2);
            }
        }
        return $price;
    }


    //获得d商品详细促销
    function getGoodsDiscounts($goods, $isdiscount_discounts, $levelid, $options = array())
    {

        $key = empty($levelid) ? 'default' : 'level' . $levelid;
        $prices = array();

        if (empty($goods['merchsale'])) {
            if (!empty($isdiscount_discounts[$key])) {
                foreach ($isdiscount_discounts[$key] as $k => $v) {
                    $k = substr($k, 6);
                    $op_marketprice = m('goods')->getOptionPirce($goods['id'], $k);
                    $gprice = $this->getFormartDiscountPrice($v, $op_marketprice);
                    $prices[] = $gprice;
                    if (!empty($options)) {
                        foreach ($options as $key => $value) {
                            if ($value['id'] == $k) {
                                $options[$key]['marketprice'] = $gprice;
                            }
                        }
                    }
                }
            }
        } else {
            if (!empty($isdiscount_discounts['merch'])) {
                foreach ($isdiscount_discounts['merch'] as $k => $v) {
                    $k = substr($k, 6);
                    $op_marketprice = m('goods')->getOptionPirce($goods['id'], $k);
                    $gprice = $this->getFormartDiscountPrice($v, $op_marketprice);
                    $prices[] = $gprice;
                    if (!empty($options)) {
                        foreach ($options as $key => $value) {
                            if ($value['id'] == $k) {
                                $options[$key]['marketprice'] = $gprice;
                            }
                        }
                    }
                }
            }
        }

        $data = array();
        $data['prices'] = $prices;
        $data['options'] = $options;

        return $data;
    }

    //获得d商品促销或会员折扣价格
    function getGoodsDiscountPrice($g, $level, $type = 0)
    {

        //商品原价
        if ($type == 0) {
            $total = $g['total'];
        } else {
            $total = 1;
        }

        $gprice = $g['marketprice'] * $total;

        if (empty($g['buyagain_islong'])) {
            $gprice = $g['marketprice'] * $total;
        }
        //重复购买购买是否享受其他折扣
        $buyagain_sale = true;
        $buyagainprice = 0;
        $canbuyagain = false;

        if (empty($g['is_task_goods'])) {
            if (floatval($g['buyagain']) > 0) {
                //第一次后买东西享受优惠
                if (m('goods')->canBuyAgain($g)) {
                    $canbuyagain = true;
                    if (empty($g['buyagain_sale'])) {
                        $buyagain_sale = false;
                    }
                }
            }
        }

        //成交的价格
        $price = $gprice;
        $price1 = $gprice;
        $price2 = $gprice;

        //任务活动物品
        $taskdiscountprice = 0; //任务活动折扣
        if (!empty($g['is_task_goods'])) {
            $buyagain_sale = false;
            $price = $g['task_goods']['marketprice'] * $total;

            if ($gprice > $price) {
                $taskdiscountprice = abs($gprice - $price);
            }
        }

        $discountprice = 0; //会员折扣
        $isdiscountprice = 0; //促销折扣
        $isd = false;
        @$isdiscount_discounts = json_decode($g['isdiscount_discounts'], true);

        //判断最终价格以哪种优惠计算 0 无优惠,1 促销优惠, 2 会员折扣
        $discounttype = 0;
        //判断是否有促销折扣
        $isCdiscount = 0;
        //判断是否有会员折扣
        $isHdiscount = 0;
        //var_dump($g['isdiscount_stat_time']);
        //是否有促销
        if ($g['isdiscount'] &&  $g['isdiscount_stat_time']<=time() && $g['isdiscount_time'] >= time() && $buyagain_sale) {

            if (is_array($isdiscount_discounts)) {
                $key = !empty($level['id']) ? 'level' . $level['id'] : 'default';
                if (!isset($isdiscount_discounts['type']) || empty($isdiscount_discounts['type'])) {
                    //统一
                    if (empty($g['merchsale'])) {
                        $isd = trim($isdiscount_discounts[$key]['option0']);
                        if (!empty($isd)) {
                            $price1 = $this->getFormartDiscountPrice($isd, $gprice, $total);
                        }
                    } else {
                        $isd = trim($isdiscount_discounts['merch']['option0']);
                        if (!empty($isd)) {
                            $price1 = $this->getFormartDiscountPrice($isd, $gprice, $total);
                        }
                    }
                } else {
                    //详细促销
                    if (empty($g['merchsale'])) {
                        $isd = trim($isdiscount_discounts[$key]['option' . $g['optionid']]);
                        if (!empty($isd)) {
                            $price1 = $this->getFormartDiscountPrice($isd, $gprice, $total);
                        }
                    } else {
                        $isd = trim($isdiscount_discounts['merch']['option' . $g['optionid']]);
                        if (!empty($isd)) {
                            $price1 = $this->getFormartDiscountPrice($isd, $gprice, $total);
                        }
                    }
                }
            }

            //判断促销价是否低于原价
            if ($price1 > $gprice) {
                $isdiscountprice = 0;
            } else {
                $isdiscountprice = abs($price1 - $gprice);
            }
            $isCdiscount = 1;
        }

        if (empty($g['isnodiscount']) && $buyagain_sale) {
            //参与会员折扣
            $discounts = json_decode($g['discounts'], true);
            if (is_array($discounts)) {

                $key = !empty($level['id']) ? 'level' . $level['id'] : 'default';
                if (!isset($discounts['type']) || empty($discounts['type'])) {
                    //统一折扣
                    if (!empty($discounts[$key])) {
                        $dd = floatval($discounts[$key]); //设置的会员折扣
                        if ($dd > 0 && $dd < 10) {
                            $price2 = round($dd / 10 * $gprice, 2);
                        }
                    } else {
                        $dd = floatval($discounts[$key . '_pay'] * $total); //设置的会员折扣
                        $md = floatval($level['discount']); //会员等级折扣
                        if (!empty($dd)) {
                            $price2 = round($dd, 2);
                        } else if ($md > 0 && $md < 10) {
                            $price2 = round($md / 10 * $gprice, 2);
                        }
                    }
                } else {
                    //详细折扣

                    $isd = trim($discounts[$key]['option' . $g['optionid']]);
                    if (!empty($isd)) {
                        $price2 = $this->getFormartDiscountPrice($isd, $gprice, $total);
                    }
                }
            }
            $discountprice = abs($price2 - $gprice);
            $isHdiscount = 1;
        }

        if ($isCdiscount == 1) {
            $price = $price1;
            $discounttype = 1;
        } else if ($isHdiscount == 1) {
            $price = $price2;
            $discounttype = 2;
        }


        //平均价格
        $unitprice = round($price / $total, 2);
        //使用促销的价格
        $isdiscountunitprice = round($isdiscountprice / $total, 2);
        //使用会员折扣的价格
        $discountunitprice = round($discountprice / $total, 2);

        if ($canbuyagain) {
            if (empty($g['buyagain_islong'])) {
                $buyagainprice = $unitprice * (10 - $g['buyagain']) / 10;
            } else {
                $buyagainprice = $price * (10 - $g['buyagain']) / 10;
            }
        }

        $price = $price - $buyagainprice;

        return array(
            'unitprice' => $unitprice,
            'price' => $price,
            'taskdiscountprice' => $taskdiscountprice,
            'discounttype' => $discounttype,
            'isdiscountprice' => $isdiscountprice,
            'discountprice' => $discountprice,
            'isdiscountunitprice' => $isdiscountunitprice,
            'discountunitprice' => $discountunitprice,
            'price0' => $gprice,
            'price1' => $price1,
            'price2' => $price2,
            'buyagainprice' => $buyagainprice
        );
    }

    //计算子订单中的相关费用
    function getChildOrderPrice($order, $goods, $dispatch_array, $merch_array, $sale_plugin, $discountprice_array)
    {
        global $_GPC;

        $totalprice = $order['price'];             //总价
        $goodsprice = $order['goodsprice'];       //商品总价
        $grprice = $order['grprice'];             //商品实际总价

        $deductprice = $order['deductprice'];     //抵扣的钱
        $deductcredit = $order['deductcredit'];   //抵扣需要扣除的积分
        $deductcredit2 = $order['deductcredit2']; //可抵扣的余额

        $deductenough = $order['deductenough'];   //满额减
        //$couponprice = $order['couponprice'];     //优惠券价格

        $is_deduct = 0;        //是否进行积分抵扣的计算
        $is_deduct2 = 0;       //是否进行余额抵扣的计算
        $deduct_total = 0;     //计算商品中可抵扣的总积分
        $deduct2_total = 0;    //计算商品中可抵扣的总余额

        $ch_order = array();

        if ($sale_plugin) {
            //积分抵扣
            if (!empty($_GPC['deduct'])) {
                $is_deduct = 1;
            }

            //余额抵扣
            if (!empty($_GPC['deduct2'])) {
                $is_deduct2 = 1;
            }
        }

        foreach ($goods as &$g) {
            $merchid = $g['merchid'];

            $ch_order[$merchid]['goods'][] = $g['goodsid'];
            $ch_order[$merchid]['grprice'] += $g['ggprice'];
            $ch_order[$merchid]['goodsprice'] += $g['marketprice'] * $g['total'];
//            $g['proportion'] = round($g['ggprice'] / $grprice, 2);
            $ch_order[$merchid]['couponprice'] = $discountprice_array[$merchid]['deduct'];

            if ($is_deduct == 1) {
                //积分抵扣
                if ($g['manydeduct']) {
                    $deduct = $g['deduct'] * $g['total'];
                } else {
                    $deduct = $g['deduct'];
                }


                if($g['seckillinfo'] && $g['seckillinfo']['status']==0){
                    //秒杀不抵扣
                }else{
                    $deduct_total += $deduct;
                    $ch_order[$merchid]['deducttotal'] += $deduct;
                }

            }

            if ($is_deduct2 == 1) {
                //余额抵扣
                if ($g['deduct2'] == 0) {
                    //全额抵扣
                    $deduct2 = $g['ggprice'];
                } else if ($g['deduct2'] > 0) {

                    //最多抵扣
                    if ($g['deduct2'] > $g['ggprice']) {
                        $deduct2 = $g['ggprice'];
                    } else {
                        $deduct2 = $g['deduct2'];
                    }
                }

                if($g['seckillinfo'] && $g['seckillinfo']['status']==0){
                    //秒杀不抵扣
                }else{
                    $ch_order[$merchid]['deduct2total'] += $deduct2;
                    $deduct2_total += $deduct2;
                }

            }
        }

        unset($g);

        foreach ($ch_order as $k => $v) {

            if ($is_deduct == 1) {
                //计算详细积分抵扣
                if ($deduct_total > 0) {
                    $n = $v['deducttotal'] / $deduct_total;
                    $deduct_credit = ceil(round($deductcredit * $n, 2));
                    $deduct_money = round($deductprice * $n, 2);
                    $ch_order[$k]['deductcredit'] = $deduct_credit;
                    $ch_order[$k]['deductprice'] = $deduct_money;
                }
            }

            if ($is_deduct2 == 1) {
                //计算详细余额抵扣
                if ($deduct2_total > 0) {
                    $n = $v['deduct2total'] / $deduct2_total;
                    $deduct_credit2 = round($deductcredit2 * $n, 2);
                    $ch_order[$k]['deductcredit2'] = $deduct_credit2;
                }
            }

            //子订单商品价格占总订单的比例
            $op = round($v['grprice'] / $grprice, 2);
            $ch_order[$k]['op'] = $op;

            if ($deductenough > 0) {
                //计算满减金额
                $deduct_enough = round($deductenough * $op, 2);
                $ch_order[$k]['deductenough'] = $deduct_enough;
            }

        }


        foreach ($ch_order as $k => $v) {
            $merchid = $k;
            $price = $v['grprice'] - $v['deductprice'] - $v['deductcredit2'] - $v['deductenough'] - $v['couponprice'] + $dispatch_array['dispatch_merch'][$merchid];

            //多商户满额减
            if ($merchid > 0) {
                $merchdeductenough = $merch_array[$merchid]['enoughdeduct'];
                if ($merchdeductenough > 0) {
                    $price -= $merchdeductenough;
                    $ch_order[$merchid]['merchdeductenough'] = $merchdeductenough;
                }
            }
            $ch_order[$merchid]['price'] = $price;
        }

        return $ch_order;

    }

    //计算订单中多商户满额减
    function getMerchEnough($merch_array)
    {
        $merch_enough_total = 0;

        $merch_saleset = array();

        foreach ($merch_array as $key => $value) {
            $merchid = $key;
            if ($merchid > 0) {
                $enoughs = $value['enoughs'];

                if (!empty($enoughs)) {
                    $ggprice = $value['ggprice'];

                    foreach ($enoughs as $e) {
                        if ($ggprice >= floatval($e['enough']) && floatval($e['money']) > 0) {
                            $merch_array[$merchid]['showenough'] = 1;
                            $merch_array[$merchid]['enoughmoney'] = $e['enough'];
                            $merch_array[$merchid]['enoughdeduct'] = $e['money'];

                            $merch_saleset['merch_showenough'] = 1;
                            $merch_saleset['merch_enoughmoney'] += $e['enough'];
                            $merch_saleset['merch_enoughdeduct'] += $e['money'];

                            $merch_enough_total += floatval($e['money']);
                            break;
                        }
                    }
                }
            }
        }

        $data = array();
        $data['merch_array'] = $merch_array;
        $data['merch_enough_total'] = $merch_enough_total;
        $data['merch_saleset'] = $merch_saleset;

        return $data;
    }

    //计算订单商品总运费
    function getOrderDispatchPrice($goods, $member, $address, $saleset = false, $merch_array, $t, $loop = 0)
    {

        global $_W;
        $realprice = 0;
        $dispatch_price = 0;
        $dispatch_array = array();
        $dispatch_merch = array();
        $total_array = array();
        $totalprice_array = array();
        $nodispatch_array = array();

        $seckill_payprice = 0;  //秒杀的金额
        $seckill_dispatchprice=0; //秒杀的邮费
        $user_city = '';
        if (!empty($address)) {
            $user_city = $address['city'];
        } else if (!empty($member['city'])) {
            $user_city = $member['city'];
        }

        foreach ($goods as $g) {
            $realprice += $g['ggprice'];
            $dispatch_merch[$g['merchid']] = 0;
            $total_array[$g['goodsid']] += $g['total'];
            $totalprice_array[$g['goodsid']] += $g['ggprice'];
        }

        foreach ($goods as $g) {
            //秒杀
            $seckillinfo = plugin_run('seckill::getSeckill', $g['goodsid'], $g['optionid'], true, $_W['openid']);

            if ($seckillinfo && $seckillinfo['status'] == 0) {
                $seckill_payprice += $g['ggprice'];
            }

            //不配送状态 0配送 1不配送
            $isnodispatch = 0;

            //是否包邮
            $sendfree = false;
            $merchid = $g['merchid'];

            if (!empty($g['issendfree'])) { //本身包邮
                $sendfree = true;

            } else {

                if ($seckillinfo && $seckillinfo['status'] == 0) {
                    //秒杀不参与满件包邮
                } else {

                    if ($total_array[$g['goodsid']] >= $g['ednum'] && $g['ednum'] > 0) { //单品满件包邮
                        $gareas = explode(";", $g['edareas']);

                        if (empty($gareas)) {
                            $sendfree = true;
                        } else {
                            if (!empty($address)) {
                                if (!in_array($address['city'], $gareas)) {
                                    $sendfree = true;
                                }
                            } else if (!empty($member['city'])) {
                                if (!in_array($member['city'], $gareas)) {
                                    $sendfree = true;
                                }
                            } else {
                                $sendfree = true;
                            }
                        }
                    }
                }


                if ($seckillinfo && $seckillinfo['status'] == 0) {
                    //秒杀不参与满额包邮
                } else {
                    if ($totalprice_array[$g['goodsid']] >= floatval($g['edmoney']) && floatval($g['edmoney']) > 0) { //单品满额包邮
                        $gareas = explode(";", $g['edareas']);
                        if (empty($gareas)) {
                            $sendfree = true;
                        } else {
                            if (!empty($address)) {
                                if (!in_array($address['city'], $gareas)) {
                                    $sendfree = true;
                                }
                            } else if (!empty($member['city'])) {
                                if (!in_array($member['city'], $gareas)) {
                                    $sendfree = true;
                                }
                            } else {
                                $sendfree = true;
                            }
                        }
                    }
                }

            }

            //读取快递信息
            if ($g['dispatchtype'] == 1) {
                //使用统一邮费

                //是否设置了不配送城市
                if (!empty($user_city)) {
                    $citys = m('dispatch')->getAllNoDispatchAreas();
                    if (!empty($citys)) {
                        if (in_array($user_city, $citys) && !empty($citys)) {
                            //如果此条包含不配送城市
                            $isnodispatch = 1;

                            $has_goodsid = 0;
                            if (!empty($nodispatch_array['goodid'])) {
                                if (in_array($g['goodsid'], $nodispatch_array['goodid'])) {
                                    $has_goodsid = 1;
                                }
                            }

                            if ($has_goodsid == 0) {
                                $nodispatch_array['goodid'][] = $g['goodsid'];
                                $nodispatch_array['title'][] = $g['title'];
                                $nodispatch_array['city'] = $user_city;
                            }
                        }
                    }
                }

                if ($g['dispatchprice'] > 0 && !$sendfree && $isnodispatch == 0) {
                    //固定运费不累计

                    $dispatch_merch[$merchid] += $g['dispatchprice'];

                    if ($seckillinfo && $seckillinfo['status'] == 0) {

                        $seckill_dispatchprice += $g['dispatchprice'];

                    }else{
                        $dispatch_price += $g['dispatchprice'];
                    }
                }


            } else if ($g['dispatchtype'] == 0) {
                //使用快递模板
                $g['dispatchid']=Dispage::get_dispatch_id($g['goodsid'],$_W['uniacid']);//wsq获取原始配送方式ID
                if (empty($g['dispatchid'])) {
                    //默认快递
                    $dispatch_data = m('dispatch')->getDefaultDispatch($merchid,$g['disgoods_id'],$g['goodsid']);//wsq
                } else {
                    
                    $dispatch_data = m('dispatch')->getOneDispatch($g['dispatchid'],$g['disgoods_id']);//wsq
                }
                if (empty($dispatch_data)) {
                    //最新的一条快递信息
                    $dispatch_data = m('dispatch')->getNewDispatch($merchid);
                }
                //是否设置了不配送城市
                if (!empty($dispatch_data)) {
                    $dkey = $dispatch_data['id'];

                    if (!empty($user_city)) {

                        $citys = m('dispatch')->getAllNoDispatchAreas($dispatch_data['nodispatchareas']);

                        if (!empty($citys)) {
                            if (in_array($user_city, $citys) && !empty($citys)) {
                                //如果此条包含不配送城市
                                $isnodispatch = 1;

                                $has_goodsid = 0;
                                if (!empty($nodispatch_array['goodid'])) {
                                    if (in_array($g['goodsid'], $nodispatch_array['goodid'])) {
                                        $has_goodsid = 1;
                                    }
                                }

                                if ($has_goodsid == 0) {
                                    $nodispatch_array['goodid'][] = $g['goodsid'];
                                    $nodispatch_array['title'][] = $g['title'];
                                    $nodispatch_array['city'] = $user_city;
                                }
                            }
                        }

                    }

                    if (!$sendfree && $isnodispatch == 0) {
                        //配送区域
                        $areas = unserialize($dispatch_data['areas']);
                        if ($dispatch_data['calculatetype'] == 1) {
                            //按件计费
                            $param = $g['total'];
                        } else {
                            //按重量计费
                            $param = $g['weight'] * $g['total'];
                        }

                        if (array_key_exists($dkey, $dispatch_array)) {
                            $dispatch_array[$dkey]['param'] += $param;
                        } else {
                            $dispatch_array[$dkey]['data'] = $dispatch_data;
                            $dispatch_array[$dkey]['param'] = $param;
                        }


                        if($seckillinfo && $seckillinfo['status']==0) {
                            if (array_key_exists($dkey, $dispatch_array)) {
                                $dispatch_array[$dkey]['seckillnums'] += $param;
                            } else {
                                $dispatch_array[$dkey]['seckillnums'] = $param;
                            }
                        }
                    }
                }
            }

        }


        if (!empty($dispatch_array)) {
            foreach ($dispatch_array as $k => $v) {
                $dispatch_data = $dispatch_array[$k]['data'];
                $param = $dispatch_array[$k]['param'];
                $areas = unserialize($dispatch_data['areas']);

                if (!empty($address)) {

                    //用户有默认地址
                    $dprice = m('dispatch')->getCityDispatchPrice($areas, $address['city'], $param, $dispatch_data);

                } else if (!empty($member['city'])) {
                    //设置了城市需要判断区域设置
                    $dprice = m('dispatch')->getCityDispatchPrice($areas, $member['city'], $param, $dispatch_data);
                } else {
                    //如果会员还未设置城市 ，默认邮费
                    $dprice = m('dispatch')->getDispatchPrice($param, $dispatch_data);
                }


                $merchid = $dispatch_data['merchid'];
                $dispatch_merch[$merchid] += $dprice;

                if( $v['seckillnums']>0){
                    $seckill_dispatchprice+=$dprice;
                }else{
                    $dispatch_price += $dprice;
                }

            }
        }



        //判断多商户是否满额包邮
        if (!empty($merch_array)) {

            foreach ($merch_array as $key => $value) {
                $merchid = $key;

                if ($merchid > 0) {
                    $merchset = $value['set'];
                    if (!empty($merchset['enoughfree'])) {
                        if (floatval($merchset['enoughorder']) <= 0) {
                            $dispatch_price = $dispatch_price - $dispatch_merch[$merchid];
                            $dispatch_merch[$merchid] = 0;
                        } else {
                            if ($merch_array[$merchid]['ggprice'] >= floatval($merchset['enoughorder'])) {
                                //订单大于设定的包邮金额
                                if (empty($merchset['enoughareas'])) {
                                    //如果不限制区域，包邮
                                    $dispatch_price = $dispatch_price - $dispatch_merch[$merchid];
                                    $dispatch_merch[$merchid] = 0;
                                } else {
                                    //如果限制区域
                                    $areas = explode(";", $merchset['enoughareas']);
                                    if (!empty($address)) {
                                        if (!in_array($address['city'], $areas)) {
                                            $dispatch_price = $dispatch_price - $dispatch_merch[$merchid];
                                            $dispatch_merch[$merchid] = 0;
                                        }
                                    } else if (!empty($member['city'])) {
                                        //设置了城市需要判断区域设置
                                        if (!in_array($member['city'], $areas)) {
                                            $dispatch_price = $dispatch_price - $dispatch_merch[$merchid];
                                            $dispatch_merch[$merchid] = 0;
                                        }
                                    } else if (empty($member['city'])) {
                                        //如果会员还未设置城市 ，默认邮费
                                        $dispatch_price = $dispatch_price - $dispatch_merch[$merchid];
                                        $dispatch_merch[$merchid] = 0;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //营销宝满额包邮
        if ($saleset) {

            if (!empty($saleset['enoughfree'])) {

                //是否满足营销宝满额包邮
                $saleset_free = 0;

                if ($loop == 0) {
                    if (floatval($saleset['enoughorder']) <= 0) {
                        $saleset_free = 1;
                    } else {

                        if ($realprice - $seckill_payprice >= floatval($saleset['enoughorder'])) {
                            //订单大于设定的包邮金额
                            if (empty($saleset['enoughareas'])) {
                                //如果不限制区域，包邮
                                $saleset_free = 1;
                            } else {
                                //如果限制区域
                                $areas = explode(";", $saleset['enoughareas']);
                                if (!empty($address)) {
                                    if (!in_array($address['city'], $areas)) {
                                        $saleset_free = 1;
                                    }
                                } else if (!empty($member['city'])) {
                                    //设置了城市需要判断区域设置
                                    if (!in_array($member['city'], $areas)) {
                                        $saleset_free = 1;
                                    }
                                } else if (empty($member['city'])) {
                                    //如果会员还未设置城市 ，默认邮费
                                    $saleset_free = 1;
                                }
                            }
                        }
                    }
                }

                if ($saleset_free == 1) {
                    $is_nofree = 0;
                    if (!empty($saleset['goodsids'])) {
                        foreach ($goods as $k => $v) {
                            if (!in_array($v['goodsid'], $saleset['goodsids'])) {
                                unset($goods[$k]);
                            } else {
                                $is_nofree = 1;
                            }
                        }
                    }

                    if ($is_nofree == 1 && $loop == 0) {
                        $new_data = $this->getOrderDispatchPrice($goods, $member, $address, $saleset, $merch_array, $t, 1);
                        $dispatch_price = $new_data['dispatch_price'];
                    } else {
                        if ($saleset_free == 1) {
                            $dispatch_price = 0;
                        }
                    }
                }
            }
        }

        if ($dispatch_price == 0) {
            foreach ($dispatch_merch as &$dm) {
                $dm = 0;
            }
            unset($dm);
        }

        if (!empty($nodispatch_array)) {
            $nodispatch = '商品';
            foreach ($nodispatch_array['title'] as $k => $v) {
                $nodispatch .= $v . ',';
            }
            $nodispatch = trim($nodispatch, ',');
            $nodispatch .= '不支持配送到' . $nodispatch_array['city'];
            $nodispatch_array['nodispatch'] = $nodispatch;
            $nodispatch_array['isnodispatch'] = 1;
        }

        $data = array();
        $data['dispatch_price'] = $dispatch_price + $seckill_dispatchprice;
        $data['dispatch_merch'] = $dispatch_merch;
        $data['nodispatch_array'] = $nodispatch_array;
        $data['seckill_dispatch_price'] = $seckill_dispatchprice;

        return $data;
    }

    //修改总订单的价格
    function changeParentOrderPrice($parent_order)
    {
        global $_W;

        $id = $parent_order['id'];
        $item = pdo_fetch("SELECT price,ordersn2,dispatchprice,changedispatchprice FROM " . tablename('ewei_shop_order') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $id, ':uniacid' => $_W['uniacid']));

        if (!empty($item)) {
            $orderupdate = array();
            $orderupdate['price'] = $item['price'] + $parent_order['price_change'];
            $orderupdate['ordersn2'] = $item['ordersn2'] + 1;

            $orderupdate['dispatchprice'] = $item['dispatchprice'] + $parent_order['dispatch_change'];
            $orderupdate['changedispatchprice'] = $item['changedispatchprice'] + $parent_order['dispatch_change'];

            if (!empty($orderupdate)) {
                pdo_update('ewei_shop_order', $orderupdate, array('id' => $id, 'uniacid' => $_W['uniacid']));
            }
        }
    }

    //计算订单中的佣金
    function getOrderCommission($orderid, $agentid = 0)
    {
        global $_W;

        if (empty($agentid)) {
            $item = pdo_fetch('select agentid from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid Limit 1', array('id' => $orderid, ':uniacid' => $_W['uniacid']));
            if (!empty($item)) {
                $agentid = $item['agentid'];
            }
        }

        $level = 0;
        $pc = p('commission');
        if ($pc) {
            $pset = $pc->getSet();
            $level = intval($pset['level']);
        }

        $commission1 = 0;
        $commission2 = 0;
        $commission3 = 0;
        $m1 = false;
        $m2 = false;
        $m3 = false;
        if (!empty($level)) {
            if (!empty($agentid)) {
                $m1 = m('member')->getMember($agentid);
                if (!empty($m1['agentid'])) {
                    $m2 = m('member')->getMember($m1['agentid']);
                    if (!empty($m2['agentid'])) {
                        $m3 = m('member')->getMember($m2['agentid']);
                    }
                }
            }
        }

        //订单商品
        $order_goods = pdo_fetchall('select g.id,g.title,g.thumb,g.goodssn,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, og.total,og.price,og.optionname as optiontitle, og.realprice,og.changeprice,og.oldprice,og.commission1,og.commission2,og.commission3,og.commissions,og.diyformdata,og.diyformfields from ' . tablename('ewei_shop_order_goods') . ' og '
            . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid '
            . ' where og.uniacid=:uniacid and og.orderid=:orderid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $orderid));

        foreach ($order_goods as &$og) {

            if (!empty($level) && !empty($agentid)) {
                $commissions = iunserializer($og['commissions']);
                if (!empty($m1)) {
                    if (is_array($commissions)) {
                        $commission1 += isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
                    } else {
                        $c1 = iunserializer($og['commission1']);
                        $l1 = $pc->getLevel($m1['openid']);
                        $commission1 += isset($c1['level' . $l1['id']]) ? $c1['level' . $l1['id']] : $c1['default'];
                    }
                }
                if (!empty($m2)) {
                    if (is_array($commissions)) {
                        $commission2 += isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
                    } else {
                        $c2 = iunserializer($og['commission2']);
                        $l2 = $pc->getLevel($m2['openid']);
                        $commission2 += isset($c2['level' . $l2['id']]) ? $c2['level' . $l2['id']] : $c2['default'];
                    }
                }
                if (!empty($m3)) {
                    if (is_array($commissions)) {
                        $commission3 += isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
                    } else {
                        $c3 = iunserializer($og['commission3']);
                        $l3 = $pc->getLevel($m3['openid']);
                        $commission3 += isset($c3['level' . $l3['id']]) ? $c3['level' . $l3['id']] : $c3['default'];
                    }
                }
            }
        }
        unset($og);

        $commission = $commission1 + $commission2 + $commission3;

        return $commission;
    }


    //检查订单中是否有下架商品
    function checkOrderGoods($orderid)
    {

        global $_W;

        $flag = 0;
        $msg = '订单中的商品' . '<br/>';
        $uniacid = $_W['uniacid'];
        $sql = "select g.id,g.title,g.status,g.deleted"
            . " from " . tablename('ewei_shop_goods') . " g left join  " . tablename('ewei_shop_order_goods') . " og on g.id=og.goodsid and g.uniacid=og.uniacid"
            . " where og.orderid=:orderid and og.uniacid=:uniacid";
        $list = pdo_fetchall($sql, array(':uniacid' => $uniacid, ':orderid' => $orderid));

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (empty($v['status']) || !empty($v['deleted'])) {
                    $flag = 1;
                    $msg .= $v['title'] . '<br/>';
                }
            }
            if ($flag == 1) {
                $msg .= '已下架,不能付款!';
            }
        }

        $data = array();
        $data['flag'] = $flag;
        $data['msg'] = $msg;
        return $data;
    }


    function  get_tax($order_goods,$dispatch_price,$goodsprice,$alldeduct){
        require_once EWEI_SHOPV2_TAX_CORE. '/tax_core.php';
        $tax=new Taxcore();
        $out_goods=array();
       // var_dump($order_goods);
        foreach($order_goods as $key=>$goods){
            $out_goods[$key]['id']=$goods['goodsid'];
            $out_goods[$key]['price']=$goods['ggprice']/$goods['total'];
            $out_goods[$key]['total']=$goods['total'];
            $out_goods[$key]['vat_rate']=$goods['vat_rate'];
            $out_goods[$key]['consumption_tax']=$goods['consumption_tax'];
        }

        $retrundata=$tax->get_dprice_order($out_goods,$dispatch_price,$goodsprice,$alldeduct);

        $out_goods=$retrundata['order_goods'];
        $depostfee=$retrundata['depostfee'];//总运费
        $out_goods=$tax->get_tax($out_goods);

        $rate=0;
        $consumption_tax=0;
        foreach ($out_goods as $goods) {
            $r[$goods['id']]=$goods;
            $consumption_tax+=$goods['tax']['consumption_tax']*$goods['total'];
            $rate+=$goods['tax']['rate']*$goods['total'];
        }
        foreach($order_goods as &$goods){
           $goods['tax']= $r[$goods['goodsid']]['tax'];
           $goods['shipping_fee']=$r[$goods['goodsid']]['shipping_fee'];
           $goods['dprice']=$r[$goods['goodsid']]['dprice'];
        }
        
        unset($goods);
        return array('order_goods'=>$order_goods,'depostfee'=>$depostfee,'tax_rate'=>$rate,'tax_consumption'=>$consumption_tax);
    }
    function get_dis_tax($order_goods,$address){
        global $_W;
        require_once EWEI_SHOPV2_TAX_CORE. '/tax_core.php';
        $tax=new Taxcore();
        $out_goods=array();
        //var_dump($order_goods);\
        
        $dispriceamount=0;
        $dispatch_array=array();
        $disprice_dispatch_price=0;
        foreach($order_goods as $key=>$goods){
            $out_goods[$key]['total']=$goods['total'];
            $out_goods[$key]['vat_rate']=$goods['vat_rate'];
            $out_goods[$key]['consumption_tax']=$goods['consumption_tax'];
            $type=Dispage::get_disType($goods['disgoods_id'],$_W['uniacid']);
           $disprice=Dispage::get_disprice($goods['goodsid'],$_W['uniacid'],$goods['optionid']);
            $out_goods[$key]['price']=$disprice;
            $dispriceamount+=$disprice*$goods['total'];
            if($goods['dispatchid']!=0){
                $dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid'],$goods['disgoods_id']);//wsq  
            }else{
                $dispatch_data = m('dispatch')->getDefaultDispatch(0,$goods['disgoods_id'],$goods['goodsid']);//
            }
          
            if ($dispatch_data['calculatetype'] == 1) {
                            //按件计费
                $param = $goods['total'];
            } else {
                            //按重量计费
                $param = $goods['weight'] * $goods['total'];
            }
            $dkey = $dispatch_data['id'];
            if (array_key_exists($dkey, $dispatch_array)) {
                    $dispatch_array[$dkey]['param'] += $param;
                } else {
                    $dispatch_array[$dkey]['data'] = $dispatch_data;
                    $dispatch_array[$dkey]['param'] = $param;
            }
        }

        //$dprice = m('dispatch')->getCityDispatchPrice($areas, $address['city'], $param, $dispatch_data);
       
     
        if (!empty($dispatch_array)) {
            foreach ($dispatch_array as $k => $v) {
                $dispatch_data = $dispatch_array[$k]['data'];
                $param = $dispatch_array[$k]['param'];
                $areas = unserialize($dispatch_data['areas']);
                if (!empty($address)) {

                    //用户有默认地址
                    $dprice = m('dispatch')->getCityDispatchPrice($areas, $address['city'], $param, $dispatch_data);

                } else if (!empty($member['city'])) {
                    //设置了城市需要判断区域设置
                    $dprice = m('dispatch')->getCityDispatchPrice($areas, $member['city'], $param, $dispatch_data);
                } else {
                    //如果会员还未设置城市 ，默认邮费
                    $dprice = m('dispatch')->getDispatchPrice($param, $dispatch_data);
                }
            }
            $disprice_dispatch_price+=$dprice;
        }
      
        //var_dump($disprice_dispatch_price);
        //die();
        if($type){
            //计算代理商运费
            $retrundata=$tax->get_dprice_order($out_goods,$disprice_dispatch_price,$dispriceamount);
            $out_goods=$retrundata['order_goods'];
            $depostfee=$retrundata['depostfee'];//总运费
            $out_goods=$tax->get_tax($out_goods);
            $alltax=0;
            foreach($out_goods as $goods){
                $alltax+=$goods['tax']['consolidated']*$goods['total'];
            }
            return array("disprice"=>$dispriceamount+$disprice_dispatch_price,'dis_shoping_fee'=>$disprice_dispatch_price,'alltax'=>$alltax);
        }
        return 0;
    }

}
