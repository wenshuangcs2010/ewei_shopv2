<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Venue_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="场馆预订";
        $cateid=intval($_GPC['id']);
        //查询当前分类下还有没有下级分类
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$cateid);
        $category=pdo_fetch("select * from ".tablename("ewei_shop_union_venue_category")." where id=:id and uniacid =:uniacid and union_id=:union_id and enable=1",$params);

        if($category){
            $_W['union']['title']=$category['catename'];
            $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_venue_category') . ' WHERE 1 and parent_id=:id  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");
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
        $category_id=pdo_fetchall("select id from ".tablename("ewei_shop_union_venue_category")." where parent_id=:parent_id and uniacid =:uniacid and union_id=:union_id",$params);

        if(!empty($category_id)){
            foreach ($category_id as $c){
                $this->get_allcategory($c['id']);
            }
        }
        return $categorylist;
    }
    public function get_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];

        $cateid=!empty($_GPC['cateid']) ? intval($_GPC['cateid']) : '';

        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid and union_id=:union_id and is_delete=0';

        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        if($cateid!=''){


            $category_list=$this->get_allcategory($cateid);
            $condition .= " and category_id in (".implode(",",$category_list).")";
        }
        $sql="select * from ".tablename("ewei_shop_union_venue")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_venue')." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
            $row['datetime']=date("Y-m-d");
            $row['datetime']=date("Y-m-d");
            $row['count']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_venue_bookedlist")." where venue_id=:id and status=1 and end_time>".time()." and  is_delete=0 ",array(':id'=>$row['id']));
            $row['status']=$this->model->check_venue_status($row['id']);
            $row['images']=tomedia($row['images']);
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="详情";
        $params=array(':id'=>$id,':uniacid'=>$_W['uniacid'],':union_id'=>$_W['unionid']);
        $article=pdo_fetch("select * from ".tablename("ewei_shop_union_dynamic")." where id=:id and uniacid=:uniacid and union_id=:union_id",$params);
        $_W['union']['title']=$article['title'];
        if($article){
            pdo_update("ewei_shop_union_dynamic",array("showcount"=>$article['showcount']+1),array("id"=>$article['id']));
        }
        include $this->template();
    }

    public function get_starttime(){
        $startarray=array();
    }
    public function add(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="预订";
        if($_W['ispost']){
            $data=trim($_GPC['date']);
            $starttime=strtotime($data." ".trim($_GPC['starttime']));
            $endtimetime=strtotime($data." ".trim($_GPC['endtime']));
            $ret=$this->model->bookvenue($_W['openid'],$starttime,$endtimetime,$id);
            if(is_error($ret)){
                show_json(0,$ret['message']);
            }
            show_json(1);
        }
        $venue=pdo_fetch("select * from ".tablename("ewei_shop_union_venue")." where id=:id",array(":id"=>$id));
        include $this->template();
    }
    public function mylist(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="我的预订";
        $list=$this->model->get_venue_bookedlist(array("openid"=>$_W['openid']));

        include $this->template();
    }

    public function list_venue(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="预订";
        $id=$_GPC['id'];
        include $this->template();
    }


    public function mylist_list(){
        global $_W;
        global $_GPC;
        $venue_id=$_GPC['venue_id'];
        $vunue_time=$_GPC['vunue_time'];
        $notop=$_GPC['notop'];
        $args=array('venue_id'=>$venue_id,'vunue_time'=>$vunue_time);
        if(empty($notop)){
            $args["openid"]=$_W['openid'];
        }

        $list=$this->model->get_venue_bookedlist($args);
        show_json(1,$list);
    }
    public function cancel(){
        global $_W;

        global $_GPC;
        $id=intval($_GPC['id']);
        $ret=pdo_update("ewei_shop_union_venue_bookedlist",array("status"=>0),array("id"=>$id));
        if($ret){
            show_json(1);
        }
        show_json(0,'取消失败');
    }

}