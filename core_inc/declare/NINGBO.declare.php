<?php
require_once "ningbo.data.php";
class NINGBO_Api extends ningboData{
	var $_cnec_jh_order = 'cnec_jh_order'; //进口订单
	var $_cnec_jh_cancel="cnec_jh_cancel";
	var $_cnec_jh_decl_byorder="cnec_jh_decl_byorder";
	function __construct($config){
		//var_Dump($config);
		$this->ningboData($config);
	}
	function to_order_declare($order,$expressname,$order_goods){
		if(empty($order['mftno'])){
			$this->setOperation();
		}else{
			$this->setOperation("1");
		}
		$this->setMftNo($order['mftno']);
		$this->setOrderNo($order['ordersn']);
		$this->setPostFee($order['dpostfee']);
		$this->setAmount($order['price']);
		$this->setTaxAmount($order['tax_rate']+$order['tax_consumption']);
		$this->setAddedValueTaxAmount($order['tax_rate']);
		$this->setConsumptionDutyAmount($order['tax_consumption']);
		$this->setGrossWeight($order['weight']);
		$this->setPaytime($order['paytime']);
		$this->setPaymentNo($order['paymentno']);
		$this->setOrderSeqNo($order['paymentno']);
		$this->setSource($order['paytype']);
		$this->setIdnum($order['imid']);
		$this->setName($order['realname']);
		$this->setLogisticsName($expressname);
		$address=unserialize($order['address']);
		$this->setConsignee($address['realname']);
		$this->setProvince($address['province']);
		$this->setCity($address['city']);
		$this->setDistrict($address['area']);
		$this->setConsigneeAddr($address['address']);
		$this->setConsigneeTel($address['mobile']);
		$this->setGoods($order_goods);
		$this->init_prams();
	}
	function init(){

		$this->xml=$this->toXml($this->params);
		//WeUtility::logging('contetn', var_export($this->params,true));
		//header("Content-Type:XML;charset=utf-8");
		return $this->cnec_jh_order();
	}
	function cnec_jh_order(){
		load()->func('communication');
		 $posturl="
                 &timestamp=".urlencode(date('Y-m-d H:i:s'))."
                 &userid=".$this->_OrgUser."
                 &sign=".md5($this->_OrgUser.$this->_Orgkey.date('Y-m-d H:i:s'))."
                 &xmlstr=".urlencode($this->xml).'
                 &msgtype='.$this->_cnec_jh_order.'
                 &customs=3105';
        $resp = ihttp_request($this->_api,$posturl);
       return (array)$resp['content'];
	}


		    /**
	     * 订单关闭
	     * @param 申报单 $mftno
	     * @return string|Ambigous
	     */
	    function cnec_jh_cancel($mftno){
	        if(empty($mftno)){
	            return $error = '申报单缺失！';
	        }
	        
	        $time = date('Y-m-d H:i:s');
	       
	        $xml_data['Header']['CustomsCode'] = $this->_CustomsCode;
	        $xml_data['Header']['OrgName'] = $this->_OrgName;
	        $xml_data['Header']['CreateTime'] = $time;
	        $xml_data['Body']['Order']['MftNo'] = $mftno;
	        $xml = $this->toXml($xml_data, 'Message');

	        $posturl="timestamp=".urlencode($time)."&userid=".$this->_OrgUser."&sign=".md5($this->_OrgUser.$this->_Orgkey.$time)."&xmlstr=".urlencode($xml).'&msgtype='.$this->_cnec_jh_cancel.'&customs=3105';
	        $resp= ihttp_request($this->_api, $posturl);
	        return (array)$resp['content'];
	    }
	    /**
	     * 订单查询
	     * @param unknown $orderid
	     * @return string|Ambigous
	     */
	    function cnec_jh_decl_byorder($mftno){
	        if(empty($mftno)){
	            return;
	        }
	        $xml_data['Header']['CustomsCode'] = $this->_CustomsCode;
	        $xml_data['Header']['OrgName'] = $this->_OrgName;
// 	        $xml_data['Header']['MftNo'] = '31052016I004075730';
	        $xml_data['Header']['MftNo'] = $mftno;
	        $xml = $this->toXml($xml_data, 'Message');
	        $posturl="
	             &timestamp=".urlencode(date('Y-m-d H:i:s'))."
                 &userid=".$this->_OrgUser."
                 &sign=".md5($this->_OrgUser . $this->_Orgkey . date('Y-m-d H:i:s'))."
                 &xmlstr=".urlencode($xml).'
                 &msgtype='.$this->_cnec_jh_decl_byorder.'
                 &customs=3105';
	        $resp= ihttp_request($this->_api, $posturl);
	        return (array)$resp['content'];
	    }
	    
}