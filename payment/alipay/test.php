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
   
        	foreach ($this->post['data'] as $key => $value) {
        	   $order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where ordersn=:ordersn", array(":ordersn"=>$value['ordersn']));
                if($order['status']!=1){
                    continue;
                }
        		$data = array();
            	$data['status'] = 2;
            	$data['express'] = $value['logisticsCode'];
            	$data['expresscom'] = $value['logisticsName'];
            	$data['expresssn'] = $value['logisticsNo'];
                $data['mftno']=$value['mftno'];
            	$data['sendtime'] = time();
            	pdo_update('ewei_shop_order', $data, array('id' => $order['id']));
            	m('notice')->sendOrderMessage($order['id']);
            	plog('order.op.send', "订单发货 ID: {$order['id']} 订单号: {$order['ordersn']} <br/>快递公司: {$shipinfo['shipping_name']} 快递单号: {$shipinfo['invoice_no']}");
        	}
        }
       	echo "SUCCESS";
    }
}
new Test();