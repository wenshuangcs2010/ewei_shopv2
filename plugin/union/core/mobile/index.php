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
        $_W['union']['title']="云工会";
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

    public function get_suggestions_list(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $openid=$_W['openid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $order = !empty($args['order']) ? $args['order'] : ' create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid and union_id=:union_id and openid=:openid';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,':openid'=>$openid);
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
            $sql="select * from ".tablename("ewei_shop_union_suggestions")." where id=:id and uniacid=:uniacid and union_id=:unionid";
            $params=array(":id"=>$id,":uniacid"=>$_W['uniacid'],':unionid'=>$_W['unionid']);
            $post=pdo_fetch($sql,$params);
        }
        include $this->template("union/suggestions_viem");
    }

}
?>