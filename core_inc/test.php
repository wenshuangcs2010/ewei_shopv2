<?php
require dirname(__FILE__).'/../../../framework/bootstrap.inc.php';
require IA_ROOT.'/addons/ewei_shopv2/defines.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT.'/addons/ewei_shopv2/core/inc/com_model.php';


$data=m('rasutil')->runBefore()->decrypt($_SERVER['HTTP_SIGN'],'hex');
var_dump($data);