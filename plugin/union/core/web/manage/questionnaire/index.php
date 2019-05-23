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
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 and type=1";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_questionnaire").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_questionnaire").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("questionnaire/index");
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
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
        $id=intval($_GPC['id']);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_questionnaire")." where id=:id",array(":id"=>$id));
            $vo['start_time']=date("Y-m-d H:i:s",$vo['start_time']);
            $vo['end_time']=date("Y-m-d H:i:s",$vo['end_time']);
        }

        if($_W['ispost']){
            $start=$_GPC['start'];
            $end=$_GPC['end'];
            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'type'=>1,
                'start_time'=>strtotime($start),
                'end_time'=>strtotime($end),
                'add_time'=>time(),
                'status'=>intval($_GPC['status']),
                'target'=>intval($_GPC['department_id']),
            );
            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_questionnaire",$data,array('id'=>$id));
            }else{
                pdo_insert("ewei_shop_union_questionnaire",$data);
            }
            $this->model->show_json(1,"ok");
        }
        include $this->template("questionnaire/post");
    }
    function addquest(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        include $this->template();
    }



}