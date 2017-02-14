<?php

/*
                   _ooOoo_
                  o8888888o
                  88" . "88
                  (| -_- |)
                  O\  =  /O
               ____/`---'\____
             .'  \\|     |//  `.
            /  \\|||  :  |||//  \
           /  _||||| -:- |||||-  \
           |   | \\\  -  /// |   |
           | \_|  ''\---/''  |   |
           \  .-\__  `-`  ___/-. /
         ___`. .'  /--.--\  `. . __
      ."" '<  `.___\_<|>_/___.'  >'"".
     | | :  `- \`.;`\ _ /`;.`/ - ` : | |
     \  \ `-.   \_ __\ /__ _/   .-` /  /
======`-.____`-.___\_____/___.-`____.-'======
                   `=---='
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         佛祖保佑       永无BUG
*/
require EWEI_SHOPV2_TAX_CORE. '/tax_core.php';
class Dispage{
	

	/**
	 * 删除代理商品更新代理数据
	 * @return [type] [description]
	 */
	public static function delDisGoods($goods_id,$data,$uniacid){
		if($goods_id==0){
			return ;
		}
		if($data['isdis']==0 && $uniacid==DIS_ACCOUNT){
			$goodslist=pdo_fetchall("SELECT id FROM ".tablename('ewei_shop_goods')." where disgoods_id=:goods_id",array(":goods_id"=>$goods_id));
			if(!empty($goodslist)){
				foreach ($goodslist as $value) {
					pdo_delete("ewei_shop_goods",array("id"=>$value['id']));
				}
				plog('goods.delete', "删除代理商品 主代理ID: {$goods_id}<br>");
			}
		}
		if($data['isdis']==1 && $uniacid==DIS_ACCOUNT){
			$goodslist=pdo_fetchall("SELECT id FROM ".tablename('ewei_shop_goods')." where disgoods_id=:goods_id",array(":goods_id"=>$goods_id));
			if(!empty($goodslist)){
				foreach ($goodslist as $value) {
					//封装需要更新的数据
					$updatedata=array(
						'consumption_tax'=>$data['consumption_tax'],//消费税率
						'vat_rate'=>$data['vat_rate'],//增值税率
						'unit'=>$data['unit'],//单位
						'goodssn'=>$data['goodssn'],//商品SKU 
						'depotid'=>$data['depotid'],//仓库ID
						'weight'=>$data['weight'],//商品重量
						'tariffnum'=>$data['tariffnum'],//商品HS编码
						'originplace'=>$data['originplace'],//商品原产地
						'keywords'=>$data['keywords'],//关键字//临时改
					);
					pdo_update("ewei_shop_goods",$updatedata,array('id'=>$value['id']));
				}
				plog('goods.edit', "更新代理商品 主商品代理ID: {$goods_id}<br>");
			}
		}
	}
	/**
	 * 代理价格的修改
	 * @param  [type] $goods_id [description]
	 * @param  [type] $data     [description]
	 * @return [type]           [description]
	 */
	public static function disPrice($goods_id,$data){
		if($goods_id==0){
			return ;
		}
		$disprice=serialize($data);
		$reseldata=pdo_fetch("select * from ".tablename("ewei_shop_goodsresel")." where goods_id=:goodsid",array(":goodsid"=>$goods_id));
		if(empty($reseldata)){
			$reseldata['goods_id']=$goods_id;
			$reseldata['disprice']=$disprice;
			$reseldata['status']=$data['isdis'];
			plog('goods.add', "新曾商品代理价 ID: {$reseldataid}<br>");
			pdo_insert('ewei_shop_goodsresel',$reseldata);
		}
		if(!empty($reseldata)){
			$reseldataid=$reseldata['id'];
			unset($reseldata['id']);
			$reseldata['goods_id']=$goods_id;
			$reseldata['disprice']=$disprice;
			$reseldata['status']=$data['isdis'];
			plog('goods.edit', "更新商品代理价 ID: {$reseldataid}<br>");
			pdo_update('ewei_shop_goodsresel',$reseldata,array('id'=>$reseldataid));
		}
	}
	/**
	 * 获取用户的代理详细信息
	 * @param  [type] $uniacid [description]
	 * @return [type]          [description]
	 */
	public static function getDisInfo($uniacid){
		if($uniacid==DIS_ACCOUNT || $uniacid==0){
			return ;
		}
		return pdo_fetch("SELECT * FROM ".tablename("ewei_shop_resellerlevel")." WHERE Accountsid=:uniacid",array(":uniacid"=>$uniacid));
	}
	/**
	 * check 代理商品状态
	 * @param  [type] $goodsid [description]
	 * @return [type]          [description]
	 */
	public static function checkGoodsStatus($goodsid,$disgoods_id,$isdis,$uniacid,$status){
		if($disgoods_id==0 && $isdis==1 && $uniacid==DIS_ACCOUNT && $status==0){//主号商品的下架
			$disgoods=pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_goods")." WHERE disgoods_id=:goodsid",array(":goodsid"=>$goodsid));
			foreach ($disgoods as $goods) {
				pdo_update("ewei_shop_goods",array("status"=>$status),array("id"=>$goods['id']));//批量下架全部代理商品

			}
			plog('goods.edit', "代理商品的批量下架 GOODSID: {$disgoods_id}<br>");
		}
		if($disgoods_id!=0 && $isdis==1 && $uniacid!=DIS_ACCOUNT && $status==1){//非主号商品的上架
			 $goods=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_goods")." WHERE id=:goodsid",array(":goodsid"=>$disgoods_id));
			 if($goods['status']==0){
			 	return false;
			 }
		}
		return true;
	}
	/**
	 * 获取一个仓库ID
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function getDepot($id){
		$depot=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_depot")." where id=:id",array(":id"=>$id));
		return $depot;
	}
	/**
	 * 获取全部仓库
	 * @param  [type] $uniacid [description]
	 * @return [type]          [description]
	 */
	public static function get_all_depot($uniacid){
		return pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_depot")." where uniacid=:uniacid",array(":uniacid"=>$uniacid));
	}
	/**
	 * 代理商品获取运费配置
	 * @param  [type] $goodsid [description]
	 * @return [type]          [description]
	 */
	public static function get_dispatch_id($goodsid,$uniacid){
		$goods=pdo_fetch("SELECT id,dispatchid,disgoods_id from ".tablename("ewei_shop_goods")." where id=:id",array(':id'=>$goodsid));
		if($uniacid!=DIS_ACCOUNT && $goods['disgoods_id']!=0){
			
			$goods=pdo_fetch("SELECT id,dispatchid from ".tablename("ewei_shop_goods")." where id=:id",array(':id'=>$goods['disgoods_id']));
			return $goods['dispatchid'];
		}
		return $goods['dispatchid'];
	}
	/**
	 * 判断商品是否是代理商品
	 * @param  [type] $disgoods_id [description]
	 * @param  [type] $uniacid     [description]
	 * @return [type]              [description]
	 */
	public static function get_disType($disgoods_id,$uniacid){
		if($disgoods_id>0 && $uniacid!=DIS_ACCOUNT){
			return true;
		}
		return false;
	}
	/**
	 * 获取商品仓库
	 * @param  [type] $type    是否是代理商品
	 * @param  [type] $goodsid [description]
	 * @return [type]          [description]
	 */
	public static function get_depotid($type,$goodsid){
		$depotid=0;
		if($goodsid==0){
			return 0;
		}
		if($type){
			$disgoods_id=pdo_fetchcolumn("SELECT disgoods_id from ".tablename("ewei_shop_goods")." where id=:id",array(':id'=>$goodsid));
			$depotid=pdo_fetchcolumn("SELECT depotid from ".tablename("ewei_shop_goods")." where id=:id",array(':id'=>$disgoods_id));
		}else{
			$depotid=pdo_fetchcolumn("SELECT depotid from ".tablename("ewei_shop_goods")." where id=:id",array(':id'=>$goodsid));
		}
		return $depotid;
	}
	/**
	 * 检查订单是否需要实名认证
	 * @param  [type] $depotid [description]
	 * @return [type]          [description]
	 */
	public static function check_readname($depotid){
		return pdo_fetchcolumn("SELECT ifidentity from ".tablename("ewei_shop_depot")." where id=:id",array(":id"=>$depotid));
	}
	
