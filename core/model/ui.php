<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Ui_EweiShopV2Model
{

    function lazy($html = '')
    {
        $html = preg_replace_callback("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]?))[\\\'|\\\"].*?[\/]?>/", function ($matches) {
            return preg_replace("/src\=/", "data-lazy=", $matches[0]);
        }, $html);
        return $html;
    }
}
