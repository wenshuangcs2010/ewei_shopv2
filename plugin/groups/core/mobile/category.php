<?php
/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Category_EweiShopV2Page extends PluginMobilePage {

	function main(){
		global $_W, $_GPC;
		try{
			$openid = $_W['openid'];
			$uniacid = $_W['uniacid'];
			$cateid = intval($_GPC['category']);
			load()->model('mc');
			$uid = mc_openid2uid($openid);
			if (empty($uid)) {
				mc_oauth_userinfo($openid);
			}
			$category = pdo_fetchall("select id,name from ".tablename('ewei_shop_groups_category')." where uniacid = :uniacid and enabled > 0 ",array(':uniacid'=>$uniacid));
			$category_name = pdo_fetch("select id,name,thumb from ".tablename('ewei_shop_groups_category')." where uniacid = :uniacid and id = :id ",array(':uniacid'=>$uniacid,':id'=>$cateid));
			if(empty($category_name)){
				$category_name['name'] = '全部活动';
			}
			//分享
			$this->model->groupsShare();
			include $this->template();
		}catch(Exception $e){
			$content = $e->getMessage();
			include $this->template('groups/error');
		}
	}
	function get_list(){
		global $_W, $_GPC;
		try{
			$uniacid = $_W['uniacid'];
			$cateid = intval($_GPC['category']);
			$list = array();
			$pindex = max(1, intval($_GPC['page']));
			$psize = 10;
			$condition = ' and uniacid = :uniacid and status=1 and deleted=0';
			$params = array(':uniacid' => $_W['uniacid']);
			if(!empty($cateid)){
				$condition .=" and category = ".$cateid;
			}
			$keyword = trim($_GPC['keyword']);
			if(!empty($keyword)){
				$condition .= " AND title like '%{$keyword}%' ";
			}

			$sql = 'SELECT COUNT(1) FROM ' . tablename('ewei_shop_groups_goods') . " where 1 {$condition}";
			$total = pdo_fetchcolumn($sql, $params);
			if (!empty($total)) {
				$sql = 'SELECT id,title,thumb,price,groupnum,groupsprice,category,isindex,goodsnum,units,sales,description FROM ' . tablename('ewei_shop_groups_goods') . '
						where 1 ' . $condition . ' ORDER BY displayorder DESC,id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
				$list = pdo_fetchall($sql, $params);
				$list = set_medias($list, 'thumb');
			}
			show_json(1,array('list'=>$list,'pagesize'=>$psize,'total'=>$total));
		}catch(Exception $e){
			$content = $e->getMessage();
			include $this->template('groups/error');
		}
	}
}
