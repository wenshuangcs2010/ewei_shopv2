<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';

class Memberorder_EweiShopV2Page extends CommissionMobileLoginPage {

    function main() {

        global $_W,$_GPC;
        $openid = $_W['openid'];

        $level = $this->set['level'];
        $member = $this->model->getInfo($openid, array());
        
        include $this->template();
    }
    function memberlevelorder(){
    	global $_W,$_GPC;
    	$member_id=$_GPC['uid'];
    	$member = m('member')->getMember($member_id);
    	//交易订单数量
      $params = array('type' => intval($_GPC['type']), 'uid' => $member['id'],'agentid'=>$member['agentid'], 'starttime' => (!(empty($_GPC['starttime'])) ? strtotime($_GPC['starttime']) : 0), 'endtime' => (!(empty($_GPC['endtime'])) ? strtotime($_GPC['endtime']) : 0));
    	include $this->template("commission/memberlevelorder");
    }
    function get_memberorderlist(){
    global $_W, $_GPC;
    $uid=$_GPC['uid'];
		$openid = $_W['openid'];
		$member =m('member')->getMember($uid);
		$level = intval($this->set['level']);
		$pindex = max(1, intval($_GPC['page']));
    $agentid=$_GPC['agentid'];
		$psize = 20;
		$status=$_GPC['status'];
    $paras=array(":uniacid"=>$_W['uniacid'],':agentid'=>$agentid);
		
		$condition=" and o.uniacid=:uniacid and o.status={$status} and o.agentid=:agentid and o.openid=:openid";
		
    $type = intval($_GPC['type']);
    $starttime = intval($_GPC['starttime']);
    $endtime = intval($_GPC['endtime']);
    $condition.=" AND o.finishtime BETWEEN :starttime AND :endtime";
    if ($type == 0) 
    {
      $starttime = strtotime(date('Y-m-d'));
      $endtime = time();
    }
    else 
    {
      if (($type == 7) || ($type == 30)) 
      {
        $starttime = strtotime(date('Y-m-d')) - (($type - 1) * 3600 * 24);
        $endtime = time();
      }
    }
    $paras[':starttime']=$starttime;
    $paras[':endtime']=$endtime;
    $paras[':openid']=$member['openid'];
    $sql="select count(*) from ".tablename("ewei_shop_order")." as o where (paytype=21 or paytype=22) and agentid=:agentid ".$condition;
   
    $ordercount=pdo_fetchcolumn($sql,$paras);
        $sql="select sum(price) from ".tablename("ewei_shop_order")." as o where (paytype=21 or paytype=22) and agentid=:agentid ".$condition;

    $orderprice=pdo_fetchcolumn($sql,$paras);
		//var_dump($status);

        $sql = "select o.* from " . tablename('ewei_shop_order') . " as o "
            ."where   o.ismr=0 and o.deleted=0 and o.isparent=0 $condition  GROUP BY o.id ORDER BY o.createtime DESC  ";
        $sql.="LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $list = pdo_fetchall($sql, $paras);
      	foreach ($list as $key => &$row) {
      		$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
      		$goods = pdo_fetchall("SELECT og.id,og.goodsid,g.thumb,og.price,og.total,g.title,og.optionname,"
							. "og.commission1,og.commission2,og.commission3,og.commissions,"
							. "og.status1,og.status2,og.status3,"
							. "og.content1,og.content2,og.content3 from " . tablename('ewei_shop_order_goods') . " og"
							. " left join " . tablename('ewei_shop_goods') . " g on g.id=og.goodsid  "
							. " where og.orderid=:orderid  and og.uniacid = :uniacid order by og.createtime  desc ", array(':uniacid' => $_W['uniacid'], ':orderid' => $row['id']));
			if($row['status'] == -1){
				$row['status'] = "已取消";
			}else if ($row['status'] == 0) {
					$row['status'] = '待付款';
				} else if ($row['status'] == 1) {
					$row['status'] = '已付款';
				} else if ($row['status'] == 2) {
					$row['status'] = '待收货';
				} else if ($row['status'] == 3) {
					$row['status'] = '已完成';
				}
      		$row['order_goods'] = set_medias($goods, 'thumb');
      		if (!empty($this->set['openorderbuyer'])) {
					$row['buyer'] = m('member')->getMember($row['openid']);
				}
      	}
      	unset($row);
      	show_json(1, array(
			'list' => $list,
			'pagesize' => $psize,
			'total' => $ordercount,
      'money'=>floatval($orderprice),
		));
    }
    function get_memberlist(){
    	global $_W, $_GPC;
		$openid = $_W['openid'];
		$member = $this->model->getInfo($openid, array());
		$level = intval($this->set['level']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
        $paras=array(":uniacid"=>$_W['uniacid']);
        $paras['agentid']=$member['id'];
        $sql = "select m.* from " . tablename('ewei_shop_member') . " as m "
            ."where  m.uniacid = :uniacid and m.agentid=:agentid  GROUP BY m.id ORDER BY m.createtime DESC  ";
        $sql.="LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
      	foreach ($list as $key => &$row) {
      		$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
      		//计算消费总额
      		$sql="select sum(price) from ".tablename("ewei_shop_order")." where  openid =:openid and status=3 and agentid=:agentid and (paytype=21 or paytype=22) and uniacid=:uniacid";
      		$params[':openid']=$row['openid'];
          $params[':agentid']=$row['agentid'];
      		$params[':uniacid']=$_W['uniacid'];
         
      		$row['price']=pdo_fetchcolumn($sql,$params);
      		if(empty($row['price'])){
      			$row['price']=0;
      		}
      		$row['url']=mobileUrl('commission.memberorder.memberlevelorder',array('uid'=>$row['id']),true);
      	}
      	unset($row);
      	show_json(1, array(
			'list' => $list,
			'pagesize' => $psize,
			'total' => $ordercount
		));
    }

}
