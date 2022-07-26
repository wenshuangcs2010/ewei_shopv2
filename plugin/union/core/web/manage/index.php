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
        $total2=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_welfare")." where status=1 and is_delete=0 and  union_id=:unionid",array(":unionid"=>$_W['unionid']));
        $total3=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_dynamic")." where  is_delete=0 and  union_id=:unionid",array(":unionid"=>$_W['unionid']));
        $sql="select s.title,s.description,m.realname,s.id from ".tablename("ewei_shop_union_suggestions")." as s ".
            " LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid =s.openid and m.uniacid = s.uniacid "
            ." where s.status=1 and  s.union_id=:unionid and s.is_delete=0 order by s.create_time desc limit 5";


        $suggestions= pdo_fetchall($sql,array(":unionid"=>$_W['unionid']));
        $welfares=pdo_fetchall("select * from ".tablename("ewei_shop_union_welfare")." where is_delete=0 and  status=1 and union_id=:unionid order by add_time desc limit 0,10",array(":unionid"=>$_W['unionid']));


        $summoney=pdo_fetchcolumn("select sum(money) from ".tablename("ewei_shop_union_welfare")." where (status>0 and status<>3) and union_id=:unionid",array(":unionid"=>$_W['unionid']));



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
    public $membertype=array(
        1=>"正式工",
        2=>"临时工",
        3=>"外派",
        4=>"借调",
        5=>"外聘",
        6=>"劳务派遣",
        7=>"其他",
    );
	public function sms_all(){
        global $_W;
        global $_GPC;

        $sql_grouplist="select id,groupname from ".tablename("ewei_shop_union_uniongroup")." where uniacid=:uniacid and union_id=:union_id";
        $grouplist=pdo_fetchall($sql_grouplist,array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        if ($_W['ispost'])
        {
            $message=trim($_GPC['desc']);
            $config= pdo_fetch("SELECT * FROM " . tablename('ewei_shop_sms_set') . " WHERE uniacid=:uniacid ", array(':uniacid'=>$_W['uniacid']));

            $status=intval($_GPC['status']);
            $member_type=intval($_GPC['member_type']);

            $uniongroupid=intval($_GPC['uniongroupid']);
            $condtion=" where 1 and  uniacid=:uniacid and union_id=:union_id and activate=1 ";
            $params=array(
                ':uniacid'=>$_W['uniacid'],
                ':union_id'=>$_W['unionid'],
            );
            if($status!=-1){
                $condtion.=" AND status=:status";
                $params[':status']=$status;
            }
            if($member_type!=-1){
                $condtion.=" and type=:type";
                $params[':type']=$member_type;
            }
            if($uniongroupid!=-1){
                $condtion.=" and uniongroupid=:uniongroupid";
                $params[':uniongroupid']=$uniongroupid;
            }
            if(empty($message)){
                $this->model->show_json(0,'请输入短信内容');
            }

            if($config['chuang']){
                $data=array(
                    'clliuk'=>$config['clliuk'],
                    'clyzaccount'=>"M3661107",
                    'clyzpassword'=>'TbYCQX0DJN3b7e',
                );
                include_once EWEI_SHOPV2_VENDOR.'chuang/api.php';
                $api=new Api($data);
                $smssign="【工汇智联】";
                $message.=",退订回复T";
                //获取待发送用户
                $sql="select count(*) from ".tablename("ewei_shop_union_members").$condtion;
                $count=pdo_fetchcolumn($sql,$params);
                if(empty($count)){
                    $this->model->show_json(0,'未查询到筛选用户');
                }
                $pageend=ceil($count/100);
                for($pageindex=1;$pageindex<=$pageend;$pageindex++){
                    $sql="select mobile_phone from ".tablename("ewei_shop_union_members").$condtion." LIMIT ".($pageindex-1).",100";
                    $list= pdo_fetchall($sql,$params,"mobile_phone");
                    $mobilelist=array_keys($list);
                    $mobilestr=join(",",$mobilelist);
                    $ret= $api->send($mobilestr,$smssign,$message);
                }

                $this->model->show_json(1,'发送成功');

            }else{
                $this->model->show_json(0, "短信配置异常请联系管理员处理");
            }

        }


        include $this->template('union/manage/union_config/sms_all');
    }


}
?>