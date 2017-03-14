<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class index_EweiShopV2Page extends MobilePage {

    function main() {
        global $_W, $_GPC;
        $task_sql = 'SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_join').'  WHERE lottery_num>0 and uniacid=:uniacid AND `join_user`=:join_user ';
        $lottery = pdo_fetchcolumn($task_sql,array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        include $this->template();
    }
    function lottery_desc(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if(!empty($id)){
            $lottery=m("lottery")->getLottery($id);
            $content=stripslashes($lottery['desc']);
            $content=html_entity_decode($content);
        }
         include $this->template('lottery/lottery_desc');
    }
    function getsharenum(){
         global $_W, $_GPC;
        $id = intval($_GPC['lottery_id']);
         $lottery=m("lottery")->getLottery($id);
         if($_W['isajax']){
            $lotteryUser=m('lottery')->getUserLottery($id,$_W['openid']);
            if(empty($lottery)){
                $arr['status']=0;
                $arr['message']="活动错误";
                echo json_encode($arr);
                exit;
            }
            if($lottery['share_unm_total']==0){
                $arr['status']=-1;
                $arr['message']="没有获得机会";
                echo json_encode($arr);
                exit;
            }
            if($lotteryUser['today_share_total']==0){
                $data['share_time']=time();
            }else{
                $bool=m("nsignutil")->getnextdate($lotteryUser['share_time']);
                if($bool){
                    $lotteryUser['today_share_total']=0;
                    $data['share_time']=time();
                }
            }
            if($lottery['todaytotal']!=-1 && $lotteryUser['today_share_total']>=$lottery['todaytotal']){
                $arr['status']=0;
                $arr['message']="今天可获得的抽奖次数已经达到上限";
                echo json_encode($arr);
                exit;
            }
            $data['today_share_total']=$lotteryUser['today_share_total']+$lottery['share_unm_total'];
            $data['user_share_num']=$lotteryUser['user_share_num']+$lottery['share_unm_total'];
            $data['user_share_total']=$lotteryUser['user_share_total']+$lottery['share_unm_total'];
            $data['other_num']=$lotteryUser['other_num']+$lottery['share_unm_total'];
            m("lottery")->updateUserLottery($data,$_W['openid'],$lottery['id']);
            $arr=array("status"=>1,'share_unm_total'=>$lottery['share_unm_total'],"message"=>"分享获得".$lottery['share_unm_total']."次抽奖机会");
            echo json_encode($arr);
            exit;
         }
    }
    //获取抽奖列表
    function getlotterylist(){
        global $_W, $_GPC;
        $page=intval($_GPC['page']);
        if($page==0){
            $page=1;
        }
        $limit = ($page-1)*15;
        $task_sql = 'SELECT j.*,l.lottery_title,l.lottery_type FROM '.tablename('ewei_shop_lottery_join').' as j left join '.tablename('ewei_shop_lottery').' as l on j.lottery_id=l.lottery_id WHERE j.lottery_num>0 and j.uniacid=:uniacid AND j.`join_user`=:join_user AND l.`is_delete`=0 ORDER BY j.addtime DESC LIMIT '.$limit.',15';
        $lottery = pdo_fetchall($task_sql,array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        foreach ($lottery as $key=>$value){
            $lottery[$key]['addtime'] = date('Y-m-d',$value['addtime']);
            $lottery[$key]['link'] = mobileUrl('lottery/index/lottery_info',array('id'=>$value['lottery_id']),true);
            if($value['lottery_type']==1){
                $lottery[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/panicon.png';
            }elseif ($value['lottery_type']==2){
                $lottery[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/guaicon.png';
            }elseif ($value['lottery_type']==3){
                $lottery[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/gridicon.png';
            }
        }
        $task_sql = 'SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_join').'  WHERE lottery_num>0 and uniacid=:uniacid AND `join_user`=:join_user ';
        $count = pdo_fetchcolumn($task_sql,array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        show_json(1,array('list'=>$lottery,'total'=>$count,'pagesize'=>15));
    }

    //抽奖详情页
    function lottery_info(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if($id){
            $task_sql = 'SELECT * FROM '.tablename('ewei_shop_lottery').' WHERE uniacid=:uniacid AND lottery_id=:id AND `is_delete`=0  ';
            $lottery = pdo_fetch($task_sql,array(':uniacid'=>$_W['uniacid'],':id'=>$id));
            $reward = unserialize($lottery['lottery_data']);
            $set = pdo_fetchcolumn('SELECT `data` FROM '.tablename('ewei_shop_lottery_default') . " WHERE uniacid =:uniacid LIMIT 1",array(':uniacid'=>$_W['uniacid']));
            if(!empty($set)){
                $set = unserialize($set);
            }
            //链表查询
            $log = pdo_fetchall('SELECT l.*,m.`nickname`,m.`avatar` FROM '.tablename('ewei_shop_lottery_log').' AS l LEFT JOIN '.tablename('ewei_shop_member').' AS m ON m.openid=l.join_user WHERE l.uniacid=:uniacid AND l.lottery_id=:lottery_id AND l.is_reward=1 LIMIT 5',array(':uniacid'=>$_W['uniacid'],':lottery_id'=>$id));
            $has_changes = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_lottery_join').'where uniacid=:uniacid AND lottery_id=:lottery_id  AND join_user=:join_user and lottery_num>0',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid'],':lottery_id'=>$id));
            //链表查询
            //用户头像
            $member = m('member')->getMember($_W['openid'], true);
        }

        if(isset($lottery['lottery_type'])&&$lottery['lottery_type']==1){
            include $this->template('lottery/indexpan');
        }elseif(isset($lottery['lottery_type'])&&$lottery['lottery_type']==2){
            include $this->template('lottery/indexgua');
        }elseif (isset($lottery['lottery_type'])&&$lottery['lottery_type']==3){
            include $this->template('lottery/indexgrid');
        }else{
            include $this->template('lottery/indexpan');
        }
    }
    //抽奖奖励列表
    function lottery_reward(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if($id){
            $task_sql = 'SELECT * FROM '.tablename('ewei_shop_bigwheel_config').' WHERE uniacid=:uniacid AND id=:id ';
            $lottery = pdo_fetch($task_sql,array(':uniacid'=>$_W['uniacid'],':id'=>$id));
            $reward = unserialize($lottery['lottery_data']);
        }else{
            $reward='';
        }

        include $this->template('lottery/lotteryreward');
    }

    //我的中奖记录
    function myreward(){
        global $_W, $_GPC;
        //用户头像
        $member = m('member')->getMember($_W['openid'], true);
        $mylog = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_log').' WHERE uniacid=:uniacid  AND openid=:join_user AND is_reward=1 ',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        include $this->template('lottery/myreward');
    }
    function myrewardpage(){
        global $_W, $_GPC;
        $page = intval($_GPC['page']);
        if(empty($page)){
            $page=1;
        }
        $limit = ($page-1)*15;
        $mylog = pdo_fetchall('SELECT l.*,m.`nickname`,m.`avatar` FROM '.tablename('ewei_shop_lottery_log').' AS l LEFT JOIN '.tablename('ewei_shop_member').' AS m ON m.openid=l.openid WHERE l.uniacid=:uniacid  AND l.openid=:join_user AND (l.is_reward=1 or l.is_reward=2) order by addtime desc LIMIT '.$limit.',15',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        $count =  pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_log').' WHERE uniacid=:uniacid  AND openid=:join_user AND (is_reward=1 or is_reward=2)',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        //处理获奖记录数据
        foreach ($mylog as $key=>$value){
            $lottery_data = unserialize($value['lottery_data']);
            if (isset($lottery_data['credit'])){
                $mylog[$key]['title'] = '积分:'.$lottery_data['credit'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] ='../addons/ewei_shopv2/static/images/qd/jifen.png';
            }elseif(isset($lottery_data['money'])){
                $mylog[$key]['title'] = '奖金:'.$lottery_data['money']['num'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/static/images/qd/jiangjin.png';
            }elseif(isset($lottery_data['bribery'])){
                $mylog[$key]['title'] = '红包:'.$lottery_data['bribery']['num'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/static/images/qd/hongbao.png';
            }elseif(isset($lottery_data['goods'])){
                $goods = array_shift($lottery_data['goods']);
                $mylog[$key]['title'] = '特惠商品:'.$goods['title'];
                if($mylog[$key]['is_reward']==2){
                     $mylog[$key]['rewarded'] = 1;
                }else{
                     $mylog[$key]['rewarded'] = 0;
                }
               
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/static/images/qd/shangpin.png';
            }
            elseif(isset($lottery_data['hxgoods'])){
                $goods = array_shift($lottery_data['hxgoods']);
                $mylog[$key]['title'] = '特惠商品:'.$goods['title'];
                if($mylog[$key]['is_reward']==2){
                     $mylog[$key]['rewarded'] = 1;
                }else{
                     $mylog[$key]['rewarded'] = 0;
                }
               
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/static/images/qd/shangpin.png';
            }
            elseif(isset($lottery_data['coupon'])){
                $coupon = array_shift($lottery_data['coupon']);
                $mylog[$key]['title'] = '优惠券:'.$coupon['couponname'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/static/images/qd/quan.png';
            }
            $mylog[$key]['addtime'] =date('Y.m.d',$value['addtime']);
            $mylog[$key]['link'] =  mobileUrl('lottery/index/mygoods',array(),true);
        }

        show_json(1,array('list'=>$mylog,'total'=>$count,'pagesize'=>15));
    }
    //获奖方式说明
    function getlottery(){
        global $_W, $_GPC;
        $set_info = pdo_fetchcolumn('SELECT `data` FROM '.tablename('ewei_shop_lottery_default') . " WHERE uniacid =:uniacid LIMIT 1",array(':uniacid'=>$_W['uniacid']));
        $set_info = unserialize($set_info);
        $set_info = unserialize($set_info['lotteryinfo']);
        $set_info = htmlspecialchars_decode($set_info);
        include $this->template('lottery/getlottery');
    }

    //中奖算法
    private  function getRand($proArr) {
        $result = '';

        //概率数组的总概率精度
        $proSum = array_sum($proArr);

        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);

        return intval($result);
    }

    //获取到获奖id后抽奖
    function getreward(){
        //抽奖之前循环判断商品是否还有库存，红包是否还有余额，若为0，则取消此奖项的概率计算
        global $_W, $_GPC;

        //获得抽奖任务id
        $id = intval($_GPC['lottery']);
        //检测并发
        $check =$this->checkSubmit('lottery_'.$id);

        if(is_error($check)){
            $data = array(
                'status'=>0,
                'info'=> $check['message']
            );
            echo json_encode($data);
            exit();
        }
        //检查活动状态
        $lottery=m("lottery")->getLottery($id);
        if(empty($lottery)){
            $data = array(
                'status'=>0,
                'info'=> "活动失效或者活动未启用",
            );
            echo json_encode($data);
            exit();
        }
        $total_cannot= $lottery['total_cannot'];//当前活动最多抽奖次数
        $todaytotal=$lottery['todaytotal'];//额外最多获得的抽奖次数
        $prizetotal=$lottery['prizetotal'];//活动最多中奖次数
        $todayusetotal=$lottery['todayusetotal'];//每天最多抽奖次数
        //检查当前活动状态
        $returndata=m("lottery")->checkLotterystatus($lottery);
        if($returndata['status']==-1){
            $data = array(
                'status'=>0,
                'info'=> $returndata['message'],
            );
            echo json_encode($data);
            exit();
        }
        $lotteryUser=m('lottery')->getUserLottery($id,$_W['openid']);
        if($total_cannot!=-1 && $total_cannot<=$lotteryUser['total_num']){//检测用户抽奖总次数是否达到上限
             $data = array(
                'status'=>0,
                'info'=> "您的抽奖次数已经达到上限",
            );
            echo json_encode($data);
            exit();
        }
        //更新用户抽奖次数和抽奖时间
            $bool=m("nsignutil")->getnextdate($lotteryUser['last_time']);
            if($bool){
                $data['last_time']=time();
                $data['lottery_num']=0;
                $lotteryUser['lottery_num']=0;
                m("lottery")->updateUserLottery($data,$_W['openid'],$lottery['id']);
            }
            //检测用户每天抽奖是否达到上限
            if($todayusetotal!=-1 && $todayusetotal<=$lotteryUser['lottery_num']){
                $data = array(
                    'status'=>0,
                    'info'=> "您今天的抽奖次数已经达到上限,明天在来吧",
                );
                echo json_encode($data);
                exit();
            }
            //检查 用户 是否有赠送次数
            //如果当天的抽奖次数比赠送次数多
            if($lotteryUser['lottery_num']>=$lottery['usegivecondition']){
                 //优先消耗用户获得的分享次数
                if($lotteryUser['user_share_num']>0){
                   $data['user_share_num']=$lotteryUser['user_share_num']-1;
                   m("lottery")->updateUserLottery($data,$_W['openid'],$lottery['id']);
                }
                //额外消耗用户获得次数
                if($lotteryUser['today_num']>0){
                   $data['today_num']=$lotteryUser['today_num']-1;
                   m("lottery")->updateUserLottery($data,$_W['openid'],$lottery['id']);
                }
                //检查是否开始积分抽奖
                if($lottery['usecreditcondition']!=0 && $lottery['usecreditcondition']!=-1){
                    $s=m('member')->getCredits($_W['openid']);
                    $has_changes=intval($s['credit1']/$lottery['usecreditcondition']);
                    if($has_changes<=0){
                        $data = array(
                            'status'=>0,
                            'info'=> "积分不够,请获取足够的积分再来",
                        );
                        echo json_encode($data);
                        exit();
                    }
                    m('member')->setCredit($_W['openid'],'credit1',-$lottery['usecreditcondition']);
                    $sy=$s['credit1']-$lottery['usecreditcondition'];
                    $msg="抽奖消耗:".$lottery['usecreditcondition']."积分"."总积分:".$sy;
                    m("nsignutil")->sendmassage($_W['openid'],$msg);
                }else{
                    $data = array(
                            'status'=>0,
                            'info'=> "赠送次数已经用完,明天在来吧",
                        );
                    echo json_encode($data);
                    exit();
                }
            }
        //检测 用户是否中奖
        $userPrize=m('lottery')->getUserPrize($id,$_W['openid']);
        if(!empty($userPrize)){
            $userPrizecount=count($userPrize);
            if($prizetotal!=-1 && $prizetotal<=$userPrizecount){
                $data = array(
                    'status'=>0,
                    'info'=> "您已经中奖多次了,给别人留点机会吧",
                );
                echo json_encode($data);
                exit();
            }
        }


        $reward = unserialize($lottery['lottery_data']);
        //如果已经抽过奖直接停止
        $temreward = array();
        foreach ($reward as $key=>$value){
            if(isset($value['reward']['goods'])){
                $pass=0;
                foreach ($value['reward']['goods'] as $val){
                    if($val['count']>=$val['total']){
                        $pass = 1;
                    }
                }
                if($pass==1){
                    $temreward[$key]=$value['probability'];
                    
                }
              
            }
            elseif(isset($value['reward']['hxgoods'])){
                $pass=0;
                
                foreach ($value['reward']['hxgoods'] as $val){
                    if($val['count']>=$val['total']){
                        $pass = 1;
                    }
                    
                }
              
                if($pass==1){
                    $temreward[$key]=$value['probability'];
                }
            }

            elseif(isset($value['reward']['money'])){
                if($value['reward']['money']['total']>=$value['reward']['money']['num']){
                    $temreward[$key]=$value['probability'];
                }
            }elseif (isset($value['reward']['bribery'])){
                if($value['reward']['bribery']['total']>=$value['reward']['bribery']['num']){
                    $temreward[$key]=$value['probability'];
                }
            }elseif (isset($value['reward']['coupon'])){
                $pass=0;
                foreach ($value['reward']['coupon'] as $val){

                    if(!empty($val['count']) && $val['count']>=$val['couponnum']){
                        $pass=1;
                    }
                }
                if($pass==1){
                    $temreward[$key]=$value['probability'];
                }
            }else{
                $temreward[$key]=$value['probability'];
            }
        }
        //如果总限制用完
        if (empty($temreward)){
            $data = array(
                'status'=>0,
                'info'=>'很遗憾,奖品库存不足了!',
            );
            echo json_encode($data);
            exit();
        }


        $reward_id =$this->getRand($temreward);
        $reward_info = $reward[$reward_id]['reward'];

        $is_reward = 0;
        if(empty($reward_info)){
            $is_reward = 0;
            $reward_info = '很遗憾,没有中奖';
        }else{
            $is_reward = 1;
            if(isset($reward_info['credit'])){
                $reward_info = $reward[$reward_id]['title'];
            }
            if(isset($reward_info['bribery'])){
                $reward_info = $reward[$reward_id]['title'];
            }
            if(isset($reward_info['money'])){
                $reward_info = $reward[$reward_id]['title'];
            }
            if(isset($reward_info['goods'])){
                $reward_info = $reward[$reward_id]['title'];
            }
            if(isset($reward_info['hxgoods'])){
                $reward_info = $reward[$reward_id]['title'];
            }
            if(isset($reward_info['coupon'])){
                $reward_info = $reward[$reward_id]['title'];
            }
        }
        
        $tempreward_id=$reward_id;
        if($lottery['firstprize']!=-1){
            if($lotteryUser['total_num']==0){
                $firstprize=$lottery['firstprize'];
                if(!empty($reward[$firstprize])){
                   
                    if(isset($reward[$firstprize]['reward'])){
                         $reward_id=$firstprize;
                         $value=$reward[$reward_id];

                         if(isset($value['reward']['goods'])){
                            foreach ($value['reward']['goods'] as $val){
                                if($val['count']>$val['total']){
                                   $reward_id=$tempreward_id;
                                   $is_reward=1;
                                }
                            }

                         }elseif(isset($value['reward']['hxgoods'])){
                            foreach ($value['reward']['hxgoods'] as $val){
                             if($val['count']>$val['total']){
                                   $reward_id=$tempreward_id;
                                   $is_reward=1;
                                }
                            }
                         }elseif (isset($value['reward']['coupon'])){
                            foreach ($value['reward']['coupon'] as $val){
                            if(!empty($val['count']) && $val['count']>$val['couponnum']){
                                   $reward_id=$tempreward_id;
                                   $is_reward=1;
                                }
                            }
                         }

                     //var_dump($reward_id);
                         //die();
                    }
                }
            }
        }
        $log_data = array(
            'uniacid'=>$_W['uniacid'],
            'lottery_id'=>$id,
            'openid'=>$_W['openid'],
            'lottery_data'=>serialize($reward[$reward_id]['reward']),
            'is_reward'=>$is_reward,
            'addtime'=>time()
        );
        pdo_insert('ewei_shop_lottery_log',$log_data);
        $params=array(':lottery_id'=>$id,':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid'],':id'=>$lotteryUser['id']);
        $res = pdo_query('UPDATE '.tablename('ewei_shop_user_lottery').' SET lottery_num=lottery_num+1,total_num=total_num+1 WHERE uniacid=:uniacid AND lottery_id=:lottery_id AND openid=:openid and id=:id',$params);

       //pdo_debug();
        if($res===false){
            load()->func('logging');
            logging_run('更新抽奖次数失败');
        }
        $data = array(
            'status'=>1,
            'id'=>$reward_id,
            'info'=>$reward_info,
            'is_reward'=>$is_reward
        );
        echo json_encode($data);
        exit();
    }

    /*
     * 获取奖励
     * 赵坤
     * 20161212
     * */
    /**
     * @return mixed
     */
    public function reward()
    {
        global $_W, $_GPC;
        if($_GPC['lottery']){
            $id = intval($_GPC['lottery']);
        }
        if(isset($_GPC['reward'])){
            $reward_id = intval($_GPC['reward']);
        }
        $lottery = m('lottery')->getLottery($id);
        if(empty($lottery)){
            $info = array(
                'status'=>0,
                'info'=>'此抽奖活动已不存在'
            );
            echo json_encode($info);
            exit();
        }
        $reward = unserialize($lottery['lottery_data']);
        $reward = $reward[$reward_id]['reward'];

        m('lottery')->reward($reward,$_W['openid'],$lottery['lottery_title'],$id);


        //减少奖励库存ewei_shop_lottery
        if(isset($reward['money'])){
            $reward['money']['total']-=$reward['money']['num'];
        }
        if(isset($reward['bribery'])){
            $reward['bribery']['total']-=$reward['bribery']['num'];
        }
        if(isset($reward['coupon'])){
            foreach ($reward['coupon'] as $key=>$val){
                @$reward['coupon'][$key]['count'] -= $val['couponnum'];
            }
        }
        if(isset($reward['goods'])){
            foreach ($reward['goods'] as $key=>$val){
                if(empty($val['spec'])){
                    $reward['goods'][$key]['count'] -= $val['total'];
                }else{
                    foreach ($val['spec'] as $k=>$v){
                        $total = $v['total'];
                    }
                    $reward['goods'][$key]['count'] -= $total;
                }
            }
        }
        if(isset($reward['hxgoods'])){
            foreach ($reward['hxgoods'] as $key=>$val){
                if(empty($val['spec'])){
                    $reward['hxgoods'][$key]['count'] -= $val['total'];
                }else{
                    foreach ($val['spec'] as $k=>$v){
                        $total = $v['total'];
                    }
                    $reward['hxgoods'][$key]['count'] -= $total;
                }
            }
        }
        $temreward = unserialize($lottery['lottery_data']);
        $temreward[$reward_id]['reward']=$reward;

        $lottery_data = array(
            'lottery_data'=>serialize($temreward)
        );
        $res = pdo_update('ewei_shop_bigwheel_config',$lottery_data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        if($res!==false){
            $info = array(
                'status'=>1,
                'info' =>'恭喜您已获得'.$temreward[$reward_id]['title']
            );
            echo json_encode($info);
            exit();
        }else{
            $info = array(
                'status'=>0,
                'info' =>'获取奖励失败'
            );
            echo json_encode($info);
            exit();
        }
    }
    //获取奖励商品列表
    public function mygoods(){
        global $_W, $_GPC;
        $loglist = pdo_fetchall('SELECT l.* FROM '.tablename('ewei_shop_lottery_log').' AS l WHERE l.uniacid=:uniacid  AND l.openid=:join_user AND l.is_reward=1',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        $goodslist =array();
        foreach ($loglist as $key=>$value){
            $log_id = $value['id'];
            $value = unserialize($value['lottery_data']);
            if(isset($value['goods'])&&!empty($value['goods'])){
                $goods = array_shift($value['goods']);
                $goods['log_id']=$log_id;
                if(!empty($goods)){
                    $searchsql = 'SELECT thumb,marketprice FROM ' . tablename('ewei_shop_goods') . ' WHERE uniacid= '.$_W['uniacid'].' and id='.$goods['id'].' and status=1 and deleted=0';
                    $goodsinfo = pdo_fetch($searchsql);
                    $thumb = tomedia($goodsinfo['thumb']);
                    $goods['thumb']=$thumb;
                    $goods['oldprice'] = $goodsinfo['marketprice'];
                }
                array_push($goodslist,$goods);
            }
            if(isset($value['hxgoods'])&&!empty($value['hxgoods'])){
                $goods = array_shift($value['hxgoods']);
                $goods['log_id']=$log_id;
                if(!empty($goods)){
                    $searchsql = 'SELECT thumb,marketprice FROM ' . tablename('ewei_shop_goods') . ' WHERE uniacid= '.$_W['uniacid'].' and id='.$goods['id'].' and status=1 and deleted=0';
                    $goodsinfo = pdo_fetch($searchsql);
                    $thumb = tomedia($goodsinfo['thumb']);
                    $goods['thumb']=$thumb;
                    $goods['oldprice'] = $goodsinfo['marketprice'];
                }
                array_push($goodslist,$goods);
            }
        }
        //var_dump($goodslist);
        include $this->template('lottery/goodslist');
    }
    //并发处理
    function checkSubmit($key, $time = 2, $message = '操作频繁，请稍后再试!')
    {

        global $_W;
        $open_redis = function_exists('redis') && !is_error(redis());
        if ($open_redis) {
            $redis_key = "{$_W['setting']['site']['key']}_{$_W['account']['key']}_{$_W['uniacid']}_{$_W['openid']}_mobilesubmit_{$key}";
            $redis = redis();
            if ($redis->setnx($redis_key, time())) {
                $redis->expireAt($redis_key, time() + $time);
            } else {
                return error(-1, $message);
            }
        }
        return true;

    }
//    //检测是否获得抽奖
//    function checkchanges(){
//        $this->model->check_isreward();
//    }
}
