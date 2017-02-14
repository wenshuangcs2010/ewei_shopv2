<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Qiniu_EweiShopV2Page extends ComWebPage {

	public function __construct($_com = 'qiniu')
	{
		parent::__construct($_com);

	}
	
	public function main() {
		global $_W, $_GPC;
 
		if($_W['ispost']){
			$data = is_array($_GPC['data'])?$_GPC['data']:array();
			 
			if($data['upload']){
				$check = com('qiniu')->save('addons/ewei_shopv2/static/images/nopic100.jpg',$data);
				if( empty($check)){
					show_json(0, "您提交的七牛配置参数有误，请核对后重试!");
				}
			}
			m('common')->updateSysset(array('qiniu'=>array('user'=>$data)));
			plog('sysset.qiniu.edit','保存七牛设置');
			show_json(1);
		}
		$qiniu = m('common')->getSysset('qiniu');
		$data= $qiniu['user'];
		include $this->template();
	}

}
