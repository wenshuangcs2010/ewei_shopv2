<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN .'commission/core/page_login_mobile.php';

class Withdraw_EweiShopV2Page extends CommissionMobileLoginPage
{

    function main(){
        global $_W, $_GPC;
        $openid = $_W['openid'];
        $member = $this->model->getInfo($openid, array('total','ok', 'apply', 'check', 'lock','pay','wait','fail'));
        $cansettle = $member['commission_ok'] >=1 && $member['commission_ok'] >= floatval($this->set['withdraw']);

        $agentid = $member['agentid'];
        if (!empty($agentid)) {
            $data = pdo_fetch('select sum(charge) as sumcharge from ' . tablename('ewei_shop_commission_log') .' where mid=:mid and uniacid=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':mid' => $agentid));
            $commission_charge = $data['sumcharge'];
            $member['commission_charge'] =  $commission_charge;
        } else {
            $member['commission_charge'] = 0;
        }
        if($_W['uniacid']==FX_ACCOUNT){
            
        }
        include $this->template();
    }

}
