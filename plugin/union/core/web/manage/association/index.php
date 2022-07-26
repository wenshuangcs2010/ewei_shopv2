<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{


    public function __construct($_com = '', $_init = false)
    {
        global $_W;
        parent::__construct($_com, $_init);


    }
    function auditor(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];

        pdo_update("ewei_shop_union_association_member",array('status'=>1),array('id'=>$id));

        $this->model->show_json(1,array("url"=>unionUrl("association"),'message'=>"ok"));
    }
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        if(!empty($_GPC['keywordes'])){
            $condition.=" and title like :keywordes";
            $paras[':keywordes']= "%".trim($_GPC['keywordes'])."%";
        }
        $sql="select * from ".tablename("ewei_shop_union_association").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association").$condition,$paras);
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
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_association")." where id=:id",array(":id"=>$id));
            if($_W['asoconfig']['usetime']){
                $vo['start_time']=date("Y-m-d H:i:s",$vo['start_time']);
                $vo['end_time']=date("Y-m-d H:i:s",$vo['end_time']);
            }
            if($vo['auditor']){
                $auditorname=pdo_fetchcolumn("select name from ".tablename("ewei_shop_union_members")." where id=:id",array(":id"=>$vo['auditor']));
            }
        }else{
            $vo['start_time']=date("Y-m-d H:i:s",time());
            $vo['end_time']=date("Y-m-d H:i:s",strtotime('+30 day'));
        }

        if($_W['ispost']){
            $start_time=$_GPC['start_time'];
            $end_time=$_GPC['end_time'];

            $assocount=intval($_GPC['assocount']);
            if(isset($_W['asoconfig']) && $_W['asoconfig']['asocount']==1){
                if($assocount<=0){
                    $this->model->show_json(0,"人数限制不能小于等于0");
                }
            }

            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'add_time'=>time(),
                'end_time'=>strtotime($end_time),
                'start_time'=>strtotime($start_time),
                'assocount'=>$assocount,
                'header_image'=>trim($_GPC['header_image']),
                'contacts'=>$_GPC['contacts'],
                'contactsphone'=>$_GPC['contactsphone'],
                'auditor'=>intval($_GPC['auditor']),
                'declaration'=>htmlspecialchars_decode($_GPC['declaration']),
            );

            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_association",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_association",$data);
            }
            $this->model->show_json(1,array("url"=>unionUrl("association"),'message'=>"ok"));
        }
        include $this->template("association/post");
    }
    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_association",array("is_delete"=>1),array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"ok");
    }


    function memberlist(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(!empty($id)){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_association")." where id=:id",array(":id"=>$id));
            $title=$vo['title'];
        }else{
            $title="全部";
        }
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association").
            $condition;
        $sql.=" order by add_time desc  ";
        $activelist = pdo_fetchall($sql, $paras);


        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where am.uniacid=:uniacid and am.union_id=:union_id ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        if($id){
            $condition.=" and am.association_id=:association_id ";
            $paras[':association_id']=$id;
        }
        if(!empty($_GPC['asso_id'])){
            $condition.=" and am.association_id=:association_id ";
            $paras[':association_id']=intval($_GPC['asso_id']);
        }
        $sql="select am.*,m.name as mname,a.title,m.mobile_phone,m.idcard,m.duties from ".tablename("ewei_shop_union_association_member").' am LEFT JOIN '.tablename("ewei_shop_union_members")." m ON am.member_id=m.id".
            " LEFT JOIN ".tablename("ewei_shop_union_association")." as a ON a.id=am.association_id ".
            $condition;
        if(!$_GPC['export']){
            $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }

        $list = pdo_fetchall($sql, $paras);

        if($_GPC['export']){
            $union_info=$this->model->get_union_info($_W['unionid']);
            $columns['columns']=array(
                array('title' => '单位', 'field' => 'unionname', 'width' => 32),
                array('title' => '姓名', 'field' => 'mname', 'width' => 16),
                array('title' => '职务', 'field' => 'duties', 'width' => 16),
                array('title' => '身份证号', 'field' => 'idcard', 'width' => 32),
                array('title' => '手机号', 'field' => 'mobile_phone', 'width' => 16),
                array('title' => '小组', 'field' => 'title', 'width' => 16),
                array('title' => '状态', 'field' => 'status', 'width' => 16),
            );
            foreach ($list as $key=>$value){
                $list[$key]['unionname']=$union_info['title'];
                if($value['status']==0){
                    $list[$key]['status']="未审核";
                }elseif($value['status']==1){
                    $list[$key]['status']="审核通过";
                }elseif ($value['status']==-1){
                    $list[$key]['status']="审核未通过";
                }
            }
            $columns['title']="小组数据";
            m('excel')->export($list,$columns);
            exit;
        }

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." am ".$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("association/memberlist");
    }
    function status(){
        global $_W;
        global $_GPC;
        $status=intval($_GPC['status']);
        $id=intval($_GPC['id']);

        pdo_update("ewei_shop_union_association_member",array("status"=>$status),array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"ok");
    }
    function notice(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where n.uniacid=:uniacid and n.union_id=:union_id  and n.is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select n.*,a.title as a_title from ".tablename("ewei_shop_union_notice")." as n LEFT JOIN ".tablename("ewei_shop_union_association")." as a ON a.id=n.association_id ".
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_notice")." n ".$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("association/notice");
    }
    function noticeadd(){
        global $_W;
        global $_GPC;
        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association").
            $condition;
        $assolist=pdo_fetchall($sql,$paras);
        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'declaration'=>trim($_GPC['declaration']),
                'uniacid'=>intval($_W['uniacid']),
                'union_id'=>intval($_W['unionid']),
                'association_id'=>intval($_W['association_id']),
                'create_time'=>time(),
            );

            pdo_insert("ewei_shop_union_notice",$data);
            $this->model->show_json(1,array("url"=>unionUrl("association/notice",'association/notice')));
        }
        include $this->template("association/noticeadd");
    }
    function noticedelete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_notice",array('is_delete'=>1),array('id'=>$id));
        $this->model->show_json(1,array("url"=>unionUrl("association/notice"),'message'=>"删除成功"));
    }
    function activity(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition="  where uniacid=:uniacid and union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association").
            $condition;
        $sql.=" order by add_time desc  ";
        $activelist = pdo_fetchall($sql, $paras);


        $condition="  where n.uniacid=:uniacid and n.union_id=:union_id  and n.deleted=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        if(!empty($_GPC['keywordes'])){
            $condition.=" and n.title like :keywords";
            $paras[':keywords']="%".trim($_GPC['keywordes'])."%";
        }
        if(!empty($_GPC['asso_id'])){
            $condition.=" and n.ass_id=:asso_id";

            $paras[':asso_id']=intval($_GPC['asso_id']);
        }

        $sql="select n.*,a.title as a_title from ".tablename("ewei_shop_union_association_activity")." as n LEFT JOIN ".tablename("ewei_shop_union_association")." as a ON a.id=n.ass_id ".
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_activity")." n ".$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("association/activity");
    }

    function activityadd(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        if($_W['ispost']){
            $data=array(
                'ass_id'=>intval($_GPC['association_id']),
                'title'=>trim($_GPC['title']),
                'create_time'=>time(),
                'description'=>htmlspecialchars_decode($_GPC['description']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
            );

            if($id){
                unset($data['create_time']);
                $ret=pdo_update("ewei_shop_union_association_activity",$data,array("id"=>$id));

            }else{
                pdo_insert("ewei_shop_union_association_activity",$data);
            }
            $this->message("添加修改成功",unionUrl("association/activity"));
           // $this->model->show_json(1,array("url"=>unionUrl("association/activity"),'message'=>"增加或修改成功"));
        }
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_association")." where uniacid=:uniacid and union_id =:union_id and is_delete=0";
        $list = pdo_fetchall($sql, $paras);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_association_activity")." where id=:id",array(":id"=>$id));
        }

        include $this->template("association/activityadd");
    }
    function activitydelete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_association_activity",array('deleted'=>1),array('id'=>$id));
        $this->model->show_json(1,array("url"=>unionUrl("association/activity"),'message'=>"删除成功"));
    }

    function memberdeleted(){
        global $_W;
        global $_GPC;

        pdo_delete("ewei_shop_union_association_member",array("id"=>$_GPC['id']));

        $this->model->show_json(1,array("url"=>unionUrl("association/memberlist"),'message'=>"删除成功"));
    }
    function asconfig(){
        global $_W;
        global $_GPC;

        $union_info=pdo_fetch("select * from ".tablename("ewei_shop_union_association_config")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));

        if($_W['ispost']){
            $data=array(
                'userdescradio'=>$_GPC['userdescradio'],
                'usetime'=>$_GPC['usetime'],
                'asocount'=>$_GPC['asocount'],
                'usecount'=>$_GPC['usecount'],
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'title'=>$_GPC['title'],
            );
            $id=intval($_GPC['id']);
            if($id){

                pdo_update("ewei_shop_union_association_config",$data,array("id"=>$id));
            }else{
            pdo_insert("ewei_shop_union_association_config",$data);
            }
            $this->model->show_json(1,array('message'=>"修改数据成功"));
        }

        include $this->template("association/asconfig");
    }

}