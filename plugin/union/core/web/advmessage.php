<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Advmessage_EweiShopV2Page extends WebPage {

    function main() {
        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = " and uniacid=:uniacid";
        $params = array(':uniacid' => $_W['uniacid']);
        if ($_GPC['status'] != '') {
            $condition.=' and status=' . intval($_GPC['status']);
        }
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition.=' and title  like :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }

        $list = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_union_advmessage') . " WHERE 1 {$condition}  ORDER BY displayorder DESC limit " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('ewei_shop_union_advmessage') . " WHERE 1 {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
    function add(){
        $this->post();
    }
    function edit(){
        $this->post();
    }
    protected function post() {
        global $_W, $_GPC;

        $id = intval($_GPC['id']);

        if ($_W['ispost']) {
            $data = array(
                'uniacid' => $_W['uniacid'],
                'displayorder' => intval($_GPC['displayorder']),
                'title' => trim($_GPC['title']),
                'thumb' => save_media($_GPC['thumb']),
                'link' => trim($_GPC['link']),
                'detail' => m('common')->html_images($_GPC['detail']),
                'status' => intval($_GPC['status']),
                'createtime' => time()
            );
            if (!empty($id)) {
                pdo_update('ewei_shop_union_advmessage', $data, array('id' => $id));
                plog('shop.notice.edit', "修改公告 ID: {$id}");
            } else {
                pdo_insert('ewei_shop_union_advmessage', $data);
                $id = pdo_insertid();
                plog('shop.notice.add', "修改公告 ID: {$id}");
            }
            show_json(1, array('url' => webUrl('union/advmessage')));
        }
        $notice = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_union_advmessage') . " WHERE id =:id and uniacid=:uniacid limit 1",array(':id'=>$id,':uniacid'=>$_W['uniacid']));
        include $this->template();
    }

    function displayorder() {
        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        $displayorder = intval($_GPC['value']);
        $item = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_union_advmessage') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        if (!empty($item)) {
            pdo_update('ewei_shop_notice', array('displayorder' => $displayorder), array('id' => $id));
            plog('shop.notice.edit', "修改公告排序 ID: {$item['id']} 标题: {$item['advname']} 排序: {$displayorder} ");
        }
        show_json(1);
    }

    function delete() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_union_advmessage') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_delete('ewei_shop_union_advmessage', array('id' => $item['id']));
            plog('shop.notice.delete', "删除公告 ID: {$item['id']} 标题: {$item['title']} ");
        }
        show_json(1, array('url' => referer()));
    }

    function status() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_union_advmessage') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_union_advmessage', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
            plog('shop.notice.edit', "修改公告状态<br/>ID: {$item['id']}<br/>标题: {$item['title']}<br/>状态: " . $_GPC['status'] == 1 ? '显示' : '隐藏');
        }
        show_json(1, array('url' => referer()));
    }

}
