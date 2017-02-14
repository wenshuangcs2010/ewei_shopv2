<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends MobilePage
{

    protected function getWapSet(){
        global $_W,$_GPC;
        $set = m('common')->getSysset(array('shop','wap'));
        $set['wap']['color']   = empty($set['wap']['color'])?'#fff':$set['wap']['color'];

        $params = array();
        if(!empty($_GPC['mid'])){
            $params['mid'] = $_GPC['mid'];
        }
        if(!empty($_GPC['backurl'])){
            $params['backurl'] = $_GPC['backurl'];
        }
        $set['wap']['loginurl'] = mobileUrl('account/login',$params);
        $set['wap']['regurl'] = mobileUrl('account/register',$params);
        $set['wap']['forgeturl'] = mobileUrl('account/forget',$params);
        return $set;
    }

    //用户登录
    public function login(){
        global $_W, $_GPC;

        if(is_weixin() || !empty($_GPC['__ewei_shopv2_member_session_'.$_W['uniacid']])){
            header('location: '.mobileUrl());
        }
        if($_W['ispost']){
            $mobile = trim($_GPC['mobile']);
            $pwd = trim($_GPC['pwd']);

            $member =pdo_fetch('select id,openid,mobile,pwd,salt from '.tablename('ewei_shop_member').' where mobile=:mobile and mobileverify=1 and uniacid=:uniacid limit 1',array(':mobile'=>$mobile,':uniacid'=>$_W['uniacid']));

            if(empty($member)){
                show_json(0, '用户不存在');
            }
            if(md5($pwd.$member['salt'])!==$member['pwd']){
                show_json(0, '用户或密码错误');
            }
            m('account')->setLogin($member);
            show_json(1, '登录成功');
        }
        $set = $this->getWapSet();
        $backurl = "";
        if(!empty($_GPC['backurl'])){
            $backurl = $_W['siteroot'].'app/index.php?'.base64_decode(urldecode($_GPC['backurl']));
        }

        $wapset = $_W['shopset']['wap'];
        $sns = $wapset['sns'];

        include  $this->template('login', null, true);
    }

    //注册
    public function register(){
        $this->rf(0);
    }

    //忘记密码
    public function forget(){
        $this->rf(1);
    }

    //Rf
    protected function rf($type){
        global $_W, $_GPC;

        if(is_weixin() || !empty($_GPC['__ewei_shopv2_member_session_'.$_W['uniacid']])){
            header('location: '.mobileUrl());
        }

        if($_W['ispost']){
            //  获取数据并验证
            $mobile = trim($_GPC['mobile']);
            $verifycode = trim($_GPC['verifycode']);
            $pwd = trim($_GPC['pwd']);
            if(empty($mobile)){
                show_json(0, '请输入正确的手机号');
            }
            if(empty($verifycode)){
                show_json(0, '请输入验证码');
            }
            if(empty($pwd)){
                show_json(0, '请输入密码');
            }
            //  判断 验证码
            $key = '__ewei_shopv2_member_verifycodesession_'.$_W['uniacid'].'_'.$mobile;
            if( !isset($_SESSION[$key]) ||  $_SESSION[$key]!==$verifycode || !isset($_SESSION['verifycodesendtime']) || $_SESSION['verifycodesendtime']+600<time()){
                show_json(0, '验证码错误或已过期!');
            }
            $member =pdo_fetch('select id,openid,mobile,pwd,salt from '.tablename('ewei_shop_member').' where mobile=:mobile and mobileverify=1 and uniacid=:uniacid limit 1',array(':mobile'=>$mobile,':uniacid'=>$_W['uniacid']));

            if(empty($type)){
                //  注册
                if(!empty($member)){
                    show_json(0, '此手机号已注册, 请直接登录');
                }
                //  获取/设置salt
                $salt =empty($member)?'':$member['salt'];
                if(empty($salt)){
                    $salt = m('account')->getSalt();
                }
                $openid =empty($member)?'':$member['openid'];
                $nickname  = empty($member)?'':$member['nickname'];
                if(empty($openid)){
                    $openid = 'wap_user_'.$_W['uniacid'].'_'.$mobile;
                    $nickname = substr($mobile,0,3).'xxxx'.substr($mobile,7,4);
                }
                $data =array(
                    'uniacid'=>$_W['uniacid'],
                    'mobile'=>$mobile,
                    'nickname'=>$nickname,
                    'openid'=>$openid,
                    'pwd'=>md5($pwd.$salt),
                    'salt'=>$salt,
                    'createtime'=>time(),
                    'mobileverify'=>1,
                    'comefrom'=>'mobile'
                );
            }else{
                //  找回密码
                if(empty($member)){
                    show_json(0, '此手机号未注册');
                }
                $salt = m('account')->getSalt();
                $data = array('salt'=>$salt,'pwd'=>md5($pwd.$salt));
            }

            // 处理写入数据库
            if(empty($member)){
                pdo_insert('ewei_shop_member', $data);
            } else{
                pdo_update('ewei_shop_member', $data, array('id'=>$member['id']));
            }
            //分销关系
            if(p('commission')){
                p('commission')->checkAgent($openid);
            }

            unset($_SESSION[$key]);

            show_json(1, empty($type) ? "注册成功" : "密码重置成功");
        }

        $sendtime = $_SESSION['verifycodesendtime'];
        if(empty($sendtime) || $sendtime+60<time()){
            $endtime = 0;
        }else{
            $endtime = 60 - (time() - $sendtime);
        }

        $set = $this->getWapSet();

        include  $this->template('rf', null, true);
    }

    //登出
    public function logout(){
        global $_W, $_GPC;
        $key = '__ewei_shopv2_member_session_'.$_W['uniacid'];
        isetcookie($key, false, -100);
        header('location: '.mobileUrl());
        exit;
    }

    public function sns(){
        global $_W, $_GPC;

        if(is_weixin() || !empty($_GPC['__ewei_shopv2_member_session_'.$_W['uniacid']])){
            header('location: '.mobileUrl());
        }

        $sns = trim($_GPC['sns']);
        if($_W['ispost'] && !empty($sns) && !empty($_GPC['openid'])){
            m('member')->checkMemberSNS($sns);
        }

        if ($_GET['openid']) {
            if ($sns == 'qq') {
                $_GET['openid'] = 'sns_qq_' . $_GET['openid'];
            }
            if ($sns == 'wx') {
                $_GET['openid'] = 'sns_wx_' . $_GET['openid'];
            }
            m('account')->setLogin($_GET['openid']);
        }

        $backurl = "";
        if (!empty($_GPC['backurl'])) {
            $backurl = $_W['siteroot'] . 'app/index.php?' . base64_decode(urldecode($_GPC['backurl']));
        }
        $backurl = empty($backurl) ? mobileUrl(null, null, true) : trim($backurl);

        header('location: ' . $backurl);
    }

    public function verifycode(){
        global $_W,$_GPC;

        $set = $this->getWapSet();

        $mobile = trim($_GPC['mobile']);
        $temp = trim($_GPC['temp']);
        $imgcode = trim($_GPC['imgcode']);

        if(empty($mobile)){
            show_json(0, '请输入手机号');
        }
        if(empty($temp)){
            show_json(0, '参数错误');
        }

        if(!empty($set['wap']['smsimgcode'])){
            if(empty($imgcode)){
                show_json(0, '请输入图形验证码');
            }
            $imgcodehash = md5(strtolower($imgcode) . $_W['config']['setting']['authkey']);
            if($imgcodehash!=trim($_GPC['__code'])){
                show_json(-1, '请输入正确的图形验证码');
            }
        }

        $sms_id = $set['wap'][$temp];
        if(empty($sms_id)){
            show_json(0, '短信发送失败(NOSMSID)');
        }

        $key = '__ewei_shopv2_member_verifycodesession_'.$_W['uniacid'].'_'.$mobile;
        @session_start();
        $code = random(5,true);
        $shopname = $_W['shopset']['shop']['name'];
        $ret = com('sms')->send($mobile, $sms_id, array('验证码'=>$code,'商城名称'=>!empty($shopname) ? $shopname : "商城名称"));
        if($ret['status']){
            $_SESSION[$key] = $code;
            $_SESSION['verifycodesendtime'] = time();
            show_json(1, "短信发送成功");
        }
        show_json(0, $ret['message']);
    }

}
