<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */

error_reporting(0);

require dirname(__FILE__).'/../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';
require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");

class updateStock
{

    private  $depotsids=array();
    private  $depot_list=array();
    private  $updateGoodslist=array();
    private  $updateGoodsopeionlist=array();
    public function __construct()
    {
        global $_W;

        $this->init();

    }
    public function init(){
        global $_W;
        $_W['uniacid']=DIS_ACCOUNT;//主站
        $pagecount=500;
        $total=$this->get_total();
        $pagenumber=ceil($total/$pagecount);
        $depots=$this->get_depots();

        if(empty($depots)){
            return ;
        }

        foreach ($depots as $item){
            $this->depotsids[]=$item['id'];
            $this->depot_list[$item['id']]=$item;
        }
        for ($pageindex=1;$pageindex<=$pagenumber;$pageindex++){
            $goodslist=$this->get_goods($pageindex,$pagecount);

            $this->foreachGoods($goodslist);
        }


    }


    private function updateadress($goods,$depot){

    }
    private function foreachGoods($goodslist){


        foreach ($goodslist as $goods){

            if(in_array($goods['depotid'],$this->depotsids) && $goods['depotid']!=27){
                $depot=$this->depot_list[$goods['depotid']];

                if($goods['hasoption']){
                   $optionlist= pdo_fetchall("select goodssn,id from ".tablename("ewei_shop_goods_option")." where goodsid=:goodsid",array(":goodsid"=>$goods['id']));
                    foreach ($optionlist as $option){
                        $stock=$this->ihttp_postdata($depot,$option['goodssn']);
                        if(isset($stock['error']) && $stock['error']==0){
                            $this->updateGoodsopeionlist[]=array('id'=>$option['id'],'stock'=>$stock['data']['stock']);
                        }
                    }
                }else{
                    $stock=$this->ihttp_postdata($depot,$goods['goodssn']);
                    if(isset($stock['error']) && $stock['error']==0){
                        $this->updateGoodslist[]=array('id'=>$goods['id'],'stock'=>$stock['data']['stock']);
                    }
                }
            }
            if($goods['depotid']==27){//运动库存单独更新
                $depot=$this->depot_list[$goods['depotid']];
               if(!empty($goods['goodssn']) && !empty($depot['storeroomid'])){
                   m("httpUtil")->updateAdressGoods($goods['goodssn'],$goods['id'],$depot['storeroomid']);
               }
            }


            //每50条更新一下数据
            if(count($this->updateGoodsopeionlist)>=50){
                $this->updateOptionStock();
                $this->updateGoodsopeionlist=array();
            }
            if(count($this->updateGoodslist)>=50){
                $this->updateStock();
                $this->updateGoodslist=array();
            }
        }
        if(count($this->updateGoodsopeionlist)>0){
            $this->updateOptionStock();
            $this->updateGoodsopeionlist=array();
        }
        if(count($this->updateGoodslist)>0){
            $this->updateStock();
            $this->updateGoodslist=array();
        }
    }
    private function updateStock(){
        $disgoods_ids=array();
        $updatesql="insert into ".tablename('ewei_shop_goods')."  (id,total) values ";
        foreach ($this->updateGoodslist as $item){
            $updatesql.="({$item['id']},{$item['stock']}),";
            //查询代理商的商品库存
            $disgoods_id=pdo_fetchall("select id from ".tablename("ewei_shop_goods")." where disgoods_id=:id",array(":id"=>$item['id']));
            if(!empty($disgoods_id)){
                $disgoods_ids[]=array("goodsids"=>$disgoods_id,'stock'=>$item['stock']);
            }
        }
        $updatesql=substr($updatesql,0,strlen($updatesql)-1);
        $updatesql.=" on duplicate key update total=values(total)";
        pdo_query($updatesql);//主站更新
        if(!empty($disgoods_ids)){
            //代理站的更新
            $disupdatesql="insert into  ".tablename('ewei_shop_goods')."  (id,total) values ";
            foreach ($disgoods_ids as $ids){
                $stock=$ids['stock'];
                foreach ($ids['goodsids'] as $goods_temp){
                    $disupdatesql.="({$goods_temp['id']},$stock),";
                }
            }
            $disupdatesql=substr($disupdatesql,0,strlen($disupdatesql)-1);
            $disupdatesql.=" on duplicate key update total=values(total)";
            pdo_query($disupdatesql);//代理站更新
        }
    }

    private function updateOptionStock(){
        $disgoods_ids=array();
        $updatesql="insert into  ".tablename('ewei_shop_goods_option')."  (id,stock) values ";
        foreach ($this->updateGoodsopeionlist as $item){
            $updatesql.="({$item['id']},{$item['stock']}),";
            //查询代理商的商品库存
            $disgoods_id=pdo_fetchall("select id from ".tablename("ewei_shop_goods_option")." where disoptionid=:id",array(":id"=>$item['id']));
            if(!empty($disgoods_id)){
                $disgoods_ids[]=array("goodsids"=>$disgoods_id,'stock'=>$item['stock']);
            }

        }
        $updatesql=substr($updatesql,0,strlen($updatesql)-1);
        $updatesql.=" on duplicate key update stock=values(stock)";
        pdo_query($updatesql);//主站更新
        if(!empty($disgoods_ids)){
            //代理站的更新
            $disupdatesql="insert into ".tablename('ewei_shop_goods_option')."  (id,stock) values ";
            foreach ($disgoods_ids as $ids){
                $stock=$ids['stock'];
                foreach ($ids['goodsids'] as $goods_temp){
                    $disupdatesql.="({$goods_temp['id']},$stock),";
                }
            }
            $disupdatesql=substr($disupdatesql,0,strlen($disupdatesql)-1);
            $disupdatesql.=" on duplicate key update stock=values(stock)";
            pdo_query($disupdatesql);//代理站更新
        }
    }
    private  function get_goods($pageindex,$pagesize){
        $sql="select id,depotid,goodssn,hasoption from ".tablename("ewei_shop_goods")." where uniacid=:uniacid and deleted=0 and status>=0   LIMIT " . ($pageindex - 1) * $pagesize . ',' . $pagesize;

        return pdo_fetchall($sql,array(":uniacid"=>DIS_ACCOUNT));
    }
    private  function get_total(){
       return pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_goods")." where uniacid =:uniacid and deleted=0 and status>=0 ",array(":uniacid"=>DIS_ACCOUNT));
    }
    //全部需要从OMS 更新的库存的商品
    private function get_depots(){
        return pdo_fetchall("select id,updateid,app_id,app_secret,storeroomid from ".tablename("ewei_shop_depot")." where  (updateid=1 or updateid=3) and  uniacid =:uniacid and ismygoods=1 and app_id<>''",array(":uniacid"=>DIS_ACCOUNT));
    }
    private function ihttp_postdata($depot,$goodssn){
        load()->func('communication');
        $url="http://oms.cnbuyers.cn/api/stock";
        SendOmsapi::init($depot['app_id'],$depot['app_secret']);
        $token=SendOmsapi::getToken();
        $resp = ihttp_post($url,array("access_token"=>$token,'only_sku'=>$goodssn));
        $content=$resp['content'];
        $content=json_decode($content,true);
        return $content;
    }
}
new updateStock();
