<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Quiz_EweiShopV2Page extends UnionMobilePage
{


    private $key_list=array(
        "A","B","C","D","E","F"
    );
    public function get_allcategory($cate_id){
        global $_W;
        static $categorylist=array();
        $categorylist[]=$cate_id;

        $params=array(":union_id"=>$_W['unionid'],":uniacid"=>$_W['uniacid'],":parent_id"=>$cate_id);
        $category_id=pdo_fetchall("select id from ".tablename("ewei_shop_union_quiz_category")." where parent_id=:parent_id and uniacid =:uniacid and union_id=:union_id",$params);

        if(!empty($category_id)){
            foreach ($category_id as $c){
                $this->get_allcategory($c['id']);
            }
        }
        return $categorylist;
    }
    public function main()
    {
        global $_W;
        global $_GPC;

        $_W['union']['title']="竞赛调研";

        //$parentlist=$this->model->_superior_unionlist($_W['unionid']);
        $condition=" where qa.end_time>:times and qa.start_time<:times and qa.status=1 and qa.uniacid=:uniacid  and qa.deleted=0 ";
//        if(!empty($parentlist) && count($parentlist)>1){
//            $condition.=' and  ((qa.union_id in ('.implode(",",$parentlist).' ) and qa.show_type=1) or qa.union_id=:union_id) ';
//        }else if(!empty($parentlist) && count($parentlist)==1){
//            $condition.=' and  ((qa.union_id ='.$parentlist[0].' and qa.show_type=1) or qa.union_id=:union_id) ';
//        }else{
            $condition.="and qa.union_id=:union_id";
      //  }
        $cateid=intval($_GPC['id']);

        $params=array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],"times"=>time());
        if($cateid!=''){
            $category_list=$this->get_allcategory($cateid);
            $condition .= " and category_id in (".implode(",",$category_list).")";
        }
        $sql="select qa.* from ".tablename("ewei_shop_union_quiz_activity")." as qa ".$condition;
        $list= pdo_fetchall($sql,$params);


        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$cateid);
        $category=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_category")." where id=:id and uniacid =:uniacid and union_id=:union_id and enable=1",$params);

        if($category){
            $_W['union']['title']=$category['catename'];
            $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_quiz_category') . ' WHERE 1 and parent_id=:id  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");
        }
        $indexid=intval($_GPC['indexid']);
        if($indexid>0){
            $uniontitle=pdo_fetchcolumn("select title from ".tablename("ewei_shop_union_menu")." where id=:indexid",array(":indexid"=>$indexid));
            $_W['union']['title']=$uniontitle;
        }
        include $this->template();
    }

    public function view(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="知识竞赛";
        $id=intval($_GPC['id']);
        if(empty($id)){
            $this->message("非法访问");
        }
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_activity")." where id=:id",array(":id"=>$id));
        if($view['title']){
            $_W['union']['title']=$view['title'];
        }
        //检查时间
        if($view['start_time']>time()){
            $this->message("活动尚未开始");
        }
        if($view['end_time']<time()){
            $this->message("活动已经结束");
        }
        if($view['type']==1){//如果题目类型是调研类型的 调研结束时间是活动的结束时间
            $view['count_times']=$view['end_time']-time();

        }

        $bottontext="开始";
        //筛选题目的数量
        $userstart=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_userstart")." where act_id=:act_id and openid =:openid",array(":act_id"=>$id,':openid'=>$_W['openid']));
        if(!empty($userstart)) {
            if(time()-$userstart['starttime']>$view['count_times']){
                //耗时完成
                $bottontext="查看详情";
            }else{
                $userstart=pdo_fetchall("select * from ".tablename("ewei_shop_union_quiz_user")." where act_id=:act_id and openid =:openid",array(":act_id"=>$id,':openid'=>$_W['openid']),"quiz_id");
                if(count($userstart)>=count(explode(",",$view['quiz_ids']))){
                    $bottontext="查看详情";
                }else{
                    $bottontext="开始";
                }
            }
        }
        include $this->template();
    }

    public function myquiz(){
        global $_W;
        global $_GPC;
        include $this->template();
    }

    public function showquzi(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['act_id']);
        if(empty($id)){
            $id=intval($_GPC['id']);
        }
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_activity")." where id=:id",array(":id"=>$id));
        $_W['union']['title']=$view['title'];
        if(empty($view)){
            $this->message("未发现活动",'','error');
        }
        //用户的答题记录
        $userstart=pdo_fetchall("select * from ".tablename("ewei_shop_union_quiz_user")." where act_id=:act_id and openid =:openid",array(":act_id"=>$id,':openid'=>$_W['openid']),"quiz_id");


        $qu_list=pdo_fetchall("select * from ".tablename("ewei_shop_union_quiz")." where id in (".$view['quiz_ids']. ")");//全部的题目
        foreach($qu_list as &$qu_info){
            $qus_type=1;
            $answerlist=array();
            $userexplode=array();
            if(isset($userstart[$qu_info['id']])){
                $userexplode=explode(",",$userstart[$qu_info['id']]['answer']);//用户的选择
                $qu_info['is_ok']=$userstart[$qu_info['id']]['is_ok'];
            }

            if($qu_info['quiztype']==1){//是非题
                $tempanswer=array('key'=>"c1","title"=>$this->key_list[0],'value'=>"是","user_check"=>0);//默认用户未选择这个答案
                if($userexplode){
                    if($userexplode[0]=="c1"){
                        $tempanswer['user_check']=1;
                    }
                }


                if($qu_info['yes']==0){
                    $tempanswer['yes']=1;
                }
                $answerlist[]=$tempanswer;
                $tempanswer2=array('key'=>"c2","title"=>$this->key_list[1],'value'=>"否","user_check"=>0);
                if($qu_info['yes']==1){
                    $tempanswer2['yes']=1;
                }
                if($userexplode){
                    if($userexplode[0]=="c2"){
                        $tempanswer2['user_check']=1;
                    }
                }
                $answerlist[]=$tempanswer2;
            }else{
                //检查是单选还是多选题

                $countlist=explode(',',$qu_info['winning']);
                $temp_answer_yes=array();//全部的正确答案的ID
                foreach ($countlist as $value){
                    $temp_answer_yes[]='c'.$value;
                }
                if(count($countlist)>1){
                    //多选题
                    $qus_type=2;
                }else{
                    $qus_type=1;//单选题
                }
            }
            if($qu_info['quiztype']==0) {//选择题
                $answerlist=array();
                $tempanswerlist=array();
                if(!empty($qu_info['c1']))$tempanswerlist['c1']=$qu_info['c1'];
                if(!empty($qu_info['c2']))$tempanswerlist['c2']=$qu_info['c2'];
                if(!empty($qu_info['c3']))$tempanswerlist['c3']=$qu_info['c3'];
                if(!empty($qu_info['c4']))$tempanswerlist['c4']=$qu_info['c4'];
                if(!empty($qu_info['c5']))$tempanswerlist['c5']=$qu_info['c5'];
                if(!empty($qu_info['c6']))$tempanswerlist['c6']=$qu_info['c6'];

                $i=0;
                foreach ($tempanswerlist as $k=> $item){
                    $temp=array('key'=>$k,"title"=>$this->key_list[$i],'value'=>$item,'user_check'=>0);
                    if(in_array($k,$userexplode)){
                        $temp['user_check']=1;
                    }
                    if(in_array($k,$temp_answer_yes)){
                        $temp['yes']=1;
                    }
                    $answerlist[]=$temp;
                    $i++;
                }
            }
            $qu_info["answerlist"]=$answerlist;
            $qu_info["qus_type"]=$qus_type;


        }
        unset($qu_info);

        include $this->template("union/quiz/showquzi");
        exit();
    }

    public function jinshai(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['act_id']);
        if(empty($id)){
            $id=intval($_GPC['id']);
        }
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_activity")." where id=:id",array(":id"=>$id));
        if($view['type']==1){
            $this->showquzi();
            exit();
        }
        pdo_begin();
        $userstart=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_userstart")." where act_id=:act_id and openid =:openid",array(":act_id"=>$id,':openid'=>$_W['openid']));
        //用户的答题记录
        $right=pdo_fetch("select sum(is_ok) as yes from ".tablename("ewei_shop_union_quiz_user")." where act_id=:act_id and openid =:openid",array(":act_id"=>$id,':openid'=>$_W['openid']));
        pdo_commit();
        $rightcount=empty($right['yes']) ? 0 : $right['yes'];
        $allcount=count(explode(",",$view['quiz_ids']));
        $notright=$allcount-$right['yes'];
        include $this->template("union/quiz/jinshai");
        exit();
    }

    public function start(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="知识竞赛";
        $id=intval($_GPC['id']);

        $key=empty($_GPC['key']) ? 1:intval($_GPC['key']);
        if(empty($id)){
            $this->message("非法访问");
        }
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_activity")." where id=:id",array(":id"=>$id));
        $_W['union']['title']=$view['title'];
        //检查时间
        if($view['start_time']>time()){
            $this->message("活动尚未开始",'','error');
        }
        if($view['end_time']<time()){
            $this->message("活动已经结束",'','error');
        }
        if(empty($view['quiz_ids'])){
            $this->message("题目错误联系管理员",'','error');
        }
        $sql="select * from ".tablename("ewei_shop_union_quiz")." where id in (".$view['quiz_ids'].") and  deleted=0";
        $quizs=pdo_fetchall($sql);
        if(empty($quizs)){
            $this->message("题目错误联系管理员",'','error');
        }
        if($view['type']==1){//如果题目类型是调研类型的 调研结束时间是活动的结束时间
            $view['count_times']=$view['end_time']-time();

        }
        //检查用户有没有做过这套题目
        $userstart=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_userstart")." where act_id=:act_id and openid =:openid",array(":act_id"=>$id,':openid'=>$_W['openid']));
        if(!empty($userstart)){
            if(time()-$userstart['starttime']>$view['count_times']){
                if($view['type']==0){
                    $this->jinshai();
                }else{
                    $this->showquzi();
                }

                exit();
            }
            $view['count_times']=($userstart['starttime']+$view['count_times'])-time();

        }else{
            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'openid'=>$_W['openid'],
                'starttime'=>time(),
                'act_id'=>$id,
            );
            pdo_insert("ewei_shop_union_quiz_userstart",$data);
        }

        //获取用户现在答题到了第几题

        $user_quiz=pdo_fetchall("select * from ".tablename("ewei_shop_union_quiz_user")." where openid=:openid and act_id=:act_id",array(":openid"=>$_W['openid'],':act_id'=>$id));
        if(!empty($user_quiz)){
            $key=count($user_quiz);//现在应该显示的题目
            $couts=explode(",",$view['quiz_ids']);

            if($key==count($couts)){
                if($view['type']==0){
                    $this->jinshai();
                }else{
                    $this->showquzi();
                }
                exit();
            }
            $key=$key+1;
        }

        $qu_list=pdo_fetchall("select * from ".tablename("ewei_shop_union_quiz")." where id in (".$view['quiz_ids']. ")");//
        foreach($qu_list as &$qu_info){
            $answerlist=array();
            $qus_type=1;
            if($qu_info['quiztype']==1){//是非题
                $answerlist[]=array('key'=>"c1","title"=>$this->key_list[0],'value'=>"是");
                $answerlist[]=array('key'=>"c2","title"=>$this->key_list[1],'value'=>"否");
            }else{
                //检查是单选还是多选题
                $count=explode(',',$qu_info['winning']);
                if(count($count)>1){
                    //多选题
                    $qus_type=2;
                }else{
                    $qus_type=1;//单选题
                }
            }
            if($qu_info['quiztype']==0) {//选择题
                $answerlist=array();
                $tempanswerlist=array();
                if(!empty($qu_info['c1']))$tempanswerlist['c1']=$qu_info['c1'];
                if(!empty($qu_info['c2']))$tempanswerlist['c2']=$qu_info['c2'];
                if(!empty($qu_info['c3']))$tempanswerlist['c3']=$qu_info['c3'];
                if(!empty($qu_info['c4']))$tempanswerlist['c4']=$qu_info['c4'];
                if(!empty($qu_info['c5']))$tempanswerlist['c5']=$qu_info['c5'];
                if(!empty($qu_info['c6']))$tempanswerlist['c6']=$qu_info['c6'];
                if($qu_info['quiz1']==1){//选项随机排列
                    $tempanswerlist=$this->arrayOrderBy($tempanswerlist);
                }
                $i=0;
                foreach ($tempanswerlist as $k=> $item){
                    $answerlist[]=array('key'=>$k,"title"=>$this->key_list[$i],'value'=>$item);
                    $i++;
                }
            }
            $qu_info["answerlist"]=$answerlist;
            $qu_info["qus_type"]=$qus_type;
        }
        unset($qu_info);

        include $this->template();
    }

    function user_chick(){
        global $_W;
        global $_GPC;
        if($_W['ispost']){
            pdo_begin();
            $act_id=intval($_GPC['act_id']);//
            //用户的选择
            $selected=$_GPC['selectid'];
            $quizid=intval($_GPC['quizid']);

            //检查用户有么有做过这道题
            $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_quiz_user")." where act_id=:act_id and quiz_id=:quizid and openid =:openid",array(":act_id"=>$act_id,":openid"=>$_W['openid'],":quizid"=>$quizid));

            if($count>0){
                show_json(0,'error');
            }
            $data=array(
                'openid'=>$_W['openid'],
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'act_id'=>$act_id,
                'quiz_id'=>$quizid,
                'answer'=>$selected,//用户选择
                'is_ok'=>0,
            );
            $qu_info=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz")." where id =:id",array(":id"=>$quizid));
            if($qu_info['quiztype']==1){//用户做的是非题
                $quizamswer=$qu_info['yes'];//正确答案
                if($quizamswer==0){
                    $temp_answer="c1";
                }else{
                    $temp_answer="c2";
                }
                //判断用户答题是否正确
                if($selected==$temp_answer){
                    //选择是回答正确 next
                    $data['is_ok']=1;
                }else{
                    $data['is_ok']=0;
                }
            }else{
                //用户做的选择题
                $countlist=explode(',',$qu_info['winning']);
                $temp_answer_yes=array();//全部的正确答案的ID
                foreach ($countlist as $value){
                    $temp_answer_yes[]='c'.$value;
                }
                //查看 是否是
                if(count($countlist)>1){//多选题
                    $selectidlist=explode(",",$selected);
                    $data['is_ok']=1;//默认全对
                    //选项少于正确答案的时候
                    if(count($selectidlist)!=count($temp_answer_yes)){
                        $data['is_ok']=0;
                    }
                    foreach ($selectidlist as $value){
                        if(!in_array($value,$temp_answer_yes)){//只要有一个错误答案 就算失败
                            $data['is_ok']=0;
                        }
                    }
                }else{
                    if(in_array($selected,$temp_answer_yes)){//答案在里面的
                        $data['is_ok']=1;//默认全对
                    }
                }

            }
            pdo_insert("ewei_shop_union_quiz_user",$data);
            pdo_commit();
            show_json(1,'next');
        }
    }




    private function arrayOrderBy($array=[]){
        //获取键值
        $keys = array_keys($array);
        //打乱键值
        shuffle($keys);
        $random = [];
        //数组重组
        foreach($keys as $key){
            $random[$key] = $array[$key];
        }
        return $random;
    }
}