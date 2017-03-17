<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Category_EweiShopV2Page extends MobilePage {

    function main() {
        global $_W,$_GPC;

        $merchid = intval($_GPC['merchid']);
        $category_set = $_W['shopset']['category'];
        $category_set['advimg'] = tomedia($category_set['advimg']);
        if ($category_set['level'] == -1 )
        {
            $this->message("暂时未开启分类",'','error');
        }
        $category = $this->getCategory($category_set['level'], $merchid);
        $set = m('common')->getSysset('category');
        include $this->template();
    }
    function suxin(){
        global $_W,$_GPC;
        $url="http://www.shuxinbaobei.com/newshuxin/link.php?openid=".$_W['openid'];
        Header("Location: $url");
    }

    function suxinpost(){
        global $_W,$_GPC;

        $url="http://www.shuxinbaobei.com/newshuxin/link.php?openid=".$_W['openid']."&url=".urlencode($_GPC['url']);
        Header("Location: $url");
    }

    protected function getCategory($level, $merchid = 0)
    {
        $level = intval($level);
        $category = m('shop')->getCategory();
        $category_parent = array();
        $category_children = array();
        $category_grandchildren = array();

        if ($merchid > 0) {
            //多商户
            $merch_plugin = p('merch');
            $merch_data = m('common')->getPluginset('merch');
            if ($merch_plugin && $merch_data['is_openmerch']) {
                $is_openmerch = 1;
            } else {
                $is_openmerch = 0;
            }

            if ($is_openmerch) {
                $merch_category = $merch_plugin->getSet('merch_category', $merchid);
                if (!empty($merch_category)) {
                    if (!empty($category['parent'])) {
                        foreach ($category['parent'] as $key => $value) {
                            if (array_key_exists($value['id'], $merch_category)) {
                                $category['parent'][$key]['enabled'] = $merch_category[$value['id']];
                            }
                        }
                    }
                    if (!empty($category['children'])) {
                        foreach ($category['children'] as $key => $value) {
                            if (!empty($value)) {
                                foreach ($value as $k => $v) {
                                    if (array_key_exists($v['id'], $merch_category)) {
                                        $category['children'][$key][$k]['enabled'] = $merch_category[$v['id']];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach($category['parent'] as $value){
            if ($value['enabled'] == 1)
            {
                $value['thumb'] = tomedia($value['thumb']);
                $value['advimg'] = tomedia($value['advimg']);
                $category_parent[$value['parentid']][] = $value;
                if (!empty($category['children'][$value['id']]) && $level >= 2)
                {
                    foreach($category['children'][$value['id']] as $val){
                        if ($val['enabled']==1)
                        {
                            $val['thumb'] = tomedia($val['thumb']);
                            $val['advimg'] = tomedia($val['advimg']);
                            $category_children[$val['parentid']][] = $val;
                            if (!empty($category['children'][$val['id']]) && $level >= 3)
                            {
                                foreach($category['children'][$val['id']] as $v){
                                    if ($v['enabled']==1)
                                    {
                                        $v['thumb'] = tomedia($v['thumb']);
                                        $v['advimg'] = tomedia($v['advimg']);
                                        $category_grandchildren[$v['parentid']][] = $v;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return array(
            'parent' => $category_parent,
            'children' => $category_children,
            'grandchildren' => $category_grandchildren,
        );
    }

}
