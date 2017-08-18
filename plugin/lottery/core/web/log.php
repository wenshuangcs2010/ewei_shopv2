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
        $condition = " and log.uniacid=:uniacid  and log.lottery_id=" . intval($_GPC['id']);
        $keyword = trim($_GPC['keyword']);
        if (!empty($keyword)) {
                $condition .= ' AND ( m.nickname LIKE :keyword or m.realname LIKE :keyword or m.mobile LIKE :keyword ) ';

            $params[':keyword'] = '%' . $keyword . '%';
        }

        if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);

            $condition .= " AND log.addtime >= :starttime AND log.addtime <= :endtime ";
            $params[':starttime'] = $starttime;
            $params[':endtime'] = $endtime;
        }

        $list = pdo_fetchall("SELECT log.*, m.avatar,m.nickname,m.realname,m.mobile FROM " . tablename('ewei_shop_lottery_log') . " log "
            . " left join " . tablename('ewei_shop_member') . ' m on m.openid = log.join_user  and m.uniacid = log.uniacid'
            . " WHERE 1 {$condition} ORDER BY log.addtime desc "
            . "  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn('SELECT count(*)  FROM ' . tablename('ewei_shop_lottery_log') . " log "
            . " left join " . tablename('ewei_shop_member') . ' m on m.openid = log.join_user  and m.uniacid = log.uniacid'
            . " where 1 {$condition}  ", $params);
//		foreach ($list as &$row) {
//			$row['times'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_postera_log') . ' where from_openid=:from_openid and posterid=:posterid and uniacid=:uniacid', array(':from_openid' => $row['from_openid'], ':posterid' => intval($_GPC['id']), ':uniacid' => $_W['uniacid']));
//		}
//		unset($row);

        $pager = pagination($total, $pindex, $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_lottery_join') . " where lottery_id=:lottery_id ", array(":lottery_id"=>intval($_GPC['id'])));
        load()->func('tpl');
        include $this->template();
    }

}
