 <?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';

class Orderlist_EweiShopV2Page extends CommissionMobileLoginPage
{

 function main(){
        global $_W, $_GPC;
        $member = m('member')->getMember($_W['openid']);
        $memberid=$member['id'];
        include $this->template();

    }
	function get_orderlist(){
		global $_W, $_GPC;
		$openid = $_W['openid'];
		$member = $this->model->getInfo($openid, array());
		$level = intval($this->set['level']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$status=$_GPC['status'];
		if(is_numeric($status) === true){
			$condition=" and o.status={$status}";
		}
		//var_dump($status);
		if ($level >= 1) {
            $condition.=' and  ( o.agentid=' . intval($member['id']);
                }
        if ($level >= 2 && $agent['level2'] > 0) {
            $condition.= " or o.agentid in( " . implode(',', array_keys($agent['level1_agentids'])) . ")";
        }
        if ($level >= 3 && $agent['level3'] > 0) {
            $condition.= " or o.agentid in( " . implode(',', array_keys($agent['level2_agentids'])) . ")";
        }
        if ($level >= 1) {
            $condition.=")";
        }
        $paras=array(":uniacid"=>$_W['uniacid']);
        $sql = "select o.* from " . tablename('ewei_shop_order') . " as o "
            ."where  o.uniacid = :uniacid and o.ismr=0 and o.deleted=0 and o.isparent=0 $condition  GROUP BY o.id ORDER BY o.createtime DESC  ";
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
			'total' => $ordercount
		));
	}
}