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
        $_W['defaultunionid']=11;
        $set = $_W['shopset'];
        $_W['shopshare'] = array(
            'title' =>$set['shop']['name'],
            'imgUrl' => tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => "工汇智联是建立在手机微信上的交流服务平台，面向系统性单位免费开放。职工打开微信关注公众号“工汇智联”并绑定个人信息，即可一键触达工会系统。十余个内置版块囊括工会工作方方面面，可实现信息发布与收集、工会选举、活动报名与签到、风采展示等多种功能。",
            'link' => mobileUrl('union',null,true)
        );
        $openid=$_W['openid'];

        $this->model->checkMember($openid);


        $this->member=$this->model->get_member($openid);

        $_W['unionid']=empty($this->member) ? 0 :$this->member['union_id'];
        if(!empty($_W['unionid'])){
            $_W['union_info']=$this->model->get_union_info($_W['unionid']);
        }
        $member=m("member")->getMember($openid);
        $memberinfo=$this->model->get_union_member_info($_W['openid']);

        if($_W['unionid']){
            $parmconfig=$this->model->get_config($_W['unionid']);
            $_W['welfareconfig']=iunserializer($parmconfig['config']);
        }

        if($member['mobile'] && empty($memberinfo)){
            $sql="select * from ".tablename("ewei_shop_union_members")." where mobile_phone=:mobile_phone and uniacid =:uniacid ";
            $mobile_list=pdo_fetchall($sql,array(':mobile_phone'=>$member['mobile'],":uniacid"=>$_W["uniacid"]));
            foreach ($mobile_list as $key=>$value){
                $union_memberdata=array(
                    'uniacid'=>$_W['uniacid'],
                    'add_time'=>time(),
                    'openid'=>$openid,
                    'status'=>1,
                );
                if($key==0 && empty($memberinfo)){
                    $union_memberdata['is_default']=1;
                }
                pdo_update('ewei_shop_union_members',$union_memberdata,array('id'=>$value['id']));
            }
            $memberinfo=$this->model->get_union_member_info($openid);
            $this->member=$this->model->get_member($openid);
            $_W['unionid']=empty($this->member)  ? 0 :$this->member['union_id'];
            if(!empty($_W['unionid'])){
                $_W['union_info']=$this->model->get_union_info($_W['unionid']);
            }
        }


            if( empty($memberinfo['openid']) && $_GPC['r']!='union.member.member_info' && $_GPC['r']!='union.member.updateinfo' && $_GPC['r']!='member.bind'  ) {
                $url = base64_encode(mobileUrl("union",'',true));
                $realmember=m("member")->getMember($openid);
                if($realmember['mobile'] && $realmember['mobileverify']==1){
                    $loginurl = mobileUrl('union/member/member_info');
                }else{
                    $loginurl = mobileUrl('member/bind', array('backurl' => $url ? $url : ""));
                }
                header('location: ' . $loginurl);
                exit;
            }



    }


}
?>