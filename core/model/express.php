<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Express_EweiShopV2Model {

    /**
     * 获取快递列表
     */
    function getExpressList() {
        global $_W;

        $sql = 'select * from ' . tablename('ewei_shop_express') . ' where status=1 order by displayorder desc,id asc';
        $data = pdo_fetchall($sql);

        return $data;
    } 
}
