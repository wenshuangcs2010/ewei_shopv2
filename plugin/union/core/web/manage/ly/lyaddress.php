<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Lyaddress_EweiShopV2Page extends UnionWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params=array();
        $condition="  where deleted=0 ";
        $sql="select id,title,theme_id,grade_id,is_dscount,is_hasget,createtime from ".tablename("ewei_shop_union_ly_lyaddress").
            $condition;
        $sql.=" order by id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $list = pdo_fetchall($sql, $params);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_lyaddress").$condition,$params);
        $pager = pagination($total, $pindex, $psize);
        $theme=$this->model->theme;
        $grade=$this->model->grade;
        foreach ($list as $item)
        {

        }
        include $this->template();
    }

    public function add(){
        global $_W;
        global $_GPC;
        $this->post();
    }
    public  function edit(){
        global $_W;
        global $_GPC;
        $this->post();
    }
    public function post(){
        global $_W;
        global $_GPC;

        $theme=$this->model->theme;
        $grade=$this->model->grade;



        $activity_id=0;

        $id=intval($_GPC['id']);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_lyaddress")." where id=:id",array(":id"=>$id));
            $activity_id=$vo['activity_id'];
        }
        //获取全部的上级地址

       $arddresslsit= pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_lyaddress")." where enabled=0");


        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'mobilephone'=>trim($_GPC['mobilephone']),
                'reception'=>trim($_GPC['reception']),
                'dishes'=>trim($_GPC['dishes']),
                'spot'=>trim($_GPC['spot']),
                'address'=>trim($_GPC['address']),
                'lng'=>trim($_GPC['lng']),
                'lat'=>trim($_GPC['lat']),
                'is_dscount'=>intval($_GPC['is_dscount']),
                'is_hasget'=>intval($_GPC['is_hasget']),
                'displayorder'=>intval($_GPC['displayorder']),
                'restaurant_dscount'=>intval($_GPC['restaurant_dscount']),
                'hasget_dscount'=>intval($_GPC['hasget_dscount']),
                'theme_id'=>intval($_GPC['theme_id']),
                'grade_id'=>intval($_GPC['grade_id']),
                'is_hot'=>intval($_GPC['is_hot']),
                'volume'=>intval($_GPC['volume']),
                'header_image'=>trim($_GPC['header_image']),
                'position'=>trim($_GPC['position']),
                'activity_id'=>intval($_GPC['activity_id']),
                'enabled' => intval($_GPC['enabled']),
                'description'=>htmlspecialchars_decode(trim($_GPC['description'])),
                "createtime"=>TIMESTAMP,
            );
            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_ly_lyaddress",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_ly_lyaddress",$data);
            }
            $this->message("数据处理成功",unionUrl("ly/lyaddress"));

        }
        include $this->template();
    }
}