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


        $menu=pdo_fetchall("select * from ".tablename("ewei_shop_union_menu")." where uniacid=:uniacid and union_id=:unionid",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid']));

        array_multisort(array_column($menu,"displayorder"),SORT_DESC, $menu);
        include $this->template();
    }
    function post(){
        global $_W;
        global $_GPC;
        $menu=pdo_fetchall("select * from ".tablename("ewei_shop_union_menu")." where uniacid=:uniacid and union_id=:unionid",array(":uniacid"=>$_W['uniacid'],":unionid"=>$_W['unionid']));

        foreach ($menu as $item){
            if(!empty($item['menutype'])){
                $type_displayorder=$item['menutype']."_displayorder";
                $statusname=$item['menutype'].'_status';
                $status=isset($_GPC[$statusname]) && $_GPC[$statusname]=="on" ? 1 :0;
                $displayorder=$_GPC[$type_displayorder];
                $updatedata=array(
                    'title'=>$_GPC[$item['menutype']],
                    'displayorder'=>$displayorder,
                    'status'=>$status,

                );
               pdo_update('ewei_shop_union_menu',$updatedata,array("id"=>$item['id']));
            }
        }
        $this->model->show_json(1,"数据修改成功");
    }

    function addmenupost(){
        global $_W;
        global $_GPC;

        if($_W['ispost']){
            $id=intval($_GPC['id']);
            $data=array(
                'displayorder'=>intval($_GPC['displayorder']),
                'icon_url'=>trim($_GPC['images']),
                'status'=>intval($_GPC['status']),
                'webstatus'=>intval($_GPC['webstatus']),
                'uniacid'=>$_W['uniacid'],
                'union_id'=>$_W['unionid'],
                'title'=>trim($_GPC['menuname']),
            );
            $modeltype=intval($_GPC['modeltype']);
            $data['menutype']=$modeltype;
            if($modeltype==0){//选择的文章模型
                //查询用户选择的文章分类
                $catearray=$_GPC['cateidarticle'];
                $catearray=array_filter($catearray);

                $data['cateid']=implode(',',$catearray);
                $cateid = end($catearray);
                if(empty($cateid) && empty($id)){
                    $this->model->show_json(0,'未选择分类无法生成链接');
                }
                if(!empty($cateid)){
                    $data['link_url']=mobileUrl("union/document",array("id"=>$cateid));
                }
            }
            elseif($modeltype==1){//选择的建言模型
                $data['link_url']=mobileUrl("union/suggestions");
            }
            elseif($modeltype==2){//选择的场馆模型
                $catearray=$_GPC['venuecateid'];
                $catearray=array_filter($catearray);
                $data['cateid']=implode(',',$catearray);
                $cateid = end($catearray);
                if(empty($cateid) && empty($id)){
                    $this->model->show_json(0,'未选择分类无法生成链接');
                }
                if(!empty($cateid)){
                    $data['link_url']=mobileUrl("union/venue",array("id"=>$cateid));
                }
            }
            elseif($modeltype==3){//选择的联谊模型
                $data['link_url']=mobileUrl("union/friendship");
            }
            elseif($modeltype==4){//选择的活动模型
                $catearray=$_GPC['activitycateid'];
                $catearray=array_filter($catearray);
                $data['cateid']=implode(',',$catearray);
                $cateid = end($catearray);
                if(empty($cateid) && empty($id)){
                    $this->model->show_json(0,'未选择分类无法生成链接');
                }
                if(!empty($cateid)){
                    $data['link_url']=mobileUrl("union/memberactivity",array("id"=>$cateid));
                }
            }elseif($modeltype==5){//福利模块
                $data['link_url']=mobileUrl("union/welfareindex");
            }
            elseif($modeltype==6){//知识竞赛模块

                $catearray=$_GPC['quizcateid'];
                $catearray=array_filter($catearray);
                $data['cateid']=implode(',',$catearray);
                $cateid = end($catearray);
                if(empty($cateid) && empty($id)){
                    $this->model->show_json(0,'未选择分类无法生成链接');
                }
                if(!empty($cateid)){
                    $data['link_url']=mobileUrl("union/quiz",array("id"=>$cateid));
                }
            }elseif($modeltype==7){//协会模块
                $data['link_url']=mobileUrl("union/association");
            }elseif($modeltype==8){
                $data['link_url']=mobileUrl("union/report/show");
            }elseif($modeltype==9){
                $data['link_url']=mobileUrl("union/vote");
            }
            elseif($modeltype==10){
                $data['link_url']=$_GPC['link_url'];
            }
            if($id){
                pdo_update("ewei_shop_union_menu",$data,array("id"=>$id));
            }else{
                pdo_insert("ewei_shop_union_menu",$data);
            }
            $this->model->show_json(1,'添加修改菜单成功');
        }
    }
    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        $status=pdo_delete("ewei_shop_union_menu",array("id"=>$id,'uniacid'=>$_W['uniacid'],'union_id'=>$_W['unionid']));

        if($status){
            $this->model->show_json(1,'删除成功');
        }
        $this->model->show_json(1,'删除失败,请重试');
    }
    function addmenu(){
        global $_W;
        global $_GPC;
        //查询现有可用分类
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        $id=intval($_GPC['id']);
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_menu")." where id=:id and union_id=:union_id and uniacid=:uniacid",array(":id"=>$id,':union_id'=>$_W['unionid'],":uniacid"=>$_W['uniacid']));
            if($vo['menutype']==0){//文章类
                $vo['cateid']=explode(",",$vo['cateid']);
                $level1cateid=$vo['cateid'][0];
                $level2cateid=isset($vo['cateid'][1]) ? $vo['cateid'][1] :0;
                $level3cateid=isset($vo['cateid'][2]) ? $vo['cateid'][2] :0;

                if($level1cateid>0){
                    $params[':parentid']=$level1cateid;
                    $level2=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1  and enable=1 and parent_id=:parentid and level=2 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }
                if($level2cateid>0){
                    $params[':parentid']=$level2cateid;
                    $level3=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1  and enable=1 and parent_id=:parentid  and level=3 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }
                unset($params[':parentid']);
            }elseif($vo['menutype']==2){//场馆模块
                $vo['cateid']=explode(",",$vo['cateid']);
                $venuecategorylevel1cateid=$vo['cateid'][0];
                $venuecategorylevel2cateid=isset($vo['cateid'][1]) ? $vo['cateid'][1] :0;
                $venuecategorylevel3cateid=isset($vo['cateid'][2]) ? $vo['cateid'][2] :0;

                if($venuecategorylevel1cateid>0){
                    $params[':parentid']=$venuecategorylevel1cateid;
                    $venuecategorylevel2=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_venue_category') . ' WHERE 1  and enable=1 and parent_id=:parentid and level=2 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }
                if($venuecategorylevel2cateid>0){
                    $params[':parentid']=$venuecategorylevel2cateid;
                    $venuecategorylevel3=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_venue_category') . ' WHERE 1  and enable=1 and parent_id=:parentid  and level=3 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }

                unset($params[':parentid']);
            }else if($vo['menutype']==4){
                $vo['cateid']=explode(",",$vo['cateid']);
                $memberactivitylevel1cateid=$vo['cateid'][0];
                $memberactivitylevel2cateid=isset($vo['cateid'][1]) ? $vo['cateid'][1] :0;
                $memberactivitylevel3cateid=isset($vo['cateid'][2]) ? $vo['cateid'][2] :0;

                if($memberactivitylevel1cateid>0){
                    $params[':parentid']=$memberactivitylevel1cateid;
                    $memberactivitylevel2=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1  and enable=1 and parent_id=:parentid and level=2 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }
                if($memberactivitylevel2cateid>0){
                    $params[':parentid']=$memberactivitylevel2cateid;
                    $memberactivitylevel3=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1  and enable=1 and parent_id=:parentid  and level=3 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }
                unset($params[':parentid']);
            }else if($vo['menutype']==6){
                $vo['cateid']=explode(",",$vo['cateid']);
                $quizlevel1cateid=$vo['cateid'][0];
                $quizlevel2cateid=isset($vo['cateid'][1]) ? $vo['cateid'][1] :0;
                $quizlevel3cateid=isset($vo['cateid'][2]) ? $vo['cateid'][2] :0;

                if($quizlevel1cateid>0){
                    $params[':parentid']=$quizlevel1cateid;
                    $quizcategorylevel2=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_quiz_category') . ' WHERE 1  and enable=1 and parent_id=:parentid and level=2 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }
                if($quizlevel2cateid>0){
                    $params[':parentid']=$quizlevel2cateid;
                    $quizcategorylevel3=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_quiz_category') . ' WHERE 1  and enable=1 and parent_id=:parentid  and level=3 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
                }

                unset($params[':parentid']);
            }
        }
        //文章类的
        $categorylist = pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
        $categorylist= $this->getLeaderArray($categorylist);
        $jsonstr=json_encode($categorylist);
        //活动分类
        $memberactivitycategorylist = pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_memberactivity_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
        $memberactivitycategorylist= $this->getLeaderArray($memberactivitycategorylist);
        $memberactivityjsonstr=json_encode($memberactivitycategorylist);
        //场馆分类
        $venuecategorylist=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_venue_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
        $venuecategorylist= $this->getLeaderArray($venuecategorylist);
        $venuecategorylistjsonstr=json_encode($venuecategorylist);
        //知识竞赛类
        $quizcategorylist=pdo_fetchall('SELECT id,parent_id,catename,level FROM ' . tablename('ewei_shop_union_quiz_category') . ' WHERE 1  and enable=1 and union_id=:union_id and uniacid=:uniacid  ORDER BY parent_id asc ', $params,"id");
        $quizcategorylist= $this->getLeaderArray($quizcategorylist);
        $quizcategorylistjsonstr=json_encode($quizcategorylist);

        if($id && $level1cateid>0){
            $level2find=$this->findPid($level1cateid,$categorylist);
            $level2children=$level2find['children'];//第二级
            $level2children=json_encode($level2children);
        }
        if($id && $memberactivitylevel1cateid>0){
            $activitycategorylevel2find=$this->findPid($memberactivitylevel1cateid,$memberactivitycategorylist);
            $activitycategorylevel2children=$activitycategorylevel2find['children'];//第二级
            $activitycategorylevel2children=json_encode($activitycategorylevel2children);
        }
        if($id && $venuecategorylevel1cateid>0){
            $vuenucategorylevel2find=$this->findPid($venuecategorylevel1cateid,$venuecategorylist);
            $vuenucategorylevel2children=$vuenucategorylevel2find['children'];//第二级
            $vuenucategorylevel2children=json_encode($vuenucategorylevel2children);
        }
        if($id && $quizlevel1cateid>0){
            $quizcategorylevel2find=$this->findPid($quizlevel1cateid,$quizcategorylist);

            $quizcategorylevel2children=$quizcategorylevel2find['children'];//第二级
            $quizcategorylevel2children=json_encode($quizcategorylevel2children);
        }



        include $this->template();
    }


    //在数组中查找指定的id
    function  findPid ( $pid = 1 , & $arr = array() ,$boo = false ,$a =array()  )
    {

        if( is_array( $arr ) )
        {
            foreach ( $arr as $k=>  $v )
            {

                if (  $v['id'] == $pid )
                {

                    if( ! $boo )
                    {
                        //$boo是false表示只找
                        return $arr[$k];
                    }
                    else
                    {

                        if( isset( $arr[$k]['children'] )  )
                        {
                            //有子类型
                            $arr[$k]['children'][] = $a   ;
                        }
                        else
                        {
                            //没有子类型
                            $arr[$k]['children'] = array()   ;
                            $arr[$k]['children'][] = $a   ;
                        }

                        return true;
                    }
                }
                else
                {
                    if( isset( $v['children'] ) )
                    {

                        $this->findPid( $pid , $arr[$k]['children'] ,$boo ,$a);//递归
                    }

                }
            }
        }
        else
        {

            return false;
        }
    }

    function   getLeaderArray( $array = array() )
    {
        $leaderArray = array ();
        if( is_array( $array )  )
        {
            //必须是数组
            foreach ( $array as $k=> $v  )
            {
                if( $v['parent_id'] == 0 )
                {

                    //顶层数组保留
                    $leaderArray[] = $v ;
                }
                else
                {

                    //否则要放到其父类型的'sub'属性里面
                    if( $this->findPid( $v['parent_id'] , $leaderArray  , true , $array[$k]  ))//找到父类型添加进父类型或者没找到
                    {
                        //子类型添加完成
                    }
                    else
                    {
                    }
                }
            }
            return $leaderArray;
        }
        else
        {
            return $array;
        }
    }
    private function init(){
         $menu=array(
             array(
                 'title'=>"工会公文",
                 'menutype'=>"document",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_office.png",
             ),
            array(
                'title'=>"工会动态",
                'menutype'=>'dynamic',
                'displayorder'=>0,
                'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_state.png",
            ),
             array(
                 'title'=>" 职工风采",
                 'menutype'=>"personnelmien",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_staff.png",
             ),
             array(
                 'title'=>"建言献策",
                 'menutype'=> "suggestions",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_suggest.png",
             ),
             array(
                 'title'=>"活动报名",
                 'menutype'=>"memberactivity",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_apply.png",
             ),
             array(
                 'title'=>"培训报名",
                 'menutype'=> 'train',
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_train.png",
             ),
             array(
                 'title'=>"知识竞赛",
                 'menutype'=> 'quiz',
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_oppetition.png",
             ),
             array(
                 'title'=>"兴趣小组",
                 'menutype'=>"association",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_assocation.png",
             ),
             array(
                 'title'=>"场馆预订",
                 'menutype'=> "venue",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_reserve.png",
             ),
             array(
                 'title'=>"疗休养",
                 'menutype'=>'recuperation',
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_culture.png",
             ),
             array(
                 'title'=>"单身联谊",
                 'menutype'=> "friendship",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_followship.png",
             ),
             array(
                 'title'=>"公益扶贫",
                 'menutype'=> "poor",
                 'displayorder'=>0,
                 'icon_image_url'=>"../addons/ewei_shopv2/plugin/union/template/mobile/default/static/images/member/ic_provety.png",
             ),






        );
         return $menu;
    }
}