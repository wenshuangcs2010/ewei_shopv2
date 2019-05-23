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
        $sql="select * from ".tablename("ewei_shop_union_activity").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_activity").$condition,$paras);
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
        $memberlist=pdo_fetchall("select * from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));


        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_activity")." where id=:id",array(":id"=>$id));
        if($vo){
            $vo['start_time']=date("Y-m-d H:i:s",$vo['starttime']);
            $vo['end_time']=date("Y-m-d H:i:s",$vo['endtime']);
            $vo['signstarttime']=date("Y-m-d H:i:s",$vo['signstarttime']);
            $vo['signendtime']=date("Y-m-d H:i:s",$vo['signendtime']);
            $vo['partpeople']=@json_decode($vo['partpeople'],true);
            $vo['imppeople']=@json_decode($vo['imppeople'],true);
        }


        if($_W['ispost']){
            $imppeople=$_GPC['imppeople'];
            $partpeople=$_GPC['partpeople'];
            $starttime=$_GPC['start'];
            $end=$_GPC['end'];
            if(empty($starttime) || empty($end)){
                $this->model->show_json(0,'活动时间有误');
            }
            $starttime=strtotime(trim($starttime));
            $endtime=strtotime(trim($end));

            if($starttime>$endtime){
                $this->model->show_json(0,'开始时间不能大于活动结束时间');
            }
            $sginstarttime=strtotime(trim($_GPC['signstart']));
            $sginendtime=strtotime(trim($_GPC['signend']));

            if(empty($sginstarttime) || empty($sginendtime)){
                $this->model->show_json(0,'签到活动时间有误');
            }
            if($sginstarttime>$sginendtime){
                $this->model->show_json(0,'签到开始时间不能大于签到结束时间');
            }

            if(empty($starttime) || empty($end)){
                $this->model->show_json(0,'活动时间有误');
            }
            if(empty($imppeople)){
                $this->model->show_json(0,'参会人员未指定');
            }
            if(empty($partpeople)){
                $this->model->show_json(0,'执行人员未指定');
            }

            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'add_time'=>time(),
                'declaration'=>trim($_GPC['declaration']),
                'starttime'=>strtotime(trim($_GPC['start'])),
                'endtime'=>strtotime(trim($_GPC['end'])),
                'signstarttime'=>$sginstarttime,
                'signendtime'=>$sginendtime,
                'address'=>trim($_GPC['address']),
                'declaration'=>trim($_GPC['declaration']),
                'partpeople'=>json_encode($partpeople),
                'imppeople'=>json_encode($imppeople),
                'status'=>intval($_GPC['status']),
            );

            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_activity",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_activity",$data);
            }
            $this->model->show_json(1,array("url"=>unionUrl("activity"),'message'=>"ok"));
        }
        include $this->template("activity\post");
    }
    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_activity",array("is_delete"=>1),array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"ok");
    }

    function showpeople(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where a.uniacid=:uniacid and a.union_id=:union_id and am.activity_id=:id";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$id);
        $sql="select am.*,m.name as m_name from ".tablename("ewei_shop_union_activity_signmember")
            .' am LEFT JOIN '.tablename("ewei_shop_union_members") ." m ON am.member_id=m.id".
            " LEFT JOIN ".tablename("ewei_shop_union_activity")." as a ON a.id=am.activity_id ".
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_activity_signmember")." where  activity_id=:id",array(':id'=>$id));
        $pager = pagination($total, $pindex, $psize);
        include $this->template("activity/memberlist");
    }
    function status(){
        global $_W;
        global $_GPC;
        $status=intval($_GPC['status']);
        $id=intval($_GPC['id']);

    }
    function notice(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where n.uniacid=:uniacid and n.union_id=:union_id  and n.is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select n.*,a.title as a_title from ".tablename("ewei_shop_union_notice")." as n LEFT JOIN ".tablename("ewei_shop_union_activity")." as a ON a.id=n.activity_id ".
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_notice").' n '.$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("activity/notice");
    }
    function noticeadd(){
        global $_W;
        global $_GPC;
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_activity").
            $condition;
        $assolist=pdo_fetchall($sql,$paras);
        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'declaration'=>trim($_GPC['declaration']),
                'uniacid'=>intval($_W['uniacid']),
                'union_id'=>intval($_W['unionid']),
                'activity_id'=>intval($_W['activity_id']),
                'create_time'=>time(),
            );
            pdo_insert("ewei_shop_union_notice",$data);
            $this->model->show_json(1,array("url"=>unionUrl("activity/notice",'activity/notice')));
        }
        include $this->template("activity/noticeadd");
    }
    function noticedelete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_activity",array('is_delete'=>1),array('id'=>$id));
        $this->model->show_json(1,array("url"=>unionUrl("activity/notice",'activity/notice')));
    }
}