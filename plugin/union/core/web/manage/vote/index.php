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

        if(!empty($_GPC['keywordes'])){
            $condition.=" and title like :keywordes ";
            $paras[':keywordes']="%".trim($_GPC['keywordes'])."%";
        }


        $sql="select * from ".tablename("ewei_shop_union_vote_activity").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_vote_activity").$condition,$paras);
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
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_activity")." where id=:id",array(":id"=>$id));
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
                'start_time'=>strtotime($start),
                'end_time'=>strtotime($end),
                'createtime'=>time(),
                'declaration'=>trim($_GPC['declaration']),
                'activity_headimg'=>trim($_GPC['images']),
            );
            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_vote_activity",$data,array('id'=>$id));
            }else{
                pdo_insert("ewei_shop_union_vote_activity",$data);
            }
            $this->model->show_json(1,"ok");
        }


        include $this->template("vote/post");
    }
}