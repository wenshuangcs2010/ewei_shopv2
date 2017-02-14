<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage {

    function main() {
        global $_W, $_GPC;
        if ($_W['ispost']){
            $virtual = array(
                'status'=>intval($_GPC['data']['status']),
                'virtual_people'=>intval($_GPC['data']['virtual_people']),
                'virtual_commission'=>intval($_GPC['data']['virtual_commission']),
                'virtual_text'=>trim($_GPC['data']['virtual_text']),
                'virtual_text2'=>trim($_GPC['data']['virtual_text2'])
            );
            m('common')->updateSysset(array('sale'=>array('virtual'=>$virtual)));
            plog('sale.virtual.edit',"修改积分排名设置");
            show_json(1);
        }
        $sale = m('common')->getSysset('sale');
        $data = $sale['virtual'];
        $module_ban = $_W['setting']['module_receive_ban'];
        if (!is_array($module_ban)) {
            $module_ban = array();
        }
        include $this->template();
    }
}
