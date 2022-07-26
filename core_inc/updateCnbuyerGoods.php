<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */



require dirname(__FILE__).'/../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';
require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");
define("CNBUYERS_GOODS_APP_ID",     "zIPXb3SB");
define("CNBUYERS_GOODS_APP_SECRET",     "be5845fa6881baf7");
//define("CNBUYERS_GOODS_APP_ID",     "20882088");
//define("CNBUYERS_GOODS_APP_SECRET",     "f37ff9712508ae07");
class updateCnbuyerGoods
{
    private $resellerlist;
    private $warehouselist=array(
        '12'=>20,
        '11'=>27,
        '8'=>17,
        '7'=>57,
        '6'=>21,
        '13'=>88,
        '14'=>95,
        '15'=>97,
    );
    public function __construct()
    {
        global $_W;
        SendOmsapi::init(CNBUYERS_GOODS_APP_ID,CNBUYERS_GOODS_APP_SECRET);
        $this->init();

    }
    private function contrast_warehouse($warehouse_id){
        if(isset($this->warehouselist[$warehouse_id])){
            return $this->warehouselist[$warehouse_id];
        }
        return 0;
    }
    private function init(){
        $warehouse=$this->getwarehouse();
        $this->resellerlist=pdo_fetchall("select * from ".tablename("ewei_shop_reseller"));

        foreach ($warehouse as $item){
            $pageindex=1;
            $pagecount=50;
            $goods_list=$this->get_goods($item['id'],'',$pageindex,$pagecount);

            if($goods_list){
                $count=$goods_list['count'];
                $allpage=ceil($count/$pagecount);
                $this->save_goods($goods_list['goods_list'],$item['id']);
                if($allpage>1){
                    for ($i=2;$i<=$allpage;$i++){
                        $goods_list=$this->get_goods($item['id'],'',$i,$pagecount);
                        if(isset($goods_list['goods_list']) && empty($goods_list['goods_list'])){
                            $this->save_goods($goods_list['goods_list'],$item['id']);
                        }
                    }
                }
            }
        }
        echo "处理完成";
    }

    private  function save_goods($goods_list,$warehouse_id){
     foreach ($goods_list as $key=> $goods){
         //检查商品是否存在
         $sql="SELECT id from ".tablename("ewei_shop_goods")." where goodssn = :goodssn and uniacid=:uniacid";
         $id=pdo_fetchcolumn($sql,array(":goodssn"=>$goods['goodssn'],':uniacid'=>DIS_ACCOUNT));
         if(empty($id)){
             $this->savegoods($goods,$warehouse_id);
         }

     }
    }

