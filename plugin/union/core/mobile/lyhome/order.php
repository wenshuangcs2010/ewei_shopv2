<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Order_EweiShopV2Page extends LyMobilePage
{
    function main(){
        global $_W;
        global $_GPC;

        $member=m("member")->getMember($_W['openid']);
        include $this->template();
    }
    function comment(){
        global $_W;
        global $_GPC;
        $lyaddresslineid=intval($_GPC['id']);
        $openid=$_W['openid'];
        $sql="select o.*,adl.title as title,adl.header_image,adl.id as addressid from ".tablename("ewei_shop_union_ly_order")." as o "
        ." LEFT  JOIN ".tablename("ewei_shop_union_ly_addressline")." as adl ON o.addresslineid=adl.id"
        ." where o.id=:id and o.openid=:openid";

        $lyaddressinfo=pdo_fetch($sql,array(":id"=>$lyaddresslineid,":openid"=>$openid));
        if ($lyaddressinfo['iscomment'] >= 2) {
            $this->message('您已经评价过了!',mobileUrl('union/lyhome/lyaddressline/orders',array('id'=>$lyaddresslineid)));
        }
        include $this->template();
    }
    function detail(){
        global $_W;
        global $_GPC;

        $orderid=intval($_GPC['id']);
        $openid=$_W['openid'];
        $sql="select o.*,adl.title as title,adl.header_image,adl.id as addressid from ".tablename("ewei_shop_union_ly_order")." as o "
            ." LEFT  JOIN ".tablename("ewei_shop_union_ly_addressline")." as adl ON o.addresslineid=adl.id"
            ." where o.id=:id and o.openid=:openid";

        $lyaddressinfo=pdo_fetch($sql,array(":id"=>$orderid,":openid"=>$openid));
        $idcard=$lyaddressinfo['imid'];
        $lyaddressinfo['imid']=strlen($idcard)==15?substr_replace($idcard,"****",8,4):(strlen($idcard)==18?substr_replace($idcard,"**********",5,11):"身份证位数不正常！");


        include $this->template();
    }

    function commentsubmit(){
        global $_W;
        global $_GPC;

        $orderid=intval($_GPC['orderid']);
        $openid=$_W['openid'];
        $order = pdo_fetch("select id,status,iscomment,addresslineid from " . tablename('ewei_shop_union_ly_order') . ' where id=:id  and openid=:openid limit 1'
            , array(':id' => $orderid, ':openid' => $openid));
        if (empty($order)) {
            show_json(0, '订单未找到');
        }
        $member = m('member')->getMember($openid);
        $comments = $_GPC['comments'];
        if (!is_array($comments)) {
            show_json(0, '数据出错，请重试!');
        }
        $d=array(
            'orderid'=>$orderid,
            'openid'=>$openid,
            'nickname'=>$member['nickname'],
            'headimgurl'=>$member['avatar'],
            'level' => $comments['level'],
            'content'=>$comments['content'],
            'checked'=>1,//默认已经审核
            'addresslineid'=>$order['addresslineid'],
            'images'=> is_array($comments['images']) ? iserializer($comments['images']) : iserializer(array()),
            'createtime'=>time(),
        );

        $old_c = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_ly_order_comment') . ' where orderid=:orderid  limit 1', array( ':orderid' => $orderid));
        if(empty($old_c)){
            pdo_insert('ewei_shop_ly_order_comment', $d);
        }
        if($order['addresslineid']>0){
            $count=pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_ly_order_comment') . ' where addresslineid=:addresslineid  limit 1', array( ':addresslineid' => $order['addresslineid']));
            $level=pdo_fetchcolumn('select sum(level) from ' . tablename('ewei_shop_ly_order_comment') . ' where addresslineid=:addresslineid  limit 1', array( ':addresslineid' => $order['addresslineid']));

            $avgevaluate=$level/$count;
            $u=array(
                'evaluate'=>round($avgevaluate,2),
            );
            pdo_update("ewei_shop_union_ly_addressline",$u,array("id"=>$order['addresslineid']));
        }
        $update['iscomment'] = 2;
        $update['status'] = 4;
        pdo_update('ewei_shop_union_ly_order', $update, array('id' => $orderid));
        show_json(1);
    }
}