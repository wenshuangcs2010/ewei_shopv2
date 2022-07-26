<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Member_EweiShopV2Page extends UnionWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params=array();
        $condition="  where deleted=0 ";
        $sql="select * from ".tablename("ewei_shop_union_ly_role_member").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_role_member").$condition,$params);
        $pager = pagination($total, $pindex, $psize);

        include $this->template();
    }

    public function add(){
        global $_W;
        global $_GPC;
        $this->post();
    }
    public  function edit(){
        global $_W;
        global $_GPC;
        $this->post();
    }
    public function post()
    {
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        if($id){
            $account=pdo_fetch("select * from ".tablename("ewei_shop_union_ly_role_member")." where id=:id",array(":id"=>$id));
            $addressids=explode(',',$account['addressids']);

        }
        $addresslist=pdo_fetchall("select * from ".tablename("ewei_shop_union_ly_lyaddress")." where enabled=0");

        if($_W['ispost']){

            $data=array(
                'username'=>trim($_GPC['username']),
                'realname'=>trim($_GPC['realname']),
                'mobile'=>trim($_GPC['mobile']),
                'status'=>trim($_GPC['status']),
            );
            if(empty($_GPC['address'])){
                $this->model->show_json(0,"抱歉管理员需要绑定一个地址");
            }
            $data['addressids']=implode(',',$_GPC['address']);
            $password=$_GPC['password'];
            $data['role']=iserializer(array('ly'=>array("ly.hotelorder")));
            if(empty($id)){
                $slat=random(4);
                $password=md5($password.$slat);
                $data['password']=$password;
                $data['createtime']=time();
                $data['salt']=$slat;
                $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_user").' where username=:username ',array(":username"=>$data['username']));
                if($count>0){
                    $this->model->show_json(0,"账号已经存在");

                }
                //登录账号效验
                $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_role_member")." where username=:username",array(":username"=>$data['username']));
                if($count>0){
                    $this->model->show_json(0,"账号已经存在");
                }
                pdo_insert("ewei_shop_union_ly_role_member",$data);
                $this->model->show_json(1,array("url"=>unionUrl('union.ly.member'),'message'=>"账号添加成功"));
            }else{

                if(!empty($password)){
                    $slat=$account['salt'];
                    $password=md5($password.$slat);
                    $data['password']=$password;
                }

               pdo_update("ewei_shop_union_ly_role_member",$data,array("id"=>$id));
                $this->model->show_json(1,array("url"=>unionUrl('ly.member'),'message'=>"修改成功"));
            }



        }
        include $this->template();
    }

    function delete(){
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        pdo_update("ewei_shop_union_ly_role_member",array("deleted"=>1),array("id"=>$id));
        $this->model->show_json(1,"删除成功");
    }


}