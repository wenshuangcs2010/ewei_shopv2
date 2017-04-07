<?php

	global $_config;
	require_once(EWEI_SHOPV2_TAX_CORE . "config.inc.php");
	$_config['erp']['key']=$stting['erp']['Key'];
	$_config['erp']['secret']=$stting['erp']['Secret'];
	$_config['erp']['userId']=$stting['erp']['UserId'];
	$_config['erp']['clientName']=$stting['erp']['clientName'];
	$_config['erp']['clientParssword']=$stting['erp']['clientParssword'];
	$_config['erp']['payParssword']=$stting['erp']['payParssword'];
	function md5_4($data) 
	{ 
		//先得到密码的密文
		$data = md5($data);
		//再把密文中的英文母全部转为大写
		$data = strtoupper($data);
		return $data;
	} 
