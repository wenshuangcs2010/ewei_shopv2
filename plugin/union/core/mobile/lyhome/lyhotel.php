<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Lyhotel_EweiShopV2Page extends LyMobilePage
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
        $condition="  where deleted=0 and enabled=0 ";

        $type=empty($_GPC['type']) ? "createtime" : $_GPC['type'];
        $typevalue=empty($_GPC['pricesort']) ? 0 :$_GPC['pricesort'];
        $params=array();
        $orderasc=" desc ";
        if($typevalue==1){
            $type=" price ";
            $orderasc=" asc ";
        }
        if($typevalue==1){
            $type=" price ";
            $orderasc=" desc ";
        }
        if($_GPC['price']==1){
            $condition.=" and price < 100 ";
        }
        if($_GPC['price']==2){
            $condition.=" and price >=100 and price <=200 ";
        }
        if($_GPC['price']==3){
            $condition.=" and price >200";
        }

        $sql="select id,title,header_image,mobilephone,price,address from ".tablename("ewei_shop_union_ly_hotel").
            $condition;
        $sql.=" order by {$type} {$orderasc}  " ;//." LIMIT ". ($pindex - 1) * $psize . ',' . $psize;
        $list=pdo_fetchall($sql,$params);

        foreach ($list as &$item){
            $item['header_image']=tomedia($item['header_image']);

        }
        unset($item);

        //$total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_hotel").$condition,$params);


        show_json(1, array('list' => $list,'total' => $total, 'pagesize' => $psize));
    }

    function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $item=pdo_fetch("select  * from ".tablename("ewei_shop_union_ly_hotel")." where id=:id",array(":id"=>$id));

        if(empty($item)){
            $this->message("非法访问");
        }
        include $this->template("union/lyhome/lyhotelview");
    }
}