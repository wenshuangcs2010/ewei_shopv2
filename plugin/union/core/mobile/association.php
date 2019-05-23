<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Association_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="文体协会";
        include $this->template();
    }

    public function get_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];

        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' aa.create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and aa.uniacid = :uniacid and aa.union_id=:union_id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);

        $sql="select aa.*,a.title as asstitle from ".tablename("ewei_shop_union_association_activity")." as aa LEFT JOIN ".tablename("ewei_shop_union_association")." as a  ON a.id =aa.ass_id "
            ."  where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_association_activity')." as aa where 1 ".$condition;

        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['datetime']=date("Y-m-d",$row['create_time']);
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
      }

    public function view(){
        global $_W;
        global $_GPC;

        $_W['union']['title']="协会详情";
        $id=intval($_GPC['id']);
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $condition = ' and aa.uniacid = :uniacid and aa.union_id=:union_id and aa.id=:id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":id"=>$id);

        $sql="select aa.*,a.title as asstitle from ".tablename("ewei_shop_union_association_activity")." as aa LEFT JOIN ".tablename("ewei_shop_union_association")." as a  ON a.id =aa.ass_id "
            ."  where 1 {$condition}";
        $article=pdo_fetch($sql,$params);


        pdo_update("ewei_shop_union_association_activity",array("readcount"=>$article['readcount']+1),array("id"=>$article['id']));
        include $this->template();
    }
    public function myass(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="我的协会";
        include $this->template();
    }

    public function myasso_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and uniacid = :uniacid and union_id=:union_id';
        $params=$pa = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $sql="select * from ".tablename("ewei_shop_union_association")
         ."  where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_association')."  where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        $params[':openid']=$_W['openid'];
        foreach ($list as &$row){
            $pa[':association_id']=$row['id'];
            $params[':association_id']=$row['id'];
            $status=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_association_member")." where association_id=:association_id and  openid =:openid and uniacid=:uniacid and union_id=:union_id",$params);
            $row['status']=is_numeric($status) ? $status : -1;
          $row['count']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where association_id=:association_id and status=1 and   uniacid=:uniacid and union_id=:union_id",$pa);
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function assview(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="详情";
        $param=array(":id"=>$id,":union_id"=>$_W['unionid']);
        $sql="select * from ".tablename("ewei_shop_union_association")." where id=:id and union_id=:union_id";
        $vo=pdo_fetch($sql,$param);

        include $this->template();
    }
    public function viewlist(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' am.add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and am.uniacid = :uniacid and am.union_id=:union_id and association_id=:id and am.status=1';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":id"=>$id);
        $sql="select am.id,m.avatar,ud.`name` as udname,am.add_time,um.`name` from ".tablename("ewei_shop_union_association_member")
            ." as am LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid = am.openid and m.uniacid=am.uniacid "
            ." LEFT JOIN ".tablename("ewei_shop_union_members")." as um ON um.openid =am.openid and um.union_id=am.union_id and um.uniacid=am.uniacid "
        ." LEFT JOIN ".tablename("ewei_shop_union_department")." as ud ON ud.id=um.department "
            ."  where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_association_member')." as am where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        $union_info=$this->model->get_union_info($union_id);
        foreach ($list as &$li){
            $li['add_time']=date("Y-m-d");
            $li['dname']=$union_info['title'];
        }
        unset($li);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function join_add(){
        global $_W;
        global $_GPC;


        if($_W['ispost']){
            $id=intval($_GPC['id']);
            $status=intval($_GPC['status']);
            if(empty($id)){
                show_json(0, "数据异常");
            }
            if($status!=-1){
                show_json(0, "数据异常");
            }
            $membercount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_association_member")." where association_id=:id and openid=:openid",array(":id"=>$id,':openid'=>$_W['openid']));
            if($membercount){

                show_json(0, "等待审核中");

            }

            $data=array(
                'association_id'=>$id,
                'member_id'=>$this->member['id'],
                'add_time'=>time(),
                'openid'=>$_W['openid'],
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
            );
            pdo_insert("ewei_shop_union_association_member",$data);
            show_json(1,array('id'=>pdo_insertid(),'msg'=>"ok"));
        }

    }
}