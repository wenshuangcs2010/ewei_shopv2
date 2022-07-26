<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once(EWEI_SHOPV2_TAX_CORE."cnbuyerapi/sendOmsapi.php");
class HttpUtil_EweiShopV2Model
{

	//获取保税超市库存
	function getGoods($goodssn){
		load()->func('communication');
		$goodsurl="http://www.cnbuyers.cn/index.php?app=webService&act=checkStockWe7&sku=".$goodssn;
		$resp = ihttp_get($goodsurl);
		 $content = $resp['content'];
	      if (empty($content)) {
	            return array();
	      }
	      $content=(array)@json_decode($content);
	      $data=(array)$content['data'];
	      return $data['stock'];
	}
	//更新库存 
	function updateStock($id,$stock){
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => DIS_ACCOUNT));
		$sql="select id from " . tablename('ewei_shop_goods') . " where disgoods_id=:disgoods_id";
		$disgoodslist=pdo_fetchall($sql,array("disgoods_id"=>$goods['id']));
		
		foreach ($disgoodslist as $v) {
			pdo_update("ewei_shop_goods",array("total"=>$stock),array("id"=>$v));
		}
	}

	//更新保税超市库存
	function updateGoods($goodssn,$goodsid,$depot){
        global $_W;
		load()->func('communication');
        $url="http://oms.cnbuyers.cn/api/stock";
        //$url="http://localhost/oms/api/stock";
        SendOmsapi::init($depot['app_id'],$depot['app_secret']);

        $token=SendOmsapi::getToken();

        $resp = ihttp_post($url,array("access_token"=>$token,'only_sku'=>$goodssn));

        $content=$resp['content'];

	    $content=json_decode($content,true);

        if($content['error']>0){
            return array();
        }
        $data=$content['data'];
	    if(empty($data)){return array();}
	    $updatedata=array(
	    	'total'=>$data['stock'],
        );
	    pdo_update("ewei_shop_goods",$updatedata,array("id"=>$goodsid));
	    $sql="select id from " . tablename('ewei_shop_goods') . " where disgoods_id=:disgoods_id";
	    plog('goods.edit', "商品同步ID:{$goodsid}");
	    $disgoodslist=pdo_fetchall($sql,array("disgoods_id"=>$goodsid));
	    if(empty($disgoodslist)){
	    	return 1;
		}
	    $disdata=array(
			'total'=>$data['stock'],
	    	);
	    $sql="update ".tablename("ewei_shop_goods")." SET ";
	    foreach($disdata as $k=>$v){
	    	if(is_numeric($v)){
	    		$str.="`".$k."`=".$v.",";
	    	}elseif(is_string($v)){
	    		$str.="`".$k.'`="'.$v.'",';
	    	}
	    	
	    }
	    $str=substr($str, 0, -1);
	    $sql.=$str;
	    foreach ($disgoodslist as $v) {
		   $t[]=$v['id'];
		}
		$ids=implode(",",$t);
		$sql.=" where id in ($ids)";
		pdo_query($sql);
		plog('goods.edit', "代理商品同步ID:{$ids}");
		return 1;
	}
	function updateGoodsOption($goodssn,$storeroomid){
		require_once(EWEI_SHOPV2_TAX_CORE."toerp/erpHttp.php");
		$n=new ErpHttp();
		$s=$n->getStoreRoom();
       
		//die();
		$retdata=$n->getGoodsStock($storeroomid,1,$goodssn,100);


		if($retdata['result']==500){
		
			return array();
		}

		return $retdata['storeroomStock'];
	}
	function updateAdressGoods($goodssn,$id,$storeroomid){
		global $_W;

		$sql="SELECT * FROM ".tablename("ewei_shop_goods_option")." where goodsid=:goodsid and uniacid=:uniacid";
		$goods_option=pdo_fetchall($sql,array(":goodsid"=>$id,":uniacid"=>$_W['uniacid']));
		$options=array();
		
		foreach ($goods_option as $key => $value) {
			$options[]=array('optionid'=>$value['id'],'specs'=>explode("_",$value['specs']),'goodssn'=>$value['goodssn']);
			$goodssn=$value['goodssn'];
			$productprice=$value['productprice'];
		}

		foreach ($options as $key => $value) {
			 $specs2[]=$value['specs'][0];
		}
		if(empty($specs2)){
		    return false;
        }
		$specs2=array_unique($specs2);
		foreach ($specs2 as $key => $value) {
			$size=pdo_fetch("SELECT title,specid from ".tablename("ewei_shop_goods_spec_item")." where uniacid=:uniacid and id=:specsid",array(":specsid"=>$value,":uniacid"=>$_W['uniacid']));
			$goodsspecsize[$size['title']]=$value;
			$specid=$size['specid'];
		}
	
		pdo_update("ewei_shop_goods_option",array("stock"=>0),array("goodsid"=>$id,"uniacid"=>$_W['uniacid']));
		$storeroom=$this->updateGoodsOption($goodssn,$storeroomid);
		
		$insert_spec_item=array();
		$stock=0;
		foreach($storeroom as $v){
			$size=$v['size'];
            $stock+=$v['stock'];
			if(isset($goodsspecsize[$size])){
				//更新
				$specs1id=$goodsspecsize[$size];
				$costprice=$v['discount']*$productprice;

				pdo_update("ewei_shop_goods_option",array("stock"=>$v['stock'],'costprice'=>$costprice),array("specs"=>$specs1id,"goodsid"=>$id,"uniacid"=>$_W['uniacid']));
			}else{
                if($productprice<=0){
                    continue;
                }
				$insert_specid=array(
					'uniacid'=>$_W['uniacid'],
					'specid'=>$specid,
					'title'=>$size,
					'show'=>1,
					);
				$costprice=$v['discount']*$productprice;
				pdo_insert("ewei_shop_goods_spec_item",$insert_specid);
				$spec_item_id=pdo_insertid();
				$insert_spec_item[]= $spec_item_id;
				$insert_option=array(
					'uniacid'=>$_W['uniacid'],
					'goodsid'=>$id,
					'title'=>$size,
					'productprice'=>$productprice,
					'costprice'=>$costprice,
					'marketprice'=>$productprice,
					'stock'=>$v['stock'],
					'weight'=>500,
					'specs'=>$spec_item_id,
					'skuId'=>$v['goodsId'],
					'goodssn'=>$v['goodsId'],
					'productsn'=>$v['goodsId'],
					'disoptionid'=>0,
					);
				pdo_insert("ewei_shop_goods_option",$insert_option);
			}
		}
		if(!empty($insert_spec_item)){
			$spec=pdo_fetch("SELECT * from ".tablename("ewei_shop_goods_spec")." where uniacid=:uniacid and id=:specsid",array(":specsid"=>$specid,":uniacid"=>$_W['uniacid']));
			$array=unserialize($spec['content']);
		    $content=serialize(array_merge($array,$insert_spec_item));
		    pdo_update("ewei_shop_goods_spec",array("content"=>$content),array("id"=>$specid));
		}
	    pdo_update("ewei_shop_goods",array("total"=>$stock),array('id'=>$id));
		/*
		$specs=$specs1id."_".$specsid;
			$retailPrice=$result['goodsList'][0]['retailPrice'];
			$conprice=$retailPrice*$v['discount'];
			pdo_update("ewei_shop_goods_option",array("stock"=>$v['stock'],'costprice'=>$conprice),array("specs"=>$specs,"goodsid"=>$id,"uniacid"=>$_W['uniacid']));
		*/

		
		return true;
	}
	function getGoodsList($goodssn){
		require_once(EWEI_SHOPV2_TAX_CORE."toerp/erpHttp.php");
		$n=new ErpHttp();
		return $n->getGoodsStock(1,$goodssn,1);
	}
	function oneupdateGoodsprice($godosid){
		global $_W;
		require_once(EWEI_SHOPV2_TAX_CORE."toerp/erpHttp.php");
		$n=new ErpHttp();
		$sql="SELECT id,goodssn,specs from ".tablename("ewei_shop_goods_option")." where goodsid=:gid and uniacid=:uniacid";
		$goodsoption=pdo_fetchall($sql,array(":gid"=>$godosid,":uniacid"=>$_W['uniacid']));
		if(empty($goodsoption)){
			return 0;
		}
		foreach ($goodsoption as $value) {
				$options[$value['goodssn']][]=$value['id'];
		}
		$sql=" update ".tablename("ewei_shop_goods_option")." set costprice= CASE id ";
		foreach ($options as $key => $value) {
			$result=$n->getGoodsList(1,$key,1);
			
			$costprice=$result['goodsList'][0]['retailPrice'];
			if(empty($costprice)){
				$costprice=0;
			}
			foreach ($value as $v) {
				$sql.=" WHEN {$v} THEN {$costprice} ";
				$updateids[]=$v;
			}
		}
		
		$updateids=implode(",", $updateids);
		$sql.=" END where id in ($updateids)";
		pdo_query($sql);
	}
	function updateGoodsPrice($depotid,$goodssn){
		global $_W;
		require_once(EWEI_SHOPV2_TAX_CORE."toerp/erpHttp.php");
		$n=new ErpHttp();
		$sql="SELECT id from ".tablename("ewei_shop_goods")." where depotid=:depotid";
		$goodslist=pdo_fetchall($sql,array(":depotid"=>$depotid));
		if(empty($goodslist)){
			return 0;
		}
		foreach ($goodslist as $key => $goods) {
			$sql="SELECT id,goodssn,specs from ".tablename("ewei_shop_goods_option")." where goodsid=:gid and uniacid=:uniacid and costprice = 0";
			$goodsoption=pdo_fetchall($sql,array(":gid"=>$goods['id'],":uniacid"=>$_W['uniacid']));
			foreach ($goodsoption as $value) {
				$options[$value['goodssn']][]=$value['id'];
			}
		}
		if(empty($options)){
			return 0;
		}
		
		foreach ($options as $key => $value) {
			$result=$n->getGoodsList(1,$key,1);
			$sql=" update ".tablename("ewei_shop_goods_option")." set costprice= CASE id ";
			$costprice=$result['goodsList'][0]['retailPrice'];
			if(empty($costprice)){
				$costprice=0;
			}
			foreach ($value as $v) {
				$sql.=" WHEN {$v} THEN {$costprice} ";
				$updateids[]=$v;
			}
			$updateids=implode(",", $updateids);
			$sql.=" END where id in ($updateids)";
			$updateids="";
		    pdo_query($sql);
		}
		
		return 1;//主站成本更新

	}

	function updateAdStock($depotid,$storeroomid){
		global $_W;
		$sql="SELECT id from ".tablename("ewei_shop_goods")." where depotid=:depotid";
		$goodslist=pdo_fetchall($sql,array(":depotid"=>$depotid));
		$ids=array();
		if(empty($goodslist)){
			return 0;
		}

		foreach ($goodslist as $key => $goods) {
			$goodsoption=pdo_fetchall("SELECT id,goodssn,specs from ".tablename("ewei_shop_goods_option")." where goodsid=:gid and uniacid=:uniacid",array(":gid"=>$goods['id'],":uniacid"=>$_W['uniacid']));
			foreach ($goodsoption as $value) {
				$goodssn=trim($value['goodssn']);
				$specs=explode("_", $value['specs']);
				$specs=reset($specs);
				$ids[$goodssn]['optionid_'.$value['id']]=array('optionid'=>$value['id'],'specid'=>$specs);
			}
		}
		$stockgoods=array();//当前货号库存和尺寸
		foreach ($ids as $key => $value) {
			$storeroom=$this->updateGoodsOption($key,$storeroomid);
			$result=$this->getGoodsList($key);
			$retailPrice=$result['goodsList'][0]['retailPrice'];
			$arr=array();
			$costprice=0;
			foreach($storeroom as $v){
				$size=$v['size'];
				$arr[$key."_".$size]=$v['stock'];
				$costprice=$retailPrice*$v['discount'];
			}
			pdo_update("ewei_shop_goods_option",array("costprice"=>$costprice),array("goodssn"=>$key,"uniacid"=>$_W['uniacid']));
			$stockgoods[$key]=$arr;
		}
		$specsids=array();
		foreach ($ids as $key => $value) {
			foreach ($value as $k => $v) {
				$specsids[]=$v['specid'];
			}
		}
		$specsids=array_unique($specsids);
		$specsids=implode(",", $specsids);

		$sql="SELECT id,title from ".tablename("ewei_shop_goods_spec_item")." where id in($specsids) and uniacid=:uniacid";
		$goodsspecsize=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid']));
		$specs=array();
		foreach ($goodsspecsize as $key => $value) {
			$specs[$value['id']]=$value['title'];
		}
		foreach ($ids as $key => &$value) {
			foreach ($value as $k => &$v) {
				$v['specid']=$specs[$v['specid']];
			}
			unset($v);
		}
		unset($value);
		
		//var_dump($stockgoods);
		foreach ($ids as $key => &$value) {
			if(!empty($stockgoods[$key])){
				$sql=" update ".tablename("ewei_shop_goods_option")." set stock= CASE id ";
				$updateids=array();
					foreach ($value as $k => &$v) {
						$stock=$stockgoods[$key][$key."_".$v['specid']];
						if(!empty($stock)){
							$sql.=" WHEN {$v['optionid']} THEN {$stock} ";
							$v['stock']=$stock;
						}else{
							$sql.=" WHEN {$v['optionid']} THEN 0 ";
							$v['stock']=0;
						}
						$updateids[]=$v['optionid'];
					}
					unset($v);
				$updateids=implode(",", $updateids);
				$sql.=" END where id in ($updateids)";
				//pdo_query($sql);
			}else{
				foreach ($value as $k => &$v) {
					$deupdateid[]=$v['optionid'];
					$v['stock']=0;
				}
				unset($v);
			}
		}
		unset($value);
		//var_dump($ids['S98775']['optionid_425']['stock']);
		//die();
		if(!empty($deupdateid)){
			$deupdateids=implode(",", $deupdateid);
			$sql=" update ".tablename("ewei_shop_goods_option")." set stock= 0 where id in($deupdateids) ";
			pdo_query($sql);//主站更新完毕
		}
		//分销站点
		foreach ($ids as $key => $value) {//分销站点更新完毕
			$goodsoption=pdo_fetchall("SELECT id,disoptionid from ".tablename("ewei_shop_goods_option")." where goodssn=:goodssn and uniacid<>:uniacid and disoptionid!=0",array(":goodssn"=>$key,":uniacid"=>$_W['uniacid']));
			//var_dump($goodsoption);
			if(empty($goodsoption)){
				continue;
			}
			$disoptionid=array();
			$sql=" update ".tablename("ewei_shop_goods_option")." set stock= CASE id ";
			foreach ($goodsoption as $option) {
				$stock=$value['optionid_'.$option['disoptionid']]['stock'];
				$disoptionid[]=$option['id'];//需要更新的ID
				$sql.=" WHEN {$option['id']} THEN {$stock} ";
			}
			$disoptionid=implode(",", $disoptionid);
			$sql.=" END where id in ($disoptionid)";
			//var_dump($sql) ;
			pdo_query($sql);
		}
	    return 1;
	}

	function updatecnbuyergoods($depot){
        global $_W;
	     $depotid=$depot['id'];
         $storeroomid=$depot['storeroomid'];


         $dsn = "mysql:host=cgtx001-gmys.rwlb.rds.aliyuncs.com;dbname=xsdb_db";
         $account="xsdb_cnbuyer_db";

         $password="xsdb_cnbuyer_db!@#)(*ABC";
         $db = new PDO($dsn, $account,$password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES'utf8';"));

        $pagecount=100;
        $pageindex=1;

        $countsql="select count(*) from ".tablename("ewei_shop_goods")." where goods_source =:warehouseid and hasoption=0 and uniacid=1 ";
        $stmt=$db->prepare($countsql);
        $stmt->execute(array(":warehouseid"=>$storeroomid));
        foreach($stmt as $row){
            $total=$row[0];
        }

        $allpage=ceil($total/$pagecount);

        for($pageindex=1;$pageindex<=$allpage;$pageindex++){
            $sql="SELECT * from ".tablename("ewei_shop_goods")." where goods_source =:warehouseid and hasoption=0 and uniacid=1 limit ".($pageindex-1)*$pagecount.",".$pagecount;
            $stmtitem=$db->prepare($sql);
            $stmtitem->execute(array(":warehouseid"=>$storeroomid));
            $temp = $stmtitem->fetchAll(pdo::FETCH_ASSOC);
            if (!empty($temp)) {
                $r=array();
                foreach ($temp as $key => $row) {
                    $r[$row['goodssn']]=$row;
                }
               $goodssnlist= array_keys($r);

                $filed=array(
                    'id'=>'',
                    'title'=>'',
                    'total'=>'',
                    'marketprice'=>"",
                    'thumb'=>'',
                    'thumb_url'=>'',
                    'content'=>'',
                    'productprice'=>'',
                    'costprice'=>'',
                    'minprice'=>'',
                    'maxprice'=>'',
                );
                $instalfiled=array(
                    'id'=>'',
                    'title'=>'',
                    'total'=>'',
                    'marketprice'=>"",
                    'thumb'=>'',
                    'thumb_url'=>'',
                    'content'=>'',
                    'productprice'=>'',
                    'costprice'=>'',
                    'minprice'=>'',
                    'maxprice'=>'',
                    'uniacid'=>'',
                    'depotid'=>$depotid,
                    'goodssn'=>'',
                    'weight'=>'',
                    'status'=>'',
                    'createtime'=>time(),
                );
                $goodslist=pdo_fetchall("select id,goodssn from ".tablename("ewei_shop_goods")." where uniacid=:uniacid and goodssn in(".implode(",",array_map('array_str',$goodssnlist)).") ",array(":uniacid"=>$_W['uniacid']),'goodssn');
                $goodsupdatesql="INSERT into ".tablename("ewei_shop_goods")."(".implode(",",array_keys($filed)).") values";
                unset($instalfiled['id']);
                $installsql="INSERT into ".tablename("ewei_shop_goods")."(".implode(",",array_keys($instalfiled)).") values";

                $isupdate=false;
                $isinstall=false;
                foreach ($r as $k=>$v){

                    $instalfiled['title']=$filed['title']=$v['title'];
                    $instalfiled['total']=$filed['total']=$v['total'];
                    $instalfiled['marketprice']= $filed['marketprice']=$v['marketprice'];
                    $thumb='';
                    $thumb_url=serialize(array());
                    if(!(strexists($v['thumb'], 'http:') || strexists($v['thumb'], 'https:')) ){
                        $thumb="https://xsdbimg.cnbuyers.cn/".$v['thumb'] ;
                        $thumb_url=iunserializer($v['thumb_url']);
                        $temp_thumb_list=array();
                        foreach ($thumb_url as $temp_thumb){
                            $temp_thumb_list[]="https://xsdbimg.cnbuyers.cn/".$temp_thumb ;
                        }
                        $thumb_url=serialize($temp_thumb_list);

                    }else{
                        $thumb=$v['thumb'];
                        $thumb_url=$v['thumb_url'];
                    }
                    $instalfiled['thumb']=$filed['thumb']=$thumb;
                    $instalfiled['thumb_url']=$filed['thumb_url']=empty($thumb_url) ? serialize(array()) :$thumb_url;
                    $instalfiled['content']=$filed['content']=$v['content'];
                    $instalfiled['productprice']=$filed['productprice']=$v['productprice'];
                    $instalfiled['costprice']=$filed['costprice']=$v['costprice'];
                    $instalfiled['minprice']=$filed['minprice']=$v['minprice'];
                    $instalfiled['maxprice']=$filed['maxprice']=$v['maxprice'];
                    $instalfiled['uniacid']=$_W['uniacid'];
                    $instalfiled['depotid']=$depotid;
                    $instalfiled['goodssn']=$v['goodssn'];
                    $instalfiled['weight']=$v['weight'];
                    $instalfiled['status']=-1;
                    $instalfiled['createtime']=time();



                    if(isset($goodslist[$v['goodssn']]) && !empty($goodslist[$v['goodssn']])){
                        $isupdate=true;
                        $filed['id']=$goodslist[$v['goodssn']]['id'];
                        $goodsupdatesql.="(".implode(",",array_map('array_str',array_values($filed)))."),";
                    }else{
                        $isinstall=true;
                        $installsql.="(".implode(",",array_map('array_str',array_values($instalfiled)))."),";

                    }
                }

                if($isupdate){
                    $goodsupdatesql=substr($goodsupdatesql,0,strlen($goodsupdatesql)-1);
                    unset($filed['id']);
                    $goodsupdatesql.=" on duplicate key update ".implode(",",array_map('array_updatesqlstr',array_keys($filed)));

                    try{
                        pdo_query($goodsupdatesql,array());
                    }catch (Exception $e){
                        return $e->getMessage();
                    }


                }
                if($isinstall){
                    $installsql=substr($installsql,0,strlen($installsql)-1);

                    try{
                        pdo_query($installsql);
                    }catch (Exception $e){
                        return $e->getMessage();
                    }

                }
            }
        }
        return 1;

    }

    function updateOmsStock($depostid){
        global $_W;
        $pagecount='100';
        $pageindex=1;

        $countsql="select count(*) from ".tablename("ewei_shop_goods")." where depotid =:depotid and uniacid=:uniacid";
        $total= pdo_fetchcolumn($countsql,array(":depotid"=>$depostid,":uniacid"=>$_W['uniacid']));

        $allpage=ceil($total/$pagecount);

        for($pageindex=1;$pageindex<=$allpage;$pageindex++){
            $sql="SELECT id,goodssn,hasoption from ".tablename("ewei_shop_goods")." where depotid =:depotid and uniacid=:uniacid limit ".($pageindex-1)*$pagecount.",".$pagecount;
            $goodslist=pdo_fetchall($sql,array(":depotid"=>$depostid,":uniacid"=>$_W['uniacid']));
            if(empty($goodslist)){
                return 0;
            }
            $goodssnlist=array();
            foreach ($goodslist as $l){

                if($l['hasoption']){
                    $optionlist=pdo_fetchall("select id,goodssn from ".tablename("ewei_shop_goods_option")." where goodsid=:goodsid",array(":goodsid"=>$l['id']));

                    foreach ($optionlist as $option){
                        $goodssnlist[$option['goodssn']]=array('id'=>$option['id'],'hasoption'=>1,'skucode'=>$option['goodssn']);
                    }

                }else{
                    $goodssnlist[$l['goodssn']]=array('id'=>$l['id'],'hasoption'=>$l['hasoption'],'skucode'=>$l['goodssn']);
                }


            }
            $url="http://oms.cnbuyers.cn/api/omsstock";
            load()->func('communication');
            $postdata=array(
                'sku'=> json_encode(array_keys($goodssnlist)),
            );

            $resp = ihttp_request($url, $postdata);
            $content=(array)json_decode($resp['content'],true);
            if(isset($content['data'])&& !empty($content['data']) ){
                $updatesql="insert into ".tablename("ewei_shop_goods")."  (id,total) values ";
                $updateoptionsql="insert into ".tablename("ewei_shop_goods_option")."  (id,stock) values ";
                $array_data=array();
                foreach ($content['data'] as $item){
                    $array_data[$item['sku']]=$item['stock'];
                }

                $is_update=false;
                $is_updateoption=false;
                foreach ($goodssnlist as $item){
                    if($item['hasoption']){
                        $stock=empty($array_data[$item['skucode']]) ? 0 :$array_data[$item['skucode']];
                        $updateoptionsql.="({$item['id']},{$stock}),";
                        $is_updateoption=true;
                    }else{
                        $is_update=true;
                        $stock=empty($array_data[$item['skucode']]) ? 0 :$array_data[$item['skucode']];
                        $updatesql.="({$item['id']},{$stock}),";
                    }
                }
                $updatesql=substr($updatesql,0,strlen($updatesql)-1);
                $updateoptionsql=substr($updateoptionsql,0,strlen($updateoptionsql)-1);
                $updatesql.=" on duplicate key update total=values(total)";
                $updateoptionsql.=" on duplicate key update stock=values(stock)";
                if($is_update){
                    pdo_query($updatesql);
                }
                if($is_updateoption){
                    pdo_query($updateoptionsql);
                }
            }
        }


    }
	function updatecnbuyerStock($depostid,$storeroomid){
		global $_W;
		$sql="SELECT id,goodssn from ".tablename("ewei_shop_goods")." where depotid =:depotid and uniacid=:uniacid";
		$goodslist=pdo_fetchall($sql,array(":depotid"=>$depostid,":uniacid"=>$_W['uniacid']));
		if(empty($goodslist)){
			return 0;
		}
		$cnbuyergoodslist=m("cnbuyerdb")->getgoodslist($storeroomid);
		$goodssplist=array();
		foreach ($cnbuyergoodslist as $goods) {
			if($goods['if_show']==0){
				$goods['stock']=0;
			}
			$goodssplist["sku_".$goods['only_sku']]=$goods['stock'];
		}
		unset($cnbuyergoodslist);
		$sql="update ".tablename("ewei_shop_goods")." set total= CASE id  ";
		$updateids=array();
		$goodsstock=array();
		foreach ($goodslist as $goods) {
			$stock=$goodssplist['sku_'.$goods['goodssn']];
			if(isset($stock)){
				$sql.=" WHEN {$goods['id']} THEN {$stock} ";
				$updateids[]=$goods['id'];
				$goodsstock['goodsid_'.$goods['id']]=$stock;
			}
			//知道 某个商品ID的库存	
		}
		$goodsids=implode(",", $updateids);
		$sql.=" END where id in ($goodsids)";

		pdo_query($sql);
		$updatesql="insert into ".tablename("ewei_shop_goods")."  (id,total) values ";
		$sql=" SELECT id,disgoods_id FROM ".tablename("ewei_shop_goods")." where uniacid <> {$_W[uniacid]} and disgoods_id in ($goodsids)";
			$disgoodslist=pdo_fetchall($sql,array(":disgoods_id"=>$g));
			foreach ($disgoodslist as $goods) {
				$stock=$goodsstock['goodsid_'.$goods['disgoods_id']];
				$updatesql.="({$goods[id]},{$stock}),";
			}
		$updatesql=substr($updatesql,0,strlen($updatesql)-1);
		$updatesql.=" on duplicate key update total=values(total)";
		pdo_query($sql);
		
	}

}