<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
class K3cloud_EweiShopV2Model
{


    function get_stock($sku,$goodsid,$total){

        global $_W;
        load()->func('communication');
        if(empty($sku)){
            return $total;
        }
        $goodssku='jasahn_'.$sku;
        $open_redis = function_exists('redis') && !(is_error(redis()));
        if($open_redis){
            $redis=redis();
            $content=$redis->get($goodssku);

            if($content){

                return $total;


            }else{
                $url="http://oms.cnbuyers.cn/api/k3cloud";
                $postData=array(
                    'sku'=>$sku,
                );
                $resp = ihttp_request($url, $postData,'',2);

                $stock=(array)json_decode($resp['content'],true);

                if(isset($stock['error']) && $stock['error']==500){
                    return 0;
                }else{

                    $this->set_stock($goodsid,$stock['data']['stock']);
                    $redis->set($goodssku,$resp['content']);

                    $redis->EXPIRE($goodssku,1800);
                    return $stock['data']['stock'];
                }
            }

        }
    }

    function set_stock($goodsid,$total,$optionid=0){
        pdo_update("ewei_shop_goods",array('total'=>$total),array("id"=>$goodsid));
    }

}