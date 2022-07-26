<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Research_EweiShopV2Page extends UnionMobilePage
{

    public function main()
    {
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $openid=$_W['openid'];
        $union_id=$_W['unionid'];
        $data=pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch")." where id=:id",array(":id"=>$id));
        //$data['description']=htmlspecialchars($data['description'],ENT_QUOTES);
        $selectdata= pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch_sign")
            ." where openid=:openid and union_id=:union_id and research_id =:research_id",
            array(":openid"=>$openid,':union_id'=>$union_id,':research_id'=>$id)
        );
        $sql="select * from ".tablename("ewei_shop_union_activityresearch_option")." where research_id=:research_id and is_delete=0";
        $list=pdo_fetchall($sql,array(":research_id"=>$id));

        include $this->template();
    }

    public function sign(){
        global $_W;
        global $_GPC;
        $union_id=$_W['unionid'];
        $openid=$_W['openid'];
        $research_id=intval($_GPC['research_id']);
        $option_id=intval($_GPC['option_id']);
        $params=array(
            'union_id'=>$union_id,
            'openid'=>$openid,
            'research_id'=>$research_id,
            'option_id'=>$option_id,
            'create_time'=>time(),
        );

       $data= pdo_fetch("select * from ".tablename("ewei_shop_union_activityresearch_sign")
            ." where openid=:openid and union_id=:union_id and research_id =:research_id",
            array(":openid"=>$openid,':union_id'=>$union_id,':research_id'=>$research_id)
        );
       if($data){
           show_json(0,'您已经选择过了,无法重复选择');
       }
       pdo_insert('ewei_shop_union_activityresearch_sign',$params);
        show_json(1);
    }

}