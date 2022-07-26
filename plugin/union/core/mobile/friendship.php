<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Friendship_EweiShopV2Page extends UnionMobilePage
{
    private $operatorid = 0;

    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="单身联谊";
        $indexid=intval($_GPC['indexid']);
        if($indexid>0){
            $uniontitle=pdo_fetchcolumn("select title from ".tablename("ewei_shop_union_menu")." where id=:indexid",array(":indexid"=>$indexid));
            $_W['union']['title']=$uniontitle;
        }
        include $this->template();
    }
    public function get_list(){
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $union_id=$_W['unionid'];
        $openid=$_W['openid'];
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 4;
        $order = !empty($args['order']) ? $args['order'] : ' f.add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' AND f.uniacid = :uniacid  AND f.is_delete=0 and f.union_id=:union_id';
        $sqlparent=$params = array(':uniacid' => $uniacid,':union_id'=>$union_id);

        if(isset($_GPC['type']) && $_GPC['type']==1){
            $condition.=" AND f.openid=:openid ";
            $params[':openid']=$openid;
        }else{
            $condition.=" AND f.verification=1 ";
        }
        if(isset($_GPC['type']) && $_GPC['type']==2){
            $sql_join=" LEFT JOIN ".tablename("ewei_shop_union_friendship_follow")." as ff ON ff.friend_id = f.id ";
            $condition.="  AND ff.openid=:openid and ff.follow=1  ";
            $params[':openid']=$openid;
        }
        $sql="select f.* from ".tablename("ewei_shop_union_friendship")." as f ".$sql_join."where 1 "
            .$condition."ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;

        $countsql="select count(*) from ".tablename('ewei_shop_union_friendship')." as f ".$sql_join." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        foreach ($list as &$row){
            $row['add_time']=date("Y-m-d",$row['add_time']);
           if($row['maritalstatus']==1){
                $row['maritalstatus']="离异";
            } elseif($row['maritalstatus']==2){
                $row['maritalstatus']="丧偶";
            }else{
                $row['maritalstatus']="未婚";
            }
            $row['header_imageurl']=tomedia($row['header_imageurl']);
            $sqlparent[':friend_id']=$row['id'];
            $sqlparent[':openid']=$openid;
            $row['fabulous']=pdo_fetchcolumn("select fabulous from ".tablename("ewei_shop_union_friendship_fabulous")." where uniacid=:uniacid and union_id=:union_id and friend_id=:friend_id and openid=:openid",$sqlparent);
            $row['follow']=pdo_fetchcolumn("select follow from ".tablename("ewei_shop_union_friendship_follow")." where uniacid=:uniacid and union_id=:union_id and friend_id=:friend_id and openid=:openid ",$sqlparent);
           // var_dump($sqlparent);
        }
        unset($row);
        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));
    }


    public function addfabuls(){
        global $_W;
        global $_GPC;
        if($_W['ispost']){
            $uniacid=$_W['uniacid'];
            $union_id=$_W['unionid'];
            $openid=$_W['openid'];
            $fabuls=intval($_GPC['fabulous']);
            $friend_id=intval($_GPC['friend_id']);
            $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":openid"=>$openid);
            $params[':friend_id']=$friend_id;
            $sql="select id from ".tablename("ewei_shop_union_friendship_fabulous")." where uniacid=:uniacid and union_id=:union_id and friend_id=:friend_id and openid=:openid ";
            $cid=pdo_fetchcolumn($sql,$params);

            if($cid){
                pdo_update('ewei_shop_union_friendship_fabulous',array("fabulous"=>$fabuls),array("id"=>$cid));
            }else{
                $data=array(
                    'uniacid'=>$uniacid,
                    'union_id'=>$union_id,
                    'fabulous'=>$fabuls,
                    'add_time'=>time(),
                    'openid'=>$openid,
                    'friend_id'=>$friend_id,
                );
                pdo_insert("ewei_shop_union_friendship_fabulous",$data);
            }
            $allfabulous= pdo_fetchcolumn('select allfabulous from '.tablename("ewei_shop_union_friendship")." where id=:id",array(":id"=>$friend_id));
            if($fabuls==1){
                pdo_update("ewei_shop_union_friendship",array("allfabulous"=>$allfabulous+1),array("id"=>$friend_id,'allfabulous'=>$allfabulous));
            }else{
                pdo_update("ewei_shop_union_friendship",array("allfabulous"=>$allfabulous-1),array("id"=>$friend_id,'allfabulous'=>$allfabulous));
            }
            show_json("1");
        }
    }
    public function addfollow(){
        global $_W;
        global $_GPC;
        if($_W['ispost']){
            $uniacid=$_W['uniacid'];
            $union_id=$_W['unionid'];
            $openid=$_W['openid'];
            $follow=intval($_GPC['follow']);
            $friend_id=intval($_GPC['friend_id']);
            $params = array(':uniacid' => $uniacid,':union_id'=>$union_id,":openid"=>$openid);
            $params[':friend_id']=$friend_id;
            $sql="select id from ".tablename("ewei_shop_union_friendship_follow")." where uniacid=:uniacid and union_id=:union_id and friend_id=:friend_id and openid=:openid ";
            $cid=pdo_fetchcolumn($sql,$params);

            if($cid){
                pdo_update('ewei_shop_union_friendship_follow',array("follow"=>$follow),array("id"=>$cid));
            }else{
                $data=array(
                    'uniacid'=>$uniacid,
                    'union_id'=>$union_id,
                    'follow'=>$follow,
                    'add_time'=>time(),
                    'openid'=>$openid,
                    'friend_id'=>$friend_id,
                );
                pdo_insert("ewei_shop_union_friendship_follow",$data);
            }
            $allfollow= pdo_fetchcolumn('select allfollow from '.tablename("ewei_shop_union_friendship")." where id=:id",array(":id"=>$friend_id));
            if($follow==1){
                pdo_update("ewei_shop_union_friendship",array("allfollow"=>$allfollow+1),array("id"=>$friend_id,'allfollow'=>$allfollow));
            }else{
                pdo_update("ewei_shop_union_friendship",array("allfollow"=>$allfollow-1),array("id"=>$friend_id,'allfollow'=>$allfollow));
            }
            show_json("1");
        }
    }

    public function addfriendship(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="发布";
        if($_W['ispost']){
            $header_imageurl=  isset($_GPC['images']) ?  tomedia($_GPC['images'][0]) : NULL;
            $life_images= isset($_GPC['life_images']) ?  array_map("tomedia",$_GPC['life_images']) : NULL;
            $data=array(
                'name'=>trim($_GPC['name']),
                'sex'=>intval($_GPC['sex']),
                'age'=>intval($_GPC['age']),
                'header_imageurl'=>$header_imageurl,
                'maritalstatus'=>intval($_GPC['maritalstatus']),
                'height'=>!empty($_GPC['height']) ? intval($_GPC['height']) :0,
                'income'=>trim($_GPC['income']),
                'education'=>trim($_GPC['education']),
                'address'=>trim($_GPC['address']),
                'work'=>trim($_GPC['work']),
                'character'=>trim($_GPC['character']),
                'other'=>trim($_GPC['other']),
                'additional'=>trim($_GPC['additional']),
                'contact'=>trim($_GPC['contact']),
                'otherage'=>trim($_GPC['otherage']),
                'othercondition'=>trim($_GPC['othercondition']),
                'otheradditional'=>trim($_GPC['otheradditional']),
                'declaration'=>trim($_GPC['declaration']),
                'life_images'=>!empty($life_images) ? implode("|",$life_images) :'',
                'add_time'=>time(),
                'openid'=>$_W['openid'],
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'otherheight'=>$_GPC['otherheight'],
                'othereducation'=>$_GPC['othereducation'],
                'otheraddress'=>$_GPC['otheraddress'],
                'otherwork'=>$_GPC['otherwork'],
                'otherincome'=>$_GPC['otherincome'],
                'othercharacter'=>$_GPC['othercharacter'],
            );
            if($id){

                pdo_update("ewei_shop_union_friendship",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_friendship",$data);
                $id=pdo_insertid();
            }

           show_json(1,array("id"=>$id));
        }
        $member=pdo_fetch("select * from ".tablename("ewei_shop_union_friendship")." where id=:id and openid=:openid and uniacid=:uniacid",array(":id"=>$id,'uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']));
        $life_images=!empty($member['life_images']) ? explode("|",$member['life_images']) :array();

        include $this->template();
    }

    function friendship_list(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $title="我的关注";
        $_W['union']['title']="我的关注";
        include $this->template('union/friendship/list');
    }
    function friendship_edit(){
        global $_W;
        global $_GPC;
        $type=intval($_GPC['type']);
        $title="我的发布";
        $_W['union']['title']="我的发布";
        include $this->template('union/friendship/editlist');
    }
    function del(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        pdo_update("ewei_shop_union_friendship",array("is_delete"=>1),array("id"=>$id));
        show_json(1);
    }
    function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $_W['union']['title']="交友详情";
        $member=pdo_fetch("select * from ".tablename("ewei_shop_union_friendship")." where id=:id  and uniacid=:uniacid",array(":id"=>$id,'uniacid'=>$_W['uniacid']));
        $life_images=!empty($member['life_images']) ? explode("|",$member['life_images']) :array();
        foreach ($life_images as $key=>$v){
            if(empty($v)){
                unset($life_images[$key]);
            }
        }


        //获取用户留言
        $params=array(":friend_id"=>$member['id']);
        if($member['openid']==$_W['openid']){
            //这个是自己的发布 显示全部留言
            $sql='select msg.*,m.avatar,m.realname,m.nickname from '.tablename("ewei_shop_union_friendship_message")." as msg LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid = msg.openid and m.uniacid=msg.uniacid where msg.friendship_id=:friend_id";

        }else{
            $sql='select msg.*,m.avatar,m.realname,m.nickname from '.tablename("ewei_shop_union_friendship_message")." as msg LEFT JOIN ".tablename("ewei_shop_member")." as m ON m.openid = msg.openid and m.uniacid=msg.uniacid where msg.friendship_id=:friend_id and msg.openid=:openid";
            $params[':openid']=$_W['openid'];
        }
        $massgalist=pdo_fetchall($sql,$params);


        include $this->template();
    }

    function addmessage(){
        global $_W;
        global $_GPC;
        $friendship_id=$_GPC['friendship_id'];
        $_W['union']['title']="发布留言";
        if($_W['ispost']){
            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'friendship_id'=> intval($_GPC['friendship_id']),
                'openid'=>$_W['openid'],
                'message'=>trim($_GPC['text']),
                'createtime'=>time(),
            );
            pdo_insert("ewei_shop_union_friendship_message",$data);
            show_json(1,array("id"=>pdo_insertid()));
        }
        include $this->template();
    }
}
?>