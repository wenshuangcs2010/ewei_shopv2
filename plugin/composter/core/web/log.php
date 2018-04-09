<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Log_EweiShopV2Page extends PluginWebPage {

	function main() {

		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$params = array(':uniacid' => $_W['uniacid']);
		$condition = " and comq.uniacid=:uniacid and comq.composterid=" . intval($_GPC['id']);
		$sql="SELECT comq.*,mg.groupname,mg.id as mgid FROM " . tablename('ewei_shop_composter_qr')." as comq"
				." LEFT JOIN ".tablename("ewei_shop_member_group")." as mg ON mg.id=comq.groupid"
				. " WHERE 1 {$condition} ORDER BY createtimes desc "
				. "  LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('SELECT count(*)  FROM ' . tablename('ewei_shop_composter_qr') 
				. " as comq where 1 {$condition}  ", $params);
		

		$pager = pagination($total, $pindex, $psize);

		load()->func('tpl');
		include $this->template();
	}
	function order(){
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
        $psize = 20;
		$compostid=$_GPC['compostid'];
		$groupid=$_GPC['groupid'];
		$params=array(":uniacid"=>$_W['uniacid'],':composterid'=>$compostid);
		$condition = " and m.uniacid=:uniacid and com.id=:composterid";
		$sql="SELECT com.*,m.id as mid FROM " . tablename('ewei_shop_composteruser') . " as com ".
			"LEFT JOIN ".tablename("ewei_shop_member")." as m ON com.openid=m.openid ".
			" WHERE 1 {$condition}";
		$member = pdo_fetch($sql, $params);
		//var_dump($member);
		$sql="SELECT * from ".tablename("ewei_shop_member_group")." where 1 and id=:groupid";
		$group=pdo_fetch($sql,array(":groupid"=>$groupid));
		
		$updategroupid=$group['updategroupid'];
		$paras=array(
			':mid'=>$member['mid'],
			':uniacid'=>$_W['uniacid'],
			':groupid'=>$updategroupid,
			);
		$condition=" and o.agentid=:mid and o.uniacid=:uniacid and mg.id=:groupid ";
		$searchtime = trim($_GPC['searchtime']);
        if (!empty($searchtime) && is_array($_GPC['time']) && in_array($searchtime, array('create', 'pay', 'send', 'finish'))) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND o.{$searchtime}time >= :starttime AND o.{$searchtime}time <= :endtime ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
		$sql="SELECT o.*,m.nickname,m.id as mid,m.realname as mrealname,m.mobile as mmobile from ".tablename("ewei_shop_order")." as o "
		." LEFT JOIN ".tablename("ewei_shop_member")." as m ON o.openid=m.openid and m.uniacid =  o.uniacid"
		." LEFT JOIN ".tablename("ewei_shop_member_group")."  as mg ON mg.id=m.groupid and mg.uniacid =  o.uniacid"
		." where 1 {$condition}";
		$sql.="LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $paras);
		$sql="SELECT count(*) from ".tablename("ewei_shop_order")." as o "
		." LEFT JOIN ".tablename("ewei_shop_member")." as m ON o.openid=m.openid and m.uniacid =  o.uniacid"
		." LEFT JOIN ".tablename("ewei_shop_member_group")."  as mg ON mg.id=m.groupid and mg.uniacid =  o.uniacid"
		." where 1 {$condition}";
		$total=pdo_fetchcolumn($sql,$paras);

		$pager = pagination($total, $pindex, $psize);
		$paytype = array(
            '0' => array('css' => 'default', 'name' => '未支付'),
            '1' => array('css' => 'danger', 'name' => '余额支付'),
            '11' => array('css' => 'default', 'name' => '后台付款'),
            '2' => array('css' => 'danger', 'name' => '在线支付'),
            '21' => array('css' => 'success', 'name' => '微信支付'),
            '22' => array('css' => 'warning', 'name' => '支付宝支付'),
            '23' => array('css' => 'warning', 'name' => '银联支付'),
            '3' => array('css' => 'primary', 'name' => '货到付款'),
        );
        $orderstatus = array(
            '-1' => array('css' => 'default', 'name' => '已关闭'),
            '0' => array('css' => 'danger', 'name' => '待付款'),
            '1' => array('css' => 'info', 'name' => '待发货'),
            '2' => array('css' => 'warning', 'name' => '待收货'),
            '3' => array('css' => 'success', 'name' => '已完成')
        );
         if (!empty($list)) {
         	 foreach ($list as &$value) {
         	 	 $s = $value['status'];
                $pt = $value['paytype'];
                 $value['statusvalue'] = $s;
                 if ($pt == 3 && empty($value['statusvalue'])) {
                    $value['statuscss'] = $orderstatus[1]['css'];
                    $value['status'] = $orderstatus[1]['name'];
                }
                if ($s == 1) {
                    if ($value['isverify'] == 1) {
                        $value['status'] = "待使用";
                    } else if (empty($value['addressid'])) {

                        if (!empty($value['ccard'])) {
                            $value['status'] = "待充值";
                        } else {
                            $value['status'] = "待取货";
                        }
                    }
                }

                if ($s == -1) {
                    if (!empty($value['refundtime'])) {
                        $value['status'] = '已退款';
                    }
                }

                $value['paytypevalue'] = $pt;
                $value['css'] = $paytype[$pt]['css'];
                $value['paytype'] = $paytype[$pt]['name'];

                $value['dispatchname'] = empty($value['addressid']) ? '自提' : $value['dispatchname'];
                if (empty($value['dispatchname'])) {
                    $value['dispatchname'] = '快递';
                }
                if ($pt == 3) {
                    $value['dispatchname'] = "货到付款";
                } else if ($value['isverify'] == 1) {
                    $value['dispatchname'] = "线下核销";
                } else if ($value['isvirtual'] == 1) {
                    $value['dispatchname'] = "虚拟物品";
                } else if (!empty($value['virtual'])) {
                    $value['dispatchname'] = "虚拟物品(卡密)<br/>自动发货";
                }
                 $order_goods = pdo_fetchall('select g.id,g.title,g.thumb,g.goodssn,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, og.total,og.price,og.optionname as optiontitle, og.realprice,og.changeprice,og.oldprice,og.commission1,og.commission2,og.commission3,og.commissions,og.diyformdata,og.diyformfields,op.specs,g.merchid,og.seckill,og.seckill_taskid,og.seckill_roomid from ' . tablename('ewei_shop_order_goods') . ' og '
                    . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid '
                    . ' left join ' . tablename('ewei_shop_goods_option') . ' op on og.optionid = op.id '
                    . ' where og.uniacid=:uniacid and og.orderid=:orderid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $value['id']));
                $goods = '';
              
                foreach ($order_goods as &$og) {
                   
                    //读取规格的图片
                    if (!empty($og['specs'])) {
                        $thumb = m('goods')->getSpecThumb($og['specs']);
                        if (!empty($thumb)) {
                            $og['thumb'] = $thumb;
                        }
                    }
                    if (!empty($level) && empty($agentid)) {
                        $commissions = iunserializer($og['commissions']);
                        if (!empty($m1)) {
                            if (is_array($commissions)) {
                                $commission1+= isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
                            } else {
                                $c1 = iunserializer($og['commission1']);
                                $l1 = $p->getLevel($m1['openid']);
                                $commission1+= isset($c1['level' . $l1['id']]) ? $c1['level' . $l1['id']] : $c1['default'];
                            }
                        }
                        if (!empty($m2)) {
                            if (is_array($commissions)) {
                                $commission2+= isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
                            } else {
                                $c2 = iunserializer($og['commission2']);
                                $l2 = $p->getLevel($m2['openid']);
                                $commission2+= isset($c2['level' . $l2['id']]) ? $c2['level' . $l2['id']] : $c2['default'];
                            }
                        }
                        if (!empty($m3)) {
                            if (is_array($commissions)) {
                                $commission3+= isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
                            } else {
                                $c3 = iunserializer($og['commission3']);
                                $l3 = $p->getLevel($m3['openid']);
                                $commission3+= isset($c3['level' . $l3['id']]) ? $c3['level' . $l3['id']] : $c3['default'];
                            }
                        }
                    }
                    $goods.="" . $og['title'] . "\r\n";

                    if (!empty($og['optiontitle'])) {
                        $goods.=" 规格: " . $og['optiontitle'];
                    }
                    if (!empty($og['option_goodssn'])) {
                        $og['goodssn'] = $og['option_goodssn'];
                    }
                    if (!empty($og['option_productsn'])) {
                        $og['productsn'] = $og['option_productsn'];
                    }


                    if (!empty($og['goodssn'])) {
                        $goods.=' 商品编号: ' . $og['goodssn'];
                    }
                    if (!empty($og['productsn'])) {
                        $goods.=' 商品条码: ' . $og['productsn'];
                    }
                    $goods.=' 单价: ' . ($og['price'] / $og['total']) . ' 折扣后: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . " 折扣后: " . $og['realprice'] . "\r\n ";

                    if (p('diyform') && !empty($og['diyformfields']) && !empty($og['diyformdata'])) {
                        $diyformdata_array = p('diyform') ->getDatas(iunserializer($og['diyformfields']), iunserializer($og['diyformdata']), 1);
                        $diyformdata = "";

                        $dflag = 1;
                        foreach ($diyformdata_array as $da) {

                            if (!empty($diy_title_data)) {
                                if(array_key_exists($da['key'], $diy_title_data)) {
                                    $dflag = 0;
                                }
                            }

                            if ($dflag == 1) {
                                $diy_title_data[$da['key']] = $da['name'];
                            }
                            $og['goods_' . $da['key']] = $da['value'];
                            $diyformdata.=$da['name'] . ": " . $da['value'] . " \r\n";
                        }
                        $og['goods_diyformdata'] = $diyformdata;
                    }
                }
                $value['goods'] = set_medias($order_goods, 'thumb');
                $value['goods_str'] = $goods;
         	 }
         	  unset($value);
         }

        
		include $this->template("composter/list");
	}
}
