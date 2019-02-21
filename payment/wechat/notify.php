<?php


/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */

error_reporting(0);
define('IN_MOBILE', true);
$input = file_get_contents('php://input');
libxml_disable_entity_loader(true);
if (!empty($input) && empty($_GET['out_trade_no'])) {
	$obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
	$data = json_decode(json_encode($obj), true);

	if (empty($data)) {
		exit('fail');
	}
	if ($data['result_code'] != 'SUCCESS' || $data['return_code'] != 'SUCCESS') {
		$result = array(
			'return_code' => 'FAIL',
			'return_msg' => empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']
		);
		echo array2xml($result);
		exit;
	}
	$get = $data;
} else {
	$get = $_GET;
}

//$get = json_decode('{
//	"appid": "wx8d9bbf88bf7288e7",
//    "attach": "17:1",
//    "bank_type": "CFT",
//    "cash_fee": "1",
//    "device_info": "ewei_shopv2",
//    "fee_type": "CNY",
//    "is_subscribe": "Y",
//    "mch_id": "1339157801",
//    "nonce_str": "LwfOb3uw",
//    "openid": "o6tC-wt10wuSf4ay40ZlJPCRZSs0",
//    "out_trade_no": "1467793863",
//    "result_code": "SUCCESS",
//    "return_code": "SUCCESS",
//    "sign": "28B4DDCA937D9752D0DF4B74A97026AD",
//    "time_end": "20160706163121",
//    "total_fee": "1",
//    "trade_type": "NATIVE",
//    "transaction_id": "4002512001201607068472561783"
//}',true);
require dirname(__FILE__).'/../../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/dispage.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';

class EweiShopWechatPay
{
    public $get;
    public $type;
    public $total_fee;
    public $set;
    public $setting;
    public $sec;
    public $sign;
    public $isapp = false;
    public $is_jie = false;

    public function __construct($get)
    {
        global $_W;
        $this->get = $get;
        $strs = explode(':', $this->get['attach']);
        $this->type = intval($strs[1]); //类型 0 购物 1 充值 2积分兑换 3 积分兑换运费 4 优惠券购买 5 拼团 11门店充值积分 12门店消费 13收银台
        $this->total_fee= round($this->get['total_fee']/100,2);
        $_W['uniacid'] = $_W['weid'] = intval($strs[0]);
        $this->init();
    }

    public function success()
    {
        $result = array(
            'return_code' => 'SUCCESS',
            'return_msg' => 'OK'
        );
        echo array2xml($result);
        exit;
    }

    public function fail()
    {
        $result = array(
            'return_code' => 'FAIL'
        );
        echo array2xml($result);
        exit;
    }

    public function init()
    {
        if ($this->type == '0'){
            $this->order();
        }elseif ($this->type == '1'){
            $this->recharge();
        }elseif ($this->type == '2'){
            $this->creditShop();
        }elseif ($this->type == '3'){
            $this->creditShopFreight();
        }elseif ($this->type == '4'){
            $this->coupon();
        }elseif ($this->type == '5'){
            $this->groups();
        }elseif ($this->type == '10'){
            $this->mr();
        }elseif ($this->type == '11'){
            $this->pstoreCredit();
        }elseif ($this->type == '12'){
            $this->pstore();
        }elseif ($this->type == '13'){
            $this->cashier();
        }

        $this->success();
    }

