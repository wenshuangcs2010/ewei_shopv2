<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Dynamic_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="工会动态";
        include $this->template();
    }

    public function get_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];

        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' createtime';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid and union_id=:union_id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $sql="select * from ".tablename("ewei_shop_union_dynamic")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_dynamic')." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['datetime']=date("Y-m-d");
            $status=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_dynamic_likelog")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and dynameid=:dynameid",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid'],":openid"=>$_W['openid'],':dynameid'=>$row['id']));

            $row['status']=empty($status) ? 0 :$status;
        }

        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));

    }

    public function view(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="详情";
        $id=intval($_GPC['id']);
        $params=array(':id'=>$id,':uniacid'=>$_W['uniacid'],':union_id'=>$_W['unionid']);
        $article=pdo_fetch("select * from ".tablename("ewei_shop_union_dynamic")." where id=:id and uniacid=:uniacid and union_id=:union_id",$params);

        if($article){
            pdo_update("ewei_shop_union_dynamic",array("showcount"=>$article['showcount']+1),array("id"=>$article['id']));
        }

        $this->model->readmember_insert($_W['openid'],3,$article['id']);
        $readmember= $this->model->readcount(3,'',$article['id']);
        $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        $notreadcount=$allcount-$readmember['count'];
        include $this->template();
    }

    public function status(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $params=array(':id'=>$id,':uniacid'=>$_W['uniacid'],':union_id'=>$_W['unionid']);
        $dynameic_info=pdo_fetch("select * from ".tablename("ewei_shop_union_document")." where id=:id and uniacid=:uniacid and union_id=:union_id",$params);
        if($dynameic_info){
            $param=array(':openid'=>$_W['openid'],':uniacid'=>$_W['uniacid'],':union_id'=>$_W['unionid'],":dynameid"=>$id);
            $member_log=pdo_fetch("select id,status from ".tablename("ewei_shop_union_dynamic_likelog")." where  openid =:openid and uniacid=:uniacid and union_id =:union_id and dynameid=:dynameid",$param);

            if(empty($member_log)){
                $data=array(
                    'status'=>1,
                    'create_time'=>time(),
                    'dynameid'=>$id,
                    'union_id'=>$_W['unionid'],
                    'uniacid'=>$_W['uniacid'],
                    'openid'=>$_W['openid'],
                );
                pdo_insert("ewei_shop_union_dynamic_likelog",$data);
                pdo_update("ewei_shop_union_document",array("like_count"=>$member_log['like_count']+1),array("id"=>$dynameic_info['id']));
            }
            if(!empty($member_log) && $member_log['status']==1){
                if($member_log['like_count']<=0){
                    $like_count=0;
                }else{
                    $like_count=$member_log['like_count']-1;
                }
                pdo_update('ewei_shop_union_dynamic_likelog',array("status"=>0),array("id"=>$member_log['id']));
                pdo_update("ewei_shop_union_document",array("like_count"=>$like_count),array("id"=>$dynameic_info['id']));
            }
            if(!empty($member_log) && $member_log['status']==0){

                pdo_update("ewei_shop_union_document",array("like_count"=>$member_log['like_count']+1),array("id"=>$dynameic_info['id']));
                pdo_update('ewei_shop_union_dynamic_likelog',array("status"=>1),array("id"=>$member_log['id']));
            }

            show_json(1);
        }
        show_json(0,"error");

    }

}