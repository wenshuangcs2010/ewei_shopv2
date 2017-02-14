<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class PluginModel {

	private $pluginname;
	private $set;

	public function __construct($name = '') {
		$this->pluginname = $name;
		$this->set = $this->getSet();
	}

	public function getSet() {
		if(empty($GLOBALS["_S"][$this->pluginname])){
			return m('common')->getPluginset( $this->pluginname);
		}
		return $GLOBALS["_S"][$this->pluginname];
	}

	public function updateSet($data = array()) {
		m('common')->updatePluginset(array($this->pluginname => $data));
	}

	function getName() {
		return pdo_fetchcolumn('select name from ' . tablename('ewei_shop_plugin') . ' where identity=:identity limit 1', array(':identity' => $this->pluginname));
	}

}
