<?php


if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Shuxinapi_EweiShopV2Model
{
	function sendOrderilst(){
		 global $_W;
		 load()->func('communication');
	}

	function get_order($openid,$start_time="",$end_time=""){
		global $_W;
		if($_W['uniacid']!=24){
			echo "参数错误";
			//return false;
		}
		if(empty($openid)){
			echo "参数错误";
			exit;
		}
		if(empty($start_time) || empty($end_time)){
			$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));
			$start_time= strtotime($BeginDate);
			 $lastdate= date('Y-m-d', strtotime("{$BeginDate} +1 month -1 day"));
			 $end_time=strtotime($lastdate);
		}
		
		$agentLevel = p("commission")->getLevel($openid);
		$order=array();
		$member=m("member")->getMember($openid);
		$uid=$member['id'];
		//$sql="SELECT price,openid,ordersn From".tablename("ewei_shop_order");
		
		
		$sql='select og.nocommission,og.commission1,o.ordersn,o.createtime,o.price,og.commissions from ' 
					.tablename('ewei_shop_order_goods') . ' og '
					. ' left join  ' . tablename('ewei_shop_order') . ' o on og.orderid=o.id '
					. " where o.createtime>=:start_time and o.createtime<=:end_time and o.status=3 and o.uniacid=:uniacid ";
		$params=array(":start_time"=>$start_time,":end_time"=>$end_time,":uniacid"=>$_W['uniacid']);
		
		if(!empty($openid)){
			$levecondition.=" and o.openid=:openid";
			$levelsql=$sql.$levecondition;
			$params[':openid']=$openid;
			$order['level']=pdo_fetchall($levelsql,$params);
			unset($params[':openid']);
			foreach ($order['level'] as $key=>$o) {
				if (empty($o['ordersn'])) {
					continue;
				}
				$commissions = iunserializer($o['commissions']);
				$commission = iunserializer($o['commission1']);
				if (empty($commissions)) {
					$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
				} else {
					$commission_ok = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
				}
				unset($order['level'][$key]['commissions']);
				unset($order['level'][$key]['commission1']);
				$order['level'][$key]['commissions']=$commission_ok;
			}

		}
		$level1="";
		$level2="";
		$level3="";
		$membercondition=" where o.agentid =:agentid";
		$membersql="SELECT id from ".tablename("ewei_shop_member").$membercondition;
		$level1=pdo_fetchall($membersql,array(":agentid"=>$uid));//下一级分销商
		if(!empty($level1)){
			foreach ($level1 as  $value) {
				$level1ids[]=$value['id'];
			}
			$level1condition.=$condition." and agentid =:uid";
			$level1sql=$sql.$level1condition;
			//var_dump($level1sql);
			$params[':uid']=$uid;
			$order['level1']=pdo_fetchall($level1sql,$params);
			foreach ($order['level1'] as $key=>$o) {
				if (empty($o['ordersn'])) {
					continue;
				}
				$commissions = iunserializer($o['commissions']);
				$commission = iunserializer($o['commission1']);
				if (empty($commissions)) {
					$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
				} else {
					$commission_ok = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
				}
				unset($order['level1'][$key]['commissions']);
				unset($order['level1'][$key]['commission1']);
				$order['level1'][$key]['commissions']=$commission_ok;
			}
			unset($params[':uid']);
		}

		if(!empty($level1ids)){
			$level1ids=implode(",", $level1ids);
			$level2condition.=$condition." and o.agentid in ($level1ids)";
			$level2sql=$sql.$level2condition;
			$order['level2']=pdo_fetchall($level2sql,$params);
			foreach ($order['level2'] as $key=>$o) {
				if (empty($o['ordersn'])) {
					continue;
				}
				$commissions = iunserializer($o['commissions']);
				$commission = iunserializer($o['commission1']);
				if (empty($commissions)) {
					$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
				} else {
					$commission_ok = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
				}
				unset($order['level2'][$key]['commissions']);
				unset($order['level2'][$key]['commission1']);
				$order['level2'][$key]['commissions']=$commission_ok;
			}
		}
		echo json_encode($order);

	}
}