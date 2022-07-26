<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Lyaddress_EweiShopV2Page extends LyMobilePage
{
    public function main(){
        global $_W;
        global $_GPC;
        //获取幻灯片

        $theme=$this->model->theme;
        $grade=$this->model->grade;




        include $this->template();
    }

    public function getlist(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 6;
        $total=0;
        $condition="  where deleted=0 and enabled=0 and parentid=0";

        $type=empty($_GPC['type']) ? "zh" : $_GPC['type'];
        $typevalue=empty($_GPC['value']) ? 0 :$_GPC['value'];
        $params=array();
        $orderasc=" desc ";
        if($type=="zh"){
            $type="createtime";
            $orderasc=" desc ";
        }
        if($type=="volume"){
            $order="volume";
            $orderasc=" desc ";
        }
        if($type=="is_dscount"){
            if($typevalue==0){
                $orderasc=" asc ";
            }
        }
        if($type=="evaluate"){
            if($typevalue==0){
                $orderasc=" asc ";
            }
        }
        if($type=="activity"){
            if($typevalue==0){
                $orderasc=" asc ";
            }
        }
        if($_GPC['areaCode']){//地区

        }
        if($_GPC['grade']){
            $condition.=" and grade_id= :grade_id";
            $params[':grade_id']=intval($_GPC['grade']);
        }
        if($_GPC['is_hasget']){
            $condition.=" and is_hasget= :is_hasget";
            $params[':is_hasget']=intval($_GPC['is_hasget']);
        }
        if($_GPC['theme']){
            $condition.=" and theme_id= :theme_id";
            $params[':theme_id']=intval($_GPC['theme']);
        }
        if($_GPC['keywords']!=''){
            $condition.=" and (title like :keyword or spot like :keyword or dishes like :keyword )";
            $params[':keyword']="%".trim($_GPC['keywords'])."%";
        }

        $sql="select id,title,address,mobilephone,header_image,is_hasget,is_dscount,is_hot,evaluate from ".tablename("ewei_shop_union_ly_lyaddress").
            $condition;
        $sql.=" order by displayorder desc  " ;//." LIMIT ". ($pindex - 1) * $psize . ',' . $psize;
        $list=pdo_fetchall($sql,$params);

        foreach ($list as &$item){
            $item['header_image']=tomedia($item['header_image']);
            $item['evaluate_num']=0;
        }
        unset($item);

        //$total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_lyaddress").$condition,$params);


        show_json(1, array('list' => $list,'total' => $total, 'pagesize' => $psize));
    }

    function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $item=pdo_fetch("select  * from ".tablename("ewei_shop_union_ly_lyaddress")." where id=:id",array(":id"=>$id));

        $children=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_lyaddress").' where enabled=0 and deleted=0 and parentid=:parentid',array(":parentid"=>$id));
        if(empty($children)){
            $addresslinelist=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_addressline")." where enabled=0 and deleted=0 and addressid=:addressid order by createtime desc",array(":addressid"=>$id));
            $dayvale=array();
            foreach ($addresslinelist as $voinfo){
                $dayvale[$voinfo['dayvale']][]=$voinfo;
            }


        }

        if(empty($item)){
            $this->message("非法访问");
        }
        include $this->template("union/lyhome/lyaddressview");
    }
}