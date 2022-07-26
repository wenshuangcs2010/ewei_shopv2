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
        $condition="  where w.uniacid=:uniacid and w.union_id=:union_id  and w.is_delete=0 and w.type=:type and w.status>0";
        $title=$this->titletype[$type];
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':type'=>$type);
        $sql="select w.*,d.name as dename,m.mobile_phone from ".tablename("ewei_shop_union_welfare")." as w ".
            " LEFT JOIN  ".tablename("ewei_shop_union_members")." as m  ON m.openid=w.openid and m.union_id=w.union_id ".
            " LEFT JOIN  ".tablename("ewei_shop_union_department")." as d  ON m.department=d.id and m.union_id=d.union_id ".
            $condition;
        if($_GPC['export']!=1){
            $sql.=" order by w.add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }

        $list = pdo_fetchall($sql, $paras);

        if($_GPC['export']==1){
            foreach ($list as $key=> $value){
                if($value['status']!=2){
                    unset($list[$key]);
                }else{
                    $list[$key]['typename']=$this->titletype[$type];
                    $list[$key]['unionname']=$_W['unionuser']['title'];

                }

            }
            $params_export=array(
                "title" => $title."(福利待发放)数据导出",
                "columns" => array(
                    array('title' => '单位', 'field' => 'unionname', 'width' => 12),
                    array('title' => '部门', 'field' => 'dename', 'width' => 12),
                    array('title' => '申请人', 'field' => 'name', 'width' => 12),
                    array('title' => '联系电话', 'field' => 'mobile_phone', 'width' => 12),
                    array('title' => '金额', 'field' => 'money', 'width' => 12),
                    array('title' => '开户行', 'field' => 'bankname', 'width' => 12),
                    array('title' => '银行卡号', 'field' => 'bankcard', 'width' => 12),
                    array('title' => '备注', 'field' => 'remarks', 'width' => 12),

                )
            );
            m('excel')->export($list,$params_export);
            exit;
        }

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_welfare").' as w '.$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
    function show(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $openid=$_W['openid'];
        $_W['union']['title']="福利详情";
        $params=array(
            ':id'=>intval($_GPC['id']),
            ':uniacid'=>$uniacid,
            ':union_id'=>$union_id,
        );
        $item=pdo_fetch("select * from ".tablename('ewei_shop_union_welfare')." where id=:id and uniacid=:uniacid and union_id=:union_id  ",$params);

        if(empty($item)){
            $this->message("数据错误");
        }
        $images=array();
        if($item['images_url']){
            $images=explode("|",$item['images_url']);
        }
        if($item['status']==0){
            $item['status']="待申请";
        }elseif($item['status']==1){
            $item['statusmessage']="审核中";
        }elseif($item['status']==2){
            $item['statusmessage']="审核通过";
        }elseif($item['status']==3){
            $item['statusmessage']="驳回申请";
        }elseif($item['status']==4){
            $item['statusmessage']="审核拒绝";
        }
        elseif($item['status']==5){
            $item['statusmessage']="已完成";
        }
        $union_info=$this->model->get_union_info($union_id);
        $member=$this->model->get_union_member_info($item['openid']);
        $sql_groups="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id and id=:groupid";
        $groups_info=pdo_fetch($sql_groups,array(":union_id"=>$member['union_id'],":uniacid"=>$member['uniacid'],':groupid'=>$member['uniongroupid']));
        $department=$this->model->get_department_info($uniacid,$union_id,$member['department']);
        $typename=$title=$this->titletype[$item['type']];

        //查询审核记录

        $sql="select * from ".tablename("ewei_shop_union_examine_log")." where welfareid=:welfareid order by `level` asc";
        $examinelist=pdo_fetchall($sql,array(":welfareid"=>$item['id']));

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

        $examine_list=pdo_fetchall("select * from ".tablename("ewei_shop_union_examine")." where uniacid=:uniacid and union_id=:union_id and enable=1 ",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid']));



        if($_W['ispost']){
            $data=array(
                'welfarestatus'=>intval($_GPC['welfarestatus']),
                'marry'=>intval($_GPC['marry']),
                'birth'=>intval($_GPC['birth']),
                'hospitalization'=>intval($_GPC['hospitalization']),
                'retire'=>intval($_GPC['retire']),
                'funeral'=>intval($_GPC['funeral']),
                'marry_moneytype'=>$_GPC['marry_moneytype'],
                'birth_moneytype'=>$_GPC['birth_moneytype'],
                'hospitalization_moneytype'=>$_GPC['hospitalization_moneytype'],
                'retire_moneytype'=>$_GPC['retire_moneytype'],
                'funeral_moneytype'=>$_GPC['funeral_moneytype'],
                'marry_total'=>$_GPC['marry_total'],
                'birth_total'=>$_GPC['birth_total'],
                'hospitalization_total'=>$_GPC['hospitalization_total'],
                'retire_total'=>$_GPC['retire_total'],
                'funeral_total'=>$_GPC['funeral_total'],
                'examine'=>$_GPC['examine'],
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
                $vo['time']=date("Y-m-d",$vo['time']);

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
                'bankname'=>trim($_GPC['bankname']),
                'bankcard'=>trim($_GPC['bankcard']),
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