<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Recuperation_EweiShopV2Page extends UnionMobilePage
{
    function main(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="医疗修养";
        $articlelist=pdo_fetchall("select * from ".tablename("ewei_shop_union_recuperation")." where unionid=:unionid and uniacid=:uniacid and is_deleted=0 and is_show=1",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid']));
        include $this->template();
    }
    function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $article=pdo_fetch("select * from ".tablename("ewei_shop_union_recuperation")." where unionid=:unionid and uniacid=:uniacid and is_deleted=0 and id=:id",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid'],":id"=>$id));

        $_W['union']['title']=$article['title'];
        if($article){
            pdo_update('ewei_shop_union_recuperation',array("click_count"=>$article['click_count']+1),array("id"=>$article['id']));
        }


        include $this->template();
    }

}