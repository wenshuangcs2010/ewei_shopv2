<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN .'creditshop/core/page_mobile.php';
class Exchange_EweiShopV2Page extends CreditshopMobilePage {
	
	function main(){
		global $_W, $_GPC;
		
		include $this->template();
	}
	
	function check(){
		global $_W, $_GPC;
		
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$id = intval($_GPC['id']);
		
		$log = pdo_fetch("select id,status from " . tablename('ewei_shop_creditshop_log') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
        , array(':id' => $id, ':uniacid' => $uniacid, ':openid' => $openid));
	    
	    if (!empty($log) && $log['status'] == 3) {
	        show_json(1);
	    }
	    show_json(0);
	}
	
	function qrcode(){
		global $_W, $_GPC;
		
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$id = intval($_GPC['id']);
		
		 $log = pdo_fetch("select id,eno from " . tablename('ewei_shop_creditshop_log') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1'
        , array(':id' => $id, ':uniacid' => $uniacid, ':openid' => $openid));
	     
	    if (empty($log)) {
	        show_json(0, '兑换记录未找到!');
	    }
	    $qrcode = $this->model->createQrcode($id);
	    show_json(1, array('qrcode' => $qrcode, 'eno' => $log['eno']));
	}
	
	function exchange(){
		global $_W, $_GPC;
		
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$id = intval($_GPC['id']);
		
		$saler = pdo_fetch('select * from ' . tablename('ewei_shop_saler') . ' where openid=:openid and uniacid=:uniacid limit 1', array(
            ':uniacid' => $_W['uniacid'], ':openid' => $openid
        ));
        if (empty($saler)) {
            show_json(0, '您无兑换权限!');
        }
		$log = pdo_fetch("select * from " . tablename('ewei_shop_creditshop_log') . ' where id=:id and uniacid=:uniacid limit 1'
        , array(':id' => $id, ':uniacid' => $uniacid));
		
		if (empty($log)) {
            show_json(0, '未找到要兑换记录!');
        }
        if (empty($log['status'])) {
            show_json(0, '无效兑换记录!');
        }
        if ($log['status'] >= 3) {
            show_json(0, '此记录已兑换过了!');
        }
		$member = m('member')->getMember($log['openid']);
        $goods = $this->model->getGoods($log['goodsid'], $member);
		if($goods['isendtime']==1 && time()>$goods['endtime']){
			show_json(0, '超出使用有效期，无法进行兑换!');
		}
		if (empty($goods['id'])) {
            show_json(0, '商品记录不存在!');
        }
        if (empty($goods['isverify'])) {
            show_json(0, '此商品不支持线下兑换!');
        }
        if (!empty($goods['type'])) {
            if ($log['status'] <= 1) {
                show_json(0, '未中奖，不能兑换!');
            }
        }
		if ($goods['money'] > 0 && empty($log['paystatus'])) {
            show_json(0, '未支付，无法进行兑换!');
        }
        if ($goods['dispatch'] > 0 && empty($log['dispatchstatus'])) {
            show_json(0, '未支付运费，无法进行兑换!');
        }
		if($goods['isendtime']==1 && $goods['currenttime']>$goods['endtime']){
			show_json(0, '超出使用有效期，无法进行兑换!');
		}
        $stores = explode(",", $goods['storeids']);
		if (!empty($storeids)) {
            //全部门店
            if (!empty($saler['storeid'])) {
                if (!in_array($saler['storeid'], $storeids)) {
                    show_json(0, '您无此门店的兑换权限!');
                }
            }
        }

        $time = time();
         pdo_update('ewei_shop_creditshop_log', array('status' => 3, 'usetime' => $time, 'verifyopenid' => $openid), array('id' => $log['id']));

        //模板消息
         $this->model->sendMessage($id);
        
        show_json(1,"【".$goods['title']."】兑换成功!");
	}
	
}
    