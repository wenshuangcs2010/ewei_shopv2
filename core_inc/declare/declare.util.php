<?php
class declareUtil{
	 /**
         * 组装XML
         * @param unknown $data
         * @param string $rootNodeName
         * @param string $xml
         * @return mixed
         */
        public static function toXml($data, $rootNodeName = 'Message', $xml=null)
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
                    declareUtil::toXml($value, $rootNodeName, $node);
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
                case 23:
                    return '03';
	            default:return ;
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