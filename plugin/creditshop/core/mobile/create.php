<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN .'creditshop/core/page_mobile.php';
class Create_EweiShopV2Page extends CreditshopMobilePage {
	
	function main(){
		global $_W, $_GPC;

        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $id = intval($_GPC['id']);
        $optionid = intval($_GPC['optionid']);
        $shop = m('common')->getSysset('shop');
        $member = m('member')->getMember($openid);
        $goods = $this->model->getGoods($id, $member,$optionid);
        if (empty($goods)) {
            $this->message("商品已下架或被删除!", mobileUrl('creditshop'), 'error');
        }
        $pay = m('common')->getSysset('pay');
        $set = m('common')->getPluginset('creditshop');
        $goods['followed'] = m('user')->followed($openid);

        if($goods['goodstype']==0){
            //如果线下兑换，读取门店
            $stores = array();
            if(!empty($goods['isverify'])){
                $storeids = array();
                if (!empty($goods['storeids'])) {
                    $storeids = array_merge(explode(',', $goods['storeids']), $storeids);
                }
                if (empty($storeids)) {
                    //全部门店
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
                } else {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1', array(':uniacid' => $_W['uniacid']));
                }
            }
        }

		include $this->template();
	}
    function dispatch(){
        global $_W, $_GPC;
        $openid = $_W['openid'];
        $uniacid = $_W['uniacid'];
        $goodsid = intval($_GPC['goodsid']);
        $addressid = intval($_GPC['addressid']);
        $optionid = intval($_GPC['optionid']);
        $member = m('member')->getMember($openid);
        $goods = $this->model->getGoods($goodsid, $member,$optionid);
        $dispatch = 0;
        $dispatch_array = array();
        if($goods['dispatchtype']==0){
            $dispatch = $goods['dispatch'];
        }else{
            $merchid = $goods['merchid'];
            if (empty($goods['dispatchid'])) {
                //默认快递
                $dispatch_data = m('dispatch')->getDefaultDispatch($merchid);
            } else {
                $dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid']);
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
                                if (in_array($goods['goodsid'], $nodispatch_array['goodid'])) {
                                    $has_goodsid = 1;
                                }
                            }

                            if ($has_goodsid == 0) {
                                $nodispatch_array['goodid'][] = $goods['id'];
                                $nodispatch_array['title'][] = $goods['title'];
                                $nodispatch_array['city'] = $user_city;
                            }
                        }
                    }

                }

                if ($goods['isverify']==0 && $goods['goodstype']==0) {
                    //配送区域
                    $areas = unserialize($dispatch_data['areas']);
                    if ($dispatch_data['calculatetype'] == 1) {
                        //按件计费
                        $param = 1;
                    } else {
                        //按重量计费
                        $param = $goods['weight'] * 1;
                    }

                    if (array_key_exists($dkey, $dispatch_array)) {
                        $dispatch_array[$dkey]['param'] += $param;
                    } else {
                        $dispatch_array[$dkey]['data'] = $dispatch_data;
                        $dispatch_array[$dkey]['param'] = $param;
                    }
                }
            }
            $dispatch_merch = array();
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
                    $dispatch = $dprice;

                }
            }

        }

        show_json(1,array('dispatch'=>$dispatch));
    }
}
