<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
/**
 * 法律维权模块
 */
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';

class Leavmessage_EweiShopV2Page extends UnionMobilePage
{
    public function __construct()
    {
        parent::__construct();

    }
    public $listarray=array();
    public function main(){
        global $_W;
        global $_GPC;

        include $this->template();
    }

    /**
     * 用户提问
     */
    public function addmessage(){
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        if($_W['ispost']){
            $title=trim($_GPC['title']);
            $desc=trim($_GPC['desc']);
            $data=array(
                'message'=>$desc,
                'title'=>$title,
                'uniacid'=>$uniacid,
                'union_id'=>$union_id,
                'openid'=>$_W['openid'],
                'createtime'=>TIMESTAMP,
            );
            pdo_insert("ewei_shop_union_leavmessage",$data);
            show_json(1,array('url'=>mobileUrl("union/leavmessage"),'message'=>"发布成功"));
        }
        include $this->template();
    }

    /**
     * 添加回复
     */
    public function addreplay(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        $parent_id=intval($_GPC['parent_id']);
        $sql="select * from ".tablename("ewei_shop_union_leavmessage")." where id=:id";
        $item=pdo_fetch($sql,array(":id"=>$id));
        if($this->member['replaystatus']!=1){
            $this->message("抱歉！您暂时不能回复当前内容");
        }

        //回复是二次回复
        if($parent_id){
            $parent_info=pdo_fetch("select * from ".tablename("ewei_shop_union_leavmessage_reply")." where id=:id",array(":id"=>$parent_id));
        }

        if($_W['ispost']){
            $desc=trim($_GPC['desc']);
            $data=array(
                'replymessage'=>$desc,
                'level'=>$parent_id>0 ? $parent_info['level']+1:1,
                'parent_id'=>$parent_id,
                'createtime'=>TIMESTAMP,
                'uniacid'=>$uniacid,
                'union_id'=>$union_id,
                'openid'=>$_W['openid'],
                'leavmsgid'=>$id,
            );
            pdo_insert("ewei_shop_union_leavmessage_reply",$data);

            pdo_update("ewei_shop_union_leavmessage",array('count'=>$item['count']+1),array("id"=>$item['id']));

            show_json(1,array('url'=>mobileUrl("union/leavmessage/view",array('id'=>$id)),'message'=>"回复成功"));
        }
        include $this->template();
    }

    public function getlist(){
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $condition = ' and lm.uniacid = :uniacid and lm.union_id=:union_id and lm.status=0 ';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        //检查用户是否有查询权限
        //正常用户 查询自己的发表法律咨询内容
        //回复权限用户可以查询全部待回复的内容
        if($this->member['replaystatus']!=1){
            $condition.=" and lm.openid=:openid ";
            $params[':openid']=$_W['openid'];
        }
        $sql="select lm.* from ".tablename("ewei_shop_union_leavmessage")." as lm"
            ."  LEFT JOIN ".tablename("ewei_shop_union_members") ." as m ON m.openid=lm.openid and m.union_id=lm.union_id"
            ."  LEFT JOIN ".tablename("ewei_shop_member") ." as shopm ON shopm.openid=lm.openid ".
            "where 1 "
            .$condition
            ." order by lm.count asc,lm.createtime desc LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;  //最新发布的维权信息
        $countsql="select count(*) from ".tablename('ewei_shop_union_leavmessage')." as lm where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);

        $list = pdo_fetchall($sql, $params);

        foreach ($list as $key=> $item){
            $list[$key]['sterttime']=date("Y-m-d",$item['createtime']);
        }

        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    public function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        $condition = ' and lm.uniacid = :uniacid and lm.union_id=:union_id and lm.status=0 and lm.id=:id';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":id"=>$id);
        $sql="select lm.*,shopm.avatar,m.name from ".tablename("ewei_shop_union_leavmessage")
            ." as lm LEFT JOIN ".tablename("ewei_shop_union_members") ." as m ON m.openid=lm.openid and m.union_id=lm.union_id "
            ."LEFT JOIN ".tablename("ewei_shop_member") ." as shopm ON shopm.openid=lm.openid".
            " where 1 "
            .$condition
            ." order by lm.createtime desc ";//最新发布的维权信息
        $item=pdo_fetch($sql,$params);
        include $this->template();
    }
    public function getreplaylist(){
        global $_W;
        global $_GPC;
        $quicid=intval($_GPC['quicid']);
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 6;
        $condition = ' and lrp.uniacid = :uniacid and lrp.union_id=:union_id and lrp.leavmsgid=:quid';
        $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,':quid'=>$quicid);
        $sql="select lrp.*,shopm.avatar,m.name from ".tablename("ewei_shop_union_leavmessage_reply")." as lrp ".
            "LEFT JOIN ".tablename("ewei_shop_union_members")." as m ON m.openid =lrp.openid and lrp.union_id=m.union_id".
            " LEFT JOIN ".tablename("ewei_shop_member")." as shopm ON shopm.openid =lrp.openid ".
            " where 1 ".$condition." order by lrp.createtime desc LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_leavmessage_reply')." as lrp where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        foreach ($list as $key=> $item){
           $array=$this->getParen_listids($item['parent_id']);
            if(!empty($array)){
                $list[$key]['clildrens']=$this->getMorelist($array);
            }
            $this->listarray=array();
        }

        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }

    private function  getParen_listids($paent_id){

        if($paent_id>0){
            $this->listarray[]=$paent_id;
            $sql="select parent_id from ".tablename("ewei_shop_union_leavmessage_reply")." where id=:id";
            $parent_id=pdo_fetchcolumn($sql,array(':id'=>$paent_id));

            if($paent_id>0){
                $this->getParen_listids($parent_id);
            }
        }
        return  $this->listarray;
    }

    private function getMorelist($array=array()){
        global $_W;
        if(empty($array)){
            return null;
        }
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        if(count($array)==1){
            $condition = ' and lrp.uniacid = :uniacid and lrp.union_id=:union_id and lrp.id=:quid';
            $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,':quid'=>$array[0]);
        }else{
            $condition = ' and lrp.uniacid = :uniacid and lrp.union_id=:union_id and lrp.id in ('.implode(",",$array).')';
            $params = array(':uniacid' => $uniacid,':union_id'=>$union_id);
        }



        $sql="select lrp.*,shopm.avatar,m.name from ".tablename("ewei_shop_union_leavmessage_reply")." as lrp ".
            "LEFT JOIN ".tablename("ewei_shop_union_members")." as m ON m.openid =lrp.openid and lrp.union_id=m.union_id".
            " LEFT JOIN ".tablename("ewei_shop_member")." as shopm ON shopm.openid =lrp.openid ".
            " where 1 ".$condition." order by lrp.createtime asc";
        $list = pdo_fetchall($sql, $params);
        return $list;
    }


}
?>