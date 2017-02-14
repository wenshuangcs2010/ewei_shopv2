<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Finance_EweiShopV2Model
{

    /**
     * 支付
     * @param type $openid openid
     * @param type $paytype 支付类型 0 余额 1 微信
     * @param type $money
     */
    public function pay($openid = '', $paytype = 0, $money = 0, $trade_no = '', $desc = '', $return = true)
    {

        global $_W, $_GPC;

        if (empty($openid)) {
            return error(-1, 'openid不能为空');
        }

        $member = m('member')->getMember($openid);
        if (empty($member)) {
            return error(-1, '未找到用户');
        }

        if (empty($paytype)) {
            //余额
            m('member')->setCredit($openid, 'credit2', $money, array(0, $desc));
            return true;
        } else {

            //钱包
            $setting = uni_setting($_W['uniacid'], array('payment'));
            if (!is_array($setting['payment'])) {
                return error(1, '没有设定支付参数');
            }

            $pay = m('common')->getSysset('pay');
            $sec = m('common')->getSec();
            $sec = iunserializer($sec['sec']);
            $wechat = $setting['payment']['wechat'];
            $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
            $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
            $certs = $sec;
            $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
            $pars = array();
            $pars['mch_appid'] = $row['key'];
            $pars['mchid'] = $wechat['mchid'];
            $pars['nonce_str'] = random(32);
            $pars['partner_trade_no'] = empty($trade_no) ? time() . random(4, true) : $trade_no;
            $pars['openid'] = $openid;
            $pars['check_name'] = 'NO_CHECK';
            $pars['amount'] = $money;
            $pars['desc'] = empty($desc) ? '现金提现' : $desc;
            $pars['spbill_create_ip'] = gethostbyname($_SERVER["HTTP_HOST"]);

            ksort($pars, SORT_STRING);
            $string1 = '';
            foreach ($pars as $k => $v) {
                $string1 .= "{$k}={$v}&";
            }
            $string1 .= "key=" . $wechat['apikey'];

            $pars['sign'] = strtoupper(md5($string1));
            $xml = array2xml($pars);
            $extras = array();
        
            $errmsg = "未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!";
            if (is_array($certs)) {

                if (empty($certs['cert']) || empty($certs['key']) || empty($certs['root'])) {
                    if ($return) {
                        if ($_W['ispost']) {
                            show_json(0, array('message' => $errmsg));
                        }
                        show_message($errmsg, '', 'error');
                    } else {
                        return error(-1, $errmsg);
                    }

                }
                $certfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
                file_put_contents($certfile, $certs['cert']);
                $keyfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
                file_put_contents($keyfile, $certs['key']);
                $rootfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
                file_put_contents($rootfile, $certs['root']);

                $extras['CURLOPT_SSLCERT'] = $certfile;
                $extras['CURLOPT_SSLKEY'] = $keyfile;
                $extras['CURLOPT_CAINFO'] = $rootfile;
            } else {
                if ($return) {
                    if ($_W['ispost']) {
                        show_json(0, array('message' => $errmsg));
                    }
                    show_message($errmsg, '', 'error');
                } else {
                    return error(-1, $errmsg);
                }
            }


            load()->func('communication');
            $resp = ihttp_request($url, $xml, $extras);


            @unlink($certfile);
            @unlink($keyfile);
            @unlink($rootfile);

            if (is_error($resp)) {
                return error(-2, $resp['message']);
            }
            if (empty($resp['content'])) {
                return error(-2, '网络错误');
            } else {
                $arr = json_decode(json_encode(simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                if ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS') {
                    return true;
                } else {
                    if ($arr['return_msg'] == $arr['err_code_des']) {
                        $error = $arr['return_msg'];
                    } else {
                        $error = $arr['return_msg']. " | " . $arr['err_code_des'];
                    }
                    return error(-2, $error);
                }
            }
        }
    }

    /**
     * 退款
     * @param type $openid openid
     * @param type $money
     */
    public function refund($openid, $out_trade_no, $out_refund_no, $totalmoney, $refundmoney=0, $app=false,$refund_account = false)
    {

        global $_W, $_GPC;

        if (empty($openid)) {
            return error(-1, 'openid不能为空');
        }

        $member = m('member')->getMember($openid);
        if (empty($member)) {
            return error(-1, '未找到用户');
        }

        $setting = uni_setting($_W['uniacid'], array('payment'));
        if (!is_array($setting['payment'])) {
            return error(1, '没有设定支付参数');
        }
        $pay = m('common')->getSysset('pay');
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        $certs = $sec;
        if (!empty($pay['weixin_sub'])){
            $wechat = array(
                'appid'=>$sec['appid_sub'],
                'mchid'=>$sec['mchid_sub'],
                'sub_appid'=>!empty($sec['sub_appid_sub']) ? $sec['sub_appid_sub'] : '',
                'sub_mch_id'=>$sec['sub_mchid_sub'],
                'apikey' => $sec['apikey_sub']
            );
            $row = array('key'=>$sec['appid_sub']);
            $certs = $sec['sub'];
        }else{ 
            $wechat = $setting['payment']['wechat'];
            $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
            $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
        }

        if($app){
            if(empty($sec['app_wechat']['appid']) || empty($sec['app_wechat']['appsecret']) || empty($sec['app_wechat']['merchid']) || empty($sec['app_wechat']['apikey'])){
                return error(1, '没有设定APP支付参数');
            }
            $wechat = array(
                'appid' => $sec['app_wechat']['appid'],
                'mchid' => $sec['app_wechat']['merchid'],
                'apikey' => $sec['app_wechat']['apikey']
            );
            $row = array('key'=>$sec['app_wechat']['appid'], 'secret'=>$sec['app_wechat']['appsecret']);
            $certs = array(
                'cert' => $sec['app_wechat']['cert'],
                'key' => $sec['app_wechat']['key'],
                'root' =>$sec['app_wechat']['root']
            );
        }

        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $pars = array();
        $pars['appid'] = $row['key'];
        $pars['mch_id'] = $wechat['mchid'];
        $pars['nonce_str'] = random(8);
        $pars['out_trade_no'] = $out_trade_no;
        $pars['out_refund_no'] = $out_refund_no;
        $pars['total_fee'] = $totalmoney;
        $pars['refund_fee'] = $refundmoney;
        $pars['op_user_id'] = $wechat['mchid'];

        if ($refund_account){
            $pars['refund_account'] = $refund_account;
        }

        if (!empty($pay['weixin_sub']) && !$app){
            if (!empty($wechat['sub_appid'] )){
                $pars['sub_appid'] = $wechat['sub_appid'];
            }
            $pars['sub_mch_id'] = $wechat['sub_mch_id'];
        }

        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {

            $string1 .= "{$k}={$v}&";
        }
        //$string1 =rtrim($string1,'&');
        $string1 .= "key=" . $wechat['apikey'];
        $pars['sign'] = strtoupper(md5($string1));

        $xml = array2xml($pars);
        $extras = array();

        $errmsg = "未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!";
        if (is_array($certs)) {

            if (empty($certs['cert']) || empty($certs['key']) || empty($certs['root'])) {
                if ($_W['ispost']) {
                    show_json(0, array('message' => $errmsg));
                }
                show_message($errmsg, '', 'error');
            }

            $certfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($certfile, $certs['cert']);
            $keyfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($keyfile, $certs['key']);
            $rootfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($rootfile, $certs['root']);

            $extras['CURLOPT_SSLCERT'] = $certfile;
            $extras['CURLOPT_SSLKEY'] = $keyfile;
            $extras['CURLOPT_CAINFO'] = $rootfile;
        } else {
            if ($_W['ispost']) {
                show_json(0, array('message' => $errmsg));
            }
            show_message($errmsg, '', 'error');
        }

        load()->func('communication');
        $resp = ihttp_request($url, $xml, $extras);
        @unlink($certfile);
        @unlink($keyfile);
        @unlink($rootfile);
        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            if ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS') {
                return true;
            } elseif ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'FAIL'&& $arr['return_msg'] == 'OK' && !$refund_account){
                if ($arr['err_code'] == 'NOTENOUGH'){
                    return $this->refund($openid, $out_trade_no, $out_refund_no, $totalmoney, $refundmoney, $app,'REFUND_SOURCE_RECHARGE_FUNDS');
                }
            }else {
                if ($arr['return_msg'] == $arr['err_code_des']) {
                    $error = $arr['return_msg'];
                } else {
                    $error = $arr['return_msg']. " | " . $arr['err_code_des'];
                }
                return error(-2, $error);
            }
        }
    }

    public function refundBorrow($openid, $out_trade_no, $out_refund_no, $totalmoney, $refundmoney=0, $gaijia=0,$refund_account=false)
    {

        global $_W, $_GPC;

        if (empty($openid)) {
            return error(-1, 'openid不能为空');
        }

        $pay = m('common')->getSysset('pay');
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        $certs = $sec['jie'];

        if (!empty($pay['weixin_jie_sub'])){
            $wechat = array(
                'sub_appid'=>!empty($sec['sub_appid_jie_sub']) ? $sec['sub_appid_jie_sub'] : '',
                'sub_mch_id'=>$sec['sub_mchid_jie_sub'],
            );
            $sec['appid'] = $sec['appid_jie_sub'];
            $sec['mchid'] = $sec['mchid_jie_sub'];
            $sec['apikey'] = $sec['apikey_jie_sub'];
            $row = array('key'=>$sec['appid_jie_sub']);
            $certs = $sec['jie_sub'];
        }else{
        if(empty($sec['appid']) || empty($sec['mchid']) || empty($sec['apikey'])){
            return error(1, '没有设定支付参数');
        }
        }

        if(!empty($gaijia)){
            $out_trade_no = $out_trade_no.'_B';
        }else{
            $out_trade_no = $out_trade_no.'_borrow';
        }

        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $pars = array();
        $pars['appid'] = $sec['appid'];
        $pars['mch_id'] = $sec['mchid'];
        $pars['nonce_str'] = random(8);
        $pars['out_trade_no'] = $out_trade_no;
        $pars['out_refund_no'] = $out_refund_no;
        $pars['total_fee'] = $totalmoney;
        $pars['refund_fee'] = $refundmoney;
        $pars['op_user_id'] = $sec['mchid'];
        if ($refund_account){
            $pars['refund_account'] = $refund_account;
        }
        if (!empty($pay['weixin_jie_sub'])){
            $pars['sub_mch_id'] = $wechat['sub_mch_id'];
            $pars['op_user_id'] = $wechat['sub_mch_id'];
            if ($wechat['sub_appid']){
                $pars['sub_appid'] = $wechat['sub_appid'];
            }
        }

        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {

            $string1 .= "{$k}={$v}&";
        }
        //$string1 =rtrim($string1,'&');
        $string1 .= "key=" . $sec['apikey'];
        $pars['sign'] = strtoupper(md5($string1));

        $xml = array2xml($pars);
        $extras = array();

        $errmsg = "未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!";
        if (is_array($certs)) {

            if (empty($certs['cert']) || empty($certs['key']) || empty($certs['root'])) {
                if ($_W['ispost']) {
                    show_json(0, array('message' => $errmsg));
                }
                show_message($errmsg, '', 'error');
            }

            $certfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($certfile, $certs['cert']);
            $keyfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($keyfile, $certs['key']);
            $rootfile = IA_ROOT . "/addons/ewei_shopv2/cert/" . random(128);
            file_put_contents($rootfile, $certs['root']);

            $extras['CURLOPT_SSLCERT'] = $certfile;
            $extras['CURLOPT_SSLKEY'] = $keyfile;
            $extras['CURLOPT_CAINFO'] = $rootfile;
        } else {
            if ($_W['ispost']) {
                show_json(0, array('message' => $errmsg));
            }
            show_message($errmsg, '', 'error');
        }

        load()->func('communication');
        $resp = ihttp_request($url, $xml, $extras);
        @unlink($certfile);
        @unlink($keyfile);
        @unlink($rootfile);
        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            if ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS') {
                return true;
            } elseif ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'FAIL'&& $arr['return_msg'] == 'OK' && !$refund_account){
                if ($arr['err_code'] == 'NOTENOUGH'){
                    $this->refundBorrow($openid, $out_trade_no, $out_refund_no, $totalmoney, $refundmoney, $gaijia,'REFUND_SOURCE_RECHARGE_FUNDS');
                }
            }else {
                if ($arr['return_msg'] == $arr['err_code_des']) {
                    $error = $arr['return_msg'];
                } else {
                    $error = $arr['return_msg']. " | " . $arr['err_code_des'];
                }
                return error(-2, $error);
            }
        }
    }

    /**
     * @param $params
     * @param $config
     * @return array
     */
    public function AlipayRefund($params,$config)
    {
        global $_W;
        $params['refund_reason'] = str_replace(array('^','|','$','#'),'',$params['refund_reason']);
        $parameter = array(
            "service"       => 'refund_fastpay_by_platform_pwd',
            "partner"       => $config['partner'],
            "_input_charset"	=> 'UTF-8',
            "notify_url"	=> isset($params['notify_url']) ? $params['notify_url'] : '',
            "seller_user_id"=>$config['partner'],
            "seller_email"=>$config['account'],
            "refund_date"=>date('Y-m-d H:i:s'),
            "batch_no"	=> date('YmdHis'),
            "batch_num"	=> '1',
            "detail_data"	=> $params['trade_no'].'^'.$params['refund_price'].'^'.$params['refund_reason'],
        );
        $parameter = array_filter($parameter);
        $prepares = array();
        foreach ($parameter as $key => $value) {
            $prepares[] = "{$key}={$value}";
        }
        sort($prepares);
        $string = implode($prepares, '&');
        $string_md5 = $string.$config['secret'];
        $parameter['sign'] = md5($string_md5);
        $parameter['sign_type'] = 'MD5';
        load()->func('communication');
        $url = 'https://mapi.alipay.com/gateway.do?'.htmlspecialchars_decode(http_build_query($parameter,'&'),ENT_QUOTES);
        return array('url'=>$url);
    }

    /**
     * @param $params  = array('out_trade_no' => 订单号,'refund_amount'=>退款金额,'refund_reason' => 退款原因);
     * @param $config = array('app_id' => ,'privatekey' => "",'publickey' => "",'alipublickey' => "");
     */
    public function newAlipayRefund($params,$config)
    {
        global $_W;
        $biz_content = array();
        $biz_content['out_trade_no'] = $params['out_trade_no'];
        //退款金额
        $biz_content['refund_amount'] = $params['refund_amount'];
        //退款原因
        $biz_content['refund_reason'] = $params['refund_reason'];
        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。(可选)
        $biz_content['out_request_no'] = $params['out_request_no'];
        //商户的操作员编号(可选)
        $biz_content['operator_id'] = $params['operator_id'];
        //商户的门店编号(可选)
        $biz_content['store_id'] = $params['store_id'];
        //	商户的终端编号(可选)
        $biz_content['terminal_id'] = $params['terminal_id'];
        $biz_content = array_filter($biz_content);

        $config['method'] = 'alipay.trade.refund';
        $config['biz_content'] = json_encode($biz_content);
        $result = m('common')->publicAliPay($config);
        if (is_error($result)){
            return $result;
        }
        if ($result['alipay_trade_refund_response']['code'] == '10000'){
            return $result['alipay_trade_refund_response'];
        }else{
            return error($result['alipay_trade_refund_response']['code'],$result['alipay_trade_refund_response']['msg'].":".$result['alipay_trade_refund_response']['sub_msg']);
        }
    }

    //        公众账号ID	appid	是	String(32)	wx8888888888888888	微信分配的公众账号ID（企业号corpid即为此appId）
    //商户号	mch_id	是	String(32)	1900000109	微信支付分配的商户号
    //设备号	device_info	否	String(32)	013467007045764	微信支付分配的终端设备号，填写此字段，只下载该设备号的对账单
    //随机字符串	nonce_str	是	String(32)	5K8264ILTKCH16CQ2502SI8ZNMTM67VS	随机字符串，不长于32位。推荐随机数生成算法
    //签名	sign	是	String(32)	C380BEC2BFD727A4B6845133519F3AD6	签名，详见签名生成算法
    //对账单日期	bill_date	是	String(8)	20140603	下载对账单的日期，格式：20140603
    //账单类型	bill_type	否	String(8)	ALL
    //ALL，返回当日所有订单信息，默认值
    //SUCCESS，返回当日成功支付的订单
    //REFUND，返回当日退款订单
    //REVOKED，已撤销的订单
    /**
     * 下载对账单
     * @param type $type ALL，返回当日所有订单信息，默认值 SUCCESS，返回当日成功支付的订单 REFUND，返回当日退款订单 REVOKED，已撤销的订单
     * @param type $money
     */
    public function downloadbill($starttime, $endtime, $type = 'ALL')
    {

        global $_W, $_GPC;
        $dates = array();
        $startdate = date('Ymd', $starttime);
        $enddate = date('Ymd', $endtime);
        if ($startdate == $enddate) {
            $dates = array($startdate);
        } else {
            $days = (float)($endtime - $starttime) / 86400;
            for ($d = 0; $d < $days; $d++) {
                $dates[] = date('Ymd', strtotime($startdate . "+{$d} day"));
            }
        }
        if (empty($dates)) {
            show_message('对账单日期选择错误!', '', 'error');
        }
        $setting = uni_setting($_W['uniacid'], array('payment'));
        if (!is_array($setting['payment'])) {
            return error(1, '没有设定支付参数');
        }

        $wechat = $setting['payment']['wechat'];
        $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
        $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
        $content = "";
        foreach ($dates as $date) {

            $dc = $this->downloadday($date, $row, $wechat, $type);

            if (is_error($dc) || strexists($dc, 'CDATA[FAIL]')) {
                continue;
            }

            $content .= $date . " 账单\r\n\r\n";
            $content .= $dc . "\r\n\r\n";
        }

        $content = "\xEF\xBB\xBF".$content;

        $file = time() . ".csv";
        header("Content-type: application/octet-stream ");
        header("Accept-Ranges: bytes ");
        header("Content-Disposition: attachment; filename={$file}");
        header("Expires: 0 ");
        header('Content-Encoding: UTF8');
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0 ");
        header("Pragma: public ");
        die($content);
    }

    private function downloadday($date, $row, $wechat, $type)
    {


        $url = 'https://api.mch.weixin.qq.com/pay/downloadbill';
        $pars = array();
        $pars['appid'] = $row['key'];
        $pars['mch_id'] = $wechat['mchid'];
        $pars['nonce_str'] = random(8);
        $pars['device_info'] = "ewei_shopv2";
        $pars['bill_date'] = $date;
        $pars['bill_type'] = $type;
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key=" . $wechat['apikey'];
        $pars['sign'] = strtoupper(md5($string1));
        $xml = array2xml($pars);
        $extras = array();
        load()->func('communication');
        $resp = ihttp_request($url, $xml, $extras);
        if (strexists($resp['content'], 'No Bill Exist')) {
            return error(-2, '未搜索到任何账单');
        }
        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        } else {
            return $resp['content'];
        }
    }

    public function closeOrder($out_trade_no = '')
    {

        global $_W, $_GPC;

        $setting = uni_setting($_W['uniacid'], array('payment'));
        if (!is_array($setting['payment'])) {
            return error(1, '没有设定支付参数');
        }

        $wechat = $setting['payment']['wechat'];
        $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
        $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));


        $url = 'https://api.mch.weixin.qq.com/pay/closeorder';
        $pars = array();
        $pars['appid'] = $row['key'];
        $pars['mch_id'] = $wechat['mchid'];
        $pars['nonce_str'] = random(8);
        $pars['out_trade_no'] = $out_trade_no;

        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {

            $string1 .= "{$k}={$v}&";
        }
        //$string1 =rtrim($string1,'&');
        $string1 .= "key=" . $wechat['apikey'];
        $pars['sign'] = strtoupper(md5($string1));

        $xml = array2xml($pars);

        load()->func('communication');
        $resp = ihttp_post($url, $xml);

        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            if ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS') {
                return true;
            } else {
                if ($arr['return_msg'] == $arr['err_code_des']) {
                    $error = $arr['return_msg'];
                } else {
                    $error = $arr['return_msg']. " | " . $arr['err_code_des'];
                }
                return error(-2, $error);
            }
        }
    }

    public function isWeixinPay($out_trade_no,$money=0, $app=false) {

		global $_W, $_GPC;


		$setting = uni_setting($_W['uniacid'], array('payment'));
		if (!is_array($setting['payment'])) {
			return error(1, '没有设定支付参数');
		}
        $pay = m('common')->getSysset('pay');
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);
        if (!empty($pay['weixin_sub'])){
            $wechat = array(
                'appid'=>$sec['appid_sub'],
                'mchid'=>$sec['mchid_sub'],
                'sub_appid'=>!empty($sec['sub_appid_sub']) ? $sec['sub_appid_sub'] : '',
                'sub_mch_id'=>$sec['sub_mchid_sub'],
                'apikey' => $sec['apikey_sub']
            );
            $row = array('key'=>$sec['appid_sub']);
        }else{
            $wechat = $setting['payment']['wechat'];
            $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
            $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
        }

         if($app){
             $wechat = array(
                 'version'=>1,
                 'apikey'=>$sec['app_wechat']['apikey'],
                 'signkey'=>$sec['app_wechat']['apikey'],
                 'appid'=>$sec['app_wechat']['appid'],
                 'mchid'=>$sec['app_wechat']['merchid']
             );
         }

		$url = 'https://api.mch.weixin.qq.com/pay/orderquery';
		$pars = array();
		$pars['appid'] = $app ? $wechat['appid'] : $row['key'];
		$pars['mch_id'] = $wechat['mchid'];
		$pars['nonce_str'] = random(8);
		$pars['out_trade_no'] = $out_trade_no;

        if (!empty($pay['weixin_sub']) && !is_h5app()){
            $pars['sub_mch_id'] = $wechat['sub_mch_id'];
        }

		ksort($pars, SORT_STRING);
		$string1 = '';
		foreach ($pars as $k => $v) {

			$string1 .= "{$k}={$v}&";
		}
		//$string1 =rtrim($string1,'&');
		$string1 .= "key=" . $wechat['apikey'];
		$pars['sign'] = strtoupper(md5($string1));

		$xml = array2xml($pars);

		load()->func('communication');
		$resp = ihttp_post($url, $xml);

		if (is_error($resp)) {
			return error(-2, $resp['message']);
		}
		if (empty($resp['content'])) {
			return error(-2, '网络错误');
		} else {
			$arr = json_decode(json_encode((array) simplexml_load_string($resp['content'])), true);
			$xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
			$dom = new \DOMDocument();
			if ($dom->loadXML($xml)) {
				$xpath = new \DOMXPath($dom);
				$code = $xpath->evaluate('string(//xml/return_code)');
				$ret = $xpath->evaluate('string(//xml/result_code)');
				$trade_state = $xpath->evaluate('string(//xml/trade_state)');
				if (strtolower($code) == 'success' && strtolower($ret) == 'success' && strtolower($trade_state) == 'success') {
					
					$total_fee = intval( $xpath->evaluate('string(//xml/total_fee)') ) / 100;
					if($total_fee!=$money){
						//判断金额
						return error(-1, '金额出错');
					}
					
					return true;
				} else {

					if( $xpath->evaluate('string(//xml/return_msg)') == $xpath->evaluate('string(//xml/err_code_des)')){
					        $error = $xpath->evaluate('string(//xml/return_msg)') ;
					}
					else{
					        $error = $xpath->evaluate('string(//xml/return_msg)') . " | " . $xpath->evaluate('string(//xml/err_code_des)');
					}
					return error(-2, $error);
				}
			} else {
				return error(-1, '未知错误');
			}
		}
	}

    public function isWeixinPayBorrow($out_trade_no,$money = 0) {

        global $_W, $_GPC;

        $out_trade_no = $out_trade_no.'_borrow';
        $pay = m('common')->getSysset('pay');
        $sec = m('common')->getSec();
        $sec = iunserializer($sec['sec']);

        if (!empty($pay['weixin_jie_sub'])){
            $wechat = array(
                'sub_appid'=>!empty($sec['sub_appid_jie_sub']) ? $sec['sub_appid_jie_sub'] : '',
                'sub_mch_id'=>$sec['sub_mchid_jie_sub'],
            );
            $sec['appid'] = $sec['appid_jie_sub'];
            $sec['mchid'] = $sec['mchid_jie_sub'];
            $sec['apikey'] = $sec['apikey_jie_sub'];
        }else{
        if(empty($sec['appid']) || empty($sec['mchid']) || empty($sec['apikey'])){
            return error(1, '没有设定支付参数');
        }
        }


        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $pars = array();
        $pars['appid'] = $sec['appid'];
        $pars['mch_id'] = $sec['mchid'];
        $pars['nonce_str'] = random(8);
        $pars['out_trade_no'] = $out_trade_no;

        if (!empty($pay['weixin_jie_sub'])){
            $pars['sub_mch_id'] = $wechat['sub_mch_id'];
        }

        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {

            $string1 .= "{$k}={$v}&";
        }
        //$string1 =rtrim($string1,'&');
        $string1 .= "key=" . $sec['apikey'];
        $pars['sign'] = strtoupper(md5($string1));

        $xml = array2xml($pars);

        load()->func('communication');
        $resp = ihttp_post($url, $xml);

        if (is_error($resp)) {
            return error(-2, $resp['message']);
        }
        if (empty($resp['content'])) {
            return error(-2, '网络错误');
        } else {
            $arr = json_decode(json_encode((array) simplexml_load_string($resp['content'])), true);
            $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
            $dom = new \DOMDocument();
            if ($dom->loadXML($xml)) {
                $xpath = new \DOMXPath($dom);
                $code = $xpath->evaluate('string(//xml/return_code)');
                $ret = $xpath->evaluate('string(//xml/result_code)');
                $trade_state = $xpath->evaluate('string(//xml/trade_state)');
                if (strtolower($code) == 'success' && strtolower($ret) == 'success' && strtolower($trade_state) == 'success') {

                    $total_fee = intval( $xpath->evaluate('string(//xml/total_fee)') ) / 100;
                    if($total_fee!=$money){
                        //判断金额
                        return error(-1, '金额出错');
                    }

                    return true;
                } else {

                    if( $xpath->evaluate('string(//xml/return_msg)') == $xpath->evaluate('string(//xml/err_code_des)')){
                        $error = $xpath->evaluate('string(//xml/return_msg)') ;
                    }
                    else{
                        $error = $xpath->evaluate('string(//xml/return_msg)') . " | " . $xpath->evaluate('string(//xml/err_code_des)');
                    }
                    return error(-2, $error);
                }
            } else {
                return error(-1, '未知错误');
            }
        }
    }


    function isAlipayNotify($gpc)
    {
        global $_W;

        $notify_id = trim($gpc['notify_id']);
        $notify_sign = trim($gpc['sign']);

        if (empty($notify_id) || empty($notify_sign)) {
            return false;
        }
        $setting = uni_setting($_W['uniacid'], array('payment'));
        if (!is_array($setting['payment'])) {
            return false;
        }
        $alipay = $setting['payment']['alipay'];

        //检查签名
        $params = array(
            'body' => $gpc['body'],
            'is_success' => $gpc['is_success'],
            'notify_id' => $gpc['notify_id'],
            'notify_time' => $gpc['notify_time'],
            'notify_type' => $gpc['notify_type'],
            'out_trade_no' => $gpc['out_trade_no'],
            'payment_type' => $gpc['payment_type'],
            'seller_id' => $gpc['seller_id'],
            'service' => $gpc['service'],
            'subject' => $gpc['subject'],
            'total_fee' => $gpc['total_fee'],
            'trade_no' => $gpc['trade_no'],
            'trade_status' => $gpc['trade_status'],
        );
        ksort($params, SORT_STRING);
        $string1 = '';
        foreach ($params as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 = rtrim($string1, '&') . $alipay['secret'];
        $sign = strtolower(md5($string1));

        if ($notify_sign != $sign) {
            return false;
        }

        //检查notifyid
        $url = "https://mapi.alipay.com/gateway.do?service=notify_verify&partner=".$alipay['partner']."&notify_id=".$notify_id;
        $resp = @file_get_contents($url);
        return preg_match("/true$/i", $resp);
    }

    public function RSAVerify($return_data, $public_key, $ksort=true){

        if(empty($return_data) || !is_array($return_data)){
            return false;
        }

        $public_key = m('common')->chackKey($public_key);
        $pkeyid = openssl_pkey_get_public($public_key);

        if(empty($pkeyid)){
            return false;
        }
        $rsasign = $return_data['sign'];
        unset($return_data['sign'], $return_data['sign_type']);

        if($ksort){
            ksort($return_data);
        }

        if(is_array($return_data) && !empty($return_data)){
            $strdata = "";
            foreach ($return_data as $k=>$v){
                if (empty($v)) {
                    continue;
                }
                if (is_array($v)){
                    $strdata .= "{$k}=".json_encode($v)."&";
                }else{
                    $strdata .= "{$k}={$v}&";
                }
            }
        }
        $strdata = trim($strdata,'&');
        $rsasign =str_replace(" ", '+', $rsasign);
        $rsasign = base64_decode($rsasign);
        $rsaverify = openssl_verify($strdata, $rsasign, $pkeyid);
        openssl_free_key($pkeyid);
        return $rsaverify;
    }

}
