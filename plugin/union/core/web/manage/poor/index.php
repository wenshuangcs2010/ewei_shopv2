<?php

if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where uniacid=:uniacid and unionid=:unionid  and is_deleted=0 ";
        $paras=array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid']);
        $sql="select * from ".tablename("ewei_shop_union_poor").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $articlelist = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_poor").$condition,$paras);


        $pager = pagination($total, $pindex, $psize);

        include $this->template();
    }
    public function add(){
        $this->post();
    }

    public function edit(){
        $this->post();
    }
    public function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_poor")." where unionid=:unionid and uniacid=:uniacid and id=:id",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid'],":id"=>$id));
        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'description'=>htmlspecialchars_decode($_GPC['description']),
                'createtime'=>time(),
                'header_image'=>$_GPC['header_image'],
                'is_show'=>intval($_GPC['is_show']),
            );
            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_poor",$data,array("id"=>$id));
            }else{
                $data['unionid']=$_W['unionid'];
                $data['uniacid']=$_W['uniacid'];
                pdo_insert("ewei_shop_union_poor",$data);
            }
            $this->message("数据添加保存成功",unionUrl('poor'));
        }
        include $this->template();
    }
    public function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
    }
}