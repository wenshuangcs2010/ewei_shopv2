<?php

/*
 * 人人商城V2
 * 
 * @author ewei 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require MODULE_ROOT. '/defines.php';
/*
wsq 加载通用配置文件
 */

class ComProcessor extends WeModuleProcessor {
    public $model;
    public $modulename;
    public $message;
    public function __construct($name = '') {
 
        $this->modulename = 'ewei_shopv2';
        $this->pluginname = $name;
      
        //自动加载插件model.php
        $this->loadModel();  
    }
      /**
     * 加载插件model
     */
    private function loadModel(){
        $modelfile = IA_ROOT.'/addons/'.$this->modulename."/core/com/".$this->pluginname.".php";
         if(is_file($modelfile)){
              $classname = ucfirst($this->pluginname)."_EweiShopV2ComModel";
              require $modelfile;
              $this->model = new $classname($this->pluginname);
         }
    }
    public function respond(){
        $this->message = $this->message;
    }

}