<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Welfare_EweiShopV2Page extends UnionMobilePage
{
    public $titletype=array(
        1=>'结婚慰问',
        2=>'生育慰问',
        3=>'住院慰问',
        4=>'退休慰问',
        5=>"丧葬慰问",
    );
    public function main()
    {
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $union_id=$_W['unionid'];
        $parmconfig=$_W['welfareconfig'];

        if($parmconfig && isset($parmconfig) && $parmconfig['welfarestatus']==1){
            $row=$_W['welfareconfig'];
            if($row['marry']==1 && $type==1){
                $this->message("慰问未启用");
            }
            if($row['birth']==1 && $type==2){
                $this->message("慰问未启用");
            }
            if($row['hospitalization']==1 && $type==3){
                $this->message("慰问未启用");
            }
            if($row['retire']==1 && $type==4){
                $this->message("慰问未启用");
            }
            if($row['funeral']==1 && $type==5){
                $this->message("慰问未启用");
            }
        }else{
            $this->message("慰问未启用,请等待管理员启用");
        }

        if($type==1){
            $moneytype=$row['marry_moneytype'];
            $typename="结婚慰问";
            $parmoney=$row['marry_total'];
        }elseif($type==2){
            $moneytype=$row['birth_moneytype'];
            $typename="生育慰问";
            $parmoney=$row['birth_total'];
        }
        elseif($type==3){
            $moneytype=$row['hospitalization_moneytype'];
            $typename="住院慰问";
            $parmoney=$row['hospitalization_total'];
        }
        elseif($type==4){
            $moneytype=$row['retire_moneytype'];
            $parmoney=$row['retire_total'];
            $typename="退休慰问";
        }
        elseif($type==5){
            $moneytype=$row['funeral_moneytype'];
            $parmoney=$row['funeral_total'];
            $typename="丧葬帮扶";
        }else{
            $this->message("未发现的慰问");
        }

        $_W['union']['title']=$typename."申请";
        include $this->template();
    }

    public function post(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $union_id=$_W['unionid'];
        $parmconfig=$_W['welfareconfig'];

        if($type==1){
            $shendid=$parmconfig['examine']['marry'];
        }elseif($type==2){
            $shendid=$parmconfig['examine']['birth'];
        }elseif($type==3){
            $shendid=$parmconfig['examine']['hospitalization'];
        }elseif($type==4){
            $shendid=$parmconfig['examine']['retire'];
        }elseif($type==5){
            $shendid=$parmconfig['examine']['funeral'];
        }

        if($_W['ispost']){
            $type=intval($_GPC['type']);
            if(!in_array($type,[1,2,3,4,5])){
                show_json(0,'非法慰问');
            }

            if($shendid>0){
                $examine=pdo_fetch("select * from ".tablename("ewei_shop_union_examine")." where uniacid=:uniacid and union_id=:union_id and enable=1 and id=:id",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid'],':id'=>$shendid));
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
                'bankcard'=>trim($_GPC['bankcard']),
                'bankname'=>trim($_GPC['bankname']),
            );
            //刚刚提交的时候 检查用户审核
            if(!empty($examine)){
                $data['examinestatus']=1;
                $optionlist=json_decode($examine['optionlist'],true);
                if($examine['open']=="OFF"){
                    $data['examine']=$examine['optionlist'];//全部流程
                    $data['examinmemberid']=$optionlist[0]['memberlist'];
                }else{
                    $uniongroupid=$this->member['uniongroupid'];
                    $member_info_shenhe=pdo_fetch("select id,name from ".tablename("ewei_shop_union_members")." where uniongroupid=:uniongroupid and union_id=:union_id and replay_power=1 limit 1",array(":union_id"=>$_W['unionid'],":uniongroupid"=>$uniongroupid));

                    if(empty($member_info_shenhe)){//没有分管领导情况
                        $data['examine']=$examine['optionlist'];//全部流程
                        $data['examinmemberid']=$optionlist[0]['memberlist'];
                    }else{
                        $addlevel=array("level"=>0,'memberlist'=>$member_info_shenhe['id'],'namelist'=>$member_info_shenhe['name']);
                        $data['examinmemberid']=$member_info_shenhe['id'];
                        if($member_info_shenhe['id']==$this->member['id']){//审核者自己提交数据
                            $addlevel['status']=2;
                            $addlevel['examinetime']=TIMESTAMP;
                            $data['examinmemberid']=$optionlist[0]['memberlist'];
                        }
                           if(!empty($addlevel)){
                               array_unshift($optionlist,$addlevel);
                           }

                        $data['examine']=json_encode($optionlist);//全部流程
                    }
                }
            }
            pdo_insert("ewei_shop_union_welfare",$data);
            show_json(1,'申请成功');
        }
    }




    function walist(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $title=$this->titletype[$type];
        $_W['union']['title']=$title."申请记录";

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
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 8;
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid and union_id=:union_id and openid=:openid and type=:type and is_delete=0';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,':openid'=>$openid,":type"=>intval($_GPC['type']));
        $sql="select * from ".tablename("ewei_shop_union_welfare")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_welfare')." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['department']=$department['name'];
            $row['username']=$this->member['name'];
            $row['datetime']=date("Y-m-d",$row['add_time']);
            if($row['status']==-1){
                $item['status']="已撤销";
            }
            elseif($row['status']==0){
                $row['status']="待申请";
            }elseif($row['status']==1){
                $row['statusmessage']="申请中";
            }elseif($row['status']==2){
                $row['statusmessage']="审批通过";
            }elseif($row['status']==3){
                $row['statusmessage']="驳回申请";
            }elseif($row['status']==4){
                $row['statusmessage']="审批不通过";
            }
            elseif($row['status']==4){
                $row['statusmessage']="已完成";
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
        $_W['union']['title']="慰问详情";
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
        if($item['status']==-1){
            $item['status']="已撤销";
        }
        elseif($item['status']==0){
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

        $sql_groups="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id and id=:groupid";
        $groups_info=pdo_fetch($sql_groups,array(":union_id"=>$this->member['union_id'],":uniacid"=>$this->member['uniacid'],':groupid'=>$this->member['uniongroupid']));

        $examine_loglist=!empty($item['examine']) ? json_decode($item['examine'],true) : array();
        foreach ($examine_loglist as $key=>$row){

            if($row['memberlist']==$item['examinmemberid'] && empty($row['status'])){
                $examine_loglist[$key]['status']=1;
            }

            if(mb_strlen($row['namelist'])>6){
                $examine_loglist[$key]['title_name']=mb_strcut($row['namelist'], mb_strlen($row['namelist'])-6, mb_strlen($row['namelist']));
            }else{
                $examine_loglist[$key]['title_name']=$row['namelist'];
            }


        }

        include $this->template();

    }


    //获取需要我审核的全部流程
    function examinelist(){
        global $_W;
        global $_GPC;

        include $this->template();
    }
    function getexamineoldlist(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,':openid'=>$_W['openid']);
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 8;
        $condition = " and log.uniacid = :uniacid and log.union_id=:union_id and log.openid=:openid";
        $sql="select w.*,m.name as username,d.name as department from ".tablename("ewei_shop_union_examine_log")." as log "
        ." LEFT JOIN ".tablename("ewei_shop_union_welfare")." as w ON w.id=log.welfareid "
            ." LEFT JOIN  ".tablename("ewei_shop_union_members")." as m ON m.openid=w.openid and m.union_id=:union_id "
            ." LEFT JOIN  ".tablename("ewei_shop_union_department")." as d ON m.department=d.id "
            ." where 1 {$condition} ORDER BY examinetime asc LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        ;

        $list = pdo_fetchall($sql, $params);
        $countsql="select count(*) from ".tablename("ewei_shop_union_examine_log")." as log "
            ." LEFT JOIN ".tablename("ewei_shop_union_welfare")." as w ON w.id=log.welfareid "
            ." LEFT JOIN  ".tablename("ewei_shop_union_members")." as m ON m.openid=w.openid "
            ." LEFT JOIN  ".tablename("ewei_shop_union_department")." as d ON m.department=d.id "
            ." where 1 {$condition} ";
        $total = pdo_fetchcolumn($countsql,$params);

        foreach ($list as &$item){
            $item['add_time']=date("Y-m-d",$item['add_time']);
            $item['msgstatus']="已审核";
            $item['typename']=$this->titletype[$item['type']];
        }
        unset($item);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));

    }
    function getexaminelist(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $mymemberid=intval($this->member['id']);

        $condition = " and w.uniacid = :uniacid and w.union_id=:union_id  and w.is_delete=0 and w.status=1 and w.examinestatus=1 and  FIND_IN_SET({$mymemberid},w.examinmemberid)";
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 8;
        $sql="select distinct w.id,w.*,m.name as username,d.name as department from ".tablename("ewei_shop_union_welfare")." as w "
            ." LEFT JOIN  ".tablename("ewei_shop_union_members")." as m ON m.openid=w.openid and m.union_id=:union_id"
            ." LEFT JOIN  ".tablename("ewei_shop_union_department")." as d ON m.department=d.id "
            ." where 1 {$condition} ORDER BY examinetime asc  LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $list = pdo_fetchall($sql, $params);
        $countsql="select count(*) from ".tablename("ewei_shop_union_welfare")." as w "
            ." LEFT JOIN  ".tablename("ewei_shop_union_members")." as m ON m.openid=w.openid and m.union_id=:union_id"
            ." LEFT JOIN  ".tablename("ewei_shop_union_department")." as d ON m.department=d.id "
            ." where 1 {$condition}";
        $total = pdo_fetchcolumn($countsql,$params);

        foreach ($list as &$item){
            $item['add_time']=date("Y-m-d",$item['add_time']);
            $item['msgstatus']="待审批";
            $item['typename']=$this->titletype[$item['type']];
        }
        unset($item);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    function examine(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $mymemberid=intval($this->member['id']);
        $_W['union']['title']="慰问审批";
        $params=array(
            ':id'=>intval($_GPC['id']),
            ':uniacid'=>$uniacid,
            ':union_id'=>$union_id,
        );
        $sql="select w.*,m.name as username,d.name as department,m.sex from ".tablename('ewei_shop_union_welfare')." as w"
            ." LEFT JOIN  ".tablename("ewei_shop_union_members")." as m ON m.openid=w.openid and m.union_id=:union_id "
            ." LEFT JOIN  ".tablename("ewei_shop_union_department")." as d ON m.department=d.id "
            ." where w.id=:id and w.uniacid=:uniacid and w.union_id=:union_id ";

        $item=pdo_fetch($sql,$params);
        if(empty($item)){
            $this->message("数据未找到");
        }
        $images=array();
        if($item['images_url']){
            $images=explode("|",$item['images_url']);
        }
        //获取其他用户审核流程意见
        //$sql="select * from ".tablename("ewei_shop_union_examine_log")." where  welfareid=:welfareid and uniacid=:uniacid and union_id=:union_id order by level asc";
        //$examine_loglist=pdo_fetchall($sql,array(':uniacid'=>$uniacid,':union_id'=>$union_id,":welfareid"=>$item['id']));
        $examine_loglist=json_decode($item['examine'],true);
        $shstatus=false;
        foreach ($examine_loglist as $key=>$row){

            if($row['memberlist']==$item['examinmemberid'] && empty($row['status']) && $shstatus==false){
                $examine_loglist[$key]['status']=1;
                $shstatus=true;
            }

            if(mb_strlen($row['namelist'])>6){
                $examine_loglist[$key]['title_name']=mb_strcut($row['namelist'], mb_strlen($row['namelist'])-6, mb_strlen($row['namelist']));
            }else{
                $examine_loglist[$key]['title_name']=$row['namelist'];
            }
        }
        $typename=$title=$this->titletype[$item['type']];
        $union_info=$this->model->get_union_info($union_id);
        include $this->template();
    }



    public function examinestatus(){
        global $_W;
        global $_GPC;
        if($_W['isajax']){

            $status=intval($_GPC['status']);

            $uuid=$this->member['id'];
            $id=intval($_GPC['id']);
            $union_id=$_W['unionid'];
            $uniacid=$_W['uniacid'];
            $params=array(
                ':id'=>$id,
                ':uniacid'=>$uniacid,
                ':union_id'=>$union_id,
            );
            $item=pdo_fetch("select * from ".tablename('ewei_shop_union_welfare')." where id=:id and uniacid=:uniacid and union_id=:union_id  ",$params);
            if($item['status']==-1){
                show_json(0,"抱歉!用户已经撤销申请");
            }
            if($item['status']==2){
                //处理完成流程
                show_json(0,"抱歉!数据异常当前申请已经完成");
            }
            if($item['status']==4){
                //处理完成流程
                show_json(0,"抱歉!当前申请已经被拒绝,无法进行其他操作！");
            }
            $examine_loglist=json_decode($item['examine'],true);
            if($uuid!=$item['examinmemberid']){
                show_json(0,"抱歉!当前审核人已经变更您无法进行操作！");
            }

            $nextkey=0;
            foreach ($examine_loglist as $key=> &$row){
                if(empty($row['status']) && $uuid==$row['memberlist'] ){
                    $row['status']=$status;
                    $row['examinetime']=TIMESTAMP;
                    $nextkey=$key+1;
                    $examine_data=array(
                        'uniacid'=>$uniacid,
                        'union_id'=>$union_id,
                        'openid'=>$_W['openid'],
                        'welfareid'=>$id,
                        'level'=>$row['level'],
                        'status'=> $status==2 ? 1:-1,
                        'examinename'=>$this->member['name'],
                        'add_time'=>TIMESTAMP,
                    );
                    pdo_insert('ewei_shop_union_examine_log',$examine_data);
                    //添加一条审核日志
                    break;
                }
            }

            unset($row);
            $nextmemberid=0;
            if(isset($examine_loglist[$nextkey]) && !empty($examine_loglist[$nextkey]) && $status==2){
                $nextmemberid=$examine_loglist[$nextkey]['memberlist'];
            }
            $data=array(
                'examinmemberid'=>$nextmemberid
            );

            //最终审核人审核数据
            if(!isset($examine_loglist[$nextkey]) &&  $status==2 && $uuid==$item['examinmemberid'] ){
                $data['status']=2;
                $data['examinestatus']=2;
            }

            if($status==-1){
                $data['status']=4;
                $data['examinestatus']=2;
                $newexamine_loglist=array();
                foreach ($examine_loglist as $k=>$v){
                    if(isset($v['status'])){
                        $newexamine_loglist[$k]=$v;
                    }
                }

                $examine_loglist=$newexamine_loglist;
            }
            $data['examine']=json_encode($examine_loglist);
            pdo_update("ewei_shop_union_welfare",$data,array("id"=>$item['id']));
            show_json(1,'操作成功');


        }
    }


}