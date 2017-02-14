<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Merchant_EweiShopV2Page extends PluginWebPage
{

    function main() {


        global $_W, $_GPC;
        ca('commission.agent.view');

        $level = $this->set['level'];

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params = array();
        $condition = '';

        $searchfield = strtolower(trim($_GPC['searchfield']));
        $keyword = trim($_GPC['keyword']);
        
//        $pager = pagination($total, $pindex, $psize);
        load()->func('tpl');
        include $this->template();
    }

    function add()
    {
        $this->post();
    }

    function edit()
    {
        $this->post();
    }

    protected function post()
    {
        include $this->template();
    }

    function delete()
    {

        global $_W, $_GPC;


        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }

        $members = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_member') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($members as $member) {
            //pdo_update('ewei_shop_member',array('agentid'=>0),array('agentid'=>$member['id']));
            pdo_update('ewei_shop_member', array('isagent' => 0, 'status' => 0), array('id' => $member['id']));
            plog('commission.agent.delete', "取消分销商资格 <br/>分销商信息:  ID: {$member['id']} /  {$member['openid']}/{$member['nickname']}/{$member['realname']}/{$member['mobile']}");
        }
        show_json(1, array('url' => referer()));
    }

}
