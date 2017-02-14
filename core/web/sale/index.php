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

    public function __construct()
    {
        if (!com("perm")->check_com('sale'))
        {
            if(cv('sale.coupon')){
                header('location: '.webUrl('sale/coupon'));
            }else
            {
                $this->message("你没有相应的权限查看");
            }
        }
    }

    function main() { 

		if(cv('sale.enough')){
			header('location: '.webUrl('sale/enough'));
		}
		elseif(cv('sale.enoughfree')){
			header('location: '.webUrl('sale/enoughfree'));
		}
        else if(cv('sale.deduct')){
            header('location: '.webUrl('sale/deduct'));
        }
		elseif(cv('sale.recharge')){
			header('location: '.webUrl('sale/recharge'));
		}
		elseif(cv('sale.coupon')){
			header('location: '.webUrl('sale/coupon'));
		} 
		else{
			header('location: ' . webUrl());
		}
    }

    function deduct() {
        global $_W, $_GPC;
        if( !function_exists("redis") || is_error(redis())){
            $this->message('请联系系统管理员设置 Redis 才能使用抵扣!','','error');
        }

        if ($_W['ispost']) {

            $post = is_array($_GPC['data']) ? $_GPC['data'] : array();
            $data['creditdeduct'] = intval($post['creditdeduct']);
            $data['credit'] = 1;
                $data['moneydeduct'] = intval($post['moneydeduct']);
                $data['money'] = round(floatval($post['money']), 2);
                $data['dispatchnodeduct'] = intval($post['dispatchnodeduct']);
            plog('sale.deduct', '修改抵扣设置');
            m('common')->updatePluginset(array('sale' => $data));
            show_json(1);
        }


        $data = m('common')->getPluginset('sale');


        load()->func('tpl');
        include $this->template('sale/index');
    }

    function enough() {
        global $_W, $_GPC;

        if ($_W['ispost']) {

            $data = is_array($_GPC['data']) ? $_GPC['data'] : array();

            $data['enoughmoney'] = round(floatval($data['enoughmoney']), 2);
            $data['enoughdeduct'] = round(floatval($data['enoughdeduct']), 2);
            $enoughs = array();
            $postenoughs = is_array($_GPC['enough']) ? $_GPC['enough'] : array();
            foreach ($postenoughs as $key => $value) {
                $enough = floatval($value);
                if ($enough > 0) {
                    $enoughs[] = array(
                        'enough' => floatval($_GPC['enough'][$key]),
                        'give' => floatval($_GPC['give'][$key])
                    );
                }
            }
            $data['enoughs'] = $enoughs;
            plog('sale.enough', '修改满额立减优惠');
            m('common')->updatePluginset(array('sale' => $data));
            show_json(1);
        }
        $areas = m('common')->getAreas();
        $data = m('common')->getPluginset('sale');
        load()->func('tpl');
        include $this->template();
    }

    function enoughfree() {
        global $_W, $_GPC;

        if ($_W['ispost']) {


            $data = is_array($_GPC['data']) ? $_GPC['data'] : array();
            $data['enoughfree'] = intval($data['enoughfree']);
            $data['enoughorder'] = round(floatval($data['enoughorder']), 2);
            $data['goodsids'] = $_GPC['goodsids'];
            plog('sale.enough', '修改满额包邮优惠');
            m('common')->updatePluginset(array('sale' => $data));
            show_json(1);
        }
        $data = m('common')->getPluginset('sale');
        if (!empty($data['goodsids'])){
            $goods = pdo_fetchall("SELECT id,uniacid,title,thumb FROM ".tablename('ewei_shop_goods')." WHERE uniacid=:uniacid AND id IN (".implode(',',$data['goodsids']).")",array(':uniacid'=>$_W['uniacid']));
        }
        $areas = m('common')->getAreas();
        include $this->template();
    }

    function recharge() {
        global $_W, $_GPC;

        if ($_W['ispost']) {

            $recharges = array();
            $datas = is_array($_GPC['enough']) ? $_GPC['enough'] : array();
            foreach ($datas as $key => $value) {
                $enough = trim($value);
                if (!empty($enough)) {
                    $recharges[] = array(
                        'enough' => trim($_GPC['enough'][$key]),
                        'give' => trim($_GPC['give'][$key])
                    );
                }
            }

            $data['recharges'] = iserializer($recharges);
            m('common')->updatePluginset(array('sale' => $data));
            plog('sale.recharge', '修改充值优惠设置');
            show_json(1);
        }

        $data = m('common')->getPluginset('sale');
        $recharges = iunserializer($data['recharges']);
        include $this->template();
    }

    public function credit1()
    {
        global $_W, $_GPC;

        if ($_W['ispost']) {

            $enough1 = array();
            $postenough1 = is_array($_GPC['enough1']) ? $_GPC['enough1'] : array();
            foreach ($postenough1 as $key => $value) {
                $enough = floatval($value);
                if ($enough > 0) {
                    $enough1[] = array(
                        'enough1' => floatval($_GPC['enough1'][$key]),
                        'give1' => floatval($_GPC['give1'][$key])
                    );
                }
            }
            $data['enough1'] = $enough1;

            $enough2 = array();
            $postenough2 = is_array($_GPC['enough2']) ? $_GPC['enough2'] : array();
            foreach ($postenough2 as $key => $value) {
                $enough = floatval($value);
                if ($enough > 0) {
                    $enough2[] = array(
                        'enough2' => floatval($_GPC['enough2'][$key]),
                        'give2' => floatval($_GPC['give2'][$key])
                    );
                }
            }

            if (!empty($enough2)){
                m('common')->updateSysset(array('trade' => array('credit'=>0)));
            }
            $data['enough1'] = $enough1;
            $data['enough2'] = $enough2;
            $data['paytype'] = is_array($_GPC['paytype']) ? $_GPC['paytype'] : array();
            m('common')->updatePluginset(array('sale' => array('credit1'=>iserializer($data))));
            plog('sale.credit1.edit', '修改基本积分活动配置');
            show_json(1,array('url'=>webUrl('sale/credit1', array('tab'=>str_replace("#tab_","",$_GPC['tab'])))));
        }
        $data = m('common')->getPluginset('sale');
        $credit1 = iunserializer($data['credit1']);
        $enough1 = empty($credit1['enough1']) ? array() : $credit1['enough1'];
        $enough2 = empty($credit1['enough2']) ? array() : $credit1['enough2'];
        include $this->template();
    }

}