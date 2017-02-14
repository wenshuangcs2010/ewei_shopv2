<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends ComWebPage {

    public function __construct($_com = 'coupon')
    {
        parent::__construct($_com);
    }

    function main() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' uniacid = :uniacid and merchid=0';
        $params = array(':uniacid' => $_W['uniacid']);
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' AND couponname LIKE :couponname';
            $params[':couponname'] = '%' . trim($_GPC['keyword']) . '%';
        }

        if (!empty($_GPC['catid'])) {
            $_GPC['catid'] = trim($_GPC['catid']);
            $condition .= ' AND catid = :catid';
            $params[':catid'] = (int)$_GPC['catid'];
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            if (!empty($starttime)) {
                $condition .= " AND createtime >= :starttime";
                $params[':starttime'] = $starttime;
            }
            if (!empty($endtime)) {
                $condition .= " AND createtime <= :endtime";
                $params[':endtime'] = $endtime;
            }
        }

        if ($_GPC['gettype'] != '') {
            $condition .= ' AND gettype = :gettype';
            $params[':gettype'] = intval($_GPC['gettype']);
        }

        if ($_GPC['type'] != '') {
            $condition .= ' AND coupontype = :coupontype';
            $params[':coupontype'] = intval($_GPC['type']);
        }
        $sql = 'SELECT * FROM ' . tablename('ewei_shop_coupon') . " "
            . " where  1 and {$condition} ORDER BY displayorder DESC,id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row) {
            $row['gettotal'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_data') . ' where couponid=:couponid and uniacid=:uniacid limit 1', array(':couponid' => $row['id'], ':uniacid' => $_W['uniacid']));
            $row['usetotal'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_data') . ' where used = 1 and couponid=:couponid and uniacid=:uniacid limit 1', array(':couponid' => $row['id'], ':uniacid' => $_W['uniacid']));
            $row['pwdjoins'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_guess') . ' where couponid=:couponid and uniacid=:uniacid limit 1', array(':couponid' => $row['id'], ':uniacid' => $_W['uniacid']));
            $row['pwdoks'] = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_coupon_guess') . ' where couponid=:couponid and uniacid=:uniacid and ok=1 limit 1', array(':couponid' => $row['id'], ':uniacid' => $_W['uniacid']));
        }
        unset($row);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_coupon') . " where 1 and {$condition}", $params);
        $pager = pagination($total, $pindex, $psize);
        $category = pdo_fetchall('select * from ' . tablename('ewei_shop_coupon_category') . ' where uniacid=:uniacid and merchid=0 order by id desc', array(':uniacid' => $_W['uniacid']), 'id');

        include $this->template();
    }

    function add() {
        $this->post();
    }

    function edit() {
        $this->post();
    }

    protected function post() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $type = intval($_GPC['type']);

        if ($_W['ispost']) {
            $data = array(
                'uniacid' => $_W['uniacid'],
                'couponname' => trim($_GPC['couponname']),
                'coupontype' => intval($_GPC['coupontype']),
                'catid' => intval($_GPC['catid']),
                'timelimit' => intval($_GPC['timelimit']),
                'usetype' => intval($_GPC['usetype']),
                'returntype' =>intval($_GPC['returntype']),
                'enough' => trim($_GPC['enough']),
                'timedays' => intval($_GPC['timedays']),
                'timestart' => strtotime($_GPC['time']['start']),
                'timeend' => strtotime($_GPC['time']['end'])+86399,
                'backtype' => intval($_GPC['backtype']),
                'deduct' => trim($_GPC['deduct']),
                'discount' => trim($_GPC['discount']),
                'backmoney' => trim($_GPC['backmoney']),
                'backcredit' => trim($_GPC['backcredit']),
                'backredpack' => trim($_GPC['backredpack']),
                'backwhen' => intval($_GPC['backwhen']),
                'gettype' => intval($_GPC['gettype']),
                'getmax' => intval($_GPC['getmax']),
                'credit' => intval($_GPC['credit']),
                'money' => trim($_GPC['money']),
                'usecredit2' => intval($_GPC['usecredit2']),
                'total' => intval($_GPC['total']),
                'bgcolor' => trim($_GPC['bgcolor']),
                'thumb' => save_media($_GPC['thumb']),
                'remark' => trim($_GPC['remark']),
                'desc' => m('common')->html_images($_GPC['desc']),
                'descnoset' => intval($_GPC['descnoset']),
                'status' => intval($_GPC['status']),
                'resptitle' => trim($_GPC['resptitle']),
                'respthumb' => save_media($_GPC['respthumb']),
                'respdesc' => trim($_GPC['respdesc']),
                'respurl' => trim($_GPC['respurl']),
                'pwdkey2' => trim($_GPC['pwdkey2']),
                'pwdwords' => trim($_GPC['pwdwords']),
                'pwdask' => trim($_GPC['pwdask']),
                'pwdsuc' => trim($_GPC['pwdsuc']),
                'pwdfail' => trim($_GPC['pwdfail']),
                'pwdfull' => trim($_GPC['pwdfull']),
                'pwdurl' => trim($_GPC['pwdurl']),
                'pwdtimes' => intval($_GPC['pwdtimes']),
                'pwdopen' => intval($_GPC['pwdopen']),
                'pwdown' => trim($_GPC['pwdown']),
                'pwdexit' => trim($_GPC['pwdexit']),
                'pwdexitstr' => trim($_GPC['pwdexitstr']),
                'displayorder' => intval($_GPC['displayorder']),
                'tagtitle' => $_GPC['tagtitle'],
                'settitlecolor' => intval($_GPC['settitlecolor']),
                'titlecolor' => $_GPC['titlecolor'],
                'limitdiscounttype' => intval($_GPC['limitdiscounttype']),
               
            );

            //商品使用限制
            $limitgoodcatetype =  intval($_GPC['limitgoodcatetype']);
            $limitgoodtype =  intval($_GPC['limitgoodtype']);

            $data['limitgoodcatetype'] =$limitgoodcatetype;
            $data['limitgoodtype'] =$limitgoodtype;

            if($limitgoodcatetype==1||$limitgoodcatetype==2)
            {
                $data['limitgoodcateids'] ='';
                $cates = array();
                if (is_array($_GPC['cates'])) {
                    $cates = $_GPC['cates'];
                    $data['limitgoodcateids'] = implode(',', $cates);
                }
            }
            else
            {
                $data['limitgoodcateids'] ='';
            }

            if($limitgoodtype==1||$limitgoodtype==2)
            {
                $data['limitgoodids'] ='';
                $goodids = array();
                if (is_array($_GPC['goodsid'])) {
                    $goodids = $_GPC['goodsid'];
                    $data['limitgoodids'] = implode(',',$goodids) ;
                }
            }
            else
            {
                $data['limitgoodids'] ='';
            }


            //是否开启等级限制
            $islimitlevel =  intval($_GPC['islimitlevel']);
            $data['islimitlevel'] =$islimitlevel;
            if($islimitlevel==1)
            {
                if(is_array($_GPC['limitmemberlevels']))
                {
                    $data['limitmemberlevels'] =implode(',',$_GPC['limitmemberlevels']) ;
                }else
                {
                    $data['limitmemberlevels'] ='';
                }

                if(is_array($_GPC['limitagentlevels']))
                {
                    $data['limitagentlevels'] =implode(',',$_GPC['limitagentlevels']) ;
                }
                else
                {
                    $data['limitagentlevels'] ='';
                }

                if(is_array($_GPC['limitpartnerlevels']))
                {
                    $data['limitpartnerlevels'] =implode(',',$_GPC['limitpartnerlevels']) ;
                }
                else
                {
                    $data['limitpartnerlevels'] ='';
                }

                if(is_array($_GPC['limitaagentlevels']))
                {
                    $data['limitaagentlevels'] =implode(',',$_GPC['limitaagentlevels']) ;
                }
                else
                {
                    $data['limitaagentlevels'] ='';
                }
            }else
            {
                $data['limitmemberlevels'] ='';
                $data['limitagentlevels'] ='';
                $data['limitpartnerlevels'] ='';
                $data['limitaagentlevels'] ='';
            }

            if ($data['discount'] > 10 ||$data['discount'] < 0 ){
                show_json(0,'您好,您输入的折扣范围不对! 请输入 0.1 ~ 10 之间数');
            }

            if (!empty($id)) {
                //查询关键字受否存在
                if (!empty($data['pwdkey2'])) {
                    $pwdkey2 = pdo_fetch("SELECT pwdkey2 FROM " . tablename('ewei_shop_coupon') . " WHERE id=:id and uniacid=:uniacid limit 1 ", array(':id' => $id, ':uniacid' => $_W['uniacid']));
                    if ($pwdkey2['pwdkey2'] != $data['pwdkey2']) {
                        $keyword = pdo_fetch("SELECT * FROM " . tablename('rule_keyword') . " WHERE content=:content and uniacid=:uniacid and id<>:id  limit 1 ", array(':content' => $data['pwdkey2'], ':uniacid' => $_W['uniacid'], ':id' => $id));
                        if (!empty($keyword)) {
                            show_json(0, array('url' => webUrl('sale/coupon/edit', array('id' => $id, 'tab' => str_replace("#tab_", "", $_GPC['tab']))),'message'=>'口令关键词已存在!'));
                        }
                    }
                }
                pdo_update('ewei_shop_coupon', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
                plog('sale.coupon.edit', "编辑优惠券 ID: {$id} <br/>优惠券名称: {$data['couponname']}");
            } else {
                //查询关键字受否存在
                if (!empty($data['pwdkey2'])) {
                    $keyword = pdo_fetch("SELECT * FROM " . tablename('rule_keyword') . " WHERE content=:content and uniacid=:uniacid limit 1 ", array(':content' => $data['pwdkey2'], ':uniacid' => $_W['uniacid']));
                    if (!empty($keyword)) {
                        show_json(0, array('url' => webUrl('sale/coupon/edit', array('id' => $id, 'tab' => str_replace("#tab_", "", $_GPC['tab']))),'message'=>'口令关键词已存在!'));
                    }
                }
                $data['createtime'] = time();
                pdo_insert('ewei_shop_coupon', $data);
                $id = pdo_insertid();
                plog('sale.coupon.add', "添加优惠券 ID: {$id}  <br/>优惠券名称: {$data['couponname']}");
            }

            $key = "ewei_shopv2:com:coupon:" . $id;
            $rule = pdo_fetch("select * from " . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'ewei_shopv2', ':name' => $key));
            if (!empty($data['pwdkey2'])) {
                //创建回复关键词
                if (empty($rule)) {
                    $rule_data = array(
                        'uniacid' => $_W['uniacid'],
                        'name' => $key,
                        'module' => 'ewei_shopv2',
                        'displayorder' => 0,
                        'status' => $data['pwdopen']
                    );
                    pdo_insert('rule', $rule_data);
                    $rid = pdo_insertid();

                    $keyword_data = array(
                        'uniacid' => $_W['uniacid'],
                        'rid' => $rid,
                        'module' => 'ewei_shopv2',
                        'content' => $data['pwdkey2'],
                        'type' => 1,
                        'displayorder' => 0,
                        'status' => $data['pwdopen']
                    );
                    pdo_insert('rule_keyword', $keyword_data);
                } else {
                    pdo_update('rule_keyword', array('content' => $data['pwdkey2'], 'status' => $data['pwdopen']), array('rid' => $rule['id']));
                }
            } else {
                if (!empty($rule)) {
                    //删除回复关键词
                    pdo_delete('rule_keyword', array('rid' => $rule['id']));
                    pdo_delete('rule', array('id' => $rule['id']));
                }
            }

            show_json(1, array('url' => webUrl('sale/coupon/edit', array('id' => $id, 'tab' => str_replace("#tab_", "", $_GPC['tab'])))));
        }


        $item = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_coupon') . " WHERE id =:id and uniacid=:uniacid and merchid=0 limit 1", array(':uniacid' => $_W['uniacid'], ':id' => $id));
        if (empty($item)) {
            $starttime = time();
            $endtime = strtotime(date('Y-m-d H:i:s', $starttime) . "+7 days");
        } else {
            $type = $item['coupontype'];
            $starttime = $item['timestart'];
            $endtime = $item['timeend'];

            //商品限制
            if($item['limitgoodcatetype']==1||$item['limitgoodcatetype']==2)
            {
                $cates = array();
                $cates = explode(',', $item['limitgoodcateids']);
            }
            if($item['limitgoodtype']==1||$item['limitgoodtype']==2)
            {
                if($item['limitgoodids']){
                    $goods = pdo_fetchall("SELECT id,title,thumb FROM ".tablename('ewei_shop_goods')." WHERE uniacid = :uniacid and id in ({$item['limitgoodids']}) ",array(':uniacid'=>$_W['uniacid']));
                }
            }

            //分类限制
            $limitmemberlevels =explode(",", $item['limitmemberlevels']);
            $limitagentlevels =explode(",", $item['limitagentlevels']);
            $limitpartnerlevels=explode(",", $item['limitpartnerlevels']);
            $limitaagentlevels=explode(",", $item['limitaagentlevels']);


        }
        $category = pdo_fetchall('select * from ' . tablename('ewei_shop_coupon_category') . ' where uniacid=:uniacid and merchid=0 order by id desc', array(':uniacid' => $_W['uniacid']), 'id');

        //---使用类型限制---
        $goodcategorys = m('shop')->getFullCategory(true,true);

        //会员等级
        $shop = $_W['shopset']['shop'];
        $levels = m('member')->getLevels();

        //分销商限制
        $hascommission = false;
        $plugin_com = p('commission');
        if ($plugin_com) {
            $plugin_com_set = $plugin_com->getSet();
            $hascommission = !empty($plugin_com_set['level']);
        }

        //股东限制
        $hasglobonus = false;
        $plugin_globonus = p('globonus');
        if ($plugin_globonus) {
            $plugin_globonus_set = $plugin_globonus->getSet();
            $hasglobonus = !empty($plugin_globonus_set['open']);
        }

        //区域代理限制
        $hasabonus = false;
        $plugin_abonus = p('abonus');
        if ($plugin_abonus) {
            $plugin_abonus_set = $plugin_abonus->getSet();
            $hasabonus = !empty($plugin_abonus_set['open']);
        }

        //分销商限制
        if ($hascommission) {
            $agentlevels = $plugin_com->getLevels();
        }


        //股东限制
        if ($hasglobonus) {
            $partnerlevels = $plugin_globonus->getLevels();
        }

        //区域代理列表
        if ($hasabonus) {
            $aagentlevels = $plugin_abonus->getLevels();
        }


        include $this->template();
    }

    function delete() {

        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $items = pdo_fetchall("SELECT id,couponname FROM " . tablename('ewei_shop_coupon') . " WHERE id in( $id ) and merchid=0 AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_delete('ewei_shop_coupon', array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
            pdo_delete('ewei_shop_coupon_data', array('couponid' => $item['id'], 'uniacid' => $_W['uniacid']));
            plog('sale.coupon.delete', "删除优惠券 ID: {$id}  <br/>优惠券名称: {$item['couponname']} ");
        }
        show_json(1, array('url' => webUrl('sale/coupon')));
    }

    function displayorder() {


        global $_W, $_GPC;

        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $displayorder = intval($_GPC['value']);
        $items = pdo_fetchall("SELECT id,couponname FROM " . tablename('ewei_shop_coupon') . " WHERE id in( $id ) and merchid=0 AND uniacid=" . $_W['uniacid']);
        foreach ($items as $item) {
            pdo_update('ewei_shop_coupon', array('displayorder' => $displayorder), array('id' => $id));
            plog('sale.coupon.displayorder', "修改优惠券排序 ID: {$item['id']} 名称: {$item['couponname']} 排序: {$displayorder} ");
        }

        show_json(1);
    }
    //商品查询--优惠券使用限制
    function query() {
        global $_W, $_GPC;
        $kwd = trim($_GPC['keyword']);
        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        $condition = " and uniacid=:uniacid and merchid=0";
        if (!empty($kwd)) {
            $condition.=" AND couponname like :couponname";
            $params[':couponname'] = "%{$kwd}%";
        }
        $time = time();
        $ds = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_coupon') . "  WHERE 1 {$condition} ORDER BY id asc", $params);
        foreach ($ds as &$d) {
            $d = com('coupon')->setCoupon($d, $time, false);
            $d['last'] = com('coupon')->get_last_count($d['id']);

            if ($d['last'] == -1) {
                $d['last'] = '不限';
            }
        }
        unset($d);
        include $this->template();
    }

    /*搜索商品*/
    function querygoods(){
        global $_W, $_GPC;
        $kwd = trim($_GPC['keyword']);
        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        $params[':deleted'] = 0;
        $condition=" and uniacid=:uniacid and deleted = :deleted ";
        if (!empty($kwd)) {
            $condition.=" AND `title` LIKE :keyword";
            $params[':keyword'] = "%{$kwd}%";
        }
        $ds = pdo_fetchall('SELECT id,title,thumb FROM ' . tablename('ewei_shop_goods') . " WHERE 1 {$condition} order by createtime desc", $params);

        $ds = set_medias($ds,array('thumb','share_icon'));
        if($_GPC['suggest']){
            die(json_encode(array('value'=>$ds)));
        }
        include $this->template();
    }


    /*搜索优惠券*/
    function querycoupons(){
        global $_W, $_GPC;
        $kwd = trim($_GPC['keyword']);
        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        $condition=" and uniacid=:uniacid ";
        if (!empty($kwd)) {
            $condition.=" AND `couponname` LIKE :keyword";
            $params[':keyword'] = "%{$kwd}%";
        }

        $ds = pdo_fetchall('SELECT id,couponname as title , thumb FROM ' . tablename('ewei_shop_coupon') . " WHERE 1 {$condition} order by createtime desc", $params);
        $ds = set_medias($ds, 'thumb');
        if($_GPC['suggest']){
            die(json_encode(array('value'=>$ds)));
        }
        include $this->template();
    }

    function send() {
        global $_W, $_GPC;

        $couponid = intval($_GPC['couponid']);
        $coupon = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_coupon') . ' WHERE id=:id and uniacid=:uniacid and merchid=0', array(':id' => $couponid, ':uniacid' => $_W['uniacid']));

        if ($_W['ispost']) {
            if (empty($coupon)) {
                show_json(0, '未找到优惠券!');
            }

            $class1 = intval($_GPC['send1']);

            $plog = '';
            if ($class1 == 1) {

                $openids = $_GPC['send_openid'];
                $plog = "发放优惠券 ID: {$couponid} 方式: 指定 OPENID 人数: " . count($openids);
            } elseif ($class1 == 2) {
                $where = '';
                if (!empty($_GPC['send_level'])) {
                    $where.= " and level =" . intval($_GPC['send_level']);
                }
                $members = pdo_fetchall("SELECT openid FROM " . tablename('ewei_shop_member') . " WHERE uniacid = '{$_W['uniacid']}'" . $where, array(), 'openid');
                if (!empty($_GPC['send_level'])) {
                    $levelname = pdo_fetchcolumn('select levelname from ' . tablename('ewei_shop_member_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_level']));
                } else {
                    $levelname = "全部";
                }
                $openids = array_keys($members);
                $plog = "发放优惠券 ID: {$couponid} 方式: 等级-{$levelname} 人数: " . count($members);
            } elseif ($class1 == 3) {
                $where = '';
                if (!empty($_GPC['send_group'])) {
                    $where.= " and groupid =" . intval($_GPC['send_group']);
                }

                $members = pdo_fetchall("SELECT openid FROM " . tablename('ewei_shop_member') . " WHERE uniacid = '{$_W['uniacid']}'" . $where, array(), 'openid');
                if (!empty($_GPC['send_group'])) {
                    $groupname = pdo_fetchcolumn('select groupname from ' . tablename('ewei_shop_member_group') . ' where id=:id limit 1', array(':id' => $_GPC['send_group']));
                } else {
                    $groupname = "全部分组";
                }
                $openids = array_keys($members);
                $plog = "发放优惠券 ID: {$couponid}  方式: 分组-{$groupname} 人数: " . count($members);
            } elseif ($class1 == 4) {
                $where = '';
                $members = pdo_fetchall("SELECT openid FROM " . tablename('ewei_shop_member') . " WHERE uniacid = '{$_W['uniacid']}'" . $where, array(), 'openid');
                $openids = array_keys($members);
                $plog = "发放优惠券 ID: {$couponid}  方式: 全部会员 人数: " . count($members);
            } elseif ($class1 == 5) {
                $where = '';
                if (!empty($_GPC['send_agentlevel'])||$_GPC['send_partnerlevels']==='0') {
                    $where.= " and agentlevel =" . intval($_GPC['send_agentlevel']);
                }
                $members = pdo_fetchall("SELECT openid FROM " . tablename('ewei_shop_member') . " WHERE uniacid = '{$_W['uniacid']}' and isagent=1 and status=1 " . $where, array(), 'openid');
                if ($_GPC['send_agentlevel'] != '') {
                    $levelname = pdo_fetchcolumn('select levelname from ' . tablename('ewei_shop_commission_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_agentlevel']));
                } else {
                    $levelname = "全部";
                }

                $openids = array_keys($members);
                $plog = "发放优惠券 ID: {$couponid}  方式: 分销商-{$levelname} 人数: " . count($members);
            } elseif ($class1 == 6) {

                $where = '';
                if (!empty($_GPC['send_partnerlevels'])||$_GPC['send_partnerlevels']==='0') {
                    $where.= " and partnerlevel =" . intval($_GPC['send_partnerlevels']);
                }
                $members = pdo_fetchall("SELECT openid FROM " . tablename('ewei_shop_member') . " WHERE uniacid = '{$_W['uniacid']}' and ispartner=1 and partnerstatus=1 " . $where, array(), 'openid');
                if ($_GPC['send_partnerlevels'] != '') {
                    $levelname = pdo_fetchcolumn('select levelname from ' . tablename('ewei_shop_globonus_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_partnerlevels']));
                } else {
                    $levelname = "全部";
                }

                $openids = array_keys($members);
                $plog = "发放优惠券 ID: {$couponid}  方式: 股东-{$levelname} 人数: " . count($members);
            }elseif ($class1 == 7) {
                $where = '';
                if (!empty($_GPC['send_aagentlevels'])||$_GPC['send_partnerlevels']==='0') {
                    $where.= " and aagentlevel =" . intval($_GPC['send_aagentlevels']);
                }
                $members = pdo_fetchall("SELECT openid FROM " . tablename('ewei_shop_member') . " WHERE uniacid = '{$_W['uniacid']}' and isaagent=1 and aagentstatus=1 " . $where, array(), 'openid');
                if ($_GPC['send_aagentlevels'] != '') {
                    $levelname = pdo_fetchcolumn('select levelname from ' . tablename('ewei_shop_abonus_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_aagentlevels']));
                } else {
                    $levelname = "全部";
                }

                $openids = array_keys($members);
                $plog = "发放优惠券 ID: {$couponid}  方式: 区域代理-{$levelname} 人数: " . count($members);
            }

            $mopenids = array();
            foreach ($openids as $openid) {
                $mopenids[] = "'" . str_replace("'", "''", $openid) . "'";
            }
            if (empty($mopenids)) {
                show_json(0, '未找到发送的会员!');
            }
            $members = pdo_fetchall('select id,openid,nickname from ' . tablename('ewei_shop_member') . ' where openid in (' . implode(',', $mopenids) . ") and uniacid={$_W['uniacid']}");
            if (empty($members)) {
                show_json(0, '未找到发送的会员!');
            }

            if ($coupon['total'] != -1) {
                //判断剩余数量
                $last = com('coupon')->get_last_count($couponid);

                if ($last <= 0) {
                    show_json(0, '优惠券数量不足,无法发放!');
                }
                $need = count($members) - $last;
                if ($need > 0) {
                    show_json(0, "优惠券数量不足,请补充 {$need} 张优惠券才能发放!");
                }
            }

            //更新推送信息
            $upgrade = array(
                'resptitle' => trim($_GPC['send_title']),
                'respthumb' => trim($_GPC['send_thumb']),
                'respdesc' => trim($_GPC['send_desc']),
                'respurl' => trim($_GPC['send_url']),
            );
            pdo_update('ewei_shop_coupon', $upgrade, array('id' => $coupon['id']));

            $send_total = intval($_GPC['send_total']);
            $send_total <= 0 && $send_total = 1;
            $account = m('common')->getAccount();
            //$set = $_W['shopset']['coupon'];
            $time = time();
            foreach ($members as $m) {
                for ($i = 1; $i <= $send_total; $i++) {

                    //增加优惠券日志
                    $log = array(
                        'uniacid' => $_W['uniacid'],
                        'merchid' => $coupon['merchid'],
                        'openid' => $m['openid'],
                        'logno' => m('common')->createNO('coupon_log', 'logno', 'CC'),
                        'couponid' => $couponid,
                        'status' => 1,
                        'paystatus' => -1,
                        'creditstatus' => -1,
                        'createtime' => $time,
                        'getfrom' => 0
                    );
                    pdo_insert('ewei_shop_coupon_log', $log);
                    $logid = pdo_insertid();

                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'merchid' => $coupon['merchid'],
                        'openid' => $m['openid'],
                        'couponid' => $couponid,
                        'gettype' => 0,
                        'gettime' => $time,
                        'senduid' => $_W['uid']
                    );
                    pdo_insert('ewei_shop_coupon_data', $data);
                }
                com('coupon')->sendMessage($coupon, $send_total, $m,  $account);
            }
			plog('sale.coupon.send', $plog);
            show_json(1, array('message' => '优惠券发放成功!', 'url' => webUrl('sale/coupon')));
        }

        $list = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_member_level') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY level asc");
        $list2 = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_member_group') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY id asc");
        $coupons = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_coupon') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY id asc");


        //是否开启分销商
        $hascommission = false;
        $plugin_com = p('commission');
        if ($plugin_com) {
            $plugin_com_set = $plugin_com->getSet();
            $hascommission = !empty($plugin_com_set['level']);
        }

        //是否开启股东
        $hasglobonus = false;
        $plugin_globonus = p('globonus');
        if ($plugin_globonus) {
            $plugin_globonus_set = $plugin_globonus->getSet();
            $hasglobonus = !empty($plugin_globonus_set['open']);
        }

        //是否开启区域代理
        $hasabonus = false;
        $plugin_abonus = p('abonus');
        if ($plugin_abonus) {
            $plugin_abonus_set = $plugin_abonus->getSet();
            $hasabonus = !empty($plugin_abonus_set['open']);
        }

        //分销商列表
        if ($hascommission) {
            $list3 = $plugin_com->getLevels();
        }

        //股东列表
        if ($hasglobonus) {
            $list4 = $plugin_globonus->getLevels();
        }

        //区域代理列表
        if ($hasabonus) {
            $list5 = $plugin_abonus->getLevels();
        }

        load()->func('tpl');


        include $this->template();
    }

    function set() {
        global $_W, $_GPC;


        if ($_W['ispost']) {

            $data = is_array($_GPC['data']) ? $_GPC['data'] : array();
            $data['consumedesc'] = m('common')->html_images($data['consumedesc']);
            $data['rechargedesc'] = m('common')->html_images($data['rechargedesc']);
            //处理幻灯片
            $imgs = $_GPC['adv_img'];
            $urls = $_GPC['adv_url'];
            $advs = array();
            if (is_array($imgs)) {
                foreach ($imgs as $key => $img) {
                    $advs[] = array(
                        'img' => save_media($img),
                        'url' => trim($urls[$key])
                    );
                }
            }
            $data['advs'] = $advs;

            m('common')->updatePluginset(array('coupon'=>$data));

            plog('sale.coupon.set', '修改基本设置');
            show_json(1,array('url'=>webUrl('sale/coupon/set', array('tab'=>str_replace("#tab_","",$_GPC['tab'])))));
        }

        $data = m('common')->getPluginset('coupon');
        $advs = is_array($data['advs']) ? $data['advs'] : array();
        include $this->template();
    }

}
