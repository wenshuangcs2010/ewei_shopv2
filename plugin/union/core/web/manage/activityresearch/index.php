<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where  union_id=:union_id  and is_delete=0 ";
        $paras=array(":union_id"=>$_W['unionid']);
        $sql="select * from ".tablename("ewei_shop_union_activityresearch").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_activityresearch").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }

    function url(){
        global $_GPC;
        $url=mobileUrl('union.research',array("id"=>$_GPC['id']),true);
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
        $data=pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch")." where id=:id",array(":id"=>$id));
        if($data){
            $data['start_time']=date("Y-m-d H:i:s",$data['start_time']);
            $data['end_time']=date("Y-m-d H:i:s",$data['end_time']);
        }

        if($_W['ispost']){
           $post=array(
               'title'=>trim($_GPC['title']),
               'union_id'=>$_W['unionid'],
               'create_time'=>time(),
               'declaration'=>htmlspecialchars_decode($_GPC['declaration']),
               'start_time'=>strtotime(trim($_GPC['start'])),
               'end_time'=>strtotime(trim($_GPC['end'])),
               'status'=>intval($_GPC['status']),
           );

            if($id){
                unset($post['create_time']);
                pdo_update("ewei_shop_union_activityresearch",$post,array('id'=>$id,'union_id'=>$_W['unionid']));
            }else{
                pdo_insert("ewei_shop_union_activityresearch",$post);
            }
            $this->model->show_json(1,array("url"=>unionUrl("activityresearch"),'message'=>"ok"));
        }
        include $this->template();
    }



    function option(){
        global $_W;
        global $_GPC;

        $id=intval($_GPC['id']);
        $data=pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch")." where id=:id",array(":id"=>$id));
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where  is_delete=0 and research_id=:id";
        $paras=array('id'=>$id);
        $sql="select * from ".tablename("ewei_shop_union_activityresearch_option").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_activityresearch_option").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }

    function optionedit(){
        $this->optionpost();
    }
    function optionadd(){
        $this->optionpost();
    }

    function optionpost(){
        global $_W;
        global $_GPC;

        $id=intval($_GPC['id']);
        $research_id=intval($_GPC['research_id']);
        $data=pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch_option")." where id=:id",array(":id"=>$id));


        if($_W['ispost']){
            $post=array(
                'option_name'=>trim($_GPC['title']),
                'research_id'=>intval($research_id),
                'status'=>intval($_GPC['status']),
                'create_time'=>time(),
            );
            if($id){
                unset($data['create_time']);
                pdo_update("ewei_shop_union_activityresearch_option",$post,array('id'=>$id));
            }else{
                pdo_insert("ewei_shop_union_activityresearch_option",$post);
            }
            $this->model->show_json(1,array("url"=>unionUrl("activityresearch/option",array("id"=>$research_id)),'message'=>"ok"));
        }
        include $this->template('activityresearch/optionpost');
    }

    function optiondel(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $research_id=intval($_GPC['research_id']);
        $data=pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch_option")." where id=:id",array(":id"=>$id));
        if(empty($data)){
            $this->model->show_json(0,"数据异常");
        }
        pdo_update("ewei_shop_union_activityresearch_option",array('is_delete'=>1),array('id'=>$id));
        $this->model->show_json(1,array("url"=>unionUrl("activityresearch/option",array("id"=>$research_id)),'message'=>"ok"));
    }


    function export(){
        global $_GPC,$_W;
        $id=intval($_GPC['id']);
        $union_id=$_W['unionid'];
        /*
        $sql="select m.name,m.mobile_phone,op.option_name,m.idcard from ".tablename("ewei_shop_union_activityresearch_sign")." as sg LEFT JOIN  "
            .tablename("ewei_shop_union_activityresearch_option")." as op ON op.id=sg.option_id "
            ."JOIN ".tablename("ewei_shop_union_members")." as m ON sg.openid=m.openid and m.union_id={$union_id}"
            .' where sg.research_id=:research_id'
        ;
        $list=pdo_fetchall($sql,array(":research_id"=>$id));
        */
        $sql="select name,mobile_phone,idcard,openid from ".tablename('ewei_shop_union_members')." where union_id=:union_id order by sort desc,add_time desc ";
        $list=pdo_fetchall($sql,array(":union_id"=>$union_id));

        $data=array();

        foreach ($list as $item){
            $data_temp=array(
                'name'=>$item['name'],
                'mobile_phone'=>$item['mobile_phone'],
                'idcard'=>$item['idcard'],
            );
            if(!empty($item['openid'])){
                $sql="select op.option_name from ".tablename('ewei_shop_union_activityresearch_sign')
                    ." as sg LEFT JOIN ".tablename('ewei_shop_union_activityresearch_option')
                    ." as op ON op.id=sg.option_id "
                    ."JOIN ".tablename("ewei_shop_union_members")." as m ON sg.openid=m.openid"
                    .' where sg.research_id=:research_id and sg.openid=:openid and m.union_id=:union_id';
                $option_name=pdo_fetchcolumn($sql,array(":research_id"=>$id,":openid"=>$item['openid'],':union_id'=>$union_id));
                $data_temp['option_name']=$option_name;
                $data[]=$data_temp;
            }else{
                $data_temp['option_name']="";
                $data[]=$data_temp;
            }
        }
        $columns=array(
            array('title' => '工会会员', 'field' => 'name', 'width' => 32),
            array('title' => "会员电话", 'field' => 'mobile_phone', 'width' => 32),
            array('title' => "身份证号码", 'field' => 'idcard', 'width' => 32),
            array('title' => "选项", 'field' => 'option_name', 'width' => 32),
        );
        m('excel')->export($data, array(
            "title" => "记录导出",
            'columns'=>$columns,
        ));
    }

}