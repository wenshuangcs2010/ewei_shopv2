<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';

class Register_EweiShopV2Page extends CommissionMobileLoginPage {

	function main() {

		global $_W, $_GPC;
		$openid = $_W['openid'];

		$set = set_medias($this->set, 'regbg');

		$member = m('member')->getMember($openid);

		if ($member['isagent'] == 1 && $member['status'] == 1) {
			header("location: " . mobileUrl('commission'));
			exit;
		}

		if ($member['agentblack']) {
			include $this->template();
			exit;
		}

        $apply_set = array();
        $apply_set['open_protocol'] = $set['open_protocol'];
        if (empty($set['applytitle'])) {
            $apply_set['applytitle'] = '分销商申请协议';
        } else {
            $apply_set['applytitle'] = $set['applytitle'];
        }


		//自定义表单
		$template_flag = 0;
		$diyform_plugin = p('diyform');
		if ($diyform_plugin) {
			$set_config = $diyform_plugin->getSet();
			$commission_diyform_open = $set_config['commission_diyform_open'];
			if ($commission_diyform_open == 1) {
				$template_flag = 1;
				$diyform_id = $set_config['commission_diyform'];
				if (!empty($diyform_id)) {
					$formInfo = $diyform_plugin->getDiyformInfo($diyform_id);
					$fields = $formInfo['fields'];
					$diyform_data = iunserializer($member['diycommissiondata']);
					$f_data = $diyform_plugin->getDiyformData($diyform_data, $fields, $member);
				}
			}
		}

		$mid = intval($_GPC['mid']);
		$agent = false;
		if (!empty($member['fixagentid'])) { //固定上级
			$mid = $member['agentid'];
			if (!empty($mid)) {
				$agent = m('member')->getMember($member['agentid']);
			}
		} else {
			if (!empty($member['agentid'])) { //如果有上线
				$mid = $member['agentid'];
				$agent = m('member')->getMember($member['agentid']);
			} else if (!empty($member['inviter'])) { //如果邀请人
				$mid = $member['inviter'];
				$agent = m('member')->getMember($member['inviter']);
			} else if (!empty($mid)) {
				$agent = m('member')->getMember($mid);
			}
		}


		if ($_W['ispost']) {
			if ($set['become']!='1') {
				show_json(0, '未开启' . $set['texts']['agent'] . "注册!");
			}

			$become_check = intval($set['become_check']);
			$ret['status'] = $become_check;

			if ($template_flag == 1) {
				$memberdata = $_GPC['memberdata'];
				$insert_data = $diyform_plugin->getInsertData($fields, $memberdata);
				$data = $insert_data['data'];
				$m_data = $insert_data['m_data'];
				$mc_data = $insert_data['mc_data'];

				$m_data['diycommissionid'] = $diyform_id;
				$m_data['diycommissionfields'] = iserializer($fields);
				$m_data['diycommissiondata'] = $data;

				$m_data['isagent'] = 1;
				$m_data['agentid'] = $mid;
				$m_data['status'] = $become_check;
				$m_data['agenttime'] = $become_check == 1 ? time() : 0;

				pdo_update('ewei_shop_member', $m_data, array('id' => $member['id']));

				if ($become_check == 1) {
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $m_data['agenttime']), TM_COMMISSION_BECOME);
				}

				if (!empty($member['uid'])) {
					if (!empty($mc_data)) {
						m('member')->mc_update($member['uid'], $mc_data);
					}
				}
			} else {

				$data = array(
					'isagent' => 1,
					'agentid' => $mid,
					'status' => $become_check,
					'realname' => $_GPC['realname'],
					'mobile' => $_GPC['mobile'],
					'weixin' => $_GPC['weixin'],
					'agenttime' => $become_check == 1 ? time() : 0
				);
				pdo_update('ewei_shop_member', $data, array('id' => $member['id']));
				if ($become_check == 1) {
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $data['agenttime']), TM_COMMISSION_BECOME);
					if (!empty($mid)) {
						//升级
						$this->model->upgradeLevelByAgent($mid);

						//股东升级
						if(p('globonus')){
							p('globonus')->upgradeLevelByAgent($mid);
						}
						//创始人升级
						if(p('author')){
							p('author')->upgradeLevelByAgent($mid);
						}
					}
				}
				if (!empty($member['uid'])) {
					//更新会员
					m('member')->mc_update($member['uid'], array('realname' => $data['realname'], 'mobile' => $data['mobile']));
				}
			}
			show_json(1, array('check' => $become_check));
		}

		$order_status = intval($set['become_order']) == 0 ? 1 : 3; //购物订单状态

		$become_check = intval($set['become_check']); //审核

		//以前未得到，修改了条件现在达到，重新判断资格
		$to_check_agent  =false;

		//分销商条件
		if (empty($set['become'])) {

			//无条件
			if (empty($member['status']) || empty($member['isagent'])) {
				$data = array(
					'isagent' => 1,
					'agentid' => $mid,
					'status' => $become_check,
					'realname' => $_GPC['realname'],
					'mobile' => $_GPC['mobile'],
					'weixin' => $_GPC['weixin'],
					'agenttime' => $become_check == 1 ? time() : 0
				);

				pdo_update('ewei_shop_member', $data, array('id' => $member['id']));
				if ($become_check == 1) {

					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $data['agenttime']), TM_COMMISSION_BECOME);
					//检测升级
					$this->model->upgradeLevelByAgent($member['id']);

					//股东升级
					if(p('globonus')){
						p('globonus')->upgradeLevelByAgent($member['id']);
					}
					//创始人升级
					if(p('author')){
						p('author')->upgradeLevelByAgent($member['id']);
					}
				}
				if (!empty($member['uid'])) {
					//更新会员
					m('member')->mc_update($member['uid'], array('realname' => $data['realname'], 'mobile' => $data['mobile']));
				}
				$member['isagent'] = 1;
				$member['status'] = $become_check;
			}
		} else if ($set['become'] == '2') {

			$status = 1;
			//订单数
			$ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . " where uniacid=:uniacid and openid=:openid and status>={$order_status} limit 1", array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if ($ordercount < intval($set['become_ordercount'])) {
				//未达到订单数
				$status = 0;
				$order_count = number_format($ordercount, 0);
				$order_totalcount = number_format($set['become_ordercount'], 0);
			} else{
				//以前未达到，现在改变条件达到了
				$to_check_agent = true;
			}
		} else if ($set['become'] == '3') {
			$status = 1;
			//消费数
			$moneycount = pdo_fetchcolumn('select sum(goodsprice) from ' . tablename('ewei_shop_order') . " where uniacid=:uniacid and openid=:openid and status>={$order_status} limit 1", array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if ($moneycount < floatval($set['become_moneycount'])) {
				//未达到消费金额
				$status = 0;
				$money_count = number_format($moneycount, 2);
				$money_totalcount = number_format($set['become_moneycount'], 2);
			} else {
				//以前未达到，现在改变条件达到了
				$to_check_agent = true;
			}
		} else if ($set['become'] == 4) {

			$goods = pdo_fetch('select id,title,thumb,marketprice from' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $set['become_goodsid'], ':uniacid' => $_W['uniacid']));
			$goodscount = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order_goods') . ' og '
				. '  left join ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid'
				. " where og.goodsid=:goodsid and o.openid=:openid and o.status>={$order_status}  limit 1", array(':goodsid' => $set['become_goodsid'], ':openid' => $openid));

			if ($goodscount <= 0) {
				$status = 0;
				$buy_goods = $goods;
				 
			} else {
    		     $to_check_agent = true;
				$status = 1;
			}
		}
		if( $to_check_agent) {

			if (empty($member['isagent'])) {
				$data = array(
					'isagent' => 1,
					'status' => $become_check,
					'agenttime' => time()
				);
				$member['isagent'] = 1;
				$member['status'] = $become_check;
				pdo_update('ewei_shop_member', $data, array('id' => $member['id']));
				if($become_check==1){
					$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $data['agenttime']), TM_COMMISSION_BECOME);
					//检测升级
					if(!empty($member['agentid'])){
						$parent = m('member')->getMember($member['agentid']);
						if(!empty($parent) && !empty($parent['status']) && !empty($parent['isagent'])){
							$this->model->upgradeLevelByAgent($parent['id']);
						}
					}
				}
			}
		}

		include $this->template();
	}

}
