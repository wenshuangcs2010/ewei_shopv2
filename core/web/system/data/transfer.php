<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
set_time_limit(0);
class Transfer_EweiShopV2Page extends SystemPage {

	function main() {

		global $_W, $_GPC;


		$wechatid = intval($_GPC['wechatid']);
		$wechatid1 = intval($_GPC['wechatid1']);
		$transtype = intval($_GPC['transtype']);
		$condition = " and uniacid=" . $wechatid;
		$where = array('uniacid' => $wechatid);
		$condition1 = " and uniacid=" . $wechatid1;
		$where1 = array('uniacid' => $wechatid1);

		function copy_data($table, $wechatid, $wechatid1, $transtype) {

			pdo_query('delete from  ' . tablename($table) . " where uniacid={$wechatid1}");

			$datas = pdo_fetchall('select * from ' . tablename($table) . " where uniacid=:uniacid", array(':uniacid' => $wechatid));
			foreach ($datas as $data) {
				unset($data['id']);
				$data['uniacid'] = $wechatid1;
				pdo_insert($table, $data);
			}
			if ($transtype == 1) {
				pdo_query('delete from  ' . tablename($table) . " where uniacid={$wechatid}");
			}
		}

		function copy_plugin_set($identity, $wechatid, $wechatid1, $transtype) {

			$setdata = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid));
			$plugins = iunserializer($setdata['plugins']);
			if (is_array($plugins) && isset($plugins[$identity])) {

				$newset = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid1));

				if (empty($newset)) {

					$newplugins = array(
						$identity => $plugins[$identity]
					);

					pdo_insert('ewei_shop_sysset', array('plugins' => iserializer($newplugins), 'sec' => iserializer(array()), 'sets' => iserializer(array()), 'uniacid' => $wechatid1));
				} else {
					$newplugins = iunserializer($newset['plugins']);
					$newplugins[$identity] = $plugins[$identity];

					pdo_update('ewei_shop_sysset', array('plugins' => iserializer($newplugins)), array('uniacid' => $wechatid1));
				}
				//缓存   
				$set = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid1));
				m('cache')->set("sysset", $set, $wechatid1);

				if ($transtype == 1) {
					unset($plugins[$identity]);
					pdo_update('ewei_shop_sysset', array('plugins' => iserializer($plugins[$identity])), array('uniacid' => $wechatid));
				}
			}
		}

		if ($_W['ispost']) {


			load()->func('file');
			if (is_array($_GPC['shop'])) {
				foreach ($_GPC['shop'] as $data) {
					if ($data == 'goods') {

						pdo_query('delete from  ' . tablename('ewei_shop_goods') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_goods_option') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_goods_param') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_goods_spec') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_goods_spec_item') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_category') . " where 1 {$condition1}");

						
						$goods = pdo_fetchall('select * from ' . tablename('ewei_shop_goods') . " where uniacid=:uniacid and isdis=1", array(':uniacid' => $wechatid));
						//pdo_debug();
						
						foreach ($goods as $g) {
							$goodsid = $g['id'];
							unset($g['id']);
							$g['uniacid'] = $wechatid1;
							$g['disgoods_id'] = $goodsid;
							pdo_insert('ewei_shop_goods', $g);
							$newgoodsid = pdo_insertid();

							$params = pdo_fetchall('select * from ' . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and uniacid=:uniacid", array(':uniacid' => $wechatid, ':goodsid' => $goodsid));
							foreach ($params as $p) {
								unset($p['id']);
								$p['uniacid'] = $wechatid1;
								$p['goodsid'] = $newgoodsid;
								pdo_insert('ewei_shop_goods_param', $p);
							}

							$newspecs = array();
							$specs = pdo_fetchall('select * from ' . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid and uniacid=:uniacid", array(':uniacid' => $wechatid, ':goodsid' => $goodsid));
							foreach ($specs as $spec) {
								$specid = $spec['id'];
								unset($spec['id']);
								$spec['uniacid'] = $wechatid1;
								$spec['goodsid'] = $newgoodsid;
								pdo_insert('ewei_shop_goods_spec', $spec);
								$newspecs[$specid] = pdo_insertid();
							}
							$allspecitemids = array();
							$options = pdo_fetchall('select * from ' . tablename('ewei_shop_goods_option') . " where goodsid=:goodsid and uniacid=:uniacid ", array(':uniacid' => $wechatid, ':goodsid' => $goodsid));

							foreach ($options as $o) {
								unset($o['id']);
								$o['uniacid'] = $wechatid1;
								$o['goodsid'] = $newgoodsid;
								pdo_insert('ewei_shop_goods_option', $o);
								$newoptionid = pdo_insertid();

								$newspecitemids = array();
								if (!empty($o['specs'])) {

									$spec_itemids = explode("_", $o['specs']);
									$spec_items = pdo_fetchall('select * from ' . tablename('ewei_shop_goods_spec_item') . " where id in (" . implode(',', $spec_itemids) . ")");

									foreach ($spec_items as $spec_item) {
										$spec_itemid = $spec_item['id'];
										if (!isset($allspecitemids[$spec_itemid])) {
											unset($spec_item['id']);
											$spec_item['uniacid'] = $wechatid1;
											$spec_item['specid'] = $newspecs[$spec_item['specid']];
											pdo_insert('ewei_shop_goods_spec_item', $spec_item);
											$newspecitemid = pdo_insertid();
											$allspecitemids[$spec_itemid] = $newspecitemid;
											$newspecitemids[] = $newspecitemid;
										}
									}
								}
								pdo_update('ewei_shop_goods_option', array('specs' => implode("_", $newspecitemids)), array('id' => $newoptionid));
							}
						}

						$pcates1 = array();
						$ccates1 = array();
						$tcates1 = array();

						$pcates = pdo_fetchall('select * from ' . tablename('ewei_shop_category') . " where uniacid=:uniacid and parentid=0", array(':uniacid' => $wechatid));

						foreach ($pcates as $pcate) {
							$pcateid = $pcate['id'];
							unset($pcate['id']);
							$pcate['uniacid'] = $wechatid1;
							pdo_insert('ewei_shop_category', $pcate);
							$newpcateid = pdo_insertid();

							$pcates1[$pcate['id']] = $newpcateid;
							pdo_update('ewei_shop_goods', array('pcate' => $newpcateid), array('uniacid' => $wechatid1, 'pcate' => $pcateid));

							$ccates = pdo_fetchall('select * from ' . tablename('ewei_shop_category') . " where parentid=:parentid ", array(':parentid' => $pcateid));
							foreach ($ccates as $ccate) {
								$ccateid = $ccate['id'];
								unset($ccate['id']);
								$ccate['uniacid'] = $wechatid1;
								$ccate['parentid'] = $newpcateid;
								pdo_insert('ewei_shop_category', $ccate);

								$newccateid = pdo_insertid();
								$ccates1[$ccate['id']] = $newccateid;
								pdo_update('ewei_shop_goods', array('ccate' => $newpcateid), array('uniacid' => $wechatid1, 'ccate' => $ccateid));

								$tcates = pdo_fetchall('select * from ' . tablename('ewei_shop_category') . " where parentid=:parentid ", array(':parentid' => $ccateid));
								foreach ($tcates as $tcate) {
									$tcateid = $ccate['id'];
									unset($tcate['id']);
									$tcate['uniacid'] = $wechatid1;
									$tcate['parentid'] = $newccateid;
									pdo_insert('ewei_shop_category', $tcate);
									$newtcateid = pdo_insertid();

									$tcates1[$tcate['id']] = $newtcateid;
									pdo_update('ewei_shop_goods', array('tcate' => $newtcateid), array('uniacid' => $wechatid1, 'tcate' => $tcateid));
								}
							}
						}
						//修改商品pcates,ccates,tcates
						$goods = pdo_fetchall('select id,pcates,ccates,tcates from ' . tablename('ewei_shop_goods') . " where uniacid=:uniacid", array(':uniacid' => $wechatid1));
						foreach ($goods as $g) {
							$gpcates = explode(',', $g['pcates']);
							$newpcates = array();
							foreach ($gpcates as $oldpcate) {
								if (isset($pcates1[$oldpcate])) {
									$newpcates[] = $pcates1[$oldpcate];
								}
							}

							$gccates = explode(',', $g['ccates']);
							$newccates = array();
							foreach ($gccates as $oldccate) {
								if (isset($ccates1[$oldccate])) {
									$newccates[] = $ccates1[$oldccate];
								}
							}

							$gtcates = explode(',', $g['tcates']);
							$newtcates = array();
							foreach ($gtcates as $oldtcate) {
								if (isset($tcates1[$oldtcate])) {
									$newtcates[] = $tcates1[$oldtcate];
								}
							}
							pdo_update('ewei_shop_goods', array('pcates' => implode(',', $newpcates), 'ccates' => implode(',', $newccates), 'tcates' => implode(',', $newtcates)), array('id' => $g['id']));
						}

						if ($transtype == 1) {
							pdo_query('delete from  ' . tablename('ewei_shop_goods') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_goods_option') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_goods_param') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_goods_spec') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_goods_spec_item') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_category') . " where 1 {$condition}");
						}
					} else if ($data == 'dispatch') {
						copy_data('ewei_shop_dispatch', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'adv') {
						copy_data('ewei_shop_adv', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'notice') {
						copy_data('ewei_shop_notice', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'level') {
						copy_data('ewei_shop_member_level', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'group') {
						copy_data('ewei_shop_member_group', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'set') {
//				pdo_update('ewei_shop_sysset',array('sets'=>  iserializer(array())),array('uniacid'=>$wechatid1));
//				
//				$setdata = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid));
//				
//                                        $set = unserialize($setdata['sets']);
//										
//			         $newset = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid1));
//				 if(empty($newset)){
//					 pdo_insert('ewei_shop_sysset',array('sets'=>  iserializer($set),'uniacid'=>$wechatid1));		 
//				 }else{
//					pdo_update('ewei_shop_sysset',array('sets'=>  iserializer($set)),array('uniacid'=>$wechatid1));		 
//				 }
//				 //缓存   
//					$set = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid1));
//					m('cache')->set("sysset",$set,$wechatid1); 
//					
//                                        if($transtype==1){
//					pdo_update('ewei_shop_sysset',array('sets'=>  iserializer(array('auth'=>$set['auth']))),array('uniacid'=>$wechatid));
//					
//                                                   //缓存   
//				          $set = pdo_fetch("select * from " . tablename('ewei_shop_sysset') . ' where uniacid=:uniacid limit 1', array(':uniacid' => $wechatid));
//					m('cache')->set("sysset",$set,$wechatid); 
//				}
					}
				}
			}


			if (is_array($_GPC['commission']) && p('commission')) {
				foreach ($_GPC['commission'] as $data) {
					if ($data == 'level') {
						copy_data('ewei_shop_commission_level', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'set') {
						copy_plugin_set('commission', $wechatid, $wechatid1, $transtype);
					}
				}
			}

			if (is_array($_GPC['poster'])) {
				foreach ($_GPC['poster'] as $data) {
					if ($data == 'poster') {
						copy_data('ewei_shop_poster', $wechatid, $wechatid1, $transtype);
					}
				}
			}
			if (is_array($_GPC['verify'])) {
				foreach ($_GPC['verify'] as $data) {
					if ($data == 'store') {
						copy_data('ewei_shop_store', $wechatid, $wechatid1, $transtype);
					}
				}
			}

			if (is_array($_GPC['perm'])) {
				foreach ($_GPC['perm'] as $data) {
					if ($data == 'role') {
						copy_data('ewei_shop_perm_role', $wechatid, $wechatid1, $transtype);
					}
				}
			}

			if (is_array($_GPC['creditshop'])) {
				foreach ($_GPC['creditshop'] as $data) {
					if ($data == 'goods') {
						pdo_query('delete from  ' . tablename('ewei_shop_creditshop_goods') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_creditshop_category') . " where 1 {$condition1}");

						$cates = pdo_fetchall('select * from ' . tablename('ewei_shop_creditshop_category') . " where uniacid=:uniacid", array(':uniacid' => $wechatid));

						$categoodsids = array();
						foreach ($cates as $cate) {
							$cateid = $cate['id'];
							unset($cate['id']);
							$cate['uniacid'] = $wechatid1;
							pdo_insert('ewei_shop_creditshop_category', $cate);
							$newcateid = pdo_insertid();


							$goods = pdo_fetchall('select * from ' . tablename('ewei_shop_creditshop_goods') . " where uniacid=:uniacid and cate=:cate", array(':uniacid' => $wechatid, ':cate' => $cateid));
							foreach ($goods as $g) {
								$goodsid = $g['id'];
								$categoodsids[] = $goodsid;
								unset($g['id']);
								$g['uniacid'] = $wechatid1;
								$g['cate'] = $newcateid;
								pdo_insert('ewei_shop_creditshop_goods', $g);
							}
						}

						if (!empty($categoodsids)) {
							$goods = pdo_fetchall('select * from ' . tablename('ewei_shop_creditshop_goods') . " where uniacid=:uniacid and id not in (" . implode(',', $categoodsids) . ")", array(':uniacid' => $wechatid));
							foreach ($goods as $g) {
								$goodsid = $g['id'];
								unset($g['id']);
								$g['uniacid'] = $wechatid1;
								pdo_insert('ewei_shop_creditshop_goods', $g);
							}
						}

						if ($transtype == 1) {
							pdo_query('delete from  ' . tablename('ewei_shop_creditshop_goods') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_creditshop_category') . " where 1 {$condition}");
						}
					} else if ($data == 'adv') {
						copy_data('ewei_shop_creditshop_adv', $wechatid, $wechatid1, $transtype);
					} else if ($data == 'set') {
						copy_plugin_set('creditshop', $wechatid, $wechatid1, $transtype);
					}
				}
			}

			if (is_array($_GPC['virtual'])) {
				foreach ($_GPC['virtual'] as $data) {
					if ($data == 'template') {
						pdo_query('delete from  ' . tablename('ewei_shop_virtual_type') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_virtual_category') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_virtual_data') . " where 1 {$condition1}");
					}

					$cates = pdo_fetchall('select * from ' . tablename('ewei_shop_virtual_category') . " where uniacid=:uniacid", array(':uniacid' => $wechatid));

					$catevirtuals = array();
					foreach ($cates as $cate) {
						$cateid = $cate['id'];
						unset($cate['id']);
						$cate['uniacid'] = $wechatid1;
						pdo_insert('ewei_shop_virtual_category', $cate);
						$newcateid = pdo_insertid();


						$virtuals = pdo_fetchall('select * from ' . tablename('ewei_shop_virtual_type') . " where uniacid=:uniacid and cate=:cate", array(':uniacid' => $wechatid, ':cate' => $cateid));
						foreach ($virtuals as $g) {
							$goodsid = $g['id'];
							$datas = pdo_fetchall('select * from ' . tablename('ewei_shop_virtual_data') . " where uniacid=:uniacid and typeid=:typeid and usetime=0", array(':uniacid' => $wechatid, ':typeid' => $goodsid));

							$catevirtuals[] = $goodsid;
							unset($g['id']);
							$g['uniacid'] = $wechatid1;
							$g['cate'] = $newcateid;
							$g['alldata'] = count($datas);
							$g['usedata'] = 0;
							pdo_insert('ewei_shop_virtual_type', $g);
							$newgoodsid = pdo_insertid();


							foreach ($datas as $d) {
								unset($d['id']);
								$d['uniacid'] = $wechatid1;
								$d['typeid'] = $newgoodsid;
								pdo_insert('ewei_shop_virtual_data', $d);
							}
						}
					}

					if (!empty($catevirtuals)) {
						$virtuals = pdo_fetchall('select * from ' . tablename('ewei_shop_virtual_type') . " where uniacid=:uniacid and id not in (" . implode(',', $catevirtuals) . ")", array(':uniacid' => $wechatid));
						foreach ($virtuals as $g) {


							$goodsid = $g['id'];

							$datas = pdo_fetchall('select * from ' . tablename('ewei_shop_virtual_data') . " where uniacid=:uniacid and typeid=:typeid  and usetime=0", array(':uniacid' => $wechatid, ':typeid' => $goodsid));


							unset($g['id']);
							$g['uniacid'] = $wechatid1;
							$g['alldata'] = count($datas);
							$g['usedata'] = 0;
							pdo_insert('ewei_shop_virtual_type', $g);

							$newgoodsid = pdo_insertid();

							$datas = pdo_fetchall('select * from ' . tablename('ewei_shop_virtual_data') . " where uniacid=:uniacid and typeid=:typeid  and usetime=0", array(':uniacid' => $wechatid, ':typeid' => $goodsid));
							foreach ($datas as $d) {
								unset($d['id']);
								$d['uniacid'] = $wechatid1;
								$d['typeid'] = $newgoodsid;
								pdo_insert('ewei_shop_virtual_data', $d);
							}
						}
					}

					if ($transtype == 1) {
						pdo_query('delete from  ' . tablename('ewei_shop_virtual_type') . " where 1 {$condition}");
						pdo_query('delete from  ' . tablename('ewei_shop_virtual_category') . " where 1 {$condition}");
						pdo_query('delete from  ' . tablename('ewei_shop_virtual_data') . " where 1 {$condition}");
					}
				}
			}

			if (is_array($_GPC['article'])) {
				foreach ($_GPC['article'] as $data) {
					if ($data == 'article') {

						pdo_query('delete from  ' . tablename('ewei_shop_article_category') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_article') . " where 1 {$condition1}");

						$cates = pdo_fetchall('select * from ' . tablename('ewei_shop_article_category') . " where uniacid=:uniacid", array(':uniacid' => $wechatid));

						$catearticles = array();
						foreach ($cates as $cate) {
							$cateid = $cate['id'];
							unset($cate['id']);
							$cate['uniacid'] = $wechatid1;
							pdo_insert('ewei_shop_article_category', $cate);
							$newcateid = pdo_insertid();


							$articles = pdo_fetchall('select * from ' . tablename('ewei_shop_article') . " where uniacid=:uniacid and article_category=:article_category", array(':uniacid' => $wechatid, ':article_category' => $cateid));
							foreach ($articles as $g) {
								$goodsid = $g['id'];
								$catearticles[] = $goodsid;
								$g['article_likenum'] = 0;
								$g['article_readnum'] = 0;
								unset($g['id']);
								$g['uniacid'] = $wechatid1;
								$g['article_category'] = $newcateid;
								pdo_insert('ewei_shop_article', $g);
							}
						}

						if (!empty($catearticles)) {
							$articles = pdo_fetchall('select * from ' . tablename('ewei_shop_article') . " where uniacid=:uniacid and id not in (" . implode(',', $catearticles) . ")", array(':uniacid' => $wechatid));
							foreach ($articles as $g) {
								$goodsid = $g['id'];
								unset($g['id']);
								$g['article_likenum'] = 0;
								$g['article_readnum'] = 0;
								$g['uniacid'] = $wechatid1;
								pdo_insert('ewei_shop_article', $g);
							}
						}

						if ($transtype == 1) {
							$articles = pdo_fetchall('select * from ' . tablename('ewei_shop_article') . " where uniacid=:uniacid and id not in (" . implode(',', $catearticles) . ")", array(':uniacid' => $wechatid));
							foreach ($articles as $article) {
								//查询关键字受否存在
								$keyword = pdo_fetch("SELECT * FROM " . tablename('rule_keyword') . " WHERE content=:content and module=:module and uniacid=:uniacid limit 1 ", array(':content' => $article['article_keyword'], ':module' => 'ewei_shop', ':uniacid' => $_W['uniacid']));
								if (!empty($keyword)) {
									pdo_delete('rule_keyword', array('id' => $keyword['id']));
									pdo_delete('rule', array('id' => $keyword['rid']));
								}
							}
							pdo_query('delete from  ' . tablename('ewei_shop_article') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_article_log') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_article_category') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_article_share') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_article_report') . " where 1 {$condition}");
						}
					}
				}
			}

			if (is_array($_GPC['coupon'])) {
				foreach ($_GPC['coupon'] as $data) {
					if ($data == 'coupon') {

						pdo_query('delete from  ' . tablename('ewei_shop_coupon_category') . " where 1 {$condition1}");
						pdo_query('delete from  ' . tablename('ewei_shop_coupon') . " where 1 {$condition1}");
						$cates = pdo_fetchall('select * from ' . tablename('ewei_shop_coupon_category') . " where uniacid=:uniacid", array(':uniacid' => $wechatid));
						$catecoupons = array();
						foreach ($cates as $cate) {
							$cateid = $cate['id'];
							unset($cate['id']);
							$cate['uniacid'] = $wechatid1;
							pdo_insert('ewei_shop_coupon_category', $cate);
							$newcateid = pdo_insertid();


							$coupons = pdo_fetchall('select * from ' . tablename('ewei_shop_coupon') . " where uniacid=:uniacid and catid=:catid", array(':uniacid' => $wechatid, ':catid' => $cateid));
							foreach ($coupons as $g) {
								$goodsid = $g['id'];
								$catecoupons[] = $goodsid;

								unset($g['id']);
								$g['uniacid'] = $wechatid1;
								$g['catid'] = $newcateid;
								pdo_insert('ewei_shop_coupon', $g);
							}
						}

						if (!empty($catecoupons)) {
							$coupons = pdo_fetchall('select * from ' . tablename('ewei_shop_coupon') . " where uniacid=:uniacid and id not in (" . implode(',', $catecoupons) . ")", array(':uniacid' => $wechatid));
							foreach ($coupons as $g) {
								$goodsid = $g['id'];
								unset($g['id']);
								$g['uniacid'] = $wechatid1;
								pdo_insert('ewei_shop_coupon', $g);
							}
						}
						if ($transtype == 1) {
							pdo_query('delete from  ' . tablename('ewei_shop_coupon') . " where 1 {$condition}");
							pdo_query('delete from  ' . tablename('ewei_shop_coupon_category') . " where 1 {$condition}");
						}
					} else if ($data == 'set') {
						copy_plugin_set('coupon', $wechatid, $wechatid1, $transtype);
					}
				}
			}

			if (is_array($_GPC['postera'])) {
				foreach ($_GPC['postera'] as $data) {
					if ($data == 'poster') {
						copy_data('ewei_shop_postera', $wechatid, $wechatid1, $transtype);
					}
				}
			}
			show_json(1);
		}
		$wechats = m('common')->getWechats();
		load()->func('tpl');
		include $this->template();
	}

}
