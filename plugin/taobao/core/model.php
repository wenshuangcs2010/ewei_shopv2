<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class TaobaoModel extends PluginModel {

	//获取一个淘宝宝贝 $itemid
	function get_item_taobao($itemid = '', $taobaourl = '', $pcate = 0, $ccate = 0, $tcate = 0,$depotid=0, $merchid = 0) {

		global $_W;
		$g = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where uniacid=:uniacid and merchid=:merchid and catch_id=:catch_id and catch_source='taobao' limit 1", array(':uniacid' => $_W['uniacid'], ':merchid'=>$merchid, ":catch_id" => $itemid));
		if ($g) {
			//  return array("result" => 1, "goodsid" => $g['id']);
		}

		//连接
		$url = $this->get_taobao_info_url($itemid);
		
		load()->func('communication');
		$response = ihttp_get($url);
		if (!isset($response['content'])) {
			return array("result" => '0', "error" => '未从淘宝获取到商品信息!');
		}
		$content = $response['content'];

		if (strexists($response['content'], "ERRCODE_QUERY_DETAIL_FAIL")) {
			return array("result" => '0', "error" => '宝贝不存在!');
		}

		$arr = json_decode($content, true);
		$data = $arr['data'];
		$itemInfoModel = $data['itemInfoModel'];

		$item = array();
		$item['id'] = $g['id'];
		$item['merchid'] = $merchid;
        if (!empty($merchid)) {
            if (empty($_W['merch_user']['goodschecked'])) {
                $item['checked'] = 1;
            } else {
                $item['checked'] = 0;
            }
        }
        $item['depotid']=$depotid;
       
		$item['pcate'] = $pcate;
		$item['ccate'] = $ccate;
		$item['tcate'] = $tcate;
		$item['cates'] = "{$pcate},{$ccate},{$tcate}";
		$item['itemId'] = $itemInfoModel['itemId'];
		$item['title'] = $itemInfoModel['title'];
		$item['pics'] = $itemInfoModel['picsPath'];
		$params = array();
		if (isset($data['props'])) {
			$props = $data['props'];
			foreach ($props as $pp) {
				$params[] = array(
					"title" => $pp['name'],
					"value" => $pp['value']
				);
			}
		}
		$item['params'] = $params;
		$specs = array();
		$options = array();

		if (isset($data['skuModel'])) {
			$skuModel = $data['skuModel'];
			if (isset($skuModel['skuProps'])) {
				$skuProps = $skuModel['skuProps'];
				foreach ($skuProps as $prop) {

					$spec_items = array();
					foreach ($prop['values'] as $spec_item) {
						$spec_items[] = array(
							'valueId' => $spec_item['valueId'],
							'title' => $spec_item['name'],
							"thumb" => $spec_item['imgUrl']
						);
					}
					$spec = array(
						"propId" => $prop['propId'],
						"title" => $prop['propName'],
						"items" => $spec_items
					);
					$specs[] = $spec;
				}
			}
			if (isset($skuModel['ppathIdmap'])) {
				$ppathIdmap = $skuModel['ppathIdmap'];
				foreach ($ppathIdmap as $key => $skuId) {

					$option_specs = array();
					$m = explode(";", $key);
					foreach ($m as $v) {
						$mm = explode(":", $v);
						$option_specs[] = array(
							"propId" => $mm[0],
							"valueId" => $mm[1]
						);
					}
					$options[] = array(
						"option_specs" => $option_specs,
						"skuId" => $skuId,
						"stock" => 0,
						"marketprice" => 0,
						"specs" => ""
					);
				}
			}
		}
		$item['specs'] = $specs;
		$stack = $data['apiStack'][0]['value'];
		$value = json_decode($stack, true);
		$item1 = array();
		$data1 = $value['data'];

		$itemInfoModel1 = $data1['itemInfoModel'];

		$item['total'] = $itemInfoModel1['quantity'];
		$item['sales'] = $itemInfoModel1['totalSoldQuantity'];

		if (isset($data1['skuModel'])) {
			$skuModel1 = $data1['skuModel'];

			if (isset($skuModel1['skus'])) {
				$skus = $skuModel1['skus'];

				foreach ($skus as $key => $val) {
					$sku_id = $key;
					foreach ($options as &$o) {
						if ($o['skuId'] == $sku_id) {
							$o['stock'] = $val['quantity'];
							foreach ($val['priceUnits'] as $p) {
								$o['marketprice'] = $p['price'];
							}
							$titles = array();

							foreach ($o['option_specs'] as $osp) {
								foreach ($specs as $sp) {
									if ($sp['propId'] == $osp['propId']) {
										foreach ($sp['items'] as $spitem) {

											if ($spitem['valueId'] == $osp['valueId']) {
												$titles[] = $spitem['title'];
											}
										}
									}
								}
							}
							$o['title'] = $titles;
						}
					}
					unset($o);
				}
			}  else {
				$mprice = 0;
				foreach ($itemInfoModel1['priceUnits'] as $p) {
					$mprice = $p['price'];
				}
				$item['marketprice'] = $mprice;
			}
		} else {
			$mprice = 0;
			foreach ($itemInfoModel1['priceUnits'] as $p) {
				$mprice = $p['price'];
			}
			$item['marketprice'] = $mprice;
		}
		$item['options'] = $options;
		$item['content'] = array();
		//详情
		$url = $this->get_taobao_detail_url($itemid);
		
		load()->func('communication');
		$response = ihttp_get($url);
	
		/*
		$response=json_decode($response['content'],true);
		$response=$response['data']['pages'];
		$contentresponse="";
		foreach ($response as $key => $value) {
			$contentresponse.=$value;
		}*/
		$response = preg_replace('/ (?:width)=(\'|").*?\\1/',' width="100%"',$response);
		$response = preg_replace('/ (?:height)=(\'|").*?\\1/',' ',$response);
		
		$item['content'] = $response;

		
		return $this->save_taobao_goods($item ,$taobaourl);
	}

	//获取一个京东宝贝 $itemid
	function get_item_jingdong($itemid = '', $jingdongurl = '', $pcate = 0, $ccate = 0, $tcate = 0, $merchid = 0) {

		error_reporting(0);
		global $_W;
		$g = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where uniacid=:uniacid and merchid=:merchid and catch_id=:catch_id and catch_source='jingdong' limit 1", array(':uniacid' => $_W['uniacid'], ":catch_id" => $itemid, ':merchid'=>$merchid));
		if ($g) {
			//  return array("result" => 1, "goodsid" => $g['id']);
		}


		//连接
		$url = $this->get_jingdong_info_url($itemid);
		load()->func('communication');
		$response = ihttp_get($url);

		$length = strval($response['headers']['Content-Length']);
		if ($length !=null) {
			return array("result" => '0', "error" => '未从京东获取到商品信息!');
		}

		$content = $response['content'];

		$dom=new DOMDocument;
		$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.$content);
		$xml=simplexml_import_dom($dom);

		//产品名称
		$prodectNameContent=$xml->xpath('//*[@id="goodName"]');
		$prodectName =  strval($prodectNameContent[0]->attributes()->value);

		if ($prodectName==null) {
			echo '宝贝不存在';
			return array("result" => '0', "error" => '宝贝不存在!');
		}


		$item = array();
		$item['id'] = $g['id'];
		$item['merchid'] = $merchid;
        if (!empty($merchid)) {
            if (empty($_W['merch_user']['goodschecked'])) {
                $item['checked'] = 1;
            } else {
                $item['checked'] = 0;
            }
        }
		$item['pcate'] = $pcate;
		$item['ccate'] = $ccate;
		$item['tcate'] = $tcate;
		$item['cates'] = "{$pcate},{$ccate},{$tcate}";
		$item['itemId'] = $itemid;
		$item['title'] = $prodectName;

		//图片路径第一张位缩略图共四张
		$pics = array();
		$imgurls=$xml->xpath('//*[@id="slide"]/ul');


		foreach($imgurls[0]->li as $imgurl)
		{
			if(count($pics)<4)
			{
				$pics[] = 'http:'.$imgurl->img->attributes()->src;
			}
		}
		$item['pics'] =$pics;

		$specs = array();

	/*	//规格1 颜色 系列 套餐
		$spec1=$xml->xpath('//*[@class="pro-color"]');
		$title1= $spec1[0]->span;
		if($spec1!=null)
		{
			$p= $spec1[0]->p;
			$spec1_items = array();
			foreach ($p->a as $spec_item) {
				$spec1_items[] = array(
					'valueId' => '',
					'title' =>strval($spec_item),
					'thumb' =>  ''
				);
			}
			$spec = array(
				'propId' => '',
				'title' => strval($title1[0]),
				'items' => $spec1_items
			);

			$specs[] = $spec;
		}


		//规格2 版本 大小
		$spec2=$xml->xpath('//*[@class="pro-size"]');
		$title2= $spec2[0]->span;

		if($spec2!=null)
		{
			$p= $spec2[0]->p;
			$spec2_items = array();
			foreach ($p->a as $spec_item) {
				$spec2_items[] = array(
					'valueId' => '',
					'title' => strval($spec_item),
					'thumb' =>  ''
				);
			}
			$spec = array(
				'propId' => '',
				'title' => strval($title2),
				'items' => $spec2_items
			);
			$specs[] = $spec;
		}

		//规格3 容积
		$spec3=$xml->xpath('//*[@class="pro-spec"]');
		$title3= trim($spec3[0]->span);
		if($spec3!=null)
		{
			$p= $spec3[0]->p;
			$spec3_items = array();
			foreach ($p->a as $spec_item) {
				$spec3_items[] = array(
					'valueId' => '',
					'title' => strval($spec_item),
					'thumb' =>  ''
				);
			}
			$spec = array(
				'propId' => '',
				'title' => strval($title3),
				'items' => $spec3_items
			);
			$specs[] = $spec;
		}
		$item['specs'] = $specs;*/

		$item['total'] = 10;
		$item['sales'] = 0;

		$prodectPriceContent=$xml->xpath('//*[@id="jdPrice"]');
		$prodectPrices =  strval($prodectPriceContent[0]->attributes()->value);

		$item['marketprice'] =$prodectPrices;


		//商品详情与参数
		$url=$this->get_jingdong_detail_url($itemid);
		$responseDetail = ihttp_get($url);
		$contenteDetail = $responseDetail['content'];
		$details = json_decode($contenteDetail, true);
		//详情
		$prodectContent = $details[wdis];

		$prodectContent =  strval($prodectContent);

		$response = preg_replace('/ (?:width)=(\'|").*?\\1/',' width="100%"',$response);
		$response = preg_replace('/ (?:height)=(\'|").*?\\1/',' ',$response);

		$item['content'] = $prodectContent;




		//参数
		$params = array();
		$pr = $details[ware][wi][code];

		preg_match_all("/<td class=\"tdTitle\">(.*?)<\/td>/i",$pr, $params1);
		preg_match_all("/<td>(.*?)<\/td>/i",$pr, $params2);
		$paramsTitle = $params1[1];
		$paramsValue = $params2[1];

		if(count($paramsTitle) ==count($paramsValue))
		{
			for($i=0; $i<count($paramsTitle);$i++){

				$params[] = array(
					"title" => $paramsTitle[$i],
					"value" => $paramsValue[$i]
				);
			}
		}
		$item['params'] = $params;
		return $this->save_jingdong_goods($item, $jingdongurl);
	}

	//获取一个1688宝贝 $itemid
	function get_item_one688($itemid = '', $one688url = '', $pcate = 0, $ccate = 0, $tcate = 0, $merchid = 0) {

		error_reporting(0);
		global $_W;

		$g = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where uniacid=:uniacid and merchid=:merchid and catch_id=:catch_id and catch_source='1688' limit 1", array(':uniacid' => $_W['uniacid'], ":catch_id" => $itemid, ':merchid'=>$merchid));


		//连接
		$url = $this->get_1688_info_url($itemid);

		load()->func('communication');
		$response = ihttp_get($url);

		$length = strval($response['headers']['Content-Length']);
		if ($length !=null) {
			return array("result" => '0', "error" => '未从京东获取到商品信息!');
		}

		$content = $response['content'];

		$dom=new DOMDocument;
		$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=gbk"/>'.$content);
		$xml=simplexml_import_dom($dom);

		//产品名称
		$prodectNameContent=$xml->xpath('//*[@id="mod-detail-title"]/h1');

		$prodectName =  strval($prodectNameContent[0]);

		if ($prodectName==null) {
			echo '宝贝不存在';
			return array("result" => '0', "error" => '宝贝不存在!');
		}

		$item = array();
		$item['id'] = $g['id'];
		$item['merchid'] = $merchid;
        if (!empty($merchid)) {
            if (empty($_W['merch_user']['goodschecked'])) {
                $item['checked'] = 1;
            } else {
                $item['checked'] = 0;
            }
        }
		$item['pcate'] = $pcate;
		$item['ccate'] = $ccate;
		$item['tcate'] = $tcate;
		$item['cates'] = "{$pcate},{$ccate},{$tcate}";
		$item['itemId'] = $itemid;
		$item['title'] = $prodectName;

		//图片路径第一张位缩略图共四张
		$pics = array();
		$imgurls=$xml->xpath('//*[@id="dt-tab"]/div/ul');

		foreach($imgurls[0]->li as $imgurlContent)
		{
			$imgurlall =$imgurlContent->attributes();
			$img =  json_decode($imgurlall[1], true);

			if(count($pics)<4)
			{
				$pics[] = $img['original'];
			}
		}
		$item['pics'] =$pics;

		$item['total'] = 10;
		$item['sales'] = 0;

		$prodectPriceContent=$xml->xpath('//*[@property="og:product:price"]');
		$prodectPrices =  strval($prodectPriceContent[0]->attributes()->content);
		$item['marketprice'] =$prodectPrices;

		//详情
		$prodectContent = $xml->xpath('//*[@id="desc-lazyload-container"]');
		$Contents =$prodectContent[0]->attributes();
		$detail = $Contents['data-tfs-url'];

		$responseDetail = ihttp_get(strval($detail));
		$contenteDetail = $responseDetail['content'];
		$contenteDetail = iconv( "GB2312","UTF-8",$contenteDetail);


		if(strpos($contenteDetail,'{')){
			$contenteDetail = substr($contenteDetail,strpos($contenteDetail,'{'),-1);
			$details= json_decode($contenteDetail, true);

			$response = $details['content'];

			$response = preg_replace('/ (?:width)=(\'|").*?\\1/',' width="100%"',$response);
			$response = preg_replace('/ (?:height)=(\'|").*?\\1/',' ',$response);

			$item['content'] = $response;
		}

		//参数
		$params = $xml->xpath('//*[@id="mod-detail-attributes"]/div[1]/table/tbody');

		$paramsTitle =array();
		$paramsValue =array();

		foreach($params[0]->tr as $tr)
		{
			foreach($tr[0]->td as $td) {
				if($td->attributes()->class =='de-feature' && strlen(strval($td))>0)
				{
					$paramsTitle[] = strval($td);
				}

				if($td->attributes()->class =='de-value' && strlen(strval($td))>0)
				{
					$paramsValue[] = strval($td);
				}
			}
		}
		$params = array();

		if(count($paramsTitle) ==count($paramsValue))
		{
			for($i=0; $i<count($paramsTitle);$i++){

				$params[] = array(
					"title" => $paramsTitle[$i],
					"value" => $paramsValue[$i]
				);
			}
		}
		$item['params'] = $params;

		return $this->save_1688_goods($item, $one688url);
	}

	//保存淘宝商品
	function save_taobao_goods($item = array(), $catch_url = '') {

		global $_W;


		$data = array(
			"uniacid" => $_W['uniacid'],
			"merchid" => $item['merchid'],
			"checked" => $item['checked'],
			"catch_source" => 'taobao',
			"catch_id" => $item['itemId'],
			"catch_url" => $catch_url,
			"title" => $item['title'],
			"total" => $item['total'],
			"marketprice" => $item['marketprice'],
			"pcate" => $item['pcate'],
			"ccate" => $item['ccate'],
			"tcate" => $item['tcate'],
			"cates" => $item['cates'],
			"sales" => $item['sales'],
			'depotid'=>$item['depotid'],
			"createtime" => time(),
			"updatetime" => time(),
			'hasoption' => count($item['options']) > 0 ? 1 : 0,
			'status' => 0,
			'deleted' => 0,
			'buylevels' => '',
			'showlevels' => '',
			'buygroups' => '',
			'showgroups' => '',
			'noticeopenid' => '',
			'storeids' => '',
		);

		//图片
		$thumb_url = array();
		$pics = $item['pics'];
		$piclen = count($pics);

		if ($piclen > 0) {
			$data['thumb'] = $this->save_image($pics[0],false);

			//其他图片
			if ($piclen > 1) {
				for ($i = 1; $i < $piclen; $i++) {
					$img = $this->save_image($pics[$i],false);
					$thumb_url[] = $img;
				}
			}
		}
		$data['thumb_url'] = serialize($thumb_url);

		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where  catch_id=:catch_id and catch_source='taobao' and uniacid=:uniacid and merchid=:merchid", array(":catch_id" => $item['itemId'], ":uniacid" => $_W['uniacid'], ":merchid" => $item['merchid']));
		if (empty($goods)) {

			pdo_insert("ewei_shop_goods", $data);
			$goodsid = pdo_insertid();
		} else {
			$goodsid = $goods['id'];

			unset($data['createtime']);
			pdo_update("ewei_shop_goods", $data, array("id" => $goodsid));
		}


		//参数
		$goods_params = pdo_fetchall("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		$params = $item['params'];
		$paramids = array();
		$displayorder = 0;
		foreach ($params as $p) {

			$oldp = pdo_fetch("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and title=:title limit 1", array(":goodsid" => $goodsid, ":title" => $p['title']));
			$paramid = 0;
			$d = array(
				"uniacid" => $_W['uniacid'],
				"goodsid" => $goodsid,
				"title" => $p['title'],
				"value" => $p['value'],
				"displayorder" => $displayorder
			);
			if (empty($oldp)) {
				pdo_insert("ewei_shop_goods_param", $d);
				$paramid = pdo_insertid();
			} else {
				pdo_update("ewei_shop_goods_param", $d, array("id" => $oldp['id']));
				$paramid = $oldp['id'];
			}
			$paramids[] = $paramid;
			$displayorder++;
		}

		if (count($paramids) > 0) {
			pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and id not in (" . implode(",", $paramids) . ")", array(":goodsid" => $goodsid));
		} else {
			pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		}

		//规格

		$specs = $item['specs'];
		$specids = array();
		$displayorder = 0;
		$newspecs = array();
		foreach ($specs as $spec) {

			$oldspec = pdo_fetch("select * from " . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid and propId=:propId limit 1", array(":goodsid" => $goodsid, ":propId" => $spec['propId']));
			$specid = 0;
			$d_spec = array(
				"uniacid" => $_W['uniacid'],
				"goodsid" => $goodsid,
				"title" => $spec['title'],
				"displayorder" => $displayorder,
				"propId" => $spec['propId']
			);
			if (empty($oldspec)) {
				pdo_insert("ewei_shop_goods_spec", $d_spec);
				$specid = pdo_insertid();
			} else {
				pdo_update("ewei_shop_goods_spec", $d_spec, array("id" => $oldspec['id']));
				$specid = $oldspec['id'];
			}
			$d_spec['id'] = $specid;


			$specids[] = $specid;

			$displayorder++;

			//spec_items
			$spec_items = $spec['items'];
			$spec_itemids = array();
			$displayorder_item = 0;
			$newspecitems = array();
			foreach ($spec_items as $spec_item) {
				$d = array(
					"uniacid" => $_W['uniacid'],
					"specid" => $specid,
					"title" => $spec_item['title'],
					"thumb" => $this->save_image($spec_item['thumb'],false),
					"valueId" => $spec_item['valueId'],
					"show" => 1,
					"displayorder" => $displayorder_item
				);
				$oldspecitem = pdo_fetch("select * from " . tablename('ewei_shop_goods_spec_item') . " where specid=:specid and valueId=:valueId limit 1", array(":specid" => $specid, ":valueId" => $spec_item['valueId']));
				$spec_item_id = 0;
				if (empty($oldspecitem)) {
					pdo_insert("ewei_shop_goods_spec_item", $d);
					$spec_item_id = pdo_insertid();
				} else {
					pdo_update("ewei_shop_goods_spec_item", $d, array("id" => $oldspecitem['id']));
					$spec_item_id = $oldspecitem['id'];
				}
				$displayorder_item++;
				$spec_itemids[] = $spec_item_id;
				$d['id'] = $spec_item_id;
				$newspecitems[] = $d;
			}
			$d_spec['items'] = $newspecitems;

			$newspecs[] = $d_spec;

			if (count($spec_itemids) > 0) {
				pdo_query("delete from " . tablename('ewei_shop_goods_spec_item') . " where specid=:specid and id not in (" . implode(",", $spec_itemids) . ")", array(":specid" => $specid));
			} else {
				pdo_query("delete from " . tablename('ewei_shop_goods_spec_item') . " where specid=:specid ", array(":specid" => $specid));
			}
			pdo_update("ewei_shop_goods_spec", array("content" => serialize($spec_itemids)), array("id" => $oldspec['id']));
		}

		if (count($specids) > 0) {
			pdo_query("delete from " . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid and id not in (" . implode(",", $specids) . ")", array(":goodsid" => $goodsid));
		} else {
			pdo_query("delete from " . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		}

		//规格
		$minprice = 0;
		$options = $item['options'];


		if (count($options) > 0) {
			$minprice = $options[0]['marketprice'];
		}
		$optionids = array();
		$displayorder = 0;
		foreach ($options as $o) {
			$option_specs = $o['option_specs'];
			$ids = array();
			$valueIds = array();
			foreach ($option_specs as $os) {
				foreach ($newspecs as $nsp) {
					foreach ($nsp['items'] as $nspitem) {
						if ($nspitem['valueId'] == $os['valueId']) {
							$ids[] = $nspitem['id'];
							$valueIds[] = $nspitem['valueId'];
						}
					}
				}
			}
			$ids = implode("_", $ids);
			$valueIds = implode("_", $valueIds);
			$do = array(
				'uniacid' => $_W['uniacid'],
				"displayorder" => $displayorder,
				"goodsid" => $goodsid,
				"title" => implode('+', $o['title']),
				"specs" => $ids,
				"stock" => $o['stock'],
				"marketprice" => $o['marketprice'],
				"skuId" => $o['skuId']
			);

			if ($minprice > $o['marketprice']) {
				$minprice = $o['marketprice'];
			}
			$oldoption = pdo_fetch("select * from " . tablename('ewei_shop_goods_option') . " where goodsid=:goodsid and skuId=:skuId limit 1", array(":goodsid" => $goodsid, ":skuId" => $o['skuId']));
			$option_id = 0;
			if (empty($oldoption)) {
				pdo_insert("ewei_shop_goods_option", $do);
				$option_id = pdo_insertid();
			} else {
				pdo_update("ewei_shop_goods_option", $do, array("id" => $oldoption['id']));
				$option_id = $oldoption['id'];
			}
			$displayorder++;
			$optionids[] = $option_id;
		}
		if (count($optionids) > 0) {
			pdo_query("delete from " . tablename('ewei_shop_goods_option') . " where goodsid=:goodsid and id not in (" . implode(",", $optionids) . ")", array(":goodsid" => $goodsid));
		} else {
			pdo_query("delete from " . tablename('ewei_shop_goods_option') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		}

		//保存详情
		$response = $item['content'];

		$content = $response['content'];


		preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg]?))[\\\'|\\\"].*?[\/]?>/", $content, $imgs);
		 //preg_match_all('/<img>(.*?(?:[\.gif|\.jpg]?))<\/img>/', $content, $imgs);

		if (isset($imgs[1])) {

			foreach ($imgs[1] as $img) {

				$catchimg = $img;
				if (substr($catchimg, 0, 2) == "//") {
					$img = "http://" . substr($img, 2);
				}
				$im = array(
					"catchimg" => $catchimg,
					"system" => $this->save_image($img,true)
				);
				$images[] = $im;
			}
		}

		preg_match("/tfsContent : \'(.*)\'/", $content, $html);
		$html = iconv("GBK", "UTF-8", $html[1]);
		if (isset($images)) {
			foreach ($images as $img) {
				$html = str_replace($img['catchimg'], $img['system'], $html);
			}
		}
		$html = m('common')->html_to_images($html);
		$hasoption = 0;

		if (count($options) > 0) {
			$hasoption = 1;
		}

		$d = array("content" => $html, "hasoption" => $hasoption);
		if ($minprice > 0) {
			$d["marketprice"] = $minprice;
		}

		pdo_update("ewei_shop_goods", $d, array("id" => $goodsid));


		if($d['hasoption']) {
			//更新最高最低价
			$sql = "update ".tablename('ewei_shop_goods')." g set
            g.minprice = (select min(marketprice) from ".tablename('ewei_shop_goods_option')." where goodsid = $goodsid and marketprice > 0),
            g.maxprice = (select max(marketprice) from ".tablename('ewei_shop_goods_option')." where goodsid = $goodsid)
            where g.id = $goodsid and g.hasoption=1";
		} else{
			$sql = "update ".tablename('ewei_shop_goods')." set minprice = marketprice,maxprice = marketprice where id = $goodsid and hasoption=0;";
		}

		pdo_query($sql);
		return array("result" => '1', "goodsid" => $goodsid);
	}

	//保存京东商品
	function save_jingdong_goods($item = array(), $catch_url = '') {

		global $_W;
		$data = array(
			"uniacid" => $_W['uniacid'],
			"merchid" => $item['merchid'],
			"checked" => $item['checked'],
			"catch_source" => 'jingdong',
			"catch_id" => $item['itemId'],
			"catch_url" => $catch_url,
			"title" => $item['title'],
			"total" => $item['total'],
			"marketprice" => $item['marketprice'],
			"pcate" => $item['pcate'],
			"ccate" => $item['ccate'],
			"tcate" => $item['tcate'],
			"cates" => $item['cates'],
			"sales" => $item['sales'],
			"createtime" => time(),
			"updatetime" => time(),
			'hasoption' => 0,
			'status' => 0,
			'deleted' => 0,
			'buylevels' => '',
			'showlevels' => '',
			'buygroups' => '',
			'showgroups' => '',
			'noticeopenid' => '',
			'storeids' => '',
			'minprice'=> $item['marketprice'],
			'maxprice'=> $item['marketprice']

		);

		//图片
		$thumb_url = array();
		$pics = $item['pics'];
		$piclen = count($pics);

		if ($piclen > 0) {
			$data['thumb'] = $this->save_image($pics[0],false);

			//其他图片
			if ($piclen > 1) {
				for ($i = 1; $i < $piclen; $i++) {
					$img = $this->save_image($pics[$i],false);
					$thumb_url[] = $img;
				}
			}
		}
		$data['thumb_url'] = serialize($thumb_url);

		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where  catch_id=:catch_id and catch_source='jingdong' and uniacid=:uniacid and merchid=:merchid", array(":catch_id" => $item['itemId'], ":uniacid" => $_W['uniacid'], ":merchid" => $item['merchid']));
		if (empty($goods)) {

			pdo_insert("ewei_shop_goods", $data);
			$goodsid = pdo_insertid();
		} else {
			$goodsid = $goods['id'];

			unset($data['createtime']);
			pdo_update("ewei_shop_goods", $data, array("id" => $goodsid));
		}


		//参数
		$goods_params = pdo_fetchall("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		$params = $item['params'];
		$paramids = array();
		$displayorder = 0;
		foreach ($params as $p) {

			$oldp = pdo_fetch("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and title=:title limit 1", array(":goodsid" => $goodsid, ":title" => $p['title']));
			$paramid = 0;
			$d = array(
				"uniacid" => $_W['uniacid'],
				"goodsid" => $goodsid,
				"title" => $p['title'],
				"value" => $p['value'],
				"displayorder" => $displayorder
			);
			if (empty($oldp)) {
				pdo_insert("ewei_shop_goods_param", $d);
				$paramid = pdo_insertid();
			} else {
				pdo_update("ewei_shop_goods_param", $d, array("id" => $oldp['id']));
				$paramid = $oldp['id'];
			}
			$paramids[] = $paramid;
			$displayorder++;
		}

		if (count($paramids) > 0) {
			pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and id not in (" . implode(",", $paramids) . ")", array(":goodsid" => $goodsid));
		} else {
			pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		}

		//保存详情
		$content = $item['content'];
		preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg]?))[\\\'|\\\"].*?[\/]?>/", $content, $imgs);

		if (isset($imgs[1])) {

			foreach ($imgs[1] as $img) {

				$catchimg = $img;
				if (substr($catchimg, 0, 2) == "//") {
					$img = "http://" . substr($img, 2);
				}
				$im = array(
					"catchimg" => $catchimg,
					"system" => $this->save_image($img,true)
				);

				$images[] = $im;
			}


		}
		$html =$content;
		//$html = iconv("GBK", "UTF-8", $html[1]);

		if (isset($images)) {
			foreach ($images as $img) {
				$html = str_replace($img['catchimg'], $img['system'], $html);
			}
		}
		$html = m('common')->html_to_images($html);
		$d = array("content" => $html);

		pdo_update("ewei_shop_goods", $d, array("id" => $goodsid));

		return array("result" => '1', "goodsid" => $goodsid);
	}

	//保存1688商品
	function save_1688_goods($item = array(), $catch_url = '') {

		global $_W;
		$data = array(
			"uniacid" => $_W['uniacid'],
			"merchid" => $item['merchid'],
			"checked" => $item['checked'],
			"catch_source" => '1688',
			"catch_id" => $item['itemId'],
			"catch_url" => $catch_url,
			"title" => $item['title'],
			"total" => $item['total'],
			"marketprice" => $item['marketprice'],
			"pcate" => $item['pcate'],
			"ccate" => $item['ccate'],
			"tcate" => $item['tcate'],
			"cates" => $item['cates'],
			"sales" => $item['sales'],
			"createtime" => time(),
			"updatetime" => time(),
			'hasoption' => 0,
			'status' => 0,
			'deleted' => 0,
			'buylevels' => '',
			'showlevels' => '',
			'buygroups' => '',
			'showgroups' => '',
			'noticeopenid' => '',
			'storeids' => '',
			'minprice'=> $item['marketprice'],
			'maxprice'=> $item['marketprice']

		);

		//图片
		$thumb_url = array();
		$pics = $item['pics'];
		$piclen = count($pics);

		if ($piclen > 0) {
			$data['thumb'] = $this->save_image($pics[0],false);

			//其他图片
			if ($piclen > 1) {
				for ($i = 1; $i < $piclen; $i++) {
					$img = $this->save_image($pics[$i],false);
					$thumb_url[] = $img;
				}
			}
		}
		$data['thumb_url'] = serialize($thumb_url);

		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where  catch_id=:catch_id and catch_source='1688' and uniacid=:uniacid and merchid=:merchid", array(":catch_id" => $item['itemId'], ":uniacid" => $_W['uniacid'], ":merchid" => $item['merchid']));
		if (empty($goods)) {

			pdo_insert("ewei_shop_goods", $data);
			$goodsid = pdo_insertid();
		} else {
			$goodsid = $goods['id'];

			unset($data['createtime']);
			pdo_update("ewei_shop_goods", $data, array("id" => $goodsid));
		}


		//参数
		$goods_params = pdo_fetchall("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		$params = $item['params'];
		$paramids = array();
		$displayorder = 0;
		foreach ($params as $p) {

			$oldp = pdo_fetch("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and title=:title limit 1", array(":goodsid" => $goodsid, ":title" => $p['title']));
			$paramid = 0;
			$d = array(
				"uniacid" => $_W['uniacid'],
				"goodsid" => $goodsid,
				"title" => $p['title'],
				"value" => $p['value'],
				"displayorder" => $displayorder
			);
			if (empty($oldp)) {
				pdo_insert("ewei_shop_goods_param", $d);
				$paramid = pdo_insertid();
			} else {
				pdo_update("ewei_shop_goods_param", $d, array("id" => $oldp['id']));
				$paramid = $oldp['id'];
			}
			$paramids[] = $paramid;
			$displayorder++;
		}

		if (count($paramids) > 0) {
			pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid and id not in (" . implode(",", $paramids) . ")", array(":goodsid" => $goodsid));
		} else {
			pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		}

		//规格

		$specs = $item['specs'];
		$specids = array();
		$displayorder = 0;
		$newspecs = array();
		foreach ($specs as $spec) {

			$oldspec = pdo_fetch("select * from " . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid and propId=:propId limit 1", array(":goodsid" => $goodsid, ":propId" => $spec['propId']));
			$specid = 0;
			$d_spec = array(
				"uniacid" => $_W['uniacid'],
				"goodsid" => $goodsid,
				"title" => $spec['title'],
				"displayorder" => $displayorder,
				"propId" => $spec['propId']
			);
			if (empty($oldspec)) {
				pdo_insert("ewei_shop_goods_spec", $d_spec);
				$specid = pdo_insertid();
			} else {
				pdo_update("ewei_shop_goods_spec", $d_spec, array("id" => $oldspec['id']));
				$specid = $oldspec['id'];
			}
			$d_spec['id'] = $specid;


			$specids[] = $specid;

			$displayorder++;

			//spec_items
			$spec_items = $spec['items'];
			$spec_itemids = array();
			$displayorder_item = 0;
			$newspecitems = array();
			foreach ($spec_items as $spec_item) {
				$d = array(
					"uniacid" => $_W['uniacid'],
					"specid" => $specid,
					"title" => $spec_item['title'],
					"thumb" => $this->save_image($spec_item['thumb'],false),
					"valueId" => $spec_item['valueId'],
					"show" => 1,
					"displayorder" => $displayorder_item
				);
				$oldspecitem = pdo_fetch("select * from " . tablename('ewei_shop_goods_spec_item') . " where specid=:specid and valueId=:valueId limit 1", array(":specid" => $specid, ":valueId" => $spec_item['valueId']));
				$spec_item_id = 0;
				if (empty($oldspecitem)) {
					pdo_insert("ewei_shop_goods_spec_item", $d);
					$spec_item_id = pdo_insertid();
				} else {
					pdo_update("ewei_shop_goods_spec_item", $d, array("id" => $oldspecitem['id']));
					$spec_item_id = $oldspecitem['id'];
				}
				$displayorder_item++;
				$spec_itemids[] = $spec_item_id;
				$d['id'] = $spec_item_id;
				$newspecitems[] = $d;
			}
			$d_spec['items'] = $newspecitems;

			$newspecs[] = $d_spec;

			if (count($spec_itemids) > 0) {
				pdo_query("delete from " . tablename('ewei_shop_goods_spec_item') . " where specid=:specid and id not in (" . implode(",", $spec_itemids) . ")", array(":specid" => $specid));
			} else {
				pdo_query("delete from " . tablename('ewei_shop_goods_spec_item') . " where specid=:specid ", array(":specid" => $specid));
			}
			pdo_update("ewei_shop_goods_spec", array("content" => serialize($spec_itemids)), array("id" => $oldspec['id']));
		}

		if (count($specids) > 0) {
			pdo_query("delete from " . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid and id not in (" . implode(",", $specids) . ")", array(":goodsid" => $goodsid));
		} else {
			pdo_query("delete from " . tablename('ewei_shop_goods_spec') . " where goodsid=:goodsid ", array(":goodsid" => $goodsid));
		}


		//保存详情
		$content = $item['content'];
		preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg]?))[\\\'|\\\"].*?[\/]?>/", $content, $imgs);

		if (isset($imgs[1])) {

			foreach ($imgs[1] as $img) {

				$catchimg = $img;
				if (substr($catchimg, 0, 2) == "//") {
					$img = "http://" . substr($img, 2);
				}
				$im = array(
					"catchimg" => $catchimg,
					"system" => $this->save_image($img,true)
				);

				$images[] = $im;
			}


		}
		$html =$content;
		//$html = iconv("GBK", "UTF-8", $html[1]);

		if (isset($images)) {
			foreach ($images as $img) {
				$html = str_replace($img['catchimg'], $img['system'], $html);
			}
		}
		$html = m('common')->html_to_images($html);
		$d = array("content" => $html);

		pdo_update("ewei_shop_goods", $d, array("id" => $goodsid));

		return array("result" => '1', "goodsid" => $goodsid);
	}

	//保存淘宝CSV商品
	function save_taobaocsv_goods($item = array(), $merchid = 0) {

		global $_W;
		$data = array(
			"uniacid" => $_W['uniacid'],
			"merchid" => $merchid,
			"catch_source" => 'taobaocsv',
			"catch_id" => '',
			"catch_url" => '',
			"title" => $item['title'],
			"total" => $item['total'],
			"marketprice" => $item['marketprice'],
			"pcate" =>'',
			"ccate" => '',
			"tcate" => '',
			"cates" => '',
			"sales" => 0,
			"createtime" => time(),
			"updatetime" => time(),
			'hasoption' => 0,
			'status' => 0,
			'deleted' => 0,
			'buylevels' => '',
			'showlevels' => '',
			'buygroups' => '',
			'showgroups' => '',
			'noticeopenid' => '',
			'storeids' => '',
			'minprice'=> $item['marketprice'],
			'maxprice'=> $item['marketprice']

		);
        if (!empty($merchid)) {
            if (empty($_W['merch_user']['goodschecked'])) {
                $data['checked'] = 1;
            } else {
                $data['checked'] = 0;
            }
        }

		//图片
		$thumb_url = array();
		$pics = $item['pics'];
		$piclen = count($pics);

		if ($piclen > 0) {

			$data['thumb'] = $this->save_image($pics[0],false);

			//其他图片
			if ($piclen > 1) {
				for ($i = 1; $i < $piclen; $i++) {
					$img = $this->save_image($pics[$i],false);
					$thumb_url[] = $img;
				}
			}
		}
		$data['thumb_url'] = serialize($thumb_url);

		pdo_insert("ewei_shop_goods", $data);
		$goodsid = pdo_insertid();

		//保存详情
		$content = $item['content'];
		preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg]?))[\\\'|\\\"].*?[\/]?>/", $content, $imgs);

		if (isset($imgs[1])) {
			foreach ($imgs[1] as $img) {

				$catchimg = $img;
				if (substr($catchimg, 0, 2) == "//") {
					$img = "http://" . substr($img, 2);
				}
				$im = array(
					"catchimg" => $catchimg,
					"system" => $this->save_image($img,true)
				);

				$images[] = $im;
			}
		}
		$html =$content;
		//$html = iconv("GBK", "UTF-8", $html[1]);

		if (isset($images)) {
			foreach ($images as $img) {
				$html = str_replace($img['catchimg'], $img['system'], $html);
			}
		}
		$html = m('common')->html_to_images($html);
		$d = array("content" => $html);
		pdo_update("ewei_shop_goods", $d, array("id" => $goodsid));

		return array("result" => '1', "goodsid" => $goodsid);
	}


	//获取淘宝宝贝数据连接
	function get_taobao_info_url($itemid) {
		return "http://hws.m.taobao.com/cache/wdetail/5.0/?id=" . $itemid;
	}

	//获取淘宝宝贝详情页连接
	function get_taobao_detail_url($itemid) {
		//return 'http://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7B"item_num_id"%3A"'.$itemid.'"%7D';

		return 'http://hws.m.taobao.com/cache/wdesc/5.0/?id=' . $itemid;
	}

	//获取京东宝贝数据连接
	function get_jingdong_info_url($itemid) {
		return "http://item.m.jd.com/ware/view.action?wareId=" . $itemid;
	}

	//获取京东宝贝详情页连接
	function get_jingdong_detail_url($itemid) {
		return 'http://item.m.jd.com/ware/detail.json?wareId=' . $itemid;
	}

	//获取1688宝贝数据连接
	function get_1688_info_url($itemid) {
		return "https://detail.1688.com/offer/" . $itemid.".html";
	}

	function save_image($url,$iscontent)
	{
		global $_W;
		$ext = strrchr($url, ".");
		if ($ext != ".jpeg" && $ext != ".gif" && $ext != ".jpg" && $ext != ".png") {
			return $url;
		}
		if(trim($url)==''){
			return $url;
		}

		$filename=random(32).$ext;
		$save_dir = ATTACHMENT_ROOT.'images/'.$_W['uniacid'].'/'.date('Y').'/'.date('m').'/';

		//创建保存目录
		if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
			return $url;
		}

        $img = @file_get_contents($url);

		if(strlen($img) != 0)
		{
			file_put_contents($save_dir.$filename,$img);
			$imgdir = 'images/'.$_W['uniacid'].'/'.date('Y').'/'.date('m').'/';
			$saveurl = save_media($imgdir.$filename, true);
			return $saveurl;
		}else
		{
			return  '';
		}

	}

