<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Util_EweiShopV2Page extends MobilePage {

    function query() {
        global $_W, $_GPC;

        $type = intval($_GPC['type']);
        $money = floatval($_GPC['money']);
        $merchs = $_GPC['merchs'];
        $goods = $_GPC['goods'];

        if($type==0)
        {
            $list = com_run('coupon::getAvailableCoupons', $type, 0, $merchs,$goods);
        }else if( $type==1)
        {
            $list = com_run('coupon::getAvailableCoupons', $type, $money, $merchs);

        }

        show_json(1, array('coupons' => $list));
    }

    function picker(){
        include $this->template();
    }

}
