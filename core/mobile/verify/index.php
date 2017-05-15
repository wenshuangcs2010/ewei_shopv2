<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends MobilePage {

	function qrcode() {
		global $_W, $_GPC;
		$orderid = intval($_GPC['id']);
		
		$order=pdo_fetch("SELECT sendtime,verifytype,verifycode,isverify,dispatchtype from ".tablename("ewei_shop_order")." where id=:id",array(":id"=>$orderid));
		$verifycode = $order['verifycode'];
		$sendtime=$order['sendtime'];
		if(!empty($order['sendtime'])){
			if(time()-$sendtime>600){
				$query = array('id' => $orderid, 'verifycode' => $order['verifycode'],'createtime'=>$order['sendtime']);
				$url = mobileUrl('verify/detail', $query, true);
				
				$file = md5( base64_encode($url) ).".jpg";
				//var_dump($file);
				$dirfiles=IA_ROOT.'/addons/ewei_shopv2/data/qrcode/'.$_W['uniacid'].'/'.$file;
				if (file_exists($dirfiles)) {
					unlink($dirfiles);
				}
			
				$verify=$this->createverifycode($order);
				$verifycode=$verify['verifycode'];
				$verifyinfo=iserializer($verify['verifyinfo']);
				//die();
				$sendtime=time();
				
				$verifycodes=implode('', $verify['verifycodes']);
				$updatedata=array("sendtime"=>$sendtime,'verifycode'=>$verifycode,'verifycodes'=>$verifycodes,'verifyinfo'=>$verifyinfo);
				pdo_update("ewei_shop_order",$updatedata,array("id"=>$orderid));
			}
		}else{
			$sendtime=time();
			pdo_update("ewei_shop_order",array("sendtime"=>$sendtime),array("id"=>$orderid));
		}
		$query = array('id' => $orderid, 'verifycode' => $verifycode,'createtime'=>$sendtime);
		//var_dump($query);
		$url = mobileUrl('verify/detail', $query, true);
		
		show_json(1, array('url' => m('qrcode')->createQrcode($url)));
	}
	function createverifycode($order){
		global $_W, $_GPC;
		extract($order);
		 if ($isverify) {
                if ($verifytype == 0 || $verifytype == 1) {
                    //一次核销+ 按次核销（一个码 )
                    $verifycode = random(8, true);
                    while (1) {
                        $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where verifycode=:verifycode and uniacid=:uniacid limit 1', array(':verifycode' => $verifycode, ':uniacid' => $_W['uniacid']));
                        if ($count <= 0) {
                            break;
                        }
                        $verifycode = random(8, true);
                    }
                } else if ($verifytype == 2) {
                    //按码核销
                    $totaltimes = intval($allgoods[0]['total']);
                    if ($totaltimes <= 0) {
                        $totaltimes = 1;
                    }
                    for ($i = 1; $i <= $totaltimes; $i++) {

                        $verifycode = random(8, true);
                        while (1) {
                            $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where concat(verifycodes,\'|\' + verifycode +\'|\' ) like :verifycodes and uniacid=:uniacid limit 1', array(':verifycodes' => "%{$verifycode}%", ':uniacid' => $_W['uniacid']));
                            if ($count <= 0) {
                                break;
                            }
                            $verifycode = random(8, true);
                        }
                        $verifycodes[] = "|" . $verifycode . "|";
                        $verifyinfo[] = array(
                            'verifycode' => $verifycode,
                            'verifyopenid' => '',
                            'verifytime' => 0,
                            'verifystoreid' => 0
                        );
                    }
                }
            } else if ($dispatchtype) {
                //自提码
                $verifycode = random(8, true);
                while (1) {
                    $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where verifycode=:verifycode and uniacid=:uniacid limit 1', array(':verifycode' => $verifycode, ':uniacid' => $_W['uniacid']));
                    if ($count <= 0) {
                        break;
                    }
                    $verifycode = random(8, true);
                }
            }
            return array('verifycode'=>$verifycode,'verifycodes'=>$verifycodes,"verifyinfo"=>$verifyinfo);
	}
	function select() {
		global $_W, $_GPC;
		$orderid = intval($_GPC['id']);
		$verifycode = trim($_GPC['verifycode']);
		if (empty($verifycode) || empty($orderid)) {
			show_json(0);
		}
		$order = pdo_fetch("select id,verifyinfo from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid limit 1'
			, array(':id' => $orderid, ':uniacid' => $_W['uniacid']));
		if (empty($order)) {
			show_json(0);
		}
		$verifyinfo = iunserializer($order['verifyinfo']);
		foreach ($verifyinfo as &$v) {
			if ($v['verifycode'] == $verifycode) {
				if (!empty($v['select'])) {
					$v['select'] = 0;
				} else {
					$v['select'] = 1;
				}
			}
		}
		unset($v);
		pdo_update('ewei_shop_order', array('verifyinfo' => iserializer($verifyinfo)), array('id' => $orderid));
		show_json(1);
	}

	function check() {
		global $_W, $_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];

		$orderid = intval($_GPC['id']);
		$order = pdo_fetch("select id,status,isverify,verified from " . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
			, array(':id' => $orderid, ':uniacid' => $uniacid, ':openid' => $openid));
		if (empty($order)) {
			show_json(0);
		}
		if (empty($order['isverify'])) {
			show_json(0);
		}
		if ($order['verifytype'] == 0) {
			if (empty($order['verified'])) {
				show_json(0);
			}
		}

		show_json(1);
	}

	function detail() {

		global $_W, $_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['id']);
		$times=intval($_GPC['createtime']);
		if(time()-$times>600){
			$this->message("二维码已经过期");
		}
		$data  = com('verify')->allow($orderid);
		if(is_error($data)){
			$this->message($data['message']);
		}
		extract($data);
		include $this->template();
	}

	function complete() {
		global $_W, $_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$orderid = intval($_GPC['id']);
		$times = intval($_GPC['times']);
		com('verify')->verify($orderid,$times);
		show_json(1);
	}
	
	function success(){
		global $_W,$_GPC;
		$id =intval($_GPC['orderid']);
		$times = intval($_GPC['times']);
		$this->message(array('title'=>'操作完成','message'=>'您可以退出浏览器了'),"javascript:WeixinJSBridge.call(\"closeWindow\");",'success');
	}
	

}
