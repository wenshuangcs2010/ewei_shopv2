<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Selectmember_EweiShopV2Page extends UnionWebPage
{
    function main(){
        global $_W;
        global $_GPC;
        $deid=empty($_GPC['deid']) ? 0 : intval($_GPC['deid']);
        $type=empty($_GPC['type']) ? '' : $_GPC['type'];
        $selectmember=$_GPC['selectvalue'];
        if(!empty($selectmember)){
            $peoplevale=explode(",",$selectmember);
        }
        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=1 order by displayorder desc";
        $level1=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=2 order by displayorder desc";
        $level2=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=3 order by displayorder desc";
        $level3=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");

        $sql="select name,parent_id,id from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1 and level=4 order by displayorder desc";
        $level4=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']),"id");



        foreach ($level1 as $item){
            if($item['parent_id']==0){
                $children=array();
                foreach ($level2 as $lev2){
                    if($lev2['parent_id']==$item['id']){
                        //查询 第三级
                        $leve3children=array();

                        foreach ($level3 as $lev3){
                            if($lev3['parent_id']==$lev2['id']){

                                $leve4children=array();

                                foreach ($level4 as $lev4){
                                    if($lev4['parent_id']==$lev3['id']){
                                        $leve4memberchildren=$this->get_cid_memberlist($lev4['id'],$peoplevale);

                                        $leve4children[]=array(
                                            'name'=>$lev4['name'],
                                            'active'=>false,
                                            'last'=>false,
                                            'img'=>'',
                                            'children'=>$leve4memberchildren,
                                        );
                                    }

                                }

                                $leve3memberchildren=$this->get_cid_memberlist($lev3['id'],$peoplevale);
                                $leve4children=array_merge($leve4children,$leve3memberchildren);

                                $leve3children[]=array(
                                    'name'=>$lev3['name'],
                                    'active'=>false,
                                    'last'=>false,
                                    'img'=>'',
                                    'children'=>$leve4children,
                                );
                            }
                        }

                        $leve2memberchildren=$this->get_cid_memberlist($lev2['id'],$peoplevale);
                        $leve3children=array_merge($leve3children,$leve2memberchildren);
                        $level2children=array(
                            'name'=>$lev2['name'],
                            'active'=>false,
                            'last'=>false,
                            'img'=>'',
                            'children'=>$leve3children,
                        );
                        $children[]=$level2children;
                    }
                }
                $leve1memberchildren=$this->get_cid_memberlist($item['id'],$peoplevale);
                $children=array_merge($children,$leve1memberchildren);
                $level1children[]=array(
                    'name'=>$item['name'],
                    'active'=>false,
                    'last'=>false,
                    'img'=>'',
                    'children'=>$children,
                );
            }
        }

        $basedata[]=array(
            'name'=>$this->user_info['title'],
            'active'=>false,
            'last'=>false,
            'img'=>'',
            'children'=>$level1children,
        );

        include $this->template();
    }

    private function   get_cid_memberlist($cid,$peoplevale=array()){
        global $_W;
        //查询本级的用户
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$_W['unionid'],
            ':department'=>$cid,
        );
        $leve4member=pdo_fetchall("select * from ".tablename("ewei_shop_union_members")." where department=:department and union_id=:union_id and uniacid=:uniacid",$params);
        $leve4memberchildren=array();
        foreach ($leve4member as $member4){
            $active=false;
            if(!empty($peoplevale)){
                if(in_array($member4['id'],$peoplevale)){
                    $active=true;
                }
            }

            $leve4memberchildren[]=array(
                'value'=>$member4['id'],
                'name'=>$member4['name'],
                'active'=>$active,
                'last'=>true,
                'img'=>'',
                'children'=>array(),
            );
        }
        return $leve4memberchildren;
    }
}