<?php

/*
 * 人人商城V2
 * 
 * @author ewei 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class MobilePage extends Page {

    public $footer = array(); //底部
    public $followBar = false; //关注条
    protected $merch_user = array();

    public function __construct() {
        global $_W,$_GPC;

        //检测商城是否关闭
        m('shop')->checkClose();

        $preview = intval($_GPC['preview']);

        $wap = m('common')->getSysset('wap');
        if($wap['open'] && !is_weixin() && empty($preview)) {
            if ($this instanceof MobileLoginPage || $this instanceof PluginMobileLoginPage) {
                if (empty($_W['openid'])) {
                    $_W['openid'] = m('account')->checkLogin();
                }
            }else{
                $_W['openid'] = m('account')->checkOpenid();
            }
        }else{

            if ($preview&&!is_weixin()) {
                $_W['openid'] = "o6tC-wmZovDTswVba3Kg1oAV_dd0";
            }
            if (EWEI_SHOPV2_DEBUG)
            {
                $_W['openid'] = "o6tC-wmZovDTswVba3Kg1oAV_dd0";
            }
        }
        //var_dump($_W['openid']);
        //获取当前用户
        $member = m('member')->checkMember();
        $_W['mid'] = !empty($member) ? $member['id'] : '';
        $_W['mopenid'] = !empty($member) ? $member['openid'] : '';
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if (!empty($_GPC['merchid']) && ($merch_plugin && $merch_data['is_openmerch']))
        {
            $this->merch_user = pdo_fetch("select * from ".tablename('ewei_shop_merch_user')." where id=:id limit 1",array(':id'=>intval($_GPC['merchid'])));
        }
    }
    public function followBar($diypage=false, $merch=false) {
        global $_W, $_GPC;

        if(is_h5app() || !is_weixin()){
            return;
        }

        $openid = $_W['openid'];
        $followed = m('user')->followed($openid);
        $mid = intval($_GPC['mid']);
        $memberid = m('member')->getMid();

        if(p('diypage')) {
            if($merch && p('merch')){
                $diypagedata = p('merch')->getSet('diypage', $merch);
            }else{
                $diypagedata = m('common')->getPluginset('diypage');
            }
            $diyfollowbar = $diypagedata['followbar'];
        }

        if($diypage){
            $diyfollowbar['params']['isopen'] = 1;
        }
        
        @session_start();
        if ((!$followed && $memberid!=$mid) || (!empty($diyfollowbar['params']['showtype']) && !empty($diyfollowbar['params']['isopen']))) {
            $set = $_W['shopset'];
            $followbar = array(
                'followurl' => $set['share']['followurl'],
                'shoplogo'=>tomedia($set['shop']['logo']),
                'shopname'=>$set['shop']['name'],
                'qrcode'=>tomedia($set['share']['followqrcode']),
                'share_member'=>false
            );
            $friend = false;
            if (!empty($mid) && $memberid!=$mid) {
                if (!empty($_SESSION[EWEI_SHOPV2_PREFIX . '_shareid']) && $_SESSION[EWEI_SHOPV2_PREFIX . '_shareid'] == $mid) {
                    $mid = $_SESSION[EWEI_SHOPV2_PREFIX . '_shareid'];
                }
                $member = m('member')->getMember($mid);
                if (!empty($member)) {
                    $_SESSION[EWEI_SHOP_PREFIX . '_shareid'] = $mid;
                    $friend = true;
                    $followbar['share_member'] = array('id'=>$member['id'], 'nickname'=>$member['nickname'], 'realname'=>$member['realname'], 'avatar'=>$member['avatar']);
                }
            }

            $showdiyfollowbar = false;
            if(p('diypage')){
                if((!empty($diyfollowbar) && !empty($diyfollowbar['params']['isopen'])) || (!empty($diyfollowbar) && $diypage)){
                    $showdiyfollowbar = true;
                    if(!empty($followbar['share_member'])){
                        if(!empty($diyfollowbar['params']['sharetext'])){
                            $touser = m('member')->getMember($memberid);
                            $diyfollowbar['text'] = str_replace('[商城名称]', '<span style="color:'.$diyfollowbar['style']['highlight'].';">'.$set['shop']['name'].'</span>', $diyfollowbar['params']['sharetext']);
                            $diyfollowbar['text'] = str_replace('[邀请人]', '<span style="color:'.$diyfollowbar['style']['highlight'].';">'.$followbar['share_member']['nickname'].'</span>', $diyfollowbar['text']);
                            $diyfollowbar['text'] = str_replace('[访问者]', '<span style="color:'.$diyfollowbar['style']['highlight'].';">'.$touser['nickname'].'</span>', $diyfollowbar['text']);
                        }else{
                            $diyfollowbar['text'] = '来自好友<span class="text-danger">'.$followbar['share_member']['nickname'].'</span>的推荐<br>'.'关注公众号，享专属服务';
                        }
                    }else{
                        if(!empty($diyfollowbar['params']['defaulttext'])){
                            $diyfollowbar['text'] = str_replace('[商城名称]', '<span style="color:'.$diyfollowbar['style']['highlight'].';">'.$set['shop']['name'].'</span>',$diyfollowbar['params']['defaulttext']);
                        }else{
                            $diyfollowbar['text'] = '欢迎进入<span class="text-danger">'.$set['shop']['name'].'</span><br>'.'关注公众号，享专属服务';
                        }
                    }
                    $diyfollowbar['text'] = nl2br($diyfollowbar['text']);
                    $diyfollowbar['logo'] = tomedia($set['shop']['logo']);
                    if($diyfollowbar['params']['icontype']==1 && !empty($followbar['share_member'])){
                        $diyfollowbar['logo'] = tomedia($followbar['share_member']['avatar']);
                    }
                    elseif ($diyfollowbar['params']['icontype']==3 && !empty($diyfollowbar['params']['iconurl'])){
                        $diyfollowbar['logo'] = tomedia($diyfollowbar['params']['iconurl']);
                    }
                    if(empty($diyfollowbar['params']['btnclick'])){
                        if(empty($diyfollowbar['params']['btnlinktype'])){
                            $diyfollowbar['link'] = $set['share']['followurl'];
                        }else{
                            $diyfollowbar['link'] = $diyfollowbar['params']['btnlink'];
                        }
                    }else{
                        if(empty($diyfollowbar['params']['qrcodetype'])){
                            $diyfollowbar['qrcode'] = tomedia($set['share']['followqrcode']);
                        }else{
                            $diyfollowbar['qrcode'] = tomedia($diyfollowbar['params']['qrcodeurl']);
                        }
                    }
                }
            }

            if($showdiyfollowbar){
                include $this->template('diypage/followbar');
            }else{
                include $this->template('_followbar');
            }
        }
    }

    public function MemberBar($diypage=false, $merch=false) {
        global $_W, $_GPC;

        if(is_h5app() || !is_weixin()){
            return;
        }
        $mid = intval($_GPC['mid']);

        $cmember_plugin = p('cmember');
        if (!$cmember_plugin) {
            return;
        }

        $openid = $_W['openid'];
        $followed = m('user')->followed($openid);
        if (!$followed) {
            return;
        }
        $check = $cmember_plugin->checkMember($openid);
        if (!empty($check)) {
            return;
        }

        $data = m('common')->getPluginset('commission');
        if (!empty($data['become_goodsid'])) {
            $goods = pdo_fetch('select id,title,thumb from ' . tablename('ewei_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1 ', array(':id' => $data['become_goodsid'], ':uniacid' => $_W['uniacid']));
        } else {
            return;
        }
        $buy_member_url = mobileUrl('goods/detail', array('id' => $goods['id'], 'mid' => $mid));

        include $this->template('cmember/_memberbar');
    }

    public function footerMenus($diymenuid=null, $p=null) {
        global $_W, $_GPC;

        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
        $cartcount = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_member_cart') . ' where uniacid=:uniacid and openid=:openid and deleted=0 and selected =1', $params);

        $commission = array();
        if(p('commission') && intval($_W['shopset']['commission']['level']>0)){
            $member = m('member')->getMember($_W['openid']);
            if(!$member['agentblack']) {
                if ($member['isagent'] == 1 && $member['status'] == 1) {
                    $commission = array(
                        'url' => mobileUrl('commission'),
                        'text' => empty($_W['shopset']['commission']['texts']['center']) ? '分销中心' : $_W['shopset']['commission']['texts']['center']
                    );
                } else {
                    $commission = array(
                        'url' => mobileUrl('commission/register'),
                        'text' => empty($_W['shopset']['commission']['texts']['become']) ? '成为分销商' : $_W['shopset']['commission']['texts']['become']
                    );
                }
            }
        }

            $showdiymenu = false;
            $routes =explode(".",$_W['routes']);
            $controller = $routes[0];

            if($controller=='member' || $controller=='cart' || $controller=='order' || $controller=='goods') {
                $controller = 'shop';
            }

            if(empty($diymenuid)) {
                $diypagedata = m('common')->getPluginset('diypage');
                if(!empty($diypagedata['menu'])) {
                    $pageid = !empty($controller) ? $controller : 'shop';
                    $pageid = $pageid=="index" ? "shop" : $pageid;

                    if($pageid=='merch' && !empty($_GPC['merchid']) && p('merch')){
                        $merchdata = p('merch')->getSet('diypage', $_GPC['merchid']);
                        if(!empty($merchdata['menu'])){
                            $diymenuid = $merchdata['menu']['shop'];
                            if(!is_weixin() || is_h5app()){
                                $diymenuid = $merchdata['menu']['shop_wap'];
                            }
                        }
                    }else{
                        $diymenuid = $diypagedata['menu'][$pageid];
                        if(!is_weixin() || is_h5app()){
                            $diymenuid = $diypagedata['menu'][$pageid.'_wap'];
                        }
                    }

                }
            }

            if(!empty($diymenuid)) {
                $menu = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_diypage_menu') . " WHERE id=:id and uniacid=:uniacid limit 1 ", array(':id'=>$diymenuid, ':uniacid'=>$_W['uniacid']));
                if(!empty($menu)) {
                    $menu = $menu['data'];
                    $menu = base64_decode($menu);
                    $diymenu = json_decode($menu, true);
                    $showdiymenu = true;
                }
            }

            if($showdiymenu){
                include $this->template('diypage/menu');
            }else{
                if($controller=='commission' && $routes[1]!='myshop'){
                    include $this->template('commission/_menu');
                } else if($controller=='creditshop'){
                    include $this->template('creditshop/_menu');
                } else if($controller=='groups'){
                    include $this->template('groups/_groups_footer');
                } else if($controller=='merch'){
                    include $this->template('merch/_menu');
                } else if($controller=='mr'){
                    include $this->template('mr/_menu');
                } else if($controller=='newmr'){
                    include $this->template('newmr/_menu');
                }elseif($controller=='sign'){
                    include $this->template('sign/_menu');
                }elseif($controller=='sns'){
                    include $this->template('sns/_menu');
                }elseif($controller=='seckill'){
                    include $this->template('seckill/_menu');
                }else{
                    include $this->template('_menu');
                }

            }

    }
    public function shopShare() {
        global $_W,$_GPC;

        $trigger = false;
        if (empty($_W['shopshare'])) {

            $set = $_W['shopset'];
            $_W['shopshare'] = array(
                'title' => empty($set['share']['title']) ? $set['shop']['name'] : $set['share']['title'],
                'imgUrl' => empty($set['share']['icon']) ? tomedia($set['shop']['logo']) : tomedia($set['share']['icon']),
                'desc' => empty($set['share']['desc']) ? $set['shop']['description'] : $set['share']['desc'],
                'link' => empty($set['share']['url']) ? mobileUrl('',null,true) : $set['share']['url']
            );
            //var_dump(mobileUrl('',null,true));
            $plugin_commission = p('commission');
            if ($plugin_commission) {
                $set = $plugin_commission->getSet();
                if (!empty($set['level'])) {
                    $openid = $_W['openid'];
                    $member = m('member')->getMember($openid);
                    if (!empty($member) && $member['status'] == 1 && $member['isagent'] == 1) {

                        if (empty($set['closemyshop'])) {
                            $myshop = $plugin_commission->getShop($member['id']);
                            $_W['shopshare'] = array(
                                'title' => $myshop['name'],
                                'imgUrl' => tomedia($myshop['logo']),
                                'desc' => $myshop['desc'],
                                'link' =>mobileUrl('commission/myshop',array('mid'=>$member['id']),true)
                            );
                        } else {
                            $_W['shopshare']['link'] = empty($_W['shopset']['share']['url']) ? mobileUrl('', array('mid' => $member['id']),true) : $_W['shopset']['share']['url'];
                        }

                        if (empty($set['become_reg']) && ( empty($member['realname']) || empty($member['mobile']))) {
                            $trigger = true;
                        }
                    } else if (!empty($_GPC['mid'])) {
                        $m = m('member')->getMember($_GPC['mid']);
                        if (!empty($m) && $m['status'] == 1 && $m['isagent'] == 1) {
                            if (empty($set['closemyshop'])) {
                                $myshop = $plugin_commission->getShop($_GPC['mid']);
                                $_W['shopshare'] = array(
                                    'title' => $myshop['name'],
                                    'imgUrl' => tomedia($myshop['logo']),
                                    'desc' => $myshop['desc'],
                                    'link' => mobileUrl('commission/myshop',array('mid'=>$member['id']),true)
                                );
                            } else {
                                $_W['shopshare']['link'] = empty($_W['shopset']['share']['url']) ? mobileUrl('', array('mid' => $_GPC['mid']),true) : $_W['shopset']['share']['url'];
                            }
                        } else {
                            $_W['shopshare']['link'] = empty($_W['shopset']['share']['url']) ? mobileUrl('', array('mid' => $_GPC['mid']),true) : $_W['shopset']['share']['url'];
                        }
                    }
                }
            }
        }
        return $trigger;
    }
    public function get_share_data(){
            
    }
    public function diyPage($type) {
        global $_W, $_GPC;

        if(empty($type) || !p('diypage')) {
            return false;
        }

        $merch = intval($_GPC['merchid']);
        if($merch && $type!='member' && $type!='commission'){
            if(!p('merch')){
                return false;
            }
            $diypagedata = p('merch')->getSet('diypage', $merch);
        }else{
            $diypagedata = m('common')->getPluginset('diypage');
        }

        if(!empty($diypagedata)) {
            $diypageid = $diypagedata['page'][$type];
            if(!empty($diypageid)) {
                $page = p('diypage')->getPage($diypageid, true);
                if(!empty($page)) {
                    p('diypage')->setShare($page);
                    $diyitems = $page['data']['items'];
                    include $this->template('diypage');
                    exit;
                }
            }
        }
    }

    public function diyLayer($v=false, $diy=false, $merch=false){
        global $_W, $_GPC;

        if(!p('diypage') || $diy) {
            return;
        }

        if($merch){
            if(!p('merch')){
                return false;
            }
            $diypagedata = p('merch')->getSet('diypage', $merch);
        }else{
            $diypagedata = m('common')->getPluginset('diypage');
        }

        if(!empty($diypagedata)) {
            $diylayer = $diypagedata['layer'];
            if(!$diylayer['params']['isopen'] && $v){
                return;
            }
            include $this->template('diypage/layer');
        }
        return;
    }

    public function diyGotop($v=false, $diy=false, $merch=false){
        global $_W, $_GPC;

        if(!p('diypage') || $diy) {
            return;
        }

        if($merch){
            if(!p('merch')){
                return false;
            }
            $diypagedata = p('merch')->getSet('diypage', $merch);
        }else{
            $diypagedata = m('common')->getPluginset('diypage');
        }

        if(!empty($diypagedata)) {
            $diygotop = $diypagedata['gotop'];
            if(!$diygotop['params']['isopen'] && $v){
                return;
            }
            include $this->template('diypage/gotop');
        }
        return;
    }
    public function wapQrcode(){

        global $_W,$_GPC;
        $currenturl = '';
        if(!is_mobile()){
            $currenturl = "{$_W['siteroot']}app/index.php?{$_SERVER['QUERY_STRING']}";
        }
        $shop = m('common')->getSysset('shop');
        $shopname = $shop['name'];
        include $this->template('_wapqrcode');
    }
}
	