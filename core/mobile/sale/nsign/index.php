<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends MobileLoginPage {
	function main() {
       global $_W, $_GPC;
       $member=m("member")->checkMember();//检查用户
       $params=array(":uniacid"=>$_W['uniacid']);
       $isshowlx=0;
       $firstweek=0;//奖励领取次数
       $checkLog=0;//连续签到天数
       $ngisconfig=pdo_fetch("SELECT * from ".tablename("ewei_shop_nsign_config")."where uniacid=:uniacid",$params);

       	//var_dump($bigwheelconfig);
       if(!empty($ngisconfig)){
       		$user_nsig=pdo_fetch("SELECT * from ".tablename("ewei_shop_nsign_user_prize")." where openid='".$_W['openid']."'");
       		if(!empty($user_nsig)){
       			$firstweek=$user_nsig['firstweek'];
       			$checkLog=$user_nsig['checkLog'];
       			$last_time=$user_nsig['last_time'];
       		}else{
            $showqda=0;
       			$last_time=$_W['timestamp'];
       		}

          $bool=m('nsignutil')->checkNsign($member['id']);
         
       }

       if(!empty($ngisconfig['lx'])){
 			$lx=iunserializer($ngisconfig['lx']);
 			if($lx['is']==1){
 				$timestamp=$_W['timestamp'];
 				$stattime=strtotime($lx['time']['start']);
	 			$endtime=strtotime($lx['time']['end']);
	 			if($timestamp>=$stattime && $timestamp<=$endtime){
	 				$isshowlx=1;
          $date=m("nsignutil")->T($last_time);

	 				if($date==0){
	 					$checkLog=$firstweek-1;
	 				}elseif($date==1){
            $checkLog=$firstweek;
          }else{
	 					 $checkLog=0;
	 				}
	 				 foreach($lx['jl'] as $key=>$v){
	 				 		$nexttime=date('m.d',strtotime('+'.($key-$checkLog).' day'));
	 				 		$nginfirstweek[$key]=array(
	 				 				'status'=>$v,
                  'is'=>$lx['jl'][$key],
	 				 				'ngistype'=>$lx['t'][$key],
	 				 				'number'=>$lx['e'][$key],
	 				 				'nexttime'=>$nexttime,
	 				 		);
	 				 }
	 			}
 			}
       }
       $lottery=pdo_fetch("SELECT * from ".tablename("ewei_shop_bigwheel_config")." where uniacid=:uniacid and status=1 and task_type=1 and is_delete=0",$params);
       	$s=m('member')->getCredits($_W['openid']);
        $reward = unserialize($lottery['lottery_data']);
        if(!empty($lottery)){
        	$type=$lottery['lottery_type'];
        	$lottery['thumb']=tomedia($lottery['thumb']);
        	$prizetype=$lottery['prizetype'];
        	//查询用户是否有记录
           $userlottyer=m("lottery")->getUserLottery($lottery['id'],$_W['openid']);
           $has_changes=0;
           if(!empty($userlottyer['last_time'])){
             $bool=m("nsignutil")->getnextdate($userlottyer['last_time']);
             if($bool){ 
               $has_changes=$lottery['usegivecondition'];
              }
           }else{
               $has_changes=$lottery['usegivecondition'];
           }
          if($lottery['usecreditcondition'] !=-1 && $lottery['usecreditcondition']!=0){
             $has_changes=$has_changes+intval($s['credit1']/$lottery['usecreditcondition']);
          }
          $has_changes=$has_changes+$userlottyer['today_num']+$userlottyer['user_share_num']; //前端用户抽奖次数控制
        	//链表查询
            $log = pdo_fetchall('SELECT l.*,m.`nickname`,m.`avatar` FROM '.tablename('ewei_shop_lottery_log').' AS l LEFT JOIN '.tablename('ewei_shop_member').' AS m ON l.openid=m.openid WHERE l.uniacid=:uniacid AND l.lottery_id=:lottery_id AND l.is_reward=1  LIMIT 10',array(':uniacid'=>$_W['uniacid'],':lottery_id'=>$lottery['id']));
        	 $member = m('member')->getMember($_W['openid'], true);
        	// var_dump($log);
          $set = $_W['shopset'];
          $_W['shopshare'] = array(
                'title' => empty($lottery['share_title']) ? $set['shop']['name'] : $lottery['share_title'],
                'imgUrl' => empty($lottery['share_thumb']) ? tomedia($set['shop']['logo']) : tomedia($lottery['share_thumb']),
                'desc' => empty($lottery['share_desc']) ? $set['shop']['description'] : $lottery['share_desc'],
                'link' => mobileUrl('sale.nsign', array('mid' => $member['id']),true),
            );
          $_W['my_share']=1;
          if(empty($lottery['link_url'])){
            $lottery['link_url']=mobileUrl('lottery/index/lottery_reward',array('id'=>$lottery['id']),true);
          }
        }
        //var_dump($_W['shopshare']);
        //if($_W['uniacid']==5){
          include $this->template("sale/nsign/indexab");
        // }else{
        //   include $this->template("sale/nsign/index");
        // }
       
    }

    function startnsign(){
    	global $_W, $_GPC;
    	if($_W['isajax']){
    		$arr=m("nsignutil")->startSign();
        echo json_encode($arr);
    		exit();
    	}
    }

}