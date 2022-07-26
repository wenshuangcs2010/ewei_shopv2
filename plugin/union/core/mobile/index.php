<?php
if (!(defined('IN_IA')))
{
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Index_EweiShopV2Page extends UnionMobilePage
{
	private $operatorid = 0;

	public function main()
	{
		global $_W;
		global $_GPC;


        $_W['union']['title']= $_W['union_info']['title'];
        //获取一条公告通知
        $sql=  "select id,link,title,endtime from ".tablename("ewei_shop_union_document")." where uniacid=:uniacid and union_id=:union_id and ishot=1 and (endtime>:times and endtime<>0 ) and isdelete=0 order by add_time desc ";
        $advmessage=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],":times"=>time(),':union_id'=>$_W['unionid']));

        foreach ($advmessage as &$row){
            if(empty($row['link'])){
                $row['link']=mobileUrl('union/document/view',array('id'=>$row['id']));
            }
        }
        unset($row);
        //获取首页幻灯片

        $sql="select * from ".tablename("ewei_shop_union_adv")." where enabled=1 and union_id=:union_id and is_pc=0 and uniacid = :uniacid order by displayorder desc";

        $advs=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        //获取一条最新的一条工


        $params=array(':uniacid'=>$_W['uniacid'],':union_id'=>$_W['unionid']);

        $info=pdo_fetch("select * from ".tablename("ewei_shop_union_index")." where uniacid=:uniacid and union_id=:union_id and type=1",$params);
        if(!empty($info) && $info['status']==1){
            $params[':cate_id']=$info['cate_id'];
            $member=$this->model->_get_union_member($_W['openid']);
            $member_id=$member['id'];
            $condition=" where uniacid=:uniacid and union_id=:union_id and category_id=:cate_id";
            $condition.= " and uniacid = :uniacid AND  isdelete = 0 AND (peopletype=0 or (peopletype=1 and find_in_set({$member_id},peoplevale)))";
            $dynamiclist=pdo_fetchall("select id,title,header_image,read_count as showcount,like_count from ".tablename("ewei_shop_union_document")
                .$condition."  order by add_time desc limit 2",$params);
            foreach ($dynamiclist as &$synamic){
                $status=pdo_fetchcolumn("select status from ".tablename("ewei_shop_union_dynamic_likelog")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and dynameid=:dynameid",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid'],":openid"=>$_W['openid'],':dynameid'=>$synamic['id']));

                $synamic['status']=empty($status) ? 0 :$status;
            }
            unset($synamic);
        }

        //工会活动
        unset($params[':cate_id']);
        $info=pdo_fetch("select * from ".tablename("ewei_shop_union_index")." where uniacid=:uniacid and union_id=:union_id and type=2",$params);

        if(!empty($info) && $info['status']==1){
            $params=array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid'],":times"=>time(),":category_id"=>$info['cate_id']);
            $sql="select mv.* from ".tablename("ewei_shop_union_memberactivity")." as mv ".
                "where mv.end_time>:times and mv.status=1 and mv.uniacid=:uniacid and mv.category_id=:category_id and mv.union_id=:union_id order by a_end_time desc limit 0,2";
            $memberactivity_list= pdo_fetchall($sql,$params);
            foreach ($memberactivity_list as &$value){
                $chick_count=pdo_fetchcolumn("select chick_count from ".tablename("ewei_shop_union_memberactivity_count")." where openid=:openid and uniacid =:uniacid and union_id=:union_id and member_activity=:member_activity",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid'],":openid"=>$_W['openid'],':member_activity'=>$value['id']));
                $value['chick_count']=empty($chick_count) ? 0 :$chick_count;
            }
            unset($value);
        }


        //首页菜单
        $menu=pdo_fetchall("select * from ".tablename("ewei_shop_union_menu")." where uniacid=:uniacid and union_id=:unionid and status=1",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid']));

        $volume=array();
        foreach ($menu as $key=> &$item){

            $volume[$key]  = $item['displayorder'];
            if((!strexists($item['link_url'], 'http://') && !strexists($item['link_url'], 'https://'))) {
                $item['link_url'] = $item['link_url']."&indexid=".$item['id'];
            }
        }


        unset($item);
        array_multisort(array_column($menu,"displayorder"),SORT_DESC, $menu);
        include $this->template();
	}

	public function get_welfare_list(){
        global $_W;
        $union_id=$this->member['union_id'];
        //$union_id=3;
        if(empty($union_id)){
            show_json(0,'没有绑定用户');
        }
	    $parmconfig=$this->model->get_config($union_id);

        $row=iunserializer($parmconfig['config']);
        $list=array();
            if($row['marry']==0){
                $list['marry']=array(
                    'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/ic_marry.png",
                    'text'=>'结婚',
                    'url'=>mobileUrl('union/welfare',array('type'=>1))
                );
            }
            if($row['birth']==0){
                $list['birth']=array(
                    'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/ic_rear.png",
                    'text'=>'生育',
                    'url'=>mobileUrl('union/welfare',array('type'=>2))
                );
            }
            if($row['hospitalization']==0){
                $list['hospitalization']=array(
                    'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/ic_hospital.png",
                    'text'=>'住院',
                    'url'=>mobileUrl('union/welfare',array('type'=>3))
                );
            }
            if($row['retire']==0){
                $list['retire']=array(
                    'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/ic_retire.png",
                    'text'=>'退休',
                    'url'=>mobileUrl('union/welfare',array('type'=>4))
                );
            }
            if($row['funeral']==0){
                $list['funeral']=array(
                    'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/ic_burial.png",
                    'text'=>'丧葬',
                    'url'=>mobileUrl('union/welfare',array('type'=>5))
                );
            }

        show_json(1, array('list' => $list,'total'=>count($list)));
    }

    public function get_index_recommend(){
        global $_W;
        global $_GPC;
        $group_id=32;
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
        $goods=array();
        $grouplist=pdo_fetch("select * from ".tablename("ewei_shop_goods_group")." where uniacid=:uniacid and id=:id",array(":id"=>$group_id,":uniacid"=>$_W['uniacid']));
        if($grouplist){
            $args['ids']=$grouplist['goodsids'];
            $args['page']=$page;
            $args['pagesize']=$pagesize;
            $goods=m('goods')->getList($args);
        }
        show_json(1, array('list' => $goods['list'], 'total' => $goods['total'], 'pagesize' => $pagesize));
	}

	public function suggestions(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="建言献策";
        include $this->template("union/suggestions");
    }
    public function mysuggestions(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="我的建言";
        include $this->template("union/suggestions_my");
    }
    public function get_suggestions_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $openid=$_W['openid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' create_time';
        $type=$_GPC['type'];
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid and union_id=:union_id and is_delete=0 ';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        $memberid=$this->member['id'];
        if($type=="my"){
            $condition.=" and openid =:openid ";
            $params[':openid']=$openid;
        }else{
            $condition.="  and status=2 and (FIND_IN_SET({$memberid},memberlist) or memberlist is null) ";
        }
        $sql="select * from ".tablename("ewei_shop_union_suggestions")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_suggestions')." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }
    public function suggestions_add(){
	    $this->get_suggestions_post();
    }
    public function get_suggestions_post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="我的发言";
        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'description'=>trim($_GPC['description']),
                'status'=>1,
                'create_time'=>time(),
                'openid'=>$_W['openid'],
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
            );
            if($id){
                show_json(0,"已提交的建言禁止修改");
            }
            pdo_insert("ewei_shop_union_suggestions",$data);
            show_json(1,array('url'=>mobileUrl('union/index/suggestions')));
        }
        if($id){
            $_W['union']['title']="建言详情";
            $sql="select * from ".tablename("ewei_shop_union_suggestions")." where id=:id and uniacid=:uniacid and union_id=:unionid and is_delete=0";
            $params=array(":id"=>$id,":uniacid"=>$_W['uniacid'],':unionid'=>$_W['unionid']);
            $post=pdo_fetch($sql,$params);
            if(empty($post)){
                $this->message("访问数据不存在");
            }
            $this->model->readmember_insert($_W['openid'],2,$id);
            $readmember= $this->model->readcount(2,'',$id);
            $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));
            $notreadcount=$allcount-$readmember['count'];
        }
        include $this->template("union/suggestions_viem");
    }
    function deletesuggestiions(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if($id){
           $status=pdo_update("ewei_shop_union_suggestions",array("is_delete"=>1),array("id"=>$id));
        }
        show_json(1,"删除成功");
    }

}
?>