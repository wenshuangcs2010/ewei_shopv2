<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */



require dirname(__FILE__).'/../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';
require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");
define("CNBUYERS_GOODS_APP_ID",     "zIPXb3SB");
define("CNBUYERS_GOODS_APP_SECRET",     "be5845fa6881baf7");

class realtimedataupload
{
    public $post;
    public function __construct()
    {
        global $_W;
        $this->post = $_POST;
        SendOmsapi::init(CNBUYERS_GOODS_APP_ID,CNBUYERS_GOODS_APP_SECRET);
        $this->init();
    }


    public function init(){
        global $_W;
        $openReq=$this->post['openReq'];
        if(empty($openReq)){
            $json['code']=0;
            $json['message']="post params error";
            die(json_encode($json)) ;
        }
        $openReq=json_decode($this->post['openReq'],true);
        $json=array("code"=>0,'message'=>'');
        $sessionid=$openReq['sessionID'];
        $ordersn=$openReq['orderNo'];
        load()->func('communication');
        $url="http://oms.cnbuyers.cn/api/kjb2c";
        $order_info=pdo_fetch("select pay_type from".tablename("ewei_shop_pay_request")." where order_sn=:ordersn and status=1",array(":ordersn"=>$ordersn));
        if(empty($order_info)){
            $json['code']=0;
            $json['message']="order error";
            die(json_encode($json)) ;
        }
        $data=m('realtimedataupload')->init($sessionid,$ordersn,$order_info['pay_type']);
        $str=m('realtimedataupload')->str_split($data);
        $token=SendOmsapi::getToken();
        $postdata['access_token']=$token;
        $postdata['postdata']=urlencode(base64_encode($str));
        $header=array('Content-Type'=>'application/x-www-form-urlencoded');
        $resp = ihttp_request($url, $postdata,$header);
        $content=json_decode($resp['content'],true);
        if($content['error']==0){
           $certNo=$content['data']['certNo'];
           $signValue=$content['data']['signValue'];
           $signValue=urldecode($signValue);
            m('realtimedataupload')->SetParams("certNo",$certNo);
            //$signValue="BWKIkSIajoGuM8kn95hYz7WdlqWCcDEZoRpoRvwHrRk7hB48XfGEI83N7rT8JMdZYV/g8xokFlfbB/kneqc3jUARu49QNeP89ashAlbjCSClONNac+jcle10p81QXwVymvKpd6no8bLplPJhGWtSMT0rtfnzvJjs6sg4VP6DLjE=";
            m('realtimedataupload')->SetParams("signValue",$signValue);
            $data=m('realtimedataupload')->get_params();
            $testurl=m("realtimedataupload")->realTimeDataUpload_url;
            $realpost['payExInfoStr']=json_encode($data,320);

            $header=array('Content-Type'=>'application/x-www-form-urlencoded');
            $resp = ihttp_request($testurl, $realpost,$header);
            $content=json_decode($resp['content'],true);
            die(json_encode($content));
        }else{
            $json['code']=0;
            $json['message']="signValue error";
            die(json_encode($json)) ;
        }
    }
}

new  realtimedataupload();