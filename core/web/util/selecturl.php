<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Selecturl_EweiShopV2Page extends WebPage {
	 
	function main() {
		global $_W, $_GPC;
		
		$full = intval($_GPC['full']);
		
		$syscate = m('common')->getSysset('category');
		if($syscate['level']>0){
			$categorys = pdo_fetchall("SELECT id,name,parentid FROM " . tablename('ewei_shop_category') . " WHERE enabled=:enabled and uniacid= :uniacid  ", array(':uniacid' => $_W['uniacid'], ':enabled' => '1'));
		}

		if(p('diypage')) {
			$diypage = p('diypage')->getPageList();
			if(!empty($diypage)) {
				$allpagetype = p('diypage')->getPageType();
			}
		}
		
		include $this->template();
	}
	 
	function query(){
		global $_W, $_GPC;
		
		$type = trim($_GPC['type']);
		$kw = trim($_GPC['kw']);
		$full = intval($_GPC['full']);
		
		if(!empty($kw) && !empty($type)){
			
			if($type=='good'){
				$list = pdo_fetchall("SELECT id,title,productprice,marketprice,thumb,sales,unit,minprice FROM " . tablename('ewei_shop_goods') . " WHERE uniacid= :uniacid and status=:status and deleted=0 AND title LIKE :title ", array(':title' => "%{$kw}%", ':uniacid' => $_W['uniacid'], ':status' => '1'));
				$list = set_medias($list, 'thumb');
			}
			elseif($type=='article'){
				$list = pdo_fetchall("select id,article_title from " . tablename('ewei_shop_article') . ' where article_title LIKE :title and article_state=1 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':title' => "%{$kw}%"));
			}
			elseif($type=='coupon'){
				$list = pdo_fetchall("select id,couponname,coupontype from " . tablename('ewei_shop_coupon') . ' where couponname LIKE :title and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':title' => "%{$kw}%"));
			}
			elseif($type=='groups'){
				$list = pdo_fetchall("select id,title from " . tablename('ewei_shop_groups_goods') . ' where title LIKE :title and status=1 and deleted=0 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':title' => "%{$kw}%"));
			}
            elseif($type=='sns'){
                $list_board = pdo_fetchall("select id,title from " . tablename('ewei_shop_sns_board') . ' where title LIKE :title and status=1 and enabled=0 and uniacid=:uniacid order by id desc ', array(':uniacid' => $_W['uniacid'], ':title' => "%{$kw}%"));
                $list_post = pdo_fetchall("select id,title from " . tablename('ewei_shop_sns_post') . ' where title LIKE :title and checked=1 and deleted=0 and uniacid=:uniacid order by id desc ', array(':uniacid' => $_W['uniacid'], ':title' => "%{$kw}%"));

                $list = array();
                if(!empty($list_board) && is_array($list_board)){
                    foreach ($list_board as &$board){
                        $board['type'] = 0;
                        $board['url'] = mobileUrl('sns/board', array('id'=>$board['id'], 'page'=>1), $full);
                    }
                    unset($board);
                    $list = array_merge($list, $list_board);
                }
                if(!empty($list_post) && is_array($list_post)){
                    foreach ($list_post as &$post){
                        $post['type'] = 1;
                        $post['url'] = mobileUrl('sns/post', array('id'=>$post['id']), $full);
                    }
                    unset($post);
                    $list = array_merge($list, $list_post);
                }
            }
            elseif($type=='creditshop'){
                $list = pdo_fetchall("select id, thumb, title, price, credit, money from " . tablename('ewei_shop_creditshop_goods') . ' where title LIKE :title and status=1 and deleted=0 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':title' => "%{$kw}%"));
            }
		}
		
		include $this->template('util/selecturl_tpl');
	}
	 
}
