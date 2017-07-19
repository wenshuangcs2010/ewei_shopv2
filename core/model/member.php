<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Member_EweiShopV2Model {

	/**
	 * 获取会员资料
	 */
	public function getInfo($openid = '') {

		global $_W;
		$uid = intval($openid);
		if ($uid == 0) {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		} else {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where id=:id  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		}
		if (!empty($info['uid'])) {
			//会员余额积分信息
			load()->model('mc');
			$uid = mc_openid2uid($info['openid']);
			$fans = mc_fetch($uid, array('credit1', 'credit2', 'birthyear', 'birthmonth', 'birthday', 'gender', 'avatar', 'resideprovince', 'residecity', 'nickname'));
			$info['credit1'] = $fans['credit1'];
			$info['credit2'] = $fans['credit2'];
			$info['birthyear'] = empty($info['birthyear']) ? $fans['birthyear'] : $info['birthyear'];
			$info['birthmonth'] = empty($info['birthmonth']) ? $fans['birthmonth'] : $info['birthmonth'];
			$info['birthday'] = empty($info['birthday']) ? $fans['birthday'] : $info['birthday'];
			$info['nickname'] = empty($info['nickname']) ? $fans['nickname'] : $info['nickname'];
			$info['gender'] = empty($info['gender']) ? $fans['gender'] : $info['gender'];
			$info['sex'] = $info['gender'];
			$info['avatar'] = empty($info['avatar']) ? $fans['avatar'] : $info['avatar'];
			$info['headimgurl'] = $info['avatar'];
			$info['province'] = empty($info['province']) ? $fans['resideprovince'] : $info['province'];
			$info['city'] = empty($info['city']) ? $fans['residecity'] : $info['city'];
		}
		if (!empty($info['birthyear']) && !empty($info['birthmonth']) && !empty($info['birthday'])) {
			$info['birthday'] = $info['birthyear'] . '-' . (strlen($info['birthmonth']) <= 1 ? '0' . $info['birthmonth'] : $info['birthmonth']) . '-' . (strlen($info['birthday']) <= 1 ? '0' . $info['birthday'] : $info['birthday']);
		}
		if (empty($info['birthday'])) {
			$info['birthday'] = '';
		}
		/*
        if($_SERVER['HTTPS']=='on'){
            $info['avatar'] = str_replace('http://','https://',$info['avatar']);
        }*/
		return $info;
	}

	/**
	 * 获取会员信息
	 */
	public function getMember($openid = '') {
		global $_W;
		$uid= (int)$openid;
		if ($uid==0) {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if(empty($info)){
			    if(strexists($openid, 'sns_qq_')){
                    $openid = str_replace('sns_qq_', '', $openid);
                    $condition = " openid_qq=:openid ";
                    $bindsns = 'qq';
                }
                elseif(strexists($openid, 'sns_wx_')){
                    $openid = str_replace('sns_wx_', '', $openid);
                    $condition = " openid_wx=:openid ";
                    $bindsns = 'wx';
                }
                if(!empty($condition)){
                    $info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where '.$condition.'  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' =>$openid));
                    if(!empty($info)){
                        $info['bindsns'] = $bindsns;
                    }
                }
			}
		} else {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));
		}
		if (!empty($info)) {
            $info = $this->updateCredits($info);
		}


		return $info;
	}

	public function updateCredits($info)
    {
        global $_W;
        $openid = $info['openid'];
        //自动检测UID情况
        if (empty($info['uid'])) {
            $followed = m('user')->followed($openid);

            if ($followed) {
                load()->model('mc');
                $uid = mc_openid2uid($openid);
                if (!empty($uid)) {
                    $info['uid'] = $uid;
                    $upgrade = array('uid' => $uid);
                    if ($info['credit1'] > 0) {
                        //同步积分
                        mc_credit_update($uid, 'credit1', $info['credit1']);
                        $upgrade['credit1'] = 0;
                    }
                    if ($info['credit2'] > 0) {
                        //同步余额
                        mc_credit_update($uid, 'credit2', $info['credit2']);
                        $upgrade['credit2'] = 0;
                    }
                    if (!empty($upgrade)) {
                        pdo_update('ewei_shop_member', $upgrade, array('id' => $info['id']));
                    }
                }
            }
        }
        $credits = $this->getCredits($openid);
        $info['credit1'] = $credits['credit1'];
        $info['credit2'] = $credits['credit2'];
        /*
        if($_SERVER['HTTPS']=='on'){
            $info['avatar'] = str_replace('http://','https://',$info['avatar']);
        }*/
        return $info;
    }

    /**
     * 通过手机号获取用户信息
     * @param $mobile
     */
	public function getMobileMember($mobile)
    {
        global $_W;
        $info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':mobile' => $mobile));
        if (!empty($info)){
            $info = $this->updateCredits($info);
        }
        return $info;
    }

	public function getMid() {
		global $_W;
		$openid = $_W['openid'];
		$member = $this->getMember($openid);
		return $member['id'];
	}

	//处理积分或余额
    public function setCredit($openid = '', $credittype = 'credit1', $credits = 0, $log = array()) {

        global $_W;
        load()->model('mc');
        $uid = mc_openid2uid($openid);

        if (empty($log)) {
            $log = array($uid, '未记录');
        }elseif (!is_array($log)){
            $log = array(0, $log);
        }

        if($credittype=='credit1' && empty($log[0]) && $credits>0){
            // 系统充值 判断是否达到积分上限
            $shopset = m('common')->getSysset('trade');
            $member = $this->getMember($openid);

            if(empty($member['diymaxcredit'])){
                // 系统设置
                if($shopset['maxcredit']>0){
                    if($member['credit1']>=$shopset['maxcredit']){
                        return error(-1, "用户积分已达上限");
                    }
                    elseif($member['credit1']+$credits>$shopset['maxcredit']){
                        $credits = $shopset['maxcredit'] - $member['credit1'];
                    }
                }
            }else{
                // 用户设置
                if($member['maxcredit']>0){
                    if($member['credit1']>=$member['maxcredit']){
                        return error(-1, "用户积分已达上限");
                    }
                    elseif($member['credit1']+$credits>$member['maxcredit']){
                        $credits = $member['maxcredit'] - $member['credit1'];
                    }
                }
            }
        }

        if (!empty($uid)) {

            //如果已关注
            $value = pdo_fetchcolumn("SELECT {$credittype} FROM " . tablename('mc_members') . " WHERE `uid` = :uid", array(':uid' => $uid));
            $newcredit = $credits + $value;

            if ($newcredit <= 0) {
                $newcredit = 0;
            }
            pdo_update('mc_members', array($credittype => $newcredit), array('uid' => $uid));

            if (empty($log)) {
                $log = array($uid, '未记录');
            }elseif (!is_array($log)){
                $log = array(0, $log);
            }

            $data = array(
                'uid' => $uid,
                'credittype' => $credittype,
                'uniacid' => $_W['uniacid'],
                'num' => $credits,
                'createtime' => TIMESTAMP,
                'module' => 'ewei_shopv2',
                'operator' => intval($log[0]),
                'remark' => $log[1],
            );
            pdo_insert('mc_credits_record', $data);
        } else {
            //如果未关注
            $value = pdo_fetchcolumn("SELECT {$credittype} FROM " . tablename('ewei_shop_member') . " WHERE  uniacid=:uniacid and openid=:openid limit 1", array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
            $newcredit = $credits + $value;
            if ($newcredit <= 0) {
                $newcredit = 0;
            }
            pdo_update('ewei_shop_member', array($credittype => $newcredit), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
        }
    }

	//获取积分或余额
	public function getCredit($openid = '', $credittype = 'credit1') {

		global $_W;
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		if (!empty($uid)) {
			//如果已关注
			return pdo_fetchcolumn("SELECT {$credittype} FROM " . tablename('mc_members') . " WHERE `uid` = :uid", array(':uid' => $uid));
		} else {
			//如果未关注
			return pdo_fetchcolumn("SELECT {$credittype} FROM " . tablename('ewei_shop_member') . " WHERE  openid=:openid and uniacid=:uniacid limit 1", array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		}
	}

	//获取积分或余额
	public function getCredits($openid = '', $credittypes = array('credit1', 'credit2')) {

		global $_W;
		load()->model('mc');
		$uid = mc_openid2uid($openid);

		$types = implode(',', $credittypes);
		if (!empty($uid)) {
			//如果已关注
			return pdo_fetch("SELECT {$types} FROM " . tablename('mc_members') . " WHERE `uid` = :uid limit 1", array(':uid' => $uid));
		} else {
			//如果未关注
			return pdo_fetch("SELECT {$types} FROM " . tablename('ewei_shop_member') . " WHERE  openid=:openid and uniacid=:uniacid limit 1", array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		}
	}

	/**
	 * 检测用户,注册用户
	 */
	public function checkMember() {

		global $_W, $_GPC;

		$member = array();
		$shopset = m('common')->getSysset(array('shop','wap'));
		$openid = $_W['openid'];

        if($_W['routes']=='order.pay_alipay' || $_W['routes']=='creditshop.log.dispatch_complete' || $_W['routes']=='creditshop.detail.creditshop_complete' || $_W['routes']=='order.pay_alipay.recharge_complete' || $_W['routes']=='order.pay_alipay.complete' || $_W['routes']=='newmr.alipay' || $_W['routes']=='newmr.callback.gprs' || $_W['routes']=='newmr.callback.bill' || $_W['routes']=='account.sns'){
            return;
        }

		if($shopset['wap']['open']){
			if($shopset['wap']['inh5app'] && is_h5app() || (empty($shopset['wap']['inh5app']) && empty($openid))){
				return;
			}
		}



		//如果你把下面这个if注释了,你就倒霉了
		if (empty($openid) && !EWEI_SHOPV2_DEBUG) {
			$diemsg = is_h5app() ? "APP正在维护, 请到公众号中访问" : "请在微信客户端打开链接";
			die("<!DOCTYPE html>
                <html>
                    <head>
                        <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                        <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                    </head>
                    <body>
                    <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>".$diemsg."</h4></div></div></div>
                    </body>
                </html>");
		}
		$member = m('member')->getMember($openid);
		$followed = m('user')->followed($openid);
		$uid = 0;
		$mc = array();
		load()->model('mc');
		if ($followed || empty($shopset['shop']['getinfo']) || $shopset['shop']['getinfo'] == 1 ) {
			$uid = mc_openid2uid($openid);
			if( !EWEI_SHOPV2_DEBUG ){
				$userinfo = mc_oauth_userinfo();

			} else {
				$userinfo = array(
					'openid' => $member['openid'],
					'nickname' => $member['nickname'],
					'headimgurl' => $member['avatar'],
					'gender' => $member['gender'],
					'province' => $member['province'],
					'city' => $member['city']
				);
			}

			$mc = array();
			$mc['nickname'] = stripcslashes($userinfo['nickname']);
			$mc['avatar'] = $userinfo['headimgurl'];
			$mc['gender'] = $userinfo['sex'];
			$mc['resideprovince'] = $userinfo['province'];
			$mc['residecity'] = $userinfo['city'];
			$mc['nickname'] = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $mc['nickname']);
			//WeUtility::logging('测试mc', var_export($mc, true));
		}
		if (empty($member) && !empty($openid)) {
			
			$member = array(
				'uniacid' => $_W['uniacid'],
				'uid' => $uid,
				'openid' => $openid,
				'realname' => !empty($mc['realname']) ? $mc['realname'] : '',
				'mobile' => !empty($mc['mobile']) ? $mc['mobile'] : '',
				'nickname' => !empty($nickname) ? $nickname : '',
				'avatar' => !empty($mc['avatar']) ? $mc['avatar'] : '',
				'gender' => !empty($mc['gender']) ? $mc['gender'] : '-1',
				'province' => !empty($mc['resideprovince']) ? $mc['resideprovince'] : '',
				'city' => !empty($mc['residecity']) ? $mc['residecity'] : '',
				'area' => !empty($mc['residedist']) ? $mc['residedist'] : '',
				'createtime' => time(),
				'status' => 0
			);
			pdo_insert('ewei_shop_member', $member);
			$member['id'] = pdo_insertid();
		} else {
			if ($member['isblack'] == 1) {
				//是黑名单
				show_message("暂时无法访问，请稍后再试!");
			}
			$upgrade = array('uid'=>$uid);
			 //WeUtility::logging('测试mc', var_export($mc, true));
			 // WeUtility::logging('测试member', var_export($member, true));
			if (isset($mc['nickname']) && $member['nickname'] != $mc['nickname']) {
				$upgrade['nickname'] = $mc['nickname'];
			}
			if (isset($mc['avatar']) && $member['avatar'] != $mc['avatar']) {
				$upgrade['avatar'] = $mc['avatar'];
			}
			if (isset($mc['gender']) && $member['gender'] != $mc['gender']) {
				$upgrade['gender'] = $mc['gender'];
			}
			if (!empty($upgrade)) {
				pdo_update('ewei_shop_member', $upgrade, array('id' => $member['id']));
			}
		}
		//分销商
		if (p('commission')) {
			p('commission')->checkAgent($openid);
		}

		//海报图扫描
		if (p('poster')) {
			p('poster')->checkScan($openid);
		}
		if (empty($member)) {
			return false;
		}

		return array(
			'id' => $member['id'],
			'openid' => $member['openid']
		);
	}

	/**
	 * 获取所有会员等级
	 * @global type $_W
	 * @return type
	 */
	function getLevels($all=true) {
		global $_W;

		$condition = '';
		if(!$all){
			$condition = " and enabled=1";
		}
		return pdo_fetchall('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid'.$condition.' order by level asc', array(':uniacid' => $_W['uniacid']));
	}

	//获取会员等级
	function getLevel($openid) {
		global $_W, $_S;
		if (empty($openid)) {
			return false;
		}
		$member = m('member')->getMember($openid);
		if (!empty($member) && !empty($member['level'])) {
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $member['level'], ':uniacid' => $_W['uniacid']));
			if (!empty($level)) {
				return $level;
			}
		}
		return array(
			'levelname' => empty($_S['shop']['levelname']) ? '普通会员' : $_S['shop']['levelname'],
			'discount' => empty($_S['shop']['leveldiscount']) ? 10 : $_S['shop']['leveldiscount'],
		);
	}

	/**
	 * 会员升级
	 * @param type $mid
	 */
	function upgradeLevel($openid) {
		global $_W;
		if (empty($openid)) {
			return;
		}

		$shopset = m('common')->getSysset('shop');
		$leveltype = intval($shopset['leveltype']);
		$member = m('member')->getMember($openid);
		if (empty($member)) {
			return;
		}

		//查找符合条件的新等级
		$level = false;
		if (empty($leveltype)) {
			$ordermoney = pdo_fetchcolumn('select ifnull( sum(og.realprice),0) from ' . tablename('ewei_shop_order_goods') . ' og '
				. ' left join ' . tablename('ewei_shop_order') . ' o on o.id=og.orderid '
				. ' where o.openid=:openid and o.status=3 and o.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . " where uniacid=:uniacid  and enabled=1 and {$ordermoney} >= ordermoney and ordermoney>0  order by level desc limit 1", array(':uniacid' => $_W['uniacid']));
		} else if ($leveltype == 1) {
			$ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=3 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . " where uniacid=:uniacid and enabled=1 and {$ordercount} >= ordercount and ordercount>0  order by level desc limit 1", array(':uniacid' => $_W['uniacid']));
		}
		if (empty($level)) {
			return;
		}
		if ($level['id'] == $member['level']) {
			return;
		}

		//旧等级
		$oldlevel = $this->getLevel($openid);
		$canupgrade = false;  //是否可以升级 

		if (empty($oldlevel['id'])) {
			//用户没有等级
			$canupgrade = true;
		} else {
			if ($level['level'] > $oldlevel['level']) {
				//新等级权重较大
				$canupgrade = true;
			}
		}
		if ($canupgrade) {
			//会员升级
			pdo_update('ewei_shop_member', array('level' => $level['id']), array('id' => $member['id']));

			//模板消息
			m('notice')->sendMemberUpgradeMessage($openid, $oldlevel, $level);
		}
	}

	/**
	 * 获取所有会员分组
	 * @global type $_W
	 * @return type
	 */
	function getGroups() {
		global $_W;
		return pdo_fetchall('select * from ' . tablename('ewei_shop_member_group') . ' where uniacid=:uniacid order by id asc', array(':uniacid' => $_W['uniacid']));
	}

	//获取会员分组
	function getGroup($openid) {
		if (empty($openid)) {
			return false;
		}

		$member = m('member')->getMember($openid);
		return $member['groupid'];
	}

	//充值积分
	function setRechargeCredit($openid = '', $money = 0) {

		if (empty($openid)) {
			return;
		}

		global $_W;
		$credit = 0;
		$set = m('common')->getSysset(array('trade', 'shop'));

		if ($set['trade']) {
			$tmoney = floatval($set['trade']['money']);
			$tcredit = intval($set['trade']['credit']);
			if ($money >= $tmoney) {
				if ($money % $tmoney == 0) {
					$credit = intval($money / $tmoney) * $tcredit;
				} else {
					$credit = (intval($money / $tmoney) + 1) * $tcredit;
				}
			}
		}
		if ($credit > 0) {
			$this->setCredit($openid, 'credit1', $credit, array(0, $set['shop']['name'] . '会员充值积分:credit2:' . $credit));
		}
	}

    //计算税费等相关费用
    function getCalculateMoney($money, $set_array) {

        $charge = $set_array['charge'];
        $begin = $set_array['begin'];
        $end = $set_array['end'];

        $array = array();
        $array['deductionmoney'] = round($money * $charge / 100, 2);
        if ($array['deductionmoney'] >= $begin && $array['deductionmoney'] <= $end) {
            $array['deductionmoney'] = 0;
        }
        $array['realmoney'] = round($money - $array['deductionmoney'], 2);
        if ($money == $array['realmoney']) {
            $array['flag'] = 0;
        } else {
            $array['flag'] = 1;
        }
        return $array;
    }

	public function checkMemberFromPlatform($openid = '') {
		global $_W;
		$acc = WeiXinAccount::create($_W['acid']);
		$userinfo = $acc->fansQueryInfo($openid);
		$userinfo['avatar'] = $userinfo['headimgurl'];

		load()->model('mc');
		$uid = mc_openid2uid($openid);
		if (!empty($uid)) {
			pdo_update('mc_members', array(
				'nickname' => $userinfo['nickname'],
				'gender' => $userinfo['sex'],
				'nationality' => $userinfo['country'],
				'resideprovince' => $userinfo['province'],
				'residecity' => $userinfo['city'],
				'avatar' => $userinfo['headimgurl']), array('uid' => $uid)
			);
		}
		pdo_update('mc_mapping_fans', array(
			'nickname' => $userinfo['nickname']
		), array('uniacid' => $_W['uniacid'], 'openid' => $openid));

		$member = $this->getMember($openid);
		if (empty($member)) {
			$mc = mc_fetch($uid, array('realname', 'nickname', 'mobile', 'avatar', 'resideprovince', 'residecity', 'residedist'));
			$member = array(
				'uniacid' => $_W['uniacid'],
				'uid' => $uid,
				'openid' => $openid,
				'realname' => $mc['realname'],
				'mobile' => $mc['mobile'],
				'nickname' => !empty($mc['nickname']) ? $mc['nickname'] : $userinfo['nickname'],
				'avatar' => !empty($mc['avatar']) ? $mc['avatar'] : $userinfo['avatar'],
				'gender' => !empty($mc['gender']) ? $mc['gender'] : $userinfo['sex'],
				'province' => !empty($mc['resideprovince']) ? $mc['resideprovince'] : $userinfo['province'],
				'city' => !empty($mc['residecity']) ? $mc['residecity'] : $userinfo['city'],
				'area' => $mc['residedist'],
				'createtime' => time(),
				'status' => 0
			);
			pdo_insert('ewei_shop_member', $member);
			$member['id'] = pdo_insertid();
			$member['isnew'] = true;
		} else {
			$member['nickname'] = $userinfo['nickname'];
			$member['avatar'] = $userinfo['headimgurl'];
			$member['province'] = $userinfo['province'];
			$member['city'] = $userinfo['city'];
			pdo_update('ewei_shop_member', $member, array('id' => $member['id']));
			$member['isnew'] = false;
		}
		return $member;
	}

	public function mc_update($mid, $data){
		global $_W;

		if(empty($mid) || empty($data)){
			return;
		}
		$wapset = m('common')->getSysset('wap');
		$member = $this->getMember($mid);
		if(!empty($wapset['open']) && isset($data['mobile']) && $data['mobile']!=$member['mobile']){
			unset($data['mobile']);
		}
		load()->model('mc');
		mc_update($this->member['uid'], $data);
	}
	
	public function checkMemberSNS($sns){
		global $_W, $_GPC;

		if(empty($sns)){
			$sns = $_GPC['sns'];
		}
		if(empty($sns)){
			return;
		}
		elseif ($sns=='wx' && !empty($_GPC['token'])){
            load()->func('communication');
			$snsurl = "https://api.weixin.qq.com/sns/userinfo?access_token=".$_GPC['token']."&openid=".$_GPC['openid']."&lang=zh_CN";
			$userinfo = ihttp_request($snsurl);
			$userinfo = json_decode($userinfo['content'], true);
			$userinfo['openid'] = 'sns_wx_'.$userinfo['openid'];
		}
		elseif ($sns=='qq'){
			$userinfo = htmlspecialchars_decode($_GPC['userinfo']);
			$userinfo = json_decode($userinfo, true);
			$userinfo['openid'] = 'sns_qq_' . $_GPC['openid'];
			$userinfo['headimgurl'] = $userinfo['figureurl_qq_2'];
			$userinfo['gender'] = $userinfo['gender']=='男'?1:2;
		}

		$data = array(
			'nickname'=>$userinfo['nickname'],
			'avatar'=>$userinfo['headimgurl'],
			'province'=>$userinfo['province'],
			'city'=>$userinfo['city'],
			'gender'=>$userinfo['sex'],
			'comefrom'=>'h5app_sns_'.$sns
		);

		$openid = trim($_GPC['openid']);
		if($sns=='qq'){
			$data['openid_qq'] = trim($_GPC['openid']);
			$openid = 'sns_qq_'.trim($_GPC['openid']);
		}
		if($sns=='wx'){
            $data['openid_wx'] = trim($_GPC['openid']);
			$openid = 'sns_wx_'.trim($_GPC['openid']);
		}

		$member = $this->getMember($openid);

		if (empty($member)){
		    $data['openid'] = $userinfo['openid'];
		    $data['uniacid'] = $_W['uniacid'];
			$data['comefrom'] = 'sns_'.$sns;
			$data['createtime'] = time();
			$data['salt'] = m('account')->getSalt();
			$data['pwd'] = rand(10000, 99999).$data['salt'];
			pdo_insert('ewei_shop_member', $data);
		}
		elseif(empty($member['bindsns']) || $member['bindsns']==$sns){
			pdo_update('ewei_shop_member', $data, array('id'=>$member['id'], 'uniacid'=>$_W['uniacid']));
		}
	}

    /**
     * @param array $level 数组中有两个参数 一个是等级,一个是新等级
     * @param array $levels 分销商等级数组
     * @return bool 如果新等级大于 原等级 则返回true 否则 false
     */
    public function compareLevel(array $level,array $levels = array())
    {
        global $_W;
        $levels = !empty($levels) ? $levels : $this->getLevels();
        $old_key = -1;
        $new_key = -1;

        foreach ($levels as $kk=>$vv ){
            if ($vv['id'] == $level[0]){
                $old_key = $vv['level'];
            }
            if ($vv['id'] == $level[1]){
                $new_key = $vv['level'];
            }
        }

        return $new_key>$old_key;
    }

    /**
     * 微信获取网页openid整合
     * @param $appid APPID;
     * @param $secret SECRET;
     * @param $snsapi 类型有snsapi_userinfo,snsapi_base;
     * @param $expired 本地cookie缓存过期时间 默认600秒
     * @return array(openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid])
     */
    public function wxuser($appid,$secret,$snsapi='snsapi_base',$expired='600'){
        global $_W;
        if (($wxuser = $_COOKIE[$_W['config']['cookie']['pre'] . $appid]) === null) {
            $code = isset($_GET['code'])?$_GET['code']:'';
            if(!$code){
                $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $oauth_url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($url).'&response_type=code&scope='.$snsapi.'&state=wxbase#wechat_redirect';
                header('Location: ' . $oauth_url);
                exit;
            }
            load()->func('communication');
            $getOauthAccessToken = ihttp_get('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code');
            $json = json_decode($getOauthAccessToken['content'],true);

            if (!empty($json['errcode']) && $json['errcode'] != '40029'){
                return $json['errmsg'];
            }

            if (!empty($json['errcode']) && $json['errcode'] == '40029'){
                $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':'?');
                $parse=parse_url($url);
                if(isset($parse['query'])){
                    parse_str($parse['query'],$params);
                    unset($params['code']);
                    unset($params['state']);
                    $url = 'http://'.$_SERVER['HTTP_HOST'].$parse['path'].'?'.http_build_query($params);
                }
                $oauth_url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($url).'&response_type=code&scope='.$snsapi.'&state=wxbase#wechat_redirect';
                header('Location: ' . $oauth_url);
                exit;
            }
            if( $snsapi == "snsapi_userinfo" ){
                $userinfo = ihttp_get('https://api.weixin.qq.com/sns/userinfo?access_token='.$json['access_token'].'&openid='.$json['openid'].'&lang=zh_CN');
                $userinfo = $userinfo['content'];
            }
            elseif ( $snsapi == "snsapi_base" )
            {
                $userinfo = array();
                $userinfo['openid'] = $json['openid'];
            }
            $userinfostr = json_encode($userinfo);
            isetcookie($appid,$userinfostr,$expired);
            return $userinfo;
        }else{
            return json_decode($wxuser,true);
        }
    }

}
