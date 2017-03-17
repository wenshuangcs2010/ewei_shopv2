<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class HttpUtil_EweiShopV2Model
{

	//获取保税超市库存
	function getGoods($goodssn){
		load()->func('communication');
		$goodsurl="http://www.cnbuyers.cn/index.php?app=webService&act=checkStockWe7&sku=".$goodssn;
		$resp = ihttp_get($goodsurl);
		 $content = $resp['content'];
	      if (empty($content)) {
	            return array();
	      }
	      $content=(array)@json_decode($content);
	      $data=(array)$content['data'];
	      return $data['stock'];
	}
	//更新库存 
	function updateStock($id,$stock){
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => DIS_ACCOUNT));
		$sql="select id from " . tablename('ewei_shop_goods') . " where disgoods_id=:disgoods_id";
		$disgoodslist=pdo_fetchall($sql,array("disgoods_id"=>$goods['id']));
		
		foreach ($disgoodslist as $v) {
			pdo_update("ewei_shop_goods",array("total"=>$stock),array("id"=>$v));
		}
		
	}

	//更新保税超市库存
	function updateGoods($goodssn,$goodsid){
		load()->func('communication');
		$goodsurl="http://www.cnbuyers.cn/index.php?app=webService&act=We7GetGoodsInfo&sku=".$goodssn;
		$resp = ihttp_get($goodsurl);
		$content = $resp['content'];
		if (empty($content)) {
	            return array();
	      }
	    $content=(array)@json_decode($content);
	    $data=(array)$content['data'];

	    if(empty($data)){return array();}
	    $updatedata=array(
	    	'title'=>$data['goods_name'],
	    	'total'=>$data['stock'],
	    	'weight'=>$data['weight']*1000,
	    	'consumption_tax'=>$data['consumption_tax'],
	    	'vat_rate'=>$data['vat_rate'],
	    	'tariffnum'=>$data['hs_code'],
	    	'unit'=>$data['unit'],
	    	'minbuy'=>$data['min_quantity'],
	    	//'costprice'=>$data['cost_price'],
	    	);

	    pdo_update("ewei_shop_goods",$updatedata,array("id"=>$goodsid));
	    $sql="select id from " . tablename('ewei_shop_goods') . " where disgoods_id=:disgoods_id";
	    plog('goods.edit', "商品同步ID:{$goodsid}");
	    $disgoodslist=pdo_fetchall($sql,array("disgoods_id"=>$goodsid));
	    if(empty($disgoodslist)){
	    	return 1;
		}
	    $disdata=array(
			'total'=>$data['stock'],
	    	'weight'=>$data['weight']*1000,
	    	'consumption_tax'=>$data['consumption_tax'],
	    	'vat_rate'=>$data['vat_rate'],
	    	'tariffnum'=>$data['hs_code'],
	    	'unit'=>$data['unit'],
	    	'minbuy'=>$data['min_quantity'],
	    	);
	    $sql="update ".tablename("ewei_shop_goods")." SET ";
	    foreach($disdata as $k=>$v){
	    	if(is_numeric($v)){
	    		$str.="`".$k."`=".$v.",";
	    	}elseif(is_string($v)){
	    		$str.="`".$k.'`="'.$v.'",';
	    	}
	    	
	    }
	    $str=substr($str, 0, -1);
	    $sql.=$str;
	    foreach ($disgoodslist as $v) {
		   $t[]=$v['id'];
		}
		$ids=implode(",",$t);
		$sql.=" where id in ($ids)";
		pdo_query($sql);
		plog('goods.edit', "代理商品同步ID:{$ids}");
		return 1;
	}



}