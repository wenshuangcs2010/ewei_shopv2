<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Lyaddressline_EweiShopV2Page extends UnionWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params=array();
        $condition="  where deleted=0 ";
        if($_GPC['keywordes']!=''){
            $condition.=" and title like :keyword";
            $params[':keyword']="%".$_GPC['keywordes']."%";
        }
        if($_W['isoperator']==1){
            $condition.=" AND userid=:userid";
            $params[':userid']=$_W['unionuser']['id'];
        }
        $sql="select * from ".tablename("ewei_shop_union_ly_addressline").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_addressline").$condition,$params);
        $pager = pagination($total, $pindex, $psize);
        $theme=$this->model->themeonline;
        $traffic=$this->model->traffic;

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

        $theme=$this->model->themeonline;
        $traffic=$this->model->traffic;

        $activity_id=0;
        $id=intval($_GPC['id']);
        if($id){

            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_addressline")." where id=:id",array(":id"=>$id));
            $activity_id=$vo['activity_id'];
        }
        $operatorlist=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_role_member")." where status=1 and deleted=0");
        //景点选择
        $lyaddress=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_lyaddress"));

        //酒店选择
        $lyhotel=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_hotel"));

        //获取全部可用报名模块数据
        $categoryactivity=$this->model->getCategroyMemberActivity($activity_id);

        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'theme_id'=>intval($_GPC['theme_id']),
                'header_image'=>trim($_GPC['header_image']),
                'unitname'=>trim($_GPC['unitname']),
                'guidancename'=>trim($_GPC['guidancename']),
                'mobilephone'=>trim($_GPC['mobilephone']),
                'lineintroduce'=>htmlspecialchars_decode(trim($_GPC['lineintroduce'])),
                'city'=>trim($_GPC['city']),
                'position'=>trim($_GPC['position']),
                'description'=>htmlspecialchars_decode(trim($_GPC['description'])),
                "createtime"=>TIMESTAMP,
                 "traffic_type"=>intval($_GPC['traffic_type']),
                 "has_scenic"=>intval($_GPC['has_scenic']),
                 "dayvale"=>trim($_GPC['dayvale']),
                 "traffic_id"=>intval($_GPC['traffic_id']),
                'oldprice'=>floatval($_GPC['oldprice']),
                'price'=>floatval($_GPC['price']),
                'volume'=>intval($_GPC['volume']),
                'activity_id'=>intval($_GPC['activity_id']),
                'addressid'=>intval($_GPC['addressid']),
                'hotelid'=>intval($_GPC['hotelid']),
                'userid'=>intval($_GPC['userid']),
                'enabled' => intval($_GPC['enabled']),
                'type' => intval($_GPC['type']),
                'costdescription'=>htmlspecialchars_decode($_GPC['costdescription']),
            );
            if($_W['isoperator']==1){
                $data['userid']=$_W['unionuser']['id'];
            }
            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_ly_addressline",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_ly_addressline",$data);
            }
            $this->message("数据处理成功",unionUrl("ly/lyaddressline"));

        }
        include $this->template();
    }

}