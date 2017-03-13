<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Index_EweiShopV2Page extends WebPage {

	 function main() {
        global $_W, $_GPC;
        $wna=array("日",'一','二','三','四','五','六');
        $nsignurl=mobileUrl('sale.nsign', array(), false);
        $nsignurl=$_W['siteroot']."app/" .substr($nsignurl,2);
        $sql="SELECT * from ".tablename("ewei_shop_nsign_config")." where uniacid=:uniacid";
        $settings=pdo_fetch($sql,array(":uniacid"=>$_W['uniacid']));
        if(!empty($settings)){
        	$settings['jl']=iunserializer($settings['jl']);
        	$settings['time']=iunserializer($settings['time']);
        	$settings['tx']=iunserializer($settings['tx']);
        	$settings['lx']=iunserializer($settings['lx']);
            //var_dump($settings['lx']['jl']);
        }
        //$settings['jl']['t'][0]=1;
        if($_W['ispost']){
        	$time=iserializer($_GPC['time']);//时段设置
        	$jl=iserializer($_GPC['jl']);//每日一提示
        	$tx=iserializer($_GPC['tx']);//每日一提示
            //$_GPC['lx']['time']['start']=strtotime($_GPC['lx']['time']['start']);
            //$_GPC['lx']['time']['end']=strtotime($_GPC['lx']['time']['end']);   
        	$lx=iserializer($_GPC['lx']);
        	 
        	// var_dump(iunserializer($time));
        	$data=array(
        		'everyday'=>intval($_GPC['everyday']),//首次签到赠送
        		'continuity'=>intval($_GPC['continuity']),//续签递增
        		'intup'=>intval($_GPC['intup']),//递增上限
        		'time'=>$time,//时段设置
        		'jl'=>$jl,//额外奖励
        		'tx'=>$tx,//每日一提示
        		'ispaihang'=>intval($_GPC['ispaihang']),//签到积分排行板开关
        		'paihangt'=>intval($_GPC['paihangt']),//排行方式
        		'px'=>intval($_GPC['px']),//相同积分排序
        		'paihangs'=>intval($_GPC['paihangs']),//显示条数,默认为前三名
        		'success_msg'=>trim($_GPC['success_msg']),//签到成功提示语
        		'continuity_msg'=>trim($_GPC['continuity_msg']),//签到失败提示语
        		'uniacid'=>$_W['uniacid'],
        		'lx'=>$lx,
        		);
        		$cachekey=m("cache")->get_key("nsignconfig",$_W['uniacid']);
	        	if($cachekey){
	        		cache_delete($cachekey);
	        	}
	        	if(!empty($settings)){
	        		pdo_update("ewei_shop_nsign_config",$data,array("id"=>$settings['id'],'uniacid'=>$_W['uniacid']));
	        		$this->message("修改成功");
	        	}else{
	        		pdo_insert("ewei_shop_nsign_config",$data);
	        	}
	        	

        	}
        	include $this->template();
        }
        function log(){
            global $_W, $_GPC;
            $params = array(':uniacid' => $_W['uniacid']);
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;
            $condition = " and log.uniacid=:uniacid";
            $keyword = trim($_GPC['keyword']);
            if (!empty($keyword)) {
                $condition .= ' AND ( m.nickname LIKE :keyword or m.realname LIKE :keyword or m.mobile LIKE :keyword ) ';

                $params[':keyword'] = '%' . $keyword . '%';
            }

        if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);

            $condition .= " AND log.last_time >= :starttime AND log.last_time <= :endtime ";
            $params[':starttime'] = $starttime;
            $params[':endtime'] = $endtime;
        }
            $list = pdo_fetchall("SELECT log.*, m.avatar,m.nickname,m.realname,m.mobile FROM " . tablename('ewei_shop_nsign_user_prize') . " log "
            . " left join " . tablename('ewei_shop_member') . ' m on m.openid = log.openid  and m.uniacid = log.uniacid'
            . " WHERE 1 {$condition} ORDER BY log.add_time desc "
            . "  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
            $total = pdo_fetchcolumn('SELECT count(*)  FROM ' . tablename('ewei_shop_nsign_user_prize') . " log "
            . " left join " . tablename('ewei_shop_member') . ' m on m.openid = log.openid  and m.uniacid = log.uniacid'
            . " where 1 {$condition}  ", $params);
            $pager = pagination($total, $pindex, $psize);
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_nsign_user_prize') . " where uniacid=:uniacid ", array(":uniacid"=>$_W['uniacid']));
            load()->func('tpl');
            include $this->template();

        }
    
    function test(){
        echo $date=m("nsignutil")->T(1489026220);
        var_dump($date);
        /*
        require EWEI_SHOPV2_TAX_CORE. '/Transfer/Transfer.php';
        $payment=Transfer::getPayment("shenfupay");
        $payment->test();*/
    }
}