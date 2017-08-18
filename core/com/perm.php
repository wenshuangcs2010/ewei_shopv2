<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Perm_EweiShopV2ComModel extends ComModel
{
    public static $allPerms = array();
    public static $getLogTypes = array();
    public static $formatPerms = array();

    public function allPerms()
    {
        if (empty(self::$allPerms)) {
            //系统
            $perms = array(
                'shop' => $this->perm_shop(),

                'goods' => $this->perm_goods(),

                'member' => $this->perm_member(),

                'order' => $this->perm_order(),

                'finance' => $this->perm_finance(),

                'statistics' => $this->perm_statistics(),

                'sysset' => $this->perm_sysset(),

                'sale' => $this->perm_sale(),

                //插件开始
                'bargain'=>$this->perm_bargain(),

                'commission' => $this->perm_commission(),

                'diyform' => $this->perm_diyform(),

                'poster' => $this->perm_poster(),

                'postera' => $this->perm_postera(),

                'taobao' => $this->perm_taobao(),

                'article' => $this->perm_article(),

                'creditshop' => $this->perm_creditshop(),

                'exhelper' => $this->perm_exhelper(),

                'diypage' => $this->perm_diypage(),

                'groups'=> $this->perm_groups(),

                'perm' => $this->perm_perm(),

                'globonus' => $this->perm_globonus(),

                'merch' => $this->perm_merch(),

                'mr' => $this->perm_mr(),

                'qa' => $this->perm_qa(),

                'abonus' => $this->perm_abonus(),

                'pstore' => $this->perm_pstore(),

                'sign' => $this->perm_sign(),

                'author' => $this->perm_author(),

                'sns'=>$this->perm_sns(),

                'backone' => $this->perm_backone(),

                'task'=>$this->perm_task(),

                'cashier' => $this->perm_cashier(),

                'seckill' => $this->perm_seckill(),

            );
            self::$allPerms = $perms;
        }
        return self::$allPerms;
    }

    protected function perm_shop()
    {
        return array(
            'text' => '商城管理',
            'adv' =>
                array(
                    'text' => '幻灯片',
                    'main' => '查看列表',
                    'view' => '查看内容',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' => array(
                        'displayorder' => 'edit',
                        'enabled' => 'edit'
                    )
                ),
            'nav' =>
                array(
                    'text' => '首页导航图标',
                    'main' => '查看列表',
                    'view' => '查看内容',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' => array(
                        'displayorder' => 'edit',
                        'status' => 'edit'
                    )
                ),
            'banner' =>
                array(
                    'text' => '首页广告',
                    'main' => '查看列表',
                    'view' => '查看内容',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' => array(
                        'displayorder' => 'edit',
                        'enabled' => 'edit',
                        'setswipe' => 'edit'
                    )
                ),
            'cube' =>
                array(
                    'text' => '首页魔方',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'recommand' =>
                array(
                    'text' => '首页商品推荐',
                    'main' => '编辑推荐商品-log',
                    'setstyle' => '设置商品组样式-log',
                ),
            'sort' =>
                array(
                    'text' => '首页元素排版',
                    'main' => '修改-log'
                ),
            'dispatch' =>
                array(
                    'text' => '配送方式',
                    'main' => '查看列表',
                    'view' => '查看内容',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' => array(
                        'displayorder' => 'edit',
                        'enabled' => 'edit',
                        'setdefault' => 'edit'
                    )
                ),
            'notice' =>
                array(
                    'text' => '公告',
                    'main' => '查看列表',
                    'view' => '查看内容',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' => array(
                        'displayorder' => 'edit',
                        'status' => 'edit'
                    )
                ),
            'comment' =>
                array(
                    'text' => '评价',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '编辑-log',
                    'post' => '回复-log',
                    'delete' => '删除-log'
                ),
            'refundaddress' =>
                array(
                    'text' => '退货地址',
                    'main' => '查看列表',
                    'view' => '查看内容',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' => array(
                        'setdefault' => 'edit'
                    )
                ),
            'verify' =>
                $this->isopen('verify', true) && $this->is_perm_plugin('verify', true) ?
                    array(
                        'text' => 'O2O核销',
                        'saler' =>
                            array(
                                'text' => '店员管理',
                                'main' => '查看列表',
                                'view' => '查看内容',
                                'add' => '添加-log',
                                'edit' => '修改-log',
                                'delete' => '删除-log',
                                'xxx' => array(
                                    'status' => 'edit'
                                )
                            ),
                        'store' =>
                            array(
                                'text' => '门店管理',
                                'main' => '查看列表',
                                'view' => '查看内容',
                                'add' => '添加-log',
                                'edit' => '修改-log',
                                'delete' => '删除-log',
                                'xxx' => array(
                                    'displayorder' => 'edit',
                                    'status' => 'edit',
                                )
                            ),
                        'set' =>
                            array(
                                'text' => '关键词设置',
                                'main' => '查看',
                                'edit' => '编辑-log'
                            )
                    ) : array(),
        );
    }

    protected function perm_goods()
    {
        return array(
            'text' => '商品管理',
            'main' => '浏览列表',
            'view' => '查看详情',
            'add' => '添加-log',
            'edit' => '修改-log',
            'delete' => '删除-log',
            'delete1' => '彻底删除-log',
            'restore' => '恢复到仓库-log',
            'xxx' => array(
                'status' => 'edit',
                'property' => 'edit',
                'goodsprice' => 'edit',
                'change' => 'edit'
            ),
            'category' => array(
                'text' => '商品分类',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete' => '删除-log',
                'xxx' => array(
                    'enabled' => 'edit'
                )
            ),
            'group' => array(
                'text' => '商品组',
                'view' => '浏览',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete' => '删除-log',
                'xxx' => array(
                    'enabled' => 'edit'
                )
            ),
            'virtual' => $this->isopen('virtual', true) && $this->is_perm_plugin('virtual', true) ?
                array(
                    'text' => '虚拟卡密',
                    'temp' => array(
                        'text' => '卡密模板管理',
                        'view' => '浏览',
                        'add' => '添加-log',
                        'edit' => '修改-log',
                        'delete' => '删除-log',
                    ),
                    'category' => array(
                        'text' => '卡密分类管理',
                        'add' => '添加-log',
                        'edit' => '编辑-log',
                        'delete' => '删除-log',
                    ),
                    'data' => array(
                        'text' => '卡密数据',
                        'add' => '添加-log',
                        'edit' => '修改-log',
                        'delete' => '删除-log',
                        'export' => '导出-log',
                        'temp' => '下载模板',
                        'import' => '导入-log',
                    ),
                ) : array(),
        );
    }

    protected function perm_member()
    {
        return array(
            'text' => '会员管理',
            'group' => array(
                'text' => '会员组',
                'main' => '查看列表',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete' => '删除-log',
            ),
            'level' => array(
                'text' => '会员等级',
                'main' => '查看列表',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete' => '删除-log',
                'xxx' => array(
                    'enable' => 'edit',
                )
            ),
            'list' => array(
                'text' => '会员管理',
                'view' => '浏览',
                'edit' => '修改-log',
                'detail' => '查看修改资料-log',
                'delete' => '删除-log',
                'xxx' => array(
                    'setblack' => 'edit',
                )
            ),
            'rank' => array(
                'text' => '排行榜',
                'main' => '查看',
                'edit' => '修改-log',
            ),
            'tmessage' => array(
                'text' => '会员群发',
                'send' => '群发消息-log',
                'xxx' => array(
                    'sendmessage' => 'send',
                    'fetch' => 'send'
                )
            ),
        );
    }

    protected function perm_bargain()
    {
        return $this->isopen('bargain') && $this->is_perm_plugin('bargain') ? array(
            'text' => m('plugin')->getName('bargain'),

            'main' => '查看砍价中列表',
            'soldout' => '查看已售罄列表',
            'notstart' => '查看未开始列表',
            'complete' => '查看已结束列表',
            'out' => '查看已下架列表',
            'recycle' => '查看回收站列表',
            'warehouse' => '添加砍价商品',
            'react' => '编辑商品',
            'huishou' => '删除商品',
            'delete' => '彻底删除商品',
            'recover' => '恢复已删除商品',
            'set' => '分享设置',
            'messageset' => '消息通知设置',
            'otherset' => '其他设置',
        ):array();

    }

    protected function perm_sale()
    {
        $array = array(
            'text' => '营销',
            'coupon' =>
                $this->isopen('coupon', true) && $this->is_perm_plugin('coupon', true) ?
                    array(
                        'text' => '优惠券管理',
                        'view' => '浏览',
                        'add' => '添加-log',
                        'edit' => '修改-log',
                        'delete' => '删除-log',
                        'send' => '发放-log',
                        'set' => '修改设置-log',
                        'xxx' => array(
                            'displayorder' => 'edit'
                        ),
                        'category' => array(
                            'text' => '优惠券分类',
                            'main' => '查看',
                            'edit' => '修改-log'
                        ),
                        'log' => array(
                            'text' => '优惠券记录',
                            'main' => '查看',
                            'export' => '导出记录'
                        ),
                    ) : array(),
            'virtual' => array(
                'text' => '关注回复',
                'view' => '浏览',
                'edit' => '修改-log',
            ),
            'package' => array(
                'text' => '套餐管理',
                'view' => '浏览',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete1' => '彻底删除-log',
                'xxx' => array(
                    'status' => 'edit',
                    'change' => 'edit'
                ),
            )
        );
        if ($this->isopen('sale', true) && $this->is_perm_plugin('sale', true)) {
            $sale = array(
                'deduct' => '修改抵扣设置-log',
                'enough' => '修改满额立减-log',
                'enoughfree' => '修改满额包邮-log',
                'recharge' => '修改充值优惠设置-log',
            );
            $array = array_merge($array, $sale);
        }
        return $array;
    }

    protected function perm_finance()
    {
        return array(
            'text' => '财务管理',
            'log' =>
                array(
                    'text' => '财务管理',
                    'recharge' => '充值记录',
                    'withdraw' => '提现申请',
                    'refund' => '充值退款-log',
                    'wechat' => '微信提现-log',
                    'manual' => '手动提现-log',
                    'refuse' => '拒绝提现-log',
                    'recharge.export' => '充值记录导出-log',
                    'withdraw.export' => '提现申请导出-log',
                ),
            'downloadbill' =>
                array(
                    'text' => '对账单',
                    'main' => '下载-log'
                ),
            // 'recharge' => array(
            //     'text' => '充值',
            //     'credit1' => '充值积分-log',
            //     'credit2' => '充值余额-log',
            // ),
            'recharge' => array(
                'text' => '充值',
                'credit1' => '充值积分-log',
                'credit2' => '充值余额-log',
                'credit2recharge' => '充值余额审核',

            ),
            'credit' => array(
                'text' => '积分余额明细',
                'credit1' => '积分明细',
                'credit1.export' => '导出积分明细',
                'credit2' => '余额明细',
                'credit2.export' => '导出余额明细',
            )
        );
    }

    protected function perm_statistics()
    {
        return array(
            'text' => '数据统计',
            'sale' =>
                array(
                    'text' => '销售统计',
                    'main' => '查看',
                    'export' => '导出-log'
                ),
            'sale_analysis' =>
                array(
                    'text' => '销售指标',
                    'main' => '查看'
                ),
            'order' =>
                array(
                    'text' => '订单统计',
                    'main' => '查看',
                    'export' => '导出-log'
                ),
            'goods' =>
                array(
                    'text' => '商品销售明细',
                    'main' => '查看',
                    'export' => '导出-log'
                ),
            'goods_rank' =>
                array(
                    'text' => '商品销售排行',
                    'main' => '查看',
                    'export' => '导出-log'
                ),
            'goods_trans' =>
                array(
                    'text' => '商品销售转化率',
                    'main' => '查看',
                    'export' => '导出-log'
                ),
            'member_cost' =>
                array(
                    'text' => '会员消费排行',
                    'main' => '查看',
                    'export' => '导出-log'
                ),
            'member_increase' =>
                array(
                    'text' => '会员增长趋势',
                    'main' => '查看'
                )
        );
    }

    protected function perm_order()
    {
        return array(
            'text' => '订单',
            'detail' => array(
                'text' => '订单详情',
                'edit' => '编辑',
            ),
            'export' => array(
                'text' => '自定义导出-log',
                'main' => '浏览页面',
                'xxx' => array(
                    'save' => 'main',
                    'delete' => 'main',
                    'gettemplate' => 'main',
                    'reset' => 'main'
                )
            ),

            'batchsend' => array(
                'text' => '批量发货',
                'main' => '批量发货-log',
                'xxx' => array(
                    'import' => 'main'
                )
            ),
            'list' => array(
                'text' => '订单管理',
                'main' => '浏览全部订单',
                'status_1' => '浏览关闭订单',
                'status0' => '浏览待付款订单',
                'status1' => '浏览已付款订单',
                'status2' => '浏览已发货订单',
                'status3' => '浏览完成的订单',
                'status4' => '浏览退货申请订单',
                'status5' => '浏览已退货订单'
            ),
            'op' => array(
                'text' => '操作',
                'delete' => '订单删除-log',
                'pay' => '确认付款-log',
                'send' => '发货-log',
                'sendcancel' => '取消发货-log',
                'finish' => '确认收货(快递单)-log',
                'verify' => '确认核销(核销单)-log',
                'fetch' => '确认取货(自提单)-log',
                'close' => '关闭订单-log',
                'changeprice' => '订单改价-log',
                'changeaddress' => '修改收货地址-log',
                'remarksaler' => '订单备注-log',
                'paycancel' => '订单取消付款-log',
                'fetchcancel' => '订单取消取货-log',
                'xxx'=>array(
                    'changeexpress'=>'send'
                ),
                'refund' => array(
                    'text' => '维权',
                    'main' => '维权信息',
                    'submit' => '提交维权申请',
                ),
            ),
        );
    }

    protected function perm_sysset()
    {
        return array(
            'text' => '设置',
            'shop' =>
                array(
                    'text' => '商城设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'follow' =>
                array(
                    "text" => "分享及关注",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'notice' =>
                array(
                    "text" => "消息提醒",
                    'edit' => '编辑-log'
                ),
            'trade' =>
                array(
                    "text" => "交易设置",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'payset' =>
                array(
                    "text" => "支付方式",
                    'edit' => '修改-log'
                ),
            'templat' =>
                array(
                    "text" => "模板设置",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'member' =>
                array(
                    "text" => "会员等级设置",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'category' =>
                array(
                    "text" => "分类层级",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'contact' =>
                array(
                    "text" => "联系方式",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'qiniu' =>
                $this->isopen('qiniu', true) && $this->is_perm_plugin('qiniu', true) ?
                    array(
                        "text" => "七牛存储",
                        'main' => '查看',
                        'edit' => '修改-log'
                    ) : array(),
            'close' =>
                array(
                    "text" => "商城关闭",
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'tmessage' =>
                array(
                    'text' => '模板消息库',
                    'main' => '查看',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),
            'sms' =>
                $this->isopen('sms', true) && $this->is_perm_plugin('sms', true) ?
                array(
                    'text' => '短信提醒',
                    'set' => array(
                        'text' => '短信设置',
                        'main' => '设置-log'
                    ),
                    'temp'=> array(
                        'text' => '短信模板库',
                        'main' => '查看列表',
                        'view' =>'查看',
                        'add' => '添加-log',
                        'edit' => '修改-log',
                        'delete' => '删除-log',
                        'testsend'=>'发送测试短信',
                        'xxx'=>array(
                            'status'=>'edit'
                        )
                    )
                ) : array(),
            'wap' =>
                array(
                    'text' => '全网通设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'cover' =>
                array(
                    'shop' =>
                        array(
                            'text' => '商城入口',
                            'main' => '查看',
                            'edit' => '修改-log'
                        ),
                    'member' =>
                        array(
                            'text' => '会员中心入口',
                            'main' => '查看',
                            'edit' => '修改-log'
                        ),
                    'favorite' =>
                        array(
                            'text' => '收藏入口',
                            'main' => '查看',
                            'edit' => '修改-log'
                        ),
                    'cart' =>
                        array(
                            'text' => '购物车入口',
                            'main' => '查看',
                            'edit' => '修改-log'
                        ),
                    'order' =>
                        array(
                            'text' => '订单入口',
                            'main' => '查看',
                            'edit' => '修改-log'
                        ),
                    'coupon' =>
                        array(
                            'text' => '优惠券入口',
                            'main' => '查看',
                            'edit' => '修改-log'
                        )
                )
        );
    }

    protected function perm_commission()
    {
        return $this->isopen('commission') && $this->is_perm_plugin('commission') ? array(
            'text' => m('plugin')->getName('commission'),
            'agent' =>
                array(
                    'text' => '分销商管理',
                    'main' => '查看列表',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'user' => '查看下线',
                    'export' => '导出-log',
                    'changeagent'=>'修改上级分销商-log',
                    'xxx' => array(
                        'check' => 'edit',
                        'agentblack' => 'edit',
                    ),

                ),
            'level' =>
                array(
                    'text' => '分销商等级',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),
            'buylevel' =>
                array(
                    'text' => '购买分销商等级',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),
            'apply' =>
                array(
                    'text' => '佣金审核',
                    'view1' => '待审核浏览',
                    'view2' => '待打款浏览',
                    'view3' => '已打款浏览',
                    'view_1' => '无效佣金浏览',
                    'detail' => '详细佣金',
                    'check' => '审核-log',
                    'pay' => '打款-log',
                    'cancel' => '重新审核-log',
                    'refuse' => '驳回申请-log',
                    'changecommission' => '修改佣金-log',
                    'export' => '导出-log'
                ),
            'increase' =>
             array(
                    'text' => '分销商趋势图',
                    'main' => '查看'
              ),

             'rank' =>
                array(
                    'text' => '佣金排行榜',
                    'main' => '查看',
                    'edit' => '修改-log',
             ),

             'notice' => array(
                'text' => '通知设置',
                'main' => '查看',
                'edit' => '修改-log',
            ),

            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log',
            ),

            'set' => array(
                'text' => '基本设置',
                'main' => '查看',
                'edit' => '修改-log'
            )
        ) : array();

    }

    protected function perm_diyform()
    {
        return $this->isopen('diyform') && $this->is_perm_plugin('diyform') ? array(
            'text' => m('plugin')->getName('diyform'),
            'temp' =>
                array(
                    'text' => '模板',
                    'main' => '查看列表',
                    'view' => '查看',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log'
                ),
            'data' =>
                array(
                    'text' => '数据',
                    'main' => '查看'
                ),
            'category' =>
                array(
                    'text' => '分类',
                    'main' => '查看',
                    'edit' => '修改-log',
                    'xxx' => array(
                        'delete' => 'edit',
                        'add' => 'edit',
                    )
                ),
            'set' =>
                array(
                    'text' => '设置',
                    'main' => '查看',
                    'edit' => '修改-log',
                ),
        ) : array();
    }

    protected function perm_poster()
    {
        return $this->isopen('poster') && $this->is_perm_plugin('poster') ? array(
            'text' => m('plugin')->getName('poster'),
            'main' => '查看列表',
            'view' => '查看',
            'add' => '添加-log',
            'edit' => '修改-log',
            'delete' => '删除-log',
            'clear' => '清除缓存-log',
            'xxx' => array(
                'setdefault' => 'edit',
            ),
            'log' =>
                array(
                    'text' => '关注记录',
                    'main' => '查看'
                ),
            'scan' =>
                array(
                    'text' => '扫描记录',
                    'main' => '查看'
                )
        ) : array();
    }

    protected function perm_postera()
    {
        return $this->isopen('postera') && $this->is_perm_plugin('postera') ? array(
            'text' => m('plugin')->getName('postera'),
            'main' => '查看列表',
            'view' => '查看',
            'add' => '添加-log',
            'edit' => '修改-log',
            'delete' => '删除-log',
            'clear' => '清除缓存-log',
            'xxx' => array(
                'setdefault' => 'edit',
            ),
            'log' =>
                array(
                    'text' => '关注记录',
                    'main' => '查看'
                ),
        ) : array();
    }
	/*拼团权限*/
	protected function perm_groups()
	{
		return $this->isopen('groups')&&$this->is_perm_plugin('groups')?array(
			'text' => m('plugin')->getName('groups'),
			'goods' => array(
				'text'=>'商品管理',
				'view'=>'查看',
				'edit'=>'编辑-log',
				'add'=>'添加-log',
				'delete'=>'删除-log',
				'delete1'=>'彻底删除-log',
                'restore' => '恢复到仓库-log',
				'xxx' =>
					array(
						'property' => 'edit',
						'status' => 'edit',
					)
			),
            'category' => array(
                'text' => '分类管理',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log',
                'xxx' =>
                    array(
                        'displayorder' => 'edit',
                        'enabled' => 'edit',
                    )
            ),
            'adv' => array(
                'text' => '幻灯片管理',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log',
            ),
            'order' => array(
                'text' => '订单管理',
                'view' => '查看',
                'pay' => '确认付款',
                'send' => '确认发货',
                'sendcancel' => '取消发货',
                'delete' => '删除订单',
                'remarksaler' => '商家备注',
                'finish' => '确认收货',
                'close' => '关闭订单',
                'changeaddress' => '编辑收货信息',
                'changeexpress' => '修改订单物流',
            ),
            'verify' => array(
                'text' => '核销查询',
                'view' => '查看',
            ),
            'team' => array(
                'text' => '拼团管理',
                'view' => '查看',
                'group' => '立即成团',
            ),
            'refund' => array(
                'text' => '维权设置',
                'view' => '查看',
                'submit' => '处理申请',
                'receipt' => '确认收货',
                'send' => '确认发货',
                'express' => '修改物流',
                'close' => '关闭订单',
                'note' => '商家备注',
            ),
            'cover' => array(
                'text' => '入口设置',
                'view' => '查看',
                'edit' => '编辑-log',
            ),
            'notice' => array(
                'text' => '通知设置',
                'view' => '查看',
                'edit' => '编辑-log',
            ),
            'set' => array(
                'text' => '基础设置',
                'view' => '查看',
                'edit' => '编辑-log',
            ),
		):array();
	}

    protected function perm_taobao()
    {
        return $this->isopen('taobao') && $this->is_perm_plugin('taobao') ? array(
            'text' => m('plugin')->getName('taobao'),
            'main' => '获取宝贝',
            'jingdong' =>array(
                'text'=>'京东助手',
                'main'=>'获取宝贝'
            ),
            'one688'=>array(
                'text'=>'1688宝贝助手',
                'main'=>'获取宝贝'
            ),
            'taobaocsv'=>array(
                'text'=>'淘宝CSV助手',
                'main'=>'获取宝贝'
            )
        ) : array();
    }

    protected function perm_article()
    {
        return $this->isopen('article') && $this->is_perm_plugin('article') ? array(
            'text' => m('plugin')->getName('article'),
            'main' => '查看列表',
            'add' => '添加-log',
            'edit' => '修改-log',
            'delete' => '删除-log',
            'record' => '查看统计',
            'xxx' =>
                array(
                    'displayorder' => 'edit',
                    'state' => 'edit',
                ),
            'category' =>
                array(
                    'text' => '分类管理',
                    'main' => '查看',
                    'edit' => '修改-log',
                    'delete' => '删除-log'
                ),
            'report' =>
                array(
                    'text' => '举报记录',
                    'main' => '查看'
                ),
            'set' =>
                array(
                    'text' => '其他设置',
                    'view' => '查看',
                    'edit' => '修改-log',
                )
        ) : array();
    }

    protected function perm_creditshop()
    {
        return $this->isopen('creditshop') && $this->is_perm_plugin('creditshop') ? array(
            'text' => m('plugin')->getName('creditshop'),
            'goods' =>
                array(
                    'text' => '商品',
                    'main' => '查看列表',
                    'view' => '查看详细',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'property' => 'edit'
                        )
                ),
            'category' =>
                array(
                    'text' => '分类',
                    'main' => '查看列表',
                    'view' => '查看详细',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'enabled' => 'edit',
                            'displayorder' => 'edit'
                        )
                ),
            'adv' =>
                array(
                    'text' => '幻灯片',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'displayorder' => 'edit',
                            'enabled' => 'edit',
                        )
                ),
            'log' =>
                array(
                    'text' => '订单/记录',
                    'exchange' => '兑换记录',
                    'draw' => '抽奖记录',
                    'order' => '待发货',
                    'convey' => '待收货',
                    'finish' => '已完成',
                    'verifying' => '待核销',
                    'verifyover' => '已核销',
                    'verify' => '全部核销',
                    'detail' => '详情',
                    'doexchange' => '确认兑换-log',
                    'export' => '导出明细-log'
                ),
            'comment' => array(
                'text' => '评价管理',
                'edit' => '回复评价',
                'check' => '审核评价'
            ),
            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'notice' =>
                array(
                    'text' => '通知设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'set' =>
                array(
                    'text' => '基础设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                )
        ) : array();
    }


    protected function perm_diypage()
    {
        return $this->isopen('diypage') && $this->is_perm_plugin('diypage') ? array(
            'text' => m('plugin')->getName('diypage'),
            'page' => array(
                'sys' => array(
                    'text' => '系统页面',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '编辑-log',
                    'delete' => '删除-log',
                    'savetemp' => '另存为模板-log'
                ),
                'diy' => array(
                    'text' => '自定义页面',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '编辑-log',
                    'delete' => '删除-log',
                    'savetemp' => '另存为模板-log'
                ),
                'mod' => array(
                    'text' => '公用模块',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '编辑-log',
                    'delete' => '删除-log'
                )
            ),
            'menu' => array(
                'text' => '自定义菜单',
                'main' => '查看列表',
                'add' => '添加-log',
                'edit' => '编辑-log',
                'delete' => '删除-log'
            ),
            'shop' => array(
                'text' => '商城页面设置',
                'page' => array(
                    'text' => '页面设置',
                    'main' => '查看',
                    'save' => '保存-log'
                ),
                'menu' => array(
                    'text' => '按钮设置',
                    'main' => '查看',
                    'save' => '保存-log'
                ),
                'layer' => array(
                    'text' => '悬浮按钮',
                    'main' => '编辑-log'
                ),
                'followbar' => array(
                    'text' => '关注条',
                    'main' => '编辑-log'
                )
            ),
            'temp' => array(
                'text' => '模板管理',
                'main' => '通过模板创建页面',
                'delete' => '删除模板',
                'category' => array(
                    'text' => '模板分类',
                    'main' => '查看',
                    'add' => '添加-log',
                    'edit' => '编辑-log',
                    'delete' => '删除-log'
                )
            )
        ) : array();
    }


    protected function perm_sign()
    {
        return $this->isopen('sign') && $this->is_perm_plugin('sign') ? array(
            'text'=>m('plugin')->getName('sign'),
            'rule'=>array(
                'text'=>'签到规则',
                'main'=>'查看',
                'edit'=>'编辑-log'
            ),
            'set'=>array(
                'text'=>'签到入口',
                'main'=>'查看',
                'edit'=>'编辑-log'
            ),
            'records'=>array(
                'text'=>'签到记录',
                'main'=>'查看'
            )
        ) : array();
    }

    protected function perm_backone()
    {
        return $this->isopen('backone') && $this->is_perm_plugin('backone') ? array(
            'text'=>m('plugin')->getName('backone'),
            'goods'=>array(
                'text'=>'商品管理',
                'main'=>'查看列表',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log'
            ),
            'apply'=>array(
                'text'=>'返还申请',
                'main'=>'查看列表',
                'view'=>'查看',
                'edit'=>'审核-log',
                'xxx' => array(
                    'submit' => 'edit',
                )
            ),
            'set'=>array(
                'text'=>'基本设置',
                'main'=>'查看',
                'edit'=>'编辑-log'
            ),
            'cover'=>array(
                'text'=>'入口设置',
                'main'=>'查看',
                'edit'=>'编辑-log'
            )
        ) : array();
    }

    protected function perm_exhelper()
    {
        return $this->isopen('exhelper') && $this->is_perm_plugin('exhelper') ? array(
            'text' => m('plugin')->getName('exhelper'),
            'print' =>
                array(
                    'single' =>
                        array(
                            'text' => '单个打印',
                            'express' => '打印快递单-log',
                            'invoice' => '打印发货单-log',
                            'dosend' => '一键发货-log'
                        ),
                    'batch' => array(
                        'text' => '批量打印',
                        'express' => '打印快递单-log',
                        'invoice' => '打印发货单-log',
                        'dosend' => '一键发货-log'
                    )
                ),
            'temp' =>
                array(
                    'express' =>
                        array(
                            'text' => '快递单模板管理',
                            'add' => '添加-log',
                            'edit' => '修改-log',
                            'delete' => '删除-log',
                            'xxx' =>
                                array(
                                    'setdefault' => 'edit'
                                )
                        ),
                    'invoice' =>
                        array(
                            'text' => '发货单模板管理',
                            'add' => '添加-log',
                            'edit' => '修改-log',
                            'delete' => '删除-log',
                            'xxx' =>
                                array(
                                    'setdefault' => 'edit'
                                )
                        )
                ),
            'sender' =>
                array(
                    'text' => '发货人信息管理',
                    'main' => '查看列表',
                    'view' => '查看',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'setdefault' => 'edit'
                        )
                ),
            'short' =>
                array(
                    'text' => '商品简称',
                    'main' => '查看',
                    'edit' => '修改-log',
                ),
            'printset' =>
                array(
                    'text' => '打印端口设置',
                    'main' => '查看',
                    'edit' => '修改-log',
                )

        ) : array();
    }

    protected function perm_perm()
    {
        return array(
            'text' => '权限系统',
            'log' => array(
                'text' => '操作日志',
                'main' => '查看列表'
            ),
            'role' => array(
                'text' => '角色管理',
                'main' => '查看列表',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete' => '删除-log',
                'xxx' => array(
                    'status' => 'edit',
                    'query' => 'main'
                )
            ),
            'user' => array(
                'text' => '操作员管理',
                'main' => '查看列表',
                'add' => '添加-log',
                'edit' => '修改-log',
                'delete' => '删除-log',
                'xxx' => array(
                    'status' => 'edit'
                )
            )
        );
    }


    protected function perm_globonus()
    {
        return $this->isopen('globonus') && $this->is_perm_plugin('globonus') ? array(
            'text' => m('plugin')->getName('globonus'),
            'partner' =>
                array(
                    'text' => '股东管理',
                    'main' => '查看列表',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'export' => '导出-log',
                    'xxx' => array(
                        'check' => 'edit',
                        'partnerblack' => 'edit',
                    ),

                ),
            'level' =>
                array(
                    'text' => '股东等级',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),

            'bonus' =>
                array(
                    'text' => '分红管理',
                    'status0' => '待确认浏览',
                    'status1' => '待结算浏览',
                    'status2' => '已结算浏览',
                    'build' => '创建结算单-log',
                    'confirm' => '确认结算单-log',
                    'pay' => '结算结算单-log',
                    'export' => '导出结算单-log',
                    'delete' => '删除结算单-log',
                    'detail' => '查看结算单详情',
                    'detail.export' => '导出结算单股东详情-log',
                    'xxx' => array(
                        'payp' => 'pay',
                        'paymoney' => 'confirm',
                    )
                ),

            'notice' => array(
                'text' => '通知设置',
                'main' => '查看',
                'edit' => '修改-log',
            ),
            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log',
                ),
            'set' => array(
                'text' => '基本设置',
                'main' => '查看',
                'edit' => '修改-log'
            )

        ) : array();
    }

    protected function perm_abonus()
    {
        return $this->isopen('abonus') && $this->is_perm_plugin('abonus') ? array(
            'text' => m('plugin')->getName('abonus'),
            'agent' =>
                array(
                    'text' => '代理管理',
                    'main' => '查看列表',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'export' => '导出-log',
                    'xxx' => array(
                        'check' => 'edit',
                        'aagentblack' => 'edit',
                    ),

                ),
            'level' =>
                array(
                    'text' => '代理等级',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),

            'bonus' =>
                array(
                    'text' => '分红管理',
                    'status0' => '待确认浏览',
                    'status1' => '待结算浏览',
                    'status2' => '已结算浏览',
                    'build' => '创建结算单-log',
                    'confirm' => '确认结算单-log',
                    'pay' => '结算结算单-log',
                    'export' => '导出结算单-log',
                    'delete' => '删除结算单-log',
                    'detail' => '查看结算单详情',
                    'detail.export' => '导出结算单详情-log',
                    'xxx' => array(
                        'payp' => 'pay',
                        'paymoney_level' => 'confirm',
                        'paymoney_aagent' => 'confirm'
                    )
                ),

            'notice' => array(
                'text' => '通知设置',
                'main' => '查看',
                'edit' => '修改-log',
            ),
            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log',
                ),
            'set' => array(
                'text' => '基本设置',
                'main' => '查看',
                'edit' => '修改-log'
            )

        ) : array();
    }


    protected function perm_merch()
    {
        return $this->isopen('merch') && $this->is_perm_plugin('merch') ? array(
            'text' => m('plugin')->getName('merch'),
            'reg' =>
                array(
                    'text' => '入驻申请',
                    'detail' => '查看详情',
                    'delete' => '删除-log',
                ),
            'user' =>
                array(
                    'text' => '商户管理',
                    'view' => '查看详情',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),
            'group' =>
                array(
                    'text' => '商户分组',
                    'view' => '查看详情',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),
            'category' =>
                array(
                    'text' => '商户分类',
                    'view' => '查看详情',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'swipe' => array(
                        'text' => '商户分类幻灯管理',
                        'view' => '查看详情',
                        'add' => '添加-log',
                        'edit' => '编辑-log',
                        'delete' => '删除-log'
                    )
                ),
            'statistics' =>
                array(
                    'text' => '数据统计',
                    'order' => '订单统计',
                    'order.export' => '导出订单统计-log',
                    'merch' => '商户统计',
                    'merch.export' => '导出商户统计-log',
                ),
            'check' =>
                array(
                    'text' => '提现申请',
                    'status1' => '待确认的申请',
                    'status2' => '待打款的申请',
                    'status3' => '已打款的申请',
                    'status_1' => '无效的申请',
                    'confirm' => '审核通过-log',
                    'pay' => '打款-log',
                    'refuse' => '驳回申请-log',
                    'export' => '导出申请单-log',
                    'detail' => '申请详情',
                    'detail.export' => '导出申请单订单详情-log',
                ),
            'notice' => array(
                'text' => '通知设置',
                'main' => '查看',
                'edit' => '修改-log',
            ),
            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log',
                    'register' => '申请入住',
                    'merchlist' => '商户导航',
                    'merchuser' => '商户导航(含定位距离)'
                ),
            'set' => array(
                'text' => '基本设置',
                'main' => '查看',
                'edit' => '修改-log'
            )

        ) : array();
    }

    protected function perm_mr()
    {
        return $this->isopen('mr') && $this->is_perm_plugin('mr') ? array(
            'text' => m('plugin')->getName('mr'),
            'goods' => array(
                'text' => '商品管理',
                'main'=>'查看列表',
                'view'=>'查看',
                'add'=>'添加-log',
                'edit'=>'编辑-log',
                'delete'=>'删除-log'
             ),
            'adv' => array(
                'text' => '幻灯片管理',
                'main'=>'查看列表',
                'view'=>'查看',
                'add'=>'添加-log',
                'edit'=>'编辑-log',
                'delete'=>'删除-log',
                'xxx'=>array(
                    'displayorder'=>'edit',
                    'enabled'=>'edit'
                )
            ),
            'order' => array(
                'text' => '订单管理',
                'main'=>'查看',
                'recharge'=>'手动充值-log',
                'refund'=>'退款-log',
                'export'=>'导出-log'
            ),
            'api' => array(
                'text' => '接口设置',
                'main'=>'查看列表',
                'view'=>'查看',
                'edit'=>'编辑-log'
            ),
            'set' => array(
                'text' => '全局设置',
                'main'=>'查看',
                'save'=>'编辑-log'
            )
        ) : array();
    }

    protected function perm_qa()
    {
        return $this->isopen('qa') && $this->is_perm_plugin('qa') ? array(
            'text' => m('plugin')->getName('qa'),
            'adv' =>
                array(
                    'text' => '幻灯片',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'displayorder' => 'edit',
                            'enabled' => 'edit',
                        )
                ),
            'question' => array(
                'text' => '问题管理',
                'main'=>'查看列表',
                'view'=>'查看',
                'add'=>'添加-log',
                'edit'=>'编辑-log',
                'delete'=>'删除-log',
                'xxx'=>array(
                    'displayorder'=>'edit',
                    'enabled'=>'edit',
                    'isrecommand'=>'edit'
                )
            ),
            'category' => array(
                'text' => '分类管理',
                'main'=>'查看列表',
                'view'=>'查看',
                'add'=>'添加-log',
                'edit'=>'编辑-log',
                'delete'=>'删除-log',
                'xxx'=>array(
                    'displayorder'=>'edit',
                    'enabled'=>'edit',
                    'isrecommand'=>'edit'
                )
            ),
            'set' => array(
                'text' => '基础设置',
                'main'=>'查看',
                'save'=>'编辑-log'
            )
        ) : array();
    }

    protected function perm_pstore()
    {
        return $this->isopen('pstore') && $this->is_perm_plugin('pstore') ? array(
            'text' => m('plugin')->getName('pstore'),
            'user' =>
                array(
                    'text' => '门店管理',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'status' => 'edit',
                        )
                ),
            'category' =>
                array(
                    'text' => '门店分类',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'status' => 'edit',
                        )
                ),
            'set' => array(
                'text' => '基础设置',
                'view'=>'查看',
                'edit'=>'编辑-log',
            ),
            'notice' => array(
                'text' => '消息通知',
                'view'=>'查看',
                'edit'=>'编辑-log',
            ),
            'clearing' => array(
                'text' => '门店结算',
                'main'=>'查看',
                'edit'=>'编辑-log'
            )
        ) : array();
    }

    protected function perm_author()
    {
        return $this->isopen('author') && $this->is_perm_plugin('author') ? array(
            'text' => m('plugin')->getName('author'),
            'partner' =>
                array(
                    'text' => '创始人管理',
                    'main' => '查看列表',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'export' => '导出-log',
                    'xxx' => array(
                        'check' => 'edit',
                        'authorblack' => 'edit',
                    ),

                ),
            'level' =>
                array(
                    'text' => '创始人等级',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                ),

            'bonus' =>
                array(
                    'text' => '分红管理',
                    'status0' => '待确认浏览',
                    'status1' => '待结算浏览',
                    'status2' => '已结算浏览',
                    'build' => '创建结算单-log',
                    'confirm' => '确认结算单-log',
                    'pay' => '结算结算单-log',
                    'export' => '导出结算单-log',
                    'delete' => '删除结算单-log',
                    'detail' => '查看结算单详情',
                    'detail.export' => '导出结算单创始人详情-log',
                    'xxx' => array(
                        'payp' => 'pay',
                        'paymoney' => 'confirm',
                    )
                ),

            'notice' => array(
                'text' => '通知设置',
                'main' => '查看',
                'edit' => '修改-log',
            ),
            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log',
                ),
            'set' => array(
                'text' => '基本设置',
                'main' => '查看',
                'edit' => '修改-log'
            ),
            'team' => array(
                'text' => '团队结算',
                'main' => '查看',
                'status0' => '待结算浏览',
                'status1' => '已结算浏览',
                'delete' => '删除结算单-log',
                'detail' => '查看结算单详情',
                'detail.edit' => '修改团队结算单'
            )
        ) : array();
    }

    protected function perm_task(){
        return $this->isopen('task') && $this->is_perm_plugin('task') ? array(
            'text' => m('plugin')->getName('task'),
            'main' => '查看列表',
            'view' => '查看',
            'add' => '添加-log',
            'edit' => '修改-log',
            'delete' => '删除-log',
            'clear' => '清除缓存-log',
            'xxx' => array(
                'setdefault' => 'edit',
            ),
            'log' =>
                array(
                    'text' => '关注记录',
                    'main' => '查看'
                ),
            'adv' =>
                array(
                    'text' => '幻灯片',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'displayorder' => 'edit',
                            'enabled' => 'edit',
                        )
                ),
            'default' =>
                array(
                    'text' => '系统设置',
                    'main' => '查看',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'xxx' =>
                        array(
                            'displayorder' => 'edit',
                            'enabled' => 'edit',
                        )
                )
        ) : array();
    }

    protected function perm_cashier()
    {
        return $this->isopen('cashier') && $this->is_perm_plugin('cashier') ? array(
            'text' => m('plugin')->getName('cashier'),
            'user' =>
                array(
                    'text' => '收银台管理',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'status' => 'edit',
                        )
                ),
            'category' =>
                array(
                    'text' => '收银台分类',
                    'main' => '查看列表',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'status' => 'edit',
                        )
                ),
            'set' => array(
                'text' => '基础设置',
                'view'=>'查看',
                'edit'=>'编辑-log',
            ),
            'notice' => array(
                'text' => '消息通知',
                'view'=>'查看',
                'edit'=>'编辑-log',
            ),
            'clearing' => array(
                'text' => '门店结算',
                'main'=>'查看',
                'edit'=>'编辑-log'
            )
        ) : array();
    }

