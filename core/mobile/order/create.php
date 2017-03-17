<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once(EWEI_SHOPV2_TAX_CORE . 'tax_core.php');
class Create_EweiShopV2Page extends MobileLoginPage
{

    //多商户
    protected function merchData()
    {
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

    //订单统一模板
    protected function diyformData($member)
    {
        global $_W, $_GPC;

        $diyform_plugin = p('diyform');
        $order_formInfo = false;
        $diyform_set = false;
        $orderdiyformid = 0;
        $fields = array();
        $f_data = array();

        if ($diyform_plugin) {
            $diyform_set = $_W['shopset']['diyform'];
            if (!empty($diyform_set['order_diyform_open'])) {
                $orderdiyformid = intval($diyform_set['order_diyform']);
                if (!empty($orderdiyformid)) {
                    $order_formInfo = $diyform_plugin->getDiyformInfo($orderdiyformid);
                    $fields = $order_formInfo['fields'];
                    $f_data = $diyform_plugin->getLastOrderData($orderdiyformid, $member);
                }
            }
        }
        return array(
            'diyform_plugin' => $diyform_plugin,
            'order_formInfo' => $order_formInfo,
            'diyform_set' => $diyform_set,
            'orderdiyformid' => $orderdiyformid,
            'fields' => $fields,
            'f_data' => $f_data
        );
    }


    function main() {

        global $_W, $_GPC;

        $open_redis = function_exists('redis') && !is_error(redis());

        $seckillinfo = false;

        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];
        $log_id=intval($_GPC['log_id']);
        $goodsid = intval(intval($_GPC['id']));
        //赠品id，订单是否有赠品
        $giftid = intval($_GPC['giftid']);
        $giftGood = array();
        $depotid=intval($_GPC['depotid']);
        //允许参加优惠
        $allow_sale = true;
        $ifidentity=Dispage::check_readname($depotid);//wsq

        


        //是否为套餐订单
        $packageid = intval($_GPC['packageid']);
        if (!$packageid) {
            //多商户
            $merchdata = $this->merchData();
            extract($merchdata);

            $merch_array = array();
            $merchs = array();
            $merch_id = 0;

            //会员
            $member = m('member')->getMember($openid, true);
            
            $member['carrier_mobile'] = empty($member['carrier_mobile']) ? $member['mobile'] : $member['carrier_mobile'];

            //会员级别
            $level = m('member')->getLevel($openid);

            $diyformdata = $this->diyformData($member);
            extract($diyformdata);

            $id = intval($_GPC['id']);
            $bargain_id = intval($_GPC['bargainid']);
            $_SESSION['bargain_id'] = null;
            if (p('bargain') && !empty($bargain_id)) {//??

                $_SESSION['bargain_id'] = $bargain_id;
                $bargain_act = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_bargain_actor') . " WHERE id = :id AND openid = :openid AND status = '0'", array(':id' => $bargain_id, ':openid' => $_W['openid']));
                if (empty($bargain_act)) {
                    die('没有这个商品!');
                }
                $bargain_act_id = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_bargain_goods') . " WHERE id = '{$bargain_act['goods_id']}'");
                if (empty($bargain_act_id)) {
                    die('没有这个商品!');
                }
                $if_bargain = pdo_fetch("SELECT bargain FROM " . tablename('ewei_shop_goods') . " WHERE id = :id AND uniacid = :uniacid ", array(':id' => $bargain_act_id['goods_id'], ':uniacid' => $_W['uniacid']));
                if (empty($if_bargain['bargain'])) {
                    die('没有这个商品!');
                }
                $id = $bargain_act_id['goods_id'];
            }


            $optionid = intval($_GPC['optionid']);

            $total = intval($_GPC['total']);
            if ($total < 1) {
                $total = 1;
            }
            $buytotal = $total; //备份数量
            //错误代码 0 正常 1 未找到商品
            $errcode = 0;

            //是否为核销单
            $isverify = false;

            //是否为虚拟物品(虚拟或卡密)
            $isvirtual = false;

            //是否是虚拟物品自动发货
            $isvirtualsend = false;

            //是否可调整商品数量
            $changenum = false;

            //是否从购物车购买
            $fromcart = 0;

            //是否提供提供发票
            $hasinvoice = false;

            //最后一个发票名称
            $invoicename = "";

            //是否支持优惠
            $buyagain_sale = true;

            $buyagainprice = 0;

            //所有商品
            $goods = array();

            if (empty($id)) {
                //购物车
                $sql = 'SELECT c.goodsid,c.total,g.maxbuy,g.type,g.issendfree,g.isnodiscount'
                    . ',g.weight,o.weight as optionweight,g.title,g.disgoods_id,g.thumb,ifnull(o.marketprice, g.marketprice) as marketprice,o.title as optiontitle,c.optionid,'
                    . ' g.storeids,g.isverify,g.deduct,g.manydeduct,g.virtual,o.virtual as optionvirtual,discounts,'
                    . ' g.deduct2,g.ednum,g.edmoney,g.edareas,g.diyformtype,g.diyformid,diymode,g.dispatchtype,g.dispatchid,g.dispatchprice,g.minbuy '
                    . ' ,g.isdiscount,g.isdiscount_time,g.isdiscount_discounts,g.cates, '
                    . ' g.virtualsend,invoice,o.specs,g.merchid,g.checked,g.merchsale,'
                    . ' g.buyagain,g.buyagain_islong,g.buyagain_condition, g.buyagain_sale, g.hasoption'
                    . ' FROM ' . tablename('ewei_shop_member_cart') . ' c '
                    . ' left join ' . tablename('ewei_shop_goods') . ' g on c.goodsid = g.id '
                    . ' left join ' . tablename('ewei_shop_goods_option') . ' o on c.optionid = o.id '
                    . " where c.openid=:openid and c.selected=1 and  c.deleted=0 and c.uniacid=:uniacid and c.depotid=:depotid  order by c.id desc";
                $goods = pdo_fetchall($sql, array(':uniacid' => $uniacid, ':openid' => $openid,':depotid'=>$depotid));

                if (empty($goods)) {
                    $errcode = 1;
                    include $this->template();
                    exit;
                } else {
                    foreach ($goods as $k => $v) {
                        if (!empty($v['hasoption'])) {
                            $opdata = m('goods')->getOption($v['goodsid'], $v['optionid']);
                            if (empty($opdata) || empty($v['optionid'])) {
                                $this->message('商品' . $v['title'] . '的规格不存在,请到购物车删除该商品重新选择规格!', '', 'error');
                            }
                        }

                        if ($is_openmerch == 0) {
                            //未开启多商户的情况下,购物车中是否有多商户的商品
                            if ($v['merchid'] > 0) {
                                $err = true;
                                include $this->template('goods/detail');
                                exit;
                            }
                        } else {
                            //判断多商户商品是否通过审核
                            if ($v['merchid'] > 0 && $v['checked'] == 1) {
                                $err = true;
                                include $this->template('goods/detail');
                                exit;
                            }
                        }

                        //读取规格的图片
                        if (!empty($v['specs'])) {
                            $thumb = m('goods')->getSpecThumb($v['specs']);
                            if (!empty($thumb)) {
                                $goods[$k]['thumb'] = $thumb;
                            }
                        }
                        if (!empty($v['optionvirtual'])) {
                            $goods[$k]['virtual'] = $v['optionvirtual'];
                        }
                        if (!empty($v['optionweight'])) {
                            $goods[$k]['weight'] = $v['optionweight'];
                        }

                        //秒杀信息
                        $goods[$k]['seckillinfo'] = plugin_run('seckill::getSeckill', $v['goodsid'], $v['optionid'], true, $_W['openid']);
                    }
                }
                $fromcart = 1;
            } else {
                //直接购买
                $sql = 'SELECT id as goodsid,disgoods_id,type,title,weight,issendfree,depotid,isnodiscount, '
                    . ' thumb,marketprice,storeids,isverify,deduct,hasoption'
                    . ' manydeduct,`virtual`,maxbuy,usermaxbuy,discounts,total as stock,deduct2,showlevels,'
                    . ' ednum,edmoney,edareas,'
                    . ' diyformtype,diyformid,diymode,dispatchtype,dispatchid,dispatchprice,cates,minbuy, '
                    . ' isdiscount,isdiscount_time,isdiscount_discounts, '
                    . ' virtualsend,invoice,needfollow,followtip,followurl,merchid,checked,merchsale, '
                    . ' buyagain,buyagain_islong,buyagain_condition, buyagain_sale'
                    . ' FROM ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid  limit 1';
                $data = pdo_fetch($sql, array(':uniacid' => $uniacid, ':id' => $id));
                $depotid=$data['depotid'];//wsq
                $ifidentity=Dispage::check_readname($data['depotid']);//wsq

                if($member['showifidentity']==1){
                    $ifidentity=true;
                }
                //秒杀信息
                $data['seckillinfo'] = plugin_run('seckill::getSeckill', $data['goodsid'], $optionid, true, $_W['openid']);


                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {

                    //秒杀不管赠品

                } else {

                    if ($giftid) {
                        $gift = pdo_fetch("select id,title,thumb,activity,giftgoodsid,goodsid from " . tablename('ewei_shop_gift') . "
                where uniacid = " . $uniacid . " and id = " . $giftid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " ");
                        if (!strstr($gift['goodsid'], (string)$goodsid)) {
                            $this->message('赠品与商品不匹配或者商品没有赠品!', '', 'error');
                        }

                        $giftGood = array();
                        if (!empty($gift['giftgoodsid'])) {
                            $giftGoodsid = explode(',', $gift['giftgoodsid']);
                            if ($giftGoodsid) {
                                foreach ($giftGoodsid as $key => $value) {
                                    $giftGood[$key] = pdo_fetch("select id,title,thumb,marketprice from " . tablename('ewei_shop_goods') . " where uniacid = " . $uniacid . " and total > 0 and status = 2 and id = " . $value . " and deleted = 0 ");
                                }
                                $giftGood = array_filter($giftGood);
                            }
                        }
                    }
                }

                if (!empty($bargain_act)) {
                    $data['marketprice'] = $bargain_act['now_price'];//??
                }

                if (empty($data) || (!empty($data['showlevels']) && !strexists($data['showlevels'], $member['level'])) || ($data['merchid'] > 0 && $data['checked'] == 1) || ($is_openmerch == 0 && $data['merchid'] > 0)) {
                    $err = true;
                    include $this->template('goods/detail');
                    exit;
                }
                $follow = m("user")->followed($openid);
                if (!empty($data['needfollow']) && !$follow && is_weixin()) {
                    $followtip = empty($goods['followtip']) ? "如果您想要购买此商品，需要您关注我们的公众号，点击【确定】关注后再来购买吧~" : $goods['followtip'];
                    $followurl = empty($goods['followurl']) ? $_W['shopset']['share']['followurl'] : $goods['followurl'];
                    $this->message($followtip, $followurl, 'error');
                }
                if ($data['minbuy'] > 0 && $total < $data['minbuy']) {
                    $total = $data['minbuy'];
                }

                $data['total'] = $total;

                $data['optionid'] = $optionid;
                if (!empty($optionid)) {
                    $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,`virtual`,stock,weight,specs from ' . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $id, ':id' => $optionid));
                    if (!empty($option)) {
                        $data['optionid'] = $optionid;
                        $data['optiontitle'] = $option['title'];
                        $data['marketprice'] = $option['marketprice'];
                        $data['virtual'] = $option['virtual'];
                        $data['stock'] = $option['stock'];
                        if (!empty($option['weight'])) {
                            $data['weight'] = $option['weight'];
                        }
                        //读取规格的图片
                        if (!empty($option['specs'])) {
                            $thumb = m('goods')->getSpecThumb($option['specs']);
                            if (!empty($thumb)) {
                                $data['thumb'] = $thumb;
                            }
                        }
                    } else {
                        if (!empty($data['hasoption'])) {
                            $this->message('商品' . $data['title'] . '的规格不存在,请重新选择规格!', '', 'error');
                        }

                    }
                }

                //可以调整数量
                if ($giftid) {
                    $changenum = false;
                } else {
                    $changenum = true;
                }
                if($log_id>0){
                    $lotterygoods=m("lottery")->show_goods($_W['openid'],$id,$log_id);
                 
                    if(!empty($lotterygoods)){
                        $data['marketprice']=$lotterygoods['marketprice'];
                        $data['minprice']=$lotterygoods['marketprice'];
                        $data['maxprice']=$lotterygoods['marketprice'];
                        $data['maxbuy'] =$lotterygoods['total'];
                        
                        $data['unitprice']=$lotterygoods['marketprice'];
                        $data['canbuy']  = false;
                        $data['usermaxbuy']=$lotterygoods['total'];
                        $allow_sale=false;
                        $changenum = false;
                    }
                   
                }
               
                //秒杀不能修改数量
                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {
                    $changenum = false;
                }
                $goods[] = $data;

            }

            $goods = set_medias($goods, 'thumb');
            //$giftgoodstemp = set_medias($giftGood, 'thumb');
            //var_dump($goods);
           // die();
            foreach ($goods as &$g) {

                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    //秒杀不管任务
                    $g['is_task_goods'] = 0;

                } else {

                    //任务活动购买商品
                    $rank = intval($_SESSION[$id . '_rank']);
                    $join_id = intval($_SESSION[$id . '_join_id']);

                    $task_goods_data = m('goods')->getTaskGoods($openid, $id, $rank, $join_id, $optionid);
                    if (empty($task_goods_data['is_task_goods'])) {
                        $g['is_task_goods'] = 0;
                    } else {
                        $allow_sale = false;
                        $g['is_task_goods'] = $task_goods_data['is_task_goods'];
                        $g['is_task_goods_option'] = $task_goods_data['is_task_goods_option'];
                        $g['task_goods'] = $task_goods_data['task_goods'];
                    }
                }


                if ($is_openmerch == 1) {
                    $merchid = $g['merchid'];
                    $merch_array[$merchid]['goods'][] = $g['goodsid'];
                }

                if ($g['isverify'] == 2) {
                    //核销商品
                    $isverify = true;
                }

                if (!empty($g['virtual']) || $g['type'] == 2 || $g['type'] == 3 || $g['type'] == 20) {
                    //虚拟商品
                    $isvirtual = true;

                    //是否虚拟物品自动发货
                    if ($g['virtualsend']) {
                        $isvirtualsend = true;
                    }
                }

                if ($g['invoice']) {
                    $hasinvoice = $g['invoice'];
                }


                //最大购买量
                //库存
                $totalmaxbuy = $g['stock'];

                //最大购买量 秒杀只读取自己的总购买数限制 无二次购买
                if (!empty($g['seckillinfo']) && $g['seckillinfo']['status'] == 0) {

                    $seckilllast = 0;
                    if ($g['seckillinfo']['maxbuy'] > 0) {
                        $seckilllast = $g['seckillinfo']['maxbuy'] - $g['seckillinfo']['selfcount'];
                    }

                    $g['totalmaxbuy'] = $g['total'];


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
                            . ' where og.goodsid=:goodsid and  o.status>=0 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $g['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));
                        $last = $data['usermaxbuy'] - $order_goodscount;
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

                    if (!empty($g['is_task_goods'])) {
                        if ($totalmaxbuy > $g['task_goods']['total']) {
                            $totalmaxbuy = $g['task_goods']['total'];
                        }
                    }

                    $g['totalmaxbuy'] = $totalmaxbuy;

                    if ($g['total'] > $g['totalmaxbuy'] && !empty($g['totalmaxbuy'])) {
                        $g['total'] = $g['totalmaxbuy'];
                    }


                    if (floatval($g['buyagain']) > 0 && empty($g['buyagain_sale'])) {
                        //第一次后买东西享受优惠
                        if (m('goods')->canBuyAgain($g)) {
                            $buyagain_sale = false;
                        }
                    }

                }

            }
            unset($g);

