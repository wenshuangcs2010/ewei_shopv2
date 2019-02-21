<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends MobilePage {


	function main() {
		global $_W, $_GPC;
		$allcategory = m('shop')->getCategory();
		$catlevel = intval($_W['shopset']['category']['level']);
		$opencategory = true; //是否自己商品不同步分类
		$plugin_commission = p('commission');
		if ($plugin_commission && intval($_W['shopset']['commission']['level']) > 0) {
			$mid = intval($_GPC['mid']);
			if (!empty($mid)) {
				$shop = p('commission')->getShop($mid);
				if (empty($shop['selectcategory'])) {
					$opencategory = false;
				}
			}
		}
		include $this->template();
	}
	function search(){
		global $_W, $_GPC;
		$params=array(
			":openid"=>$_W['openid'],
			":uniacid"=>$_W['uniacid'],
			);
		if($_GPC['show']==1){
			$lastkeywords=pdo_fetchcolumn("SELECT keywords FROM ".tablename("ewei_shop_keywordscount")." WHERE uniacid=:uniacid and openid=:openid order by updatetimes desc Limit 0,1",$params);
		}
		$searchlist=pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_keywordscount")." WHERE uniacid=:uniacid and openid=:openid order by updatetimes desc Limit 0,10",$params);
		include $this->template();
	}
	function createHtml(){
		$html="";
	}
	function serachtitle(){
		global $_W, $_GPC;
		$keywords=$_GPC['keyword'];
		if(!empty($keywords)){
			$rest=m("goods")->searchTitle($keywords);
			$array="";
			foreach ($rest as $key => $value) {
				$title=mb_substr($value['title'],0,25,'utf-8');
				//var_dump($title);
				$array[$key]=array(
					'url'=>mobileUrl("goods/detail",array("id"=>$value['id'])),
					'title'=>$title,
					);
			}
			echo  json_encode($array);
		}
	}
	function gift(){
		global $_W,$_GPC;
		$uniacid = $_W['uniacid'];
		$giftid = intval($_GPC['id']);

		$gift = pdo_fetch("select * from ".tablename('ewei_shop_gift')." where uniacid = ".$uniacid." and id = ".$giftid." and starttime <= ".time()." and endtime >= ".time()." and status = 1 ");
		$giftgoodsid = explode(",",$gift['giftgoodsid']);
		$giftgoods = array();
		if(!empty($giftgoodsid)){
			foreach($giftgoodsid as $key => $value){
				$giftgoods[$key] = pdo_fetch("select id,status,title,thumb,marketprice from ".tablename('ewei_shop_goods')." where uniacid = ".$uniacid." and deleted = 0 and total > 0 and id = ".$value." and status = 2 ");
			}
			$giftgoods = array_filter($giftgoods);
		}

		include $this->template();
	}

	function get_list() {


		global $_GPC, $_W;

		$args = array(
			'pagesize' => 10,
			'page' => intval($_GPC['page']),
			'isnew' => trim($_GPC['isnew']),
			'ishot' => trim($_GPC['ishot']),
			'isrecommand' => trim($_GPC['isrecommand']),
			'isdiscount' => trim($_GPC['isdiscount']),
			'istime' => trim($_GPC['istime']),
			'issendfree' => trim($_GPC['issendfree']),
			'keywords' => trim($_GPC['keywords']),
			'cate' => trim($_GPC['cate']),
			'order' => trim($_GPC['order']),
			'by' => trim($_GPC['by'])
		);
		if(!empty($args['keywords'])){
			m("goods")->setKeyWords($args['keywords']);
		}
		//判断是否开启自选商品
		$plugin_commission = p('commission');
		if ($plugin_commission && intval($_W['shopset']['commission']['level'])>0 && empty($_W['shopset']['commission']['closemyshop']) && !empty($_W['shopset']['commission']['select_goods'])) {
            $frommyshop = intval($_GPC['frommyshop']);
			$mid = intval($_GPC['mid']);
			if (!empty($mid) && !empty($frommyshop)) {
				$shop = p('commission')->getShop($mid);
				if (!empty($shop['selectgoods'])) {
					$args['ids'] = $shop['goodsids'];
				}
			}
		}
		$this->_condition($args);
	}

	function query() {
		global $_GPC, $_W;
		$args = array(
			'pagesize' => 10,
			'page' => intval($_GPC['page']),
			'isnew' => trim($_GPC['isnew']),
			'ishot' => trim($_GPC['ishot']),
			'isrecommand' => trim($_GPC['isrecommand']),
			'isdiscount' => trim($_GPC['isdiscount']),
			'istime' => trim($_GPC['istime']),
			'keywords' => trim($_GPC['keywords']),
			'cate' => trim($_GPC['cate']),
			'order' => trim($_GPC['order']),
			'by' => trim($_GPC['by'])
		);
		$this->_condition($args);
	}

	private function _condition($args)
	{
		global $_GPC, $_W;
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
        $set = m('common')->getSysset(array('shop', 'pay'));
		if ($merch_plugin && $merch_data['is_openmerch']) {
			$args['merchid'] = intval($_GPC['merchid']);
		}

		if (isset($_GPC['nocommission'])) {
			$args['nocommission'] = intval($_GPC['nocommission']);
		}
        //查询禁用余额的仓库
        $sql="select id from ".tablename("ewei_shop_depot")." where uniacid=:uniacid and isusebalance=1";
        $idlist=pdo_fetchall($sql,array(':uniacid'=>$_W['uniacid']),'id');
        if(!empty($idlist)){
            $idlist=  array_keys($idlist);
        }
		$goods = m('goods')->getList($args);

        foreach ($goods['list'] as &$good){
            if( $set['pay']['credit'] == 1){
                $good['creditsuccess']=1;
            }

            if(in_array($good['depotid'],$idlist)){
                $good['creditsuccess']=0;
            }
        }
        unset($good);
		show_json(1, array('list' => $goods['list'], 'total' => $goods['total'], 'pagesize' => $args['pagesize']));
	}

}
