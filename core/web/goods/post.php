<?php

global $_W, $_GPC;

$shopset_level = intval($_W['shopset']['commission']['level']);

$id = intval($_GPC['id']);

$item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_goods') . " WHERE id = :id and uniacid = :uniacid", array(':id' => $id,':uniacid'=>$_W['uniacid']));

$labelname = json_decode($item['labelname'],true);
$packgoods=pdo_fetch("SELECT * from ".tablename("ewei_shop_goodspacks") . " WHERE goodsid = :id and uniacid = :uniacid", array(':id' =>$item['id'],':uniacid'=>$_W['uniacid']));
if(!empty($packgoods)){
    $packgs=json_decode($packgoods['packgoods'],true);
    foreach ($packgs as &$pacgoods) {
       $sql="SELECT title,thumb from ".tablename("ewei_shop_goods")." where id=:goodsid and uniacid=:uniacid";
       $packgo=pdo_fetch($sql,array(":goodsid"=>$pacgoods['goodsid'],':uniacid'=>$_W['uniacid']));
       $pacgoods['数量']=$packgo['title'];
       $pacgoods['thumb']=$packgo['thumb'];
    
       $pacgoods['value']="{$pacgoods['goodsid']},{$pacgoods['hasoptions']},{$pacgoods['num']},{$pacgoods['spacid']}";
    }
    unset($pacgoods);
}

if(empty($labelname)){
    $labelname = array();
}
foreach($labelname as $key => $value){
    $label[$key]['id'] = $value;
    $label[$key]['labelname'] = $value;
}
/*wsq*/
$resellerlist=pdo_fetchall("select * from ".tablename("ewei_shop_reseller"));
if($item['disgoods_id']>0 && $_W['uniacid']!=DIS_ACCOUNT){
    $depot_data = pdo_fetchall('select * from ' . tablename('ewei_shop_depot') . ' where uniacid=:uniacid and enabled=1 order by id desc', array(':uniacid' => DIS_ACCOUNT));
}else{
    $depot_data = pdo_fetchall('select * from ' . tablename('ewei_shop_depot') . ' where uniacid=:uniacid and enabled=1 order by id desc', array(':uniacid' => $_W['uniacid']));
}

if(!empty($item)){
    

    if($_W['uniacid']!=DIS_ACCOUNT){
        
        $disprice=Dispage::get_disprice($id,$_W['uniacid']);

    }
    if($_W['uniacid']==DIS_ACCOUNT){
          $disprice=pdo_fetch("select * from ".tablename("ewei_shop_goodsresel")." where goods_id=:goodsid",array(":goodsid"=>$id));
         $disprice=unserialize($disprice['disprice']);
    }
}
//税率展示优化
if($item){
    $item['consumption_tax']=$item['consumption_tax']*100;
    $item['vat_rate']=$item['vat_rate']*100;
}
/*wsqend*/
//是否为多商户商品
$merchid = 0;
$merch_plugin = p('merch');
if (!empty($item)) {
    if ($item['merchid'] > 0) {
        $merchid = intval($item['merchid']);

        if ($merch_plugin) {
            $merch_user = $merch_plugin->getListUserOne($merchid);
        }
    }
}

if (p('diyform')) {
    $diyform = p('diyform');

    $globalData = $diyform->globalData();
    extract($globalData);

    if (!empty($item['diysaveid'])) {
        $diyforminfo = $diyform->getDiyformInfo($item['diysaveid'], 0);
    }
}


$ccard_plugin = p('ccard');
$ccard = 0;
if ($ccard_plugin) {
    $ccard = 1;
}

$category = m('shop')->getFullCategory(true,false);

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
$groups = m('member')->getGroups();
//佣金计算
$commission_level = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_commission_level') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY commission1 asc");

foreach($commission_level as &$l){
    $l['key'] ='level'.$l['id'];
}
unset($l);

$commission_level =array_merge(array(
    array(
        'key'=>'default',
        'levelname'=>empty($_W['shopset']['commission']['levelname'])?'默认等级':$_W['shopset']['commission']['levelname']
    )
),$commission_level);

$com_virtual = com('virtual');


