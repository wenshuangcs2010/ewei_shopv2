<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Fcard_EweiShopV2Page extends WebPage
{
	 function main()
    {
    	global $_W,$_GPC;
    	$unitlist=m("unit")->getUnitList();
    	if (empty($starttime) || empty($endtime)) {
			$starttime = $endtime = time();
		}
		if ($_W['ispost']) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			if($_GPC['unitid']==-1){
				$unitid=0;
			}else{
				$unitid=$_GPC['unitid'];
			}
			$result = m('unit')->downloadbill($starttime, $endtime, $unitid);
			if (is_error($result)) {
				$this->message($result['message'], '', 'error');
			}
			plog('finance.downloadbill.main',"下载对账单");
		}
    	load()->func('tpl');

		include $this->template();
    }
}