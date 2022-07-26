<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Secondlevel_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $menu_index=pdo_fetchall("select * from ".tablename("ewei_shop_union_index")." where uniacid=:uniacid and union_id=:unionid",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid']));
        if(empty($menu_index)){
            $menu_index=array(
                0=>array("type"=>1,'title'=>"公会动态"),
                1=>array("type"=>2,'title'=>"会员活动"),
            );

            foreach ($menu_index as $index){
                $data=array(
                    'status'=>0,
                    'union_id'=>$_W['unionid'],
                    'uniacid'=>$_W['uniacid'],
                    'title'=>$index['title'],
                    'type'=>$index['type'],
                );
                pdo_insert("ewei_shop_union_index",$data);
            }
        }

        include $this->template();
    }
    function edit(){
        $this->post();
    }

    function add(){
        $this->post();
    }

    function post(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        if($type==1){
            //文章类的
            $categorylist = pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");

        }

        if($type==2){
            //文章类的
            $categorylist = pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");

        }
        $params[':type']=$type;
        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_index")." where uniacid=:uniacid and union_id=:union_id and type=:type",$params);
        if($_W['ispost']){


            $data=array(
                'status'=>$_GPC['status'],
                'type'=>$type,
                'cate_id'=>$_GPC['cate_id'],
            );
            if(empty($vo)){
                $data['union_id']=$_W['unionid'];
                $data['uniacid']=$_W['uniacid'];
                $data['title']=$type==1 ? "公会动态" :"会员活动";

                pdo_insert('ewei_shop_union_index',$data);
            }else{
                pdo_update('ewei_shop_union_index',$data,array("type"=>$type,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }
            $this->model->show_json(1,'修改成功');
        }


        include $this->template();
    }



}