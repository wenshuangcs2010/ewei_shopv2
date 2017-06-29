<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require_once IA_ROOT . '/addons/ewei_shopv2/defines.php';
require_once EWEI_SHOPV2_INC . 'plugin_processor.php';
require_once EWEI_SHOPV2_INC . 'receiver.php';

class PosterProcessor extends PluginProcessor {

	public function __construct() {
		parent::__construct('poster');
	}

	public function respond($obj = null) {
		global $_W;
		$message = $obj->message;
	
		$msgtype = strtolower($message['msgtype']);
		$event = strtolower($message['event']);
	//WeUtility::logging('rule>检查',$event);
		//更新用户信息
		$obj->member = $this->model->checkMember($message['from']);
	

		if ($msgtype == 'text' || $event == 'click') {
			return $this->responseText($obj);
		} else if ($msgtype == 'event') {
			if ($event == 'scan') {

				//扫描
				return $this->responseScan($obj);
			} else if ($event == 'subscribe') {

				//关注
				return $this->responseSubscribe($obj);
			}
		}
	}

	private function responseText($obj) {
		
		global $_W;
		//url调用，避免5秒超时返回
		$timeout = 4;
		load()->func('communication');
		$url = mobileUrl('poster/build',array('timestamp'=>TIMESTAMP),true);
		$resp = ihttp_request($url, array('openid' => $obj->message['from'], 'content' => urlencode($obj->message['content'])), array(), $timeout);
	
		return $this->responseEmpty();
	}

	private function responseEmpty() {
		ob_clean();
		ob_start();
		echo '';
		ob_flush();
		ob_end_flush();
		exit(0);
	}

	private function responseDefault($obj) {
		global $_W;
		//未找到推荐人， 查找默认回复信息
		return $obj->respText('感谢您的关注!');
	}

