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
    	/*
        $html = preg_replace_callback("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]?))[\\\'|\\\"].*?[\/]?>/", function ($matches) {
            return preg_replace("/src\=/", "data-lazy=", $matches[0]);
        }, $html);*/
        global $_W;
        $html = preg_replace_callback("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]?))[\\\'|\\\"].*?[\/]?>/", function ($matches) use ($_W) {
            $images = $matches[0];
            $attachurl = str_replace(array('https://','http://'),'',$_W['attachurl_local']);
            if (strexists($images, $attachurl)){
                $image = $matches[1];
                $image = str_replace(array('https://','http://'),'',$image);
                $image = str_replace($attachurl,'',$image);


                $images = str_replace(array('https://','http://'),'',$images);
                $images = str_replace($attachurl,'',$images);

                $images = str_replace($image,tomedia($image),$images);

            }
            return preg_replace("/src=/", "data-lazy=", $images);
        }, $html);
        return $html;
    }
}
