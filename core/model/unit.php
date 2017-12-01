<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
//饭卡 支付 通用类
class Unit_EweiShopV2Model {
    var $paytype=8;
    //检查用户是否可以使用饭卡
    function checkMember($groupid){
        global $_W;
        if(empty($groupid)){
            return false;
        }
        $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_unitlist') . " WHERE groupid =:groupid and uniacid=:uniacid  order by id desc limit 1", array(':groupid' => $groupid, ':uniacid' => $_W['uniacid']));
        if(empty($item)){
            return false;
        }
        return $item;
    }
    function checkOrder($openid,$unititem,$order){
        global $_W;
        //$unitmonthprice=-1  月消费金额不设上线
        //$unittodayprice=-1  日消费金额不设上线
        //检查用户本月使用饭卡已经消费多少钱
        $month=$this->getMonth();
        $params=array(
            ":uniacid"=>$_W['uniacid'],
            //":paytype"=>$this->paytype,
            ':openid'=>$openid,
            ":firstday"=>$month[0],
            ":lastday"=>$month[1],
            );
        $sql="select sum(price) from ".tablename("ewei_shop_unit_pay_log")." where uniacid=:uniacid and  openid =:openid and status=1  and   addtime BETWEEN :firstday and :lastday";
        $monthprice=pdo_fetchcolumn($sql,$params);
        $monthprice=floatval($monthprice);//用户的月饭卡消费金额
        $unitmonthprice=floatval($unititem['monthprice']);//单位允许的月消费金额
        //如果月消费金额大于单位规定的月消费金额 禁止支付
        if($monthprice>=$unitmonthprice && $unitmonthprice!=-1){
            return false;
        }
        //每月剩余的可消费金额
        $surplusmonthprice=$unitmonthprice-$monthprice;
        //本次支付的金额是否比剩余的月消费金额多 如果多 禁止使用饭卡
        if($surplusmonthprice<$order['price'] && $unitmonthprice!=-1){
            return false;
        }
        //检查用户今日使用饭卡已经消费多少钱
        $sql="select sum(price) from ".tablename("ewei_shop_unit_pay_log")." where uniacid=:uniacid  and openid =:openid and status=1 and  addtime BETWEEN :firstday and :lastday";
        $taday=$this->getTaday();
        $params[':firstday']=$taday[0];
        $params[':lastday']=$taday[1];
        $tadayprice=pdo_fetchcolumn($sql,$params);
        //用户的日饭卡消费金额
        $tadayprice=floatval($tadayprice);
        //单位允许的月消费金额
        $unittodayprice=floatval($unititem['todayprice']);//单位允许的月消费金额
         //如果日消费金额大于单位规定的日消费金额 禁止支付
        if($tadayprice>=$unittodayprice && $unittodayprice!=-1){
            return false;
        }
        //每日剩余的可消费金额
        $surplustadayprice=$unittodayprice-$tadayprice;
        //本次支付的金额是否比剩余的月消费金额多 如果多 禁止使用饭卡
        if($order['price']>$surplustadayprice && $unitmonthprice!=-1){
            return false;
        }
        return true;
    }

    function getMonth(){
        $firstday = mktime(0,0,0,date('m'),1,date('Y'));
        $lastday = mktime(23,59,59,date('m'),date('t'),date('Y'));
        return array($firstday,$lastday);
    }
    function getTaday(){
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        return array($beginToday,$endToday);
    }
    function getUnitList($unitid=0){
        global $_W;
        $params=array(
            'uniacid'=>$_W['uniacid'],
            );
        if(empty($unitid)){
           $sql="SELECT * from ".tablename("ewei_shop_unitlist")." where uniacid=:uniacid and isdel=0";  
       }else{
            $sql="SELECT * from ".tablename("ewei_shop_unitlist")." where uniacid=:uniacid and isdel=0 and id=:id";
            $params[':id']=$unitid;
       }
       return pdo_fetchAll($sql,$params);
    }

    function downloadbill($starttime, $endtime,$unitid=0){
        global $_W, $_GPC;
        $unitlist=$this->getUnitList($unitid);
        
    }
}
