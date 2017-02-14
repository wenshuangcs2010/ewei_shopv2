<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Single_EweiShopV2Page extends PluginWebPage {

    function main(){
        global $_W, $_GPC;
        // 定义 支付方式
        $paytype = array(
            '0' => array('css' => 'default', 'name' => '未支付'),
            '1' => array('css' => 'danger', 'name' => '余额支付'),
            '11' => array('css' => 'default', 'name' => '后台付款'),
            '2' => array('css' => 'danger', 'name' => '在线支付'),
            '21' => array('css' => 'success', 'name' => '微信支付'),
            '22' => array('css' => 'warning', 'name' => '支付宝支付'),
            '23' => array('css' => 'warning', 'name' => '银联支付'),
            '3' => array('css' => 'primary', 'name' => '货到付款'),
        );
        // 定义 订单状态
        $orderstatus = array(
            '0' => array('css' => 'danger', 'name' => '待付款'),
            '1' => array('css' => 'info', 'name' => '待发货'),
            '2' => array('css' => 'warning', 'name' => '待收货'),
            '3' => array('css' => 'success', 'name' => '已完成')
        );

        if (empty($starttime) && empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $printset = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_exhelper_sys') . " WHERE uniacid=:uniacid and merchid=0 limit 1", array(':uniacid' => $_W['uniacid']));

        $lodopUrl_ip = 'localhost';
        $lodopUrl_port = empty($printset['port'])?8000:$printset['port'];
        $lodopUrl = 'http://'.$lodopUrl_ip.":".$lodopUrl_port."/CLodopfuncs.js";

        load()->func('tpl');
        include $this->template();

    }
    // 获取订单基本信息
    function getdata(){
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];

        if($_W['ispost']){

            // 定义 基础 查询条件
            $condition = " o.uniacid = :uniacid and m.uniacid = :uniacid and o.deleted=0 and o.addressid<>0 and o.merchid=0 and o.isparent=0 ";
            $paras = array(':uniacid' => $_W['uniacid']);

            // 获取 支付方式
            if ($_GPC['paytype'] != '') {
                if ($_GPC['paytype'] == '2') {
                    $condition .= " AND ( o.paytype =21 or o.paytype=22 or o.paytype=23 )";
                } else {
                    $condition .= " AND o.paytype =" . intval($_GPC['paytype']);
                }
            }
            // 获取 订单状态
            $status = intval($_GPC['status']);
            $statuscondition = '';
            if ($status != '') {
                if ($status == '4') {
                    $statuscondition = " AND o.refundstate>0 and o.refundid<>0";
                } else if ($status == '5') {
                    $statuscondition = " AND o.refundtime<>0";
                } else if ($status=='1'){
                    $statuscondition = " AND ( o.status = 1 or (o.status=0 and o.paytype=3) )";
                } else if($status=='0'){
                    $statuscondition = " AND o.status = 0 and o.paytype<>3";
                } else {
                    $statuscondition = " AND o.status = ".intval($status);
                }
            }else{
                $statuscondition = " and o.status>-1 ";
            }
            // 获取 搜索时间
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }
            $searchtime = trim($_GPC['searchtime']);
            if (!empty($searchtime) && !empty($_GPC['starttime']) && !empty($_GPC['endtime']) && in_array($searchtime, array('create', 'pay', 'send', 'finish'))) {
                $starttime = strtotime($_GPC['starttime']);
                $endtime = strtotime($_GPC['endtime']);
                $condition .= " AND o.{$searchtime}time >= :starttime AND o.{$searchtime}time <= :endtime ";
                $paras[':starttime'] = $starttime;
                $paras[':endtime'] = $endtime;
            }
            // 获取 快递单打印状态
            $printstate = intval($_GPC['printstate']);
            if ($printstate!='') {
                $condition .= " AND o.printstate=".$printstate." ";
            }
            // 获取 发货单打印状态
            $printstate2 = $_GPC['printstate2'];
            if ($printstate2!='') {
                $condition .= " AND o.printstate2=".$printstate2." ";
            }
            $sqlcondition = '';
            // 获取关键字 与 查询类型
            if (!empty($_GPC['searchfield']) && !empty($_GPC['keyword'])) {
                $paras[':keyword'] = trim($_GPC['keyword']);
                $searchfield = trim(strtolower($_GPC['searchfield']));
                $keyword = trim($_GPC['keyword']);
                if ($searchfield == 'ordersn') {
                    $condition .= " AND o.ordersn LIKE '%{$keyword}%'";
                } else if ($searchfield == 'member') {
                    $condition .= " AND (m.realname LIKE '%{$keyword}%' or m.mobile LIKE '%{$keyword}%' or m.nickname LIKE '%{$keyword}%')";
                } else if ($searchfield == 'address') {
                    $condition .= " AND ( a.realname LIKE '%{$keyword}%' or a.mobile LIKE '%{$keyword}%' or o.carrier LIKE '%{$keyword}%' )";
                } else if ($searchfield == 'expresssn') {
                    $condition .= " AND o.expresssn LIKE '%{$keyword}%'";
                } else if ($searchfield == 'goodstitle') {
                    $sqlcondition =  " inner join ( select distinct og.orderid from " . tablename('ewei_shop_order_goods') . " og left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid where og.uniacid = '$uniacid' and og.merchid=0 and (locate(:keyword,g.title)>0)) gs on gs.orderid=o.id";
                } else if ($searchfield == 'goodssn') {
                    $sqlcondition =  " inner join ( select distinct og.orderid from " . tablename('ewei_shop_order_goods') . " og left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid where og.uniacid = '$uniacid' and og.merchid=0 and (locate(:keyword,g.goodssn)>0)) gs on gs.orderid=o.id";
                }
            }

            $sql = "select o.* ,a.realname ,m.nickname, d.dispatchname,m.nickname,r.status as refundstatus from " . tablename('ewei_shop_order') . " o"
                . " left join " . tablename('ewei_shop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,-1)<>-1"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " left join " . tablename('ewei_shop_dispatch') . " d on d.id = o.dispatchid "
                .$sqlcondition. " where $condition $statuscondition ORDER BY o.createtime DESC,o.status DESC  ";
            $orders = pdo_fetchall($sql, $paras);
            //print_r($orders);
            $list = array();
            foreach ($orders as $order) {
                if(!empty($order['address_send'])){
                    $order_address = iunserializer($order['address_send']);
                }else{
                    $order_address = iunserializer($order['address']);
                }


                if(!is_array($order_address)){
                    $member_address = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_address') . " WHERE id=:id and uniacid=:uniacid limit 1", array(':id'=>$order['addressid'],':uniacid'=>$_W['uniacid']));
                    $addresskey = $member_address['realname'] . $member_address['mobile'] . $member_address['province'] . $member_address['city'] . $member_address['area'] . $member_address['address'];
                }else{
                    $addresskey = $order_address['realname'] . $order_address['mobile'] . $order_address['province'] . $order_address['city'] . $order_address['area'] . $order_address['address'];
                }

                if (!isset($list[$addresskey])) {
                    $list[$addresskey] = array('realname' => $order_address['realname'], 'orderids' => array());
                }
                $list[$addresskey]['orderids'][] = $order['id'];
            }

            include $this->template('exhelper/print/single/print_tpl');
        }
    }
    // 获取订单明细
    function getorder(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $orderids = trim($_GPC['orderids']);
            if (empty($orderids)) {
                die('无任何订单，无法查看');
            }
            $arr = explode(',', $orderids);
            if (empty($arr)) {
                die('无任何订单，无法查看');
            }
            $paytype = array('0' => array('css' => 'default', 'name' => '未支付'),'1' => array('css' => 'danger', 'name' => '余额支付'),'11' => array('css' => 'default', 'name' => '后台付款'),'2' => array('css' => 'danger', 'name' => '在线支付'),
                '21' => array('css' => 'success', 'name' => '微信支付'),'22' => array('css' => 'warning', 'name' => '支付宝支付'),'23' => array('css' => 'warning', 'name' => '银联支付'),'3' => array('css' => 'primary', 'name' => '货到付款'),
            );
            $orderstatus = array(
                '-1' => array('css' => 'default', 'name' => '已关闭'),'0' => array('css' => 'danger', 'name' => '待付款'),'1' => array('css' => 'info', 'name' => '待发货'),
                '2' => array('css' => 'warning', 'name' => '待收货'),'3' => array('css' => 'success', 'name' => '已完成')
            );

            $sql = "select o.* , a.realname,a.mobile,a.province,a.city,a.area, d.dispatchname,m.nickname,r.status as refundstatus from " . tablename('ewei_shop_order') . " o"
                . " left join " . tablename('ewei_shop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,-1)<>-1"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " left join " . tablename('ewei_shop_dispatch') . " d on d.id = o.dispatchid "
                . " where o.id in ( " . implode(',', $arr) . ") and o.uniacid={$_W['uniacid']} and m.uniacid={$_W['uniacid']} and o.merchid=0 and o.isparent=0 ORDER BY o.createtime DESC,o.status DESC  ";
            $list = pdo_fetchall($sql, $paras);
            foreach ($list as &$value) {
                $s = $value['status'];
                $value['statusvalue'] = $s;
                $value['statuscss'] = $orderstatus[$s]['css'];
                $value['statusname'] = $orderstatus[$s]['name'];

                if ($s == -1) {
                    if ($value['refundstatus'] == 1) {
                        $value['status'] = '已退款';
                    }
                }

                $p = $value['paytype'];
                $value['css'] = $paytype[$p]['css'];
                $value['paytypename'] = $paytype[$p]['name'];
                $value['dispatchname'] = empty($value['addressid']) ? '自提' : $value['dispatchname'];
                if (empty($value['dispatchname'])) {
                    $value['dispatchname'] = '快递';
                }
                if ($value['isverify'] == 1) {
                    $value['dispatchname'] = "线下核销";
                } else if (!empty($value['virtual'])) {
                    $value['dispatchname'] = "虚拟物品(卡密)<br/>自动发货";
                }
                // 如果快递单地址不为空则调用
                if(!empty($value['address_send'])){
                    $addressa = iunserializer($value['address_send']);
                }else{
                    $addressa = iunserializer($value['address']);
                }
                if (is_array($addressa)) {
                    $value['realname'] = $addressa['realname'];
                    $value['mobile'] = $addressa['mobile'];
                    $value['province'] = $addressa['province'];
                    $value['city'] = $addressa['city'];
                    $value['area'] = $addressa['area'];
                    $value['address'] = $addressa['address'];
                }
                $value['address'] = array(
                    'realname' => $value['realname'],
                    'nickname' => $value['nickname'],
                    'mobile' => $value['mobile'],
                    'province' => $value['province'],
                    'city' => $value['city'],
                    'area' => $value['area'],
                    'address' => $value['address'],
                );

                if($value['status']==1 || ($value['status']==0 && $value['paytype']==3)){
                    $value['send_status'] = 1;
                }else{
                    $value['send_status']  = 0;
                }

                //订单商品
                $order_goods = pdo_fetchall('select g.id,g.title,g.shorttitle,g.thumb,g.goodssn,og.optionid,g.unit,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, g.weight, og.total,og.price,og.optionname as optiontitle, og.realprice,og.id as ordergoodid,og.printstate,og.printstate2 from ' . tablename('ewei_shop_order_goods') . ' og '
                    . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid '
                    . ' where og.uniacid=:uniacid and og.merchid=0 and og.orderid=:orderid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $value['id']));

                foreach($order_goods as $i=>$order_good){
                    if(!empty($order_good['optionid'])){
                        $option = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_goods_option') . " WHERE id=:id and uniacid=:uniacid limit 1", array(':id'=>$order_good['optionid'],':uniacid'=>$_W['uniacid']));
                        $order_goods[$i]['weight'] = $option['weight'];
                        $order_goods[$i]['goodssn'] = $option['goodssn'];
                        $order_goods[$i]['productsn'] = $option['productsn'];
                        $order_goods[$i]['unit'] = $option['unit'];
                    }
                }

                $goods = '';
                foreach ($order_goods as &$og) {
                    $goods.="" . $og['title'] . "\r\n";
                    if (!empty($og['optiontitle'])) {
                        $goods.=" 规格: " . $og['optiontitle'];
                    }
                    if (!empty($og['option_goodssn'])) {
                        $og['goodssn'] = $og['option_goodssn'];
                    }
                    if (!empty($og['option_productsn'])) {
                        $og['productsn'] = $og['option_productsn'];
                    }
                    if (!empty($og['goodssn'])) {
                        $goods.=' 商品编号: ' . $og['goodssn'];
                    }
                    if (!empty($og['productsn'])) {
                        $goods.=' 商品条码: ' . $og['productsn'];
                    }
                    $goods.=' 单价: ' . ($og['price'] / $og['total']) . ' 折扣后: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . " 折扣后: " . $og['realprice'] . "\r\n ";

                }
                unset($og);
                $value['goods'] = set_medias($order_goods, 'thumb');
                $value['goods_str'] = $goods;
            }
            unset($value);
            $total = pdo_fetchcolumn(
                'SELECT COUNT(*) FROM ' . tablename('ewei_shop_order') . " o "
                . " left join " . tablename('ewei_shop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,-1)<>-1"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid  "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " WHERE o.id in ( " . implode(',', $arr) . ") and o.merchid=0 and o.isparent=0 and o.uniacid={$_W['uniacid']}", $paras);
            $totalmoney = pdo_fetchcolumn(
                'SELECT sum(o.price) FROM ' . tablename('ewei_shop_order') . " o "
                . " left join " . tablename('ewei_shop_order_refund') . " r on r.orderid=o.id and ifnull(r.status,-1)<>-1"
                . " left join " . tablename('ewei_shop_member') . " m on m.openid=o.openid  "
                . " left join " . tablename('ewei_shop_member_address') . " a on o.addressid = a.id "
                . " WHERE o.id in ( " . implode(',', $arr) . ") and o.merchid=0 and o.isparent=0 and o.uniacid={$_W['uniacid']}", $paras);

            $address = false;
            if (!empty($list)) {
                $address = $list[0]['address'];
            }
            // 处理 发货信息
            $address['sendinfo'] = '';
            $sendinfo = array();

            foreach($list as $item){
                foreach($item['goods'] as $k=>$g){
                    if( isset($sendinfo[$g['id']])) {
                        $sendinfo[$g['id']]['num']+=$g['total'];
                    }
                    else{
                        $sendinfo[$g['id']]  =array('title'=>empty($g['shorttitle'])?$g['title']:$g['shorttitle'],'num'=>$g['total'],'optiontitle'=>!empty($g['optiontitle'])?'('.$g['optiontitle'].')':'');
                    }
                }
            }
            $sendinfos = array();
            foreach($sendinfo as $gid => $info){
                $info['gid'] = $gid;
                $sendinfos[] = $info;
                $address['sendinfo'].=$info['title'].$info['optiontitle'].' x '.$info['num'].'; ';
            }

            $temps = $this->model->getTemp();
            extract($temps);

            include $this->template('exhelper/print/single/print_tpl_detail');
        }
    }
    // 保存 打印时修改的address_send
    function saveuser(){
        global $_W, $_GPC;
        if($_W['ispost']){
            $ordersns =$_GPC['ordersns'];
            if(is_array($ordersns)){
                $data = array(
                    'realname' => trim($_GPC['realname']),
                    'nickname' => trim($_GPC['nickname']),
                    'mobile' => intval($_GPC['mobile']),
                    'province' => trim($_GPC['province']),
                    'city' => trim($_GPC['city']),
                    'area' => trim($_GPC['area']),
                    'address' => trim($_GPC['address']),
                );
                $address_send = iserializer($data);
                foreach($ordersns as $ordersn){
                    pdo_update('ewei_shop_order', array("address_send"=>$address_send), array('ordersn'=>$ordersn));
                }
                exit;
            }
        }

    }
    // 获取打印模板 发件人模板、快递单模板、发货单模板
    function getprintTemp(){
        global $_W, $_GPC;
        if($_W['ispost']){
            $type = intval($_GPC['type']);
            $printTempId = intval($_GPC['printTempId']);
            $printUserId = intval($_GPC['printUserId']);
            if(empty($type)){
                die(json_encode(array("result"=>'error','resp'=>"打印错误! 请刷新重试。EP01")));
            }
            if(empty($printTempId)){
                die(json_encode(array("result"=>'error','resp'=>"加载模版错误! 请重新选择打印模板。EP02")));
            }
            if(empty($printUserId)){
                die(json_encode(array("result"=>'error','resp'=>"加载模版错误! 请重新选择发件人信息模板。EP03")));
            }
            $tempSender = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_exhelper_senduser') . " WHERE id=:id and uniacid=:uniacid and merchid=0 limit 1", array(':id'=>$printUserId, ':uniacid' => $_W['uniacid']));
            $expTemp = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_exhelper_express') . " WHERE id=:id and uniacid=:uniacid and merchid=0 limit 1", array(':id'=>$printTempId, ':uniacid' => $_W['uniacid']));
            $shop_set = m('common')->getSysset('shop');
            $expDatas = htmlspecialchars_decode($expTemp['datas']);
            $expDatas = json_decode($expDatas,true);
            $expTemp['shopname'] = $shop_set['name'];
            $repItems = array("sendername","sendertel","senderaddress","sendersign","sendertime","sendercode","sendercccc");
            $repDatas = array($tempSender["sendername"],$tempSender["sendertel"],$tempSender["senderaddress"],$tempSender["sendersign"],date("Y-m-d H:i"),$tempSender["sendercode"],$tempSender["sendercity"]);
            if(is_array($expDatas)){
                foreach($expDatas as $index=>$data){
                    $expDatas[$index]['items'] = str_replace($repItems,$repDatas,$data['items']);
                }
            }
            die(json_encode(array("result"=>'success','respDatas'=>$expDatas,'respUser'=>$tempSender,'respTemp'=>$expTemp)));
        }
    }
    // 修改订单打印状态
    function changestate(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $arr = $_GPC['arr'];
            $type = intval($_GPC['type']);
            if(empty($arr) || empty($type)){
                die(json_encode(array("result"=>'error','resp'=>'数据错误。EP04')));
            }
            foreach($arr as $i=>$data){
                $orderid = $data['orderid'];
                $ordergoodid = $data['ordergoodid'];
                // 查询出已打印次数
                $ordergood = pdo_fetch("SELECT id,goodsid,printstate,printstate2 FROM " . tablename('ewei_shop_order_goods') . " WHERE goodsid=:goodsid and orderid=:orderid and uniacid=:uniacid and merchid=0 limit 1", array(':orderid'=>$orderid, ':goodsid' => $ordergoodid,':uniacid' => $_W['uniacid']));
                if($type==1){
                    pdo_update('ewei_shop_order_goods', array("printstate"=>$ordergood['printstate']+1), array('id' => $ordergood['id']));
                    $orderprint = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_order_goods') . " WHERE orderid=:orderid and printstate=0 and uniacid= :uniacid and merchid=0", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
                }
                elseif($type==2){
                    pdo_update('ewei_shop_order_goods', array("printstate2"=>$ordergood['printstate2']+1), array('id' => $ordergood['id']));
                    $orderprint = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_order_goods') . " WHERE orderid=:orderid and printstate2=0 and uniacid= :uniacid and merchid=0", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
                }
                // 判断 如果当前订单中的商品全部打印 则标记订单打印状态
                if($orderprint==0){
                    $printstatenum = 2;
                }else{
                    $printstatenum = 1;
                }
                if($type==1){
                    pdo_update('ewei_shop_order', array("printstate"=>$printstatenum), array('id' => $orderid));
                }
                elseif($type==2){
                    pdo_update('ewei_shop_order', array("printstate2"=>$printstatenum), array('id' => $orderid));
                }
            }
            die(json_encode(array("result"=>'success','orderprintstate'=>$printstatenum)));
        }
    }

    function getorderinfo(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $orderids = $_GPC['orderids'];
            $temp_express = intval($_GPC['temp_express']);

            $in = implode(',',$orderids);
            if(empty($in)){
                exit; // orders 为空
            }
            $printTemp = pdo_fetch("SELECT id,type,expressname,express,expresscom FROM " . tablename('ewei_shop_exhelper_express') . " WHERE id=:id and type=:type and uniacid=:uniacid and merchid=0 limit 1", array(':id'=>$temp_express,':type'=>1,':uniacid' => $_W['uniacid']));
            if(empty($printTemp) || !is_array($printTemp)){
                exit; // 未选择快递单单模板
            }
            if(empty($printTemp['expresscom'])){
                $printTemp['expresscom'] = "其他快递";
            }
            $orders = pdo_fetchall("SELECT id,ordersn,address,address_send,status,paytype,expresscom,expresssn,dispatchtype FROM " . tablename('ewei_shop_order') . " WHERE id in( $in ) and (status=1 or (paytype=3 and status=0)) and uniacid=:uniacid and merchid=0 and isparent=0 order by ordersn desc ", array(':uniacid' => $_W['uniacid']));
            if(empty($orders)){
                exit;  // 订单信息为空
            }
            $paytype = array('0' => array('css' => 'default', 'name' => '未支付'),'1' => array('css' => 'danger', 'name' => '余额支付'),'11' => array('css' => 'default', 'name' => '后台付款'),'2' => array('css' => 'danger', 'name' => '在线支付'),
                '21' => array('css' => 'success', 'name' => '微信支付'),'22' => array('css' => 'warning', 'name' => '支付宝支付'),'23' => array('css' => 'warning', 'name' => '银联支付'),'3' => array('css' => 'primary', 'name' => '货到付款'),
            );
            $orderstatus = array(
                '-1' => array('css' => 'default', 'name' => '已关闭'),'0' => array('css' => 'danger', 'name' => '待付款'),'1' => array('css' => 'info', 'name' => '待发货'),
                '2' => array('css' => 'warning', 'name' => '待收货'),'3' => array('css' => 'success', 'name' => '已完成')
            );

            foreach($orders as $i=>$order){
                if(!empty($order['address_send'])){
                    $orders[$i]['address_address'] = iunserializer($order['address_send']);
                }else{
                    $orders[$i]['address_address'] = iunserializer($order['address']);
                }

                if($order['status']==1 || ($order['status']==0 && $order['paytype']==3)){
                    $orders[$i]['send_status'] = 1;
                }else{
                    $orders[$i]['send_status']  = 0;
                }

                $p = $order['paytype'];
                $orders[$i]['paycss'] = $paytype[$p]['css'];
                $orders[$i]['paytypename'] = $paytype[$p]['name'];

                $s = $order['status'];
                $orders[$i]['statuscss'] = $orderstatus[$s]['css'];
                $orders[$i]['statusname'] = $orderstatus[$s]['name'];
                if ($s == -1) {
                    if ($order['refundstatus'] == 1) {
                        $orders[$i]['statusname'] = '已退款';
                    }
                }

                if(empty($order['expresscom'])){
                    $orders[$i]['expresscom'] = "其他快递";
                }
            }

            include $this->template('exhelper/print/print_tpl_dosend');

        }
    }

    function dosend(){
        global $_W, $_GPC;

        if($_W['ispost']){
            $orderid = intval($_GPC['orderid']);
            $express = trim($_GPC['express']);	// 快递编码
            $expresssn = intval($_GPC['expresssn']);	// 快递号
            $expresscom = trim($_GPC['expresscom']);	// 快递公司

            $orderinfo = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_order') . " WHERE id=:orderid and status>-1 and uniacid=:uniacid and merchid=0 limit 1", array(':orderid'=>$orderid,':uniacid' => $_W['uniacid']));
            if(empty($orderinfo)){
                die(json_encode(array('result'=>'error','resp'=>'订单不存在')));
            }
            if($orderinfo['status']==1 || ($orderinfo['status']==0 && $orderinfo['paytype']==3)){
                // 判断 订单状态未待发货
                pdo_update('ewei_shop_order', array(
                    "express"=>trim($express),
                    'expresssn'=>trim($expresssn),
                    'expresscom'=>trim($expresscom),
                    'sendtime' => time(),
                    'status'=>2
                ), array('id'=>$orderid));

                //取消退款状态
                if (!empty($orderinfo['refundid'])) {
                    $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $orderinfo['refundid']));
                    if (!empty($refund)) {
                        pdo_update('ewei_shop_order_refund', array('status' => -1), array('id' => $orderinfo['refundid']));
                        pdo_update('ewei_shop_order', array('refundid' => 0), array('id' => $orderinfo['id']));
                    }
                }
                //模板消息
                m('notice')->sendOrderMessage($orderinfo['id']);

                plog('exhelper.print.single.dosend', "一键发货 订单号: {$orderinfo['ordersn']} <br/>快递公司: {$_GPC['expresscom']} 快递单号: {$_GPC['expresssn']}");

                die(json_encode(array('result'=>'success')));
            }
        }
    }



}
