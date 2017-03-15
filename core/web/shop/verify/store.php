<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Store_EweiShopV2Page extends ComWebPage {

    public function __construct($_com='verify')
    {
        parent::__construct($_com);
    }

    function main() {

        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $paras = array(':uniacid' => $_W['uniacid']);
        $condition = " uniacid = :uniacid";
        $url=mobileUrl('store.map', array(), false);
        $url=$_W['siteroot']."app/" .substr($url,2);
        //var_dump($url);
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= " AND (storename LIKE '%{$_GPC['keyword']}%' OR address LIKE '%{$_GPC['keyword']}%' OR tel LIKE '%{$_GPC['keyword']}%')";
        }

        if (!empty($_GPC['type'])) {
            $type = intval($_GPC['type']);
            $condition .= " AND type = :type";
            $paras[':type'] = $type;
        }


        $sql = "SELECT * FROM " . tablename('ewei_shop_store') . " WHERE $condition ORDER BY displayorder desc,id desc";
        $sql.=" LIMIT " . ($pindex - 1) * $psize . ',' . $psize;

        $sql_count = "SELECT count(1) FROM " . tablename('ewei_shop_store') . " WHERE $condition";

        $total = pdo_fetchcolumn($sql_count, $paras);
        $pager = pagination($total, $pindex, $psize);

        $list = pdo_fetchall($sql, $paras);
        $storeurl=mobileUrl("store",array(),ture);
        foreach ($list as &$row) {
            $row['salercount'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_saler') . ' where storeid=:storeid limit 1', array(':storeid' => $row['id']));
        }
        unset($row);
        include $this->template();
        
    }

    function add() {
        $this->post();
    }

    function edit() {
        $this->post();
    }

    protected function post() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        $store_config=pdo_fetch("SELECT * from ".tablename("ewei_shop_store_config")." where uniacid=:uniacid",array(":uniacid"=>$_W['uniacid']));
        if ($_W['ispost']) {
            $data = array(
                'uniacid' => $_W['uniacid'],
                'storename' => trim($_GPC['storename']),
                'address' => trim($_GPC['address']),
                'tel' => trim($_GPC['tel']),
                'lng' => $_GPC['map']['lng'],
                'lat' => $_GPC['map']['lat'],
                'type' => intval($_GPC['type']),
                'realname' => trim($_GPC['realname']),
                'mobile' => trim($_GPC['mobile']),
                'fetchtime' => trim($_GPC['fetchtime']),
	            'saletime' => trim($_GPC['saletime']),
	            'logo' => save_media($_GPC['logo']),
	            'desc' => trim($_GPC['desc']),
                'status' => intval($_GPC['status']),
                'locationurl'=>$_GPC['locationurl'],
                'gametype'=>$_GPC['gametype'],
                'defaultchick'=>$_GPC['defaultchick'],
            );
            $data['order_printer'] = is_array($_GPC['order_printer']) ? implode(',',$_GPC['order_printer']) : '';
            $data['order_template'] = intval($_GPC['order_template']);
            $data['ordertype'] = is_array($_GPC['ordertype']) ? implode(',',$_GPC['ordertype']) : '';
            if (!empty($id)) {
                pdo_update('ewei_shop_store', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                plog('shop.verify.store.edit', "编辑门店 ID: {$id}");
            } else {
                pdo_insert('ewei_shop_store', $data);
                $id = pdo_insertid();
                plog('shop.verify.store.add', "添加门店 ID: {$id}");
            }
             $storeconfg=array(
                "store_thumb"=>$_GPC['store_thumb'],
                'uniacid' => $_W['uniacid'],
                );
            if(!empty($store_config)){
                pdo_update("ewei_shop_store_config",$storeconfg,array("id"=>$store_config['id']));
            }else{
                pdo_insert("ewei_shop_store_config",$storeconfg);
            }

            show_json(1, array('url' => webUrl('shop/verify/store')));
        }
        $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_store') . " WHERE id =:id and uniacid=:uniacid limit 1", array(':uniacid' => $_W['uniacid'], ':id' => $id));
        if(!empty($store_config)){
            $item['store_thumb']=$store_config['store_thumb'];
        }
        if ($printer = com('printer')){
            $item = $printer->getStorePrinterSet($item);
            $order_printer_array = $item['order_printer'];
            $ordertype = $item['ordertype'];
            $order_template = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_printer_template') . ' WHERE uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));
        }
        include $this->template();
        
    }

    function delete() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,storename FROM " . tablename('ewei_shop_store') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_delete('ewei_shop_store', array('id' => $item['id']));
            plog('shop.verify.store.delete', "删除门店 ID: {$item['id']} 门店名称: {$item['storename']} ");
        }
        show_json(1, array('url' => referer()));
    }

	function displayorder() {

		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$displayorder = intval($_GPC['value']);
		$item = pdo_fetchall("SELECT id,storename FROM " . tablename('ewei_shop_store') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
		if (!empty($item)) {
			pdo_update('ewei_shop_store', array('displayorder' => $displayorder), array('id' => $id));
			plog('shop.verify.store.edit', "修改门店排序 ID: {$item['id']} 门店名称: {$item['storename']} 排序: {$displayorder} ");
		}
 		show_json(1);
	}

    function status() {
        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,storename FROM " . tablename('ewei_shop_store') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_store', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
            plog('shop.verify.store.edit', "修改门店状态<br/>ID: {$item['id']}<br/>门店名称: {$item['storename']}<br/>状态: " . $_GPC['status'] == 1 ? '启用' : '禁用');
        }
        show_json(1, array('url' => referer()));
    }

    function query() {
        global $_W, $_GPC;
        $kwd = trim($_GPC['keyword']);
        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        $condition = " and uniacid=:uniacid and type in (1,2,3) and status=1";

        if (!empty($kwd)) {
            $condition.=" AND `storename` LIKE :keyword";
            $params[':keyword'] = "%{$kwd}%";
        }
        $ds = pdo_fetchall('SELECT id,storename FROM ' . tablename('ewei_shop_store') . " WHERE 1 {$condition} order by id asc", $params);

        if ($_GPC['suggest']) {
            die(json_encode(array('value' => $ds)));
        }

        include $this->template('shop/verify/store/query');
        exit;
    }

}
