<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Cart_EweiShopV2Page extends MobileLoginPage {

    function main() {
        global $_W,$_GPC;
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            include $this->template('merch/member/cart');
            exit;
        }
        include $this->template();
    }
    function get_list(){

        global $_W,$_GPC;
        $newlist=array();
        $uniacid = $_W['uniacid'];
        $openid =$_W['openid'];
        $condition = ' and f.uniacid= :uniacid and f.openid=:openid and f.deleted=0';
        $params = array(':uniacid' => $uniacid, ':openid' => $openid);
        $list = array();
        $total = 0;
        $totalprice = 0;
        $ischeckall = true;

        //会员级别
        $level = m('member')->getLevel($openid);

        $sql = 'SELECT f.id,f.total,f.goodsid,g.total as stock,f.depotid, o.stock as optionstock, g.maxbuy,g.title,g.thumb,ifnull(o.marketprice, g.marketprice) as marketprice,'
            . ' g.productprice,o.title as optiontitle,f.optionid,o.specs,g.isdiscount_stat_time,g.minbuy,g.maxbuy,g.unit,f.merchid,g.checked,g.isdiscount_discounts,g.isdiscount,g.isdiscount_time,g.isnodiscount,g.discounts,g.merchsale'
            . ' ,f.selected FROM ' . tablename('ewei_shop_member_cart') . ' f '
            . ' left join ' . tablename('ewei_shop_goods') . ' g on f.goodsid = g.id '
            . ' left join ' . tablename('ewei_shop_goods_option') . ' o on f.optionid = o.id '
            . ' where 1 ' . $condition . ' ORDER BY `id` DESC ';
        $list = pdo_fetchall($sql, $params);

        foreach ($list as &$g) {

            $g['thumb'] = tomedia($g['thumb']);
            $seckillinfo = plugin_run('seckill::getSeckill',$g['goodsid'] ,$g['optionid'] ,true, $_W['openid']);
            if(!empty($seckillinfo)){
                $check_buy = plugin_run('seckill::checkBuy', $seckillinfo, $g['title']);
                if(is_error($check_buy)){
                    $seckillinfo="";
                }
            }
            if (!empty($g['optionid'])) {
                $g['stock'] = $g['optionstock'];

                //读取规格的图片
                if (!empty($g['specs'])) {
                    $thumb = m('goods')->getSpecThumb($g['specs']);
                    if (!empty($thumb)) {
                        $g['thumb'] =tomedia( $thumb );
                    }
                }
            }
            if($g['selected']){
                
                //促销或会员折扣
                $prices = m('order')->getGoodsDiscountPrice($g, $level, 1);

                $total+=$g['total'];
                $g['marketprice'] = $g['ggprice'] = $prices['price'];

                if( $seckillinfo && $seckillinfo['status']==0){

                    $seckilllast = 0;
                    if( $seckillinfo['maxbuy']>0) {
                        $seckilllast = $seckillinfo['maxbuy'] - $seckillinfo['selfcount'];
                    }

                    $normal = $g['total'] - $seckilllast;
                    if($normal<=0){
                        $normal =  0;
                    }

                    $totalprice+= $seckillinfo['price'] * $seckilllast  +  $g['marketprice'] * $normal;
                    $g['seckillmaxbuy'] = $seckillinfo['maxbuy'];
                    $g['seckillselfcount'] = $seckillinfo['selfcount'];
                    $g['seckillprice'] = $seckillinfo['price'];
                    $g['seckilltag'] = $seckillinfo['tag'];
                    $g['seckilllast'] = $seckilllast;
                    $newlist[$g['depotid']]['totalprice']+=$seckillinfo['price'] * $seckilllast  +  $g['marketprice'] * $normal;


                } else{
                    $totalprice+=$g['marketprice'] * $g['total'];
                    $newlist[$g['depotid']]['totalprice']+=$g['marketprice'] * $g['total'];
                }

            }


            //库存
            $totalmaxbuy = $g['stock'];


            if( $seckillinfo && $seckillinfo['status']==0){

                if( $totalmaxbuy > $g['seckilllast']){

                      $totalmaxbuy = $g['seckilllast'];
                }
                if($g['total']>$totalmaxbuy){
                    $g['total'] = $totalmaxbuy;
                }

                $g['minbuy'] = 0;
            } else {

                //最大购买量
                if ($g['maxbuy'] > 0) {

                    if ($totalmaxbuy != -1) {
                        if ($totalmaxbuy > $g['maxbuy']) {
                            $totalmaxbuy = $g['maxbuy'];
                        }
                    } else {
                        $totalmaxbuy = $g['maxbuy'];
                    }
                }

                //总购买量
                if ($g['usermaxbuy'] > 0) {
                    $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
                        . ' where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $g['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));
                    $last = $g['usermaxbuy'] - $order_goodscount;
                    if ($last <= 0) {
                        $last = 0;
                    }
                    if ($totalmaxbuy != -1) {
                        if ($totalmaxbuy > $last) {
                            $totalmaxbuy = $last;
                        }
                    } else {
                        $totalmaxbuy = $last;
                    }
                }

                //最小购买
                if ($g['minbuy'] > 0) {

                    if ($g['minbuy'] > $totalmaxbuy) {
                        $g['minbuy'] = $totalmaxbuy;
                    }

                }
            }

            $g['totalmaxbuy'] = $totalmaxbuy;
            $newlist[$g['depotid']]['totalmaxbuy']+=$totalmaxbuy;
            $newlist[$g['depotid']]['depotid']=$g['depotid'];
            $newlist[$g['depotid']]['total']=$g['total'];
            $g['unit'] = empty($data['unit']) ? '件' : $data['unit'];
            $newlist[$g['depotid']]['goodslist'][]=$g;
            if(empty($g['selected'])){
                $ischeckall =false;
            }


        }
        unset($g);
        $list = set_medias($list, 'thumb');
        $merch_user = array();
        $merch = array();
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {

            $getListUser = $merch_plugin->getListUser($list);
            $merch_user = $getListUser['merch_user'];
            $merch = $getListUser['merch'];

        }
        if(empty($newlist)){

            $newlist = array();
        }
        $r=array();

        foreach($newlist as &$goods){
            if($goods['depotid']>0){
                $depot=Dispage::getDepot($goods['depotid']);
                $goods['depottitle']= $depot['title'];
            }
            $r[]=$goods;
        }
        unset($goods);
        unset($newlist);
       // var_dump($newlist);
        show_json(1, array(
            'ischeckall'=>$ischeckall,
            //'list'=>$list,
            'list'=>$r,
            'total'=>$total,
            'totalprice'=>round($totalprice,2),
            'merch_user'=>$merch_user ,
            'merch'=>$merch));

    }

    function select(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $select = intval($_GPC['select']);
        $depotid=intval($_GPC['depotid']);
        if(!empty($id)){
            $data = pdo_fetch("select id,goodsid,optionid, total from " . tablename('ewei_shop_member_cart') . " "
                . " where id=:id and uniacid=:uniacid and openid=:openid limit 1 ", array(':id' => $id, ':uniacid' => $_W['uniacid'],':openid'=>$_W['openid']));
            if (!empty($data)) {
                pdo_update('ewei_shop_member_cart', array('selected' => $select), array('id' => $id, 'uniacid' => $_W['uniacid']));
            }
        } else {
            pdo_update('ewei_shop_member_cart', array('selected' => $select), array('uniacid' => $_W['uniacid'],'openid'=>$_W['openid'],'depotid'=>$depotid));
        }

        show_json(1);
    }
    function update(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $goodstotal = intval($_GPC['total']);
        $optionid = intval($_GPC['optionid']);
        empty($goodstotal) && $goodstotal = 1;
        $data = pdo_fetch("select id,goodsid,optionid, total from " . tablename('ewei_shop_member_cart') . " "
            . " where id=:id and uniacid=:uniacid and openid=:openid limit 1 ", array(':id' => $id, ':uniacid' => $_W['uniacid'],':openid'=>$_W['openid']));
        if (empty($data)) {
            show_json(0,'无购物车记录');
        }


        $goods =pdo_fetch('select id,maxbuy,minbuy,total,unit from '.tablename('ewei_shop_goods').' where id=:id and uniacid=:uniacid and status=1 and deleted=0',array(':id'=>$data['goodsid'],':uniacid'=>$_W['uniacid']));
        if(empty($goods)){
            show_json(0,'商品未找到');
        }
        pdo_update('ewei_shop_member_cart', array('total' => $goodstotal,'optionid'=>$optionid), array('id' => $id, 'uniacid' => $_W['uniacid'],'openid'=>$_W['openid']));

        $seckillinfo  =  plugin_run('seckill::getSeckill',$data['goodsid'] ,$data['optionid'] ,true, $_W['openid']);
        if(!empty($seckillinfo)){
            $check_buy = plugin_run('seckill::checkBuy', $seckillinfo, $data['title']);
            if(is_error($check_buy)){
                $seckillinfo="";
            }
        }
        if( $seckillinfo && $seckillinfo['status']==0) {
            $g =array();
            $g['seckillmaxbuy'] = $seckillinfo['maxbuy'];
            $g['seckillselfcount'] = $seckillinfo['selfcount'];
            $g['seckillprice'] = $seckillinfo['price'];
            show_json(1,array('seckillinfo'=>$g));
        }
        show_json(1);


    }

    function add() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $total = intval($_GPC['total']);

        $total <= 0 && $total = 1;
        $optionid = intval($_GPC['optionid']);
        $goods = pdo_fetch('select id,marketprice,diyformid,diyformtype,depotid,diyfields, isverify, `type`,merchid, cannotrefund,maxbuy from '.tablename('ewei_shop_goods').' where id=:id and uniacid=:uniacid limit 1',array(':id'=>$id,':uniacid'=>$_W['uniacid']));
        if (empty($goods)) {
            show_json(0, '商品未找到');
        }
        $seckillinfo = false;
        $seckill  = p('seckill');
        if( $seckill){
            $time = time();
            $seckillinfo = $seckill->getSeckill($id);
            if(!empty($seckillinfo)){
                $check_buy = plugin_run('seckill::checkBuy', $seckillinfo, $data['title']);
                if(is_error($check_buy)){
                    $seckillinfo="";
                }else{
                    if($time >= $seckillinfo['starttime'] && $time<$seckillinfo['endtime']){
                    $seckillinfo['status'] = 0;
                    }elseif( $time < $seckillinfo['starttime'] ){
                        $seckillinfo['status'] = 1;
                    }else {
                        $seckillinfo['status'] = -1;
                    }
                }
            }
        }
        $member = m('member')->getMember($_W['openid']);
        if(!empty($_W['shopset']['wap']['open']) && !empty($_W['shopset']['wap']['mustbind']) && empty($member['mobileverify'])){
            show_json(0, array('message'=>"请先绑定手机", 'url'=>mobileUrl('member/bind', null, true)));
        }

        //是否可以加入购物车
        if ($goods['isverify'] == 2 || $goods['type'] == 2 || $goods['type'] == 3 || !empty($goods['cannotrefund'])) {
            show_json(0, '此商品不可加入购物车<br>请直接点击立刻购买');
        }

        //赠品活动
        $giftid = intval($_GPC['giftid']);
        $gift = pdo_fetch("select * from ".tablename('ewei_shop_gift')." where uniacid = ".$_W['uniacid']." and id = ".$giftid." and starttime >= ".time()." and endtime <= ".time()." and status = 1 ");


        //自定义表单
        $diyform_plugin = p('diyform');
        $diyformid = 0;
        $diyformfields = iserializer(array());
        $diyformdata = iserializer(array());





        if ($diyform_plugin) {
            $diyformdata = $_GPC['diyformdata'];
            if (!empty($diyformdata) && is_array($diyformdata)) {
                $diyformfields = false;
                if( $goods['diyformtype']==1){
                    //模板
                    $diyformid = intval($goods['diyformid']);
                    $formInfo = $diyform_plugin->getDiyformInfo($diyformid);
                    if(!empty($formInfo)){
                        $diyformfields = $formInfo['fields'];
                    }

                } else if($goods['diyformtype']==2){
                    //自定义
                    $diyformfields = iunserializer($goods['diyfields']);
                }

                if(!empty($diyformfields)){
                    $insert_data = $diyform_plugin->getInsertData($diyformfields, $diyformdata);
                    $diyformdata = $insert_data['data'];
                    $diyformfields = iserializer($diyformfields);
                }
            }
        }

        $data = pdo_fetch("select id,total,diyformid from " . tablename('ewei_shop_member_cart') . ' where goodsid=:id and openid=:openid and   optionid=:optionid  and deleted=0 and  uniacid=:uniacid   limit 1', array(
            ':uniacid' => $_W['uniacid'],
            ':openid' => $_W['openid'],
            ':optionid' => $optionid,
            ':id' => $id
        ));

        if (empty($data)) {
            $data = array(
                'uniacid' => $_W['uniacid'],
                'merchid' => $goods['merchid'],
                'openid' => $_W['openid'],
                'goodsid' => $id,
                'optionid' => $optionid,
                'marketprice' => $goods['marketprice'],
                'total' => $total,
                'selected'=>1,
                'diyformid'=>$diyformid,
                'diyformdata'=> $diyformdata,
                'diyformfields'=> $diyformfields,
                'createtime' => time(),
                'depotid'=>$goods['depotid'],//wsq
            );
            pdo_insert('ewei_shop_member_cart', $data);

        } else {

            if(!empty($seckillinfo)){
                if(count($seckillinfo['options'])==1){
                    $seckillmaxbuy=$seckillinfo['options'][0]['maxbuy'];//商品限购

                    if($data['total']>=$seckillmaxbuy){
                        show_json(0, '秒杀商品限购'.$seckillmaxbuy.'件');
                        exit;
                    }
                    
                }
            }else{
                if($data['total']>=$goods['maxbuy'] && $goods['maxbuy']!=0){
                     show_json(0, '商品限购'.$goods['maxbuy'].'件');
                }
            }
            $data['diyformid'] = $diyformid;
            $data['diyformdata'] = $diyformdata;
            $data['diyformfields'] = $diyformfields;
            $data['total']+=$total;
            pdo_update('ewei_shop_member_cart', $data, array('id' => $data['id']));
        }

        //购物车数量
        $cartcount = pdo_fetchcolumn('select sum(total) from ' . tablename('ewei_shop_member_cart') . ' where openid=:openid and deleted=0 and uniacid=:uniacid limit 1', array(
            ':uniacid' => $_W['uniacid'],
            ':openid' => $_W['openid']
        ));


        show_json(1, array('isnew' => false, 'cartcount' => $cartcount));
    }

    function remove(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'];
        if (empty($ids) || !is_array($ids)) {
            show_json(0, '参数错误');
        }
        $sql = "update " . tablename('ewei_shop_member_cart') . ' set deleted=1 where uniacid=:uniacid and openid=:openid and id in (' . implode(',', $ids) . ')';
        pdo_query($sql, array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
        show_json(1);

    }

    function tofavorite(){
        global $_W,$_GPC;
        $uniacid =$_W['uniacid'];
        $openid =$_W['openid'];
        $ids = $_GPC['ids'];
        if (empty($ids) || !is_array($ids)) {
            show_json(0, '参数错误');
        }
        foreach ($ids as $id) {
            $goodsid = pdo_fetchcolumn('select goodsid from ' . tablename('ewei_shop_member_cart') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1 ', array(':id' => $id, ':uniacid' => $uniacid, ':openid' => $openid));
            if (!empty($goodsid)) {
                $fav = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_favorite') . ' where goodsid=:goodsid and uniacid=:uniacid and openid=:openid and deleted=0 limit 1 ', array(':goodsid' => $goodsid, ':uniacid' => $uniacid, ':openid' => $openid));
                if ($fav <= 0) {
                    $fav = array(
                        'uniacid' => $uniacid,
                        'goodsid' => $goodsid,
                        'openid' => $openid,
                        'deleted' => 0,
                        'createtime' => time()
                    );
                    pdo_insert('ewei_shop_member_favorite', $fav);
                }
            }
        }

        $sql = "update " . tablename('ewei_shop_member_cart') . ' set deleted=1 where uniacid=:uniacid and openid=:openid and id in (' . implode(',', $ids) . ')';
        pdo_query($sql, array(':uniacid' => $uniacid, ':openid' => $openid));
        show_json(1);

    }

    function submit(){


        global $_W,$_GPC;
        $uniacid = $_W['uniacid'];
        $openid =$_W['openid'];
        $depotid=$_GPC['depotid'];
        $member = m('member')->getMember($openid);
        $condition = ' and f.uniacid= :uniacid and f.openid=:openid and f.selected=1 and f.deleted=0 and g.depotid=:depotid';
        $params = array(':uniacid' => $uniacid, ':openid' => $openid,':depotid'=>$depotid);

        $sql = 'SELECT f.id,f.total,f.goodsid,g.total as stock, o.stock as optionstock, g.maxbuy,g.title,g.thumb,ifnull(o.marketprice, g.marketprice) as marketprice,'
            . ' g.productprice,o.title as optiontitle,f.optionid,o.specs,g.minbuy,g.maxbuy,g.unit,f.merchid,g.checked,g.isdiscount_discounts,g.isdiscount,g.isdiscount_time,g.isnodiscount,g.discounts,g.merchsale'
            . ' ,f.selected,g.status,g.deleted as goodsdeleted FROM ' . tablename('ewei_shop_member_cart') . ' f '
            . ' left join ' . tablename('ewei_shop_goods') . ' g on f.goodsid = g.id '
            . ' left join ' . tablename('ewei_shop_goods_option') . ' o on f.optionid = o.id '
            . ' where 1 ' . $condition . ' ORDER BY `id` DESC ';
        $list = pdo_fetchall($sql, $params);
        if(empty($list)){
            show_json(0,'没有选择任何商品');
        }

        foreach ($list as &$g) {

            if(empty($g['unit'])){
                $g['unit'] = "件";
            }
            if($g['status']!=1 || $g['goodsdeleted']==1){
                show_json(0,$g['title'].'<br/> 已经下架');
            }

            $seckillinfo = plugin_run('seckill::getSeckill',$g['goodsid'] ,$g['optionid'] ,true, $_W['openid']);
            if(!empty($seckillinfo)){
                $check_buy = plugin_run('seckill::checkBuy', $seckillinfo, $g['title']);
                if(is_error($check_buy)){
                    $seckillinfo="";
                }
            }
            if (!empty($g['optionid'])) {
                $g['stock'] = $g['optionstock'];
            }

            if( $seckillinfo && $seckillinfo['status']==0){

                $check_buy = plugin_run('seckill::checkBuy',  $seckillinfo , $g['title'] ,$g['unit']);
                if(is_error($check_buy)){
                    show_json(-1 ,  $check_buy['message']);
                }

            } else{


                $levelid = intval($member['level']);
                $groupid = intval($member['groupid']);

                //判断会员权限
                if ($g['buylevels'] != '') {
                    $buylevels = explode(',', $g['buylevels']);
                    if (!in_array($levelid, $buylevels)) {
                        show_json(0, '您的会员等级无法购买<br/>' . $g['title'] . '!');
                    }
                }
                //会员组权限
                if ($g['buygroups'] != '') {
                    $buygroups = explode(',', $g['buygroups']);
                    if (!in_array($groupid, $buygroups)) {
                        show_json(0, '您所在会员组无法购买<br/>' . $g['title'] . '!');
                    }
                }

                if ($g['minbuy'] > 0) {
                    if ($g['total'] < $g['minbuy']) {
                        show_json(0, $g['title'] . '<br/> ' . $g['minbuy'] . $g['unit'] . "起售!");
                    }
                }
                if ($g['maxbuy'] > 0) {
                    if ($g['total'] > $g['maxbuy']) {
                        show_json(0, $g['title'] . '<br/> 一次限购 ' . $g['maxbuy'] . $g['unit'] . "!");
                    }
                }

                if ($g['usermaxbuy'] > 0) {
                    $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
                        . ' where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $g['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));
                    if ($order_goodscount >= $g['usermaxbuy']) {
                        show_json(0, $g['title'] . '<br/> 最多限购 ' . $g['usermaxbuy'] . $g['unit'] . "!");
                    }
                }
                if (!empty($optionid)) {
                    $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,stock,`virtual`,weight from ' . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $goodsid, ':id' => $optionid));
                    if (!empty($option)) {
                            if ($option['stock'] != -1) {
                                if (empty($option['stock'])) {
                                    show_json(-1, $g['title'] . "<br/>" . $option['title'] . " 库存不足!");
                                }
                        }
                    }
                }
                else{

                    if ($g['stock'] != -1) {
                        if (empty($g['stock'])) {
                            show_json(0, $g['title'] . "<br/>库存不足!");
                        }
                    }

                }
            }
        }
        show_json(1);
    }

}
