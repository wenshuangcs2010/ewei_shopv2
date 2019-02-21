<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */

error_reporting(1);
require dirname(__FILE__).'/../../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/dispage.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';
class Test
{
    public $post;

    public function __construct()
    {
        global $_W;
        $this->post = $_POST;
        if($this->post['type']=="shipping"){

            global $_W;
            $this->post = $_POST;
            if($this->post['type']=="shipping"){


                foreach ($this->post['data'] as $key => $value) {
                    $order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where ordersn=:ordersn", array(":ordersn"=>$value['ordersn']));
                    //WeUtility::logging('post_test', var_export($order,true));
                    if($order['status']!=1){
                        continue;
                    }
                    $data = array();
                    $time=time();
                    $data = array('sendtype' =>0, 'express' => trim( $value['logisticsCode']), 'expresscom' => trim($value['logisticsName']), 'expresssn' => trim($value['logisticsNo']), 'sendtime' => $time);
                    if($value['sendtype']==1){
                        $ogoods = pdo_fetchall('select sendtype from ' . tablename('ewei_shop_order_goods') . ' where orderid = ' .$order['id'] . ' order by sendtype desc ');
                        $senddata = array('sendtype' => $ogoods[0]['sendtype'] + 1, 'sendtime' => $time);
                        $data['sendtype'] = $ogoods[0]['sendtype'] + 1;

                        foreach ($value['sendgoods'] as $only_sku){
                            pdo_update('ewei_shop_order_goods', $data, array('goodssn' => $only_sku['only_sku'], 'orderid' => $order['id']));
                        }
                        $send_goods = pdo_fetch('select * from ' . tablename('ewei_shop_order_goods')  . ' where orderid = ' . $order['id'] . ' and sendtype = 0  limit 1 ');
                        if (empty($send_goods))
                        {
                            $senddata['status'] = 2;
                        }
                        pdo_update('ewei_shop_order', $senddata, array('id' => $order['id']));
                    }else{
                        $data['status'] = 2;

                        pdo_update('ewei_shop_order', $data, array('id' => $order['id']));
                    }
                    plog('order.op.send', "订单发货 ID: {$order['id']} 订单号: {$order['ordersn']} <br/>快递公司: {$value['logisticsName']} 快递单号: {$value['logisticsNo']}");
                }
            }
        }
       	echo "SUCCESS";
    }
}
new Test();