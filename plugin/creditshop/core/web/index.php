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

	public function main() {
		global $_W;
		if (cv('creditshop.goods')) {
			header('location: ' . webUrl('creditshop/goods'));
		} else if (cv('creditshop.category')) {
			header('location: ' . webUrl('creditshop/category'));
		} else if (cv('creditshop.adv')) {
			header('location: ' . webUrl('creditshop/adv'));
		} else if (cv('creditshop.log')) {
			header('location: ' . webUrl('creditshop/log'));
		}  else if (cv('creditshop.cover')) {
			header('location: ' . webUrl('creditshop/cover'));
		} else if (cv('creditshop.notice')) {
			header('location: ' . webUrl('creditshop/notice'));
		} else if (cv('creditshop.set')) {
			header('location: ' . webUrl('creditshop/set'));
		}else{
			header('location: ' . webUrl());
		}
		exit;
	}

	/*public function set() {
		global $_W, $_GPC;
	 
		if ($_W['ispost']) {
			ca('creditshop.set.edit');
			$data = is_array($_GPC['data']) ? $_GPC['data'] : array();
			$data['share_icon'] = save_media($data['share_icon']);
			//兑换关键词
			$exchangekeyword = $data['exchangekeyword'];

			$keyword = m('common')->keyExist($exchangekeyword);
			if(!empty($keyword)){
				if($keyword['name']!='ewei_shopv2:creditshop'){
					show_json(0, '关键字已存在!');
				}
			}
			$rule = pdo_fetch("select * from " . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':name' => "ewei_shopv2:creditshop"));
			if (empty($rule)) {
				$rule_data = array(
					'uniacid' => $_W['uniacid'],
					'name' => 'ewei_shopv2:creditshop',
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
					'content' => trim($exchangekeyword),
					'type' => 1,
					'displayorder' => 0,
					'status' => 1
				);
				pdo_insert('rule_keyword', $keyword_data);
			} else {
				pdo_update('rule_keyword', array('content' => trim($exchangekeyword)), array('rid' => $rule['id']));
			}
			
			$this->updateSet($data);

			//模板缓存
			m('cache')->set('template_' . $this->pluginname, $data['style']);
			plog('creditshop.set.edit', '修改积分商城基本设置');
			
			show_json(1);
		}

		$styles = array();
		$dir = IA_ROOT . "/addons/ewei_shopv2/plugin/" . $this->pluginname . "/template/mobile/";
		if ($handle = opendir($dir)) {
			while (($file = readdir($handle)) !== false) {
				if ($file != ".." && $file != ".") {
					if (is_dir($dir . "/" . $file)) {
						$styles[] = $file;
					}
				}
			}
			closedir($handle);
		}
		$data = $this->set;
		include $this->template();
	}*/

	public function notice() {
		global $_W, $_GPC;
		$set = $this->set;
		if ($_W['ispost']) {
			
			ca('creditshop.notice.edit');
			
			$data = is_array($_GPC['tm']) ? $_GPC['tm'] : array();
			if (is_array($_GPC['openids'])) {
				$data['openids'] = implode(",", $_GPC['openids']);
			}
			$this->updateSet(array('tm'=>$data));
			
			plog('creditshop.notice.edit', '修改积分商城通知设置');
			
			show_json(1);
		}
		//通知人
		$salers = array();
		if (isset($set['tm']['openids'])) {
			if (!empty($set['tm']['openids'])) {

				$openids = array();
				$strsopenids = explode(",", $set['tm']['openids']);
				foreach ($strsopenids as $openid) {
					$openids[] = "'" . $openid . "'";
				}
				$salers = pdo_fetchall("select id,nickname,avatar,openid from " . tablename('ewei_shop_member') . ' where openid in (' . implode(",", $openids) . ") and uniacid={$_W['uniacid']}");
			}
		}
		include $this->template();
	}
}
