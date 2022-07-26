<?php
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Login_EweiShopV2Page extends UnionWebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;

		if (!(empty($_GPC['auth_code']))) 
		{
			$auth_code = authcode(base64_decode($_GPC['auth_code']), 'DECODE', 'ewei_shopv2_union');

			if ($auth_code) 
			{
				$account = explode('|', $auth_code);
				$this->login($account[0], $account[1], $account[2]);
			}
		}
		if ($_W['ispost'] && $_W['isajax']) 
		{
			$username = trim($_GPC['username']);
			$password = trim($_GPC['password']);
			$is_operator = intval($_GPC['is_operator']);
			$this->login($username, $password, NULL, $is_operator);
		}
		$submitUrl = unionUrl('login');

		$set = $this->set;
		include $this->template();
	}
	public function login($username, $password, $salt = NULL, $is_operator = 0) 
	{
		global $_W;
		global $_GPC;
        $user = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_union_user') . ' WHERE username=:username AND uniacid=:uniacid AND status=1 AND deleted=0 LIMIT 1', array(':username' => $username, ':uniacid' => $_W['uniacid']));
		if (!$user)
		{
		    //酒店线路管理员登录
            $operator = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_union_ly_role_member') . ' WHERE username=:username AND status=1 AND deleted=0  LIMIT 1', array(':username' => $username));

            if (empty($operator))
            {
                show_json(0, '用户名不存在!');
            }
            $password = md5($password . $operator['salt']);
            if ($operator['password'] != $password)
            {
                show_json(0, '用户名密码错误!');
            }
            $perm = iunserializer($operator['role']);

            if (empty($perm))
            {
                show_json(0, '用户没有有任何权限!');
            }
            $user = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_union_ly_role_member') . ' WHERE id=:id  AND status=1 AND deleted=0 LIMIT 1', array(':id' => $operator['id']));

            if (!(empty($user)))
            {

                session_start();
                $_SESSION['__union_' . (int) $_GPC['i'] . '_session'] = $user;

                show_json(1, array('url' => unionUrl(current($perm)[0])));
            }
            else
            {
                show_json(0, '用户名不存在!');
            }
		}

		if ($salt !== NULL) 
		{
			if (!(empty($user))) 
			{
				if (($user['salt'] == $salt) && ($user['password'] == $password)) 
				{
					session_start();
					$_SESSION['__union_' . (int) $_GPC['i'] . '_session'] = $user;
					header('Location:' . unionUrl('index'));
					exit();
				}
			}
			header('Location:' . unionUrl('login'));
			exit();
		}
		else if (!(empty($user))) 
		{
			if ($user['deleted']) 
			{
				show_json(0, '该用户已被删除!');
			}
			$password = md5($password . $user['salt']);
			if ($user['password'] == $password) 
			{
				session_start();
				$_SESSION['__union_' . (int) $_GPC['i'] . '_session'] = $user;
				show_json(1, array('url' => unionUrl('index')));
			}
			else 
			{
				show_json(0, '用户名密码错误!');
			}
		}
		else 
		{
			show_json(0, '用户名不存在!');
		}
	}
}
?>