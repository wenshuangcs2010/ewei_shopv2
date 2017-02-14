<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Plugin_EweiShopV2Model {

	/**
	 * 判断是否有插件
	 * @param type $pluginName
	 */
	public function exists($pluginName = '') {

		$dbplugin = pdo_fetchall('select * from ' . tablename('ewei_shop_plugin') . ' where identity=:identyty limit  1', array(':identity' => $pluginName));
		if (empty($dbplugin)) {
			return false;
		}
		return true;
	}

	/**
	 * 获取所有插件
	 * @return type
	 */
	public function getAll($iscom = false,$status='') {
		global $_W;
		$plugins = '';
		if ($status !== '')
		{
			$status = 'and status = '.intval($status);
		}
		if ($iscom) {
			$plugins = m('cache')->getArray('coms2', "global");
			if (empty($plugins)) {
				$plugins = pdo_fetchall('select * from ' . tablename('ewei_shop_plugin') . ' where iscom=1 and deprecated=0 '.$status.' order by displayorder asc');
				m('cache')->set('coms2', $plugins, "global");
			}
		} else {
			$plugins = m('cache')->getArray('plugins2', "global");
			if (empty($plugins)) {
				$plugins = pdo_fetchall('select * from ' . tablename('ewei_shop_plugin') . ' where iscom=0 and deprecated=0 '.$status.' order by displayorder asc');
				m('cache')->set('plugins2', $plugins, "global");
			}
		}
		return $plugins;
	}

	public function refreshCache($status='',$iscom = false) {
		if ($status !== '')
		{
			$status = 'and status = '.intval($status);
		}
		$com = pdo_fetchall('select * from ' . tablename('ewei_shop_plugin') . ' where iscom=1 and deprecated=0 '.$status.' order by displayorder asc');
		m('cache')->set('coms2', $com, "global");

		$plugins = pdo_fetchall('select * from ' . tablename('ewei_shop_plugin') . ' where iscom=0 and deprecated=0 '.$status.' order by displayorder asc');
		m('cache')->set('plugins2', $plugins, "global");

		if ($iscom)
		{
			return $com;
		}
		else
		{
			return $plugins;
		}
	}

	public function getList($status='') {

		$list = $this->getCategory();
		$plugins = $this->getAll(false,$status);

		foreach ($list as $ck => &$cv) {

			$ps = array();
			foreach ($plugins as $p) {
				if ($p['category'] == $ck) {
					$ps[] = $p;
				}
			}
			$cv['plugins'] = $ps;
		}
		unset($cv);
		return $list;
	}

	public function getName($identity = '') {

		$plugins = $this->getAll();

		foreach ($plugins as $p) {
			if ($p['identity'] == $identity) {
				return $p['name'];
			}
		}
		return '';
	}

	public function loadModel($pluginname = '') {

		static $_model;
		if (!$_model) {
			$modelfile = IA_ROOT . '/addons/ewei_shopv2/plugin/' . $pluginname . "/core/model.php";

			if (is_file($modelfile)) {
				$classname = ucfirst($pluginname) . "Model";
				require_once EWEI_SHOPV2_CORE . "inc/plugin_model.php";
				require_once $modelfile;
				$_model = new $classname($pluginname);
			}
		}

		return $_model;
	}

	public function getCategory() {
		return array(
			"biz" => array('name' => '业务类'),
			"sale" => array('name' => "营销类"),
			"tool" => array('name' => "工具类"),
			"help" => array('name' => "辅助类")
		);
	}

}
