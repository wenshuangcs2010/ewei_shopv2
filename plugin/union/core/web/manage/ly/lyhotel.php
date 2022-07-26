<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Lyhotel_EweiShopV2Page extends UnionWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params=array();
        $condition="  where deleted=0 ";
        $sql="select * from ".tablename("ewei_shop_union_ly_hotel").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_hotel").$condition,$params);
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
        $activity_id=0;
        $id = intval($_GPC['id']);
        if ($id) {
            $vo = pdo_fetch("select * from " . tablename("ewei_shop_union_ly_hotel") . " where id=:id", array(":id" => $id));
            $activity_id=$vo['activity_id'];
        }
        //获取全部可用报名模块数据
        $categoryactivity=$this->model->getCategroyMemberActivity($activity_id);
        if ($_W['ispost']) {
            $data = array(
                'title' => trim($_GPC['title']),
                'mobilephone' => trim($_GPC['mobilephone']),
                'description' => htmlspecialchars_decode(trim($_GPC['description'])),
                'hints' => htmlspecialchars_decode(trim($_GPC['hints'])),
                "createtime" => TIMESTAMP,
                'address' => trim($_GPC['address']),
                'lng' => trim($_GPC['lng']),
                'lat' => trim($_GPC['lat']),
                'price' => floatval($_GPC['price']),
                'spot' => trim($_GPC['spot']),
                'activity_id' => intval($_GPC['activity_id']),
                'enabled' => intval($_GPC['enabled']),
                'header_image' => trim($_GPC['header_image']),
            );
            if ($id) {
                unset($data['createtime']);
                pdo_update("ewei_shop_union_ly_hotel", $data, array("id" => $id));
            } else {
                pdo_insert("ewei_shop_union_ly_hotel", $data);
            }
            $this->message("数据处理成功", unionUrl("ly/lyhotel"));
        }
        include $this->template();
    }

    public function del(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $status=pdo_delete("ewei_shop_union_ly_hotel",array("id"=>$id));

        if($status){
            $this->model->show_json(1,'成功删除');
        }
        $this->model->show_json(0,'删除失败');
    }
}