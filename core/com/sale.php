<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
function sort_enoughs($a, $b) {
    $enough1 = floatval($a['enough']);
    $enough2 = floatval($b['enough']);
    if ( $enough1==$enough2) {
        return 0;
    } else {
        return ($enough1 < $enough2) ? 1 : -1;
    }
}

class Sale_EweiShopV2ComModel extends ComModel {

    public function getEnoughsGoods() {

        global $_W,$_S;
        $set = $_S['sale'];
        $goodsids = $set['goodsids'];
        return $goodsids;
    }

    public function getEnoughs() {

        global $_W,$_S;
        $set = $_S['sale'];
        $allenoughs = array();
        $enoughs = $set['enoughs'];
        if (floatval($set['enoughmoney']) > 0 && floatval($set['enoughdeduct']) > 0) {
            $allenoughs[] = array('enough' => floatval($set['enoughmoney']), 'money' => floatval($set['enoughdeduct']));
        }
        if (is_array($enoughs)) {
            foreach ($enoughs as $e) {
                if (floatval($e['enough']) > 0 && floatval($e['give']) > 0) {
                    $allenoughs[] = array('enough' => floatval($e['enough']), 'money' => floatval($e['give']));
                }
            }
        }
        usort($allenoughs, "sort_enoughs");
        return $allenoughs;
    }
    public function getEnoughFree(){

        global $_W,$_S;
        $set = $_S['sale'];
        if(!empty($set['enoughfree'])){
            return $set['enoughorder']>0?$set['enoughorder']:-1;
        }
        return false;
    }
    public function getRechargeActivity() {
        global $_S;
        $set = $_S['sale'];
        $recharges = iunserializer($set['recharges']);
        if (is_array($recharges)) {
            usort($recharges, "sort_enoughs");
            return $recharges;
        }
        return false;
    }
    //充值 活动
    public function setRechargeActivity($log) {
        global $_W,$_S;
        $set = m('common')->getPluginset('sale');
        $recharges = iunserializer($set['recharges']);
        $credit2 = 0;
        $enough = 0;
        $give = '';

//        print_r($recharges);exit;

        if (is_array($recharges)) {
            usort($recharges, "sort_enoughs");
            foreach ($recharges as $r) {
                if (empty($r['enough']) || empty($r['give'])) {
                    continue;
                }
                if ($log['money'] >= floatval($r['enough'])) {
                    if (strexists($r['give'], '%')) {
                        $credit2 = round(floatval(str_replace('%', '', $r['give'])) / 100 * $log['money'], 2);
                    } else {
                        $credit2 = round(floatval($r['give']), 2);
                    }
                    $enough = floatval($r['enough']);
                    $give = $r['give'];
                    break;
                }
            }
        }
//        print_r($log);exit;


        if ($credit2 > 0) {
            m('member')->setCredit($log['openid'], 'credit2', $credit2, array('0', $_S['shop']['name'] . '充值满' . $enough . '赠送' . $give, '现金活动'));
            pdo_update('ewei_shop_member_log', array('gives' => $credit2), array('id' => $log['id']));
        }
        $this->getCredit1($log['openid'],$log['money'],21,2);
    }

    /**
     * 传入金额,生成满立减优惠
     * @param int $price
     * @return array
     */
    public function getCredit1($openid,$price = 0,$paytype = 1,$type=1,$refund=0) {

        global $_W;
        $type = intval($type);
        if (empty($openid) || empty($price) || empty($type)){
            return 0;
        }
        $data = m('common')->getPluginset('sale');
        $credit1 = iunserializer($data['credit1']);
        if ($type == '1'){
            $name = '购物送积分';
            $enoughs = empty($credit1['enough1']) ? array() : $credit1['enough1'];

            if (empty($credit1['paytype'])){
                return 0;
            }
            if (!empty($credit1['paytype']) && !in_array($paytype,array_keys($credit1['paytype']))){
            return 0;
            }
        }else{
            $name = '充值送积分';
            $enoughs = empty($credit1['enough2']) ? array() : $credit1['enough2'];
        }

        $allenoughs = array();
        if (is_array($enoughs)) {
            foreach ($enoughs as $e) {
                if (floatval($e['enough'.$type]) > 0 && floatval($e['give'.$type]) > 0) {
                    $allenoughs[] = array('enough' => floatval($e['enough'.$type]), 'money' => floatval($e['give'.$type]));
                }
            }
        }
        usort($allenoughs, "sort_enoughs");

        $money = 0;
        foreach ($allenoughs as $key=>$value){
            if ($price >= $value['enough'] && $value > 0){
                $money = floatval($value['money']) > $money ? $value['money'] : $money;
            }
        }
        if ($money>0){
            $money *= $price;
            if (empty($refund)){
                m('member')->setCredit($openid,'credit1',$money,'积分活动,'.$name.': '.$money.'积分');
            }else{
                m('member')->setCredit($openid,'credit1',-$money,'积分活动,'.$name.'退款 : '.-$money.'积分');
            }
        }
        return $money;
    }

}
