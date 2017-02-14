<?php

class Shengpay_payment extends paybase{
    private $payHost;
    private $debug=false;
    private $key='';
    var $_gateway="https://mas.shengpay.com/web-acquire-channel/cashier.htm";
    private $params=array(
        'Name'=>'B2CPayment',
        'Version'=>'V4.1.1.1.1',
        'Charset'=>'UTF-8',
        'MsgSender'=>'',
        'SendTime'=>'',
        'OrderNo'=>'',
        'OrderAmount'=>'',
        'OrderTime'=>'',
        'PayType'=>'',
        'InstCode'=>'',
        'PageUrl'=>'',
        'NotifyUrl'=>'',
        'ProductName'=>'',
        'BuyerContact'=>'',
        'BuyerIp'=>"",
        'Ext1'=>'',
        'Ext2'=>'',
        'SignType'=>'MD5',
        'SignMsg'=>'',
    );
    var $_config=array();
    function __construct($config){
        $this->_config = $config;
    }
    function Shengpay_payment($config) {
        $this->__construct($config);
    }
    function init($para_temp){
        $this->params['MsgSender']  = $this->_config['shengpay_account'];
        $this->params['SendTime']   = "20140108144133";
        $this->params['OrderNo']    = $para_temp['out_trade_no'];
//         $this->params['OrderNo'] = $order_info['order_sn'];
        $this->params['OrderAmount']= floatval($para_temp['total_fee']); //支付款项
//        $this->params['OrderAmount'] = 0.01;
        $this->params['OrderTime']  = date('YmdHis');
        $this->params['PayType'] = '';
        $this->params['InstCode']   = '';
        $this->params['PageUrl']    = "http://".$_SERVER['HTTP_HOST']."/addons/ewei_shopv2/return_url.php";
        $this->params['NotifyUrl']  = "http://".$_SERVER['HTTP_HOST']."/addons/ewei_shopv2/shengpaynotify_url.php";
        $this->params['ProductName']= $para_temp['subject'];

    }
    function buildRequestForm($para_temp) {
        $buyer_ip = $this->getClientIP();
        if($buyer_ip){
            $this->params['BuyerIp']    = $buyer_ip;
        }else{
            $this->params['BuyerIp']    = '127.0.0.1';
        }
        $this->init($para_temp);
        $sign=$this->getSign();
        
        $this->params['SignMsg']=$sign;
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->_gateway."' method='POST'>";
        while (list ($key, $val) = each ($this->params)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='提交' style='display:none;'></form>";
        
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        
        echo $sHtml;
    }
    function getSign(){
        $params=array(
            'Name'=>$this->params['Name'],
            'Version'=>$this->params['Version'],
            'Charset'=>$this->params['Charset'],
            'MsgSender'=>$this->params['MsgSender'],
            'SendTime'=>$this->params['SendTime'],
            'OrderNo'=>$this->params['OrderNo'],
            'OrderAmount'=>$this->params['OrderAmount'],
            'OrderTime'=>$this->params['OrderTime'],
            'Currency'=>$this->params['Currency'],
            'PayType'=>$this->params['PayType'],

            'PayChannel'=>$this->params['PayChannel'],
            'InstCode'=>$this->params['InstCode'],
            'PageUrl'=>$this->params['PageUrl'],
            'BackUrl'=>$this->params['BackUrl'],
            'NotifyUrl'=>$this->params['NotifyUrl'],
            'ProductName'=>$this->params['ProductName'],
            'BuyerContact'=>$this->params['BuyerContact'],
            'BuyerIp'=>$this->params['BuyerIp'],
            'realName'=>$this->params['realName'],
            'idNo'=>$this->params['idNo'],
            'mobile'=>$this->params['mobile'],
            'Ext1'=>$this->params['Ext1'],
            'SignType'=>"MD5",
        );
        foreach($params as $key=>$value){
            if(!empty($value))
                $origin.=$value."|";
            }
        $SignMsg=strtoupper(md5($origin.$this->_config['shengpay_key']));
        return $SignMsg;
    }

}
?>