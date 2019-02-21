<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Depot_EweiShopV2Page extends WebPage {

    function main() {

        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = " and uniacid=:uniacid";
        $params = array(':uniacid' => $_W['uniacid']);
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition.=' and title  like :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        $list = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_depot') . " WHERE 1 {$condition}  ORDER BY id DESC limit " . ($pindex - 1) * $psize . ',' . $psize, $params);
        $total = pdo_fetchcolumn("SELECT count(1) FROM " . tablename('ewei_shop_depot') . " WHERE 1 {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);

        include $this->template();
    }

    function add() {
        $this->post();
    }
    function updatastock(){
        global $_W, $_GPC;
        if($_W['uniacid']!=DIS_ACCOUNT){
            show_json(0,"非法访问");
        }
        $depot=Dispage::getDepot($_GPC['id']);
        if($depot['ismygoods']==0){
            show_json(0,"仓库无需更新库存");
        }
        if(empty($depot['storeroomid'])){
             show_json(0,"仓库ID错误未填写");
        }
        // $s=m("httpUtil")->updateGoodsPrice($_GPC['id'],$depot['storeroomid']);
        //  show_json($s,'更新');
        // die();
        if($depot['updateid']==1){
            m("httpUtil")->updatecnbuyerStock($_GPC['id'],$depot['storeroomid']);
            show_json(1);
        }
        elseif($depot['updateid']==2){
            m("httpUtil")->updateAdStock($_GPC['id'],$depot['storeroomid']);
            show_json(1);
        }
        show_json(0,'此仓库无法更新');
    }
    function edit() {
        $this->post();
    }
    function test(){
        //echo "111";
       $res=m("cnbuyerdb")->updateCnbuyerStock("310516625460004342",-1);
       //var_dump($res);
    }
    protected function post() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if ($_W['ispost']) {

            $data = array();
            $data['uniacid'] = $_W['uniacid'];
            $data['title'] = trim($_GPC['title']);
            $data['enabled'] = $_GPC['enabled'];
            $data['if_customs'] = intval($_GPC['if_customs']);
            $data['customs_place'] = '';
            $data['customs_code'] = '';
            $data['customs_name'] = '';
            $data['ifidentity'] = intval($_GPC['ifidentity']);
            $data['ismygoods']=intval($_GPC['ismygoods']);
            if($data['if_customs']){
                $data['customs_place'] = trim($_GPC['customs_place']);
                $data['customs_code'] = trim($_GPC['customs_code']);
                $data['customs_name'] = trim($_GPC['customs_name']);
            }
            if($data['ismygoods']){
                $data['updateid'] = intval($_GPC['updateid']);
                $data['cnbuyershoping_id']=intval($_GPC['cnbuyershoping_id']);
                $data['storeroomid']=trim($_GPC['storeroomid']);
            }
            $data['if_declare'] = intval($_GPC['if_declare']);
            $data['api_url'] = trim($_GPC['api_url']);
            $data['test_api'] = trim($_GPC['test_api']);
            $data['orgname'] = trim($_GPC['orgname']);
            $data['rrguser'] = trim($_GPC['rrguser']);
            $data['app_id'] = trim($_GPC['app_id']);
            $data['app_secret'] = trim($_GPC['app_secret']);
            $data['orgkey'] = trim($_GPC['orgkey']);
            $data['ordershop'] = trim($_GPC['ordershop']);
            $data['orderfrom'] = trim($_GPC['orderfrom']);
            $data['isusebalance'] = intval($_GPC['isusebalance']);
            if (!empty($id)) {
                plog('shop.depot.edit',"修改仓库 ID: {$id}");
                pdo_update('ewei_shop_depot', $data, array('id' => $id));
            } else {
                pdo_insert('ewei_shop_depot', $data);
                $id = pdo_insertid();
                plog('shop.depot.add',"添加仓库 ID: {$id}");
            }

            show_json(1, array('url' => webUrl('shop/depot', array('op' => 'display'))));
        }
        //修改
        if(!empty($id)){
            $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_depot') . " WHERE id = '$id' and uniacid = '{$_W['uniacid']}'");
        }
        include $this->template();
    }

    function delete() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_depot') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_delete('ewei_shop_depot', array('id' => $item['id']));
            plog('shop.refundaddress.delete', "删除仓库 ID: {$item['id']} 标题: {$item['title']} ");
        }
        show_json(1, array('url' => referer()));
    }

    function setdefault() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        if ($_GPC['isdefault'] == 1) {
            pdo_update('ewei_shop_depot', array('isdefault' => 0), array('uniacid' => $_W['uniacid']));
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_depot') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_depot', array('isdefault' => intval($_GPC['isdefault'])), array('id' => $item['id']));
            plog('shop.refundaddress.edit', "修改配送方式默认状态<br/>ID: {$item['id']}<br/>标题: {$item['title']}<br/>状态: " . $_GPC['isdefault'] == 1 ? '是' : '否');
        }
        show_json(1, array('url' => referer()));
    }

    function tpl() {

        global $_W, $_GPC;
        $random = random(16);
        ob_clean();
        ob_start();
        include $this->template('shop/refundaddress/tpl');
        $contents = ob_get_contents();
        ob_clean();
        die(json_encode(array(
            'random' => $random,
            'html' => $contents
        )));
    }

}