            if ($hasinvoice) {
                $invoicename = pdo_fetchcolumn('select invoicename from ' . tablename('ewei_shop_order') . " where openid=:openid and uniacid=:uniacid and ifnull(invoicename,'')<>''", array(':openid' => $openid, ':uniacid' => $uniacid));
                if (empty($invoicename)) {
                    $invoicename = $member['realname'];
                }
            }

            if ($is_openmerch == 1) {
                //读取多商户营销设置
                foreach ($merch_array as $key => $value) {
                    if ($key > 0) {
                        $merch_id = $key;
                        $merch_array[$key]['set'] = $merch_plugin->getSet('sale', $key);
                        $merch_array[$key]['enoughs'] = $merch_plugin->getEnoughs($merch_array[$key]['set']);
                    }
                }
            }


            //商品总重量
            $weight = 0;

            //计算初始价格
            $total = 0; //商品数量
            $goodsprice = 0; //商品价格
            $realprice = 0; //需支付
            $deductprice = 0; //积分抵扣的
            $taskdiscountprice = 0; //任务活动优惠
            $discountprice = 0; //会员优惠
            $isdiscountprice = 0; //促销优惠
            $deductprice2 = 0; //余额抵扣限额
            $stores = array(); //核销门店
            $lotterydiscountprice = 0; //游戏活动优惠
            $address = false; //默认地址
            $carrier = false; //自提地点
            $carrier_list = array(); //自提点
            $dispatch_list = false;
            $dispatch_price = 0; //邮费

            $seckill_dispatchprice = 0; //秒杀商品的运费
            $seckill_price = 0;//秒杀减少的金额
            $seckill_payprice = 0;//秒杀的消费金额

            $ismerch = 0;

            if ($is_openmerch == 1) {
                if (!empty($merch_array)) {
                    if (count($merch_array) > 1) {
                        $ismerch = 1;
                    }
                }
            }

            if (!$isverify && !$isvirtual && !$ismerch) { //虚拟 或 卡密 或 不同多商户的商品 不读取自提点
                if ($merch_id > 0) {
                    $carrier_list = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(1,3) order by displayorder desc,id desc', array(':uniacid' => $_W['uniacid'], ':merchid' => $merch_id));
                } else {
                    $carrier_list = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(1,3) order by displayorder desc,id desc', array(':uniacid' => $_W['uniacid']));
                }
            }

            //营销插件
            $sale_plugin = com('sale');
            $saleset = false;
            if ($sale_plugin && $buyagain_sale && $allow_sale) {
                $saleset = $_W['shopset']['sale'];
                $saleset['enoughs'] = $sale_plugin->getEnoughs();
            }

            //计算产品成交价格及是否包邮
            foreach ($goods as &$g) {

                if (empty($g['total']) || intval($g['total']) < 1) {
                    $g['total'] = 1;
                }

                //秒杀无优惠
                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    $gprice = $g['ggprice'] = $g['seckillinfo']['price'] * $g['total'];
                    $seckill_payprice += $g['seckillinfo']['price'] * $g['total'];
                    $seckill_price += ($g['marketprice'] * $g['total'] - $gprice);

                } else {
                    //商品原价
                    $gprice = $g['marketprice'] * $g['total'];

                    //促销或会员折扣
                    if($log_id==0 || empty($log_id)){//当特惠商品存在的时候不在使用会员价格
                        $prices = m('order')->getGoodsDiscountPrice($g, $level);
                        $g['ggprice'] = $prices['price'];
                        $g['unitprice'] = $prices['unitprice'];
                    }
                   
                }


                if ($is_openmerch == 1) {
                    $merchid = $g['merchid'];
                    $merch_array[$merchid]['ggprice'] += $g['ggprice'];
                    $merchs[$merchid] += $g['ggprice'];
                }

