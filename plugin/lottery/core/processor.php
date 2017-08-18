<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require IA_ROOT . '/addons/ewei_shopv2/defines.php';
require EWEI_SHOPV2_INC . 'plugin_processor.php';

class LotteryProcessor extends PluginProcessor {
    //暂时无用
    public function __construct() {
        parent::__construct('lottery');
    }

    public function respond($obj = null) {
        global $_W;
        $message = $obj->message;
        $msgtype = strtolower($message['msgtype']);
        $event = strtolower($message['event']);
    }


    private function responseEmpty() {
        ob_clean();
        ob_start();
        echo '';
        ob_flush();
        ob_end_flush();
        exit(0);
    }

}
