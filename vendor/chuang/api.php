<?php

class Api{

    private static $app_account="";
    private static $app_password="";
    private static $api_send_url="";
    public function __construct($option)
    {

        self::$app_account = $option['clyzaccount'];
        self::$app_password = $option['clyzpassword'];
        self::$api_send_url = $option['clliuk'];
    }

    function init(){

    }

    function send($mobiel,$smssign,$params){

        //创蓝接口参数
        $postArr = array (
            'account'  =>  self::$app_account,
            'password' => self::$app_password,
            'msg' => urlencode($smssign.$params),
            'phone' => $mobiel,
            'report' => 'true',
        );

        load()->func('communication');
        $header=array(
            'Content-Type'=>"application/json; charset=utf-8",
        );
        $postFields = json_encode($postArr);
        $resp = ihttp_request(self::$api_send_url, $postFields,$header);
        $resp =json_decode($resp['content'],true);

        return $resp;
    }
}