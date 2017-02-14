<?php
/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */

if (!defined('ES_PATH')) {
    exit('Access Denied');
}
class EmptyController  extends Controller {
    
     function index(){
         global $controller;
         trigger_error( " Controller <b>{$controller}</b> Not Found !" );
     }
    
}