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

        }

        if($_W['ispost']){

            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'add_time'=>time(),
                'declaration'=>trim($_GPC['declaration']),
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
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where am.uniacid=:uniacid and am.union_id=:union_id ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select am.*,m.name as mname,a.title from ".tablename("ewei_shop_union_association_member").' am LEFT JOIN '.tablename("ewei_shop_union_members")." m ON am.member_id=m.id".
            " LEFT JOIN ".tablename("ewei_shop_union_association")." as a ON a.id=am.association_id ".
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
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
        pdo_update("ewei_shop_union_association",array('is_delete'=>1),array('id'=>$id));
        $this->model->show_json(1,array("url"=>unionUrl("association/notice"),'message'=>"删除成功"));
    }
    function activity(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where n.uniacid=:uniacid and n.union_id=:union_id  and n.deleted=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
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
            $this->model->show_json(1,array("url"=>unionUrl("association/activity"),'message'=>"增加或修改成功"));
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
        pdo_update("ewei_shop_union_association",array('deleted'=>1),array('id'=>$id));
        $this->model->show_json(1,array("url"=>unionUrl("association/activity"),'message'=>"删除成功"));
    }

}