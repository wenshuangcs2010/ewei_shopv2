<?php
	//盛付通转账代扣接口
	//
	/**
	* 
	*/

	class Shenfupay 
	{	
		function __construct(){
			$this->Shenfupay();
		}

		
		//默认为1.0
		var $version='1.0';
		//Transfer-转账，Billing-代扣
		var $InterfaceType='Billing';
		var $Payer_MemberId="";
		var $_Payeskey = '';
		//接口编号
		var $AppId;
		var $_customs_api_trans = 'http://mas.shengpay.com/api-acquire-channel/services/trans';
		//转账请求
		var $ReqBody=array(
			'Amount'=>'',
			'Currency'=>'Rmb',
			'MerchantOrderId'=>'',
			'Payer'=>array('MemberId'=>'','MemberIdType'=>"PtId"),
			'ToPayer'=>array('MemberId'=>'','MemberIdType'=>"PtId"),
			'Ext'=>'',
			);
		//调用方机器名
		var $MachineName="";
		var $_Machine="";
		//商户号
		var $MerchantNo='';
		//摘要信息
		var $Summary="";
		//签名类型
		var $SignType="2";
		//签名串
		var $Mac;
		//扩展字段
		function test(){
			var_dump($this->ReqBody['ToPayer']['MemberId']);
		}
		var $_customs_api	=  'http://mas.shengpay.com/api-acquire-channel/services/trans?wsdl';  //代发代扣网关
		
		function Shenfupay()
		{
			require EWEI_SHOPV2_TAX_CORE. '/Transfer/Transfer.config.php';
			$this->_Payeskey=$config['shenfupay']['a']['Payeskey'];
			$this->MerchantNo=$config['shenfupay']['a']['MerchantNo'];
			$this->Summary=$config['shenfupay']['Summary'];
			$this->_Machine=$config['shenfupay']['Machine'];
			$this->MachineName=$config['shenfupay']['MachineName'];
			$this->Payer_MemberId=$config['shenfupay']['b']['MerchantNo'];
			$this->ReqBody['ToPayer']['MemberId']=$config['shenfupay']['a']['MerchantNo']."@sfb.mer";
		}
		/*
		function __construct($pay_config){
			$this->MerchantNo=$api_config['partner'];
		}
		*/
		/**
		 * [_transPay description]
		 * @param  [type] $order_info     订单
		 * @param  [type] $Payer_MemberId 付款方 盛付通账号
		 * @return [type]                 [description]
		 */
		function _transPay($order_info,$Payer_MemberId){
			$this->ReqBody['Amount']=$order_info['order_amount'];
			$this->ReqBody['MerchantOrderId']=$order_info['order_sn'];
			$this->ReqBody['Payer']['MemberId']=$Payer_MemberId."@sfb.mer";
			$Ext=array(
				array("Key"=>"invokeIp","Value"=>$this->MachineName),
    			array("Key"=>"idNo","Value"=>$order_info['im_id']),
    			array("Key"=>"realName","Value"=>$order_info['real_name']),
    			array("Key"=>"mobile","Value"=>$order_info['phone_mob']),
				);
			$this->ReqBody['Ext']=$Ext;
			$this->Mac=$this->getSignStr();
			return $this->soap_clice();
		}


		private function soap_clice(){

			if(!get_extension_funcs("soap")){
				return false;
			}
			ini_set('soap.wsdl_cache_enabled', 0);
			//rdump(get_extension_funcs("soap"));
			try {
	    		//$client = new SoapClient("HelloService.wsdl",array('encoding'=>'UTF-8'));
	    		$options = array(
					'trace'=>true,
					'cache_wsdl'=>WSDL_CACHE_NONE,
					'soap_version'=> SOAP_1_1
				);
	    		$client = new SoapClient($this->_customs_api,$options);
	    		$param = array(
	    			'Version' => $this->version,
	    			'InterfaceType' =>$this->InterfaceType,
	    			'AppId' => $this->AppId,
	    			'MerchantNo' => $this->MerchantNo,
	    			'ReqBody' => $this->ReqBody,
	    			'MachineName' => $this->_Machine,
	    			'Summary' => $this->Summary,
	    			'SignType' => $this->SignType,
	    			'Mac' => $this->Mac,
	    			//'Ext' => $this->Ext,
	    			);
	    		//rdump($param);

	  			$transResponse = $client->__soapCall('Transfer', array(array('request'=>$param)),array('location' => $this->_customs_api_trans)); //以数组形式传递params 
	  			
	  			$respTrans_s = $transResponse->TransferResult;
	  			return $respTrans_s;
			} catch (SOAPFault $e) {
    			print $e;
			}
		}
		private function getSignStr(){
			$signMessage=$this->version."|".
				$this->InterfaceType."|".
				$this->MerchantNo."|".
				$this->AppId."|".
				$this->ReqBody['Amount']."|".
				strtoupper($this->ReqBody['Currency'])."|".
				$this->ReqBody['MerchantOrderId']."|".
				$this->ReqBody['Payer']['MemberId']."|".
				strtoupper($this->ReqBody['Payer']['MemberIdType'])."|".
				$this->ReqBody['ToPayer']['MemberId']."|".
				strtoupper($this->ReqBody['ToPayer']['MemberIdType'])."|".
				$this->_Machine."|".
				$this->Summary.
				$this->_Payeskey;
				//rdump($signMessage);
			$sign= mb_convert_encoding($signMessage, 'gbk', 'utf-8');
			return strtoupper(md5($sign));
		}
	}
	
?>