<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Diy_EweiShopV2Page extends PluginWebPage {

	function main() {
		global $_W, $_GPC;
		$pagetype = 'diy';

		if (!empty($_GPC['keyword'])) {
			$keyword = '%' . trim($_GPC['keyword']) . '%';
			$condition = " and name like '{$keyword}' ";
		}
		if(!empty($_GPC['catid'])){
			$condition.= " and catid = '{$_GPC[catid]}' ";
		}
		if(empty($_GPC['page'])){
			$page=1;
		}else{
			$page=$_GPC['page'];
		}
		$category= pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_diypage_category') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY displayorder asc");
		$result = $this->model->getPageList('diy',$condition,$page);
		extract($result);

		include $this->template('diypage/page/list');
	}

	function edit() {
		$this->post('edit');
	}

	function  add(){
		$this->post('add');
	}

	protected function post($do){
		global $_W, $_GPC;

		$result = $this->model->verify($do, 'diy');
		extract($result);
		$categorys= pdo_fetchall("SELECT id,name FROM " . tablename('ewei_shop_diypage_category') . " WHERE uniacid = '{$_W['uniacid']}' and status=1 ORDER BY displayorder asc");
		if($template && $do=='add') {
			$template['data'] = base64_decode($template['data']);
			$template['data'] = json_decode($template['data'], true);
			$page = $template;
		}

		$allpagetype = $this->model->getPageType();
		$typename = $allpagetype[$type]['name'];
        $diymenu = pdo_fetchall('select id, name from ' . tablename('ewei_shop_diypage_menu') . ' where merch=:merch and uniacid=:uniacid  order by id desc', array(':merch'=>intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
        $category = pdo_fetchall("SELECT id, name FROM " . tablename('ewei_shop_diypage_template_category') . " WHERE merch=:merch and uniacid=:uniacid order by id desc ", array(':merch'=>intval($_W['merchid']), ':uniacid'=>$_W['uniacid']));

		//	读取用户等级
		$levels = array();
		$levels['member'] = m('member')->getLevels(false);
		array_unshift($levels['member'], array('id'=>'default', 'levelname'=>'默认等级'));
		//	读取分销商等级
		if(p('commission')){
			$levels['commission'] = p('commission')->getLevels(true, true);
		}

		if($_W['ispost']) {
			$data = $_GPC['data'];
			var_dump($data);
			die();
			$this->model->savePage($id, $data);
		}

        $hasplugins = json_encode(array(
            'creditshop' => p('creditshop') ? 1 : 0,
            'merch' => p('merch') ? 1 : 0,
            'seckill' => p('seckill') ? 1 : 0
        ));

		include $this->template('diypage/page/post');
	}

	function delete(){
		global $_W, $_GPC;

		$id = intval($_GPC['id']);
		if(empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}
		
		$this->model->delPage($id);
	}

	function savetemp() {
		global $_W, $_GPC;

		$temp = array(
			'type'=>intval($_GPC['type']),
			'cate'=>intval($_GPC['cate']),
			'name'=>trim($_GPC['name']),
			'preview'=>trim($_GPC['preview']),
			'data'=>$_GPC['data'],
		);
		$this->model->saveTemp($temp);
	}
}