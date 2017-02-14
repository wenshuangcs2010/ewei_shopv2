<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}
require MODULE_ROOT. '/defines.php';
 
class PluginProcessor extends WeModuleProcessor {
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
 
        $modelfile = IA_ROOT.'/addons/'.$this->modulename."/plugin/".$this->pluginname."/core/model.php";
         if(is_file($modelfile)){
              $classname = ucfirst($this->pluginname)."Model";
              require $modelfile;
              $this->model = new $classname($this->pluginname);
         }
    }
    public function respond($obj=''){
        $this->message = $this->message;
    }
}