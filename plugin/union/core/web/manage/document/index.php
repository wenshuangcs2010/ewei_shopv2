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
        $condition="  where uniacid=:uniacid and union_id=:union_id  and isdelete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select id,title,add_time from ".tablename("ewei_shop_union_document").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_document").$condition,$paras);


        $pager = pagination($total, $pindex, $psize);
        include $this->template("document/index");
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
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id);
            $title="修改公文";
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_document")." where id =:id and uniacid=:uniacid and union_id=:union_id",$paras);
        }else{
            $title="添加公文";
        }

        
        if($_W['ispost']){
            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'title'=>trim($_GPC['title']),
                'enclosure_url'=>trim($_GPC['docurl']),
                'description'=>htmlspecialchars_decode($_GPC['description']),
                'add_time'=>time(),
            );
            if($id){
                if(empty($_GPC['docurl'])){
                    unset($data['enclosure_url']);
                    unset($data['add_time']);
                }
                pdo_update("ewei_shop_union_document",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_document",$data);
            }
            $this->message("数据处理成功",unionUrl("document"));
        }
        include $this->template("document/post");
    }

}