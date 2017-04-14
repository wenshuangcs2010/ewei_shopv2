<?php
require_once(EWEI_SHOPV2_TAX_CORE."core.php");
class ErpHttp{
	//仓库获取接口
	var $postdata;
	function __construct(){
		global $_config;
		$this->postdata['key']=$_config['erp']['key'];
		$this->postdata['secret']=$_config['erp']['secret'];
		$this->postdata['userId']=$_config['erp']['userId'];
	}
	var $sdkpay="sdkj2014";
	var $storeurl="http://ku.inshion.com:8006/OpenApi/StoreroomList.ashx";
	var $LogisticsListUrl="http://ku.inshion.com:8006/OpenApi/LogisticsList.ashx";
	var $GoodsStock="http://ku.inshion.com:8006/OpenApi/GoodsStock.ashx";
	var $GoodsListURl="http://ku.inshion.com:8006/OpenApi/GoodsList.ashx";
	var $sendOrderUrl="http://ku.inshion.com:8006/OpenApi/ReceiveOrder.ashx";
	public function  getStoreRoom(){
		load()->func('communication');
		$this->postdata['time']=date("Y/m/d H:i:s");
		$this->postdata['yzm']=strtoupper(md5($this->postdata['secret'].$this->postdata['time']));
		$resp = ihttp_request($this->storeurl, $this->postdata);
		if(!empty($resp['content'])){
			//$arr = (array) simplexml_load_string($resp['content'],null, LIBXML_NOCDATA);
			$resp=json_decode($resp['content'], true);
			return $resp;
		}
	}
	public function getLogisticsList($storeroomId){//获取仓库配送方式
	
		$this->postdata['time']=date("Y/m/d H:i:s");
		$this->postdata['yzm']=strtoupper(md5($this->postdata['secret'].$this->postdata['time']));
		$this->postdata['storeroomId']=$storeroomId;
		load()->func('communication');
		$resp = ihttp_request($this->LogisticsListUrl, $this->postdata);
		if(!empty($resp['content'])){
			//$arr = (array) simplexml_load_string($resp['content'],null, LIBXML_NOCDATA);
			$resp=json_decode($resp['content'], true);;
			var_dump($resp);
		}
	}

	public function getGoodsStock($storeroomId,$pageindex,$goodsSn="",$pagesize=500){
		global $_config;
		$this->postdata['time']=date("Y/m/d H:i:s");
		$this->postdata['yzm']=strtoupper(md5($this->postdata['secret'].$this->postdata['time']));
		$this->postdata['storeroomId']=$storeroomId;
		$this->postdata['goodsId']=$goodsSn;
		$this->postdata['pageindex']=$pageindex;//必填
		$this->postdata['pagesize']=$pagesize;
		$this->postdata['clientName']=$_config['erp']['clientName'];//必填

		load()->func('communication');
		$resp = ihttp_request($this->GoodsStock, $this->postdata);
		if(!empty($resp['content'])){
			//$arr = (array) simplexml_load_string($resp['content'],null, LIBXML_NOCDATA);
			$resp=json_decode($resp['content'], true);;
			return $resp;
		}
	}

	public function getGoodsList($pageindex,$goodssn="",$pagesize=500){
		$this->postdata['time']=date("Y/m/d H:i:s");
		$this->postdata['yzm']=strtoupper(md5($this->postdata['secret'].$this->postdata['time']));
		$this->postdata['goodsId']=$goodssn;
		$this->postdata['pageindex']=$pageindex;//必填
		$this->postdata['pagesize']=$pagesize;
		//$this->postdata['startTime']=date("Y/m/d H:i:s",strtotime("-30 day"));
		//$this->postdata['endTime']=date("Y/m/d H:i:s");
		load()->func('communication');
		$resp = ihttp_request($this->GoodsListURl, $this->postdata);
		if(!empty($resp['content'])){
			//$arr = (array) simplexml_load_string($resp['content'],null, LIBXML_NOCDATA);
			$resp=json_decode($resp['content'], true);;
			return $resp;
		}
	}

	public function sendOrder($order){
		global $_config;
		$datatime=date("Y/m/d H:i:s");
		$this->postdata['time']=$datatime;
		$this->postdata['yzm']=strtoupper(md5($this->postdata['secret'].$this->postdata['time']));
		$this->postdata['orderId']=$order['order_sn'];
		$this->postdata['storeroomId']=$order['storeroomid'];
		$this->postdata['logisticId']=$order['logisticid'];
		$this->postdata['clientName']=$_config['erp']['clientName'];//必填
		//$this->postdata['clientMessage']=md5(str);
		$clientMessage=md5_4($_config['erp']['clientName'].md5_4($_config['erp']['clientParssword']).$datatime);
		$this->postdata['clientMessage']=$clientMessage;
		$payMessage=md5_4($_config['erp']['clientName'].md5_4($_config['erp']['payParssword']).$this->sdkpay);
		$this->postdata['payMessage']=$payMessage;
		$this->postdata['receiveName']=$order['receiveName'];//收货人
		$this->postdata['provinceName']=$order['provinceName'];//省
		$this->postdata['cityName']=$order['cityName'];//市
		$this->postdata['areaName']=$order['areaName'];//区
		$this->postdata['address']=$order['address'];//详细地址
		$this->postdata['postCode']=$order['postCode'];//邮编
		$this->postdata['mobile']=$order['mobile'];//手机
		$this->postdata['phone']=$order['phone'];//电话
		$this->postdata['orderDetail']=json();//JOSN
	}
	public function test(){
		var_dump(md5_4(123));
	}
	
}