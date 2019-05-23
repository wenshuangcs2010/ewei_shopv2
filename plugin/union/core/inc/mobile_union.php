<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class UnionMobilePage extends PluginMobilePage
{
    public $member;
	public function __construct() 
	{
		global $_W;
		global $_GPC;

		parent::__construct();

        if (empty($_W['openid'])) {
            $_W['openid'] = m('account')->checkLogin();
        }
        $_W['shopset']['shop']['name']="云工会";
        $openid=$_W['openid'];
        $this->member=$this->model->get_member($openid);
        $_W['unionid']=empty($this->member) ? 0 :$this->member['union_id'];
	}
}
?>