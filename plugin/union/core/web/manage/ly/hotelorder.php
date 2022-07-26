<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Hotelorder_EweiShopV2Page extends UnionWebPage
{
    function main(){


        global $_W;


        global $_GPC;


        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where o.deleted=0  ";
        $paras=array();
        if(!empty($_GPC['keywordes'])){
            $condition.="  AND (o.name like :keywords or o.mobile like :keywords ) ";
            $paras[':keywords']=$_GPC['keywordes']."%";
        }
        if(is_numeric($_GPC['status'])){
            $condition.="  AND o.status=:status ";
            $paras[':status']=intval($_GPC['status']);
        }
        if(is_numeric($_GPC['addressonlineid'])){
            $condition.="  AND adl.id=:adlid ";
            $paras[':adlid']=intval($_GPC['addressonlineid']);
        }
        if($_GPC['datetime']!=''){
            $times=explode(" - ",$_GPC['datetime']);
            $times1=strtotime($times[0]);
            $times2=strtotime($times[1]);

            $itemtime=strtotime($_GPC['datetime']);
            $condition.="  AND ( o.times>=:start AND o.times<=:end )";
            $paras[':start']=$times1;
            $paras[':end']=$times2;
        }
        $addresssql="select id,title from ".tablename("ewei_shop_union_ly_addressline")." where type=0 and enabled=0";

        if($_W['isoperator']==1){
            //$condition.=" AND adl.userid=:userid";
            //$paras[':userid']=$_W['unionuser']['id'];
            $addressids=$_W['unionuser']['addressids'];
            $condition.=" AND ad.id in(".$addressids.")";
            $addresssql.=" AND addressid in (".$addressids.")";

        }



        $addresslist=pdo_fetchall($addresssql);

        $sql="select o.*,adl.title as adltitle,ad.title as addresstitle from ".tablename("ewei_shop_union_ly_order")." as o ".

            "LEFT JOIN ".tablename("ewei_shop_union_ly_addressline")." as adl ON adl.id=o.addresslineid ".
            "LEFT JOIN ".tablename("ewei_shop_union_ly_lyaddress")." as ad ON adl.addressid=ad.id".
            $condition;

        if(empty($_GPC['export'])){
            $sql.=" order by o.times desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        }

        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_order")." as o ".
             "LEFT JOIN ".tablename("ewei_shop_union_ly_addressline")." as adl ON adl.id=o.addresslineid ".
               "LEFT JOIN ".tablename("ewei_shop_union_ly_lyaddress")." as ad ON adl.addressid=ad.id"
            .$condition,$paras);
        if($_GPC['export']==1){

            foreach ($list as &$row){
                $row['times']=date('Y-m-d',$row['times']);
                if($row['status']==0){
                    $row['status_str']="待确认";
                }elseif($row['status']==1){
                    $row['status_str']="已确认";
                }elseif($row['status']==2){
                    $row['status_str']="已取消";
                }elseif($row['status']==3){
                    $row['status_str']="待评价";
                }elseif($row['status']==4){
                    $row['status_str']="已完成";
                }
            }
            unset($row);
            m('excel')->export($list, array(
                "title" => "订单数据-" . date('Y-m-d-H-i', time()),
                "columns" => $this->defaultColumns(),
            ));


        }
        $pager = pagination($total, $pindex, $psize);
        include $this->template();
    }
    function edit(){
        $this->post();
    }


    protected function defaultColumns()
    {
        return array(
            array('title' => '订单号', 'field' => 'ordersn', 'width' => 24),
            array('title' => '预约人', 'field' => 'name', 'width' => 25),
            array('title' => '预约人身份证', 'field' => 'imid', 'width' => 44),
            array('title' => '联系电话', 'field' => 'mobile', 'width' => 44),
            array('title' => '预约时间', 'field' => 'times', 'width' => 44),
            array('title' => '入住人数', 'field' => 'number', 'width' => 12),
            array('title' => '路线名称', 'field' => 'adltitle', 'width' => 12),
            array('title' => '景点', 'field' => 'addresstitle', 'width' => 12),
            array('title' => '状态', 'field' => 'status_str', 'width' => 12),
            array('title' => '取消原因', 'field' => 'canceldesc', 'width' => 12),
        );

    }

    function post(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);

        if($id){
            $paras=array(":id"=>$id);
            $vo = pdo_fetch("select * from ".tablename("ewei_shop_union_ly_order")." where   id=:id",$paras);
        }
        if($_W['ispost']){
            $data=array(
                'name'=>trim($_GPC['name']),
                'imid'=>trim($_GPC['imid']),
                'mobile'=>trim($_GPC['mobile']),
                'number'=>trim($_GPC['number']),
                'status'=>trim($_GPC['status']),
                'times'=>strtotime($_GPC['times']),
            );

            if($id){
                pdo_update("ewei_shop_union_ly_order",$data,array("id"=>$vo['id']));
            }
            $this->addlog("修改数据源数据".var_export($vo,true)."修改数据".var_export($data,true));
            $this->model->show_json(1,'修改成功');
        }

        include $this->template('ly/hotelorder/post');
    }
    private function addlog($msg){
        global $_W;
        if($_W['isoperator']==1){
           $username= $_W['unionuser']['username'];
           $userid= $_W['unionuser']['id'];
        }else{
            $username="系统管理员";
            $userid=0;
        }
        $data=array(
            'username'=>$username,
            'userid'=>$userid,
            'msg'=>$msg,
            'createtime'=>time(),
        );
        pdo_insert('ewei_shop_union_ly_log',$data);

    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $this->addlog("删除订单ID".$id);
        $status= pdo_update("ewei_shop_union_ly_order",array("deleted"=>1),array("id"=>$id));
        if($status){
            $this->model->show_json(1,'成功删除');
        }
        $this->model->show_json(0,'删除失败');
    }
    function confirm(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $order=pdo_fetch('select * from '.tablename("ewei_shop_union_ly_order")." where id=:id",array(":id"=>$id));
        $lyaddressinfo=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_addressline")." where id=:id",array(":id"=>$order['addresslineid']));
        $this->addlog("确认订单,ID:".$order['id'].';ORDERSN:'.$order['ordersn']);
        pdo_update("ewei_shop_union_ly_order",array("status"=>1),array("id"=>$id));
        $addressid=$lyaddressinfo['addressid'];
        if($addressid){
            if(!empty($order['mobile'])){

                $ret = com('sms')->send($order['mobile'], 33, array('预约人'=>$order['name'],'预约标题'=>$lyaddressinfo['title'],'预约时间'=>date("Y-m-d",$order['times']),'线路联系人'=>$lyaddressinfo['mobilephone']));

            }
        }
        //发送消息
        $this->model->show_json(1,'订单确认成功');
    }
    function confirmorder(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if(empty($id)){
            $this->model->show_json(0,'订单未找到');
        }
        $paras=array(":id"=>$id);
        $vo = pdo_fetch("select * from ".tablename("ewei_shop_union_ly_order")." where   id=:id",$paras);
        $data=array(
            'status'=>4,
        );
        if($id){
            pdo_update("ewei_shop_union_ly_order",$data,array("id"=>$id));
        }
        $this->addlog("订单完成,ID:".$vo['id'].';ORDERSN:'.$vo['ordersn']);
        $this->model->show_json(1,'订单设置成功');
    }
    function cancel(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        if($id){
            $paras=array(":id"=>$id);
            $vo = pdo_fetch("select * from ".tablename("ewei_shop_union_ly_order")." where   id=:id",$paras);
        }
        if($_W['ispost']){
            $lyaddressinfo=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_addressline")." where id=:id",array(":id"=>$vo['addresslineid']));
            $data=array(
                'status'=>2,
                'canceldesc'=>trim($_GPC['canceldesc']),
            );
            if($id){
                pdo_update("ewei_shop_union_ly_order",$data,array("id"=>$id));
            }
            $this->addlog("取消订单,ID:".$vo['id'].';ORDERSN:'.$vo['ordersn'].'取消原因:'.$data['canceldesc']);
            $ret = com('sms')->send($vo['mobile'], 34, array('预约人'=>$vo['name'],'预约标题'=>$lyaddressinfo['title'],'预约时间'=>date("Y-m-d",$vo['times']),'取消原因'=>$_GPC['canceldesc'],'线路联系人'=>$lyaddressinfo['mobilephone']));

            $this->model->show_json(1,'取消订单成功');
        }
        //发送消息
        include $this->template('');
    }
}