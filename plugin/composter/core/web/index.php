<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends PluginWebPage {

	function main() {
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$params = array(':uniacid' => $_W['uniacid']);
		$condition = " and m.uniacid=:uniacid ";
		$sql="SELECT com.*,m.realname FROM " . tablename('ewei_shop_composteruser') . " as com ".
			"LEFT JOIN ".tablename("ewei_shop_member")." as m ON com.openid=m.openid ".
			"WHERE 1 {$condition}  LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
	
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_composteruser') . " as m where 1 {$condition} ", $params);
		$pager = pagination($total, $pindex, $psize);
		include $this->template();
	}

	function add() {
		$this->post();
	}

	function edit() {
		$this->post();
	}

	protected function post() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		if(!empty($id)){
			$sql="SELECT *  from ".tablename("ewei_shop_composteruser")." where id=:id";
			$item=pdo_fetch($sql,array(":id"=>$id));

		}
		//获取全部分销商
		$sql="SELECT id,realname,openid from".tablename("ewei_shop_member")." where isagent=1 and status=1 and uniacid=:uniacid";
		$agentmemberlist=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid']));
		foreach ($agentmemberlist as $key => $value) {
			if(empty($value['realname'])){
				unset($agentmemberlist[$key]);
			}
		}
		if ($_W['ispost']) {
			$data = array(
				'uniacid' => $_W['uniacid'],
				'openid' => trim($_GPC['agentsopenid']),
				'type' => intval($_GPC['type']),
				'bedown'=>intval($_GPC['bedown']),
				'beagent'=>intval($_GPC['beagent']),
			
			);
            $reward_totle = array(
                'reccredit_totle'=>intval($_GPC['reccredit_totle']),
                'recmoney_totle'=>floatval($_GPC['recmoney_totle']),
            );
			if (!empty($id)) {
				pdo_update('ewei_shop_composteruser', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
			} else {
				pdo_insert('ewei_shop_composteruser', $data);
				$id = pdo_insertid();
			}
			//创建回复关键词
			$ruleauto = pdo_fetch("select * from " . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':name' => "ewei_shopv2:composter:auto"));
			if (empty($ruleauto)) {
				$rule_data = array(
					'uniacid' => $_W['uniacid'],
					'name' => 'ewei_shopv2:composter:auto',
					'module' => 'ewei_shopv2',
					'displayorder' => 0,
					'status' => 1
				);
				pdo_insert('rule', $rule_data);
				$rid = pdo_insertid();

				$keyword_data = array(
					'uniacid' => $_W['uniacid'],
					'rid' => $rid,
					'module' => 'ewei_shopv2',
					'content' => 'EWEI_SHOPV2_COMPOSTER',
					'type' => 1,
					'displayorder' => 0,
					'status' => 1
				);
				pdo_insert('rule_keyword', $keyword_data);
			}
			show_json(1, array('url' => webUrl('composter/edit', array('id' => $id, 'tab' => str_replace("#tab_", "", $_GPC['tab'])))));
		}
	
		
		include $this->template();
	}

	

}
