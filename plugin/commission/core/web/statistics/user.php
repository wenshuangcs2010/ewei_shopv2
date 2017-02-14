<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class User_EweiShopV2Page extends PluginWebPage {


    function main() {

        global $_W, $_GPC;

        $condition = '';
        $params = array();

        //下级代理
        $level = intval($_GPC['level']);
        $searchstart = intval($_GPC['searchstart']);

        $sql = "select dm.id,dm.diycommissionfields,diycommissionfields,diymemberfields,diymemberdata,dm.nickname,dm.realname,dm.avatar,l.levelname from " . tablename('ewei_shop_member') . " dm "
            . " left join " . tablename('ewei_shop_commission_level') . " l on l.id = dm.agentlevel"
            . " left join " . tablename('mc_mapping_fans') . "f on f.openid=dm.openid and f.uniacid={$_W['uniacid']}"
            . " where dm.uniacid = " . $_W['uniacid'] . " and dm.isagent =1  {$condition} ORDER BY dm.agenttime desc";


        if (!empty($searchstart)) {
            $userlist = pdo_fetchall($sql, $params);
        }

        $pindex = max(1, intval($_GPC['page']));
        $psize = 30;

        $list = array();

        if (!empty($userlist)) {

            $has_createtime = 0;
            $has_agenttime = 0;

            if (!empty($_GPC['createtime']['start']) && !empty($_GPC['createtime']['end'])) {
                $has_createtime = 1;
                $cstarttime = strtotime($_GPC['createtime']['start']);
                $cendtime = strtotime($_GPC['createtime']['end']);
                $sql_createtime = " AND dm.createtime >= :cstarttime AND dm.createtime <= :cendtime ";
            }

            if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
                $has_agenttime = 1;
                $astarttime = strtotime($_GPC['time']['start']);
                $aendtime = strtotime($_GPC['time']['end']);
                $sql_agenttime = " AND dm.agenttime >= :astarttime AND dm.agenttime <= :aendtime ";
            }

            foreach ($userlist as $k => $v) {
                $agentid = $v['id'];
                $member = $this->model->getInfo($agentid);

                if (empty($member['agentcount'])) {
                    continue;
                }

//              $total = $member['agentcount'];
                $level1 = $member['level1'];
                $level2 = $member['level2'];
                $level3 = $member['level3'];
                $level11 = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_member') . ' where isagent=0 and agentid=:agentid and uniacid=:uniacid limit 1', array(':agentid' => $agentid, ':uniacid' => $_W['uniacid']));

                $condition = '';
                $params = array();
                if (empty($level)) {
                    $condition = " and ( dm.agentid={$member['id']}";
                    if ($level1 > 0) {
                        $condition.= " or  dm.agentid in( " . implode(',', array_keys($member['level1_agentids'])) . ")";
                    }
                    if ($level2 > 0) {
                        $condition.= " or  dm.agentid in( " . implode(',', array_keys($member['level2_agentids'])) . ")";
                    }
                    $condition.=' )';
                    $hasagent = true;
                } else if ($level == 1) {
                    if ($level1 > 0) {
                        $condition = " and dm.agentid={$member['id']}";
                        $hasagent = true;
                    } else {
                        continue;
                    }

                } else if ($level == 2) {
                    if ($level2 > 0) {
                        $condition = " and dm.agentid in( " . implode(',', array_keys($member['level1_agentids'])) . ")";
                        $hasagent = true;
                    }else {
                        continue;
                    }
                } else if ($level == 3) {
                    if ($level3 > 0) {
                        $condition = " and dm.agentid in( " . implode(',', array_keys($member['level2_agentids'])) . ")";
                        $hasagent = true;
                    }else {
                        continue;
                    }
                }

                if (!empty($_GPC['mid'])) {
                    $condition.=' and dm.id=:mid';
                    $params[':mid'] = intval($_GPC['mid']);
                }
                /*$searchfield = strtolower(trim($_GPC['searchfield']));
                $keyword = trim($_GPC['keyword']);
                if (!empty($searchfield) && !empty($keyword)) {

                    if ($searchfield == 'member') {

                        $condition.=' and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)';
                        $params[':keyword'] = "%{$keyword}%";
                    } else if ($searchfield == 'parent') {

                        if ($keyword == '总店') {
                            $condition.=' and dm.agentid=0';
                        } else {

                            $condition.=' and ( p.mobile like :keyword or p.nickname like :keyword or p.realname like :keyword)';
                            $params[':keyword'] = "%{$keyword}%";
                        }
                    }
                }*/
                if ($_GPC['isagent'] != '') {
                    $condition.=' and dm.isagent=' . intval($_GPC['isagent']);
                }
                if ($_GPC['status'] != '') {
                    $condition.=' and dm.status=' . intval($_GPC['status']);
                }

                if (!empty($has_createtime)) {
                    $condition.= $sql_createtime;
                    $params[':cstarttime'] = $cstarttime;
                    $params[':cendtime'] = $cendtime;
                }

                if (!empty($has_agenttime)) {
                    $condition.= $sql_agenttime;
                    $params[':astarttime'] = $astarttime;
                    $params[':aendtime'] = $aendtime;
                }

                if ($_GPC['followed'] != '') {
                    if ($_GPC['followed'] == 2) {
                        $condition.=' and f.follow=0 and dm.uid<>0';
                    } else {
                        $condition.=' and f.follow=' . intval($_GPC['followed']);
                    }
                }
                if ($_GPC['isagentblack'] != '') {
                    $condition.=' and dm.agentblack=' . intval($_GPC['isagentblack']);
                }


                if ($hasagent) {
                    $child_sql = "select dm.* from " . tablename('ewei_shop_member') . " dm "
                        . " left join " . tablename('mc_mapping_fans') . "f on f.openid=dm.openid  and f.uniacid={$_W['uniacid']}"
                        . " where dm.uniacid = " . $_W['uniacid'] . " {$condition}   ORDER BY dm.agenttime desc";

                    $child_list = pdo_fetchall($child_sql, $params);

                    if (!empty($child_list)) {
                        foreach ($child_list as &$row) {

                            $row['pagent'] = $v;
//                        $row['level'] = $this->model->getAgentLevel($member, $row['id']);

                            $info = $this->model->getInfo($row['openid'], array('total', 'pay'));

                            $row['levelcount'] = $info['agentcount'];
                            if ($this->set['level'] >= 1) {
                                $row['level1'] = $info['level1'];
                            }
                            if ($this->set['level'] >= 2) {
                                $row['level2'] = $info['level2'];
                            }
                            if ($this->set['level'] >= 3) {
                                $row['level3'] = $info['level3'];
                            }
                            $row['credit1'] = m('member')->getCredit($row['openid'], 'credit1');
                            $row['credit2'] = m('member')->getCredit($row['openid'], 'credit2');
                            $row['commission_total'] = $info['commission_total'];
                            $row['commission_pay'] = $info['commission_pay'];
                            $row['followed'] = m('user')->followed($row['openid']);

                            if ($row['agentid'] == $member['id']) {
                                $row['level'] = 1;
                            } else if (in_array($row['agentid'], array_keys($member['level1_agentids']))) {
                                $row['level'] = 2;
                            } else if (in_array($row['agentid'], array_keys($member['level2_agentids']))) {
                                $row['level'] = 3;
                            }
                        }
                        $list = array_merge($list, $child_list);
                    }
                }
                unset($row);

            }
        }



        $total = count($list);
        if ($_GPC['export'] == 1) {
            foreach ($list as &$row) {
                $row['createtime'] = date('Y-m-d H:i', $row['createtime']);
                $row['agentime'] = empty($row['agenttime']) ? '' : date('Y-m-d H:i', $row['agentime']);
                $row['groupname'] = empty($row['groupname']) ? '无分组' : $row['groupname'];
                $row['levelname'] = empty($row['levelname']) ? '普通等级' : $row['levelname'];
                $row['parentname'] = empty($row['pagent']['nickname']) ? '总店' : "[" . $row['agentid'] . "]" . $row['pagent']['nickname'];
                $row['statusstr'] = empty($row['status']) ? '' : "通过";
                $row['followstr'] = empty($row['followed']) ? '' : "已关注";

                if (p('diyform')  && !empty($row['diymemberfields']) && !empty($row['diymemberdata'])) {
                    $diyformdata_array = p('diyform')->getDatas(iunserializer($row['diymemberfields']), iunserializer($row['diymemberdata']));
                    $diyformdata = "";
                    foreach ($diyformdata_array as $da) {
                        $diyformdata.=$da['name'] . ": " . $da['value'] . "\r\n";
                    }
                    $row['member_diyformdata'] = $diyformdata;
                }

                if (p('diyform')  && !empty($row['pagent']['diycommissionfields']) && !empty($row['pagent']['diycommissiondata'])) {
                    $diyformdata_array = p('diyform')->getDatas(iunserializer($row['pagent']['diycommissionfields']), iunserializer($row['pagent']['diycommissiondata']));
                    $diyformdata = "";
                    foreach ($diyformdata_array as $da) {
                        $diyformdata.=$da['name'] . ": " . $da['value'] . "\r\n";
                    }
                    $row['pagent_diyformdata'] = $diyformdata;
                }

                if (p('diyform')  && !empty($row['pagent']['diymemberfields']) && !empty($row['pagent']['diymemberdata'])) {
                    $diyformdata_array = p('diyform')->getDatas(iunserializer($row['pagent']['diymemberfields']), iunserializer($row['pagent']['diymemberdata']));
                    $diyformdata = "";
                    foreach ($diyformdata_array as $da) {
                        $diyformdata.=$da['name'] . ": " . $da['value'] . "\r\n";
                    }
                    $row['pmember_diyformdata'] = $diyformdata;
                }
            }
            unset($row);

            $columns = array(
                array('title' => 'ID', 'field' => 'id', 'width' => 12),
                array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
                array('title' => '姓名', 'field' => 'realname', 'width' => 12),
                array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
                array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
                array('title' => 'openid', 'field' => 'openid', 'width' => 24),
                array('title' => '上级', 'field' => 'parentname', 'width' => 12),
                array('title' => '分销商等级', 'field' => 'levelname', 'width' => 12),
                array('title' => '点击数', 'field' => 'clickcount', 'width' => 12),
                array('title' => '下线分销商总数', 'field' => 'levelcount', 'width' => 12),
                array('title' => '一级下线分销商数', 'field' => 'level1', 'width' => 12),
                array('title' => '二级下线分销商数', 'field' => 'level2', 'width' => 12),
                array('title' => '三级下线分销商数', 'field' => 'level3', 'width' => 12),
                array('title' => '累计佣金', 'field' => 'commission_total', 'width' => 12),
                array('title' => '打款佣金', 'field' => 'commission_pay', 'width' => 12),
                array('title' => '注册时间', 'field' => 'createtime', 'width' => 12),
                array('title' => '成为分销商时间', 'field' => 'createtime', 'width' => 12),
                array('title' => '审核状态', 'field' => 'createtime', 'width' => 12),
                array('title' => '是否关注', 'field' => 'followstr', 'width' => 12)
            );

            if (p('diyform')) {
                $columns[] = array('title' => '粉丝会员自定义信息', 'field' => 'member_diyformdata', 'width' => 36);
                $columns[] = array('title' => '粉丝分销商申请自定义信息', 'field' => 'agent_diyformdata', 'width' => 36);

                $columns[] = array('title' => '推荐人会员自定义信息', 'field' => 'pmember_diyformdata', 'width' => 36);
                $columns[] = array('title' => '推荐人分销商申请自定义信息', 'field' => 'pagent_diyformdata', 'width' => 36);

            }

            m('excel')->export($list, array(
                "title" => "推广下线-" . date('Y-m-d-H-i', time()),
                "columns" => $columns
            ));
        } else {

            if ($total > 0) {
                $pager = pagination($total, $pindex, $psize);

                $start = ($pindex-1) * $psize;
                if (!empty($list)) {
                    $list = array_slice($list, $start, $psize);
                }
            }
        }




        load()->func('tpl');
        include $this->template();
    }

    function query() {
        global $_W, $_GPC;
        $kwd = trim($_GPC['keyword']);
        $wechatid = intval($_GPC['wechatid']);
        if (empty($wechatid)) {
            $wechatid = $_W['uniacid'];
        }
        $params = array();
        $params[':uniacid'] = $wechatid;
        $condition = " and uniacid=:uniacid and isagent=1 and status=1";
        if (!empty($kwd)) {
            $condition.=" AND ( `nickname` LIKE :keyword or `realname` LIKE :keyword or `mobile` LIKE :keyword )";
            $params[':keyword'] = "%{$kwd}%";
        }
        if (!empty($_GPC['selfid'])) {
            $condition.=" and id<>" . intval($_GPC['selfid']);
        }
        $ds = pdo_fetchall('SELECT id,avatar,nickname,openid,realname,mobile FROM ' . tablename('ewei_shop_member') . " WHERE 1 {$condition} order by createtime desc", $params);

        include $this->template('commission/query');
    }

    function check() {
        global $_W, $_GPC;

        $id = intval($_GPC['id']);


        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $status = intval($_GPC['status']);
        $members = pdo_fetchall("SELECT id,openid,nickname,realname,mobile,status FROM " . tablename('ewei_shop_member') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        $time = time();
        foreach ($members as $member) {
            if ($member['status'] === $status) {
                continue;
            }
            if ($status == 1) {
                pdo_update('ewei_shop_member', array('status' => 1, 'agenttime' => $time), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));

                plog('commission.agent.check', "审核分销商 <br/>分销商信息:  ID: {$member['id']} /  {$member['openid']}/{$member['nickname']}/{$member['realname']}/{$member['mobile']}");

                //成为分销商消息通知 
                $this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $time), TM_COMMISSION_BECOME);

                //检测升级	
                if (!empty($member['agentid'])) {
                    $this->model->upgradeLevelByAgent($member['agentid']);

                    //股东升级
                    if(p('globonus')){
                        p('globonus')->upgradeLevelByAgent($member['agentid']);
                    }
                    //创始人升级
                    if(p('author')){
                        p('author')->upgradeLevelByAgent($member['agentid']);
                    }

                }
            } else {
                pdo_update('ewei_shop_member', array('status' => 0, 'agenttime' => 0), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
                plog('commission.agent.check', "取消审核 <br/>分销商信息:  ID: {$member['id']} /  {$member['openid']}/{$member['nickname']}/{$member['realname']}/{$member['mobile']}");
            }
        }
        show_json(1, array('url' => referer()));
    }

    function agentblack() {

        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }
        $agentblack = intval($_GPC['agentblack']);
        $members = pdo_fetchall("SELECT id,openid,nickname,realname,mobile,agentblack FROM " . tablename('ewei_shop_member') . " WHERE id in( $id ) AND uniacid=" . $_W['uniacid']);
        foreach ($members as $member) {
            if ($member['agentblack'] === $agentblack) {
                continue;
            }
            if ($agentblack == 1) {
                pdo_update('ewei_shop_member', array('isagent' => 1, 'status' => 0, 'agentblack' => 1), array('id' => $member['id']));
                plog('commission.agent.agentblack', "设置黑名单 <br/>分销商信息:  ID: {$member['id']} /  {$member['openid']}/{$member['nickname']}/{$member['realname']}/{$member['mobile']}");
            } else {
                pdo_update('ewei_shop_member', array('isagent' => 1, 'status' => 1, 'agentblack' => 0), array('id' => $member['id']));
                plog('commission.agent.agentblack', "取消黑名单 <br/>分销商信息:  ID: {$member['id']} /  {$member['openid']}/{$member['nickname']}/{$member['realname']}/{$member['mobile']}");
            }
        }
        show_json(1, array('url' => referer()));
    }

}
