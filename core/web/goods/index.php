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


   function test11(){
     global $_W, $_GPC;
     $name=$_GPC['name'];
    
     $sql="select * from ".tablename("ewei_shop_goods")." where title={$name}";
     $s=pdo_fetch($sql);
     var_dump($s);
     die();
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
        //wsq 代理价格
        $goodsresel=pdo_fetchall("SELECT * from ".tablename("ewei_shop_goodsresel"));
        $t=array();
        foreach($goodsresel as $resel){
            $t[$resel['goods_id']]=unserialize($resel['disprice']);
        }
        if($_GPC['export']==1){

            $categorys = m('shop')->getFullCategory(true);
            $sql = 'SELECT g.id,g.unit,g.isdis,g.title,g.goodssn,g.marketprice,g.discounts,g.disgoods_id FROM ' . tablename('ewei_shop_goods') . 'g' . $sqlcondition . $condition . $groupcondition . ' ORDER BY g.`status` DESC, g.`displayorder` DESC';
            $goodslist=pdo_fetchall($sql,$params);
            $categorystemp=array();
            foreach ($categorys as $r) {
               $categorystemp[$r['id']]=$r['name'];
            }
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
                array('title' => '商品SKU', 'field' => 'goodssn', 'width' => 24),
                array('title' => '商品名称', 'field' => 'title', 'width' => 24),
                array('title' => '单位', 'field' => 'unit', 'width' => 12),
                array('title' => '商品单价', 'field' => 'marketprice', 'width' => 12),
              
                array('title' => '代理状态', 'field' => 'isdis', 'width' => 12),
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
                    'width' => 24);
            }
            
            foreach($goodslist as $key=> $goodstemp){
            $discounts=(array)json_decode($goodslist[$key]['discounts']);
               foreach($levels as $l){
                $goodslist[$key][$l['key']]=$discounts[$l['key'].'_pay'];
               }
                $goodslist[$key]['goodssn']=$goodstemp['goodssn']."`";
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
    function test(){
        $tax=new Tax();
        $tax->tax_rate(63);
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

        $ds = pdo_fetchall('SELECT id,title,thumb,marketprice,productprice,share_title,share_icon,description,minprice,costprice,total,content
              FROM ' . tablename('ewei_shop_goods') . " WHERE 1 {$condition} order by createtime desc", $params);
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

}
