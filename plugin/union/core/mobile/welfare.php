<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Welfare_EweiShopV2Page extends UnionMobilePage
{
    public $titletype=array(
        1=>'结婚',
        2=>'生育',
        3=>'住院',
        4=>'退休',
        5=>"丧葬",
    );
    public function main()
    {
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $union_id=$_W['unionid'];
        $parmconfig=$this->model->get_config($union_id);
        if($parmconfig && isset($parmconfig['config'])){
            $row=iunserializer($parmconfig['config']);
            if($row['marry']==1 && $type==1){
                $this->message("福利未启用");
            }
            if($row['birth']==1 && $type==2){
                $this->message("福利未启用");
            }
            if($row['hospitalization']==1 && $type==3){
                $this->message("福利未启用");
            }
            if($row['retire']==1 && $type==4){
                $this->message("福利未启用");
            }
            if($row['funeral']==1 && $type==5){
                $this->message("福利未启用");
            }
        }else{
            $this->message("福利未启用,请等待管理员启用");
        }

        if($type==1){
            $moneytype=$row['marry_moneytype'];
            $typename="结婚";
        }elseif($type==2){
            $moneytype=$row['birth_moneytype'];
            $typename="生育";
        }
        elseif($type==3){
            $moneytype=$row['hospitalization_moneytype'];
            $typename="住院";
        }
        elseif($type==4){
            $moneytype=$row['retire_moneytype'];
            $typename="退休";
        }
        elseif($type==5){
            $moneytype=$row['funeral_moneytype'];
            $typename="丧葬";
        }else{
            $this->message("未发现的福利");
        }
        $_W['union']['title']=$typename."福利申请";
        include $this->template();
    }

    public function post(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $union_id=$_W['unionid'];

        if($_W['ispost']){
            $type=intval($_GPC['type']);
            if(!in_array($type,[1,2,3,4,5])){
                show_json(0,'非法福利');
            }
            $images=isset($_GPC['images']) ? array_map("tomedia",$_GPC['images']) :null;
            $member=m("member")->getMember($_W['openid']);
            $moeny=floatval($_GPC['money']);
            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'type'=>$type,
                'time'=>strtotime($_GPC['datetime']),
                'money'=>$moeny,
                'images_url'=>isset($images) ? implode("|",$images) : "",
                'remarks'=>trim($_GPC['remarks']),
                'amounttype'=>intval($_GPC['moneytype']),
                'openid'=>$_W['openid'],
                'status'=>1,
                'add_time'=>time(),
                'name'=>$member['realname'],
                'member_id'=>"",
            );
            pdo_insert("ewei_shop_union_welfare",$data);
            show_json(1,'申请成功');
        }

    }
    function walist(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $title=$this->titletype[$type];
        $_W['union']['title']=$title."福利";
        include $this->template();
    }
    function get_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $openid=$_W['openid'];

        $department=$this->model->get_department_info($uniacid,$union_id,$this->member['department']);
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid and union_id=:union_id and openid=:openid and type=:type';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,':openid'=>$openid,":type"=>intval($_GPC['type']));
        $sql="select * from ".tablename("ewei_shop_union_welfare")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_welfare')." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['department']=$department['name'];
            $row['username']=$this->member['name'];
            $row['datetime']=date("Y-m-d",$row['add_time']);
            if($row['status']==0){
                $row['status']="待申请";
            }elseif($row['status']==1){
                $row['statusmessage']="申请中";
            }elseif($row['status']==2){
                $row['statusmessage']="审核通过";
            }elseif($row['status']==3){
                $row['statusmessage']="驳回申请";
            }elseif($row['status']==4){
                $row['statusmessage']="审核拒绝";
            }


        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }


    function view(){
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
            ":openid"=>$openid,
        );
        $item=pdo_fetch("select * from ".tablename('ewei_shop_union_welfare')." where id=:id and uniacid=:uniacid and union_id=:union_id and openid=:openid ",$params);

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
        $union_info=$this->model->get_union_info($union_id);
        $department=$this->model->get_department_info($uniacid,$union_id,$this->member['department']);
        $typename=$title=$this->titletype[$item['type']];
        include $this->template();

    }




}