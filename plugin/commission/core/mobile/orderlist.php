 <?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';

class Orderlist_EweiShopV2Page extends CommissionMobileLoginPage
{

 function main(){
        global $_W, $_GPC;
        $member = m('member')->getMember($_W['openid']);
        $memberid=$member['id'];
        include $this->template();

    }

}