    /**
     * 订单支付
     */
    public function order()
    {
        
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        $tid = $this->get['out_trade_no'];

        $isborrow = 0;
        $borrowopenid = '';

        if (strpos($tid,'_borrow')!==false){
            $tid = str_replace('_borrow','',$tid);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }

        if (strpos($tid,'_B')!==false){
            $tid = str_replace('_B','',$tid);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }

        if (strexists($tid, 'GJ')) {
            $tids = explode("GJ", $tid);
            $tid = $tids[0];
        }

        $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid  limit 1';
        $params = array();
        $params[':tid'] = $tid;
        $params[':module'] = 'ewei_shopv2';

        $log = pdo_fetch($sql, $params);
        if (!empty($log) && $log['status'] == '0'  && $log['fee']==$this->total_fee ) {

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
                    $ret['tag'] = $log['tag'];
                    $ret['paytype'] = 21;
                    $ret['paymentno']=$this->get['transaction_id'];
                    $result = $site->$method($ret);
                    if ($result) {

                        $log['tag'] = iunserializer($log['tag']);
                        $log['tag']['transaction_id'] = $this->get['transaction_id'];
                        $record = array();
                        $record['status'] = '1';
                        $record['tag'] = iserializer($log['tag']);
                        pdo_update('core_paylog', $record, array('plid' => $log['plid']));
                        pdo_update('ewei_shop_order', array('paytype' => 21,'isborrow'=>$isborrow,'borrowopenid'=>$borrowopenid, 'apppay'=>$this->isapp?1:0), array('ordersn' => $log['tid'],'uniacid'=>$log['uniacid']));
                        $sql = 'SELECT id FROM ' . tablename('ewei_shop_pay_request') . ' WHERE `order_sn`=:tid and `pay_type`=:pay_type limit 1';
                        $id=pdo_fetchcolumn($sql,array(":tid"=>$this->get['out_trade_no'],':pay_type'=>21));
                        if($id){
                            $data=array('initalResponse'=>json_encode($this->get),'status'=>1);
                            pdo_update("ewei_shop_pay_request",$data,array("id"=>$id));
                        }
                    }
                }
            }
        }else{
            $this->fail();
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
        $logno = trim($this->get['out_trade_no']);

        $isborrow = 0;
        $borrowopenid = '';
        if (strpos($logno,'_borrow')!==false){
            $logno = str_replace('_borrow','',$logno);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }

        if (empty($logno)) {
            $this->fail();
        }
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_log') . ' WHERE `uniacid`=:uniacid and `logno`=:logno limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        $OK= !empty($log) && empty($log['status']) && $log['money']==$this->total_fee;
        if (!$this->set['pay']['weixin_jie'] && !$this->isapp){
            $OK = $OK && $log['openid']==$this->get['openid'];
        }
        if ($OK) {
            //充值状态
            pdo_update('ewei_shop_member_log', array('status' => 1, 'rechargetype' => 'wechat','isborrow'=>$isborrow,'borrowopenid'=>$borrowopenid, 'apppay'=>$this->isapp?1:0), array('id' => $log['id']));
            //增加会员余额
            $shopset = m('common')->getSysset('shop');
            m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $shopset['name'].'会员充值:wechatnotify:credit2:' . $log['money']));
            //充值积分
            m('member')->setRechargeCredit($log['openid'], $log['money']);

            //充值活动
            com_run('sale::setRechargeActivity', $log);

            //优惠券
            com_run('coupon::useRechargeCoupon', $log);

            //模板消息
            m('notice')->sendMemberLogMessage($log['id']);
        }elseif ($log['money']==$this->total_fee){
            pdo_update('ewei_shop_member_log', array('rechargetype' => 'wechat','isborrow'=>$isborrow,'borrowopenid'=>$borrowopenid, 'apppay'=>$this->isapp?1:0), array('id' => $log['id']));
        }
    }

    /**
     * 积分商城兑换
     */
    public function creditShop()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        //积分兑换
        $logno = trim($this->get['out_trade_no']);
        if (empty($logno)) {
            exit;
        }
        $logno = str_replace('_borrow','',$logno);
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_creditshop_log') . ' WHERE `logno`=:logno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        if (!empty($log) && empty($log['status'])) {
            $goods = pdo_fetch("SELECT * FROM" .tablename("ewei_shop_creditshop_goods") . "WHERE id=:id and uniacid=:uniacid limit 1 ",array(":id"=>$log['goodsid'], ":uniacid"=>$_W['uniacid']));
            if(!empty($goods) && $this->total_fee==$goods['money']){
                pdo_update('ewei_shop_creditshop_log', array('paystatus' => 1, 'paytype' => 1), array('id' => $log['id']));
            }
        }
    }

    /**
     * 积分兑换运费问题
     */
    public function creditShopFreight()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        //积分兑换运费支付
        $dispatchno = trim($this->get['out_trade_no']);
        $dispatchno = str_replace('_borrow','',$dispatchno);
        if (empty($dispatchno)) {
            exit;
        }
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_creditshop_log') . ' WHERE `dispatchno`=:dispatchno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':dispatchno' => $dispatchno));
        if (!empty($log) && empty($log['dispatchstatus'])) {
            pdo_update('ewei_shop_creditshop_log', array('dispatchstatus' => 1), array('id' => $log['id']));
        }
    }

    /**
     * 优惠券支付
     */
    public function coupon()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        $logno = str_replace('_borrow','',$this->get['out_trade_no']);
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_coupon_log') . ' WHERE `logno`=:logno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        $coupon = pdo_fetchcolumn('select money from ' . tablename('ewei_shop_coupon') . ' where id=:id limit 1', array(':id' => $log['couponid']));
        if ($coupon==$this->total_fee){
            com_run('coupon::payResult', $logno);
        }
    }

    /**
     * 拼团支付
     */
    public function groups()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        //拼团支付
        $orderno = trim($this->get['out_trade_no']);
        $orderno = str_replace('_borrow','',$orderno);
        if (empty($orderno)) {
            exit;
        }
        if ($this->is_jie){
            pdo_update('ewei_shop_groups_order', array('isborrow'=>'1','borrowopenid'=>$this->get['openid'],'paymentno'=>$this->get['transaction_id']), array('orderno'=>$orderno, 'uniacid' => $_W['uniacid']));
        }
         pdo_update('ewei_shop_groups_order', array('paymentno'=>$this->get['transaction_id']), array('orderno'=>$orderno, 'uniacid' => $_W['uniacid']));
        if(p('groups')){
            p('groups')->payResult($orderno,'wechat', $this->isapp?true:false);
        }
    }

    /**
     * 话费充值
     */
    public function mr()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        $ordersn = trim($this->get['out_trade_no']);
        $isborrow = 0;
        $borrowopenid = '';
        if (strpos($ordersn,'_borrow')!==false){
            $ordersn = str_replace('_borrow','',$ordersn);
            $isborrow = 1;
            $borrowopenid = $$this->get['openid'];
        }
        if (empty($ordersn)) {
            exit;
        }
        if(p('mr')){
            $price = pdo_fetchcolumn('select payprice from ' . tablename('ewei_shop_mr_order') . ' where ordersn=:ordersn limit 1', array(':ordersn' =>$ordersn));
            if($price==$$this->total_fee){
                if($isborrow==1){
                    pdo_update('ewei_shop_order', array('isborrow'=>$isborrow,'borrowopenid'=>$borrowopenid), array('ordersn' => $ordersn));
                }
                p('mr')->payResult($ordersn,'wechat');
            }
        }
    }

    /**
     * 门店积分充值
     */
    public function pstoreCredit()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        $ordersn = trim($this->get['out_trade_no']);
        $ordersn = str_replace('_borrow','',$ordersn);
        if (empty($ordersn)) {
            exit;
        }
        if(p('pstore')){
            p('pstore')->payResult($ordersn,$this->total_fee);
        }
    }

    /**
     * 门店支付
     */
    public function pstore()
    {
        global $_W;
        if (!$this->publicMethod()){
            exit(__FUNCTION__);
        }
        $ordersn = trim($this->get['out_trade_no']);
        $ordersn = str_replace('_borrow','',$ordersn);
        if (empty($ordersn)) {
            exit;
        }
        if(p('pstore')){
            p('pstore')->wechat_complete($ordersn);
        }
    }

    /**
     * 收银台支付
     */
    public function cashier()
    {
        global $_W;
        $ordersn = trim($this->get['out_trade_no']);
        if (empty($ordersn)) {
            exit;
        }
        if(p('cashier')){
            p('cashier')->payResult($ordersn);
        }
    }

    /**
     * 使用商城自带支付 公用方法
     * @return bool
     */
    public function publicMethod()
    {
        global $_W;
        
        $this->set = m('common')->getSysset(array('shop', 'pay'));
        $this->setting = uni_setting($_W['uniacid'], array('payment'));
        $isdisorder=0;
        if (is_array($this->setting['payment']) || $this->set['pay']['weixin_jie'] == 1 || $this->set['pay']['weixin_sub'] ==1 || $this->set['pay']['weixin_jie_sub'] ==1 || $this->get['trade_type']=='APP') {
            
            $this->is_jie =strpos($this->get['out_trade_no'],'_B')!==false || strpos($this->get['out_trade_no'],'_borrow')!==false;
            $sec_yuan = m('common')->getSec();
            $this->sec =iunserializer($sec_yuan['sec']);

          
            $tid = $this->get['out_trade_no'];
            
            $jearray=Dispage::getDisaccountArray();

            if(in_array($_W['uniacid'], $jearray)){
                if($this->is_jie){
                    $tid = str_replace('_borrow','',$tid);
                }
                $isdisorder=pdo_fetchcolumn("SELECT isdisorder FROM ".tablename("ewei_shop_order")." where ordersn=:ordersn",array(":ordersn"=>$tid));
                if($isdisorder==1){
                    $setting = uni_setting(DIS_ACCOUNT, array('payment'));
                    if (is_array($setting['payment'])) {
                         $jieweipay = $setting['payment']['wechat'];
                    }
                    $this->set['pay']['weixin_jie']=1;
                    $this->set['pay']['weixin_jie_sub']=1;
                    $this->sec['apikey']=$jieweipay['apikey'];
                    $this->sec['apikey_jie_sub']=$jieweipay['apikey'];
                }
            }
            
            if (($this->set['pay']['weixin_jie'] == 1 && $this->is_jie) || $this->set['pay']['weixin_sub'] ==1 || ($this->set['pay']['weixin_jie_sub'] ==1 && $this->is_jie)){

                if($this->set['pay']['weixin_sub'] ==1){
                    $wechat = array(
                        'version'=>1,
                        'key'=>$this->sec['apikey_sub'],
                        'signkey'=>$this->sec['apikey_sub'],
                    );
                }
                if ($this->set['pay']['weixin_jie'] == 1 && $this->is_jie){
                    $wechat = array(
                        'version'=>1,
                        'key'=>$this->sec['apikey'],
                        'signkey'=>$this->sec['apikey'],
                    );
                }
                if($this->set['pay']['weixin_jie_sub'] ==1 && $this->is_jie){
                    $wechat = array(
                        'version'=>1,
                        'key'=>$this->sec['apikey_jie_sub'],
                        'signkey'=>$this->sec['apikey_jie_sub'],
                    );
                }

            }
            else if($this->set['pay']['weixin'] ==1){
                $wechat = $this->setting['payment']['wechat'];
            }

            if ($this->get['trade_type']=='APP' && $this->set['pay']['app_wechat']==1){
                $this->isapp = true;
                $wechat = array(
                    'version'=>1,
                    'key'=>$this->sec['app_wechat']['apikey'],
                    'signkey'=>$this->sec['app_wechat']['apikey'],
                    'appid'=>$this->sec['app_wechat']['appid'],
                    'mchid'=>$this->sec['app_wechat']['merchid']
                );
            }
            
            if (!empty($wechat)) {
                ksort($this->get);
                $string1 = '';
                foreach ($this->get as $k => $v) {
                    if ($v != '' && $k != 'sign') {
                        $string1 .= "{$k}={$v}&";
                    }
                }
                $wechat['signkey'] = ($wechat['version'] == 1) ? $wechat['key'] : $wechat['signkey'];
                $this->sign = strtoupper(md5($string1 . "key={$wechat['signkey']}"));
                
                $this->get['openid'] = isset($this->get['sub_openid']) ? $this->get['sub_openid'] : $this->get['openid'];
                if ( $this->sign ==  $this->get['sign']) {
                    return true;
                }
            }
        }
        return false;

    }
}
new EweiShopWechatPay($get);
exit('fail');
