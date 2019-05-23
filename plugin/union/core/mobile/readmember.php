<?php



if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Readmember_EweiShopV2Page extends UnionMobilePage
{
    function main(){
        global $_W;
        global $_GPC;

        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $level= !empty($_GPC['level']) ? intval($_GPC['level']) : 1;
        $readtype=intval($_GPC['readtype']);
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
        $order = !empty($args['order']) ? $args['order'] : 'rdm.createtime';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and rdm.uniacid = :uniacid and rdm.union_id=:union_id and rdm.type=:type';
        $params=$pa = array(':uniacid' => $uniacid,':union_id'=>$union_id,":type"=>$readtype);
        if($level==1){
            $sql="select IFNULL(unm.name,m.nickname) as realname,m.avatar from ".tablename("ewei_shop_union_members")
                ." as unm   LEFT OUTER  JOIN ".tablename("ewei_shop_union_readmembers")." as rdm ON rdm.openid =unm.openid and rdm.union_id=unm.union_id ".
                " LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid =rdm.openid and m.uniacid = rdm.uniacid "
                ."  where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

            $countsql="select count(*) from ".tablename('ewei_shop_union_readmembers')." as rdm  where 1 ".$condition;
            $total = pdo_fetchcolumn($countsql,$params);
            $list = pdo_fetchall($sql, $params);
        }else{
            $condition = ' and unm.uniacid = :uniacid and unm.union_id=:union_id and t1.i is null and unm.status=1 and unm.activate=1 ';

            $sql="select IFNULL(unm.name,m.nickname) as realname,m.avatar FROM ".tablename("ewei_shop_union_members")." as unm".
                " LEFT JOIN " .tablename("ewei_shop_member")." as m ON m.openid=unm.openid and unm.uniacid=m.uniacid"
            ." LEFT JOIN ( select openid as i,type from ".tablename("ewei_shop_union_readmembers").") as t1 ON unm.openid =t1.i and t1.type=".$readtype
                ."  where 1 {$condition}  LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

            $countsql="select count(*) FROM ".tablename("ewei_shop_union_members")." as unm".
                " LEFT JOIN " .tablename("ewei_shop_member")." as m ON m.openid=unm.openid"
                ." LEFT JOIN ( select openid as i,type from ".tablename("ewei_shop_union_readmembers").") as t1 ON unm.openid =t1.i and t1.type=".$readtype;
            unset($params[':type']);
            $total = pdo_fetchcolumn($countsql,$params);

            $list = pdo_fetchall($sql, $params);

        }


        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));

    }
    function members(){
        global $_W;
        global $_GPC;
        $readtype=intval($_GPC['readtype']);
        include $this->template("union/readmembers");
    }
}