if ($_W['ispost']) {

    if (empty($id))  {
        $goodstype = intval($_GPC['type']);
    } else {
        $goodstype = intval($_GPC['goodstype']);
    }

//    if ($diyform) {
//        if ($goodstype == 1 && $_GPC['diyformtype'] == 2) {
//            show_json(0,array('message'=>'替换模式只试用于虚拟物品类型，实体物品无效！请重新选择！'));
//        }
//    }
    $data = array(
        'uniacid' => intval($_W['uniacid']),
        'displayorder' => intval($_GPC['displayorder']),
        'title' => trim($_GPC['goodsname']),
        'subtitle' => trim($_GPC['subtitle']),
        'shorttitle' => trim($_GPC['shorttitle']),
        'keywords' => trim($_GPC['keywords']),
        'thumb_first' => intval($_GPC['thumb_first']),
        'type' => $goodstype,
        'labelname' => json_encode($_GPC['labelname']),
        'isrecommand' => intval($_GPC['isrecommand']),
        'ishot' => intval($_GPC['ishot']),
        'isnew' => intval($_GPC['isnew']),
        'isdiscount' => intval($_GPC['isdiscount']),
        'isdiscount_title' => trim(mb_substr($_GPC['isdiscount_title'],0,5,'UTF-8')),
        'isdiscount_time' =>  strtotime($_GPC['stat_time']['end']),
        'isdiscount_stat_time' =>strtotime($_GPC['stat_time']['start']),
        'issendfree' => intval($_GPC['issendfree']),
        'isnodiscount' => intval($_GPC['isnodiscount']),
        'istime' => intval($_GPC['istime']),
        'timestart' => strtotime($_GPC['saletime']['start']),
        'timeend' => strtotime($_GPC['saletime']['end']),
        'description' => trim($_GPC['description']),
        'goodssn' => trim($_GPC['goodssn']),
        'unit' => trim($_GPC['unit']),
        'createtime' => TIMESTAMP,
        'total' => intval($_GPC['total']),
        'showtotal' => intval($_GPC['showtotal']),
        'totalcnf' => intval($_GPC['totalcnf']),
        'marketprice' => $_GPC['marketprice'],
        'weight' => $_GPC['weight'],
        'costprice' => $_GPC['costprice'],
        'productprice' => trim($_GPC['productprice']),
        'productsn' => trim($_GPC['productsn']),
        'credit' => trim($_GPC['credit']),
        'brief_desc'=>trim($_GPC['brief_desc']),
        'maxbuy' => intval($_GPC['maxbuy']),
        'minbuy' => intval($_GPC['minbuy']),
        'usermaxbuy' => intval($_GPC['usermaxbuy']),
        'hasoption' => intval($_GPC['hasoption']),
        'sales' => intval($_GPC['sales']),
        'share_icon' => trim($_GPC['share_icon']),
        'share_title' => trim($_GPC['share_title']),
        'status' => intval($_GPC['status']),
        'groupstype' => intval($_GPC['groupstype']),//是否支持拼团
        'virtualsend' => intval($_GPC['virtualsend']),
        'virtualsendcontent' => trim($_GPC['virtualsendcontent']),
        'buyshow' => intval($_GPC['buyshow']),
        'showlevels' => is_array($_GPC['showlevels']) ? implode(",", $_GPC['showlevels']) : '',
        'buylevels' => is_array($_GPC['buylevels']) ? implode(",", $_GPC['buylevels']) : '',
        'showgroups' => is_array($_GPC['showgroups']) ? implode(",", $_GPC['showgroups']) : '',
        'buygroups' => is_array($_GPC['buygroups']) ? implode(",", $_GPC['buygroups']) : '',
        'noticeopenid' => trim($_GPC['noticeopenid']),
        'noticetype' => is_array($_GPC['noticetype']) ? implode(",", $_GPC['noticetype']) : '',
        'needfollow' => intval($_GPC['needfollow']),
        'followurl' => trim($_GPC['followurl']),
        'followtip' => trim($_GPC['followtip']),
        'deduct' => $_GPC['deduct'],
        'manydeduct' => $_GPC['manydeduct'],
        'deduct2' => $_GPC['deduct2'],
        'virtual'=>$goodstype==3?intval($_GPC['virtual']):0,
        'tariffnum'=>$_GPC['tariffnum'],
        'originplace'=>$_GPC['originplace'],

//      'discounts' => is_array($_GPC['discounts']) ? json_encode($_GPC['discounts']) : json_encode(array()),
        'ednum' => intval($_GPC['ednum'])
    , 'edareas' => trim($_GPC['edareas'])
    , 'edmoney' => trim($_GPC['edmoney'])
    , 'invoice' => intval($_GPC['invoice'])
    , 'repair' => intval($_GPC['repair'])
    , 'seven' => intval($_GPC['seven'])
    , 'money'=>trim($_GPC['money'])
    , 'province'=>trim($_GPC['province'])
    , 'city'=>trim($_GPC['city'])
    , 'quality'=>intval($_GPC['quality'])
    , 'sharebtn'=>intval($_GPC['sharebtn'])
    , 'autoreceive'=>intval($_GPC['autoreceive'])
    , 'cannotrefund'=>intval($_GPC['cannotrefund'])
    , 'buyagain'=>floatval($_GPC['buyagain'])
    , 'buyagain_islong'=>intval($_GPC['buyagain_islong'])
    , 'buyagain_condition'=>intval($_GPC['buyagain_condition'])
    , 'buyagain_sale'=>intval($_GPC['buyagain_sale'])
    , 'diypage'=>intval($_GPC['diypage'])
    , 'cashier'=>intval($_GPC['cashier'])
    , 'isdis'=>intval($_GPC['isdis'])
    , 'depotid'=>intval($_GPC['depotid'])
    , 'consumption_tax'=>floatval($_GPC['consumption_tax']/100)
    , 'vat_rate'=>floatval($_GPC['vat_rate']/100)

    );
    $buyagain_commission = is_array($_GPC['buyagain_commission']) ? $_GPC['buyagain_commission'] : array();
    if (!empty($buyagain_commission)){
        $buyagain_commission['type'] = 0;
        $data['buyagain_commission'] = json_encode($buyagain_commission);
    }

    if ($merchid == 0) {
        //线下核销
        $data['isverify'] = $_GPC['isverify'];
        $data['verifytype'] = intval($_GPC['verifytype']);
        $data['storeids'] = is_array($_GPC['storeids']) ? implode(',', $_GPC['storeids']) : '';
        if (intval($_GPC['isverify']) == 2 || $goodstype == 2 || $goodstype == 3) {
            $data['cash'] = 0;
        } else {
            $data['cash'] = intval($_GPC['cash']);
        }

        //店铺信息
        $data['detail_logo'] = save_media($_GPC['detail_logo']);
        $data['detail_shopname'] = trim($_GPC['detail_shopname']);
        $data['detail_totaltitle'] = trim($_GPC['detail_totaltitle']);
        $data['detail_btntext1'] = trim($_GPC['detail_btntext1']);
        $data['detail_btnurl1'] = trim($_GPC['detail_btnurl1']);
        $data['detail_btntext2'] = trim($_GPC['detail_btntext2']);
        $data['detail_btnurl2'] = trim($_GPC['detail_btnurl2']);
    } else {
        if (intval($item['isverify']) == 2 || $goodstype == 2 || $goodstype == 3) {
            $data['cash'] = 0;
        } else {
            $data['cash'] = intval($_GPC['cash']);
        }
        $data['merchsale'] = intval($_GPC['merchsale']);
    }

    $cateset = m('common')->getSysset('shop');
    $pcates = array();
    $ccates = array();
    $tcates = array();
    $fcates = array();
    $cates = array();
    $pcateid=0;
    $ccateid = 0;
    $tcateid = 0;
    if (is_array($_GPC['cates'])) {

        $cates = $_GPC['cates'];

        foreach ($cates as $key=>$cid) {

            $c = pdo_fetch('select level from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));

            if($c['level']==1){ //一级
                $pcates[] = $cid;
            } else if($c['level']==2){  //二级
                $ccates[] = $cid;
            } else if($c['level']==3){  //三级
                $tcates[] =$cid;
            }

            if($key==0){
                //兼容 1.x
                if($c['level']==1){ //一级
                    $pcateid = $cid;
                }
                else if($c['level']==2){
                    $crow = pdo_fetch('select parentid from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));
                    $pcateid = $crow['parentid'];
                    $ccateid = $cid;

                }
                else if($c['level']==3){
                    $tcateid = $cid;
                    $tcate = pdo_fetch('select id,parentid from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));
                    $ccateid = $tcate['parentid'];
                    $ccate = pdo_fetch('select id,parentid from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $ccateid, ':uniacid' => $_W['uniacid']));
                    $pcateid = $ccate['parentid'];
                }
            }


        }

    }

    $data['pcate'] = $pcateid;
    $data['ccate'] = $ccateid;
    $data['tcate'] = $tcateid;
    $data['cates'] = implode(',', $cates);

    $data['pcates'] = implode(',', $pcates);
    $data['ccates'] = implode(',', $ccates);
    $data['tcates'] = implode(',', $tcates);

    $data['content'] = m('common')->html_images($_GPC['content']);
    $data['buycontent'] = m('common')->html_images($_GPC['buycontent']);

    //是否开启分销
    if (p('commission')) {
        $cset = p('commission')->getSet();
        if (!empty($cset['level'])) {
            $data['nocommission'] = intval($_GPC['nocommission']);
            $data['hascommission'] = intval($_GPC['hascommission']);
            $data['hidecommission'] = intval($_GPC['hidecommission']);
            $data['commission1_rate'] = $_GPC['commission1_rate'];
            $data['commission2_rate'] = $_GPC['commission2_rate'];
            $data['commission3_rate'] = $_GPC['commission3_rate'];
            $data['commission1_pay'] = $_GPC['commission1_pay'];
            $data['commission2_pay'] = $_GPC['commission2_pay'];
            $data['commission3_pay'] = $_GPC['commission3_pay'];
            $data['commission_thumb'] = save_media($_GPC['commission_thumb']);
        }
    }

    if ($ccard_plugin) {
        if ($goodstype == 20) {
            $data['rdays'] = intval($_GPC['rdays']);
            $data['ccardexplain'] = trim($_GPC['ccardexplain']);
            $data['ccardtimeexplain'] = trim($_GPC['ccardtimeexplain']);
            $data['nocommission'] = 1;
        }
    }

    //如果有自定义表单
    if ($diyform) {
        if ($_GPC['diyformtype'] == 2) {
            $diydata = $diyform->getInsertDataByAdmin();
            $diydata = iserializer($diydata);
            $_GPC['diysave'] = intval($_GPC['diysave']);
            if ($_GPC['diysave'] == 1) {
                $diysaveid = $item['diysaveid'];

                $insert = array();
                $insert['title'] = '商品ID' . $item['id'] . '的自定义表单';
                $insert['fields'] = $diydata;

                $is_save = $diyform->isHasDiyform($diysaveid);

                if (empty($is_save)) {
                    $insert['uniacid'] = $_W['uniacid'];
                    pdo_insert('ewei_shop_diyform_type', $insert);
                    $data['diysaveid'] = pdo_insertid();
                } else {
                    pdo_update('ewei_shop_diyform_type', $insert, array('id' => $diysaveid));
                }
            }
            $data['diyfields'] = $diydata;
            $data['diysave'] = $_GPC['diysave'];
        }

        $data['diyformtype'] = $_GPC['diyformtype'];
        if ($_GPC['diyformtype'] == 1) {
            $data['diyformid'] = $_GPC['diyformid'];
        }
        $data['diymode'] = intval($_GPC['diymode']);
    }

    //运费设置
    $data['dispatchtype'] = intval($_GPC['dispatchtype']);
    $data['dispatchprice'] = trim($_GPC['dispatchprice']);
    $data['dispatchid'] = intval($_GPC['dispatchid']);

    if ($data['total'] === -1) {
        $data['total'] = 0;
        $data['totalcnf'] = 2;
    }

    if (is_array($_GPC['thumbs'])) {
        $thumbs = $_GPC['thumbs'];
        $thumb_url = array();
        foreach ($thumbs as $th) {
            $thumb_url[] = trim($th);
        }

        //兼容1.x
        $data['thumb'] = save_media($thumb_url[0]);
        unset($thumb_url[0]);

        $data['thumb_url'] = serialize(m('common')->array_images($thumb_url));



    }

    if (empty($id)) {
        $data['merchid'] = 0;
        pdo_insert('ewei_shop_goods', $data);
        $id = pdo_insertid();
        plog('goods.add', "添加商品 ID: {$id}<br>".(!empty($data['nocommission']) ? "是否参与分销 -- 否" : "是否参与分销 -- 是"));
    } else {
        if($item['disgoods_id']>0){
            unset($data['isdis']);
            unset($data['depotid']);
            unset($data['consumption_tax']);
            unset($data['vat_rate']);
        }
        unset($data['createtime']);
        pdo_update('ewei_shop_goods', $data, array('id' => $id));
        plog('goods.edit', "编辑商品 ID: {$id}<br>".(!empty($data['nocommission']) ? "是否参与分销 -- 否" : "是否参与分销 -- 是"));
    }
    //处理组合商品
    if($data['type']==4){
        $gpcpackgoods=$_GPC['ssspackagegoods'];
       
        foreach ($gpcpackgoods as $key => $value) {
             $goodstmp=explode(",", $value);
             $goods[]=array(
                    'goodsid'=>$goodstmp[0],
                    'hasoptions'=>$goodstmp[1],
                    'num'=>$goodstmp[2],
                    'spacid'=>$goodstmp[3]);
             $tempgoodsid=$goodstmp[0];// 获取一个临时的ID
        }
        $packsdata['goodsid']=$id;
        $packsdata['packgoods']=json_encode($goods);
        $packsdata['uniacid']=$_W['uniacid'];

        if(empty($packgoods)){
            pdo_insert("ewei_shop_goodspacks",$packsdata);
        }else{
            pdo_update("ewei_shop_goodspacks",$packsdata,array("id"=>$packgoods['id'],"uniacid"=>$_W['uniacid']));
        }
       $tedepotid=pdo_fetchcolumn("SELECT depotid from ".tablename("ewei_shop_goods")." where id=:tempid",array(":tempid"=>$tempgoodsid));
         pdo_update("ewei_shop_goods", array("depotid" => $tedepotid), array("id" => $id));//更新商品所在仓库
    }
    //处理商品参数
    $param_ids = $_POST['param_id'];
    $param_titles = $_POST['param_title'];
    $param_values = $_POST['param_value'];
    $param_displayorders = $_POST['param_displayorder'];
    $len = count($param_ids);
    $paramids = array();
    for ($k = 0; $k < $len; $k++) {
        $param_id = "";
        $get_param_id = $param_ids[$k];
        $a = array(
            "uniacid"=>$_W['uniacid'],
            "title" => $param_titles[$k],
            "value" => $param_values[$k],
            "displayorder" => $k,
            "goodsid" => $id,
        );
        if (!is_numeric($get_param_id)) {
            pdo_insert("ewei_shop_goods_param", $a);
            $param_id = pdo_insertid();
        } else {
            pdo_update("ewei_shop_goods_param", $a, array('id' => $get_param_id));
            $param_id = $get_param_id;
        }
        $paramids[] = $param_id;
    }

    if (count($paramids) > 0) {
        pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=$id and id not in ( " . implode(',', $paramids) . ")");
    } else {
        pdo_query("delete from " . tablename('ewei_shop_goods_param') . " where goodsid=$id");
    }

    $totalstocks = 0;
    //处理商品规格
    $files = $_FILES;
    $spec_ids = $_POST['spec_id'];
    $spec_titles = $_POST['spec_title'];
    $specids = array();
    $len = count($spec_ids);
    $specids = array();
    $spec_items = array();
    for ($k = 0; $k < $len; $k++) {
        $spec_id = "";
        $get_spec_id = $spec_ids[$k];
        $a = array(
            "uniacid" => $_W['uniacid'],
            "goodsid" => $id,
            "displayorder" => $k,
            "title" => $spec_titles[$get_spec_id]
        );
        if (is_numeric($get_spec_id)) {
            pdo_update("ewei_shop_goods_spec", $a, array("id" => $get_spec_id));
            $spec_id = $get_spec_id;
        } else {
            pdo_insert("ewei_shop_goods_spec", $a);
            $spec_id = pdo_insertid();
        }
        //子项
        $spec_item_ids = $_POST["spec_item_id_" . $get_spec_id];
        $spec_item_titles = $_POST["spec_item_title_" . $get_spec_id];
        $spec_item_shows = $_POST["spec_item_show_" . $get_spec_id];
        $spec_item_thumbs = $_POST["spec_item_thumb_" . $get_spec_id];
        $spec_item_oldthumbs = $_POST["spec_item_oldthumb_" . $get_spec_id];
        $spec_item_virtuals = $_POST["spec_item_virtual_" . $get_spec_id];

        $itemlen = count($spec_item_ids);
        $itemids = array();
        for ($n = 0; $n < $itemlen; $n++) {
            $item_id = "";
            $get_item_id = $spec_item_ids[$n];
            $d = array(
                "uniacid" => $_W['uniacid'],
                "specid" => $spec_id,
                "displayorder" => $n,
                "title" => $spec_item_titles[$n],
                "show" => $spec_item_shows[$n],
                "thumb" => save_media($spec_item_thumbs[$n]),
                "virtual" => $data['type'] == 3 ? $spec_item_virtuals[$n] : 0
            );
            $f = "spec_item_thumb_" . $get_item_id;
            if (is_numeric($get_item_id)) {
                pdo_update("ewei_shop_goods_spec_item", $d, array("id" => $get_item_id));
                $item_id = $get_item_id;
            } else {
                pdo_insert("ewei_shop_goods_spec_item", $d);
                $item_id = pdo_insertid();
            }
            $itemids[] = $item_id;
            //临时记录，用于保存规格项
            $d['get_id'] = $get_item_id;
            $d['id'] = $item_id;
            $spec_items[] = $d;
        }
        //删除其他的
        if (count($itemids) > 0) {
            pdo_query("delete from " . tablename('ewei_shop_goods_spec_item') . " where uniacid={$_W['uniacid']} and specid=$spec_id and id not in (" . implode(",", $itemids) . ")");
        } else {
            pdo_query("delete from " . tablename('ewei_shop_goods_spec_item') . " where uniacid={$_W['uniacid']} and specid=$spec_id");
        }
        //更新规格项id
        pdo_update("ewei_shop_goods_spec", array("content" => serialize($itemids)), array("id" => $spec_id));
        $specids[] = $spec_id;
    }
    //删除其他的
    if (count($specids) > 0) {
        pdo_query("delete from " . tablename('ewei_shop_goods_spec') . " where uniacid={$_W['uniacid']} and goodsid=$id and id not in (" . implode(",", $specids) . ")");
    } else {
        pdo_query("delete from " . tablename('ewei_shop_goods_spec') . " where uniacid={$_W['uniacid']} and goodsid=$id");
    }
    //保存规格
    $optionArray = json_decode($_POST['optionArray'],true);
    $isdiscountDiscountsArray = json_decode($_POST['isdiscountDiscountsArray'],true);
    $discountArray = json_decode($_POST['discountArray'],true);
    $dispriceArray = json_decode($_POST['dispriceArray'],true);
    $commissionArrayPost = json_decode($_POST['commissionArray'],true);
    $option_idss = $optionArray['option_ids'];
    $len = count($option_idss);
    $optionids = array();
    $levelArray = array();
    $dispricearr=array();
    $isDiscountsArray = array();
    $commissionArray = array();

    $commissionDefaultArray = array();
    for ($k = 0; $k < $len; $k++) {
        $option_id = "";
        $ids = $option_idss[$k];
        $get_option_id = $optionArray['option_id'][$k];

        $idsarr = explode("_", $ids);
        $newids = array();
        foreach ($idsarr as $key => $ida) {
            foreach ($spec_items as $it) {
                if ($it['get_id'] == $ida) {
                    $newids[] = $it['id'];
                    break;
                }
            }
        }

        $newids = implode("_", $newids);
        $a = array(
            "uniacid" => $_W['uniacid'],
            "title" => $optionArray['option_title'][$k],
            "productprice" => $optionArray['option_productprice'][$k],
            "costprice" => $optionArray['option_costprice'][$k],
            "marketprice" => $optionArray['option_marketprice'][$k],
            "stock" => $optionArray['option_stock'][$k],
            "weight" => $optionArray['option_weight'][$k],
            "goodssn" => $optionArray['option_goodssn'][$k],
            "productsn" => $optionArray['option_productsn'][$k],
            "goodsid" => $id,
            "specs" => $newids,
            'virtual' => $data['type'] == 3 ? $optionArray['option_virtual'][$k] : 0,
        );

        $totalstocks+=$a['stock'];
        if (empty($get_option_id)) {
            pdo_insert("ewei_shop_goods_option", $a);
            $option_id = pdo_insertid();
        } else {
            pdo_update("ewei_shop_goods_option", $a, array('id' => $get_option_id));
            $option_id = $get_option_id;
        }
        $optionids[] = $option_id;

        foreach ($levels as $level) {
            $levelArray[$level['key']]['option'.$option_id] = $discountArray['discount_' . $level['key']][$k];
            $isDiscountsArray[$level['key']]['option'.$option_id] = $isdiscountDiscountsArray['isdiscount_discounts_'.$level['key']][$k];
        }
        foreach ($commission_level as $level) {
            if($level['key']=='default')
            {
                $commissionArray[$level['key']]['option'.$option_id] = $commissionArrayPost['commission']['commission_level_' . $level['key'] . "_" . $ids];
            }
            else
            {
                $commissionArray[$level['key']]['option'.$option_id] = $commissionArrayPost['commission']['commission_level_' . $level['id'] . "_" . $ids];
            }

        }
        foreach ($resellerlist as  $res) {
            $dispricearr['level'.$res['id']]['option'.$option_id]=$dispriceArray['disprice_'.$res['id']][$k];
        }
    }
    //处理代理价
    /*wsq 更改*/
    if($_W['uniacid']==DIS_ACCOUNT){

        $discounts_arr=array();
        $dispricearr_json=array();
        if(!empty($dispricearr)){

            $discounts_arr = array_merge($discounts_arr,$dispricearr);
            $dispricearr_json = json_encode($discounts_arr);

        }
        Dispage::disPrice($id,$_GPC['disprice'],$data['hasoption'],$dispricearr_json); 
            //处理代理商品的税率 和其他的一些数据
        Dispage::delDisGoods($id,$data,$_W['uniacid']);
        //wsqend
    }
    //更新discounts
    if ((int)$_GPC['discounts']['type'] == 1 && $data['hasoption'])
    {
        $discounts_arr = array(
            'type' => (int)$_GPC['discounts']['type'],
        );

        $discounts_arr = array_merge($discounts_arr,$levelArray);
        $discounts_json = json_encode($discounts_arr);
    }
    else
    {
        $discounts_json = is_array($_GPC['discounts']) ? json_encode($_GPC['discounts']) : json_encode(array());
    }
    pdo_update('ewei_shop_goods', array('discounts'=>$discounts_json), array('id' => $id));

    //更新isdiscount_discounts
    $has_merch = 0;
    $old_isdiscount_discounts = json_decode($item['isdiscount_discounts'], true);
    if (!empty($old_isdiscount_discounts['merch'])) {
        $has_merch = 1;
    }

    if (!empty($isDiscountsArray) && $data['hasoption'])
    {
        $is_discounts_arr = array_merge(array('type' => 1),$isDiscountsArray);
        if ($has_merch == 1) {
            $is_discounts_arr['merch'] = $old_isdiscount_discounts['merch'];
        }
        $is_discounts_json = json_encode($is_discounts_arr);
    }
    else
    {
        foreach ($levels as $level)
        {
            if ($level['key']=='default')
            {
                $isDiscountsDefaultArray[$level['key']]['option0'] = $_GPC['isdiscount_discounts_level_' . $level['key'] . "_default"];
            }
            else
            {
                $isDiscountsDefaultArray[$level['key']]['option0'] = $_GPC['isdiscount_discounts_level_' . $level['id'] . "_default"];
            }
        }
        $is_discounts_arr = array_merge(array('type' => 0),$isDiscountsDefaultArray);
        if ($has_merch == 1) {
            $is_discounts_arr['merch'] = $old_isdiscount_discounts['merch'];
        }
        $is_discounts_json = is_array($is_discounts_arr) ? json_encode($is_discounts_arr) : json_encode(array());
    }
    pdo_update('ewei_shop_goods', array('isdiscount_discounts'=>$is_discounts_json), array('id' => $id));

    //更新commission
    if(!empty($commissionArray) && $data['hasoption'])
    {
        $commissionArray = array_merge(array('type'=>(int)$_GPC['commission_type']),$commissionArray);
        $commission_arr = array(
            'commission' => is_array($commissionArray) ? json_encode($commissionArray) : json_encode(array())
        );
    }
    else
    {
        foreach ($commission_level as $level) {
            if ($level['key']=='default')
            {
                if ( !empty($_GPC['commission_level_' . $level['key'] . "_default"]) )
                {
                    foreach ($_GPC['commission_level_' . $level['key'] . "_default"] as $key=>$value)
                    {
                        $commissionDefaultArray[$level['key']]['option0'][] = $value;
                    }
                }
            }
            else
            {
                if ( !empty($_GPC['commission_level_' . $level['id'] . "_default"]) )
                {
                    foreach ($_GPC['commission_level_' . $level['id'] . "_default"] as $key=>$value)
                    {
                        $commissionDefaultArray[$level['key']]['option0'][] = $value;
                    }
                }
            }
        }
        $commissionDefaultArray = array_merge(array('type'=>(int)$_GPC['commission_type']),$commissionDefaultArray);
        $commission_arr = array(
            'commission' => is_array($commissionDefaultArray) ? json_encode($commissionDefaultArray) : json_encode(array())
        );
    }
    pdo_update('ewei_shop_goods', $commission_arr, array('id' => $id));

    if (count($optionids) > 0 && $data['hasoption'] !== 0) {
        pdo_query("delete from " . tablename('ewei_shop_goods_option') . " where goodsid=$id and id not in ( " . implode(',', $optionids) . ")");

        //更新最低价和最高价
        $sql = "update ".tablename('ewei_shop_goods')." g set
            g.minprice = (select min(marketprice) from ".tablename('ewei_shop_goods_option')." where goodsid = $id),
            g.maxprice = (select max(marketprice) from ".tablename('ewei_shop_goods_option')." where goodsid = $id)
            where g.id = $id and g.hasoption=1";

        pdo_query($sql);
    } else {
        pdo_query("delete from " . tablename('ewei_shop_goods_option') . " where goodsid=$id");
        $sql = "update ".tablename('ewei_shop_goods')." set minprice = marketprice,maxprice = marketprice where id = $id and hasoption=0;";
        pdo_query($sql);
    }

    //如果是有促销,那么更新最大最小价格
    $sqlgoods = "SELECT id,title,thumb,marketprice,productprice,minprice,maxprice,isdiscount,isdiscount_time,isdiscount_discounts,sales,total,description,merchsale FROM " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1";
    $goodsinfo = pdo_fetch($sqlgoods,array(':id'=>$id,':uniacid'=>$_W['uniacid']));
    $goodsinfo = m('goods')->getOneMinPrice($goodsinfo);

    pdo_update('ewei_shop_goods',array('minprice'=>$goodsinfo['minprice'],'maxprice'=>$goodsinfo['maxprice']),array('id'=>$id,'uniacid'=>$_W['uniacid']));

    //总库存
    if ($data['type'] == 3 && $com_virtual) {
        $com_virtual->updateGoodsStock($id);
    } else {
        if ($data['hasoption'] !== 0 && ($data['totalcnf'] != 2)) {
            pdo_update("ewei_shop_goods", array("total" => $totalstocks), array("id" => $id));
        }
    }
    show_json(1,array('url'=>webUrl('goods/edit', array('id' => $id,'tab'=>str_replace("#tab_","",$_GPC['tab'])))));
}

if (!empty($id)) {

    if (empty($item)) {
        $this->message('抱歉，商品不存在或是已经删除！', '', 'error');
    }


    $noticetype = explode(',', $item['noticetype']);

    $cates = explode(',', $item['cates']);


    //佣金设置
    $commission = json_decode($item['commission'], true);
    if (isset($commission['type']))
    {
        $commission_type = $commission['type'];
        unset($commission['type']);
    }


    $buyagain_commission = array();
    if (!empty($item['buyagain_commission'])){
        $buyagain_commission = json_decode($item['buyagain_commission'], true);
    }
    //会员折扣
    $discounts = json_decode($item['discounts'], true);

    $isdiscount_discounts = json_decode($item['isdiscount_discounts'], true);
    if($_W['uniacid']==DIS_ACCOUNT){
        $reseldata=pdo_fetch("select * from ".tablename("ewei_shop_goodsresel")." where goods_id=:goodsid",array(":goodsid"=>$id));
        $resellerldatas=json_decode($reseldata['disprice'],true);
    }
    $allspecs = pdo_fetchall("select * from " . tablename('ewei_shop_goods_spec') . " where goodsid=:id order by displayorder asc", array(":id" => $id));
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("select a.id,a.specid,a.title,a.thumb,a.show,a.displayorder,a.valueId,a.virtual,b.title as title2 from " . tablename('ewei_shop_goods_spec_item') . " a left join " . tablename('ewei_shop_virtual_type') . " b on b.id=a.virtual  where a.specid=:specid order by a.displayorder asc", array(":specid" => $s['id']));
    }
    unset($s);

    //商品参数
    $params = pdo_fetchall("select * from " . tablename('ewei_shop_goods_param') . " where goodsid=:id order by displayorder asc", array(':id' => $id));

    //商品图片

    if (!empty($item['thumb'])) {
        $piclist =array_merge( array($item['thumb']), iunserializer($item['thumb_url']) );
    }

    $item['content'] = m('common')->html_to_images($item['content']);



    //处理规格项
    $html = "";
    $discounts_html='';
    $commission_html='';
    $isdiscount_discounts_html='';
    $resellerlisthtml="";
    $options = pdo_fetchall("select * from " . tablename('ewei_shop_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
    //排序好的specs
    $specs = array();
    //找出数据库存储的排列顺序
    if (count($options) > 0) {
        $specitemids = explode("_", $options[0]['specs']);
        foreach ($specitemids as $itemid) {
            foreach ($allspecs as $ss) {
                $items = $ss['items'];
                foreach ($items as $it) {
                    if ($it['id'] == $itemid) {
                        $specs[] = $ss;
                        break;
                    }
                }
            }
        }
        $html = '';
        $html .= '<table class="table table-bordered table-condensed">';
        $html .= '<thead>';
        $html .= '<tr class="active">';
        $discounts_html .= '<table class="table table-bordered table-condensed">';
        $discounts_html .= '<thead>';
        $discounts_html .= '<tr class="active">';
        $resellerlisthtml .= '<table class="table table-bordered table-condensed">';
        $resellerlisthtml .= '<thead>';
        $resellerlisthtml .= '<tr class="active">';
        $commission_html .= '<table class="table table-bordered table-condensed">';
        $commission_html .= '<thead>';
        $commission_html .= '<tr class="active">';
        $isdiscount_discounts_html .= '<table class="table table-bordered table-condensed">';
        $isdiscount_discounts_html .= '<thead>';
        $isdiscount_discounts_html .= '<tr class="active">';
        $len = count($specs);
        $newlen = 1; //多少种组合
        $h = array(); //显示表格二维数组
        $rowspans = array(); //每个列的rowspan
        for ($i = 0; $i < $len; $i++) {
            //表头
            $html .= "<th>" . $specs[$i]['title'] . "</th>";
            $discounts_html .= "<th>" . $specs[$i]['title'] . "</th>";
            $resellerlisthtml .="<th>" . $specs[$i]['title'] . "</th>";
            $commission_html .= "<th>" . $specs[$i]['title'] . "</th>";
            $isdiscount_discounts_html .= "<th>" . $specs[$i]['title'] . "</th>";
            //计算多种组合
            $itemlen = count($specs[$i]['items']);
            if ($itemlen <= 0) {
                $itemlen = 1;
            }
            $newlen *= $itemlen;
            //初始化 二维数组
            $h = array();
            for ($j = 0; $j < $newlen; $j++) {
                $h[$i][$j] = array();
            }
            //计算rowspan
            $l = count($specs[$i]['items']);
            $rowspans[$i] = 1;
            for ($j = $i + 1; $j < $len; $j++) {
                $rowspans[$i]*= count($specs[$j]['items']);
            }
        }
        $canedit = ce('goods',$item);

        if($canedit){

            foreach ($levels as $level) {
                $discounts_html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$level['levelname'].'</div><div class="input-group"><input type="text" class="form-control  input-sm discount_'.$level['key'].'_all" VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'discount_'.$level['key'].'\');"></a></span></div></div></th>';
                $isdiscount_discounts_html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$level['levelname'].'</div><div class="input-group"><input type="text" class="form-control  input-sm isdiscount_discounts_'.$level['key'].'_all" VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'isdiscount_discounts_'.$level['key'].'\');"></a></span></div></div></th>';
            }
            foreach($resellerlist as $le){
                $resellerlisthtml.='<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$le['name'].'</div><div class="input-group"><input type="text" class="form-control  input-sm disprice_'.$le['id'].'_all" VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'disprice_'.$le['id'].'\');"></a></span></div></div></th>';
            }

            foreach ($commission_level as $level) {
                $commission_html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$level['levelname'].'</div></div></th>';
            }

            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">库存</div><div class="input-group"><input type="text" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">现价</div><div class="input-group"><input type="text" class="form-control  input-sm option_marketprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_marketprice\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">原价</div><div class="input-group"><input type="text" class="form-control input-sm option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">成本价</div><div class="input-group"><input type="text" class="form-control input-sm option_costprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_costprice\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">编码</div><div class="input-group"><input type="text" class="form-control input-sm option_goodssn_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_goodssn\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">条码</div><div class="input-group"><input type="text" class="form-control input-sm option_productsn_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productsn\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">重量（克）</div><div class="input-group"><input type="text" class="form-control input-sm option_weight_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_weight\');"></a></span></div></div></th>';
        }
        else {

            foreach ($levels as $level) {
                $discounts_html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$level['levelname'].'</div></div></th>';
                $isdiscount_discounts_html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$level['levelname'].'</div></div></th>';
            }
            foreach($resellerlist as $le){
                $resellerlisthtml.='<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$le['name'].'</div></div></th>';
            }
            foreach ($commission_level as $level) {
                $commission_html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">'.$level['levelname'].'</div></div></th>';
            }

            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">库存</div></div></th>';
            $html .= '<th"><div class=""><div style="padding-bottom:10px;text-align:center;">销售价格</div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">市场价格</div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">成本价格</div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">商品编码</div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">商品条码</div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">重量（克）</div></th>';
        }
        $html .= '</tr></thead>';
        $discounts_html .= '</tr></thead>';
        $resellerlisthtml.='</tr></thead>';
        $isdiscount_discounts_html .= '</tr></thead>';
        $commission_html .= '</tr></thead>';

        for ($m = 0; $m < $len; $m++) {
            $k = 0;
            $kid = 0;
            $n = 0;
            for ($j = 0; $j < $newlen; $j++) {
                $rowspan = $rowspans[$m];
                if ($j % $rowspan == 0) {
                    $h[$m][$j] = array("html" => "<td class='full' rowspan='" . $rowspan . "'>" . $specs[$m]['items'][$kid]['title'] . "</td>", "id" => $specs[$m]['items'][$kid]['id']);
                } else {
                    $h[$m][$j] = array("html" => "", "id" => $specs[$m]['items'][$kid]['id']);
                }
                $n++;
                if ($n == $rowspan) {
                    $kid++;
                    if ($kid > count($specs[$m]['items']) - 1) {
                        $kid = 0;
                    }
                    $n = 0;
                }
            }
        }
        $hh = "";
        $dd = "";
        $disre = "";
        $isdd = "";
        $cc = "";
        for ($i = 0; $i < $newlen; $i++) {
            $hh.="<tr>";
            $dd.="<tr>";
            $disre.="<tr>";
            $isdd.="<tr>";
            $cc.="<tr>";
            $ids = array();
            for ($j = 0; $j < $len; $j++) {
                $hh.=$h[$j][$i]['html'];
                $dd.=$h[$j][$i]['html'];
                $isdd.=$h[$j][$i]['html'];
                $disre.=$h[$j][$i]['html'];
                $cc.=$h[$j][$i]['html'];
                $ids[] = $h[$j][$i]['id'];
            }
            $ids = implode("_", $ids);
            $val = array("id" => "", "title" => "", "stock" => "", "costprice" => "", "productprice" => "", "marketprice" => "", "weight" => "",'virtual'=>'');
            $discounts_val = array("id" => "", "title" => "", "level" => "", "costprice" => "", "productprice" => "", "marketprice" => "", "weight" => "",'virtual'=>'');
            $disprice_val = array("id" => "", "title" => "", "level" => "", "costprice" => "", "productprice" => "", "marketprice" => "", "weight" => "",'virtual'=>'');
            $isdiscounts_val = array("id" => "", "title" => "", "level" => "", "costprice" => "", "productprice" => "", "marketprice" => "", "weight" => "",'virtual'=>'');
            $commission_val = array("id" => "", "title" => "", "level" => "", "costprice" => "", "productprice" => "", "marketprice" => "", "weight" => "",'virtual'=>'');
            foreach ($levels as $level) {
                $discounts_val[$level['key']] = '';
                $isdiscounts_val[$level['key']] = '';
            }

            foreach ($commission_level as $level) {
                $commission_val[$level['key']] = '';
            }

            foreach ($options as $o) {
                if ($ids === $o['specs']) {
                    $val = array(
                        "id" => $o['id'],
                        "title" => $o['title'],
                        "stock" => $o['stock'],
                        "costprice" => $o['costprice'],
                        "productprice" => $o['productprice'],
                        "marketprice" => $o['marketprice'],
                        "goodssn"=>$o['goodssn'],
                        "productsn"=>$o['productsn'],
                        "weight" => $o['weight'],
                        'virtual'=>$o['virtual']
                    );
                    $discount_val = array(
                        "id" => $o['id'],
                    );
                    foreach ($levels as $level) {
                        $discounts_val[$level['key']] = is_string($discounts[$level['key']]) ? '' : $discounts[$level['key']]['option'.$o['id']];
                        $isdiscounts_val[$level['key']] = is_string($isdiscount_discounts[$level['key']]) ? '' : $isdiscount_discounts[$level['key']]['option'.$o['id']];
                    }
                    foreach ($resellerlist as $v) {
                        $disprice_val['level'.$v['id']]=is_string($resellerldatas['level'.$v['id']]) ? "" :$resellerldatas['level'.$v['id']]['option'.$o['id']];
                    }
                    $commission_val = array();
                    foreach ($commission_level as $level) {
                        $temp = is_string($commission[$level['key']]) ? '' : $commission[$level['key']]['option'.$o['id']];
                        if (is_array($temp))
                        {
                            foreach ($temp as $t_val)
                            {
                                $commission_val[$level['key']][] = $t_val;
                            }
                        }
                    }
                    unset($temp);
                    break;
                }
            }
            if($canedit){
                foreach ($levels as $level) {
                    $dd .= '<td>';
                    $isdd .= '<td>';
                    if($level['key']=='default')
                    {
                        $dd .= '<input data-name="discount_level_'.$level['key'].'_' . $ids . '"  type="text" class="form-control discount_'.$level['key'].' discount_'.$level['key'].'_' . $ids . '" value="' . $discounts_val[$level['key']] . '"/> ';
                        $isdd .= '<input data-name="isdiscount_discounts_level_'.$level['key'].'_' . $ids . '"  type="text" class="form-control isdiscount_discounts_'.$level['key'].' isdiscount_discounts_'.$level['key'].'_' . $ids . '" value="' . $isdiscounts_val[$level['key']] . '"/> ';
                    }
                    else
                    {
                        $dd .= '<input data-name="discount_level_'.$level['id'].'_' . $ids . '"  type="text" class="form-control discount_level'.$level['id'].' discount_level'.$level['id'].'_' . $ids . '" value="' . $discounts_val['level'.$level['id']] . '"/> ';
                        $isdd .= '<input data-name="isdiscount_discounts_level_'.$level['id'].'_' . $ids . '"  type="text" class="form-control isdiscount_discounts_level'.$level['id'].' isdiscount_discounts_level'.$level['id'].'_' . $ids . '" value="' . $isdiscounts_val['level'.$level['id']] . '"/> ';
                    }
                    $dd .= '</td>';
                    $isdd .= '</td>';
                }
                foreach($resellerlist as $le){
                    $disre.='<td>';
                    $disre .= '<input data-name="disprice_level_'.$le['id'].'_' . $ids . '"  type="text" class="form-control disprice_'.$le['id'].' disprice_'.$level['id'].'_' . $ids . '" value="' . $disprice_val['level'.$le['id']] . '"/> ';
                    $disre.='</td>';
                }
                //var_dump($disprice_val);
               // var_dump($disre);
                $dd .= '<input data-name="discount_id_' . $ids . '"  type="hidden" class="form-control discount_id discount_id_' . $ids . '" value="' . $discounts_val['id'] . '"/>';
                $dd .= '<input data-name="discount_ids"  type="hidden" class="form-control discount_ids discount_ids_' . $ids . '" value="' . $ids . '"/>';
                $dd .= '<input data-name="discount_title_' . $ids . '"  type="hidden" class="form-control discount_title discount_title_' . $ids . '" value="' . $discounts_val['title'] . '"/>';
                $dd .= '<input data-name="discount_virtual_' . $ids . '"  type="hidden" class="form-control discount_title discount_virtual_' . $ids . '" value="' . $discounts_val['virtual'] . '"/>';
                $dd .= "</tr>";

                $disre .= '<input data-name="discount_id_' . $ids . '"  type="hidden" class="form-control disprice_id discount_id_' . $ids . '" value="' . $disprice_val['id'] . '"/>';
                $disre .= '<input data-name="disprice_ids"  type="hidden" class="form-control disprice_ids disprice_ids_' . $ids . '" value="' . $ids . '"/>';
                $disre .= '<input data-name="disprice_title_' . $ids . '"  type="hidden" class="form-control disprice_title disprice_title_' . $ids . '" value="' . $disprice_val['title'] . '"/>';
                $disre .= '<input data-name="disprice_virtual_' . $ids . '"  type="hidden" class="form-control disprice_title disprice_virtual_' . $ids . '" value="' . $disprice_val['virtual'] . '"/>';
                $disre .= "</tr>";

                $isdd .= '<input data-name="isdiscount_discounts_id_' . $ids . '"  type="hidden" class="form-control isdiscount_discounts_id isdiscount_discounts_id_' . $ids . '" value="' . $isdiscounts_val['id'] . '"/>';
                $isdd .= '<input data-name="isdiscount_discounts_ids"  type="hidden" class="form-control isdiscount_discounts_ids isdiscount_discounts_ids_' . $ids . '" value="' . $ids . '"/>';
                $isdd .= '<input data-name="isdiscount_discounts_title_' . $ids . '"  type="hidden" class="form-control isdiscount_discounts_title isdiscount_discounts_title_' . $ids . '" value="' . $isdiscounts_val['title'] . '"/>';
                $isdd .= '<input data-name="isdiscount_discounts_virtual_' . $ids . '"  type="hidden" class="form-control isdiscount_discounts_title isdiscount_discounts_virtual_' . $ids . '" value="' . $isdiscounts_val['virtual'] . '"/>';
                $isdd .= "</tr>";


                foreach ($commission_level as $level) {
                    $cc .= '<td>';
//						dump($commission);
                    if(!empty($commission_val) && isset($commission_val[$level['key']]))
                    {
                        foreach ($commission_val as $c_key=>$c_val)
                        {
                            if($c_key == $level['key'])
                            {
                                if($level['key']=='default')
                                {
                                    for ($c_i = 0;$c_i < $shopset_level;$c_i ++)
                                    {
                                        $cc .= '<input data-name="commission_level_'.$level['key'].'_' . $ids . '"  type="text" class="form-control commission_'.$level['key'].' commission_'.$level['key'].'_' . $ids . '" value="' . $c_val[$c_i] . '" style="display:inline;width: '.(96/$shopset_level).'%;"/> ';
                                    }
                                }
                                else
                                {
                                    for ($c_i = 0;$c_i < $shopset_level;$c_i ++)
                                    {
                                        $cc .= '<input data-name="commission_level_'.$level['id'].'_' . $ids . '"  type="text" class="form-control commission_level'.$level['id'].' commission_level'.$level['id'].'_' . $ids . '" value="' . $c_val[$c_i] . '" style="display:inline;width: '.(96/$shopset_level).'%;"/> ';
                                    }
                                }

                            }
                        }
                    }
                    else
                    {
                        if($level['key']=='default')
                        {
                            for ($c_i = 0; $c_i < $shopset_level; $c_i++) {
                                $cc .= '<input data-name="commission_level_' . $level['key'] . '_' . $ids . '"  type="text" class="form-control commission_' . $level['key'] . ' commission_' . $level['key'] . '_' . $ids . '" value="" style="display:inline;width: ' . (96 / $shopset_level) . '%;"/> ';
                            }
                        }
                        else
                        {
                            for ($c_i = 0; $c_i < $shopset_level; $c_i++) {
                                $cc .= '<input data-name="commission_level_' . $level['id'] . '_' . $ids . '"  type="text" class="form-control commission_level' . $level['id'] . ' commission_level' . $level['id'] . '_' . $ids . '" value="" style="display:inline;width: ' . (96 / $shopset_level) . '%;"/> ';
                            }
                        }
                    }

                    $cc .= '</td>';
                }
                $cc .= '<input data-name="commission_id_' . $ids . '"  type="hidden" class="form-control commission_id commission_id_' . $ids . '" value="' . $commissions_val['id'] . '"/>';
                $cc .= '<input data-name="commission_ids"  type="hidden" class="form-control commission_ids commission_ids_' . $ids . '" value="' . $ids . '"/>';
                $cc .= '<input data-name="commission_title_' . $ids . '"  type="hidden" class="form-control commission_title commission_title_' . $ids . '" value="' . $commissions_val['title'] . '"/>';
                $cc .= '<input data-name="commission_virtual_' . $ids . '"  type="hidden" class="form-control commission_title commission_virtual_' . $ids . '" value="' . $commissions_val['virtual'] . '"/>';
                $cc .= "</tr>";
                $hh .= '<td>';
                $hh .= '<input data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/>';
                $hh .= '</td>';
                $hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';
                $hh .= '<input data-name="option_virtual_' . $ids . '"  type="hidden" class="form-control option_virtual option_virtual_' . $ids . '" value="' . $val['virtual'] . '"/>';
                $hh .= '<td><input data-name="option_marketprice_' . $ids . '" type="text" class="form-control option_marketprice option_marketprice_' . $ids . '" value="' . $val['marketprice'] . '"/></td>';
                $hh .= '<td><input data-name="option_productprice_' . $ids . '" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['productprice'] . '"/></td>';
                $hh .= '<td><input data-name="option_costprice_' . $ids . '" type="text" class="form-control option_costprice option_costprice_' . $ids . '" " value="' . $val['costprice'] . '"/></td>';
                $hh .= '<td><input data-name="option_goodssn_' . $ids . '" type="text" class="form-control option_goodssn option_goodssn_' . $ids . '" " value="' . $val['goodssn'] . '"/></td>';
                $hh .= '<td><input data-name="option_productsn_' . $ids . '" type="text" class="form-control option_productsn option_productsn_' . $ids . '" " value="' . $val['productsn'] . '"/></td>';
                $hh .= '<td><input data-name="option_weight_' . $ids . '" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['weight'] . '"/></td>';
                $hh .= '</tr>';
            } else{
                $hh .= '<td>' . $val['stock'] . '</td>';
                $hh .= '<td>' . $val['marketprice'] . '</td>';
                $hh .= '<td>' . $val['productprice'] . '</td>';
                $hh .= '<td>' . $val['costprice'] . '</td>';
                $hh .= '<td>' . $val['goodssn'] . '</td>';
                $hh .= '<td>' . $val['productsn'] . '</td>';
                $hh .= '<td>' . $val['weight'] . '</td>';
                $hh .= '</tr>';
            }
        }

        $discounts_html .= $dd;
        $discounts_html .= "</table>";
        $resellerlisthtml .=$disre;
        $resellerlisthtml .="</table>";
        $isdiscount_discounts_html .= $isdd;
        $isdiscount_discounts_html .= "</table>";
        $html .= $hh;
        $html .= "</table>";
        $commission_html .= $cc;
        $commission_html .= "</table>";
    }


    //会员权限
    if ($item['showlevels'] != '') {
        $item['showlevels'] = explode(',', $item['showlevels']);
    }
    if ($item['buylevels'] != '') {
        $item['buylevels'] = explode(',', $item['buylevels']);
    }
    if ($item['showgroups'] != '') {
        $item['showgroups'] = explode(',', $item['showgroups']);
    }
    if ($item['buygroups'] != '') {
        $item['buygroups'] = explode(',', $item['buygroups']);
    }

    //核销门店
    if ($merchid == 0) {
        $stores = array();
        if (!empty($item['storeids'])) {
            $stores = pdo_fetchall('select id,storename from ' . tablename('ewei_shop_store') . ' where id in (' . $item['storeids'] . ' ) and uniacid=' . $_W['uniacid']);
        }
    }

    //单独通知人
    if (!empty($item['noticeopenid'])) {
        $saler = m('member')->getMember($item['noticeopenid']);

    }
}



//查询快递模板
$dispatch_data = pdo_fetchall('select * from ' . tablename('ewei_shop_dispatch') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by displayorder desc', array(':uniacid' => $_W['uniacid'], ':merchid' =>$merchid));

//分销插件设置
if (p('commission')) {
    $com_set = p('commission')->getSet();
}
if ($com_virtual) {
    $virtual_types = pdo_fetchall("select * from " . tablename('ewei_shop_virtual_type') . " where uniacid=:uniacid and merchid=:merchid order by id asc", array(":uniacid" => $_W['uniacid'], ':merchid' =>$merchid));
}

//宽度店铺信息
if ($merchid == 0) {
    $details = pdo_fetchall('select detail_logo,detail_shopname,detail_btntext1, detail_btnurl1 ,detail_btntext2,detail_btnurl2,detail_totaltitle from '
        . tablename('ewei_shop_goods') . " where uniacid=:uniacid and detail_shopname<>'' group by detail_shopname", array(':uniacid' => $_W['uniacid']));
    foreach ($details as &$d) {
        $d['detail_logo_url'] = tomedia($d['detail_logo']);
    }
    unset($d);
}


$areas = m('common')->getAreas();

//如果有自定义表单
if ($diyform) {
    $form_list = $diyform->getDiyformList();
    $dfields = iunserializer($item['diyfields']);
}

if(p('diypage')){
    // 获取商品详情页列表
    $detailPages = p('diypage')->getPageList('allpage', " and type=5 ");
    $detailPages = $detailPages['list'];
}

include $this->template('goods/post');
exit;