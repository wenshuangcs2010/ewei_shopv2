<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Set_EweiShopV2Page extends PluginWebPage {

	function main() {

		global $_W,$_GPC;
		$form_list = $this->model->getDiyformList();
		if ($_W['ispost']) {
			ca('diyform.set.edit');
			$data = is_array($_GPC['setdata']) ? $_GPC['setdata'] : array();
			$this->updateSet($data);
			plog('diyform.set.edit', '修改基本设置');
			show_json(1);
		}
		$set = $this->set;
		include $this->template();
	}

}
