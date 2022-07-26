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
        $condition="  where uniacid=:uniacid and union_id=:union_id  and deleted=0 ";

        if($_GPC['keywordes']!=''){

            $condition.=" and title like '%".$_GPC['keywordes']."%'";
        }


        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $sql="select * from ".tablename("ewei_shop_union_quiz").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);



        foreach ($list as &$row){
            if($row['quiztype']==0){
                $dn=array();
                $d=explode(",",$row['winning']);
                if(in_array(1,$d)){
                    $dn[]=$row['c1'];
                }
                if(in_array(2,$d)){
                    $dn[]=$row['c2'];
                }
                if(in_array(3,$d)){
                    $dn[]=$row['c3'];
                }
                if(in_array(4,$d)){
                    $dn[]=$row['c4'];
                }
                if(in_array(5,$d)){
                    $dn[]=$row['c5'];
                }
                if(in_array(6,$d)){
                    $dn[]=$row['c6'];
                }
                $row['d']=implode(',',$dn);
            }
            if($row['quiztype']==1){
                $row['d']=$row['yes']==0 ? "是" :"否";
            }
        }
        unset($row);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_quiz").$condition,$paras);
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
        $status=$this->model->checkunion();
        if($_W['ispost']){
            $start=$_GPC['start'];
            $end=$_GPC['end'];
            $signstart=$_GPC['signstart'];
            $signend=$_GPC['signend'];
            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'title'=>trim($_GPC['quiztare']),
                'quiztype'=>intval($_GPC['quiztype']),
                'quiz1'=>intval($_GPC['quiz1']),
                'c1'=>$_GPC['option'][0],
                'c2'=>$_GPC['option'][1],
                'c3'=>$_GPC['option'][2],
                'c4'=>$_GPC['option'][3],
                'c5'=>$_GPC['option'][4],
                'c6'=>$_GPC['option'][5],
                'winning'=>isset($_GPC['answers']) ? implode(",",$_GPC['answers']): "" ,
                'yes'=>intval($_GPC['yes']),
            );

            if($id){
                pdo_update("ewei_shop_union_quiz",$data,array("id"=>$id));
            }else{
                $data['create_time']=time();
                pdo_insert("ewei_shop_union_quiz",$data);
            }
            message('添加修改题目', unionUrl("quiz"), 'success');
          die();
        }
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_quiz")." where id=:id",array(":id"=>$id));
            if($vo){
                $winning=explode(",",$vo['winning']);
            }
        }

        include $this->template();
    }

    public function activity(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where uniacid=:uniacid and union_id=:union_id  and deleted=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);

        if($_GPC['category_id']!=''){
            $category_list=$this->get_categorylist($_GPC['category_id']);


            $condition.=" and category_id in (".implode(",",$category_list).")";
        }
        if($_GPC['keywordes']!=''){

            $condition.=" and title like '%".trim($_GPC['keywordes'])."%'";
        }
        $sql="select * from ".tablename("ewei_shop_union_quiz_activity").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_quiz_category') . ' WHERE 1  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");

        foreach ($list as &$value){
            $value['catename']=$categorylist[$value['category_id']]['catename'];
        }
        unset($value);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_quiz_activity").$condition,$paras);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }

    public function activitypost(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_quiz_category') . ' WHERE 1  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");

        if($_W['ispost']){
            $quizids=$_GPC['quizids'];
            $quizidscount=!empty($_GPC['quizids']) ? count(explode(',',$quizids)) :0;
            $quizids=array_unique(explode(',',$quizids));

            if($quizidscount<=0){
                $this->message("请选择一些题目");
            }
            $data=array(
                'union_id'=>$_W['unionid'],
                'uniacid'=>$_W['uniacid'],
                'title'=>$_GPC['title'],
                'start_time'=>strtotime($_GPC['start_date']),
                'end_time'=>strtotime($_GPC['end_date']),
                'quiz_ids'=>implode(",",$quizids),
                'count_times'=>intval($_GPC['count_times']),
                'status'=>intval($_GPC['status']),
                'quizcount'=>!empty($_GPC['quizids']) ? count($quizids) :0,
                'description'=>$_GPC['description'],
                'category_id'=>$_GPC['category_id'],
                'type'=>$_GPC['type'],
                'isreal'=>$_GPC['isreal'],
                'header_image'=>$_GPC['header_image'],
            );

            if(!$id){
                $data['create_time']=time();
                pdo_insert("ewei_shop_union_quiz_activity",$data);
            }else{
                pdo_update("ewei_shop_union_quiz_activity",$data,array("id"=>$id));
            }
            $this->message("添加和修改数据成功",unionUrl('quiz/activity'));
        }
        if($id){
           $vo= pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_activity")." where id=:id",array(
                ':id'=>$id
            ));
        }
        include $this->template();
    }

    public function query_list(){
        global $_W;
        global $_GPC;

        include $this->template();
    }
    public $columns=array(
        array('title' => '题目名称', 'field' => '', 'width' => 32),
        array('title' => "题目类型", 'field' => '', 'width' => 32),
        array('title' => "答案A", 'field' => '', 'width' => 32),
        array('title' => "答案B", 'field' => '', 'width' => 32),
        array('title' => "答案C", 'field' => '', 'width' => 32),
        array('title' => "答案D", 'field' => '', 'width' => 32),
        array('title' => "答案E", 'field' => '', 'width' => 32),
        array('title' => "答案F", 'field' => '', 'width' => 32),
        array('title' => "正确答案", 'field' => '', 'width' => 32),

    );
    private function get_categorylist($categoryid){
        static $categorylist=array();
        $categorylist[]=$categoryid;
        $catertoy=pdo_fetchall("select id from ".tablename("ewei_shop_union_quiz_category")." where parent_id=:id",array(":id"=>$categoryid));
        foreach ($catertoy as $cate){
            $this->get_categorylist($cate['id']);
        }
        return $categorylist;
    }

    public function import(){
        global $_W;
        global $_GPC;
        if($_W['ispost']){
            $url=$_GPC['inputxcel'];
            $data=$this->checkdata($url);

            if(is_error($data)){
                $this->model->show_json(0,$data['message']);
            }
            $this->model->show_json(1,$data['message']);
        }
        include $this->template("quiz/member_import");
    }
    private $data_array=array(
        1=>"A",
        2=>"B",
        3=>"C",
        4=>"D",
        5=>"E",
        6=>"F",
    );

    private function checkdata($url){
        global $_W;
        $len=strpos($url,"union");
        if($len===false){
            return error(-1,'文件错误');
        }
        $filename=substr($url,$len);
        $uploadfile=ATTACHMENT_ROOT .$filename;
        try{
            if(!is_file($uploadfile)){
                throw new Exception("文件错误,联系管理员");
            }
            $ext =  strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
            if($ext!='xlsx' && $ext!='xls' ){
                throw new Exception("请上传 xls 或 xlsx 格式的Excel文件");
            }
            $rows = m('excel')->importurl($uploadfile,$ext);
            if(is_error($rows)){
                throw new Exception($rows['message']);
            }

            pdo_begin();
            $indertdata=array();
            $success=0;
            $error=0;


            foreach ( m('excel')->importurl($uploadfile,$ext) as $key=> $row){
                if(empty($row[0])){
                    continue;
                }
                $title=trim($row[0]);//题目
                $quiz_type=trim($row[1]); //题目类型
                $c1=trim($row[2]);//答案1
                $c2=trim($row[3]);//答案2
                $c3=trim($row[4]);//答案3
                $c4=trim($row[5]);//答案4
                $c5=trim($row[6]);//答案5
                $c6=trim($row[7]);//答案6
                $answers=trim($row[8]);//正确答案
                if(!in_array($quiz_type,array('单选题','多选题','是非题'))){
                    throw new Exception("第".($key+2)."题目类型错误请重新选择");
                }
                $data=array(
                    'union_id'=>$_W['unionid'],
                    'uniacid'=>$_W['uniacid'],
                    'title'=>trim($title),
                    'c1'=>$c1,
                    'c2'=>$c2,
                    'c3'=>$c3,
                    'c4'=>$c4,
                    'c5'=>$c5,
                    'c6'=>$c6,

                );
                if($quiz_type=="单选题"){
                    $data['quiztype']=0;
                    if(count(explode(",",$answers))>1){
                        throw new Exception("第".($key+2)."行题目类型和答案不符合");
                    }
                    $winning=array_search(strtoupper($answers),$this->data_array);

                    if(empty($winning)){
                        throw new Exception("第".($key+2)."行题目类型和答案错误请更新表格");
                    }
                    $data['winning']=$winning;
                }elseif($quiz_type=="多选题"){
                    $answers_ids=array();
                    $data['quiztype']=0;
                    $answers=str_replace("，",",",$answers);
                  
                    $answers_array=explode(",",$answers);
                    if(count($answers_array)<=1){
                        throw new Exception("第".($key+2)."行题目类型和答案不符合");
                    }
                    $answers_array=array_map("strtoupper",$answers_array);
                    foreach ($answers_array as $v){
                        $winning=array_search($v,$this->data_array);
                        if(empty($winning)){
                            throw new Exception("第".($key+2)."行题目类型和答案错误请更新表格");
                        }
                        $answers_ids[]=$winning;
                    }
                    $data['winning']=implode(",",$answers_ids);
                }elseif($quiz_type=="是非题"){
                    $data['quiztype']=1;
                    if($answers=="是"){
                        $data['yes']=0;
                    }elseif($answers=="否"){
                        $data['yes']=1;
                    }else{
                        throw new Exception("第".($key+2)."行未能识别到答案请更新表格");
                    }
                }
                $data['create_time']=time();
               // var_dump($data);
                $ret=pdo_insert("ewei_shop_union_quiz",$data);
                if($ret){
                    $success++;
                }else{
                    $error++; 
                }
            }

            pdo_commit();
        }catch (Exception $e){
            pdo_rollback();
            return error(-1,$e->getMessage());
        }finally{
            @unlink($uploadfile);
        }

        return array("message"=>"导入成功数据".$success."失败数据".$error);
    }


    public function query(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = isset($_GPC['limit']) ? intval($_GPC['limit']) : 10;
        $condition="  where uniacid=:uniacid and union_id=:union_id  and deleted=0 ";
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);

        $keyworderlist=$_GPC['key'];
        if(!empty($keyworderlist)){

            foreach ($keyworderlist as $key=>$value){

                if($key=="title"){
                    $condition.=" and ".$key." like :".$key." ";
                    $paras[':title']="%".$value."%";
                }
            }
        }


        $sql="select * from ".tablename("ewei_shop_union_quiz").
            $condition;
        $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $list = pdo_fetchall($sql, $paras);

        foreach ($list as &$row){
            $dn=array();
            if($row['quiztype']==0){
                $d=explode(",",$row['winning']);
                if(in_array(1,$d)){
                    $dn[]=$row['c1'];
                }
                if(in_array(2,$d)){
                    $dn[]=$row['c2'];
                }
                if(in_array(3,$d)){
                    $dn[]=$row['c3'];
                }
                if(in_array(4,$d)){
                    $dn[]=$row['c4'];
                }
                if(in_array(5,$d)){
                    $dn[]=$row['c5'];
                }
                if(in_array(6,$d)){
                    $dn[]=$row['c6'];
                }
                if(is_array($dn)){
                    $row['d']=implode(',',$dn);
                }

            }
            if($row['quiztype']==1){
                $row['d']=$row['yes']==1 ? "是" :"否";
            }
            $row['quiztype']=$row['quiztype']==1 ? "是非题" :"选择题";
        }
        unset($row);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_quiz").$condition,$paras);
        echo json_encode(array('code'=>0,'msg'=>'','count'=>$total,'data'=>$list));
    }


    public function get_activity_list(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        $total=0;
        $list=array();
        if($id){
            $pindex = max(1, intval($_GPC['page']));
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
            $psize = isset($_GPC['limit']) ? intval($_GPC['limit']) : 10;
            $condition="  where uniacid=:uniacid and union_id=:union_id  and deleted=0 ";
            $vo= pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_activity")." where id=:id",array(
                ':id'=>$id
            ));

            $ids= !empty($vo['quiz_ids']) ? explode(",",$vo['quiz_ids']) : array();

            if(!$ids){
                return json_encode(array('code'=>0,'msg'=>'','count'=>$total,'data'=>$list));
            }
            $condition.=" and id in (".$vo['quiz_ids'].") ";

            $sql="select * from ".tablename("ewei_shop_union_quiz").
                $condition;
            $sql.=" order by create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
            $list = pdo_fetchall($sql, $paras);
            foreach ($list as &$row){
                $dn=array();
                if($row['quiztype']==0){
                    $d=explode(",",$row['winning']);
                    if(in_array(1,$d)){
                        $dn[]=$row['c1'];
                    }
                    if(in_array(2,$d)){
                        $dn[]=$row['c2'];
                    }
                    if(in_array(3,$d)){
                        $dn[]=$row['c3'];
                    }
                    if(in_array(4,$d)){
                        $dn[]=$row['c4'];
                    }
                    if(in_array(5,$d)){
                        $dn[]=$row['c5'];
                    }
                    if(in_array(6,$d)){
                        $dn[]=$row['c6'];
                    }
                    if(is_array($dn)){
                        $row['d']=implode(',',$dn);
                    }

                }
                if($row['quiztype']==1){
                    $row['d']=$row['yes']==1 ? "否" :"是";
                }
                $row['quiztype']=$row['quiztype']==1 ? "是非题" :"选择题";
            }
            unset($row);
            $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_quiz").$condition,$paras);
        }
        echo json_encode(array('code'=>0,'msg'=>'','count'=>$total,'data'=>$list));
    }
    //获取用户的答题记录
    public function getuser_uqiz(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        $export=intval($_GPC['export']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $view=pdo_fetch("select * from ".tablename("ewei_shop_u nion_quiz_activity")." where id=:id",array(":id"=>$id));
        if(empty($view)){
            $this->message('没有找打的答题记录','','error');
        }
        $couts=count(explode(",",$view['quiz_ids']));
        $params=array(":act_id"=>$id,":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']);
        $condition=" where qu.act_id=:act_id and qu.uniacid=:uniacid and qu.union_id=:union_id ";
        if($view['type']==0){
            $sql="select unm.name,mst.count  as countok,qu.starttime,qu.id from ".tablename("ewei_shop_union_quiz_userstart")." as qu "
                ." LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON unm.openid=qu.openid and unm.union_id=:union_id"
                ." LEFT JOIN "."(select sum(is_ok) as count,t.openid from ims_ewei_shop_union_quiz_user as t where t.act_id=:act_id GROUP BY t.openid) as mst On mst.openid=qu.openid".$condition;
            $sql.=" order by countok desc, starttime asc   ";

            if($export!=1){
                $sql.=" LIMIT ". ($pindex - 1) * $psize . ',' . $psize;
            }
        }elseif($view['type']==1){
            $sql="select unm.name,mst.count as countok,qu.starttime,qu.id from ".tablename("ewei_shop_union_quiz_userstart")." as qu "
                ." LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON unm.openid=qu.openid and unm.union_id=:union_id"
                ." LEFT JOIN "."(select count(*) as count,t.openid from ims_ewei_shop_union_quiz_user as t where t.act_id=:act_id GROUP BY t.openid) as mst On mst.openid=qu.openid".$condition;
            $sql.=" order by countok desc, starttime asc ";

            if($export!=1){
                $sql.=" LIMIT ". ($pindex - 1) * $psize . ',' . $psize;
            }
        }




        $list=pdo_fetchall($sql,$params);
        foreach ($list as $key=>$value){
            if($view['isreal']==1){
                $list[$key]['name']="xxx";

            }
            if($value['id']==0){
                unset($list[$key]);
            }else{
                $list[$key]['counts']= $couts;
                $list[$key]['starttime']= date("Y-m-d H:i:s");
            }
        }

        if ($_GPC['export'] == '1' && $view['type']==0) {

            //$pValue = mb_substr($view['title'], 0, 20,'utf-8');
            $pValue="";

            m('excel')->export($list, array(
                "title" => "答题记录导出-".$pValue . date('Y-m-d-H-i', time()),
                "columns" => array(

                    array('title' => '姓名', 'field' => 'name', 'width' => 12),
                    array('title' => '答题正确的数量', 'field' => 'countok', 'width' => 12),
                    array('title' => '题目总数量', 'field' => 'counts', 'width' => 24),
                    array('title' => '开始答题时间', 'field' => 'starttime', 'width' => 12),

                )
            ));
        }
        if ($_GPC['export'] == '1' && $view['type']==1) {
            $pValue = mb_substr($view['title'], 0, 20,'utf-8');
            $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
            $columns=array(
                array('title' => '姓名', 'field' => 'name', 'width' => 12),
                array('title' => '电话', 'field' => 'mobile', 'width' => 12),
            );
            $condition="  where uniacid=:uniacid and union_id=:union_id  and deleted=0 ";
            $condition.=" and id in (".$view['quiz_ids'].") ";
            $sql="select * from ".tablename("ewei_shop_union_quiz").
                $condition;
            $qulist = pdo_fetchall($sql,$paras);
            //查询有几个人参与答题了
            $paras[':act_id']=$view['id'];
            $sql=" select unm.name,unm.mobile_phone,quser.act_id,unm.openid from ".tablename("ewei_shop_union_quiz_userstart")." as quser ".
            " LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON unm.openid=quser.openid and unm.union_id=:union_id where 1 ".
            " and quser.act_id=:act_id and quser.uniacid =:uniacid and quser.union_id=:union_id ";
            $readmembers = pdo_fetchall($sql,$paras);

            $exportlist=array();
            foreach ($readmembers as $key=> $member){
                $paras[':openid']=$member['openid'];
                $memebr_quilist=pdo_fetchall("select * from ".tablename("ewei_shop_union_quiz_user")." where 1 and uniacid =:uniacid and union_id=:union_id and openid=:openid and act_id=:act_id",$paras,'quiz_id');
                $readmembers[$key]['quizs']=$memebr_quilist;
                $memberlistquiz=array(
                    'name'=>$member['name'],
                    'mobile'=>$member['mobile_phone'],
                );
                if($view['isreal']==1){
                    $memberlistquiz=array(
                        'name'=>"xxxx",
                        'mobile'=>"1xxxxxxxx",
                    );
                }
                foreach ($qulist as $k=> $quize){
                    $anwser='';
                    if(isset($memebr_quilist[$quize['id']]) && isset($memebr_quilist[$quize['id']]['quiz_id']) && $memebr_quilist[$quize['id']]['quiz_id']==$quize['id']){

                        if($quize['quiztype']==1){//判断题
                            $anwser= $memebr_quilist[$quize['id']]['answer']=="c1" ? "是" : "否";
                        }else{
                            $anwserlist=explode(",",$memebr_quilist[$quize['id']]['answer']);
                            foreach ($anwserlist as $a){
                                $anwser.=$this->get_answer($a,$quize)." ";
                            }
                        }
                        $memberlistquiz['anwser_'.$k]=$anwser;
                    }else{
                        $memberlistquiz['anwser_'.$k]='未答';
                    }

                }

                $exportlist[]=$memberlistquiz;
            }
            foreach ($qulist as $key=> $value){
                $columns[]=array("title"=>$value['title'],'field' => 'anwser_'.$key, 'width' => 12);
            }



            m('excel')->export($exportlist, array(
                "title" => "答题记录导出-".$pValue,
                'columns'=>$columns,
            ));

        }

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_quiz_userstart")." as qu".$condition,$params);
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }

    private function get_answer($type,$quize){

        switch ($type){
            case "c1":
                return "A:".$quize[$type];
            case "c2":
                return "B:".$quize[$type];
            case "c3":
                return "C:".$quize[$type];
            case "c4":return "D:".$quize[$type];
            case "c5":return "E:".$quize[$type];
            case "c6":return "F:".$quize[$type];
        }
    }


    public  function deletequizlog(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
       $userstart_info= pdo_fetch("select * from ".tablename("ewei_shop_union_quiz_userstart")." where id=:id and uniacid=:uniacid and union_id=:union_id",array(":id"=>$id,":uniacid"=>$_W['uniacid'],':union_id'=>$_W['unionid']));
        $sql="select * from ".tablename("ewei_shop_union_quiz_user")." where act_id=:act_id and openid=:openid and uniacid=:uniacid";
        $userloglist=pdo_fetchall($sql,array(":act_id"=>$userstart_info['act_id'],":openid"=>$userstart_info['openid'],":uniacid"=>$_W['uniacid']));
        $ret=true;
        pdo_begin();
        if($userloglist){
            $ret= pdo_delete("ewei_shop_union_quiz_user",array("act_id"=>$userstart_info['act_id'],"openid"=>$userstart_info['openid'],"uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid']));
        }
        $dret=pdo_delete("ewei_shop_union_quiz_userstart",array("id"=>$userstart_info['id']));
        if($ret && $dret){
            pdo_commit();
            $this->model->show_json(1,"删除成功");
        }else{
            pdo_rollback();
            $this->model->show_json(0,"删除失败");
        }



    }


}