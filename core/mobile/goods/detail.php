<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Detail_EweiShopV2Page extends MobilePage {
    
    function main() {
       
        global $_W, $_GPC;
        $openid =$_W['openid'];
        $uniacid = $_W['uniacid'];
        $id = intval($_GPC['id']);
        $rank = intval($_GPC['rank']);
        $join_id = intval($_GPC['join_id']);

        if (!empty($join_id)) {
            $_SESSION[$id . '_rank'] = $rank;
            $_SESSION[$id . '_join_id'] = $join_id;
        }

        $err = false;

        //多商户
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        $commission_data = m('common')->getPluginset('commission');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }

        //赠品活动
        $isgift = 0;
        $gifts = array();
        $giftgoods = array();
        $gifts = pdo_fetchall("select id,goodsid,giftgoodsid,thumb,title from ".tablename('ewei_shop_gift')." where uniacid = ".$uniacid." and activity = 2 and status = 1 and starttime <= ".time()." and endtime >= ".time()."  ");
        foreach($gifts as $key => $value){
            if(strstr($value['goodsid'],trim($id))){
                $giftgoods = explode(",",$value['giftgoodsid']);
                foreach($giftgoods as $k => $val){
                    $isgift = 1;
                    $gifts[$key]['gift'][$k] = pdo_fetch("select id,title,thumb,marketprice from ".tablename('ewei_shop_goods')." where uniacid = ".$uniacid." and deleted = 0 and total > 0 and status = 2 and id = ".$val." ");
                    $gifttitle = !empty($gifts[$key]['gift'][$k]['title']) ? $gifts[$key]['gift'][$k]['title'] : '赠品';
                }
            }else{
                unset($gifts[$key]);
            }
        }

        //商品
        $goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
        if($_W['uniacid']==DIS_ACCOUNT && !empty($goods['goodssn'])){

            $ret=m("cnbuyerdb")->get_stock($goods['goodssn']);
           if(!empty($ret)){
                if($ret['if_show']==0){
                    $goods['total']=0;
                }else{
                  $goods['total']=$ret['stock'];
                }
           }
        }
        if($goods['disgoods_id']>0 && !empty($goods['goodssn'])){
            
            $ret=m("cnbuyerdb")->get_stock($goods['goodssn']);
            if(!empty($ret)){
                if($ret['if_show']==0){
                    $goods['total']=0;
                }else{
                    $goods['total']=$ret['stock'];
                }
            }
        }

        $merchid = $goods['merchid'];
        $labelname = json_decode($goods['labelname'],true);

        $style = pdo_fetch("SELECT id,uniacid,style FROM " . tablename('ewei_shop_goods_labelstyle') . " WHERE uniacid=" . $uniacid);

        if ($is_openmerch == 0) {
            //未开启多商户的情况下,此商品是否有多商户的商品
            if ($merchid > 0) {
                $err = true;
                include $this->template('goods/detail');

                exit;
            }
        } else {
            //判断多商户商品是否通过审核
            if ($merchid > 0 && $goods['checked'] == 1) {
                $err = true;
                include $this->template('goods/detail');
                exit;
            }
        }


        $member = m('member')->getMember($openid);
        if(com('coupon'))
        {
            $coupons =$this->getCouponsbygood($goods['id']);
        }
        $showgoods = m('goods')->visit($goods, $member);

        if (empty($goods) || empty($showgoods)){
            $err = true;
            include $this->template();
            exit;
        }
        $seckillinfo = false;
        $seckill  = p('seckill');
        if( $seckill){
            $time = time();
           
            $seckillinfo = $seckill->getSeckill($goods['id'],0,false);

            if(!empty($seckillinfo)){
                $check_buy  = plugin_run('seckill::checkBuy', $seckillinfo, $goods['title']);
                if(!is_error($check_buy)){
                    if($time >= $seckillinfo['starttime'] && $time<$seckillinfo['endtime']){
                        $seckillinfo['status'] = 0;
                        unset($_SESSION[$id . '_log_id']);
                        unset($_SESSION[$id . '_task_id']);
                        unset($log_id);
                    }elseif( $time < $seckillinfo['starttime'] ){
                        $seckillinfo['status'] = 1;
                    }else {
                        $seckillinfo['status'] = -1;
                    }
                }else{
                    $seckillinfo="";
                }
            }
        }

        //任务活动购买商品
        $task_goods_data = m('goods')->getTaskGoods($openid, $id, $rank, $join_id);
        if (empty($task_goods_data['is_task_goods'])) {
            $is_task_goods = 0;

            //如果是砍价商品,自动跳转
            if ($goods['bargain']>0){echo "<script>window.location.href = '".mobileUrl('bargain/detail',array('id'=>$goods['bargain']))."'</script>";return;}
        } else {
            $is_task_goods = $task_goods_data['is_task_goods'];
            $is_task_goods_option = $task_goods_data['is_task_goods_option'];
            $task_goods = $task_goods_data['task_goods'];
        }

        $goods['sales'] = $goods['sales'] + $goods['salesreal'];

        $goods['content'] = m('ui')->lazy($goods['content']);

        $buyshow = 0;
        if ($goods['buyshow'] == 1) {
            $sql = "select o.id from " . tablename('ewei_shop_order') . " o left join " . tablename('ewei_shop_order_goods') . " g on o.id = g.orderid";
            $sql .= " where o.openid=:openid and g.goodsid=:id and o.status>0 and o.uniacid=:uniacid limit 1";
            $buy_goods = pdo_fetch($sql, array(':openid' => $openid, ':id' => $id, ':uniacid' => $_W['uniacid']));

            if (!empty($buy_goods)) {
                $buyshow = 1;
                $goods['buycontent'] = m('ui')->lazy($goods['buycontent']);
            }
        }

        $goods['unit'] = empty($goods['unit'])?'件':$goods['unit'];


        //使用的快递是否有不配送区域

        $citys = m('dispatch')->getNoDispatchAreas($goods);
        if (!empty($citys) && is_array($citys)) {
            $has_city = 1;
        } else {
            $has_city = 0;
        }

        //套餐
        $package_goods = pdo_fetch("select pg.id,pg.pid,pg.goodsid,p.displayorder from ".tablename('ewei_shop_package_goods')." as pg
                        left join ".tablename('ewei_shop_package')." as p on pg.pid = p.id
                        where pg.uniacid = ".$uniacid." and pg.goodsid = ".$id." ORDER BY p.displayorder desc,pg.id desc limit 1 ");
        if($package_goods['pid']){
            $packages = pdo_fetchall("SELECT id,title,thumb,packageprice FROM ".tablename('ewei_shop_package_goods')."
                    WHERE uniacid = ".$uniacid." and pid = ".$package_goods['pid']."  ORDER BY id DESC");
            $packages = set_medias($packages,array('thumb'));
        }
        if($goods['type']==4){//组合商品
            $packg=Dispage::getPackgoods($goods['id'],$_W['uniacid']);
            foreach ($packg as  $vg) {
                $weight+=$vg['weight']*$vg['number'];
                //检查商品库存是否足够
                if($vg['total']<$vg['number']){//组合中可购买数量
                    $goods['total']=0;

                }
                $decountprice+=$vg['minprice']*$vg['number'];
            }
            $goods['weight']=$weight;
        }
        //运费
        $goods['dispatchprice'] = $this->getGoodsDispatchPrice($goods);
        //var_dump($goods['dispatchprice']);
        //幻灯片
        $thumbs = iunserializer($goods['thumb_url']);
        if(empty($thumbs)){
            $thumbs = array( $goods['thumb'] );
        }
        if (!empty($goods['thumb_first'])&&!empty($goods['thumb'])) {
            $thumbs =array_merge( array($goods['thumb']), $thumbs );
        }

        //规格specs
        $spcgoodsid=$goods['id'];
        // if($goods['disgoods_id']>0){
        //     $spcgoodsid=$goods['disgoods_id'];
        //     $uniacid=DIS_ACCOUNT;
        // }
        $specs = pdo_fetchall('select * from ' . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid and  uniacid=:uniacid order by displayorder asc", array(':goodsid' => $spcgoodsid, ':uniacid' => $uniacid));
        $spec_titles = array();
        foreach ($specs as $key => $spec) {
            if ($key >= 2) {
                break;
            }
            $spec_titles[] = $spec['title'];
        }
        $spec_titles = implode('、', $spec_titles);

        //参数
        $params = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_goods_param') . " WHERE uniacid=:uniacid and goodsid=:goodsid order by displayorder asc", array(':uniacid' => $uniacid, ":goodsid" => $spcgoodsid));

        $goods = set_medias($goods, 'thumb');
        //$goods['canbuy'] = !empty($goods['status']) && empty($goods['deleted']);
        $goods['canbuy'] = $goods['status'] == 1 && empty($goods['deleted']);

        if (!empty($goods['hasoption'])){
            $options = pdo_fetchall('select id,stock from ' . tablename('ewei_shop_goods_option') . ' where goodsid=:goodsid and uniacid=:uniacid order by displayorder asc', array(':goodsid' => $spcgoodsid, ':uniacid' => $uniacid),'stock');
            $options_stock = array_keys($options);
            if($options_stock){
                $goods['total'] = max($options_stock);
            }else{
                $goods['total'] = 0;
            }
        }
        if ($goods['total'] <= 0) {
            $goods['canbuy'] = false;
        }

        $goods['timestate'] = '';

        //判断用户最大购买量
        $goods['userbuy'] = '1';
        if ($goods['usermaxbuy'] > 0) {
            $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('ewei_shop_order_goods') . ' og '
                . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
                . ' where og.goodsid=:goodsid and  o.status>=1 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $goods['id'], ':uniacid' => $uniacid, ':openid' => $openid));
            if ($order_goodscount >= $goods['usermaxbuy']) {
                $goods['userbuy'] = 0;
                $goods['canbuy']  = false;
            }
        }
        $levelid = $member['level'];
        $groupid = $member['groupid'];

        //判断会员权限
        $goods['levelbuy'] = '1';
        if ($goods['buylevels'] != '') {
            $buylevels = explode(',', $goods['buylevels']);
            if (!in_array($levelid, $buylevels)) {
                $goods['levelbuy'] = 0;
                $goods['canbuy']  = false;

            }
        }

        //会员组权限
        $goods['groupbuy'] = '1';
        if ($goods['buygroups'] != '') {
            $buygroups = explode(',', $goods['buygroups']);
            if (!in_array($groupid, $buygroups)) {
                $goods['groupbuy'] = 0;
                $goods['canbuy']  = false;
            }
        }

        //判断限时购
        $goods['timebuy'] = '0';
        if(empty($seckillinfo)) {
            if ($goods['istime'] == 1) {
                if (time() < $goods['timestart']) {
                    $goods['timebuy'] = '-1';
                    $goods['canbuy'] = false;
                } else if (time() > $goods['timeend']) {
                    $goods['timebuy'] = '1';
                    $goods['canbuy'] = false;
                }
            }
        }

        //是否可以加入购物车
        $canAddCart = true;
        if ($goods['isverify'] == 2 || $goods['type'] == 2 || $goods['type'] == 3 || $goods['type'] == 20 || !empty($goods['cannotrefund']) || !empty($is_task_goods) || !empty($gifts)) {
            $canAddCart = false;
        }
         if(!empty($seckillinfo)){//秒杀商品禁止加入购物车
            $canAddCart = false;
         }
        if ($goods['type'] == 2 && empty($specs)) {
            $gflag = 1;
        } else {
            $gflag = 0;
        }
        //营销活动
       
        $enoughs = com_run('sale::getEnoughs'); //立减
        $goods_nofree = com_run('sale::getEnoughsGoods');

        if (empty($is_task_goods)) {
            $enoughfree = com_run('sale::getEnoughFree'); //满包邮
            $data = m('common')->getPluginset('sale');
            $goodsprovinces=$data['enoughprovince'];
            
        }

//        var_dump($enoughfree);exit;


        if(!empty($goods_nofree)) {
            if (in_array($id, $goods_nofree)) {
                $enoughfree = false;
            }
        }
        if ($enoughfree && $goods['minprice'] > $enoughfree) {
            $goods['dispatchprice'] = 0;
        }

        $hasSales = false;
        if ($goods['ednum'] > 0 || $goods['edmoney'] > 0) {
            $hasSales = true;
        }
        if ($enoughfree || ($enoughs && count($enoughs) > 0)) {
            $hasSales = true;
        }
        //价格显示
        $minprice = $goods['minprice']; $maxprice = $goods['maxprice'] ;

        $level = m('member')->getLevel($openid);
        if (empty($is_task_goods)) {
            $memberprice = m('goods')->getMemberPrice($goods, $level);
        }

        if($goods['isdiscount'] && $goods['isdiscount_stat_time']<=time() && $goods['isdiscount_time']>=time()){
            $goods['oldmaxprice'] = $maxprice;
            $prices = array();
          
            $isdiscount_discounts = json_decode($goods['isdiscount_discounts'],true);
            if (!isset($isdiscount_discounts['type']) || empty($isdiscount_discounts['type'])) {
                //统一促销
                $prices_array = m('order')->getGoodsDiscountPrice($goods, $level, 1);
                $prices[] = $prices_array['price'];
            } else {
                //详细促销
                $goods_discounts = m('order')->getGoodsDiscounts($goods, $isdiscount_discounts, $levelid);
                $prices = $goods_discounts['prices'];
            }
            $minprice = min($prices);
            $maxprice = max($prices);
        }else{

            if ( isset($options) && count($options) > 0 && $goods['hasoption']) {
                $optionids = array();
                foreach ($options as $val){
                    $optionids[] = $val['id'];
                }
                //更新最低价和最高价
                $sql = "update ".tablename('ewei_shop_goods')." g set
        g.minprice = (select min(marketprice) from ".tablename('ewei_shop_goods_option')." where goodsid = $id),
        g.maxprice = (select max(marketprice) from ".tablename('ewei_shop_goods_option')." where goodsid = $id)
        where g.id = $id and g.hasoption=1";
                pdo_query($sql);
            } else {
                $sql = "update ".tablename('ewei_shop_goods')." set minprice = marketprice,maxprice = marketprice where id = $id and hasoption=0;";
                pdo_query($sql);
            }
            $goods_price = pdo_fetch("select minprice,maxprice from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
            $maxprice = (float)$goods_price['maxprice'];
            $minprice = (float)$goods_price['minprice'];
        }

        if (!empty($is_task_goods)) {
            if ( isset($options) && count($options) > 0 && $goods['hasoption']) {
                $prices = array();
                foreach ($task_goods['spec'] as $k => $v) {
                    $prices[] = $v['marketprice'];
                }
                $minprice2 = min($prices);
                $maxprice2 = max($prices);

                if ($minprice2 < $minprice) {
                    $minprice = $minprice2;
                }
                if ($maxprice2 > $maxprice) {
                    $maxprice = $maxprice2;
                }
            } else {
                $minprice = $task_goods['marketprice'];
                $maxprice = $task_goods['marketprice'];
            }
        }
        $log_id=0;
        $goods['minprice'] = $minprice; $goods['maxprice'] =$maxprice;
        if($_GPC['log_id']>0){
            $log_id=$_GPC['log_id'];
            $lotterygoods=m("lottery")->show_goods($openid,$id,$_GPC['log_id']);
            if(!empty($lotterygoods)){
                
               // var_dump($lotterygoods['marketprice']);
                $goods['marketprice']=$lotterygoods['marketprice'];
                $goods['minprice']=$lotterygoods['marketprice'];
                $goods['maxprice']=$lotterygoods['marketprice'];
                $goods['productprice']=$lotterygoods['marketprice'];
                if($order_goodscount>=$lotterygoods['total']){
                    $goods['userbuy'] = 0;
                    $goods['canbuy']  = false;
                }
                $canAddCart = false;
            }
        }
        
        //是否显示商品评论
        $getComments = empty($_W['shopset']['trade']['closecommentshow']);

        //是否有配套服务
        $hasServices = $goods['cash'] || $goods['seven'] || $goods['repair'] || $goods['invoice'] || $goods['quality'];

        //是否收藏了
        $isFavorite = m('goods')->isFavorite($id);

        //购物车数量
        $cartCount = m('goods')->getCartCount();

        //浏览量 + 浏览记录
        m('goods')->addHistory($id);


        //店铺信息
        $shop = set_medias(m('common')->getSysset('shop'), 'logo');
        //店铺信息
        $shop['url'] = mobileUrl('',null,true);
        $mid = intval($_GPC['mid']);
        //判断是否开启分销
        $opencommission = false;
        if (p('commission')) {
            if (empty($member['agentblack'])) { //不在黑名单
                $cset = p('commission')->getSet();
                $opencommission = intval($cset['level']) > 0;

               $commissionprice=Dispage::get_goods_commission_price($goods['id'],$openid);
                //是否是小店
                if ($opencommission) {
                    if (empty($mid)) {
                        if ($member['isagent'] == 1 && $member['status'] == 1) {
                            $mid = $member['id'];
                        }
                    }
                    if (!empty($mid)) {
                        if (empty($cset['closemyshop'])) {
                            $shop = set_medias( p('commission')->getShop($mid), 'logo');
                            $shop['url'] = mobileUrl('commission/myshop', array('mid' => $mid),true);
                        }
                    }

                }
            }
        }
        if (empty($this->merch_user))
        {
            $merch_flag = 0;
            if ($is_openmerch == 1 && $goods['merchid'] > 0) {
                $merch_user = pdo_fetch("select * from ".tablename('ewei_shop_merch_user')." where id=:id limit 1",array(':id'=>intval($goods['merchid'])));
                if (!empty($merch_user)) {
                    $shop = $merch_user;
                    $merch_flag = 1;
                }
            }

            if ($merch_flag == 1) {
                $shopdetail = array(
                    'logo' => !empty($goods['detail_logo']) ? tomedia($goods['detail_logo']) : tomedia($shop['logo']),
                    'shopname' => !empty($goods['detail_shopname']) ? $goods['detail_shopname'] : $shop['merchname'],
                    'description' =>!empty($goods['detail_totaltitle']) ? $goods['detail_totaltitle'] : $shop['desc'],
                    'btntext1' => trim($goods['detail_btntext1']),
                    'btnurl1' => !empty($goods['detail_btnurl1']) ? $goods['detail_btnurl1'] : mobileUrl('goods'),
                    'btntext2' => trim($goods['detail_btntext2']),
                    'btnurl2' => !empty($goods['detail_btnurl2']) ? $goods['detail_btnurl2'] : mobileUrl('merch',array('merchid'=> $goods['merchid']))
                );
            } else {
                $shopdetail = array(
                    'logo' => !empty($goods['detail_logo']) ? tomedia($goods['detail_logo']) : $shop['logo'],
                    'shopname' => !empty($goods['detail_shopname']) ? $goods['detail_shopname'] : $shop['name'],
                    'description' =>!empty($goods['detail_totaltitle']) ? $goods['detail_totaltitle'] : $shop['desc'],
                    'btntext1' => trim($goods['detail_btntext1']),
                    'btnurl1' => !empty($goods['detail_btnurl1']) ? $goods['detail_btnurl1'] : mobileUrl('goods'),
                    'btntext2' => trim($goods['detail_btntext2']),
                    'btnurl2' => !empty($goods['detail_btnurl2']) ? $goods['detail_btnurl2'] : $shop['url']
                );
            }


            $param = array(':uniacid'=>$_W['uniacid']);
            if ($merch_flag == 1) {
                $sqlcon = " and merchid=:merchid";
                $param[':merchid'] = $goods['merchid'];
            }

            //统计
            if (empty($shop['selectgoods'])) {
                $statics = array(
                    'all'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid {$sqlcon} and status=1 and deleted=0", $param),
                    'new'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid {$sqlcon} and isnew=1 and status=1 and deleted=0", $param),
                    'discount'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid {$sqlcon} and isdiscount=1 and status=1 and deleted=0", $param)
                );
            } else {
                $goodsids = explode(",", $shop['goodsids']);
                $statics = array(
                    'all'=>count($goodsids),
                    'new'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid {$sqlcon} and id in( {$shop['goodsids']} ) and isnew=1 and status=1 and deleted=0", $param),
                    'discount'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid {$sqlcon} and id in( {$shop['goodsids']} ) and isdiscount=1 and status=1 and deleted=0", $param)
                );
            }
        }
        else
        {
            if ($goods['checked'] == 1)
            {
                $err = true;
                include $this->template();
                exit;
            }
            $shop = $this->merch_user;
            $shopdetail = array(
                'logo' => !empty($goods['detail_logo']) ? tomedia($goods['detail_logo']) : tomedia($shop['logo']),
                'shopname' => !empty($goods['detail_shopname']) ? $goods['detail_shopname'] : $shop['merchname'],
                'description' =>!empty($goods['detail_totaltitle']) ? $goods['detail_totaltitle'] : $shop['desc'],
                'btntext1' => trim($goods['detail_btntext1']),
                'btnurl1' => !empty($goods['detail_btnurl1']) ? $goods['detail_btnurl1'] : mobileUrl('goods'),
                'btntext2' => trim($goods['detail_btntext2']),
                'btnurl2' => !empty($goods['detail_btnurl2']) ? $goods['detail_btnurl2'] : mobileUrl('merch',array('merchid'=> $goods['merchid']))
            );

            //统计
            if (empty($shop['selectgoods'])) {
                $statics = array(
                    'all'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid and merchid=:merchid and status=1 and deleted=0",array(':uniacid'=>$_W['uniacid'],':merchid'=>$goods['merchid'])),
                    'new'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid and merchid=:merchid and isnew=1 and status=1 and deleted=0",array(':uniacid'=>$_W['uniacid'],':merchid'=>$goods['merchid'])),
                    'discount'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid and merchid=:merchid and isdiscount=1 and status=1 and deleted=0",array(':uniacid'=>$_W['uniacid'],':merchid'=>$goods['merchid']))
                );
            } else {
                $goodsids = explode(",", $shop['goodsids']);
                $statics = array(
                    'all'=>count($goodsids),
                    'new'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid and merchid=:merchid and id in( {$shop['goodsids']} ) and isnew=1 and status=1 and deleted=0",array(':uniacid'=>$_W['uniacid'],':merchid'=>$goods['merchid'])),
                    'discount'=>pdo_fetchcolumn('select count(1) from '.tablename('ewei_shop_goods')." where uniacid=:uniacid and merchid=:merchid and id in( {$shop['goodsids']} ) and isdiscount=1 and status=1 and deleted=0",array(':uniacid'=>$_W['uniacid'],':merchid'=>$goods['merchid']))
                );
            }
        }

        //分享
        $goodsdesc = !empty($goods['description']) ? $goods['description']  : $goods['subtitle'];
        $_W['shopshare'] = array(
            'title' => !empty($goods['share_title']) ? $goods['share_title'] : $goods['title'],
            'imgUrl' => !empty($goods['share_icon']) ? tomedia($goods['share_icon']) : tomedia($goods['thumb']),
            'desc' => !empty($goodsdesc) ? $goodsdesc  : $_W['shopset']['shop']['name'],
            'link' => mobileUrl('goods/detail', array('id' => $goods['id']),true)
        );
        $com = p('commission');
        if ($com) {
            $cset = $_W['shopset']['commission'];
            if (!empty($cset)) {
                if ($member['isagent'] == 1 && $member['status'] == 1) {
                    $_W['shopshare']['link'] = mobileUrl('goods/detail', array('id' => $goods['id'], 'mid' => $member['id']),true);
                } else if (!empty($_GPC['mid'])) {
                    $_W['shopshare']['link'] = mobileUrl('goods/detail', array('id' => $goods['id'], 'mid' => $_GPC['mid']),true);
                }
            }
        }

        //核销门店
        $stores = array();
        if ($goods['isverify'] == 2) {
            $storeids = array();
            if (!empty($goods['storeids'])) {
                $storeids = array_merge(explode(',', $goods['storeids']), $storeids);
            }

            if (empty($storeids)) {
                //全部门店
                if ($merchid > 0) {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 ', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
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
        $new_temp = !is_mobile()?1:intval($_W['shopset']['template']['detail_temp']);
        if($new_temp && $getComments){
            $showComments = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and level>=0 and deleted=0 and checked=0 and uniacid=:uniacid", array(':goodsid'=>$id,':uniacid'=>$_W['uniacid']));
        }

        if(p('diypage')){
            $diypage = p('diypage')->detailPage($goods['diypage']);
            if($diypage){
                include $this->template('diypage/detail');
                exit();
            }
        }

        include $this->template();

    }
    //获取赠品信息
    function querygift(){
        global $_W,$_GPC;
        $uniacid = $_W['uniacid'];
        $giftid = $_GPC['id'];

        $gift = pdo_fetch("select * from ".tablename('ewei_shop_gift')." where uniacid = ".$uniacid." and status = 1 and id = ".$giftid." ");
        show_json(1,$gift);
    }

    /* 计算一个商品的运费
     * @param type $goods 商品数据
     * @param type $param 重量或数量
     * @param type $areas 特殊的地区
     * @param type $city 邮寄到的城市
     * @return type
     */

    protected function getGoodsDispatchPrice($goods) {
        if (!empty($goods['issendfree'])) {
            //包邮
            return 0;
        }

        if ($goods['type'] == 2 || $goods['type'] == 3 || $goods['type'] == 20) {
            //虚拟物品或虚拟卡密
            return 0;
        }

        if ($goods['dispatchtype'] == 1) {
            //统一运费
            return $goods['dispatchprice'];
        } else {
            //运费模板

            if (empty($goods['dispatchid'])) {
                //默认快递
                $dispatch = m('dispatch')->getDefaultDispatch($goods['merchid'],$goods['disgoods_id'],$goods['id']);
                //var_dump($dispatch);
            } else {
                $dispatch = m('dispatch')->getOneDispatch($goods['dispatchid']);
            }

            if (empty($dispatch)) {
                //最新的一条快递信息
                $dispatch = m('dispatch')->getNewDispatch($goods['merchid']);
            }

            $areas = iunserializer($dispatch['areas']);
            if (!empty($areas) && is_array($areas))
            {
                $firstprice = array();
                foreach ($areas as $val){
                    $firstprice[] = $val['firstprice'];
                }
                array_push($firstprice,m('dispatch')->getDispatchPrice(1, $dispatch));
                $ret = array(
                    'min' => round(min($firstprice),2),
                    'max' => round(max($firstprice),2)
                );
            }

            else
            {

                $ret = m('dispatch')->getDispatchPrice(1, $dispatch);
            }

            return $ret;
        }
    }

    function get_detail() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $goods = pdo_fetch('select * from '.tablename('ewei_shop_goods').' where id=:id and uniacid=:uniacid limit 1',array(':id'=>$id,':uniacid'=>$_W['uniacid']));
        
        die(m('ui')->lazy($goods['content']));
    }

    function get_comments(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $percent = 100;
        $params = array(':goodsid'=>$id,':uniacid'=>$_W['uniacid']);
        $count = array(
            "all"=>pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and level>=0 and deleted=0 and checked=0 and uniacid=:uniacid",$params),
            "good"=>pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and level>=5 and deleted=0 and checked=0 and uniacid=:uniacid",$params),
            "normal"=>pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and level>=2 and level<=4 and deleted=0 and checked=0 and uniacid=:uniacid",$params),
            "bad"=>pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and level<=1 and deleted=0 and checked=0 and uniacid=:uniacid",$params),
            "pic"=>pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and ifnull(images,'a:0:{}')<>'a:0:{}' and deleted=0 and checked=0 and uniacid=:uniacid",$params)
        ) ;
        $list = array();
        if($count['all']>0){
            $percent = intval( $count['good'] / (empty($count['all'])?1:$count['all']) * 100);
            $list = pdo_fetchall('select nickname,level,content,images,createtime from '.tablename('ewei_shop_order_comment')." where goodsid=:goodsid and deleted=0 and checked=0 and uniacid=:uniacid order by istop desc, createtime desc, id desc limit 2",array(':goodsid'=>$id,':uniacid'=>$_W['uniacid']));
            foreach($list as &$row){
                $row['createtime'] = date('Y-m-d H:i',$row['createtime']);
                $row['images'] = set_medias(iunserializer($row['images']));
                $row['nickname'] = cut_str($row['nickname'], 1, 0).'**'.cut_str($row['nickname'], 1, -1);
            }
            unset($row);
        }
        show_json(1,array('count'=>$count, 'percent'=>$percent, 'list'=>$list));
    }

    function get_comment_list(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $level = trim($_GPC['level']);
        $params = array(':goodsid'=>$id,':uniacid'=>$_W['uniacid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $condition = "";
        if($level=='good'){
            $condition=" and level=5";
        } else if($level=='normal'){
            $condition=" and level>=2 and level<=4";
        }else if($level=='bad'){
            $condition=" and level<=1";
        }else if($level=='pic'){
            $condition=" and ifnull(images,'a:0:{}')<>'a:0:{}'";
        }
        $list = pdo_fetchall("select * from ".tablename('ewei_shop_order_comment')." "
            . "  where goodsid=:goodsid and uniacid=:uniacid and deleted=0 and checked=0 $condition order by istop desc, createtime desc, id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach($list as &$row){
            $row['headimgurl'] = tomedia($row['headimgurl']);
            $row['createtime'] = date('Y-m-d H:i',$row['createtime']);
            $row['images'] = set_medias(iunserializer($row['images']));
            $row['reply_images'] = set_medias(iunserializer($row['reply_images']));
            $row['append_images'] = set_medias(iunserializer($row['append_images']));
            $row['append_reply_images'] = set_medias(iunserializer($row['append_reply_images']));
            $row['nickname'] = cut_str($row['nickname'], 1, 0).'**'.cut_str($row['nickname'], 1, -1);
        }
        unset($row);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order_comment') . " where goodsid=:goodsid  and uniacid=:uniacid and deleted=0 and checked=0 {$condition}", $params);
        show_json(1,array('list'=>$list,'total'=>$total,'pagesize'=>$psize));
    }
    function qrcode() {
        global $_W, $_GPC;
        $url = $_W['root'];
        show_json(1, array('url' => m('qrcode')->createQrcode($url)));
    }
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
     //获取当前商品及当前用户组可领取的免费优惠券
    function getCouponsbygood($goodid){
        global $_W, $_GPC;

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        // 读取 优惠券
        $time = time();

        $param = array();
        $param[':uniacid'] = $_W['uniacid'];

        $sql = "select id,timelimit,coupontype,timedays,timestart,timeend,thumb,couponname,enough,backtype,deduct,discount,backmoney,backcredit,backredpack,bgcolor,thumb,credit,money,getmax,merchid,total as t,limitmemberlevels,limitagentlevels,limitpartnerlevels,limitaagentlevels,limitgoodcatetype,limitgoodcateids,limitgoodtype,limitgoodids,tagtitle,settitlecolor,titlecolor from " . tablename('ewei_shop_coupon') . " c ";
        $sql.=" where uniacid=:uniacid and money=0 and credit = 0 and coupontype=0";
        if ($is_openmerch == 0) {
            $sql .= ' and merchid=0';
        }else {
            if (!empty($_GPC['merchid'])) {
                $sql .= ' and merchid=:merchid';
                $param[':merchid'] = intval($_GPC['merchid']);
            }else
            {
                $sql .= ' and merchid=0';
            }
        }

        //分销商限制
        $hascommission = false;
        $plugin_com = p('commission');
        if ($plugin_com) {
            $plugin_com_set = $plugin_com->getSet();
            $hascommission = !empty($plugin_com_set['level']);
            if(empty($plugin_com_set['level']))
            {
                $sql .= ' and ( limitagentlevels = "" or  limitagentlevels is null )';
            }
        }
        else
        {
            $sql .= ' and ( limitagentlevels = "" or  limitagentlevels is null )';
        }

        //股东限制
        $hasglobonus = false;
        $plugin_globonus = p('globonus');
        if ($plugin_globonus) {
            $plugin_globonus_set = $plugin_globonus->getSet();
            $hasglobonus = !empty($plugin_globonus_set['open']);
            if(empty($plugin_globonus_set['open']))
            {
                $sql .= ' and ( limitpartnerlevels = ""  or  limitpartnerlevels is null )';
            }
        }
        else
        {
            $sql .= ' and ( limitpartnerlevels = ""  or  limitpartnerlevels is null )';
        }

        //区域代理限制
        $hasabonus = false;
        $plugin_abonus = p('abonus');
        if ($plugin_abonus) {
            $plugin_abonus_set = $plugin_abonus->getSet();
            $hasabonus = !empty($plugin_abonus_set['open']);
            if(empty($plugin_abonus_set['open']))
            {
                $sql .= ' and ( limitaagentlevels = "" or  limitaagentlevels is null )';
            }
        }
        else
        {
            $sql .= ' and ( limitaagentlevels = "" or  limitaagentlevels is null )';
        }

        $sql.=" and gettype=1 and (total=-1 or total>0) and ( timelimit = 0 or  (timelimit=1 and timeend>unix_timestamp()))";
        $sql.=" order by displayorder desc, id desc  ";

        $list = set_medias(pdo_fetchall($sql, $param), 'thumb');
        if(empty($list))
        {
            $list=array();
        }

        if(!empty($goodid))
        {
            $goodparam[':uniacid'] = $_W['uniacid'];
            $goodparam[':id'] = $goodid;
            $sql = "select id,cates,marketprice,merchid   from " . tablename('ewei_shop_goods') ;
            $sql.=" where uniacid=:uniacid and id =:id order by id desc LIMIT 1 "; //类型+最低消费+示使用
            $good = pdo_fetch($sql, $goodparam);
        }

        $cates = explode(',',$good['cates']);

        if (!empty($list)) {
            foreach ($list as $key =>&$row) {
                $row = com('coupon')->setCoupon($row, time());

                $row['thumb'] = tomedia($row['thumb']);

                $row['timestr'] = "永久有效";
                if (empty($row['timelimit'])) {
                    if (!empty($row['timedays'])) {
                        $row['timestr'] = "自领取日后".$row['timedays']."天有效";
                    }
                } else {
                    if ($row['timestart'] >= $time) {
                        $row['timestr'] = '有效期至:'.date('Y-m-d', $row['timestart']) . '-' . date('Y-m-d', $row['timeend']);
                    } else {
                        $row['timestr'] = '有效期至:'.date('Y-m-d', $row['timeend']);
                    }
                }

                if ($row['backtype'] == 0) {
                    $row['backstr'] = '立减';
                    $row['backmoney'] = $row['deduct'];
                    $row['backpre'] = true;

                    if($row['enough']=='0')
                    {
                        $row['color']='org ';
                    }
                    else
                    {
                        $row['color']='blue';
                    }
                } else if ($row['backtype'] == 1) {
                    $row['backstr'] = '折';
                    $row['backmoney'] = $row['discount'];
                    $row['color']='red ';
                } else if ($row['backtype'] == 2) {
                    if($row['coupontype']=='0')
                    {
                        $row['color']='red ';
                    }
                    else
                    {
                        $row['color']='pink ';
                    }

                    if ($row['backredpack'] > 0) {
                        $row['backstr'] = '返现';
                        $row['backmoney'] = $row['backredpack'];
                        $row['backpre'] = true;
                    } else if ($row['backmoney'] > 0) {
                        $row['backstr'] = '返利';
                        $row['backmoney'] = $row['backmoney'];
                        $row['backpre'] = true;
                    } else if (!empty($row['backcredit'])) {
                        $row['backstr'] = '返积分';
                        $row['backmoney'] = $row['backcredit'];
                    }
                }




                //分类限制
                $limitmemberlevels =explode(",", $row['limitmemberlevels']);
                $limitagentlevels =explode(",", $row['limitagentlevels']);
                $limitpartnerlevels=explode(",", $row['limitpartnerlevels']);
                $limitaagentlevels=explode(",", $row['limitaagentlevels']);

                $p= 0;
                if($row['islimitlevel'] ==1) {
                    $openid = trim($_W['openid']);
                    $member = m('member')->getMember($openid);

                    if(!empty($row['limitmemberlevels'])||$row['limitmemberlevels']=='0')
                    {
                        //会员等级
                        $level1 = pdo_fetchall('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid and  id in ('.$coupon['limitmemberlevels'].') ', array(':uniacid' => $_W['uniacid']));

                        if (in_array($member['level'],$limitmemberlevels)){
                            $p= 1;
                        }
                    };

                    if((!empty($coupon['limitagentlevels'])||$coupon['limitagentlevels']=='0')&&$hascommission) {
                        //分销商等级
                        $level2 = pdo_fetchall('select * from ' . tablename('ewei_shop_commission_level') . ' where uniacid=:uniacid and id  in ('.$coupon['limitagentlevels'].') ', array(':uniacid' => $_W['uniacid']));

                        if($member['isagent']=='1'&&$member['status']=='1')
                        {
                            if (in_array($member['agentlevel'],$limitagentlevels)){
                                $p= 1;
                            }
                        }
                    }

                    if((!empty($coupon['limitpartnerlevels'])||$coupon['limitpartnerlevels']=='0')&&$hasglobonus) {
                        //股东等级
                        $level3 = pdo_fetchall('select * from ' . tablename('ewei_shop_globonus_level') . ' where uniacid=:uniacid and  id in('.$coupon['limitpartnerlevels'].') ', array(':uniacid' => $_W['uniacid']));

                        if($member['ispartner']=='1'&&$member['partnerstatus']=='1')
                        {
                            if (in_array($member['partnerlevel'],$limitpartnerlevels)){
                                $p= 1;
                            }
                        }
                    }
                    if((!empty($coupon['limitaagentlevels'])||$coupon['limitaagentlevels']=='0')&&$hasabonus) {
                        //区域代理
                        $level4 = pdo_fetchall('select * from ' . tablename('ewei_shop_abonus_level') . ' where uniacid=:uniacid and  id in ('.$coupon['limitaagentlevels'].') ', array(':uniacid' => $_W['uniacid']));

                        if($member['isaagent']=='1'&&$member['aagentstatus']=='1')
                        {
                            if (in_array($member['aagentlevel'],$limitaagentlevels)){
                                $p= 1;
                            }
                        }
                    }
                }else
                {
                    $p= 1;
                }


                if($p== 1)
                {
                    $p=0;
                    $limitcateids =explode(',',$row['limitgoodcateids']);
                    $limitgoodids =explode(',',$row['limitgoodids']);
                    if($row['limitgoodcatetype']==0&&$row['limitgoodtype']==0)
                    {
                        $p= 1;
                    }

                    if($row['limitgoodcatetype']==1)
                    {
                        $result = array_intersect($cates,$limitcateids);
                        if(count($result)>0)
                        {
                            $p= 1;
                        }
                    }

                    if($row['limitgoodtype']==1)
                    {
                        $isin = in_array($good['id'],$limitgoodids);
                        if($isin){
                            $p= 1;
                        }
                    }

                    //判断当前优惠券是否有可以生效的商品;
                    if($p==0)
                    {
                        unset($list[$key]);
                    }
                }else
                {
                    unset($list[$key]);
                }

            }
            unset($row);
        }
        return array_values($list);
    }
     //商品详情页领取可用优惠券
    public function pay($a=array(), $b=array()){
        global $_W, $_GPC;

        $openid = $_W['openid'];
        $id = intval($_GPC['id']);
        $coupon = pdo_fetch('select * from ' . tablename('ewei_shop_coupon') . ' where id=:id and uniacid=:uniacid  limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        $coupon = com('coupon')->setCoupon($coupon, time());
        //无法从领券中心领取
        if (empty($coupon['gettype'])) {
            show_json(-1, '无法'.$coupon['gettypestr']);
        }

        if ($coupon['total'] != -1) {
            if ($coupon['total'] <= 0) {
                show_json(-1, '优惠券数量不足'); //数量不足
            }
        }
        if (!$coupon['canget']) {
            show_json(-1, "您已超出{$coupon['gettypestr']}次数限制"); //已经领取完
        }
        if($coupon['money'] > 0||$coupon['credit']>0)
        {
            show_json(-1, '此优惠券需要前往领卷中心兑换'); //数量不足
        }

        $logno = m('common')->createNO('coupon_log', 'logno', 'CC');

        //生成日志
        $log = array(
            'uniacid' => $_W['uniacid'],
            'merchid' => $coupon['merchid'],
            'openid' => $openid,
            'logno' => $logno,
            'couponid' => $id,
            'status' => 0,
            'paystatus' => -1 ,
            'creditstatus' => -1 ,
            'createtime' => time(),
            'getfrom'=>1
        );
        pdo_insert('ewei_shop_coupon_log', $log);

        $result = com('coupon')->payResult($log['logno']);
        if(is_error($result)){
            show_json($result['errno'],$result['message']);
        }

        show_json(1,array('url'=>$result['url'],'dataid'=>$result['dataid'],'coupontype'=>$result['coupontype']));
    }

}
