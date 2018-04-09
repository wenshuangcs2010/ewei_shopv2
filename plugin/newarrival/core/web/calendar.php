<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN.'seckill/core/seckill_page_web.php';
class Calendar_EweiShopV2Page extends SeckillWebPage  {

    function main() {
        global $_W;

        $currentyear = date('Y');
        $currentmonth = date('m');

        $years = array();
        for($i=0;$i<=10;$i++){
            $years[] = $currentyear + $i;
        }

        $months = array();
        for($i=1;$i<=12;$i++){
            $months[] = $i;
        }


        include $this->template();
    }
    function dates(){
        global $_W,$_GPC;
        $redis_prefix = $this->model->get_prefix();
        $year = trim($_GPC['year']);
        $month = trim($_GPC['month']);
        $day  = get_last_day($year,$month);
        $calendar = redis()->hGetAll("{$redis_prefix}calendar_{$year}_{$month}");

        if(empty($calendar)){
            $calendar = array();
            for($i=1;$i<=$day;$i++){
                if($i<10){
                    $i='0'.$i;
                }
                $calendar[ date("{$year}-{$month}-{$i}") ] = false;
            }
        }else{
            $result = array();

            for($i=1;$i<=$day;$i++){
                if($i<10){
                    $i='0'.$i;
                }
                $date  = "{$year}-{$month}-{$i}";
                $result[$date] = false;
                if(isset($calendar[ $date ] )){
                    $value =trim($calendar[ $date ]);
                    $result[ $date ] = false;
                    if(!empty($value)){
                        $result[ $date ] =
                            array(
                                'taskid'=>$value,
                                'title'=>pdo_fetchcolumn('select title from '.tablename('ewei_shop_seckill_task').' where id=:id limit 1',array(':id'=>$value))
                            );

                    }
                }
            }
            $calendar = $result;
        }

        $week = date('w', strtotime(date("{$year}-{$month}-1") ));
        include $this->template();

    }

    function set(){
        global $_W,$_GPC;

        $taskid = intval($_GPC['taskid']);
        $date = trim($_GPC['date']);

        if(empty($taskid) || empty($date)){
            show_json(0, "参数错误" );
        }
        $redis_prefix = $this->model->get_prefix();
        $time =strtotime($date);
        $year = date('Y',$time);
        $month = date('m',$time);

        $task =pdo_fetch('select id ,title from '.tablename('ewei_shop_seckill_task').' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$taskid));
        if(empty($task)){
            show_json(0, "任务未找到" );
        }
        $rl=redis()->hSet("{$redis_prefix}calendar_{$year}_{$month}" , date('Y-m-d',$time), $taskid);
        show_json(1,array('taskid'=>$task['id'],'title'=>$task['title']));
    }

    function delete(){
        global $_W,$_GPC;
        $date = trim($_GPC['date']);
        if( empty($date)){
            show_json(0, "参数错误" );
        }
        $time =strtotime($date);
        $year = date('Y',$time);
        $month = date('m',$time);
        $redis_prefix = $this->model->get_prefix();
        redis()->hDel("{$redis_prefix}calendar_{$year}_{$month}" , $date);
        show_json(1);
    }

    function clear(){
        global $_W,$_GPC;
        $year = trim($_GPC['year']);
        $month = trim($_GPC['month']);
        if($month < 10) $month = "0".$month;
        $redis_prefix = $this->model->get_prefix();
        redis()->delete("{$redis_prefix}calendar_{$year}_{$month}");
        show_json(1);
    }
    function batch_set(){


        global $_W,$_GPC;

        $taskid = intval($_GPC['taskid']);
        $year = trim($_GPC['year']);
        $month = trim($_GPC['month']);
        if($month < 10) $month = "0".$month;
        $days = $_GPC['days'];


        if(empty($taskid) || empty($year) || empty($month)){
            show_json(0, "参数错误" );
        }
        if(!is_array($days) || empty($days)){
            show_json(0, "参数错误" );
        }
        $task =pdo_fetch('select id ,title from '.tablename('ewei_shop_seckill_task').' where uniacid=:uniacid and id=:id limit 1',array(':uniacid'=>$_W['uniacid'],':id'=>$taskid));
        if(empty($task)){
            show_json(0, "任务未找到" );
        }

        if($days[0]=='all'){
            array_shift($days);
        }
        $maxday  = get_last_day($year,$month);
        $arr = array();
        $dates = array();
        for($i=1;$i<=$maxday;$i++){
            if($i<10){
                $i= '0'.$i;
            }
            $date = date("{$year}-{$month}-{$i}") ;
            $week = date('w',strtotime($date));
            if($week==0){
                $week =  7;
            }
            if( in_array($week , $days)){
                $arr[$date ] = $taskid;
                $dates[] = $date;
            }
        }
        $redis_prefix = $this->model->get_prefix();
        redis()->hMset("{$redis_prefix}calendar_{$year}_{$month}" , $arr);


        show_json(1,array('taskid'=>$task['id'],'title'=>$task['title'],'dates'=>implode(',',$dates)));

    }


    function batch_delete(){
        global $_W,$_GPC;
        $year = trim($_GPC['year']);
        $month = trim($_GPC['month']);
        if($month < 10) $month = "0".$month;
        $days = $_GPC['days'];
        if(empty($year) || empty($month)){
            show_json(0, "参数错误" );
        }
        if(!is_array($days) || empty($days)){
            show_json(0, "参数错误" );
        }

        if($days[0]=='all'){
            array_shift($days);
        }
        $redis = redis();
        $redis_prefix = $this->model->get_prefix();
        $calendar = $redis->hGetAll("{$redis_prefix}calendar_{$year}_{$month}");
        if(!is_array($calendar)){
            $calendar = array();
        }
        $maxday  = get_last_day($year,$month);

        $dates = array();
        for($i=1;$i<=$maxday;$i++){
            if($i<10){
                $i= '0'.$i;
            }
            $date = date("{$year}-{$month}-{$i}") ;
            $week = date('w',strtotime($date));
            if($week==0){
                $week =  7;
            }
            if( in_array($week , $days)){
                if(is_array($calendar) && isset($calendar[$date])){
                    unset($calendar[$date]);
                    $redis->hDel("{$redis_prefix}calendar_{$year}_{$month}" , $date);
                    $dates[] = $date;
                }
            }
        }
        if(empty($calendar)){
            $redis->delete("{$redis_prefix}calendar_{$year}_{$month}");
        }
        show_json(1,array('dates'=>implode(',',$dates)));


    }

}
