<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Maillist_EweiShopV2Page extends UnionMobilePage
{
    static $cidlist=array();
    static $departmentlist=array();
    function main(){
        global $_W;
        global $_GPC;
        $categoryid=intval($_GPC['categoryid']);
        if($categoryid==-1){
            $categoryid=0;
        }
        $union_title=$_W['union_info']['title'];
        $keywords=empty($_GPC['keywords']) ? "" :trim($_GPC['keywords']);

        if($keywords!=""){
            $params=array(
                ':union_id'=>$_W['unionid'],
                ':uniacid'=>$_W['uniacid'],

            );
            $sql="select unm.name,unm.id,m.avatar,unm.duties from ".tablename("ewei_shop_union_members")." as unm LEFT JOIN "
                .tablename("ewei_shop_member")." as m ON m.openid=unm.openid and m.uniacid=:uniacid "
                ." left JOIN ".tablename("ewei_shop_union_department")." as d ON d.id=unm.department "
                ." where unm.union_id=:union_id and unm.uniacid=:uniacid and (unm.name like '%".$keywords."%' or m.mobile like '%".$keywords."%' or d.name like '%".$keywords."%')   and unm.activate=1 order by unm.sort desc";

            $memberlist=pdo_fetchall($sql,$params);
            foreach ($memberlist as &$value){
                $value['title_name']=mb_strcut($value['name'], 0, 3);
            }
            unset($value);
            $list= array("categorylist"=>array(),"memberlist"=>$memberlist);
        }
        elseif($categoryid==-1){

        }elseif($categoryid!=-1){
            $list=$this->get_category_list($categoryid);
        }

        include $this->template();
    }

    public function get_department($id){
        $category=pdo_fetch("select id,parent_id,name,level from ".tablename("ewei_shop_union_department")." where id=:parent_id",array(":parent_id"=>$id));
        self::$departmentlist[]=$category;
        if($category['parent_id']){
            $this->get_department($category['parent_id']);
        }
    }

    public function memberinfo(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $unionmember=$this->model->get_union_member_info($id);
        $departmentid=$unionmember['department'];
        $this->get_department($departmentid);
        $departmentname="";
        array_multisort(self::$departmentlist,SORT_ASC,array_column(self::$departmentlist,'level'));
        foreach (self::$departmentlist as $value){
            $departmentname.=$value['name']."-";
        }
        $departmentname=mb_substr($departmentname,0,strlen($departmentname)-1);

        $member=m("member")->getMember($unionmember['openid']);
        $_W['union']['title']="会员中心";
        $company=$this->model->get_union_info($_W['unionid']);
        $set = m('common')->getPluginset('union');
        include $this->template();
    }
    public function matrixmain(){
        global $_W;
        global $_GPC;
        $list=$this->get_category_list(2378);
        $list=$list['categorylist'];
        foreach ($list as $key=>$dep){
            $child=$this->get_category_list($dep['id']);
            if(!empty($child['categorylist'])){
                $list[$key]['children']=$child['categorylist'];
            }
        }

        include $this->template();
    }
    public function matrix(){
        global $_W;
        global $_GPC;
        $categoryid=intval($_GPC['categoryid']);
        $categorinfo=pdo_fetch("select id,parent_id,name,level from ".tablename("ewei_shop_union_department")." where id=:parent_id",array(":parent_id"=>$categoryid));

        $list=$this->get_category_list($categoryid);
        $list=$list['categorylist'];
        include $this->template();
    }

    private  function get_category_list($cid){
        global $_W;
        $params=array(
            ':union_id'=>$_W['unionid'],
            ':uniacid'=>$_W['uniacid'],
            ':cateid'=>$cid
        );
        $categorylist=pdo_fetchall("select name,id from ".tablename("ewei_shop_union_department")." where parent_id=:cateid and enable=1 and uniacid =:uniacid and union_id=:union_id order by displayorder desc",$params);
        foreach ($categorylist as &$value){
            self::$cidlist=array();
            $cateids=$this->get_cate_ids($value['id']);
            if(count($cateids)>1){
                $condition=" department in (".implode(",",$cateids).")";
            }else{
                $condition=" department=".$cateids[0];
            }
            $sql="select count(*) from ".tablename("ewei_shop_union_members")." where ".$condition."  and activate=1 and union_id=:union_id and uniacid =:uniacid ";
            $value['count']=pdo_fetchcolumn($sql,array(
                ':union_id'=>$_W['unionid'],
                ':uniacid'=>$_W['uniacid'],

            ));
            $value['tag_name']=mb_strcut($value['name'], 0, 3);
        }
        unset($value);
        $sql="select unm.name,unm.id,m.avatar,unm.duties from ".tablename("ewei_shop_union_members")." as unm LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid=unm.openid and m.uniacid=:uniacid "
             ." where unm.union_id=:union_id and unm.uniacid=:uniacid and unm.department=:cateid and unm.activate=1 order by unm.sort desc";
        $memberlist=pdo_fetchall($sql,$params);
        foreach ($memberlist as &$value){
            $value['title_name']=mb_strcut($value['name'], 0, 3);
        }
        unset($value);

        return array("categorylist"=>$categorylist,"memberlist"=>$memberlist);
    }



    private function get_cate_ids($cateid){

        self::$cidlist[]=$cateid;
        $category=pdo_fetchall("select id from ".tablename("ewei_shop_union_department")." where parent_id=:parent_id",array(":parent_id"=>$cateid));
        if(!empty($category)){
            foreach ($category as $value){
                $this->get_cate_ids($value['id']);
            }
        }
        return self::$cidlist;
    }
}