                $g['dflag'] = intval($g['ggprice'] < $gprice);

                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    //秒杀不管优惠
                } else {
                    if (empty($bargain_id)) {//如果不是砍价订单,执行下面语句
                        //会员优惠
                        $discountprice += $prices['discountprice'];
                        //任务活动优惠
                        $taskdiscountprice += $prices['taskdiscountprice'];

                        //折扣价格
                        $g['taskdiscountprice'] = $prices['taskdiscountprice'];
                        $g['discountprice'] = $prices['discountprice'];
                        $g['isdiscountprice'] = $prices['isdiscountprice'];
                        $g['discounttype'] = $prices['discounttype'];
                        $g['isdiscountunitprice'] = $prices['isdiscountunitprice'];
                        $g['discountunitprice'] = $prices['discountunitprice'];

                        $buyagainprice += $prices['buyagainprice'];

                        if ($prices['discounttype'] == 1) {
                            //促销优惠
                            $isdiscountprice += $prices['isdiscountprice'];
                        } else if ($prices['discounttype'] == 2) {
                            //会员优惠
                            $discountprice += $prices['discountprice'];
                        }
                    }
                }


                //需要支付
                $realprice += $g['ggprice'];


                //商品原价
                //$goodsprice += $gprice;
                if ($gprice > $g['ggprice']) {
                    $goodsprice += $gprice;
                } else {
                    $goodsprice += $g['ggprice'];
                }

                //商品数据
                $total += $g['total'];


                if (empty($bargain_id)) {//如果不是砍价订单,执行下面语句

                    if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                        //秒杀不参与二次购买
                        $g['deduct'] = 0;
                    } else {
                        if (floatval($g['buyagain']) > 0 && empty($g['buyagain_sale'])) {
                            //第一次后买东西享受优惠
                            if (m('goods')->canBuyAgain($g)) {
                                $g['deduct'] = 0;
                            }
                        }
                    }

                    if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                        //秒杀不参与抵扣
                    } else {

                        
                     

                            //积分抵扣
                            if ($g['manydeduct']) {
                                $deductprice += $g['deduct'] * $g['total'];
                            } else {
                                $deductprice += $g['deduct'];
                            }

                            //余额抵扣限额

                            if ($g['deduct2'] == 0) {
                                //全额抵扣
                                $deductprice2 += $g['ggprice'];
                            } else if ($g['deduct2'] > 0) {
                                //最多抵扣
                                if ($g['deduct2'] > $g['ggprice']) {
                                    $deductprice2 += $g['ggprice'];
                                } else {
                                    $deductprice2 += $g['deduct2'];
                                }
                            }
                        
                    }
                }

            }
            unset($g);


            if ($isverify) {
                //核销单 所有核销门店
                $storeids = array();
                $merchid = 0;
                foreach ($goods as $g) {
                    if (!empty($g['storeids'])) {
                        $merchid = $g['merchid'];
                        $storeids = array_merge(explode(',', $g['storeids']), $storeids);
                    }
                }

                if (empty($storeids)) {
                    //门店加入支持核销的判断
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
            } else {
                //默认地址
                $address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where openid=:openid and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1'
                    , array(':uniacid' => $uniacid, ':openid' => $openid));

                if (!empty($carrier_list)) {
                    $carrier = $carrier_list[0];
                }
                //实体物品计算运费wsq
                if (!$isvirtual) {
                    $dispatch_array = m('order')->getOrderDispatchPrice($goods, $member, $address, $saleset, $merch_array, 0);//
                    $dispatch_price = $dispatch_array['dispatch_price'] - $dispatch_array['seckill_dispatch_price'];
                    $seckill_dispatchprice = $dispatch_array['seckill_dispatch_price'];
                }
            }


            //多商户满减
            if ($is_openmerch == 1) {
                if (empty($bargain_id)) {
                    $merch_enough = m('order')->getMerchEnough($merch_array);
                    $merch_array = $merch_enough['merch_array'];
                    $merch_enough_total = $merch_enough['merch_enough_total'];
                    $merch_saleset = $merch_enough['merch_saleset'];

                    if ($merch_enough_total > 0) {
                        $realprice -= $merch_enough_total;
                    }
                }
            }

            if ($saleset) {
                //满额减

                if (empty($bargain_id)) {
                    foreach ($saleset['enoughs'] as $e) {
                        if ($realprice - $seckill_payprice >= floatval($e['enough']) && floatval($e['money']) > 0) { //减掉秒杀的金额再算满减
                            $saleset['showenough'] = true;
                            $saleset['enoughmoney'] = $e['enough'];
                            $saleset['enoughdeduct'] = $e['money'];
                            $realprice -= floatval($e['money']);
                            break;
                        }
                    }
                }

                //余额抵扣加上运费
                 if (empty($saleset['dispatchnodeduct'])) {
                      $deductprice2+=$dispatch_price;
                 }
            }


            $realprice += $dispatch_price + $seckill_dispatchprice;

            $deductcredit = 0; //抵扣需要扣除的积分
            $deductmoney = 0; //抵扣的钱
            $deductcredit2 = 0; //余额抵扣的钱


            //积分抵扣
            if (!empty($saleset)) {

                if (!empty($saleset['creditdeduct'])) {
                    $credit = $member['credit1'];
                    if ($credit > 0) {
                        $credit = floor($credit);
                    }
                    $pcredit = intval($saleset['credit']); //积分比例
                    $pmoney = round(floatval($saleset['money']), 2); //抵扣比例
                    if ($pcredit > 0 && $pmoney > 0) {
                        if ($credit % $pcredit == 0) {
                            $deductmoney = round(intval($credit / $pcredit) * $pmoney, 2);
                        } else {
                            $deductmoney = round((intval($credit / $pcredit) + 1) * $pmoney, 2);
                        }
                    }

                    if ($deductmoney > $deductprice) {
                        $deductmoney = $deductprice;
                    }

                    if ($deductmoney > $realprice - $seckill_payprice) {  //减掉秒杀的金额再抵扣
                        $deductmoney = $realprice  - $seckill_payprice;
                    }
                    if ($pmoney * $pcredit != 0)
                        $deductcredit = floor($deductmoney / $pmoney * $pcredit);
                }

                if (!empty($saleset['moneydeduct'])) {

                    $deductcredit2 = m('member')->getCredit($openid, 'credit2');
                    if ($deductcredit2 > $realprice  - $seckill_payprice) {  //减掉秒杀的金额再抵扣
                        $deductcredit2 = $realprice  - $seckill_payprice;
                    }
                    if ($deductcredit2 > $deductprice2) {
                        $deductcredit2 = $deductprice2;
                    }

                }
            }

            //商品数据
            $goodsdata = array();
            $goodsdata_temp = array();
            foreach ($goods as $g) {


                $goodsdata[] = array('goodsid' => $g['goodsid'], 'total' => $g['total'], 'optionid' => $g['optionid'], 'marketprice' => $g['marketprice']
                , 'merchid' => $g['merchid'], 'cates' => $g['cates'], 'discounttype' => $g['discounttype'], 'isdiscountprice' => $g['isdiscountprice']
                , 'discountprice' => $g['discountprice'], 'isdiscountunitprice' => $g['isdiscountunitprice'], 'discountunitprice' => $g['discountunitprice']);
                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    //秒杀不管二次购买
                } else {
                    if (floatval($g['buyagain']) > 0) {
                        //第一次后买东西享受优惠
                        if (!m('goods')->canBuyAgain($g) || !empty($g['buyagain_sale'])) {
                            $goodsdata_temp[] = array('goodsid' => $g['goodsid'], 'total' => $g['total'], 'optionid' => $g['optionid'], 'marketprice' => $g['marketprice']
                            , 'merchid' => $g['merchid'], 'cates' => $g['cates'], 'discounttype' => $g['discounttype'], 'isdiscountprice' => $g['isdiscountprice']
                            , 'discountprice' => $g['discountprice'], 'isdiscountunitprice' => $g['isdiscountunitprice'], 'discountunitprice' => $g['discountunitprice']);
                        }
                    } else {
                        $goodsdata_temp[] = array('goodsid' => $g['goodsid'], 'total' => $g['total'], 'optionid' => $g['optionid'], 'marketprice' => $g['marketprice']
                        , 'merchid' => $g['merchid'], 'cates' => $g['cates'], 'discounttype' => $g['discounttype'], 'isdiscountprice' => $g['isdiscountprice']
                        , 'discountprice' => $g['discountprice'], 'isdiscountunitprice' => $g['isdiscountunitprice'], 'discountunitprice' => $g['discountunitprice']);
                    }
                }


                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    //秒杀不管赠品

                } else {
                    //指定商品赠品
                    if ($giftid) {
                        $gift = array();
                        $giftdata = pdo_fetch("select giftgoodsid from " . tablename('ewei_shop_gift') . " where uniacid = " . $uniacid . " and id = " . $giftid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " ");
                        if ($giftdata['giftgoodsid']) {
                            $giftgoodsid = explode(',', $giftdata['giftgoodsid']);

                            foreach ($giftgoodsid as $key => $value) {
                                $gift[$key] = pdo_fetch("select id as goodsid,title,thumb,marketprice from " . tablename('ewei_shop_goods') . " where uniacid = " . $uniacid . " and total > 0 and status = 2 and id = " . $value . " and deleted = 0 ");
                                if ($gift[$key]) {
                                    $gift[$key]['total'] = $total;
                                }
                                $total+=$gift[$key]['total'];
                                $goodsprice+=$gift[$key]['marketprice']*$gift[$key]['total'];
                                $realprice += floatval($gift[$key]['marketprice'])*$gift[$key]['total'];
                            }
                            $gift = array_filter($gift);
                            $goodsdata = array_merge($goodsdata, $gift);
                        }
                    } else {//订单满额赠品
                        $isgift = 0;
                        $gifts = array();
                        $giftgoods = array();
                        $gifts = pdo_fetchall("select id,goodsid,giftgoodsid,thumb,title from " . tablename('ewei_shop_gift') . "
                    where uniacid = " . $uniacid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " and orderprice <= " . $goodsprice . " and activity = 1 ");
                        foreach ($gifts as $key => $value) {
                            $isgift = 1;
                            $giftgoods = explode(",", $value['giftgoodsid']);
                            foreach ($giftgoods as $k => $val) {
                                $giftgoodsdetail = pdo_fetch("select id,title,thumb,marketprice from " . tablename('ewei_shop_goods') . " where uniacid = " . $uniacid . " and deleted = 0 and total > 0 and status = 2 and id = " . $val . " ");
                                if ($giftgoodsdetail) {
                                    $gifts[$key]['gift'][$k] = $giftgoodsdetail;
                                }
                            }
                            $gifts = array_filter($gifts);
                            $gifttitle = $gifts[$key]['gift'][$key]['title'] ? $gifts[$key]['gift'][$key]['title'] : '赠品';
                        }
                    }
                }

            }

            //var_dump($goodsdata);
           // die();
            //可用优惠券(减掉秒杀的商品及总价)
            $couponcount = com_run('coupon::consumeCouponCount', $openid, $realprice, $merch_array, $goodsdata_temp);
            if (empty($goodsdata_temp) || !$allow_sale) {
                $couponcount = 0;
            }
            //var_dump($allow_sale);

            // 强制绑定手机号
            $mustbind = 0;
            if (!empty($_W['shopset']['wap']['open']) && !empty($_W['shopset']['wap']['mustbind']) && empty($member['mobileverify'])) {
                $mustbind = 1;
            }

            if ($is_openmerch == 1) {
                $merchs = $merch_plugin->getMerchs($merch_array);
            }


            $goods_list = array();
            if ($ismerch) {
                $getListUser = $merch_plugin->getListUser($goods);
                $merch_user = $getListUser['merch_user'];

                foreach ($getListUser['merch'] as $k => $v) {
                    if (empty($merch_user[$k]['merchname'])) {
                        $goods_list[$k]['shopname'] = $_W['shopset']['shop']['name'];
                    } else {
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

            //订单创建数据
            $createInfo = array(
                'id' => $id,
                'gdid' => intval($_GPC['gdid']),
                'fromcart' => $fromcart,
                'addressid' => !empty($address) && !$isverify && !$isvirtual ? $address['id'] : 0,
                'storeid' => !empty($carrier_list) && !$isverify && !$isvirtual ? $carrier_list[0]['id'] : 0,
                'couponcount' => $couponcount,
                'coupon_goods'=>$goodsdata_temp,
                'isvirtual' => $isvirtual,
                'isverify' => $isverify,
                'goods' => $goodsdata,
                'merchs' => $merchs,
                'orderdiyformid' => $orderdiyformid,
                "log_id"=>$log_id,
                'giftid' => $giftid,
                'mustbind' => $mustbind
            );

            $buyagain = $buyagainprice;

        } else {


            $g = $_GPC['goods'];
            $g = json_decode(htmlspecialchars_decode($g, ENT_QUOTES), true);

            $package = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_package') . " WHERE uniacid = " . $uniacid . " and id = " . $packageid . " ");
            $package = set_medias($package, array('thumb'));
            if ($package['starttime'] > time()) {
                $this->message('套餐活动还未开始，请耐心等待!', '', 'error');
            }
            if ($package['endtime'] < time()) {
                $this->message('套餐活动已结束，谢谢您的关注，请浏览其他套餐或商品！', '', 'error');
            }

            $goods = array();
            $goodsprice = 0;
            $marketprice = 0;

            foreach ($g as $key => $value) {
                $goods[$key] = pdo_fetch("select id,title,thumb,marketprice,disgoods_id,depotid from " . tablename('ewei_shop_goods') . "
                            where id = " . $value['goodsid'] . " and uniacid = " . $uniacid . " ");
                $option = array();
                $packagegoods = array();
                if ($value['optionid'] > 0) {
                    $option = pdo_fetch("select title,packageprice from " . tablename('ewei_shop_package_goods_option') . "
                            where optionid = " . $value['optionid'] . " and goodsid=" . $value['goodsid'] . " and uniacid = " . $uniacid . " and pid = " . $packageid . " ");
                    $goods[$key]['packageprice'] = $option['packageprice'];
                } else {
                    $packagegoods = pdo_fetch("select title,packageprice from " . tablename('ewei_shop_package_goods') . "
                            where goodsid=" . $value['goodsid'] . " and uniacid = " . $uniacid . " and pid = " . $packageid . " ");
                    $goods[$key]['packageprice'] = $packagegoods['packageprice'];
                }


                $goods[$key]['optiontitle'] = !empty($option['title']) ? $option['title'] : '';
                $goods[$key]['optionid'] = !empty($value['optionid']) ? $value['optionid'] : 0;;
                $goods[$key]['goodsid'] = $value['goodsid'];
                $goods[$key]['total'] = 1;
                if ($option) {
                    $goods[$key]['packageprice'] = $option['packageprice'];
                } else {
                    $goods[$key]['packageprice'] = $goods[$key]['packageprice'];
                }
                $goodsprice += $goods[$key]['packageprice'];
                $marketprice += $goods[$key]['marketprice'];

            }
            //wsq检查套餐商品是否在同一个仓库中
            foreach ($goods as $key => $value) {
                $t[$value['depotid']]=$value['depotid'];
                $depotid=$value['depotid'];
            }
            if(count($t)>1){
                $this->message('套餐活动错误，谢谢您的关注，请联系管理员修改！', '', 'error');
            }
             $ifidentity=Dispage::check_readname($depotid);
             if($member['showifidentity']==1){
                    $ifidentity=true;
            }
            //默认地址
            $address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where openid=:openid and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1'
                , array(':uniacid' => $uniacid, ':openid' => $openid));

            $total = count($goods);
            $dispatch_price = $package['freight'];
            $realprice = $goodsprice + $package['freight'];

            //订单创建数据
            $createInfo = array(
                'id' => 0,
                'gdid' => intval($_GPC['gdid']),
                'fromcart' => 0,
                'packageid' => $packageid,
                'addressid' => $address['id'],
                'storeid' => 0,
                'couponcount' => 0,
                'isvirtual' => 0,
                'isverify' => 0,
                'goods' => $goods,
                'merchs' => 0,
                'orderdiyformid' => 0,
                'mustbind' => 0
            );
        }


        $_W['shopshare']['hideMenus'] = array('menuItem:share:qq', 'menuItem:share:QZone', 'menuItem:share:email', 'menuItem:copyUrl', 'menuItem:openWithSafari', 'menuItem:openWithQQBrowser', 'menuItem:share:timeline', 'menuItem:share:appMessage');

        include $this->template();
    }

    function getcouponprice()
    {
        global $_GPC;
        $couponid = intval($_GPC['couponid']);
        $goodsarr = $_GPC['goods'];
        $goodsprice = $_GPC['goodsprice'];
        $discountprice = $_GPC['discountprice'];
        $isdiscountprice = $_GPC['isdiscountprice'];

        $result = $this->caculatecoupon($couponid, $goodsarr, $goodsprice, $discountprice, $isdiscountprice);

        if (empty($result)) {
            show_json(0);
        } else {
            show_json(1, $result);
        }
    }

    function caculatecoupon($couponid, $goodsarr, $totalprice, $discountprice, $isdiscountprice, $isSubmit = 0, $discountprice_array = array(), $merchisdiscountprice = 0)
    {
        global $_W;

        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];

        if (empty($goodsarr)) {
            return false;
        }

        $sql = 'SELECT d.id,d.couponid,c.enough,c.backtype,c.deduct,c.discount,c.backmoney,c.backcredit,c.backredpack,c.merchid,c.limitgoodtype,c.limitgoodcatetype,c.limitgoodids,c.limitgoodcateids,c.limitdiscounttype  FROM ' . tablename('ewei_shop_coupon_data') . " d";
        $sql .= " left join " . tablename('ewei_shop_coupon') . " c on d.couponid = c.id";
        $sql .= ' where d.id=:id and d.uniacid=:uniacid and d.openid=:openid and d.used=0  limit 1';
        $data = pdo_fetch($sql, array(':uniacid' => $uniacid, ':id' => $couponid, ':openid' => $openid));

        $merchid = intval($data['merchid']);

        if (empty($data)) {
            return null;
        }


        if (is_array($goodsarr)) {

            $goods = array();

            foreach ($goodsarr as $g) {

                if (empty($g)) {
                    continue;
                }

                if ($merchid > 0 && $g['merchid'] != $merchid) {
                    continue;
                }

                $cates = explode(',', $g['cates']);
                $limitcateids = explode(',', $data['limitgoodcateids']);
                $limitgoodids = explode(',', $data['limitgoodids']);

                $pass = 0;

                if ($data['limitgoodcatetype'] == 0 && $data['limitgoodtype'] == 0) {
                    $pass = 1;
                }

                if ($data['limitgoodcatetype'] == 1) {
                    $result = array_intersect($cates, $limitcateids);
                    if (count($result) > 0) {
                        $pass = 1;
                    }
                }

                if ($data['limitgoodtype'] == 1) {
                    $isin = in_array($g['goodsid'], $limitgoodids);
                    if ($isin) {
                        $pass = 1;
                    }
                }
                if ($pass == 1) {
                    $goods[] = $g;
                }
            }

            $limitdiscounttype = intval($data['limitdiscounttype']);
            $coupongoodprice = 0;
            $gprice = 0;

            foreach ($goods as $k => $g) {

                $gprice = (float)$g['marketprice'] * (float)$g['total'];

                switch ($limitdiscounttype) {
                    case 1:
                        $coupongoodprice += $gprice - (float)$g['discountunitprice'] * (float)$g['total'];
                        $discountprice_array[$g['merchid']]['coupongoodprice'] += $gprice - (float)$g['discountunitprice'] * (float)$g['total'];

                        if ($g['discounttype'] == 1) {
                            $isdiscountprice -= (float)$g['isdiscountunitprice'] * (float)$g['total'];
                            $discountprice += (float)$g['discountunitprice'] * (float)$g['total'];

                            if ($isSubmit == 1) {
                                //计算价格
                                $totalprice = $totalprice - $g['ggprice'] + $g['price2'];
                                $discountprice_array[$g['merchid']]['ggprice'] = $discountprice_array[$g['merchid']]['ggprice'] - $g['ggprice'] + $g['price2'];
                                $goodsarr[$k]['ggprice'] = $g['price2'];
                                //重现计算多商户优惠

                                $discountprice_array[$g['merchid']]['isdiscountprice'] -= (float)$g['isdiscountunitprice'] * (float)$g['total'];
                                $discountprice_array[$g['merchid']]['discountprice'] += (float)$g['discountunitprice'] * (float)$g['total'];
                                //重现计算多商户促销优惠
                                if (!empty($data['merchsale'])) {
                                    $merchisdiscountprice -= (float)$g['isdiscountunitprice'] * (float)$g['total'];
                                    $discountprice_array[$g['merchid']]['merchisdiscountprice'] -= (float)$g['isdiscountunitprice'] * (float)$g['total'];
                                }
                            }
                        }
                        break;
                    case 2:
                        $coupongoodprice += $gprice - (float)$g['isdiscountunitprice'] * (float)$g['total'];
                        $discountprice_array[$g['merchid']]['coupongoodprice'] += $gprice - (float)$g['isdiscountunitprice'] * (float)$g['total'];
                        if ($g['discounttype'] == 2) {
                            $discountprice -= (float)$g['discountunitprice'] * (float)$g['total'];

                            if ($isSubmit == 1) {
                                //计算价格
                                $totalprice = $totalprice - $g['ggprice'] + $g['price1'];
                                $discountprice_array[$g['merchid']]['ggprice'] = $discountprice_array[$g['merchid']]['ggprice'] - $g['ggprice'] + $g['price1'];
                                $goodsarr[$k]['ggprice'] = $g['price1'];

                                $discountprice_array[$g['merchid']]['discountprice'] -= (float)$g['discountunitprice'] * (float)$g['total'];
                            }
                        }
                        break;
                    case 3:
                        $coupongoodprice += $gprice;
                        $discountprice_array[$g['merchid']]['coupongoodprice'] += $gprice;
                        if ($g['discounttype'] == 1) {
                            $isdiscountprice -= (float)$g['isdiscountunitprice'] * (float)$g['total'];

                            if ($isSubmit == 1) {
                                $totalprice = $totalprice - $g['ggprice'] + $g['price0'];
                                $discountprice_array[$g['merchid']]['ggprice'] = $discountprice_array[$g['merchid']]['ggprice'] - $g['ggprice'] + $g['price0'];
                                $goodsarr[$k]['ggprice'] = $g['price0'];

                                //重现计算多商户促销优惠
                                if (!empty($data['merchsale'])) {
                                    $merchisdiscountprice -= $g['isdiscountunitprice'] * (float)$g['total'];
                                    $discountprice_array[$g['merchid']]['merchisdiscountprice'] -= $g['isdiscountunitprice'] * (float)$g['total'];
                                }
                                $discountprice_array[$g['merchid']]['isdiscountprice'] -= $g['isdiscountunitprice'] * (float)$g['total'];
                            }
                        } else if ($g['discounttype'] == 2) {
                            $discountprice -= (float)$g['discountunitprice'] * (float)$g['total'];

                            if ($isSubmit == 1) {
                                $totalprice = $totalprice - $g['ggprice'] + $g['price0'];
                                $goodsarr[$k]['ggprice'] = $g['price0'];

                                $discountprice_array[$g['merchid']]['ggprice'] = $discountprice_array[$g['merchid']]['ggprice'] - $g['ggprice'] + $g['price0'];
                                $discountprice_array[$g['merchid']]['discountprice'] -= (float)$g['discountunitprice'] * (float)$g['total'];
                            }
                        }
                        break;
                    default:
                        if ($g['discounttype'] == 1) {
                            //促销优惠
                            $coupongoodprice += $gprice - (float)$g['isdiscountunitprice'] * (float)$g['total'];
                            $discountprice_array[$g['merchid']]['coupongoodprice'] += $gprice - (float)$g['isdiscountunitprice'] * (float)$g['total'];
                        } else if ($g['discounttype'] == 2) {
                            //会员优惠
                            $coupongoodprice += $gprice - (float)$g['discountunitprice'] * (float)$g['total'];
                            $discountprice_array[$g['merchid']]['coupongoodprice'] += $gprice - (float)$g['discountunitprice'] * (float)$g['total'];
                        } else if ($g['discounttype'] == 0) {
                            $coupongoodprice += $gprice;
                            $discountprice_array[$g['merchid']]['coupongoodprice'] += $gprice;
                        }
                        break;
                }
            }


            $deduct = ((float)$data['deduct']);
            $discount = ((float)$data['discount']);
            $backtype = ((float)$data['backtype']);

            $deductprice = 0;
            $coupondeduct_text = '';

            if ($deduct > 0 && $backtype == 0 && $coupongoodprice > 0) {
                if ($deduct > $coupongoodprice) {
                    $deduct = $coupongoodprice;
                }
                if ($deduct <= 0) {
                    $deduct = 0;
                }
                $deductprice = $deduct;
                $coupondeduct_text = '优惠券优惠';

                foreach ($discountprice_array as $key => $value) {
                    $discountprice_array[$key]['deduct'] = ((float)$value['coupongoodprice']) / (float)$coupongoodprice * $deduct;
                }
            } else if ($discount > 0 && $backtype == 1) {
                $deductprice = $coupongoodprice * (1 - $discount / 10);
                if ($deductprice > $coupongoodprice) {
                    $deductprice = $coupongoodprice;
                }
                if ($deductprice <= 0) {
                    $deductprice = 0;
                }

                foreach ($discountprice_array as $key => $value) {
                    $discountprice_array[$key]['deduct'] = ((float)$value['coupongoodprice']) * (1 - $discount / 10);
                }


                if ($merchid > 0) {
                    $coupondeduct_text = '店铺优惠券折扣(' . $discount . '折)';
                } else {
                    $coupondeduct_text = '优惠券折扣(' . $discount . '折)';
                }
            }
        }

        $totalprice -= $deductprice;

        $return_array = array();
        //根据优惠券规则计算后的促销优惠
        $return_array['isdiscountprice'] = $isdiscountprice;
        //根据优惠券规则计算后的会员折扣
        $return_array['discountprice'] = $discountprice;
        //优惠券折扣
        $return_array['deductprice'] = $deductprice;
        //参与优惠券优惠的商品总价
        $return_array['coupongoodprice'] = $coupongoodprice;
        //优惠券标题
        $return_array['coupondeduct_text'] = $coupondeduct_text;
        //根据优惠券规则计算后的商品总价
        $return_array['totalprice'] = $totalprice;
        //多商户订单信息
        $return_array['discountprice_array'] = $discountprice_array;
        //多商户优惠券价格
        $return_array['merchisdiscountprice'] = $merchisdiscountprice;
        //优惠券多商户ID
        $return_array['couponmerchid'] = $merchid;
        //商品信息更新
        $return_array['$goodsarr'] = $goodsarr;


        return $return_array;
    }


    function caculate()
    {
        global $_W, $_GPC;
        $open_redis = function_exists('redis') && !is_error(redis());
        $log_id=$_GPC['log_id'];
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        $merch_array = array();

        //允许参加优惠
        $allow_sale = true;

        //需支付
        $realprice = 0;

        //是否免邮
        $nowsendfree = false;

        //是否为核销单
        $isverify = false;

        //是否为虚拟物品(虚拟或卡密)
        $isvirtual = false;

        //任务活动优惠
        $taskdiscountprice = 0;

        //会员优惠
        $discountprice = 0;

        //促销优惠
        $isdiscountprice = 0;

        //积分抵扣的
        $deductprice = 0;

        //余额抵扣限额
        $deductprice2 = 0;

        //余额抵扣的钱
        $deductcredit2 = 0;

        //是否支持优惠
        $buyagain_sale = true;

        $buyagainprice = 0;

        $seckill_price = 0; //秒杀商品的总金额
        $seckill_payprice = 0; //秒杀的支付金额
        $seckill_dispatchprice = 0; //秒杀的运费

        $dispatchid = intval($_GPC['dispatchid']);

        $totalprice = floatval($_GPC['totalprice']);

        //快递还是自提 true为自提
        $dflag = $_GPC['dflag'];

        $addressid = intval($_GPC['addressid']);
        $address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where  id=:id and openid=:openid and uniacid=:uniacid limit 1'
            , array(':uniacid' => $uniacid, ':openid' => $openid, ':id' => $addressid));

        $member = m('member')->getMember($openid, true);
        $level = m('member')->getLevel($openid);
        $weight = floatval($_GPC['weight']);
        $dispatch_price = 0;
        $deductenough_money = 0; //满额减
        $deductenough_enough = 0;

        $goodsarr = $_GPC['goods'];

        if(count($goodsarr)==1){
            if($log_id>0){
               foreach($goodsarr as $g){
                    $lotterygoods=m("lottery")->show_goods($openid,$g['goodsid'],$log_id);
                    
                    if(!empty($lotterygoods)){

                        $allow_sale=false;
                    }
                } 
            }
            
        }
        if (is_array($goodsarr)) {

            $weight = 0;

            //所有商品
            $allgoods = array();

            foreach ($goodsarr as &$g) {
                if (empty($g)) {
                    continue;
                }

                $goodsid = $g['goodsid'];
                $optionid = $g['optionid'];
                $goodstotal = $g['total'];


                if ($goodstotal < 1) {
                    $goodstotal = 1;
                }
                if (empty($goodsid)) {
                    $nowsendfree = true;
                }
                $sql = 'SELECT id as goodsid,title,type, weight,total,issendfree,isnodiscount,disgoods_id, thumb,marketprice,cash,isverify,goodssn,productsn,sales,istime,'
                    . ' timestart,timeend,usermaxbuy,maxbuy,unit,buylevels,buygroups,deleted,status,deduct,manydeduct,`virtual`,'
                    . ' discounts,deduct2,ednum,edmoney,edareas,diyformid,diyformtype,diymode,dispatchtype,dispatchid,dispatchprice,'
                    . ' isdiscount,isdiscount_time,isdiscount_discounts ,virtualsend,merchid,merchsale,'
                    . ' buyagain,buyagain_islong,buyagain_condition, buyagain_sale,bargain'
                    . ' FROM ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid  limit 1';
                $data = pdo_fetch($sql, array(':uniacid' => $uniacid, ':id' => $goodsid));
                $data['seckillinfo'] = plugin_run('seckill::getSeckill', $goodsid, $optionid, true, $_W['openid']);

                if (empty($data)) {
                    $nowsendfree = true;
                }
                /*
                if ($data['status'] == 2) {
                    $data['marketprice'] = 0;
                }
            */
                //任务活动购买商品
                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {
                    //秒杀不管任务
                    $data['is_task_goods'] = 0;

                } else {
                    $rank = intval($_SESSION[$goodsid . '_rank']);
                    $join_id = intval($_SESSION[$goodsid . '_join_id']);
                    $task_goods_data = m('goods')->getTaskGoods($openid, $goodsid, $rank, $join_id, $optionid);
                    if (empty($task_goods_data['is_task_goods'])) {
                        $data['is_task_goods'] = 0;
                    } else {
                        $allow_sale = false;
                        $data['is_task_goods'] = $task_goods_data['is_task_goods'];
                        $data['is_task_goods_option'] = $task_goods_data['is_task_goods_option'];
                        $data['task_goods'] = $task_goods_data['task_goods'];
                    }
                }

                $data['stock'] = $data['total'];
                $data['total'] = $goodstotal;
                if (!empty($optionid)) {
                    $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,stock,`virtual`,weight from ' . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $goodsid, ':id' => $optionid));
                    if (!empty($option)) {
                        $data['optionid'] = $optionid;
                        $data['optiontitle'] = $option['title'];
                        $data['marketprice'] = $option['marketprice'];
                        if (!empty($option['weight'])) {
                            $data['weight'] = $option['weight'];
                        }
                    }
                }

                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {
                    $data['ggprice'] = $data['seckillinfo']['price'] * $g['total'];

                    $seckill_payprice += $data['ggprice'];

                    $seckill_price+= $data['marketprice'] * $g['total'];

                } else {
                    //计算折扣或促销后成交价格
                    if($log_id==0 || empty($log_id)){
                        $prices = m('order')->getGoodsDiscountPrice($data, $level);
                        $data['ggprice'] = $prices['price'];
                    }
                   
                   
                }

                if ($is_openmerch == 1) {
                    $merchid = $data['merchid'];
                    $merch_array[$merchid]['goods'][] = $data['goodsid'];
                    $merch_array[$merchid]['ggprice'] += $data['ggprice'];
                }

                if ($data['isverify'] == 2) {
                    $isverify = true;
                }

                if (!empty($data['virtual']) || $data['type'] == 2 || $data['type'] == 3 || $data['type'] == 20) {
                    $isvirtual = true;
                }

                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {

                    //秒杀不管其他活动
                    $g['taskdiscountprice'] = 0;
                    $g['discountprice'] = 0;
                    $g['isdiscountprice'] = 0;
                    $g['discounttype'] = 0;

                } else {

                    $g['taskdiscountprice'] = $prices['taskdiscountprice'];
                    $g['discountprice'] = $prices['discountprice'];
                    $g['isdiscountprice'] = $prices['isdiscountprice'];
                    $g['discounttype'] = $prices['discounttype'];

                    $taskdiscountprice += $prices['taskdiscountprice'];

                    //重复购买的优惠价格
                    $buyagainprice += $prices['buyagainprice'];

                }


                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {

                    //秒杀不管优惠
                } else {
                    if ($prices['discounttype'] == 1) {
                        //促销优惠
                        $isdiscountprice += $prices['isdiscountprice'];
                    } else if ($prices['discounttype'] == 2) {
                        //会员优惠
                        $discountprice += $prices['discountprice'];
                    }
                }

                if($log_id>0){
                    
                    $lotterygoods=m("lottery")->show_goods($openid,$goodsid,$log_id);
              
                        if(!empty($lotterygoods)){
                            $allow_sale=false;
                            $data['ggprice']=$lotterygoods['marketprice'];
                        }
                   
                }
                $realprice += $data['ggprice'];

                $allgoods[] = $data;

                if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {
                    //二次
                } else {
                    if (floatval($g['buyagain']) > 0 && empty($g['buyagain_sale'])) {
                        //第一次后买东西享受优惠
                        if (m('goods')->canBuyAgain($g)) {
                            $buyagain_sale = false;
                        }
                    }
                }

            }
            unset($g);


            if ($is_openmerch == 1) {
                //读取多商户营销设置
                foreach ($merch_array as $key => $value) {
                    if ($key > 0) {
                        $merch_array[$key]['set'] = $merch_plugin->getSet('sale', $key);
                        $merch_array[$key]['enoughs'] = $merch_plugin->getEnoughs($merch_array[$key]['set']);
                    }
                }
            }

            //营销
            $sale_plugin = com('sale');
            $saleset = false;
            if ($sale_plugin && $buyagain_sale && $allow_sale) {
                $saleset = $_W['shopset']['sale'];
                $saleset['enoughs'] = $sale_plugin->getEnoughs();
            }

            foreach ($allgoods as $g) {

                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    //秒杀不管二次
                    $g['deduct'] = 0;
                } else {
                    if (floatval($g['buyagain']) > 0 && empty($g['buyagain_sale'])) {
                        //第一次后买东西享受优惠
                        if (m('goods')->canBuyAgain($g)) {
                            $g['deduct'] = 0;
                        }
                    }
                }
                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {

                    //秒杀不管抵扣
                } else {

                    if( $open_redis ) {

                        //积分抵扣
                        if ($g['manydeduct']) {
                            $deductprice += $g['deduct'] * $g['total'];
                        } else {
                            $deductprice += $g['deduct'];
                        }

                        //余额抵扣限额
                        if ($g['deduct2'] == 0) {
                            //全额抵扣
                            $deductprice2 += $g['ggprice'];
                        } else if ($g['deduct2'] > 0) {

                            //最多抵扣
                            if ($g['deduct2'] > $g['ggprice']) {
                                $deductprice2 += $g['ggprice'];
                            } else {
                                $deductprice2 += $g['deduct2'];
                            }
                        }
                    }
                }
            }

            if ($isverify || $isvirtual) {
                $nowsendfree = true;
            }

            if (!empty($allgoods) && !$nowsendfree) {
                //计算运费
                $dispatch_array = m('order')->getOrderDispatchPrice($allgoods, $member, $address, $saleset, $merch_array, 1);

                $dispatch_price = $dispatch_array['dispatch_price'] -$dispatch_array['seckill_dispatch_price'];
                $nodispatch_array = $dispatch_array['nodispatch_array'];
                $seckill_dispatchprice =$dispatch_array['seckill_dispatch_price'];

            }

            //多商户满减
            if ($is_openmerch == 1) {
                $merch_enough = m('order')->getMerchEnough($merch_array);
                $merch_array = $merch_enough['merch_array'];
                $merch_enough_total = $merch_enough['merch_enough_total'];
                $merch_saleset = $merch_enough['merch_saleset'];

                if ($merch_enough_total > 0) {
                    $realprice -= $merch_enough_total;
                }
            }

            if ($saleset) {
                //满额减 (减掉秒杀金额)
                foreach ($saleset['enoughs'] as $e) {
                    if ($realprice - $seckill_payprice >= floatval($e['enough']) && floatval($e['money']) > 0) {
                        $deductenough_money = floatval($e['money']);
                        $deductenough_enough = floatval($e['enough']);
                        $realprice -= floatval($e['money']);
                        break;
                    }
                }
            }

            //使用快递
            if ($dflag != 'true') {
                //余额抵扣加上运费
                if (empty($saleset['dispatchnodeduct'])) {
                     $deductprice2+=$dispatch_price;
                }
            }

            $goodsdata_coupon = array();


            foreach ($allgoods as $g) {

                if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                    //秒杀不管优惠券

                } else {

                    if (floatval($g['buyagain']) > 0) {
                        //第一次后买东西享受优惠
                        if (!m('goods')->canBuyAgain($g) || !empty($g['buyagain_sale'])) {
                            $goodsdata_coupon[] = array('goodsid' => $g['goodsid'], 'total' => $g['total'], 'optionid' => $g['optionid'], 'marketprice' => $g['marketprice']
                            , 'merchid' => $g['merchid'], 'cates' => $g['cates'], 'discounttype' => $g['discounttype'], 'isdiscountprice' => $g['isdiscountprice']
                            , 'discountprice' => $g['discountprice'], 'isdiscountunitprice' => $g['isdiscountunitprice'], 'discountunitprice' => $g['discountunitprice']);
                        }
                    } else {
                        $goodsdata_coupon[] = array('goodsid' => $g['goodsid'], 'total' => $g['total'], 'optionid' => $g['optionid'], 'marketprice' => $g['marketprice']
                        , 'merchid' => $g['merchid'], 'cates' => $g['cates'], 'discounttype' => $g['discounttype'], 'isdiscountprice' => $g['isdiscountprice']
                        , 'discountprice' => $g['discountprice'], 'isdiscountunitprice' => $g['isdiscountunitprice'], 'discountunitprice' => $g['discountunitprice']);
                    }
                }

            }
            //是否合适的优惠券
            $couponcount = com_run('coupon::consumeCouponCount', $openid, $realprice - $seckill_payprice, $merch_array, $goodsdata_coupon);
            if (empty($goodsdata_coupon) || !$allow_sale) {
                $couponcount = 0;
            }

            $realprice += $dispatch_price +$seckill_dispatchprice;

            $deductcredit = 0; //抵扣需要扣除的积分
            $deductmoney = 0; //抵扣的钱


            if (!empty($saleset)) {
                //积分抵扣
                $credit = $member['credit1'];
                if ($credit > 0) {
                    $credit = floor($credit);
                }
                if (!empty($saleset['creditdeduct'])) {
                    $pcredit = intval($saleset['credit']); //积分比例
                    $pmoney = round(floatval($saleset['money']), 2); //抵扣比例


                    if ($pcredit > 0 && $pmoney > 0) {
                        if ($credit % $pcredit == 0) {
                            $deductmoney = round(intval($credit / $pcredit) * $pmoney, 2);
                        } else {
                            $deductmoney = round((intval($credit / $pcredit) + 1) * $pmoney, 2);
                        }
                    }
                    if ($deductmoney > $deductprice) {
                        $deductmoney = $deductprice;
                    }
                    if ($deductmoney > $realprice - $seckill_payprice) {  //减掉秒杀抵扣的金额
                        $deductmoney = $realprice - $seckill_payprice;
                    }
                    $deductcredit = floor($pmoney * $pcredit == 0 ? 0 : $deductmoney / $pmoney * $pcredit);
                }

                //余额抵扣
                 if (!empty($saleset['moneydeduct'])) {

                     $deductcredit2 = $member['credit2'];
                     if ($deductcredit2 > $realprice - $seckill_payprice) {  //减掉秒杀抵扣的金额
                         $deductcredit2 = $realprice - $seckill_payprice;
                     }
                     if ($deductcredit2 > $deductprice2) {
                         $deductcredit2 = $deductprice2;
                     }
                 }
            }
        }
        if ($is_openmerch == 1) {
            $merchs = $merch_plugin->getMerchs($merch_array);
        }

        $return_array = array();
        $return_array['price'] = $dispatch_price + $seckill_dispatchprice;
        $return_array['couponcount'] = $couponcount;
        $return_array['realprice'] = $realprice;
        $return_array['deductenough_money'] = $deductenough_money;
        $return_array['deductenough_enough'] = $deductenough_enough;
        $return_array['deductcredit2'] = $deductcredit2;
        $return_array['deductcredit'] = $deductcredit;
        $return_array['deductmoney'] = $deductmoney;
        $return_array['taskdiscountprice'] = $taskdiscountprice;
        $return_array['discountprice'] = $discountprice;
        $return_array['isdiscountprice'] = $isdiscountprice;

        $return_array['merch_showenough'] = $merch_saleset['merch_showenough'];
        $return_array['merch_deductenough_money'] = $merch_saleset['merch_enoughdeduct'];
        $return_array['merch_deductenough_enough'] = $merch_saleset['merch_enoughmoney'];
        $return_array['merchs'] = $merchs;
        $return_array['buyagain'] = $buyagainprice;

        $return_array['seckillprice'] = $seckill_price - $seckill_payprice;

        if (!empty($nodispatch_array['isnodispatch'])) {
            $return_array['isnodispatch'] = 1;
            $return_array['nodispatch'] = $nodispatch_array['nodispatch'];
        } else {
            $return_array['isnodispatch'] = 0;
            $return_array['nodispatch'] = '';
        }
        show_json(1, $return_array);
    }

    function submit()
    {

        global $_W, $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $log_id=intval($_GPC['log_id']);
        $open_redis = function_exists('redis') && !is_error(redis());

        if( $open_redis ) {
            $redis_key = "{$_W['uniacid']}_order_submit_{$openid}";
            $redis = redis();
            if (!is_error($redis)) {
                if ($redis->setnx($redis_key, time())) {
                    $redis->expireAt($redis_key, time() + 2);
                } else {
                    show_json(0, '不要短时间重复下单!');
                }
            }
        }
      
        //会员
        $member = m('member')->getMember($openid);
        //是黑名单
        if ($member['isblack'] == 1) {
            show_json(0);
        }

        // 验证是否必须绑定手机
        if (!empty($_W['shopset']['wap']['open']) && !empty($_W['shopset']['wap']['mustbind']) && empty($member['mobileverify'])) {
            show_json(0, array('message' => "请先绑定手机", 'url' => mobileUrl('member/bind', null, true)));
        }

        //允许参加优惠
        $allow_sale = true;

        //是否为套餐订单
        $packageid = intval($_GPC['packageid']);
        $package = array();         //套餐详情
        $packgoods = array();       //套餐商品详情
        $packageprice = 0;
        if (!empty($packageid)) {
            //套餐详情
            $package = pdo_fetch("SELECT id,title,price,freight,cash,starttime,endtime FROM " . tablename('ewei_shop_package') . "
                    WHERE uniacid = " . $uniacid . " and id = " . $packageid . " and deleted = 0 and status = 1  ORDER BY id DESC");
            if (empty($package)) {
                show_json(0, '未找到套餐！');
            }
            if ($package['starttime'] > time()) {
                show_json(0, '套餐活动未开始，请耐心等待！');
            }
            if ($package['endtime'] < time()) {
                show_json(0, '套餐活动已结束，谢谢您的关注，请您浏览其他套餐或商品！');
            }

            //套餐商品
            $packgoods = pdo_fetchall("SELECT id,title,thumb,packageprice,`option`,goodsid FROM " . tablename('ewei_shop_package_goods') . "
                    WHERE uniacid = " . $uniacid . " and pid = " . $packageid . "  ORDER BY id DESC");

            if (empty($packgoods)) {
                show_json(0, '未找到套餐商品！');
            }
        }

        $data = $this->diyformData($member);
        extract($data);

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        $merch_array = array();

        $ismerch = 0;
        $discountprice_array = array();

        //会员等级
        $level = m('member')->getLevel($openid);

        $dispatchid = intval($_GPC['dispatchid']);

        //配送方式
        $dispatchtype = intval($_GPC['dispatchtype']);

        //地址
        $addressid = intval($_GPC['addressid']);
        $address = false;
        if (!empty($addressid) && $dispatchtype == 0) {
            $address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1'
                , array(':uniacid' => $uniacid, ':openid' => $openid, ':id' => $addressid));
            if (empty($address)) {
                show_json(0, '未找到地址');
            }
        }

        $carrierid = intval($_GPC['carrierid']);

        $goods = $_GPC['goods'];

        $goods[0]['bargain_id'] = $_SESSION['bargain_id'];//砍价订单的价格传递
        $_SESSION['bargain_id'] == null;

        if (!empty($goods[0]['bargain_id'])) {
            $allow_sale = false;
        }
        if (empty($goods) || !is_array($goods)) {
            show_json(0, '未找到任何商品');
        }
       
        

        //所有商品
        $allgoods = array();
        $tgoods = array();
        $totalprice = 0; //总价
        $goodsprice = 0; //商品总价
        $grprice = 0; //商品实际总价
        $weight = 0; //总重量
        $taskdiscountprice = 0; //任务活动优惠
        $discountprice = 0; //折扣的钱
        $isdiscountprice = 0; //促销优惠的钱
        $merchisdiscountprice = 0; //多商户促销优惠的钱
        $cash = 1; //是否支持货到付款
        $depotid=0;//仓库ID
        $isdisorder=0;//是不是代理订单
        $deductprice = 0; //抵扣的钱

        $deductprice2 = 0; // 余额最多可抵扣
        $virtualsales = 0; //虚拟卡密的虚拟销量

        $dispatch_price = 0;

        $seckill_price = 0;//秒杀的商品价格
        $seckill_payprice = 0; //秒杀商品的总价格，此部分钱不参加活动
        $seckill_dispatchprice = 0; //秒杀商品的运费，不参与余额抵扣抵扣

        //是否支持重购优惠
        $buyagain_sale = true;

        $buyagainprice = 0;

        $sale_plugin = com('sale'); //营销插件
        $saleset = false;
        if ($sale_plugin && $allow_sale) {
            $saleset = $_W['shopset']['sale'];
            $saleset['enoughs'] = $sale_plugin->getEnoughs();
        }
        $isvirtual = false;
        $isverify = false;
        $verifytype = 0; //核销类型
        $isvirtualsend = false;

        $couponmerchid = 0; //使用的优惠券merchid

        //赠品
        $giftid = $_GPC['giftid'];
        if ($giftid) {
            $gift = array();
            $giftdata = pdo_fetch("select giftgoodsid from " . tablename('ewei_shop_gift') . " where uniacid = " . $uniacid . " and id = " . $giftid . " and status = 1 and starttime <= " . time() . " and endtime >= " . time() . " ");
            if ($giftdata['giftgoodsid']) {
                $giftgoodsid = explode(',', $giftdata['giftgoodsid']);
                foreach ($giftgoodsid as $key => $value) {
                    $gift[$key] = pdo_fetch("select id as goodsid,title,thumb from " . tablename('ewei_shop_goods') . " where uniacid = " . $uniacid . " and deleted = 0 and status = 2 and id = " . $value . " and deleted = 0 ");
                    //$gift[$key]['marketprice'] = 0;
                }
                $gift = array_filter($gift);
                $goods = array_merge($goods, $gift);
            }
        }
        //var_dump($goods);
        // die();
        foreach ($goods as $g) {
            if (empty($g)) {
                continue;
            }

            $goodsid = intval($g['goodsid']);
            $optionid = intval($g['optionid']);
            $goodstotal = intval($g['total']);
            if ($goodstotal < 1) {
                $goodstotal = 1;
            }

            if (empty($goodsid)) {
                show_json(0, '参数错误');
            }//wsq
            $sql = 'SELECT id as goodsid,title,type, weight,total,issendfree,disgoods_id,depotid,isnodiscount, thumb,marketprice,cash,isverify,vat_rate,consumption_tax,verifytype,'
                . ' goodssn,productsn,sales,istime,timestart,timeend,hasoption,'
                . ' usermaxbuy,minbuy,maxbuy,unit,buylevels,buygroups,deleted,'
                . ' status,deduct,manydeduct,`virtual`,discounts,deduct2,ednum,edmoney,edareas,diyformtype,diyformid,diymode,'
                . ' dispatchtype,dispatchid,dispatchprice,merchid,merchsale,cates,'
                . ' isdiscount,isdiscount_time,isdiscount_discounts, virtualsend,'
                . ' buyagain,buyagain_islong,buyagain_condition, buyagain_sale'
                . ' FROM ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid  limit 1';
            $data = pdo_fetch($sql, array(':uniacid' => $uniacid, ':id' => $goodsid));
            $data['seckillinfo'] = plugin_run('seckill::getSeckill', $goodsid, $optionid, true, $_W['openid']);
           
           

            
            //抽奖商品的购买
            
            $depotid=$data['depotid'];
            $ordertype=Dispage::get_disType($data['disgoods_id'],$_W['uniacid']);//wsq
            if($ordertype){
                $isdisorder=1;
            }
            // if ($data['status'] == 2) {
            //     $data['marketprice'] = 0;
            // }

            if (empty($data['status']) || !empty($data['deleted'])) {
                show_json(0, $data['title'] . '<br/> 已下架!');
            }

            if (!empty($data['hasoption'])) {
                $opdata = m('goods')->getOption($data['goodsid'], $optionid);
                if (empty($opdata) || empty($optionid)) {
                    show_json(0, '商品' . $data['title'] . '的规格不存在,请到购物车删除该商品重新选择规格!');
                }
            }

            //任务活动购买商品
            $rank = intval($_SESSION[$goodsid . '_rank']);
            $join_id = intval($_SESSION[$goodsid . '_join_id']);
            if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {



                //秒杀不管任务
                $data['is_task_goods'] = 0;
                $tgoods = false;
            } else {

                $task_goods_data = m('goods')->getTaskGoods($openid, $goodsid, $rank, $join_id, $optionid);
                if (empty($task_goods_data['is_task_goods'])) {
                    $data['is_task_goods'] = 0;
                } else {
                    $allow_sale = false;
                    $tgoods['title'] = $data['title'];
                    $tgoods['openid'] = $openid;
                    $tgoods['goodsid'] = $goodsid;
                    $tgoods['optionid'] = $optionid;
                    $tgoods['total'] = $goodstotal;

                    $data['is_task_goods'] = $task_goods_data['is_task_goods'];
                    $data['is_task_goods_option'] = $task_goods_data['is_task_goods_option'];
                    $data['task_goods'] = $task_goods_data['task_goods'];
                }

            }


            $merchid = $data['merchid'];
            $merch_array[$merchid]['goods'][] = $data['goodsid'];

            if ($merchid > 0) {
                $ismerch = 1;
            }

            $virtualid = $data['virtual'];
            $data['stock'] = $data['total'];
            $data['total'] = $goodstotal;
            if ($data['cash'] != 2) {
                $cash = 0;
            }
            //套餐配送方式
            if (!empty($packageid)) {
                $cash = $package['cash'];
            }

            $unit = empty($data['unit']) ? '件' : $data['unit'];

            //一次购买量，总购买量，限时购，会员级别，会员组判断
            //最低购买
            if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {

                $check_buy = plugin_run('seckill::checkBuy',  $data['seckillinfo'] , $data['title'] ,$data['unit']);
                if(is_error($check_buy)){
                    show_json(-1 ,  $check_buy['message']);
                }

            } else {

                if ($data['minbuy'] > 0) {
                    if ($goodstotal < $data['minbuy']) {
                        show_json(0, $data['title'] . '<br/> ' . $data['minbuy'] . $unit . "起售!");
                    }
                }
                if ($data['maxbuy'] > 0) {
                    if ($goodstotal > $data['maxbuy']) {
                        show_json(0, $data['title'] . '<br/> 一次限购 ' . $data['maxbuy'] . $unit . "!");
                    }
                }

                if ($data['usermaxbuy'] > 0) {
                    $order_goodscount = pdo_fetchcolumn('select ifnull(sum(og.total),0)  from ' . tablename('ewei_shop_order_goods') . ' og '
                        . ' left join ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
                        . ' where og.goodsid=:goodsid and  o.status>=0 and o.openid=:openid  and og.uniacid=:uniacid ', array(':goodsid' => $data['goodsid'], ':uniacid' => $uniacid, ':openid' => $openid));
                    if ($order_goodscount >= $data['usermaxbuy']) {
                        show_json(0, $data['title'] . '<br/> 最多限购 ' . $data['usermaxbuy'] . $unit . "!");
                    }
                }
                if (!empty($data['is_task_goods'])) {
                    if ($goodstotal > $data['task_goods']['total']) {
                        show_json(0, $data['title'] . '<br/> 任务活动优惠限购 ' . $data['task_goods']['total'] . $unit . "!");
                    }
                }
                //判断限时购
                if ($data['istime'] == 1) {
                    if (time() < $data['timestart']) {
                        show_json(0, $data['title'] . '<br/> 限购时间未到!');
                    }
                    if (time() > $data['timeend']) {
                        show_json(0, $data['title'] . '<br/> 限购时间已过!');
                    }
                }

                $levelid = intval($member['level']);
                $groupid = intval($member['groupid']);

                //判断会员权限
                if ($data['buylevels'] != '') {
                    $buylevels = explode(',', $data['buylevels']);
                    if (!in_array($levelid, $buylevels)) {
                        show_json(0, '您的会员等级无法购买<br/>' . $data['title'] . '!');
                    }
                }
                //会员组权限
                if ($data['buygroups'] != '') {
                    $buygroups = explode(',', $data['buygroups']);
                    if (!in_array($groupid, $buygroups)) {
                        show_json(0, '您所在会员组无法购买<br/>' . $data['title'] . '!');
                    }
                }

            }


            if (!empty($optionid)) {
                $option = pdo_fetch('select id,title,marketprice,goodssn,productsn,stock,`virtual`,weight from ' . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid  limit 1', array(':uniacid' => $uniacid, ':goodsid' => $goodsid, ':id' => $optionid));

                if (!empty($option)) {
                    if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {
                    } else {

                        if ($option['stock'] != -1) {
                            if (empty($option['stock'])) {
                                show_json(-1, $data['title'] . "<br/>" . $option['title'] . " 库存不足!stock=".$option['stock']);
                            }
                        }

                    }

                    $data['optionid'] = $optionid;
                    $data['optiontitle'] = $option['title'];
                    $data['marketprice'] = $option['marketprice'];
                    //套餐规格
                    $packageoption = array();
                    if ($packageid) {
                        $packageoption = pdo_fetch("select packageprice from " . tablename('ewei_shop_package_goods_option') . "
                                where uniacid = " . $uniacid . " and goodsid = " . $goodsid . " and optionid = " . $optionid . " and pid = " . $packageid . " ");
                        $data['marketprice'] = $packageoption['packageprice'];
                        $packageprice += $packageoption['packageprice'];
                    }

                    $virtualid = $option['virtual'];

                    if (!empty($option['goodssn'])) {
                        $data['goodssn'] = $option['goodssn'];
                    }
                    if (!empty($option['productsn'])) {
                        $data['productsn'] = $option['productsn'];
                    }
                    if (!empty($option['weight'])) {
                        $data['weight'] = $option['weight'];
                    }
                }
            } else {
                //套餐无规格
                if ($packageid) {
                    $pg = pdo_fetch("select packageprice from " . tablename('ewei_shop_package_goods') . "
                                where uniacid = " . $uniacid . " and goodsid = " . $goodsid . " and pid = " . $packageid . " ");
                    $data['marketprice'] = $pg['packageprice'];
                    $packageprice += $pg['packageprice'];
                }
                if ($data['stock'] != -1) {
                    if (empty($data['stock'])) {
                        show_json(0, $data['title'] . "<br/>库存不足!");
                    }
                }
            }

            $data['diyformdataid'] = 0;
            $data['diyformdata'] = iserializer(array());
            $data['diyformfields'] = iserializer(array());
            if (intval($_GPC['fromcart']) == 1) {

                if ($diyform_plugin) {
                    $cartdata = pdo_fetch('select id,diyformdataid,diyformfields,diyformdata from ' . tablename('ewei_shop_member_cart') . " "
                        . " where goodsid=:goodsid and optionid=:optionid and openid=:openid and deleted=0 order by id desc limit 1"
                        , array(':goodsid' => $data['goodsid'], ':optionid' => intval($data['optionid']), ':openid' => $openid));
                    if (!empty($cartdata)) {
                        $data['diyformdataid'] = $cartdata['diyformdataid'];
                        $data['diyformdata'] = $cartdata['diyformdata'];
                        $data['diyformfields'] = $cartdata['diyformfields'];
                    }
                }
            } else {
                if (!empty($data['diyformtype']) && $diyform_plugin) {

                    $temp_data = $diyform_plugin->getOneDiyformTemp($_GPC['gdid'], 0);

                    $data['diyformfields'] = $temp_data['diyformfields'];
                    $data['diyformdata'] = $temp_data['diyformdata'];

                    if ($data['diyformtype'] == 2) {
                        $data['diyformid'] = 0;
                    } else {
                        $data['diyformid'] = $data['diyformid'];

                    }
                }
            }
            if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {


                //秒杀价格
                $data['ggprice'] = $gprice = $data['seckillinfo']['price'] * $goodstotal;
                $seckill_payprice += $gprice;
                $seckill_price+=  $data['marketprice'] * $goodstotal - $gprice;
                $goodsprice += $data['marketprice'] * $goodstotal ;

                $data['taskdiscountprice'] = 0;
                $data['discountprice'] = 0;
                $data['discountprice'] = 0;
                $data['discounttype'] = 0;
                $data['isdiscountunitprice'] = 0;
                $data['discountunitprice'] = 0;
                $data['price0'] = 0;
                $data['price1'] = 0;
                $data['price2'] = 0;
                $data['buyagainprice'] = 0;

                //秒杀不管折扣

            } else {
                $gprice = $data['marketprice'] * $goodstotal;
                $goodsprice += $gprice;

                //成交价格
                $prices = m('order')->getGoodsDiscountPrice($data, $level);
                $data['ggprice'] = $prices['price'];
                $data['taskdiscountprice'] = $prices['taskdiscountprice'];
                $data['discountprice'] = $prices['discountprice'];
                $data['discountprice'] = $prices['discountprice'];
                $data['discounttype'] = $prices['discounttype'];;
                $data['isdiscountunitprice'] = $prices['isdiscountunitprice'];
                $data['discountunitprice'] = $prices['discountunitprice'];
                $data['price0'] = $prices['price0'];
                $data['price1'] = $prices['price1'];
                $data['price2'] = $prices['price2'];
                $data['buyagainprice'] = $prices['buyagainprice'];

                $buyagainprice += $prices['buyagainprice'];
                $taskdiscountprice += $prices['taskdiscountprice'];


                if ($prices['discounttype'] == 1) {
                    $isdiscountprice += $prices['isdiscountprice'];
                    $discountprice += $prices['discountprice'];

                    if (!empty($data['merchsale'])) {
                        $merchisdiscountprice += $prices['isdiscountprice'];
                        $discountprice_array[$merchid]['merchisdiscountprice'] += $prices['isdiscountprice'];
                    }

                    $discountprice_array[$merchid]['isdiscountprice'] += $prices['isdiscountprice'];
                } else if ($prices['discounttype'] == 2) {
                    $discountprice += $prices['discountprice'];
                    $discountprice_array[$merchid]['discountprice'] += $prices['discountprice'];

                }

                $discountprice_array[$merchid]['ggprice'] += $prices['ggprice'];

            }
            if($log_id>0){
                $lotterygoods=m("lottery")->show_goods($_W['openid'],$goodsid,$log_id);
                if(!empty($lotterygoods)){
                    
                    $data['ggprice']=$lotterygoods['marketprice'];
                    $saleset = false;
                }
            }
           
            
            $merch_array[$merchid]['ggprice'] += $data['ggprice'];
            $totalprice += $data['ggprice'];


            if ($data['isverify'] == 2) {
                $isverify = true;
                $verifytype = $data['verifytype'];
            }
            if (!empty($data['virtual']) || $data['type'] == 2 || $data['type'] == 3  || $data['type'] == 20) {
                $isvirtual = true;

                if ($data['type'] == 20 && p('ccard')) {
                    $ccard = 1;
                }

                if ($data['virtualsend']) {
                    $isvirtualsend = true;
                }
            }

            if ($data['seckillinfo'] && $data['seckillinfo']['status'] == 0) {

                //秒杀不管二次，抵扣
            } else {


                if (floatval($data['buyagain']) > 0 && empty($data['buyagain_sale'])) {
                    //第一次后买东西享受优惠
                    if (m('goods')->canBuyAgain($data)) {
                        $data['deduct'] = 0;
                        $saleset = false;
                    }
                }




               

                    //积分抵扣
                    if ($data['manydeduct']) {
                        $deductprice += $data['deduct'] * $data['total'];
                    } else {
                        $deductprice += $data['deduct'];
                    }

                    //余额抵扣限额
                    if ($data['deduct2'] == 0) {
                        //全额抵扣
                        $deductprice2 += $data['ggprice'];
                    } else if ($data['deduct2'] > 0) {

                        //最多抵扣
                        if ($data['deduct2'] > $data['ggprice']) {
                            $deductprice2 += $data['ggprice'];
                        } else {
                            $deductprice2 += $data['deduct2'];
                        }
                    }
                


            }

            $virtualsales += $data['sales'];

            $allgoods[] = $data;
        }
        $grprice = $totalprice;


        if (count($goods) > 1 && !empty($tgoods)) {
            show_json(0, '任务活动优惠商品' . $tgoods['title'] . '不能放入购物车下单,请单独购买');
        }

        if (empty($allgoods)) {
            show_json(0, '未找到任何商品');
        }
        $couponid = intval($_GPC['couponid']);
        //参数检查wsq
        $ifidentity=Dispage::check_readname($depotid);//wsq
        if($member['showifidentity']==1){
            $ifidentity=true;
        }
        if($ifidentity){
            $realname=$_GPC['realname'];
            $imid=$_GPC['imid'];
            if(empty($realname) || empty($imid)){
                show_json(0, '姓名和身份证必须填写');
            }
        }
        if ($is_openmerch == 1) {
            //读取多商户营销设置
            foreach ($merch_array as $key => $value) {
                if ($key > 0) {
                    $merch_array[$key]['set'] = $merch_plugin->getSet('sale', $key);
                    $merch_array[$key]['enoughs'] = $merch_plugin->getEnoughs($merch_array[$key]['set']);
                }
            }
            if ($allow_sale) {
                //多商户满额减
                $merch_enough = m('order')->getMerchEnough($merch_array);
                $merch_array = $merch_enough['merch_array'];
                $merch_enough_total = $merch_enough['merch_enough_total'];
                $merch_saleset = $merch_enough['merch_saleset'];

                if ($merch_enough_total > 0) {
                    $totalprice -= $merch_enough_total;
                }
            }
        }
        
        //满额减
        $deductenough = 0;
        if ($saleset) {
            foreach ($saleset['enoughs'] as $e) {
                if ($totalprice - $seckill_payprice >= floatval($e['enough']) && floatval($e['money']) > 0) {
                    $deductenough = floatval($e['money']);
                    if ($deductenough > $totalprice - $seckill_payprice) {
                        $deductenough = $totalprice - $seckill_payprice;
                    }
                    break;
                }
            }
        }

        $goodsdata_coupon = array();
        $goodsdata_coupon_temp = array();

        foreach ($allgoods as $g) {
            if ($g['seckillinfo'] && $g['seckillinfo']['status'] == 0) {
                //秒杀商品不使用优惠券
                $goodsdata_coupon_temp[] = $g;
            } else {

                if (floatval($g['buyagain']) > 0) {
                    //第一次后买东西享受优惠
                    if (!m('goods')->canBuyAgain($g) || !empty($g['buyagain_sale'])) {
                        $goodsdata_coupon[] = $g;
                    } else {
                        $goodsdata_coupon_temp[] = $g;
                    }
                } else {
                    $goodsdata_coupon[] = $g;
                }
            }
        }


        $return_array = $this->caculatecoupon($couponid, $goodsdata_coupon, $totalprice, $discountprice, $isdiscountprice, 1, $discountprice_array, $merchisdiscountprice);



        $couponprice = 0;
        $coupongoodprice = 0;
        if (!empty($return_array)) {
            $isdiscountprice = $return_array['isdiscountprice'];
            $discountprice = $return_array['discountprice'];
            $couponprice = $return_array['deductprice'];
            $totalprice = $return_array['totalprice'] ;
            $discountprice_array = $return_array['discountprice_array'];
            $merchisdiscountprice = $return_array['merchisdiscountprice'];
            $coupongoodprice = $return_array['coupongoodprice'];
            $couponmerchid = $return_array['couponmerchid'];
            $allgoods = $return_array['$goodsarr'];
            $allgoods = array_merge($allgoods, $goodsdata_coupon_temp);
        }


        //$isvirtual 实体物品计算运费
        //$isverify  非核销计算运费
        //$dispatchtype 选择了快递(非自提)计算运费
        if (!$isvirtual && !$isverify && $dispatchtype == 0) {
            if (empty($addressid)) {
                show_json(0, '请选择地址');
            }

            $dispatch_array = m('order')->getOrderDispatchPrice($allgoods, $member, $address, $saleset, $merch_array, 2);

            $dispatch_price = $dispatch_array['dispatch_price'] -  $dispatch_array['seckill_dispatch_price'];

            $seckill_dispatchprice = $dispatch_array['seckill_dispatch_price'];
            $nodispatch_array = $dispatch_array['nodispatch_array'];

            if (!empty($nodispatch_array['isnodispatch'])) {
                show_json(0, $nodispatch_array['nodispatch']);
            }
        }


        //满额减
        $totalprice -= $deductenough;
        //运费
        $totalprice += $dispatch_price + $seckill_dispatchprice;
        //余额最多抵扣+运费
        if ($saleset && empty($saleset['dispatchnodeduct'])) {
            $deductprice2 += $dispatch_price ;
        }

        if (empty($goods[0]['bargain_id'])) {
            //积分抵扣
            $deductcredit = 0; //抵扣需要扣除的积分
            $deductmoney = 0; //抵扣的钱
            $deductcredit2 = 0; //可抵扣的余额

            if ($sale_plugin) {
                //积分抵扣
                if (!empty($_GPC['deduct'])) {
                    //会员积分
                    $credit = $member['credit1'];
                    if ($credit > 0) {
                        $credit = floor($credit);
                    }
                    if (!empty($saleset['creditdeduct'])) {
                        $pcredit = intval($saleset['credit']); //积分比例
                        $pmoney = round(floatval($saleset['money']), 2); //抵扣比例

                        if ($pcredit > 0 && $pmoney > 0) {
                            if ($credit % $pcredit == 0) {
                                $deductmoney = round(intval($credit / $pcredit) * $pmoney, 2);
                            } else {
                                $deductmoney = round((intval($credit / $pcredit) + 1) * $pmoney, 2);
                            }
                        }
                        if ($deductmoney > $deductprice ) {
                            $deductmoney = $deductprice;
                        }
                        if ($deductmoney > $totalprice - $seckill_payprice) { //减掉秒杀的钱再抵扣
                            $deductmoney = $totalprice - $seckill_payprice;
                        }
                        $deductcredit = floor($deductmoney / $pmoney * $pcredit);
                    }
                }
                $totalprice -= $deductmoney;
            }

            //余额抵扣
            if (!empty($saleset['moneydeduct'])) {
                 if (!empty($_GPC['deduct2'])) {
                     $deductcredit2 = $member['credit2'];
                     if ($deductcredit2 > $totalprice - $seckill_payprice) {  //减掉秒杀的钱再抵扣
                         $deductcredit2 = $totalprice - $seckill_payprice;
                     }
                     if ($deductcredit2 > $deductprice2) {
                         $deductcredit2 = $deductprice2;
                     }
                 }
                 $totalprice -= $deductcredit2;
             }

        }


        //生成核销消费码
        $verifyinfo = array();
        $verifycode = "";
        $verifycodes = array();
        if ($isverify || $dispatchtype) {

            if ($isverify) {
                if ($verifytype == 0 || $verifytype == 1) {
                    //一次核销+ 按次核销（一个码 )
                    $verifycode = random(8, true);
                    while (1) {
                        $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where verifycode=:verifycode and uniacid=:uniacid limit 1', array(':verifycode' => $verifycode, ':uniacid' => $_W['uniacid']));
                        if ($count <= 0) {
                            break;
                        }
                        $verifycode = random(8, true);
                    }
                } else if ($verifytype == 2) {
                    //按码核销
                    $totaltimes = intval($allgoods[0]['total']);
                    if ($totaltimes <= 0) {
                        $totaltimes = 1;
                    }
                    for ($i = 1; $i <= $totaltimes; $i++) {

                        $verifycode = random(8, true);
                        while (1) {
                            $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where concat(verifycodes,\'|\' + verifycode +\'|\' ) like :verifycodes and uniacid=:uniacid limit 1', array(':verifycodes' => "%{$verifycode}%", ':uniacid' => $_W['uniacid']));
                            if ($count <= 0) {
                                break;
                            }
                            $verifycode = random(8, true);
                        }
                        $verifycodes[] = "|" . $verifycode . "|";
                        $verifyinfo[] = array(
                            'verifycode' => $verifycode,
                            'verifyopenid' => '',
                            'verifytime' => 0,
                            'verifystoreid' => 0
                        );
                    }
                }
            } else if ($dispatchtype) {
                //自提码
                $verifycode = random(8, true);
                while (1) {
                    $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where verifycode=:verifycode and uniacid=:uniacid limit 1', array(':verifycode' => $verifycode, ':uniacid' => $_W['uniacid']));
                    if ($count <= 0) {
                        break;
                    }
                    $verifycode = random(8, true);
                }
            }
        }
        $carrier = $_GPC['carriers'];
        $carriers = is_array($carrier) ? iserializer($carrier) : iserializer(array());

        if ($totalprice <= 0) {
            $totalprice = 0;
        }
       

        if ($ismerch == 0 || ($ismerch == 1 && count($merch_array) == 1)) {
            //需要创建一个订单
            $multiple_order = 0;
        } else {
            //需要创建多个订单
            $multiple_order = 1;
        }
        $disaccount= Dispage::getDisInfo($uniacid);
        if(!empty($disaccount)){
            $distcode=$disaccount['distcode'];
        }else{
            $distcode="SH";
        }
        //生成订单号
        if ($ismerch > 0) {

            $ordersn = m('common')->createNO('order', 'ordersn', $distcode.'ME');
        } else {
            $ordersn = m('common')->createNO('order', 'ordersn', $distcode);
        }
        if (!empty($goods[0]['bargain_id']) && p('bargain')) {//???
            $bargain_act = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_bargain_actor') . " WHERE id = :id AND openid = :openid ", array(':id' => $goods[0]['bargain_id'], ':openid' => $_W['openid']));
            if (empty($bargain_act)) {
                die('没有这个商品');
            }
            $totalprice = $bargain_act['now_price'] + $dispatch_price;
            $goodsprice = $bargain_act['now_price'];
            if (!pdo_update('ewei_shop_bargain_actor', array('status' => 1), array('id' => $goods[0]['bargain_id'], 'openid' => $_W['openid']))) {
                die('下单失败');
            }
            $ordersn = substr_replace($ordersn, $distcode.'KJ', 0, 2);//砍价订单号
        }
        //套餐订单价格
        $is_package = 0;
        if (!empty($packageid)) {
            $goodsprice = $packageprice;
            $dispatch_price = $package['freight'];
            $totalprice = $packageprice + $package['freight'];
            $is_package = 1;
        }

        if(count($allgoods)==1){

            $lotterygoodsall=m("lottery")->show_goods($_W['openid'],$goodsid,$log_id);
            if(!empty($lotterygoodsall)){
                pdo_update("ewei_shop_lottery_log",array("is_reward"=>2),array('id'=>$log_id));
            }
        }
        
        //订单数据
        $order = array();
        $order['ismerch'] = $ismerch;
        $order['parentid'] = 0;
        $order['isdisorder']=$isdisorder;
        $order['uniacid'] = $uniacid;
        $order['openid'] = $openid;
        $order['ordersn'] = $ordersn;
        $order['price'] = $totalprice;
        $order['oldprice'] = $totalprice;
        $order['grprice'] = $grprice;
        $order['depotid']=$depotid;//wsq
        $order['taskdiscountprice'] = $taskdiscountprice;
        $order['discountprice'] = $discountprice;
        $order['isdiscountprice'] = $isdiscountprice;
        $order['merchisdiscountprice'] = $merchisdiscountprice;
        $order['cash'] = $cash;
        $order['status'] = 0;
        $order['remark'] = trim($_GPC['remark']);
        $order['addressid'] = empty($dispatchtype) ? $addressid : 0;
        $order['goodsprice'] = $goodsprice;
        $order['dispatchprice'] = $dispatch_price + $seckill_dispatchprice;
        $order['dispatchtype'] = $dispatchtype;
        $order['dispatchid'] = $dispatchid;
        $order['storeid'] = $carrierid;
        $order['carrier'] = $carriers;
        $order['createtime'] = time();
        $order['olddispatchprice'] = $dispatch_price+ $seckill_dispatchprice;
        $order['couponid'] = $couponid;
        $order['couponmerchid'] = $couponmerchid;
        $order['paytype'] = 0; //如果是上门取货，支付方式为3
        $order['deductprice'] = $deductmoney;
        $order['deductcredit'] = $deductcredit;
        $order['deductcredit2'] = $deductcredit2;
        $order['deductenough'] = $deductenough;
        $order['merchdeductenough'] = $merch_enough_total;
        $order['couponprice'] = $couponprice;
        $order['merchshow'] = 0;
        $order['buyagainprice'] = $buyagainprice;
        $order['ispackage'] = $is_package;
        $order['packageid'] = $packageid;
        $order['imid']=$imid;
        $order['realname']=$realname;
        $order['seckilldiscountprice'] = $seckill_price;

        if (!empty($ccard)) {
            $order['ccard'] = 1;
        }
        if($member['showifidentity']==1){
            $order['if_customs_z']=1;
        }
        $deductenough=$order['deductenough'];//满额立减优惠
        $couponprice=$order['couponprice'];//优惠券优惠
        $buyagainprice=$order['buyagainprice'];//重复购买优惠
        $discountprice=$oder['discountprice'];//会员优惠
        //$isdiscountprice=$order['isdiscountprice'];//促销优惠
        $deductprice=$order['deductprice'];//抵扣
        $seckilldiscountprice=$oder['seckilldiscountprice'];//秒杀优惠
        $alldeduct=$deductenough+$couponprice+$buyagainprice+$discountprice+$isdiscountprice+$deductprice+$seckilldiscountprice;
        //var_dump($allgoods);
        //die();
        $returndata=m("order")->get_tax($allgoods,$dispatch_price,$goodsprice,$alldeduct);//正常算税
        $allgoods=$returndata['order_goods'];
        $order['dpostfee']=$returndata['depostfee'];
        $order['tax_rate']=$returndata['tax_rate'];
        $order['tax_consumption']=$returndata['tax_consumption'];
        
        $disdata=m("order")->get_dis_tax($allgoods,$address);
        if(!empty($disdata)){
            $ordertax=$order['tax_rate']+$order['tax_consumption'];
            $diff_fee=$ordertax-$disdata['alltax'];
            $order['disorderamount']=$disdata['disprice'];
            if($diff_fee>0){
                $order['disorderamount']=$disdata['disprice']+$diff_fee;
                $order['dff_fee']=$diff_fee;
            }
            $order['dis_shipping_fee']=$disdata['dis_shoping_fee'];
        }
       
        //创始人字段
        $author = p('author');
        if ($author) {
            $author_set = $author->getSet();
            if (!empty($member['agentid']) && !empty($member['authorid'])) {
                $order['authorid'] = $member['authorid'];
            }
            if (!empty($author_set['selfbuy']) && !empty($member['isauthor']) && !empty($member['authorstatus'])) {
                $order['authorid'] = $member['id'];
            }
        }

        if ($multiple_order == 0) {
            //创建一个订单的字段
            $order_merchid = current(array_keys($merch_array));
            $order['merchid'] = intval($order_merchid);
            $order['isparent'] = 0;
            $order['transid'] = '';
            $order['isverify'] = $isverify ? 1 : 0;
            $order['verifytype'] = $verifytype;
            $order['verifycode'] = $verifycode;
            $order['verifycodes'] = implode('', $verifycodes);
            $order['verifyinfo'] = iserializer($verifyinfo);
            $order['virtual'] = $virtualid;
            $order['isvirtual'] = $isvirtual ? 1 : 0;
            $order['isvirtualsend'] = $isvirtualsend ? 1 : 0;

            $order['invoicename'] = trim($_GPC['invoicename']);
        } else {
            //创建多个订单的字段
            $order['isparent'] = 1;
            $order['merchid'] = 0;
        }

        if ($diyform_plugin) {
            if (is_array($_GPC['diydata']) && !empty($order_formInfo)) {

                $diyform_data = $diyform_plugin->getInsertData($fields, $_GPC['diydata']);
                $idata = $diyform_data['data'];
                $order['diyformfields'] = iserializer($fields);
                $order['diyformdata'] = $idata;
                $order['diyformid'] = $order_formInfo['id'];
            }
        }

        if (!empty($address)) {
            $order['address'] = iserializer($address);
        }
        pdo_insert('ewei_shop_order', $order);
        $orderid = pdo_insertid();


        if (!empty($goods[0]['bargain_id']) && p('bargain')) {
            pdo_update('ewei_shop_bargain_actor', array('order' => $orderid), array('id' => $goods[0]['bargain_id'], 'openid' => $_W['openid']));
        }
        if ($multiple_order == 0) {
            //开始创建一个订单

            //保存订单商品
            foreach ($allgoods as $goods) {
                $order_goods = array();
                if (!empty($bargain_act) && p('bargain')) {
                    $goods['total'] = 1;
                }

                $order_goods['merchid'] = $goods['merchid'];
                $order_goods['merchsale'] = $goods['merchsale'];
                $order_goods['uniacid'] = $uniacid;
                $order_goods['orderid'] = $orderid;
                $order_goods['goodsid'] = $goods['goodsid'];
                $order_goods['price'] = $goods['marketprice'] * $goods['total'];
                $order_goods['total'] = $goods['total'];
                $order_goods['optionid'] = $goods['optionid'];
                $disprice=Dispage::get_disprice($goods['goodsid'],$_W['uniacid']);
                $order_goods['disprice']=$disprice==0?$goods['ggprice']:$disprice;
                $order_goods['createtime'] = time();
                $order_goods['optionname'] = $goods['optiontitle'];
                $order_goods['goodssn'] = $goods['goodssn'];
                $order_goods['productsn'] = $goods['productsn'];
                $order_goods['realprice'] = $goods['ggprice'];
                $order_goods['oldprice'] = $goods['ggprice'];
                //wsq
                $order_goods['dprice']=$goods['dprice'];
                $order_goods['pricetaxrate']=$goods['tax']['rate']*$goods['total'];
                $order_goods['taxconsumption']=$goods['tax']['consumption_tax']*$goods['total'];
                $order_goods['shipping_fee']=$goods['shipping_fee'];
                //endwsq
                if ($goods['discounttype'] == 1) {
                    $order_goods['isdiscountprice'] = $goods['isdiscountprice'];
                } else {
                    $order_goods['isdiscountprice'] = 0;
                }
                $order_goods['openid'] = $openid;

                if ($diyform_plugin) {
                    if ($goods['diyformtype'] == 2) {
                        //商品使用了独立自定义的表单
                        $order_goods['diyformid'] = 0;
                    } else {
                        //商品使用了表单模板
                        $order_goods['diyformid'] = $goods['diyformid'];
                    }
                    $order_goods['diyformdata'] = $goods['diyformdata'];
                    $order_goods['diyformfields'] = $goods['diyformfields'];
                }
                if (floatval($goods['buyagain']) > 0) {
                    //数据库是否有购买过的商品没用掉的
                    if (!m('goods')->canBuyAgain($goods)) {
                        $order_goods['canbuyagain'] = 1;
                    }
                }
                if($goods['seckillinfo'] && $goods['seckillinfo']['status']==0){
                    $order_goods['seckill'] = 1;
                    $order_goods['seckill_taskid'] =$goods['seckillinfo']['taskid'] ;
                    $order_goods['seckill_roomid'] =$goods['seckillinfo']['roomid'] ;
                    $order_goods['seckill_timeid'] =$goods['seckillinfo']['timeid'] ;
                }
                pdo_insert('ewei_shop_order_goods', $order_goods);

                if($goods['seckillinfo'] && $goods['seckillinfo']['status']==0){

                    plugin_run("seckill::setSeckill" , $goods['seckillinfo'] , $goods , $_W['openid'],  $orderid , 0 , $order['createtime']);

                }


            }


        } else {
            //开始创建多个子订单

            //记录订单商品中的订单id
            $og_array = array();

            $ch_order_data = m('order')->getChildOrderPrice($order, $allgoods, $dispatch_array, $merch_array, $sale_plugin, $discountprice_array);


            foreach ($merch_array as $key => $value) {

                //生成子订单号
                $order['ordersn'] = m('common')->createNO('order', 'ordersn', 'ME');

                $merchid = $key;
                $order['merchid'] = $merchid;
                $order['parentid'] = $orderid;
                $order['isparent'] = 0;
                $order['merchshow'] = 1;

                $order['dispatchprice'] = $dispatch_array['dispatch_merch'][$merchid];
                $order['olddispatchprice'] = $dispatch_array['dispatch_merch'][$merchid];

                $order['merchisdiscountprice'] = $discountprice_array[$merchid]['merchisdiscountprice'];
                $order['isdiscountprice'] = $discountprice_array[$merchid]['isdiscountprice'];
                $order['discountprice'] = $discountprice_array[$merchid]['discountprice'];

                $order['price'] = $ch_order_data[$merchid]['price'];
                $order['grprice'] = $ch_order_data[$merchid]['grprice'];
                $order['goodsprice'] = $ch_order_data[$merchid]['goodsprice'];

                $order['deductprice'] = $ch_order_data[$merchid]['deductprice'];
                $order['deductcredit'] = $ch_order_data[$merchid]['deductcredit'];
                $order['deductcredit2'] = $ch_order_data[$merchid]['deductcredit2'];

                $order['merchdeductenough'] = $ch_order_data[$merchid]['merchdeductenough'];
                $order['deductenough'] = $ch_order_data[$merchid]['deductenough'];


                //多商户参与优惠券计算的商品价格(参与活动之后的价格)
                $order['coupongoodprice'] = $discountprice_array[$merchid]['coupongoodprice'];

                $order['couponprice'] = $discountprice_array[$merchid]['deduct'];

                if (empty($order['couponprice'])) {
                    $order['couponid'] = 0;
                    $order['couponmerchid'] = 0;
                } else if ($couponmerchid > 0) {
                    if ($merchid == $couponmerchid) {
                        $order['couponid'] = $couponid;
                        $order['couponmerchid'] = $couponmerchid;
                    } else {
                        $order['couponid'] = 0;
                        $order['couponmerchid'] = 0;
                    }
                }

                pdo_insert('ewei_shop_order', $order);

                //子订单id
                $ch_orderid = pdo_insertid();

                $merch_array[$merchid]['orderid'] = $ch_orderid;

                if ($couponmerchid > 0) {
                    if ($merchid == $couponmerchid) {
                        $couponorderid = $ch_orderid;
                    }
                }
                foreach ($value['goods'] as $k => $v) {
                    //$v 商品id
                    $og_array[$v] = $ch_orderid;
                }
            }

            //子订单保存订单商品
            foreach ($allgoods as $goods) {

                $goodsid = $goods['goodsid'];

                $order_goods = array();
                $order_goods['parentorderid'] = $orderid;
                $order_goods['merchid'] = $goods['merchid'];
                $order_goods['merchsale'] = $goods['merchsale'];
                $order_goods['orderid'] = $og_array[$goodsid];
                $disprice=Dispage::get_disprice($goods['goodsid'],$_W['uniacid']);
                $order_goods['disprice']=$disprice==0?$goods['ggprice']:$disprice;
                $order_goods['uniacid'] = $uniacid;
                $order_goods['goodsid'] = $goodsid;
                $order_goods['price'] = $goods['marketprice'] * $goods['total'];
                $order_goods['total'] = $goods['total'];
                $order_goods['optionid'] = $goods['optionid'];
                $order_goods['createtime'] = time();
                $order_goods['optionname'] = $goods['optiontitle'];
                $order_goods['goodssn'] = $goods['goodssn'];
                $order_goods['productsn'] = $goods['productsn'];
                $order_goods['realprice'] = $goods['ggprice'];
                $order_goods['oldprice'] = $goods['ggprice'];
                $order_goods['isdiscountprice'] = $goods['isdiscountprice'];
                $order_goods['openid'] = $openid;

                if ($diyform_plugin) {
                    if ($goods['diyformtype'] == 2) {
                        //商品使用了独立自定义的表单
                        $order_goods['diyformid'] = 0;
                    } else {
                        //商品使用了表单模板
                        $order_goods['diyformid'] = $goods['diyformid'];
                    }
                    $order_goods['diyformdata'] = $goods['diyformdata'];
                    $order_goods['diyformfields'] = $goods['diyformfields'];
                }
                if (floatval($goods['buyagain']) > 0) {
                    //数据库是否有购买过的商品没用掉的
                    if (!m('goods')->canBuyAgain($goods)) {
                        $order_goods['canbuyagain'] = 1;
                    }
                }
                pdo_insert('ewei_shop_order_goods', $order_goods);

            }
        }

        //创建优惠券发送任务 数据$orderid
        if (com('coupon') && !empty($orderid)) {
            com('coupon')->addtaskdata($orderid); //订单支付
        }


        //更新会员信息
        if (is_array($carrier)) {
            $up = array('realname' => $carrier['carrier_realname'], 'carrier_mobile' => $carrier['carrier_mobile']);
            pdo_update('ewei_shop_member', $up, array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
            if (!empty($member['uid'])) {
                load()->model('mc');
                mc_update($member['uid'], $up);
            }
        }

        //删除购物车
        if ($_GPC['fromcart'] == 1) {
            //删除购物车
            
            pdo_query('update ' . tablename('ewei_shop_member_cart') . ' set deleted=1 where depotid=:depotid and openid=:openid and uniacid=:uniacid and selected=1 ', array(':uniacid' => $uniacid, ':openid' => $openid,':depotid'=>$depotid));
        }

        if ($deductcredit > 0) {
            //扣除抵扣积分
            m('member')->setCredit($openid, 'credit1', -$deductcredit, array('0', $_W['shopset']['shop']['name'] . "购物积分抵扣 消费积分: {$deductcredit} 抵扣金额: {$deductmoney} 订单号: {$ordersn}"));
        }

        if ($buyagainprice > 0) {
            m('goods')->useBuyAgain($orderid);
        }

        if ($deductcredit2 > 0) {
            //扣除抵扣余额
            m('member')->setCredit($openid, 'credit2', -$deductcredit2, array('0', $_W['shopset']['shop']['name'] . "购物余额抵扣: {$deductcredit2} 订单号: {$ordersn}"));
        }

        if (empty($virtualid)) {
            //卡密的 付款才计算库存
            //设置库存
            m('order')->setStocksAndCredits($orderid, 0);
        } else {
            //虚拟卡密虚拟销量
            if (isset($allgoods[0])) {
                $vgoods = $allgoods[0];
                pdo_update('ewei_shop_goods', array('sales' => $vgoods['sales'] + $vgoods['total']), array('id' => $vgoods['goodsid']));
            }
        }

        //优惠券设置
        $plugincoupon = com('coupon');
        if ($plugincoupon) {
            if ($couponmerchid > 0 && $multiple_order == 1) {
                $oid = $couponorderid;
            } else {
                $oid = $orderid;
            }
            $plugincoupon->useConsumeCoupon($oid);
        }


        //任务活动下单成功
        if (!empty($tgoods)) {
            $rank = intval($_SESSION[$tgoods['goodsid'] . '_rank']);
            $join_id = intval($_SESSION[$tgoods['goodsid'] . '_join_id']);
            m('goods')->getTaskGoods($tgoods['openid'], $tgoods['goodsid'], $rank, $join_id, $tgoods['optionid'], $tgoods['total']);
            $_SESSION[$tgoods['goodsid'] . '_rank'] = 0;
            $_SESSION[$tgoods['goodsid'] . '_join_id'] = 0;
        }

        //模板消息
        m('notice')->sendOrderMessage($orderid);

        //打印机打印
        com_run('printer::sendOrderMessage', $orderid);

        //分销设置
        $pluginc = p('commission');
        if ($pluginc) {
            //分销订单检测
            if ($multiple_order == 0) {
                $pluginc->checkOrderConfirm($orderid);
            } else {
                //处理子订单
                if (!empty($merch_array)) {
                    foreach ($merch_array as $key => $value) {
                        $pluginc->checkOrderConfirm($value['orderid']);
                    }
                }
            }
        }
        //代理订单处理wsq
        //税费处理
        unset($_SESSION[$openid . "_order_create"]);
        show_json(1, array('orderid' => $orderid));
    }


    //单品模板
    protected function singleDiyformData($id = 0)
    {

        global $_W, $_GPC;
        //单品
        $goods_data = false;
        $diyformtype = false;
        $diyformid = 0;
        $diymode = 0;
        $formInfo = false;
        $goods_data_id = 0;
        $diyform_plugin = p('diyform');
        if ($diyform_plugin && !empty($id)) {

            $sql = 'SELECT id as goodsid,type,diyformtype,diyformid,diymode,diyfields FROM ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid  limit 1';
            $goods_data = pdo_fetch($sql, array(':uniacid' => $_W['uniacid'], ':id' => $id));
            if (!empty($goods_data)) {
                $diyformtype = $goods_data['diyformtype'];
                $diyformid = $goods_data['diyformid'];
                $diymode = $goods_data['diymode'];

                if ($goods_data['diyformtype'] == 1) {
                    $formInfo = $diyform_plugin->getDiyformInfo($diyformid);
                } else if ($goods_data['diyformtype'] == 2) {
                    $fields = iunserializer($goods_data['diyfields']);
                    if (!empty($fields)) {
                        $formInfo = array(
                            'fields' => $fields
                        );
                    }
                }
            }
        }
        return array(
            'goods_data' => $goods_data,
            'diyformtype' => $diyformtype,
            'diyformid' => $diyformid,
            'diymode' => $diymode,
            'formInfo' => $formInfo,
            'goods_data_id' => $goods_data_id,
            'diyform_plugin' => $diyform_plugin
        );
    }

    function diyform()
    {
        global $_W, $_GPC;
        $goodsid = intval($_GPC['id']);
        $cartid = intval($_GPC['cartid']);
        $openid = $_W['openid'];
        $data = $this->singleDiyformData($goodsid);
        extract($data);

        if ($diyformtype == 2) {
            $diyformid = 0;
        } else {
            $diyformid = $goods_data['diyformid'];
        }

        $fields = $formInfo['fields'];

        $insert_data = $diyform_plugin->getInsertData($fields, $_GPC['diyformdata']);
        $idata = $insert_data['data'];

        $goods_temp = $diyform_plugin->getGoodsTemp($goodsid, $diyformid, $openid);

        $insert = array(
            'cid' => $goodsid,
            'openid' => $openid,
            'diyformid' => $diyformid,
            'type' => 3,
            'diyformfields' => iserializer($fields),
            'diyformdata' => $idata,
            'uniacid' => $_W['uniacid']
        );

        if (empty($goods_temp)) {
            pdo_insert('ewei_shop_diyform_temp', $insert);
            $gdid = pdo_insertid();
        } else {
            pdo_update('ewei_shop_diyform_temp', $insert, array('id' => $goods_temp['id']));
            $gdid = $goods_temp['id'];
        }

        if (!empty($cartid)) {
            $cart_data = array(
                'diyformid' => $insert['diyformid'],
                'diyformfields' => $insert['diyformfields'],
                'diyformdata' => $insert['diyformdata']
            );
            pdo_update('ewei_shop_member_cart', $cart_data, array('id' => $cartid));
        }
        show_json(1, array('goods_data_id' => $gdid));
    }

}