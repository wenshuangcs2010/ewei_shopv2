<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Dispatch_EweiShopV2Model {

    /**
     * 计算运费
     * @param type $param 重量或者是数量
     * @param type $d
     * @param type $calculatetype -1默认读取$d中的calculatetype值 1按数量计算运费 0按重量计算运费
     */
    function getDispatchPrice($param, $d, $calculatetype = -1) {

        if (empty($d)) {
            return 0;
        }
        $price = 0;
        if ($calculatetype == -1) {
            $calculatetype = $d['calculatetype'];
        }
        if ($calculatetype == 1) {
            if ($param <= $d['firstnum']) {
                $price = floatval($d['firstnumprice']);
            } else {

                $price = floatval($d['firstnumprice']);

                $secondweight = $param - floatval($d['firstnum']);
                $dsecondweight = floatval($d['secondnum']) <= 0 ? 1 : floatval($d['secondnum']);
                $secondprice = 0;
                if ($secondweight % $dsecondweight == 0) {
                    $secondprice = ($secondweight / $dsecondweight ) * floatval($d['secondnumprice']);
                } else {
                    $secondprice = ((int) ( $secondweight / $dsecondweight ) + 1) * floatval($d['secondnumprice']);
                }

                $price+=$secondprice;
            }
        } else {
            if ($param <= $d['firstweight']) {
                if ($param > 0) {
                    $price = floatval($d['firstprice']);
                } else {
                    $price = 0;
                }
            } else {
                $price = floatval($d['firstprice']);

                $secondweight = $param - floatval($d['firstweight']);
                $dsecondweight = floatval($d['secondweight']) <= 0 ? 1 : floatval($d['secondweight']);
                $secondprice = 0;
                if ($secondweight % $dsecondweight == 0) {
                    $secondprice = ($secondweight / $dsecondweight ) * floatval($d['secondprice']);
                } else {
                    $secondprice = ((int) ( $secondweight / $dsecondweight ) + 1) * floatval($d['secondprice']);
                }

                $price+=$secondprice;
            }
        }

        return $price;
    }

    function getCityDispatchPrice($areas, $city, $param, $d) {
        if (is_array($areas) && count($areas) > 0) {
            foreach ($areas as $area) {
                $citys = explode(";", $area['citys']);
                if (in_array($city, $citys) && !empty($citys)) {
                    //如果此条包含 用户城市
                    return $this->getDispatchPrice($param, $area, $d['calculatetype']);
                }
            }
        }
        return $this->getDispatchPrice($param, $d);
    }

    /**
     * 获取默认快递信息
     */
    function getDefaultDispatch($merchid = 0,$isdis=0,$goods_id=0) {
        global $_W;
        $type=Dispage::get_disType($isdis,$_W['uniacid']);
        $depotid=Dispage::get_depotid($type,$goods_id);
        if($type){
            $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where isdefault=1 and uniacid=:uniacid and depotid=:depotid and merchid=:merchid and enabled=1 Limit 1';
             $params = array(':uniacid' => DIS_ACCOUNT, ':merchid' => $merchid,'depotid'=>$depotid);
        }else{
            $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where isdefault=1 and uniacid=:uniacid and merchid=:merchid and enabled=1 and depotid=:depotid Limit 1';
            $params = array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid,'depotid'=>$depotid);
        }
        
        $data = pdo_fetch($sql, $params);
        return $data;
    }

    /**
     * 获取最新的一条快递信息
     */
    function getNewDispatch($merchid = 0) {
        global $_W;

        $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by id desc Limit 1';
        $params = array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid);
        $data = pdo_fetch($sql, $params);

        return $data;
    }

    /**
     * 获取一条快递信息wsq
     */
    function getOneDispatch($id,$isdis=0) {
        global $_W;
        /*
        $params = array(':uniacid' => $_W['uniacid']);
        if ($id==0)
        {
            $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where isdefault=1 and uniacid=:uniacid and enabled=1 Limit 1';
        }
        else
        {
            $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where id=:id and uniacid=:uniacid and enabled=1 Limit 1';
            $params[':id'] = $id;
        }*/
        if($isdis>0 && $_W['uniacid']!=DIS_ACCOUNT){
            $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where id=:id and uniacid=:uniacid and enabled=1 Limit 1';
            $params[':uniacid'] = DIS_ACCOUNT;
            $params[':id'] = $id;
        }else{
            $sql = 'select * from ' . tablename('ewei_shop_dispatch') . ' where id=:id and uniacid=:uniacid and enabled=1 Limit 1';
            $params[':id'] = $id;
            $params[':uniacid'] = $_W['uniacid'];
        }
        $data = pdo_fetch($sql, $params);
        return $data;
    }

    function getAllNoDispatchAreas($areas = array()) {
        global $_W;

        $tradeset = m('common')->getSysset('trade');
        $tradeset['nodispatchareas'] = iunserializer($tradeset['nodispatchareas']);

        $set_citys = array();
        $dispatch_citys = array();

        if (!empty($tradeset['nodispatchareas'])) {
            $set_citys = explode(";", trim($tradeset['nodispatchareas'], ';'));
        }

        if (!empty($areas)) {
            $areas = iunserializer($areas);
            if (!empty($areas)) {
                $dispatch_citys = explode(";", trim($areas, ';'));
            }
        }

        $citys = array();
        if (!empty($set_citys)) {
            $citys = $set_citys;
        }

        if (!empty($dispatch_citys)) {
            $citys = array_merge($citys, $dispatch_citys);
            $citys = array_unique($citys);
        }

        return $citys;
    }


    function getNoDispatchAreas($goods) {
        global $_W;

        if ($goods['type'] == 2 || $goods['type'] == 3) {
            //虚拟物品或虚拟卡密
            return '';
        }
        if ($goods['dispatchtype'] == 1) {
            //统一运费
            $nodispatchareas = $this->getAllNoDispatchAreas();
        } else {
            //运费模板

            if (empty($goods['dispatchid'])) {
                //默认快递
                $dispatch = m('dispatch')->getDefaultDispatch($goods['merchid']);
            } else {
                $dispatch = m('dispatch')->getOneDispatch($goods['dispatchid']);
            }

            if (empty($dispatch)) {
                //最新的一条快递信息
                $dispatch = m('dispatch')->getNewDispatch($goods['merchid']);
            }

            $nodispatchareas = $this->getAllNoDispatchAreas($dispatch['nodispatchareas']);
        }
        return $nodispatchareas;
    }
}
