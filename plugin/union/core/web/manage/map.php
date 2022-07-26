<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Map_EweiShopV2Page extends UnionWebPage
{
    function main(){
        include $this->template();
    }
}