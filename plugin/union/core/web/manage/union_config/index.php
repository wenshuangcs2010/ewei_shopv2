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

        $union_info=$this->model->get_union_info($_W['unionid']);
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


            $data=array(
                'name'=>trim($_GPC['name']),
                'title'=>trim($_GPC['title']),
                'mobile'=>trim($_GPC['mobile']),
                'shopstatus'=>intval($_GPC['shopstatus']),
                'shopurl'=>trim($_GPC['shopurl']),
                'shopname'=>empty($_GPC['shopname']) ? "福利商城" : trim($_GPC['shopname']),
            );

            if($id){
                unset($data['add_time']);
                pdo_update("ewei_shop_union_user",$data,array('id'=>$id,'uniacid'=>$_W['uniacid']));
            }

        $this->model->show_json(1,"单位数据修改成功");
    }


    function password(){
        global $_W;
        global $_GPC;

        if($_W['ispost']){
            $oldpassword=$_GPC['oldpassword'];
            $newpassword=$_GPC['newpassword'];
            $checkpassword=$_GPC['checkpassword'];
            $user=$_SESSION['__union_' . (int) $_GPC['i'] . '_session'];
            $username=$user['username'];
            if(empty($newpassword)){
                $this->model->show_json(0,"新输入新密码");
            }
            if($newpassword!=$checkpassword){
                $this->model->show_json(0,"2次输入新密码不一致");
            }
            $dbuser = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_union_user') . ' WHERE username=:username AND uniacid=:uniacid AND status=1 AND deleted=0 LIMIT 1', array(':username' => $username, ':uniacid' => $_W['uniacid']));
            if(empty($dbuser)){
                $this->model->show_json(0,"数据异常，请联系管理员");
            }
            $oldmd5pass = md5($oldpassword . $user['salt']);

            if($oldmd5pass!=$dbuser['password']){
                $this->model->show_json(0,"原密码错误,无法修改密码");
            }
            $newmd5pass= md5($newpassword . $user['salt']);
            $res=pdo_update('ewei_shop_union_user',array('password'=>$newmd5pass),array('id'=>$dbuser['id']));
            if($res){
                unset($_SESSION['__union_' . (int) $_GPC['i'] . '_session']);
                $this->model->show_json(1,"密码修改成功,请重新登录");
            }
            $this->model->show_json(0,"密码修改失败请重试");
        }

        include $this->template();
    }


}