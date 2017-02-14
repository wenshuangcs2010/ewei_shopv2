<?php

/*
 * 人人商城V2
 * 
 * @author ewei 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class DataModel  {

	public function read($key =''){
		global $_W,$_GPC;
		return m('cache')->getArray("data_".$_W['uniacid']."_".$key);
	}
	public function write($key,$data){
		global $_W,$_GPC;
		m('cache')->set("data_".$_W['uniacid']."_".$key,$data);
	}
}