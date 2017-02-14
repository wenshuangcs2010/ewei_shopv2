<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Favorite_EweiShopV2Page extends MobileLoginPage {

	function main() {
		global $_W, $_GPC;
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch'])
        {
            include $this->template('merch/member/favorite');
        }
        else
        {
            include $this->template();
        }
	}
	function get_list(){
		
		global $_W,$_GPC;
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
 		$condition = ' and f.uniacid = :uniacid and f.openid=:openid and f.deleted=0';
		if ($merch_plugin && $merch_data['is_openmerch'])
		{
			$condition = ' and f.uniacid = :uniacid and f.openid=:openid and f.deleted=0 and f.type=0';
		}
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
		$sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_member_favorite') . " f where 1 {$condition}";
		$total = pdo_fetchcolumn($sql, $params);
		$list = array();
		if (!empty($total)) {
			$sql = 'SELECT f.id,f.goodsid,g.title,g.thumb,g.marketprice,g.productprice,g.merchid FROM ' . tablename('ewei_shop_member_favorite') . ' f '
				. ' left join ' . tablename('ewei_shop_goods') . ' g on f.goodsid = g.id '
				. ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
			$list = pdo_fetchall($sql, $params);
			$list = set_medias($list, 'thumb');
            if (!empty($list) && $merch_plugin && $merch_data['is_openmerch'])
            {
                $merch_user = $merch_plugin->getListUser($list,'merch_user');
                foreach ($list as &$row)
                {
                    $row['merchname'] = $merch_user[$row['merchid']]['merchname'] ? $merch_user[$row['merchid']]['merchname'] : $_W['shopset']['shop']['name'];
                }
                unset($row);
            }
		}
		show_json(1,array('list'=>$list,'total'=>$total,'pagesize'=>$psize));

	}

	function toggle() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$isfavorite = intval($_GPC['isfavorite']);
		$goods = pdo_fetch('select * from '.tablename('ewei_shop_goods').' where id=:id and uniacid=:uniacid limit 1',array(':id'=>$id,':uniacid'=>$_W['uniacid']));
		if (empty($goods)) {
			show_json(0, '商品未找到');
		}
		$data = pdo_fetch('select id,deleted from ' . tablename('ewei_shop_member_favorite') . ' where uniacid=:uniacid and goodsid=:id and openid=:openid limit 1', array(
			':uniacid' => $_W['uniacid'],
			':openid' => $_W['openid'],
			':id' => $id
		));
		if (empty($data)) {
			if(!empty($isfavorite)){
				$data = array(
					'uniacid' => $_W['uniacid'],
					'goodsid' => $id,
					'openid' => $_W['openid'], //兼容1.x
					'createtime' => time()
				);
				pdo_insert('ewei_shop_member_favorite', $data);
			}
		} else {
			pdo_update('ewei_shop_member_favorite', array('deleted' => $isfavorite?0:1), array('id' => $data['id'], 'uniacid' => $_W['uniacid']));
		}

		show_json(1,array('isfavorite'=>$isfavorite==1));
	}

	function remove() {

		global $_W, $_GPC;
		$ids = $_GPC['ids'];
		if (empty($ids) || !is_array($ids)) {
			show_json(0, '参数错误');
		}
		$sql = "update " . tablename('ewei_shop_member_favorite') . ' set deleted=1 where openid=:openid and id in (' . implode(',', $ids) . ')';
		pdo_query($sql, array(':openid' => $_W['openid']));
		show_json(1);
	}

}
