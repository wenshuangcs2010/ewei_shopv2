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

        if($_GPC['keywordes']!=''){
            $condition.=" and name like '%".$_GPC['keywordes']."%' ";

        }


        $sql="select * from ".tablename("ewei_shop_union_friendship").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_friendship").$condition,$paras);
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
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_friendship")." where id=:id",array(":id"=>$id));
            if($vo){
                $vo['thumbs']=explode("|",$vo['life_images']);

            }
        }

        if($_W['ispost']){
            $life_image=explode("|",$_GPC['life_images']);
            foreach ($life_image as $key=>$li){
                if(empty($li)){
                    unset($life_image[$key]);
                }
            }

            $data=array(
                'name'=>trim($_GPC['name']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'add_time'=>time(),
                'header_imageurl'=>trim($_GPC['images']),
                'sex'=>intval($_GPC['sex']),
                'age'=>intval($_GPC['age']),
                'maritalstatus'=>intval($_GPC['maritalstatus']),
                'height'=>intval($_GPC['height']),
                'income'=>trim($_GPC['income']),
                'education'=>trim($_GPC['education']),
                'address'=>trim($_GPC['address']),
                'work'=>trim($_GPC['work']),
                'character'=>trim($_GPC['character']),
                'other'=>trim($_GPC['other']),
                'additional'=>trim($_GPC['additional']),
                'contact'=>trim($_GPC['contact']),
                'otherage'=>trim($_GPC['otherage']),
                'othercondition'=>trim($_GPC['othercondition']),
                'otheradditional'=>trim($_GPC['otheradditional']),
                'declaration'=>trim($_GPC['declaration']),
                'verification'=>intval($_GPC['verification']),
                'life_images'=>implode("|",$life_image),
            );

            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_friendship",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_friendship",$data);
            }
            $this->model->show_json(1,"ok");
        }
        include $this->template("friendship/post");
    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_friendship",array("is_delete"=>1),array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
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