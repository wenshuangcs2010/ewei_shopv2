<?php
    class Declarecore{
        /**
         * 组装XML
         * @param unknown $data
         * @param string $rootNodeName
         * @param string $xml
         * @return mixed
         */
        function toXml($data, $rootNodeName = 'Message', $xml=null)
        {
            if (ini_get('zend.ze1_compatibility_mode') == 1)
            {
                ini_set ('zend.ze1_compatibility_mode', 0);
            }
             
            if ($xml == null)
            {
                $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8' ?><$rootNodeName  />");
                if($rootNodeName == 'mo'){
                    $xml = simplexml_load_string("<?xml version='1.0'?><$rootNodeName version='1.0.0' />");
                }
            }
            
            
             
            foreach($data as $key => $value)
            {
                if(!$value) $value = " ";
                if($value =='00') $value = "0";
                if (is_numeric($key))
                {
                    $key = "Detail_". (string) $key;
                }
                $key = preg_replace('/[^a-z]/i', '', $key);
                if (is_array($value))
                {
                    if($key == 'Detail'){
                        $value = current($value);
                    }
                     
                    $node = $xml->addChild($key);
                    $this->toXml($value, $rootNodeName, $node);
                }
                else
                {
                    $value = $value;
                    $xml->addChild($key,$value);
                }
            }
            return $xml->asXML();
        }
         
        /**
         * 请求api 数据
         * @param unknown_type $api
         * @param unknown_type $posturl
         * @return Ambigous <unknown, multitype:>
         */
        function _curl_php($api, $posturl){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $api);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $posturl);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
            $response = curl_exec($curl);
            
