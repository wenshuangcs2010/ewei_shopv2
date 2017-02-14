<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Tmessage_EweiShopV2Page extends WebPage
{

	function main() {
		global $_W, $_GPC;

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = " and uniacid=:uniacid";
		$params = array(':uniacid' => $_W['uniacid']);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition.=' and title  like :keyword';
			$params[':keyword'] = "%{$_GPC['keyword']}%";
		}

		$list = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_member_message_template') . " WHERE 1 {$condition}  ORDER BY id asc limit " . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('ewei_shop_member_message_template') . " WHERE 1 {$condition}", $params);
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

		if (!empty($_GPC['id'])) {
			$list = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_message_template') . ' WHERE id=:id and uniacid=:uniacid ', array(':id' => $_GPC['id'], ':uniacid' => $_W['uniacid']));
			$data = iunserializer($list['data']);
		}
		if ($_W['ispost']) {
			$id = $_GPC['id'];
			$keywords = $_GPC['tp_kw'];
			$value = $_GPC['tp_value'];
			$color = $_GPC['tp_color'];
			if (!empty($keywords)) {
				$data = array();
				foreach ($keywords as $key => $val) {
					$data[] = array('keywords' => $keywords[$key], 'value' => $value[$key], 'color' => $color[$key]);
				}
			}
			$insert = array(
				'title' => $_GPC['tp_title'],
				'template_id' => trim($_GPC['tp_template_id']),
				'first' => trim($_GPC['tp_first']),
				'firstcolor' => trim($_GPC['firstcolor']),
				'data' => iserializer($data),
				'remark' => trim($_GPC['tp_remark']),
				'remarkcolor' => trim($_GPC['remarkcolor']),
				'url' => trim($_GPC['tp_url']),
				'uniacid' => $_W['uniacid']
			);

			if (empty($id)) {
				pdo_insert('ewei_shop_member_message_template', $insert);
				$id = pdo_insertid();
				plog('sysset.tmessage.delete', "添加群发模板 ID: {$id} 标题: {$insert['title']} ");
			} else {
				pdo_update('ewei_shop_member_message_template', $insert, array('id' => $id));
				plog('sysset.tmessage.delete', "编辑群发模板 ID: {$id} 标题: {$insert['title']} ");
			}
			show_json(1, array('url' => webUrl('sysset/tmessage')));
		}
		include $this->template();
	}

	function delete() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}
		$items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_member_message_template') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
		foreach ($items as $item) {
			pdo_delete('ewei_shop_member_message_template', array('id' => $id, 'uniacid' => $_W['uniacid']));
			plog('sysset.tmessage.delete', "删除群发模板 ID: {$item['id']} 标题: {$item['title']} ");
		}
		show_json(1, array('url' => referer()));
	}

	function query() {
		global $_W, $_GPC;
		$kwd = trim($_GPC['keyword']);
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];
		$condition = " and uniacid=:uniacid";
		if (!empty($kwd)) {
			$condition.=" AND `title` LIKE :keyword";
			$params[':keyword'] = "%{$kwd}%";
		}
		$ds = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_member_message_template') . " WHERE 1 {$condition} order by id asc", $params);
		if ($_GPC['suggest']) {
			die(json_encode(array('value' => $ds)));
		}
		include $this->template();
	}
	
	function tpl(){
		global $_W,$_GPC;
		$kw = $_GPC['kw'];
                  include $this->template();
	}

}
