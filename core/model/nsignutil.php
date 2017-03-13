<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Nsignutil_EweiShopV2Model {
	 function test(){
	 	//echo "111";
	 	$this->startSign($uid);
	 	//$msg=array(0=>array("title"=>"签到提醒","value"=>"今天需要签到了哦"));
	 	//$r=$this->sendmassage("oIeNnwzHrT6vXpiIUss3l5lt_W2w",$msg);
	 	//echo "今天第".$r."个签到";
	 	//var_dump($nsign_config);
	 }

	 //签到
	 function startSign(){
	 	global $_W;
	 	$member=m("member")->checkMember();//检查用户
	 	$array=$this->checktime();//检查签到时间
	 	$nsign_config=$this->getSignConfig();
	 	if($array['status']==-1){
	 		var_dump($array);
	 		return $array;
	 	}

	 	$openid=$member['openid'];
	 	$uid=$member['id'];
	 	
	 	$bool=$this->checkNsign($uid);//检查用户今天是是否签到
	 	//$bool=true;//检查用户今天是是否签到
	 	if(!$bool){
	 		$array['status']=-1;
	 		$array['msg']=$nsign_config['continuity_msg'];
	 		$this->sendmassage($openid,$array['msg']);
	 		return $array;
	 	}
	 	$this->setNsign($uid);//添加一条签到记录
	 	$Signinintegral=$this->signSetcredit1($uid,$openid);//
	 	$msg=$nsign_config['success_msg'];
	 	$msg = str_replace('[签到积分]', $Signinintegral['fistcredit1'], $msg);
	 	$ewjl=$checkUserFistjl=$this->checkUserFistjl($uid,$bool);
	 	if($bool){
	 		$ewjl=$this->extrajl($nsign_config);
	 		$msg = str_replace('[额外奖值]', $ewjl, $msg);
	 	}else{
	 		$msg = str_replace('[额外奖值]', $ewjl, $msg);
	 	}
	 	$mycount=$this->getTadaySigncount();
	 	$msg=str_replace('[首次积分]', $nsign_config['everyday'], $msg);
	 	$msg=str_replace('[续签积分]', $nsign_config['continuity'], $msg);
	 	$msg=str_replace('[下期积分]', $Signinintegral['nextcredit1'], $msg);
	 	$msg=str_replace('[当天签到量]', $mycount, $msg);
	 	$user = m('member')->getMember($openid);

	 	$msg = str_replace('[昵称]', $user['nickname'], $msg);
	 	$nsigiUser= $this->getNsigiUser($uid);
	 	$msg=str_replace('[签到总积分]', $nsigiUser['totalcredit1'], $msg);
	 	$s=m('member')->getCredits($openid);
	 	$msg=str_replace('[总积分]', $s['credit1'], $msg);
	 	
	 	$this->sendmassage($openid,$msg);
	 	$ret=array(
	 		'firstweek'=>$nsigiUser['firstweek'],
	 		'credit1'=>$s['credit1'],
	 		);
	 	return array('status'=>0,'ret'=>$ret,'msg'=>$msg);
	 }
	 function extrajl($nsign_config){
		global $_W;
		$weekarray=array("日","一","二","三","四","五","六");
		$wekdate=date("w");
		$extrajl=iunserializer($nsign_config['jl']);
		if($extrajl['is']!=1){
			return false;
		}
		if(in_array($wekdate, $extrajl['w'])){
			$mycount=$this->getTadaySigncount();
			foreach($extrajl['a'] as $k=>$v){
					if(!empty($v)){
						$jla=$v;
						$jlb=$extrajl['b'][$k];
						$jlc=$extrajl['t'][$k];
						$jld=$extrajl['e'][$k];
						if($jlc==1){
							//奖励积分
							$log=array("0","星期".$weekarray[$wekdate]."签到额外奖励积分".$jld);
							if($mycount>=$jla && $mycount<=$jlb){
								$this->setCreditnign($_W['openid'],$jld,$log);
								return "额外积分+".$jld;
							}
							//
						}else{
							$lottery=$this->getNsignLottery();
							if(!empty($lottery)){
								$userlottery=m("lottery")->getUserLottery($lottery['id'],$_W['openid']);
								$data['today_num']=$userlottery['today_num']+$jld;
								$data['other_num']=$userlottery['other_num']+$jld;
								m("lottery")->updateUserLottery($data,$_W['openid'],$lottery['id']);
							}
							return "额外抽奖+".$jld;
						}
					}
					return $msg;
			}
		}
		return false;
	 }
	 function checkNsign($uid){//检查用户今天是是否签到
	 	global $_W;
	 	$nsignuser=pdo_fetch("SELECT * from ".tablename("ewei_shop_nsign_user_prize")." where uid=:uid",array(":uid"=>$uid));
	 	if(empty($nsignuser)){
	 		return true;
	 	}
	 	return $this->getnextdate($nsignuser['last_time']);
	 }

	 function getnextdate($last_time){
	 	if(empty($last_time)){
	 		$last_time=date("Y-m-d H:i:s",strtotime("-1 day"));
	 	}else{
	 		$last_time=date("Y-m-d H:i:s",$last_time);
	 	}
	 	$b = substr($last_time,0,10);
	 	$c = date('Y-m-d');
	 	if($b==$c){
	 		return false;
	 	}
	 	return true;
	 }
	 function getNsigiUser($uid){//获取用户签到记录
	 	$nsignuser=pdo_fetch("SELECT * from ".tablename("ewei_shop_nsign_user_prize")." where uid=:uid",array(":uid"=>$uid));
	 	return $nsignuser;
	 }
	 function setNsign($uid){//添加签到记录
	 	global $_W;
	 	$nsignuser=$this->getNsigiUser($uid);
	 	$total=$nsignuser['nsigntotal'];
	 	$data=array(
	 		"uid"=>$uid,
	 		'add_time'=>$_W['timestamp'],
	 		'last_time'=>$_W['timestamp'],
	 		'nsigntotal'=>$total+1,
	 		'uniacid'=>$_W['uniacid'],
	 		'openid'=>$_W['openid'],
	 		);
	 	if(!empty($nsignuser)){
	 		unset($data['add_time']);
	 		$date=m("nsignutil")->T($nsignuser['last_time']);

	 		if($date==1){
	 			$data['checkLog']=$nsignuser['checkLog']+1;//计数器
	 		}else{
	 			$data['checkLog']=1;
	 		}
	 		pdo_update("ewei_shop_nsign_user_prize",$data,array("id"=>$nsignuser['id'],'uid'=>$uid));
	 	}else{
	 		pdo_insert("ewei_shop_nsign_user_prize",$data);
	 	}
	 }

	 function setCreditnign($openid,$credit1,$log){
	 	$this->updateCreditTotal($openid,$credit1);
	 	m("member")->setCredit($openid,'credit1',$credit1,$log);
	 }
	 function signSetcredit1($uid,$openid){//签到获取积分
	 	global $_W;
	 	$nsign_config=$this->getSignConfig();
	 	//$nsign_config['everyday'];
	 	$nsigiUser= $this->getNsigiUser($uid);
	 	$credit1=0;
	 	if($nsigiUser['checkLog']==1){
	 		$log=array('0', "首次签到获得".$nsign_config['everyday']."积分");
	 		$credit1=$nsign_config['everyday'];
	 	}
	 	if($nsigiUser['checkLog']>1){
	 		$credit1=($nsigiUser['checkLog']-1)*$nsign_config['continuity']+$nsign_config['everyday'];
	 		$log=array('0', "连续签到".$nsigiUser['checkLog']."获得".$credit1."积分");
	 	}

	 	if($nsign_config['intup']<=$credit1){
	 		$credit1=$nsign_config['intup'];
	 		$log=array('0', "连续签到".$nsigiUser['checkLog']."获得".$credit1."积分");
	 	}
	 	// 下期积分计算
	 	$nextcredit1=$nsigiUser['checkLog']*$nsign_config['continuity']+$nsign_config['everyday'];
		if($nsign_config['intup']<=$nextcredit1){
			$nextcredit1=$nsign_config['intup'];
		}

	 	$this->setCreditnign($openid,$credit1,$log);
	 	return array("fistcredit1"=>$credit1,'nextcredit1'=>$nextcredit1);
	 }
	 function updateCreditTotal($openid,$credit1){
	 	//echo "<br/>".$credit1."----".$openid;
	 	$sql="update ".tablename('ewei_shop_nsign_user_prize')." set totalcredit1=totalcredit1+:credit1 where openid = :openid";
	 		pdo_query($sql,array(":credit1"=>$credit1,"openid"=>$openid));
	 }
	 function checkUserFistjl($uid,&$bool=false){//检查用户是否开始首周额外奖励
	 	global $_W;
	 	$bool=false;
	 	$nsign_config=$this->getSignConfig();
	 	$lx=iunserializer($nsign_config['lx']);
	 	$timestamp=$_W['timestamp'];
	 	$nsigiUser= $this->getNsigiUser($uid);//
	 	$firstweek=$nsigiUser['firstweek'];//用户首周奖励签到进度
		$checkLog=$nsigiUser['checkLog'];//连续签到进度
		if($firstweek>=7){
			$bool=true;
			return false;
		}
		//die();
	 	if($lx['is']==1){
	 		$stattime=strtotime($lx['time']['start']);
	 		$endtime=strtotime($lx['time']['end']);
	 		if($timestamp>=$stattime && $timestamp<=$endtime){
				if($lx['jl'][$checkLog-1]==1){
					//var_dump($lx['jl'][$checkLog-1]);
					if($checkLog>$firstweek){
						if($lx['t'][$checkLog-1]==1){
							//积分奖励
							$credit1=$lx['e'][$checkLog-1];
							$log=array('0', "首周连续签到".$checkLog."天,获得".$credit1."积分");
							$this->setCreditnign($_W['openid'],$credit1,$log);
							pdo_update('ewei_shop_nsign_user_prize',array('firstweek'=>$checkLog),array("id"=>$nsigiUser['id']));
							return "首周连续签到获得额外积分+".$credit1;
						}else{
							//抽奖机会
							$credit1=$lx['e'][$checkLog-1];
							$lottery=$this->getNsignLottery();
							//var_dump($lottery);
							if(!empty($lottery)){
								$userlottery=m("lottery")->getUserLottery($lottery['id'],$_W['openid']);
								$data['today_num']=$userlottery['today_num']+$credit1;
								$data['other_num']=$userlottery['other_num']+$credit1;
								m("lottery")->updateUserLottery($data,$_W['openid'],$lottery['id']);
							}
							pdo_update('ewei_shop_nsign_user_prize',array('firstweek'=>$checkLog),array("id"=>$nsigiUser['id']));
							return "抽奖机会奖励+".$credit1;
						}
					}
				}else{
					if($checkLog>$firstweek){
						pdo_update('ewei_shop_nsign_user_prize',array('firstweek'=>$checkLog),array("id"=>$nsigiUser['id']));
					}
				}
	 		}
	 		//var_dump(strtotime($lx['time']['start']));
	 	}
	 	$bool=false;
	 	return false;
	 }
	 //查询开起签到活动的场景
	 function getNsignLottery(){
	 	global $_W;
		$params=array(":uniacid"=>$_W['uniacid']);
		$lottery=pdo_fetch("SELECT * from ".tablename("ewei_shop_bigwheel_config")." where uniacid=:uniacid and status=1 and task_type=1 and is_delete=0",$params);
		return $lottery;
	 }
	 //检查是否开启签到时段
	 function checktime(){
	 	global $_W;
	 	$array=array("status"=>0,'message'=>"");
	 	$timestamp=date("Hi",$_W['timestamp']);
	 	//$timestamp="750";
	 	$nsign_config=$this->getSignConfig();
	 	$time=iunserializer($nsign_config['time']);
	 	$istime=$time['is'];
	 	$stattime=$time['a'];
	 	$endtime=$time['e'];
	 	//var_dump($timestamp);
	 	if($istime){
	 		if(abs($timestamp)>$stattime && abs($timestamp)<$endtime){
	 		}else{
	 			$array['status']=-1;
	 			$array['message']=$time['t'];
	 		}
	 	}
	 	return $array;
	 }
	 //获取配置
	function getSignConfig(){
		global $_W;
		$nsign_config=m("cache")->get("nsignconfig",$_W['uniacid']);
		if(empty($nsign_config)){
			$sql="SELECT * from ".tablename("ewei_shop_nsign_config")." where uniacid=:uniacid";
			$nsign_config=pdo_fetch($sql,array(":uniacid"=>$_W['uniacid']));
			m("cache")->set('nsignconfig',$nsign_config,$_W['unaicid']);
		}
        return $nsign_config;
	}
	function getTadaySigncount(){//获取自己是第几个签到的
		global $_W;
		$member=m("member")->checkMember();//检查用户
		$uid=$member['id'];
		$nsignuser=pdo_fetch("SELECT * from ".tablename("ewei_shop_nsign_user_prize")." where uid=:uid",array(":uid"=>$uid));
		$today = strtotime(date('Y-m-d', $_W['timestamp']));
		$sql="SELECT count(*) from ".tablename("ewei_shop_nsign_user_prize")." where last_time>={$today} and uniacid=:uniacid and last_time<={$nsignuser[last_time]} order by last_time asc";
		
		$count=pdo_fetchcolumn($sql,array(":uniacid"=>$_W['uniacid']));

		return $count;
	}

	//每日用户提示
	function usertx(){
		global $_W;
		$today = strtotime(date('Y-m-d', $_W['timestamp']));//今天 0点
		$beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));//昨天0点
		var_dump($beginYesterday);
		var_dump($today);
		$sql="SELECT * from ".tablename("ewei_shop_nsign_user_prize").
		" where last_time<={$today} and last_time>{$beginYesterday} and istx=1";
		$userlist=pdo_fetchall($sql);
		var_dump($userlist);
	}
	//发送客服消息
	function sendmassage($openid,$msg,$url = '', $account = null){
		m("message")->sendCustomNotice($openid,$msg,$url = '', $account = null);
	}


	function T($time)
	{
	   //获取今天凌晨的时间戳
	   $day = strtotime(date('Y-m-d',time()));
	   //获取昨天凌晨的时间戳
	   $pday = strtotime(date('Y-m-d',strtotime('-1 day')));
	   //获取现在的时间戳
	   $nowtime = time();
	   if($time<$pday){//昨天以前
		 return -1;
	   }
	   if($time<$day && $time>$pday ){//如果时间<今天0点 并且时间 大于昨天0点
	      return 1;//昨天
	   }
	   return 0;//今天
	}
}
