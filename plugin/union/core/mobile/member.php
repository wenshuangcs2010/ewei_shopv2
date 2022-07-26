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
        $unionmember=$this->model->get_member($_W['openid'],$_W['union_id']);
        $_W['union']['title']="会员中心";
        $company=$this->model->get_union_info($_W['unionid']);
        $set = m('common')->getPluginset('union');
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
        //查询用火所在的部门
        $backurl=base64_encode(mobileUrl("union/member/member_info",array(),true));
        if($this->member['department']){
            $department=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where id=:id",array(':id'=>$this->member['department']));
        }
        $member['birthday']=$member['birthyear']."-".$member['birthmonth'].'-'.$member['birthday'];
        //根据用户填写的手机号填写查询工会信息
        if($member['mobile']){
           $union_member_info=  pdo_fetch("select * from ".tablename("ewei_shop_union_members")." where mobile_phone=:mobile_phone and is_default=1",array(":mobile_phone"=>$member['mobile']));

           if(empty($union_member_info)){
                $union_member_info=  pdo_fetch("select * from ".tablename("ewei_shop_union_members")." where mobile_phone=:mobile_phone limit 1",array(":mobile_phone"=>$member['mobile']));
                if(!empty($union_member_info)){
                   $union_memebr=$this->model->get_union_info($union_member_info['union_id']);
                   $department=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where id=:id",array(':id'=>$union_member_info['department']));
                   $member['birthday']=$union_member_info['year']."-".$union_member_info['moth'].'-'.$union_member_info['day'];
                }
           }


            //如果还是没有账号
            if(empty($union_member_info)){
                $union_member_info=$this->model->get_member($_W['openid']);
                if(!empty($union_member_info)){
                    $department=pdo_fetch("select * from ".tablename("ewei_shop_union_department")." where id=:id",array(':id'=>$union_member_info['department']));
                    $member['birthday']=$union_member_info['year']."-".$union_member_info['moth'].'-'.$union_member_info['day'];
                }
            }
            //这个是有手机号的情况
            if(empty($union_member_info)){
                $union_memebr=$this->model->get_union_info($_W['defaultunionid']);
                $union_member_info=array(
                   'uniacid'=>$_W['uniacid'],
                   'union_id'=>$_W['defaultunionid'],
                   'mobile_phone'=>$member['mobile'],
                   'activate'=>1,
                   'add_time'=>TIMESTAMP,
                   'openid'=>$_W['openid'],
                   'type'=>1,
                   'entrytime'=>TIMESTAMP,
                   'status'=>1,
                   'is_default'=>1,
                );
                pdo_insert("ewei_shop_union_members",$union_member_info);
                $union_member_info['id']=pdo_insertid();
            }

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
        //检查手机号是否修改

        $sql="select * from ".tablename("ewei_shop_union_members")." where openid=:openid and union_id=:union_id and uniacid =:uniacid ";
        $nowMember=pdo_fetch($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':openid'=>$openid));

        if(!empty($nowMember) && $nowMember['mobile_phone']!=$member_info['mobile']){
            //用户进行了手机号更新需要更新工会手机号
            pdo_update("ewei_shop_union_members",array('mobile_phone'=>$member_info['mobile']),array('mobile_phone'=>$nowMember['mobile_phone'],'uniacid'=>$_W['uniacid']));

        }

        //查询当前手机号在几个工会中存在
        $sql="select * from ".tablename("ewei_shop_union_members")." where mobile_phone=:mobile_phone and uniacid =:uniacid ";
        $mobile_list=pdo_fetchall($sql,array(':mobile_phone'=>$member_info['mobile'],":uniacid"=>$_W["uniacid"]));
        $union_member_info=$this->model->get_member($_W['openid']);

        if(empty($mobile_list) && empty($union_member_info)){
            show_json(0, "您还未加入工会,请联系贵工会管理员");
        }
        //查询有没有 默认工会的存在
        $sql="select id from ".tablename("ewei_shop_union_members")." where mobile_phone=:mobile_phone and uniacid =:uniacid and is_default=1 ";
        $is_default_id=pdo_fetchcolumn($sql,array(':mobile_phone'=>$member_info['mobile'],":uniacid"=>$_W["uniacid"]));

       foreach ($mobile_list as $key=>$value){
           $member_update=array(
               'realname'=>trim($_GPC['realname']),
               'birthyear'=>isset($_GPC['birthday'][0]) ? $_GPC['birthday'][0]:'',
               'birthmonth'=>isset($_GPC['birthday'][1]) ? $_GPC['birthday'][1]:'',
               'birthday'=>isset($_GPC['birthday'][2])?$_GPC['birthday'][2]:'',
           );
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
               'status'=>1,

           );
            if($key==0 && empty($is_default_id)){
                $union_memberdata['is_default']=1;
            }
           pdo_begin();
           unset($union_memberdata['add_time']);
           pdo_update('ewei_shop_union_members',$union_memberdata,array('id'=>$value['id']));
           pdo_update("ewei_shop_member",$member_update,array("openid"=>$openid,'uniacid'=>$_W['uniacid']));
           pdo_commit();
       }
        show_json(1, array('message'=>"用户绑 定成功",'url'=>mobileUrl("union")));
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
            pdo_update('ewei_shop_union_members',array("openid"=>$openid,"status"=>1),array('id'=>$union_member['id']));
        }
        if($union_member['activate']==0){
            show_json(0,  "您的账号正在审核中");
        }

        pdo_update('ewei_shop_union_members',array("is_default"=>0),array('mobile_phone'=>$member_info['mobile']));
        pdo_update('ewei_shop_union_members',array("is_default"=>1),array('id'=>$union_member['id']));
        show_json(0, "已切换默认公司");
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