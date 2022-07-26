<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Lynews_EweiShopV2Page extends UnionWebPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $params=array();
        $condition="  where deleted=0 ";
        $sql="select * from ".tablename("ewei_shop_union_ly_news").
            $condition;
        $sql.=" order by createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params);

        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_ly_news").$condition,$params);
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

        $id = intval($_GPC['id']);
        if ($id) {
            $vo = pdo_fetch("select * from " . tablename("ewei_shop_union_ly_news") . " where id=:id", array(":id" => $id));
        }
        if ($_W['ispost']) {
            $data = array(
                'title' => trim($_GPC['title']),
                'newstitle' => trim($_GPC['newstitle']),
                'status'=>intval($_GPC['status']),
                'description' => htmlspecialchars_decode(trim($_GPC['description'])),
                "createtime" => TIMESTAMP,
            );
            if ($id) {
                unset($data['createtime']);
                pdo_update("ewei_shop_union_ly_news", $data, array("id" => $id));
            } else {
                pdo_insert("ewei_shop_union_ly_news", $data);
            }
            $this->message("数据处理成功", unionUrl("ly/lynews"));
        }
        include $this->template();
    }

    public function del(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $status=pdo_delete("ewei_shop_union_ly_news",array("id"=>$id));

        if($status){
            $this->model->show_json(1,'成功删除');
        }
        $this->model->show_json(0,'删除失败');
    }
}