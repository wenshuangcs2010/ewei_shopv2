<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class index_EweiShopV2Page extends PluginMobilePage {

    function main() {
        global $_W, $_GPC;
        $task_sql = 'SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_join').'  WHERE lottery_num>0 and uniacid=:uniacid AND `join_user`=:join_user ';
        $lottery = pdo_fetchcolumn($task_sql,array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        //检查是否有积分抽奖
        $newtime=time();
        $creditLottey_sql="SELECT * from ".tablename("ewei_shop_lottery")." where task_type=5 and start_time<={$newtime} and end_time>{$newtime} and is_delete=0 LIMIT 1";
        $creditLottey=pdo_fetch($creditLottey_sql);
        $creditLottey['addtime']=date('Y-m-d',$creditLottey['addtime']);
        include $this->template();
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
                $lottery[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/q/gridicon.png';
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
            if($lottery['task_type']==5){
                $member=m("member")->getInfo($_W['openid']);
                $credit1=$member['credit1'];
                $ss=unserialize($lottery['task_data']);
                $has_changes= floor($credit1/$ss['credit']);
            }else{
                $has_changes = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_lottery_join').'where uniacid=:uniacid AND lottery_id=:lottery_id  AND join_user=:join_user and lottery_num>0',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid'],':lottery_id'=>$id));
            }
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
            $task_sql = 'SELECT * FROM '.tablename('ewei_shop_lottery').' WHERE uniacid=:uniacid AND lottery_id=:id AND `is_delete`=0  ';
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
        $mylog = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_log').' WHERE uniacid=:uniacid  AND join_user=:join_user AND is_reward=1 ',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));

        include $this->template('lottery/myreward');
    }
    function myrewardpage(){
        global $_W, $_GPC;
        $page = intval($_GPC['page']);
        if(empty($page)){
            $page=1;
        }
        $limit = ($page-1)*15;
        $mylog = pdo_fetchall('SELECT l.*,m.`nickname`,m.`avatar` FROM '.tablename('ewei_shop_lottery_log').' AS l LEFT JOIN '.tablename('ewei_shop_member').' AS m ON m.openid=l.join_user WHERE l.uniacid=:uniacid AND m.uniacid=:uniacid AND l.join_user=:join_user AND l.is_reward=1 order by addtime desc  LIMIT '.$limit.',15',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        $count =  pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_log').' WHERE uniacid=:uniacid  AND join_user=:join_user AND is_reward=1 ',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
      
        //处理获奖记录数据
        foreach ($mylog as $key=>$value){
            $lottery_data = unserialize($value['lottery_data']);
            if (isset($lottery_data['credit'])){
                $mylog[$key]['title'] = '积分:'.$lottery_data['credit'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] ='../addons/ewei_shopv2/plugin/lottery/static/images/jifen.png';
            }elseif(isset($lottery_data['money'])){
                $mylog[$key]['title'] = '奖金:'.$lottery_data['money']['num'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/jiangjin.png';
            }elseif(isset($lottery_data['bribery'])){
                $mylog[$key]['title'] = '红包:'.$lottery_data['bribery']['num'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/hongbao.png';
            }elseif(isset($lottery_data['goods'])){
                $goods = array_shift($lottery_data['goods']);
                $mylog[$key]['title'] = '特惠商品:'.$goods['title'];
                if($goods['total']==0){
                    $mylog[$key]['rewarded'] = 1;
                }else{
                    $mylog[$key]['rewarded'] = 0;
                }
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/shangpin.png';
            }elseif(isset($lottery_data['coupon'])){
                $coupon = array_shift($lottery_data['coupon']);
                $mylog[$key]['title'] = '优惠券:'.$coupon['couponname'];
                $mylog[$key]['rewarded'] = 1;
                $mylog[$key]['icon'] = '../addons/ewei_shopv2/plugin/lottery/static/images/quan.png';
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
        if(!empty($id)){
            $task_sql = 'SELECT * FROM '.tablename('ewei_shop_lottery').' WHERE uniacid=:uniacid AND lottery_id=:id AND `is_delete`=0  ';
            $lottery = pdo_fetch($task_sql,array(':uniacid'=>$_W['uniacid'],':id'=>$id));
            $reward = unserialize($lottery['lottery_data']);
        }
       
        //检测是否已经抽过奖
        if($lottery['task_type']==5){
            $member=m("member")->getInfo($_W['openid']);
            $credit1=$member['credit1'];
            $ss=unserialize($lottery['task_data']);
            $lottery_credit1=$ss['credit'];
            $has_changes= floor($credit1/$lottery_credit1);
        }else{
            $has_changes = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('ewei_shop_lottery_join').' WHERE uniacid=:uniacid AND lottery_id=:id AND join_user=:join_user AND lottery_num>0',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid'],':id'=>$id));
        }
        //如果已经抽过奖直接停止
        if(empty($has_changes)){
            $data = array(
                'status'=>0,
                'info'=>$lottery['lottery_cannot']
            );
            echo json_encode($data);
            exit();
        }else{
            if($lottery['task_type']==5){
                $logtag="消耗".$lottery_credit1."积分换取一次抽奖机会";
                $data=array(
                    'uniacid'=>$_W['uniacid'],
                    'join_user'=>$_W['openid'],
                    'lottery_id'=>$id,
                    'lottery_num'=>1,
                    'lottery_tag'=>$logtag,
                    'addtime'=>time(),
                    );
                pdo_insert("ewei_shop_lottery_join",$data);
                $join_id=pdo_insertid();
                if($join_id==0){
                    $data = array(
                        'status'=>0,
                        'info'=>"数据效验错误重新抽奖",
                    );
                    echo json_encode($data);
                    exit();
                }
            }else{
                $join_id = pdo_fetchcolumn('SELECT `id` FROM '.tablename('ewei_shop_lottery_join').' WHERE uniacid=:uniacid AND lottery_id=:id AND join_user=:join_user AND lottery_num>0 order by addtime desc limit 1',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid'],':id'=>$id));
            }
            
        }

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
            }elseif(isset($value['reward']['money'])){
                if($value['reward']['money']['total']>=$value['reward']['money']['num']){
                    $temreward[$key]=$value['probability'];
                }
            }elseif (isset($value['reward']['bribery'])){
                if($value['reward']['bribery']['total']>=$value['reward']['bribery']['num']){
                    $temreward[$key]=$value['probability'];
                }
            }elseif (isset($value['reward']['coupon'])){
                $pass=0;
                //
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
            if(isset($reward_info['coupon'])){
                $reward_info = $reward[$reward_id]['title'];
            }
        }

        $log_data = array(
            'uniacid'=>$_W['uniacid'],
            'lottery_id'=>$id,
            'join_user'=>$_W['openid'],
            'lottery_data'=>serialize($reward[$reward_id]['reward']),
            'is_reward'=>$is_reward,
            'addtime'=>time()
        );
        pdo_insert('ewei_shop_lottery_log',$log_data);
        if($lottery['task_type']==5){
            m("member")->setCredit($_W['openid'],'credit1',-$lottery_credit1,$logtag);
        }
        $res = pdo_query('UPDATE '.tablename('ewei_shop_lottery_join').' SET lottery_num=lottery_num-1 WHERE uniacid=:uniacid AND lottery_id=:id AND join_user=:join_user and id=:join_id',array(':id'=>$id,':join_id'=>$join_id,':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
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
        $task_sql = 'SELECT * FROM '.tablename('ewei_shop_lottery').' WHERE uniacid=:uniacid AND lottery_id=:id AND `is_delete`=0  ';
        $lottery = pdo_fetch($task_sql,array(':uniacid'=>$_W['uniacid'],':id'=>$id));
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

        $this->model->reward($reward,$_W['openid'],$lottery['lottery_title'],$id);


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
        $temreward = unserialize($lottery['lottery_data']);
        $temreward[$reward_id]['reward']=$reward;

        $lottery_data = array(
            'lottery_data'=>serialize($temreward)
        );
        $res = pdo_update('ewei_shop_lottery',$lottery_data,array('uniacid'=>$_W['uniacid'],'lottery_id'=>$id));
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
        $loglist = pdo_fetchall('SELECT l.* FROM '.tablename('ewei_shop_lottery_log').' AS l WHERE l.uniacid=:uniacid  AND l.join_user=:join_user AND l.is_reward=1',array(':uniacid'=>$_W['uniacid'],':join_user'=>$_W['openid']));
        $goodslist =array();
        foreach ($loglist as $key=>$value){
            $log_id = $value['log_id'];
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
        }

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
