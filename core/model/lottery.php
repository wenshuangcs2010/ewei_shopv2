<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Lottery_EweiShopV2Model {

	function gethas_changes($lottery_id){//检查用户抽奖次数

	}


	//新用户增加抽奖次数
	function getUserLottery($lottery_id,$openid,$num=0){
		global $_W;
		$params=array(":uniacid"=>$_W['uniacid'],":openid"=>$openid,':lottery_id'=>$lottery_id);
		$userlottery=pdo_fetch("SELECT * from ".tablename("ewei_shop_user_lottery")." where uniacid=:uniacid and openid=:openid and lottery_id=:lottery_id",$params);

		//pdo_debug();
		//var_dump($userlottery);
		if(empty($userlottery)){
			$userlottery=array(
				'uniacid'=>$_W['uniacid'],
				'lottery_id'=>$lottery_id,
				'total_num'=>0,
				'today_num'=>$num,
				'openid'=>$openid,
				);
			pdo_insert("ewei_shop_user_lottery",$userlottery);
			$id=pdo_insertid();
			$userlottery['id']=$id;
		}
		return $userlottery;
	}
	//获取活动内容
	function getLottery($lottery_id){
		global $_W;
		$params=array(":uniacid"=>$_W['uniacid'],":id"=>$lottery_id);
		$lottery=pdo_fetch("SELECT * from ".tablename("ewei_shop_bigwheel_config")." where uniacid=:uniacid and status=1 and id=:id ",$params);
		return $lottery;
	}
	//检测活动是否开始
	function checkLotterystatus($lottery){
		global $_W;
		$starttime=$lottery['starttime'];
		$endtime=$lottery['endtime'];
		$newtime=$_W['timestamp'];
		if($newtime<$starttime){
			return array("status"=>-1,"message"=>"抽奖活动还未开始");
		}
		if($newtime>$endtime){
			return array("status"=>-1,"message"=>"抽奖活动已经结束");
		}
		return array("status"=>1,"message"=>"");
	}
	//获取用户中奖记录
	function getUserPrize($lottery_id,$opneid="",$bool=false){
		global $_W;	
		$params[':lottery_id']=$lottery_id;
		$params[':uniacid']=$_W['uniacid'];
		if(!$bool){
			$content=" where lottery_id=:lottery_id and uniacid=:uniacid and is_reward=1 or is_reward=2";
		}else{
			$content=" where lottery_id=:lottery_id and uniacid=:uniacid";
		}
		$sql="SELECT * FROM ".tablename("ewei_shop_lottery_log").$content." order by addtime desc";
		if(!empty($openid)){
			$sql.=" and openid=:openid";
			$params[':openid']=$opneid;
		}
		return pdo_fetchall($sql,$params);
	}

	//更新抽奖次数信息
	function updateUserLottery($data,$openid,$lottery_id){
		global $_W;
		return pdo_update("ewei_shop_user_lottery",$data,array("uniacid"=>$_W['uniacid'],"openid"=>$openid,'lottery_id'=>$lottery_id));
	}

	public function reward($poster,$openid,$title,$lottery_id){
        if(empty($poster)||empty($openid)){
            return false;
        }
        global $_W;
        //载入日志函数

        //积分
        if (isset($poster['credit']) && $poster['credit'] > 0) {
            m('member')->setCredit($openid, 'credit1', $poster['credit'], array(0, '抽奖获得+' . $poster['credit']));
        }
        //现金
        if (isset($poster['money']) && $poster['money']['num'] > 0) {
            // $val['money']['type'] 0:余额1：微信
            $pay = $poster['money']['num'];
            if ($poster['money']['type'] == 1) {
                $pay *= 100;
            }
            m('finance')->pay($openid, $poster['money']['type'], $pay, '', '抽奖获得', false);
        }
        //红包
        if (isset($poster['bribery']) && $poster['bribery'] > 0) {
            $setting = uni_setting($_W['uniacid'], array('payment'));
            if (!is_array($setting['payment'])) {
                return error(1, '没有设定支付参数');
            }
            $sec = m('common')->getSec();
            $sec = iunserializer($sec['sec']);
            $certs = $sec;
            $wechat = $setting['payment']['wechat'];
            $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
            $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));

            //红包参数
            $tid = rand(1, 1000) . time() . rand(1, 10000);//订单编号
            $params = array(
                'openid' => $openid,
                'tid' => $tid,
                'send_name' => '推荐奖励',
                'money' => $poster['bribery']['num'],
                'wishing' => '推荐奖励',
                'act_name' => $title,
                'remark' => '推荐奖励',
            );
            //微信接口参数
            $wechat = array(
                'appid' => $row['key'],
                'mchid' => $wechat['mchid'],
                'apikey' => $wechat['apikey'],
                'certs' => $certs
            );
            $err = m('common')->sendredpack($params, $wechat);
            dump($err);
            if (!is_error($err)) {
                $reward = $poster;
                $reward['bribery']['briberyOrder'] = $tid;
                $reward = serialize($reward);
                $upgrade = array(
                    'lottery_data' => $reward
                );
                $log_id = pdo_fetchcolumn('SELECT log_id FROM '.tablename('ewei_shop_lottery_log').' WHERE uniacid=:uniacid AND join_user=:join_user AND lottery_id=:lottery_id AND is_reward=1 ORDER BY addtime DESC LIMIT 1',array(':uniacid'=>$_W['uniacid'],':join_user'=>$openid,':lottery_id'=>$lottery_id));
                pdo_update('ewei_shop_lottery_log', $upgrade, array('log_id' => $log_id));
            } else {//红包发送失败
                dump($err);
                show_json(0,'WechatRedError');
            }
        }
        //优惠券
        if (isset($poster['coupon']) && !empty($poster['coupon'])) {
            //赠送优惠券
            $cansendreccoupon = false;
            $plugin_coupon = com('coupon');
            unset($poster['coupon']['total']);
            foreach ($poster['coupon'] as $k => $v) {
                if ($plugin_coupon) {
                    //推荐者奖励
                    if (!empty($v['id']) && $v['couponnum'] > 0) {
                        $reccoupon = $plugin_coupon->getCoupon($v['id']);
                        if (!empty($reccoupon)) {
                            $cansendreccoupon = true;
                        }
                    }
                }

                //优惠券通知
                if ($cansendreccoupon) {
                    //发送优惠券
                    $plugin_coupon->taskposter(array('openid'=>$openid), $v['id'], $v['couponnum'],8);
                }
            }
        }
    }

    //查询是有中奖商品
    function show_goods($openid,$goodsid,$log_id){
        global $_W;
        $params[':uniacid']=$_W['uniacid'];
        $params[':openid']=$_W['openid'];
        $params[':id']=$log_id;
        $reward=pdo_fetch("SELECT * from".tablename("ewei_shop_lottery_log")." where  is_reward=1 and openid=:openid and uniacid=:uniacid and id=:id",$params);
       
        if(!empty($reward)){
             $lottery_data=unserialize($reward['lottery_data']);
             if(isset($lottery_data['goods'])){
                $goods=$lottery_data['goods'];
            }elseif(isset($lottery_data['hxgoods'])){
                $goods=$lottery_data['hxgoods'];
            }

            foreach($goods as $g){
                if($g['id']==$goodsid){
                    return $g;
                }
            }
        }
        return false;
    }
      public function lottery_complain($reward){
        if(isset($reward['credit'])){
            return '积分:'.$reward['credit'];
        }
        if(isset($reward['money'])){
            return '奖金:'.$reward['money']['num'].'元';
        }
        if(isset($reward['bribery'])){
            return '红包:'.$reward['bribery']['num'].'元';
        }
        if(isset($reward['goods'])){
//            dump($reward['goods']);
            foreach ($reward['goods'] as $k =>$v) {
                $total = $v['total'];
                break;
            }
            return '特惠商品:'.$total.'个';
        }
        if(isset($reward['coupon'])){
            return '优惠券:'.$reward['coupon']['coupon_num'].'张';
        }
    }

    public function get_lotter_data($reward){
        if(isset($reward['goods'])){
            return 1;
        }
        if(isset($reward['hxgoods'])){
            return 1;
        }
        return 0;
    }
}

