<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends MobileLoginPage {

    //多商户
    protected function merchData() {
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }

        return array(
            'is_openmerch' => $is_openmerch,
            'merch_plugin' => $merch_plugin,
            'merch_data' => $merch_data
        );
    }

    function main() {
        global $_W,$_GPC;
        $trade = m('common')->getSysset('trade');

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        if ($is_openmerch == 1) {
            include $this->template('merch/order/index');
        }else{
            include $this->template();
        }
    }

    function get_list(){

        global $_W,$_GPC;
        $uniacid =$_W['uniacid'];
        $openid =$_W['openid'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 50;
        $show_status = $_GPC['status'];
        $r_type = array( '0' => '退款', '1' => '退货退款', '2' => '换货');
        $condition = " and openid=:openid and ismr=0 and deleted=0 and uniacid=:uniacid ";
        $params = array(
            ':uniacid' => $uniacid,
            ':openid' => $openid
        );

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        $condition .= " and merchshow=0 ";

        if ($show_status != '') {
            $show_status =intval($show_status);

            switch ($show_status)
            {
                case 0:
                    $condition.=' and status=0 and paytype!=3';
                    break;
                case 2:
                    $condition.=' and (status=2 or status=0 and paytype=3)';
                    break;
                case 4:
                    $condition.=' and refundstate>0';
                    break;
                case 5:
                    $condition .= " and userdeleted=1 ";
                    break;
                default:
                    $condition.=' and status=' . intval($show_status);
            }

            if ($show_status != 5) {
                $condition .= " and userdeleted=0 ";
            }
        } else {
            $condition .= " and userdeleted=0 ";
        }

        $com_verify = com('verify');

        $s_string = '';
        if (p('ccard')) {
            $s_string = ',ccard';
        }

        $list = pdo_fetchall("select id,addressid,ordersn,price,dispatchprice,status,iscomment,isverify,
verified,verifycode,verifytype,iscomment,refundid,expresscom,express,expresssn,finishtime,`virtual`,
paytype,expresssn,refundstate,dispatchtype,verifyinfo,merchid,isparent,userdeleted{$s_string}
 from " . tablename('ewei_shop_order') . " where 1 {$condition} order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . " where 1 {$condition}", $params);

        $refunddays = intval($_W['shopset']['trade']['refunddays']);
        if ($is_openmerch == 1) {
            $merch_user = $merch_plugin->getListUser($list,'merch_user');
        }

        foreach ($list as &$row) {

            $param = array();

            if ($row['isparent'] == 1) {
                $scondition = " og.parentorderid=:parentorderid";
                $param[':parentorderid'] = $row['id'];
            } else {
                $scondition = " og.orderid=:orderid";
                $param[':orderid'] = $row['id'];
            }

            //所有商品
            $sql = "SELECT og.goodsid,og.total,g.title,g.thumb,g.status,og.price,og.optionname as optiontitle,og.optionid,op.specs,g.merchid,og.seckill,og.seckill_taskid FROM " . tablename('ewei_shop_order_goods') . " og "
                . " left join " . tablename('ewei_shop_goods') . " g on og.goodsid = g.id "
                . " left join " . tablename('ewei_shop_goods_option') . " op on og.optionid = op.id "
                . " where $scondition order by og.id asc";

            $goods = pdo_fetchall($sql, $param);

            $ismerch = 0;
            $merch_array = array();

            foreach($goods as &$r){
                $r['seckilltask'] = false;
                if($r['seckill']){
                    $r['seckill_task'] = plugin_run('seckill::getTaskInfo',$r['seckill_taskid']);
                }

                $merchid = $r['merchid'];
                $merch_array[$merchid]= $merchid;
                //读取规格的图片
                if (!empty($r['specs'])) {
                    $thumb = m('goods')->getSpecThumb($r['specs']);
                    if (!empty($thumb)) {
                        $r['thumb'] = $thumb;
                    }
                }
            }
            unset($r);

            if (!empty($merch_array)) {
                if (count($merch_array) > 1) {
                    $ismerch = 1;
                }
            }
            $goods = set_medias($goods, 'thumb');

            if(empty($goods)){
                $goods = array();
            }
            foreach($goods as &$r){
                $r['thumb'].="?t=".random(50);
            }
            unset($r);

            $goods_list = array();
            if ($ismerch) {
                $getListUser = $merch_plugin->getListUser($goods);
                $merch_user = $getListUser['merch_user'];

                foreach ($getListUser['merch'] as $k => $v) {
                    if (empty($merch_user[$k]['merchname'])) {
                        $goods_list[$k]['shopname'] = $_W['shopset']['shop']['name'];
                    }else{
                        $goods_list[$k]['shopname'] = $merch_user[$k]['merchname'];
                    }
                    $goods_list[$k]['goods'] = $v;
                }
            } else {
                if ($merchid == 0) {
                    $goods_list[0]['shopname'] = $_W['shopset']['shop']['name'];
                } else {
                    $merch_data = $merch_plugin->getListUserOne($merchid);
                    $goods_list[0]['shopname'] = $merch_data['merchname'];
                }
                $goods_list[0]['goods'] = $goods;
            }


            $row['goods'] = $goods_list;
            $row['goods_num'] = count($goods);
            $statuscss = "text-cancel";

            switch ($row['status']) {
                case "-1":
                    $status = "已取消";
                    break;
                case "0":
                    if ($row['paytype'] == 3) {

                        $status = "待发货";
                    } else {
                        $status = "待付款";
                    }
                    $statuscss = "text-cancel";
                    break;
                case "1":
                    if ($row['isverify'] == 1) {
                        $status = "使用中";
                    } else if (empty($row['addressid'])) {
                        if (!empty($row['ccard'])) {
                            $status = "充值中";
                        } else {
                            $status = "待取货";
                        }
                    } else {
                        $status = "待发货";
                    }
                    $statuscss = "text-warning";
                    break;
                case "2": $status = "待收货";
                    $statuscss = "text-danger";
                    break;
                case "3":
                    if (empty($row['iscomment'])) {
                        if ($show_status == 5) {
                            $status = "已完成";
                        } else {
                            $status = empty($_W['shopset']['trade']['closecomment']) ? "待评价" : "已完成";

                        }
                    } else {
                        $status = "交易完成";
                    }
                    $statuscss = "text-success";
                    break;
            }
            $row['statusstr'] = $status;
            $row['statuscss'] = $statuscss;
            if ($row['refundstate'] > 0 && !empty($row['refundid'])) {

                $refund = pdo_fetch("select * from " . tablename('ewei_shop_order_refund') . ' where id=:id and uniacid=:uniacid and orderid=:orderid limit 1'
                    , array(':id' => $row['refundid'], ':uniacid' => $uniacid, ':orderid' => $row['id']));

                if (!empty($refund)) {
                    $row['statusstr'] = '待' . $r_type[$refund['rtype']];
                }
            }
            //是否可以退款
            $canrefund = false;
            /*if ($row['status'] == 1 || $row['status'] == 2) {
                $canrefund = true;
                if ($row['status'] == 2 && $row['price'] == $row['dispatchprice']) {
                    if ($row['refundstate'] > 0) {
                        $canrefund = true;
                    } else {
                        $canrefund = false;
                    }
                }
            } else if ($row['status'] == 3) {
                if ($row['isverify'] != 1 && empty($row['virtual'])) { //如果不是核销或虚拟物品，则可以退货
                    if ($row['refundstate'] > 0) {
                        $canrefund = true;
                    } else {
                        if ($refunddays > 0) {
                            $days = intval((time() - $row['finishtime']) / 3600 / 24);
                            if ($days <= $refunddays) {
                                $canrefund = true;
                            }
                        }
                    }
                }
            }*/
            $row['canrefund'] = $canrefund;
            //是否可以核销
            $row['canverify'] = false;

            $canverify = false;

            if ($com_verify) {
                $showverify =  $row['dispatchtype'] || $row['isverify'];
                if ($row['isverify']) {

                    if ($row['verifytype'] == 0 || $row['verifytype'] == 1) {
                        $vs = iunserializer($row['verifyinfo']);
                        $verifyinfo = array(
                            array(
                                'verifycode' => $row['verifycode'],
                                'verified' => $row['verifytype'] == 0 ? $row['verified'] : count($vs) >= $row['goods'][0]['goods']['total']
                            )
                        );
                        if ($row['verifytype'] == 0) {
                            $canverify = empty($row['verified']) && $showverify;
                        } else if ($row['verifytype'] == 1) {
                            $canverify = count($vs) < $row['goods'][0]['goods']['total'] && $showverify;
                        }

                    } else {

                        $verifyinfo = iunserializer($row['verifyinfo']);

                        $last = 0;
                        foreach ($verifyinfo as $v) {
                            if (!$v['verified']) {
                                $last++;
                            }
                        }
                        $canverify = $last > 0 && $showverify;
                    }

                } else if (!empty($row['dispatchtype'])) {
                    $canverify = $row['status'] == 1 && $showverify;
                }
            }

            $row['canverify']  = $canverify;

            if ($is_openmerch == 1) {
                $row['merchname'] = $merch_user[$row['merchid']]['merchname'] ? $merch_user[$row['merchid']]['merchname'] : $_W['shopset']['shop']['name'];
            }
        }
        unset($row);

        show_json(1,array('list'=>$list,'pagesize'=>$psize,'total'=>$total));
    }

    function alipay() {
        global $_W, $_GPC;
        $url = urldecode($_GPC['url']);
        if(!is_weixin()){
            header('location: ' . $url);
            exit;
        }
        include $this->template();
    }

    function detail() {

        global $_W, $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $member = m('member')->getMember($openid, true);
        $orderid = intval($_GPC['id']);

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

        if ($order['merchshow'] == 1) {
            header('location: ' . mobileUrl('order'));
            exit;
        }

        if ($order['userdeleted'] == 2) {
            $this->message('订单已经被删除!','','error');
        }

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        $merchid = $order['merchid'];
        //商品信息
        $diyform_plugin = p('diyform');
        $diyformfields = "";
        if ($diyform_plugin) {
            $diyformfields = ",og.diyformfields,og.diyformdata";
        }

        $param = array();
        $param[':uniacid'] = $_W['uniacid'];

        if ($order['isparent'] == 1) {
            $scondition = " og.parentorderid=:parentorderid";
            $param[':parentorderid'] = $orderid;
        } else {
            $scondition = " og.orderid=:orderid";
            $param[':orderid'] = $orderid;
        }

        $condition1 = '';
        if(p('ccard')) {
            $condition1 .= ',g.ccardexplain,g.ccardtimeexplain';
        }

        $goodsid_array =array();
        $goods = pdo_fetchall("select og.goodsid,og.price,g.title,g.thumb,g.status, g.cannotrefund, og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,g.storeids,og.seckill,og.seckill_taskid{$diyformfields}{$condition1}  from " . tablename('ewei_shop_order_goods') . " og "
            . " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
            . " where $scondition and og.uniacid=:uniacid ", $param);

        foreach($goods as &$g){

            $g['seckill_task'] = false;
            if($g['seckill']){
                $g['seckill_task'] = plugin_run('seckill::getTaskInfo',$g['seckill_taskid']);
            }
        }
        unset($g);
        //商品是否支持退换货
        $goodsrefund = true;

        if(!empty($goods)) {
            foreach ($goods as &$g) {
                $goodsid_array[] = $g['goodsid'];
                if (!empty($g['optionid'])) {
                    $thumb = m('goods')->getOptionThumb($g['goodsid'], $g['optionid']);
                    if (!empty($thumb)) {
                        $g['thumb'] = $thumb;
                    }
                }
                if(!empty($g['cannotrefund']) && $order['status']==2){
                    $goodsrefund = false;
                }
            }
            unset($g);
        }
        $diyform_flag = 0;

        if ($diyform_plugin) {
            foreach ($goods as &$g) {
                $g['diyformfields'] = iunserializer($g['diyformfields']);
                $g['diyformdata'] = iunserializer($g['diyformdata']);
                unset($g);
            }

            //订单统一模板
            if (!empty($order['diyformfields']) && !empty($order['diyformdata'])) {
                $order_fields = iunserializer($order['diyformfields']);
                $order_data = iunserializer($order['diyformdata']);
            }
        }

        //收货地址
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

        //门店
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
        $showverify = false;  //是否显示消费码
        $canverify = false;  //是否可以核销
        $verifyinfo = false;
        if (com('verify')) {
            $showverify = $order['dispatchtype'] || $order['isverify'];

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
                        $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                    } else {
                        $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
                    }
                } else {
                    if ($merchid > 0) {
                        $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                    } else {
                        $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
                    }
                }

                if ($order['verifytype'] == 0 || $order['verifytype'] == 1) {
                    $vs = iunserializer($order['verifyinfo']);
                 
                    $verifyinfo = array(
                        array(
                            'verifycode' => $order['verifycode'],
                            'verified' => $order['verifytype'] == 0 ?$order['verified']: count($vs)>=$goods[0]['total']
                        )
                    );
                    if( $order['verifytype']==0 ) {
                        $canverify = empty($order['verified']) && $showverify;
                    } else if( $order['verifytype']==1 ){
                        $canverify = count($vs)<$goods[0]['total']  && $showverify;
                    }

                } else {
                    $verifyinfo = iunserializer($order['verifyinfo']);

                    $last = 0;
                    foreach($verifyinfo as $v){
                        if(!$v['verified']){
                            $last++;
                        }
                    }
                    $canverify = $last>0 && $showverify;
                }
            }
            else if(!empty($order['dispatchtype'])){

                $verifyinfo = array(
                    array(
                        'verifycode' => $order['verifycode'],
                        'verified' => $order['status'] == 3
                    )
                );

                $canverify = $order['status']==1 && $showverify;
            }

        }
        $order['canverify'] = $canverify;
        $order['showverify'] = $showverify;

        //虚拟物品信息
        $order['virtual_str'] = str_replace("\n", "<br/>", $order['virtual_str']);

        //是否可以退款
        if ($order['status'] == 1 || $order['status'] == 2) {
            $canrefund = true;
            if ($order['status'] == 2 && $order['price'] == $order['dispatchprice']) {
                if ($order['refundstate'] > 0) {
                    $canrefund = true;
                } else {
                    $canrefund = false;
                }
            }
        } else if ($order['status'] == 3) {
            if ($order['isverify'] != 1 && empty($order['virtual'])) { //如果不是核销或虚拟物品，则可以退货
                if ($order['refundstate'] > 0) {
                    $canrefund = true;
                } else {
                    $tradeset = m('common')->getSysset('trade');
                    $refunddays = intval($tradeset['refunddays']);

                    if ($refunddays > 0) {
                        $days = intval((time() - $order['finishtime']) / 3600 / 24);
                        if ($days <= $refunddays) {
                            $canrefund = true;
                        }
                    }
                }

            }
        }

        if(!$goodsrefund && $canrefund){
            $canrefund = false;
        }


        if(p('ccard')) {

            if(!empty($order['ccard']) && $order['status'] > 1) {
                $canrefund = false;
            }

            $comdata = m('common')->getPluginset('commission');
            if (!empty($comdata['become_goodsid']) && !empty($goodsid_array)) {
                if(in_array($comdata['become_goodsid'], $goodsid_array)) {
                    $canrefund = false;
                }
            }
        }

        $order['canrefund'] = $canrefund;

        //如果发货，查找第一条物流
        $express = false;
        if ($order['status'] >= 2 && empty($order['isvirtual']) && empty($order['isverify'])) {
            $expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);
            if (count($expresslist) > 0) {
                $express = $expresslist[0];
            }
        }
        $shopname = $_W['shopset']['shop']['name'];

        if (!empty($order['merchid']) && $is_openmerch == 1)
        {
            $merch_user = $merch_plugin->getListUser($order['merchid']);
            $shopname = $merch_user['merchname'];
            $shoplogo = tomedia($merch_user['logo']);
        }
        include $this->template();
    }

    function express() {
        global $_W, $_GPC;

        global $_W, $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $orderid = intval($_GPC['id']);

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
        if (empty($order['addressid'])) {
            $this->message('订单非快递单，无法查看物流信息!');
        }
        if ($order['status'] < 2) {
            $this->message('订单未发货，无法查看物流信息!');
        }
        //商品信息
        $goods = pdo_fetchall("select og.goodsid,og.price,g.title,g.thumb,og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,g.storeids{$diyformfields}  from " . tablename('ewei_shop_order_goods') . " og "
            . " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid "
            . " where og.orderid=:orderid and og.uniacid=:uniacid ", array(':uniacid' => $uniacid, ':orderid' => $orderid));

        $expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);

        include $this->template();
    }


}
