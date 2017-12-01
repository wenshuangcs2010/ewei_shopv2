<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Unit_EweiShopV2Page extends WebPage {

    function main() {
        global $_W, $_GPC;
        $condition = " and uniacid=:uniacid";
        $params = array(':uniacid' => $_W['uniacid']);

        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition.=' and ( unitname like :unitname)';
            $params[':unitname'] = "%{$_GPC['keyword']}%";
        }

        $list = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_unitlist') . " WHERE 1 {$condition} ORDER BY id asc", $params);
       
        include $this->template();
    }

    function add() {
        $this->post();
    }

    function edit() {
        $this->post();
    }

    protected function post() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $condition = " and uniacid=:uniacid";
        $params = array(':uniacid' => $_W['uniacid']);
        $alllist = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_member_group') . " WHERE 1 {$condition} ORDER BY id asc", $params);
        $group = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_unitlist') . " WHERE id =:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
        if($group['groupid']){
            $plugin_coupon = com('coupon');
            $reccoupon = $plugin_coupon->getCoupon($group['groupid']);
        }
        if ($_W['ispost']) {

            $data = array(
                'uniacid' => $_W['uniacid'],
                'unitname' => trim($_GPC['unitname']),
                'monthprice'=>trim($_GPC['monthprice']),
                'todayprice'=>trim($_GPC['todayprice']),
                'groupid'=>$_GPC['groupid'],
            );
           
            if (!empty($id)) {
                pdo_update('ewei_shop_unitlist', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                plog('member.group.edit', "修改单位 ID: {$id}");
            } else {
                pdo_insert('ewei_shop_unitlist', $data);
                $id = pdo_insertid();
                plog('member.group.add', "添加修改单位 ID: {$id}");
            }
            show_json(1, array('url' => webUrl('member/unit', array('op' => 'display'))));
        }
        include $this->template();
    }

    function delete() {
        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,unitname FROM " . tablename('ewei_shop_unitlist') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_delete('ewei_shop_unitlist', array('id' => $item['id']));
            plog('member.group.delete', "删除单位 ID: {$item['id']} 名称: {$item['unitname']} ");
        }
        show_json(1, array('url' => referer()));
    }

}
