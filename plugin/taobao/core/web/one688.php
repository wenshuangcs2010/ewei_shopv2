<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class One688_EweiShopV2Page extends PluginWebPage {

	function main() {
		global $_W, $_GPC;
		$sql = 'SELECT * FROM ' . tablename('ewei_shop_category') . ' WHERE `uniacid` = :uniacid ORDER BY `parentid`, `displayorder` DESC';
		$category = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']), 'id');
		$parent = $children = array();
		if (!empty($category)) {
			foreach ($category as $cid => $cate) {
				if (!empty($cate['parentid'])) {
					$children[$cate['parentid']][] = $cate;
				} else {
					$parent[$cate['id']] = $cate;
				}
			}
		}
		$shopset = $_W['shopset']['shop'];
		load()->func('tpl');
		include $this->template();
	}

	function fetch() {
		global $_GPC;
		set_time_limit(0);
		$ret = array();
		$url = $_GPC['url'];
		$pcate = intval($_GPC['pcate']);
		$ccate = intval($_GPC['ccate']);
		$tcate = intval($_GPC['tcate']);
		if (is_numeric($url)) {
			$itemid = $url;
		} else {
			preg_match("/(\d+).html/i", $url, $matches);
			if (isset($matches[1])) {
				$itemid = $matches[1];
			}
		}

		if (empty($itemid)) {
			die(json_encode(array("result" => 0, "error" => "未获取到 itemid!")));
		}
		$ret = $this->model->get_item_one688($itemid, $_GPC['url'], $pcate, $ccate, $tcate);
		plog('1688.main', '1688抓取宝贝 1688id:' . $itemid);
		die(json_encode($ret));
	}

}
