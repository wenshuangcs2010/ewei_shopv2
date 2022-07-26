<?php



if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Readmember_EweiShopV2Page extends UnionMobilePage
{
    function main(){
        global $_W;
        global $_GPC;

        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $level= !empty($_GPC['level']) ? intval($_GPC['level']) : 1;
        $readtype=intval($_GPC['readtype']);
        $articleid=intval($_GPC['articleid']);
        $condition = ' and  unm.activate=1 and  unm.status=1 and rdm.uniacid = :uniacid and rdm.union_id=:union_id and rdm.type=:type and rdm.groupid=:groupid';
        $params=$pa = array(':uniacid' => $uniacid,':union_id'=>$union_id,":type"=>$readtype,":groupid"=>$articleid);
        $readmember="all";



        if($level==1){
            $sql="select count(*) as count,dep.id as department,dep.displayorder from ".tablename("ewei_shop_union_readmembers")." as rdm "
                ." left join ".tablename("ewei_shop_union_members")." as unm ON unm.openid =rdm.openid and unm.union_id=rdm.union_id"
                ." LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON unm.department =dep.id "
                ." where 1 "
                .$condition." group by unm.department order by dep.displayorder desc,unm.sort desc ";
            $list = pdo_fetchall($sql, $params);

        }else{
            if($readtype==1 && $articleid>0){//文章东西
                //查询本片应读人
                $info=$this->model->get_document_info($articleid);
                $article=$info['info'];
                if(!empty($article['peoplevale'])){
                    $readmember=$article['peoplevale'];
                }
            }

            $condition=" where rem.openid is NULL  and unm.activate=1 and unm.status=1 and  unm.union_id=:union_id and unm.uniacid=:uniacid ";
            if($readmember!="all"){
                $condition.=" and unm.id in (".$readmember.")";
            }

            $sql="select dep.id as department,dep.`name`,count(*) as count,dep.displayorder from ims_ewei_shop_union_members as unm ".
                " left join ims_ewei_shop_union_readmembers as rem on unm.openid = rem.openid and rem.type=".$readtype." and groupid=".$articleid.
                " left join ims_ewei_shop_union_department as dep ON dep.id=unm.department"
                .$condition." GROUP BY dep.id order by dep.displayorder desc,unm.sort desc ";


            $list = pdo_fetchall($sql, array(':union_id'=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
        }

        $depalist=pdo_fetchall("select * from ".tablename("ewei_shop_union_department")." where 1 and uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']),"id");


        //部门的层级关系
        foreach ($list as &$value){
            if(!empty($value['department'])){

                $parent= $this->getparent($depalist,$value['department']);
                $value['depname']=$parent['name'];
                $value['parent_id']=$parent['id'];
            }else{
                $value['depname']="无部门";
                $value['parent_id']=0;
                $value['displayorder']=-1;
            }
            $value['level']=$level;
            $value['readtype']=$readtype;
            $value['articleid']=$articleid;
        }
        unset($value);
        $res = array();
        foreach ($list as $key =>$item){
            if(!isset($res[$item['parent_id']])) $res[$item['parent_id']] = $item;
            else{
                $res[$item['parent_id']]['count'] += $item['count'];
            }

        }
        array_multisort(array_column($res,"displayorder"),SORT_DESC,$res);


        show_json(1, array('list' => $res,'total' => 1, 'pagesize' => 1));
    }

    /**
     * @param $array
     * @param $id
     * @return mixed
     */
    function getparent($array, $id){
        static $parent=array();
        if($array[$id]['level']==1){

            $parent= $array[$id];
        }else{
            $parent_id=$array[$id]['parent_id'];

            if(!is_null($array[$id])){
                $this->getparent($array,$parent_id);
            }

        }
        return $parent;
    }
    function members(){
        global $_W;
        global $_GPC;
        $readtype=intval($_GPC['readtype']);
        $articleid=intval($_GPC['articleid']);
        include $this->template("union/readmembers");
    }



    function category(){
        global $_W;
        global $_GPC;
        $categoryid=intval($_GPC['cid']);
        $level=intval($_GPC['level']);
        $readtype=intval($_GPC['readtype']);
        $articleid=intval($_GPC['articleid']);
        $union_id=$_W['unionid'];
        $uniacid=$_W['uniacid'];
        $readmember="all";
        $category_info=pdo_fetch("select id,name from ".tablename("ewei_shop_union_department")." where id=:parent_id",array(":parent_id"=>$categoryid));
        if($level==1){
            //检查对应部门有没有看过的人
            $sql="select rem.openid,unm.name from ".tablename("ewei_shop_union_readmembers")." as rem "
                ." LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON unm.openid = rem.openid "
                ." LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON unm.department =dep.id "
                ." where unm.uniacid =:uniacid  and unm.activate=1 and unm.status=1 and  rem.union_id=:union_id and unm.department=:department and rem.type=:readtype and rem.groupid=:groupid order by unm.sort desc";
            $readmemberlist=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],":union_id"=>$union_id,':readtype'=>$readtype,":department"=>$categoryid,":groupid"=>$articleid));

        }

        if($level==2){
            if($readtype==1 && $articleid>0){//文章东西
                //查询本片应读人
                $info=$this->model->get_document_info($articleid);
                $article=$info['info'];
                if(!empty($article['peoplevale'])){
                    $readmember=$article['peoplevale'];
                }
            }

            $sql="select unm.openid,unm.name from ".tablename("ewei_shop_union_members")." as unm "
                ." LEFT JOIN ".tablename("ewei_shop_union_readmembers")." as rem ON unm.openid = rem.openid and rem.type=:readtype"
                ." LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON unm.department =dep.id "
                ." where rem.openid is NULL and unm.activate=1 and unm.status=1 and  unm.department=:department and  unm.uniacid =:uniacid and unm.union_id=:union_id ";
            if($readmember!="all"){
                $sql.=" and unm.id in (".$readmember.")";
            }

            $readmemberlist=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],":union_id"=>$union_id,':readtype'=>$readtype,":department"=>$categoryid));

        }

        //直属下级
        $categorychilren=pdo_fetchall("select id,name from ".tablename("ewei_shop_union_department")." where parent_id=:parent_id",array(":parent_id"=>$categoryid));
        foreach ($categorychilren as $key=> &$item){
            $cidlist=array();
            $cidlist=$this->get_parent_chilren($item['id']);
            $cidlist[]=$item['id'];
            if(!empty($cidlist)){

                if($level==1){

                    $sql="select count(*) from ".tablename("ewei_shop_union_readmembers")." as adm "
                        ." LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON unm.openid=adm.openid "
                        ." where unm.department in (".implode(",",$cidlist).") and unm.activate=1 and  adm.uniacid=:unaicid and adm.union_id=:union_id and adm.groupid=:groupid"
                    ;
                    $count=pdo_fetchcolumn($sql,array(":unaicid"=>$uniacid,':union_id'=>$union_id,":groupid"=>$articleid));

                    $item['count']=$count;
                    if(empty($count)){
                        unset($categorychilren[$key]);
                    }
                }
                if($level==2){
                    $sql="select count(*) from ".tablename("ewei_shop_union_members")." as unm "
                        ." LEFT JOIN ".tablename("ewei_shop_union_readmembers")." as adm ON unm.openid=adm.openid "
                        ." where unm.department in (".implode(",",$cidlist).") and adm.openid is NULL and unm.activate=1 and unm.status=1 and   unm.uniacid=:unaicid and unm.union_id=:union_id"
                    ;
                    if($readtype==1 && $articleid>0){//文章东西
                        //查询本片应读人
                        $info=$this->model->get_document_info($articleid);
                        $article=$info['info'];
                        if(!empty($article['peoplevale'])){
                            $readmember=$article['peoplevale'];
                        }
                        unset($article);
                    }
                    if($readmember!="all"){
                        $sql.=" and unm.id in (".$readmember.")";
                    }

                    $count=pdo_fetchcolumn($sql,array(":unaicid"=>$uniacid,':union_id'=>$union_id));
                    $item['count']=$count;
                }
            }
        }
        unset($item);
        foreach ($categorychilren as $key => $value){
            if($value['count']==0){
                unset($categorychilren[$key]);
            }
        }

        $list=array(
            'category'=>$categorychilren,
            'memberlist'=>$readmemberlist,
        );

        include $this->template("union/categoryreadmembers");
    }
    //查询下级部门还有的查询 分类ID
    function get_parent_chilren($cid){
        static $cidlist=array();

        $category=pdo_fetchall("select id from ".tablename("ewei_shop_union_department")." where parent_id=:parent_id",array(":parent_id"=>$cid));
        if(!empty($category)){
            foreach ($category as $value){
                $cidlist[]=$value['id'];
                $this->get_parent_chilren($value['id']);
            }
        }
        return $cidlist;
    }
}