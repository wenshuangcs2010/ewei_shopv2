<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class pclogin_EweiShopV2Page extends LyMobilePage
{
    function main(){
        global $_W,$_GPC;

        if(empty($_W['openid'])){
            $this->message("抱歉请通过微信浏览器访问");
        }

        if (!function_exists('redis') || is_error(redis())) {
            $this->message("网络异常！请重试或者联系管理员");
        }

        $sessionid=$_GPC['sessionid'];
        if(empty($sessionid)){
            $this->message("参数异常");
        }
        if(is_weixin()){
            $url = mobileUrl('union/pclogin',array('sessionid'=>$sessionid),true);
            $account_api =m("common")->getAccount();
            $jssdkconfig = $account_api->getJssdkConfig($url);
        }


        $redis=redis();
        $rediskey="ecc_csrf_token_".$sessionid;
        $csrf_token= $redis->get($rediskey);
        if($csrf_token){
            $scanrediskey='ecc_scan_status_'.$sessionid;
            $lasttime= $redis->ttl($rediskey);
            $redis->set($scanrediskey,true);
            $redis->expire($scanrediskey,$lasttime);
        }else{
            $this->message("二维码已经过期！请重新刷新二维码");
        }
        include $this->template();
    }
    function login(){
        global $_W,$_GPC;
        if (!function_exists('redis') || is_error(redis())) {
            $this->message("网络异常！请重试或者联系管理员");
        }
        $openid=$_W['openid'];


        $member=$this->model->get_member($openid);

        if(empty($member)){
            show_json(2);
        }

        $sessionid=$_GPC['sessionid'];
        $redis=redis();
        $rediskey="ecc_csrf_token_".$sessionid;
        $csrf_token= $redis->get($rediskey);
        if($csrf_token){
            $scanrediskey='ecc_log_status_'.$sessionid;
            $lasttime= $redis->ttl($rediskey);
            $redis->set($scanrediskey,$_W['openid']);
            $redis->expire($scanrediskey,$lasttime);
            show_json(1);
        }
        show_json(0,"登录二维码已经失效");
    }
}