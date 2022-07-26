<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{

    public $department=array();
    public $membertype=array(
        1=>"正式工",
        2=>"临时工",
        3=>"外派",
        4=>"借调",
        5=>"外聘",
        6=>"劳务派遣",
        7=>"其他",
    );
    public $membersex=array(
        1=>"男",
        2=>"女",
    );
    public $columns=array(

        array('title' => '处室/部门(必填)', 'field' => '', 'width' => 32),
        array('title' => "姓名(必填)", 'field' => '', 'width' => 32),
        array('title' => "职务", 'field' => '', 'width' => 32),
        array('title' => "固话号", 'field' => '', 'width' => 32),
        array('title' => "手机号(必填)", 'field' => '', 'width' => 32),
        array('title' => "性别(男,女)(必填)", 'field' => '', 'width' => 32),
        array('title' => "职工类型（正式职工 临时职工 外派 借调 其他(必填)", 'field' => '', 'width' => 32),
        array('title' => '备注', 'field' => '', 'width' => 32),
    );
    //在数组中查找指定的id
    function  findPid ( $pid = 1 , & $arr = array() ,$boo = false ,$a =array()  )
    {

        if( is_array( $arr ) )
        {
            foreach ( $arr as $k=>  $v )
            {

                if (  $v['id'] == $pid )
                {

                    if( ! $boo )
                    {
                        //$boo是false表示只找
                        return $arr[$k];
                    }
                    else
                    {
                        if( isset( $arr[$k]['children'] )  )
                        {
                            //有子类型
                            $arr[$k]['children'][] = $a   ;
                        }
                        else
                        {
                            //没有子类型
                            $arr[$k]['children'] = array()   ;
                            $arr[$k]['children'][] = $a   ;
                        }

                        return true;
                    }
                }
                else
                {
                    if( isset( $v['children'] ) )
                    {

                        $this->findPid( $pid , $arr[$k]['children'] ,$boo ,$a);//递归
                    }

                }
            }
        }
        else
        {

            return false;
        }
    }

    function   getLeaderArray( $array = array() )
    {
        $leaderArray = array ();
        if( is_array( $array )  )
        {
            //必须是数组
            foreach ( $array as $k=> $v  )
            {
                if( $v['parent_id'] == 0 )
                {

                    //顶层数组保留
                    $leaderArray[] = $v ;
                }
                else
                {
                    //否则要放到其父类型的'sub'属性里面
                    if( $this->findPid( $v['parent_id'] , $leaderArray  , true , $array[$k]  ))//找到父类型添加进父类型或者没找到
                    {
                        //子类型添加完成
                    }
                    else
                    {
//                        //在数组中没有找到
//                        // 自动将改父类型补充为顶层元素
//                        $leaderArray[$v['parent_id']] = [
//                            'id'=> $v['parent_id'],
//                            'parent_id'  =>0,
//                            'name'=>'',
//                            'children'=> [
//                                $k=> $array[$k]
//                            ]
//                        ];
                    }
                }
            }
            return $leaderArray;
        }
        else
        {
            return $array;
        }
    }
    //检查被打开的数据
    public function selectopen(&$arr,$select){
        if(is_array($arr)){
            foreach ($arr as $key=> $v){

                if($v['id']==$select && isset($v['children']) && $v['parent_id']==0 ){

                    $arr[$key]['spread']=true;

                    return true;
                }elseif($v['id']==$select && isset($v['children']) && $v['parent_id']>0 ){
                    $arr[$key]['spread']=true;
                    $this->selectopen($this->department,$v['parent_id']);
                }
                elseif($v['id']==$select && $v['parent_id']>0 ){

                    $this->selectopen($this->department,$v['parent_id']);
                }else{
                    if(isset($v['children'])){
                        $this->selectopen($arr[$key]['children'],$select);
                    }
                }
            }
        }else{
            return false;
        }
    }

    public function main(){
        //计算还未看的用户
        global $_W;
        global $_GPC;

        //查询全部的分类
        $sql="select name as label,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 order by parent_id asc,displayorder desc";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");
        array_unshift($department,array('label'=>$this->user_info['title'],'parent_id'=>0,'id'=>0));

        $this->department=$this->getLeaderArray($department);
        $sql_grouplist="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id";
        $grouplist=pdo_fetchall($sql_grouplist,array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where 1 and  m.uniacid=:uniacid and m.union_id=:union_id  and m.isdelete=0 ";
        $name=empty($_GPC['name']) ? "":$_GPC['name'];
        $mobile=empty($_GPC['mobile']) ? "":$_GPC['mobile'];

        $selector1=empty($_GPC['selector1']) ? "" : intval($_GPC['selector1']);
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);

        $member_type=empty($_GPC['member_type']) ? "" :intval($_GPC['member_type']);

        if($member_type!=''){
            $condition.=" and m.type =:member_type ";
            $paras[':member_type']=$member_type;
        }
        if($_GPC['status']!=''){
            $condition.=" and m.status =:status ";
            $paras[':status']=intval($_GPC['status']);
        }

        if($name){
            $condition.=" and (m.name like :name or m.duties like :name )";
            $paras[':name']="%".$name."%";
        }
        if($mobile){
            $condition.=" and m.mobile_phone like :mobile ";
            $paras[':mobile']=$mobile."%";
        }
        if($selector1){
            $condition.=" and m.department = :department ";
            $paras[':department']=$selector1;
            //选择分类
            $this->selectopen($this->department,$selector1);
        }
        if($_GPC['jobjointime'] && !empty($_GPC['jobjointime'])){
            $jobjointime=explode(" - ",$_GPC['jobjointime']);
            $condition.=" and m.jobjointime between :jobstarttime and :jobendtime ";
            $paras[':jobstarttime']=strtotime("-1 second",strtotime($jobjointime[0]));
            $paras[':jobendtime']=strtotime($jobjointime[1]);


        }
        if($_GPC['applyuniontime'] && !empty($_GPC['applyuniontime'])){
            $applyuniontime=explode(" - ",$_GPC['applyuniontime']);
            $condition.=" and m.applyuniontime between :applystarttime and :applyendtime ";

            $paras[':applystarttime']=strtotime("-1 second",strtotime($applyuniontime[0]));
            $paras[':applyendtime']=strtotime($applyuniontime[1]);
        }

        if($_GPC['approvaluniontime'] && !empty($_GPC['approvaluniontime'])){
            $approvaluniontime=explode(" - ",$_GPC['approvaluniontime']);
            $condition.=" and m.approvaluniontime between :approvalunionstarttime and :approvalunionendtime ";

            $paras[':approvalunionstarttime']=strtotime("-1 second",strtotime($approvaluniontime[0]));
            $paras[':approvalunionendtime']=strtotime($approvaluniontime[1]);
        }
        if($_GPC['uniongroupid'] && is_numeric($_GPC['uniongroupid'])){
            $condition.=" and m.uniongroupid =:uniongroupid ";
            $paras[':uniongroupid']=$_GPC['uniongroupid'];
        }
        if($_GPC['activate']!='' && is_numeric($_GPC['activate'])){
            $condition.=" and m.activate =:activate ";
            $paras[':activate']=$_GPC['activate'];
        }
        if($_GPC['sex']!='' && is_numeric($_GPC['sex'])){
            $condition.=" and m.sex =:sex ";
            $paras[':sex']=$_GPC['sex'];
        }
        if($_GPC['age_min'] && $_GPC['age_max']){

            $minage=strtotime("- {$_GPC['age_min']} year ",strtotime(date("Y-01-01")));
            $maxage=strtotime("- {$_GPC['age_max']} year ",strtotime(date("Y-01-01")));
            $condition.=" and ((m.childrenonebirthday>:maxage and m.childrenonebirthday<:minage) or (m.childrentowbirthday>:maxage and m.childrentowbirthday<:minage) )";
            $paras[':minage']=$minage;
            $paras[':maxage']=$maxage;


        }
        $sql="select m.*,d.name as dname from ".tablename("ewei_shop_union_members")." as m LEFT JOIN ".
            tablename("ewei_shop_union_department")." as d ON d.id=m.department".
            $condition;
        $sql.=" order by sort desc,add_time desc ";
        if($_GPC['export']!=1){
            $sql.=" LIMIT ".($pindex - 1) * $psize . ',' . $psize;
        }

        $list = pdo_fetchall($sql, $paras);
        $exgroup=array();
        foreach ($grouplist as $gli){
            $exgroup[$gli['id']]=$gli['groupname'];
        }
        if($_GPC['export']==1){


            $columns['title']= "单位数据导出";
            $columns['columns']=array(
                array('title' => '序号', 'field' => 'fieldid', 'width' => 32),
                array('title' => '单位', 'field' => 'unionname', 'width' => 32),
                array('title' => '处室/部门/部门化子公司', 'field' => 'level1', 'width' => 24),
                array('title' => '一级子部门(若有)', 'field' => 'level2', 'width' => 24),
                array('title' => '二级子部门(若有)', 'field' => 'level3', 'width' => 24),
                array('title' => '三级子部门(若有)', 'field' => 'level4', 'width' => 24),
                array('title' => '姓名', 'field' => 'name', 'width' => 16),
                array('title' => '手机号', 'field' => 'mobile_phone', 'width' => 16),
                array('title' => '职务', 'field' => 'duties', 'width' => 16),
                array('title' => '身份证号', 'field' => 'idcard', 'width' => 32),
                array('title' => '固定电话', 'field' => 'telephone', 'width' => 16),
                array('title' => '性别', 'field' => 'sex', 'width' => 8),
                array('title' => '职工类型', 'field' => 'type', 'width' => 8),
                array('title' => '备注', 'field' => 'remk', 'width' => 32),
                array('title' => '入职时间', 'field' => 'jobjointime', 'width' => 32),
                array('title' => '申请入会时间', 'field' => 'applyuniontime', 'width' => 32),
                array('title' => '批准入会时间', 'field' => 'approvaluniontime', 'width' => 32),
                array('title' => '所属工会小组', 'field' => 'uniongroupid', 'width' => 32),
                array('title' => '子女一姓名', 'field' => 'childrenonename', 'width' => 32),
                array('title' => '子女一性别', 'field' => 'childrenonesex', 'width' => 32),
                array('title' => '子女一身份证号码', 'field' => 'childrenoneidcard', 'width' => 32),
                array('title' => '子女二姓名', 'field' => 'childrentowname', 'width' => 32),
                array('title' => '子女二性别', 'field' => 'childrentowsex', 'width' => 32),
                array('title' => '子女二身份证号码', 'field' => 'childrentowidcard', 'width' => 32),
            );
            $unioninfo=$this->model->get_union_info($_W['unionid']);
            $sql="select name,parent_id,id,level from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid  order by displayorder desc";
            $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");


            foreach ($list as $key=> $item){
                $deinfo=$department[$item['department']];


                if($deinfo['level']==1){
                    $level1=$deinfo['name'];
                    $level2="";
                    $level3="";
                    $level4="";
                }
                if($deinfo['level']==2){
                    $level1=$department[$deinfo['parent_id']]['name'];
                    $level2=$deinfo['name'];
                    $level3="";
                    $level4="";
                }
                if($deinfo['level']==3){
                    $level2_info=$department[$deinfo['parent_id']];
                    $level1_info=$department[$level2_info['parent_id']];
                    $level1=$level1_info['name'];
                    $level2=$level2_info['name'];
                    $level3=$deinfo['name'];
                    $level4='';

                }
                if($deinfo['level']==4){
                    $level3_info=$department[$deinfo['parent_id']];
                    $level2_info=$department[$level3_info['parent_id']];
                    $level1_info=$department[$level2_info['parent_id']];
                    $level1=$level1_info['name'];
                    $level2=$level2_info['name'];
                    $level3=$level3_info['name'];
                    $level4=$deinfo['name'];


                }
                $list_array[]=array(

                    'fieldid'=>$key+1,
                    'unionname'=>$unioninfo['title'],
                    'level1'=>$level1,
                    'level2'=>$level2,
                    'level3'=>$level3,
                    'level4'=>$level4,
                    'name'=>$item['name'],
                    'duties'=>$item['duties'],
                    'idcard'=>$item['idcard'],
                    'telephone'=>$item['telephone'],
                    'mobile_phone'=>$item['mobile_phone'],
                    'sex'=>$this->membersex[$item['sex']],
                    'type'=>$this->membertype[$item['type']],
                    'remk'=>$item['remk'],
                    'jobjointime'=>date("Y-m-d",$item['jobjointime']),
                    'applyuniontime'=>date("Y-m-d",$item['applyuniontime']),
                    'approvaluniontime'=>date("Y-m-d",$item['approvaluniontime']),
                    'uniongroupid'=>$exgroup[$item['uniongroupid']],
                    'childrenonename'=>$item['childrenonename'],
                    'childrenonesex'=>$this->membersex[$item['childrenonesex']],
                    'childrenoneidcard'=>$item['childrenoneidcard'],
                    'childrentowname'=>$item['childrentowname'],
                    'childrentowsex'=>$this->membersex[$item['childrentowsex']],
                    'childrentowidcard'=>$item['childrentowidcard'],

                );
            }



            m('excel')->export($list_array,$columns);
            exit;
        }
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." as m ".$condition,$paras);
        foreach ($list as &$row){
            $row['type']=$this->membertype[$row['type']];
            $row['sex']=$this->membersex[$row['sex']];
            $row['groupname']=$exgroup[$row['uniongroupid']];

        }
        unset($row);
        $categorydata=json_encode($this->department,320);
        $pager = pagination($total, $pindex, $psize);
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
        include $this->template();
    }
    public function add(){
        $this->post();
    }
    public function edit(){
        $this->post();
    }
    function password(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $action=unionUrl("member/index/password");
        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and id=:id",array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id));
        if($_W['ispost']){
            $password=$_GPC['password'];
            $newpassword=$_GPC['newpassword'];
            if($password!=$newpassword){
                $this->model->show_json(0,"2次输入的密码不一致");
            }
            if(empty($vo['openid'])){
                $this->model->show_json(0,"抱歉当前账号尚未绑定微信");
            }
            $member=m("member")->getMember($vo['openid']);
            if(empty($member)){
                $this->model->show_json(0,"抱歉当前账号尚未绑定微信");
            }
            $pass=md5($password.$member['salt']);
            pdo_update("ewei_shop_member",array('pwd'=>$pass),array('id'=>$member['id']));
            $this->model->show_json(1,"重置密码成功");
        }

        include $this->template("member/password");
    }
    public function post(){
        global $_W;
        global $_GPC;
        $action=unionUrl("member/index/post");
        $id=intval($_GPC['id']);

        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and id=:id",array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id));
            if($vo['year']){
                $vo['birthday']=$vo['year'].'-'.$vo['moth'].'-'.$vo['day'];
            }
            if($vo['jobjointime']){
                $vo['jobjointime']=date("Y-m-d",$vo['jobjointime']);
            }
            if($vo['applyuniontime']){
                $vo['applyuniontime']=date("Y-m-d",$vo['applyuniontime']);
            }
            if($vo['approvaluniontime']){
                $vo['approvaluniontime']=date("Y-m-d",$vo['approvaluniontime']);
            }

        }
        $sql_grouplist="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id";
        $grouplist=pdo_fetchall($sql_grouplist,array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
        if($_W['ispost']){
            $parssword="";
           $data=array(
               'name'=>trim($_GPC['name']),
               'sex'=>intval($_GPC['sex']),
               'type'=>intval($_GPC['type']),
               'department'=>intval($_GPC['department']),
               'remk'=>trim($_GPC['remk']),
               'activate'=>trim($_GPC['activate']),
               'duties'=>trim($_GPC['duties']),
               'telephone'=>trim($_GPC['telephone']),
               'uniongroupid'=>intval($_GPC['uniongroupid']),
               'replaystatus'=>intval($_GPC['replaystatus']),
               'jobjointime'=>strtotime($_GPC['jobjointime']),
               'applyuniontime'=>strtotime($_GPC['applyuniontime']),
               'childrenonename'=>trim($_GPC['childrenonename']),
               'childrenonesex'=>intval($_GPC['childrenonesex']),
               'childrenoneidcard'=>$_GPC['childrenoneidcard'],
               'childrentowname'=>$_GPC['childrentowname'],
               'childrentowsex'=>$_GPC['childrentowsex'],
               'childrentowidcard'=>$_GPC['childrentowidcard'],
               'replay_power'=>intval($_GPC['replay_power']),
           );

           if(!empty($data['childrenoneidcard'])){
               $idcard_array=m('idcard')->splitIdcard($data['childrenoneidcard']);
               if(is_error($idcard_array)){
                   $this->model->show_json(0,"子女一身份证号码".$idcard_array['message']);
               }
               $data['childrenonebirthday']=strtotime($idcard_array['year']."-".$idcard_array['month']."-".$idcard_array['day']);
           }
            if(!empty($data['childrentowidcard'])){
                $idcard_array=m('idcard')->splitIdcard($data['childrentowidcard']);
                if(is_error($idcard_array)){
                    $this->model->show_json(0,"子女二身份证号码".$idcard_array['message']);
                }
                $data['childrentowbirthday']=strtotime($idcard_array['year']."-".$idcard_array['month']."-".$idcard_array['day']);
            }

           if($data['activate']==1 && $id){
               $data['entrytime']=time();
               //检查手机号是否有默认
               $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where is_default=1 and mobile_phone=:mobile_phone",array(":mobile_phone"=>$vo['mobile']));
               if($count<=0){
                   $data['is_default']=1;
               }
           }
           if(isset($_GPC['date']) && !empty($_GPC['date'])){
               $date=explode('-',trim($_GPC['date']));
               $data['year']=$date[0];
               $data['moth']=$date[1];
               $data['day']=$date[2];
           }
           if($id){
               $data['idcard']=trim($_GPC['idcard']);
               $idcard_array=m('idcard')->splitIdcard($data['idcard']);
               if(is_error($idcard_array)){
                   $this->model->show_json(0,$idcard_array['message']);
               }
               pdo_update("ewei_shop_union_members",$data,array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],'id'=>$id));
               $this->model->show_json(1,'修改成功');
           }else{
               $data['add_time']=time();
               //检查mobile_phone 是否重复
               $sql="select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and mobile_phone=:mobile_phone ";
               $mobile_member_count= pdo_fetchcolumn($sql,array(":uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],':mobile_phone'=>trim($_GPC['mobile_phone'])));

               if(!empty($mobile_member_count)){
                   $this->model->show_json(0,'手机号码重复请检测');
               }
               $data['mobile_phone']=trim($_GPC['mobile_phone']);
               $data['uniacid']=$_W['uniacid'];
               $data['union_id']=$_W['unionid'];
               $data['idcard']=trim($_GPC['idcard']);
               $idcard_array=m('idcard')->splitIdcard($data['idcard']);
               if(is_error($idcard_array)){
                   $this->model->show_json(0,$idcard_array['message']);
               }
               pdo_insert("ewei_shop_union_members",$data);
               $this->model->show_json(1,'添加成功');
           }
        }

        include $this->template("member/member_post");

    }

    public function export(){
        global $_W;
        global $_GPC;
        $sql_grouplist="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id";
        $grouplist=pdo_fetchall($sql_grouplist,array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        $exgroup=array();
        foreach ($grouplist as $gli){
            $exgroup[$gli['id']]=$gli['groupname'];
        }

        $columns['columns']=array(
            array('title' => '单位', 'field' => 'unionname', 'width' => 32),
            array('title' => '处室/部门/部门化子公司', 'field' => 'level1', 'width' => 24),
            array('title' => '一级子部门(若有)', 'field' => 'level2', 'width' => 24),
            array('title' => '二级子部门(若有)', 'field' => 'level3', 'width' => 24),
            array('title' => '三级子部门(若有)', 'field' => 'level4', 'width' => 24),
            array('title' => '姓名', 'field' => 'name', 'width' => 16),
            array('title' => '职务', 'field' => 'duties', 'width' => 16),
            array('title' => '身份证号', 'field' => 'idcard', 'width' => 32),
            array('title' => '手机号', 'field' => 'mobile_phone', 'width' => 16),
            array('title' => '固定电话', 'field' => 'telephone', 'width' => 16),
            array('title' => '性别', 'field' => 'sex', 'width' => 8),
            array('title' => '职工类型', 'field' => 'type', 'width' => 8),
            array('title' => '备注', 'field' => 'remk', 'width' => 32),
            array('title' => '入职时间', 'field' => 'jobjointime', 'width' => 32),
            array('title' => '申请入会时间', 'field' => 'applyuniontime', 'width' => 32),
            array('title' => '批准入会时间', 'field' => 'approvaluniontime', 'width' => 32),
            array('title' => '所属工会小组', 'field' => 'uniongroupid', 'width' => 32),
            array('title' => '子女一姓名', 'field' => 'childrenonename', 'width' => 32),
            array('title' => '子女一性别', 'field' => 'childrenonesex', 'width' => 32),
            array('title' => '子女一身份证号码', 'field' => 'childrenoneidcard', 'width' => 32),
            array('title' => '子女二姓名', 'field' => 'childrentowname', 'width' => 32),
            array('title' => '子女二性别', 'field' => 'childrentowsex', 'width' => 32),
            array('title' => '子女二身份证号码', 'field' => 'childrentowidcard', 'width' => 32),
        );
        $unioninfo=$this->model->get_union_info($_W['unionid']);
        $list_array=array();
        $sql="select * from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id =:union_id and activate=1  order by sort desc";
        $list=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid']));
        $sql="select name,parent_id,id,level from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid  order by displayorder desc";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        foreach ($list as $item){
            $deinfo=$department[$item['department']];


            if($deinfo['level']==1){
                $level1=$deinfo['name'];
                $level2="";
                $level3="";
                $level4="";
            }
            if($deinfo['level']==2){
                $level1=$department[$deinfo['parent_id']]['name'];
                $level2=$deinfo['name'];
                $level3="";
                $level4="";
            }
            if($deinfo['level']==3){
                $level2_info=$department[$deinfo['parent_id']];
                $level1_info=$department[$level2_info['parent_id']];
                $level1=$level1_info['name'];
                $level2=$level2_info['name'];
                $level3=$deinfo['name'];
                $level4='';

            }
            if($deinfo['level']==4){
                $level3_info=$department[$deinfo['parent_id']];
                $level2_info=$department[$level3_info['parent_id']];
                $level1_info=$department[$level2_info['parent_id']];
                $level1=$level1_info['name'];
                $level2=$level2_info['name'];
                $level3=$level3_info['name'];
                $level4=$deinfo['name'];


            }
            $list_array[]=array(
                'unionname'=>$unioninfo['title'],
                'level1'=>$level1,
                'level2'=>$level2,
                'level3'=>$level3,
                'level4'=>$level4,
                'name'=>$item['name'],
                'duties'=>$item['duties'],
                'idcard'=>$item['idcard'],
                'telephone'=>$item['telephone'],
                'mobile_phone'=>$item['mobile_phone'],
                'sex'=>$this->membersex[$item['sex']],
                'type'=>$this->membertype[$item['type']],
                'remk'=>$item['remk'],
                'jobjointime'=>date("Y-m-d",$item['jobjointime']),
                'applyuniontime'=>date("Y-m-d",$item['applyuniontime']),
                'approvaluniontime'=>date("Y-m-d",$item['approvaluniontime']),
                'uniongroupid'=>$exgroup[$item['uniongroupid']],
                'childrenonename'=>$item['childrenonename'],
                'childrenonesex'=>$this->membersex[$item['childrenonesex']],
                'childrenoneidcard'=>$item['childrenoneidcard'],
                'childrentowname'=>$item['childrentowname'],
                'childrentowsex'=>$this->membersex[$item['childrentowsex']],
                'childrentowidcard'=>$item['childrentowidcard'],
            );
        }

        //var_dump($list_array);
        //die();
        m("excel")->export_title($list_array,'单 位 工 会 会 员 信 息 表',$columns);
    }

    public function import(){
        global $_W;
        global $_GPC;


        if($_W['ispost']){
            $url=$_GPC['inputxcel'];
            $data=$this->checkdata($url);

            if(is_error($data)){
                $this->model->show_json(0,$data['message']);
            }
            $this->model->show_json(1,$data['message']);
        }
        include $this->template("member/member_import");
    }



    private function  createdepartment($name,&$departmentarray,$level,$parent_id,$key){
        global $_W;
        $params=array(":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid']);

        //检查部门是否存在
        if(!in_array($name,$departmentarray)){
            $params[':name']=$name;
            $department=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and name=:name",$params);

            if(empty($department)){
                $data=array(
                    'union_id'=>$_W['unionid'],
                    'uniacid'=>$_W['uniacid'],
                    'name'=>$name,
                    'parent_id'=>$parent_id,
                    'enable'=>1,
                    'addtime'=>time(),
                    'displayorder'=>0,
                    'level'=>$level,
                );
                $ret=pdo_insert("ewei_shop_union_department",$data);
                if($ret){
                    $parent_id=pdo_insertid();
                }else{

                    return error(-1,($key+1)."行部门数据插入错误,请重试");
                }
                $departmentarray[$parent_id]=$name;
                //array_push($departmentarray,array($parent_id=>$name));
                $department1_key=$parent_id;
            }else {
                $department1keys=array_keys($departmentarray);
                if(!in_array($department['id'],$department1keys)){
                    //array_push($departmentarray,array($department['id']=>$department['name']));
                    $departmentarray[$department['id']]=$department['name'];
                }
                $department1_key=$department['id'];
            }
        }else{
            $department1_key=array_search($name,$departmentarray);
        }
        return $department1_key;
    }
    private function trimall($str)//删除空格
    {
        $qian=array(" ","　","\t","\n","\r");
        $hou=array("","","","","");

        return str_replace($qian,$hou,$str);
    }

    public function test(){
        $date="2010.2.3";

        if(strtotime($date)===false ){
            var_dump("error");
            var_dump(strtotime($date));
        };

        $data=date("Y-m-d",strtotime($date));
        var_dump($data);
        die();
    }
    private function getalluniongroup(){
        //查询当前 全部所在分组
        global $_W;
        $sql="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id";
        $list=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']),"id");
        $a=array();
        foreach ($list as $item){
            $a[$item['id']]=$item['groupname'];
        }

        return $a;
    }
    //将一些异常的时间格式转换成可识别格式
    function excelTime($date, $time = false) {
        $date=str_replace('。','.',$date);
        $date=str_replace(".","-",$date);
        $date=str_replace("/","-",$date);
        if(function_exists('GregorianToJD')){
            if (is_numeric( $date )) {
                $jd = GregorianToJD( 1, 1, 1970 );
                $gregorian = JDToGregorian( $jd + intval ( $date ) - 25569 );
                $date = explode( '/', $gregorian );
                $date_str = str_pad( $date [2], 4, '0', STR_PAD_LEFT )
                    ."-". str_pad( $date [0], 2, '0', STR_PAD_LEFT )
                    ."-". str_pad( $date [1], 2, '0', STR_PAD_LEFT )
                    . ($time ? " 00:00:00" : '');
                return $date_str;
            }
        }else{
            $date=$date>25568?$date+1:25569;
            /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
            $ofs=(70 * 365 + 17+2) * 86400;
            $date = date("Y-m-d",($date * 86400) - $ofs).($time ? " 00:00:00" : '');
        }
        return $date;
    }
    private function adduniongroup($groupname,&$grouplist){
        //查询当前 全部所在分组
        global $_W;


        if(in_array($groupname,$grouplist)){

            $key_id = array_search($groupname, $grouplist);
            return $key_id;
        }


        $data=array(
            'groupname'=>$groupname,
            'uniacid'=>$_W['uniacid'],
            'union_id'=>$_W['unionid'],
        );
        pdo_insert("ewei_shop_union_uniongroup",$data);
        $key_id=pdo_insertid();
        $grouplist=$grouplist+array($key_id=>$groupname);
        return $key_id;
    }

    /**
     * @param $name 名字
     * @param $parent_id 上级ID
     * @return int
     */
    private function selectandadddepartment($name,$parent_id,$level){
        global $_W;
        $params[':name']=$name;
        $params[':parent_id']=$parent_id;
        $params[':union_id']=$_W['unionid'];
        $params[':uniacid']=$_W['uniacid'];
        $departmentid=pdo_fetchcolumn("select id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and parent_id=:parent_id and  uniacid=:uniacid and name=:name",$params);
        if(empty($departmentid)){
            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'name'=>$name,
                'parent_id'=>$parent_id,
                'enable'=>1,
                'addtime'=>time(),
                'displayorder'=>0,
                'level'=>$level,
            );
            $ret=pdo_insert("ewei_shop_union_department",$data);
            $departmentid=pdo_insertid();
        }
        return $departmentid;
    }

    private  function createnewdepartment($row1,$row2="",$row3="",$row4=""){
        global $_W;

        $departmentid=$this->selectandadddepartment($row1,0,1);//一级
        if(!empty($departmentid) && !empty($row2)){
            $departmentid=$this->selectandadddepartment($row2,$departmentid,2);//二级
        }
        if(!empty($departmentid) && !empty($row3)){
            $departmentid=$this->selectandadddepartment($row3,$departmentid,3);//三级
        }
        if(!empty($departmentid) && !empty($row4)){
            $departmentid=$this->selectandadddepartment($row4,$departmentid,4);//4级
        }
        return $departmentid;

    }

    private function checkdata($url){
        global $_W;
        $len=strpos($url,"union");
        if($len===false){
            return error(-1,'文件错误');
        }
        $filename=substr($url,$len);
        $uploadfile=ATTACHMENT_ROOT .$filename;
        try{

            $grouplist=$this->getalluniongroup();
            if(!is_file($uploadfile)){
                throw new Exception("文件错误,联系管理员");
            }
            $ext =  strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
            if($ext!='xlsx' && $ext!='xls' && $ext!='cvs' ){
                throw new Exception("请上传 xls 或 xlsx 或者 cvs 格式的Excel文件");
            }

            $import_rows = m('excel')->importurl($uploadfile,$ext);

            if(is_error($import_rows)){
                throw new Exception($import_rows['message']);
            }
            $displays=0;

            $displayers[]=array();
            $displayers1[]=array();
            $displayers2[]=array();
            $displayers3[]=array();
            foreach ($rows = m('excel')->importurl($uploadfile,$ext) as $key=> $row){
                if($key<1){
                    continue;
                }
                if(!empty(trim($row[1]))){
                    $displays++;
                }
                if(!empty($row[1])){
                    $displayers[]=trim($row[1]);//部门1
                }
                if(!empty($row[2])){
                    $displayers1[]=trim($row[2]);//部门2
                }
                if(!empty($row[3])){
                    $displayers2[]=trim($row[3]);//部门3
                }
                if(!empty($row[4])){
                    $displayers3[]=trim($row[4]);//部门4
                }

            }

            $displayers=array_filter($displayers);


            $displayers1=array_filter($displayers1);


            $displayers2=array_filter($displayers2);


            $displayers3=array_filter($displayers3);



            pdo_begin();
            $indertdata=array();
            $success=0;
            $error=0;
            $membertype=array_flip($this->membertype);
            $membersex=array_flip($this->membersex);

            $uniacid=$_W['uniacid'];
            $union_id=$_W['unionid'];


            //更新全部工会用户变成 未激活和 排序变成0
            pdo_update("ewei_shop_union_members",array("sort"=>0,'activate'=>0),array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid']));


            pdo_delete("ewei_shop_union_department",array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid']));

            foreach ($rows = m('excel')->importurl($uploadfile,$ext) as $key=> $row){
                if($key<1){
                    continue;
                }
                $row1=trim($row[1]);//部门1
                $row2=trim($row[2]);//部门2
                $row3=trim($row[3]);//部门3
                $row4=trim($row[4]);//部门4




                $name=trim($row[5]);//姓名
                $duties=trim($row[6]);//职务
                $idcard_num=trim(strval($row[7]));//身份证号

                $mobile=trim($row[8]);//手机号
                $telephone=trim($row[9]);//固定电话
                $sex=trim($row[10]);//性别
                $type=trim($row[11]);//职工类型
                $remk=trim($row[12]);//备注
                $jobjointime=trim($row[13]);//入职时间
                $applyuniontime=trim($row[14]);//申请入会时间
                $approvaluniontime=trim($row[15]);//批准入会时间
                $uniongroup=trim($row[16]);//工会小组
                $childrenonename=trim($row[17]);//子女1姓名
                $childrenonesex=trim($row[18]);//子女1性别
                $childrenoneidcard=trim(strval($row[19]));//子女1身份证

                $childrentowname=trim($row[20]);//子女1姓名
                $childrentowsex=trim($row[21]);//子女1性别
                $childrentowidcard=trim(strval($row[22]));//子女1身份证
                if(empty($row1)){
                    throw new Exception(($key+1)."行,需要添加一个部门");
                }
                $department_id=$this->createnewdepartment($row1,$row2,$row3,$row4);

                if(empty($name) && empty($mobile)){
                    continue;
                }
                if(empty($mobile)){
                    throw new Exception(($key+1)."行,手机号码未录入");
                }
                if(strlen($mobile)<11){
                    throw new Exception(($key+1)."行,手机号码未录入");
                }
                if(empty($name)){
                    throw new Exception(($key+1)."行,姓名未录入");
                }
                if(!isset($membersex[$sex])){
                    throw new Exception(($key+1)."行,性别错误");
                }
                if(!isset($membertype[$type])){
                    throw new Exception(($key+1)."行,职工类型错误");
                }



                if($department_id==0){
                    throw new Exception(($key+1)."部门出现异常请重试");
                }
                $sql="select id from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and mobile_phone=:mobile_phone  and isdelete=0 ";

                $cheack_mobile_repeat=pdo_fetchcolumn($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':mobile_phone'=>$mobile));

                $idcard_array=array();
                //身份证效验
                if(!empty($idcard_num)){
                    $idcard_array=m('idcard')->splitIdcard($idcard_num);

                    if(is_error($idcard_array)){

                        throw new Exception(($key+1)."行:"."本人身份证数据错误,错误号码:".":{$idcard_num}");
                    }
                }else{
                    $idcard_array['year']='';
                    $idcard_array['month']='';
                    $idcard_array['day']='';
                }


                $import_data=array(
                    'name'=>$name,
                    'sex'=>$membersex[$sex],
                    'type'=>$membertype[$type],
                    'department'=>$department_id,
                    'remk'=>$remk,
                    'duties'=>$duties,
                    'telephone'=>$telephone,
                    'activate'=>1,
                    'idcard'=>$idcard_num,
                    'year'=>$idcard_array['year'],
                    'moth'=>$idcard_array['month'],
                    'day'=>$idcard_array['day'],
                    'entrytime'=>time(),
                    'sort'=>$displays*10,
                    'uniongroupid'=>'',
                    'jobjointime'=>0,
                    'applyuniontime'=>0,
                    'approvaluniontime'=>0,
                    'childrenonename'=>'',
                    'childrenonesex'=>0,
                    'childrenonebirthday'=>0,
                    'childrenoneidcard'=>'',
                    'childrentowname'=>'',
                    'childrentowsex'=>0,
                    'childrentowbirthday'=>0,
                    'childrentowidcard'=>'',

                );
                if(!empty($uniongroup)){
                    $groupid=$this->adduniongroup($uniongroup,$grouplist);
                    $import_data['uniongroupid']=$groupid;
                }

                if(!empty($jobjointime)){

                    if(strtotime($jobjointime)===false) {
                        $jobjointime=$this->excelTime($jobjointime);
                        if(strtotime($jobjointime)===false){
                            throw new Exception(($key+1)."行入职时间错误:".$jobjointime);
                        }
                    }

                    $import_data['jobjointime']=strtotime($jobjointime);
                }
                //申请入会时间效验
                if(!empty($applyuniontime)){
                    if(strtotime($applyuniontime)===false) {
                        $applyuniontime = $this->excelTime($applyuniontime);
                        if (strtotime($applyuniontime) === false) {
                            throw new Exception(($key + 1) . "行申请入会时间导入错误");
                        }
                    }
                    $import_data['applyuniontime']=strtotime($applyuniontime);
                }
                //批准入会时间效验
                if(!empty($approvaluniontime)){
                    if(strtotime($approvaluniontime)===false) {
                        $approvaluniontime = $this->excelTime($approvaluniontime);
                        if (strtotime($approvaluniontime) === false) {
                            throw new Exception(($key + 1) . "行批准入会时间导入错误");
                        }
                    }
                    $import_data['approvaluniontime']=strtotime($approvaluniontime);
                }
                //如果有小孩1名字
                if(!empty($childrenonename)){
                    $import_data['childrenonename']=$childrenonename;
                }
                //小孩1的性别
                if(!empty($childrenonesex)){
                    $import_data['childrenonesex']=$membersex[$childrenonesex];
                }
                //子女1身份证号码
                if(!empty($childrenoneidcard)){
                    $childrenoneidcard_array=m('idcard')->splitIdcard($childrenoneidcard);
                    if(is_error($childrenoneidcard_array)){
                        throw new Exception(($key+1).'子女1身份证号码异常：'.$childrenoneidcard_array['message']);
                    }
                    $childrenonebirthday=$childrenoneidcard_array['year']."-".$childrenoneidcard_array['month']."-".$childrenoneidcard_array['day'];
                    if(strtotime($childrenonebirthday)===false){
                        throw new Exception(($key+1)."子女1身份证号码中生日数据异常");
                    }
                    $import_data['childrenonebirthday']=strtotime($childrenonebirthday);
                    $import_data['childrenoneidcard']=$childrenoneidcard;
                }

                //如果有小孩2名字
                if(!empty($childrentowname)){
                    $import_data['childrentowname']=$childrentowname;
                }
                //小孩2的性别
                if(!empty($childrentowsex)){
                    $import_data['childrentowsex']=$membersex[$childrentowsex];
                }
                //子女2身份证号码
                if(!empty($childrentowidcard)){
                    $childrentowidcard_array=m('idcard')->splitIdcard($childrentowidcard);
                    if(is_error($childrentowidcard_array)){
                        throw new Exception(($key+1)."子女2身份证号码异常：".$childrentowidcard_array['message']);
                    }
                    $childrentowbirthday=$childrentowidcard_array['year']."-".$childrentowidcard_array['month']."-".$childrentowidcard_array['day'];
                    if(strtotime($childrentowbirthday)===false){
                        throw new Exception(($key+1)."子女2身份证号码中生日数据异常");
                    }
                    $import_data['childrentowbirthday']=strtotime($childrentowbirthday);
                    $import_data['childrentowidcard']=$childrentowidcard;
                }

                if($cheack_mobile_repeat){
                    if($cheack_mobile_repeat){
                       // var_dump($import_data);
                        $status=pdo_update("ewei_shop_union_members",$import_data,array("id"=>$cheack_mobile_repeat));
                    }
                    $status ? $success++:$error++;
                }else{
                    $import_data['uniacid']=$_W['uniacid'];
                    $import_data['mobile_phone']=$mobile;
                    $import_data['union_id']=$_W['unionid'];
                    $import_data['add_time']=TIMESTAMP;

                    $status=pdo_insert("ewei_shop_union_members",$import_data);
                    $status ? $success++:$error++;
                }
                $mobile_phone[]=$mobile;
                $displays--;
            }

            if($mobile_phone){
                $mobile_reqpeat=$this->FetchRepeatMemberInArray($mobile_phone);
            }
            if(!empty($mobile_reqpeat)){
                $mobile_reqpeat=array_unique($mobile_reqpeat);
                throw new Exception("导入数据中重复的手机号".join(",",$mobile_reqpeat));
            }

            //对一级部门进行排序和关闭显示
           // pdo_update("ewei_shop_union_department",array('displayorder'=>0),array('parent_id'=>0,"union_id"=>$_W['unionid']));
            $i=count($displayers);
            foreach ($displayers as $item){
                $params=array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],':name'=>$item);
                $selectitem=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where uniacid=:uniacid and union_id=:union_id and level=1 and name=:name ",$params);
                pdo_update("ewei_shop_union_department",array("displayorder"=>$i*10,'enable'=>1),array('id'=>$selectitem['id']));
                $i--;
            }

            //二级部门进行排序和关闭显示
           // pdo_update("ewei_shop_union_department",array('displayorder'=>0),array('level'=>2,"union_id"=>$_W['unionid']));
            $i=count($displayers1);
            foreach ($displayers1 as $item){
                $params=array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],':name'=>$item);
                $selectitem=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where uniacid=:uniacid and union_id=:union_id and level=2 and name=:name ",$params);
                pdo_update("ewei_shop_union_department",array("displayorder"=>$i*10,'enable'=>1),array('id'=>$selectitem['id']));
                $i--;
            }

            //三级级部门进行排序和关闭显示
            //pdo_update("ewei_shop_union_department",array('displayorder'=>0),array('level'=>3,"union_id"=>$_W['unionid']));
            $i=count($displayers2);
            foreach ($displayers2 as $item){
                $params=array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],':name'=>$item);
                $selectitem=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where uniacid=:uniacid and union_id=:union_id and level=3 and name=:name ",$params);
                pdo_update("ewei_shop_union_department",array("displayorder"=>$i*10,'enable'=>1),array('id'=>$selectitem['id']));
                $i--;
            }

            //三级级部门进行排序和关闭显示
            //pdo_update("ewei_shop_union_department",array('displayorder'=>0),array('level'=>4,"union_id"=>$_W['unionid']));
            $i=count($displayers3);
            foreach ($displayers3 as $item){
                $params=array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],':name'=>$item);
                $selectitem=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where uniacid=:uniacid and union_id=:union_id and level=4 and name=:name ",$params);
                pdo_update("ewei_shop_union_department",array("displayorder"=>$i*10,'enable'=>1),array('id'=>$selectitem['id']));
                $i--;
            }


            pdo_commit();

        }catch (Exception $e){
            pdo_rollback();

            return  $this->model->show_json(0,$e->getMessage());
        }finally{
            @unlink($uploadfile);
        }

        return array("message"=>"导入成功数据".$success."失败数据".$error);
    }


    function FetchRepeatMemberInArray($array) {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique ( $array );
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc ( $array, $unique_arr );
        return $repeat_arr;
    }


    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_delete("ewei_shop_union_members",array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"ok");
    }

    function  get_cid_memberlist($cid,$peoplevale=array()){
        global $_W;
        //查询本级的用户
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$_W['unionid'],
            ':department'=>$cid,
        );
        $leve4member=pdo_fetchall("select * from ".tablename("ewei_shop_union_members")." where department=:department and union_id=:union_id and uniacid=:uniacid",$params);
        $leve4memberchildren=array();
        foreach ($leve4member as $member4){
            $active=false;
            if(!empty($peoplevale)){
                if(in_array($member4['id'],$peoplevale)){
                    $active=true;
                }
            }

            $leve4memberchildren[]=array(
                'value'=>$member4['id'],
                'name'=>$member4['name'],
                'active'=>$active,
                'last'=>true,
                'img'=>'',
                'children'=>array(),
            );
        }
        return $leve4memberchildren;
    }

    function getmemberlist(){
        global $_W;
        global $_GPC;
        $deid=empty($_GPC['deid']) ? 0 : intval($_GPC['deid']);
        $type=empty($_GPC['type']) ? '' : $_GPC['type'];
        if($deid && empty($_GPC['type'])){
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$deid);
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_document")." where id =:id and uniacid=:uniacid and union_id=:union_id",$paras);
            $peoplevale=array();
            if(isset($vo['peoplevale']) && !empty($vo['peoplevale'])){
                $peoplevale=explode(",",$vo['peoplevale']);
            }
        }elseif($deid && $type=="vote"){
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$deid);
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id =:id and uniacid=:uniacid and union_id=:union_id",$paras);
            $peoplevale=array();
            if(isset($vo['peoplevale']) && !empty($vo['peoplevale'])){
                $peoplevale=explode(",",$vo['peoplevale']);
            }
        }
        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=1 order by displayorder desc";
        $level1=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=2 order by displayorder desc";
        $level2=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=3 order by displayorder desc";
        $level3=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=4 order by displayorder desc";
        $level4=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");



        foreach ($level1 as $item){
            if($item['parent_id']==0){
                $children=array();
                foreach ($level2 as $lev2){
                    if($lev2['parent_id']==$item['id']){
                        //查询 第三级
                        $leve3children=array();

                        foreach ($level3 as $lev3){
                            if($lev3['parent_id']==$lev2['id']){

                                $leve4children=array();

                                foreach ($level4 as $lev4){
                                    if($lev4['parent_id']==$lev3['id']){
                                        $leve4memberchildren=$this->get_cid_memberlist($lev4['id'],$peoplevale);

                                        $leve4children[]=array(
                                            'name'=>$lev4['name'],
                                            'active'=>false,
                                            'last'=>false,
                                            'img'=>'',
                                            'children'=>$leve4memberchildren,
                                        );
                                    }

                                }

                                $leve3memberchildren=$this->get_cid_memberlist($lev3['id'],$peoplevale);
                                $leve4children=array_merge($leve4children,$leve3memberchildren);

                                $leve3children[]=array(
                                    'name'=>$lev3['name'],
                                    'active'=>false,
                                    'last'=>false,
                                    'img'=>'',
                                    'children'=>$leve4children,
                                );
                            }
                        }

                        $leve2memberchildren=$this->get_cid_memberlist($lev2['id'],$peoplevale);
                        $leve3children=array_merge($leve3children,$leve2memberchildren);
                        $level2children=array(
                            'name'=>$lev2['name'],
                            'active'=>false,
                            'last'=>false,
                            'img'=>'',
                            'children'=>$leve3children,
                        );
                        $children[]=$level2children;
                    }
                }
                $leve1memberchildren=$this->get_cid_memberlist($item['id'],$peoplevale);
                $children=array_merge($children,$leve1memberchildren);
                $level1children[]=array(
                    'name'=>$item['name'],
                    'active'=>false,
                    'last'=>false,
                    'img'=>'',
                    'children'=>$children,
                );
            }
        }

        $basedata[]=array(
            'name'=>$this->user_info['title'],
            'active'=>false,
            'last'=>false,
            'img'=>'',
            'children'=>$level1children,
        );

        include $this->template();
    }


}