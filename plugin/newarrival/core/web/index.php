<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN.'seckill/core/seckill_page_web.php';
class Index_EweiShopV2Page extends SeckillWebPage
{

    function main()
    {

        global $_W;
        if (cv('newarrival.task')) {
            header('location: ' . webUrl('newarrival/task'));
        } else if (cv('newarrival.goods')) {
            header('location: ' . webUrl('newarrival/goods'));
        } else if (cv('newarrival.category')) {
            header('location: ' . webUrl('newarrival/category'));
        } else if (cv('newarrival.adv')) {
            header('location: ' . webUrl('newarrival/adv'));
        } else if (cv('newarrival.calendar')) {
            header('location: ' . webUrl('newarrival/calendar'));
        } else if (cv('newarrival.cover')) {
            header('location: ' . webUrl('newarrival/cover'));
        }  else{
            header('location: ' . webUrl());
        }
    }
}