//	protected function perm_system()
//	{
//		return array(
//			'text' => '系统设置',
//			'main' => '系统设置主页',
//			'plugin' =>
//				array(
//					'index' =>
//						array(
//							'text' => '应用信息',
//							'main' => '浏览',
//						),
//					'perm' =>
//						array(
//							'text' => '应用权限',
//							'main' => '浏览',
//							'add' => '添加-log',
//							'edit' => '修改-log',
//							'delete' => '删除-log',
//							'query' => '按条件查询模板',
//							'switchs' => '公众号应用权限-log',
//						),
//				),
//			'copyright' =>
//				array(
//					'index' =>
//						array(
//							'text' => '版权手机端',
//							'main' => '浏览',
//						),
//					'manage' =>
//						array(
//							'text' => '版权电脑版',
//							'main' => '浏览',
//						),
//					'notice' =>
//						array(
//							'text' => '公告管理',
//							'main' => '浏览',
//							'add' => '添加-log',
//							'edit' => '修改-log',
//							'delete' => '删除-log',
//							'displayorder' => '修改公告排序-log',
//							'status' => '修改公告状态-log',
//						),
//				),
//			'data' =>
//				array(
//					'index' =>
//						array(
//							'text' => '数据清理',
//							'main' => '浏览',
//						),
//					'backup' =>
//						array(
//							'text' => '数据下载',
//							'main' => '浏览',
//						),
//					'task' =>
//						array(
//							'text' => '计划任务',
//							'main' => '浏览',
//						),
//					'transfer' =>
//						array(
//							'text' => '数据转移',
//							'main' => '浏览',
//						),
//				),
//			'site' =>
//				array(
//					'index' =>
//						array(
//							'text' => '基本设置',
//							'main' => '浏览',
//						),
//					'banner' =>
//						array(
//							'text' => '幻灯片',
//							'main' => '浏览',
//							'add' => '添加-log',
//							'edit' => '修改-log',
//							'delete' => '删除-log',
//							'displayorder' => '修改幻灯片排序-log',
//							'status' => '修改幻灯片状态-log',
//						),
//					'case' =>
//						array(
//							'text' => '案例',
//							'main' => '浏览',
//							'add' => '添加-log',
//							'edit' => '修改-log',
//							'delete' => '删除-log',
//							'displayorder' => '修改案例排序-log',
//							'status' => '修改案例状态-log',
//						),
//					'link' =>
//						array(
//							'text' => '友情链接',
//							'main' => '浏览',
//							'delete' => '删除-log',
//						),
//					'category' =>
//						array(
//							'text' => '文章分类',
//							'main' => '浏览',
//							'delete' => '删除-log',
//						),
//				),
//		);
//	}

    //插件是否开启
    public function isopen($pluginname = '', $iscom = false)
    {

        if (empty($pluginname)) {
            return false;
        }
        $plugins = m('plugin')->getAll($iscom);
        $plugins_name = array();
        foreach ($plugins as $val){
            $plugins_name[] = $val['identity'];
        }
        if (in_array($pluginname, $plugins_name)) {
            foreach ($plugins as $plugin) {
                if ($plugin['identity'] == strtolower($pluginname)) {
                    if (empty($plugin['status'])) {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
        return true;
    }

    //公众号是否有权
    public function is_perm_plugin($pluginname = '', $iscom = false)
    {
        global $_W;
        if (empty($pluginname)) {
            return false;
        }
        $path = IA_ROOT . "/addons/ewei_shopv2/data/global";
        $permset = intval(m('cache')->getString('permset', 'global'));
        if (empty($permset) && is_file($path.'/perm.cache')){
            $permset = authcode(file_get_contents($path.'/perm.cache'),'DECODE','global');
        }
        if (!$permset){
            return true;
        }
        $perm_plugin = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_perm_plugin') . " WHERE acid=:uniacid limit 1", array(':uniacid' => $_W['uniacid']));
        if (!empty($perm_plugin)) {
            $plugins = explode(',', $perm_plugin['plugins']);
            $coms = explode(',', $perm_plugin['coms']);
            if ($iscom) {
                return in_array($pluginname, $coms);
            } else {
                return in_array($pluginname, $plugins);
            }
        }
        return true;
    }

    public function check_edit($permtype = '', $item = array())
    {
        if (empty($permtype)) {
            return false;
        }
//        if (strexists($permtype,'author')){
//            return true;
//        }
        if (!$this->check_perm($permtype)) {
            return false;
        }
        if (empty($item['id'])) {
            $add_perm = $permtype . ".add";
            if (!$this->check($add_perm)) {
                return false;
            }
            return true;
        } else {
            $edit_perm = $permtype . ".edit";
            if (!$this->check($edit_perm)) {
                return false;
            }
            return true;
        }
    }

    public function check_perm($permtypes = '')
    {
        global $_W;
        $check = true;
        if (empty($permtypes)) {
            return false;
        }
//        if (strexists($permtypes,'author')){
//            return true;
//        }
        if (!strexists($permtypes, '&') && !strexists($permtypes, '|')) {

            $check = $this->check($permtypes);
        } else if (strexists($permtypes, '&')) {

            $pts = explode('&', $permtypes);
            foreach ($pts as $pt) {
                $check = $this->check($pt);
                if (!$check) {
                    break;
                }
            }
        } else if (strexists($permtypes, '|')) {
            $pts = explode('|', $permtypes);
            foreach ($pts as $pt) {
                $check = $this->check($pt);
                if ($check) {
                    break;
                }
            }
        }
        return $check;
    }


    private function check($permtype = '')
    {
        global $_W, $_GPC;

        if ($_W['role'] == 'manager' || $_W['role'] == 'founder') {
            return true;
        }
        $uid = $_W['uid'];
        if (empty($permtype)) {
            return false;
        }
        $user = pdo_fetch('select u.status as userstatus,r.status as rolestatus,u.perms2 as userperms,r.perms2 as roleperms,u.roleid from ' . tablename('ewei_shop_perm_user') . ' u '
            . ' left join ' . tablename('ewei_shop_perm_role') . ' r on u.roleid = r.id '
            . ' where u.uid=:uid limit 1 ', array(':uid' => $uid));

        if (empty($user) || empty($user['userstatus'])) {
            return false;
        }
        if (!empty($user['roleid']) && empty($user['rolestatus'])) {
            return false;
        }

        $role_perms = explode(',', $user['roleperms']);
        $user_perms = explode(',', $user['userperms']);
        $perms = array_merge($role_perms, $user_perms);
        if (empty($perms)) {
            return false;
        }


        $is_xxx = $this->check_xxx($permtype);

        if ($is_xxx) {
            if (!in_array($is_xxx, $perms)) {
                return false;
            }
        } else {
            if (!in_array($permtype, $perms)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 查看是不是继承
     * @param $permtype
     * @return bool|string
     */
    function check_xxx($permtype)
    {
        if ($permtype) {
            $allPerm = $this->allPerms();
            $permarr = explode('.', $permtype);
            if (isset($permarr[3])) {
                $is_xxx = isset($allPerm[$permarr[0]][$permarr[1]][$permarr[2]]['xxx'][$permarr[3]]) ? $allPerm[$permarr[0]][$permarr[1]][$permarr[2]]['xxx'][$permarr[3]] : false;
            } elseif (isset($permarr[2])) {
                $is_xxx = isset($allPerm[$permarr[0]][$permarr[1]]['xxx'][$permarr[2]]) ? $allPerm[$permarr[0]][$permarr[1]]['xxx'][$permarr[2]] : false;
            } elseif (isset($permarr[1])) {
                $is_xxx = isset($allPerm[$permarr[0]]['xxx'][$permarr[1]]) ? $allPerm[$permarr[0]]['xxx'][$permarr[1]] : false;
            } else {
                $is_xxx = false;
            }
            if ($is_xxx) {
                $permarr = explode('.', $permtype);
                array_pop($permarr);
                $is_xxx = implode('.', $permarr) . '.' . $is_xxx;
            }
            return $is_xxx;
        }
        return false;

    }

    function check_plugin($pluginname = '')
    {

        global $_W, $_GPC;

        //如果未开启插件分权
        $permset = $this->getPermset();
        if (empty($permset)) {
            return true;
        }

        if ($_W['role'] == 'founder' || empty($_W['role'])) {
            return true;
        }

        //插件开关判断
        $isopen = $this->isopen($pluginname);
        if (!$isopen) {
            return false;
        }

        $allow = true;
        //如果多客户服务商城判断插件
        $acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE `uniacid`=:uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
        //先判断公众号的
        $ac_perm = pdo_fetch('select  plugins from ' . tablename('ewei_shop_perm_plugin') . ' where acid=:acid limit 1', array(':acid' => $acid));
        if (!empty($ac_perm)) {

            $allow_plugins = explode(',', $ac_perm['plugins']);
            if (!in_array($pluginname, $allow_plugins)) {
                $allow = false;
            }
        } else {

            load()->model('account');
            $accounts = uni_owned($_W['founder']);
            if (in_array($_W['uniacid'], array_keys($accounts))) {
                //判读公众号是否属于超级管理员
                $allow = true;
            } else {
                $allow = false;
            }
        }
        if (!$allow) {
            return false;
        }

        return true;
    }

    function getPermset()
    {
        $path = IA_ROOT . "/addons/ewei_shopv2/data/global";
        $permset = intval(m('cache')->getString('permset', 'global'));
        if (empty($permset) && is_file($path.'/perm.cache')){
            $permset = authcode(file_get_contents($path.'/perm.cache'),'DECODE','global');
        }
        return $permset;
    }

    function check_com($comname = '')
    {

        global $_W, $_GPC;

        //如果未开启插件分权
        $permset = $this->getPermset();
        if (empty($permset)) {
            return true;
        }
        if ($_W['role'] == 'founder' || empty($_W['role'])) {
            return true;
        }

        //插件开关判断
        $isopen = $this->isopen($comname, true);
        if (!$isopen) {
            return false;
        }

        $allow = true;
        //如果多客户服务商城判断插件
        $acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE `uniacid`=:uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
        //先判断公众号的
        $ac_perm = pdo_fetch('select  coms from ' . tablename('ewei_shop_perm_plugin') . ' where acid=:acid limit 1', array(':acid' => $acid));
        if (!empty($ac_perm)) {

            $allow_coms = explode(',', $ac_perm['coms']);
            if (!in_array($comname, $allow_coms)) {
                $allow = false;
            }
        } else {

            load()->model('account');
            $accounts = uni_owned($_W['founder']);
            if (in_array($_W['uniacid'], array_keys($accounts))) {
                //判读公众号是否属于超级管理员
                $allow = true;
            } else {
                $allow = false;
            }

        }
        if (!$allow) {
            return false;
        }
        return true;

    }

    public function getLogName($type = '', $logtypes = null)
    {
        if (!$logtypes) {
            $logtypes = $this->getLogTypes();
        }
        foreach ($logtypes as $t) {
            if ($t['value'] == $type) {
                return $t['text'];
            }
        }
        return '';
    }

    public function getLogTypes($all = false)
    {
        if (empty(self::$getLogTypes)) {
            $perms = $this->allPerms();
            $array = array();
            foreach ($perms as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $ke => $val) {
                        if (!is_array($val)) {
                            if ($all) {
                                if ($ke == 'text') {
                                    $text = str_replace("-log", "", $value['text']);
                                } else {
                                    $text = str_replace("-log", "", $value['text'] . "-" . $val);
                                }
                                $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke));
                            } else {
                                if (strexists($val, '-log')) {
                                    $text = str_replace("-log", "", $value['text'] . "-" . $val);
                                    if ($ke == 'text') {
                                        $text = str_replace("-log", "", $value['text']);
                                    }
                                    $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke));
                                }
                            }

                        }
                        if (is_array($val) && $ke != 'xxx') {
                            foreach ($val as $k => $v) {
                                if (!is_array($v)) {
                                    if ($all) {
                                        if ($ke == 'text') {
                                            $text = str_replace("-log", "", $value['text'] . "-" . $val['text']);
                                        } else {
                                            $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v);
                                        }
                                        $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k));
                                    } else {
                                        if (strexists($v, '-log')) {
                                            $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v);
                                            if ($k == 'text') {
                                                $text = str_replace("-log", "", $value['text'] . "-" . $val['text']);
                                            }
                                            $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k));
                                        }
                                    }

                                }
                                if (is_array($v) && $k != 'xxx') {
                                    foreach ($v as $kk => $vv) {
                                        if (!is_array($vv)) {
                                            if ($all) {
                                                if ($ke == 'text') {
                                                    $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text']);
                                                } else {
                                                    $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text'] . "-" . $vv);
                                                }
                                                $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k . "." . $kk));
                                            } else {
                                                if (strexists($vv, '-log')) {
                                                    if (empty($val['text']))
                                                        $text = str_replace("-log", "", $value['text'] . "-" . $v['text'] . "-" . $vv);
                                                    else
                                                        $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text'] . "-" . $vv);
                                                    if ($kk == 'text') {
                                                        $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text']);
                                                    }
                                                    $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k . "." . $kk));
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            self::$getLogTypes = $array;
        }
        return self::$getLogTypes;
    }

    /*	public function getLogTypes1($all = false)
        {
            $perms = $this->allPerms();
            $array = array();
            array_walk($perms, function ($value, $key) use (&$array, $all) {
                if (is_array($value)) {
                    array_walk($value, function ($val, $ke) use (&$array, $value, $key, $all) {
                        if (!is_array($val)) {
                            if ($all) {
                                if ($ke == 'text') {
                                    $text = str_replace("-log", "", $value['text']);
                                } else {
                                    $text = str_replace("-log", "", $value['text'] . "-" . $val);
                                }
                                $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke));
                            } else {
                                if (strexists($val, '-log')) {
                                    $text = str_replace("-log", "", $value['text'] . "-" . $val);
                                    if ($ke == 'text') {
                                        $text = str_replace("-log", "", $value['text']);
                                    }
                                    $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke));
                                }
                            }

                        }
                        if (is_array($val) && $ke != 'xxx') {
                            array_walk($val, function ($v, $k) use (&$array, $value, $key, $val, $ke, $all) {
                                if (!is_array($v)) {
                                    if ($all) {
                                        if ($ke == 'text') {
                                            $text = str_replace("-log", "", $value['text'] . "-" . $val['text']);
                                        } else {
                                            $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v);
                                        }
                                        $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k));
                                    } else {
                                        if (strexists($v, '-log')) {
                                            $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v);
                                            if ($k == 'text') {
                                                $text = str_replace("-log", "", $value['text'] . "-" . $val['text']);
                                            }
                                            $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k));
                                        }
                                    }

                                }
                                if (is_array($v) && $k != 'xxx') {
                                    array_walk($v, function ($vv, $kk) use (&$array, $value, $key, $val, $ke, $v, $k, $all) {
                                        if (!is_array($vv)) {
                                            if ($all) {
                                                if ($ke == 'text') {
                                                    $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text']);
                                                } else {
                                                    $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text'] . "-" . $vv);
                                                }
                                                $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k . "." . $kk));
                                            } else {
                                                if (strexists($vv, '-log')) {
                                                    if (empty($val['text']))
                                                        $text = str_replace("-log", "", $value['text'] . "-" . $v['text'] . "-" . $vv);
                                                    else
                                                        $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text'] . "-" . $vv);
                                                    if ($kk == 'text') {
                                                        $text = str_replace("-log", "", $value['text'] . "-" . $val['text'] . "-" . $v['text']);
                                                    }
                                                    $array[] = array('text' => $text, 'value' => str_replace(".text", "", $key . "." . $ke . "." . $k . "." . $kk));
                                                }
                                            }

                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            });
            return $array;
        }*/

    public function log($type = '', $op = '')
    {
        global $_W;
        $this->check_xxx($type);
        if ($is_xxx = $this->check_xxx($type)) {
            $type = $is_xxx;
        }
        static $_logtypes;
        if (!$_logtypes) {
            $_logtypes = $this->getLogTypes();
        }
        $log = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['uid'],
            'name' => $this->getLogName($type, $_logtypes),
            'type' => $type,
            'op' => $op,
            'ip' => CLIENT_IP,
            'createtime' => time()
        );
        pdo_insert('ewei_shop_perm_log', $log);
    }

    public function formatPerms()
    {
        if (empty(self::$formatPerms)) {
            $perms = $this->allPerms();
            $array = array();
            foreach($perms as $key=>$value) {
                if (is_array($value)) {
                    foreach($value as $ke=>$val) {
                        if (!is_array($val)) {
                            $array['parent'][$key][$ke] = $val;
                        }
                        if (is_array($val) && $ke != 'xxx') {
                            foreach($val as $k=>$v) {
                                if (!is_array($v)) {
                                    $array['son'][$key][$ke][$k] = $v;
                                }
                                if (is_array($v) && $k != 'xxx') {
                                    foreach($v as $kk => $vv) {
                                        if (!is_array($vv)) {
                                            $array['grandson'][$key][$ke][$k][$kk] = $vv;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            self::$formatPerms = $array;
        }
        return self::$formatPerms;
    }

//    public function formatPerms()
//    {
//        $perms = $this->allPerms();
//        $array = array();
//        array_walk($perms, function ($value, $key) use (&$array) {
//            if (is_array($value)) {
//                array_walk($value, function ($val, $ke) use (&$array, $key) {
//                    if (!is_array($val)) {
//                        $array['parent'][$key][$ke] = $val;
//                    }
//                    if (is_array($val) && $ke != 'xxx') {
//                        array_walk($val, function ($v, $k) use (&$array, $key, $ke) {
//                            if (!is_array($v)) {
//                                $array['son'][$key][$ke][$k] = $v;
//                            }
//                            if (is_array($v) && $k != 'xxx') {
//                                array_walk($v, function ($vv, $kk) use (&$array, $key, $ke, $k) {
//                                    if (!is_array($vv)) {
//                                        $array['grandson'][$key][$ke][$k][$kk] = $vv;
//                                    }
//                                });
//                            }
//                        });
//                    }
//                });
//            }
//        });
//        return $array;
//    }


    protected function perm_sns()
    {
        return $this->isopen('sns') && $this->is_perm_plugin('sns') ? array(
            'text' => m('plugin')->getName('sns'),
            'adv' =>
                array(
                    'text' => '幻灯片',
                    'main' => '查看列表',
                    'view' => '查看详细',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'displayorder' => 'edit',
                            'enabled' => 'edit',
                        )
                ),
            'category' =>
                array(
                    'text' => '分类管理',
                    'main' => '查看列表',
                    'view' => '查看详细',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'enabled' => 'edit',
                            'displayorder' => 'edit'
                        )
                ),
            'level' =>
                array(
                    'text' => '等级管理',
                    'main' => '查看列表',
                    'view' => '查看详细',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'view' => '查看详细',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'enabled' => 'edit'
                        )
                ),

            'member' =>
                array(
                    'text' => '会员管理',
                    'main' => '查看列表',
                    'delete' => '删除-log',
                    'setblack' => '设置黑名单-log'
                ),

              'manage' =>
                 array(
                     'text' => '版主管理',
                     'main' => '查看列表',
                     'view' => '查看详细',
                     'add' => '添加-log',
                     'edit' => '修改-log',
                     'delete' => '删除-log' 
                ),


            'board' =>
                array(
                    'text' => '版块管理',
                    'main' => '查看列表',
                    'view' => '查看详细',
                    'add' => '添加-log',
                    'edit' => '修改-log',
                    'delete' => '删除-log',
                    'xxx' =>
                        array(
                            'status' => 'edit',
                            'displayorder' => 'edit'
                        )
                ),

            'posts' =>
                array(
                    'text' => '话题管理',
                    'main' => '查看',
                    'delete' => '删除-log',
                    'delete1' => '彻底删除-log',
                    'check' => '审核-log',
                    'best' => '精华-log',
                    'top' => '置顶-log'
                ),
            'replys' =>
                array(
                    'text' => '评论管理',
                    'main' => '查看',
                    'delete' => '删除-log',
                    'delete1' => '彻底删除-log',
                    'check' => '审核-log'
                ),

            'cover' =>
                array(
                    'text' => '入口设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'notice' =>
                array(
                    'text' => '通知设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                ),
            'set' =>
                array(
                    'text' => '基础设置',
                    'main' => '查看',
                    'edit' => '修改-log'
                )
        ) : array();
    }

    /*拼团权限*/
    protected function perm_seckill()
    {
        return $this->isopen('seckill')&&$this->is_perm_plugin('seckill')?array(
            'text' => m('plugin')->getName('seckill'),
            'task' => array(
                'text'=>'专题管理',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log' ,
                'xxx' =>
                    array(
                        'enabled' => 'edit'
                    )
            ),
            'room' => array(
                'text'=>'会场管理',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log' ,
                'xxx' =>
                    array(
                        'enabled' => 'edit'
                    )
            ),
            'goods' => array(
                'text' => '商品管理',
                'view'=>'查看',
                'delete'=>'取消-log'
            ),
            'category' => array(
                'text' => ' 分类管理',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log',
            ),
            'adv' => array(
                'text' => '幻灯片管理',
                'view'=>'查看',
                'edit'=>'编辑-log',
                'add'=>'添加-log',
                'delete'=>'删除-log',
            ),
            'calendar' => array(
                'text' => '任务设置',
                'view' => '查看',
                'edit' => '编辑-log',
            ),
            'cover' => array(
                'text' => '入口设置',
                'view' => '查看',
                'edit' => '编辑-log',
            )
        ):array();
    }
}
