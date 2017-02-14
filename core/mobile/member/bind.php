<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Bind_EweiShopV2Page extends MobileLoginPage
{

    protected $member;


    function __construct()
    {
        global $_W, $_GPC;
        parent::__construct();
    }

    /*
     * ***用户绑定逻辑
     *
     * 1. GPC到新手机号
     * 2. 查询此手机号是否存在用户
     * 2.1. 如果不存在 则直接绑定
     * 2.2. 如果已存在 得到member2 判断是否是自己
     * 2.2.1. 如果是自己 则返回
     * 2.2.2. 如果不是自己 则判断 member2 是否是微信用户
     * 2.2.2.1. 如果 member2 不是微信用户 则进行合并
     * 2.2.2.2. 如果 member2 是微信用户 则判断自己 member 是否是微信用户
     * 2.2.2.2.1. 如果自己 member 也是微信用户 则执行解绑、绑定
     * 2.2.2.2.2. 如果 自己 member 不是微信用户则合并
     *
     */

    public function main(){
        global $_W, $_GPC;

        $member = m('member')->getMember($_W['openid']);

        $bind = !empty($member['mobile']) && !empty($member['mobileverify']) ? 1 : 0;

        if($_W['ispost']){
            $mobile = trim($_GPC['mobile']);
            $verifycode = trim($_GPC['verifycode']);
            $pwd = trim($_GPC['pwd']);
            $confirm = intval($_GPC['confirm']);

            @session_start();
            $key = '__ewei_shopv2_member_verifycodesession_' . $_W['uniacid'] . '_' . $mobile;
            if( !isset($_SESSION[$key]) ||  $_SESSION[$key]!==$verifycode || !isset($_SESSION['verifycodesendtime']) || $_SESSION['verifycodesendtime']+600<time()){
                show_json(0, '验证码错误或已过期');
            }

            $member2 = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and uniacid=:uniacid and mobileverify=1 limit 1', array(':mobile' => $mobile, ':uniacid' => $_W['uniacid']));

            if(empty($member2)){
                $salt = m('account')->getSalt();
                $this->update($member['id'], array('mobile'=>$mobile, 'pwd'=>md5($pwd.$salt), 'salt'=>$salt, 'mobileverify'=>1));

                unset($_SESSION[$key]);
                m('account')->setLogin($member['id']);
                show_json(1, "bind success (0)");
            }

            // member 不等于空 判断是否是自己
            if($member['id']==$member2['id']){
                show_json(0 ,"此手机号已与当前账号绑定");
            }

            // 如果 两用户都是 微信用户
            if($this->iswxm($member) && $this->iswxm($member2)){
                if($confirm){
                    $salt = m('account')->getSalt();
                    $this->update($member['id'], array('mobile'=>$mobile, 'pwd'=>md5($pwd.$salt), 'salt'=>$salt, 'mobileverify'=>1));
                    $this->update($member2['id'], array('mobileverify'=>0));

                    unset($_SESSION[$key]);
                    m('account')->setLogin($member['id']);
                    show_json(1, "bind success (1)");
                }else{
                    show_json(-1, "<center>此手机号已与其他帐号绑定<br>如果继续将会解绑之前帐号<br>确定继续吗？</center>");
                }
            }

            // 判断 member2 不是是微信用户
            if(!$this->iswxm($member2)){
                if($confirm) {
                    // member 不是微信用户 则进行合并
                    $result = $this->merge($member2, $member);
                    if (empty($result['errno'])) {
                        show_json(0, $result['message']);
                    }

                    $salt = m('account')->getSalt();
                    $this->update($member['id'], array('mobile' => $mobile, 'pwd' => md5($pwd . $salt), 'salt' => $salt, 'mobileverify' => 1));

                    unset($_SESSION[$key]);
                    m('account')->setLogin($member['id']);
                    show_json(1, "bind success (2)");
                }else{
                    show_json(-1, "<center>此手机号已通过其他方式注册<br>如果继续将会合并账号信息<br>确定继续吗？</center>");
                }
            }

            // 判断 member 不是微信用户
            if(!$this->iswxm($member)){

                if($confirm) {
                    // 合并用户
                    $result = $this->merge($member, $member2);
                    if (empty($result['errno'])) {
                        show_json(0, $result['message']);
                    }

                    $salt = m('account')->getSalt();
                    $this->update($member2['id'], array('mobile' => $mobile, 'pwd' => md5($pwd . $salt), 'salt' => $salt, 'mobileverify' => 1));

                    unset($_SESSION[$key]);
                    m('account')->setLogin($member2['id']);
                    show_json(1, "bind success (3)");
                }else{
                    show_json(-1, "<center>此手机号已通过其他方式注册<br>如果继续将会合并账号信息<br>确定继续吗？</center>");
                }
            }

        }

        $sendtime = $_SESSION['verifycodesendtime'];
        if(empty($sendtime) || $sendtime+60<time()){
            $endtime = 0;
        }else{
            $endtime = 60 - (time() - $sendtime);
        }

        include $this->template();

    }

    protected function update($mid=0, $arr=array()){
        global $_W;
        if(empty($mid) || empty($arr) || !is_array($arr)){
            return;
        }
        pdo_update('ewei_shop_member', $arr, array('id'=>$mid,'uniacid'=>$_W['uniacid']));
    }

    protected function iswxm($member=array()){
        if(empty($member) || !is_array($member)){
            return true;
        }
        if(strexists($member['openid'], 'sns_wx_') || strexists($member['openid'], 'sns_qq_') || strexists($member['openid'], 'wap_user_')) {
            return false;
        }
        return true;
    }

    //  A用户 合并至 B用户
    protected function merge($a=array(), $b=array()){
        global $_W;

        if(empty($a) || empty($b) || $a['id']==$b['id']){
            return error(0, "params error");
        }

        // 2. 会员基本信息  level groupid  createtime childtime isblack
        $createtime = $a['createtime'] > $b['createtime'] ? $b['createtime'] : $a['createtime'];
        $childtime = $a['childtime'] > $b['childtime'] ? $b['childtime'] : $a['childtime'];
        $comparelevel = m('member')->compareLevel(array($a['level'], $b['level']));
        $level = $comparelevel ? $b['level'] : $a['level'];
        //$groupid = '';
        $isblack = !empty($a['isblack']) || !empty($b['isblack']) ? 1 : 0;

        // qq openid
        $openid_qq = !empty($b['openid_qq']) && empty($a['openid_qq']) ? $b['openid_qq'] : $a['openid_qq'];
        $openid_wx = !empty($b['openid_wx']) && empty($a['openid_wx']) ? $b['openid_wx'] : $a['openid_wx'];

        // 2.2. 上级关系
        // 如果A是分销商
        if(!empty($a['isagent']) && empty($b['isagent'])){
            $isagent = 1;
            $agentid = $a['agentid'];
            $status = !empty($a['status']) ? 1 : 0;
            $agenttime = $a['agenttime'];
            $agentlevel = $a['agentlevel'];
            $agentblack = $a['agentblack'];
            $fixagentid = $a['fixagentid'];
        }
        // 如果B是分销商
        elseif(!empty($b['isagent']) && empty($a['isagent'])){
            $isagent = 1;
            $agentid = $b['agentid'];
            $status = !empty($b['status']) ? 1 : 0;
            $agenttime = $b['agenttime'];
            $agentlevel = $b['agentlevel'];
            $agentblack = $b['agentblack'];
            $fixagentid = $b['fixagentid'];
        }
        // 如果A、B都是分销商
        elseif(!empty($b['isagent']) && !empty($a['isagent'])){
            // 判断分销商等级 取高级用户
            $compare = p('commission')->compareLevel(array($a['agentlevel'], $b['agentlevel']));
            $isagent = 1;
            if($compare){
                $agentid = $b['agentid'];
                $status = !empty($b['status']) ? 1 : 0;
                $agentblack = !empty($b['agentblack']) ? 1 : 0;
                $fixagentid = !empty($b['fixagentid']) ? 1 : 0;
            }else{
                $agentid = $a['agentid'];
                $status = !empty($a['status']) ? 1 : 0;
                $agentblack = !empty($a['agentblack']) ? 1 : 0;
                $fixagentid = !empty($a['fixagentid']) ? 1 : 0;
            }
            $agenttime = $compare ? $b['agenttime'] : $a['agenttime'];
            $agentlevel = $compare ? $b['agentlevel'] : $a['agentlevel'];
        }

        // 3. 合伙人
        if(!empty($a['isauthor']) && empty($b['isauthor'])){
            // 如果A是合伙人
            $isauthor = $a['isauthor'];
            $authorstatus = !empty($a['authorstatus']) ? 1 : 0;
            $authortime = $a['authortime'];
            $authorlevel = $a['authorlevel'];
            $authorblack = $a['authorblack']; // 合伙人黑名单
        }
        elseif(!empty($b['isauthor']) && empty($a['isauthor'])){
            // 如果B是合伙人
            $isauthor = $b['isauthor'];
            $authorstatus = !empty($b['authorstatus']) ? 1 : 0;
            $authortime = $b['authortime'];
            $authorlevel = $b['authorlevel'];
            $authorblack = $b['authorblack']; // 合伙人黑名单
        }
        elseif(!empty($b['isauthor']) && !empty($a['isauthor'])){
            // 如果A、B都是合伙人
            return error(0, "此手机号已绑定另一用户(a1)<br>请联系管理员");
        }

        // 4. 股东
        if(!empty($a['ispartner']) && empty($b['ispartner'])){
            // 如果A是股东
            $ispartner = 1;
            $partnerstatus = !empty($a['partnerstatus']) ? 1 : 0;
            $partnertime = $a['partnertime'];
            $partnerlevel = $a['partnerlevel'];
            $partnerblack = $a['partnerblack'];
        }
        elseif(!empty($b['ispartner']) && empty($a['ispartner'])){
            // 如果B是股东
            $ispartner = 1;
            $partnerstatus = !empty($b['partnerstatus']) ? 1 : 0;
            $partnertime = $b['partnertime'];
            $partnerlevel = $b['partnerlevel'];
            $partnerblack = $b['partnerblack'];
        }
        elseif(!empty($b['ispartner']) && !empty($a['ispartner'])){
            // 如果A、B都是股东
            return error(0, "此手机号已绑定另一用户(p)<br>请联系管理员");
        }

        // 4. 区域代理
        if(!empty($a['isaagent']) && empty($b['isaagent'])){
            // 如果A是区域代理
            $isaagent = $a['isaagent'];
            $aagentstatus = !empty($a['aagentstatus']) ? 1 : 0;
            $aagenttime = $a['aagenttime'];
            $aagentlevel = $a['aagentlevel'];
            $aagenttype = $a['aagenttype'];
            $aagentprovinces = $a['aagentprovinces'];
            $aagentcitys = $a['aagentcitys'];
            $aagentareas = $a['aagentareas'];
        }
        elseif(!empty($b['isaagent']) && empty($a['isaagent'])){
            // 如果B是区域代理
            $isaagent = $b['isaagent'];
            $aagentstatus = !empty($b['aagentstatus']) ? 1 : 0;
            $aagenttime = $b['aagenttime'];
            $aagentlevel = $b['aagentlevel'];
            $aagenttype = $b['aagenttype'];
            $aagentprovinces = $b['aagentprovinces'];
            $aagentcitys = $b['aagentcitys'];
            $aagentareas = $b['aagentareas'];
        }
        elseif(!empty($b['isaagent']) && !empty($a['isaagent'])){
            // 如果A、B都是区域代理
            return error(0, "此手机号已绑定另一用户(a2)<br>请联系管理员");
        }

        // 处理更新数据
        $arr = array();
        // 基本信息
        if(isset($createtime)){
            $arr['createtime'] = $createtime;
        }
        if(isset($childtime)){
            $arr['childtime'] = $childtime;
        }
        if(isset($level)){
            $arr['level'] = $level;
        }
        if(isset($groupid)){
            $arr['groupid'] = $groupid;
        }
        if(isset($isblack)){
            $arr['isblack'] = $isblack;
        }
        if(isset($openid_qq)){
            $arr['openid_qq'] = $openid_qq;
        }
        if(isset($openid_wx)){
            $arr['openid_wx'] = $openid_wx;
        }
        // 分销
        if(isset($status)){
            $arr['status'] = $status;
        }
        if(isset($isagent)){
            $arr['isagent'] = $isagent;
        }
        if(isset($agentid)){
            $arr['agentid'] = $agentid;
        }
        if(isset($agenttime)){
            $arr['agenttime'] = $agenttime;
        }
        if(isset($agentlevel)){
            $arr['agentlevel'] = $agentlevel;
        }
        if(isset($agentblack)){
            $arr['agentblack'] = $agentblack;
        }
        if(isset($fixagentid)){
            $arr['fixagentid'] = $fixagentid;
        }
        // 合伙人
        if(isset($isauthor)){
            $arr['isauthor'] = $isauthor;
        }
        if(isset($authorstatus)){
            $arr['authorstatus'] = $authorstatus;
        }
        if(isset($authortime)){
            $arr['authortime'] = $authortime;
        }
        if(isset($authorlevel)){
            $arr['authorlevel'] = $authorlevel;
        }
        if(isset($authorblack)){
            $arr['authorblack'] = $authorblack;
        }
        // 股东
        if(isset($ispartner)){
            $arr['ispartner'] = $ispartner;
        }
        if(isset($partnerstatus)){
            $arr['partnerstatus'] = $partnerstatus;
        }
        if(isset($partnertime)){
            $arr['partnertime'] = $partnertime;
        }
        if(isset($partnerlevel)){
            $arr['partnerlevel'] = $partnerlevel;
        }
        if(isset($partnerblack)){
            $arr['partnerblack'] = $partnerblack;
        }
        // 区域代理
        if(isset($isaagent)){
            $arr['isaagent'] = $isaagent;
        }
        if(isset($aagentstatus)){
            $arr['aagentstatus'] = $aagentstatus;
        }
        if(isset($aagenttime)){
            $arr['aagenttime'] = $aagenttime;
        }
        if(isset($aagentlevel)){
            $arr['aagentlevel'] = $aagentlevel;
        }
        if(isset($aagenttype)){
            $arr['aagenttype'] = $aagenttype;
        }
        if(isset($aagentprovinces)){
            $arr['aagentprovinces'] = $aagentprovinces;
        }
        if(isset($aagentcitys)){
            $arr['aagentcitys'] = $aagentcitys;
        }
        if(isset($aagentareas)){
            $arr['aagentareas'] = $aagentareas;
        }

        if(!empty($arr) && is_array($arr)){
            pdo_update('ewei_shop_member', $arr, array('id'=>$b['id']));
        }

        // 2. 分销信息
        pdo_update('ewei_shop_commission_apply', array('mid' => $b['id']), array('uniacid' => $_W['uniacid'], 'mid' => $a['id']));
        //订单上级
        pdo_update('ewei_shop_order',array('agentid'=>$b['id']),array('agentid'=>$a['id']));
        //会员上级
        pdo_update('ewei_shop_member',array('agentid'=>$b['id']),array('agentid'=>$a['id']));


        // 1. 合并用户余额积分合并
        if($a['credit1']>0){
            m('member')->setCredit($b['openid'], 'credit1', abs($a['credit1']), '全网通会员数据合并增加积分 +' . $a['credit1']);
        }
        if($a['credit2']>0) {
            m('member')->setCredit($b['openid'], 'credit2', abs($a['credit2']), '全网通会员数据合并增加余额 +' . $a['credit2']);
        }

        // 删除用户A
        pdo_delete('ewei_shop_member', array('id' => $a['id'], 'uniacid' => $_W['uniacid']));

        // 0. 替换所有数据表里的openid
        $tables = pdo_fetchall("SHOW TABLES like '%_ewei_shop_%'");
        foreach ($tables as $k => $v) {
            $v = array_values($v);
            $tablename = str_replace($_W['config']['db']['tablepre'], '', $v[0]);
            // 更新表中 含有 openid、uniacid的表
            if (pdo_fieldexists($tablename, 'openid') && pdo_fieldexists($tablename, 'uniacid')) {
                pdo_update($tablename, array('openid' => $b['openid']), array('uniacid' => $_W['uniacid'], 'openid' => $a['openid']));
            }
            // 更新表中 含有 openid、acid的表
            if (pdo_fieldexists($tablename, 'openid') && pdo_fieldexists($tablename, 'acid')) {
                pdo_update($tablename, array('openid' => $b['openid']), array('acid' => $_W['acid'], 'openid' => $a['openid']));
            }
            // 更新表中 含有 mid、uniacid的表
            if (pdo_fieldexists($tablename, 'mid') && pdo_fieldexists($tablename, 'uniacid')) {
                pdo_update($tablename, array('mid' => $b['id']), array('uniacid' => $_W['uniacid'], 'mid' => $a['id']));
            }
        }

        return error(1);
    }
}
