<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Source_EweiShopV2Page extends PluginWebPage {

	function main() {
		global $_W, $_GPC;
		
		$article_sys = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_article_sys') . " WHERE uniacid=:uniacid limit 1 ", array(':uniacid' => $_W['uniacid']));
		
		if(empty($article_sys['article_source'])){
			$sourceUrl = $_W['siteroot'] . 'addons/ewei_shopv2/plugin/article/static/images';
		}else{
			$sourceUrl = $article_sys['article_source'];
			$endstr = substr($sourceUrl, -1);
			if($endstr=="/"){
				$sourceUrl = rtrim($sourceUrl, "/");
			}
		}
		
		
		include $this->template();
	}

}
