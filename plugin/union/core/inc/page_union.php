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



		if (!($this->model->is_perm($_W['routes'])) && ($_W['routes'] != 'login') && ($_W['routes'] != 'quit') && ($_W['routes'] != 'qr')) 
		{
			$this->message('暂时没有权限查看!');
		}


        $this->user_info=$this->model->userInfo($_W['unionid']);


        $this->member_info=m('member')->getMember( $this->user_info['manageopenid']);
        $menu=$this->model->getMenu();


        if(isset($_W['unionuser']['role'])){

            $_W['menu']=array('ly'=>$menu['ly']);
            $role=iunserializer($_W['unionuser']['role']);


            $rolevaluelist=array();

            foreach ($role as $value){
                foreach ($value as $v){
                    $rolevaluelist[]=$v;
                }
            }

            foreach ($_W['menu'] as $k=>$item){
                foreach ($item['items'] as $j=>$v){

                    if(in_array($v['route'],$rolevaluelist)==false){
                        unset($_W['menu'][$k]['items'][$j]);
                    }
                }
            }
            $_W['isoperator']=1;
        }else{
            if(!empty($this->user_info['perm_role'])){
                $user_perm=explode(",",$this->user_info['perm_role']);
                foreach ($menu as $key=>$v){
                    if(!in_array($key,$user_perm)){
                        unset($menu[$key]);
                    }
                }
            }else{
                $user_perm=$this->model->defaultperms();
                foreach ($menu as $key=>$v){
                    if(!in_array($key,$user_perm)){
                        unset($menu[$key]);
                    }
                }
            }
            $_W['menu']=$menu;
        }
        if($_W['routes']!='member.suggestions'){
            $memu_key=explode(".",$_W['routes']);
            $_W['routes_key']=$memu_key[0];
        }else{
            $_W['routes_key']="suggestions";
        }


        //查询文章分类被推荐到首页的情况
        $dcoument_category=pdo_fetchall("select * from ".tablename("ewei_shop_union_document_category")." where is_index=1 and uniacid =:uniacid and union_id=:union_id order by displayorder desc",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        foreach ($dcoument_category as &$item){
            $childrens=pdo_fetchall("select * from ".tablename("ewei_shop_union_document_category")." where uniacid=:uniacid and union_id=:union_id and parent_id=:parent_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],':parent_id'=>$item['id']),"id");
            $item['children']=$childrens;
            $item['childrenids']=array_keys($childrens);
        }
        unset($item);
        $_W['dcoument_category']=$dcoument_category;
        //查询被推荐的活动

        $actity_category=pdo_fetchall("select * from ".tablename("ewei_shop_union_memberactivity_category")." where is_index=1 and uniacid =:uniacid and union_id=:union_id order by displayorder desc",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        foreach ($actity_category as &$item){
            $childrens=pdo_fetchall("select * from ".tablename("ewei_shop_union_memberactivity_category")." where uniacid=:uniacid and union_id=:union_id and parent_id=:parent_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],':parent_id'=>$item['id']),"id");
            $item['children']=$childrens;
            $item['childrenids']=array_keys($childrens);
        }
        unset($item);
        $_W['actity_category']=$actity_category;
        $asoconfig=pdo_fetch("select * from ".tablename("ewei_shop_union_association_config")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        $_W['asoconfig']=$asoconfig;


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