<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends MobilePage {
	function main(){
		global $_W, $_GPC;
		$storelist=pdo_fetchall("SELECT * from ".tablename("ewei_shop_store")." where uniacid=:uniacid and status=1",array(":uniacid"=>$_W['uniacid']));
		$storelist = set_medias($storelist, 'logo');
		$mobileurl='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		//var_dump($storelist);
		$store_config=pdo_fetch("SELECT * from ".tablename("ewei_shop_store_config")." where uniacid=:uniacid",array(":uniacid"=>$_W['uniacid']));
		if(!empty($store_config)){
			$store_thumb=tomedia($store_config['store_thumb']);
		}
		include $this->template();
	}

	function getDistance($lat1, $lng1, $lat2, $lng2) 
	{
		$earthRadius = 6367000; //approximate radius of earth in meters 
		$lat1 = ($lat1 * pi() ) / 180;
		$lng1 = ($lng1 * pi() ) / 180;
		$lat2 = ($lat2 * pi() ) / 180;
		$lng2 = ($lng2 * pi() ) / 180;
		$calcLongitude = $lng2 - $lng1;
		$calcLatitude = $lat2 - $lat1;
		$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2); 
		$stepTwo = 2 * asin(min(1, sqrt($stepOne))); 
		$calculatedDistance = $earthRadius * $stepTwo; 
		return round($calculatedDistance); 
	}


	function updatenum(){
		global $_W, $_GPC;
		if($_W['isajax']){
			$id=$_GPC['id'];
			$sql="update ".tablename("ewei_shop_store")." set defaultchick=defaultchick+1 where id=:id";
			pdo_query($sql,array(":id"=>$id));
			//echo json_encode("ss");
		}
	}
}