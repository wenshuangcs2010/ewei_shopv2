<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Memberactivity_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;

        $_W['union']['title']="会员活动";
        $params=array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],"times"=>time());
        $sql="select mv.* from ".tablename("ewei_shop_union_memberactivity")." as mv ".

            "where mv.end_time>:times and mv.status=1 and mv.uniacid=:uniacid and mv.union_id=:union_id";
        $list= pdo_fetchall($sql,$params);

        foreach ($list as &$row){
            $row['mystatus']=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_memberactivity_sgin")." where openid =:openid and activity_id =:activity_id",array(":openid"=>$_W['openid'],":activity_id"=>$row['id']));
        }
        unset($row);

        include $this->template();
    }
    public function status(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(empty($id)){
            show_json(0,'数据错误');
        }
        $signmember= pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:activity_id and openid =:openid ",array(":openid"=>$_W['openid'],':activity_id'=>$id));
        $data=array(
            'openid'=>$_W['openid'],
            'create_time'=>time(),
            'activity_id'=>$id,
            'union_id'=>$_W['unionid'],
            'uniacid'=>$_W['uniacid'],
            'description'=>trim($_GPC['description']),
            'status'=>2,
        );
        if(empty($signmember)){

            pdo_insert("ewei_shop_union_memberactivity_sgin",$data);
            $indesertid=pdo_insertid();
            if($indesertid){
                show_json(1,'ok');
            }
        }
        if($signmember['status']==2){
            show_json(0,'报名成功请勿重复点击');
        }else{
            unset($data['create_time']);
            $ret=pdo_update("ewei_shop_union_memberactivity_sgin",$data,array("id"=>$signmember['id']));
            if($ret){
                show_json(1,'ok');
            }
        }
        show_json(0,'报名失败请重试');
    }

    public function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="活动详情";
        if(empty($id)){
            $this->message("数据错误");
        }
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity")." where id=:id",array(":id"=>$id));
        if(empty($view)){
            $this->message("数据错误");
        }
        $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:activity_id and status=2",array(":activity_id"=>$view['id']));

        //查询我自己有没有报名

        $mycount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:activity_id and status=2 and openid=:openid ",array(":activity_id"=>$view['id'],":openid"=>$_W['openid']));


        include $this->template();
    }

    public function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="活动报名";
        $member=m("member")->getMember($_W['openid']);

        include $this->template();
    }

    public function showpeoper(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="报名名单";
        include $this->template();
    }

    public function memberlist(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];

        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' ms.create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and ms.uniacid = :uniacid and ms.union_id=:union_id and ms.status=2 ';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $sql="select ms.*,m.name,mem.avatar from ".tablename("ewei_shop_union_memberactivity_sgin")." as ms ".
            " LEFT JOIN ".tablename("ewei_shop_union_members")." as m ON m.openid=ms.openid and m.union_id=:union_id ".
            " LEFT JOIN ".tablename("ewei_shop_member")." as mem ON mem.openid=m.openid and mem.uniacid=:uniacid "
            . " where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_memberactivity_sgin')." as ms where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['datetime']=date("Y-m-d H:i:s",$row['create_time']);
            $row['description']=empty($row['description']) ? "无":$row['description'];
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }
}