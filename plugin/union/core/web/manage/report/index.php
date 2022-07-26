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


        if($_GPC['keywordes']!=''){

            $condition.=" and title like '%".$_GPC['keywordes']."%'";
        }
        $sql="select * from ".tablename("ewei_shop_union_report").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);



        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_report").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $memberlist=pdo_fetchall("select id,name from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and status=1 and activate=1 and union_id=:union_id ",array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));

        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_report")." where id=:id",array(":id"=>$id));
            $vo['starttime']=date("Y-m-d H:i:s",$vo['starttime']);
            $vo['endtime']=date("Y-m-d H:i:s",$vo['endtime']);
            $vo['peoplevale']=explode(",",$vo['peoplevale']);
        }
        if($_W['ispost']){
            $start=$_GPC['starttime'];
            $end=$_GPC['endtime'];
            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'starttime'=>strtotime($start),
                'endtime'=>strtotime($end),
                'create_time'=>time(),
                'status'=>intval($_GPC['status']),
                'declaration'=>trim($_GPC['declaration']),
                'originator'=>trim($_GPC['originator']),
                'phone'=>trim($_GPC['phone']),
                'show_type'=>intval($_GPC['show_type']),
                'sign_type'=>intval($_GPC['sign_type']),
                'credit'=>intval($_GPC['credit']),
                'has_points'=>intval($_GPC['has_points']),
                'peoplevale'=>!empty($_GPC['peoplevale'])?implode(',',$_GPC['peoplevale']):"",
            );

           if($id){
               unset($data['sign_type']);
               unset($data['has_points']);
               pdo_update("ewei_shop_union_report",$data,array("id"=>$vo['id']));
           }else{
               pdo_insert("ewei_shop_union_report",$data);
           }
           $this->model->show_json(1,"添加修改数据成功");
        }
        include $this->template();
    }
    function add(){
        $this->post();
    }
    function edit(){
        $this->post();
    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $status=pdo_delete("ewei_shop_union_report",array("id"=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));

        if($status){
            $this->model->show_json(1,'删除成功');
        }
        $this->model->show_json(1,'删除失败,请重试');
    }

    function createqrcode(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(empty($id)){
            $this->model->show_json(1,"参数错误");
        }
        $url=mobileUrl("union/report",array("id"=>$id,'union_id'=>$_W['unionid']),true);
        $files=m("qrcode")->createQrcode($url);
        header('location: ' . $files);
    }

    function showpeople(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $id=intval($_GPC['id']);
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$_W['unionid'],
            ':id'=>$id,
        );
        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_report")." where id=:id",array(":id"=>$id));
        $sql="select s.id,s.name,s.createtime,s.mobile as mobile_phone,dep.name as depname from "
            .tablename("ewei_shop_union_report_sign")." as s"
            ." LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON  unm.openid=s.openid and unm.union_id=s.union_id  "
            ." LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON dep.id=unm.department  "
            ."  where 1 "
            ." and s.uniacid=:uniacid and s.union_id=:union_id and s.report_id=:id  ";


        if($_GPC['export']!=1){
            $sql.=" LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }

        $list = pdo_fetchall($sql, $params);



        if($_GPC['export']==1){

            foreach ($list as $key=>$item){
                $list[$key]['createtime']=date("Y-m-d H:i:s",$item['createtime']);
            }

            $pValue = mb_substr($vo['title'], 0, 21);
            $params_export=array(
                "title" => "签到数据-".$pValue,
                "columns" => array(

                    array('title' => '处室/部门', 'field' => 'depname', 'width' => 12),
                    array('title' => '姓名', 'field' => 'name', 'width' => 12),
                    array('title' => '手机号', 'field' => 'mobile_phone', 'width' => 12),
                    array('title' => '签到时间', 'field' => 'createtime', 'width' => 24),
                )
            );

            m('excel')->export($list,$params_export);
            exit;
        }
        $countsql="select count(*) from ".tablename('ewei_shop_union_report_sign')." as s where 1 ".
            " and s.uniacid=:uniacid and s.union_id=:union_id and s.report_id=:id";

        $total = pdo_fetchcolumn($countsql,$params);
        $pager = pagination($total, $pindex, $psize);
        if($vo['show_type']==1){
            //查询指定人选中未签到的人员
            $peoplevale=explode(",",$vo['peoplevale']);
            $sql="select unm.id,unm.name,s.createtime,unm.mobile_phone from ".tablename("ewei_shop_union_members")
                ." as unm  LEFT JOIN ".tablename("ewei_shop_union_report_sign")." as s ON unm.openid=s.openid and s.report_id=:id  where 1 "
                ." and unm.uniacid=:uniacid and unm.union_id=:union_id  and s.openid is NULL and unm.id in(".$vo['peoplevale'].")";

            $notinlist = pdo_fetchall($sql, $params);

        }

        $notsignlist="";

        include $this->template();
    }
}