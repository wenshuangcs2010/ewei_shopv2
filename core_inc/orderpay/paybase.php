<?php

class paybase{
	/* 外部处理网关 */
    var $_gateway   = '';
    /* 支付方式唯一标识 */
    var $_code      = '';

    public static function getPayment($paycode,$config){
      require $paycode.'/'.$paycode.'_payment.php';
      $classname=ucfirst($paycode)."_payment";
      return new $classname($config);
    }

    function getClientIP()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return $ip;
    }

    public function updateorder($ordersn,$partner_trade_no=""){
        echo "11";
        $orderinfo=pdo_fetch("select * from ".tablename("ewei_shop_order_dispay")." where order_sn=:ordersn",array(":ordersn"=>$ordersn));
        if(!empty($orderinfo)){
             $data=array(
                'status'=>2,
                'paymentno'=>$partner_trade_no,
                'pay_time'=>time(),
            );
            pdo_update("ewei_shop_order_dispay",$data,array("id"=>$orderinfo['id']));
            pdo_update($orderinfo['order_table'],array("paystatus"=>2),array("id"=>$orderinfo['order_id']));
        }
    }
}