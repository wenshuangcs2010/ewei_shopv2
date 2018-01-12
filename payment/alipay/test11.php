
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

			$order=pdo_fetch("SELECT * from ".tablename("ewei_shop_order")." where ordersn=:ordersn", array(":ordersn"=>"SHCG120111470861"));
			$ret=m('notice')->sendOrderMessage($order['id']);
			var_dump($ret);
			echo "SUCCESS";
    }
}
new Test();