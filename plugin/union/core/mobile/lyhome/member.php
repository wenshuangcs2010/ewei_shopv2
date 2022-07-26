<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Member_EweiShopV2Page extends LyMobilePage
{
    function main(){
        global $_W;
        global $_GPC;

        $member=m("member")->getMember($_W['openid']);

        if($member['mobile']=="" || empty($member['mobileverify'])){
           $url= mobileUrl("union.lyhome.member.bind");
            header("Location:{$url}");
            exit();
        }

        include $this->template();
    }


    function bind(){
        global $_W;
        global $_GPC;
        $url= mobileUrl("union.lyhome.member");
        $backurl = $_W['siteroot'].'app/index.php?'.base64_decode(urldecode($url));
        $member=m("member")->getMember($_W['openid']);
        if($_W['ispost']){
            $mobile = trim($_GPC['mobile']);
            $verifycode = trim($_GPC['verifycode']);

            if(empty($mobile)){
                show_json(0, '请输入正确的手机号');
            }
            if(empty($verifycode)){
                show_json(0, '请输入验证码');
            }
            $key = '__ewei_shopv2_member_verifycodesession_'.$_W['uniacid'].'_'.$mobile;
            if( !isset($_SESSION[$key]) ||  $_SESSION[$key]!==$verifycode || !isset($_SESSION['verifycodesendtime']) || $_SESSION['verifycodesendtime']+600<time()){
                show_json(0, '验证码错误或已过期!');
            }
            pdo_update("ewei_shop_member",array('mobile'=>$mobile,'mobileverify'=>1),array("id"=>$member['id']));

            $orderlist=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_order")." where mobile=:mobile",array("mobile"=>$mobile));

            foreach ($orderlist as $o){
                pdo_update("ewei_shop_union_ly_order",array("openid"=>$member['openid']),array("id"=>$o['id']));
            }
            show_json(1,'ok');

        }
        $sendtime = $_SESSION['verifycodesendtime'];
        if(empty($sendtime) || $sendtime+60<time()){
            $endtime = 0;
        }else{
            $endtime = 60 - (time() - $sendtime);
        }
        include $this->template();
    }
}