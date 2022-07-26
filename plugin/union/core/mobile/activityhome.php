<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Activityhome_EweiShopV2Page extends LyMobilePage
{
     function main(){
         global $_W, $_GPC;
         $set = m('common')->getSysset(array('shop','wap'));
         $openid=$_W['openid'];
         //如果你把下面这个if注释了,你就倒霉了

         if (empty($openid) && !EWEI_SHOPV2_DEBUG) {
             $diemsg = is_h5app() ? "APP正在维护, 请到公众号中访问" : "请在微信客户端打开链接";
             die("<!DOCTYPE html>
                <html>
                    <head>
                        <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                        <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                    </head>
                    <body>
                    <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>".$diemsg."</h4></div></div></div>
                    </body>
                </html>");
         }
         /*
         $member=$this->model->get_member($openid);
         if(empty($member) || $member['union_id']==$_W['defaultunionid']){//不能是默认工会的用户
             include $this->template();
         }else{
             $url=mobileUrl("union/quiz",array(),true);
             Header("HTTP/1.1 303 See Other");

             Header("Location: $url");

             exit;
         }
        */
         include $this->template();
     }
    
        function reg(){
            global $_W,$_GPC;
            $_W['defaultunionid']=11;
            $union_id=$_GPC['unionid'];
            $mobile=$_GPC['mobile'];
            $realname=trim($_GPC['realname']);
            $verifycode = trim($_GPC['verifycode']);
            $openid=$_W['openid'];
            $unionname=trim($_GPC['unionname']);
            $sql="select * from ".tablename("ewei_shop_union_user").' where title = :title and deleted=0 and status=1 and uniacid=:uniacid limit 0,1';
            $union_info=pdo_fetch($sql,array(":title"=>$unionname,':uniacid'=>$_W['uniacid']));
            if(empty($union_info)){
                show_json(3,"error");
            }
            $key = '__ewei_shopv2_member_verifycodesession_'.$_W['uniacid'].'_'.$mobile;
            if( !isset($_SESSION[$key]) ||  $_SESSION[$key]!==$verifycode || !isset($_SESSION['verifycodesendtime']) || $_SESSION['verifycodesendtime']+600<time()){
                show_json(0, '验证码错误或已过期!');
            }

            //检查注册的手机号 在当前工会是否注册
            $sql="select * from ".tablename("ewei_shop_union_members")." where union_id=:unionid and mobile_phone=:mobile";
            $items=pdo_fetch($sql,array(":unionid"=>$union_id,'mobile'=>$mobile));
            //如果用户有注册
            if($items){
                pdo_update("ewei_shop_union_members",array('openid'=>$openid,'status'=>1),array("id"=>$items['id']));
            }

            $sql="select * from ".tablename("ewei_shop_union_members")." where union_id=:unionid and openid=:openid ";
            $unionmemberinfo=pdo_fetch($sql,array(":unionid"=>$union_id,":openid"=>$openid));//对应注册的账号有没有会员
            if($unionmemberinfo){//如果用户有注册这个
                pdo_update("ewei_shop_union_members",array("is_default"=>0),array("openid"=>$openid));// 其他的变成非默认
                pdo_update("ewei_shop_union_members",array("is_default"=>1),array("id"=>$unionmemberinfo['id']));
                show_json(1,"成功");
            }
//            $member=$this->model->get_member($openid);
//            if(!empty($member) && $member['union_id']==$_W['defaultunionid']){
//                pdo_update("ewei_shop_union_members",array("is_default"=>0,"mobile_phone"=>$mobile),array("openid"=>$openid));
//            }
            pdo_update("ewei_shop_union_members",array("is_default"=>0,"mobile_phone"=>$mobile),array("openid"=>$openid));
            $data=array(
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$union_info['id'],
                'mobile_phone'=>$mobile,
                'activate'=>1,
                'add_time'=>TIMESTAMP,
                'openid'=>$_W['openid'],
                'type'=>1,
                'entrytime'=>TIMESTAMP,
                'status'=>1,
                'is_default'=>1,
                'name'=>$realname,
            );

           pdo_insert("ewei_shop_union_members",$data);
           $member_info=m("member")->getMember($openid);

               $data=array(
                   'mobileverify'=>1,
                   'mobile'=>$mobile,
                   'realname'=>$realname,
               );
               pdo_update("ewei_shop_member",$data,array("id"=>$member_info['id']));

            show_json(1,"成功");

        }



     function getuninonlist(){
         global $_W,$_GPC;

         $keywords=trim($_GPC['keyword']);
         if($keywords){
             $keywords="%".$keywords."%";
             $sql="select * from ".tablename("ewei_shop_union_user").' where title like :title and deleted=0 and status=1 and uniacid=:uniacid limit 0,15';
             $list=pdo_fetchall($sql,array(":title"=>$keywords,':uniacid'=>$_W['uniacid']));
             foreach ($list as $key => $value) {
                 $title=mb_substr($value['title'],0,25,'utf-8');
                 $array[$key]=array(
                     'id'=>$value['id'],
                     'title'=>$title,
                 );
             }
             echo  json_encode($array);
         }
     }


}