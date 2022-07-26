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

   function main()
    {
        global $_W;
        if($_W['uniacid']!=DIS_ACCOUNT){
            $loginurl = webUrl('union');
            header('location: ' . $loginurl);
            exit();
        }

        $shop_data = m('common')->getSysset('shop');

        //多商户
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }

        //待发货详细信息
        $order_sql ="select id,ordersn,createtime,address,price,invoicename from " . tablename('ewei_shop_order') . " where uniacid = :uniacid and merchid=0 and isparent=0 and deleted=0 AND ( status = 1 or (status=0 and paytype=3) ) ORDER BY createtime ASC LIMIT 20";

        $order = pdo_fetchall($order_sql,array(':uniacid' => $_W['uniacid']));

        foreach ($order as &$value)
        {
            $value['address'] = iunserializer($value['address']);
        }
        unset($value);
        $order_ok = $order;

        //公告信息
        $notice = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_system_copyright_notice') . " ORDER BY displayorder ASC,createtime DESC LIMIT 10" );

	$hascommission =false;
	if(p('commission')){
	     $hascommission =  intval($_W['shopset']['commission']['level'])>0;
	}

        include $this->template();
    }


    function view()
    {
        global $_GPC;
        $id = intval($_GPC['id']);
        $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_system_copyright_notice') . " WHERE id = $id ORDER BY displayorder ASC,createtime DESC" );
        $item['content'] = htmlspecialchars_decode($item['content']);
        include $this->template('shop/view');
    }

    public function ajax()
    {
        global $_W;
        $paras = array(':uniacid' => $_W['uniacid']);
        //已售罄商品
        $goods_totals = pdo_fetchcolumn(
            'SELECT COUNT(1) FROM ' . tablename('ewei_shop_goods') ." WHERE uniacid = :uniacid and status=1 and deleted=0 and total<=0 and total<>-1  ",
            $paras
        );
        //待审核提现
        $finance_total = pdo_fetchcolumn("select count(1) from " . tablename('ewei_shop_member_log') . " log "
            . " left join " . tablename('ewei_shop_member') . " m on m.openid=log.openid and m.uniacid= log.uniacid"
            . " left join " . tablename('ewei_shop_member_group') . " g on m.groupid=g.id"
            . " left join " . tablename('ewei_shop_member_level') . " l on m.level =l.id"
            . " where log.uniacid=:uniacid and log.type=:type and log.money<>0 and log.status=:status", array(':uniacid' => $_W['uniacid'], ':type' => 1,':status'=>0));

        //分销商总数
        $commission_agent_total = pdo_fetchcolumn("select count(1) from" . tablename('ewei_shop_member') . " dm "
            . " left join " . tablename('ewei_shop_member') . " p on p.id = dm.agentid "
            . " left join " . tablename('mc_mapping_fans') . "f on f.openid=dm.openid"
            . " where dm.uniacid =:uniacid and dm.isagent =1", array(':uniacid' => $_W['uniacid']));

        //待审核分销商
        $commission_agent_status0_total = pdo_fetchcolumn("select count(1) from" . tablename('ewei_shop_member') . " dm "
            . " left join " . tablename('ewei_shop_member') . " p on p.id = dm.agentid "
            . " left join " . tablename('mc_mapping_fans') . "f on f.openid=dm.openid"
            . " where dm.uniacid =:uniacid and dm.isagent =1 and dm.status=:status", array(':uniacid' => $_W['uniacid'], ':status' => 0));

        //待审核佣金提现申请
        $commission_apply_status1_total = pdo_fetchcolumn("select count(1) from" . tablename('ewei_shop_commission_apply') . " a "
            . " left join " . tablename('ewei_shop_member') . " m on m.uid = a.mid"
            . " left join " . tablename('ewei_shop_commission_level') . " l on l.id = m.agentlevel"
            . " where a.uniacid=:uniacid and a.status=:status", array(':uniacid' => $_W['uniacid'], ':status' => 1));

        //待打款佣金提现申请
        $commission_apply_status2_total = pdo_fetchcolumn("select count(1) from" . tablename('ewei_shop_commission_apply') . " a "
            . " left join " . tablename('ewei_shop_member') . " m on m.uid = a.mid"
            . " left join " . tablename('ewei_shop_commission_level') . " l on l.id = m.agentlevel"
            . " where a.uniacid=:uniacid and a.status=:status", array(':uniacid' => $_W['uniacid'], ':status' => 2));

        show_json(1,array(
            'goods_totals' => $goods_totals,
            'finance_total' => $finance_total,
            'commission_agent_total' => $commission_agent_total,
            'commission_agent_status0_total' => $commission_agent_status0_total,
            'commission_apply_status1_total' => $commission_apply_status1_total,
            'commission_apply_status2_total' => $commission_apply_status2_total
        ));
    }
}
