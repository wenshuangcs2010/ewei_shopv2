<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Lyaddressline_EweiShopV2Page extends LyMobilePage
{
    public function main(){
        global $_W;
        global $_GPC;
        //获取幻灯片

        $theme=$this->model->themeonline;
        $traffic=$this->model->traffic;
        //目的地
        $all=pdo_fetchall("select `position`,dayvale from ".tablename("ewei_shop_union_ly_addressline")." where  deleted=0 and enabled=0");
        $citylist=array();
        $dayvalelist=array();
        foreach ($all as $city){
            $citylist[]=$city['position'];
            $dayvalelist[]=$city['dayvale'];
        }
        if(!empty($citylist)){
            $citylist= array_unique($citylist);
            $dayvalelist=array_unique($dayvalelist);
        }
        if($_GPC['addressid']){
            $addressid=intval($_GPC['addressid']);
        }

        include $this->template();
    }
    function get_list(){
        global $_W,$_GPC;
        $openid =$_W['openid'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 50;
        $show_status = $_GPC['status'];

        $condition=" where 1 and o.deleted=0 and openid=:openid";
        $params=array(":openid"=>$_W['openid']);
        if(is_numeric($show_status)){
            $condition.=" and  o.status =:status";
            $params[':status']=$show_status;
        }
        $sql="select o.*,h.title as hoteltitle,adl.title as adltitle,ad.title as addresstitle,adl.header_image as addresheader_image,adl.oldprice from ".tablename("ewei_shop_union_ly_order")." as o ".
            "LEFT JOIN ".tablename("ewei_shop_union_ly_hotel")." as h ON h.id=o.hotelid ".
            "LEFT JOIN ".tablename("ewei_shop_union_ly_addressline")." as adl ON adl.id=o.addresslineid ".
            "LEFT JOIN ".tablename("ewei_shop_union_ly_lyaddress")." as ad ON adl.addressid=ad.id".
            $condition;
        $sql.=" order by o.id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $list=pdo_fetchall($sql,$params);

        foreach ($list as &$value){
            if($value['status']==0){
                $value['statusstr']="待确认";
            }elseif($value['status']==1){
                $value['statusstr']="已确认";
            }
            elseif($value['status']==2){
                $value['statusstr']="已取消";
            }
            elseif($value['status']==3){
                $value['statusstr']="待评价";
            }
            elseif($value['status']==4){
                $value['statusstr']="已评价";
            }
            $value['addresheader_image']=tomedia($value['addresheader_image']);

        }
        unset($value);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_order")." as o ".$condition,$params);


        show_json(1,array('list'=>$list,'pagesize'=>$psize,'total'=>$total));
    }
    function add(){
        global $_W;
        global $_GPC;

        $openid=$_W['openid'];
        $open_redis = function_exists('redis') && !(is_error(redis()));
        if ($open_redis)
        {
            $redis_key = $_W['uniacid'] . '_order_submit_' . $openid;
            $redis = redis();
            if (!(is_error($redis)))
            {
                if ($redis->setnx($redis_key, time()))
                {
                    $redis->expireAt($redis_key, time() + 2);
                }
                else if (($redis->get($redis_key) + 2) < time())
                {
                    $redis->del($redis_key);
                }
                else
                {
                    show_json(0, '不要短时间重复下单!');
                }
            }
        }

        $addreddlineid=intval($_GPC['id']);

        $lyaddressinfo=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_addressline")." where id=:id",array(":id"=>$addreddlineid));

        //
        $datetime=$_GPC['datetime'];
        //预约时间控制
        $nowtime=time();
        if($nowtime>strtotime($datetime)){
            show_json(0,'抱歉预约时间错误');
        }
        $bookdata=date('w',strtotime($datetime));
        $dffday=3;
        if($bookdata==0 || $bookdata==6){
            $dffday=7;
        }
        $day= ceil((strtotime($datetime)-$nowtime)/86400);
        if($day<$dffday){
            show_json(0,"节假日需要提前7天预约，其他时间需要提前3天");
        }
        if(empty($_GPC['images'][0])){
            show_json(0,"抱歉您未上传介绍信");
        }
        //检查用户短时间内是否进行了下单
        $nextorderlist=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_order")." where openid=:openid and status!=2 and times>:createtime  order by times desc",array(":openid"=>$_W['openid'],':createtime'=>$nowtime));

        foreach ($nextorderlist as $orders){
            $lastday=abs(strtotime($datetime)-$orders['times']);
            $lastday=ceil($lastday/60/60/24);
            if($lastday<15){
                show_json(0,"您在".date("Y-m-d",$orders['times'])."已经有出行计划！无法进行相邻15天预约");
            }
        }
//        var_dump($nextorderlist);
//        die();


        $data=array(
            'ordersn'=>"AD".date("YmdHis").random(4,true),
            'hotelid'=>$lyaddressinfo['hotelid'],
            'name'=>trim($_GPC['name']),
            'mobile'=>trim($_GPC['mobile']),
            'times'=>strtotime($datetime),
            'number'=>trim($_GPC['number']),
            'imid'=>trim($_GPC['imid']),
            'type'=>0,
            'openid'=>$_W['openid'],
            'price'=>$lyaddressinfo['price'],
            'addresslineid'=>$addreddlineid,
            'jieshao'=>trim($_GPC['images'][0]),
            'createtime'=>time(),
        );
        pdo_insert("ewei_shop_union_ly_order",$data);
       // $orderid=pdo_insertid();
        //短信消息发送
        //获取当前管理员绑定的手机号
        $addressid=$lyaddressinfo['addressid'];
        if($addressid){
            $memberlist=pdo_fetchall("select id,mobile from ".tablename('ewei_shop_union_ly_role_member')." where  FIND_IN_SET({$addressid}, addressids)");

            foreach ($memberlist as $value){
                if(!empty($value['mobile'])){
                    $ret = com('sms')->send($value['mobile'], 38, array('预订人'=>$data['name'],'预订标题'=>$lyaddressinfo['title'],'预订时间'=>$datetime,'入住人数'=>$data['number']));
                }
              }
        }
        show_json(1,'预约完成');
    }

    function lyaddresslinebook(){
        global $_W;
        global $_GPC;
        $lyaddresslineid=$_GPC['id'];

        $lyaddressinfo=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_addressline")." where id=:id",array(":id"=>$lyaddresslineid));

        include $this->template();
    }
    public function getlist(){
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 6;
        $total=0;
        $condition="  where deleted=0 and enabled=0 and type=1";

        $type=empty($_GPC['type']) ? "zh" : $_GPC['type'];
        $typevalue=empty($_GPC['value']) ? 0 :$_GPC['value'];
        $params=array();
        $orderasc=" desc ";

        if($type=="zh"){
            $type="createtime";
            $orderasc=" desc ";
        }
        if($type=="volume"){
            $order="volume";
            $orderasc=" desc ";
        }

        if($type=="evaluate"){
            if($typevalue==0){
                $orderasc=" asc ";
            }
        }
        if($type=="activity"){
            $type='createtime ';
            if($typevalue==0){
                $orderasc=" asc ";
            }
        }
        if($_GPC['addressid']){
            $condition.=" and addressid= :addressid";
            $params[':addressid']=intval($_GPC['addressid']);
        }

        if(!empty($_GPC['areaCode'])){//地区
            $condition.=" and position= :city";
            $params[':city']=trim($_GPC['areaCode']);
        }
        if($_GPC['xingcheng']){//地区
            $condition.=" and dayvale = :dayvale";
            $params[':dayvale']=trim($_GPC['xingcheng']);
        }
        if($_GPC['traffic']){
            $condition.=" and traffic_id= :traffic_id";
            $params[':traffic_id']=intval($_GPC['traffic']);
        }
        if($_GPC['traffic_type']){
            $condition.=" and traffic_type= :traffic_type";
            $params[':traffic_type']=intval($_GPC['traffic_type']);
        }
        if($_GPC['has_scenic']){
            $condition.=" and has_scenic= :has_scenic";
            $params[':has_scenic']=intval($_GPC['has_scenic']);
        }

        if($_GPC['theme']){
            $condition.=" and theme_id= :theme_id";
            $params[':theme_id']=intval($_GPC['theme']);
        }
        if($_GPC['keywords']!=''){
            $condition.=" and (title like :keyword or unitname like :keyword)";
            $params[':keyword']="%".trim($_GPC['keywords'])."%";
        }

        $sql="select id,title,mobilephone,header_image,price,oldprice,unitname,evaluate from ".tablename("ewei_shop_union_ly_addressline").
            $condition;
        $sql.=" order by {$type} {$orderasc}  " ;//." LIMIT ". ($pindex - 1) * $psize . ',' . $psize;
        $list=pdo_fetchall($sql,$params);

        foreach ($list as &$item){
            $item['header_image']=tomedia($item['header_image']);
            $item['evaluate_num']=pdo_fetchcolumn("select count(*) from ".tablename('ewei_shop_ly_order_comment')." where addresslineid=:addresslineid",array(":addresslineid"=>$item['id']));
        }
        unset($item);

        //$total=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_addressline").$condition,$params);


        show_json(1, array('list' => $list,'total' => $total, 'pagesize' => $psize));
    }

    function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $traffic=$this->model->traffic;
        $item=pdo_fetch("select  * from ".tablename("ewei_shop_union_ly_addressline")." where id=:id",array(":id"=>$id));

        if(empty($item)){
            $this->message("非法访问");
        }
        $count=pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_ly_order_comment') . ' where addresslineid=:addresslineid and checked=1  limit 1', array( ':addresslineid' => $item['id']));

        $counlev=pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_ly_order_comment') . ' where level>=4 and addresslineid=:addresslineid and checked=1  limit 1', array( ':addresslineid' => $item['id']));
        if($count>0 ){
            $lodnd=round($counlev/$count,2)*100;
        }
        $commentlist=pdo_fetchall("select * from ".tablename("ewei_shop_ly_order_comment")." where addresslineid=:id and checked=1 limit 0,5",array(":id"=>$item['id']));
        include $this->template("union/lyhome/lyaddresslineview");
    }

    function orders(){
        global $_W;
        global $_GPC;
        include $this->template("union/lyhome/lyaddressorders");
    }


}