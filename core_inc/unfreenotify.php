<?php
define('IN_MOBILE', true);

require dirname(__FILE__).'/../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/dispage.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';
$input = file_get_contents('php://input');
if (!empty($input)) {
    $data = json_decode($input, true);
    $post = $data;
}else{
    $post = $_POST;
}
WeUtility::logging('params', $post);
class unfreenotify
{
    public $post;
    public $success;
    public function __construct($post)
    {
        global $_W;
        $this->post = $post;
        $this->init();
    }


    public function init(){
        $success['code']="SUCCESS";
        $success['message']="成功";
        $error=array(
            'code'=>'fail',
            'message'=>'系统异常',
        );
        $post=$this->post;
        if(!empty($post)){
            $resource=$post['resource'];

            $ciphertext=$resource['ciphertext'];
            $nonce=$resource['nonce'];
            $associated_data=$resource['associated_data'];
            $receiverdata=m("wxpayv3")->aesdecryptToString($associated_data,$nonce,$ciphertext);
            WeUtility::logging('params', $receiverdata);


            $out_order_no=$receiverdata['out_order_no'];
            $unfreeze_order=pdo_fetch("select * from ".tablename("ewei_shop_unfreeze_order").'where out_order_no=:out_order_no',array(":out_order_no"=>$out_order_no));
            if(!empty($unfreeze_order)){
                pdo_update('ewei_shop_unfreeze_order',array("unfreeze_status"=>2),array("id"=>$unfreeze_order['id']));
                exit(json_encode($success,320));
            }else{
                $error['message']="分账单号异常";
            }
        }
        WeUtility::logging('test', "ssssssssss");
        exit(json_encode($error,320));
    }
}

new unfreenotify($get);