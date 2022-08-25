<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage {


    function test(){
        global $_W, $_GPC;

        var_dump( m("wxpayv3")->get_certificates());

    }
    function main() {

        global $_W, $_GPC;

        //多商户
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }
        $depotsql="SELECT * from ".tablename("ewei_shop_depot")." where uniacid=:uniacid and enabled=1";
        $depotlist=pdo_fetchall($depotsql,array(":uniacid"=>$_W['uniacid']));
        if($_W['uniacid']!=DIS_ACCOUNT){
            $depotsql="SELECT * from ".tablename("ewei_shop_depot")." where uniacid=:uniacid and enabled=1";
            $depotACCOUNTlist=pdo_fetchall($depotsql,array(":uniacid"=>DIS_ACCOUNT));
            $depotlist = array_merge($depotlist, $depotACCOUNTlist);
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $sqlcondition = $groupcondition = '';
        $condition = ' WHERE g.`uniacid` = :uniacid';
        $orderbuy=' ORDER BY g.`status` DESC, g.`displayorder` DESC,g.`id` DESC';
        $params = array(':uniacid' => $_W['uniacid']);
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);

            $sqlcondition = ' left join ' . tablename('ewei_shop_goods_option') . ' op on g.id = op.goodsid';
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

        if (!empty($_GPC['cate'])) {
            $_GPC['cate'] =$args['cate']= intval($_GPC['cate']);
            $category = m('shop')->getAllCategory();
            $catearr = array($args['cate']);
            foreach ($category as $index => $row) {
                if ($row['parentid'] == $args['cate']) {
                    $catearr[] = $row['id'];
                    foreach ($category as $ind => $ro) {
                        if ($ro['parentid'] == $row['id']) {
                            $catearr[] = $ro['id'];
                        }
                    }
                }
            }
            $catearr = array_unique($catearr);
            $condition .= " AND ( ";
            foreach ($catearr as $key=>$value){
                if ($key==0) {
                    $condition .= "FIND_IN_SET({$value},g.cates)";
                }else{
                    $condition .= " || FIND_IN_SET({$value},g.cates)";
                }
            }
            $condition .= " <>0 )";
           // $condition .= " AND FIND_IN_SET({$_GPC['cate']},cates)<>0 ";
        }
        if(isset($_GPC['depotid']) && is_numeric($_GPC['depotid'])){
            $condition .=" AND g.depotid={$_GPC['depotid']}";
        }
        $goodsfrom = strtolower(trim($_GPC['goodsfrom']));
        empty($goodsfrom) && $_GPC['goodsfrom'] = $goodsfrom = 'sale';

        if ($goodsfrom == 'sale') {
            $condition .= ' AND g.`status` > 0 and g.`checked`=0 and g.`total`>0 and g.`deleted`=0';
            $status = 1;
        } else if ($goodsfrom == 'out') {
            $condition .= ' AND g.`status` > 0 and g.`total` <= 0 and g.`deleted`=0';
            $status = 1;
        } else if ($goodsfrom == 'stock') {
            $status = 0;
            $condition .= ' AND (g.`status` = 0 or g.`checked`=1) and g.`deleted`=0';
        } else if ($goodsfrom == 'cycle') {
            $status = 0;
            $condition .= ' AND g.`deleted`=1';
        }else if($goodsfrom=="cnbuyer"){
            $condition .= ' AND g.`status` = -1 and g.`checked`=0 and g.`total`>0 and g.`deleted`=0';
            $status = 0;
        }
        //wsq 获取当前公众号代理ID
        if($_W['uniacid']!=DIS_ACCOUNT){
            $DisInfo= Dispage::getDisInfo($_W['uniacid']);
        }
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
        
        //var_dump($levels);
        //wsq 代理等级
        $reseller=pdo_fetchall("SELECT * from ".tablename("ewei_shop_reseller"));
        //$reseller=array();
        //wsq 代理价格
        $goodsresel=pdo_fetchall("SELECT * from ".tablename("ewei_shop_goodsresel"));
        $t=array();
        foreach($goodsresel as $resel){
            $t[$resel['goods_id']]=unserialize($resel['disprice']);
        }
        if($_GPC['export']==1){
            //获取全部仓库
            $depotsql="SELECT * from ".tablename("ewei_shop_depot");
            $depostlist=pdo_fetchall($depotsql);
            $de=array();
            foreach ($depostlist as $key => $value) {
                $de[$value['id']]=$value['title'];
            }
            $categorys = m('shop')->getFullCategory(true);
            $sql = 'SELECT g.id,g.unit,g.productprice,g.costprice,g.isdis,g.title,g.goodssn,g.productsn,g.sales,g.total,g.ccates,g.thumb,g.depotid,g.marketprice,g.discounts,g.disgoods_id,g.consumption_tax,g.vat_rate,g.subtitle,g.keywords,g.displayorder,g.isdiscount_discounts FROM ' . tablename('ewei_shop_goods') . 'g' . $sqlcondition . $condition . $groupcondition . ' ORDER BY g.`status` DESC, g.`displayorder` DESC';
            $goodslist=pdo_fetchall($sql,$params);
            $categorystemp=array();
            foreach ($categorys as $r) {
               $categorystemp[$r['id']]=$r['name'];
            }
            //var_dump($categorystemp);
            $disinfo=Dispage::getDisInfo($_W['uniacid']);
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
            $columns = array(
                array('title' => '排序', 'field' => 'displayorder', 'width' => 24),
                array('title' => '商品SKU', 'field' => 'goodssn', 'width' => 24),
                array('title' => '商品名称', 'field' => 'title', 'width' => 124),
                array('title' => '商品单价', 'field' => 'marketprice', 'width' => 12),
                array('title' => '商品原价', 'field' => 'productprice', 'width' => 12),
                array('title' => '成本', 'field' => 'costprice', 'width' => 12),
                array("title"=>'仓库','field' => 'depot', 'width' => 12),
               // array('title' => '销量', 'field' => 'sales', 'width' => 12),
                array('title' => '库存', 'field' => 'total', 'width' => 12),
            );
            if($_W['uniacid']!=DIS_ACCOUNT){
                 $columns[]=array('title' => '代理价', 'field' => 'disprice', 'width' => 24);
            }else{
                foreach ($reseller as $key => $value) {
                    $columns[]=array('title' => $value['name'], 'field' => "dis".$value['id'], 'width' => 24);
                }
            }
            foreach($levels as $l){
                $columns[]=array(
                    'title' => $l['levelname'],
                    'field' => $l['key'],
                    'width' => 24
                );
                $columns[]=array(
                    'title' => "促销".$l['levelname'],
                    'field' => "c".$l['key'],
                    'width' => 24
                );
            }
            
            foreach($goodslist as $key=> $goodstemp){
               // var_dump($goodstemp['ccates']);
            $discounts=(array)json_decode($goodslist[$key]['discounts']);
            $isdiscounts=json_decode($goodslist[$key]['isdiscount_discounts'],true);

               foreach($levels as $l){
                $goodslist[$key][$l['key']]=$discounts[$l['key'].'_pay'];
               }
                foreach($levels as $l){
                    $goodslist[$key]['c'.$l['key']]=$isdiscounts[$l['key']]['option0'];
                }


                $goodslist[$key]['goodssn']=$goodstemp['goodssn']."`";
                $goodslist[$key]['depot']=empty($goodstemp['depotid'])?"默认仓库" :$de[$goodstemp['depotid']];
                $goodslist[$key]['imgthumb']="http://wx.lylife.com.cn/attachment/".$goodstemp['thumb'];
               if($_W['uniacid']!=DIS_ACCOUNT){
                 $goodslist[$key]['disprice']=$t[$goodstemp['disgoods_id']][$disinfo['resellerid']];
               }
               foreach ($reseller as $k => $value) {
                    if(empty($t[$goodstemp['id']])){
                        $goodslist[$key]['dis'.$value['id']]=0;
                    }else{
                        $goodslist[$key]['dis'.$value['id']]=$t[$goodstemp['id']][$value['id']];
                    }
               }
               switch ($goodstemp['isdis']) {
                   case '0':
                        $goodslist[$key]['isdis']="非代理";
                   break;
                   case '1':
                       $goodslist[$key]['isdis']="代理商品";
                       break;
                   default:
                       # code...
                       break;
               }
            }
      //die();
             plog('order.op.export', "导出商品");
           
              

              
              m('excel')->export($goodslist, array(
                "title" => "订单数据-" . date('Y-m-d-H-i', time()),
                "columns" => $columns
            ));
           
           exit;
        }
        $sql = 'SELECT g.id FROM ' . tablename('ewei_shop_goods') . 'g' . $sqlcondition . $condition . $groupcondition;
        $total_all = pdo_fetchall($sql, $params);

        $total = count($total_all);
        unset($total_all);
        if (!empty($total)) {
            // $sqltitle="g.*";
            if ($goodsfrom == 'stock' && $_W['uniacid']!=DIS_ACCOUNT) {
                $sqlcondition.= ' left join ' . tablename('ewei_shop_goods') . ' g2 on g.disgoods_id = g2.id';
                $orderbuy.=",g2.`status` desc";
                //$sqltitle="g.id,g.title,g.pcate,g.ccate,g.thumb,g.displayorder,g.discounts,g.total,g.sales,g.tcate,g.pcates,g.tcates,g.disgoods_id,g.cates";
                $sqltitle="g.*,g2.status as account_shop";
            }else{
                $sqltitle="g.*";
            }
            $sql = 'SELECT '.$sqltitle.' FROM ' . tablename('ewei_shop_goods') . 'g' . $sqlcondition . $condition . $groupcondition . $orderbuy .' LIMIT '. ($pindex - 1) * $psize . ',' . $psize;
           
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
       
        //var_dump($sql);
        //die();
        $categorys = m('shop')->getFullCategory(true);
		$category = array();
		foreach($categorys as $cate){
			$category[$cate['id']] = $cate;
		}
		include $this->template();
    }
   
    function add() {
        $this->post();
    }

    function edit() {
        $this->post();
    }

    protected function post() {

        require dirname(__FILE__)."/post.php";
    }
     function update(){
        global $_W, $_GPC;
        if(is_numeric($_REQUEST['id'])){
             $id = intval($_REQUEST['id']);
        }else{
             $id = $_REQUEST['id'];
             $ids=explode(",", $id);
        }
        $category = m('shop')->getFullCategory(true,false);
        if($_W['ispost']){
           
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
               
            if(!is_numeric($id)){
                foreach($ids as $id){
                    $ret[]=pdo_update("ewei_shop_goods",$data,array("id"=>$id));
                }
            }else{
                 pdo_update("ewei_shop_goods",$data,array("id"=>$id));
            }
            show_json(1, array('url' => referer()));
        }
        
       include $this->template();
    }
    function delete() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_goods') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_update('ewei_shop_goods', array('deleted' => 1), array('id' => $item['id']));
            plog('goods.delete', "删除商品 ID: {$item['id']} 商品名称: {$item['title']} ");
        }
        show_json(1, array('url' => referer()));
    }

    function status() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title,disgoods_id,isdis FROM " . tablename('ewei_shop_goods') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            $bool=Dispage::checkGoodsStatus($item['id'],$item['disgoods_id'],$item['isdis'],$_W['uniacid'],$_GPC['status']);
            if(!$bool){
                show_json(0, array('message' => "主站商品已经下架无法上架"));
            }
            pdo_update('ewei_shop_goods', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
            plog('goods.edit', "修改商品状态<br/>ID: {$item['id']}<br/>商品名称: {$item['title']}<br/>状态: " . $_GPC['status'] == 1 ? '上架' : '下架');
        }
        show_json(1, array('url' => referer()));
    }

    function checked() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_goods') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_goods', array('checked' => intval($_GPC['checked'])), array('id' => $item['id']));
            plog('goods.edit', "修改商品状态<br/>ID: {$item['id']}<br/>商品名称: {$item['title']}<br/>状态: " . $_GPC['checked'] == 0 ? '审核通过' : '审核中');
        }

        show_json(1, array('url' => referer()));
    }

    function delete1() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_goods') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_delete('ewei_shop_goods', array('id' => $item['id']));
            plog('goods.edit', "从回收站彻底删除商品<br/>ID: {$item['id']}<br/>商品名称: {$item['title']}");
        }
        show_json(1, array('url' => referer()));
    }

    function restore() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,title FROM " . tablename('ewei_shop_goods') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_goods', array('deleted' => 0), array('id' => $item['id']));
            plog('goods.edit', "从回收站恢复商品<br/>ID: {$item['id']}<br/>商品名称: {$item['title']}");
        }
        show_json(1, array('url' => referer()));
    }

    function property() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $type = $_GPC['type'];
        $data = intval($_GPC['data']);
        if (in_array($type, array('new', 'hot', 'recommand', 'discount', 'time', 'sendfree', 'nodiscount'))) {

            pdo_update("ewei_shop_goods", array("is" . $type => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
            if ($type == 'new') {
                $typestr = "新品";
            } else if ($type == 'hot') {
                $typestr = "热卖";
            } else if ($type == 'recommand') {
                $typestr = "推荐";
            } else if ($type == 'discount') {
                $typestr = "促销";
            } else if ($type == 'time') {
                $typestr = "限时卖";
            } else if ($type == 'sendfree') {
                $typestr = "包邮";
            } else if ($type == 'nodiscount') {
                $typestr = "不参与折扣状态";
            }
            plog('goods.edit', "修改商品{$typestr}状态   ID: {$id}");
        }
        if (in_array($type, array('status'))) {

            pdo_update("ewei_shop_goods", array($type => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
            plog('goods.edit', "修改商品上下架状态   ID: {$id}");
        }
        if (in_array($type, array('type'))) {
            pdo_update("ewei_shop_goods", array($type => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
            plog('goods.edit', "修改商品类型   ID: {$id}");
        }
        show_json(1);
    }

    function change() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            show_json(0, array('message' => '参数错误'));
        }
        $type = trim($_GPC['type']);
        $value = trim($_GPC['value']);
        if (!in_array($type, array('title', 'marketprice', 'total', 'goodssn', 'productsn', 'displayorder','disprice','memberprice'))) {
            show_json(0, array('message' => '参数错误'));
        }
        if( $type=="disprice"){//wsq
            $gr=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_goodsresel")." where goods_id=:goodsid",array(':goodsid'=>$id));
            $key=$_GPC['key'];
             $value=round($value,2);
            if(empty($gr)){
                $disprice=array($key=>$value);
                $strdisprice=serialize($disprice);
                $data=array('goods_id'=>$id,'disprice'=>$strdisprice);
                pdo_insert("ewei_shop_goodsresel",$data);
            }else{
                $disprice=unserialize($gr['disprice']);
                $disprice[$key]=$value;
                $strdisprice=serialize($disprice);
                $data=array('disprice'=>$strdisprice);
               pdo_update("ewei_shop_goodsresel",$data,array('id'=>$gr['id']));
            }
            //pdo_update("ewei_shop_goodsresel",);
            show_json(1,"ok");
        }
        if($type=="memberprice"){
            $goods=pdo_fetch("SELECT * FROM ".tablename("ewei_shop_goods")." where id=:goodsid",array(':goodsid'=>$id));
            $key=$_GPC['key'];
            $value=round($value,2);
            $discounts=(array)json_decode($goods['discounts']);
          
            if(empty($discounts[$key."_pay"])){
                $discounts[$key]="";
                $discounts[$key."_pay"]=$value;
            }else{
                $discounts[$key."_pay"]=$value;
            }
            $discounts=json_encode($discounts);
           pdo_update("ewei_shop_goods",array("discounts"=>$discounts),array("id"=>$id));
            plog('goods.edit', "修改商品会员价格:{$value}   ID: {$id}");
           show_json(1,"ok");
        }

        $goods = pdo_fetch('select id,hasoption from ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));
        if (empty($goods)) {
            show_json(0, array('message' => '参数错误'));
        }

        pdo_update('ewei_shop_goods', array($type => $value), array('id' => $id));

        if ($goods['hasoption'] == 0) {
            $sql = "update ".tablename('ewei_shop_goods')." set minprice = marketprice,maxprice = marketprice where id = {$goods['id']} and hasoption=0;";
            pdo_query($sql);
        }
        show_json(1);
    }

    function tpl() {
        global $_GPC, $_W;
        $tpl = trim($_GPC['tpl']);
        if ($tpl == 'option') {

            $tag = random(32);
            include $this->template('goods/tpl/option');
        } else if ($tpl == 'spec') {

            $spec = array("id" => random(32), "title" => $_GPC['title']);
            include $this->template('goods/tpl/spec');
        } else if ($tpl == 'specitem') {

            $spec = array("id" => $_GPC['specid']);
            $specitem = array("id" => random(32), "title" => $_GPC['title'], "show" => 1);
            include $this->template('goods/tpl/spec_item');
        } else if ($tpl == 'param') {

            $tag = random(32);
            include $this->template('goods/tpl/param');
        }
    }

    function query(){
        global $_W, $_GPC;
        $kwd = trim($_GPC['keyword']);
        $type = intval($_GPC['type']);

        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        $condition=" and status=1 and deleted=0 and uniacid=:uniacid";
        if (!empty($kwd)) {
            $condition.=" AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)";
            $params[':keywords'] = "%{$kwd}%";
        }
        if (empty($type)) {
            $condition.=" AND `type` != 10 ";
        }else{
            $condition.=" AND `type` = :type ";
            $params[':type'] = $type;
        }

        $ds = pdo_fetchall('SELECT id,title,thumb,marketprice,productprice,share_title,share_icon,description,minprice,costprice,total
              FROM ' . tablename('ewei_shop_goods') . " WHERE 1 {$condition} order by createtime desc", $params);
        foreach ($ds as $key=> $value) {
            $ds[$key]['title']=$str = str_replace('#', '', $value['title']);
        }
        $ds = set_medias($ds,array('thumb','share_icon'));
        if($_GPC['suggest']){
            die(json_encode(array('value'=>$ds)));
        }
        include $this->template();

    }

    function goodsprice()
    {
        global $_W;
        $sql = "update ".tablename('ewei_shop_goods')." g set 
g.minprice = (select min(marketprice) from ".tablename('ewei_shop_goods_option')." where g.id = goodsid),
g.maxprice = (select max(marketprice) from ".tablename('ewei_shop_goods_option')." where g.id = goodsid)
where g.hasoption=1 and g.uniacid=".$_W['uniacid'].";
update ".tablename('ewei_shop_goods')." set minprice = marketprice,maxprice = marketprice where hasoption=0 and uniacid=".$_W['uniacid'].";";
        pdo_run($sql);
        show_json(1);
    }
    function stockimport(){
        $columns = array();
        $columns[] = array('title' => '商品货号', 'field' => '', 'width' => 32);
        $columns[] = array('title' => '库存', 'field' => '', 'width' => 32);
        $columns[] = array('title' => '价格', 'field' => '', 'width' => 32);
        $columns[] = array('title' => '仓库ID', 'field' => '', 'width' => 32);
        $columns[] = array('title' => '标题', 'field' => '', 'width' => 32);
        m('excel')->temp('批量库存导入数据模板', $columns);
    }
    function stock(){
        global $_W;
        global $_GPC;
        if ($_W['ispost'])
        {
            $rows = m('excel')->import('excelfile');
            $num = count($rows);
            $time = time();
            $i = 0;
            $err_array = array();


            foreach ($rows as $rownum => $col )
            {

                if($rownum==0){
                    continue;
                }
                $goodsn = trim($col[0]);
                if(empty($goodsn)){
                    continue;
                }
                $sql="SELECT id,consumption_tax,vat_rate,marketprice FROM ".tablename("ewei_shop_goods")." where hasoption=0 and goodssn =:goods_sn and uniacid=:uniacid and merchid=:merchid";
                $goods = pdo_fetch($sql, array(':goods_sn' => $goodsn, ':uniacid' => $_W['uniacid'], ':merchid' => 0));

                if(!empty($goods['id'])){
                    $updatetimes=array();
                    $stock = !is_numeric($col[1]) ? "":trim($col[1]);

                    $updatetimes['total'] =$stock;

                    $marketprice = !is_numeric($col[2]) ? 0 : trim($col[2]);

                    if($marketprice>0){
                        $updatetimes['marketprice'] =$marketprice;
                    }

                    $depotid = !is_numeric($col[3]) ? 0 : trim($col[3]);
                    if($depotid>0){
                        $updatetimes['depotid'] =$depotid;
                    }
                    $title = empty($col[4]) ? '' : trim($col[4]);
                    if(!empty($title)){
                        $updatetimes['title'] =$title;
                    }

                    $cs=pdo_update("ewei_shop_goods",$updatetimes,array("id"=>$goods['id']));
                    if($cs){
                        ++$i;
                    }else{
                        $err_array[] = $goodsn;
                    }
                }else{
                    $err_array[] = $goodsn;
                }

            }
            $tip = '';
            $msg = $i . '个商品更新成功！';
            if ($i < $num)
            {
                $url = '';
                if (!(empty($err_array)))
                {
                    $j = 1;
                    $tip .= '<br>' . count($err_array) . '个商品,失败的商品编号: <br>';
                    foreach ($err_array as $k => $v )
                    {
                        $tip .= $v . ' ';
                        if (($j % 2) == 0)
                        {
                            $tip .= '<br>';
                        }
                        ++$j;
                    }
                }
            }
            else
            {
                $url = webUrl('goods/stock');
            }
            $this->message($msg . $tip, $url, '');

        }
        include $this->template();
    }

    function isdiscount(){
        global $_W;
        global $_GPC;
        if ($_W['ispost'])
        {


            $isdiscount_time=$_GPC['isdiscount_time'];
            $starttime=strtotime($isdiscount_time['start']);
            $endtime=strtotime($isdiscount_time['end']);
            $data=array(
                'isdiscount'=>1,
                'isdiscount_stat_time'=>$starttime,
                'isdiscount_time'=>$endtime,
            );
            $levels = m('member')->getLevels();
            foreach ($levels as &$lev){
                $lev['key']='level' . $lev['id'];
            }
            unset($lev);
            $levels = array_merge(array( array('id' => 0, 'key' => 'default', 'levelname' => (empty($_W['shopset']['shop']['levelname']) ? '默认会员' : $_W['shopset']['shop']['levelname'])) ), $levels);

            foreach ($levels as $lev){

                $levels_array[$lev['levelname']]=$lev;
            }
            $rows = m('excel')->importall('excelfile');

            pdo_begin();
            $errormesage='';
            foreach ($rows as $rownum => $col )
            {
                $linecount=count($col);

                if($rownum==0){
                    for ($i=0;$i<$linecount;$i++){
                        if(isset($levels_array[$rows[0][$i]])){
                            $col_array[$i]=$levels_array[$rows[0][$i]];
                        }
                    }
                    continue;
                }
                $goodssn=trim($col[0]);
                if(empty($goodssn)){
                    continue;
                }
                //验证货号是否是正常的

                $goods=pdo_fetch("select hasoption,id from ".tablename("ewei_shop_goods")." where goodssn=:goodssn and uniacid=:uniacid and status=1",array(':goodssn'=>$goodssn,":uniacid"=>$_W['uniacid']) );


                if($goods['hasoption']==1 || empty($goods)){
                    // throw new Exception('货号不存在或者当前商品带有规格属性goodssn='.$goodssn);
                    $errormesage.='货号不存在或者当前商品带有规格属性goodssn='.$goodssn."<br/>";
                    continue;
                }


                $isDiscountsDefaultArray=array();
                $msg="";

                for ($i=0;$i<$linecount;$i++) {
                    if (isset($col_array[$i]) && $col_array[$i]['key'] == "default") {

                        if($col[$i]<=0){
                            // throw new Exception('货号'.$goodssn."促销金额不能小于或者等于0");
                            $errormesage.='货号'.$goodssn."促销金额不能小于或者等于0"."<br/>";
                            continue;
                        }
                        $isDiscountsDefaultArray[$col_array[$i]['key']]['option0'] = $col[$i];
                        $msg="{$goodssn}:"."设置促销价格-".$col[$i];
                    } elseif (isset($col_array[$i])) {
                        if($col[$i]<=0){
                            // throw new Exception('货号'.$goodssn."促销金额不能小于或者等于0");
                            $errormesage.='货号'.$goodssn."促销金额不能小于或者等于0"."<br/>";
                            continue;
                        }
                        $msg.="{$goodssn}:"."设置促销价格-".$col[$i];
                        $isDiscountsDefaultArray[$col_array[$i]['key']]['option0'] = $col[$i];
                    }
                }
                if(empty($isDiscountsDefaultArray)){
                    // throw new Exception('货号'.$goodssn."促销金额设置失败");
                    $errormesage.='货号'.$goodssn."促销金额设置失败"."<br/>";
                    continue;
                }
                $is_discounts_arr = array_merge(array('type ' => 0), $isDiscountsDefaultArray);
                $data['isdiscount_discounts'] = ((is_array($is_discounts_arr) ? json_encode($is_discounts_arr) : json_encode(array())));
                $data['isdiscount_title']=$col[1];
                $ret=pdo_update('ewei_shop_goods',$data,array("uniacid"=>$_W['uniacid'],"id"=>$goods['id']));
                if(!$ret){
                    // throw new Exception('货号'.$goodssn."促销金额设置失败");
                    $errormesage.='货号'.$goodssn."促销金额设置失败"."<br/>";
                    continue;
                }

            }
            if(!empty($errormesage)){
                pdo_rollback();
                $this->message($errormesage, webUrl("goods.isdiscount"), '');
            }
            pdo_commit();

            $this->message("导入数据成功",  webUrl("goods.isdiscount"), '');
        }
        include $this->template();
    }

    public function import(){
        global $_W;
        global $_GPC;
        $levels = m('member')->getLevels();
        $levels = array_merge(array( array('id' => 0, 'key' => 'default', 'levelname' => (empty($_W['shopset']['shop']['levelname']) ? '默认会员' : $_W['shopset']['shop']['levelname'])) ), $levels);
        $columns = array();
        $columns[] = array('title' => '商品货号', 'field' => '', 'width' => 32);
        $columns[] = array('title' => '促销名称', 'field' => '', 'width' => 32);

        foreach ($levels as $lev){
            $columns[] = array('title' => $lev['levelname'], 'field' => '', 'width' => 32);
        }

        m('excel')->temp('批量导入促销活动模板', $columns);
    }



}
