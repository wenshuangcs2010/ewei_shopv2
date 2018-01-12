<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Role_EweiShopV2Page extends WebPage {

	function main() {

		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));

		$psize = 20;
		$status = $_GPC['status'];
		$condition = " and uniacid = :uniacid and deleted=0";
		$params = array(':uniacid' => $_W['uniacid']);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition.=' and rolename like :keyword';
			$params[':keyword'] = "%{$_GPC['keyword']}%";
		}
		if ($_GPC['status'] != '') {
			$condition.=' and status=' . intval($_GPC['status']);
		}
		$list = pdo_fetchall("SELECT *  FROM " . tablename('ewei_shop_perm_role') . " WHERE 1 {$condition} ORDER BY id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
		foreach ($list as &$row) {
			$row['usercount'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_perm_user') . ' where roleid=:roleid limit 1', array(':roleid' => $row['id']));
		}
		unset($row);
		$total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('ewei_shop_perm_role') . "  WHERE 1 {$condition} ", $params);
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
		$item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_perm_role') . " WHERE id =:id and deleted=0 and uniacid=:uniacid limit 1", array(':uniacid' => $_W['uniacid'], ':id' => $id));
		$perms = com('perm')->formatPerms();
		
		$role_perms = array();
		$user_perms = array();
		if (!empty($item)) {
			$role_perms = explode(',', $item['perms2']);
		}
		$user_perms = explode(',', $item['perms2']);
		if ($_W['ispost']) {
			$data = array(
				'uniacid' => $_W['uniacid'],
				'rolename' => trim($_GPC['rolename']),
				'status' => intval($_GPC['status']),
				'perms2' => is_array($_GPC['perms']) ? implode(',', $_GPC['perms']) : ''
			);
			if (!empty($id)) {
				pdo_update('ewei_shop_perm_role', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
				plog('perm.role.edit', "修改角色 ID: {$id}");
			} else {
				pdo_insert('ewei_shop_perm_role', $data);
				$id = pdo_insertid();
				plog('perm.role.add', "添加角色 ID: {$id} ");
			}
			show_json(1);
		}
		
		include $this->template();
	}

	function delete() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}
		$items = pdo_fetchall("SELECT id,rolename FROM " . tablename('ewei_shop_perm_role') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
		foreach ($items as $item) {
			pdo_delete('ewei_shop_perm_role', array('id' => $item['id']));
			plog('perm.role.delete', "删除角色 ID: {$item['id']} 角色名称: {$item['rolename']} ");
		}
		show_json(1, array('url' => referer()));
	}

	function status() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}
		$status = intval($_GPC['status']);
		$items = pdo_fetchall("SELECT id,rolename FROM " . tablename('ewei_shop_perm_role') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
		foreach ($items as $item) {
			pdo_update('ewei_shop_perm_role', array('status' => $status), array('id' => $item['id']));
			plog('perm.role.edit', "修改角色状态 ID: {$item['id']} 角色名称: {$item['rolename']} 状态: " . ($status == 0 ? '禁用' : '启用'));
		}
		show_json(1, array('url' => referer()));
	}

	function query() {
		global $_GPC, $_W;
		$kwd = trim($_GPC['keyword']);
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];
		$condition = " and uniacid=:uniacid and deleted=0";
		if (!empty($kwd)) {
			$condition.=" AND `rolename` LIKE :keyword";
			$params[':keyword'] = "%{$kwd}%";
		}
		$ds = pdo_fetchall('SELECT id,rolename,perms2 FROM ' . tablename('ewei_shop_perm_role') . " WHERE status=1 {$condition} order by id asc", $params);
		include $this->template();
		exit;
	}

}
