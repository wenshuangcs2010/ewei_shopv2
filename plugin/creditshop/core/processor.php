<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require IA_ROOT . '/addons/ewei_shopv2/defines.php';
require EWEI_SHOPV2_INC . '/plugin_processor.php';

class CreditshopProcessor extends PluginProcessor {

    public function __construct() {
        parent::__construct('creditshop');
		$this->sessionkey = EWEI_SHOPV2_PREFIX . 'order_wechat_verify_info';
		$this->codekey = EWEI_SHOPV2_PREFIX . 'order_wechat_verify_code';
    } 

    public function respond($obj = null) {
        global $_W;
        $message = $obj->message;
        $openid = $obj->message['from'];
        $content = $obj->message['content'];
        $msgtype = strtolower($message['msgtype']);
        $event = strtolower($message['event']);
		@session_start();


        if ($msgtype == 'text' || $event == 'click') {
            
            $saler = pdo_fetch('select * from ' . tablename('ewei_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1', array(
                ':uniacid' => $_W['uniacid'], ':openid' => $openid
            ));
            if (empty($saler)) {
                return $this->responseEmpty();
            }
            if (!$obj->inContext) {
                $obj->beginContext();
                return $obj->respText('请输入8位及以上积分商城兑换码:');
            } else if ($obj->inContext) {
				if (is_numeric($content)) {
					if (strlen($content) >= 8) {
						$log = pdo_fetch('select * from ' . tablename('ewei_shop_creditshop_log') . ' where eno=:eno and uniacid=:uniacid  limit 1', array(':eno' => $content, ':uniacid' => $_W['uniacid']));
						$member = m('member')->getMember($log['openid']);
						$goods = p('creditshop')->getGoods($log['goodsid'], $member);
						if(empty($log)){
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('未找到兑换码，请重新输入! 退出请回复n。');
						}
						/*if (empty($log['status'])) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('无效兑换记录，请重新输入! 退出请回复n。');
						}
						if ($log['status'] >= 3) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('此记录已兑换过了，请重新输入! 退出请回复n。');
						}
						if (empty($goods['id'])) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('商品记录不存在，请重新输入! 退出请回复n。');
						}
						if (empty($goods['isverify'])) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('此商品不支持线下兑换，请重新输入! 退出请回复n。');
						}
						if (!empty($goods['type'])) {
							if ($log['status'] <= 1) {
								unset($_SESSION[$this->sessionkey]);
								return $obj->respText('此记录未中奖，不能兑换，请重新输入! 退出请回复n。');
							}
						}
						if ($goods['money'] > 0 && empty($log['paystatus'])) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('未支付，无法进行兑换!');
						}
						if ($goods['dispatch'] > 0 && empty($log['dispatchstatus'])) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText('未支付运费，无法进行兑换!');
						}

						$stores = explode(",",$goods['storeids']);
						if (!empty($storeids)) {
							//全部门店
							if (!empty($saler['storeid'])) {
								if (!in_array($saler['storeid'], $storeids)) {
									unset($_SESSION[$this->sessionkey]);
									return $obj->respText('您无此门店的兑换权限，请重新输入! 退出请回复n。');
								}
							}
						}*/

						$allow = p('creditshop')->allow($log['id'], 0, $openid);
						if (is_error($allow)) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText($allow['message'] . " 请输入其他核销码，退出请回复 n。");
						}
						extract($allow);

						$allow = p('creditshop')->allow($log['id'], 0, $openid);
						if (is_error($allow)) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText($allow['message'] . " 请输入其他核销码，退出请回复 n。");
						}
						extract($allow);

						$_SESSION[$this->sessionkey] = json_encode(array(
							'logid' => $log['id'],
							'openid' => $log['openid'],
							'isverify' => $goods['isverify'],
							'verifytype' => $goods['verifytype'],
							'lastverifys' => $allow['lastverifys']
						));
						$str = '';
						$str .= "您正在对积分商城的订单进行线下兑换\r\n";
						$str .= "\r\n订单号: {$log['logno']}\r\n";
						$str .= "商品: {$goods['title']}\r\n";
						if($goods['acttype']==0){
							$str .= "价格: {$goods['credit']}积分+{$goods['money']}元\r\n";
						}
						elseif($goods['acttype']==1){
							$str .= "价格: {$goods['credit']}积分\r\n";
						}
						elseif($goods['acttype']==2){
							$str .= "价格: {$goods['money']}元\r\n";
						}
						elseif($goods['acttype']==3){
							$str .= "价格: 免费\r\n";
						}
						$str .= "\r\n信息正确请回复 y 进行兑换确认，回复 n 退出。";

						return $obj->respText($str);
					} else {
						if (isset($_SESSION[$this->sessionkey])) {
							$session = json_decode($_SESSION[$this->sessionkey], true);
							if ($session['verifytype'] == 1) {
								if (intval($content) <= 0) {
									return $obj->respText('订单最少核销 1 次!');
								}
								if (intval($content) > $session['lastverifys']) {
									return $obj->respText("此订单最多核销 {$session['lastverifys']} 次!");
								}
								$result = p('creditshop')->verify($session['logid'], intval($content), '', $openid);
								if (is_error($result)) {
									unset($_SESSION[$this->sessionkey]);
									return $obj->respText($allow['message'] . " 请输入其他核销码，退出请回复 n。");
								}
								$obj->endContext();
								return $obj->respText('核销成功!');
							}
						}
						return $obj->respText('请输入8位及以上积分商城兑换码:');
					}
				} else if (strtolower($content) == 'y') {
					if (isset($_SESSION[$this->sessionkey])) {
						$session = json_decode($_SESSION[$this->sessionkey], true);
						if ($session['verifytype'] == 1) {
							return $obj->respText('请输入核销次数:');
						}
						$result = p('creditshop')->verify($session['logid'], 0, $session[$this->codekey], $openid);
						if (is_error($result)) {
							unset($_SESSION[$this->sessionkey]);
							return $obj->respText($result['message'] . " 请输入其他核销码，退出请回复 n。");
						}

						$time = time();
						//pdo_update('ewei_shop_creditshop_log', array('status' => 3, 'usetime' => $time, 'verifyopenid' => $openid), array('id' => $session['logid']));
						p('creditshop')->sendMessage($session['logid']);
						$obj->endContext();
						return $obj->respText('兑换成功!');
					}
					return $obj->respText('请输入8位及以上数字兑换码:');
				} else {
					@session_start();
					unset($_SESSION[$this->sessionkey]);
					unset($_SESSION[$this->codekey]);
					$obj->endContext();
					return $obj->respText("退出成功.");
				}
            }
        }
    }

    private function responseEmpty() {
        ob_clean();
        ob_start();
        echo '';
        ob_flush();
        ob_end_flush();
        exit(0);
    }

}
