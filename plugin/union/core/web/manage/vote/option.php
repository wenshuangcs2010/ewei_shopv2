<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Option_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $quiz_info=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id=:id ",array(":id"=>$id));

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where uniacid=:uniacid and union_id=:union_id  and quiz_id=:quiz_id ";


        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":quiz_id"=>$quiz_info['id']);
        if($_GPC['keywordes']!=''){
            $condition.=" and title like :title";
            $paras[':title']="%".trim($_GPC['keywordes'])."%";
        }
        $sql="select * from ".tablename("ewei_shop_union_vote_option").$condition."  order by createtime desc ";
        if($_GPC['export']!=1){
            $sql.=" LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }
        $list = pdo_fetchall($sql, $paras);
        if($_GPC['export']==1){
//            if($quiz_info['peoplevale']){
//                $allcount=count(explode(",",$quiz_info['peoplevale']));
//            }else{
//                $allcount=pdo_fetchcolumn("select count(*) from  ".tablename("ewei_shio_uniuon_members")." where status=1 and activate=1 and union_id=:union_id and uniacid=:uniacid",array(":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid']));
//            }
//            foreach ($list as $key=> $p){
//                $list[$key]['allcount']=$allcount;
//            }

            $params_export=array(
                "title" => "投票数据数据",
                "columns" => array(
                    array('title' => '选项', 'field' => 'title', 'width' => 12),
                    array('title' => '选票数量', 'field' => 'ticketcount', 'width' => 12),
                    array('title' => '点击数量', 'field' => 'clikcount', 'width' => 12),
//                    array('title' => '全票数量', 'field' => 'allcount', 'width' => 12),
//                    array('title' => '弃票数据', 'field' => 'lastcount', 'width' => 12),
                )
            );
            m('excel')->export($list,$params_export);
            exit;
        }



        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_vote_option").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }

    function add(){
        $this->post();
    }
    function edit(){
        $this->post();
    }
    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $quiz_id=intval($_GPC['quiz_id']);
        $quiz_info=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id=:quiz_id ",array(":quiz_id"=>$quiz_id));

        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_option")." where id=:id",array(":id"=>$id));

        }
        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'quiz_id'=>$quiz_info['id'],
                'image'=>trim($_GPC['images']),
                'displayorder'=>intval($_GPC['displayorder']),
                'createtime'=>time(),
                'declaration'=>htmlspecialchars_decode(trim($_GPC['declaration'])),
            );

            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_vote_option",$data,array('id'=>$id));
            }else{
                pdo_insert("ewei_shop_union_vote_option",$data);
            }
            $this->model->show_json(1,"ok");
        }


        include $this->template();
    }
    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $status=pdo_delete("ewei_shop_union_vote_option",array("id"=>$id,"union_id"=>$_W['unionid'],'uniacid'=>$_W['uniacid']));
        if($status){
            $this->model->show_json(1,"删除成功");
        }else{
            $this->model->show_json(0,'删除失败');
        }
    }
}