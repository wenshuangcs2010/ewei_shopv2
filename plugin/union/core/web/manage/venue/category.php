<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Category_EweiShopV2Page extends UnionWebPage
{
     function main(){
         global $_W;
         global $_GPC;
         $pindex = max(1, intval($_GPC['page']));
         $psize = 20;
         $condition = ' and uniacid=:uniacid and union_id=:union_id';
         $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid'
         ]);
         if ($_GPC['status'] != '')
         {
             $condition .= ' and enable=' . intval($_GPC['status']);
         }
         if (!(empty($_GPC['keyword'])))
         {
             $_GPC['keyword'] = trim($_GPC['keyword']);
             $condition .= ' and catename  like :keyword';
             $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
         }
         $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_venue_category') . ' WHERE 1 ' . $condition . '  ORDER BY parent_id asc ', $params,"id");

         $category=$this->model->getLeaderArray($list);

         include $this->template();
     }


    function deletecategory(){
        global $_GPC;
        global $_W;
        $id=intval($_GPC['id']);
        $status=pdo_delete("ewei_shop_union_venue_category",array("id"=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        if($status){
            $this->model->show_json(1,'删除成功');
        }
        $this->model->show_json(1,'删除失败,请重试');
    }
    function editcategory(){
         $this->addcategory();
    }
    function addcategory(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $parent_id=intval($_GPC['parent_id']);
        if(!empty($id)){
            $vo=pdo_fetch("select * from ".tablename('ewei_shop_union_venue_category')." where id=:id",array(":id"=>$id));
            if($vo['parent_id']){
                $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_venue_category')." where id=:id",array(":id"=>$vo['parent_id']));
            }
        }
        if(!empty($parent_id)){
            $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_venue_category')." where id=:id",array(":id"=>$parent_id));
        }
        if($_W['ispost']){
            if($_GPC['parent_id']>0){

                $level=pdo_fetchcolumn("select level from ".tablename("ewei_shop_union_venue_category")." where 1 and id=:id",array(":id"=>$_GPC['parent_id']));
            }
            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'parent_id'=>intval($_GPC['parent_id']),
                'images'=>$_GPC['images'],
                'enable'=>intval($_GPC['enable']),
                'catename'=>trim($_GPC['catename']),
                'head_images'=>trim($_GPC['head_images']),
                'createtime'=>time(),
                'level'=>$_GPC['parent_id']>0 ? $level+1 :1,
            );
            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_venue_category",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_venue_category",$data);
            }
            $this->model->show_json(1,"添加修改成功");

        }

        include $this->template();
    }
}