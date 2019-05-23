<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Member_EweiShopV2Page extends UnionMobilePage
{

    public function main()
    {
        global $_W;
        global $_GPC;
        $member=m("member")->getmember($_W['openid']);
        $_W['union']['title']="会员中心";
        $company=$this->model->get_union_info($_W['unionid']);
        include $this->template();
    }

    public function unionlist(){
        include $this->template();
    }

    public function get_list(){
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $member=m("member")->getMember($_W['openid']);
        $mobilephone=$member['mobile'];
        $condition = ' and um.uniacid = :uniacid AND um.status=1 and um.deleted=0 and ums.mobile_phone=:mobile ';
        $keyword=trim($_GPC['keywords']);
        if(!empty($keyword)){
            $condition.=' and keyword like :keyword';
            $params[':keyword']="%".$keyword."%";
        }

        $params = array(':uniacid' => $uniacid,":mobile"=>$mobilephone);
        $sql="select um.id,um.title,IFNULL(ums.is_default,-1) as is_default,IFNULL(ums.activate,-1) as activate from ".tablename("ewei_shop_union_user")." as um ".
            " LEFT JOIN ".tablename("ewei_shop_union_members")." as ums ON ums.union_id=um.id "
            ." where 1 ".$condition."ORDER BY ums.is_default desc,ums.activate desc LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_user')." um ".
            " LEFT JOIN ".tablename("ewei_shop_union_members")." as ums ON ums.union_id=um.id ". " where 1 ".$condition;

        $total = pdo_fetchcolumn($countsql,$params);

        $list = pdo_fetchall($sql, $params);


        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function member_info(){
        global $_W;
        global $_GPC;
        $member=m("member")->getmember($_W['openid']);
        $union_memebr=$this->model->get_union_info($_W['unionid']);
        $member['birthday']=$member['birthyear']."-".$member['birthmonth'].'-'.$member['birthday'];
        //查询用火所在的部门

        if($this->member['department']){
            $department=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where id=:id",array(':id'=>$this->member['department']));
        }
        include $this->template();
    }
    //数据更新
    public function updateinfo(){
        global $_W;
        global $_GPC;
        $openid=$_W['openid'];
        $member_info=m("member")->getMember($openid);
        if(empty($member_info['mobile'])){
            show_json(0, "请先绑定手机号");
        }
        if(empty($this->member)){
            show_json(0, "还未加入工会,请先加入工会后做操作");
        }

        $member_update=array(
            'realname'=>trim($_GPC['realname']),
            'birthyear'=>isset($_GPC['birthday'][0]) ? $_GPC['birthday'][0]:'',
            'birthmonth'=>isset($_GPC['birthday'][1]) ? $_GPC['birthday'][1]:'',
            'birthday'=>isset($_GPC['birthday'][2])?$_GPC['birthday'][2]:'',
        );
        if($member_info['level']!=30){
            $member_update['level']=30;
        }
        $union_memberdata=array(
            'uniacid'=>$_W['uniacid'],
            'mobile_phone'=>$member_info['mobile'],
            'name'=>trim($_GPC['realname']),
            'year'=>isset($_GPC['birthday'][0]) ? $_GPC['birthday'][0]:'',
            'moth'=>isset($_GPC['birthday'][1]) ? $_GPC['birthday'][1]:'',
            'day'=>isset($_GPC['birthday'][2])?$_GPC['birthday'][2]:'',
            'nick_name'=>$member_info['nick_name'],
            'add_time'=>time(),
            'openid'=>$openid,
            'wechat'=>$_GPC['wechat'],
            'mail'=>$_GPC['mail'],
        );
        pdo_begin();
        if($this->member){
            unset($union_memberdata['add_time']);
            pdo_update('ewei_shop_union_members',$union_memberdata,array('id'=>$this->member['id']));
        }
        pdo_update("ewei_shop_member",$member_update,array("openid"=>$openid,'uniacid'=>$_W['uniacid']));
        pdo_commit();
        show_json(1, "公司已经切换");
    }

    public function joinunion(){
        global $_W;
        global $_GPC;
        $openid=$_W['openid'];
        $union_id=intval($_GPC['unionid']);
        //检查用户有没有绑定手机号
        $member_info=m("member")->getMember($openid);


        $union_member=$this->model->get_mobile_member($member_info['mobile'],$union_id);
        if(empty($union_member)){
            show_json(1);
        }
        if(empty($union_member["openid"])){//绑定OPENID
            pdo_update('ewei_shop_union_members',array("openid"=>$openid),array('id'=>$union_member['id']));
        }
        if($union_member['status']==0 || $union_member['activate']==0){
            show_json(0, "您的账号正在审核中");
        }
        if($member_info['level']!=30){
            pdo_update("ewei_shop_member",array("level"=>30),array("id"=>$member_info['id']));
        }
        pdo_update('ewei_shop_union_members',array("is_default"=>0),array('mobile_phone'=>$member_info['mobile']));
        pdo_update('ewei_shop_union_members',array("is_default"=>1),array('id'=>$union_member['id']));
        show_json(0, "已切换默认默认公司");
    }

    public function join_union(){
        global $_W;
        global $_GPC;
        $unionid=intval($_GPC['unionid']);
        $union_memebr=$this->model->get_union_info($unionid);
        $openid=$_W['openid'];
        //检查用户有没有绑定手机号
        $member=m("member")->getMember($openid);
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid";
        $department=pdo_fetchall($sql,array(":union_id"=>$unionid,':uniacid'=>$_W['uniacid']));
        include $this->template();
    }

    public function join_union_post(){
        global $_W;
        global $_GPC;
        $openid=$_W['openid'];
        $union_id=intval($_GPC['unionid']);
        if(empty($union_id)){
            show_json(0, "数据异常请重试");
        }
        $member=m("member")->getMember($openid);
        if(empty($member['mobile'])){
            show_json(0, "请先绑定手机号");
        }
        //查询这个用户没有信息
        $union_member=$this->model->get_mobile_member($member['mobile'],$union_id);

        if(empty($union_member)){
            //添加账号
            $union_memberdata=array(
                'uniacid'=>$_W['uniacid'],
                'mobile_phone'=>$member['mobile'],
                'name'=>trim($member['realname']),
                'year'=>$member['birthyear'],
                'moth'=>$member['birthmonth'],
                'day'=>$member['birthday'],
                'nick_name'=>$member['nick_name'],
                'add_time'=>time(),
                'openid'=>$openid,
                'union_id'=>$union_id,
            );
            if(pdo_insert('ewei_shop_union_members',$union_memberdata)){
                show_json(0,"加入成功请等待管理员审核");
            };
        }
        if($union_member['status']==0 || $union_member['activate']==0){
            show_json(0, "您的账号正在审核中");
        }
    }

}