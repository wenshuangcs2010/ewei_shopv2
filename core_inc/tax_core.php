<?php
/**
* 
*/
class Taxcore 
{
	/**
		* [core_tax description]
		* @param  [type]  $goods_price           申报价格
		* @param  integer $vat_rate        增值税率
		* @param  integer $consumption_tax 消费税率
		* @param  integer $tariff          关税税率
		* @return [type]                   ARRAY
		*/
		public function core_tax($goods_price,$vat_rate=0,$consumption_tax=0,$tariff=0){
			//法定关税税额
			$charge_tariff=$goods_price*$tariff;
			//法定消费税额
			$charge_consumption_tax=$goods_price/(1-$consumption_tax)*$consumption_tax;
			//法定增值税税额
			$charge_rate=($goods_price+$charge_consumption_tax)*$vat_rate;
			//var_dump($charge_rate);
			//应征消费税额
			$draft_consumption_tax=$charge_consumption_tax*0.7;
			//应征增值税税额
			$draft_rate=$charge_rate*0.7;
			//综合税
			$consolidated_tax=($charge_consumption_tax+$charge_rate+$charge_tariff)*0.7;
			return array('charge_tariff'=>$charge_tariff,'consumption_tax'=>$draft_consumption_tax,'rate'=>$draft_rate,'consolidated'=>$consolidated_tax);
		}
		/**
		 * 逆推申报价格
		 * @param  [type] $goods_price     售价
		 * @param  [type] $vat_rate        增值税率
		 * @param  [type] $consumption_tax 消费税率
		 * @return [type]                  INt
		 */
		private function get_taxprice($goods_price,$vat_rate,$consumption_tax){
			$caounts=0.7*($consumption_tax+$vat_rate)/(1-$consumption_tax);//综合税税率
			$price=$goods_price/(1+$caounts);
			return $this->get_decimal_price($price);
		}
		/**
		 * 	计算综合税率
		 */
		public function get_compositetaxrate($vat_rate,$consumption_tax){
			 $tax_tax= ($vat_rate+$consumption_tax)/(1-$consumption_tax)*0.7;
			 return $this->get_decimal_price($tax_tax,4);
		}
		/**
		 * 获取四舍五入的价格 //默认4位小数
		 * @var [type]
		 */
		private function get_decimal_price($price,$number=4){
			return round($price, $number);
		}
		/**
		* 获取商品优惠后的单价
	 	* @return [type] [description]
	 	*/
		private function get_price($unitprice,$total,$alldeduct,$goodsprice){

			if($alldeduct>0){
				$alldeduct=$alldeduct;
				return $unitprice-$unitprice*$total/$goodsprice*$alldeduct/$total;
			}
			//var_Dump($unitprice."--".$unitprice."---".$total."---".$goodsprice);
			return $unitprice;
		}

		/**
	  		* 申报运费计算
	  		* @param  [type] $ordergoods   isdis,dprice,total
	  		* @param  [type] $shipping_fee [description]
	  		* @return [type]               [description]
	  	*/
	  	function get_depostfee($ordergoods,$shipping_fee){
	  		$before_tax_price=0;
	  		foreach($ordergoods as $goods){

	  		}
	  	}

	  	function get_dprice_order($ordergoods,$shipping_fee,$goodsprice,$alldeduct=0){
	  		$before_tax_price=0;
	  		$test=0;
	  		//var_dump($ordergoods);
	  		foreach($ordergoods as &$goods){
	  			//$goods['price']=$goods['realprice']/$goods['total'];
	  			$newprice=$this->get_price($goods['price'],$goods['total'],$alldeduct,$goodsprice);//申报单价

	  			$goods['dprice']=$this->get_taxprice($newprice,$goods['vat_rate'],$goods['consumption_tax']);
	  			$before_tax_price+=$goods['dprice']*$goods['total'];
	  			$goods['tax']=$this->get_compositetaxrate($goods['vat_rate'],$goods['consumption_tax']);
	  			$result["$goods[tax]"][]=$goods;//将税率相同的放在一起
	  		}
	  		unset($goods);
	  		foreach($result as $k=> $v){
				$goods_amount=0;
				foreach($v as $goods){
						$goods_amount+=$goods['dprice']*$goods['total'];//每种税率申报商品总价
				}
				if($before_tax_price!=0){
					$tax_all[$k]=array("ratio"=>$goods_amount/$before_tax_price,'goods_amount'=>$goods_amount);
					//求出每种税率所占全部金额的比例
				}else{
					$tax_all[$k]=array("ratio"=>0,'goods_amount'=>$goods_amount);
				}
			}
			foreach ($tax_all as $key => $value) {
				$test+=(1+$key)*$value['ratio'];
			}
			if($test==0){
				$shengbao_shping_fee=0;
			}else{
				$shengbao_shping_fee=$shipping_fee/$test;//申报总运费
			}
			

			foreach ($tax_all as $key => $value) {
				$shping_fee_tax[$key]=$shengbao_shping_fee*$value['ratio'];//每种税率分配的总运费
			}
			foreach($ordergoods as &$goods){
			if($shping_fee_tax["$goods[tax]"]==0){
				$shping_fee_tax["$goods[tax]"]=1;
			}
			$goods["shipping_fee"]=0;
			if($shengbao_shping_fee!=0){
				$goods["shipping_fee"]=$goods['dprice']/$tax_all["$goods[tax]"]['goods_amount']*$shping_fee_tax["$goods[tax]"];//每个申报商品分配的税前运费
			}
			}

			unset($goods);
			return array('depostfee'=>$shengbao_shping_fee,"order_goods"=>$ordergoods);
	  	}

	  	function get_tax($ordergoods){
	  		foreach ($ordergoods as &$goods) {
	  			$goods['tax']=$this->core_tax($goods['dprice']+$goods['shipping_fee'],$goods['vat_rate'],$goods['consumption_tax']);
	  		}
	  		unset($goods);
	  		return $ordergoods;
	  	}



}