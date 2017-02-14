<?php
/**
 * Created by Yang.
 * User: pc
 * Date: 2016/3/21
 * Time: 20:07
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Indexremove_EweiShopV2Page extends WebPage
{

    function main()
    {
        global $_W,$_GPC;
        if ($_W['ispost'])
        {
            $orderid = trim($_GPC['data']['orderid']);
            $recharge = trim($_GPC['data']['recharge']);
            $params = array('uniacid'=>$_W['uniacid']);
            if (!empty($orderid))
            {
                $params['ordersn'] = $orderid;
                pdo_update('ewei_shop_order',array('deleted'=>1),$params);
                show_json(1);
            }
            if (!empty($recharge))
            {
                $params['logno'] = $recharge;
                pdo_delete('ewei_shop_member_log',$params);
                show_json(1);
            }
        }
        include $this->template();
    }
}