<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Orderstatic_EweiShopV2Page extends PluginWebPage {

    function main() {
        global $_W, $_GPC;
        $status = intval($_GPC['status']);
        empty($status) && $status = 1;
        if ($status == -1) {
            if(!cv('commission.statistics.orderstatic')){
                $this->message("你没有相应的权限查看");
            }
        } else {
            if(!cv('commission.statistics.orderstatic' . $status)){
                $this->message("你没有相应的权限查看");
            }
        }
        $sql="SELECT * FROM ".tablename("ewei_shop_member")." where isagent=1 and ordercheck=1";

        $list=pdo_fetchAll($sql);
        //统计订单总数
        //订单金额总数
        
        //下级人数
        //累计销售总额
        foreach ($list as $key => &$row) {
            //获取用户的销售总额
            $sql="SELECT sum(price) from ".tablename('ewei_shop_order')." where agentid={$row[id]} and status>1 and uniacid={$_W['uniacid']}";
            $row['ordermoney']=pdo_fetchcolumn($sql);

            $sql="SELECT count(*) from ".tablename('ewei_shop_order')." where agentid={$row[id]} and status>1 and uniacid={$_W['uniacid']}";
            $row['order_count']=pdo_fetchcolumn($sql);
        }
        unset($row);
        include $this->template();
    }


    function getonemember(){
          global $_W, $_GPC;
    }
}
