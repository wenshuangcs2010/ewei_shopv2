<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage {

    function main()
    {
        header('location: ' . webUrl('mc/index/index'));
        exit;
    }

    function index()
    {
        global $_W;
        include $this->template();
    }

}
