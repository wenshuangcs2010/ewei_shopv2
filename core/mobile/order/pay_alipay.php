<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Pay_Alipay_EweiShopV2Page extends MobilePage {

    function main()
    {
        global $_W, $_GPC;
        $url = urldecode($_GPC['url']);
        if(is_weixin()){
            header('location: ' . $url);
            exit;
        }

        include $this->template('order/alipay');
    }

    public function complete() {
        global $_GPC, $_W;

        $set = m('common')->getSysset(array('shop', 'pay'));

        $fromwechat = intval($_GPC['fromwechat']);

        $tid = $_GPC['out_trade_no'];

        if(is_h5app()){
            $sec = m('common')->getSec();
            $sec =iunserializer($sec['sec']);
            $public_key = $sec['app_alipay']['public_key'];

            if(empty($set['pay']['app_alipay']) || empty($public_key)){
                $this->message('支付出现错误，请重试(1)!', mobileUrl('order'));
            }

            $alidata = base64_decode($_GET['alidata']);
            $alidata = json_decode($alidata, true);
            $alisign = m('finance')->RSAVerify($alidata, $public_key, false);

            $tid = $this->str($alidata['out_trade_no']);

            if($alisign==0){
                $this->message('支付出现错误，请重试(2)!', mobileUrl('order'));
            }

        }else{
            if(empty($set['pay']['alipay'])){
                $this->message('未开启支付宝支付!', mobileUrl('order'));
            }
            if (!m('finance')->isAlipayNotify($_GET)) {
                $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':tid' => $tid));
                if($log['status']==1 && $log['fee']==$_GPC['total_fee']){
                    if($fromwechat){
                        $this->message(array("message"=>"请返回微信查看支付状态", "title"=>"支付成功!", "buttondisplay"=>false), null, 'success');
                    }else{
                        $this->message(array("message"=>"请返回商城查看支付状态", "title"=>"支付成功!"), mobileUrl('order'), 'success');
                    }
                }
                $this->message(array('message'=>'支付出现错误，请重试(支付验证失败)!', 'buttondisplay'=>$fromwechat?false:true), $fromwechat?null:mobileUrl('order'));
            }
        }

        $log = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':tid' => $tid));


        if (empty($log)) {
            $this->message(array('message'=>'支付出现错误，请重试(支付验证失败2)!', 'buttondisplay'=>$fromwechat?false:true), $fromwechat?null:mobileUrl('order'));
        }

        if(is_h5app()){
            $alidatafee = $this->str($alidata['total_fee']);
            $alidatastatus = $this->str($alidata['success']);
            if($log['fee']!=$alidatafee || !$alidatastatus){
                $this->message('支付出现错误，请重试(4)!', mobileUrl('order'));
            }
        }

        if ($log['status'] != 1) {
            //支付宝支付
            $record = array();
            $record['status'] = '1';
            $record['type'] = 'alipay';
            pdo_update('core_paylog', $record, array('plid' => $log['plid']));

            //取orderid
            $orderid = pdo_fetchcolumn('select id from ' . tablename('ewei_shop_order') . ' where ordersn=:ordersn and uniacid=:uniacid', array(':ordersn' => $log['tid'], ':uniacid' => $_W['uniacid']));

            if (!empty($orderid))  {
                m('order')->setOrderPayType($orderid, 22);
                $data_alipay = array(
                    'transid' => $_GET['trade_no']
                );
                if(is_h5app()){
                    $data_alipay['transid'] = $alidata['trade_no'];
                    $data_alipay['apppay'] = 1;
                }
                pdo_update('ewei_shop_order', $data_alipay, array('id' => $orderid ));
            }

            $ret = array();
            $ret['result'] = 'success';
            $ret['type'] = 'alipay';
            $ret['from'] = 'return';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['weid'] = $log['weid'];
            $ret['uniacid'] = $log['uniacid'];
            m('order')->payResult($ret);
        }


        if(is_h5app()){
            $url = mobileUrl('order/detail', array('id' => $orderid),true);
            die("<script>top.window.location.href='{$url}'</script>");
        }else{
            if($fromwechat) {
                $this->message(array("message" => "请返回微信查看支付状态", "title" => "支付成功!", "buttondisplay" => false), null, 'success');
            }else{
                $this->message(array("message"=>"请返回商城查看支付状态", "title"=>"支付成功!"), mobileUrl('order'), 'success');
            }
        }

    }


    function recharge_complete() {
        global $_W, $_GPC;

        $fromwechat = intval($_GPC['fromwechat']);

        $logno = trim($_GPC['out_trade_no']);
        $notify_id = trim($_GPC['notify_id']); //支付宝通知ID
        $sign = trim($_GPC['sign']);
        $set = m('common')->getSysset(array('shop', 'pay'));
        if(is_h5app()){
            $sec = m('common')->getSec();
            $sec =iunserializer($sec['sec']);
            $public_key = $sec['app_alipay']['public_key'];

            if(empty($_GET['alidata'])){
                $this->message('支付出现错误，请重试(1)!', mobileUrl('member'));
            }

            if(empty($set['pay']['app_alipay']) || empty($public_key)){
                $this->message('支付出现错误，请重试(2)!', mobileUrl('order'));
            }

            $alidata = base64_decode($_GET['alidata']);

            $alidata = json_decode($alidata, true);
            $alisign = m('finance')->RSAVerify($alidata, $public_key, false);

            $logno = $this->str($alidata['out_trade_no']);

            if($alisign==0){
                $this->message('支付出现错误，请重试(3)!', mobileUrl('member'));
            }
            $transid=$alidata['trade_no'];
        }else{

            if (empty($logno)) {
                $this->message(array('message'=>'支付出现错误，请重试(支付验证失败1)!', 'buttondisplay'=>$fromwechat?false:true), $fromwechat?null:mobileUrl('member'));
            }

            if(empty($set['pay']['alipay'])){
                $this->message(array('message'=>'支付出现错误，请重试(未开启支付宝支付)!', 'buttondisplay'=>$fromwechat?false:true), $fromwechat?null:mobileUrl('member'));
            }

            if (!m('finance')->isAlipayNotify($_GET)) {
                $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_log') . ' WHERE `logno`=:logno and `uniacid`=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
                if (!empty($log) && !empty($log['status'])) {
                    if($fromwechat){
                        $this->message(array("message"=>"请返回微信查看支付状态", "title"=>"支付成功!", "buttondisplay"=>false), null, 'success');
                    }else{
                        $this->message(array("message"=>"请返回商城查看支付状态", "title"=>"支付成功!"), mobileUrl('member'), 'success');
                    }
                }
                $this->message(array('message'=>'支付出现错误，请重试(支付验证失败2)!', 'buttondisplay'=>$fromwechat?false:true), $fromwechat?null:mobileUrl('member'));
            }
            $transid=$_GET['trade_no'];
        }

        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_log') . ' WHERE `logno`=:logno and `uniacid`=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        if (!empty($log) && empty($log['status'])) {

            //充值状态
            pdo_update('ewei_shop_member_log', array('status' => 1, 'rechargetype' => 'alipay', 'apppay'=>is_h5app()?1:0,'transid'=>$transid), array('id' => $log['id']));
            //增加会员余额
            m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $_W['shopset']['shop']['name'].'会员充值:alipayreturn:credit2:' . $log['money']));
            //充值积分
            m('member')->setRechargeCredit($log['openid'], $log['money']);
            //充值活动
            com_run('sale::setRechargeActivity', $log);

            //优惠券
            com_run('coupon::useRechargeCoupon', $log);

            //模板消息
            m('notice')->sendMemberLogMessage($log['id']);
        }

        if(is_h5app()){
            $url = mobileUrl('member', null, true);
            die("<script>top.window.location.href='{$url}'</script>");
        }else{
            if ($fromwechat){
                $this->message(array("message"=>"请返回微信查看支付状态", "title"=>"支付成功!", "buttondisplay"=>false), null, 'success');
            }else{
                $this->message(array("message"=>"请返回商城查看支付状态", "title"=>"支付成功!"), mobileUrl('member'), 'success');
            }
        }
    }

    protected function str($str){
        $str = str_replace('"', '', $str);
        $str = str_replace("'", '', $str);
        return $str;
    }

}
