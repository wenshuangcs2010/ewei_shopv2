<?php
require_once "declare.util.php";
class NINGBOData extends declareUtil{
	var $params=array();
	var $_OrgName;
	var $_OrgUser;
	var $_OrderShop;
	var $_Orgkey;
	var $_CustomsCode;
	var $_OrderFrom;
	var $_OTOCode;
	var $_api;
	var $xml;

	function ningboData($config){

		$this->_OrgName=$config['orgname'];
		$this->_OrgUser=$config['rrguser'];
		$this->_OrderShop=$config['ordershop'];
		$this->_Orgkey=$config['orgkey'];
		$this->_CustomsCode=$config['customs_code'];
		$this->_OrderFrom=$config['orderfrom'];
		$this->_OTOCode="";
		$this->_api=$config['api_url'];
	}
	function init_prams(){

		$this->params['Header']['CustomsCode']=$this->_CustomsCode;
		$this->params['Header']['OrgName'] = $this->_OrgName;
		$this->params['Header']['CreateTime']=date('Y-m-d H:i:s', time());
		$this->params['Body']['Order']['OrderShop'] =$this->_OrderShop;
		$this->params['Body']['Order']['OTOCode'] = $this->_OTOCode;
		$this->params['Body']['Order']['OrderFrom'] = $this->_OrderFrom;
		$this->params['Body']['Order']['PackageFlag'] = "00";
		$this->params['Body']['Order']['InsuranceFee'] = "00";
		$this->params['Body']['Order']['Email'] ="";
		$this->params['Body']['Order']['TariffAmount'] = "00";
		$this->params['Body']['Order']['DisAmount'] = '00';
		$this->params['Body']['Order']['Promotions']['Promotion']['ProAmount'] = '';
		$this->params['Body']['Order']['Promotions']['Promotion']['ProRemark'] = '';
		$this->params['Body']['Pay']['MerId'] = ''; //银联在线商户号
		$this->params['Body']['Logistics']['LogisticsNo'] = ''; //运单号
		$this->params['Body']['Logistics']['MailNo'] = ''; //邮编
		$this->params['Body']['Logistics']['GoodsName'] = '';

	}
	//新申报还是修改申报
	function setOperation($operation=""){
		if(empty($operation)){
			$operation="00";
		}else{
			$operation="1";
		}
		$this->params['Body']['Order']['Operation']=$operation;
	}

