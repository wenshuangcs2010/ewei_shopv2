<?php
error_reporting(E_ALL & ~E_NOTICE);

if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Index_EweiShopV2Page extends PluginMobileLoginPage {

    function main() {
        global $_W,$_GPC;
        $myMid = (int)m('member')->getMid();
        $mid = (int)$_GPC['mid'];
        if ($mid!==$myMid){
            echo "<script>window.location.href='".mobileUrl('bargain',array('mid'=>$myMid))."'</script>";die();
        }
        /*JSSDK分享设置*/
        $share_res = pdo_fetch("SELECT * FROM ".tablename('ewei_shop_bargain_account') ."WHERE id = :id",array(':id'=>$_W['uniacid']));
        if (!empty($share_res['mall_title'])) {
            $share['title'] = $share_res['mall_title'];
        }else{
            $share['title'] = $share_res['mall_name'];
        }
        if (!empty($share_res['mall_content'])) {
            $share['content'] = $share_res['mall_content'];
        }else{
            $share['content'] = $share_res['mall_name'];
        }
        if (!empty($share_res['mall_logo'])) {
            $share['logo'] = tomedia($share_res['mall_logo']);
        }else{
            $share['logo'] = tomedia("images/share_logo.jpg");
        }
        $mid = m('member')->getMid();
        $_W['shopshare'] = array(
            'title' => $share['title'] ,
            'desc' => $share['content'] ,
            'link' => mobileUrl('bargain',array('mid'=>$mid),true) ,
            'imgUrl' => $share['logo']
        );

        if ($_W['ispost']){
            $keywords = '%'.$_GPC['keywords'].'%';
            /*筛选出正在砍价中的商品*/
            $res2 = pdo_fetchall("SELECT * FROM". tablename('ewei_shop_bargain_goods') ."WHERE account_id = :account_id AND status ='0' ORDER BY id DESC",array(':account_id'=>$_W['uniacid']));

            foreach($res2 as $i=>$value) {
                if (strtotime($res2[$i]['start_time'])>time()) {
                    continue;
                }elseif (strtotime($res2[$i]['end_time'])<time()) {
                    continue;
                }
                $res[$i] = $res2[$i];
                $res3 = pdo_fetch("SELECT * FROM". tablename('ewei_shop_goods') ."WHERE id = :id AND deleted = '0' AND status = '1' AND title LIKE :title ",array (':id'=>$res[$i]['goods_id'],':title'=>$keywords));
                if (empty($res3)){
                    unset($res[$i]);
                    continue;
                }
                $res[$i]['title'] = $res3['title'];
                $res[$i]['title2'] = $res3['subtitle'];
                $res[$i]['images'] = $res3['thumb'];
                $res[$i]['start_price'] = $res3['marketprice'];
            }

            include $this->template();
            return;
        }

        /*筛选出正在砍价中的商品*/
        $res2 = pdo_fetchall("SELECT * FROM". tablename('ewei_shop_bargain_goods') ."WHERE account_id = :account_id AND status ='0' ORDER BY id DESC",array(':account_id'=>$_W['uniacid']));

        foreach($res2 as $i=>$value) {
            if (strtotime($res2[$i]['start_time'])>time()) {
                continue;
            }elseif (strtotime($res2[$i]['end_time'])<time()) {
                continue;
            }
            $res[$i] = $res2[$i];
            $res3 = pdo_fetch("SELECT * FROM". tablename('ewei_shop_goods') ."WHERE id = :id AND deleted = '0' AND status = '1'",array(':id'=>$res[$i]['goods_id']));
            if (empty($res3)){
                unset($res[$i]);
                continue;
            }
            $res[$i]['title'] = $res3['title'];
            $res[$i]['title2'] = $res3['subtitle'];
            $res[$i]['images'] = $res3['thumb'];
            if(substr($res3['marketprice'],-3,3) == '.00'){
                $res3['marketprice'] = intval($res3['marketprice']);
            }
            if(substr($res[$i]['end_price'],-3,3) == '.00'){
                $res[$i]['end_price'] = intval($res[$i]['end_price']);
            }
            $res[$i]['start_price'] = $res3['marketprice'];
        }

        include $this->template();
    }


    function purchase(){
        global $_W,$_GPC;
        $myMid = (int)m('member')->getMid();
        $mid = (int)$_GPC['mid'];
        if ($mid!==$myMid){
            echo "<script>window.location.href='".mobileUrl('bargain',array('mid'=>$myMid))."'</script>";die();
        }
        /*JSSDK分享设置*/
        $share_res = pdo_fetch("SELECT * FROM". tablename('ewei_shop_bargain_account') ."WHERE id = :id",array(':id'=>$_W['uniacid']));
        if (!empty($share_res['mall_title'])) {
            $share['title'] = $share_res['mall_title'];
        }else{
            $share['title'] = $share_res['mall_name'];
        }
        if (!empty($share_res['mall_content'])) {
            $share['content'] = $share_res['mall_content'];
        }else{
            $share['content'] = $share_res['mall_name'];
        }
        if (!empty($share_res['mall_logo'])) {
            $share['logo'] = tomedia($share_res['mall_logo']);
        }else{
            $share['logo'] = tomedia("images/share_logo.jpg");
        }
        $mid = m('member')->getMid();
        $_W['shopshare'] = array(
            'title' => $share['title'] ,
            'desc' => $share['content'] ,
            'link' => mobileUrl('bargain',array('mid'=>$mid),true) ,
            'imgUrl' => $share['logo']
        );
        $act = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_actor') ." WHERE openid= :openid AND account_id = :account_id AND status = '1' ORDER BY id DESC",array(':openid'=>$_W['openid'],':account_id'=>$_W['uniacid']));//所有我发起的活动
        //筛选出已参加的活动
        for ($i=0; $i < count($act); $i++) {
            $goods[$i] = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_goods') ." WHERE id=:id",array(':id'=>$act[$i]['goods_id']));


            $ewei_detail = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_goods') ." WHERE id = :id AND status = '1'",array(':id'=>$goods[$i][0]['goods_id']));
            if (empty($ewei_detail)){
                unset($goods[$i]);
                continue;
            }

            $goods[$i][0]['title'] = $ewei_detail['title'];
            $goods[$i][0]['title2'] = $ewei_detail['subtitle'];
            $goods[$i][0]['start_price'] = $ewei_detail['marketprice'];
            $goods[$i][0]['sold'] = $ewei_detail['sales'];
            $goods[$i][0]['stock'] = $ewei_detail['total'];
            $goods[$i][0]['images'] = json_encode(array($ewei_detail['thumb']));
            $goods[$i][0]['content'] = $ewei_detail['content'];
            $goods[$i][0]['actor_id'] = $act[$i]['id'];
            $goods[$i][0]['now_price'] = $act[$i]['now_price'];
        }

        include $this->template();
    }

    function act(){
        global $_W,$_GPC;
        $myMid = (int)m('member')->getMid();
        $mid = (int)$_GPC['mid'];
        if ($mid!==$myMid){
            echo "<script>window.location.href='".mobileUrl('bargain',array('mid'=>$myMid))."'</script>";die();
        }
        /*JSSDK分享设置*/
        $share_res = pdo_fetch("SELECT * FROM". tablename('ewei_shop_bargain_account') ."WHERE id = :id",array(':id'=>$_W['uniacid']));
        if (!empty($share_res['mall_title'])) {
            $share['title'] = $share_res['mall_title'];
        }else{
            $share['title'] = $share_res['mall_name'];
        }
        if (!empty($share_res['mall_content'])) {
            $share['content'] = $share_res['mall_content'];
        }else{
            $share['content'] = $share_res['mall_name'];
        }
        if (!empty($share_res['mall_logo'])) {
            $share['logo'] = tomedia($share_res['mall_logo']);
        }else{
            $share['logo'] = tomedia("images/share_logo.jpg");
        }
        $mid = m('member')->getMid();
        $_W['shopshare'] = array(
            'title' => $share['title'] ,
            'desc' => $share['content'] ,
            'link' => mobileUrl('bargain',array('mid'=>$mid),true) ,
            'imgUrl' => $share['logo']
        );
        $act = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_actor') ." WHERE openid= :openid AND account_id = :account_id AND status = '0' ORDER BY id DESC",array(':openid'=>$_W['openid'],':account_id'=>$_W['uniacid']));//所有我发起的活动
        //筛选出已参加的活动
        for ($i=0; $i < count($act); $i++) {
            $goods[$i] = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_goods') ." WHERE id=:id AND status = '0'",array(':id'=>$act[$i]['goods_id']));

            $ewei_detail = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_goods') ." WHERE id = :id AND status = '1' AND deleted='0'",array(':id'=>$goods[$i][0]['goods_id']));
            if (empty($ewei_detail)){
                unset($goods[$i]);
                continue;
            }
            $goods[$i][0]['title'] = $ewei_detail['title'];
            $goods[$i][0]['title2'] = $ewei_detail['subtitle'];
            $goods[$i][0]['start_price'] = $ewei_detail['marketprice'];
            $goods[$i][0]['sold'] = $ewei_detail['sales'];
            $goods[$i][0]['stock'] = $ewei_detail['total'];
            $goods[$i][0]['images'] = json_encode(array($ewei_detail['thumb']));
            $goods[$i][0]['content'] = $ewei_detail['content'];
            $goods[$i][0]['actor_id'] = $act[$i]['id'];
            $goods[$i][0]['now_price'] = $act[$i]['now_price'];
            if(substr($goods[$i][0]['end_price'],-3,3) == '.00'){
                $goods[$i][0]['end_price'] = intval($goods[$i][0]['end_price']);
            }
            if(substr($goods[$i][0]['start_price'],-3,3) == '.00'){
                $goods[$i][0]['start_price'] = intval($goods[$i][0]['start_price']);
            }
            if(substr($goods[$i][0]['now_price'],-3,3) == '.00'){
                $goods[$i][0]['now_price'] = intval($goods[$i][0]['now_price']);
            }

            if (((strtotime($act[$i]['created_time'])+($goods[$i][0]['time_limit']*3600)) <time()) && $goods[$i][0]['time_limit'] != '0'){
                $goods[$i][0]['label_swi'] = 1;//超过砍价限时
            }
            if ($goods[$i][0]['now_price'] == $goods[$i][0]['end_price']){
                $goods[$i][0]['label_swi'] = 3;//已经到低价
            }
            if (strtotime($goods[$i][0]['end_time'])<time()){
                $goods[$i][0]['label_swi'] = 2;//活动已经结束
            }
        }

        include $this->template();
    }

    function detail(){//砍价商品详情
        global $_W, $_GPC;
        $id =  (int)$_GPC["id"];//商品id
        $myMid = (int)m('member')->getMid();
        $mid = (int)$_GPC['mid'];
        if ($mid!==$myMid){
            echo "<script>window.location.href='".mobileUrl('bargain/detail',array('mid'=>$myMid,'id'=>$id))."'</script>";die();
        }

        $res = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_bargain_goods') ." WHERE id = :id AND status='0'",array(':id'=>$id));
        if(!$res){//没有这个商品
            include $this->template('bargain/lost');
            return;
        }
        $ewei_detail = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_goods') ." WHERE id = :id AND status = '1' AND deleted = '0'",array(':id'=>$res['goods_id']));
        if(!$ewei_detail){//没有这个商品
            include $this->template('bargain/lost');
            return;
        }
        $res['title'] = $ewei_detail['title'];
        $res['title2'] = $ewei_detail['subtitle'];
        if(substr($ewei_detail['marketprice'],-3,3) == '.00'){
            $ewei_detail['marketprice'] = intval($ewei_detail['marketprice']);
        }
        if(substr($res['end_price'],-3,3) == '.00'){
            $res['end_price'] = intval($res['end_price']);
        }
        $res['start_price'] = $ewei_detail['marketprice'];

        $res['sold'] = $ewei_detail['sales'];
        $res['stock'] = $ewei_detail['total'];
        $res['images'] = unserialize($ewei_detail['thumb_url']);
        $res['content'] = $ewei_detail['content'];
        //如果已经库存不足||时间已过||时间未到  不能购买
        if ($res['type']==1 && $res['mode'] == 1) {
            $m_swi = 1;//不能直接购买
        }else{$m_swi=0;}

        $start_time = strtotime($res['start_time']) - time();
        $type = $res['type'];
        $time1 = strtotime($res['end_time']);
        $year = substr($res['end_time'],0,4);
        $month = substr($res['end_time'],5,2);
        $day = substr($res['end_time'],8,2);
        $hour = substr($res['end_time'],11,2);
        $minute = substr($res['end_time'],14,2);
        $second = substr($res['end_time'],17,2);
        $start_time = strtotime($res['start_time']) - time();
        $time3 = $time1 - time();
        $status = 3;
        $swi = 0;
        if ($start_time>0) {
            $status = '0';//代表未开始
            $swi = 1;
        }elseif ($time3 < 0) {
            $status = '1';//代表已结束
            $swi = 2;
        }elseif ($res['stock']<=0 && $swi==0) {
            $swi = 3;//代表没有库存
        }
        $res['user_set'] = urldecode($res['user_set']);
        $share_res = json_decode($res['user_set'],true);

        if (!empty($share_res['goods_title'])) {
            $share['title'] = $share_res['goods_title'];
        }else{
            $share['title'] = $res['title'];
        }
        if (!empty($share_res['goods_content'])) {
            $share['content'] = $share_res['goods_content'];
        }else{
            $share['content'] = $res['title2'];
        }
        if (!empty($share_res['goods_logo'])) {
            $share['logo'] = tomedia($share_res['goods_logo']);
        }else{
            $share['logo'] = tomedia($ewei_detail['thumb']);
        }
        $mid = m('member')->getMid();
        $_W['shopshare'] = array(
            'title' => $share['title'] ,
            'desc' => $share['content'] ,
            'link' => mobileUrl('bargain/detail',array('id'=>$id,'mid'=>$mid),true) ,
            'imgUrl' => $share['logo']
        );
        $res['custom'] = urldecode($res['custom']);
        $res['custom'] = json_decode($res['custom'],true);

        if (!empty($res['initiate'])) {
            $act_id = pdo_get('ewei_shop_bargain_actor', array('goods_id' => $id, 'openid' => $_W['openid'],'status'=>0), 'id');
            if (!empty($act_id)) {
                $act_swi = $act_id;
            }
        }
        include $this->template();
    }

    public function bargain(){//砍价
        global $_W, $_GPC;
        $isFollowed = $this->model->checkFollowed();
        $id = $_GPC['id'];
        $ajax = $_GPC['ajax'];
        $myMid = (int)m('member')->getMid();
        $mid = (int)$_GPC['mid'];
        if ($mid!==$myMid){
            echo "<script>window.location.href='".mobileUrl('bargain/bargain',array('mid'=>$myMid,'id'=>$id))."'</script>";die();
        }
        if ($id == NULL) {
            $id = 1;
        }
        $account_set = pdo_get('ewei_shop_bargain_account',array('id'=>$_W['uniacid']),array('partin','rule'));

        $res = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_bargain_actor') ." WHERE id = :id",array(':id'=>$id));
        $res2 = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_bargain_goods') ." WHERE id = :id AND status='0'",array(':id'=>$res['goods_id']));
        $ewei_detail = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_goods') ." WHERE id = :id AND status = '1' AND bargain > 0 AND deleted=0",array(':id'=>$res2['goods_id']));
        if(!$ewei_detail['id'] || !$res['id'] ||!$res2['id']){//没有这个商品
            include $this->template('bargain/lost');
            return;
        }
        $res2['title'] = $ewei_detail['title'];
        $res2['title2'] = $ewei_detail['subtitle'];
        $res2['start_price'] = $ewei_detail['marketprice'];
        if(substr($res2['start_price'],-3,3) == '.00'){
            $res2['start_price'] = intval($res2['start_price']);
        }
        if(substr($res2['end_price'],-3,3) == '.00'){
            $res2['end_price'] = intval($res2['end_price']);
        }
        $res2['sold'] = $ewei_detail['sales'];
        $res2['stock'] = $ewei_detail['total'];
        $res2['images'] = json_encode(array($ewei_detail['thumb']));
        $res2['content'] = $ewei_detail['content'];
        //如果库存不足、时间结束、时间尚未开始则不能发起砍价

        if(!$res || !$res2){
            include $this->template('bargain/lost');
            return;
        }
        if(substr($res['bargain_price'],-3,3) == '.00'){
            $res['bargain_price'] = intval($res['bargain_price']);
        }
        if(substr($res['now_price'],-3,3) == '.00'){
            $res['now_price'] = intval($res['now_price']);
        }
        //用户开关$swi
        if ($_W['openid'] === $res['openid']) {
            $swi = 111;//是发起者本人
        }else{
            $swi = 222;//不是发起者本人
        }

        $time2 = strtotime($res2['end_time']);
        $time3 = $time2 - time();
        $start_time = strtotime($res2['start_time']) - time();
        $type = $res['type'];
        if ($res2['time_limit']>0){
            $showtime = strtotime($res['created_time'])+$res2['time_limit']*3600;
            $showtime = date("Y-m-d H:i:s",$showtime);
            $year = substr($showtime,0,4);
            $month = substr($showtime,5,2);
            $day = substr($showtime,8,2);
            $hour = substr($showtime,11,2);
            $minute = substr($showtime,14,2);
            $second = substr($showtime,17,2);
        }else{
            $year = substr($res2['end_time'],0,4);
            $month = substr($res2['end_time'],5,2);
            $day = substr($res2['end_time'],8,2);
            $hour = substr($res2['end_time'],11,2);
            $minute = substr($res2['end_time'],14,2);
            $second = substr($res2['end_time'],17,2);
        }
        $status = 3;
        if ($res2['type'] == 1 && $res2['mode'] == 1 && ($res['now_price']>$res2['end_price'])) {
            $trade_swi = 4;//底价模式,没砍到底价
        }

        if ($start_time>0) {
            $status = '0';//尚未开始
        }elseif ($time3 < 0) {
            $status = '1';
            $trade_swi = 2;//已经结束
        }
        $account_set['partin'] *= -1;
        $res3 = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_record') ." WHERE actor_id = :actor_id ORDER BY id DESC LIMIT 0,10",array(':actor_id'=>$id));
        $res4 = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_actor') ." WHERE bargain_price <= '{$account_set['partin']}' AND account_id=:uniacid ORDER BY bargain_price ASC LIMIT 10",array(':uniacid'=>$_W['uniacid']));

        $min_price = $res2['end_price'];
        $max_price = $res2['start_price'];
        if (pdo_fetch("SELECT * FROM ". tablename('ewei_shop_bargain_actor') ." WHERE id = :id AND status = '1'",array(':id'=>$id))) {
            if ($trade_swi!=2) {
                $trade_swi = 1;//已经成交
            }
        }elseif ($res2['stock']<=0) {
            if ($trade_swi!=2&&$trade_swi!=1){
                $trade_swi = 3;//库存不足
            }
        }
        // $min = $res2['min']*-1;
        // $max = $res2['max'];
        if (!empty($res2['time_limit'])) {
            $time_limit = ($res2['time_limit']*3600) + strtotime($res['created_time']) -time();
        }else{
            $time_limit =$time3;
        }
        //$ajax = 151 为ajax的请求状态，此时调用砍价方法，并打印出砍价结果
        if ($ajax == 151) {
            if ($isFollowed !== true){
                die('请先关注再砍价');
            }
            echo $this->cut($id,$time_limit,$min_price,$res2['each_time'],$res2['total_time'],$max_price,$res2['probability']);
            die();
        }else{
            $res2['user_set'] = urldecode($res2['user_set']);
            $share_res = json_decode($res2['user_set'],true);

            if ($type == 1) {
                if(!empty($share_res['bargain_title'])){
                    //?
                }else{
                    $share['title'] =$res2['title'];
                }

                if (!empty($share_res['bargain_content_f'])) {
                    $share['content'] = $share_res['goods_content'];
                }else{
                    $share['content'] = $res2['title2'];
                }
                if (!empty($share_res['bargain_logo'])) {
                    $share['logo'] = tomedia($share_res['bargain_logo']);
                }else{
                    $tt = json_decode($res2["images"]);
                    $share['logo'] = tomedia($tt[0]);
                }

            }elseif ($type == 0) {//普通模式

                if(!empty($share_res['bargain_title'])){
                    //
                    $weikan = $res2['start_price'] - $res['bargain_price'];
                    $share_res['bargain_title'] = str_replace("[已砍]", $res['bargain_price'], $share_res['bargain_title']);
                    $share_res['bargain_title'] = str_replace("[未砍]", $weikan, $share_res['bargain_title']);
                    $share_res['bargain_title'] = str_replace("[现价]", $res['now_price'], $share_res['bargain_title']);
                    $share_res['bargain_title'] = str_replace("[原价]", $res2['start_price'], $share_res['bargain_title']);
                    $share['title'] = str_replace("[底价]", $res2['end_price'], $share_res['bargain_title']);
                }else{
                    $share['title'] =$res2['title'];
                }
                if (!empty($share_res['bargain_content'])) {
                    $share['content'] = $share_res['bargain_content'];
                }else{
                    $share['content'] = $res2['title2'];
                }
                if (!empty($share_res['bargain_logo'])) {
                    $share['logo'] = tomedia($share_res['bargain_logo']);
                }else{
                    $tt = json_decode($res2["images"]);
                    $share['logo'] = tomedia($tt[0]);
                }

            }

            $_W['shopshare'] = array(
                'title' => $share['title'] ,
                'desc' => $share['content'] ,
                'link' => mobileUrl('bargain/bargain',array('id'=>$id,'mid'=>$mid),true) ,
                'imgUrl' => $share['logo']
            );
            $account_set['partin'] *= -1;
            $myself_swi = 0;
            $myself_count = pdo_get('ewei_shop_bargain_record',array('openid'=>$_W['openid'],'actor_id'=>$res['id']),'id');
            if (empty($myself_count['id']) && $res2['myself']>0){
                $myself_swi = 1;
            }
            include $this->template();
        }
    }

    function join(){

        global $_W,$_GPC;
        $user_info = m('member')->getMember($_W['openid']);
        if(empty($user_info)){
            die('身份验证失败');
        }
        $goods_id = (int)$_GPC['goods'];
        $res = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_bargain_goods') ." WHERE id = :id",array(':id'=>$goods_id));
        if ($res['act_times']>=$res['maximum']){
            $this->message('活动次数已到达上限,不能发起砍价',mobileUrl('bargain/detail',array('id'=>$goods_id)));
        }
        if(!empty($res['initiate'])){
            if(!empty($count['id'])){
                echo "<script>window.location.href = '".mobileUrl('bargain/bargain',array('id'=>$count['id']))."'</script>";                die();
            }
        }
        $goods_detail = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_goods') ." WHERE id = :id AND status='1'",array(':id'=>$res['goods_id']));

        if ($goods_detail['total']<=0) {
            $this->message('库存不足,不能发起砍价',mobileUrl('bargain/detail',array('id'=>$goods_id)));
        }elseif(strtotime($res['end_time'])<time()){
            $this->message('活动时间已经结束',mobileUrl('bargain/detail',array('id'=>$goods_id)));
        }elseif (strtotime($res['start_time'])>time()) {
            $this->message('活动时间尚未开始',mobileUrl('bargain/detail',array('id'=>$goods_id)));
        }elseif ($goods_detail['status'] != 1){
            $this->message('商品已下架',mobileUrl('bargain/detail',array('id'=>$goods_id)));
        }
        $time = date("Y-m-d H:i:s",time());
        $data = array('goods_id' => $goods_id, 'now_price' => $goods_detail['marketprice'], 'created_time' => $time, 'update_time' => $time, 'bargain_times' => 0, 'openid' =>$user_info['openid'], 'nickname' => $user_info['nickname'], 'head_image' =>$user_info['avatar'], 'bargain_price' => 0,'status' => 0,'account_id' => $_W['uniacid']);
        if(!empty($user_info['openid'])){
            $if = pdo_insert('ewei_shop_bargain_actor',$data);
            $id = pdo_insertid();
            pdo_query("UPDATE ". tablename('ewei_shop_bargain_goods') ." SET act_times=act_times+1 WHERE id= :id",array(':id'=>$goods_id));
        }else{die('拒绝访问');}


        if ($id) {
            $url = mobileUrl('bargain/bargain',array('id'=>$id),true);
            header("Location:".$url);
//            echo "<script>window.location.href='".$url."'</script>";
            return;
        }else{
            echo "不允许跳转";
        }

    }

    //砍价算法
    public function cut($actor_id,$time3,$min_price,$each_time,$total_time,$max_price,$probability_json){
        global $_GPC,$_W;
        $sum = 0;
        $probability = json_decode($probability_json,true);
        $rand_num = rand(1,100);
        for ($i=0; $i < count($probability['rand']); $i++) {
            $sum += $probability['rand'][$i];
            if ($rand_num <= $sum) {//说明在当前区间内
                $loop = $i;
                break;
            }
        }


        $min = $probability['min'][$loop];
        $max = $probability['max'][$loop];
        $info = m('member')->getMember($_W['openid']);
        //这里需要完成对min和max的赋值
        $min *= 100;
        $max *= 100;
        $record_res = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_record') ." WHERE actor_id=:actor_id AND openid= :openid",array(':actor_id'=>$actor_id,':openid'=>$_W['openid']));//本人砍价记录
        $all_record = pdo_fetchall("SELECT * FROM ". tablename('ewei_shop_bargain_record') ." WHERE actor_id= :actor_id",array(':actor_id'=>$actor_id));
        if (empty($actor_id)) {
            return "砍价失败！";
        } elseif ($time3 <= 0) {
            return "砍价已结束！";
        }
        elseif(count($record_res)>=$each_time||count($all_record)>=$total_time){
            return "砍价机会已用完！";
        }

        //砍价算法
        $cut_price = rand($min,$max)/100;
        $cut_price = round($cut_price,2);
        $now_price = pdo_fetch("SELECT * FROM ". tablename('ewei_shop_bargain_actor') ." WHERE id = :id",array(':id'=>$actor_id));
        if ($now_price['status'] == 1) {
            return "已经成交过了！";
        }
        $now_price['now_price'] = round($now_price['now_price'],2);
        $min_price = round($min_price,2);

        if ($now_price['now_price'] <= $min_price) {
            return "砍到底价了,快去告诉TA<br>可以按底价购买啦！";
        }

        $temp_price = 2000000;
        $lastbargain = 0;

        $temp_price = $now_price['now_price'] + $cut_price;//砍后价格
        if ($temp_price < $min_price) {//处理最后一次砍价
            $cut_price = $min_price - $now_price['now_price'];//负数
            $cut_price = round($cut_price,2);

            if ($cut_price>0){$cut_price = -1 * $cut_price;}//保证负数
            $lastbargain = 1;
        }


        $cut_price =round($cut_price,2);
        $time = date('Y-m-d H:i:s',time());
        $insert_data = array('actor_id'=>$actor_id,'bargain_price'=>$cut_price,'openid'=>$_W['openid'],'nickname'=>$info['nickname'],'head_image'=>$info['avatar'],'bargain_time'=>$time);
        $res_ins = pdo_insert('ewei_shop_bargain_record',$insert_data);
        $res_act = pdo_query("UPDATE ". tablename('ewei_shop_bargain_actor') ." SET now_price = now_price + :now_price ,update_time = :update_time , bargain_price = bargain_price + :bargain_price WHERE id= :id",array(':now_price'=>$cut_price,':update_time'=>$time,':bargain_price'=>$cut_price,':id'=>$actor_id));

        if ($cut_price<=0) {
            $now_price['now_price'] += $cut_price;
            $cut_price = (-1)*$cut_price;//正数
            $this->sendBargainResult($now_price['openid'], $cut_price, $now_price['now_price'], $info['nickname'], '砍掉', '成功', $lastbargain);
            pdo_query("UPDATE ". tablename('ewei_shop_bargain_actor') ." SET bargain_times = bargain_times + 1 WHERE id = :id",array(':id'=>$actor_id));
            return "成功砍掉{$cut_price}元！";
        }elseif($cut_price > 0){

            $now_price['now_price'] += $cut_price;
            $this->sendBargainResult($now_price['openid'], $cut_price, $now_price['now_price'], $info['nickname'], '增加', '失败');
            pdo_query("UPDATE ". tablename('ewei_shop_bargain_actor') ." SET bargain_times = bargain_times + 1 WHERE id = :id",array(':id'=>$actor_id));
            return "难度增加{$cut_price}元！";
        }
    }

    function rule(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $myMid = (int)m('member')->getMid();
        $mid = (int)$_GPC['mid'];
        if ($mid!==$myMid){
            echo "<script>window.location.href='".mobileUrl('bargain/rule',array('mid'=>$myMid))."'</script>";die();
        }
        $rule = pdo_get('ewei_shop_bargain_goods',array('id'=>$id,'account_id'=>$_W['uniacid']),array('rule'));
        if (empty($rule['rule'])) {
            $rule = pdo_get('ewei_shop_bargain_account', array('id' => $_W['uniacid']), array('rule'));
        }

        include $this->template();
    }


    private function sendBargainResult($openid, $cut_price, $now_price, $nickname, $iORr, $sORf ,$last = 0) {
        global $_W, $_GPC;
            $time = date('Y-m-d H:i',time());
            $datas[] = array('name' => '砍价金额', 'value' => $cut_price);
            $datas[] = array('name' => '当前金额', 'value' =>  $now_price);
            $datas[] = array('name' => '砍价时间', 'value' => $time);
            $datas[] = array('name' => '砍价人昵称', 'value' => $nickname);
            $datas[] = array('name' => '砍掉或增加', 'value' => $iORr);
            $datas[] = array('name' => '成功或失败', 'value' => $sORf);
            $url = mobileUrl('bargain/bargain',array('id'=>$_GPC['id']),1);
            $remark  =  "\n<a href='{$url}'>点击查看详情</a>";

            if ($last == 1){
                $tag = 'bargain_fprice';
                $text = "砍到底价通知：\n\n[砍价人昵称]帮您砍到底价了，\n砍价结果：[砍掉或增加]了[砍价金额]元\n砍价时间：[砍价时间]\n当前成交价：[当前金额]元\n\n<a href='{$url}'>点击查看详情</a>";
                $message = array(
                    'first' => array('value' => "砍到底价通知\n", "color" => "#000000"),
                    'keyword1' => array('title' => '任务名称', 'value' =>   "{$nickname}帮你砍到底价", "color" => "#000000"),
                    'keyword2' => array('title' => '通知类型', 'value' => "砍到底价通知", "color" => "#000000"),
                    'remark' => array('value' => "砍价金额：{$iORr}了{$cut_price}元\n砍价时间：{$time}\n当前价格：{$now_price}元\n\n点击立即下单", "color" => "#000000")
                );
            }else{
                $tag = 'bargain_message';
                $text = "砍价成功通知：\n\n{$nickname}帮您砍价{$sORf}，\n砍价结果：{$iORr}了{$cut_price}元\n砍价时间：".$time."\n当前成交价：{$now_price}元\n".$remark;
                $message = array(
                    'first' => array('value' => "砍价{$sORf}通知\n", "color" => "#000000"),
                    'keyword1' => array('title' => '任务名称', 'value' =>   "{$nickname}帮你砍价{$sORf}", "color" => "#000000"),
                    'keyword2' => array('title' => '通知类型', 'value' => "砍价{$sORf}通知", "color" => "#000000"),
                    'remark' => array('value' => "砍价金额：{$iORr}了{$sORf}元\n砍价时间：{$time}\n当前价格：{$now_price}元\n\n点击立即下单", "color" => "#000000")
                );
            }

        $this->sendNotice(array(
            "openid" => $openid,
            'tag' => $tag,
            'default' => $message,
            'cusdefault' => $text,
            'url' => $url,
            'datas' => $datas,
        ));
    }

    public function sendNotice(array $params) {
        global $_W, $_GPC;

        $tag = isset($params['tag']) ? $params['tag'] : '';
        $touser = isset($params['openid']) ? $params['openid'] : '';
        if (empty($touser)) {
            return;
        }
        $tm = $_W['shopset']['notice'];
        if(empty($tm)) {
            $tm = m('common')->getSysset('notice');
        }

        $tm_temp = $tm[$tag . "_template"];

        $templateid = $tm_temp;
        $datas = isset($params['datas']) ? $params['datas'] : array();

        $default_message = isset($params['default']) ? $params['default'] : array();
        $cusdefault_message = $this->replaceTemplate( isset($params['cusdefault']) ? $params['cusdefault'] :'', $datas);;

        $url = isset($params['url']) ? $params['url'] : '';
        $account = isset($params['account']) ? $params['account'] : m('common')->getAccount();


        if(!empty($tm[$tag.'_close_advanced'])){
            //关闭提醒
            return;
        }

        if (!empty($templateid)) {
            $template = pdo_fetch('select * from ' . tablename('ewei_shop_member_message_template') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $templateid, ':uniacid' => $_W['uniacid']));
            if (!empty($template)) {
                $template_message = array(
                    'first' => array('value' => $this->replaceTemplate($template['first'], $datas), 'color' => $template['firstcolor']),
                    'remark' => array('value' => $this->replaceTemplate($template['remark'], $datas), 'color' => $template['remarkcolor'])
                );
                $data = iunserializer($template['data']);

                foreach ($data as $d) {
                    $template_message[$d['keywords']] = array('value' => $this->replaceTemplate($d['value'], $datas), 'color' => $d['color']);
                }

                $Custom_message = $this->replaceTemplate($template['send_desc'], $datas);

                $messagetype = $template['messagetype'];

                if(empty($messagetype))
                {
                    //客服消息
                    $ret = m('message')->sendTexts($touser, $Custom_message, $url, $account);
                    if (is_error($ret)) {
                        //模板消息
                        $ret = m('message')->sendTplNotice($touser, $template['template_id'], $template_message, $url, $account);
                    }
                }
                else if($messagetype==1)
                {
                    //模板消息
                    $ret = m('message')->sendTplNotice($touser, $template['template_id'], $template_message, $url, $account);

                }
                else if($messagetype==2)
                {
                    //客服消息
                    $ret = m('message')->sendTexts($touser, $Custom_message, $url, $account);
                }
            } else {
                //默认文本客服消息
                $ret = m('message')->sendTexts($touser, $cusdefault_message, '', $account);

                if (is_error($ret)) {
                    //默认模板消息
                    $templatetype = pdo_fetch('select templateid  from ' . tablename('ewei_shop_member_message_template_type') . ' where typecode=:typecode  limit 1', array(':typecode' => $tag));

                    if(!empty($templatetype['templateid']))
                    {
                        $ret = m('message')->sendTplNotice($touser, $templatetype['templateid'], $default_message, $url, $account);
                    }
                }
            }
        } else {
            //默认文本客服消息

            $ret = m('message')->sendTexts($touser, $cusdefault_message, '', $account);
            if (is_error($ret)) {
                //默认模板消息
                $templatetype = pdo_fetch('select templateid  from ' . tablename('ewei_shop_member_message_template_type') . ' where typecode=:typecode  limit 1', array(':typecode' => $tag));

                if(!empty($templatetype['templateid']))
                {
                    $ret = m('message')->sendTplNotice($touser, $templatetype['templateid'], $default_message, $url, $account);
                }
            }
        }
    }

    protected function replaceTemplate($str, $datas = array()) {
        foreach ($datas as $d) {
            $str = str_replace("[" . $d['name'] . "]", $d['value'], $str);
        }
        return $str;
    }
}