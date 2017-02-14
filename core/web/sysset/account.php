<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Account_EweiShopV2Page extends WebPage {

    public function main(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $start = ($pindex - 1) * $psize;
        $condition = '';
        $pars = array();
        $keyword = trim($_GPC['keyword']);
        $s_uniacid = intval($_GPC['s_uniacid']);
        if(!empty($keyword)) {
            $condition =" AND a.`name` LIKE :name";
            $pars[':name'] = "%{$keyword}%";
        }

        $tsql = "SELECT COUNT(*) FROM " . tablename('uni_account'). " as a LEFT JOIN". tablename('account'). " as b ON a.default_acid = b.acid WHERE a.default_acid <> 0 and b.isdeleted=0 {$condition}";
        $total = pdo_fetchcolumn($tsql, $pars);
        $pager = pagination($total, $pindex, $psize);
        $sql = "SELECT * FROM ". tablename('uni_account'). " as a LEFT JOIN". tablename('account'). " as b ON a.default_acid = b.acid WHERE a.default_acid <> 0 and b.isdeleted=0 {$condition} ORDER BY a.`rank` DESC, a.`uniacid` DESC LIMIT {$start}, {$psize}";
        $list = pdo_fetchall($sql, $pars);
        if(!empty($list)) {
            foreach($list as $unia => &$account) {
                $account['details'] = uni_accounts($account['uniacid']);
                $account['role'] = uni_permission($_W['uid'], $account['uniacid']);
                $account['setmeal'] = uni_setmeal($account['uniacid']);
            }
        }
        if(!$_W['isfounder']) {
            $stat = user_account_permission();
        }
        if (!empty($_W['setting']['platform']['authstate'])) {
            load()->classs('weixin.platform');
            $account_platform = new WeiXinPlatform();
            $authurl = $account_platform->getAuthLoginUrl();
        }

        $list_tmp = array();
        foreach ($list as $val){
            if ($val['role'] != false) {
                $list_tmp[] = $val;
            }
        }
        $list =$list_tmp;
        include $this->template('sysset/account/index');
    }

    public function choose()
    {
        global $_W,$_GPC;
        $uniacid = intval($_GPC['uniacid']);
        $role = uni_permission($_W['uid'], $uniacid);
        if(empty($role)) {
            message('操作失败, 非法访问.');
        }
        isetcookie('__uniacid', $uniacid, 7 * 86400);
        isetcookie('__uid', $_W['uid'], 7 * 86400);
        header('location: ' . webUrl('shop'));
    }

}
