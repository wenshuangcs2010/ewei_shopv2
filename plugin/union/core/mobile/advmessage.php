<?php

if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Advmessage_EweiShopV2Page extends UnionMobilePage
{
    function main(){

    }

    function detail(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(empty($id)){
            $this->message("非法参数禁止访问");
        }
        $sql=  "select * from ".tablename("ewei_shop_union_advmessage")." where uniacid=:uniacid and status=1 and id=:id order by displayorder desc limit 1";
        $notice=pdo_fetch($sql,array(":uniacid"=>$_W['uniacid'],':id'=>$id));
        $notice['detail']= m('ui')->lazy($notice['detail']);
        include $this->template();
    }
}