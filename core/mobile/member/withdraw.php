<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Withdraw_EweiShopV2Page extends MobileLoginPage {

    function main() {

        global $_W, $_GPC;
        //参数
        $set = $_W['shopset']['trade'];
        $status = 1;

        $openid = $_W['openid'];

        if (empty($set['withdraw'])) {
            $this->message('系统未开启提现!', '', 'error');

        }

        $withdrawcharge = $set['withdrawcharge'];
        $withdrawbegin = floatval($set['withdrawbegin']);
        $withdrawend = floatval($set['withdrawend']);

        //当前余额
        $credit = m('member')->getCredit($_W['openid'], 'credit2');

        $last_data = $this->getLastApply($openid);

        //提现方式
        $type_array = array();

        if($set['withdrawcashweixin'] == 1) {
            $type_array[0]['title'] = '提现到微信钱包';
        }

        if($set['withdrawcashalipay'] == 1) {
            $type_array[2]['title'] = '提现到支付宝';

            if (!empty($last_data)) {
                if ($last_data['applytype'] != 2) {
                    $type_last = $this->getLastApply($openid, 2);

                    if (!empty($type_last)) {
                        $last_data['alipay'] = $type_last['alipay'];
                    }
                }
            }
        }

        if($set['withdrawcashcard'] == 1) {
            $type_array[3]['title'] = '提现到银行卡';

            if (!empty($last_data)) {
                if ($last_data['applytype'] != 3) {
                    $type_last = $this->getLastApply($openid, 3);
                    if (!empty($type_last)) {
                        $last_data['bankname'] = $type_last['bankname'];
                        $last_data['bankcard'] = $type_last['bankcard'];
                    }
                }
            }

            $condition = " and uniacid=:uniacid";
            $params = array(':uniacid' => $_W['uniacid']);
            $banklist = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_commission_bank') . " WHERE 1 {$condition}  ORDER BY displayorder DESC", $params);
        }

        if (!empty($last_data)) {
            if (array_key_exists($last_data['applytype'], $type_array)) {
                $type_array[$last_data['applytype']]['checked'] = 1;
            }
        }

        include $this->template();
    }

    function submit() {
        global $_W, $_GPC;

        $set = $_W['shopset']['trade'];
        if (empty($set['withdraw'])) {
            show_json(0,'系统未开启提现!');
        }

        $set_array = array();
        $set_array['charge'] = $set['withdrawcharge'];
        $set_array['begin'] = floatval($set['withdrawbegin']);
        $set_array['end'] = floatval($set['withdrawend']);

        $money = floatval($_GPC['money']);
        $credit = m('member')->getCredit($_W['openid'], 'credit2');

        if($money <= 0){
            show_json(0,'提现金额错误!');
        }

        if ($money > $credit) {
            show_json(0, '提现金额过大!');
        }


        //生成申请
        $apply = array();

        //提现方式
        $type_array = array();

        if($set['withdrawcashweixin'] == 1) {
            $type_array[0]['title'] = '提现到微信钱包';
        }

        if($set['withdrawcashalipay'] == 1) {
            $type_array[2]['title'] = '提现到支付宝';
        }

        if($set['withdrawcashcard'] == 1) {
            $type_array[3]['title'] = '提现到银行卡';
            $condition = " and uniacid=:uniacid";
            $params = array(':uniacid' => $_W['uniacid']);
            $banklist = pdo_fetchall("SELECT * FROM " . tablename('ewei_shop_commission_bank') . " WHERE 1 {$condition}  ORDER BY displayorder DESC", $params);
        }

        $applytype = intval($_GPC['applytype']);
        if (!array_key_exists($applytype, $type_array)) {
            show_json(0, '未选择提现方式，请您选择提现方式后重试!');
        }

        if ($applytype == 2) {
            //支付宝
            $realname = trim($_GPC['realname']);
            $alipay = trim($_GPC['alipay']);
            $alipay1 = trim($_GPC['alipay1']);
            if (empty($realname)) {
                show_json(0, '请填写姓名!');
            }
            if (empty($alipay)) {
                show_json(0, '请填写支付宝帐号!');
            }
            if (empty($alipay1)) {
                show_json(0, '请填写确认帐号!');
            }
            if ($alipay != $alipay1) {
                show_json(0, '支付宝帐号与确认帐号不一致!');
            }
            $apply['realname'] = $realname;
            $apply['alipay'] = $alipay;
        } else if ($applytype == 3) {
            //银行卡号
            $realname = trim($_GPC['realname']);
            $bankname = trim($_GPC['bankname']);
            $bankcard = trim($_GPC['bankcard']);
            $bankcard1 = trim($_GPC['bankcard1']);
            if (empty($realname)) {
                show_json(0, '请填写姓名!');
            }
            if (empty($bankname)) {
                show_json(0, '请选择银行!');
            }
            if (empty($bankcard)) {
                show_json(0, '请填写银行卡号!');
            }
            if (empty($bankcard1)) {
                show_json(0, '请填写确认卡号!');
            }
            if ($bankcard != $bankcard1) {
                show_json(0, '银行卡号与确认卡号不一致!');
            }
            $apply['realname'] = $realname;
            $apply['bankname'] = $bankname;
            $apply['bankcard'] = $bankcard;
        }

        $realmoney = $money;
        if (!empty($set_array['charge'])) {
            $money_array = m('member')->getCalculateMoney($money, $set_array);

            if($money_array['flag']) {
                $realmoney = $money_array['realmoney'];
                $deductionmoney = $money_array['deductionmoney'];
            }
        }

        m('member')->setCredit($_W['openid'], 'credit2', -$money, array(0,$_W['shopset']['set'][''].'余额提现预扣除: ' . $money . ',实际到账金额:' . $realmoney . ',手续费金额:' . $deductionmoney ));
        $logno = m('common')->createNO('member_log', 'logno', 'RW');

        $apply['uniacid'] = $_W['uniacid'];
        $apply['logno'] = $logno;
        $apply['openid'] = $_W['openid'];
        $apply['title'] = '余额提现';
        $apply['type'] = 1;
        $apply['createtime'] = time();
        $apply['status'] = 0;
        $apply['money'] = $money;
        $apply['realmoney'] = $realmoney;
        $apply['deductionmoney'] = $deductionmoney;
        $apply['charge'] = $set_array['charge'];
        $apply['applytype'] = $applytype;

        pdo_insert('ewei_shop_member_log', $apply);
        $logid = pdo_insertid();

        //模板消息
        m('notice')->sendMemberLogMessage($logid);

        show_json(1);
    }

    function getLastApply($openid, $applytype = -1)
    {
        global $_W;

        $params = array(':uniacid' => $_W['uniacid'],':openid'=>$openid);
        $sql = "select applytype,alipay,bankname,bankcard,realname from " . tablename('ewei_shop_member_log') . " where openid=:openid and uniacid=:uniacid";

        if ($applytype > -1) {
            $sql .= " and applytype=:applytype";
            $params[':applytype'] = $applytype;
        }
        $sql .= " order by id desc Limit 1";
        $data = pdo_fetch($sql, $params);

        return $data;
    }

}
