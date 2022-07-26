<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Department_EweiShopV2Page extends UnionWebPage
{
    public function main(){
        //计算还未看的用户
        global $_W;
        global $_GPC;
        $title="部门管理";
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid order by parent_id asc,displayorder desc";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");
        $category=$this->model->getLeaderArray($department);

        $column=array_column($category,'displayorder');
        array_multisort($column, SORT_DESC, $category);

        include $this->template();
    }

    public function add(){
        $action=unionUrl("member/department/add");
        $this->post($action);
    }
    public function edit(){
        $action=unionUrl("member/department/edit");
        $this->post($action);
    }
    public function post($action){
        $title="部门管理";
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where id=:id and union_id=:union_id",array(":id"=>$id,':union_id'=>$_W['unionid']));
            if($vo['parent_id']){
                $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_department')." where id=:id",array(":id"=>$vo['parent_id']));
            }
        }
        $parent_id=intval($_GPC['parent_id']);
        if(!empty($parent_id)){
            $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_department')." where id=:id",array(":id"=>$parent_id));
        }
        if($_W['ispost']){
            $name=trim($_GPC['name']);
            $parent_id=intval($_GPC['parent_id']);
            $enable=intval($_GPC['enable']);


            //查询上级的level

            $level=pdo_fetchcolumn("select level from ".tablename("ewei_shop_union_department")." where id=:parent_id",array(":parent_id"=>$parent_id));

            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'name'=>$name,
                'parent_id'=>$parent_id,
                'enable'=>$enable,
                'displayorder'=>$_GPC['displayorder'],
                'level'=>empty($level) ? 1 : $level+1,
            );
            if($id){


                pdo_update("ewei_shop_union_department",$data,array("id"=>$id));
                $this->model->show_json(1,"修改成功");
            }else{

                $data['addtime'] =time();
                pdo_insert("ewei_shop_union_department",$data);
                $this->model->show_json(1,"添加成功");
            }
        }

        include $this->template("member/departmentpost");
    }
    public function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if($id){
            pdo_delete("ewei_shop_union_department",array("id"=>$id));
            $this->model->show_json(1,"删除成功");
        }


    }


}