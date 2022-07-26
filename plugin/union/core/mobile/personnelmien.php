<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Personnelmien_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="职工风采";
        include $this->template();
    }

    public function get_personnelmien(){
        global $_W;
        global $_GPC;

        $args = array(
            'pagesize' => 10,
            'page' => intval($_GPC['page']),
            'order' => trim($_GPC['order']),
            'by' => trim($_GPC['by']),
        );

       $list= $this->model->get_personnelmien($args);
       show_json(1,$list);
    }

    public function info(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $info=$this->model->get_info_personnelmien($id);
        $personnelmien_info=$info['info'];
        $_W['union']['title']=$personnelmien_info['title'];

        $personnelmien_info['description']=$this->model->lazy($personnelmien_info['description']);

        $this->model->readmember_insert($_W['openid'],2);
        $readmember= $this->model->readcount(2);
        $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        $notreadcount=$allcount-$readmember['count'];
        include $this->template("union/personnelmien_info");
    }

}