//             $Str = fopen($_SERVER['DOCUMENT_ROOT']."/222.txt","w");
//             fwrite($Str,$response);
//             fclose($Str);

            $Str = fopen($_SERVER['DOCUMENT_ROOT']."/222.txt","a+");
            fwrite($Str,__LINE__.'---'. $response);
            fclose($Str);
            
            $stdclassobject = simplexml_load_string($response);
            $_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
            if(!empty($_array)){
                foreach ($_array as $key => $value){
                    $value = (is_array($value) || is_object($value)) ? get_object_vars($value) : $value;
                    $return_data[$key] = $value;
                }
            }
            return $return_data;
        }
        
        /**
         * 递归的方式将XML转换成数组
         * @param unknown_type $xml
         * @return multitype:multitype:Ambigous <multitype:>  multitype:Ambigous <multitype:Ambigous <multitype:> , multitype:>  unknown |unknown
         */
        function xml_to_array($xml)
        {
            $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
            if(preg_match_all($reg, $xml, $matches))
            {
                $count = count($matches[0]);
                $arr = array();
                for($i = 0; $i < $count; $i++)
                {
                    $key= $matches[1][$i];
                    $val = $this->xml_to_array( $matches[2][$i] );
                    if(array_key_exists($key, $arr))
                    {
                        if(is_array($arr[$key]))
                        {
                            if(!array_key_exists(0,$arr[$key]))
                            {
                                $arr[$key] = array($arr[$key]);
                            }
                        }else{
                            $arr[$key] = array($arr[$key]);
                        }
                        $arr[$key][] = $val;
                    }else{
                        $arr[$key] = $val;
                    }
                }
                return $arr;
            }else{
                return $xml;
            }
        }
         
        /**
         * 替换特殊字符
         **/
        function replace_specialChar($strParam){
            $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
            return preg_replace($regex," ",$strParam);
        }
    }

	class Kjb2c extends Declarecore{
	    var $_api;
	    var $_test_api;
	    var $_OrgName;
	    var $_OrgUser;
	    var $_Orgkey;
	    var $_CustomsCode;
	    var $_OrderShop;
	    var $_OTOCode;
	    var $_OrderFrom;
	    
	    var $_cnec_jh_order; //进口订单
	    var $_cnec_jh_decl_byorder;//申报查询
	    var $_cnec_jh_cancel; //进口订单关闭
	    var $_cnec_jh_rejdec; //退货
	    var $_cnec_jh_rejser;//退货详情
	    function __construct($depotid=10)
	    {
	        $this->Kjb2c($depotid);
	    }
	    
	    function Kjb2c($depotid)
	    {
	        date_default_timezone_set("PRC"); //时间格式
	        $this->_api = 'https://api.kjb2c.com/dsapi/dsapi.do';
	        $this->_test_api = 'http://api.trainer.kjb2c.com/dsapi/dsapi.do';
	        if($depotid){
	        	
	        	
		       	$depot = pdo_fetch("select * from" . tablename('ewei_shop_depot') . " where id=:id limit 1", array(':id' => $depotid));
		        $this->_OrgName=empty($depot['orgname']) ? $this->_OrgName : $depot['orgname'];
		        $this->_OrgUser=empty($depot['rrguser']) ? $this->_OrgUser : $depot['rrguser'];
		        $this->_OrderShop=empty($depot['ordershop']) ? $this->_OrderShop :$depot['ordershop'];
		        $this->_Orgkey=empty($depot['orgkey']) ?$this->_Orgkey :$depot['orgkey'];
		        $this->_CustomsCode=empty($depot['customs_code']) ?$this->_CustomsCode :$depot['customs_code'];
		        $this->_OrderFrom=empty($depot['orderfrom']) ?$this->_OrderFrom :$depot['orderfrom'];
		        $this->_OTOCode = ''; //OTO店铺代码
	       }else{
		       	$this->_OrgName = '浙江省粮油食品进出口股份有限公司';
		        $this->_OrgUser = "zjcof";
		        $this->_Orgkey = 'ce3a17ba-7a34-4635-a133-1d1cfeb694b4';
		        $this->_CustomsCode = '3301910115'; // 海关编码
		        $this->_OrderShop = '10083'; //店铺代码
		        $this->_OTOCode = ''; //OTO店铺代码
		        $this->_OrderFrom = '0007'; //购物网站代码
	       }

	        
	        
	        $this->_cnec_jh_order = 'cnec_jh_order'; //进口订单
	        $this->_cnec_jh_decl_byorder = 'cnec_jh_decl_byorder'; // 申报查询
	        $this->_cnec_jh_cancel = 'cnec_jh_cancel'; // 进口订单关闭
	        $this->_cnec_jh_rejdec = 'cnec_jh_rejdec'; // 退货
	        $this->_cnec_jh_rejser = 'cnec_jh_rejser';
	    }
	    
	    /**
	     * 进口订单申报
	     * @param unknown $order_id
	     */
	    function cnec_jh_order($order){
	        global $_W;
	        $openid = $_W['openid'];
	        $uniacid = $_W['uniacid'];
	        if (empty($order)) {
	            return $error = '没有该订单！';
	        }
	       
	       $goods_list = pdo_fetchall("SELECT g.title, g.uniacid, g.unit, g.weight, g.dispatchid, o.goodssn as option_goodssn, o.productsn as option_productsn,o.total,g.type, o.dprice FROM " . tablename('ewei_shop_order_goods') .
            " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid", array(':orderid' => $order['id']));
	       $dispatchid = 0;
	       foreach($goods_list as $rec_id => $goods)
	       {
	           $goods_detail[$rec_id]['Detail']['ProductId'] = $goods['option_goodssn'];
	           $goods_detail[$rec_id]['Detail']['GoodsName'] = $this->replace_specialChar($goods['title']);
	           $goods_detail[$rec_id]['Detail']['Qty'] = $goods['total'];
	           if(empty($goods['unit'])) $goods['unit'] = '件';
	           $goods_detail[$rec_id]['Detail']['Unit'] = $goods['unit'];
	           $goods_detail[$rec_id]['Detail']['Price'] = $goods['dprice'];
	           $goods_detail[$rec_id]['Detail']['Amount'] = $goods['total'] * $goods['dprice'];
	           $grossweight+=$goods['weight'];
	           
	           if($g['dispatchid'] > 0 && count($goods_list) == 1){
	               $dispatchid = $g['dispatchid'];
	           }
	       }
	       
	       //使用快递模板
	       $depot = pdo_fetch("select * from" . tablename('ewei_shop_depot') . " where id=:id limit 1", array(':id' => $order['depotid']));
	       if(count($goods_list) == 1 && $dispatchid){
	           $dispatch_data = m('dispatch')->getOneDispatch($dispatchid, $depot['uniacid'], $order['depotid']);
	       }else{
	           $dispatch_data = m('dispatch')->getDefaultDispatch($depot['uniacid'], $order['depotid']);
	       }

	       $operation = "00";
	       if($order['mftno']){
	           $operation = "1";
	       } 
	       
	       $address = unserialize($order['address']);
	       //var_dump($address);
	       $xml_data['Header']['CustomsCode'] = $this->_CustomsCode;
	       $xml_data['Header']['OrgName'] = $this->_OrgName;
	       $xml_data['Header']['CreateTime'] =date('Y-m-d H:i:s', $order['createtime']);
	       $xml_data['Body']['Order']['Operation'] = $operation;
	       $xml_data['Body']['Order']['MftNo'] = $order['mftno'];
	       $xml_data['Body']['Order']['OrderShop'] = $this->_OrderShop;
	       $xml_data['Body']['Order']['OTOCode'] = $this->_OTOCode;
	       $xml_data['Body']['Order']['OrderFrom'] = $this->_OrderFrom;
	       $xml_data['Body']['Order']['PackageFlag'] = "00";
	       $xml_data['Body']['Order']['OrderNo'] = $order['ordersn'];
	       $xml_data['Body']['Order']['PostFee'] = $order['dpostfee'];
	       $xml_data['Body']['Order']['InsuranceFee'] = "00";
	       $xml_data['Body']['Order']['Amount'] = $order['price'];
	       $xml_data['Body']['Order']['BuyerAccount'] = $address['realname'];
	       $xml_data['Body']['Order']['Phone'] = $address['mobile'];
	       $xml_data['Body']['Order']['Email'] ="";
	       $xml_data['Body']['Order']['TaxAmount'] = $order['tax_rate']+$order['tax_consumption'];
	       $xml_data['Body']['Order']['TariffAmount'] = "00";
	       $xml_data['Body']['Order']['AddedValueTaxAmount'] = $order['tax_rate'];
	       $xml_data['Body']['Order']['ConsumptionDutyAmount'] = $order['tax_consumption'];
	       $xml_data['Body']['Order']['GrossWeight'] = $grossweight;
	       $xml_data['Body']['Order']['DisAmount'] = '00';
	       $xml_data['Body']['Order']['Promotions']['Promotion']['ProAmount'] = '';
	       $xml_data['Body']['Order']['Promotions']['Promotion']['ProRemark'] = '';
	       $xml_data['Body']['Order']['Goods'] =$goods_detail;
	       $xml_data['Body']['Pay']['Paytime'] = date('Y-m-d H:i:s', $order['paytime']);
	       $xml_data['Body']['Pay']['PaymentNo'] = $order['paymentno'];
	       $xml_data['Body']['Pay']['OrderSeqNo'] = $order['ordersn']; //商家送支付机构订单交易号
	       //微信支付
	       if($order['paytype'] == 21){
	           load()->model('payment');
	           $setting = uni_setting($_W['uniacid'], array('payment'));
	           //$xml_data['Body']['Pay']['PaymentNo'] = $order['ordersn'];
	           //$xml_data['Body']['Pay']['OrderSeqNo'] = $setting['payment']['wechat']['mchid']; //微信商户号
	           $xml_data['Body']['Pay']['PaymentNo'] = $order['paymentno'];
	           $xml_data['Body']['Pay']['OrderSeqNo']= $order['paymentno'];
	       }
	       $xml_data['Body']['Pay']['Source'] = $this->get_source($order['paytype']); //支付方式代码
	       $xml_data['Body']['Pay']['Idnum'] = $order['imid']; //身份证
	       $xml_data['Body']['Pay']['Name'] = $order['realname'];  //真实姓名
	       $xml_data['Body']['Pay']['MerId'] = ''; //银联在线商户号
	       $xml_data['Body']['Logistics']['LogisticsNo'] = ''; //运单号
	       $xml_data['Body']['Logistics']['LogisticsName'] = $dispatch_data['expressname']; //快递公司名称
	       $xml_data['Body']['Logistics']['Consignee'] = $address['realname']; //收货人名称
	       $xml_data['Body']['Logistics']['Province'] = $address['province']; 
	       $xml_data['Body']['Logistics']['City'] = $address['city']; 
	       $xml_data['Body']['Logistics']['District'] = $address['area']; 
	       $xml_data['Body']['Logistics']['ConsigneeAddr'] = $this->replace_specialChar($address['province'].$address['city'].$address['area'].$address['address']);
	       $xml_data['Body']['Logistics']['ConsigneeTel'] = $address['mobile'];
	       $xml_data['Body']['Logistics']['MailNo'] = ''; //邮编
	       $xml_data['Body']['Logistics']['GoodsName'] = '';
	       $xml = $this->toXml($xml_data, 'Message');

	       date_default_timezone_set("PRC");
            $posturl="
                 &timestamp=".urlencode(date('Y-m-d H:i:s'))."
                 &userid=".$this->_OrgUser."
                 &sign=".md5($this->_OrgUser.$this->_Orgkey.date('Y-m-d H:i:s'))."
                 &xmlstr=".urlencode($xml).'
                 &msgtype='.$this->_cnec_jh_order.'
                 &customs=3105';
	       return $this->_curl_php($this->_api, $posturl);
	       
	    }
	    //通用申报
	    /*
	     组装order
	     */
	    function comm_cnec_jh_order($order){
	    	global $_W;
	        $uniacid = $_W['uniacid'];
	        if (empty($order)) {
	            return $error = '没有该订单！';
	        }
	        $goods_list=$order['goods_list'];
	        foreach($goods_list as $rec_id => $goods)
	       {
	           $goods_detail[$rec_id]['Detail']['ProductId'] = $goods['option_goodssn'];
	           $goods_detail[$rec_id]['Detail']['GoodsName'] = $this->replace_specialChar($goods['title']);
	           $goods_detail[$rec_id]['Detail']['Qty'] = $goods['total'];
	           if(empty($goods['unit'])) $goods['unit'] = '件';
	           $goods_detail[$rec_id]['Detail']['Unit'] = $goods['unit'];
	           $goods_detail[$rec_id]['Detail']['Price'] = $goods['dprice'];
	           $goods_detail[$rec_id]['Detail']['Amount'] = $goods['total'] * $goods['dprice'];
	           $grossweight+=$goods['weight'];
	           if($g['dispatchid'] > 0 && count($goods_list) == 1){
	               $dispatchid = $g['dispatchid'];
	           }
	       }
	           //使用快递模板
	       $depot = pdo_fetch("select * from" . tablename('ewei_shop_depot') . " where id=:id limit 1", array(':id' => $order['depotid']));
	       if(count($goods_list) == 1 && $dispatchid){
	           $dispatch_data = m('dispatch')->getOneDispatch($dispatchid, $depot['uniacid'], $order['depotid']);
	       }else{
	           $dispatch_data = m('dispatch')->getDefaultDispatch($depot['uniacid'], $order['depotid']);
	       }
	       
	     

	       $operation = "00";
	       if($order['mftno']){
	           $operation = "1";
	       } 
	       $address = $order['address'];
	       $xml_data['Header']['CustomsCode'] = $this->_CustomsCode;
	       $xml_data['Header']['OrgName'] = $this->_OrgName;
	       $xml_data['Header']['CreateTime'] =date('Y-m-d H:i:s', $order['createtime']);
	       $xml_data['Body']['Order']['Operation'] = $operation;
	       $xml_data['Body']['Order']['MftNo'] = $order['mftno'];
	       $xml_data['Body']['Order']['OrderShop'] = $this->_OrderShop;
	       $xml_data['Body']['Order']['OTOCode'] = $this->_OTOCode;
	       $xml_data['Body']['Order']['OrderFrom'] = $this->_OrderFrom;
	       $xml_data['Body']['Order']['PackageFlag'] = "00";
	       $xml_data['Body']['Order']['OrderNo'] = $order['ordersn'];
	       $xml_data['Body']['Order']['PostFee'] = $order['dpostfee'];
	       $xml_data['Body']['Order']['InsuranceFee'] = "00";
	       $xml_data['Body']['Order']['Amount'] = $order['price'];
	       $xml_data['Body']['Order']['BuyerAccount'] = $address['realname'];
	       $xml_data['Body']['Order']['Phone'] = $address['mobile'];
	       $xml_data['Body']['Order']['Email'] ="";
	       $xml_data['Body']['Order']['TaxAmount'] = $order['tax_rate']+$order['tax_consumption'];
	       $xml_data['Body']['Order']['TariffAmount'] = "00";
	       $xml_data['Body']['Order']['AddedValueTaxAmount'] = $order['tax_rate'];
	       $xml_data['Body']['Order']['ConsumptionDutyAmount'] = $order['tax_consumption'];
	       $xml_data['Body']['Order']['GrossWeight'] = $grossweight;
	       $xml_data['Body']['Order']['DisAmount'] = '00';
	       $xml_data['Body']['Order']['Promotions']['Promotion']['ProAmount'] = '';
	       $xml_data['Body']['Order']['Promotions']['Promotion']['ProRemark'] = '';
	       $xml_data['Body']['Order']['Goods'] =$goods_detail;
	       $xml_data['Body']['Pay']['Paytime'] = date('Y-m-d H:i:s', $order['paytime']);
	       $xml_data['Body']['Pay']['PaymentNo'] = $order['paymentno'];
	       $xml_data['Body']['Pay']['OrderSeqNo'] = $order['ordersn']; //商家送支付机构订单交易号
	       //微信支付
	       if($order['paytype'] == 21){
	           load()->model('payment');
	           $setting = uni_setting($_W['uniacid'], array('payment'));
	           //$xml_data['Body']['Pay']['PaymentNo'] = $order['ordersn'];
	           //$xml_data['Body']['Pay']['OrderSeqNo'] = $setting['payment']['wechat']['mchid']; //微信商户号
	           $xml_data['Body']['Pay']['PaymentNo'] = $order['paymentno'];
	           $xml_data['Body']['Pay']['OrderSeqNo']= $order['paymentno'];
	       }
	       $xml_data['Body']['Pay']['Source'] = $this->get_source($order['paytype']); //支付方式代码
	       $xml_data['Body']['Pay']['Idnum'] = $order['imid']; //身份证
	       $xml_data['Body']['Pay']['Name'] = $order['realname'];  //真实姓名
	       $xml_data['Body']['Pay']['MerId'] = ''; //银联在线商户号
	       $xml_data['Body']['Logistics']['LogisticsNo'] = ''; //运单号
	       $xml_data['Body']['Logistics']['LogisticsName'] = $dispatch_data['expressname']; //快递公司名称
	       $xml_data['Body']['Logistics']['Consignee'] = $address['realname']; //收货人名称
	       $xml_data['Body']['Logistics']['Province'] = $address['province']; 
	       $xml_data['Body']['Logistics']['City'] = $address['city']; 
	       $xml_data['Body']['Logistics']['District'] = $address['area']; 
	       $xml_data['Body']['Logistics']['ConsigneeAddr'] = $this->replace_specialChar($address['province'].$address['city'].$address['area'].$address['address']);
	       $xml_data['Body']['Logistics']['ConsigneeTel'] = $address['mobile'];
	       $xml_data['Body']['Logistics']['MailNo'] = ''; //邮编
	       $xml_data['Body']['Logistics']['GoodsName'] = '';
	       $xml = $this->toXml($xml_data, 'Message');

	       date_default_timezone_set("PRC");
            $posturl="
                 &timestamp=".urlencode(date('Y-m-d H:i:s'))."
                 &userid=".$this->_OrgUser."
                 &sign=".md5($this->_OrgUser.$this->_Orgkey.date('Y-m-d H:i:s'))."
                 &xmlstr=".urlencode($xml).'
                 &msgtype='.$this->_cnec_jh_order.'
                 &customs=3105';
                 ///var_dump($xml);
	       return $this->_curl_php($this->_api, $posturl);
	    }
	    /**
	     * 订单查询
	     * @param unknown $orderid
	     * @return string|Ambigous
	     */
	    function cnec_jh_decl_byorder($order){
	        if(empty($order)){
	            return;
	        }
	        
	        $xml_data['Header']['CustomsCode'] = $this->_CustomsCode;
	        $xml_data['Header']['OrgName'] = $this->_OrgName;
// 	        $xml_data['Header']['MftNo'] = '31052016I004075730';
	        $xml_data['Header']['MftNo'] = $order['mftno'];
	        $xml = $this->toXml($xml_data, 'Message');
	        $posturl="
	             &timestamp=".urlencode(date('Y-m-d H:i:s'))."
                 &userid=".$this->_OrgUser."
                 &sign=".md5($this->_OrgUser . $this->_Orgkey . date('Y-m-d H:i:s'))."
                 &xmlstr=".urlencode($xml).'
                 &msgtype='.$this->_cnec_jh_decl_byorder.'
                 &customs=3105';
	        return $this->_curl_php($this->_api, $posturl);
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

	    	
	        return $this->_curl_php($this->_api, $posturl);
	    }
	    
	    /**
	     * 退货
	     */
	    function cnec_jh_rejdec($id){
	        $orderfund=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order_refund")." where id=:id",array(":id"=>$id));
	        $order=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:orderid",array(":orderid"=>$orderfund['orderid']));
	        $time = date('Y-m-d H:i:s');
	        $xml_data['Header']['CustomsCode'] = $this->_CustomsCode;
	        $xml_data['Header']['CreateTime'] = $time;
	        $xml_data['Body']['RejectedInfo']['MftNo']= $order['mftno'];
	        $xml_data['Body']['RejectedInfo']['WaybillNo']=$orderfund['expresssn'];
	        $xml_data['Body']['RejectedInfo']['Flag']="00";
	        $goods=unserialize($orderfund['goodssn']);
	        foreach($goods as $g){
	        	$array[]=array("Detail"=>array("ProductId"=>$g['goodssn'],"RejectedQty"=>$g['goodsnumber']));
	        }
	        var_dump($orderfund);
	        $xml_data['Body']['RejectedInfo']['RejectedGoods']=$array;
	        $xml = $this->toXml($xml_data, 'Message');
	        
	        $posturl="timestamp=".urlencode($time)."&userid=".$this->_OrgUser."&sign=".md5($this->_OrgUser.$this->_Orgkey.$time)."&xmlstr=".urlencode($xml).'&msgtype='.$this->cnec_jh_rejdec.'&customs=3105';
	        return $this->_curl_php($this->_api, $posturl);
	    }
	    function cnec_jh_rejser($id){
	    	$orderfund=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order_refund")." where id=:id",array(":id"=>$id));
	        $order=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_order")." WHERE id=:orderid",array(":orderid"=>$orderfund['orderid']));
	        $xml_data['Header']['MftNo']=$order['mftno'];
	        $xml_data['Header']['WaybillNo']=$orderfund['expresssn'];
	        $xml_data['Header']['Flag']="00";
	        $xml = $this->toXml($xml_data, 'Message');
	        $posturl="timestamp=".urlencode($time)."&userid=".$this->_OrgUser."&sign=".md5($this->_OrgUser.$this->_Orgkey.$time)."&xmlstr=".urlencode($xml).'&msgtype='.$this->cnec_jh_rejdec.'&customs=3105';
	        return $this->_curl_php($this->_api, $posturl);
	    }
	    /**
	     * 对应跨境购支付方式代码
	     * @param unknown $pay_type  （1：余额支付；11：后台付款； 21：微信支付； 22：支付宝支付； 23：银联支付； 3：货到付款）
	     * @return void|string
	     */
	    function get_source($pay_type){
	        switch ($pay_type){
	            case 22:
	                return '02';
	                break;
	            case 21:
	                return '13';
	            default:return ;
	        }
	    }
	    
	    
	}
	
	class kjxswc extends Declarecore{
	    function __construct()
	    {
	        $this->kjxswc();
	    }
	
	    function kjxswc()
	    {
	        date_default_timezone_set("PRC"); //时间格式
	        header("Content-Type: text/html; charset=utf-8");
	
	        $this->_api = 'http://60.190.243.90:8686/ecmhx/kjb/OrderWS?wsdl'; //订单申报(正式)
	        $this->_api_orderStatus = 'http://60.190.243.90:8686/ecmhx/kjb/OrderWS?wsdl'; //查询订单状态(正式)

// 	        $this->_api = 'http://60.190.243.90:8866/ecmtest/kjb/OrderWS?wsdl'; //订单申报(测试)
// 	        $this->_api_orderStatus = 'http://60.190.243.90:8866/ecmtest/kjb/OrderWS?wsdl'; //查询订单状态(测试)
	        
	        $this->_version = 'v1.0';
	        $this->_appsecret = 'ml4321';
	        $this->_companyCode = '142927434'; //发送方备案编号
	        $this->_companyName = '浙江省粮油食品进出口股份有限公司';// 企业备案名称
	        $this->_eCommerceCode = '1205877800007145';//电商企业编号
	        $this->_eCommerceName = '富春粮油食品有限公司'; //电商企业名称
	        // $this->_eCommerceCode = '6518450800008154';//电商企业编号
	        // $this->_eCommerceName = '香港融信商贸科技有限公司'; //电商企业名称
	        // $this->_eCommerceCode = '316852779';//电商企业编号
	        // $this->_eCommerceName = '香港富春粮油有限公司'; //电商企业名称
	        $this->_logisCompanyName = '百世物流科技（中国）有限公司';//物流企业名称
	        $this->_logisCompanyCode = 'WL15041401';//物流企业编码
	    }
	
	    /**
	     * 杭州保税订单申报
	     * @param 订单ID $order_id
	     * @return 申报结果 array()
	     */
	    function pushOrderDataInfo($order){
	        if (empty($order)) {
	            return $error = '没有该订单！';
	        }
// 	        print_R($order); die;
	        switch ($order['paytype']){
// 	            case 'shengpay':
// 	                $payCompanyCode = 'ZF14112001';//盛付通支付编码
// 	                break;
	            case 21:
	                $payCompanyCode = 'ZF14120401';//微信、财付通支付编码
	                break;
	            case 22:
	                $payCompanyCode = 'ZF14021901';//支付公司编码（支付宝）
	                break;
	        }
	        $address = unserialize($order['address']);
	        $userProcotol = '本人承诺所购买商品系个人合理自用，现委托商家代理申报、代缴税款等通关事宜，本人保证遵守《海关法》和国家相关法律法规，保证所提供的身份信息和收货信息真实完整，无侵犯他人权益的行为，以上委托关系系如实填写，本人愿意接受海关、检验检疫机构及其他监管部门的监管，并承担相应法律责任。';
	        $order_arr = array();
	        $order_arr['head']['businessType'] = 'IMPORTORDER';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfSign']['companyCode']= $this->_companyCode;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfSign']['businessNo'] = $order['ordersn'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfSign']['businessType'] = IMPORTORDER;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfSign']['declareType'] = 1;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfSign']['note'] = '';
	        
	        //商品节点
	        $goods_list = pdo_fetchall("SELECT g.title, g.uniacid, g.unit, g.weight, g.dispatchid, g.goodssn, g.productsn, g.tariffnum, g.originplace, o.total,g.type, o.price, o.dprice FROM " . tablename('ewei_shop_order_goods') .
	            " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid", array(':orderid' => $order['id']));
	        $i = 0;
	        $goods_count = 0;
	        foreach($goods_list as $key => $goods)
	        {
	            $i++;
	            $goods_count+= $goods['total'];
	            $jkfOrderDetailList['jkfOrderDetail']['goodsOrder'] = $i;
	            $jkfOrderDetailList['jkfOrderDetail']['itemNo'] = $goods['goodssn'];
	            $jkfOrderDetailList['jkfOrderDetail']['sourceNo'] = $goods['productsn'];
	            $jkfOrderDetailList['jkfOrderDetail']['goodsName'] = $goods['title'];
	            $jkfOrderDetailList['jkfOrderDetail']['goodsModel'] = '';
	            $jkfOrderDetailList['jkfOrderDetail']['codeTs'] = $goods['tariffnum'];
	            $jkfOrderDetailList['jkfOrderDetail']['grossWeight'] = $goods['weight'];
	            $jkfOrderDetailList['jkfOrderDetail']['unitPrice'] = $goods['price']/$goods['total'];
	            $jkfOrderDetailList['jkfOrderDetail']['goodsUnit'] = $goods['unit'];
	            $jkfOrderDetailList['jkfOrderDetail']['goodsCount'] = $goods['total'];
	            $jkfOrderDetailList['jkfOrderDetail']['originCountry'] = $goods['originplace'];
	        }

	        //订单节点
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['eCommerceCode'] = $this->_eCommerceCode;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['eCommerceName'] = $this->_eCommerceName;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['ieFlag'] = 'I';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['payType'] = '01';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['payCompanyCode'] =  $payCompanyCode;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['payNumber'] = $order['paymentno'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['orderTotalAmount'] = $order['price'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['orderNo'] = $order['ordersn'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['orderTaxAmount'] = $order['tax_consumption']+$order['tax_rate'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['orderGoodsAmount'] = $order['goodsprice'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['feeAmount'] =  $order['dpostfee'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['companyName'] = $this->_companyName;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['companyCode'] = $this->_companyCode;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['tradeTime'] = date('Y-m-d H:i:s', $order['paytime']);
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['currCode'] = '142';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['totalAmount'] = $order['goodsprice'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['consigneeEmail'] = '';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['consigneeTel'] = $address['mobile'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['consignee'] = $address['realname'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['consigneeAddress'] = $address['province'].' '.$address['city'].' '.$address['area']. $address['address'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['totalCount'] = $goods_count;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['postMode'] = '';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['senderCountry'] = '110';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['senderName'] = 'Joy';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['purchaserId'] = $order['openid'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['logisCompanyName'] = $this->_logisCompanyName;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['logisCompanyCode'] = $this->_logisCompanyCode;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['zipCode'] = '';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['note'] = '';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['wayBills'] = '';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['rate'] = '1';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['userProcotol'] = $userProcotol;
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderImportHead']['insureAmoun'] = '0';
	        //商品节点
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfOrderDetailList'] = $jkfOrderDetailList;
	        //配送节点
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['id'] =  $order['openid'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['name'] =  $order['realname'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['email'] =  '';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['telNumber'] =  $address['mobile'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['address'] =  $address['province'].' '.$address['city'].' '.$address['area']. $address['address'];
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['paperType'] =  '01';
	        $order_arr['body']['orderInfoList']['orderInfo']['jkfGoodsPurchaser']['paperNumber'] =  $order['imid'];
	        $xml = $this->toXml($order_arr, 'mo');
// 	        echo $xml; die;
	        $parameters['xml'] = $xml;
	        $parameters['sign'] = base64_encode(md5($xml.$this->_appsecret, true));
	        $parameters['version'] = $this->_version;
	        $client = new SoapClient($this->_api);
	        $return = $client->receiveOrder($parameters);
	        $returns = $this->xml_to_array($return->return);
	        $return_data = $returns['mo']['head']['body']['responses']['responseItems'];
	        return $return_data;
	         
	    }
	
	    function orderStatus($order){
	        if (empty($order)){
	            return;
	        }
	
	        $order_arr['head']['companyCode'] = $this->_companyCode;
// 	        $order_arr['body']['ordernoList']['orderno'] = 'KKDD1614486659';
	        $order_arr['body']['ordernoList']['orderno'] = $order['ordersn'];
	        $xml = $this->toXml($order_arr, 'mo');
	        $parameters['xml'] = $xml;
	        $parameters['sign'] = base64_encode(md5($xml.$this->_appsecret, true));
	        $parameters['version'] = $this->_version;
	        $client = new SoapClient($this->_api_orderStatus);
	        $return = $client->orderStatus($parameters);
	        $returns = $this->xml_to_array($return->return);
	        return $returns;
	    }

	}
	
	class Haitun extends Declarecore{
	    function __construct()
	    {
	        $this->haitunApp();
	    }
	    
	    function haitunApp()
	    {
	        date_default_timezone_set("PRC"); //时间格式
	        header("Content-Type: text/html; charset=utf-8");
	        include_once('/sdk/haitunSDK.php');
	        require_once(EWEI_SHOPV2_TAX_CORE . 'sdk/haitunSDK.php');
	        
	        $this->_appsecret = '27E6C447-B14C-4FDB-B046-B735F20F6B57';
	        $this->_code = array(
	            0=> array("Code" => 0, "Desc" => "True"),								//签名正确
	            1=> array("Code" => 1, "Desc" => "Sign error"), 						//签名错误（超时、格式有误）
	            2=> array("Code" => 2, "Desc" => "There is no such goods"), //产品Id为空
	            3=> array("Code" => 3, "Desc" => "There is no such orders"), //订单Id为空
	            4=> array("Code" => 4, "Desc" => "Not find this order"), 		//该订单没找到
	            5=> array("Code" => 5, "Desc" => "Parameter is missing"), 	//查询条件不足
	            6=> array("Code" => 6, "Desc", "The request timeout"), 			//请求超时
	        );
	    }
	    
	    function index(){
	        if( empty($_GET['type']) ) {
	            $data = openSdk::api('test', array(
	                'sku' => 'DEAP001'
	            ));
	            print_r($data);
	            // 			print_r(openSdk::$lastData);
	        } else {
	            if( openSdk::check() ) {
	                if( $_GET['type'] === 'token' ) {
	                    $data = openSdk::token();
	                }
	            } else {
	                $data = array(
	                    'status' => '40002',
	                    'data'   => array(
	                        'msg' => 'validation failed'
	                    )
	                );
	            }
	            echo openSdk::crypt($data);
	        }
	    
	    }
	    
	    /**
	     * 订单提交
	     */
	    function pushOrderDataInfo($order)
	    {
	        global $_W;
	        $openid = $_W['openid'];
	        $uniacid = $_W['uniacid'];
	        if (empty($order)) {
	            return $error = '没有该订单！';
	        }
	         
	        $goods_list = pdo_fetchall("SELECT g.title, g.uniacid, g.unit, g.weight, g.dispatchid, o.goodssn as option_goodssn, o.productsn as option_productsn,o.total,g.type, o.dprice FROM " . tablename('ewei_shop_order_goods') .
	            " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid", array(':orderid' => $order['id']));
	    
	        $items = array();
	        $i = 0;
	        foreach($goods_list as $rec_id => $goods)
	        {
// 	            $items[$i]['quantity'] = $goods['total'];
	            $items[$i]['goodsName'] = $this->replace_specialChar($goods['title']);
	            $items[$i]['goodsSn'] = $goods['goodssn'];
	            $items[$i]['goodsPrice'] = $goods['dprice'];
	            $i++;
	        
	            //使用快递模板
	            if(empty($dispatch_data)){
	                if (empty($goods['dispatchid'])) {
	                    $dispatch_data = m('dispatch')->getDefaultDispatch($goods['uniacid']);
	                } else {
	                    $dispatch_data = m('dispatch')->getOneDispatch($goods['dispatchid'], $goods['uniacid']);
	                }
	            }
	        }
	        
	        $address = unserialize($order['address']);
	        
	        switch ($order['paytype']){
	            case 21:
	                $payment_name = '微信';
	                break;
	            case 22:
	                $payment_name = '支付宝';
	                break;
	        }
	        
	        $post_data = array('data'=> array(
	            array(
	                'consignee' => $address['realname'],
	                'country' => '中国',
	                'province' => $address['province'],
	                'city' => $address['city'],
	                'district' => $address['area'],
	                'address' =>$this->replace_specialChar($address['province'].$address['city'].$address['area'].$address['address']),
	                'zipcode' =>'',
	                'tel' => '',
	                'mobile' =>$address['phone_mob'],
	                'orderSn' => $order['ordersn'],
	                'idCardNumber' =>$order['imid'],
	                'siteType' => '其它',
	                'siteName' => '浙江粮油',
	                'siteUrl' => '地址',
	                'consumerNote' =>'',
	                'moneyPaid' =>$order['price'],
	                'paymentInfoMethod' =>$payment_name,
	                'paymentInfoNumber' =>$order['paymentno'],
	                'paymentAccount' => 'cnbuyers@zjcof.com',
	                'isCheck' => 'no',
	                'items' => $items
	            )
	        ));
	         
	        $data = openSdk::api('pushOrderDataInfo', $post_data);
	        return $data;
	    }
	    
	    /**
	     * 获取订单状态
	     */
	    function getOrderStatus($orderSn){
	        if (!$orderSn){
	            return;
	        }
	        
	        $data = openSdk::api('getOrderStatus', array('orderSn' => $orderSn));
	        return $data;
	    }
	    
	    /**
	     * 获取产品库存
	     */
	    function getStocks(){
	        $sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
	        $sku = 'AUBL002';
	        if (!$sku){
	            $this->show_warning('Error','','index.php?app=seller_order&type=declare_orders');
	            return;
	        }
	    
	        include_once(ROOT_PATH . '/includes/open/haitunSDK.php');
	        $data = openSdk::api('getStocks', array('sku' => $sku));
	        print_R($data);die;
	    }
	    
	    /**
	     *    获取有效的订单信息
	     *
	     *    @author    Garbin
	     *    @param     array $status
	     *    @param     string $ext
	     *    @return    array
	     */
	    function _get_valid_order_info($status, $ext = '')
	    {
	        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
	        if (!$order_id)
	        {
	    
	            return array();
	        }
	        if (!is_array($status))
	        {
	            $status = array($status);
	        }
	    
	        if ($ext)
	        {
	            $ext = ' AND ' . $ext;
	        }
	    
	        $model_order    =&  m('order');
	        /* 只有已发货的货到付款订单可以收货 */
	        $order_info     = $model_order->get(array(
	            'conditions'    => "order_alias.order_id={$order_id} AND seller_id=" . $this->visitor->get('manage_store') . " AND status " . db_create_in($status) . $ext,
	            'join'          => 'has_orderextm',
	        ));
	    
	        if (empty($order_info))
	        {
	    
	            return array();
	        }
	    
	        return array($order_id, $order_info);
	    }
	}

?>