	/**
	 * 获取订单商品
	 * @return [type] 检查是什么订单
	 */
	private static function get_ordergoods($orderid,$type=0){
		$order_goods=array();
		switch ($type) {
			
			default:
				$content=" and orderid=:orderid";
				$sql="SELECT og.id,og.goodsid,g.disgoods_id,og.disprice,og.optionid,og.price,og.total from ".tablename("ewei_shop_order_goods")." og  "
					 . ' left join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id '
					 ." where 1".$content;
				$order_goods=pdo_fetchall($sql,array(":orderid"=>$orderid));
				break;
		}
		return $order_goods;
	}
	/**
	 * 获取订单数据
	 * @return [type] 检查是什么订单 并将订单统一格式
	 */
	public static function get_order($orderid,$type=0){
		$orderinfo=array();
		$temporder=array();
		$tempordergoods=array();
		switch ($type) {
			default:
			$order= pdo_fetch("SELECT id,price,depotid,paystatus,uniacid,deductenough,couponprice,buyagainprice,discountprice,isdiscountprice,deductprice,deductcredit2,seckilldiscountprice,dispatchprice,goodsprice from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
			$deductenough=$order['deductenough'];//满额立减优惠
			$couponprice=$order['couponprice'];//优惠券优惠
			$buyagainprice=$order['buyagainprice'];//重复购买优惠
			$discountprice=$oder['discountprice'];//会员优惠
			$isdiscountprice=$order['isdiscountprice'];//促销优惠
			$deductprice=$order['deductprice'];//抵扣
			$seckilldiscountprice=$oder['seckilldiscountprice'];//秒杀优惠
			$alldeduct=$deductenough+$couponprice+$buyagainprice+$discountprice+$isdiscountprice+$deductprice+$seckilldiscountprice;//总优惠
			$depotid=$order['depotid'];
			$ordergoods=Dispage::get_ordergoods($orderid,$type);
			foreach($ordergoods as $key=>$goods){
				$type=Dispage::get_disType($goods['disgoods_id'],$order['uniacid']);
				if($type){
					$depotid=Dispage::get_depotid($type,$goods['goodsid']);//如果是代理商品找到最新的仓库信息
				}
				$tempordergoods[$key]['id']=$goods['id'];
				$tempordergoods[$key]['total']=$goods['total'];
				$tempordergoods[$key]['orderid']=$orderid;
				$tempordergoods[$key]['goodsid']=$goods['goodsid'];
				$tempordergoods[$key]['depotid']=$depotid;
				$tempordergoods[$key]['disgoods_id']=$goods['disgoods_id'];
				$tempordergoods[$key]['isdis']=$type;
				$tempordergoods[$key]['disprice']=$goods['disprice'];
				$tempordergoods[$key]['unitprice']=$goods['price']/$goods['total'];//商品单价
			}
			$temporder=array(
				'id'=>$order['id'],
				'price'=>$order['price'],
				'depotid'=>$depotid,
				'uniacid'=>$order['uniacid'],
				'goodsprice'=>$order['goodsprice'],
				'shipping_fee'=>$order['dispatchprice'],
				'alldeduct'=>$alldeduct,
				);
			$orderinfo['order']=$temporder;
			$orderinfo['ordergoods']=$tempordergoods;
			unset($ordergoods);
			unset($order);
			unset($type);
		}
		return $orderinfo;
	}

	/**
	 * 获取代理价
	 * @param  [type] $goodsid [description]
	 * @param  [type] $uniacid [description]
	 * @return [type]          [description]
	 */
	public static function get_disprice($goodsid,$uniacid){
		$disgoods_id=pdo_fetchcolumn("select disgoods_id from ".tablename("ewei_shop_goods")." where id =:id",array(":id"=>$goodsid));
		$bool=Dispage::get_disType($disgoods_id,$uniacid);
		if($bool){
			$disinfo=Dispage::getDisInfo($uniacid);
			$resellerid=$disinfo['resellerid'];

			return Dispage::get_goods_disprice($disgoods_id,$resellerid);
		}
		return 0;
	}
	/**
	 * 获取单个单个商品的
	 * @param  [type] $goodsid [description]
	 * @return [type]          [description]
	 */
	public static function get_goods_disprice($goodsid,$resellerid){
		$resel=pdo_fetch("SELECT * from ".tablename("ewei_shop_goodsresel")." WHERE goods_id=:goodsid",array(":goodsid"=>$goodsid));
		if(!empty($resel)){
			
			$disprice=unserialize($resel['disprice']);
			return $disprice[$resellerid];
		}
		return 0;
	}

	public static function get_disprice_order($orderid,$type=0){
		switch ($type) {
			default:
				
				break;
		}
	}

	public static function get_goods_commission_price($goodsid,$openid){

		$cinfo = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id limit 1", array(':id' => $goodsid));

		if(empty($cinfo)){
			return 0;
		}
		$price=$cinfo['marketprice'];
		$set = p('commission')->getSet();
		$levels =p('commission')->getLevels();
		$goods_commission = !empty($cinfo['commission']) ? json_decode($cinfo['commission'], true) : '';
		$commissionlist=array();
		if (empty($cinfo['nocommission'])) { //如果参与分销
            if ($cinfo['hascommission'] == 1) {
				if (empty($goods_commission['type'])) {
					$commissionlist['commission1'] = array('default' => $set['level'] >= 1 ? ($cinfo['commission1_rate'] > 0 ? round($cinfo['commission1_rate']/ 100* $price, 2) . "" : round($cinfo['commission1_pay'] , 2)) : 0);

					
                    $commissionlist['commission2'] = array('default' => $set['level'] >= 2 ? ($cinfo['commission2_rate'] > 0 ? round($cinfo['commission2_rate'] * $price / 100, 2) . "" : round($cinfo['commission2_pay'] , 2)) : 0);
                 
                    $commissionlist['commission3'] = array('default' => $set['level'] >= 3 ? ($cinfo['commission3_rate'] > 0 ? round($cinfo['commission3_rate'] * $price / 100, 2) . "" : round($cinfo['commission3_pay'], 2)) : 0);
                    foreach ($levels as $level) {

                        $commissionlist['commission1']['level' . $level['id']] = $cinfo['commission1_rate'] > 0 ? round($cinfo['commission1_rate'] * $price / 100, 2) . "" : round($cinfo['commission1_pay'], 2);
                        $commissionlist['commission2']['level' . $level['id']] = $cinfo['commission2_rate'] > 0 ? round($cinfo['commission2_rate'] * $price / 100, 2) . "" : round($cinfo['commission2_pay'], 2);
                        $commissionlist['commission3']['level' . $level['id']] = $cinfo['commission3_rate'] > 0 ? round($cinfo['commission3_rate'] * $price / 100, 2) . "" : round($cinfo['commission3_pay'], 2);
                    }
				}else{
					if (empty($cinfo['hasoption'])) {
						for ($i = 0; $i < $set['level']; $i++) {
							if (!empty($goods_commission['default']['option0'][$i])){
								if (strexists($goods_commission['default']['option0'][$i], '%')) {
                                                    //促销折扣
                                    $dd = floatval(str_replace('%', '', $goods_commission['default']['option0'][$i]));

                                    if ($dd > 0 && $dd < 100) {
                                        $temp_price[$i] = round($dd / 100 * $price, 2);
                                    } else {
                                    $temp_price[$i] = 0.00;
									}
								}else{
									$temp_price[$i] = round($goods_commission['default']['option0'][$i], 2);
								}
							}
						}
						$commissionlist['commission1'] = array('default' => $set['level'] >= 1 ? $temp_price[0] : 0);
                        $commissionlist['commission2'] = array('default' => $set['level'] >= 2 ? $temp_price[1] : 0);
                        $commissionlist['commission3'] = array('default' => $set['level'] >= 3 ? $temp_price[2] : 0);
                        foreach ($levels as $level) {
                                            $temp_price = array();
                                            for ($i = 0; $i < $set['level']; $i++) {
                                                if (!empty($goods_commission['level' . $level['id']]['option0'][$i])) {
                                                    if (strexists($goods_commission['level' . $level['id']]['option0'][$i], '%')) {
                                                        //促销折扣
                                                        $dd = floatval(str_replace('%', '', $goods_commission['level' . $level['id']]['option0'][$i]));

                                                        if ($dd > 0 && $dd < 100) {
                                                            $temp_price[$i] = round($dd / 100 * $price, 2);
                                                        } else {
                                                            $temp_price[$i] = 0.00;
                                                        }
                                                    } else {
                                                        //促销价格
                                                        $temp_price[$i] = round($goods_commission['level' . $level['id']]['option0'][$i], 2);
                                                    }
                                                }
                                            }

                                            $commissionlist['commission1']['level' . $level['id']] = $temp_price[0];
                                            $commissionlist['commission2']['level' . $level['id']] = $temp_price[1];
                                            $commissionlist['commission3']['level' . $level['id']] = $temp_price[2];
                        }

					}
				}
            }else {
             	$commissionlist['commission1'] = array('default' => $set['level'] >= 1 ? round($set['commission1'] * $price / 100, 2) . "" : 0);
                                $commissionlist['commission2'] = array('default' => $set['level'] >= 2 ? round($set['commission2'] * $price / 100, 2) . "" : 0);
                                $commissionlist['commission3'] = array('default' => $set['level'] >= 3 ? round($set['commission3'] * $price / 100, 2) . "" : 0);
                                foreach ($levels as $level) {

                                    $commissionlist['commission1']['level' . $level['id']] = $set['level'] >= 1 ? round($level['commission1'] * $price / 100, 2) . "" : 0;
                                    $commissionlist['commission2']['level' . $level['id']] = $set['level'] >= 2 ? round($level['commission2'] * $price / 100, 2) . "" : 0;
                                    $commissionlist['commission3']['level' . $level['id']] = $set['level'] >= 3 ? round($level['commission3'] * $price / 100, 2) . "" : 0;
                                }
            }
        }else {
                $commissionlist['commission1'] = array('default' => 0);
                $commissionlist['commission2'] = array('default' => 0);
                $commissionlist['commission3'] = array('default' => 0);
                foreach ($levels as $level) {
                    $commissionlist['commission1']['level' . $level['id']] = 0;
                    $commissionlist['commission2']['level' . $level['id']] = 0;
                    $commissionlist['commission3']['level' . $level['id']] = 0;
                }
            }
       
		 $m1 = m('member')->getMember($openid);

		 if ($m1['isagent'] == 1 && $m1['status'] == 1) { 
				$l1 = p('commission')->getLevel($m1['openid']);
				$commissions['level1'] = empty($l1) ? round($commissionlist['commission1']['default'], 2) : round($commissionlist['commission1']['level' . $l1['id']], 2);
		 }
			return $commissions['level1'];
    }


    public static function createNO($table, $field, $prefix) {

		$billno = date('YmdHis') . random(6, true);
		while (1) {
			$count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_' . $table) . " where {$field}=:billno limit 1", array(':billno' => $billno));
			if ($count <= 0) {
				break;
			}
			$billno = date('YmdHis') . random(6, true);
		}
		return $prefix . $billno;
	}


}