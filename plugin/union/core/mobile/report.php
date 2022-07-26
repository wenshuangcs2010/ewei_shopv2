<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Report_EweiShopV2Page extends LyMobilePage
{
    public function main(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=intval($_GPC['union_id']);
        if(empty($union_id)){
            $union_id=$_W['unionid'];
        }
        $_W['union']['title']="签到";
        if(empty($id)){
            $this->message("签到数据未找到");
        }
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$union_id,
            ':id'=>$id,
        );
        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_report")." where id=:id and union_id=:union_id and uniacid=:uniacid",$params);
        $memberinfo=$this->model->get_union_member_info($_W['openid']);

        if(empty($vo)){
            $this->message("签到活动未开启");
        }
        $union_info=$this->model->get_union_info($vo['union_id']);
        $params[':openid']=$_W['openid'];
        if($vo['sign_type']==0){
            //查询当前用户有没有签到
            $sign_info=pdo_fetch("select * from ".tablename("ewei_shop_union_report_sign")." where uniacid=:uniacid and union_id=:union_id and report_id=:id and openid=:openid ",$params);

        }else{
           $startTimes= strtotime(date("Y-m-d 00:00:00"));
           $endTimes=strtotime(date("Y-m-d 23:59:59"));
            $params[':startTimes']=$startTimes;
            $params[':endtime']=$endTimes;
            //今天是否有签到
            $sign_info=pdo_fetch("select * from ".tablename("ewei_shop_union_report_sign")
                ." where uniacid=:uniacid and union_id=:union_id and report_id=:id and openid=:openid  and createtime>:startTimes and createtime<=:endtime",$params);

        }

        if(!empty($sign_info)){
            header('location: ' . mobileUrl("union/report/sign_list",array("id"=>$id,'union_id'=>$union_id)));
        }

                $_W['shopshare']['hideMenus'] = array('menuItem:share:qq', 'menuItem:share:QZone', 'menuItem:share:email');

                $_W['shopshare']['hideMenus'][] = 'menuItem:copyUrl';
                $_W['shopshare']['hideMenus'][] = 'menuItem:openWithSafari';
                $_W['shopshare']['hideMenus'][] = 'menuItem:openWithQQBrowser';


                $_W['shopshare']['hideMenus'][] = 'menuItem:share:timeline';


                $_W['shopshare']['hideMenus'][] = 'menuItem:share:appMessage';

        include $this->template();
    }

    public function show(){
        global $_W;
        global $_GPC;
        $_W['union']['title']="签到活动";
        $startTimes= strtotime(date("Y-m-d 00:00:00"));
        $endTimes=strtotime(date("Y-m-d 23:59:59"));
        $member=$this->model->get_union_member_info($_W['openid']);
        $union_id=$member['union_id'];
        $member_id=$member['id'];
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$union_id,
           // ":times"=>time(),
        );

        $sql="select * from ".tablename("ewei_shop_union_report")." where  union_id=:union_id and uniacid=:uniacid  "
            . " and (show_type=0 or (show_type=1 and find_in_set({$member_id},peoplevale)))"
            ." order by endtime desc,create_time desc ";
        $list=pdo_fetchall($sql,$params);

        foreach ($list as &$item){
            $item['sign_in']=0;
            if($item['has_points']==1 && !empty($_W['openid'])){
                $params_sign=array(
                    ':uniacid'=>$_W['uniacid'],
                    ':union_id'=>$union_id,
                    ':id'=>$item['id'],
                    ':startTimes'=>$startTimes,
                    ':endtime'=>$endTimes,
                    ':openid'=>$_W['openid'],
                );
                $sign_info=pdo_fetch("select * from ".tablename("ewei_shop_union_report_sign")
                    ." where uniacid=:uniacid and union_id=:union_id and report_id=:id and openid=:openid  and createtime>:startTimes and createtime<=:endtime",$params_sign);
                if(!empty($sign_info)){
                    $item['sign_in']=1;
                }
            }
        }
        unset($item);

        $_W['shopshare']['hideMenus'] = array('menuItem:share:qq', 'menuItem:share:QZone', 'menuItem:share:email');
        $_W['shopshare']['hideMenus'][] = 'menuItem:copyUrl';
        $_W['shopshare']['hideMenus'][] = 'menuItem:openWithSafari';
        $_W['shopshare']['hideMenus'][] = 'menuItem:openWithQQBrowser';
        $_W['shopshare']['hideMenus'][] = 'menuItem:share:timeline';
        $_W['shopshare']['hideMenus'][] = 'menuItem:share:appMessage';
        include $this->template();
    }
    public function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=intval($_GPC['union_id']);
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ":id"=>$id,
        );
        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_report")." where id=:id and uniacid=:uniacid",$params);
        $unionid=$view['union_id'];

        $_W['union']['title']=$view['title'];
        $this->model->readmember_insert($_W['openid'],4,$view['id']);
        $readmember= $this->model->readcount(4,$view['peoplevale'],$view['id']);

        if(isset($view['peoplevale']) && !empty($view['peoplevale'])){
            $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and id in(".$view['peoplevale'].") and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$unionid));

        }else{
            $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$unionid));
        }
        $notreadcount=$allcount-$readmember['count'];

        if($view['has_points']){
            $params=array(
                ":openid"=>$_W['openid'],
               // ':starttime'=>

            );
            $credit=pdo_fetchcolumn("select sum(credit) from ".tablename("ewei_shop_union_report_credit")." where openid=:openid ",$params);
        }

        $_W['shopshare'] = array(
            'title' =>$view['title'],
            'imgUrl' => tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $view['title'],
            'link' => mobileUrl('union/report/view',array('id'=>$id,'union_id'=>$union_id),true)
        );
        include $this->template();
    }
    function sign_list(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=intval($_GPC['union_id']);
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$union_id,
            ':id'=>$id,
        );

        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_report")." where id=:id and union_id=:union_id and uniacid=:uniacid",$params);
        $_W['union']['title']=$vo['title'];
        $params[':openid']=$_W['openid'];
         if($vo['sign_type']==0){
             $sign_info=pdo_fetch("select * from ".tablename("ewei_shop_union_report_sign")." where uniacid=:uniacid and union_id=:union_id and report_id=:id and openid=:openid ",$params);
         }else{
             $startTimes= strtotime(date("Y-m-d 00:00:00"));
             $endTimes=strtotime(date("Y-m-d 23:59:59"));
             $params[':startTimes']=$startTimes;
             $params[':endtime']=$endTimes;
             //今天是否有签到
             $sign_info=pdo_fetch("select * from ".tablename("ewei_shop_union_report_sign")
                 ." where uniacid=:uniacid and union_id=:union_id and report_id=:id and openid=:openid  and createtime>:startTimes and createtime<=:endtime",$params);
         }

        if($_W['ispost']){
            if(time()<$vo['starttime']){
                show_json(0,'还未到签到时间');
            }
            if(time()>$vo['endtime']){
                show_json(0,'签到时间已经结束');
            }
            //查询当前用户有没有签到
            if(!empty($sign_info)){
                show_json(0,'您已经签到过了,请勿重复签到');
            }
            try{
                pdo_begin();
                $data=array(
                    'uniacid'=>$_W['uniacid'],
                    'union_id'=>$union_id,
                    'report_id'=>$vo['id'],
                    'openid'=>$_W['openid'],
                    'createtime'=>time(),
                    'name'=>trim($_GPC['username']),
                    'mobile'=>trim($_GPC['mobile'])
                );
                pdo_insert("ewei_shop_union_report_sign",$data);
                $data_id=pdo_insertid();
                if($data_id && $vo['has_points']==1){
                    //赠送积分
                    $card_id=$this->model->setCredit($_W['openid'],$vo);
                }
                pdo_commit();
            }catch (Exception $e){
                pdo_rollback();
                show_json(0,'签到失败');
            }




            show_json(1,'签到成功');
        }


        $_W['shopshare']['hideMenus'] = array('menuItem:share:qq', 'menuItem:share:QZone', 'menuItem:share:email');
        $_W['shopshare']['hideMenus'][] = 'menuItem:copyUrl';
        $_W['shopshare']['hideMenus'][] = 'menuItem:openWithSafari';
        $_W['shopshare']['hideMenus'][] = 'menuItem:openWithQQBrowser';
        $_W['shopshare']['hideMenus'][] = 'menuItem:share:timeline';
        $_W['shopshare']['hideMenus'][] = 'menuItem:share:appMessage';
        include $this->template();
    }

    function getmemberlist(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $union_id=intval($_GPC['union_id']);
        if(empty($union_id)){
            $union_id=$_W['unionid'];
        }
        $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
        $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 12;
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':union_id'=>$union_id,
            ':id'=>$id,
        );
        $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_report")." where id=:id and union_id=:union_id and uniacid=:uniacid",$params);

        if($vo['sign_type']==0){
            $params=array(
                ':uniacid'=>$_W['uniacid'],
                ':union_id'=>$union_id,
                ':id'=>$id,
            );
            $sql="select s.*,dep.name as dename from ".tablename("ewei_shop_union_report_sign")." as s "
                ."LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON s.openid=unm.openid and unm.union_id=:union_id "
                ."LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON dep.id=unm.department  "
                ."  where s.uniacid=:uniacid and s.union_id=:union_id and report_id=:id";


            $countsql="select count(*) from ".tablename("ewei_shop_union_report_sign")." as s where s.uniacid=:uniacid and s.union_id=:union_id and report_id=:id";
            $total = pdo_fetchcolumn($countsql,$params);
            $list = pdo_fetchall($sql, $params);

            foreach ($list as $key=> $row){
                $list[$key]['createtime']=date("Y-m-d H:i:s",$row['createtime']);
                $list[$key]['index']=($page-1)*$pagesize+$key+1;
            }
        }else{
            $startTimes= strtotime(date("Y-m-d 00:00:00"));
            $endTimes=strtotime(date("Y-m-d 23:59:59"));

            $params=array(
                ':uniacid'=>$_W['uniacid'],
                ':union_id'=>$union_id,
                ':id'=>$id,
            );
            $params[':startTimes']=$startTimes;
            $params[':endtime']=$endTimes;
            $sql="select s.*,dep.name as dename from ".tablename("ewei_shop_union_report_sign")." as s "
                ."LEFT JOIN ".tablename("ewei_shop_union_members")." as unm ON s.openid=unm.openid and unm.union_id=:union_id "
                ."LEFT JOIN ".tablename("ewei_shop_union_department")." as dep ON dep.id=unm.department  "
                ."  where s.uniacid=:uniacid and s.union_id=:union_id and report_id=:id  and createtime>=:startTimes and createtime<=:endtime";
            $countsql="select count(*) from ".tablename("ewei_shop_union_report_sign")." as s where s.uniacid=:uniacid and s.union_id=:union_id and report_id=:id and createtime>=:startTimes and createtime<=:endtime";
            $total = pdo_fetchcolumn($countsql,$params);
            $list = pdo_fetchall($sql, $params);

            foreach ($list as $key=> $row){
                $list[$key]['createtime']=date("Y-m-d H:i:s",$row['createtime']);
                $list[$key]['index']=($page-1)*$pagesize+$key+1;
            }

        }



        show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $pagesize));

    }
}
?>