<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class User_EweiShopV2Page extends PluginWebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$params = array(':uniacid' => $_W['uniacid']);
		$condition = '';
		$keyword = trim($_GPC['keyword']);
		if (!(empty($keyword))) 
		{
			$condition .= ' and ( name like :keyword or mobile like :keyword or title like :keyword)';
			$params[':keyword'] = '%' . $keyword . '%';
		}
		if ($_GPC['categoryid'] != '') 
		{
			$condition .= ' and categoryid=' . intval($_GPC['categoryid']);
		}
		if ($_GPC['status'] != '') 
		{
			$condition .= ' and status=' . intval($_GPC['status']);
		}
		$sql = 'select * from ' . tablename('ewei_shop_union_user') . ' where uniacid=:uniacid AND deleted=0 ' . $condition . ' ORDER BY id desc limit ' . (($pindex - 1) * $psize) . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_union_user') . ' where uniacid=:uniacid AND deleted=0 ' . $condition, $params);
		$pager = pagination($total, $pindex, $psize);
		$category = $this->model->categoryAll();
		load()->func('tpl');
		include $this->template();
	}
	public function add() 
	{
		$this->post();
	}
	public function edit() 
	{
		$this->post();
	}
	protected function post() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$userset = array();
		if ($id) 
		{
			$item = pdo_fetch('select * from ' . tablename('ewei_shop_union_user') . ' where id=:id AND deleted=0 and uniacid=:uniacid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid']));

			if ($item['set'])
			{
				$userset = json_decode($item['set'], true);
			}
			if (!(empty($item['openid']))) 
			{
				$openid = m('member')->getMember($item['openid']);
			}
			if (!(empty($item['manageopenid']))) 
			{
				$manageopenid = m('member')->getMember($item['manageopenid']);
			}
			if (!(empty($item['management']))) 
			{
				$item['management'] = trim($item['management'], ',');
				$item['management'] = str_replace(',', '\',\'', $item['management']);
				$management = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member') . ' WHERE  uniacid=:uniacid AND `openid` IN (\'' . $item['management'] . '\')', array(':uniacid' => $_W['uniacid']));
			}
		}
		$diyform_flag = 0;
		$diyform_plugin = p('diyform');
		$f_data = array();
		if ($diyform_plugin && !(empty($_W['shopset']['union']['apply_diyform'])))
		{
			if (!(empty($item['diyformdata']))) 
			{
				$diyform_flag = 1;
				$fields = iunserializer($item['diyformfields']);
				$f_data = iunserializer($item['diyformdata']);
			}
			else 
			{
				$diyform_id = $_W['shopset']['union']['apply_diyformid'];
				if (!(empty($diyform_id))) 
				{
					$formInfo = $diyform_plugin->getDiyformInfo($diyform_id);
					if (!(empty($formInfo))) 
					{
						$diyform_flag = 1;
						$fields = $formInfo['fields'];
					}
				}
			}
		}
		if ($_W['ispost']) 
		{
			$wechatpay = '';
			if (is_array($_GPC['wechatpay'])) 
			{
				$wechatpay = $_GPC['wechatpay'];
				if ($_FILES['cert_file']['name']) 
				{
					$wechatpay['cert'] = $this->model->upload_cert('cert_file');
				}
				if ($_FILES['key_file']['name']) 
				{
					$wechatpay['key'] = $this->model->upload_cert('key_file');
				}
				if ($_FILES['root_file']['name']) 
				{
					$wechatpay['root'] = $this->model->upload_cert('root_file');
				}
				$wechatpay = json_encode($wechatpay);
			}
			if (!(empty($item))) 
			{
				$alipay_yuan = json_decode($item['alipay'], true);
				if (empty($_GPC['alipay']['publickey'])) 
				{
					$_GPC['alipay']['publickey'] = $alipay_yuan['publickey'];
				}
				if (empty($_GPC['alipay']['privatekey'])) 
				{
					$_GPC['alipay']['privatekey'] = $alipay_yuan['privatekey'];
				}
				if (empty($_GPC['alipay']['alipublickey'])) 
				{
					$_GPC['alipay']['alipublickey'] = $alipay_yuan['alipublickey'];
				}
				$userset['printer_status'] = intval($_GPC['printer_status']);
				$userset['printer'] = ((isset($_GPC['printer']) ? implode(',', $_GPC['printer']) : ''));
				$userset['printer_template'] = trim($_GPC['printer_template']);
				$userset['printer_template_default'] = trim($_GPC['printer_template_default']);
				$userset['credit1'] = trim($_GPC['credit1']);
				$userset['credit1_double'] = ((empty($_GPC['credit1_double']) ? 1 : (double) $_GPC['credit1_double']));
			}
			$alipay = ((is_array($_GPC['alipay']) ? json_encode($_GPC['alipay']) : ''));
			$lifetime = $_GPC['lifetime'];
			$params = array('uniacid' => $_W['uniacid'], 'storeid' => $_GPC['storeid'], 'merchid' => $_GPC['merchid'], 'setmeal' => $_GPC['setmeal'], 'title' => $_GPC['title'], 'logo' => $_GPC['logo'], 'manageopenid' => $_GPC['manageopenid'], 'isopen_commission' => $_GPC['isopen_commission'], 'openid' => $_GPC['openid'], 'name' => $_GPC['name'], 'mobile' => $_GPC['mobile'], 'categoryid' => $_GPC['categoryid'], 'wechat_status' => $_GPC['wechat_status'], 'wechatpay' => $wechatpay, 'alipay_status' => $_GPC['alipay_status'], 'alipay' => $alipay, 'withdraw' => $_GPC['withdraw'], 'username' => $_GPC['username'], 'password' => (!(empty($_GPC['password'])) ? $_GPC['password'] : ''), 'status' => $_GPC['status'], 'lifetimestart' => strtotime($lifetime['start']), 'lifetimeend' => strtotime($lifetime['end']), 'set' => json_encode($userset), 'can_withdraw' => intval($_GPC['can_withdraw']), 'show_paytype' => intval($_GPC['show_paytype']), 'couponid' => (is_array($_GPC['couponid']) ? implode(',', $_GPC['couponid']) : ''), 'management' => (is_array($_GPC['management']) ? implode(',', $_GPC['management']) : ''));
            if($_GPC['parent_id']>0){
                //查询LEVEL
                $level=pdo_fetchcolumn("select level from ".tablename('ewei_shop_union_user')." where id=:parent_id",array(':parent_id'=>$_GPC['parent_id']));
                $params['level']=$level+1;
            }
            $params['parent_id']=intval($_GPC['parent_id']);


            $params['perm_role']=implode(",",$_GPC['perms']);
			$user_totle = (int) pdo_fetchcolumn('SELECT id FROM ' . tablename('ewei_shop_union_user') . ' WHERE username=:username AND uniacid=:uniacid AND deleted=0 LIMIT 1', array(':username' => $params['username'], ':uniacid' => $_W['uniacid']));
			$store = pdo_fetch('SELECT id,storeid FROM ' . tablename('ewei_shop_union_user') . ' WHERE uniacid=:uniacid AND deleted=0 LIMIT 1', array(':uniacid' => $_W['uniacid']));
			$merch = pdo_fetch('SELECT id,merchid FROM ' . tablename('ewei_shop_union_user') . ' WHERE uniacid=:uniacid AND deleted=0 LIMIT 1', array(':uniacid' => $_W['uniacid']));
			if ($id) 
			{
				if ($user_totle && ($user_totle != $id)) 
				{
					show_json(0, '该登录用户名称,已经存在!请更换!');
				}

				$params['id'] = $id;
				if ($item['status'] != $params['status']) 
				{
					if ($params['status'] == 0) 
					{
						$message = '关闭';
					}
					else if ($params['status'] == 1) 
					{
						$message = '开启';
					}
					$this->model->sendMessage(array('name' => $params['name'], 'mobile' => $params['mobile'], 'status' => $message, 'createtime' => time()), 'checked', $params['manageopenid']);
				}
			}

			$res = $this->model->savaUser($params);


			if (isset($res['createtime'])) 
			{
				plog('union.user.add', '添加工会 ID: ' . $res['id'] . ' 工会名: ' . $res['title'] . '<br/>帐号: ' . $res['username']);
			}
			else 
			{
				plog('union.user.edit', '编辑工会 ID: ' . $res['id'] . ' 工会名: ' . $item['title'] . ' -> ' . $res['title'] . '<br/>帐号: ' . $item['username'] . ' -> ' . $res['username']);
			}
			show_json(1, array('url' => webUrl('union/user/edit', array('id' => $res['id'], 'tab' => str_replace('#tab_', '', $_GPC['tab'])))));
		}
		$category = $this->model->categoryAll();


		$wechatpay = json_decode($item['wechatpay'], true);
		$alipay = json_decode($item['alipay'], true);
		$order_template = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_printer_template') . ' WHERE uniacid=:uniacid  AND merchid=0', array(':uniacid' => $_W['uniacid']));

        $userlist=pdo_fetchall('select * from ' . tablename('ewei_shop_union_user') . ' where id<>:id AND deleted=0 and uniacid=:uniacid ', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        $perms=$this->model->getMenu();
        if(!empty($item['perm_role'])){
            $user_perms = explode(',', $item['perm_role']);
        }else{
            $user_perms=$this->model->defaultperms();
        }
		include $this->template();
	}
	public function status() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_union_user') . ' WHERE id in(' . $id . ') AND deleted=0 AND uniacid=' . $_W['uniacid']);
		foreach ($items as $item ) 
		{
			pdo_update('ewei_shop_union_user', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('union.user.edit', (('修改工会账户状态<br/>ID: ' . $item['id'] . '<br/>工会名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status']) == 1 ? '启用' : '禁用'));
		}
		show_json(1);
	}
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_union_user') . ' WHERE id in(' . $id . ') AND deleted=0 AND uniacid=' . $_W['uniacid']);
		foreach ($items as $item ) 
		{
			pdo_update('ewei_shop_union_user', array('deleted' => 1), array('id' => $item['id']));
			plog('union.user.delete', '删除`工会 <br/>工会:  ID: ' . $item['id'] . ' / 名称:   ' . $item['title']);
		}
		show_json(1);
	}
}
?>