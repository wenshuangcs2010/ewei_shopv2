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
			$this->db = new PDO($dsn, $this->dbusername, $this->dbpass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
		}catch(Exception $e){
			$this->db="";

		}
	}
	public function __construct() {
		$this->getdb();
	}
	public function getgoodslist($storeroomid){
		$sql="select g.only_sku,gp.stock,g.if_show from cs_goods as g ".
		" LEFT JOIN cs_goods_spec as gp ON g.goods_id=gp.goods_id".
		" where g.type='material' and g.store_id ={$storeroomid}";
		if(!$this->db){
			return false;
		}
		$ret=$this->db->query($sql);
		$result=$ret->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

	public function get_stock($goodssn){
		$sql="select g.only_sku,gp.stock,g.if_show from cs_goods as g ".
		" LEFT JOIN cs_goods_spec as gp ON g.goods_id=gp.goods_id".
		" where g.type='material' and g.only_sku='{$goodssn}'  LIMIT 0,1 ";
		if(!$this->db){
			return false;
		}
		$result=$this->db->query($sql);
		if(!empty($result)){
			$result=$result->fetch(PDO::FETCH_ASSOC);
		}
		//
		return $result;
	}
	public function updateCnbuyerStock($goodssn,$num){
		$sql="select gp.spec_id,g.only_sku,gp.stock,g.if_show from cs_goods as g ".
		" LEFT JOIN cs_goods_spec as gp ON g.goods_id=gp.goods_id".
		" where  g.only_sku ='{$goodssn}'";
		if(!$this->db){
			return false;
		}
		$ret=$this->db->query($sql);
		$result=$ret->fetch(PDO::FETCH_ASSOC);
		
		$updatesql="update cs_goods_spec set stock=stock+{$num} where spec_id=".$result['spec_id'];
		$this->db->query($updatesql);
	}
	//查询已经获取过的身份证信息
	public function getRelanme($relname,$idcardno){
		$sql="select * from cs_realname where realname='{$relname}' and idno='{$idcardno}'";
		if(!$this->db){
			return false;
		}
		$ret=$this->db->query($sql);
		if(!empty($ret)){
			$result=$ret->fetch(PDO::FETCH_ASSOC);
			return $result;
		}
		return false;
	}
	public function getimidList($imid){
		$sql="select count(*) from cs_realname where idno='{$imid}'";
		if(!$this->db){
			return false;
		}
		$ret=$this->db->query($sql);
		if(!empty($ret)){
			$result=$ret->fetch(PDO::FETCH_ASSOC);
			return $result;
		}
		return false;
	}
	public function insertRelanme($data){
		
		$sql="insert into cs_realname";
		$condition=$this->implode($data,",") ;
		$sql.=" SET {$condition['fields']}";
		$stmt = $this->db->prepare($sql);
		$stmt->execute($condition['params']);
		return $this->db->lastInsertId();
	}
	public function getOrderinvon($order_sn){
		$sql="select o.invoice_no,oe.shipping_name,sh.com_code from cs_order as o LEFT JOIN cs_order_extm oe ON o.order_id = oe.order_id LEFT JOIN cs_shipping sh ON sh.shipping_id=oe.shipping_id where o.order_sn='{$order_sn}'";
		$ret=$this->db->query($sql);
		if(!empty($ret)){
			$result=$ret->fetch(PDO::FETCH_ASSOC);
			return $result;
		}
		return false;
	}
	public function __destruct (){//关闭链接
		$this->db="";
	}
	private function implode($params, $glue = ',') {
		$result = array('fields' => ' 1 ', 'params' => array());
		$split = '';
		$suffix = '';
		$allow_operator = array('>', '<', '<>', '!=', '>=', '<=', '+=', '-=', 'LIKE', 'like');
		if (in_array(strtolower($glue), array('and', 'or'))) {
			$suffix = '__';
		}
		if (!is_array($params)) {
			$result['fields'] = $params;
			return $result;
		}
		if (is_array($params)) {
			$result['fields'] = '';
			foreach ($params as $fields => $value) {
				$operator = '';
				if (strpos($fields, ' ') !== FALSE) {
					list($fields, $operator) = explode(' ', $fields, 2);
					if (!in_array($operator, $allow_operator)) {
						$operator = '';
					}
				}
				if (empty($operator)) {
					$fields = trim($fields);
					if (is_array($value)) {
						$operator = 'IN';
					} else {
						$operator = '=';
					}
				} elseif ($operator == '+=') {
					$operator = " = `$fields` + ";
				} elseif ($operator == '-=') {
					$operator = " = `$fields` - ";
				}
				if (is_array($value)) {
					$insql = array();
					foreach ($value as $k => $v) {
						$insql[] = ":{$suffix}{$fields}_{$k}";
						$result['params'][":{$suffix}{$fields}_{$k}"] = is_null($v) ? '' : $v;
					}
					$result['fields'] .= $split . "`$fields` {$operator} (".implode(",", $insql).")";
					$split = ' ' . $glue . ' ';
				} else {
					$result['fields'] .= $split . "`$fields` {$operator}  :{$suffix}$fields";
					$split = ' ' . $glue . ' ';
					$result['params'][":{$suffix}$fields"] = is_null($value) ? '' : $value;
				}
			}
		}
		return $result;
	}
}