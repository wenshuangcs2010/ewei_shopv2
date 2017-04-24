<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class ComposterModel extends PluginModel {
	public function getFixedTicket($posterid, $member, $uniaccount,$groupid,$type=0) {
		global $_W, $_GPC;
		if($type==0){
			$scene_str = md5("ewei_shop_poster:{$_W['uniacid']}:{$posterid}:{$member['openid']}:{$groupid}");
			$bb = "{\"action_info\":{\"scene\":{\"scene_str\":\"" . $scene_str . "\"} },\"action_name\":\"QR_LIMIT_STR_SCENE\"}";
		}
		if($type==1){
			$scene_id=$_W['uniacid'].$member['id'].$posterid.$groupid;
			$bb = "{\"expire_seconds\":2592000,\"action_name\":\"QR_SCENE\",\"action_info\":{\"scene\":{\"scene_id\":\"" . $scene_id . "\"} }}";
		}
		$token = $uniaccount->fetch_token();
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $token;
		$ch1 = curl_init();
		curl_setopt($ch1, CURLOPT_URL, $url);
		curl_setopt($ch1, CURLOPT_POST, 1);
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch1, CURLOPT_POSTFIELDS, $bb);
		$c = curl_exec($ch1);
		$result = @json_decode($c, true);
		if (!is_array($result)) {
			return false;
		}

		if (!empty($result['errcode'])) {
			return error(-1, $result['errmsg']);
		}
		$ticket = $result['ticket'];
		return array('barcode' => json_decode($bb, true), 'ticket' => $ticket);
	}
	public function getQRByTicket($ticket = '') {
		global $_W;

		if (empty($ticket)) {
			return false;
		}
		$sql='select * from ' . tablename('ewei_shop_composter_qr') . ' where ticket=:ticket and uniacid=:acid  limit 1';
		$params=array(':ticket' => $ticket, ':acid' => $_W['uniacid']);
		$qrs = pdo_fetchall($sql,$params);
		$count = count($qrs);
		if ($count <= 0) {
			return false;
		}
		if ($count == 1) {
			return $qrs[0];
		}
		return false;
	}

	
}
