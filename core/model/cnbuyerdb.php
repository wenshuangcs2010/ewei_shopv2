<?php

class Cnbuyerdb_EweiShopV2Model {
	var $dbname="cnbuyers_cs";
	var $dbusername="cgfx";
	var $dbpass="cgfx123456";
	var $host="192.168.200.3";
	// var $dbusername="root";
	// var $dbpass="root";
	// var $host="127.0.0.1";
	var $db="";
	private function getdb(){
		$dsn = "mysql:host=".$this->host.";dbname=".$this->dbname;
		try{
			$this->db = new PDO($dsn, $this->dbusername, $this->dbpass);
		}catch(Exception $e){
			echo $e->getMessage()."\n";
			exit;
		}
	}
	public function __construct() {
		$this->getdb();
	}
	public function getgoodslist($storeroomid){
		$sql="select g.only_sku,gp.stock,g.if_show from cs_goods as g ".
		" LEFT JOIN cs_goods_spec as gp ON g.goods_id=gp.goods_id".
		" where g.type='material' and g.store_id ={$storeroomid}";
		$ret=$this->db->query($sql);
		$result=$ret->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}


	public function updateCnbuyerStock($goodssn,$num){
		$sql="select gp.spec_id,g.only_sku,gp.stock,g.if_show from cs_goods as g ".
		" LEFT JOIN cs_goods_spec as gp ON g.goods_id=gp.goods_id".
		" where  g.only_sku ='{$goodssn}'";
		
		$ret=$this->db->query($sql);
		$result=$ret->fetch(PDO::FETCH_ASSOC);
	
		$updatesql="update cs_goods_spec set stock=stock+{$num} where spec_id=".$result['spec_id'];
		$this->db->query($updatesql);
	}
	public function __destruct (){//关闭链接
		$this->db="";
	}
}