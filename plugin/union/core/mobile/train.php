<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Train_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;

        $_W['union']['title']="培训活动";

        $parentlist=$this->model->_superior_unionlist($_W['unionid']);
        $condition=" where mv.end_time>:times and mv.status=1 and mv.uniacid=:uniacid  and mv.deleted=0 ";
        if(!empty($parentlist) && count($parentlist)>1){
            $condition.=' and  ((mv.union_id in ('.implode(",",$parentlist).' ) and mv.show_type=1) or mv.union_id=:union_id) ';
        }else if(!empty($parentlist) && count($parentlist)==1){
            $condition.=' and  ((mv.union_id ='.$parentlist[0].' and mv.show_type=1) or mv.union_id=:union_id) ';
        }else{
            $condition.="and mv.union_id=:union_id";
        }
        $union_user=array();
        foreach ($parentlist as $info){
            $item=$this->model->get_union_info($info);
            $union_user[$info]=$item['title'];
        }
        $params=array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],"times"=>time());
        $sql="select mv.* from ".tablename("ewei_shop_union_train")." as mv ".$condition." order by mv.create_time desc";
        $list= pdo_fetchall($sql,$params);

        foreach ($list as &$value){
            if($value['union_id']!=$_W['unionid']){
                $value['user_name']=$union_user[$value['union_id']];
            }
            $value['mystatus']=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_train_log")." where openid =:openid and train_id =:activity_id",array(":openid"=>$_W['openid'],":activity_id"=>$value['id']));
        }
        unset($value);
        include $this->template();
    }



    public function status(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(empty($id)){
            show_json(0,'数据错误');
        }
        $returnurl=mobileUrl("union/train/view",array("id"=>$id));

        $condition = ' and ms.uniacid = :uniacid and ms.status=2 ';
        $countsql="select count(*) from ".tablename('ewei_shop_union_train_log')." as ms where 1 ".$condition;
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_train")." where id=:id",array(":id"=>$id));

        $total = pdo_fetchcolumn($countsql,array(':uniacid' => $_W['unionid']));//已经报名的总人数

        if($view['count']<=$total){
            show_json(0,'已到达总报名人数');
        }

        $signmember= pdo_fetch("select * from ".tablename("ewei_shop_union_train_log")." where train_id=:activity_id and openid =:openid ",array(":openid"=>$_W['openid'],':activity_id'=>$id));
        $data=array(
            'openid'=>$_W['openid'],
            'create_time'=>time(),
            'train_id'=>$id,
            'union_id'=>$_W['unionid'],
            'uniacid'=>$_W['uniacid'],
            'description'=>trim($_GPC['description']),
            'status'=>2,
        );
        if(empty($signmember)){

            pdo_insert("ewei_shop_union_train_log",$data);
            $indesertid=pdo_insertid();
            if($indesertid){
                show_json(1,array("massage"=>'ok','url'=>$returnurl));
            }
        }

        if($signmember['status']==2){
            show_json(0,'报名成功请勿重复点击');
        }else{
            unset($data['create_time']);
            $ret=pdo_update("ewei_shop_union_train_log",$data,array("id"=>$signmember['id']));
            if($ret){

                show_json(1,array("massage"=>'ok','url'=>$returnurl));
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
        $parentlist=$this->model->_superion_union_info_list($_W['unionid']);
        $condition=" where id=:id ";
        $pars=array(":id"=>$id);
        if(!empty($parentlist)){
            $condition.=' and  ((union_id ='.$parentlist[0].' and show_type=1 ) or union_id=:union_id) ';
            $pars[':union_id']=$_W['unionid'];
        }
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_train").$condition,$pars);

        if(empty($view)){
            $this->message("数据错误");
        }
        $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_train_log")." where train_id=:activity_id and status=2",array(":activity_id"=>$view['id']));

        //查询我自己有没有报名

        $mycount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_train_log")." where train_id=:activity_id and status=2 and openid=:openid ",array(":activity_id"=>$view['id'],":openid"=>$_W['openid']));


        include $this->template();
    }

    public function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="活动报名";
        $member=m("member")->getMember($_W['openid']);
        $status=$this->model->checkunion();
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
        $condition = ' and ms.uniacid =:uniacid and ms.status=2 and ms.union_id=:union_id and ms.train_id=:train_id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":train_id"=>$id);

        $sql="select ms.*,m.name,mem.avatar from ".tablename("ewei_shop_union_train_log")." as ms ".
            " LEFT JOIN ".tablename("ewei_shop_union_members")." as m ON m.openid=ms.openid and m.union_id=:union_id ".
            " LEFT JOIN ".tablename("ewei_shop_member")." as mem ON mem.openid=m.openid and mem.uniacid=:uniacid "
            . " where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_train_log')." as ms where 1 ".$condition;


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