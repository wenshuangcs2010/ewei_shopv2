<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends MobileLoginPage {

    //多商户
    protected function merchData() {
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch']) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }

        return array(
            'is_openmerch' => $is_openmerch,
            'merch_plugin' => $merch_plugin,
            'merch_data' => $merch_data
        );
    }

    function main() {
        global $_W, $_GPC;

        $openid = $_W['openid'];
        $cateid = trim($_GPC['catid']);
        $set = m('common')->getPluginset('coupon');
        if(!empty($set['closecenter'])){
            header('location: '.mobileUrl('member'));
            exit;
        }

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        // 读取幻灯片
        $advs = is_array($set['advs']) ? $set['advs'] : array();
        $shop = m('common')->getSysset('shop');

        // 读取分类

        $param = array();
        $param[':uniacid'] = $_W['uniacid'];
        $sql = 'select * from ' . tablename('ewei_shop_coupon_category') . ' where uniacid=:uniacid';
        if ($is_openmerch == 0) {
            $sql .= ' and merchid=0';
        } else {
            if (!empty($_GPC['merchid'])) {
                $sql .= ' and merchid=:merchid';
                $param[':merchid'] = intval($_GPC['merchid']);
            }
        }

        $sql .= ' and status=1 order by displayorder desc';
        $category = pdo_fetchall($sql, $param);

        include $this->template();
    }

    function getlist(){
        global $_W, $_GPC;

        //多商户
        $merchdata = $this->merchData();
        extract($merchdata);

        // 读取 优惠券
        $cateid = trim($_GPC['cateid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $time = time();

        $param = array();
        $param[':uniacid'] = $_W['uniacid'];

        $sql = "select id,timelimit,coupontype,timedays,timestart,timeend,thumb,couponname,enough,backtype,deduct,discount,backmoney,backcredit,backredpack,bgcolor,thumb,credit,money,getmax,merchid,total as t,tagtitle,settitlecolor,titlecolor from " . tablename('ewei_shop_coupon') . " c ";
        $sql.=" where uniacid=:uniacid";
        if ($is_openmerch == 0) {
            $sql .= ' and merchid=0';
        }else {
            if (!empty($_GPC['merchid'])) {
                $sql .= ' and merchid=:merchid';
                $param[':merchid'] = intval($_GPC['merchid']);
            }
        }

        //分销商限制
        $plugin_com = p('commission');
        if ($plugin_com) {
            $plugin_com_set = $plugin_com->getSet();
            if(empty($plugin_com_set['level']))
            {
                $sql .= ' and ( limitagentlevels = "" or  limitagentlevels is null )';
            }
        }
        else
        {
            $sql .= ' and ( limitagentlevels = "" or  limitagentlevels is null )';
        }

        //股东限制
        $plugin_globonus = p('globonus');
        if ($plugin_globonus) {
            $plugin_globonus_set = $plugin_globonus->getSet();
            if(empty($plugin_globonus_set['open']))
            {
                $sql .= ' and ( limitpartnerlevels = ""  or  limitpartnerlevels is null )';
            }
        }
        else
        {
            $sql .= ' and ( limitpartnerlevels = ""  or  limitpartnerlevels is null )';
        }

        //区域代理限制
        $plugin_abonus = p('abonus');
        if ($plugin_abonus) {
            $plugin_abonus_set = $plugin_abonus->getSet();
            if(empty($plugin_abonus_set['open']))
            {
                $sql .= ' and ( limitaagentlevels = "" or  limitaagentlevels is null )';
            }
        }
        else
        {
            $sql .= ' and ( limitaagentlevels = "" or  limitaagentlevels is null )';
        }


        $sql.=" and gettype=1 and (total=-1 or total>0) and ( timelimit = 0 or  (timelimit=1 and timeend>unix_timestamp()))";
        if (!empty($cateid)) {
            $sql.=" and catid=" . $cateid;
        }
        $total = pdo_fetchcolumn($sql, $param);
        $sql.=" order by displayorder desc, id desc  LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $coupons = set_medias(pdo_fetchall($sql, $param), 'thumb');
        if(empty($coupons))
        {
            $coupons=array();
        }
        foreach ($coupons as $i=>&$row) {
            $row = com('coupon')->setCoupon($row, $time);
            $last= com('coupon')->get_last_count($row['id']);

            if($row['t']!=-1)
            {
                if($last <=0)
                {
                    $row['last']= $last;
                    $row['isdisa']='1';
                }
                else
                {
                    $totle = $row['t'];
                    $row['last']= $last;
                    $row['lastratio']= intval($last/$totle*100);
                }
            }else
            {
                $row['last']= 1;
                $row['lastratio']= 100;
            }

            $title2='';
            $title3='';
            $title4='';
            $tagtitle = '';
            if($row['coupontype']=='0')
            {
                if($row['enough']>0)
                {
                    $title2 ='满'.((float)$row['enough']).'元可用';
                }
                else
                {
                    $title2 = '无金额门槛';
                }
            }
            elseif($row['coupontype']=='1')
            {
                if($row['enough']>0)
                {
                    $title2 ='充值满'.((float)$row['enough']).'元可用';
                }else
                {
                    $title2 = '无金额门槛';
                }
            } if($row['coupontype']=='2')
            {
                if($row['enough']>0)
                {
                    $title2 ='满'.((float)$row['enough']).'元可用';
                }
                else
                {
                    $title2 = '无金额门槛';
                }
            }


            if($row['backtype']==0)
            {

                $title3='<span>'.((float)$row['deduct']).'</span>';
                if($row['enough']=='0')
                {
                    $row['color']='org ';
                    $tagtitle = '代金券';
                }
                else
                {
                    $row['color']='blue';
                    $tagtitle = '满减券';
                }
            }
            if($row['backtype']==1)
            {
                $row['color']='red ';
                $title3='<span>'.((float)$row['discount']).'</span>折 ';
                $tagtitle = '打折券';
            }
            if($row['backtype']==2)
            {
                if($row['coupontype']=='0')
                {
                    $row['color']='red ';
                    $tagtitle = '购物返现券';
                }
                else if($row['coupontype']=='1')
                {
                    $row['color']='pink ';
                    $tagtitle = '充值返现券';
                }
                else if($row['coupontype']=='2')
                {
                    $row['color']='red ';
                    $tagtitle = '购物返现券';
                }

                if (!empty($row['backmoney']) && $row['backmoney'] > 0) {
                    $title3 =  '送<span>'.$row['backmoney'].'</span>元余额 ';
                }
                elseif (!empty($row['backcredit']) && $row['backcredit'] > 0) {
                    $title3 =  '送<span>'.$row['backcredit'].'</span>积分 ';
                }
                elseif (!empty($row['backredpack']) && $row['backredpack'] > 0) {
                    $title3 =  '送<span>'.$row['backredpack'].'</span>元红包 ';
                }
            }
            if($row['tagtitle']=='')
            {
               $row['tagtitle'] =  $tagtitle;
            }

            if ($row['timestr']=='0')
            {
                $title4='永久有效';
            }
            else if($row['timestr']=='1')
            {
                $title4 ='即'.$row['gettypestr'].'日内'. $row['timedays'].'天有效';
            }else
            {
                $title4 ='有效期 '.$row['timestr'];
            }

            $row['title2']= $title2;
            $row['title3']= $title3;
            $row['title4']= $title4;
        }

        unset($row);
        show_json(1, array('list' => $coupons, 'pagesize' => $psize, 'total'=>$total));
    }

}
