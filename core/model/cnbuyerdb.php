<?php

class Cnbuyerdb_EweiShopV2Model {
	var $dbname="cnbuyer_cs";
	var $dbusername="cgfx";
	var $dbpass="cgfx123456";
	var $db="";
	private function getdb(){
		$dsn = "mysql:host=192.168.200.3;dbname=".$this->dbname;
		try{
			$this->db = new PDO($dsn, $dbusername, $dbpass);
		}catch(Exception $e){
			echo $e->getMessage()."\n";
			exit;
		}
	}
	public function __construct() {
		$this->getdb();
	}
	public function test(){
		echo "aa";
	}
}