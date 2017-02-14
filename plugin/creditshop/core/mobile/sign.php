<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Sign_EweiShopV2Page extends PluginMobilePage {

    public function main() {
        global $_W, $_GPC;

        include $this->template();
    }

}