<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class PluginPfMobilePage extends Page {

    public $model;
    public $set;
    public function __construct() {

        m('shop')->checkClose();
        $this->model = m('plugin')->loadModel($GLOBALS["_W"]['plugin']);
        $this->set = $this->model->getSet();
    }
		
	public function getSet(){
		return $this->set;
	}
   
}
