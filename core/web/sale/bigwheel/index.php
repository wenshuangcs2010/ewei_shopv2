<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage {

	function main(){
		global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $params = array(':uniacid' => $_W['uniacid']);
        $condition = " and uniacid=:uniacid and `is_delete`=0 ";
        $sql="SELECT * FROM " . tablename('ewei_shop_bigwheel_config') . " WHERE 1 ".$condition." order by add_times desc, displayorder desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params);
        //var_dump($list);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_bigwheel_config') . " where 1 {$condition} ", $params);
        $pager = pagination($total, $pindex, $psize);
		include $this->template();
	}
	function add(){
		$this->post();
	}
	function edit(){
		$this->post();
	}
    function delete(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
      
        $ret=pdo_update('ewei_shop_bigwheel_config',array('is_delete'=>1),array('id' => $id, 'uniacid' => $_W['uniacid']));
        plog('lottery.delete', "删除抽奖 ID: {$id}");
        if($ret){
           show_json(1);  
       }else{
        show_json(0,array("message"=>"删除失败"));  
       }
       
    }
	function post(){
		global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $type = empty($_GPC['lottery_type']) ? 1 : intval($_GPC['lottery_type']);
		if(empty($item['start_time'])||empty($item['end_time'])){
            $starttime = time();
            $endtime = strtotime(date('Y-m-d H:i', $starttime) . "+30 days");
        }else{
            $starttime = $item['start_time'];
            $endtime = $item['end_time'];
        }
        if($id){
            $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_bigwheel_config') . " WHERE id =:lottery_id and uniacid=:uniacid limit 1", array(':lottery_id' => $id, ':uniacid' => $_W['uniacid']));
            $type = intval($item['lottery_type']);
            $reward = unserialize($item['lottery_data']);
        }
		if($_W['ispost']){
            $data=array(
                'lottery_type'=>$type,
                'uniacid'=>$_W['uniacid'],
                'displayorder'=>$_GPC['displayorder'],
                'activityname'=>$_GPC['activityname'],
                'thumb'=>$_GPC['thumb'],
                'starttime'=>$starttime,
                'endtime'=>$endtime,
                'task_type'=>$_GPC['task_type'],
                'prizetype'=>$_GPC['prizetype'],
                'usecreditcondition'=>intval($_GPC['usecreditcondition']),
                'usegivecondition'=>intval($_GPC['usegivecondition']),
                'lottery_cannot'=>$_GPC['lottery_cannot'],
                'total_cannot'=>intval($_GPC['total_cannot']),
                'todaytotal'=>intval($_GPC['todaytotal']),
                'prizetotal'=>intval($_GPC['prizetotal']),
                'todayusetotal'=>intval($_GPC['todayusetotal']),
                'status'=>intval($_GPC['status']),
                'desc'=>trim($_GPC['desc']),
                'add_times'=>time(),
                'share_unm_total'=>intval($_GPC['share_unm_total']),
                'firstprize'=>empty($_GPC['firstprize'])?-1:$_GPC['firstprize'],
                'share_title'=>$_GPC['share_title'],
                'share_thumb'=>$_GPC['share_thumb'],
                'share_desc'=>$_GPC['share_desc'],
                'link_url'=>$_GPC['link_url'],
                );
           
			$reward = array();
            $rec_reward = htmlspecialchars_decode($_GPC['reward_data']);
            $rec_reward = json_decode($rec_reward,1);
            $rec_data = array();
            if(!empty($rec_reward)){
                foreach($rec_reward as $val){
                    $rank = intval($val['rank']);
                    if($val['type'] == 1){
                        $rec_data[$rank]['credit']=intval($val['num']);
                    }elseif($val['type']==2){
                        $rec_data[$rank]['money']['num']=intval($val['num']);
                        $rec_data[$rank]['money']['total']=intval($val['total']);
                        $rec_data[$rank]['money']['type']=intval($val['moneytype']);
                    }elseif($val['type']==3){
                        $rec_data[$rank]['bribery']['total']=intval($val['total']);
                        $rec_data[$rank]['bribery']['num']=intval($val['num']);
                    }elseif($val['type']==4){
                        $goods_id = intval($val['goods_id']);
                        $goods_name = trim($val['goods_name']);
                        $goods_img = trim($val['img']);
                        $goods_price = floatval($val['goods_price']);
                        $goods_total = intval($val['goods_total']);
                        $goods_totalcount = intval($val['goods_totalcount']);
                        $goods_spec = intval($val['goods_spec']);
                        $goods_specname = trim($val['goods_specname']);
                        if(isset($rec_data[$rank]['goods'][$goods_id]['spec'])){
                            $oldspec = $rec_data[$rank]['goods'][$goods_id]['spec'];
                        }else{
                            $oldspec = array();
                        }
                        $rec_data[$rank]['goods'][$goods_id]=array(
                            'id'=>$goods_id,
                            'img'=>$goods_img,
                            'title'=>$goods_name,
                            'marketprice'=>$goods_price,
                            'total'=>$goods_total,
                            'count'=>$goods_totalcount,
                            'spec' =>$oldspec
                        );
                        if($goods_spec>0){
                            $rec_data[$rank]['goods'][$goods_id]['spec'][$goods_spec]= array(
                                'goods_spec' =>$goods_spec,
                                'goods_specname' =>$goods_specname,
                                'marketprice'=>$goods_price,
                                'total'=>$goods_total
                            );
                        }else{
                            $rec_data[$rank]['goods'][$goods_id]['spec']= '';
                        }
                    }elseif($val['type']==5){
                        $coupon_id = intval($val['coupon_id']);
                        $coupon_name = trim($val['coupon_name']);
                        $coupon_img = trim($val['img']);
                        $coupon_num = intval($val['coupon_num']);
                        $coupon_total = intval($val['coupon_total']);
                        $rec_data[$rank]['coupon'][$coupon_id]=array(
                            'id'=>$coupon_id,
                            'img'=>$coupon_img,
                            'couponname'=>$coupon_name,
                            'couponnum'=>$coupon_num,
                            'count'=>$coupon_total
                        );
                        if(isset($rec_data[$rank]['coupon']['total'])){
                            $rec_data[$rank]['coupon']['total']+= $coupon_num;
                        }else{
                            $rec_data[$rank]['coupon']['total'] = 0;
                            $rec_data[$rank]['coupon']['total']+= $coupon_num;
                        }
                    }elseif($val['type']==7){
                        $goods_id = intval($val['goods_id']);
                        $goods_name = trim($val['goods_name']);
                        $goods_img = trim($val['img']);
                        $goods_price = floatval($val['goods_price']);
                        $goods_total = intval($val['goods_total']);
                        $goods_totalcount = intval($val['goods_totalcount']);
                        $goods_spec = intval($val['goods_spec']);
                        $goods_specname = trim($val['goods_specname']);
                        if(isset($rec_data[$rank]['goods'][$goods_id]['spec'])){
                            $oldspec = $rec_data[$rank]['goods'][$goods_id]['spec'];
                        }else{
                            $oldspec = array();
                        }
                        $rec_data[$rank]['hxgoods'][$goods_id]=array(
                            'id'=>$goods_id,
                            'img'=>$goods_img,
                            'title'=>$goods_name,
                            'marketprice'=>$goods_price,
                            'total'=>$goods_total,
                            'count'=>$goods_totalcount,
                            'spec' =>$oldspec
                        );
                        if($goods_spec>0){
                            $rec_data[$rank]['hxgoods'][$goods_id]['spec'][$goods_spec]= array(
                                'goods_spec' =>$goods_spec,
                                'goods_specname' =>$goods_specname,
                                'marketprice'=>$goods_price,
                                'total'=>$goods_total
                            );
                        }else{
                            $rec_data[$rank]['hxgoods'][$goods_id]['spec']= '';
                        }
                    }
                }
            }
            $reward_rank = htmlspecialchars_decode($_GPC['reward_rank']);
            $reward_rank = json_decode($reward_rank,1);
            $rec_rank = array();
            foreach ($reward_rank as $key=>$value){
                $rec_rank['title'] = $value['title'];
                $rec_rank['icon'] = $value['icon'];
                $rec_rank['probability'] = $value['probability'];
                $rec_rank['reward']= $rec_data[$value['rank']];
                array_push($reward,$rec_rank);
            }
            $data['lottery_data'] = serialize($reward);
            if(empty($id)){
                $res=pdo_insert("ewei_shop_bigwheel_config",$data);
                $id = pdo_insertid();
                if($res){
                    plog('lottery.edit', "添加抽奖活动 ID: {$id}<br>");
                }else{
                    show_json(0, '添加操作失败');
                }
                show_json(1, array('url' => webUrl('sale/bigwheel/edit',array("id"=>$id))));
            }else{
                $res=pdo_update("ewei_shop_bigwheel_config",$data,array("id"=>$id,"uniacid"=>$_W['uniacid']));
                if($res){
                    plog('lottery.edit', "修改抽奖活动 ID: {$id}<br>");
                }else{
                    show_json(0, '添加操作失败');
                }
                 show_json(1, array('url' => webUrl('sale/bigwheel/edit',array("id"=>$id))));
            }
          
		}
		include $this->template();
	}

     public function testlottery(){
        global $_GPC;
        $_GPC['testreward'];
        $reward = array();
        $inforeward = array();
        $temreward = array();
        $teminforeward = array();
        foreach ($_GPC['testreward'] as $key=>$value){
            $temreward[$value['rank']] = $value['probability'];
            $teminforeward[$value['rank']] = $value;
        }
        ksort($temreward,1);
        foreach ($temreward as $key=>$value){
            array_push($reward,$value);
            array_push($inforeward,$teminforeward[$key]);
        }
        $num = $this->getRand($reward);
        $info = array(
            'status'=>1,
            'num'=>$num,
            'info'=>$inforeward[$num]
        );
        echo json_encode($info);
        exit();
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

}