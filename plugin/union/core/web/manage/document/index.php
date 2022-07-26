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

        $is_https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
        if($is_https){
            $backurl="https://".$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }else{
            $backurl="http://".$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");


        $condition="  where uniacid=:uniacid and union_id=:union_id  and isdelete=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);

        if(isset($_GPC['categoryid']) && $_GPC['categoryid']!=''){
            $category_list=$this->get_categorylist($_GPC['categoryid']);


            $condition.=" and category_id in (".implode(",",$category_list).")";



           // $paras[':categoryid']=intval($_GPC['categoryid']);
        }
        if($_GPC['keywordes']!=''){
            $condition.=" and title like '%".$_GPC['keywordes']."%' ";
            //$paras[':keywordes']=trim($_GPC['keywordes']);
        }


        $sql="select id,title,add_time,category_id,displayorder from ".tablename("ewei_shop_union_document").
            $condition;
        $sql.=" order by add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);

        foreach ($list as &$value){
            $value['categoryname']=$categorylist[$value['category_id']]['catename'];
            $value['backurl']=base64_encode($backurl);
        }
        unset($value);



        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_document").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template("document/index");
    }

    private function get_categorylist($categoryid){
        static $categorylist=array();
        $categorylist[]=$categoryid;
        $catertoy=pdo_fetchall("select id from ".tablename("ewei_shop_union_document_category")." where parent_id=:id",array(":id"=>$categoryid));
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

    function addcategory(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $parent_id=intval($_GPC['parent_id']);
        if(!empty($id)){
           $vo=pdo_fetch("select * from ".tablename('ewei_shop_union_document_category')." where id=:id",array(":id"=>$id));
           if($vo['parent_id']){
               $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_document_category')." where id=:id",array(":id"=>$vo['parent_id']));
           }

        }
        if(!empty($parent_id)){
            $parent=pdo_fetch("select * from ".tablename('ewei_shop_union_document_category')." where id=:id",array(":id"=>$parent_id));
        }

        if($_W['ispost']){
            if($_GPC['parent_id']>0){

                 $level=pdo_fetchcolumn("select level from ".tablename("ewei_shop_union_document_category")." where 1 and id=:id",array(":id"=>$_GPC['parent_id']));
            }
            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'parent_id'=>intval($_GPC['parent_id']),
                'images'=>$_GPC['images'],
                'head_images'=>$_GPC['head_images'],
                'enable'=>intval($_GPC['enable']),
                'catename'=>trim($_GPC['catename']),
                'createtime'=>time(),
                'displayorder'=>intval($_GPC['displayorder']),
                'is_index'=>intval($_GPC['is_index']),

                'level'=>$_GPC['parent_id']>0 ? $level+1 :1,
            );

            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_document_category",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_document_category",$data);
            }
            $this->model->show_json(1,"添加修改成功");

        }

        include $this->template();
    }
    function deletecategory(){
        global $_GPC;
        global $_W;
        $id=intval($_GPC['id']);
        $status=pdo_delete("ewei_shop_union_document_category",array("id"=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        if($status){
            $this->model->show_json(1,'删除成功');
        }
        $this->model->show_json(1,'删除失败,请重试');
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
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1 ' . $condition . '  ORDER BY parent_id asc ',$params);

//        foreach ($list as &$value){
//            if($value['parent_id']>0){
//                $value['parentname']=$list[$value['parent_id']]['catename'];
//            }
//        }
//        unset($value);
        $category=$this->model->getLeaderArray($list);

        include $this->template();
    }
    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        //查询现有可用分类
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc  ', $params);
        $category=$this->model->getLeaderArray($list);
        $column=array_column($category,'displayorder');
        array_multisort($column, SORT_DESC, $category);
        if($id){
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id);
            $title="修改公文";
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_document")." where id =:id and uniacid=:uniacid and union_id=:union_id",$paras);
            if($vo['peoplevale']){

               $peoplevale=pdo_fetchall("select name from ".tablename("ewei_shop_union_members")." where id in (".$vo['peoplevale'].")");
            }
            if($vo['endtime']){
                $vo['endtime']=date("Y-m-d H:i:s",$vo['endtime']);
            }

        }else{
            $title="添加公文";
        }

        $status=$this->model->checkunion();

        //查询我的直属工会
        if($status){
            $sql="select * from ".tablename("ewei_shop_union_user")." where status=1 and  parent_id=:parent_id and uniacid=:uniacid";
            $union_list=pdo_fetchall($sql,array(":parent_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));

        }

        if($_W['ispost']){
            if(isset($_GPC['backurl']) && !empty($_GPC['backurl'])){
                $backurl=base64_decode($_GPC['backurl']);
            }else{
                $backurl=unionUrl("document");
            }
            if(empty($_GPC['category_id'])){
                $this->message("需要先添加一个分类");
            }


            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'title'=>trim($_GPC['title']),
                'enclosure_url'=>trim($_GPC['docurl']),
                'description'=>htmlspecialchars_decode($_GPC['description']),
                'add_time'=>time(),
                'endtime'=>empty($_GPC['endtime']) ? 0 :strtotime($_GPC['endtime']),
                'header_image'=>$_GPC['header_image'],
                'category_id'=>intval($_GPC['category_id']),
                'show_type'=>empty($_GPC['show_type']) ? 0 :intval($_GPC['show_type']),
                'ishot'=>empty($_GPC['ishot']) ? 0 :intval($_GPC['ishot']),
                'link'=>empty($_GPC['link']) ? '' :trim($_GPC['link']),
                'peopletype'=>intval($_GPC['peopletype']),
                'peoplevale'=>!empty($_GPC['peoplevale']) ? $_GPC['peoplevale'] :'',
                'show_typevalue'=>!empty($_GPC['show_typevalue']) ? implode(",",$_GPC['show_typevalue']) :'',
                'displayorder'=>intval($_GPC['displayorder']),
            );

            if($data['peopletype']==1 && empty($data['peoplevale'])){
                $this->message("需要指定接受人");
            }

            if($id){
                if(empty($_GPC['docurl'])){
                    unset($data['enclosure_url']);
                    unset($data['add_time']);
                }
                pdo_update("ewei_shop_union_document",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_document",$data);
            }
            $this->message("数据处理成功",$backurl);
        }
        include $this->template("document/post");
    }

}