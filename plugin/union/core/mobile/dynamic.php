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

        $this->model->readmember_insert($_W['openid'],3);
        $readmember= $this->model->readcount(3);
        $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        $notreadcount=$allcount-$readmember['count'];
        include $this->template();
    }

}