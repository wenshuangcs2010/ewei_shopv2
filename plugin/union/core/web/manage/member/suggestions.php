<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Suggestions_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where p.uniacid=:uniacid and p.union_id=:union_id  and p.is_delete=0 and p.status>0";
        if($_GPC['keywordes']!=''){

            $condition.=" and title like '%".$_GPC['keywordes']."%'";
        }
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select p.title,p.status,p.id,m.name,p.create_time from ".tablename("ewei_shop_union_suggestions")." as p LEFT JOIN ".
            tablename("ewei_shop_union_members")." as m ON m.openid=p.openid and p.union_id=m.union_id ".
            $condition;
        $sql.=" order by p.create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);

        foreach ($list as &$row){
            if($row['status']==0){
                $row['status']="待提交";
            }
            elseif($row['status']==1){
                $row['status']="待采纳";
            }
            elseif($row['status']==2){
                $row['status']="已采纳";
            }elseif($row['status']==3){
                $row['status']="未采纳";
            }
        }
        unset($row);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_suggestions")." as p ".$condition,$paras);
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

        if($id){
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id);
            $title="审核";
            $vo=pdo_fetch("select p.*,m.name from ".tablename("ewei_shop_union_suggestions")." p LEFT JOIN ".tablename("ewei_shop_union_members")." as m ON m.openid=p.openid where p.id =:id and p.uniacid=:uniacid and p.union_id=:union_id",$paras);
        }else{
            $title="添加";
        }
        if($_W['ispost']){
            $status=intval($_GPC['status']);
            if($status<1){
                $this->message("未做任何修改",unionUrl("member/suggestions",array("id"=>$id)));
            }
            $data=array(
                'status'=>intval($_GPC['status']),
                'memberlist'=>$_GPC['memberlist'],
                'memberlistname'=>$_GPC['memberlistname'],
            );
            if($id){
                unset($data['create_time']);
                pdo_update("ewei_shop_union_suggestions",$data,array('id'=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));
            }
            $this->message("数据处理成功",unionUrl("member/suggestions"));
        }
        include $this->template("member/suggestions_post");
    }
}