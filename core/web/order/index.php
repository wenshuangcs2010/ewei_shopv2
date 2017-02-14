<?php
/**
 * Created by Yang.
 * User: pc
 * Date: 2016/3/21
 * Time: 20:07
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage
{

    function main()
    {
        global $_W;
        include $this->template();
    }

    /**
     * 查询订单金额
     * @param int $day 查询天数
     * @return bool
     */
    protected function selectOrderPrice($day=0)
    {
        global $_W;
        $day = (int)$day;

        if($day != 0)
        {
            $createtime1 = strtotime(date('Y-m-d',time()-$day*3600*24));
            $createtime2 = strtotime(date('Y-m-d',time()));
        }
        else
        {
            $createtime1 = strtotime(date('Y-m-d',time()));
            $createtime2 = strtotime(date('Y-m-d',time()+3600*24));
        }
        $sql = 'select id,price,createtime from '.tablename('ewei_shop_order').' where uniacid = :uniacid and ismr=0 and isparent=0 and (status > 0 or ( status=0 and paytype=3)) and deleted=0 and createtime between :createtime1 and :createtime2';
        $param = array(
            ':uniacid'=>$_W['uniacid'],
            ':createtime1'=>$createtime1,
            ':createtime2'=>$createtime2,
        );
        $pdo_res = pdo_fetchall($sql,$param);
        $price = 0;
        foreach ($pdo_res as $arr){
            $price += $arr['price'];
        }
        $result = array(
            'price' => round($price,1),
            'count' => count($pdo_res),
            'fetchall' => $pdo_res,
        );
        return $result;
    }

    /**
     * 查询近七天交易记录
     * @param array $pdo_fetchall 查询订单的记录
     * @param int $days 查询天数默认7
     * @return $transaction["price"] 七日 每日交易金额
     * @return $transaction["count"] 七日 每日交易订单数
     */
    protected function selectTransaction(array $pdo_fetchall,$days=7)
    {
        $transaction = array();
        $days = (int)$days;
        if (!empty($pdo_fetchall))
        {
            for ($i = $days; $i >=1;$i--)
            {
                $transaction['price'][date("Y-m-d",time()-$i*3600*24)] = 0;
                $transaction['count'][date("Y-m-d",time()-$i*3600*24)] = 0;
            }
            foreach($pdo_fetchall as $key=>$value)
            {
                if ( array_key_exists(date("Y-m-d",$value['createtime']),$transaction['price']) )
                {
                    $transaction['price'][date("Y-m-d",$value['createtime'])] += $value['price'];
                    $transaction['count'][date("Y-m-d",$value['createtime'])] += 1;
                }
            }
            return $transaction;
        }
        return array();
    }

    /**
     * ajax return 交易订单
     */
    protected function order($day)
    {
        global $_GPC;
        $day = (int)$day;
        $orderPrice = $this->selectOrderPrice($day);
        $orderPrice['avg'] = empty($orderPrice['count']) ? 0 : round($orderPrice['price']/$orderPrice['count'],1);
        unset($orderPrice['fetchall']);
        return $orderPrice;
    }

    public function ajaxorder()
    {
        $order0 = $this->order(0);
        $order1 = $this->order(1);
        $order7 = $this->order(7);
        $order30 = $this->order(30);
        $order7['price'] = $order7['price']+$order0['price'];
        $order7['count'] = $order7['count']+$order0['count'];
        $order7['avg'] = empty($order7['count']) ? 0 : round($order7['price']/$order7['count'],1);
        $order30['price'] = $order30['price'] + $order0['price'];
        $order30['count'] = $order30['count'] + $order0['count'];
        $order30['avg'] = empty($order30['count']) ? 0 : round($order30['price']/$order30['count'],1);
        show_json(1,array(
            'order0'=>$order0,
            'order1'=>$order1,
            'order7'=>$order7,
            'order30'=>$order30,
        ));
    }

    /**
     * ajax return 七日交易记录.近7日交易时间,交易金额,交易数量
     */
    function ajaxtransaction()
    {
        $orderPrice = $this->selectOrderPrice(7);
        $transaction = $this->selectTransaction($orderPrice['fetchall'],7);
        if (empty($transaction))
        {
            for ($i = 7; $i >=1;$i--)
            {
                $transaction['price'][date("Y-m-d",time()-$i*3600*24)] = 0;
                $transaction['count'][date("Y-m-d",time()-$i*3600*24)] = 0;
            }
        }
        echo json_encode(array(
            'price_key'=>array_keys($transaction['price']),//交易时间
            'price_value'=>array_values($transaction['price']),//交易金额
            'count_value'=>array_values($transaction['count'])//交易数量
        ));
    }
}