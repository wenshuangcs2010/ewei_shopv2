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

	/*function saveexpress() {

		global $_W, $_GPC;

		$id = intval($_GPC['id']);
		$express = $_GPC['express'];
		$expresscom = $_GPC['expresscom'];
		$expresssn = trim($_GPC['expresssn']);

		if (empty($id)) {
			show_json(0, "参数错误!");
		}

		if (empty($expresssn)) {
			show_json(0, "请填写快递单号!");
		}
		$change_data = array();
		$change_data['express'] = $express;
		$change_data['expresscom'] = $expresscom;
		$change_data['expresssn'] = $expresssn;
		pdo_update('ewei_shop_order', $change_data, array('id' => $id, 'uniacid' => $_W['uniacid']));
		show_json(1);
	}

	function saveaddress() {
		global $_W, $_GPC;
		$provance = $_GPC['provance'];
		$realname = $_GPC['realname'];
		$mobile = $_GPC['mobile'];
		$city = $_GPC['city'];
		$area = $_GPC['area'];
		$address = trim($_GPC['address']);
		$id = intval($_GPC['id']);


		if (empty($id)) {
			show_json(0, "参数错误！");
		}
		if (empty($realname)) {
			show_json(0, "请填写收件人姓名！");
		}

		if (empty($mobile)) {
			show_json(0, "请填写收件人手机！");
		}

		if ($provance == '请选择省份') {
			show_json(0, "请选择省份！");
		}

		if (empty($address)) {
			show_json(0, "请填写详细地址！");
		}

		$item = pdo_fetch("SELECT address FROM " . tablename('ewei_shop_order') . " WHERE id = :id and uniacid=:uniacid", array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$address_array = iunserializer($item['address']);

		$address_array['realname'] = $realname;
		$address_array['mobile'] = $mobile;
		$address_array['province'] = $provance;
		$address_array['city'] = $city;
		$address_array['area'] = $area;
		$address_array['address'] = $address;
		$address_array = iserializer($address_array);

		pdo_update('ewei_shop_order', array('address' => $address_array), array('id' => $id, 'uniacid' => $_W['uniacid']));

		show_json(1);
	}*/



}
