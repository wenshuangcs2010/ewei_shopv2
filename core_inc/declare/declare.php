<?php
class DeclareCore{
	public static function getObject($type,$config){
		require_once(EWEI_SHOPV2_TAX_CORE. 'declare/'.$type.'.declare.php');
		$calasname=$type."_Api";
		$object=new $calasname($config);
		return $object;
	}
}