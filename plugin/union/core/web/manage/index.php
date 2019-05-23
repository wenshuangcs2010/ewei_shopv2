<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$userset = $this->getUserSet();
        //工会人员总数
        $union_members=$this->model->getUnionList($_W['unionid']);
        $total1=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_suggestions")." where status=1 and union_id=:unionid",array(":unionid"=>$_W['unionid']));
        $total2=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_welfare")." where status=1 and union_id=:unionid",array(":unionid"=>$_W['unionid']));
        $total3=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_dynamic")." where  is_delete=0 and  union_id=:unionid",array(":unionid"=>$_W['unionid']));
        $sql="select s.title,s.description,m.realname from ".tablename("ewei_shop_union_suggestions")." as s ".
            " LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid =s.openid and m.uniacid = s.uniacid "
            ." where s.status>0 and  s.union_id=:unionid and s.is_delete=0 order by s.create_time desc limit 5";


        $suggestions= pdo_fetchall($sql,array(":unionid"=>$_W['unionid']));
        $welfares=pdo_fetchall("select * from ".tablename("ewei_shop_union_welfare")." where is_delete=0 and  status>0 and union_id=:unionid order by add_time desc limit 0,5",array(":unionid"=>$_W['unionid']));


        $summoney=pdo_fetchcolumn("select sum(money) from ".tablename("ewei_shop_union_welfare")." where (status>0 and status<>3) and union_id=:unionid",array(":unionid"=>$_W['unionid']));
        $dynamics= pdo_fetchall("select * from ".tablename("ewei_shop_union_dynamic")." where is_delete=0 and   union_id=:unionid order by createtime desc limit 3",array(":unionid"=>$_W['unionid']));

        include $this->template();
	}
	public function quit() 
	{
		global $_W;
		global $_GPC;
		unset($_SESSION['__union_' . (int) $_GPC['i'] . '_session']);
		header('location: ' . unionUrl('login'));
	}

	public function query_member() 
	{
		global $_W;
		global $_GPC;
		$mobile = $_GPC['mobile'];
		if (!($mobile)) 
		{
			show_json(0);
		}
		$info = m('member')->getMobileMember($mobile);
		if (!(empty($info['salt'])) && !(empty($info['pwd']))) 
		{
			show_json(1);
		}
		else 
		{
			show_json(2);
		}
		show_json(0);
	}
	public function verify_password() 
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) 
		{
			$password = trim($_GPC['password']);
			$mobile = $_GPC['mobile'];
			$info = m('member')->getMobileMember($mobile);
			if (md5($password . $info['salt']) == $info['pwd']) 
			{
				show_json(1, $info);
			}
		}
		show_json(0);
	}
	public function set_password() 
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) 
		{
			$password = trim($_GPC['password']);
			$mobile = $_GPC['mobile'];
			$info = m('member')->getMobileMember($mobile);
			if (empty($info['salt']) && empty($info['pwd'])) 
			{
				$salt = random(8);
				$pwd = md5($password . $salt);
				pdo_update('ewei_shop_member', array('pwd' => $pwd, 'salt' => $salt), array('id' => $info['id']));
				show_json(1, $info);
			}
		}
		show_json(0);
	}

}
?>