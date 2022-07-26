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

        if(isset($_GPC['categoryid']) && $_GPC['categoryid']!=''){
            $category_list=$this->get_categorylist($_GPC['categoryid']);


            $condition.=" and category_id in (".implode(",",$category_list).")";
        }
        if($_GPC['keywordes']!=''){

             $condition.=" and title like '%".$_GPC['keywordes']."%'";
        }
        $sql="select * from ".tablename("ewei_shop_union_memberactivity").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");

        foreach ($list as &$row){
            $row['signcount']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:tid",array("tid"=>$row['id']));
            $row['catename']=$categorylist[$row['category_id']]['catename'];
        }
        unset($row);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("memberactivity/index");
    }
    private function get_categorylist($categoryid){
        static $categorylist=array();
        $categorylist[]=$categoryid;
        $catertoy=pdo_fetchall("select id from ".tablename("ewei_shop_union_memberactivity_category")." where parent_id=:id",array(":id"=>$categoryid));
        foreach ($catertoy as $cate){
            $this->get_categorylist($cate['id']);
        }
        return $categorylist;
    }

    function add(){
        $this->post();
    }
    function edit(){
        $this->post();
    }

    function qrcode(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(empty($id)){
            $this->model->show_json(1,"参数错误");
        }
        $url=mobileUrl("union/memberactivity/view",array("id"=>$id,'union_id'=>$_W['unionid']),true);
        $files=m("qrcode")->createQrcode($url);
        header('location: ' . $files);
    }

    function category(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid and union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'],":union_id"=>$_W['unionid']);
        if ($_GPC['status'] != '')
        {
            $condition .= ' and enable=' . intval($_GPC['status']);
        }
        if (!(empty($_GPC['keyword'])))
        {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' and catename  like :keyword';
            $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
        }
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1 ' . $condition . '  ORDER BY parent_id asc' , $params,"id");

        $category=$this->model->getLeaderArray($list);


        include $this->template();
    }

    function addcategory(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $parent_id=intval($_GPC['parent_id']);
        if(!empty($id)){
            $vo=pdo_fetch("select * from ".tablename('ewei_shop_union_memberactivity_category')." where id=:id",array(":id"=>$id));
            if($vo['parent_id']){
                $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_memberactivity_category')." where id=:id",array(":id"=>$vo['parent_id']));
            }
        }
        if(!empty($parent_id)){
            $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_memberactivity_category')." where id=:id",array(":id"=>$parent_id));
        }

        if($_W['ispost']){
            if($_GPC['parent_id']>0){

                $level=pdo_fetchcolumn("select level from ".tablename("ewei_shop_union_memberactivity_category")." where 1 and id=:id",array(":id"=>$_GPC['parent_id']));
            }
            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'parent_id'=>intval($_GPC['parent_id']),
                'images'=>$_GPC['images'],
                'enable'=>intval($_GPC['enable']),
                'catename'=>trim($_GPC['catename']),
                'head_images'=>trim($_GPC['head_images']),
                'displayorder'=>intval($_GPC['displayorder']),
                'is_index'=>intval($_GPC['is_index']),
                'createtime'=>time(),
                'level'=>$_GPC['parent_id']>0 ? $level+1 :1,
            );
            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_memberactivity_category",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_memberactivity_category",$data);
            }
            $this->model->show_json(1,"添加修改成功");

        }

        include $this->template();
    }
    function deletecategory(){
        global $_GPC;
        global $_W;
        $id=intval($_GPC['id']);
        $status=pdo_delete("ewei_shop_union_memberactivity_category",array("id"=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        if($status){
            $this->model->show_json(1,'删除成功');
        }
        $this->model->show_json(1,'删除失败,请重试');
    }






    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        //查询现有可用分类
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid and level<=2  ORDER BY displayorder desc, id DESC  ', $params);

        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity")." where id=:id",array(":id"=>$id));
            $vo['start_time']=date("Y-m-d H:i:s",$vo['start_time']);
            $vo['end_time']=date("Y-m-d H:i:s",$vo['end_time']);
            $vo['a_start_time']=date("Y-m-d H:i:s",$vo['a_start_time']);
            $vo['a_end_time']=date("Y-m-d H:i:s",$vo['a_end_time']);
        }
        $parent=$this->model->checkparent_children($_W['unionid']);
        if($parent['parent_id']>0){
            //查询上级现在有发送那些活动给我
            $parentlist=pdo_fetchall("select * from ".tablename("ewei_shop_union_memberactivity")." where uniacid=:uniacid and union_id=:union_id and is_child_join=1 and a_start_time<:times and a_end_time>:times",array(":uniacid"=>$_W['uniacid'],':union_id'=>$parent['parent_id'],":times"=>time()),'id');

        }
        //新曾是否需要审核用户
        /**
         * 是否需要绑定上级ID
         *  是否发布到下级
         * 如果是绑定上级的活动 要强制审核
         */
        if($_W['ispost']){
            $start=$_GPC['start'];
            $end=$_GPC['end'];
            $signstart=$_GPC['signstart'];
            $signend=$_GPC['signend'];
            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'start_time'=>strtotime($start),
                'end_time'=>strtotime($end),
                'a_start_time'=>strtotime($signstart),
                'a_end_time'=>strtotime($signend),
                'create_time'=>time(),
                'status'=>intval($_GPC['status']),
                'declaration'=>htmlspecialchars_decode($_GPC['declaration']),
                'originator'=>trim($_GPC['originator']),
                'phone'=>trim($_GPC['phone']),
                'images'=>trim($_GPC['images']),
                'category_id'=>intval($_GPC['category_id']),
                'membercount'=>intval($_GPC['membercount']),
                'autoverify'=>intval($_GPC['autoverify']),
                'is_child_join'=>intval($_GPC['is_child_join']),
                'parent_activity_id'=>intval($_GPC['parent_activity_id']),
                'lng'=>trim($_GPC['lng']),
                'lat'=>trim($_GPC['lat']),
                'address'=>trim($_GPC['address']),
            );

            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_memberactivity",$data,array('id'=>$id));
            }else{
                pdo_insert("ewei_shop_union_memberactivity",$data);
            }
            $this->model->show_json(1,"ok");
        }
        include $this->template("memberactivity/post");
    }

    private function get_activity($id){
        static $ids=array();

        $ids[]=$id;
        $parent_activity_id_list=pdo_fetchall("select id from ".tablename("ewei_shop_union_memberactivity")." where parent_activity_id=:id",array(":id"=>$id));

        foreach ($parent_activity_id_list as $parent_id){
            $this->get_activity($parent_id['id']);
        }

        return $ids;
    }

    public function showpeople(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $ids=$this->get_activity($id);

        $listcount=count($ids);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        if($listcount>1){
            $condition="  where a.uniacid=:uniacid  and am.activity_id in (".implode(",",$ids).") and if(am.union_id=".$_W['unionid'].",am.status is not null,am.status=2) order by create_time desc ";
            $paras=array(':uniacid'=>$_W['uniacid']);

        }else{
            $condition="  where a.uniacid=:uniacid and a.union_id=:union_id and am.activity_id=:id  order by create_time desc ";
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$id);
        }
        $sql="select am.*,m.name as m_name,u.title as unionname,m.mobile_phone as phonenumber from ".tablename("ewei_shop_union_memberactivity_sgin")
            .' am LEFT JOIN '.tablename("ewei_shop_union_members") ." m ON am.openid=m.openid and am.union_id=m.union_id ".
            " LEFT JOIN ".tablename("ewei_shop_union_memberactivity")." as a ON a.id=am.activity_id ".
            ' LEFT JOIN '.tablename("ewei_shop_union_user") ." u ON a.union_id=u.id".
            $condition;

        if(!$_GPC['export']==1){
            $sql.=" LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }

        $list = pdo_fetchall($sql, $paras);

        if($_GPC['export']==1){
           // $sql='select * from '.tablename("ewei_shop_union_memberactivity_sgin")." where uniacid=:uniacid and union_id=:union_id and activity_id=:id";
            //$list = pdo_fetchall($sql, $paras);
            foreach ($list as $key=> $value){
                if($value['status']!=2){
                    unset($list[$key]);
                }
            }
            $params_export=array(
                "title" => "活动数据导出",
                "columns" => array(
                    array('title' => '单位', 'field' => 'unionname', 'width' => 12),
                    array('title' => '名称', 'field' => 'm_name', 'width' => 12),
                    array('title' => '电话', 'field' => 'phonenumber', 'width' => 12),
                    array('title' => '备注', 'field' => 'description', 'width' => 12),

                )
            );
            m('excel')->export($list,$params_export);
            exit;
        }

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity_sgin")." where  activity_id=:id",array(':id'=>$id));
        $pager = pagination($total, $pindex, $psize);
        include $this->template("memberactivity/memberlist");
    }
    function memberlistdeltet(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        pdo_delete("ewei_shop_union_memberactivity_sgin",array("id"=>$id,"uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        $this->model->show_json(1,"删除成功");
    }
    function memberlistedit(){
        global $_W;
        global $_GPC;

        $id=intval($_GPC['id']);
        $activityid=intval($_GPC['activityid']);
        $sql="select am.*,m.name as m_name,m.mobile_phone as phonenumber from ".tablename("ewei_shop_union_memberactivity_sgin")
            .' am LEFT JOIN '.tablename("ewei_shop_union_members") ." m ON am.openid=m.openid and am.union_id=m.union_id ".
            " where am.uniacid=:uniacid and am.union_id=:union_id and am.id=:id  order by create_time desc ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id);
        $vo = pdo_fetch($sql, $paras);
        if($_W['ispost']){
            $phone=trim($_GPC['phone']);
            $data=array(
                'description'=>trim($_GPC['description']),
                'dataurl'=>trim($_GPC['docurl']),
                'status'=>intval($_GPC['status'])
            );
            if($id){
                pdo_update("ewei_shop_union_memberactivity_sgin",$data,array("id"=>$id));
            }else{
                $item=pdo_fetch("select * from ".tablename("ewei_shop_union_members")." where mobile_phone=:mobile_phone and union_id=:unionid and uniacid=:uniacid",array(":mobile_phone"=>$phone,':unionid'=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
                if(empty($item)){
                    $this->model->show_json(0,"找不到用户");
                }
                if(empty($item['openid'])){
                    $this->model->show_json(0,"当前用户尚未登录或者绑定手机号");
                }
                $activityidsigncount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity_sgin")." where openid=:openid and union_id=:union_id",array(":openid"=>$item['openid'],":union_id"=>$_W['unionid']));
               if($activityidsigncount>0){
                   $this->model->show_json(0,"此用户已经报名,请勿重复添加");
               }
               $data['openid']=$item['openid'];
               $data['create_time']=TIMESTAMP;
               $data['activity_id']=$activityid;
               $data['union_id']=$_W['unionid'];
               $data['uniacid']=$_W['uniacid'];
                pdo_insert("ewei_shop_union_memberactivity_sgin",$data);
            }
            $this->model->show_json(1,"ok");
        }
        include $this->template("memberactivity/memberlistedit");
    }





}