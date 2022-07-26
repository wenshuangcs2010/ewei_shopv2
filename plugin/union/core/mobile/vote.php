<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Vote_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $indexid=intval($_GPC['indexid']);
        if($indexid>0){
            $uniontitle=pdo_fetchcolumn("select title from ".tablename("ewei_shop_union_menu")." where id=:indexid",array(":indexid"=>$indexid));
            $_W['union']['title']=$uniontitle;
        }else{
            $_W['union']['title']="投票";
        }
        $sql="select * from ".tablename("ewei_shop_union_vote_activity")." where union_id=:union_id and uniacid=:uniacid and end_time>:tiems";
        $activitylist=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid'],":tiems"=>time()));

        $_W['shopshare'] = array(
            'title' =>$uniontitle,
            'imgUrl' => tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $uniontitle,
            'link' => mobileUrl('union/vote',null,true)
        );

        include $this->template();
    }
    public function quizlist(){
        global $_W;
        global $_GPC;
        $activity_id=intval($_GPC['activity_id']);
        $sql="select * from ".tablename("ewei_shop_union_vote_activity")." where id=:activity_id";
        $activity_info=pdo_fetch($sql,array(":activity_id"=>$activity_id));
        //全部的问题
        if(empty($activity_info)){
            $this->message("活动数据错误");
        }
        $_W['union']['title']=$activity_info['title'];
        $list=pdo_fetchall("select * from ".tablename("ewei_shop_union_vote_quiz")." where activity_id=:activity_id and union_id=:union_id",array(":activity_id"=>$activity_id,':union_id'=>$activity_info['union_id']));

        foreach ($list as &$item){
            $item['play']=false;
            if($item['type']==1){
               $count= pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_vote_user_click")." where quiz_id=:quiz_id and activity_id=:activity_id and openid=:openid",array(":activity_id"=>$activity_id,':quiz_id'=>$item['id'],":openid"=>$_W['openid']));
                if($item['votecount']==$count){
                    $item['play']=true;
                }
            }else{
                $count= pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_vote_user_click")." where quiz_id=:quiz_id and activity_id=:activity_id and openid=:openid",array(":activity_id"=>$activity_id,':quiz_id'=>$item['id'],":openid"=>$_W['openid']));
                if($count>0){
                    $item['play']=true;
                }
            }
        }
        unset($item);


        $_W['shopshare'] = array(
            'title' =>$activity_info['title'],
            'imgUrl' => tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $activity_info['title'],
            'link' => mobileUrl('union/vote/quizlist',array('activity_id'=>$activity_id),true)
        );


        include $this->template();
    }
    public function quiz(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=intval($_GPC['union_id']);
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id=:id",array(":id"=>$id));
        //获取当前问题的活动ID和时间
        $sql="select * from ".tablename("ewei_shop_union_vote_activity")." where id=:activity_id";
        $activity_info=pdo_fetch($sql,array(":activity_id"=>$view['activity_id']));
        $last_time=$activity_info['end_time'];

        $_W['union']['title']=$view['title'];
        //全部的选项
        $sql="select * from ".tablename("ewei_shop_union_vote_option")." where uniacid=:uniacid and union_id=:union_id  and quiz_id=:quiz_id ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":quiz_id"=>$view['id']);
        pdo_update('ewei_shop_union_vote_quiz',array('clickcount'=>$view['clickcount']+1),array("id"=>$view['id']));
        $list = pdo_fetchall($sql, $paras);
        foreach ($list as &$item){
            if(empty($item['image'])){
                $item['image']=EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/votedefault.png";
            }
        }
        unset($item);
        if(empty($list)){
            $this->message("活动数据错误");
        }
        $optioncount=count($list);
        $alloptiontackcount=0;
        foreach ($list as $op){
            $alloptiontackcount+=$op['ticketcount'];
        }
        $_W['shopshare'] = array(
            'title' =>$view['title'],
            'imgUrl' => !empty($view['image']) ? tomedia($view['image']) : tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $view['title'],
            'link' => mobileUrl('union/vote/quiz',array('id'=>$id),true)
        );
        include $this->template();
    }

    //用户点击 投票按钮的时候

    public function option(){
        global $_W;
        global $_GPC;
        $optionid=intval($_GPC['id']);
        $quiz_id=intval($_GPC['quiz_id']);
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":quiz_id"=>$quiz_id);
        $sql="select id,title,ticketcount from ".tablename("ewei_shop_union_vote_option")." where uniacid=:uniacid and union_id=:union_id  and quiz_id=:quiz_id  order by ticketcount desc limit 0,6" ;
        $optionlist = pdo_fetchall($sql, $paras);
        $ranklist=array();
        $memberlistkey=array();
        foreach ($optionlist as $key=> $value){
            $ranklist[$value['id']]=$key+1;
        }
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":quiz_id"=>$quiz_id);
        $sql="select id,title,ticketcount from ".tablename("ewei_shop_union_vote_option")." where uniacid=:uniacid and union_id=:union_id  and quiz_id=:quiz_id " ;
        $optionlist_rank = pdo_fetchall($sql, $paras);
        foreach ($optionlist_rank as $key=> $value){
            $memberlistkey[$value['id']]=$key+1;
        }



        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id=:id",array(":id"=>$quiz_id));

        $optionitem=pdo_fetch("SELECT * from ".tablename("ewei_shop_union_vote_option")." where id=:id",array(":id"=>$optionid));



        pdo_update('ewei_shop_union_vote_option',array('clikcount'=>$optionitem['clikcount']+1),array("id"=>$optionitem['id']));

        include $this->template();
    }
    public function ticket(){
        global $_W;
        global $_GPC;
        $openid=$_W['openid'];
        $optionid=intval($_GPC['optionid']);
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$optionid);
        $sql="select * from ".tablename("ewei_shop_union_vote_option")." where uniacid=:uniacid and union_id=:union_id  and id=:id  order by ticketcount desc";
        $option_info = pdo_fetch($sql, $paras);//具体的选项
        if(empty($option_info)){
            show_json(0,"候选人已失效,投票失败");
        }
        $quiz_info=pdo_fetch("select * from ".tablename("ewei_shop_union_vote_quiz")." where id=:id",array(":id"=>$option_info['quiz_id']));
        if(empty($quiz_info)){
            show_json(0,"候选人已失效,投票失败");
        }
        //获取当前问题的活动ID和时间
        $sql="select * from ".tablename("ewei_shop_union_vote_activity")." where id=:activity_id";
        $activity_info=pdo_fetch($sql,array(":activity_id"=>$quiz_info['activity_id']));
        if(empty($activity_info)){
            show_json(0,"候选人已失效,投票失败");
        }
        if(time()>$activity_info['end_time']){
            show_json(0,"活动已经结束,投票失败");
        }
        $memberinfo=$this->model->get_member($openid,$activity_info['union_id']);
        if(empty($memberinfo)){
            show_json(0,"您还未在当前投票活动开启工会中激活账户,请先激活账户后参与投票");
        }
        if(!empty($quiz_info['peoplevale'])){
           $peoplevale=explode(',',$quiz_info['peoplevale']);
           if(!in_array($memberinfo['id'],$peoplevale)){
               show_json(0,"抱歉！当前投票限制投票人,您暂时没有获得投票权利");
           }
        }
        //查询当前用户是否投票过这个选项
        $sql="select * from ".tablename("ewei_shop_union_vote_user_click")." where openid=:openid and optionid=:optionid and quiz_id=:quiz_id and activity_id=:activity_id and uniacid=:uniacid and union_id=:union_id";
        $params=array(
            ':openid'=>$openid,
            ':optionid'=>$optionid,
            ':quiz_id'=>$quiz_info['id'],
            ':activity_id'=>$activity_info['id'],
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$quiz_info['union_id'],
        );
        $usertakethis=pdo_fetch($sql,$params);


        //用户对当前问题的总投票数
        $usertakelist=pdo_fetchall("select * from ".tablename("ewei_shop_union_vote_user_click")." where openid =:openid and quiz_id=:quiz_id",array(":openid"=>$openid,":quiz_id"=>$quiz_info['id']));
        $usertakecount=count($usertakelist);

        //是否限制票数
        if($quiz_info['type']==1 && $quiz_info['votecount']<=$usertakecount){
            show_json(0,"抱歉！您的投票次数已经达到上限");
        }
        if(!empty($usertakethis)){
            show_json(0,"抱歉！您已经投过票了，每个选项仅能投票一次");
        }
        if($quiz_info['type']==0 && $usertakecount>0 ){
            show_json(0,"抱歉！您已经投过票了，当前问题仅能投票一次");
        }
        try{
            pdo_begin();
            $data=array(
                'openid'=>$openid,
                'createtime'=>time(),
                'quiz_id'=>$quiz_info['id'],
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$quiz_info['union_id'],
                'optionid'=>$optionid,
                'activity_id'=>$activity_info['id'],
            );
            pdo_insert("ewei_shop_union_vote_user_click",$data);
            $insertid=pdo_insertid();
            $upstatus=pdo_update("ewei_shop_union_vote_option",array("ticketcount"=>$option_info['ticketcount']+1),array("id"=>$option_info['id']));
            pdo_commit() ;
        }catch (Exception $e){
            pdo_rollback();
            show_json(0,"投票失败,请重新尝试");
        }
        if($insertid && $upstatus){
            show_json(1,"投票成功");
        }
    }


}