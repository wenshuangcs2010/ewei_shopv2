<?php

class Api_EweiShopV2Page extends Page {

 function main(){
 	global $_W,$_GPC;
 	$openid=$_GPC['openid'];
 	//$openid="olLwew-sdqFwB36L99OFShXsHDho";
 	m("shuxinapi")->get_order($openid,$_GPC['start_time'],$_GPC['end_time']);
 }
}