//以下方法暂未使用

	function check_remote_file_exists($url) {
		$curl = curl_init($url);
		//不取回数据
		curl_setopt($curl, CURLOPT_NOBODY, true);
		//发送请求
		$result = curl_exec($curl);
		$found = false;
		if ($result !== false) {
			//检查http响应码是否为200
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($statusCode == 200) {
				$found = true;
			}
		}
		curl_close($curl);
		return $found;
	}

	function get_pageno_url($url = '', $pageNo = 1) {
		$url.= "/search.htm?pageNo=" . $pageNo;
		return $url;
	}

	//获取页面
	function get_total_page($url = '', $taobao = false) {
		if (empty($url)) {
			return array("totalpage" => 0);
		}
		$content = $this->get_page_content($url);
		die($content);

		$str = "";
		if ($taobao) {
			$str = "/<span class=\"page-info\">(.*)<\/span>/";
		} else {
			$str = "/<b class=\"ui-page-s-len\">(.*)<\/b>/";
		}
		preg_match($str, $content, $p);
		if (is_array($p)) {
			$pages = explode("/", $p[1]);
			return array("totalpage" => $pages[1]);
		}

		return array("totalpage" => 0);
	}

	function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}

	//获取页面内容
	function get_page_content($url = '', $pageNo = 1) {
		if (empty($url)) {
			return array("totalpage" => 0);
		}
		$url = $this->get_pageno_url($url, $pageNo);
		load()->func('communication');
		$response = ihttp_get($url);
		if (!isset($response['content'])) {
			return array("result" => 0);
		}

		return $response['content'];
	}

	//获取真实url
	function getRealURL($url) {

		if (function_exists("stream_context_set_default")) {
			stream_context_set_default(
				array(
					'http' => array(
						'method' => 'HEAD'
					)
				)
			);
		}

		$header = get_headers($url, 1);
		if (strpos($header[0], '301') || strpos($header[0], '302')) {
			if (is_array($header['Location'])) {
				return $header['Location'][count($header['Location']) - 1];
			} else {
				return $header['Location'];
			}
		} else {
			return $url;
		}
	}

	//获取每页产品(天猫)
	function get_pag_items($pageContent = '') {
		$str = '/data-id="(.*)"/U';
		preg_match_all($str, $pageContent, $items);
		if (isset($items[1])) {
			return $items[1];
		}
		return array();
	}
}
