<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Task_EweiShopV2Page extends SystemPage {

	function main() {
		global $_W, $_GPC;

        $task_mode = m('cache')->getString('task_mode', 'global');
		$receive_time = m('cache')->getString('receive_time', 'global');
		$closeorder_time = m('cache')->getString('closeorder_time', 'global');
		$couponback_time = m('cache')->getString('couponback_time', 'global');
		$groups_order_cancelorder_time = m('cache')->getString('groups_order_cancelorder_time', 'global');
		$groups_team_refund_time = m('cache')->getString('groups_team_refund_time', 'global');
		$groups_receive_time = m('cache')->getString('groups_receive_time', 'global');

		if ($_W['ispost']) {
            m('cache')->set('task_mode', intval($_GPC['task_mode']), 'global');
			m('cache')->set('receive_time', intval($_GPC['receive_time']), 'global');
			m('cache')->set('closeorder_time', intval($_GPC['closeorder_time']), 'global');
			m('cache')->set('couponback_time', intval($_GPC['couponback_time']), 'global');
			m('cache')->set('groups_order_cancelorder_time', intval($_GPC['groups_order_cancelorder_time']), 'global');
			m('cache')->set('groups_team_refund_time', intval($_GPC['groups_team_refund_time']), 'global');
			m('cache')->set('groups_receive_time', intval($_GPC['groups_receive_time']), 'global');
			//show_json(0,intval($_GPC['groups_receive_time']));
			show_json(1);
		}

		include $this->template();
	}

}
