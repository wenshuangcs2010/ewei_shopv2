<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Log_EweiShopV2Page extends PluginWebPage {

	function main() {

		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$params = array(':uniacid' => $_W['uniacid']);
		$condition = " and comq.uniacid=:uniacid and comq.composterid=" . intval($_GPC['id']);
		$sql="SELECT comq.*,mg.groupname FROM " . tablename('ewei_shop_composter_qr')." as comq"
				." LEFT JOIN ".tablename("ewei_shop_member_group")." as mg ON mg.id=comq.groupid"
				. " WHERE 1 {$condition} ORDER BY createtimes desc "
				. "  LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		

		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('SELECT count(*)  FROM ' . tablename('ewei_shop_composter_qr') 
				. " as comq where 1 {$condition}  ", $params);
		

		$pager = pagination($total, $pindex, $psize);

		load()->func('tpl');
		include $this->template();
	}

}
