<?php

class shenfupay extends customs{
	var $_gateway   =   'http://global.shengpay.com/fexchange-customs/rest/submitApply';  

	function to_customs($params){
		require EWEI_SHOPV2_TAX_CORE.'/transfer/Transfer.config.php';
		$parameter = array(
				"requestNo" => $params['order_sn'],//订单号
				"customsType" => $params['customs_place'],//报关地点
				"businessMode" => 'BONDED', 
				"merchantOrderNo"	=> $params['order_sn'], 
				"payOrderNo"	=> $params['trade_num'],
				"orderAmount" => $params['amount'],
				"paymentAmount"	=> $params['amount'],
				"expressFee"	=> $params['shipping_fee'],
				"tax"	=> $params['amount_tariff'],
				"merchantNo" => $config['shenfupay']["a"]['MerchantNo'],
				"companyCustomsCode" =>$params['mch_customs_no'],
				"memo" => '',
		);
		
		$res_msg =  $this->_curl_post($this->_gateway, $parameter);

		$arrstr['message']=$res_msg->responseMsg;
		$arrstr['responseCode'] = $res_msg->responseCode;
		$arrstr['status'] = $res_msg->status;
	
		return $arrstr;
	}
}