	private function responseScan($obj) {
		global $_W;

		$openid = $obj->message['from'];
		$sceneid = $obj->message['eventkey'];
		$ticket = $obj->message['ticket'];

		if (empty($ticket)) {
			return $this->responseDefault($obj);
		}
		$qr = $this->model->getQRByTicket($ticket);
		if (empty($qr)) {
			return $this->responseDefault($obj);
		}

		$poster = pdo_fetch('select * from ' . tablename('ewei_shop_poster') . ' where type=4 and isdefault=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
		if (empty($poster)) {
			return $this->responseDefault($obj);
		}
		//扫描次数
		$this->model->scanTime($openid, $qr['openid'], $poster);
		//推荐者
		$qrmember = m('member')->getMember($qr['openid']);

		//分销
		$this->commission($poster, $obj->member, $qrmember);

		$url = trim($poster['respurl']);
		if (empty($url)) {
			if ($qrmember['isagent'] == 1 && $qrmember['status'] == 1) {
				$url = mobileUrl('commission/myshop',array('mid'=>$qrmember['id']));
			} else {
				$url = mobileUrl('',array('mid'=>$qrmember['id']));
			}
		}
		if ($poster['resptype'] == '0')
		{
			if(!empty($poster['resptitle'])){
				$news = array(array('title' => $poster['resptitle'], 'description' => $poster['respdesc'], 'picurl' => tomedia($poster['respthumb']), 'url' => $url));
				return $obj->respNews($news);
			}
		}
		if ($poster['resptype'] == '1')
		{
			if(!empty($poster['resptext'])){
				return $obj->respText($poster['resptext']);
			}
		}
		return $this->responseEmpty();
	}

	private function responseSubscribe($obj) {
		global $_W;
		$openid = $obj->message['from'];
		$keys = explode('_', $obj->message['eventkey']);
		$sceneid = isset($keys[1]) ? $keys[1] : '';
		$ticket = $obj->message['ticket'];
		$member = $obj->member;

        $receiver = new Receiver();
		$receiver->saleVirtual($obj);
		if (empty($ticket)) {
			return $this->responseDefault($obj);
		}

		$qr = $this->model->getQRByTicket($ticket);
		if (empty($qr)) {
			return $this->responseDefault($obj);
		}

		$poster = pdo_fetch('select * from ' . tablename('ewei_shop_poster') . ' where `type`=4 and isdefault=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
		if (empty($poster)) {
			return $this->responseDefault($obj);
		}

		if ($member['isnew']) {
			pdo_update('ewei_shop_poster', array('follows' => $poster['follows'] + 1), array('id' => $poster['id']));
		}
		//推荐者
		$qrmember = m('member')->getMember($qr['openid']);
		if($qrmember['groupid']!=0){
			$membergroup=pdo_fetch("SELECT * from ".tablename("ewei_shop_member_group")." where id=:id",array(":id"=>$qrmember['groupid']));
			$data=array();
			if($membergroup['isgroup']==1){
				$data['groupid']=$membergroup['updategroupid'];
			}//自动分组
			if($membergroup['isupmember']==1){
				$data['level']=$membergroup['levelsid'];
			}//自动升级会员
			if(!empty($data)){
				pdo_update("ewei_shop_member",$data,array("openid"=>$openid,'uniacid'=>$_W['uniacid']));
			}
			
			if(!empty($membergroup['entrytext'])){
				$poster['entrytext']=$membergroup['entrytext'];//拦截分组消息
			}
		}
		//检测日志
		$log = pdo_fetch('select * from ' . tablename('ewei_shop_poster_log') . ' where openid=:openid and posterid=:posterid and uniacid=:uniacid limit 1', array(':openid' => $openid, ':posterid' => $poster['id'], ':uniacid' => $_W['uniacid']));
		if (empty($log) && $openid != $qr['openid']) {
			$log = array('uniacid' => $_W['uniacid'], 'posterid' => $poster['id'], 'openid' => $openid, 'from_openid' => $qr['openid'], 'subcredit' => $poster['subcredit'], 'submoney' => $poster['submoney'], 'reccredit' => $poster['reccredit'], 'recmoney' => $poster['recmoney'], 'createtime' => time());
			pdo_insert('ewei_shop_poster_log', $log);
			$log['id'] = pdo_insertid();
			
			//关注者入账描述
			$subpaycontent = $poster['subpaycontent'];
			if (empty($subpaycontent)) {
				$subpaycontent = '您通过 [nickname] 的推广二维码扫码关注的奖励';
			}
			$subpaycontent = str_replace("[nickname]", $qrmember['nickname'], $subpaycontent);

			//推荐者入账描述
			$recpaycontent = $poster['recpaycontent'];
			if (empty($recpaycontent)) {
				$recpaycontent = '推荐 [nickname] 扫码关注的奖励';
			}
			$recpaycontent = str_replace("[nickname]", $member['nickname'], $subpaycontent);

			//第一次扫描,赠送积分
			if ($poster['subcredit'] > 0) {
				//关注者积分
				m('member')->setCredit($openid, 'credit1', $poster['subcredit'], array(0, '扫码关注积分+' . $poster['subcredit']));
			}
			if ($poster['submoney'] > 0) {
				//关注者奖励
				$pay = $poster['submoney'];
				if ($poster['paytype'] == 1) {
					$pay *= 100;
				}
				m('finance')->pay($openid, $poster['paytype'], $pay, '', $subpaycontent,false);
			}

            $reward_totle = !empty($poster['reward_totle'])?json_decode($poster['reward_totle'],true):array();

            $reward_real = pdo_fetch('select sum(reccredit) as reccredit_totle,sum(recmoney) as recmoney_totle,sum(reccouponnum) as reccouponnum_totle  from ' . tablename('ewei_shop_poster_log') . ' where from_openid=:from_openid and posterid=:posterid and uniacid=:uniacid and createtime between :time1 and :time2 limit 1', array(':from_openid' => $qr['openid'], ':posterid' => $poster['id'], ':uniacid' => $_W['uniacid'],':time1'=>strtotime(date('Y-m',time())."-1"),':time2'=>time()));

            if (empty($reward_totle['reccredit_totle']) || intval($reward_totle['reccredit_totle']) > intval($reward_real['reccredit_totle'])){
                if ($poster['reccredit'] > 0) {
                    //推荐者积分
                    m('member')->setCredit($qr['openid'], 'credit1', $poster['reccredit'], array(0, '推荐扫码关注积分+' . $poster['reccredit']));
                }
            }
            if (empty($reward_totle['recmoney_totle']) || floatval($reward_totle['recmoney_totle']) > floatval($reward_real['recmoney_totle'])){
                if ($poster['recmoney'] > 0) {
                    //推荐者钱
                    $pay = $poster['recmoney'];
                    if ($poster['paytype'] == 1) {
                        $pay *= 100;
                    }
                    m('finance')->pay($qr['openid'], $poster['paytype'], $pay, '', $recpaycontent,false);
                }
            }
            
			//赠送优惠券
			$cansendreccoupon =false;
			$cansendsubcoupon =false;
			$plugin_coupon = com('coupon');

			if($plugin_coupon){
				//防止用户通过关注无限制获取优惠劵
				if($qrmember['groupid']!=0){
					if(!empty($membergroup['reccouponid']) && $membergroup['reccouponnum']>0){
						$groupreccoupon = $plugin_coupon->getCoupon($membergroup['reccouponid']);
						if(!empty($groupreccoupon)){
							$upgrade['subcouponid'] = $poster['subcouponid'];
							$upgrade['subcouponnum'] = $poster['subcouponnum'];
							$plugin_coupon->poster($member, $poster['subcouponid'],$poster['subcouponnum']);
						}
					}
				}
				//推荐者奖励
                if (empty($reward_totle['reccouponnum_totle']) || intval($reward_totle['reccouponnum_totle']) > intval($reward_real['reccouponnum_totle'])){
                    if(!empty($poster['reccouponid']) && $poster['reccouponnum']>0){
                        $reccoupon = $plugin_coupon->getCoupon($poster['reccouponid']);
                        if(!empty($reccoupon)){
                            $cansendreccoupon = true;
                        }
                    }
                }
				//关注者奖励
				if(!empty($poster['subcouponid']) && $poster['subcouponnum']>0){
					$subcoupon = $plugin_coupon->getCoupon($poster['subcouponid']);
 					if(!empty($subcoupon)){
						$cansendsubcoupon = true;
					}
				}
			}
			
			if (!empty($poster['subtext'])) {
				//推荐人奖励通知
				$subtext = $poster['subtext'];
				$subtext = str_replace("[nickname]", $member['nickname'], $subtext);
				$subtext = str_replace("[credit]", $poster['reccredit'], $subtext);
				$subtext = str_replace("[money]", $poster['recmoney'], $subtext);
				if($reccoupon){
					$subtext = str_replace("[couponname]", $reccoupon['couponname'], $subtext);
					$subtext = str_replace("[couponnum]", $poster['reccouponnum'], $subtext);
				}

                if (!empty($poster['templateid'])) {
					m('message')->sendTplNotice($qr['openid'], $poster['templateid'], array('first' => array('value' => "推荐关注奖励到账通知", "color" => "#4a5077"), 'keyword1' => array('value' => '推荐奖励', "color" => "#4a5077"), 'keyword2' => array('value' => $subtext, "color" => "#4a5077"), 'remark' => array('value' => "\r\n谢谢您对我们的支持！", "color" => "#4a5077"),), '');
				} else {
					m('message')->sendCustomNotice($qr['openid'], $subtext);
				}
			}

			if (!empty($poster['entrytext'])) {

				//关注者奖励通知
				$entrytext = $poster['entrytext'];
				
				$entrytext = str_replace("[nickname]", $qrmember['nickname'], $entrytext);
				$entrytext = str_replace("[credit]", $poster['subcredit'], $entrytext);
				$entrytext = str_replace("[money]", $poster['submoney'], $entrytext);

				if($subcoupon){
					$entrytext = str_replace("[couponname]", $subcoupon['couponname'], $entrytext);
					$entrytext = str_replace("[couponnum]", $poster['subcouponnum'], $entrytext);
				}
				
				if (!empty($poster['templateid'])) {
					m('message')->sendTplNotice($openid, $poster['templateid'], array('first' => array('value' => "关注奖励到账通知", "color" => "#4a5077"), 'keyword1' => array('value' => '关注奖励', "color" => "#4a5077"), 'keyword2' => array('value' => $entrytext, "color" => "#4a5077"), 'remark' => array('value' => "\r\n谢谢您对我们的支持！", "color" => "#4a5077"),), '');
				} else {
					m('message')->sendCustomNotice($openid, $entrytext);
				}
			}
			
			$upgrade = array();
			if($cansendreccoupon){
				$upgrade['reccouponid'] = $poster['reccouponid'];
				$upgrade['reccouponnum'] = $poster['reccouponnum'];
				$plugin_coupon->poster($qrmember, $poster['reccouponid'],$poster['reccouponnum']);
			}
			if($cansendsubcoupon){
				$upgrade['subcouponid'] = $poster['subcouponid'];
				$upgrade['subcouponnum'] = $poster['subcouponnum'];
				$plugin_coupon->poster($member, $poster['subcouponid'],$poster['subcouponnum']);
			}
			if(!empty($upgrade)){
				pdo_update('ewei_shop_poster_log',$upgrade,array('id'=>$log['id']));
			}
		}


		//分销
		$this->commission($poster, $member, $qrmember);

		$url = trim($poster['respurl']);
		if (empty($url)) {
			if ($qrmember['isagent'] == 1 && $qrmember['status'] == 1) {
				$url = mobileUrl('commission/myshop',array('mid'=>$qrmember['id']));
			} else {
				$url = mobileUrl('',array('mid'=>$qrmember['id']));
			}
		}

		if ($poster['resptype'] == '0')
		{
			if(!empty($poster['resptitle'])){
				$news = array(array('title' => $poster['resptitle'], 'description' => $poster['respdesc'], 'picurl' => tomedia($poster['respthumb']), 'url' => $url));
				return $obj->respNews($news);
			}
		}
		if ($poster['resptype'] == '1')
		{
			if(!empty($poster['resptext'])){
				return $obj->respText($poster['resptext']);
			}
		}
		return $this->responseEmpty();
	
	}

	private function commission($poster, $member, $qrmember) {
		$time = time();

		$p = p('commission');
		if ($p) {
			$cset = $p->getSet();
			if (!empty($cset)) {
				if ($member['isagent'] != 1) {//如果扫码会员不是分销商或准分销商，且没有上线
					if ($qrmember['isagent'] == 1 && $qrmember['status'] == 1) {//如果推荐人分销商
						if (!empty($poster['bedown'])) {//如果扫码成为下线
							if (empty($member['agentid'])) {
								if(empty($member['fixagentid'])){
									//上级是分销商,扫码成为下线，没有上线
									$member['agentid'] = $qrmember['id'];
									$authorid = empty($qrmember['isauthor']) ? $qrmember['authorid'] : $qrmember['id'];
									$author = p('author');
									if ($author) {
										$p->upgradeLevelByAgent($qrmember['id']);
										pdo_update('ewei_shop_member', array('agentid' => $qrmember['id'], 'childtime' => $time,'authorid'=>$authorid), array('id' => $member['id']));
									}else{
                                        pdo_update('ewei_shop_member', array('agentid' => $qrmember['id'], 'childtime' => $time), array('id' => $member['id']));
									}

									if ($author){
										$author_set = $author->getSet();
										if(!empty($author_set['become']) && ($author_set['become']=='2' || $author_set['become']=='5')){
											$can_author = false;
											$getAgentsDownNum = $p->getAgentsDownNum($qrmember['openid']);
											if ($author_set['become']=='2'){
												if ($getAgentsDownNum['level1'] >= $author_set['become_down1']){
													$can_author = true;
												}elseif ($getAgentsDownNum['level2'] >= $author_set['become_down2']){
													$can_author = true;
												}elseif ($getAgentsDownNum['level3'] >= $author_set['become_down3']){
													$can_author = true;
												}
											}elseif($author_set['become']=='5'){
												if ($getAgentsDownNum['total'] >= $author_set['become_downcount']){
													$can_author = true;
												}
											}

											if($can_author){
												$become_check = intval($author_set['become_check']);
												if (empty($member['authorblack'])) { //不是黑名单
													pdo_update('ewei_shop_member', array('authorstatus' => $become_check, 'isauthor' => 1, 'authortime' => $time), array('uniacid' => $_W['uniacid'], 'id' => $qrmember['id']));

													//发送成为创始人
													if ($become_check == 1) {
														$p->sendMessage($qrmember['openid'], array('nickname' => $qrmember['nickname'], 'authortime' => $time), TM_AUTHOR_BECOME);
													}
												}
											}
										}
									}

									//发送增加通知
									$p->sendMessage($qrmember['openid'], array('nickname' => $member['nickname'], 'childtime' => $time), TM_COMMISSION_AGENT_NEW);

									//检测升级
									$p->upgradeLevelByAgent($qrmember['id']);

									//股东升级
									if(p('globonus')){
										p('globonus')->upgradeLevelByAgent($qrmember['id']);
									}
									//区域代理升级
									if (p('abonus')) {
										p('abonus')->upgradeLevelByAgent($qrmember['id']);
									}
									//创始人升级
									if(p('author')){
										p('author')->upgradeLevelByAgent($qrmember['id']);
									}
								} 
							}
							//判断是否直接成为分销商
							if (!empty($poster['beagent'])) {
								$become_check = intval($cset['become_check']);
								pdo_update('ewei_shop_member', array('isagent' => 1, 'status' => $become_check, 'agenttime' => $time), array('id' => $member['id']));
								if ($become_check == 1) {

									$p->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $time), TM_COMMISSION_BECOME);
									//检测升级
									$p->upgradeLevelByAgent($qrmember['id']);

									//股东升级
									if(p('globonus')){
										p('globonus')->upgradeLevelByAgent($qrmember['id']);
									}
									//区域代理升级
									if (p('abonus')) {
										p('abonus')->upgradeLevelByAgent($qrmember['id']);
									}
									//创始人升级
									if(p('author')){
										p('author')->upgradeLevelByAgent($qrmember['id']);
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
