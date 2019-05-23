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
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_venue").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_venue").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
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
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_venue")." where id=:id",array(":id"=>$id));
        }

        if($_W['ispost']){

            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'add_time'=>time(),
                'enable'=>intval($_GPC['enable']),
            );
            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_venue",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_venue",$data);
            }
            $this->model->show_json(1,"ok");
        }
        include $this->template("venue/post");
    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_venue",array("is_delete"=>1),array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"ok");
    }

    function bookedlist(){
        global $_W;
        global $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where bl.uniacid=:uniacid and bl.union_id=:union_id  and bl.is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select bl.*,v.title,m.nickname from ".tablename("ewei_shop_union_venue_bookedlist")
            ." as bl LEFT JOIN ".tablename("ewei_shop_union_venue")." as v ON v.id =bl.venue_id ".
             " LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid=bl.openid".
            $condition;
        $sql.=" order by bl.create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_venue_bookedlist")." as bl ".$condition,$paras);
        $pager = pagination($total, $pindex, $psize);


        include $this->template("venue/bookedlist");
    }




}