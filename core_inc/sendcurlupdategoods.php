<?php

curl_init_post(1);

function curl_init_post($timeout){
    if (function_exists('curl_init') && function_exists('curl_exec') && $timeout > 0) {
        $url="https://wx.lylife.com.cn/addons/ewei_shopv2/core_inc/updateCnbuyerGoods.php";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($url,CURLOPT_HTTPHEADER,$headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        //$output = json_decode($output,true);
        var_dump($output);
        return $output;
    }
}