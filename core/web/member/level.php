<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Level_EweiShopV2Page extends WebPage {

    function main() {
        global $_W, $_GPC;
        $set = m('common')->getSysset();
        $shopset = $set['shop'];
        $default = array(
            'id' => 'default',
            'levelname' => empty($set['shop']['levelname']) ? '普通等级' : $set['shop']['levelname'],
            'discount' => $set['shop']['leveldiscount'],
            'ordermoney' => 0,
            'ordercount' => 0
        );

        $condition = " and uniacid=:uniacid";
        $params = array(':uniacid' => $_W['uniacid']);

        if ($_GPC['enabled'] != '') {
            $condition.=' and enabled=' . intval($_GPC['enabled']);
        }

        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition.=' and ( levelname like :levelname)';
            $params[':levelname'] = "%{$_GPC['keyword']}%";
        }

        if (p('cmember')) {
            $condition .= " and flag=1";
        }

        $others = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_member_level') . " WHERE 1 {$condition} ORDER BY level asc", $params);
        $list = array_merge(array($default), $others);
        include $this->template();
    }

    function add() {
        $this->post();
    }

    function edit() {
        $this->post();
    }

    protected function post() {
        global $_W, $_GPC,$_S;

        $id = trim($_GPC['id']);
        $set = $_S;

        $setdata = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));

        if ($id == 'default') {
            $level = array(
                'id' => 'default',
                'levelname' => empty($set['shop']['levelname']) ? '普通等级' : $set['shop']['levelname'],
                'discount' => $set['shop']['leveldiscount'],
                'ordermoney' => 0,
                'ordercount' => 0
            );
          
        } else {
            $level = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_member_level') . " WHERE id=:id and uniacid=:uniacid limit 1", array(':uniacid' => $_W['uniacid'], ':id' => intval($id)));
        }


        if ($_W['ispost']) {
            $enabled = intval($_GPC['enabled']);
            $iscommission = intval($_GPC['iscommission']);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'level' => intval($_GPC['level']),
                'levelname' => trim($_GPC['levelname']),
                'ordercount' => intval($_GPC['ordercount']),
                'ordermoney' => $_GPC['ordermoney'],
                'discount' => trim($_GPC['discount']),
                'iscommission' => $iscommission,
            );
            
            if (!empty($id)) {
                if ($id == 'default') {
                    $updatecontent = "<br/>等级名称: {$set['shop']['levelname']}->{$data['levelname']}"
                            . "<br/>折扣: {$set['shop']['leveldiscount']}->{$data['discount']}";
                    $set['shop']['levelname'] = $data['levelname'];
                    $set['shop']['leveldiscount'] = $data['discount'];
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'sets' => iserializer($set)
                    );
                    if (empty($setdata)) {
                        pdo_insert('ewei_shop_sysset', $data);
                    } else {
                        pdo_update('ewei_shop_sysset', $data, array('uniacid' => $_W['uniacid']));
                    }
                    $setdata = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
                    m('cache')->set('sysset', $setdata);
                    plog('member.level.edit', "修改会员默认等级" . $updatecontent);
                } else {
                    $updatecontent = "<br/>等级名称: {$level['levelname']}->{$data['levelname']}"
                            . "<br/>折扣: {$level['leveldiscount']}->{$data['discount']}";
                    pdo_update('ewei_shop_member_level', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                    plog('member.level.edit', "修改会员等级 ID: {$id}" . $updatecontent);
                }
            } else {
                pdo_insert('ewei_shop_member_level', $data);
                $id = pdo_insertid();
                plog('member.level.add', "添加会员等级 ID: {$id}");
            }
            show_json(1, array('url' => webUrl('member/level')));
        }
        $level_array = array();
        for ($i = 0; $i < 101; $i++) {
            $level_array[$i] = $i;
        }
        include $this->template();
    }

    function delete() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,levelname FROM " . tablename('ewei_shop_member_level') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {

            pdo_delete('ewei_shop_member_level', array('id' => $item['id']));
            plog('member.level.delete', "删除等级 ID: {$item['id']} 标题: {$item['levelname']} ");
        }
        show_json(1, array('url' => referer()));
    }

    function enabled() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,levelname FROM " . tablename('ewei_shop_member_level') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_member_level', array('enabled' => intval($_GPC['enabled'])), array('id' => $item['id']));
            plog('member.level.edit', "修改会员等级状态<br/>ID: {$item['id']}<br/>标题: {$item['levelname']}<br/>状态: " . $_GPC['enabled'] == 1 ? '启用' : '禁用');
        }
        show_json(1, array('url' => referer()));
    }

}
