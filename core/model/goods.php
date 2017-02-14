<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Goods_EweiShopV2Model {

    /**
     * 获取商品规格
     * @param type $goodsid
     * @param type $optionid
     * @return type
     */
    public function getOption($goodsid = 0, $optionid = 0) {
        global $_W;
        return pdo_fetch("select * from " . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid Limit 1', array(':id' => $optionid, ':uniacid' => $_W['uniacid'], ':goodsid' => $goodsid));
    }

    /**
     * 获取商品规格的价格
     * @param type $goodsid
     * @param type $optionid
     * @return type
     */
    public function getOptionPirce($goodsid = 0, $optionid = 0) {
        global $_W;
        return pdo_fetchcolumn("select marketprice from " . tablename('ewei_shop_goods_option') . ' where id=:id and goodsid=:goodsid and uniacid=:uniacid', array(':id' => $optionid, ':uniacid' => $_W['uniacid'], ':goodsid' => $goodsid));
    }

    /**
     * 获取宝贝
     * @param type $page
     * @param type $pagesize
     */
    public function getList($args = array()) {

        global $_W;

        $openid = $_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $random = !empty($args['random']) ? $args['random'] : false;

        $order = !empty($args['order']) ? $args['order'] : ' displayorder desc,createtime desc';
        $orderby = empty($args['order']) ? '' : (!empty($args['by']) ? $args['by'] : '' );

        //多商户
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }

        $condition = ' and `uniacid` = :uniacid AND `deleted` = 0 and status=1';
        $params = array(':uniacid' => $_W['uniacid']);

        //指定商户
        $merchid= !empty($args['merchid']) ? trim($args['merchid']) : '';
        if (!empty($merchid)) {
            $condition.=" and merchid=:merchid and checked=0";
            $params[':merchid'] = $merchid;
        } else {
            if ($is_openmerch == 0) {
                //未开启多商户的情况下,只读取平台商品
                $condition .= ' and `merchid` = 0';
            } else {
                //开启多商户的情况下,过滤掉未通过审核的商品
                $condition .= ' and `checked` = 0';
            }
        }

        // 类型
        if(empty($args['type'])){
            $condition.=" and type !=10 ";
        }

        //指定ID
        $ids = !empty($args['ids']) ? trim($args['ids']) : '';
        if (!empty($ids)) {
            $condition.=" and id in ( " . $ids . ")";
        }

        //新品
        $isnew = !empty($args['isnew']) ? 1 : 0;
        if (!empty($isnew)) {
            $condition.=" and isnew=1";
        }
        //热销
        $ishot = !empty($args['ishot']) ? 1 : 0;
        if (!empty($ishot)) {
            $condition.=" and ishot=1";
        }
        //推荐
        $isrecommand = !empty($args['isrecommand']) ? 1 : 0;
        if (!empty($isrecommand)) {
            $condition.=" and isrecommand=1";
        }
        //折扣
        $isdiscount = !empty($args['isdiscount']) ? 1 : 0;
        if (!empty($isdiscount)) {
            $condition.=" and isdiscount=1";
        }
        //包邮
        $issendfree = !empty($args['issendfree']) ? 1 : 0;
        if (!empty($issendfree)) {
            $condition.=" and issendfree=1";
        }

        //限时购
        $istime = !empty($args['istime']) ? 1 : 0;
        if (!empty($istime)) {
            //$condition.=" and istime=1 and " . time() . ">=timestart and " . time() . "<=timeend";
            $condition.=" and istime=1 ";
        }

        //是否参与分销
        if (isset($args['nocommission'])) {
            $condition .= ' AND `nocommission`=' . intval($args['nocommission']);
        }

        //关键词
        $keywords = !empty($args['keywords']) ? $args['keywords'] : '';
        if (!empty($keywords)) {
            
            $keywords=$this->replace_specialChar($keywords);
            $tmp = str_replace(array(","," ",' '),'', $keywords);
            $str_length = mb_strlen($tmp,'UTF8');
            
            for($i = 0; $i<$str_length; $i++){
                    $keyword_arr[] = $this->cc_msubstr($tmp, $i, 1);
                };
            //$conditions1.=" `title` LIKE '%{$keywords}%' AND ";
            foreach ($keyword_arr as $word)
            {
                $conditions2[] = "`title` LIKE '%{$word}%'";
            }
            $conditions2 = join(' AND ', $conditions2);
            $condition.=" AND ({$conditions2})";
            
            /*
            $condition .= ' AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)';
            $params[':keywords'] = '%' . trim($keywords) . '%';
            */
        }

        //分类
        if(!empty($args['cate'])){
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
                    $condition .= "FIND_IN_SET({$value},cates)";
                }else{
                    $condition .= " || FIND_IN_SET({$value},cates)";
                }
            }
            $condition .= " <>0 )";
        }

        $member =m('member')->getMember($openid);
        if (!empty($member)) {
            $levelid = intval($member['level']);
            $groupid = intval($member['groupid']);
            $condition.=" and ( ifnull(showlevels,'')='' or FIND_IN_SET( {$levelid},showlevels)<>0 ) ";
            $condition.=" and ( ifnull(showgroups,'')='' or FIND_IN_SET( {$groupid},showgroups)<>0 ) ";
        } else {
            $condition.=" and ifnull(showlevels,'')='' ";
            $condition.=" and   ifnull(showgroups,'')='' ";
        }


        $total = "";

        if (!$random) {
            $sql = "SELECT id,title,thumb,marketprice,productprice,minprice,maxprice,isdiscount,isdiscount_time,isdiscount_discounts,sales,total,description,bargain,type FROM " . tablename('ewei_shop_goods') . " where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
            $total = pdo_fetchcolumn("select count(*) from " . tablename('ewei_shop_goods') . " where 1 {$condition} ",$params);
        } else {
            $sql = "SELECT id,title,thumb,marketprice,productprice,minprice,maxprice,isdiscount,isdiscount_time,isdiscount_discounts,sales,total,description,bargain,type FROM " . tablename('ewei_shop_goods') . " where 1 {$condition} ORDER BY rand() LIMIT " . $pagesize;
            $total  = $pagesize;
        }

        $list = pdo_fetchall($sql, $params);
        $list = set_medias($list, 'thumb');
        return array("list"=>$list,"total"=>$total);
    }


    /**
     * 获取宝贝
     * @param type $page
     * @param type $pagesize
     */
    public function getListbyCoupon($args = array()) {

        global $_W;

        $openid = $_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $random = !empty($args['random']) ? $args['random'] : false;

        $order = !empty($args['order']) ? $args['order'] : ' displayorder desc,createtime desc';
        $orderby = empty($args['order']) ? '' : (!empty($args['by']) ? $args['by'] : '' );

        $couponid = empty($args['couponid']) ? '' : $args['couponid'];

        //多商户
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }

        $condition = ' and g.`uniacid` = :uniacid AND g.`deleted` = 0 and g.status=1';
        $params = array(':uniacid' => $_W['uniacid']);

        //指定商户
        $merchid= !empty($args['merchid']) ? trim($args['merchid']) : '';
        if (!empty($merchid)) {
            $condition.=" and g.merchid=:merchid and g.checked=0";
            $params[':merchid'] = $merchid;
        } else {
            if ($is_openmerch == 0) {
                //未开启多商户的情况下,只读取平台商品
                $condition .= ' and g.`merchid` = 0';
            } else {
                //开启多商户的情况下,过滤掉未通过审核的商品
                $condition .= ' and g.`checked` = 0';
            }
        }

        // 类型
        if(empty($args['type'])){
            $condition.=" and g.type !=10 ";
        }

        //指定ID
        $ids = !empty($args['ids']) ? trim($args['ids']) : '';
        if (!empty($ids)) {
            $condition.=" and g.id in ( " . $ids . ")";
        }

        //新品
        $isnew = !empty($args['isnew']) ? 1 : 0;
        if (!empty($isnew)) {
            $condition.=" and g.isnew=1";
        }
        //热销
        $ishot = !empty($args['ishot']) ? 1 : 0;
        if (!empty($ishot)) {
            $condition.=" and g.ishot=1";
        }
        //推荐
        $isrecommand = !empty($args['isrecommand']) ? 1 : 0;
        if (!empty($isrecommand)) {
            $condition.=" and g.isrecommand=1";
        }
        //折扣
        $isdiscount = !empty($args['isdiscount']) ? 1 : 0;
        if (!empty($isdiscount)) {
            $condition.=" and g.isdiscount=1";
        }
        //包邮
        $issendfree = !empty($args['issendfree']) ? 1 : 0;
        if (!empty($issendfree)) {
            $condition.=" and g.issendfree=1";
        }

        //限时购
        $istime = !empty($args['istime']) ? 1 : 0;
        if (!empty($istime)) {
            //$condition.=" and istime=1 and " . time() . ">=timestart and " . time() . "<=timeend";
            $condition.=" and g.istime=1 ";
        }

        //是否参与分销
        if (isset($args['nocommission'])) {
            $condition .= ' AND g.`nocommission`=' . intval($args['nocommission']);
        }

        //关键词
        $keywords = !empty($args['keywords']) ? $args['keywords'] : '';
        if (!empty($keywords)) {
            $condition .= ' AND (g.`title` LIKE :keywords OR g.`keywords` LIKE :keywords)';
            $params[':keywords'] = '%' . trim($keywords) . '%';
        }

        //分类
        if(!empty($args['cate'])){
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
        }

        $member =m('member')->getMember($openid);
        if (!empty($member)) {
            $levelid = intval($member['level']);
            $groupid = intval($member['groupid']);
            $condition.=" and ( ifnull(g.showlevels,'')='' or FIND_IN_SET( {$levelid},g.showlevels)<>0 ) ";
            $condition.=" and ( ifnull(g.showgroups,'')='' or FIND_IN_SET( {$groupid},g.showgroups)<>0 ) ";
        } else {
            $condition.=" and ifnull(g.showlevels,'')='' ";
            $condition.=" and   ifnull(g.showgroups,'')='' ";
        }


        $table =tablename('ewei_shop_goods').' g';

        $distinct='';

        if($couponid>0)
        {
            $data = pdo_fetch('select c.*  from ' . tablename('ewei_shop_coupon_data') . '  cd inner join  ' . tablename('ewei_shop_coupon') . ' c on cd.couponid = c.id  where cd.id=:id and cd.uniacid=:uniacid and coupontype =0  limit 1', array(':id' => $couponid, ':uniacid' => $_W['uniacid']));
            if(!empty($data))
            {
                if($data['limitgoodcatetype']==1&&!empty($data['limitgoodcateids']))
                {
                    $limitcateids=explode(',',$data['limitgoodcateids']);
                    if(count($limitcateids)>0)
                    {
                        $table ='(';
                        $i=0;
                        foreach($limitcateids as $cateid)
                        {
                            $i++;
                            if($i>1)
                            {
                                $table .=' union all ';
                            }
                            $table .='select * from '.tablename('ewei_shop_goods').' where FIND_IN_SET('.$cateid.',cates)';
                        }
                        $table .=') g ';

                        $distinct='distinct';
                    }
                }

                if($data['limitgoodtype']==1&&!empty($data['limitgoodids']))
                {
                    $condition .=' and  g.id in ('.$data['limitgoodids'].') ';
                }
            }
        }

        if (!$random) {
            $sql = "SELECT ".$distinct." g.id,g.title,g.thumb,g.marketprice,g.productprice,g.minprice,g.maxprice,g.isdiscount,g.isdiscount_time,g.isdiscount_discounts,g.sales,g.total,g.description,g.bargain FROM " .$table . " where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
            $total = pdo_fetchcolumn("select ".$distinct." count(*) from " . $table . " where 1 {$condition} ",$params);
        } else {
            $sql = "SELECT ".$distinct." g.id,g.title,g.thumb,g.marketprice,g.productprice,g.minprice,g.maxprice,g.isdiscount,g.isdiscount_time,g.isdiscount_discounts,g.sales,g.total,g.description,g.bargain FROM " . $table . " where 1 {$condition} ORDER BY rand() LIMIT " . $pagesize;
            $total  = $pagesize;
        }

        $list = pdo_fetchall($sql, $params);
        $list = set_medias($list, 'thumb');
        return array("list"=>$list,"total"=>$total);
    }

    public function getTotals() {
        global $_W;
        return array(
            "sale" => pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_goods') . " where status > 0 and checked=0 and deleted=0 and total>0 and uniacid=:uniacid", array(':uniacid' => $_W['uniacid'])),
            "out" => pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_goods') . " where status > 0 and deleted=0 and total=0 and uniacid=:uniacid", array(':uniacid' => $_W['uniacid'])),
            "dis"=>pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_goods') . " where status > 0 and checked=0 and deleted=0 and total>0 and uniacid=:uniacid and isdis=1 ", array(':uniacid' => DIS_ACCOUNT)),
            "stock" => pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_goods') . " where (status=0 or checked=1) and deleted=0 and uniacid=:uniacid", array(':uniacid' => $_W['uniacid'])),
            "cycle" => pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_goods') . " where deleted=1 and uniacid=:uniacid", array(':uniacid' => $_W['uniacid'])),
        );
    }

    /**
     * 获取宝贝评价
     * @param type $page
     * @param type $pagesize
     */
    public function getComments($goodsid = '0', $args = array()) {

        global $_W;
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;

        $condition = ' and `uniacid` = :uniacid AND `goodsid` = :goodsid and deleted=0';
        $params = array(':uniacid' => $_W['uniacid'], ':goodsid' => $goodsid);
        $sql = "SELECT id,nickname,headimgurl,content,images FROM " . tablename('ewei_shop_goods_comment') . " where 1 {$condition} ORDER BY createtime desc LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row) {
            $row['images'] = set_medias(unserialize($row['images']));
        }
        unset($row);
        return $list;
    }

    public function isFavorite($id = '') {

        global $_W;

        $count = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member_favorite') . " where goodsid=:goodsid and deleted=0 and openid=:openid and uniacid=:uniacid limit 1", array(':goodsid' => $id, ':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
        return $count > 0;
    }

    public function addHistory($goodsid = 0) {
        global $_W;

        //浏览总数
        pdo_query("update " . tablename('ewei_shop_goods') . " set viewcount=viewcount+1 where id=:id and uniacid='{$_W[uniacid]}' ", array(":id" => $goodsid));

        //浏览记录
        $history = pdo_fetch('select id,times from ' . tablename('ewei_shop_member_history') . ' where goodsid=:goodsid and uniacid=:uniacid and openid=:openid limit 1'
            , array(':goodsid' => $goodsid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
        if (empty($history)) {
            $history = array(
                'uniacid' => $_W['uniacid'],
                'openid' => $_W['openid'],
                'goodsid' => $goodsid,
                'deleted' => 0,
                'createtime' => time(),
                'times' => 1
            );
            pdo_insert('ewei_shop_member_history', $history);
        } else {
            pdo_update('ewei_shop_member_history', array('deleted' => 0, 'times' => $history['times'] + 1), array('id' => $history['id']));
        }
    }

    public function getCartCount() {

        global $_W, $_GPC;
//		if (empty($_W['mid'])) {
//
//			$carts = m('cookie')->get('carts');
//			if (!is_array($carts)) {
//				return 0;
//			}
//
//			$cartcount = 0;
//			foreach ($carts as $c => $total) {
//				$cartcount+=$total;
//			}
//			return $cartcount;
//		}
        $count = pdo_fetchcolumn('select sum(total) from ' . tablename('ewei_shop_member_cart') . " where uniacid=:uniacid and openid=:openid and deleted=0 limit 1", array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
        return $count;
    }

    /**
     * 获取商品规格图片
     * @param type $specs
     * @return type
     */
    public function getSpecThumb($specs) {
        global $_W;

        $thumb = '';
        $cartspecs = explode('_', $specs);
        $specid = $cartspecs[0];

        if (!empty($specid)) {
            $spec = pdo_fetch("select thumb from " . tablename('ewei_shop_goods_spec_item') . " "
                . " where id=:id and uniacid=:uniacid limit 1 ", array(':id' => $specid, ':uniacid' => $_W['uniacid']));

            if (!empty($spec)) {
                if (!empty($spec['thumb'])) {
                    $thumb = $spec['thumb'];
                }
            }
        }
        return $thumb;
    }

    /**
     * 获取商品规格图片
     * @param type $specs
     * @return type
     */
    public function getOptionThumb($goodsid = 0, $optionid = 0) {
        global $_W;

        $thumb = '';
        $option  = $this->getOption($goodsid, $optionid);
        if (!empty($option)) {
            $specs = $option['specs'];
            $thumb = $this->getSpecThumb($specs);
        }
        return $thumb;
    }

    public function getAllMinPrice($goods)
    {
        global $_W;

        if (is_array($goods))
        {
            $openid =$_W['openid'];
            $level = m('member')->getLevel($openid);
            $member = m('member')->getMember($openid);
            $levelid = $member['level'];

            foreach ($goods as &$value){
                $minprice = $value['minprice']; $maxprice = $value['maxprice'] ;

                if($value['isdiscount'] && $value['isdiscount_time']>=time()){
                    $value['oldmaxprice'] = $maxprice;
                    $isdiscount_discounts = json_decode($value['isdiscount_discounts'],true);
                    $prices = array();

                    if (!isset($isdiscount_discounts['type']) || empty($isdiscount_discounts['type'])) {
                        //统一促销
                        $prices_array = m('order')->getGoodsDiscountPrice($value, $level, 1);
                        $prices[] = $prices_array['price'];
                    } else {
                        //详细促销
                        $goods_discounts = m('order')->getGoodsDiscounts($value, $isdiscount_discounts, $levelid);
                        $prices = $goods_discounts['prices'];
                    }
                    $minprice = min($prices);
                    $maxprice = max($prices);
                }

                $value['minprice'] = $minprice;
                $value['maxprice'] = $maxprice;
            }
            unset($value);
        }
        else
        {
            $goods = array();
        }
        return $goods;
    }

    public function getOneMinPrice($goods)
    {
        $goods = array($goods);
        $res = $this->getAllMinPrice($goods);
        return $res[0];
    }

    public function getMemberPrice($goods, $level)
    {
        if(!empty($goods['isnodiscount'])){
            return;
        }
        $discounts = json_decode($goods['discounts'], true);
        if (is_array($discounts)) {
            $key = !empty($level['id']) ? 'level' . $level['id'] : 'default';
            if (!isset($discounts['type']) || empty($discounts['type'])) {
                $memberprice = $goods['minprice'];
                if (!empty($discounts[$key])){
                    $dd = floatval($discounts[$key]); //设置的会员折扣
                    if ($dd > 0 && $dd < 10) {
                        $memberprice = round($dd / 10 * $goods['minprice'], 2);
                    }
                }else{
                    $dd = floatval($discounts[$key.'_pay']); //设置的会员折扣
                    $md = floatval($level['discount']); //会员等级折扣
                    if (!empty($dd)){
                        $memberprice = round($dd, 2);
                    }else if ($md > 0 && $md < 10) {
                        $memberprice = round($md / 10 * $goods['minprice'], 2);
                    }
                }
                return $memberprice;
            } else {
                //详细折扣
                $options = m('goods')->getOptions($goods);
                $marketprice =  array();
                foreach ($options as $option){
                    $discount = trim($discounts[$key]['option' . $option['id']]);
                    if($discount==''){
                        $discount = round(floatval($level['discount'])*10,2).'%';
                    }
                    $optionprice = m('order')->getFormartDiscountPrice($discount, $option['marketprice']);
                    $marketprice[] =$optionprice;
                }
                $minprice = min($marketprice);
                $maxprice = max($marketprice);
                $memberprice = array(
                    'minprice'=>(float)$minprice,
                    'maxprice'=>(float)$maxprice
                );
                if($memberprice['maxprice']>$memberprice['minprice']){
                    $memberprice = $memberprice['minprice']."~".$memberprice['maxprice'];
                }else{
                    $memberprice = $memberprice['minprice'];
                }
                return $memberprice;
            }
        }
        return;
    }

    public function getOptions($goods)
    {
        global $_W;
        $id = $goods['id'];
        $specs =false;
        $options = false;
        if (!empty($goods) && $goods['hasoption']) {
            $specs = pdo_fetchall('select* from ' . tablename('ewei_shop_goods_spec') . ' where goodsid=:goodsid and uniacid=:uniacid order by displayorder asc', array(':goodsid' => $id, ':uniacid' => $_W['uniacid']));
            foreach($specs as &$spec) {
                $spec['items'] = pdo_fetchall('select * from '.tablename('ewei_shop_goods_spec_item')." where specid=:specid order by displayorder asc",array(':specid'=>$spec['id']));
            }
            unset($spec);
            $options = pdo_fetchall('select * from ' . tablename('ewei_shop_goods_option') . ' where goodsid=:goodsid and uniacid=:uniacid order by displayorder asc', array(':goodsid' => $id, ':uniacid' => $_W['uniacid']));
        }
        return $options;
    }

    /**
     * 商品访问权限
     * @param array $goods
     * @param array $member
     * @return int
     */
    public function visit($goods=array(), $member=array()){
        global $_W;

        if(empty($goods)){
            return 1;
        }
        if(empty($member)){
            $member = m('member')->getMember($_W['openid']);
        }
        $showlevels = $goods['showlevels']!='' ? explode(',', $goods['showlevels']) : array();
        $showgroups = $goods['showgroups']!='' ? explode(',', $goods['showgroups']) : array();
        $showgoods = 0;
        if(!empty($member)){
            if((!empty($showlevels)&&in_array($member['level'], $showlevels)) || (!empty($showgroups) && in_array($member['groupid'], $showgroups)) || (empty($showlevels) && empty($showgroups))){
                $showgoods = 1;
            }
        }else{
            if(empty($showlevels) && empty($showgroups)){
                $showgoods = 1;
            }
        }
        return $showgoods;
    }

    /**
     *
     * 是否已经有重复购买的商品
     * @param $goods
     * @return bool
     */
    public function canBuyAgain($goods)
    {
        global $_W;
        $condition = '';
        $id = $goods['id'];
        if (isset($goods['goodsid'])){
            $id = $goods['goodsid'];
        }
        if (empty($goods['buyagain_islong'])){
            $condition = ' AND canbuyagain = 1';
        }
        $order_goods = pdo_fetchall("SELECT id,orderid FROM ".tablename('ewei_shop_order_goods')." WHERE uniacid=:uniaicd AND openid=:openid AND goodsid=:goodsid {$condition}",array(':uniaicd'=>$_W['uniacid'],':openid'=>$_W['openid'],'goodsid'=>$id),'orderid');

        if (empty($order_goods)){
            return false;
        }
        $order = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_order')." WHERE uniacid=:uniaicd AND status>=:status AND id IN (".implode(',',array_keys($order_goods)).")",array(':uniaicd'=>$_W['uniacid'],':status'=>(empty($goods['buyagain_condition'])?'1':'3')));
        return !empty($order);
    }

    /**
     * 使用掉重复购买的变量
     * @param $goods
     */
    public function useBuyAgain($orderid)
    {
        global $_W;
        $order_goods = pdo_fetchall("SELECT id,goodsid FROM ".tablename('ewei_shop_order_goods')." WHERE uniacid=:uniaicd AND openid=:openid AND canbuyagain = 1 AND orderid <> :orderid",array(':uniaicd'=>$_W['uniacid'],':openid'=>$_W['openid'],'orderid'=>$orderid),'goodsid');
        if (empty($order_goods)){
            return false;
        }
        pdo_query('UPDATE '.tablename('ewei_shop_order_goods')." SET `canbuyagain`='0' WHERE uniacid=:uniacid AND goodsid IN (".implode(',',array_keys($order_goods)).")",array(':uniacid'=>$_W['uniacid']));
    }

    public function getTaskGoods($openid, $goodsid, $rank, $join_id, $optionid = 0, $total = 0)
    {
        global $_W;

        $is_task_goods = 0;
        $is_task_goods_option = 0;
        $task_plugin = p('task');

        $param = array();
        $param['openid'] = $openid;
        $param['goods_id'] = $goodsid;
        $param['rank'] = $rank;
        $param['join_id'] = $join_id;
        $param['goods_spec'] = $optionid;
        $param['goods_num'] = $total;

        if ($task_plugin && !empty($join_id)) {
            $task_goods = $task_plugin->getGoods($param);
        }

        if (!empty($task_goods) && empty($total) && !empty($join_id)) {
            if (!empty($task_goods['spec'])) {
                foreach ($task_goods['spec'] as $k => $v) {
                    if (empty($v['total'])) {
                        unset($task_goods['spec'][$k]);
                        continue;
                    }

                    if (!empty($optionid)) {
                        if ($k == $optionid) {
                            $task_goods['marketprice'] = $v['marketprice'];
                            $task_goods['total'] = $v['total'];
                        } else {
                            unset($task_goods['spec'][$k]);
                        }
                    }

                    if (!empty($optionid) && $k != $optionid) {
                        unset($task_goods['spec'][$k]);
                    } else if (!empty($optionid) && $k != $optionid) {
                        $task_goods['marketprice'] = $v['marketprice'];
                        $task_goods['total'] = $v['total'];
                    }
                }
                if (!empty($task_goods['spec'])) {
                    $is_task_goods = 1;
                    $is_task_goods_option = 1;
                }
            } else {
                if (!empty($task_goods['total'])) {
                    $is_task_goods = 1;
                }
            }
        }

        $data = array();
        $data['is_task_goods'] = $is_task_goods;
        $data['is_task_goods_option'] = $is_task_goods_option;
        $data['task_goods'] = $task_goods;

        return $data;


    }

    private function replace_specialChar($strParam){
        $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        return preg_replace($regex,"",$strParam);
    }
    private function cc_msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
    {
        if(function_exists("mb_substr"))
            return mb_substr($str, $start, $length, $charset);
        elseif(function_exists('iconv_substr')) {
            return iconv_substr($str,$start,$length,$charset);
        }
        $re['utf-8']   = "/[/x01-/x7f]|[/xc2-/xdf][/x80-/xbf]|[/xe0-/xef][/x80-/xbf]{2}|[/xf0-/xff][/x80-/xbf]{3}/";
        $re['gb2312'] = "/[/x01-/x7f]|[/xb0-/xf7][/xa0-/xfe]/";
        $re['gbk']    = "/[/x01-/x7f]|[/x81-/xfe][/x40-/xfe]/";
        $re['big5']   = "/[/x01-/x7f]|[/x81-/xfe]([/x40-/x7e]|/xa1-/xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
        if($suffix) return $slice."…";
        return $slice;
    }

}
