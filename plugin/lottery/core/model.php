<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class LotteryModel extends PluginModel {

    /*
       * 提供指定价格商品接口
       * 赵坤
       * 20161012
       * $param = array('goods_num'=>1,'goods_id'=>1,'goods_spec'=>1,'openid'=>'openid','log_id'=>'log_id '); goods_num:待减数量
       * */
    public function getGoods($param=''){
        if(empty($param)){
            return false;
        }

        if(!isset($param['log_id'])||empty($param['log_id'])){
            return false;
        }else{
            $param['log_id']=intval($param['log_id']);
        }


        $log = pdo_fetch('select * from '.tablename('ewei_shop_lottery_log').' where log_id=:log_id and join_user=:join_user and is_reward=1',array(':log_id'=>$param['log_id'],':join_user'=>$param['openid']));
        if(empty($log)){

            return false;
        }

        $lottery_data = unserialize($log['lottery_data']);

        $goods_info = $lottery_data['goods'][$param['goods_id']];
        if(isset($param['goods_num'])&&!empty($param['goods_num'])){
            /*减库存操作*/
            $goods_num=intval($param['goods_num']);
            if($goods_num==0){
                return true;
            }

            if(!empty($goods_info['spec'])){
                //规格
                if($goods_num>$goods_info['spec'][$param['goods_spec']]['total']){

                    return false;
                }else{

                    $lottery_data['goods'][$param['goods_id']]['spec'][$param['goods_spec']]['total']-=$goods_num;
                    pdo_update('ewei_shop_lottery_log',array('lottery_data'=>serialize($lottery_data)),array('log_id'=>$param['log_id']));

                    return true;
                }
            }else{
                //无规格
                if($goods_num>$goods_info['total']){

                    return false;
                }else{
                    $lottery_data['goods'][$param['goods_id']]['total']-=$goods_num;
                    pdo_update('ewei_shop_lottery_log',array('lottery_data'=>serialize($lottery_data)),array('log_id'=>$param['log_id']));
                    return true;
                }
            }
        }else{
            $lottery = pdo_fetch('select lottery_days,is_goods from '.tablename('ewei_shop_lottery').' where lottery_id=:lottery_id',array(':lottery_id'=>$log['lottery_id']));
            $date = $lottery['lottery_days']+$log['addtime'];
            if($date>time() || empty($data)){
                $goods_info['is_goods']=$lottery['is_goods'];
                return $goods_info;
            }else{
                message('奖励已经过期','','warning');
                return false;
            }

        }
    }
    /*
     * 获取抽奖机会
     * 赵坤
     * 20161212
     *
     * */
    public function getLottery($openid,$type,$data){
        global $_W;
        //type 1:消费 2:签到 3:任务 4:其他

        $lotterylist = pdo_fetchall('SELECT * FROM '.tablename('ewei_shop_lottery').' WHERE uniacid='.$_W['uniacid'].' AND start_time<'.time().' AND  end_time>'.time().' AND is_delete=0 AND task_type='.$type.' ORDER BY addtime DESC');

        if(!empty($lotterylist)){
            if($type==1){
                //消费
                $join_info = array('money'=>0,'num'=>0,'lottery_id'=>0);
                foreach ($lotterylist as $key=>$value){
                    $value['task_data'] = unserialize($value['task_data']);
                    if($value['task_data']['pay_type']==0||$data['paytype']==$value['task_data']['pay_type']) {
                        if ($data['money'] >= $value['task_data']['pay_money']) {
                            if ($value['task_data']['pay_money'] > $join_info['money']) {
                                $join_info['money'] = $value['task_data']['pay_money'];
                                $join_info['num'] = $value['task_data']['pay_num'];
                                $join_info['lottery_id'] = $value['lottery_id'];
                            }
                        }
                    }
                }
                if(!empty($join_info['lottery_id'])){
                    for($i=1;$i<=$join_info['num'];$i++){
                        $join_data=array(
                            'uniacid'=>$_W['uniacid'],
                            'join_user'=>$openid,
                            'lottery_id'=>$join_info['lottery_id'],
                            'lottery_num'=>1,
                            'lottery_tag'=>'消费满'.$join_info['money'].'元,赠'.$join_info['num'].'次',
                            'addtime'=>time()
                        );
                        pdo_insert('ewei_shop_lottery_join',$join_data);
                    }
                    return $join_info['lottery_id'];
                }

                return false;

            }elseif ($type==2){
                //签到
                $is_reward=false;
                foreach ($lotterylist as $key=>$value){
                    $value['task_data'] = unserialize($value['task_data']);
                    if($data['day']==$value['task_data']['sign_day']){
                        if($value['task_data']['sign_num']>0){
                            for($i=1;$i<=$value['task_data']['sign_num'];$i++){
                                $join_data=array(
                                    'uniacid'=>$_W['uniacid'],
                                    'join_user'=>$openid,
                                    'lottery_id'=>$value['lottery_id'],
                                    'lottery_num'=>1,
                                    'lottery_tag'=>'签到满'.$value['task_data']['sign_day'].'天,赠'.$value['task_data']['sign_num'].'次',
                                    'addtime'=>time()
                                );
                                pdo_insert('ewei_shop_lottery_join',$join_data);
                            }
                           $is_reward=$value['lottery_id'];
                        }
                    }
                }
                return $is_reward;
            }elseif ($type==3){
                //任务
                $is_reward=false;
                foreach ($lotterylist as $key=>$value){
                    $value['task_data'] = unserialize($value['task_data']);
                    if($data['taskid']==$value['task_data']['poster_id'] || $value['task_data']['poster_id']==0){
                        if($value['task_data']['poster_num']>0){
                            for($i=1;$i<=$value['task_data']['poster_num'];$i++){
                                $join_data=array(
                                    'uniacid'=>$_W['uniacid'],
                                    'join_user'=>$openid,
                                    'lottery_id'=>$value['lottery_id'],
                                    'lottery_num'=>1,
                                    'lottery_tag'=>'完成任务海报,赠'.$value['task_data']['poster_num'].'次',
                                    'addtime'=>time()
                                );
                                pdo_insert('ewei_shop_lottery_join',$join_data);
                            }

                            $is_reward=$value['lottery_id'];
                        }
                    }
                }
                return $is_reward;
            }elseif ($type==4){
                //其他
                $is_reward=false;
                foreach ($lotterylist as $key=>$value){
                    $value['task_data'] = unserialize($value['task_data']);
                    if($data['taskid']==$value['task_data']['poster_id']){
                        if($value['task_data']['poster_num']>0){
                            for($i=1;$i<=$value['task_data']['poster_num'];$i++){
                                $join_data=array(
                                    'uniacid'=>$_W['uniacid'],
                                    'join_user'=>$openid,
                                    'lottery_id'=>$value['lottery_id'],
                                    'lottery_num'=>1,
                                    'lottery_tag'=>'完成任务海报,赠'.$value['task_data']['poster_num'].'次',
                                    'addtime'=>time()
                                );
                                pdo_insert('ewei_shop_lottery_join',$join_data);
                            }
                            $is_reward=$value['lottery_id'];
                        }
                    }
                }
                return $is_reward;
            }
        }

    }

    /*
     * 发送抽奖列表
     * 赵坤
     * 20161215
     * */
    public function getLotteryList($openid,$param=array()){
        global $_W;
        if(empty($openid)){
           return false;
        }
        //url调用，避免5秒超时返回
        $lottery_list = pdo_fetch('select j.*,l.lottery_title from '.tablename('ewei_shop_lottery_join').' as j left join '.tablename('ewei_shop_lottery').' as l on j.lottery_id=l.lottery_id where j.lottery_num>0 and j.uniacid=:uniacid and j.join_user=:join_user and j.lottery_id=:lottery_id and l.is_delete=0',array(':uniacid'=>$_W['uniacid'],':join_user'=>$openid,':lottery_id'=>intval($param['lottery_id'])));

        if(!empty($lottery_list)){

            $datas = array(
                array("name" => "活动名称", "value" => $lottery_list['title']),
            );
            $url = mobileUrl('lottery/index/lottery_info',array('id'=>$param['lottery_id']),true);
            $remark  =  "\n<a href='{$url}'>点击去抽奖</a>";

            $text = "恭喜您获得抽奖机会 \n".$remark;

            $message = array(
                'first' => array('value' => "恭喜您获得抽奖机会", "color" => "#000000"),
                'keyword1' => array('value' => $lottery_list['title'], "color" => "#000000"),
                'keyword2' => array('value' => '获得抽奖机会', "color" => "#000000"),
                'remark' => array('value' => "恭喜您获得抽奖机会！！", "color" => "#000000")
            );

            m('notice')->sendNotice(array(
                "openid" => $openid,
                'tag' => 'lottery_get',
                'default' => $message,
                'cusdefault' => $text,
                'url' => $url,
                'datas' => $datas
            ));
            // 短信通知
            //com_run('sms::callsms', array('tag' => 'backpoint_ok', 'datas' => $datas, 'mobile' => $member['mobile']));
        }
    }

    //奖励抽奖
    public function reward($poster,$openid,$title,$lottery_id){
        if(empty($poster)||empty($openid)){
            return false;
        }
        global $_W;
        //载入日志函数

        //积分
        if (isset($poster['credit']) && $poster['credit'] > 0) {
            m('member')->setCredit($openid, 'credit1', $poster['credit'], array(0, '推荐扫码关注积分+' . $poster['credit']));
        }
        //现金
        if (isset($poster['money']) && $poster['money']['num'] > 0) {
            // $val['money']['type'] 0:余额1：微信
            $pay = $poster['money']['num'];
            if ($poster['money']['type'] == 1) {
                $pay *= 100;
            }
            m('finance')->pay($openid, $poster['money']['type'], $pay, '', '任务活动推荐奖励', false);
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
                    $plugin_coupon->taskposter(array('openid'=>$openid), $v['id'], $v['couponnum']);
                }
            }
        }
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
    //检测是否有获得抽奖机会（15秒钟之间是否有新生成的抽奖机会）
    public function check_isreward(){
        global $_W;
        $end_time = time();
        $start_time = $end_time-15;
        $changes = pdo_fetch('select * from '.tablename('ewei_shop_lottery_join').' where uniacid=:uniacid and join_user=:join_user and addtime>'.$start_time.' and addtime<='.$end_time.' limit 1',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        if(!empty($changes)){
            return array('is_changes'=>1,'lottery'=>$changes);
//            show_json(1,$changes);
        }else{
            return array('is_changes'=>0);
//            show_json(0);
        }
    }
    

}
