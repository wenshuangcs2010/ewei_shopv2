<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where uniacid=:uniacid and union_id=:union_id  ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_adv").
            $condition;
        $sql.=" order by displayorder desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_adv").$condition,$paras);

        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
    function add(){
        $this->post();
    }
    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        if($id){
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id);
            $vo = pdo_fetch("select * from ".tablename("ewei_shop_union_adv")." where union_id =:union_id and uniacid =:uniacid and id=:id",$paras);
        }
        if($_W['ispost']){
            $data=array(
                'uniacid'=>$_W["uniacid"],
                'union_id'=>$_W['unionid'],
                'advname'=>trim($_GPC['advname']),
                'link'=>trim($_GPC['link']),
                'thumb'=>$_GPC['thumb'],
                'displayorder'=>$_GPC['displayorder'],
                'enabled'=>$_GPC['enabled'],
                'is_pc'=>intval($_GPC['is_pc']),
            );

            if($id){
                pdo_update("ewei_shop_union_adv",$data,array("id"=>$vo['id']));
            }else{
                pdo_insert("ewei_shop_union_adv",$data);
            }
            $this->model->show_json(1,'添加修改成功');
        }

        include $this->template();


    }
    function edit(){
        $this->post();
    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $status=pdo_delete("ewei_shop_union_adv",array("id"=>$id,"uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid']));

        if($status){
            $this->model->show_json(1,'成功删除');
        }
        $this->model->show_json(0,'删除失败');
    }
}