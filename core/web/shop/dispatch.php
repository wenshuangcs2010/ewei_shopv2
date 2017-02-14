<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Dispatch_EweiShopV2Page extends WebPage {

    function main() {

        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $depostlist=Dispage::get_all_depot($_W['uniacid']);
        foreach ($depostlist as $key => $value) {
            $r[$value['id']]=$value['title'];
        }
        $condition = " and uniacid=:uniacid and merchid=0";
        $params = array(':uniacid' => $_W['uniacid']);
        if ($_GPC['enabled'] != '') {
            $condition.=' and enabled=' . intval($_GPC['enabled']);
        }
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition.=' and dispatchname  like :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        $list = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_dispatch') . " WHERE 1 {$condition}  ORDER BY displayorder DESC limit " . ($pindex - 1) * $psize . ',' . $psize, $params);
        foreach ($list as &$row) {
            $row['depotid']=$r[$row['depotid']];
        }
        unset($row);
        $total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('ewei_shop_dispatch') . " WHERE 1 {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);



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
        $depostlist=Dispage::get_all_depot($_W['uniacid']);
        if ($_W['ispost']) {
            //配送区域
            $areas = array();
            $randoms = $_GPC['random'];
            if (is_array($randoms)) {
                foreach ($randoms as $random) {
                    $citys = trim($_GPC['citys'][$random]);
                    if (empty($citys)) {
                        continue;
                    }
                    if ($_GPC['firstnum'][$random] < 1) {
                        $_GPC['firstnum'][$random] = 1;
                    }
                    if ($_GPC['secondnum'][$random] < 1) {
                        $_GPC['secondnum'][$random] = 1;
                    }
                    $areas[] = array(
                        'citys' => $_GPC['citys'][$random],
                        'firstprice' => $_GPC['firstprice'][$random],
                        'firstweight' => $_GPC['firstweight'][$random],
                        'secondprice' => $_GPC['secondprice'][$random],
                        'secondweight' => $_GPC['secondweight'][$random],
                        'firstnumprice' => $_GPC['firstnumprice'][$random],
                        'firstnum' => $_GPC['firstnum'][$random],
                        'secondnumprice' => $_GPC['secondnumprice'][$random],
                        'secondnum' => $_GPC['secondnum'][$random]
                    );
                }
            }

            $_GPC['default_firstnum'] = trim($_GPC['default_firstnum']);
            if ($_GPC['default_firstnum'] < 1) {
                $_GPC['default_firstnum'] = 1;
            }
            $_GPC['default_secondnum'] = trim($_GPC['default_secondnum']);
            if ($_GPC['default_secondnum'] < 1) {
                $_GPC['default_secondnum'] = 1;
            }
            $data = array(
                'uniacid' => $_W['uniacid'],
                'merchid' => 0,
                'displayorder' => intval($_GPC['displayorder']),
                'dispatchtype' => intval($_GPC['dispatchtype']),
                'isdefault' => intval($_GPC['isdefault']),
                'dispatchname' => trim($_GPC['dispatchname']),
                'express' => trim($_GPC['express']),
                'calculatetype' => trim($_GPC['calculatetype']),
                'firstprice' => trim($_GPC['default_firstprice']),
                'firstweight' => trim($_GPC['default_firstweight']),
                'secondprice' => trim($_GPC['default_secondprice']),
                'secondweight' => trim($_GPC['default_secondweight']),
                'firstnumprice' => trim($_GPC['default_firstnumprice']),
                'firstnum' => $_GPC['default_firstnum'],
                'secondnumprice' => trim($_GPC['default_secondnumprice']),
                'secondnum' => $_GPC['default_secondnum'],
                'areas' => iserializer($areas),
                'depotid'=>trim($_GPC['depotid']),
                'nodispatchareas' => iserializer($_GPC['nodispatchareas']),
                'enabled' => intval($_GPC['enabled'])
            );

            if ($data['isdefault']) {
                pdo_update('ewei_shop_dispatch', array('isdefault' => 0), array('uniacid' => $_W['uniacid'], 'merchid' => 0,'depotid'=>$data['depotid']));//wsq
            }

            if (!empty($id)) {
                plog('shop.dispatch.edit', "修改配送方式 ID: {$id}");
                pdo_update('ewei_shop_dispatch', $data, array('id' => $id));
            } else {
                pdo_insert('ewei_shop_dispatch', $data);
                $id = pdo_insertid();
                plog('shop.dispatch.add', "添加配送方式 ID: {$id}");
            }

            show_json(1, array('url' => webUrl('shop/dispatch', array('op' => 'display'))));
        }
        //修改
        $dispatch = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_dispatch') . " WHERE id = '$id' and merchid=0 and uniacid = '{$_W['uniacid']}'");
        if (!empty($dispatch)) {
            $dispatch_areas = unserialize($dispatch['areas']);
            $dispatch_carriers = unserialize($dispatch['carriers']);
            $dispatch_nodispatchareas = unserialize($dispatch['nodispatchareas']);
        }

        $areas = m('common')->getAreas();

        $express_list = m('express')->getExpressList();

        include $this->template();
    }

    function delete() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,dispatchname FROM " . tablename('ewei_shop_dispatch') . " WHERE id in( $id ) AND merchid=0 AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_delete('ewei_shop_dispatch', array('id' => $item['id']));
            plog('shop.dispatch.delete', "删除配送方式 ID: {$item['id']} 标题: {$item['dispatchname']} ");
        }
        show_json(1, array('url' => referer()));
    }

    function enabled() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,dispatchname FROM " . tablename('ewei_shop_dispatch') . " WHERE id in( $id ) AND merchid=0 AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_dispatch', array('enabled' => intval($_GPC['enabled'])), array('id' => $item['id']));
            plog('shop.dispatch.edit', "修改配送方式状态<br/>ID: {$item['id']}<br/>标题: {$item['dispatchname']}<br/>状态: " . $_GPC['enabled'] == 1 ? '显示' : '隐藏');
        }
        show_json(1, array('url' => referer()));
    }

    function setdefault() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        $depotid=intval($_GPC['depotid']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        if ($_GPC['isdefault'] == 1) {
            pdo_update('ewei_shop_dispatch', array('isdefault' => 0), array('uniacid' => $_W['uniacid'], 'merchid' => 0,'depotid'=>$depotid));
        }
        $items = pdo_fetchall("SELECT id,dispatchname FROM " . tablename('ewei_shop_dispatch') . " WHERE id in( $id ) AND merchid=0 AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_dispatch', array('isdefault' => intval($_GPC['isdefault'])), array('id' => $item['id']));
            plog('shop.dispatch.edit', "设为默认配送方式<br/>ID: {$item['id']}<br/>标题: {$item['dispatchname']}<br/>状态: " . $_GPC['isdefault'] == 1 ? '是' : '否');
        }
        show_json(1, array('url' => referer()));
    }
function displayorder() {

		global $_W, $_GPC;
		$id = intval($_GPC['id']);

		$displayorder = intval($_GPC['value']);
		$item = pdo_fetchall("SELECT id,dispatchname FROM " . tablename('ewei_shop_dispatch') . " WHERE id in( $id ) AND merchid=0 AND uniacid=" . $_W['uniacid']);

		if (!empty($item)) {
			pdo_update('ewei_shop_dispatch', array('displayorder' => $displayorder), array('id' => $id));
			plog('shop.dispatch.edit', "修改配送方式排序 ID: {$item['id']} 标题: {$item['dispatchname']} 排序: {$displayorder} ");
		}
		show_json(1);
	}

    function tpl() {
        global $_W, $_GPC;
        $random = random(16);

        ob_start();
        include $this->template('shop/dispatch/tpl');
        $contents = ob_get_contents();
        ob_clean();
        die(json_encode(array(
            'random' => $random,
            'html' => $contents
        )));
    }

}
