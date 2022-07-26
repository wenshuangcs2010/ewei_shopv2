<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Lynews_EweiShopV2Page extends LyMobilePage
{
    public function main(){
        global $_W;
        global $_GPC;


        include $this->template();
    }

    public function getlist(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $total=0;
        $condition="  where deleted=0 and status=1 ";

        $order="createtime";
        $params=array();
        $orderasc=" desc ";

        if($_GPC['keywords']!=''){
            $condition.="and title like :keyword";
            $params[':keyword']="%{$_GPC['keywords']}%";
        }

        $sql="select id,title,newstitle,createtime from ".tablename("ewei_shop_union_ly_news").
            $condition;
        $sql.=" order by {$order} {$orderasc}  ";//." LIMIT ". ($pindex - 1) * $psize . ',' . $psize;
        $list=pdo_fetchall($sql,$params);

        foreach ($list as &$item){
            $item['createtime']=date("Y-m-d",$item['createtime']);
        }
        unset($item);

        $total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_news").$condition,$params);


        show_json(1, array('list' => $list,'total' => $total, 'pagesize' => $psize));
    }

    function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $item=pdo_fetch("select  * from ".tablename("ewei_shop_union_ly_news")." where id=:id",array(":id"=>$id));

        if(empty($item)){
            $this->message("非法访问");
        }
        include $this->template("union/lyhome/lynewsview");
    }
}