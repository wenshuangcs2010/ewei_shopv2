<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Httpdown_EweiShopV2Page extends WebPage {

	function main(){
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$depot=Dispage::getDepot($goods['depotid']);
		if(empty($goods['goodssn'])){
			$goodsoption=pdo_fetchall("SELECT id,goodssn from ".tablename("ewei_shop_goods_option")." where goodsid=:gid",array(":gid"=>$goods['id']));
			
			foreach ($goodsoption as $value) {
				$goodssn[]=trim($value['goodssn']);
			}
			$goodssn=array_unique($goodssn);
			$goods['goodssn']=$goodssn;
			
		}
		if(empty($goods['goodssn'])){
			show_json(0,"参数丢失无法更新");
		}
		if($depot['updateid']==1||$depot['updateid']==3){
			$return=m("httpUtil")->updateGoods($goods['goodssn'],$goods['id']);
		}elseif($depot['updateid']==2){
			//同步成本
			//m("httpUtil")->oneupdateGoodsprice($goods['id']);

			$return=m("httpUtil")->updateAdressGoods($goods['goodssn'],$goods['id'],$depot['storeroomid']);
			//show_json(1,$retdata);
		}
		if($return){
			show_json(1,"更新成功");
		}
		show_json(0,'更新失败');
	}
}