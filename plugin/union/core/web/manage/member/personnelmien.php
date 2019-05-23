<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Personnelmien_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where m.uniacid=:uniacid and m.union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select m.*,d.name as dname from ".tablename("ewei_shop_union_personnelmien")." as m LEFT JOIN ".
            tablename("ewei_shop_union_department")." as d ON d.id=m.department_id".
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_personnelmien")." as m ".$condition,$paras);


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
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
        if($id){
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id);
            $title="修改";
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_personnelmien")." where id =:id and uniacid=:uniacid and union_id=:union_id",$paras);
        }else{
            $title="添加";
        }
        if($_W['ispost']){

            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'title'=>trim($_GPC['title']),
                'teamname'=>trim($_GPC['teamname']),
                'description'=>htmlspecialchars_decode($_GPC['description']),
                'create_time'=>time(),
                'update_time'=>time(),
                'is_publish'=>intval($_GPC['is_publish']),
                'displayorder'=>intval($_GPC['displayorder']),
                'department_id'=>intval($_GPC['department_id']),
                'header_imageurl'=>$_GPC['images']
            );
            if($id){
                unset($data['create_time']);

                pdo_update("ewei_shop_union_personnelmien",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_personnelmien",$data);
            }
            $this->message("数据处理成功",unionUrl("member/personnelmien"));
        }
        include $this->template("member/personnelmien_post");
    }
}