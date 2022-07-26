<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Gygygoods_EweiShopV2Page extends PluginWebPage {

    function main()
    {
        global $_W, $_GPC;
        $uploadStart = '0';
        $uploadnum = '0';
        $excelurl =$_W['siteroot'].'addons/ewei_shopv2/plugin/taobao/data/test.xlsx';
        if ($_W['ispost']) {
            $rows = m('excel')->importall('excelfile');
            $depostid=intval($_GPC['depotid']);
            $num = count($rows);
            foreach ($rows as $key=> $item){

                if($key==0){
                    $columns= array_flip($item);
                    continue;
                }

                $costprice=$item[$columns['供货价']];
                $productprice=$item[$columns['RMB市场价']];
                $marketprice=$item[$columns['零售价']];
                $dist_costprice=$item[$columns['分销价']];
                $total=$item[$columns['总库存']];
                $goodssn=$item[$columns['spucode']];
                $spanc =$item[$columns['规格']];
                $color =$item[$columns['颜色']];
                $stocklist =explode(",",$item[$columns['库存']]);
                $optionlist=array();
                $hasoption=0;
                $spanclist=explode(",",$spanc);
                if(count($spanclist)>1){
                    $hasoption=1;
                    foreach ($spanclist as $k=> $val){
                        $optionlist[$val]= array('goodssn'=>$goodssn."_".$val,'total'=>$stocklist[$k],'spec'=>$val);
                    }

                }else{
                    $goodssn=$goodssn."_".$item[$columns['规格']];
                }
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'merchid' => 0,
                    'goodssn'=>$goodssn,
                    'catch_source' => 'gygygoods',
                    'catch_id' => '',
                    'catch_url' => '',
                    'title' => $item[$columns['商品名']],
                    'total' => $total,
                    'marketprice' => $marketprice,
                    'pcate' => $item[$columns['分类1']],
                    'ccate' => $item[$columns['分类2']],
                    'tcate' => $item[$columns['分类3']],
                    'cates' => empty($item[$columns['分类3']]) ? $item[$columns['分类2']] : $item[$columns['分类3']],
                    'sales' => 0,
                    'createtime' => time(),
                    'updatetime' => time(),
                    'status' => 0,
                    'weight'=>1000,
                    'hasoption'=>$hasoption,
                    'minprice' => $marketprice,
                    'maxprice' => $marketprice,
                    'costprice'=>$costprice,
                    'productprice'=>$productprice,
                    'depotid'=>$depostid
                );
                $thumb_url = array();
                $thumblist=$item[$columns['主图']];
                $thumblist=explode(",",$thumblist);
                $data['thumb']=$this->thumblist_substr_replace($thumblist[0],true);
                if(count($thumblist)>1){
                    unset($thumblist[0]);
                    $data['thumb_url'] = serialize($thumblist);
                }else{
                    $data['thumb_url']=serialize(array());
                }
                $content="";
                $content_gy =$item[$columns['详情图']];

                $content_gy =explode(",",$content_gy);
                $content="<p>";
                foreach ($content_gy as $v){
                    $content.='<img src="'.$this->thumblist_substr_replace($v).'" width="100%"/>';
                }
                $content.="</p>";
                $data['content']=$content;
                $goodsinfo=pdo_fetch("select id from ".tablename("ewei_shop_goods")." where goodssn=:goodssn and uniacid=:uniacid",array(":goodssn"=>$goodssn,':uniacid'=>$_W['uniacid']));

                if($goodsinfo){
                    pdo_update("ewei_shop_goods",$data,array("id"=>$goodsinfo['id']));
                    $goodsid=$goodsinfo['id'];
                }else{
                    pdo_insert('ewei_shop_goods',$data);
                    $goodsid=pdo_insertid();
                }

                if($data['hasoption']){
                    $spancdata=array(
                        'uniacid'=>$_W['uniacid'],
                        'goodsid'=>$goodsid,
                        'title'=>$color,
                    );
                    $spancdata_info=pdo_fetch("select id from ".tablename("ewei_shop_goods_spec")." where goodsid=:goodsid",array(":goodsid"=>$goodsid));

                    if($spancdata_info){

                        $goodsoptionlist=pdo_fetchall("select * from ".tablename("ewei_shop_goods_option")." where goodsid=:goodsid",array(":goodsid"=>$goodsid),'title');

                        $optioninstalsql="INSERT into ".tablename("ewei_shop_goods_option")."(`id`,`productprice`,`marketprice`,`costprice`,`stock`) values";

                        foreach ($goodsoptionlist as $kdd=> $option){
                            $optiondata=array(
                                'id'=>$option['id'],
                                'productprice'=>$productprice,
                                'marketprice'=>$marketprice,
                                'costprice'=>$costprice,
                                'stock'=>$optionlist[$kdd]['total'],
                            );
                            $optioninstalsql.="(".implode(",",array_map('array_str',$optiondata))."),";
                        }
                        $optioninstalsql=substr($optioninstalsql,0,strlen($optioninstalsql)-1);
                        $optioninstalsql.=" on duplicate key update productprice=values(productprice),marketprice=values(marketprice),costprice=values(costprice),stock=values(stock)";
                        pdo_query($optioninstalsql,array());

                    }else{
                        pdo_insert('ewei_shop_goods_spec',$spancdata);
                        $specid=pdo_insertid();
                        $speclist=array();
                        $optioninstalsql="INSERT into ".tablename("ewei_shop_goods_option")."(`uniacid`,`goodsid`,`title`,`productprice`,`marketprice`,`costprice`,`stock`,`weight`,`specs`,`goodssn`) values";
                        $itemids=array();

                        foreach ($optionlist as $kjj=> $k_value){
                            $specitem=array(
                                'uniacid'=>$_W['uniacid'],
                                'specid'=>$specid,
                                'title'=>$k_value['spec'],
                                'show'=>1,
                                'displayorder'=>$kjj,
                            );
                            pdo_insert('ewei_shop_goods_spec_item',$specitem);
                            $specitemid= pdo_insertid();
                            $itemids[]=$specitemid;
                            $optiondata=array(
                                'uniacid'=>$_W['uniacid'],
                                'goodsid'=>$goodsid,
                                'title'=>$k_value['spec'],
                                'productprice'=>$productprice,
                                'marketprice'=>$marketprice,
                                'costprice'=>$costprice,
                                'stock'=>$k_value['total'],
                                'weight'=>1000,
                                'specs'=>$specitemid,
                                'goodssn'=>$k_value['goodssn'],

                            );
                            $optioninstalsql.="(".implode(",",array_map('array_str',$optiondata))."),";
                        }
                        $optioninstalsql=substr($optioninstalsql,0,strlen($optioninstalsql)-1);
                        pdo_query($optioninstalsql,array());
                        pdo_update("ewei_shop_goods_spec",array('content' => serialize($itemids)),array('id'=>$specid));
                    }
                }
                $li=pdo_fetchall("select id from ".tablename("ewei_shop_goods_param")." where goodsid=:goodsid",array(":goodsid"=>$goodsid));
                if(empty($li)){
                    $baranname=$item[$columns['品牌']];
                    $paramssql="INSERT into ".tablename("ewei_shop_goods_param")."(`uniacid`,`goodsid`,`title`,`value`) values";
                    if(!empty($baranname)){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'品牌',
                            'value'=>$baranname,
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        // pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if(!empty($item[$columns['季节']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'季节',
                            'value'=>$item[$columns['季节']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if(!empty($item[$columns['性别']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'性别',
                            'value'=>$item[$columns['性别']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if(!empty($item[$columns['产地']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'产地',
                            'value'=>$item[$columns['产地']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    } if(!empty($item[$columns['材质']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'材质',
                            'value'=>$item[$columns['材质']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        // pdo_insert("ewei_shop_goods_param",$params);
                    }if(!empty($item[$columns['颜色']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'颜色',
                            'value'=>$item[$columns['颜色']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if(!empty($item[$columns['品类']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'品类',
                            'value'=>$item[$columns['品类']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if(!empty($item[$columns['描述']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'描述',
                            'value'=>$item[$columns['描述']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if(!empty($item[$columns['商品英文名']])){
                        $params=array(
                            'uniacid'=>$_W['uniacid'],
                            'goodsid'=>$goodsid,
                            'title'=>'商品英文名',
                            'value'=>$item[$columns['商品英文名']]
                        );
                        $paramssql.="(".implode(",",array_map('array_str',$params))."),";
                        //pdo_insert("ewei_shop_goods_param",$params);
                    }
                    if($params){
                        $paramssql=substr($paramssql,0,strlen($paramssql)-1);
                        pdo_query($paramssql,array());

                    }
                }

            }
            $this->message("添加商品成功");

        }
        $sql='SELECT * FROM ' . tablename('ewei_shop_depot') . ' WHERE `uniacid` = :uniacid ';
        $depostlist = pdo_fetchall($sql, array(':uniacid' => $_W['uniacid']), 'id');
        include $this->template();
    }
    private function thumblist_substr_replace ($url,$_witch=false){

        $url=str_replace("http","https",$url);
        if($_witch){
            $url=$url.'?imageView2/0/w/800/h/800';
        }else{
            $url=$url.'?imageView2/0/w/750';
        }
        return $url;
    }

}