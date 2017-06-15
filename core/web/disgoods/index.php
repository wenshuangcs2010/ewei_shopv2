<?php

class Index_EweiShopV2Page extends WebPage {
	function main(){
		global $_W, $_GPC;
		$uniacid=DIS_ACCOUNT;
		$pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $groupcondition = '';
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $condition=" WHERE 1 and g.uniacid=:uniacid";
        $endtime=time();
        $sqlcondition = ' left join '.tablename("ewei_shop_goodsresel").' gr on g.id = gr.goods_id';
        $params = array(':uniacid' => $uniacid);
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);

            $sqlcondition .= ' left join ' . tablename('ewei_shop_goods_option') . ' op on g.id = op.goodsid';

            if ($merch_plugin) {
                $sqlcondition .= " left join " . tablename('ewei_shop_merch_user') . " merch on merch.id = g.merchid and merch.uniacid=g.uniacid";
            }

            $groupcondition = ' group by g.`id`';

            $condition .= ' AND (g.`id` = :id or g.`title` LIKE :keyword or g.`keywords` LIKE :keyword or g.`goodssn` LIKE :keyword or g.`productsn` LIKE :keyword or op.`title` LIKE :keyword or op.`goodssn` LIKE :keyword or op.`productsn` LIKE :keyword';
            if ($merch_plugin) {
                $condition .= ' or merch.`merchname` LIKE :keyword';
            }
            $condition .= ' )';

