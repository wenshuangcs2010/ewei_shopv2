<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Selectcoupon_EweiShopV2Page extends WebPage {

	function main() {
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' uniacid = :uniacid and merchid=0';
        $params = array(':uniacid' => $_W['uniacid']);
		 $sql = 'SELECT * FROM ' . tablename('ewei_shop_coupon') . " "
            . " where  1 and {$condition} ORDER BY displayorder DESC,id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params); $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_coupon') . " where 1 and {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);
		
		include $this->template();
	}
}