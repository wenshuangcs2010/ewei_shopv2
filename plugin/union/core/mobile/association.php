<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Association_EweiShopV2Page extends UnionMobilePage
{


    public function __construct()
    {
        global $_W;
        parent::__construct();
        $asoconfig=pdo_fetch("select * from ".tablename("ewei_shop_union_association_config")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        if(!empty($asoconfig)){
            $_W['asoconfig']=$asoconfig;
        }

    }

    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="兴趣小组";
        $indexid=intval($_GPC['indexid']);
        if($indexid>0){
            $uniontitle=pdo_fetchcolumn("select title from ".tablename("ewei_shop_union_menu")." where id=:indexid",array(":indexid"=>$indexid));
            $_W['union']['title']=$uniontitle;
        }
        $condition = ' where 1 and assomber.uniacid = :uniacid and assomber.union_id=:union_id and asinfo.is_delete=0 ';
        $params=array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);
        $condition.=" and  assomber.openid=:openid ";
        $params[':openid']=$_W['openid'];
        $sql="select asinfo.* from ".tablename("ewei_shop_union_association_member")." as assomber ".
            "LEFT JOIN ".tablename("ewei_shop_union_association")." as asinfo ON asinfo.id=assomber.association_id ".$condition;

        $alllist=pdo_fetchall($sql,$params);


        include $this->template();
    }

    public function get_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];

        $openid=$_W['openid'];

        $assolist=pdo_fetchall("select association_id from ".tablename("ewei_shop_union_association_member")." where openid =:openid and union_id=:union_id  and status=1 and uniacid=:uniacid",array(":openid"=>$openid,':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']),'association_id');


        $category_id=intval($_GPC['category_id']);

        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' aa.create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and aa.uniacid = :uniacid and aa.union_id=:union_id and aa.deleted=0  ';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        if(!empty($assolist) && empty($category_id)){
            $keylsit=array_keys($assolist);
            $condition.=" and aa.ass_id in (".implode(",",$keylsit).") ";
        }else{
            if($category_id>0){
                $condition.=" and aa.ass_id=:category_id ";
                $params[':category_id']=$category_id;
            }else{
                $condition.=" and aa.ass_id=0 ";
            }
        }



        $sql="select aa.*,a.title as asstitle from ".tablename("ewei_shop_union_association_activity")." as aa LEFT JOIN ".tablename("ewei_shop_union_association")." as a  ON a.id =aa.ass_id "
            ."  where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_association_activity')." as aa where 1 ".$condition;

        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['datetime']=date("Y-m-d",$row['create_time']);
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
      }

    public function view(){
        global $_W;
        global $_GPC;

        $_W['union']['title']="小组详情";
        $id=intval($_GPC['id']);
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $condition = ' and aa.uniacid = :uniacid and aa.union_id=:union_id and aa.id=:id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":id"=>$id);

        $sql="select aa.*,a.title as asstitle from ".tablename("ewei_shop_union_association_activity")." as aa LEFT JOIN ".tablename("ewei_shop_union_association")." as a  ON a.id =aa.ass_id "
            ."  where 1 {$condition}";
        $article=pdo_fetch($sql,$params);


        pdo_update("ewei_shop_union_association_activity",array("readcount"=>$article['readcount']+1),array("id"=>$article['id']));
        include $this->template();
    }
    public function myass(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="兴趣小组";
        include $this->template();
    }

    /**
     * 查询用户没有参与的小组
     */
    public function myasso_not_join(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $condition = ' and uniacid = :uniacid and union_id=:union_id  and is_delete=0';
        $params=array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $assolist=pdo_fetchall("select association_id from ".tablename("ewei_shop_union_association_member")." where openid =:openid and union_id=:union_id  and status=1 and uniacid=:uniacid",array(":openid"=>$_W['openid'],':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']),'association_id');



        if(!empty($assolist)){
            $keylsit=array_keys($assolist);
            $condition.=" and id not in (".implode(",",$keylsit).") ";
        }

        $sql=" select * from ".tablename("ewei_shop_union_association")
            ."  where 1 {$condition} ORDER BY add_time desc LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_association')."  where 1 ".$condition;

        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['status']=-1;
            $row['header_image']=tomedia($row['header_image']);
            $row['count']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where association_id=:association_id and status=1 and   uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],":association_id"=>$row['id']));

        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }


    /*
     * 查询自己参加了几个小组
     * */
    public function myasso_new_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and assomber.uniacid = :uniacid and assomber.union_id=:union_id ';
        $params=array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $condition.=" and  assomber.openid=:openid ";
        $params[':openid']=$_W['openid'];
        $sql="select asinfo.* from ".tablename("ewei_shop_union_association_member")." as assomber ".
            "LEFT JOIN ".tablename("ewei_shop_union_association")." as asinfo ON asinfo.id=assomber.association_id"
            ."  where 1 {$condition} and asinfo.is_delete=0 ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_association_member')." as assomber  where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        foreach ($list as &$row){
            $row['status']=1;
            $row['header_image']=tomedia($row['header_image']);
            $row['count']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where association_id=:association_id and status=1 and   uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],":association_id"=>$row['id']));

        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));

    }


    public function myasso_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and uniacid = :uniacid and union_id=:union_id and is_delete=0';
        $params=$pa = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $sql="select * from ".tablename("ewei_shop_union_association")
         ."  where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_association')."  where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        $params[':openid']=$_W['openid'];
        foreach ($list as &$row){
            $pa[':association_id']=$row['id'];
            $params[':association_id']=$row['id'];
            $status=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_association_member")." where association_id=:association_id and  openid =:openid and uniacid=:uniacid and union_id=:union_id",$params);
            $row['status']=is_numeric($status) ? $status : -1;
            $row['count']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where association_id=:association_id and status=1 and   uniacid=:uniacid and union_id=:union_id",$pa);
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function assview(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="详情";
        $param=array(":id"=>$id,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association")." where id=:id and union_id=:union_id and uniacid=:uniacid";
        $vo=pdo_fetch($sql,$param);

        $type='join';


        $auditorprivte=false;
        $bonttontext="加入小组";
         //需要做规定的的时间内加入
        if(isset($_W['asoconfig']) && $_W['asoconfig']['usetime']==1){
            //查询用户有没有加入当前小组和用户
           if($vo['start_time']>time() || $vo['end_time']<=time()){
               $type='fullmember';
               $bonttontext="小组审核时间到期";
           }
        }
        //如果有控制每个组所需要的人数
        if(isset($_W['asoconfig']) && $_W['asoconfig']['asocount']==1){
            $countmember=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where uniacid =:uniacid and union_id=:union_id and association_id=:id",$param);
            if($vo['assocount']<$countmember){
                $type='fullmember';
                $bonttontext="小组人员已满";
            }
        }
        $param=array(":id"=>$id,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],':openid'=>$_W['openid']);
        $count_member=pdo_fetch("select * from ".tablename("ewei_shop_union_association_member")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and association_id=:id",$param);
        if(!empty($count_member)){
            $type='signout';
            $bonttontext="退出小组";
        }
        //检查当前用户 是不上审核用户
        if($this->member['id']==$vo['auditor']){
            //小组组长选项

            $auditorprivte=true;
        }
        include $this->template();
    }

    public function addassociation(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $param=array(":id"=>$id,":union_id"=> $_W['unionid'],":uniacid"=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association")." where id=:id and union_id=:union_id and uniacid=:uniacid";
        $vo=pdo_fetch($sql,$param);

        $param=array(":id"=>$id,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],':openid'=>$_W['openid']);
        $count_member=pdo_fetch("select * from ".tablename("ewei_shop_union_association_member")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and association_id=:id",$param);
        if(empty($count_member)){
            $type='join';
            $bottontext="立即加入";
        }else{
            $type='signout';
            $bottontext="退出小组";
        }


        if(isset($_W['asoconfig']) && $_W['asoconfig']['usetime']==1){
            if($vo['start_time']>time() || $vo['end_time']<=time()){
                $type='timeout';
                $bottontext="小组审核时间到期";
            }
        }

        include $this->template();
    }


    public function addtions(){
        global $_W;
        global $_GPC;
        $type=$_GPC['type'];
        $assomemberid=intval($_GPC['assomemberid']);
        $assoid=intval($_GPC['assoid']);
        if(empty($assoid)){
            show_json(0,'参数错误请返回重试');
        }
        $openid=$_W['openid'];
        $param=array(":id"=>$assoid,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],':openid'=>$openid);
        $count_member=pdo_fetch("select * from ".tablename("ewei_shop_union_association_member")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and association_id=:id",$param);

        $sql="select * from ".tablename("ewei_shop_union_association")." where id=:id and union_id=:union_id and uniacid=:uniacid";
        $vo=pdo_fetch($sql,array(":id"=>$assoid,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],':id'=>$assoid));

        if(empty($count_member) && $type=="join"){
            //判断加入总数
            if(isset($_W['asoconfig']) && $_W['asoconfig']['usecount']>0){
                $openid=$_W['openid'];
                $param=array(":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],':openid'=>$openid);
                $allcount_member=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where openid=:openid and uniacid =:uniacid and union_id=:union_id",$param);
                if($allcount_member>=$_W['asoconfig']['usecount']){
                    show_json(0,'您加入的小组已到达上限');
                }
            }
            $data=array(
                'association_id'=>$assoid,
                'member_id'=>$this->member['id'],
                'add_time'=>time(),
                'openid'=>$_W['openid'],
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'desc'=>trim($_GPC['content']),
                'status'=>0,
            );
            if($_W['asoconfig']['usecount']==0){
                $data['status']=1;
            }


            if($this->member['id']==$vo['auditor']){
                $data['status']=1;//审核者自己跳过审核步骤
            }
            pdo_insert("ewei_shop_union_association_member",$data);
            show_json(1,array('id'=>pdo_insertid(),'msg'=>"加入小组成功"));
        }
        if($type=='signout'){
            $ret=pdo_delete("ewei_shop_union_association_member",array("id"=>$assomemberid));
            if($ret){
                show_json(1,array('id'=>$assomemberid,'msg'=>"退出小组成功"));
            }
            show_json(0,'退出小组失败,请检查数据');
        }
        show_json(0,'位置参数错误，请联系管理员');
    }

    public function viewlist(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];

        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;

        $order = ' am.status asc ';

        $openid=$_W['openid'];
        $param=array(":id"=>$id,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],':openid'=>$openid);
        $count_member=pdo_fetch("select * from ".tablename("ewei_shop_union_association_member")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and association_id=:id",$param);

           if(!empty($count_member)){
               $order="if(am.id={$count_member['id']},1,2),am.add_time desc";

           }


        $condition = ' and am.uniacid = :uniacid and am.union_id=:union_id and association_id=:id ';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":id"=>$id);



        $sql="select am.id,m.avatar,ud.`name` as udname,am.add_time,um.`name`,am.status,am.association_id from ".tablename("ewei_shop_union_association_member")
            ." as am LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid = am.openid and m.uniacid=am.uniacid "
            ." LEFT JOIN ".tablename("ewei_shop_union_members")." as um ON um.openid =am.openid and um.union_id=am.union_id and um.uniacid=am.uniacid "
        ." LEFT JOIN ".tablename("ewei_shop_union_department")." as ud ON ud.id=um.department "
            ."  where 1 {$condition} ORDER BY {$order}  LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_association_member')." as am where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        $union_info=$this->model->get_union_info($union_id);
        foreach ($list as &$li){
            $li['add_time']=date("Y-m-d",$li['add_time']);
            $li['dname']=$union_info['title'];
        }
        unset($li);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function join_add(){
        global $_W;
        global $_GPC;


        if($_W['ispost']){
            $id=intval($_GPC['id']);
            $status=intval($_GPC['status']);
            if(empty($id)){
                show_json(0, "数据异常");
            }
            if($status!=-1){
                show_json(0, "数据异常");
            }
            $membercount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where association_id=:id and openid=:openid",array(":id"=>$id,':openid'=>$_W['openid']));
            if($membercount){

                show_json(0, "等待审核中");

            }

            $data=array(
                'association_id'=>$id,
                'member_id'=>$this->member['id'],
                'add_time'=>time(),
                'openid'=>$_W['openid'],
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
            );
            pdo_insert("ewei_shop_union_association_member",$data);
            show_json(1,array('id'=>pdo_insertid(),'msg'=>"ok"));
        }

    }

    function examine(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $union_info=$this->model->get_union_info($union_id);
        $asso_id=intval($_GPC['assoid']);
        $params=array(
            ':id'=>intval($_GPC['id']),
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$union_id,
            'association_id'=>$asso_id,
        );
        $sql="select w.*,m.name as username,d.name as department,m.sex from ".tablename('ewei_shop_union_association_member')." as w"
            ." LEFT JOIN  ".tablename("ewei_shop_union_members")." as m ON m.openid=w.openid and m.union_id=:union_id "
            ." LEFT JOIN  ".tablename("ewei_shop_union_department")." as d ON m.department=d.id "
            ." where w.id=:id and w.uniacid=:uniacid and w.union_id=:union_id and w.association_id=:association_id";

        $item=pdo_fetch($sql,$params);
        $param=array(":id"=>$asso_id,":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association")." where id=:id and union_id=:union_id and uniacid=:uniacid";
        $vo=pdo_fetch($sql,$param);


        include $this->template();
    }


    function examinestatus(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $status=intval($_GPC['status']);
        $sql="select * from ".tablename("ewei_shop_union_association_member")." where id=:id and uniacid=:uniacid";
        $item=pdo_fetch($sql,array(":uniacid"=>$_W['uniacid'],":id"=>$id));
        if(empty($item)){
            show_json(0,'用户已经取消申请');
        }
        if($item['status']==-1){
            show_json(0,'用户已经被审核');
        }
        $param=array(":id"=>$item['association_id'],":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association")." where id=:id and union_id=:union_id and uniacid=:uniacid";
        $vo=pdo_fetch($sql,$param);
        if($_W['asoconfig']['asocount']){
            $param=array(":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],'association_id'=>$item['association_id']);
            $allcount_member=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where  status=1 and uniacid =:uniacid and union_id=:union_id and association_id=:association_id",$param);
            if($allcount_member>=$vo['assocount']){
                show_json(0,'抱歉！小组已达到人数上线无法进行审核');
            }
        }
        if($status==1){
            pdo_update('ewei_shop_union_association_member',array('status'=>1),array("id"=>$item['id']));
            show_json(1,'审核通过');
        }
        if($status==-1){
            pdo_delete("ewei_shop_union_association_member",array('id'=>$item['id']));
            show_json(1,'审核拒绝');
        }


    }
}