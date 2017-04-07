<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage {

	function main() {
		global $_W, $_GPC;
		if (cv('taobao.main')) {
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
			$sql='SELECT * FROM ' . tablename('ewei_shop_depot') . ' WHERE `uniacid` = :uniacid ';
			$depostlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']), 'id');
			$shopset = $_W['shopset']['shop'];
			load()->func('tpl');
			include $this->template();
		}else if (cv('taobao.jingdong')) {
			header('location: ' . webUrl('taobao/jingdong'));
			exit;
		} else if (cv('taobao.one688')) {
			header('location: ' . webUrl('taobao/one688'));
			exit;
		} else if (cv('taobao.taobaocsv')) {
			header('location: ' . webUrl('taobao/taobaocsv'));
			exit;
		}
	}

	function fetch() {
		global $_GPC;
		set_time_limit(0);
		$ret = array();
		$url = $_GPC['url'];
		$pcate = intval($_GPC['pcate']);
		$ccate = intval($_GPC['ccate']);
		$tcate = intval($_GPC['tcate']);
		$depotid = intval($_GPC['depotid']);
		
		if (is_numeric($url)) {
			$itemid = $url;
		} else {
			preg_match("/id\=(\d+)/i", $url, $matches);
			if (isset($matches[1])) {
				$itemid = $matches[1];
			}
		}
		if (empty($itemid)) {
			die(json_encode(array("result" => 0, "error" => "未获取到 itemid!")));
		}
		$ret = $this->model->get_item_taobao($itemid, $_GPC['url'], $pcate, $ccate, $tcate,$depotid);
		plog('taobao.main', '淘宝抓取宝贝 淘宝id:' . $itemid);
		die(json_encode($ret));
	}

}