	public  function get_values(){
		return $this->params;
	}
	//申报单号
	function setMftNo($MftNo=""){
		$this->params['Body']['Order']['MftNo']=$MftNo;
	}
	//物流备注
	function setDefault01($default=""){
		if(!empty($default)){
			$this->params['Body']['Logistics']['Default01'] = $default;
		}
	}
	//订单号
	function setOrderNo($OrderNo){
		$this->params['Body']['Order']['OrderNo']=$OrderNo;
	}
	function isOrderNo(){
		return array_key_exists('OrderNo', $this->params['Body']['Order']);
	}
	//运费
	function setPostFee($PostFee=0){
		$this->params['Body']['Order']['PostFee']=$PostFee;
	}
	//订单总价
	function setAmount($Amount){
		$this->params['Body']['Order']['Amount']=$Amount;
	}
	function isAmount(){
		return array_key_exists('Amount', $this->params['Body']['Order']);
	}
	//税总额
	function setTaxAmount($TaxAmount){
		if(empty($TaxAmount)){
			$TaxAmount='00';
		}
		$this->params['Body']['Order']['TaxAmount']=$TaxAmount;
	}
	//增值税
	function setAddedValueTaxAmount($AddedValueTaxAmount){
		if(empty($AddedValueTaxAmount)){
			$AddedValueTaxAmount=0;
		}
		$this->params['Body']['Order']['AddedValueTaxAmount']=$AddedValueTaxAmount;	
	}
	//消费税
	function setConsumptionDutyAmount($ConsumptionDutyAmount){
		if(empty($ConsumptionDutyAmount)){
			$ConsumptionDutyAmount=0;
		}
		$this->params['Body']['Order']['ConsumptionDutyAmount']=$ConsumptionDutyAmount;	
	}
	//商品重量
	function setGrossWeight($GrossWeight){
		$this->params['Body']['Order']['GrossWeight']=$GrossWeight;
	}
	//支付时间
	function setPaytime($Paytime){
		$this->params['Body']['Pay']['Paytime']=date('Y-m-d H:i:s', $Paytime);
	}
	//商家送支付机构订单交易号
	function setPaymentNo($PaymentNo){
		$this->params['Body']['Pay']['PaymentNo']=$PaymentNo;
	}
	//商家送支付机构订单订单号
	function setOrderSeqNo($OrderSeqNo){
		$this->params['Body']['Pay']['OrderSeqNo']=$OrderSeqNo;
	}
	//支付方式代码
	function setSource($paytype){
		$this->params['Body']['Pay']['Source']=$this->get_source($paytype);
	}
	//身份证
	function setIdnum($Idnum){
		$this->params['Body']['Pay']['Idnum']=$Idnum;
	}
	//真实姓名
	function setName($Name){
		$this->params['Body']['Pay']['Name']=$Name;
	}
	//快递公司名称
	function setLogisticsName($LogisticsName){
		$this->params['Body']['Logistics']['LogisticsName']=$LogisticsName;
	}
	//收货人名称
	function setConsignee($Consignee){
		$this->params['Body']['Logistics']['Consignee']=$Consignee;
		$this->params['Body']['Order']['BuyerAccount']=$Consignee;
	}
	//省
	function setProvince($Province){
		$this->params['Body']['Logistics']['Province']=$Province;
	}
	//市
	function setCity($City){
		$this->params['Body']['Logistics']['City']=$City;
	}
	//区
	function setDistrict($District){
		$this->params['Body']['Logistics']['District']=$District;
	}
	//详细地址
	function setConsigneeAddr($ConsigneeAddr){
		if(!array_key_exists('Province', $this->params)){
			$ConsigneeAddr=$this->params['Body']['Logistics']['Province'].$this->params['Body']['Logistics']['City'].$this->params['Body']['Logistics']['District'].$ConsigneeAddr;
		}
		$this->params['Body']['Logistics']['ConsigneeAddr']=$ConsigneeAddr;
	}
	//收货人电话
	function setConsigneeTel($ConsigneeTel){
		$this->params['Body']['Logistics']['ConsigneeTel']=$ConsigneeTel;
		$this->params['Body']['Order']['Phone']=$ConsigneeTel;
	}

	function setGoods($goods_list){

		foreach($goods_list as $rec_id => $goods)
		{
			if($goods['goodstype']==4){
				
				$packgoods=json_decode($goods['content3'],true);
				
				foreach ($packgoods as $key => $value) {
					$goods_detail[]=array(
						"Detail"=>
						array(
							'ProductId'=>$value['goodssn'],
							'GoodsName'=>$this->replace_specialChar($value['title']),
							'Qty'=> $value['total'],
							'Unit'=>$value['unit'],
							'Price'=>$value['dprice'],
							'Amount'=>$value['dprice']*$value['total'],
							));
				}
				continue;
			}
			$goods_detail[]=array(
						"Detail"=>
						array(
							'ProductId'=>$goods['goodssn'],
							'GoodsName'=>$this->replace_specialChar($goods['title']),
							'Qty'=> $goods['total'],
							'Unit'=>$goods['unit'],
							'Price'=>$goods['dprice'],
							'Amount'=>$goods['dprice']*$goods['total'],
							));
			// $goods_detail[$rec_id]['Detail']['ProductId'] = $goods['goodssn'];
			// $goods_detail[$rec_id]['Detail']['GoodsName'] = $this->replace_specialChar($goods['title']);
			// $goods_detail[$rec_id]['Detail']['Qty'] = $goods['total'];
			// if(empty($goods['unit'])) $goods['unit'] = '件';
			// 	$goods_detail[$rec_id]['Detail']['Unit'] = $goods['unit'];
			// 	$goods_detail[$rec_id]['Detail']['Price'] = $goods['dprice'];
			// 	$goods_detail[$rec_id]['Detail']['Amount'] = $goods['total'] * $goods['dprice'];
			
			}
			
		$this->params['Body']['Order']['Goods']=$goods_detail;
	}
	function setBuyerIdnum($BuyerIdnum){//订购人订购人身份证号码
		$this->params['Body']['Order']['BuyerIdnum']=$BuyerIdnum;
	}
	function setBuyerName($BuyerName){//订购人姓名
		$this->params['Body']['Order']['BuyerName']=$BuyerName;
	}
	function setBuyerIsPayer($BuyerIsPayer=1){//订购人支付人是否一致（0=不一致，1=一致）

		$this->params['Body']['Order']['BuyerIsPayer']=$BuyerIsPayer;
	}


}