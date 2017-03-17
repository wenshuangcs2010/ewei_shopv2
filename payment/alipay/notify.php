<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */

error_reporting(0);
define('IN_MOBILE', true);
require dirname(__FILE__).'/../../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';

class aliApy
{
    public $post;
    public $subject;
    public $body;
    public $strs;
    public $type;
    public $total_fee;
    public $setting;
    public $sec;
    public $isapp = false;

    public function __construct()
    {
        global $_W;
        $this->post = $_POST;
        if (!empty($this->post['subject'])){
            $this->subject = iconv('gbk','utf-8',$this->post['subject']);
        }
        if (!empty($this->post['body'])){
            $this->body = iconv('gbk','utf-8',$this->post['body']);
        }
        if (empty($this->post)){
            exit('fail');
        }
        if($this->post['trade_status']!='TRADE_SUCCESS'){
            exit('fail');
        }
        $this->strs = explode(':', $this->body);
        $this->type = intval($this->strs[1]); //类型 0 购物 1 充值 2收银台
        $this->total_fee= round($this->post['total_fee'],2);
        $_W['uniacid'] = $_W['weid'] = intval($this->strs[0]);
        $this->init();
    }

    public function init()
    {
        if ($this->type == '0'){
            $this->order();
        }elseif ($this->type == '1'){
            $this->recharge();
        }elseif ($this->type == '2'){
            $this->cashier();
        }
        exit('success');
    }

    /**
     * 订单支付
     */
    public function order()
    {
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        $tid = $this->post['out_trade_no'];
        if(strexists($tid,'GJ')){
            $tids = explode("GJ",$tid);
            $tid = $tids[0];
        }
        $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `tid`=:tid and `module`=:module limit 1';
        $params = array();
        $params[':tid'] = $tid;
        $params[':module'] = 'ewei_shopv2';
        $log = pdo_fetch($sql, $params);
        WeUtility::logging('orderpay', var_export($this->post,true).'aa');

        if($this->post['total_fee']!=$log['fee']){
            exit('fail');
        }

        if (!empty($log) && $log['status'] == '0') {
            $site = WeUtility::createModuleSite($log['module']);
            if (!is_error($site)) {
                $method = 'payResult';
                if (method_exists($site, $method)) {
                    $ret = array();
                    $ret['weid'] = $log['weid'];
                    $ret['uniacid'] = $log['uniacid'];
                    $ret['result'] = 'success';
                    $ret['type'] = $log['type'];
                    $ret['from'] = 'return';
                    $ret['tid'] = $log['tid'];
                    $ret['user'] = $log['openid'];
                    $ret['fee'] = $log['fee'];
                    $ret['is_usecard'] = $log['is_usecard'];
                    $ret['card_type'] = $log['card_type'];
                    $ret['card_fee'] = $log['card_fee'];
                    $ret['card_id'] = $log['card_id'];
                    $ret['paymentno']=$this->post['trade_no'];
                    $result = $site->$method($ret);
                    if ($result) {
                        $record = array();
                        $record['status'] = '1';
                        pdo_update('core_paylog', $record, array('plid' => $log['plid']));
                        pdo_update('ewei_shop_order', array('paytype' => 22, 'apppay'=>$this->isapp?1:0), array('ordersn' => $log['tid'],'uniacid'=>$log['uniacid']));
                        exit('success');
                    }
                }
            }
        }
    }

    /**
     * 会员充值
     */
    public function recharge()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        //充值
        $logno = trim($this->post['out_trade_no']);

        if (empty($logno)) {
            exit;
        }
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_log') . ' WHERE `uniacid`=:uniacid and `logno`=:logno limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));

        if($this->post['total_fee']!=$log['money']){
            exit('fail');
        }

        if (!empty($log) && empty($log['status'])) {
            //充值状态
            pdo_update('ewei_shop_member_log', array('status' => 1, 'rechargetype' => 'alipay', 'apppay'=>$this->isapp?1:0), array('id' => $log['id']));
            //增加会员余额
            $shopset = m('common')->getSysset('shop');
            m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $shopset['name'].'会员充值:credit2:' . $log['money']));
            //充值积分
            m('member')->setRechargeCredit($log['openid'], $log['money']);

            //充值活动
            com_run('sale::setRechargeActivity', $log);

            //优惠券
            com_run('coupon::useRechargeCoupon', $log);

            //模板消息
            m('notice')->sendMemberLogMessage($log['id']);
        }
    }

    /**
     * 收银台支付
     */
    public function cashier()
    {
        global $_W;
        $ordersn = trim($this->post['out_trade_no']);
        if (empty($ordersn)) {
            exit;
        }
        if(p('cashier')){
//            p('cashier')->payResult($ordersn);
        }
    }

    /**
     * 使用商城自带支付 公用方法
     * @return bool
     */
    public function publicMethod()
    {
        global $_W;
        $this->setting = uni_setting($_W['uniacid'], array('payment'));
        if (isset($this->strs[2]) && $this->strs[2]=='APP'){
            $wapset = m('common')->getSysset('wap');
            $this->setting['payment']['alipay'] = array(
                'switch'=>1,
                'public_key'=>$wapset['alipublic']
            );
        }
        if (!empty($this->setting['payment']['alipay'])){
            $sec_yuan = m('common')->getSec();
            $this->sec =iunserializer($sec_yuan['sec']);

            if ($this->post['sign_type']=="RSA"){
                if (isset($this->strs[2]) && $this->strs[2]=='APP'){
                    $public_key = $this->sec['app_alipay']['public_key'];
                    if(empty($public_key)){
                        exit;
                    }
                    $this->isapp = true;
                    return m('finance')->RSAVerify($this->post, $public_key, true);
                }
            }else{
                $prepares = array();
                foreach ($this->post as $key => $value) {
                    if ($key != 'sign' && $key != 'sign_type') {
                        $prepares[] = "{$key}={$value}";
                    }
                }
                sort($prepares);
                $string = implode($prepares, '&');
                $string .= $this->setting['payment']['alipay']['secret'];
                $sign = md5($string);
                if ($sign == $this->post['sign']){
                    return true;
                }
            }
        }
        return false;
    }
}
new aliApy();
