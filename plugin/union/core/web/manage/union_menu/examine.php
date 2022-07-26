<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Examine_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_union_examine")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        foreach ($list as $item){
            $item['optionlist']=iunserializer($item['optionlist']);
        }

        include $this->template();
    }

    function add(){
        $this->post();
    }
    function edit(){
        $this->post();
    }
    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_examine")." where id=:id",array(":id"=>$id));
            if($vo){
                $vo['optionlist']=json_decode($vo['optionlist'],true);
            }

        }
        if($_W['ispost']){
            $open=empty($_GPC['open']) ? "OFF" :$_GPC['open'];
            $addoption=$_GPC['addoption'];
            $addname=$_GPC['addname'];
            if($open=="OFF" && empty($addoption[0])){
                $this->model->show_json(0,'请选择一级审核人');
            }

            $optionlist=array();
            foreach ($addoption as $key=> $item){
                if(empty($item)){
                    $level=$key+1;
                    $this->model->show_json(0,"请选择{$level}级审核人");
                }
                $optionlist[]=array(
                    'level'=>$key,
                    'memberlist'=>$item,
                    'namelist'=>$addname[$key],
                );
            }

            $data=array(
                'displayorder'=>intval($_GPC['displayorder']),
                'catename'=>trim($_GPC['catename']),
                'enable'=>intval($_GPC['enable']),
                'optionlist'=>json_encode($optionlist),
                'open'=>$open,
            );

            if($id){
                pdo_update("ewei_shop_union_examine",$data,array("id"=>$vo['id']));
            }else{
                $data['uniacid']=$_W['uniacid'];
                $data['union_id']=$_W['unionid'];
                pdo_insert("ewei_shop_union_examine",$data);
            }
            $this->model->show_json(1,"流程添加成功");
        }
        include $this->template();
    }
}