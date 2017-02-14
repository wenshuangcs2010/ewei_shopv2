<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'globonus/core/page_login_mobile.php';

class Bonus_EweiShopV2Page extends GlobonusMobileLoginPage
{

    function main()
    {
        global $_W, $_GPC;
        $status = intval($_GPC['status']);
        $bonus = $this->model->getBonus($_W['openid'], array('ok', 'lock', 'total'));
        include $this->template();
    }

    function get_list()
    {

        global $_W, $_GPC;
        $member = m('member')->getMember($_W['openid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition = " and `openid`=:openid and uniacid=:uniacid";
        $params = array(
            ':openid' => $_W['openid'],
            ':uniacid' => $_W['uniacid']
        );
        $status = trim($_GPC['status']);
        if ($status == 1) {
            $condition .= ' and status=1';
        } elseif ($status == 2) {

            $condition .= ' and (status=-1 or status=0)';
        }

        $list = pdo_fetchall("select *  from " . tablename('ewei_shop_globonus_billp') . " where 1 {$condition} order by id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_globonus_billp') . " where 1 {$condition}", $params);
        show_json(1, array('total' => $total, 'list' => $list, 'pagesize' => $psize));

    }

}
