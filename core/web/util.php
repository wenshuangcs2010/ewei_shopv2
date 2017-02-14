<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Util_EweiShopV2Page extends WebPage {

	function autonum() {

		global $_W, $_GPC;
		$num = $_GPC['num'];
		$len = intval($_GPC['len']);
		$len == 0 && $len = 1;
		$arr = array($num);
		$maxlen = strlen($num);
		for ($i = 1; $i <= $len; $i++) {
			$add = bcadd($num, $i) . "";
			$addlen = strlen($add);
			if ($addlen > $maxlen) {
				$maxlen = $addlen;
			}
			$arr[] = $add;
		}
		$len = count($arr);
		for ($i = 0; $i < $len; $i++) {
			$zerocount = $maxlen - strlen($arr[$i]);
			if ($zerocount > 0) {
				$arr[$i] = str_pad($arr[$i], $maxlen, "0", STR_PAD_LEFT);
			}
		}
		die(json_encode($arr));
	}

	function days() {
		global $_W, $_GPC;
		//获取某月天数
		$year = intval($_GPC['year']);
		$month = intval($_GPC['month']);

		die(get_last_day($year, $month));
	}

	function express() {

		global $_W, $_GPC;
		$express = trim($_GPC['express']);
		$expresssn = trim($_GPC['expresssn']);
		$list = m('util')->getExpressList($express, $expresssn);
		include $this->template();
	}

}
