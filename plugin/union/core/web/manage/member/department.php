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
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));


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
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and parent_id=0 and enable=1";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));


        if($_W['ispost']){
            $name=trim($_GPC['name']);
            $parent_id=intval($_GPC['parent_id']);
            $enable=intval($_GPC['enable']);
            //查询是否有重复的
            $repeat_id=pdo_fetchcolumn("select id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and name=:name",array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],'name'=>$name));

            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'name'=>$name,
                'parent_id'=>$parent_id,
                'enable'=>$enable,
            );
            if($id){
                if($repeat_id!=$id){
                    $this->model->show_json(0,"禁止出现重复部门");
                }
                pdo_update("ewei_shop_union_department",$data,array("id"=>$id));
                $this->model->show_json(1,"修改成功");
            }else{
                if($repeat_id){
                      $this->model->show_json(0,"禁止出现重复部门");
                }
                $data['addtime'] =time();
                pdo_insert("ewei_shop_union_department",$data);
                $this->model->show_json(1,"添加成功");
            }
        }
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where id=:id and union_id=:union_id",array(":id"=>$id,':union_id'=>$_W['unionid']));
        }

        include $this->template("member/departmentpost");
    }



}