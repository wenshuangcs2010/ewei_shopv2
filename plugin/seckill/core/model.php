<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class SeckillModel extends PluginModel
{

    function get_prefix()
    {

        global $_W;
        if( empty($_W['account']['key'])){
            $_W['account']['key'] = pdo_fetchcolumn('SELECT `key` FROM ' . tablename('account_wechats') . " WHERE uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
        }
        return "ewei_shopv2_{$_W['setting']['site']['key']}_{$_W['uniacid']}_{$_W['account']['key']}_seckill_";
    }

    function setTaskCache($id)
    {

        global $_W;

        if (is_error(redis())) {
            return;
        }
        $redis_prefix = $this->get_prefix();

        //任务
        $task = pdo_fetch('select * from ' . tablename('ewei_shop_seckill_task') . ' where id=:id limit 1', array(':id' => $id));
        redis()->delete("{$redis_prefix}info_{$id}");
        redis()->hMset("{$redis_prefix}info_{$id}", $task);

        //会场
        $allrooms = pdo_fetchall('select * from ' . tablename('ewei_shop_seckill_task_room') . " where taskid=:taskid and enabled=1 and uniacid=:uniacid order by `displayorder` desc", array(':taskid' => $id, ':uniacid' => $_W['uniacid']));
        redis()->delete("{$redis_prefix}rooms_{$id}");
        foreach ($allrooms as $room) {
            redis()->rPush("{$redis_prefix}rooms_{$id}", json_encode($room));
        }


        redis()->delete("{$redis_prefix}times_{$id}");
        $alltimes = pdo_fetchall('select * from ' . tablename('ewei_shop_seckill_task_time') . " where taskid=:taskid and uniacid=:uniacid order by `time` asc", array(':taskid' => $id, ':uniacid' => $_W['uniacid']));

        $redisgoods = array();

        foreach ($alltimes as &$time) {

            //商品缓存
            $goods = pdo_fetchall('select * from ' . tablename('ewei_shop_seckill_task_goods') . " where taskid=:taskid and timeid=:timeid and uniacid=:uniacid order by displayorder asc", array(':taskid' => $id, ':timeid' => $time['id'], ':uniacid' => $_W['uniacid']));
            if (!empty($goods)) {
                if (!isset($redisgoods[$time['time']]) || !is_array($redisgoods[$time['time']])) {
                    $redisgoods['time-' . $time['time']] = array();
                }
                redis()->rPush("{$redis_prefix}times_{$id}", json_encode($time));
                $redisgoods['time-' . $time['time']] = json_encode($goods);
            }
        }

        redis()->delete("{$redis_prefix}goods_{$id}");
        if (!empty($redisgoods)) {
            redis()->hMset("{$redis_prefix}goods_{$id}", $redisgoods);
        }

    }



    /**
     * 获取任务
     * @param $taskid
     * @return bool|int|string
     */
    function usedDate($taskid)
    {

        if (is_error(redis())) {
            return false;
        }

        global $_W;
        $redis_prefix = $this->get_prefix();
        $calendar = redis()->hGetAll("{$redis_prefix}calendar");
        if (!is_array($calendar) || empty($calendar)) {
            return false;
        }
        foreach ($calendar as $k => $v) {
            if (!empty($v) && is_array($v)) {
                if ($v['taskid'] == $taskid) {
                    return $k;
                }
            }
        }
        return false;
    }

    /**
     * 获取今天的秒杀任务
     * @return mixed
     */

    function getTodaySeckill()
    {
        if (is_error(redis())) {
            return false;
        }
        $redis_prefix = $this->get_prefix();
        global $_W;
        $year = date('Y');
        $month = date('m');
        return redis()->hGet("{$redis_prefix}calendar_{$year}_{$month}", date('Y-m-d'));

    }

    function getTodaySeckillInfo()
    {
        if (is_error(redis())) {
            return false;
        }
        global $_W;
        $taskid = $this->getTodaySeckill();
        if (!empty($taskid)) {
            return $this->getTaskInfo($taskid);
        }
        return false;
    }

    function getRooms($taskid)
    {

        global $_W, $_GPC;
        $redis = redis();
        if (is_error(redis())) {
            return false;
        }
        $redis_prefix = $this->get_prefix();
        $allrooms = array();
        $rooms = $redis->lGetRange("{$redis_prefix}rooms_{$taskid}", 0, -1);
        foreach ($rooms as $room) {

            $room = json_decode($room, true);
            if (is_array($room)) {
                $allrooms[] = $room;
            }
        }
        return $allrooms;
    }

    function getMainRoom($taskid)
    {
        if (is_error(redis())) {
            return false;
        }
        global $_W, $_GPC;
        $redis = redis();

        $rooms = $this->getRooms($taskid);
        if (empty($rooms)) {
            return false;
        }
        return $rooms[0];
    }

    function getRoomInfo($taskid, $roomid)
    {
        global $_W;
        if (is_error(redis())) {
            return false;
        }
        $rooms = $this->getRooms($taskid);

        foreach ($rooms as $room) {
            if ($room['id'] == $roomid) {
                return $room;
            }
        }
        return false;
    }

    function getTaskInfo($taskid)
    {

        global $_W, $_GPC;
        if (is_error(redis())) {
            return false;
        }
        $redis = redis();
        $redis_prefix = $this->get_prefix();
        $info = $redis->hGetAll("{$redis_prefix}info_{$taskid}");
        if (empty($info)) {
            return false;
        }
        return $info;
    }

    function getTaskTimes($taskid)
    {
        global $_W, $_GPC;
        $redis = redis();
        if (is_error(redis())) {
            return false;
        }
        $redis_prefix = $this->get_prefix();
        $times = $redis->lGetRange("{$redis_prefix}times_{$taskid}", 0, -1);

        $alltimes = array();
        if (empty($times)) {
            return false;
        }
        foreach ($times as $time) {
            $time = json_decode($time, true);
            if (is_array($time)) {

                $alltimes[] = $time;
            }
        }
        return $alltimes;
    }

    function getTaskGoods($taskid)
    {
        global $_W, $_GPC;
        $redis = redis();
        if (is_error(redis())) {
            return false;
        }
        $redis_prefix = $this->get_prefix();
        $info = $redis->hGetAll("{$redis_prefix}goods_{$taskid}");
        if (empty($info)) {
            return false;
        }
        return $info;
    }

    function deleteTodaySeckill()
    {
        global $_W;
        if (is_error(redis())) {
            return false;
        }
        $redis_prefix = $this->get_prefix();
        $year = date('Y');
        $month = date('m');
        $date = date('Y-m-d');
        redis()->hDel("{$redis_prefix}calendar_{$year}_{$month}", $date);
    }


    function getSeckillCount($taskid, $timeid, $goodsid, $optionid = 0, $openid = '')
    {
        global $_W, $_GPC;
        if (is_error(redis())) {
            return false;
        }
        $redis = redis();
        $date = date('Y-m-d');
        $redis_prefix = $this->get_prefix();
        $keys = $redis->keys("{$redis_prefix}queue_{$date}_{$taskid}_{$timeid}_{$goodsid}_*");
        if (empty($keys)) {
            return array('count' => 0, 'notpay' => 0, 'selfcount' => 0, 'selfnotpay' => 0, 'selftotalcount' => 0, 'selftotalnotpay' => 0);
        }

        $count = 0; //总下单数
        $notpay = 0; //总未付款的数
        $selfcount = 0; // 自己下单数（指定规格）
        $selftotalcount = 0; //自己下单数（所有规格)

        $selfnotpay = 0; // 自己未付款的数（指定规格）
        $selftotalnotpay = 0; //自己未付款的数（所有规格）

        foreach ($keys as $key) {

            $arr = explode('_', $key);
            $key_optionid = $arr[11];
            $queue = $redis->lGetRange($key, 0, -1);
            foreach ($queue as $data) {
                $data = @json_decode($data, true);
                if (!is_array($data)) {
                    continue;
                }

                $count++;

                if ($data['status'] <= 0) {
                    $notpay++;
                }

                if (!empty($openid) && $data['openid'] == $openid) {
                    if ($key_optionid == $optionid) {
                        $selfcount++;
                    }
                    $selftotalcount++;
                }

                if ($data['status'] <= 0 && !empty($openid) && $data['openid'] == $openid) {
                    if ($key_optionid === $optionid) {
                        $selfnotpay++;
                    }
                    $selftotalnotpay++;
                }
            }
        }

        return array('count' => $count, 'notpay' => $notpay, 'selfcount' => $selfcount, 'selfnotpay' => $selfnotpay, 'selftotalcount' => $selftotalcount, 'selftotalnotpay' => $selftotalnotpay);

    }

    public function getSeckillGoods($taskid, $time, $goodsid)
    {

        global $_W, $_GPC;
        if (is_error(redis())) {
            return false;
        }
        $redis_prefix = $this->get_prefix();
        $timegoods = array();

        $goods = redis()->hGetAll("{$redis_prefix}goods_{$taskid}");
        if (empty($goods)) {
            return false;
        }

        $goods = @json_decode($goods['time-' . $time], true);

        if (!is_array($goods)) {
            return false;
        }

        foreach ($goods as $g) {

            if (!is_array($g)) {
                return false;
            }


            if ($g['goodsid'] == $goodsid || $goodsid == 'all') {
                $timegoods[] = $g;
            }
        }

        return $timegoods;

    }


    /**
     * 设置秒杀数据
     */
    function setSeckill($seckillinfo, $goods, $openid, $orderid, $status, $createtime)
    {

        global $_W, $_GPC;

        if (is_error(redis())) {
            return false;
        }
        $taskid = $seckillinfo['taskid'];
        $timeid = $seckillinfo['timeid'];
        $goodsid = $goods['goodsid'];
        $optionid = intval($goods['optionid']);

        $date = date('Y-m-d');
        $redis_prefix = $this->get_prefix();
        $key = "{$redis_prefix}queue_{$date}_{$taskid}_{$timeid}_{$goodsid}_{$optionid}";

        $redis = redis();
        if ($redis->ttl($key) == -1) {
            $redis->expireAt($key, $seckillinfo['endtime'] + 1);
        }
        $index = -1;
        $queue = $redis->lGetRange($key, 0, -1);

        foreach ($queue as $dindex => $data) {
            $data = @json_decode($data, true);
            if (!is_array($data)) {
                continue;
            }
            if ($data['orderid'] == $orderid && $data['openid'] == $openid) {
                $index = $dindex;
                break;
            }
        }
        $data = array(
            'orderid' => $orderid,
            'openid' => $openid,
            'status' => $status,
            'createtime' => $createtime
        );

        if ($index == -1) {
            for ($i = 1; $i <= $goods['total']; $i++) {
                $push = $redis->lPush($key, json_encode($data));
            }

        } else {
            $push = $redis->lSet($key, $index, json_encode($data));
        }

        return $key;
    }

    /**
     * 删除未付款过期数据
     */
    function deleteSeckill()
    {

        global $_W;

        if (is_error(redis())) {
            return false;
        }
        $currenttime = time();

        $redis = redis();
        $redis_prefix = $this->get_prefix();
        $keys = $redis->keys("{$redis_prefix}queue_*");
        $orders = array();
        foreach ($keys as $key) {
            $queue = $redis->lGetRange($key, 0, -1);

            $tags = explode('_', $key);
            $taskid = $tags[8];

            $task = $this->getTaskInfo($taskid);


            $closesec = $task['closesec'];


            if (!empty($queue)) {

                foreach ($queue as $value) {
                    $data = @json_decode($value, true);
                    if (!is_array($data)) {
                        continue;
                    }
                    if ($data['status'] <= 0 && $currenttime - $data['createtime'] >= $closesec) {

                        $redis->lRemove($key, $value, 1);

                        if (!in_array($data['orderid'], $orders)) {
                            $orders[] = $data['orderid'];
                        }

                    }
                }
            }
            if ($redis->lLen($key) <= 0) {
                $redis->delete($key);
            }

            if (!empty($orders)) {

                $p = com('coupon');
                foreach ($orders as $orderid) {
                    $o = pdo_fetch('select  id,openid,deductcredit2,ordersn,isparent,deductcredit,deductprice   from ' . tablename('ewei_shop_order') . " where id=:id  limit 1", array(':id' => $orderid));
                    if (!empty($o) && $o['status'] == 0) {

                        //多商户父订单跳过
                        if ($o['isparent'] == 0) {
                            if ($p) {
                                //退还优惠券
                                if (!empty($o['couponid'])) {
                                    $p->returnConsumeCoupon($o['id']); //自动关闭订单
                                }
                            }

                            //处理订单库存及用户积分情况
                            m('order')->setStocksAndCredits($o['id'], 2);

                            //返还抵扣余额
                            m('order')->setDeductCredit2($o);

                            //返还抵扣积分
                            if ($o['deductprice'] > 0) {
                                m('member')->setCredit($o['openid'], 'credit1', $o['deductcredit'], array('0', $_W['shopset']['shop']['name'] . "秒杀自动关闭订单返还抵扣积分 积分: {$o['deductcredit']} 抵扣金额: {$o['deductprice']} 订单号: {$o['ordersn']}"));
                            }
                        }

                        pdo_query("update " . tablename('ewei_shop_order') . ' set status=-1,canceltime=' . time() . ' where id=' . $o['id']);
                    }
                }
            }
        }
        return true;
    }

    function getSeckill($goodsid, $optionid = 0, $realprice = true, $openid = '')
    {
        global $_W;
        $redis = redis();

        if (is_error($redis)) {
            return false;
        }

        static $deletedSeckill = null;
        if (is_null($deletedSeckill)) {
            //删除未付款
            $this->deleteSeckill();
            $deletedSeckill = true;
        }

        $id = $this->getTodaySeckill();

        if (empty($id)) {
            return false;
        }

        $times = $this->getTaskTimes($id);
       // var_dump($times);
        $options = array();
        $currenttime = time();

        $timegoods = array();
        $sktime = 0;
        $timeid = 0;
        $roomid = 0;
        $taskid = 0;
        $goods_starttime = 0;
        $goods_endtime = 0;
        foreach ($times as $key => $time) {

            $starttime = strtotime(date("Y-m-d {$time['time']}:00:00"));

            if (isset($times[$key + 1])) {

                $end = $times[$key + 1]['time'] - 1;
                $endtime = strtotime(date("Y-m-d {$end}:59:59"));

            } else {
                $endtime = strtotime(date("Y-m-d 23:59:59"));
            }
            $time['endtime'] = $endtime;
            $time['starttime'] = $starttime;

            if ($currenttime >= $starttime && $currenttime <= $endtime) {

                $timeid = $time['id'];
                $taskid = $time['taskid'];
                $goods_starttime = $starttime;
                $goods_endtime = $endtime;
                $sktime = $time['time'];
                

                $timegoods = $this->getSeckillGoods($id, $time['time'], $goodsid);
               

            } else if ($starttime > $currenttime) {

                //未开始
                if (empty($timegoods)) {

                    $timeid = $time['id'];
                    $goods_starttime = $starttime;
                    $goods_endtime = $endtime;
                    $taskid = $time['taskid'];
                    $sktime = $time['time'];
                    $timegoods = $this->getSeckillGoods($id, $time['time'], $goodsid);

                }
            }

        }

        $total = 0;
        $count = 0;
        $selfcount = 0;
        $selftotalcount = 0;
        $maxbuy = 0;
        $notpay = 0;
        $selfnotpay = 0;
        $selftotalnotpay = 0;
        $totalmaxbuy = 0;

        if (!empty($timegoods)) {

            $roomid = $timegoods[0]['roomid'];
            $total = $timegoods[0]['total'];
            $price = $timegoods[0]['price'];
            $totalmaxbuy = $timegoods[0]['totalmaxbuy'];


            if (count($timegoods) <= 1 && empty($timegoods['optionid'])) {
                //单规格
                $counts = $this->getSeckillCount($id, $timeid, $timegoods[0]['goodsid'], 0, $openid);
                $count = $counts['count'];
                $selfcount = $counts['selfcount'];
                $selftotalcount = $counts['selftotalcount'];
                $notpay = $counts['notpay'];
                $selfnotpay = $counts['selfnotpay'];
                $selftotalnotpay = $counts['selftotalnotpay'];
                $maxbuy = $timegoods[0]['maxbuy'];
                $percent = ceil($count / (empty($total) ? 1 : $total) * 100);
                $options[] = $timegoods[0];

            } else {
                $total = 0;
                $price = $timegoods[0]['price'];
                $option_goods = null;
                //多规格
                if (!empty($optionid)) {

                    foreach ($timegoods as $g) {
                        if ($g['optionid'] == $optionid) {
                            $total = $g['total'];
                            $counts = $this->getSeckillCount($id, $timeid, $g['goodsid'], $optionid, $openid);
                            $count = $counts['count'];
                            $selfcount = $counts['selfcount'];
                            $selftotalcount = $counts['selftotalcount'];
                            $selfnotpay = $counts['selfnotpay'];
                            $selftotalnotpay = $counts['selftotalnotpay'];
                            $notpay = $counts['notpay'];
                            $maxbuy = $g['maxbuy'];
                            $percent = ceil($count / (empty($g['total']) ? 1 : $g['total']) * 100);
                            break;
                        }
                    }

                } else {

                    foreach ($timegoods as $g) {
                        $total += $g['total'];
                        if ($g['price'] <= $price) {
                            $price = $g['price'];
                        }

                        $options[] = $g;
                    }

                    $counts = $this->getSeckillCount($id, $timeid, $g['goodsid'], 0, $openid);
                    $count = $counts['count'];
                    $selfcount = $counts['selfcount'];
                    $selfnotpay = $counts['selfnotpay'];
                    $notpay = $counts['notpay'];
                    $selftotalcount = $counts['selftotalcount'];
                    $selftotalnotpay  = $counts['selftotalnotpay'];

                    $percent = ceil($count / (empty($total) ? 1 : $total) * 100);
                }
            }


            if (!$realprice) {

                $price = price_format($price);

            }

            $tag = "";
            $taskinfo = $this->getTaskInfo($taskid);
            $roominfo = $this->getRoomInfo($taskid, $roomid);
            if (!empty($taskinfo['tag'])) {
                $tag = $taskinfo['tag'];
            }
            if (!empty($roominfo['tag'])) {
                $tag = $roominfo['tag'];
            }

            $status = false;
            if ($currenttime >= $goods_starttime && $currenttime <= $goods_endtime) {
                $status = 0;
            } else if ($currenttime < $goods_starttime) {
                $status = 1;
            } else if ($currenttime > $goods_endtime) {
                $status = -1;
            }

//dump(array(
//    'taskid' => $taskid,
//    'roomid' => $roomid,
//    'timeid' => $timeid,
//    'total' => $total,
//    'count' => $count,
//    'selfcount' => $selfcount,
//    'selftotalcount' => $selftotalcount,
//    'notpay' => $notpay,
//    'selfnotpay' => $selfnotpay,
//    'selftotalnotpay' => $selftotalnotpay,
//    'maxbuy' => $maxbuy,
//    'totalmaxbuy'=>$totalmaxbuy,
//    'tag' => $tag,
//    'time' => $sktime,
//    'options' => $options,
//    'starttime' => $goods_starttime,
//    'endtime' => $goods_endtime,
//    'price' => $price,
//    'percent' => $percent,
//    'status' => $status
//));exit;
            return array(
                'taskid' => $taskid,
                'roomid' => $roomid,
                'timeid' => $timeid,
                'total' => $total,
                'count' => $count,
                'selfcount' => $selfcount,
                'selftotalcount' => $selftotalcount,
                'notpay' => $notpay,
                'selfnotpay' => $selfnotpay,
                'selftotalnotpay' => $selftotalnotpay,
                'maxbuy' => $maxbuy,
                'totalmaxbuy' => $totalmaxbuy,
                'tag' => $tag,
                'time' => $sktime,
                'options' => $options,
                'starttime' => $goods_starttime,
                'endtime' => $goods_endtime,
                'price' => $price,
                'percent' => $percent,
                'status' => $status
            );
        }
        return false;
    }


    function orderRefund($item)
    {

        $shopset = m('common')->getSysset('shop');
        //退款金额
        $realprice = $item['price'];
        $refundtype = 0;
        $refundno = 'SKR' . date('YmdH:i:s') . random(4, true);

        //购物积分
        $goods = pdo_fetchall("SELECT g.id,g.credit, o.total,o.realprice FROM " . tablename('ewei_shop_order_goods') .
            " o left join " . tablename('ewei_shop_goods') . " g on o.goodsid=g.id " . " WHERE o.orderid=:orderid and o.uniacid=:uniacid", array(':orderid' => $item['id'], ':uniacid' => $item['uniacid']));

        if ($item['paytype'] == 1) {
            //余额支付，直接返回余额
            m('member')->setCredit($item['openid'], 'credit2', $realprice, array(0, $shopset['name'] . "秒杀退款: {$realprice}元 订单号: " . $item['ordersn']));
            $result = true;

        } else if ($item['paytype'] == 21) {


            //微信支付，走退款 接口
            //直接退还扣除减掉余额抵扣
            $realprice = round($realprice - $item['deductcredit2'], 2);

            if ($realprice > 0) {
                $result = m('finance')->refund($item['openid'], $item['ordersn'], $refundno, $realprice * 100, $realprice * 100, !empty($item['apppay']) ? true : false);
                if (is_error($result)){
                    $result = m('finance')->refundBorrow($item['borrowopenid'], $item['ordersn'], $refundno, $realprice * 100, $realprice * 100, !empty($item['ordersn2']) ? 1 : 0);
                }
            }
            $refundtype = 2;

        }


        //计算订单中商品累计赠送的积分
        $credits = m('order')->getGoodsCredit($goods);

        //扣除会员购物赠送积分
        if ($credits > 0) {
            m('member')->setCredit($item['openid'], 'credit1', -$credits, array(0, $shopset['name'] . "退款扣除购物赠送积分: {$credits} 订单号: " . $item['ordersn']));
        }

        //返还抵扣积分
        if ($item['deductcredit'] > 0) {
            m('member')->setCredit($item['openid'], 'credit1', $item['deductcredit'], array('0', $shopset['name'] . "购物返还抵扣积分 积分: {$item['deductcredit']} 抵扣金额: {$item['deductprice']} 订单号: {$item['ordersn']}"));
        }
        if (!empty($refundtype)) {
            //在线支付，返还余额抵扣
            if ($realprice < 0) {
                $item['deductcredit2'] = $realprice;
            }
            m('order')->setDeductCredit2($item);
        }

        //处理赠送余额情况
        m('order')->setGiveBalance($item['id'], 2);

        //退还优惠券
        if (com('coupon') && !empty($item['couponid'])) {
            com('coupon')->returnConsumeCoupon($item['id']); //申请退款成功
        }

        //更新订单退款状态
        pdo_update('ewei_shop_order', array('refundstate' => 0, 'status' => -1, 'refundtime' => time()), array('id' => $item['id'], 'uniacid' => $item['uniacid']));

        //更新实际销量
        foreach ($goods as $g) {
            //实际销量
            $salesreal = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('ewei_shop_order_goods') . ' og '
                . ' left join ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid '
                . ' where og.goodsid=:goodsid and o.status>=1 and o.uniacid=:uniacid limit 1', array(':goodsid' => $g['id'], ':uniacid' => $item['uniacid']));
            pdo_update('ewei_shop_goods', array('salesreal' => $salesreal), array('id' => $g['id']));
        }

        //模板消息
        m('notice')->sendOrderMessage($item['id'], true);

    }

    function setOrderPay($orderid)
    {

        global $_W;
        if (is_error(redis())) {
            return false;
        }
        $redis = redis();
        $redis_prefix = $this->get_prefix();
        $date = date('Y-m-d');
        $order = pdo_fetch('select id,ordersn, price,openid,status, paytype, deductcredit2, couponid,isparent,merchid,agentid,createtime from ' . tablename('ewei_shop_order') . ' where  id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $orderid));

        if (empty($order)) {
            return "";
        }

        if (intval($order['status']) < 0){//订单已关闭
            $this->orderRefund($order);
            return 'refund';
        }

        $goods = pdo_fetchall('select uniacid,total,goodsid,optionid,seckill_timeid,seckill_taskid from  ' . tablename('ewei_shop_order_goods') . " where orderid={$order['id']} and  seckill=1 ");

        foreach ($goods as $g) {

            $key =  "{$redis_prefix}queue_{$date}_{$g['seckill_taskid']}_{$g['seckill_timeid']}_{$g['goodsid']}_{$g['optionid']}";

            $queue = $redis->lGetRange($key, 0, -1);

            $paydata = json_encode(array('orderid' => $order['id'], 'openid' => $order['openid'], 'status' => 1, 'createtime' => $order['createtime']));

            if (empty($queue)) {
                //付款成功，但是结束，应该退款
                $this->orderRefund($order);
                return 'refund';
            } else {

                $has = false;
                foreach ($queue as $index => $value) {
                    $data = @json_decode($value, true);
                    if (!is_array($data)) {
                        continue;
                    }
                    if ($data['orderid'] == $order['id'] && $data['openid'] == $order['openid']) {
                        $has = true;
                        $redis->lSet($key, $index, $paydata);
                    }
                }
                if (!$has) {
                    //退款
                    $this->orderRefund($order);
                    return 'refund';
                }
            }
        }
        return "";
    }

    function getTaskSeckillInfo()
    {

        global $_W;
        if (is_error(redis())) {
            return false;
        }

        if (empty($taskid)) {
            $taskid = $this->getTodaySeckill();
            if (empty($taskid)) {
                return false;
            }
        }
        $task = $this->getTodaySeckillInfo();
        if (empty($taskid)) {
            return false;
        }
        $redis = redis();
        $times = $this->getTaskTimes($taskid);

        $options = array();
        $currenttime = time();
        $timegoods = array();
        $goods_starttime = 0;
        $goods_endtime = 0;
        $hasCurrent = false;
        $timegoods = false;
        $status = -1;

        foreach ($times as $key => $time) {


            $starttime = strtotime(date("Y-m-d {$time['time']}:00:00"));

            if (isset($times[$key + 1])) {

                $end = $times[$key + 1]['time'] - 1;
                $endtime = strtotime(date("Y-m-d {$end}:59:59"));

            } else {
                $endtime = strtotime(date("Y-m-d 23:59:59"));
            }
            $time['endtime'] = $endtime;
            $time['starttime'] = $starttime;

            if ($currenttime >= $starttime && $currenttime <= $endtime) {

                $timeid = $time['id'];

                $taskid = $time['taskid'];
                $goods_starttime = $starttime;
                $goods_endtime = $endtime;
                $sktime = $time['time'];
                $timegoods = $this->getSeckillGoods($taskid, $time['time'], 'all');
                $hasCurrent = true;
                $status = 0;
                break;

            } else if( $currenttime <$starttime){

                $timeid = $time['id'];

                $taskid = $time['taskid'];
                $goods_starttime = $starttime;
                $goods_endtime = $endtime;
                $sktime = $time['time'];
                $timegoods = $this->getSeckillGoods($taskid, $time['time'], 'all');
                $hasCurrent = true;
                $status = 1;
                break;
            }
        }
        if (empty($timegoods)) {
            return false;
        }

        shuffle($timegoods);
        $allgoods = array();
        foreach ($timegoods as $g) {
            if (count($allgoods) >= 10) {
                break;
            }
            if (!in_array($g['goodsid'], $allgoods)) {
                $allgoods[$g['goodsid']][] = array('optionid' => $g['optionid'], 'price' => $g['price']);
            }

        }

        $goodsids = array_keys($allgoods);

        $dbgoods = pdo_fetchall('select id,thumb,marketprice from ' . tablename('ewei_shop_goods') . ' where id in (' . implode(',', $goodsids) . ') and uniacid=' . $_W['uniacid'], array(), 'id');
        $goods = array();
        foreach ($allgoods as $gid => $tgs) {


            $price = $tgs[0]['price'];
            foreach ($tgs as $tg) {
                if ($price > $tg['price']) {
                    $price = $tg['price'];
                }
            }
            $goods[] = array(
                'thumb' => tomedia($dbgoods[$gid]['thumb']),
                'price' => price_format($price),
                'marketprice' => price_format($dbgoods[$gid]['marketprice']),
            );
        }

        return array(
            'tag' => $task['tag'],
            'status'=>$status,
            'time' => $sktime < 10 ? '0' . $sktime : $sktime,
            'endtime' => $goods_endtime,
            'starttime' => $goods_starttime,
            'goods' => $goods
        );

    }

    public function checkBuy($seckillinfo, $title, $unit = '')
    {
        if (empty($unit)) {
            $unit = '件';
        }
        if ($seckillinfo['percent'] >= 100) {
            if ($seckillinfo['notpay'] > 0) {
                return error(-1, $title . '<br/> 已经抢完了 ，但还有 ' . $seckillinfo['notpay'] . " {$unit}未付款的, 抓住机会哦~");
            } else {
                return error(-1, $title . '<br/> 已经抢完了 !');
            }

        } else {
            if ($seckillinfo['totalmaxbuy'] > 0) {
                if ($seckillinfo['selftotalcount'] >= $seckillinfo['totalmaxbuy']) {

                    if ($seckillinfo['selftotalnotpay'] > 0) {
                        return error(-1, $title . '<br/> 最多抢购 ' . $seckillinfo['totalmaxbuy'] . ' ' . $unit . ",  您有{$seckillinfo['selftotalnotpay']}个未付款的，抓紧付款哦~");
                    } else {
                        return error(-1, $title . '<br/> 您已经抢购 ' . $seckillinfo['totalmaxbuy'] . ' ' . $unit . "了哦，不能继续抢购了，看看别的吧!");
                    }
                }

            }

            if ($seckillinfo['maxbuy'] > 0) {
                if ($seckillinfo['selfcount'] >= $seckillinfo['maxbuy']) {

                    if ($seckillinfo['selfnotpay'] > 0) {
                        return error(-1, $title . '<br/> 最多抢购 ' . $seckillinfo['maxbuy'] . ' ' . $unit . ",  您有{$seckillinfo['selfnotpay']}个未付款的，抓紧付款哦~");
                    } else {
                        return error(-1, $title . '<br/> 您已经抢购 ' . $seckillinfo['maxbuy'] . ' ' . $unit . "了哦，不能继续抢购了，看看别的吧!");
                    }
                }
            }
        }
        return true;

    }

    public function checkTaskGoods($taskid, $roomid, $goodsids)
    {

        if (is_error(redis())) {

            return false;
        }
        if (empty($goodsids)) {

            return true;
        }

        $error = array();
        $times = $this->getTaskTimes($taskid);
        if (!is_array($times)) {
            return true;
        }
        foreach ($times as $time) {
            $goods = $this->getSeckillGoods($taskid, $time['time'], 'all');
            if (!is_array($goods)) {
                continue;
            }
            foreach ($goods as $g) {
                if (in_array($g['goodsid'], $goodsids) && $g['roomid'] != $roomid) {
                    $room = $this->getRoomInfo($taskid, $g['roomid']);
                    $goodstitle = pdo_fetchcolumn('select title from ' . tablename('ewei_shop_goods') . ' where id=:id limit 1', array(':id' => $g['goodsid']));
                    $url = webUrl('seckill/room/edit', array('taskid' => $taskid, 'id' => $room['id']));
                    if (!isset($error['goods-' . $g['goodsid']])) {
                        $error['goods-' . $g['goodsid']] = "商品&lt;span class='text text-danger'&gt;【{$goodstitle}】&lt;/span&gt;在会场&lt;a href='{$url}' target='_blank'&gt;【{$room['title']}】&lt;/a&gt;中的 &lt;span class='text text-danger'&gt;{$time['time']}&lt;/span&gt; 点场已经存在，不能重复添加";
                    }
                }
            }
        }
        if (!empty($error)) {
            return error(-1, implode('<br/>', $error));
        }
        return true;
    }
}
