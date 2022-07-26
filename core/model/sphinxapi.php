<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once (EWEI_SHOPV2_VENDOR."sphinx/sphinxapi.php");
class Sphinxapi_EweiShopV2Model {
    static $sphinxapi;
    function searchkey($key){
        self::$sphinxapi->SetServer('172.16.52.93',9312);
        self::$sphinxapi->SetMatchMode(SPH_MATCH_ANY);
        self::$sphinxapi->SetArrayResult ( true );

        $result = self::$sphinxapi->Query($key,'*');
        var_dump($result);

        // 避免没有匹配记录时报错
        if(empty($result['matches'])) {
            return null;
        }
        $result = $result['matches'];
        $result = array_column($result, 'id');
        return $result;
    }

    public function __construct()
    {
        $this->init();
    }
    function init(){
        if(is_null(self::$sphinxapi)){
            self::$sphinxapi= new SphinxClient();
        }
    }
}