            $params[':keyword'] = '%' . $_GPC['keyword'] . '%';
            $params[':id'] = $_GPC['keyword'];
        }
        if(!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])){
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND g.createtime>={$starttime} and g.createtime<={$endtime}";
        }
        if (!empty($_GPC['cate'])) {
            $_GPC['cate'] = intval($_GPC['cate']);
            $condition .= " AND FIND_IN_SET({$_GPC['cate']},cates)<>0 ";
        }
         if (!empty($_GPC['disstatus']) && $_GPC['disstatus']!=0) {
            $condition .= " AND g.id not in(SELECT disgoods_id from ims_ewei_shop_goods AS g1 WHERE g1.`uniacid` = :guniacid) ";
             $params[':guniacid'] = $_W['uniacid'];
         }
        $condition.=" AND g.status > 0 and g.checked=0 and g.deleted=0 and g.total>0 and  g.isdis=1 ";
        //var_dump($condition);
        $sql = 'SELECT g.id FROM ' . tablename('ewei_shop_goods') . 'g' . $sqlcondition . $condition . $groupcondition;
        $total_all = pdo_fetchall($sql, $params);
        $total = count($total_all);
        unset($total_all);
        if (!empty($total)) {
            $sql = 'SELECT g.*,gr.disprice,gr.hasoptions as g1hasoption FROM ' . tablename('ewei_shop_goods') . 'g' . $sqlcondition . $condition . $groupcondition . ' ORDER BY g.`status` DESC, g.`displayorder` DESC,
                g.`id` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
                //var_dump($sql);
                //die();
            $list = pdo_fetchall($sql, $params);
            $pager = pagination($total, $pindex, $psize);

            if ($merch_plugin) {
                $merch_user = $merch_plugin->getListUser($list,'merch_user');
                if (!empty($list) && !empty($merch_user)) {
                    foreach ($list as &$row) {
                        $row['merchname'] = $merch_user[$row['merchid']]['merchname'] ? $merch_user[$row['merchid']]['merchname'] : $_W['shopset']['shop']['name'];
                    }
                }
            }
        }
        $disinfo=Dispage::getDisInfo($_W['uniacid']);
        $disleve=$disinfo['resellerid'];
        //获取代理商品列表
        $disgoodslist=pdo_fetchall("SELECT disgoods_id from ".tablename("ewei_shop_goods") ." WHERE uniacid=:uniacid and disgoods_id>0",array(":uniacid"=>$_W['uniacid']));
        foreach($disgoodslist as $goodsid){
			$disr[$goodsid['disgoods_id']]=$goodsid['disgoods_id'];
        }
       	if(!empty($list)){
       		foreach ($list as &$goods) {
                if($goods['g1hasoption']){
                    $disprice=json_decode($goods['disprice'],true);
                    $disprice=$disprice['level'.$disleve];
                     //var_dump($disprice);
                    $dispricekey = array_search(min($disprice),$disprice);
                   
                    $goods['disprice']=$disprice[$dispricekey];
                    $goods['zprice']=$goods['marketprice']-$disprice[$dispricekey];
                }else{
                    $disprice=unserialize($goods['disprice']);
                    $goods['disprice']=$disprice[$disleve];
                    $goods['zprice']=$goods['marketprice']-$disprice[$disleve];
                }
        		
        	}
        	unset($goods);
       	}
        $categorys = m('shop')->getFullCategory(true);
		$category = array();
		foreach($categorys as $cate){
			$category[$cate['id']] = $cate;
		}
		include $this->template();
	}


	function ajaxpost(){
        global $_W, $_GPC;
        if($_GPC['checked']==1){
            $goods= pdo_fetch("select * from ".tablename("ewei_shop_goods")." WHERE id=:id and uniacid=:uniacid",array(":id"=>$_GPC['goods_id'],':uniacid'=>DIS_ACCOUNT));
            //检测商品是否设置代理价
            $disinfo=Dispage::getDisInfo($_W['uniacid']);
            $resellerid=$disinfo['resellerid'];
 
            $disprce= Dispage::get_goods_disprice($goods['id'],$resellerid);
            if($disprce==0){
               // show_json(0,"未设置代理价请联系管理员");
                echo json_encode(array('status'=>0,'message'=>"未设置代理价请联系管理员"));
                return ;
            }
            
            if($goods){
                $goods['disgoods_id']=$_GPC['goods_id'];
                $goods['status']=0;
                $goods['uniacid']=$_W['uniacid'];
                $goods['costprice']=0;

                //$goods['cate']="";
                unset($goods['id']);
                unset($goods['nocommission']);
                unset($goods['hascommission']);
                unset($goods['hidecommission']);
                unset($goods['commission1_rate']);
                unset($goods['commission2_rate']);
                unset($goods['commission3_rate']);
                unset($goods['commission1_pay']);
                unset($goods['commission2_pay']);
                unset($goods['commission3_pay']);
                unset($goods['commission_thumb']);
                $goods['discounts']=json_encode(array("type"=>0));
                unset($goods['isdiscount']);
                pdo_insert("ewei_shop_goods",$goods);
                $insertgoodsid=pdo_insertid();//商品ID
               
                $goods_goods_spec=pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_goods_spec")." WHERE goodsid=:id",array(":id"=>$_GPC['goods_id']));
                $speclist=array();
                if(empty($goods_goods_spec)){
                     plog('goods.edit', "复制一个代理商品到当前公众号<br/>ID: {$goods['id']}<br/>商品名称: {$goods['title']}");
                     return false;
                }
                foreach ($goods_goods_spec as $spec) {
                	$oldspecid=$spec['id'];
                	unset($spec['id']);
					unset($spec['content']);
					$spec['uniacid']=$_W['uniacid'];
					$spec['goodsid']=$insertgoodsid;
                	pdo_insert("ewei_shop_goods_spec",$spec);
                	$specid=pdo_insertid();
					$shop_goods_spec_item=pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_goods_spec_item")." WHERE specid=:id",array(":id"=>$oldspecid));
					$t=array();
					foreach ($shop_goods_spec_item as $spec_item) {
						$oldspec_itemid=$spec_item['id'];
						unset($spec_item['id']);
						$spec_item['specid']=$specid;
						$spec_item['uniacid']=$_W['uniacid'];
                        pdo_insert("ewei_shop_goods_spec_item",$spec_item);
                        $specitemid=pdo_insertid();
                        $speclist[$specid][]=array('new'=>$specitemid,'old'=>$oldspec_itemid);
                        $t[]=$specitemid;
					}
					$spec['content']=serialize($t);
					pdo_update("ewei_shop_goods_spec",$spec,array("id"=>$specid));
                }

                $shop_goods_option=pdo_fetchall("SELECT * FROM ".tablename("ewei_shop_goods_option")." WHERE goodsid=:id",array(":id"=>$_GPC['goods_id']));
                $oldgoods=array();
                foreach ($shop_goods_option as $key => $value) {
                    $oldgoods[$value['specs']]=$value;
                }
                $oldspec_item=array();
                $newspec_item=array();
                foreach ($speclist as $key => $o) {
                    $old=array();
                    $new=array();
                    foreach ($o as  $v) {
                        $old[]=$v['old'];
                        $new[]=$v['new'];
                    }
                    $oldspec_item[$key][]=$old;
                    $newspec_item[$key][]=$new;
                }
                $old1arr=reset($oldspec_item);
                $old2arr=end($oldspec_item);
                $new1arr=reset($newspec_item);
                $new2arr=end($newspec_item);
                $dikae=$this->combineDika($old1arr[0],$old2arr[0]);
                $dikae2=$this->combineDika($new1arr[0],$new2arr[0]);
                $disprice=Dispage::get_disprice_order($_GPC['goods_id'],$resellerid);
                //var_dump($disprice);
               foreach ($dikae as $key => $value) {
                  $l= implode("_", $value);
                  $l2=implode("_", $dikae2[$key]);//新增加
                  $disgoodsprice=$disprice['option'.$oldgoods[$l]['id']];
                  $goods=$oldgoods[$l];
                  
                  $goods['goodsid']=$insertgoodsid;
                  $goods['specs']=$l2;
                  $goods['disoptionid']=$goods['id'];
                  $goods['costprice']=$disgoodsprice;
                  $goods['uniacid']=$_W['uniacid'];
                  unset($goods['id']);
                  pdo_insert("ewei_shop_goods_option",$goods);
                  //var_dump($oldgoods[$l]);
               }
                //die();
                plog('goods.edit', "复制一个代理商品到当前公众号<br/>ID: {$goods['id']}<br/>商品名称: {$goods['title']}");
            }
        }
     
        if($_GPC['checked']==0){
        $id = intval($_GPC['goods_id']);
        $goods=pdo_fetch("select * from ".tablename("ewei_shop_goods")." WHERE disgoods_id=:disgoods_id and uniacid=:uniacid",array(":disgoods_id"=>$id,':uniacid'=>$_W['uniacid']));
        //var_dump($goods);
        if($goods['hasoption']==1){
            pdo_delete("ewei_shop_goods_option",array("goodsid"=>$goods['id'],"uniacid"=>$_W['unaicid']));
            $goodsspeclist=pdo_fetchall("select id from ".tablename("ewei_shop_goods_spec")." where goodsid=:goodsid and uniacid=:uniacid",array(":goodsid"=>$goods['id'],":uniacid"=>$_W['uniacid']));
            foreach ($goodsspeclist as $value) {
                pdo_delete("ewei_shop_goods_spec_item",array("specid"=>$value['id'],"uniacid"=>$_W['uniacid']));
            }
            pdo_delete("ewei_shop_goods_spec",array("goodsid"=>$goods['id'],"uniacid"=>$_W['uniacid']));

        }
        pdo_delete('ewei_shop_goods', array('id' => $goods['id']));

        plog('goods.edit', "从回收站彻底删除商品<br/>ID: {$goods['id']}<br/>商品名称: {$goods['title']}");
    
        }
        show_json(1,array("goods_id"=>$_GPC['goods_id'],'check'=>$_GPC['checked']));
    }


    function export(){
        global $_W, $_GPC;
        if($_W['uniacid']!=DIS_ACCOUNT){
            $this->message("非法访问");
        }
        if($_W['ispost']){
            //var_Dump($_FILES);
            if (!empty ($_FILES['file']['name']))
             {
                $tmp_file = $_FILES['file']['tmp_name'];
                $file_types = explode ( ".", $_FILES ['file'] ['name'] );
                $file_type = $file_types [count ( $file_types ) - 1];
                $tempfilt_type=strtolower ($file_type);
                if ($tempfilt_type != "xls" && $tempfilt_type != "xlsx")
                {
                      $this->message ( '不是Excel文件，重新上传' );
                }
                $savePath = EWEI_SHOPV2_PATH . 'cert/';
                $str = "dis".date ('Ymdhis' );
                //var_dump($savePath);
                //die();
                $file_name = $str . "." . $file_type;
                $ret=copy($tmp_file, $savePath . $file_name);
                if (!$ret) 
                {
                    $this->message ( '上传失败' );
                }
                //wsq 代理等级
                $reseller=pdo_fetchall("SELECT * from ".tablename("ewei_shop_reseller"));
                foreach($reseller as $rel){
                    $re[$rel['name']]=$rel['id'];
                }
                require_once IA_ROOT . '/framework/library/phpexcel/PHPExcel.php';
                //获取会员等级
                $levels = m('member')->getLevels();
                foreach($levels as &$l){
                    $l['key'] ='level'.$l['id'];
                }
                unset($l);
                $levels =array_merge(array(
                    array(
                        'id'=>0,
                        'key'=>'default',
                        'levelname'=>empty($_W['shopset']['shop']['levelname'])?'默认会员':$_W['shopset']['shop']['levelname']
                    )
                ),$levels);
               // $excel = new PHPExcel();
                $objReader = PHPExcel_IOFactory::createReaderForFile($savePath.$file_name);
                $objPHPExcel = $objReader->load($savePath.$file_name);
                //var_Dump($objPHPExcel->setActiveSheetIndex(1));
               $sheet= $objPHPExcel->getActiveSheet(1);
               $sheetarray=$sheet->toArray();
               /*
               foreach($sheet->getRowIterator() as $row)  //逐行处理
                {
                    if($row->getRowIndex()<1)  //确定从哪一行开始读取
                    {
                         continue;
                    }
                     foreach($row->getCellIterator() as $key=>$cell)  //逐列读取
                     {
                        $data = $cell->getValue(); //获取cell中数据
                        if($row->getRowIndex()==1){
                            $sheetdata[$data]=$data;
                            continue;
                        }
                    }
                }*/
                //$titledata=$sheetarray[0];
                unset($sheetarray[0]);
               foreach ($sheetarray as $key => $value) {
                    $goodsn=$value[0];
                    if(!empty($goodsn)){
                        unset($value[0]);
                        foreach ($value as $key => $v) {
                            if($key<5){
                                $reldata[]=$v;
                            }else{
                                $levels[]=$v;
                            }
                        }
                         var_dump($reldata);//代理价
                         var_dump($levels);//会员价
                    }
                   
                   echo "<br/>";
               }
                //var_dump($sheetdata);
                //$goodsn = $objPHPExcel->getActiveSheet(1)->getCell('A1')->getValue();
                //$distype = $objPHPExcel->getActiveSheet(1)->getCell('b1')->getValue();
                //var_DUMP($data1);
                unset($sheet);
                unset($objReader);
                unlink($savePath.$file_name);
                die();
             }
             $this->message("文件不存在");
        }
        include $this->template();
    }


    /** 
     * 所有数组的笛卡尔积 
     * 
     * @param unknown_type $data 
     */  
    function combineDika() {
        $data = func_get_args();
        $cnt = count($data);
        $result = array();
        foreach($data[0] as $item) {
            $result[] = array($item);
        }
        for($i = 1; $i < $cnt; $i++) {
            $result = $this->combineArray($result,$data[$i]);
        }
        return $result;
    }
    /**
     * 两个数组的笛卡尔积
     *
     * @param unknown_type $arr1
     * @param unknown_type $arr2
     */
    function combineArray($arr1,$arr2) {
        $result = array();  
        foreach ($arr1 as $item1) {
            foreach ($arr2 as $item2) {
                $temp = $item1;
                $temp[] = $item2;
                $result[] = $temp;
            }
        }
        return $result;  
    }
}