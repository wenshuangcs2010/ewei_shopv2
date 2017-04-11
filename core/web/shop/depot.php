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
        set_time_limit(0);
         require_once(EWEI_SHOPV2_TAX_CORE."toerp/erphttp.php");
        $n=new ErpHttp();
        $n->test();
        //ob_end_clean();
       // ob_implicit_flush(1);
       /*
       
        $exportlist = array();
        $list=$n->getStoreRoom();
        $columns=array(
            array('title' => '商品编号', 'field' => 'goodssn', 'width' => 24),
            array('title' => '商品类型', 'field' => 'sizeGroup', 'width' => 24),
            array('title' => '仓库', 'field' => 'cname', 'width' => 24),
            array('title' => '仓库ID', 'field' => 'storeroomId', 'width' => 24),
            array('title' => '库存', 'field' => 'stock', 'width' => 24),
            );
      
        $c1="20170210DZAC";
        $c1n="江浙5仓";
        $c2="BYXSC20160830";
        $c2n="江浙3仓";
        $c3='hzfxc150717';
        $c3n="杭州1仓";
        $c4="hznew";
        $c4n="杭州新仓";
        //foreach ($list['storeroomList'] as $key => $value){
            $i=1;
            while (true) {
                $res=$n->getGoodsStock($c1,$i,'',100);
                if(empty($res['storeroomStock'])){
                   break;
                }
                foreach($res['storeroomStock'] as $key=>$Stock ){
                   
                    
                    // if($res['storeroomStock'][$key-1]['goodsId']==$Stock['goodsId'] && $key !=0){
                    //     continue;
                    // }
                    $exportlist[]=array('goodssn'=>$Stock['goodsId'],'sizeGroup'=>$Stock['sizeGroup'],'cname'=>$c1n,'storeroomId'=>$c1,'stock'=>$Stock['stock']);
                }                
                $res['storeroomStock']="";
                $i++;
            } 
       // }
        m('excel')->export($exportlist, array(
            "title" => "商品数据-" . date('Y-m-d-H-i', time()),
            "columns" => $columns
        ));
    */
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
            $data['orgkey'] = trim($_GPC['orgkey']);
            $data['ordershop'] = trim($_GPC['ordershop']);
            $data['orderfrom'] = trim($_GPC['orderfrom']);
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
