<?php
require_once("CryptAES.inc.php");
class Sendorder{
	//保税超市账号:gouqq
	var $app_id="ueNzpS61848";
	var $app_sect="4b6c573fa9d74159";
	var $params=array();
	var $api="http://www.cnbuyers.cn/index.php?app=webService&act=addToutOrder&app_id=APP_ID&v=2.0&format=json";
	public function init($params){
		$this->params['consignee']=urlencode($params['address']['realname']);
		$this->params['order_sn']=$params['ordersn'];
		$this->params['realName']=urlencode($params['realname']);
		$this->params['account_id']="";
		$this->params['imId']=$params['imid'];
		$this->params['disType']="T";
		$this->params['payment_id']=13;
		$this->params['tradeNum']=$params['paymentno'];;
		$this->params['out_order_sn']=$params['ordersn'];
		$this->params['order_amount']=$params['price'];
		$this->params['phoneMob']=$params['address']['mobile'];
		$this->params['address']=urlencode($params['address']['address']);
		$this->params['province']=urlencode($params['address']['province']);
		$this->params['city']=urlencode($params['address']['city']);
		$this->params['county']=urlencode($params['address']['area']);
		$this->params['shipping_id']="";
		$this->params['shipping_fee']=$params['dispatchprice'];
	}
	public function get_values(){
		return $this->params;
	}
	public function init_out_goods($goodslist){
		$goodstemp1=array();
		foreach($goodslist as $goods){
			$goodstemp['goods_name']="aaa";
			$goodstemp['only_sku']=$goods['goodssn'];
			$goodstemp['quantity']=$goods['total'];
			$goodstemp['price']=$goods['price']/$goods['total'];
			$goodstemp1[]=$goodstemp;
		}
		$this->params['outOrderGoods']=$goodstemp1;
	}



	public function iHttpPost(){
		
		$apiurl = str_replace('APP_ID', $this->app_id, $this->api);
		$poststr=json_encode($this->params);
		//var_dump($poststr);
		$postdata['paramjson']=Security::encrypt($poststr,$this->app_sect);
		//var_dump($postdata['paramjson']);
		load()->func('communication');
		$resp = ihttp_request($apiurl, $postdata);
		$content=(array)json_decode($resp['content']);
		$data=(array)$content['data'];
		return $data;
	}

	public function datadeencrypt($data){
		return Security::decrypt($data,$this->app_sect);
	}

}