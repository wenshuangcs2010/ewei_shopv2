<?php

/*
 * 人人商城
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
define('PAGE_MEMBER', 0);
class DiypageModel extends PluginModel {

    private $plugin = array();
    private $ordernum = array();
    private $member = array();
    private $commission = array();

	public function getPageList($type='allpage', $condition=null, $page=1){
		global $_W;

		if($type=='diy'){
			$c = " and type=1 ";
		}
		elseif ($type=='sys'){
			$c = " and type>1 and type<99 ";
		}
		elseif ($type=='mod'){
			$c = " and type=99 ";
		}
		elseif ($type=='allpage') {
			$c = " and type>0 and type<99 ";
		}

		if(!empty($condition)){
			$c .= $condition;
		}
		$pindex = max(1, $page);
		$psize = 20;

        if($page>0){
            $limit = ' limit '. ($pindex - 1) * $psize . ',' . $psize;
        }

		$list = pdo_fetchall('select id, `name`, `type`, createtime, lastedittime, keyword from ' . tablename('ewei_shop_diypage') . ' where merch=:merch and uniacid=:uniacid '. $c .' order by type desc, id desc '. $limit, array(':merch'=>intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_diypage') . " where merch=:merch and uniacid=:uniacid " . $c, array(':merch'=>intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$pager = pagination($total, $pindex, $psize);
		if(!empty($list)) {
			$allpagetype = $this->getPageType();
			foreach ($list as $index=>$item) {
				$type = $item['type'];
				$list[$index]['typename'] = $allpagetype[$type]['name'];
				$list[$index]['typeclass'] = $allpagetype[$type]['class'];
			}
		}

		return array(
			'list'=>$list,
			'total'=>$total,
			'pager'=>$pager
		);
	}
public function exchangePage($pageid = 0) 
    {
        global $_W;
        $set = $this->getSet();
        if (!(empty($pageid))) 
        {
            $page = $this->getPage($pageid, true);
        }
        if (empty($pageid) || (!(empty($pageid)) && empty($page))) 
        {
            if (!(empty($set['page']['exchange']))) 
            {
                $page = $this->getPage($set['page']['exchange'], true);
            }
        }
        if (empty($page) || !(is_array($page)) || !(is_array($page['data']['items']))) 
        {
            return false;
        }
        $exchange_input = array();
        $pageitems = $page['data']['items'];
        if (!(empty($pageitems))) 
        {
            foreach ($pageitems as $pageitemid => $pageitem ) 
            {
                if ($pageitem['id'] == 'exchange_input') 
                {
                    $exchange_input = $pageitem;
                }
            }
        }
        return array('exchange_input' => $exchange_input, 'diyadv' => $page['data']['page']['diyadv'], 'items' => $pageitems);
    }
    public function getPage($id, $mobile=false){
		global $_W;

		if(empty($id)) {
			return;
		}

        $page = pdo_fetch('select * from ' . tablename('ewei_shop_diypage') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id'=>$id, ':uniacid' => $_W['uniacid']));

		if(!empty($page)) {
			$page['data'] = base64_decode($page['data']);

            if($mobile){
                $memberpage = $page['type']==3 ? true : false;
                $this->calculate($page['data'], $memberpage);
                $this->verifymobile($page['id'], $page['type']);
            }
			$page['data'] = json_decode($page['data'], true);
			if(empty($page['data']['page']['visitlevel'])){
				$page['data']['page']['visitlevel'] = array('member'=>array(), 'commission'=>array());
			}
			if(empty($page['data']['page']['novisit'])){
				$page['data']['page']['novisit'] = array('title'=>array(), 'link'=>array());
			}

			if(!empty($page['data']['items']) && is_array($page['data']['items'])){
				// 循环第一遍  执行 更新数据
              
				foreach ($page['data']['items'] as $itemid=> &$item) {
                   
      
					if($item['id']=='goods') {
					    $creditshop = !empty($item['params']['goodstype']) ? true : false;
						if($item['params']['goodsdata']=='0') {
							// 更新商品信息
							if(!empty($item['data']) && is_array($item['data'])) {
								$goodsids = array();
								foreach ($item['data'] as $index=>$data) {
									if(!empty($data['gid'])) {
										$goodsids[] = $data['gid'];
									}
								}
								if(!empty($goodsids) && is_array($goodsids)) {
									$item['data'] = array();
									$newgoodsids = implode(',', $goodsids);
                                    if($creditshop){
                                        $goods = pdo_fetchall("select id,isnodiscount,discounts, title, thumb, price as productprice, minmoney as minprice, mincredit, total, showlevels, showgroups, `type`, goodstype from " . tablename('ewei_shop_creditshop_goods') . " where id in( $newgoodsids ) and status=1 and deleted=0 and uniacid=:uniacid order by displayorder desc ", array(':uniacid' => $_W['uniacid']));
                                    }else{
                                        $goods = pdo_fetchall("select id,brief_desc, title,isnodiscount,discounts, thumb, productprice, minprice, total, showlevels, showgroups, bargain, merchid from " . tablename('ewei_shop_goods') . " where id in( $newgoodsids ) and status=1 and deleted=0 and checked=0 and uniacid=:uniacid order by displayorder desc ", array(':uniacid' => $_W['uniacid']));
                                    }
									if(!empty($goods) && is_array($goods)) {
                                         $level = m('member')->getLevel($_W['openid']);

										foreach ($goodsids as $goodsid) {
											foreach ($goods as $index=>$good) {
												if($good['id']==$goodsid){
                                                   
												    // 此处去掉判断权限
                                                    /*
                                                    $memberprice=0;
                                                    $memberprice = m('goods')->getMemberPrice($good, $level);

                                                    if($good['minprice']>$memberprice && $memberprice!=0){
                                                        $good['minprice']=$memberprice;
                                                    }*/
                                                    $childid = rand(1000000000, 9999999999);
                                                    $childid = 'C' . $childid;
                                                    if(isset($good['brief_desc']) && mb_strlen($good['brief_desc'],"utf-8")>38){
                                                        $newStr = mb_substr($good['brief_desc'],0,38,"UTF8").".....";
                                                        $good['brief_desc']=$newStr;
                                                    }
                                                    $item['data'][$childid] = array(
                                                        'thumb'=>$good['thumb'],
                                                        'brief_desc'=>empty($good['brief_desc']) ? "" : time($good['brief_desc']),
                                                        'title'=>$good['title'],
                                                        'price'=>$good['minprice'],
                                                        'gid'=>$good['id'],
                                                        'total'=>$good['total'],
                                                        'bargain'=>$good['bargain'],
                                                        'productprice'=>$good['productprice'],
                                                        'credit'=>$good['mincredit'],
                                                        'ctype'=>$good['type'],
                                                        'gtype'=>$good['goodstype']
                                                    );
												}
											}
										}
									}
								}
							}
						}
						elseif($item['params']['goodsdata']=='1') {
							// 更新分类信息
							if(!empty($item['params']['cateid'])) {
								// 查询分类信息
                                if(empty($item['params']['goodstype'])){
                                    $category = pdo_fetch('select id,`name`, enabled from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id'=>$item['params']['cateid'], ':uniacid' => $_W['uniacid']));
                                }else{
                                    $category = pdo_fetch('select id,`name`, enabled from ' . tablename('ewei_shop_creditshop_category') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id'=>$item['params']['cateid'], ':uniacid' => $_W['uniacid']));
                                }
								if(!empty($category)) {
									$item['params']['catename'] = $category['name'];
								}else{
									$item['params']['catename'] = '';
									$item['params']['cateid'] = '';
								}
								if(empty($category['enabled'])){
                                    $item['data'] = array();
                                }
							}
						}
						elseif($item['params']['goodsdata']=='2' && empty($item['params']['goodstype'])) {
							// 更新分组信息
							if(!empty($item['params']['groupid'])) {
								// 查询分组信息
								$group = pdo_fetch('select id, `name` from ' . tablename('ewei_shop_goods_group') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id'=>$item['params']['groupid'], ':uniacid' => $_W['uniacid']));
								if(!empty($group)) {
									$item['params']['groupname'] = $group['name'];
								}else{
									$item['params']['groupname'] = '';
									$item['params']['groupid'] = '';
								}
							}
						}
						if(empty($item['data'])){
                            unset($page['data']['items'][$itemid]);
                        }
					}
					elseif($item['id']=='merchgroup' && p('merch')){

                        if($item['params']['merchdata']=='0') {
                            // 更新商户信息
                            if(!empty($item['data']) && is_array($item['data'])) {
                                $merchids = array();
                                foreach ($item['data'] as $index => $data) {
                                    if (!empty($data['merchid'])) {
                                        $merchids[] = $data['merchid'];
                                    }
                                }
                            }
                            if(!empty($merchids) && is_array($merchids)) {
                                $item['data'] = array();
                                $newmerchids = implode(',', $merchids);
                                $merchs = pdo_fetchall("select id, merchname, logo, status, `desc` from " . tablename('ewei_shop_merch_user') . " where id in( $newmerchids ) and status=1 and uniacid=:uniacid order by isrecommand desc ", array(':uniacid' => $_W['uniacid']));
                                if(!empty($merchs) && is_array($merchs)) {
                                    foreach ($merchids as $merchid) {
                                        foreach ($merchs as $index => $merch) {
                                            if ($merch['id'] == $merchid) {
                                                $childid = rand(1000000000, 9999999999);
                                                $childid = 'C' . $childid;
                                                $item['data'][$childid] = array(
                                                    'name' => $merch['merchname'],
                                                    'desc' => $merch['desc'],
                                                    'thumb' => $merch['logo'],
                                                    'merchid' => $merch['id'],
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        elseif($item['params']['merchdata']=='1'){
                            if(!empty($item['params']['cateid'])) {
                                $category = pdo_fetch('select id, `catename`, status from ' . tablename('ewei_shop_merch_category') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id'=>$item['params']['cateid'], ':uniacid' => $_W['uniacid']));
                            }
                            if(!empty($category)) {
                                $item['params']['catename'] = $category['catename'];
                            }else{
                                $item['params']['catename'] = '';
                                $item['params']['cateid'] = '';
                            }
                            if(empty($category['status'])){
                                $item['data'] = array();
                            }
                            if(!empty($category) && !empty($category['status'])){
                                $merchs = pdo_fetchall("select id, merchname, logo, status, `desc` from " . tablename('ewei_shop_merch_user') . " where cateid=:cateid and status=1 and uniacid=:uniacid order by isrecommand desc ", array(':uniacid' => $_W['uniacid'], ':cateid'=>$item['params']['cateid']));
                                if(!empty($merchs) && is_array($merchs)) {
                                    $item['data'] = array();
                                    foreach ($merchs as $index => $merch) {
                                        $childid = rand(1000000000, 9999999999);
                                        $childid = 'C' . $childid;
                                        $item['data'][$childid] = array(
                                            'name' => $merch['merchname'],
                                            'desc' => $merch['desc'],
                                            'thumb' => $merch['logo'],
                                            'merchid' => $merch['id'],
                                        );
                                    }
                                }
                            }
                        }
                        elseif($item['params']['merchdata']=='2'){

                            if(!empty($item['params']['groupid'])) {
                                // 查询分组信息
                                $group = pdo_fetch('select id, groupname, status from ' . tablename('ewei_shop_merch_group') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id'=>$item['params']['groupid'], ':uniacid' => $_W['uniacid']));
                                if(!empty($group)) {
                                    $item['params']['groupname'] = $group['groupname'];
                                }else{
                                    $item['params']['groupname'] = '';
                                    $item['params']['groupid'] = '';
                                }
                            }
                            if(empty($group['status'])){
                                $item['data'] = array();
                            }
                            if(!empty($group) && !empty($group['status'])){
                                $merchs = pdo_fetchall("select id, merchname, logo, status, `desc` from " . tablename('ewei_shop_merch_user') . " where groupid=:groupid and status=1 and uniacid=:uniacid order by isrecommand desc ", array(':uniacid' => $_W['uniacid'], ':groupid'=>$item['params']['groupid']));
                                if(!empty($merchs) && is_array($merchs)) {
                                    $item['data'] = array();
                                    foreach ($merchs as $index => $merch) {
                                        $childid = rand(1000000000, 9999999999);
                                        $childid = 'C' . $childid;
                                        $item['data'][$childid] = array(
                                            'name' => $merch['merchname'],
                                            'desc' => $merch['desc'],
                                            'thumb' => $merch['logo'],
                                            'merchid' => $merch['id'],
                                        );
                                    }
                                }
                            }
                        }
                        elseif($item['params']['merchdata']=='3'){
                            $merchs = pdo_fetchall("select id, merchname, logo, status, `desc` from " . tablename('ewei_shop_merch_user') . " where isrecommand=1 and status=1 and uniacid=:uniacid order by isrecommand desc ", array(':uniacid' => $_W['uniacid']));
                            if(!empty($merchs) && is_array($merchs)) {
                                $item['data'] = array();
                                foreach ($merchs as $index => $merch) {
                                    $childid = rand(1000000000, 9999999999);
                                    $childid = 'C' . $childid;
                                    $item['data'][$childid] = array(
                                        'name' => $merch['merchname'],
                                        'desc' => $merch['desc'],
                                        'thumb' => $merch['logo'],
                                        'merchid' => $merch['id'],
                                    );
                                }
                            }
                        }
                    }
					elseif($item['id']=='diymod') {
						// 更新公用模块名称
						if(!empty($item['params']['modid'])) {
							$diymod = $this->getPage($item['params']['modid']);
							if(!empty($diymod)) {
								$item['params']['modname'] = $diymod['name'];
							}else{
								$item['params']['modname'] = '模块不存在，请重新插入';
							}
						}
					}
					elseif($item['id']=='richtext') {
						$item['params']['content'] = htmlspecialchars_decode($item['params']['content']);
					}
					elseif ($item['id']=='picture' || $item['id']=='picturew') {
						if(empty($item['style'])) {
							$item['style'] = array(
								'background'=>'#ffffff',
								'paddingtop'=>'0',
								'paddingleft'=>'0'
							);
						}
					}
					elseif($item['id']=='detail_tab'){
					    if(empty($item['params']['goodstext'])){
                            $item['params']['goodstext'] = "商品";
                        }
					    if(empty($item['params']['detailtext'])){
                            $item['params']['detailtext'] = "详情";
                        }
                    }
                    elseif($item['id']=='coupon'){
                        // 更新优惠券信息
                        if(!empty($item['data']) && is_array($item['data'])) {
                            $couponids = array();
                            foreach ($item['data'] as $index => $data) {
                                if (!empty($data['couponid'])) {
                                    $couponids[] = $data['couponid'];
                                }
                            }
                            if(!empty($couponids) && is_array($couponids)) {
                                $newcouponids = implode(',', $couponids);
                                $coupons = pdo_fetchall('select * from ' . tablename('ewei_shop_coupon') . " where id in( $newcouponids ) and uniacid=:uniacid", array(':uniacid' => $_W['uniacid']), 'id');

                                foreach ($item['data'] as $childid=>&$child){
                                    $couponid = $child['couponid'];
                                    $coupon = $coupons[$couponid];

                                    if(empty($coupon)||empty($coupon['gettype'])){
                                        unset($item['data'][$childid]);
                                    }else{
                                        // 更新优惠券信息
                                        $child['name'] = $coupon['couponname'];

                                        if($coupon['coupontype']==0){
                                            if($coupon['enough']>0) {
                                                $child['desc'] ='满'.((float)$coupon['enough']).'元可用';
                                            }else {
                                                $child['desc'] ='无门槛使用';
                                            }
                                        }
                                        elseif($coupon['coupontype']==1){
                                            if($coupon['enough']>0) {
                                                $child['desc'] ='充值满'.((float)$coupon['enough']).'元可用';
                                            }else {
                                                $child['desc'] ='充值任意金额';
                                            }
                                        }

                                        if($coupon['backtype']==0){
                                            $child['price']='￥'.((float)$coupon['deduct']);
                                        }
                                        elseif($coupon['backtype']==1){
                                            $d['price']=((float)$coupon['discount']).'折 ';
                                        }
                                        elseif($coupon['backtype']==2){
                                            $values = 0;
                                            if (!empty($coupon['backmoney']) && $coupon['backmoney'] > 0) {
                                                $values = $values + $coupon['backmoney'];
                                            }
                                            if (!empty($coupon['backcredit']) && $coupon['backcredit'] > 0) {
                                                $values = $values + $coupon['backcredit'];
                                            }
                                            if (!empty($coupon['backredpack']) && $coupon['backredpack'] > 0) {
                                                $values = $values + $coupon['backredpack'];
                                            }
                                            $child['price'] = '￥'.$values;
                                        }
                                    }
                                }
                                unset($child, $coupon, $couponid);
                            }
                        }
                    }
					elseif (empty($item['id'])){
						unset($page['data']['items'][$itemid]);
					}
				}

				unset($item);

				$this->savePage($page['id'], $page['data'], false);
			}

			// 手机端 循环第一遍  遍历 公用模块 处理
			if($mobile && !empty($page['data']['items']) && is_array($page['data']['items'])) {
				$tempmod = array();
				foreach ($page['data']['items'] as $itemid=>$item) {
					if($item['id']=='diymod') {
						$modid = $item['params']['modid'];
						$diymod = $this->getPage($modid);
						if(!empty($diymod) && !empty($diymod['data'])) {
							$tempmod[$itemid] = $diymod['data']['items'];
						} else {
							unset($page['data']['items'][$itemid]);
						}
					}
				}

				if(!empty($tempmod)) {
					$newmod = array();
					foreach ($page['data']['items'] as $itemid=>$item) {
						if($item['id']=='diymod'){
							$newmod = array_merge($newmod, $tempmod[$itemid]);
						}else{
							$newmod[$itemid] = $item;
						}
					}
				}
				if(!empty($newmod) && is_array($newmod)) {
					$page['data']['items'] = $newmod;
				}

			}

			if($mobile && !empty($page['data']['items']) && is_array($page['data']['items'])) {
				// 手机端 循环第二遍 执行 临时赋值
				foreach ($page['data']['items'] as $itemid=>&$item) {
					if($item['id']=='goods') {
					    // 判断浏览权限
                   
						if($item['params']['goodsdata']=='0') {
                            if(!empty($item['data']) && is_array($item['data'])) {
                                $goodsids = array();
                                foreach ($item['data'] as $index => $data) {
                                    if (!empty($data['gid'])) {
                                        $goodsids[] = $data['gid'];
                                    }
                                }
                                if (!empty($goodsids) && is_array($goodsids)) {
                                    $newgoodsids = implode(',', $goodsids);
                                    if ($creditshop) {
                                        $goods = pdo_fetchall("select id, showlevels, showgroups from " . tablename('ewei_shop_creditshop_goods') . " where id in( $newgoodsids ) and status=1 and deleted=0 and uniacid=:uniacid order by displayorder desc ", array(':uniacid' => $_W['uniacid']));
                                    } else {
                                        $goods = pdo_fetchall("select id,brief_desc, showlevels, showgroups from " . tablename('ewei_shop_goods') . " where id in( $newgoodsids ) and status=1 and deleted=0 and checked=0 and uniacid=:uniacid order by displayorder desc ", array(':uniacid' => $_W['uniacid']));
                                    }
                            
                                    if (!empty($goods) && is_array($goods)) {
                                        foreach ($item['data'] as $childid=>$childgoods) {
                                            foreach ($goods as $index => $good) {
                                                if ($good['id'] == $childgoods['gid']) {
                                                    $showgoods = m('goods')->visit($good, $this->member);
                                                    if (empty($showgoods)) {
                                                        unset($item['data'][$childid]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
						elseif($item['params']['goodsdata']=='1') {
							// 根据条件读取商品分类里的商品 并进行临时赋值
							$limit = $item['params']['goodsnum'];
							$cateid = $item['params']['cateid'];
							if(!empty($cateid)) {
                                $orderby = ' displayorder desc, createtime desc';
                                $goodssort = $item['params']['goodssort'];
                                if(!empty($goodssort)) {
                                    if($goodssort==1) {	// 销量
                                        $orderby = empty($item['params']['goodstype']) ? ' sales desc, displayorder desc' : ' joins desc, displayorder desc';
                                    }
                                    elseif($goodssort==2) {	// 价格降序
                                        $orderby = empty($item['params']['goodstype']) ? ' minprice desc, displayorder desc' : ' minmoney desc, displayorder desc';
                                    }
                                    elseif($goodssort==3) {	// 价格升序
                                        $orderby = empty($item['params']['goodstype']) ? ' minprice asc, displayorder desc' : ' minmoney asc, displayorder desc';
                                    }
                                }

							    if(empty($item['params']['goodstype'])){
                                    $goodslist = m('goods')->getList(array(
                                        'cate'=>$cateid,
                                        'pagesize'=>$limit,
                                        'page'=>1,
                                        'order'=>$orderby
                                    ));
                                    $goods = $goodslist['list'];

                                }else{
                                    $goods = pdo_fetchall("select id, title,isnodiscount,discounts, thumb, price as productprice, minmoney as minprice, mincredit, total, showlevels, showgroups, `type`, goodstype from " . tablename('ewei_shop_creditshop_goods') . " where cate=:cate and status=1 and deleted=0 and uniacid=:uniacid order by {$orderby} limit ".$limit, array(':cate'=>$cateid, ':uniacid' => $_W['uniacid']));
                                }

								$item['data'] = array();
								if(!empty($goods) && is_array($goods)) {
                                    
									foreach ($goods as $index=>$good) {
                                        $showgoods = m('goods')->visit($good, $this->member);
                                      
                                        if(!empty($showgoods)){
                                            $childid = rand(1000000000, 9999999999);
                                            $childid = 'C' . $childid;
                                            if(mb_strlen($good['brief_desc'],"utf-8")>38){
                                                $newStr = mb_substr($good['brief_desc'],0,38,"UTF8").".....";
                                                $good['brief_desc']=$newStr;
                                            }
                                            $item['data'][$childid] = array(
                                                'thumb'=>$good['thumb'],
                                                'brief_desc'=>empty($good['brief_desc']) ? "":trim($good['brief_desc']),
                                                'title'=>$good['title'],
                                                'price'=>$good['minprice'],
                                                'gid'=>$good['id'],
                                                'total'=>$good['total'],
                                                'bargain'=>$good['bargain'],
                                                'productprice'=>$good['productprice'],
                                                'credit'=>$good['mincredit'],
                                                'ctype'=>$good['type'],
                                                'gtype'=>$good['goodstype']
                                            );
                                        }
									}

								}
							} else {
								$item['data'] = array();
							}
						}
						elseif($item['params']['goodsdata']=='2' && empty($item['params']['goodstype'])) {
							// 根据条件读取商品分组里的商品 并进行临时赋值
							$limit = $item['params']['goodsnum'];
							$groupid = intval($item['params']['groupid']);

							if(!empty($groupid)) {
								$group = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_goods_group') . " WHERE id=:id and uniacid=:uniacid limit 1 ", array(':id'=>$groupid, ':uniacid'=>$_W['uniacid']));
							}
							$item['data'] = array();
							if(!empty($group) && !empty($group['goodsids'])) {

								$orderby = ' order by displayorder desc';
								$goodssort = $item['params']['goodssort'];
								if(!empty($goodssort)) {
									if($goodssort==1) {	// 销量
                                        $orderby = empty($item['params']['goodstype']) ? ' order by sales desc, displayorder desc' : ' order by joins desc, displayorder desc';
									}
									elseif($goodssort==2) {	// 价格降序
                                        $orderby = empty($item['params']['goodstype']) ? ' order by  minprice desc, displayorder desc' : ' order by  minmoney desc, displayorder desc';
									}
									elseif($goodssort==3) {	// 价格升序
                                        $orderby = empty($item['params']['goodstype']) ? ' order by  minprice asc, displayorder desc' : ' order by  minmoney asc, displayorder desc';
									}
								}

								$goodsids = $group['goodsids'];
								$goods = pdo_fetchall("select id, brief_desc,title,isnodiscount,discounts thumb, minprice, sales, total, showlevels, showgroups, bargain from " . tablename('ewei_shop_goods') . " where id in( $goodsids ) and status=1 and `deleted`=0 and `status`=1 and uniacid=:uniacid " . $orderby . " limit {$limit}", array(':uniacid' => $_W['uniacid']));
								if(!empty($goods) && is_array($goods)) {

                                    $level = m('member')->getLevel($_W['openid']);
									foreach ($goods as $index=>$good) {
                                        $showgoods = m('goods')->visit($good, $this->member);
                                        if(!empty($showgoods)){
                                            /*
                                            $memberprice=0;
                                            $memberprice = m('goods')->getMemberPrice($good, $level);
                                            if($good['minprice']>$memberprice && $memberprice!=0){
                                                $good['minprice']=$memberprice;
                                            }*/
                                            $childid = rand(1000000000, 9999999999);
                                            $childid = 'C' . $childid;
                                            if(mb_strlen($good['brief_desc'],"utf-8")>38){
                                                $newStr = mb_substr($good['brief_desc'],0,38,"UTF8").".....";
                                                $good['brief_desc']=$newStr;
                                            }
                                            $item['data'][$childid] = array(
                                                'thumb'=>$good['thumb'],
                                                'brief_desc'=>empty($good['brief_desc'])?"":trim($good['brief_desc']),
                                                'title'=>$good['title'],
                                                'price'=>$good['minprice'],
                                                'gid'=>$good['id'],
                                                'total'=>$good['total'],
                                                'bargain'=>$good['bargain']
                                            );
                                        }
									}
								}
							}
						}
						elseif($item['params']['goodsdata']>2) {
                            $args = array(
                                'pagesize'=>$item['params']['goodsnum'],
                                'page'=>1,
                                'order' => ' displayorder desc, createtime desc'
                            );

                            $goodssort = $item['params']['goodssort'];
                            if(!empty($goodssort)) {

                                if($goodssort==1) {	// 销量
                                    $args['order'] = empty($item['params']['goodstype']) ? ' sales desc, displayorder desc' : ' joins desc, displayorder desc';
                                }
                                elseif($goodssort==2) {	// 价格降序
                                    $args['order'] = empty($item['params']['goodstype']) ? ' minprice desc, displayorder desc' : 'minmoney desc, mincredit desc, displayorder desc';
                                }
                                elseif($goodssort==3) {	// 价格升序
                                    $args['order'] = empty($item['params']['goodstype']) ? ' minprice asc, displayorder desc' : 'minmoney asc, mincredit asc, displayorder desc';
                                }
                            }

						    if(empty($item['params']['goodstype'])){
                                if($item['params']['goodsdata']==3){
                                    $args['isnew'] = 1;
                                }
                                elseif($item['params']['goodsdata']==4){
                                    $args['ishot'] = 1;
                                }
                                elseif($item['params']['goodsdata']==5){
                                    $args['isrecommand'] = 1;
                                }
                                elseif($item['params']['goodsdata']==6){
                                    $args['isdiscount'] = 1;
                                }
                                elseif($item['params']['goodsdata']==7){
                                    $args['issendfree'] = 1;
                                }
                                elseif($item['params']['goodsdata']==8){
                                    $args['istime'] = 1;
                                }
                                $goodslist = m('goods')->getList($args);
                                $goods = $goodslist['list'];
                            }else{
                                $condition = " and status=1 and deleted=0 and uniacid=:uniacid ";
                                $params = array(
                                    'uniacid'=>$_W['uniacid'],
                                    'uniacid'=>$_W['uniacid'],
                                );
                                if($item['params']['goodsdata']==5){
                                    $condition .= " and isrecommand=1 ";
                                }
                                elseif($item['params']['goodsdata']==9){
                                    $condition .= " and type=0 ";
                                }
                                elseif($item['params']['goodsdata']==10){
                                    $condition .= " and type=1 ";
                                }
                                $goods = pdo_fetchall("select id, title, thumb, price as productprice, minmoney as minprice, mincredit, total, showlevels, showgroups, `type`, goodstype from " . tablename('ewei_shop_creditshop_goods') . " where 1 {$condition} order by {$args['order']} limit ".$args['pagesize'], $params);
                            }

							$item['data'] = array();
							if(!empty($goods) && is_array($goods)) {
							    unset($index);
								foreach ($goods as $index=>$good) {
                                    $showgoods = m('goods')->visit($good, $this->member);
                                    if(!empty($showgoods)){
                                        $childid = rand(1000000000, 9999999999);
                                        $childid = 'C' . $childid;
                                        if(mb_strlen($good['brief_desc'],"utf-8")>38){
                                            $newStr = mb_substr($good['brief_desc'],0,38,"UTF8").".....";
                                            $good['brief_desc']=$newStr;
                                        }
                                        $item['data'][$childid] = array(
                                            'thumb'=>$good['thumb'],
                                            'title'=>$good['title'],
                                            'brief_desc'=>empty($good['brief_desc'])?"":trim($good['brief_desc']),
                                            'price'=>$good['minprice'],
                                            'gid'=>$good['id'],
                                            'total'=>$good['total'],
                                            'bargain'=>$good['bargain'],
                                            'productprice'=>$good['productprice'],
                                            'credit'=>$good['mincredit'],
                                            'ctype'=>$good['type'],
                                            'gtype'=>$good['goodstype']
                                        );
                                    }
								}
							}
						}
					}
					elseif($item['id']=='notice') {
						if($item['params']['noticedata']=='0') {
							$limit = !empty($item['params']['noticenum']) ? $item['params']['noticenum'] : 5;
							// 执行读取 商城公告 并进行临时赋值
                            if(!empty($page['merch'])){
                                $notices = pdo_fetchall("select id, title, link, thumb from " . tablename('ewei_shop_merch_notice') . " where uniacid=:uniacid and status=1 order by displayorder desc limit {$limit}", array(':uniacid' => $_W['uniacid']));
                            }else{
                                $notices = pdo_fetchall("select id, title, link, thumb from " . tablename('ewei_shop_notice') . " where uniacid=:uniacid and status=1 order by displayorder desc limit {$limit}", array(':uniacid' => $_W['uniacid']));
                            }
							$item['data'] = array();
							if(!empty($notices) && is_array($notices)) {
								foreach ($notices as $index=>$notice) {
									$childid = rand(1000000000, 9999999999);
									$childid = 'C' . $childid;
									$item['data'][$childid] = array(
										'id'=>$notice['id'],
										'title'=>$notice['title'],
										'linkurl'=>$notice['link']
									);
								}
							}
						}
					}
					elseif($item['id']=='richtext') {
						$content = $item['params']['content'];
						if (!empty($content)) {
							$content = base64_decode($content);
							$content = m('ui')->lazy($content);
							$item['params']['content'] = base64_encode($content);
						}
					}
					elseif($item['id']=='listmenu'){
                        // 处理是否显示
                        if(empty($item['data']) || !is_array($item['data'])){
                            unset($page['data']['items'][$itemid]);
                        }
                        foreach ($item['data'] as $childid=>&$child){
                            if(empty($child['text'])){
                                unset($item['data'][$childid]);
                            }
                            if(!empty($child['linkurl'])){
                                $linkurl = $this->judge('url', $child['linkurl']);
                                if(!$linkurl){
                                    unset($item['data'][$childid]);
                                }
                                $child['dotnum'] = $this->judge('dot', $child['linkurl']);
                            }
                        }
                        unset($child);
                    }
                    elseif($item['id']=='member'){
                        $member = $this->member;
                        $item['info'] =array(
                            'avatar' => $member['avatar'],
                            'nickname' => $member['nickname'],
                            'levelname' => $member['levelname'],
                            'textmoney' => $_W['shopset']['trade']['moneytext'],
                            'textcredit' => $_W['shopset']['trade']['credittext'],
                            'money' => $member['credit2'],
                            'credit' => intval($member['credit1'])
                        );
                    }
                    elseif($item['id']=='icongroup'){
                        if(empty($item['data']) || !is_array($item['data'])){
                            unset($page['data']['items'][$itemid]);
                        }
                        foreach ($item['data'] as $childid=>&$child){
                            if(empty($child['iconclass'])){
                                unset($item['data'][$childid]);
                            }
                            if(!empty($child['linkurl'])){
                                $linkurl = $this->judge('url', $child['linkurl']);
                                if(!$linkurl){
                                    unset($item['data'][$childid]);
                                }else{
                                    $child['dotnum'] = $this->judge('dot', $child['linkurl']);
                                }
                            }
                        }
                        unset($child);
                    }
                    elseif($item['id']=='bindmobile'){
                        $member = $this->member;
                        if(empty($member) || !empty($member['mobileverify'])){
                            unset($page['data']['items'][$itemid]);
                        }else{
                            $item['params']['linkurl'] = mobileUrl('member/bind');
                        }
                    }
                    elseif($item['id']=='logout'){
                        if(is_weixin()){
                            unset($page['data']['items'][$itemid]);
                        }else{
                            $member = $this->member;
                            if(empty($member)){
                                unset($page['data']['items'][$itemid]);
                            }else{
                                $item['params']['bindurl'] = !empty($member['mobileverify']) ? mobileUrl('member/changepwd') : mobileUrl('member/bind');
                                $item['params']['logouturl'] = mobileUrl('account/logout');
                            }
                        }
                    }
                    elseif($item['id']=='memberc'){
                        $member = $this->member;
                        $commission = $this->commission;

                        $item['params']['avatar'] = $member['avatar'];
                        $item['params']['nickname'] = $member['nickname'];
                        $item['params']['levelname'] = $member['commissionlevelname'];
                        $item['params']['textyaun'] = $commission['set']['texts']['yuan'];
                        $item['params']['textsuccesswithdraw'] = $commission['set']['texts']['commission_pay'];
                        $item['params']['textcanwithdraw'] = $commission['set']['texts']['commission_ok'];
                        $item['params']['successwithdraw'] = number_format($member['commission_pay'], 2);
                        $item['params']['canwithdraw'] = number_format($member['commission_ok'], 2);
                        $item['params']['upname'] = $commission['set']['texts']['up'];
                        $item['params']['upmember'] = empty($member['up'])?"总店":$member['up']['nickname'];
                    }
                    elseif($item['id']=='blockgroup'){
                        if(empty($item['data']) || !is_array($item['data'])){
                            unset($item);
                        }
                        foreach ($item['data'] as $childid=>&$child){
                            if(empty($child['iconclass'])){
                                unset($item['data'][$childid]);
                            }
                            $child['tipnum'] = "";
                            $child['tiptext'] = "";
                            if(!empty($child['linkurl'])){
                                $linkurl = $this->judge('url', $child['linkurl']);
                                if(!$linkurl){
                                    unset($item['data'][$childid]);
                                }else{
                                    $child['tipnum'] = $this->judge('tipnum', $child['linkurl']);
                                    $child['tiptext'] = $this->judge('tiptext', $child['linkurl']);
                                }
                            }
                        }
                        unset($child);
                    }
                    elseif($item['id']=='menu' && !empty($item['style']['showtype'])){
                        if(!empty($item['data'])){
                            $swiperpage = empty($item['style']['pagenum'])?8:$item['style']['pagenum'];
                            $data_temp = array();
                            $k = 0; $i = 1;
                            foreach ($item['data'] as $childid=>$child){
                                $data_temp[$k][$childid] = $child;
                                if($i<$swiperpage){
                                    $i++;
                                }else{
                                    $i=1; $k++;
                                }
                            }
                            $item['data_temp'] = $data_temp;
                            unset($swiperpage, $data_temp, $k, $i);
                        }else{
                            unset($page['data']['items'][$itemid]);
                        }
                    }
                    elseif($item['id']=='picturew' && !empty($item['params']['showtype'])){
                        if(!empty($item['data'])){
                            $swiperpage = empty($item['style']['pagenum'])?2:$item['style']['pagenum'];
                            $data_temp = array();
                            $k = 0; $i = 1;
                            foreach ($item['data'] as $childid=>$child){
                                $data_temp[$k][$childid] = $child;
                                if($i<$swiperpage){
                                    $i++;
                                }else{
                                    $i=1; $k++;
                                }
                            }
                            $item['data_temp'] = $data_temp;
                            unset($swiperpage, $data_temp, $k, $i);
                        }else{
                            unset($page['data']['items'][$itemid]);
                        }
                    }elseif($item['id']=='seckillgroup'){
                        $item['data'] = plugin_run('seckill::getTaskSeckillInfo');
                    }
				}
				unset($item);
			}
          
			if ($mobile && !empty($page['data'])){
                $page['data'] = json_encode($page['data']);
                $page['data'] = $this->url($page['data']);
                $page['data'] = json_decode($page['data'], true);
            }
		}
         

		return $page;
	}

    public 	function savePage($id, $data, $update=true) {
		global $_W;

		$keyword = $data['page']['keyword'];
		if(!empty($keyword) && $update) {
			$result = m('common')->keyExist($keyword);
			if(!empty($result)) {
				if($result['name']!='ewei_shopv2:diypage:'.$id) {
					show_json(0, '关键字已存在！');
				}
			}else{
				if(!empty($result)) {
					show_json(0, '关键字已存在！');
				}
			}
		}

		$pagedata = json_encode($data);
        if($update){
            $pagedata = $this->saveImg($pagedata);
        }

		$diypage = array(
			'data'=>base64_encode($pagedata),
			'name'=>$data['page']['name'],
			'keyword'=>$data['page']['keyword'],
			'type'=>$data['page']['type'],
			'diymenu'=>$data['page']['diymenu'],
            'catid'=>$data['page']['catid'],
		);
		if(!empty($id)) {
			if($update) {
				$diypage['lastedittime'] = time();
			}
			pdo_update('ewei_shop_diypage', $diypage, array('id'=>$id, 'uniacid'=>$_W['uniacid']));
			$savetype = 'update';
		}else{
			$diypage['uniacid'] = $_W['uniacid'];
			$diypage['createtime'] = time();
			$diypage['lastedittime'] = time();
            $diypage['merch']=intval($_W['merchid']);
			pdo_insert('ewei_shop_diypage', $diypage);
			$id = pdo_insertid();
			$savetype = 'insert';
		}
		if(!empty($keyword) && $update) {
			// 处理关键字
			$rule = pdo_fetch("select * from " . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':name' => "ewei_shopv2:diypage:" . $id));

			if (!empty($rule)) {
				pdo_update('rule_keyword', array('content' => $keyword), array('rid' => $rule['id']));
			} else {
				$rule_data = array(
					'uniacid' => $_W['uniacid'],
					'name' => 'ewei_shopv2:diypage:' . $id,
					'module' => 'ewei_shopv2',
					'displayorder' => 0,
					'status' => 1
				);
				pdo_insert('rule', $rule_data);
				$rid = pdo_insertid();
				$keyword_data = array(
					'uniacid' => $_W['uniacid'],
					'rid' => $rid,
					'module' => 'ewei_shopv2',
					'content' => $keyword,
					'type' => 1,
					'displayorder' => 0,
					'status' => 1
				);
				pdo_insert('rule_keyword', $keyword_data);
			}
		}

		if($update) {
			$item = pdo_fetch("select id, type, name, keyword from " . tablename('ewei_shop_diypage') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));

			if($savetype=='update') {
				if($item['type']==1) {
					plog('diypage.page.diy.edit', '更新自定义页面 id: '.$item['id'].'  名称:'.$item['name'].'  关键字: '.$item['keyword']);
				}
				elseif ($item['type']>1 && $item['type']<99) {
					plog('diypage.page.sys.edit', '更新系统页面 id: '.$item['id'].'  名称:'.$item['name'].'  关键字: '.$item['keyword']);
				}
				elseif ($item['type']==99) {
					plog('diypage.page.mod.edit', '更新公用模块 id: '.$item['id'].'  名称:'.$item['name'].'  关键字: '.$item['keyword']);
				}
			}
			elseif ($savetype=='insert') {
				if($item['type']==1) {
					plog('diypage.page.diy.add', '添加自定义页面 id: '.$item['id'].'  名称:'.$item['name'].'  关键字: '.$item['keyword']);
				}
				elseif ($item['type']>1 && $item['type']<99) {
					plog('diypage.page.sys.add', '添加系统页面 id: '.$item['id'].'  名称:'.$item['name'].'  关键字: '.$item['keyword']);
				}
				elseif ($item['type']==99) {
					plog('diypage.page.mod.add', '添加公用模块 id: '.$item['id'].'  名称:'.$item['name'].'  关键字: '.$item['keyword']);
				}
			}

			$result = array('id'=>$id);

			if($savetype=='insert') {
				if($diypage['type']==1) {
					$pagetype = 'diy';
				}
				elseif ($diypage['type']>1 && $diypage['type']<99) {
					$pagetype = 'sys';
				}
				elseif ($diypage['type']==99) {
					$pagetype = 'mod';
				}
				$result['jump'] = webUrl('diypage/page') . '.' . $pagetype . '.edit&id=' . $id;
			}

			show_json(1, $result);
		}

	}

    public function  delPage($id) {
		global $_W;

		if(empty($id)) {
			show_json(1);
		}

		$items = pdo_fetchall("SELECT id,name,keyword FROM " . tablename('ewei_shop_diypage') . " WHERE id in( $id ) and uniacid=:uniacid ", array(':uniacid'=>$_W['uniacid']));

		foreach ($items as $item) {
			pdo_delete('ewei_shop_diypage', array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
			// 执行删除 关键字
			if($item['type']==1){
				plog('diypage.page.diy.delete', '删除自定义页面 id: '.$item['id'].'  名称:'.$item['name']);
			}
			elseif($item['type']>1&&$item['type']<99){
				plog('diypage.page.sys.delete', '删除系统页面 id: '.$item['id'].'  名称:'.$item['name']);
			}
			if($item['type']==99){
				plog('diypage.page.mod.delete', '删除公用模块 id: '.$item['id'].'  名称:'.$item['name']);
			}

			// 执行删除关键字
			$keyword = pdo_fetch("SELECT * FROM " . tablename('rule_keyword') . " WHERE content=:content and module=:module and uniacid=:uniacid limit 1 ", array(':content' => $item['keyword'], ':module' => 'ewei_shopv2', ':uniacid' => $_W['uniacid']));
			if (!empty($keyword)) {
				pdo_delete('rule_keyword', array('id' => $keyword['id']));
				pdo_delete('rule', array('id' => $keyword['rid']));
			}
		}

		show_json(1);
	}

    public function saveImg($str) {
	    if(empty($str) || is_array($str)){
	        return;
        }

        // 处理 元素中还有imgurl的图片
        $str = preg_replace_callback("/\"imgurl\"\:\"([^\'\" ]+)\"/", function($matches){
            $preg = !empty($matches[1]) ? istripslashes($matches[1]) : "";
            if(empty($preg)){
                return  "\"imgurl\":\"\"";
            }
            $newUrl = save_media($preg);
            return "\"imgurl\":\"".$newUrl."\"";
        }, $str);

        // 处理 富文本中的图片
        $str = preg_replace_callback("/\"content\"\:\"([^\'\" ]+)\"/", function($matches){
            $preg = !empty($matches[1]) ? istripslashes($matches[1]) : "";
            $preg = base64_decode($preg);
            if(empty($preg)){
                return  "\"content\":\"\"";
            }
            $preg = m('common')->html_images($preg);
            $newcontent = base64_encode($preg);
            return "\"content\":\"".$newcontent."\"";
        }, $str);

        return $str;
    }

    public function saveTemp($temp){
		global $_W, $_GPC;

		if(empty($temp) || empty($temp['data'])){
			show_json(0, '保存失败，数据为空。');
		}
		$temp['uniacid'] = $_W['uniacid'];
		$temp['data']['page']['keyword'] = "";
		$temp['data'] = base64_encode(json_encode($temp['data']));

		pdo_insert('ewei_shop_diypage_template', $temp);

		if($temp['type']==1) {
			plog('diypage.page.diy.savetemp', '另存为模板 名称:'.$temp['name']);
		}
		elseif ($temp['type']>1&&$temp['type']<99) {
			plog('diypage.page.sys.savetemp', '另存为模板 名称:'.$temp['name']);
		}

		show_json(1);
	}

    public function verify($do, $pagetype) {
		global $_W, $_GPC;

		$id = intval($_GPC['id']);
		$tid = intval($_GPC['tid']);
		$type = intval($_GPC['type']);
		$result = array(
			'do'=>$do,
			'id'=>$id,
			'tid'=>$tid,
			'type'=>$type,
			'pagetype'=>$pagetype
		);

		if($do=='add') {
			if(!empty($type)) {
				$getpagetype = $this->getPageType($type);
				$getpagetype = $getpagetype['pagetype'];
				if($getpagetype!=$pagetype) {
					$url = webUrl('diypage/page') . '.' . $pagetype . '.add';
					header('location: ' . $url);
					exit;
				}
			} else {
				if($pagetype=='diy') {
					$result['type'] = 1;
				}
				elseif ($pagetype=='sys') {
					$result['type'] = empty($_GPC['type']) ? 2 : intval($_GPC['type']);
				}
				elseif ($pagetype=='mod') {
					$result['type'] = 99;
				}
			}
			if(!empty($tid)) {
				$template = pdo_fetch("select * from " . tablename('ewei_shop_diypage_template') . ' where id=:id and (uniacid=:uniacid or uniacid=0) limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $tid));
				if(!empty($template) && $template['type']==$result['type']) {
					$result['type'] =  $template['type'];
					$result['template'] = $template;
					return $result;
				}
			}
		}
		elseif ($do=='edit') {
			if (empty($id)) {
				$url = webUrl('diypage/page') . '.' . $pagetype . '.add';
				header('location: ' . $url);
				exit;
			}
			$page = $this->getPage($id);
			if(empty($page)) {
				$url = webUrl('diypage/page') . '.' . $pagetype . '.add';
				header('location: ' . $url);
				exit;
			}
			// 如果页面 类型不等于当前类型  执行跳转

			if($pagetype=='diy' && $page['type']!=1) {
				$type = $this->getPageType($page['type']);
				$url = webUrl('diypage/page') . '.' . $type['pagetype'] . '.edit&id=' . $id;
				header('location: ' . $url);
				exit;
			}
			elseif ($pagetype=='sys' && ($page['type']<2 || $page['type']>=99)) {
				$type = $this->getPageType($page['type']);
				$url = webUrl('diypage/page') . '.' . $type['pagetype'] . '.edit&id=' . $id;
				header('location: ' . $url);
				exit;
			}
			elseif ($pagetype=='mod' && $page['type']!=99) {
				$type = $this->getPageType($page['type']);
				$url = webUrl('diypage/page') . '.' . $type['pagetype'] . '.edit&id=' . $id;
				header('location: ' . $url);
				exit;
			}
			$result['page'] = $page;
			$result['type'] = $page['type'];
		}
		return $result;
	}

    public function getPageType($type=null) {
		$pagetype =  array(
			1 => array('name'=>'自定义', 'pagetype'=>'diy', 'class'=>''),
			2 => array('name'=>'商城首页', 'pagetype'=>'sys', 'class'=>'success'),
			3 => array('name'=>'会员中心', 'pagetype'=>'sys', 'class'=>'primary'),
			4 => array('name'=>'分销中心', 'pagetype'=>'sys', 'class'=>'warning'),
			5 => array('name'=>'商品详情页', 'pagetype'=>'sys', 'class'=>'danger'),
			6 => array('name'=>'积分商城', 'pagetype'=>'sys', 'class'=>'info'),
            7 => array('name'=>'整点秒杀', 'pagetype'=>'sys', 'class'=>'danger'),
            8 => array('name'=>'大转盘', 'pagetype'=>'sys', 'class'=>'games'),
			99 => array('name'=>'公用模块', 'pagetype'=>'mod', 'class'=>''),
		);
		if(!empty($type)) {
			return $pagetype[$type];
		}
		return $pagetype;
	}

    public function setShare($page){
		global $_W, $_GPC;

		if(empty($page)){
			return;
		}
		// 定义分享数据

        $urlpage = 'diypage';
        $urlparm = array('id'=>$page['id']);

		$_W['shopshare'] = array(
			'title' => $_W['shopset']['shop']['name'],
			'imgUrl' => tomedia($_W['shopset']['shop']['logo']),
			'desc' => $_W['shopset']['shop']['description'],
		);

        if ($page['type']==1 || $page['type']==2){
            $_W['shopshare']['title'] = $page['data']['page']['title'];
            $_W['shopshare']['imgUrl'] = tomedia($page['data']['page']['icon']);
            $_W['shopshare']['desc'] = $page['data']['page']['desc'];
        }
        elseif ($page['type']==5){
            $urlpage = 'goods/detail';
            $urlparm = array('id'=>$_GPC['id']);
        }

		if (p('commission')) {
			$set = p('commission')->getSet();
			if (!empty($set['level'])) {
				$member = m('member')->getMember($_W['openid']);
				if (!empty($member) && $member['status'] == 1 && $member['isagent'] == 1) {
                    $urlparm['mid'] = intval($member['id']);
				} else if (!empty($_GPC['mid'])) {
                    $urlparm['mid'] = intval($_GPC['mid']);
				}
                $_W['shopshare']['link'] = mobileUrl($urlpage, $urlparm, true);
			}
		}else{
            $_W['shopshare']['link'] = mobileUrl($urlpage, $urlparm, true);
        }
	}

	public function calculate($str=null, $member=false){
	    global $_W;

	    if(empty($str)){
	        return;
        }
        // 判断 处理 插件
        $plugins = array();
        // 分销中心
        if(strexists($str, "r=commission")){
            $plugins['commission'] = false;
            $plugin_commissiom = p('commission');
            if($plugin_commissiom){
                $plugin_commissiom_set = $plugin_commissiom->getSet();
                if(!empty($plugin_commissiom_set['level'])){
                    $plugins['commission'] = true;
                }
                $member = p('commission')->getInfo($_W['openid'], array('total', 'ordercount0', 'ok', 'ordercount', 'wait', 'pay'));
                if(strexists($str, "r=commission.withdraw")){
                    $this->commission['commission_total'] = $member['commission_total'];
                }
                if(strexists($str, "r=commission.order")){
                    $this->commission['ordercount0'] = $member['ordercount0'];
                }
                if(strexists($str, "r=commission.log")){
                    $this->commission['applycount'] = pdo_fetchcolumn('select count(id) from ' . tablename('ewei_shop_commission_apply') . ' where mid=:mid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':mid' => $member['id']));
                }
                if(strexists($str, "r=commission.down")){
                    $level1 = $level2 = $level3 = 0;
                    $level1 = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where agentid=:agentid and uniacid=:uniacid limit 1'
                        , array(":agentid" => $member['id'], ':uniacid' => $_W['uniacid']));
                    if ($plugin_commissiom_set['level'] >= 2 && count($member['level1_agentids']) > 0) {
                        $level2 = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . " where agentid in( " . implode(',', array_keys($member['level1_agentids'])) . ") and uniacid=:uniacid limit 1"
                            , array(':uniacid' => $_W['uniacid']));
                    }
                    if ($plugin_commissiom_set['level'] >= 3 && count($member['level2_agentids']) > 0) {
                        $level3 = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . " where agentid in( " . implode(',', array_keys($member['level2_agentids'])) . ") and uniacid=:uniacid limit 1"
                            , array(':uniacid' => $_W['uniacid']));
                    }
                    $this->commission['downcount'] = $level1 + $level2 + $level3;
                }
                if(strexists($str, "r=commission.qrcode")){
                    $plugins['commission_qrcode'] = false;
                    if(!$plugin_commissiom_set['closed_qrcode']){
                        $plugins['commission_qrcode'] = true;
                    }
                }
                if(strexists($str, "r=commission.myshop.set")){
                    $plugins['commission_myshop_set'] = false;
                    if(empty($plugin_commissiom_set['closemyshop'])){
                        $plugins['commission_myshop_set'] = true;
                    }
                }
                if(strexists($str, "r=commission.rank")){
                    $plugins['commission_rank_status'] = false;
                    if(!empty($plugin_commissiom_set['rank']['status'])){
                        $plugins['commission_rank_status'] = true;
                    }
                }
            }
        }

        // 积分商城
        if(strexists($str, "r=creditshop")){
            $plugins['creditshop'] = true;
            if($member) {
                $plugin_creditshop = p('creditshop');
                if ($plugin_creditshop) {
                    $plugin_creditshop_set = $plugin_creditshop->getSet();
                    if (empty($plugin_creditshop_set['centeropen'])) {
                        $plugins['creditshop'] = false;
                    }
                }
            }
        }
        // 人人拼团
        if(strexists($str, "r=groups")){
            $plugins['groups'] = true;
        }
        // 全民股东
        if(strexists($str, "r=globonus")){
            $plugins['globonus'] = false;
            $plugin_globonus = p('globonus');
            if($plugin_globonus){
                $plugin_globonus_set = $plugin_globonus->getSet();
                if(!empty($plugin_globonus_set['open'])){
                    $plugins['globonus'] = true;
                }
                if($member && empty($plugin_globonus_set['openmembercenter'])){
                    $plugins['globonus'] = false;
                }
            }
        }
        // 区域代理
        if(strexists($str, "r=abonus")){
            $plugins['abonus'] = false;
            $plugin_abonus = p('abonus');
            if($plugin_abonus){
                $plugin_abonus_set = $plugin_abonus->getSet();
                if(!empty($plugin_abonus_set['open'])){
                    $plugins['abonus'] = true;
                }
                if($member && empty($plugin_abonus_set['openmembercenter'])){
                    $plugins['abonus'] = false;
                }
            }
        }
        // 联合创始人
        if(strexists($str, "r=author")){
            $plugins['author'] = false;
            $plugin_author = p('author');
            if($plugin_author){
                $plugin_author_set = $plugin_author->getSet();
                if(!empty($plugin_author_set['open'])){
                    $plugins['author'] = true;
                }
                if($member && empty($plugin_author_set['openmembercenter'])){
                    $plugins['author'] = false;
                }
            }
        }
        // 人人社区
        if(strexists($str, "r=sns")){
            $plugins['sns'] = true;
        }
        // 积分签到
        if(strexists($str, "r=sign")){
            $plugins['sign'] = false;
            $plugin_sign = p('sign');
            if($plugin_sign){
                $plugin_sign_set = $plugin_sign->getSet();
                if(!empty($plugin_sign_set['isopen'])){
                    $plugins['sign'] = true;
                }
                if($member && empty($plugin_sign_set['iscenter'])){
                    $plugins['sign'] = false;
                }
            }
        }
        // 帮助中心
        if(strexists($str, "r=qa")){
            $plugins['qa'] = true;
            if($member) {
                $plugin_qa = p('qa');
                if ($plugin_qa) {
                    $plugin_qa_set = $plugin_qa->getSet();
                    if (empty($plugin_qa_set['showmember'])) {
                        $plugins['qa'] = false;
                    }
                }
            }
        }
        // 优惠券
        if(strexists($str, 'r=coupon')){
            $plugins['coupon'] = false;
            $plugin_coupon_set = $_W['shopset']['coupon'];
            if(empty($plugin_coupon_set['closemember'])){
                $plugins['coupon'] = true;
            }
            if($member && empty($plugin_coupon_set['closecenter'])){
                $plugins['coupon'] = false;
            }
        }

        $this->plugin = $plugins;

        // 判断 处理 会员信息
        if(strexists($str, "memberc") && p('commission')){
            $member = p('commission')->getInfo($_W['openid'], array('total', 'ordercount0', 'ok', 'ordercount', 'wait', 'pay'));
            if(!empty($member)){
                $member['commissionlevel'] = p('commission')->getLevel($_W['openid']);
                $member['up'] = false;
                if (!empty($member['agentid'])) {
                    $member['up'] = m('member')->getMember($member['agentid']);
                }
                $this->commission['set'] = $commissionset = p('commission')->getSet();
                $member['commissionlevelname'] = empty($member['commissionlevel']) ? ( empty($commissionset['levelname'])?'普通等级':$commissionset['levelname'] ) : $member['commissionlevel']['levelname'];
            }

        }else{
            $member = m('member')->getMember($_W['openid']);
            $level = m('member')->getLevel($_W['openid']);
            $member['levelname'] = $level['levelname'];
        }

        $this->member = $member;

        // 判断 处理 订单数量信息
        $ordernum = array();
        if(strexists($str, "r=order") || strexists($str, 'r=member.cart') || strexists($str, 'r=coupon.my')){
            $params = array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']);
            $merch_plugin = p('merch');
            $merch_data = m('common')->getPluginset('merch');
            if(strexists($str, "status=0")){
                $condition = " status=0 ";
                if(!$merch_plugin && !$merch_data['is_openmerch']){
                    $condition .= " and isparent=0 ";
                }
                $ordernum['status0'] = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where '.$condition.' and openid=:openid and uniacid=:uniacid limit 1',$params);
            }
            if(strexists($str, "status=1")){
                $condition = " status=1 and refundid=0 ";
                if(!$merch_plugin && !$merch_data['is_openmerch']){
                    $condition .= " and isparent=0 ";
                }
                $ordernum['status1'] = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where '.$condition.' and openid=:openid and uniacid=:uniacid limit 1',$params);
            }
            if(strexists($str, "status=2")){
                $condition = " status=2 and refundid=0 ";
                if(!$merch_plugin && !$merch_data['is_openmerch']){
                    $condition .= " and isparent=0 ";
                }
                $ordernum['status2'] = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where '.$condition.' and openid=:openid and uniacid=:uniacid limit 1',$params);
            }
            if(strexists($str, "status=4")){
                $condition = " refundstate=1 ";
                if(!$merch_plugin && !$merch_data['is_openmerch']){
                    $condition .= " and isparent=0 ";
                }
                $ordernum['status4'] = pdo_fetchcolumn('select count(*) from '.tablename('ewei_shop_order').' where '.$condition.' and openid=:openid and uniacid=:uniacid limit 1',$params);
            }
            if(strexists($str, "r=member.cart")){
                $condition = " deleted=0 ";
                if(!$merch_plugin && !$merch_data['is_openmerch']){
                    $condition .= " and selected=1 ";
                }
                $ordernum['cartnum'] = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_member_cart') . ' where '.$condition.' and uniacid=:uniacid and openid=:openid ', $params);
            }
            if(strexists($str, "r=member.favorite")){
                $condition = " deleted=0 ";
                if(!$merch_plugin && !$merch_data['is_openmerch']){
                    $condition .= " and `type`=1 ";
                }
                $ordernum['favorite'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_favorite') . ' where '.$condition.' and uniacid=:uniacid and openid=:openid ', $params);
            }
            if(strexists($str, "r=sale.coupon.my")){
                $time = time();
                $sql = "select count(*) from ".tablename('ewei_shop_coupon_data')." d";
                $sql.=" left join ".tablename('ewei_shop_coupon')." c on d.couponid = c.id";
                $sql.=" where d.openid=:openid and d.uniacid=:uniacid and  d.used=0 "; //类型+最低消费+示使用
                $sql.=" and (   (c.timelimit = 0 and ( c.timedays=0 or c.timedays*86400 + d.gettime >=unix_timestamp() ) )  or  (c.timelimit =1 and c.timestart<={$time} && c.timeend>={$time})) order by d.gettime desc"; //有效期
                $ordernum['coupon'] = pdo_fetchcolumn($sql,array(':openid'=>$_W['openid'],':uniacid'=>$_W['uniacid']));
            }
        }
        $this->ordernum = $ordernum;
    }

	public function judge($type=null, $str=null){
	    if(empty($type) || empty($str)){
	        return;
        }
        elseif ($type=='url'){
            $plugin = $this->plugin;
            if(empty($plugin) || !is_array($plugin)){
                return true;
            }
            if(strexists($str, ".")){
                $str = str_replace(".", "_", $str);
            }
            foreach ($plugin as $key=>$val){
                if(strexists($str, $key) && !$val){
                    return false;
                    break;
                }
            }
            return true;
        }
        elseif ($type=='dot'){
            $order = $this->ordernum;
            if(strexists($str, "r=order")){
                if(strexists($str, "status=0")){
                    return $order['status0'];
                }
                elseif(strexists($str, "status=1")){
                    return $order['status1'];
                }
                elseif(strexists($str, "status=2")){
                    return $order['status2'];
                }
                elseif(strexists($str, "status=4")){
                    return $order['status4'];
                }
            }
            elseif(strexists($str, "r=member.cart")){
                return $order['cartnum'];
            }
            elseif(strexists($str, "r=member.favorite")){
                return $order['favorite'];
            }
            elseif(strexists($str, "r=sale.coupon.my")){
                return $order['coupon'];
            }
            return 0;
        }
        elseif ($type=='tipnum'){
            if(strexists($str, "r=commission.withdraw")){
                return $this->commission['commission_total'];
            }
            elseif(strexists($str, "r=commission.order")){
                return $this->commission['ordercount0'];
            }
            elseif(strexists($str, "r=commission.log")){
                return $this->commission['applycount'];
            }
            elseif(strexists($str, "r=commission.down")){
                return $this->commission['downcount'];
            }
            return "";
        }
        elseif ($type=='tiptext'){
            if(strexists($str, "r=commission.withdraw")){
                return $this->commission['set']['texts']['yuan'];
            }
            elseif(strexists($str, "r=commission.order") || strexists($str, "r=commission.log")){
                return "笔";
            }
            elseif(strexists($str, "r=commission.down")){
                return "人";
            }
        }
        return;
    }

    public function url($str){
        global $_W, $_GPC;

        if(empty($str)){
            return;
        }
        $mid = intval($_GPC['mid']);

        if(!empty($_W['openid'])){
            $myid = m('member')->getMid();
            if(!empty($myid)){
                $member = pdo_fetch("select id,isagent,status from". tablename('ewei_shop_member'). 'where id='.$myid);
                if(!empty($member['isagent']) && !empty($member['status'])){
                    $mid = $myid;
                }
            }
        }

        if(empty($mid)){
            return $str;
        }

          $str = preg_replace_callback("/\"linkurl\"\:\"([^\'\" ]+)\"/", function($matches) use($mid){
//        $str = preg_replace_callback("/\"linkurl\"\:\"?([^\'\" ]+).*?\"/", function($matches) use($mid){

            $preg = $matches[1];
            if(strexists($preg,"mid=")){
                return  "\"linkurl\":\"".$preg."\"";
            }
            if(!strexists($preg,"javascript")){
                $preg = preg_replace('/(&|\?)mid=[\d+]/', "", $preg);

                if(strexists($preg,"?")){
                    $newpreg = $preg."&mid=$mid";

                }else{
                    $newpreg = $preg."?mid=$mid";
                }
                return "\"linkurl\":\"".$newpreg."\"";
            }
        }, $str);

        return $str;
    }

    protected function verifymobile($id=0, $type=0){
        global $_GPC;
        if (empty($id) || empty($type)){
            return;
        }
        $diypagedata = m('common')->getPluginset('diypage');
        $page = $diypagedata['page'];
        if(empty($page)){
            return;
        }

        if(!empty($_GPC['preview'])){
            return;
        }

        if($_GPC['r']=='diypage'){
            if($type==2 && $page['home']==$id){
                header("location: ". mobileUrl(null, array('mid'=>$_GPC['mid'])));
            }
            elseif($type==3 && $page['member']==$id){
                header("location: ". mobileUrl('member', array('mid'=>$_GPC['mid'])));
            }
            elseif($type==4 && $page['commission']==$id){
                header("location: ". mobileUrl('commission', array('mid'=>$_GPC['mid'])));
            }
        }
        return;
    }

    public function detailPage($pageid=0) {
        global $_W;

        $set = $this->getSet();

        if(!empty($pageid)){
            $page = $this->getPage($pageid, true);
        }
        if(empty($pageid) || (!empty($pageid) && empty($page))){
            if(!empty($set['page']['detail'])){
                $page = $this->getPage($set['page']['detail'], true);
            }
        }

        if(empty($page) || !is_array($page) || !is_array($page['data']['items'])){
            return false;
        }
        $pageitems = $page['data']['items'];

        $background = $page['data']['page']['background'];
        $followbar = $page['data']['page']['followbar'];

        $detail_tab = array();
        $detail_navbar = array();

        $detail_seckill = array();
        foreach ($pageitems as $itemid=>$pageitem){
            if($pageitem['id']=='detail_tab'){
                $detail_tab = array('style'=>$pageitem['style'], 'params'=>$pageitem['params']);
                unset($pageitems[$itemid]);
            }
            elseif($pageitem['id']=='detail_navbar'){
                $detail_navbar = array('style'=>$pageitem['style'], 'params'=>$pageitem['params']);
                unset($pageitems[$itemid]);
            }
            elseif($pageitem['id']=='detail_comment'){
                $detail_comment = array('style'=>$pageitem['style'], 'params'=>$pageitem['params']);
            }
            elseif($pageitem['id']=='detail_seckill'){
                $detail_seckill = array('style'=>$pageitem['style'], 'params'=>$pageitem['params']);
            }
        }

        $navbar = array();
        if(!empty($detail_navbar)){
            $btnlike = array(
                'iconclass'=>'icon-like',
                'icontext'=>'关注',
                'type'=>'like'
            );
            $btnshop = array(
                'iconclass'=>'icon-shop',
                'icontext'=>'店铺',
                'type'=>'shop'
            );
            $btncart = array(
                'iconclass'=>'icon-cart',
                'icontext'=>'购物车',
                'linkurl'=>mobileUrl('member/cart'),
                'type'=>'cart'
            );

            if(intval($detail_navbar['params']['hidelike'])<1){
                if($detail_navbar['params']['hidelike']==0){
                    $navbar[] = $btnlike;
                }
                elseif($detail_navbar['params']['hidelike']==-1){
                    $navbar[] = $btnshop;
                }
                elseif($detail_navbar['params']['hidelike']==-2){
                    $navbar[] = $btncart;
                }
                elseif($detail_navbar['params']['hidelike']==-3){
                    $navbar[] = array(
                        'iconclass'=>empty($detail_navbar['params']['likeiconclass'])?"icon-like":$detail_navbar['params']['likeiconclass'],
                        'icontext'=>empty($detail_navbar['params']['liketext'])?"关注":$detail_navbar['params']['liketext'],
                        'linkurl'=>$detail_navbar['params']['likelink']
                    );
                }
            }
            if(intval($detail_navbar['params']['hideshop'])<1){
                if($detail_navbar['params']['hideshop']==0){
                    $navbar[] = $btnshop;
                }
                elseif($detail_navbar['params']['hideshop']==-1){
                    $navbar[] = $btnlike;
                }
                elseif($detail_navbar['params']['hideshop']==-2){
                    $navbar[] = $btncart;
                }
                elseif($detail_navbar['params']['hideshop']==-3){
                    $navbar[] = array(
                        'iconclass'=>empty($detail_navbar['params']['shopiconclass'])?"icon-shop":$detail_navbar['params']['shopiconclass'],
                        'icontext'=>empty($detail_navbar['params']['shoptext'])?"店铺":$detail_navbar['params']['shoptext'],
                        'linkurl'=>$detail_navbar['params']['shoplink']
                    );
                }
            }
            if(intval($detail_navbar['params']['hidecart'])<1){
                if($detail_navbar['params']['hidecart']==0){
                    $navbar[] = $btncart;
                }
                elseif($detail_navbar['params']['hidecart']==-1){
                    $navbar[] = $btnlike;
                }
                elseif($detail_navbar['params']['hidecart']==-2){
                    $navbar[] = $btnshop;
                }
                elseif($detail_navbar['params']['hidecart']==-3){
                    $navbar[] = array(
                        'iconclass'=>empty($detail_navbar['params']['carticonclass'])?"icon-cart":$detail_navbar['params']['carticonclass'],
                        'icontext'=>empty($detail_navbar['params']['carttext'])?"购物车":$detail_navbar['params']['carttext'],
                        'linkurl'=>$detail_navbar['params']['cartlink']
                    );
                }
            }
        }

        return array(
            'background'=>$background,
            'followbar'=>$followbar,
            'tab'=>$detail_tab,
            'navbar'=>$detail_navbar,
            'diynavbar'=>$navbar,
            'comment'=>$detail_comment,
            'seckill'=>$detail_seckill,
            'diylayer'=>$page['data']['page']['diylayer'],
            'items'=>$pageitems
        );
    }

    public function toArray($data){
        if(empty($data) || !is_array($data)){
            return array();
        }
        $newData = array();
        foreach ($data as $index=>$item){
            if(!empty($item) && is_array($item)){
                $newData[] = $item;
            }
        }
        return $newData;
    }


    public function seckillPage($pageid=0) {
        global $_W;

        $set = $this->getSet();

        if(!empty($pageid)){
            $page = $this->getPage($pageid, true);
        }

        if(empty($pageid) || (!empty($pageid) && empty($page))){
            if(!empty($set['page']['seckill'])){
                $page = $this->getPage($set['page']['seckill'], true);
            }
        }

        if(empty($page) || !is_array($page) || !is_array($page['data']['items'])){
            return false;
        }
        $pageitems = $page['data']['items'];

        $seckill_list = array();
        foreach ($pageitems as $itemid=>$pageitem){
            if($pageitem['id']=='seckill_list'){
                $seckill_list = $pageitem;
                break;
            }
        }

        return array(

            'seckill_list'=>$seckill_list,
            'diylayer'=>$page['data']['page']['diylayer'],
            'diymenu'=>$page['data']['page']['diymenu'],
            'items'=>$pageitems
        );
    }

}
