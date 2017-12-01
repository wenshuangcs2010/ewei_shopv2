<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Goods_rank_EweiShopV2Page extends WebPage {

    function main() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params = array();

        $condition = " and og.uniacid={$_W['uniacid']} ";
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['datetime'])) {
            $starttime = strtotime($_GPC['datetime']['start']);
            $endtime = strtotime($_GPC['datetime']['end']);

            if (!empty($starttime)) {
                $condition .= " AND o.createtime >= {$starttime}";
            }

            if (!empty($endtime)) {
                $condition .= " AND o.createtime <= {$endtime} ";
            }

        }

        $condition1 = ' and g.uniacid=:uniacid';
        $params1 = array(':uniacid' => $_W['uniacid']);
        if (!empty($_GPC['title'])) {
            $_GPC['title'] = trim($_GPC['title']);
            $condition1.=" and g.title like :title";
            $params1[':title'] = "%{$_GPC['title']}%";
        }
        $orderby = !isset($_GPC['orderby']) ? 'money' : ( empty($_GPC['orderby']) ? 'money' : 'count');

        $sql = "SELECT g.id,g.title,g.thumb,"
            . "(select ifnull(sum(og.realprice),0) from  " . tablename('ewei_shop_order_goods') . " og left join " . tablename('ewei_shop_order') . " o on og.orderid=o.id  where o.status>=1 and og.goodsid=g.id {$condition})  as money,"
            . "(select ifnull(sum(og.total),0) from  " . tablename('ewei_shop_order_goods') . " og left join " . tablename('ewei_shop_order') . " o on og.orderid=o.id  where o.status>=1 and og.goodsid=g.id {$condition}) as count  "
            . "from " . tablename('ewei_shop_goods') . " g  "
            . "where 1 {$condition1}  order by {$orderby} desc ";
        if (empty($_GPC['export'])) {
            $sql.="LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }
        $list = pdo_fetchall($sql, $params1);
        $total = pdo_fetchcolumn("select  count(*) from " . tablename('ewei_shop_goods') . ' g '
            . " where 1 {$condition1} ", $params1);
        $pager = pagination($total, $pindex, $psize);

//导出Excel
        if ($_GPC['export'] == 1) {

            ca('statistics.goods_rank.export');
			
            $list[] = array('data' => '商品销售排行', 'count' => $total);
            foreach ($list as &$row) {
                $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
            }
            unset($row);

            m('excel')->export($list, array(
                "title" => "商品销售报告-" . date('Y-m-d-H-i', time()),
                "columns" => array(
                    array('title' => '商品名称', 'field' => 'title', 'width' => 36),
                    array('title' => '数量', 'field' => 'count', 'width' => 12),
                    array('title' => '价格', 'field' => 'money', 'width' => 12)
                )
            ));
			
			plog('statistics.goods_rank.export', '导出商品销售排行');
			
        }
        load()->func('tpl');
        include $this->template('statistics/goods_rank');
    }

}
