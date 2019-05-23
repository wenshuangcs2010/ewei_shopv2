<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{

    public $titletype=array(
        1=>'结婚',
        2=>'生育',
        3=>'住院',
        4=>'退休',
        5=>"丧葬",
    );
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $type=intval($_GPC['type']);
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 and type=:type and status>0";
        $title=$this->titletype[$type];
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':type'=>$type);
        $sql="select * from ".tablename("ewei_shop_union_welfare").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_welfare").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
    function add(){
        $this->post();
    }
    function edit(){
        $this->post();
    }
    function config(){
        global $_W;
        global $_GPC;

        $config_union=$this->model->get_union_welfare_config($_W['unionid']);
        $config=!empty($config_union)?iunserializer($config_union['config']):null;

        if($_W['ispost']){
            $data=array(
                'marry'=>intval($_GPC['marry']),
                'birth'=>intval($_GPC['birth']),
                'hospitalization'=>intval($_GPC['hospitalization']),
                'retire'=>intval($_GPC['retire']),
                'funeral'=>intval($_GPC['funeral']),
                'marry_moneytype'=>$_GPC['marry_moneytype'],
                'birth_moneytype'=>$_GPC['birth_moneytype'],
                'hospitalization_moneytype'=>$_GPC['hospitalization_moneytype'],
                'retire_moneytype'=>$_GPC['retire_moneytype'],
                'funeral_moneytype'=>$_GPC['funeral_moneytype']
            );

            if($config_union){
                $data=iserializer($data);
                $insertdata=array("config"=>$data);
                pdo_update("ewei_shop_union_welfare_config",$insertdata,array('uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                $data=iserializer($data);
                $insertdata=array("config"=>$data);
                $insertdata['uniacid']=$_W['uniacid'];
                $insertdata['union_id']=$_W['unionid'];
                pdo_insert("ewei_shop_union_welfare_config",$insertdata);
            }
            $this->message("修改成功",unionUrl("welfare/config"));
        }

        include $this->template();
    }
    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $type=$_GPC['type'];
        $title=$this->titletype[$type];
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_welfare")." where id=:id",array(":id"=>$id));
            if($vo){
                $vo['thumbs']=!empty($vo['images_url']) ? explode("|",$vo['images_url']):null;

            }
        }
        if($_W['ispost']){
            $strtotime=strtotime($_GPC['date']);
            $data=array(
                'name'=>trim($_GPC['name']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'add_time'=>time(),
                'type'=>$type,
                'images_url'=>trim($_GPC['life_images']),
                'money'=>trim($_GPC['money']),
                'time'=>$strtotime,
                'remarks'=>trim($_GPC['remarks']),
                'status'=>intval($_GPC['status']),
            );
            if(empty($_GPC['date'])){
                $this->model->show_json(0,"{$title}时间未填写");
            }
            $sql="select id from ".tablename("ewei_shop_union_members")." where name=:name and uniacid=:uniacid and union_id=:unionid";
            $member_id=pdo_fetchcolumn($sql,array(":name"=>$data['name'],':uniacid'=>$_W['uniacid'],':unionid'=>$_W['unionid']));
            $data['member_id']=$member_id;
            if(empty($member_id)){
                $this->model->show_json(0,"未查询到当前申请人");
            }
            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_welfare",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_welfare",$data);
            }
            $this->model->show_json(1,array("url"=>unionUrl("welfare/index",array('type'=>$type)),'message'=>"ok"));
        }
        include $this->template("welfare/post");
    }
    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_welfare",array("is_delete"=>1),array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"ok");
    }



}