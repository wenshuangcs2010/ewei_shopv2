<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require_once EWEI_SHOPV2_PLUGIN . 'union/core/inc/common.php';
class UnionWebPage extends PluginWebPage
{
	public $pluginname;
	public $model;
	public $plugintitle;
	public $set;
	public $user_info;
	public $member_info;
	public function __construct($_com = '', $_init = false) 
	{
		global $_W;

		if (!(empty($_com))) 
		{
			if (!(com('perm')->check_com($_com))) 
			{
				$this->message('你没有相应的权限查看');
			}
		}
		else 
		{
			parent::__construct(false);
		}
		$this->pluginname = $_W['plugin'];
		$this->modulename = 'ewei_shopv2';
		$this->plugintitle = m('plugin')->getName($this->pluginname);
		$this->model = m('plugin')->loadModel($this->pluginname);
		$this->set = $this->model->getSet();
        $_W['union_name']="工会";
		if ($_W['ispost']) 
		{
			rc($this->pluginname);
		}

		$_W['routes'] = str_replace('union.manage.', '', $_W['routes']);

		if (empty($this->set['isopen'])) 
		{
			if (($_W['routes'] != 'login') && ($_W['routes'] != 'quit')) 
			{
				$this->message('暂未开启,工会插件!', unionUrl('quit'));
			}
		}

		if ($_W['unionuser']['lifetimeend'] < time())
		{
			if (($_W['routes'] != 'login') && ($_W['routes'] != 'quit')) 
			{

				$this->message('账号已到期!', unionUrl('quit'));
			}
		}
		if (!($this->model->is_perm($_W['routes'])) && ($_W['routes'] != 'login') && ($_W['routes'] != 'quit') && ($_W['routes'] != 'qr')) 
		{
			$this->message('暂时没有权限查看!');
		}
        $this->user_info=$this->model->userInfo($_W['unionid']);

        $this->member_info=m('member')->getMember( $this->user_info['manageopenid']);
	}
	public function template($filename = '', $type = TEMPLATE_INCLUDEPATH, $account = false) 
	{
		global $_W;
		global $_GPC;
		load()->func('tpl');
		if (empty($filename)) 
		{
			$filename = str_replace('.', '/', $_W['routes']);
		}
		$filename = str_replace('/add', '/post', $filename);
		$filename = str_replace('/edit', '/post', $filename);
		$name = 'ewei_shopv2';
		$moduleroot = IA_ROOT . '/addons/ewei_shopv2';
		$compile = IA_ROOT . '/data/tpl/web/' . $_W['template'] . '/union/' . $name . '/' . $filename . '.tpl.php';
		$source = $moduleroot . '/template/' . $filename . '.html';
		if (!(is_file($source))) 
		{
			$source = $moduleroot . '/template/' . $filename . '/index.html';
		}
		if (!(is_file($source))) 
		{
			$explode = explode('/', $filename);
			$source = $moduleroot . '/plugin/union/template/web/manage/' . implode('/', $explode) . '.html';
			if (!(is_file($source))) 
			{
				$source = $moduleroot . '/plugin/union/template/web/manage/' . implode('/', $explode) . '/index.html';
			}
		}
		if (!(is_file($source))) 
		{
			$explode = explode('/', $filename);
			$temp = array_slice($explode, 1);
			$source = $moduleroot . '/plugin/' . $explode[0] . '/template/web/' . implode('/', $temp) . '.html';
			if (!(is_file($source))) 
			{
				$source = $moduleroot . '/plugin/' . $explode[0] . '/template/web/' . implode('/', $temp) . '/index.html';
			}
		}
		if (!(is_file($source))) 
		{
			exit('Error: template source \'' . $filename . '\' is not exist!');
		}
		if (DEVELOPMENT || !(is_file($compile)) || (filemtime($compile) < filemtime($source))) 
		{
			shop_template_compile($source, $compile, true);
		}
		return $compile;
	}
	public function manageMenus() 
	{
		global $_GPC;
		global $_W;
		$routes = explode('.', $_W['routes']);
		$tab = ((isset($routes[0]) ? $routes[0] : ''));
		include $this->template($tab . '/tabs');
	}
	public function getUserSet($name = '') 
	{
		global $_W;
		return $this->model->getUserSet($name, $_W['unionid']);
	}
	public function updateUserSet($data = array()) 
	{
		global $_W;
		return $this->model->updateUserSet($data, $_W['unionid']);
	}
	public function qr() 
	{
		global $_W;
		global $_GPC;
		$url = trim($_GPC['url']);
		require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
		QRcode::png($url, false, QR_ECLEVEL_L, 16, 1);
	}
}
?>