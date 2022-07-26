<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Quiz_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $activity_info=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_activity")." where id=:id",array(":id"=>intval($_GPC['id'])));

        $condition="  where uniacid=:uniacid and union_id=:union_id and activity_id=:activity_id ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":activity_id"=>$activity_info['id']);
        $sql="select * from ".tablename("ewei_shop_union_vote_quiz").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        foreach ($list as $key=>$quizinfo){
            $list[$key]['optioncount']=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_vote_option")." where quiz_id=:quiz_id",array(":quiz_id"=>$quizinfo['id']));
        }
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_vote_quiz").$condition,$paras);
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
        $activity_id=intval($_GPC['activity_id']);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id=:id",array(":id"=>$id));
            if($vo['peoplevale']){
                $peoplevale=pdo_fetchall("select name from ".tablename("ewei_shop_union_members")." where id in (".$vo['peoplevale'].")");
            }
        }
        if($_W['ispost']){
            $data=array(
                'title'=>trim($_GPC['title']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'image'=>trim($_GPC['images']),
                'displayorder'=>intval($_GPC['displayorder']),
                'type'=>intval($_GPC['type']),
                'votecount'=>intval($_GPC['votecount']),
                'peoplevale'=>$_GPC['peoplevale'],
                'createtime'=>time(),
                'activity_id'=>$activity_id,
                'declaration'=>htmlspecialchars_decode(trim($_GPC['declaration'])),
            );

            if($id){
                unset($data['createtime']);
                pdo_update("ewei_shop_union_vote_quiz",$data,array('id'=>$id));
            }else{
                pdo_insert("ewei_shop_union_vote_quiz",$data);



            }
            $this->model->show_json(1,"ok");
        }


        include $this->template();
    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $status=pdo_delete("ewei_shop_union_vote_quiz",array("id"=>$id,"union_id"=>$_W['unionid'],'uniacid'=>$_W['uniacid']));
        if($status){
            $this->model->show_json(1,"删除成功");
        }else{
            $this->model->show_json(0,'删除失败');
        }
    }

    function createqrcode(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        if(empty($id)){
            $this->model->show_json(1,"参数错误");
        }
        $url=mobileUrl("union/vote/quiz",array("id"=>$id,'union_id'=>$_W['unionid']),true);
        $files=m("qrcode")->createQrcode($url);
        header('location: ' . $files);
    }
}