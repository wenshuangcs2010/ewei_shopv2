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

class ComposterProcessor extends PluginProcessor {

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

	private function responseSubscribe($obj) {
		global $_W;

		$openid = $obj->message['from'];
		$sceneid = $obj->message['eventkey'];
		$ticket = $obj->message['ticket'];
		$member = $obj->member;
		if (empty($ticket)) {
			return $obj->respText('wuticket!');
			return $this->responseDefault($obj);
		}
		$qr = p("composter")->getQRByTicket($ticket);
		
		if (empty($qr)) {
			return $obj->respText('wuqr!');
			return $this->responseDefault($obj);
		}
		
		$composterid=$qr['composterid'];
		$poster = pdo_fetch('select * from ' . tablename('ewei_shop_composteruser') . ' where id=:compostid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'],":compostid"=>$composterid));
		if(empty($poster)){
			return $obj->respText('wuposter!');
			return $this->responseDefault($obj);
		}
		$qrmember = m('member')->getMember($poster['openid']);//推荐者
		if($qr['groupid']!=0){
			$membergroup=pdo_fetch("SELECT * from ".tablename("ewei_shop_member_group")." where id=:id",array(":id"=>$qr['groupid']));
			//会员归组
			if($membergroup['isgroup']==1){
				$data['groupid']=$membergroup['updategroupid'];
			}
			if($membergroup['isupmember']==1){
				$data['level']=$membergroup['levelsid'];
			}
			if(!empty($data)){
				pdo_update("ewei_shop_member",$data,array("openid"=>$openid,'uniacid'=>$_W['uniacid']));
			}
			if(!empty($membergroup['entrytext'])){
				$poster['entrytext']=$membergroup['entrytext'];//拦截分组消息
				$entrytext = str_replace("[nickname]", $qrmember['nickname'], $poster['entrytext']);
			}
		}
		$this->commission($poster, $member, $qrmember);//升级分销
		if(!empty($entrytext)){
			m('message')->sendCustomNotice($openid, $entrytext);
		}
		
		return $this->responseEmpty();
	}

	private function responseScan($obj) {
		global $_W;
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
