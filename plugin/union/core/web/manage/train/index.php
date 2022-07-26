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
        $condition="  where uniacid=:uniacid and union_id=:union_id  and deleted=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_train").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_train").$condition,$paras);
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

        if($_W['ispost']){
            $start=$_GPC['start'];
            $end=$_GPC['end'];
            $signstart=$_GPC['signstart'];
            $signend=$_GPC['signend'];
            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'title'=>trim($_GPC['title']),
                'originator'=>trim($_GPC['originator']),
                'phone'=>trim($_GPC['phone']),
                'start_time'=>strtotime($start),
                "end_time"=>strtotime($end),
                "t_start_time"=>strtotime($signstart),
                "t_end_time"=>strtotime($signend),
                'declaration'=>trim($_GPC['declaration']),
                'count'=>trim($_GPC['count']),
                'status'=>$_GPC['status'],
            );

            if($id){
                pdo_update("ewei_shop_union_train",$data,array("id"=>$id));
            }else{
                $data['create_time']=time();
                pdo_insert("ewei_shop_union_train",$data);
            }
            $this->model->show_json(1,'数据处理成功');
        }
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_train")." where id=:id",array(":id"=>$id));
            $vo['start_time']=date("Y-m-d H:i:s",$vo['start_time']);
            $vo['end_time']=date("Y-m-d H:i:s",$vo['end_time']);
            $vo['a_start_time']=date("Y-m-d H:i:s",$vo['t_start_time']);
            $vo['a_end_time']=date("Y-m-d H:i:s",$vo['t_end_time']);
        }
        $status=$this->model->checkunion();
        include $this->template();
    }

    function showmember(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where a.uniacid=:uniacid and a.union_id=:union_id and am.train_id=:id";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$id);
        $sql="select am.*,m.name as m_name from ".tablename("ewei_shop_union_train_log")
            .' am LEFT JOIN '.tablename("ewei_shop_union_members") ." m ON am.openid=m.openid and am.union_id=m.union_id ".
            " LEFT JOIN ".tablename("ewei_shop_union_train")." as a ON a.id=am.train_id ".
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_train_log")." where  train_id=:id",array(':id'=>$id));
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
}