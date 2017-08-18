<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Log_EweiShopV2Page extends MobileLoginPage {

    function main() {
       
        global $_W, $_GPC;
        $_GPC['type'] = intval($_GPC['type']);
        include $this->template();
    }
    function test(){
       if(is_qyweixin()){
            var_dump("企业微信");
       }else{
         var_dump("非企业微信");
       }
     
    }
    function get_list(){
        global $_W, $_GPC;
        $type = intval($_GPC['type']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $apply_type = array(0 => '微信钱包', 2 => '支付宝', 3 => '银行卡');

        $condition = " and openid=:openid and uniacid=:uniacid and type=:type";
        $params = array(
            ':uniacid' => $_W['uniacid'],
            ':openid' => $_W['openid'],
            ':type' => intval($_GPC['type'])
        );

        $list = pdo_fetchall("select * from " . tablename('ewei_shop_member_log') . " where 1 {$condition} order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_log') . " where 1 {$condition}", $params);
        foreach ($list as &$row) {
            $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
            $row['typestr'] = $apply_type[$row['applytype']];
        }
        unset($row);
        show_json(1,array('list'=>$list,'total'=>$total,'pagesize'=>$psize));
    }

}
