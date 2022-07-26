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
        $cateid=intval($_GPC['id']);


        $_W['union']['title']="会员活动";
        $params=array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],"times"=>time());
        $condition="where mv.end_time>=:times and mv.a_start_time<=:times and mv.status=1 and mv.uniacid=:uniacid and mv.union_id=:union_id ";

        if($cateid!=0){
            $category_list=$this->get_allcategory($cateid);
            $condition .= " and mv.category_id in (".implode(",",$category_list).")";
        }

        $sql="select mv.* from ".tablename("ewei_shop_union_memberactivity")." as mv ".$condition;

        $list= pdo_fetchall($sql,$params);

        foreach ($list as &$row){
            $row['mystatus']=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_memberactivity_sgin")." where openid =:openid and activity_id =:activity_id",array(":openid"=>$_W['openid'],":activity_id"=>$row['id']));
            $condition = ' and ms.uniacid = :uniacid and ms.status=2 and activity_id=:activity_id';
            $countsql="select count(*) from ".tablename('ewei_shop_union_memberactivity_sgin')." as ms where 1 ".$condition;
            $total = pdo_fetchcolumn($countsql,array(':uniacid' => $_W['uniacid'],':activity_id'=>$row['id']));//已经报名的总人数
            $row['show']=true;
            if($row['membercount']<=$total && $row['membercount']!=0  ){
              $row['show']=false;
            }
        }
        unset($row);

        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$cateid);
        $category=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity_category")." where id=:id and uniacid =:uniacid and union_id=:union_id and enable=1",$params);

        if($category){
            $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1 and parent_id=:id  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");
            $_W['union']['title']=$category['catename'];
        }
        $indexid=intval($_GPC['indexid']);
        if($indexid>0){
            $uniontitle=pdo_fetchcolumn("select title from ".tablename("ewei_shop_union_menu")." where id=:indexid",array(":indexid"=>$indexid));
            $_W['union']['title']=$uniontitle;
        }
        include $this->template();
    }

    private function get_allcategory($cate_id){
        global $_W;
        static $categorylist=array();
        $categorylist[]=$cate_id;

        $params=array(":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],":parent_id"=>$cate_id);
        $category_id=pdo_fetchall("select id from ".tablename("ewei_shop_union_memberactivity_category")." where parent_id=:parent_id and uniacid =:uniacid and union_id=:union_id",$params);

        if(!empty($category_id)){
            foreach ($category_id as $c){
                $this->get_allcategory($c['id']);
            }
        }
        return $categorylist;
    }
    public function status(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);


        if(empty($id)){
            show_json(0,'数据错误');
        }
        $signmember= pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:activity_id and openid =:openid ",array(":openid"=>$_W['openid'],':activity_id'=>$id));

        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity")." where id=:id",array(":id"=>$id));
        $data=array(
            'openid'=>$_W['openid'],
            'create_time'=>time(),
            'activity_id'=>$id,
            'union_id'=>$_W['unionid'],
            'uniacid'=>$_W['uniacid'],
            'description'=>trim($_GPC['description']),
            'status'=>1,
        );
        if($view['autoverify']==1){
            $data['status']=2;
        }

        $condition = ' and ms.uniacid = :uniacid and activity_id=:activity_id';
        $countsql="select count(*) from ".tablename('ewei_shop_union_memberactivity_sgin')." as ms where 1 ".$condition;


        $total = pdo_fetchcolumn($countsql,array(':uniacid' => $_W['uniacid'],':activity_id'=>$id));//已经报名包含未审核的总人数

        if($view['membercount']<=$total && $view['membercount']!=0  ){
            show_json(0,'已到达报名人数上线');
        }


        if(empty($signmember)){

            pdo_insert("ewei_shop_union_memberactivity_sgin",$data);
            $indesertid=pdo_insertid();
            if($indesertid){
                show_json(1,array("massage"=>'ok','url'=>mobileUrl("union/memberactivity/view",array("id"=>$id))));
            }
        }
        if($signmember['status']==2){
            show_json(0,'报名成功请勿重复点击');
        }elseif($signmember['status']==1){
            show_json(0,'报名审核中，请等待管理员审核');
        }else{
            unset($data['create_time']);
            $ret=pdo_update("ewei_shop_union_memberactivity_sgin",$data,array("id"=>$signmember['id']));
            if($ret){
                show_json(1,array("url"=>mobileUrl("union/memberac tivity/view",array("id"=>$id))));
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
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity")." where id=:id and union_id=:union_id",array(":id"=>$id,":union_id"=>$_W['unionid']));
        if(empty($view)){
            $this->message("活动未开启或者非当前工会活动",mobileUrl("union/index"));
        }
        $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:activity_id and status=2",array(":activity_id"=>$view['id']));

        //查询我自己有没有报名

        $mycount=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity_sgin")." where activity_id=:activity_id  and openid=:openid ",array(":activity_id"=>$view['id'],":openid"=>$_W['openid']));

        $this->model->readmember_insert($_W['openid'],3,$view['id']);
        $readmember= $this->model->readcount(3,'',$view['id']);
        $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));
        $notreadcount=$allcount-$readmember['count'];
        $condition = ' and ms.uniacid = :uniacid and ms.status=2 and activity_id=:activity_id';
        $countsql="select count(*) from ".tablename('ewei_shop_union_memberactivity_sgin')." as ms where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,array(':uniacid' => $_W['uniacid'],':activity_id'=>$id));//已经报名的总人数

        $bottontext=true;
        if($view['membercount']<=$total && $view['membercount']!=0  ){
            $bottontext=false;
        }
        $_W['shopshare'] = array(
            'title' =>$view['title'],
            'imgUrl' => !empty($view['images'])? tomedia($view['images']) :tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $view['title'],
            'link' => mobileUrl('union/memberactivity/view',array('id'=>$view['id']),true)
        );

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

    public function click_status(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        $openid=$_W['openid'];
        if(empty($openid)){
            show_json(0,'非法访问');
        }
        if(empty($_W['unionid'])){
            show_json(0,'未绑定工会');
        }
        //查询活动
        $allchickcount=pdo_fetchcolumn("select likecount from ".tablename("ewei_shop_union_memberactivity")." where id=:id",array(":id"=>$id));

        //查询当前用户是否点击红心
        $chick_count=pdo_fetch("select chick_count from ".tablename("ewei_shop_union_memberactivity_count")." where  openid =:openid and union_id = :unionid and member_activity =:member_activity",array(':member_activity'=>$id,":openid"=>$openid,":unionid"=>$_W['unionid']));

        if($chick_count['chick_count']==1){
            pdo_update("ewei_shop_union_memberactivity_count",array("chick_count"=>0),array("openid"=>$openid,'union_id'=>$_W['unionid'],'member_activity'=>$id));

            pdo_update("ewei_shop_union_memberactivity",array("likecount"=>$allchickcount-1),array("id"=>$id));
            show_json(1);
        }
        if($chick_count['chick_count']==0 && !empty($chick_count)){
            pdo_update("ewei_shop_union_memberactivity",array("likecount"=>$allchickcount+1),array("id"=>$id));
            pdo_update("ewei_shop_union_memberactivity_count",array("chick_count"=>1),array("openid"=>$openid,'union_id'=>$_W['unionid'],'member_activity'=>$id));
            show_json(1);
        }
        $data=array(
            'uniacid'=>$_W['uniacid'],
            'union_id'=>$_W['unionid'],
            'chick_count'=>1,
            'openid'=>$openid,
            'member_activity'=>$id,
        );
        if(empty($chick_count)){

            pdo_update("ewei_shop_union_memberactivity",array("likecount"=>$allchickcount+1),array("id"=>$id));
            pdo_insert("ewei_shop_union_memberactivity_count",$data);
        }
        show_json(1);

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
        $condition = ' and ms.uniacid = :uniacid and ms.union_id=:union_id and ms.status=2 and activity_id=:id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":id"=>$id);
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