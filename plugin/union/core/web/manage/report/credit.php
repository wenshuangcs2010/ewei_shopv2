<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
//积分管理
class Credit_EweiShopV2Page extends UnionWebPage
{
    public function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $paras=array(
            ':union_id'=>$_W['unionid']
        );

        $where='';
        $startTime=$_GPC['starttime'];
        $endTime=$_GPC['endtime'];
        if(!empty($startTime)){
            $startTime.=" 00:00:00";
            $startTime=strtotime($startTime);
        }
        if(!empty($endTime)){
            $endTime.=" 23:59:59";
            $endTime=strtotime($endTime);
        }

        if(!empty($startTime)){
            $paras[':starttime']=$startTime;
            $where.=" and c.createtime>=:starttime";
        }
        if(!empty($endTime)){
            $paras[':endtime']=$endTime;
            $where.=" and c.createtime<=:endtime";
        }
        if($_GPC['export']){
            $sql="select sum(c.credit) as credit,count(*) as count,c.openid from ".tablename("ewei_shop_union_report_credit")." as c"
            ." where c.union_id=:union_id ".$where." group by c.openid";
               $list= pdo_fetchall($sql,$paras);

           foreach ($list as &$item){

               $member=pdo_fetch("select name,mobile_phone from ".tablename("ewei_shop_union_members")."where openid=:openid and union_id=:union_id",array(":openid"=>$item['openid'],':union_id'=>$_W['unionid']));
               $item['name']=empty($member) ? "非本公会":$member['name'];
               $item['mobile_phone']=empty($member) ? "000":$member['mobile_phone'];
           }
           unset($item);
            $columns['title']= "积分统计";
            $columns['columns']=array(
                array('title' => '姓名', 'field' => 'name', 'width' => 32),
                array('title' => '手机号', 'field' => 'mobile_phone', 'width' => 32),
                array('title' => '签到次数', 'field' => 'count', 'width' => 32),
                array('title' => '积分总数', 'field' => 'credit', 'width' => 24),
            );
            m('excel')->export($list,$columns);
            exit();
        }

        $sql=" select c.*,dep.name as depname,m.name as username,m.mobile_phone,r.title as activity_name from ".tablename("ewei_shop_union_report_credit")." as c".
            " LEFT JOIN ".tablename("ewei_shop_union_report")." as r ON r.id=c.report_id".
            " LEFT JOIN ".tablename("ewei_shop_union_members")." as m ON m.openid=c.openid and m.union_id=:union_id"
            ." LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON dep.id=m.department  "
            ." where c.union_id=:union_id".$where." order by c.createtime desc";
        $sql.=" LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $totalsql= " select count(*) from ".tablename("ewei_shop_union_report_credit")
            ." as c LEFT JOIN ".tablename("ewei_shop_union_report")." as r ON r.id=c.report_id where r.union_id=:union_id ".$where." order by c.createtime desc";
        $total = pdo_fetchcolumn($totalsql,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
}