    /*保存多规格商品商品*/
    private  function savegoods($goods,$warehouse_id){
        $insertdata=array();
        $insertdata['title']=$goods['title'];
        $insertdata['goodssn']=$goods['goodssn'];
        $insertdata['uniacid']=DIS_ACCOUNT;
        $insertdata['marketprice']=$goods['marketprice'];
        $insertdata['costprice']=$goods['we7costprice'];
        if($goods['hasoption']==0){
            $insertdata['isdis']=1;
        }
        $insertdata['thumb_url']=serialize($goods['thumb_url']);
        $insertdata['depotid']=$this->contrast_warehouse($warehouse_id);
        $insertdata['thumb']=isset($goods['thumb_url']) ? $goods['thumb_url'][0]:"";
        $insertdata['total']=isset($goods['total']) ? $goods['total']:0;
        $insertdata['weight']=isset($goods['weight']) ? $goods['weight']:0;
        $insertdata['hasoption']=isset($goods['hasoption']) ? $goods['hasoption']:0;
        $insertdata['unit']=isset($goods['unit']) ? $goods['unit']:"";
        $insertdata['content']=isset($goods['content']) ? $goods['content']:'';
        $insertdata['subtitle']=isset($goods['subtitle']) ? $goods['subtitle']:"";
        $insertdata['type']=isset($goods['type']) ? $goods['type']:0;
        $insertdata['createtime']=time();
        $insertdata['status']=-1;

        try{
            pdo_begin();
            pdo_insert('ewei_shop_goods',$insertdata);
            $goodsid=pdo_insertid();
            if($goods['hasoption']){
                $discount['type']=1;
                $title_list=array();
                foreach ($goods['specs'] as $spec){
                    $data=array(
                        'uniacid'=>DIS_ACCOUNT,
                        'goodsid'=>$goodsid,
                        'title'=>$spec['title'],
                    );
                    pdo_insert('ewei_shop_goods_spec',$data);
                    $specid=pdo_insertid();
                    $spec_item_ids=array();
                    foreach ($spec['list'] as $title){
                        $spec_item=array(
                            'uniacid'=>DIS_ACCOUNT,
                            'specid'=>$specid,
                            'title'=>$title,
                        );
                        pdo_insert('ewei_shop_goods_spec_item',$spec_item);
                        $spec_item_id=pdo_insertid();
                        $spec_item_ids[]=$spec_item_id;
                        $title_list[$title]=$spec_item_id;
                    }
                    pdo_update("ewei_shop_goods_spec",array("content"=>serialize($spec_item_ids)),array('id'=>$specid));
                }
                $spac_list=array();
                $optionsdiscounts=array();
                foreach ($goods['options'] as $spac){
                    $spac_list=explode("+",$spac['title']);
                    $specs=array();
                    foreach ($spac_list as $title){
                        $specs[]=$title_list[$title];
                    }
                    $option_item=array(
                        'title'=>$spac['title'],
                        'goodsid'=>$goodsid,
                        'uniacid'=>DIS_ACCOUNT,
                        'marketprice'=>$spac['marketprice'],
                        'costprice'=>$spac['we7costprice'],
                        'stock'=>$spac['stock'],
                        'weight'=>$spac['weight'],
                        'goodssn'=>$spac['goodssn'],
                        'specs'=>count($specs)>1 ? implode("_",$specs) : $specs[0],
                    );
                    pdo_insert("ewei_shop_goods_option",$option_item);
                    $optionid=pdo_insertid();
                    $discount["default"]["option{$optionid}"]="";
                    $discount["level23"]["option{$optionid}"]="";
                    $discount["level30"]["option{$optionid}"]=$spac['costprice'];
                }
                pdo_update('ewei_shop_goods',array("discounts"=>json_encode($discount)),array('id'=>$goodsid));
            }else{
                $discount['type']=0;
                $discount['default_pay']='';
                $discount['level23']='';
                $discount['level23_pay']='';
                $discount['level30']='';
                $discount['level30_pay']=$goods['costprice'];
                pdo_update('ewei_shop_goods',array("discounts"=>json_encode($discount)),array('id'=>$goodsid));
                $goodsresel=array();
                foreach ($this->resellerlist as $res){
                    $goodsresel[$res['id']]=$goods['costprice'];
                }
                $goodsresel=serialize($goodsresel);
                $reseldata['disprice']=$goodsresel;
                $reseldata['hasoptions']=0;
                $reseldata['goods_id']=$goodsid;
                pdo_insert("ewei_shop_goodsresel",$reseldata);
            }
        pdo_commit();
        }catch (Exception $e){

            pdo_rollback();
        }

    }

    private function get_lastupdatetime(){
        $time=strtotime(date("Y-m-d",strtotime(" -1 day")));
    }
    private function get_goods($warehouse,$starttime="",$pageindex=1,$pagecount=50){
        load()->func('communication');
        $url="http://oms.cnbuyers.cn/api/goodslist";
        //$url="http://localhost/oms/api/goodslist";
        $token=SendOmsapi::getToken();
        $postdata=array();
        $postdata['access_token']=$token;
        $postdata['store_id']=$warehouse;
        $postdata['pageindex']=$pageindex;
        $postdata['pagecount']=$pagecount;
        $postdata['showcostprice']=1;
        if(!$starttime){
            $endtims=date("Y-m-d",strtotime("+1 day"));
            $startTimes=$this->get_lastupdatetime();
            $postdata['startTime']=date("Y-m-d",$startTimes);
            $postdata['endTime']=$endtims;
        }

        $resp = ihttp_post($url,$postdata);

        $content=json_decode($resp['content'],true);

        if(!isset($content['error']) && $content['error']>0){
            //var_dump($content);
            return false;
        }
        return $content['data'];
    }
    private function getwarehouse()
    {
        load()->func('communication');
        $url="http://oms.cnbuyers.cn/api/warehouse";
        //$url="http://localhost/oms/api/warehouse";
        $token=SendOmsapi::getToken();
        $resp = ihttp_post($url,array("access_token"=>$token));
        $content=$resp['content'];
        $content=json_decode($content,true);
        if(!isset($content['error']) && $content['error']>0){
            return false;
        }
        return $content['data'];
    }
}
new updateCnbuyerGoods();