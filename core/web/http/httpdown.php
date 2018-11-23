<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Httpdown_EweiShopV2Page extends WebPage {
	var $bind=array();
	var $PDOStatement=null;
	var $linkID=null;
	function main(){
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$depot=Dispage::getDepot($goods['depotid']);
		if(empty($goods['goodssn'])){
			$goodsoption=pdo_fetchall("SELECT id,goodssn from ".tablename("ewei_shop_goods_option")." where goodsid=:gid",array(":gid"=>$goods['id']));
			
			foreach ($goodsoption as $value) {
				$goodssn[]=trim($value['goodssn']);
			}
			$goodssn=array_unique($goodssn);
			$goods['goodssn']=$goodssn;
			
		}
		if(empty($goods['goodssn'])){
			show_json(0,"参数丢失无法更新");
		}

		
		if($depot['updateid']==1||$depot['updateid']==3){
			$return=m("httpUtil")->updateGoods($goods['goodssn'],$goods['id']);
		}elseif($depot['updateid']==2){
			//同步成本
			//m("httpUtil")->oneupdateGoodsprice($goods['id']);

			$return=m("httpUtil")->updateAdressGoods($goods['goodssn'],$goods['id'],$depot['storeroomid']);
			
			//show_json(1,$retdata);
		}
		if($return){
			show_json(1,"更新成功");
		}
		show_json(0,'更新失败');
	}


	function cnbuyersynch(){
		global $_W, $_GPC;
		$this->linkID=$this->get_cnbuyerDb();
		$id = intval($_GPC['id']);
		$goods = pdo_fetch("select * from " . tablename('ewei_shop_goods') . " where id=:id and uniacid=:uniacid limit 1", array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$depot=Dispage::getDepot($goods['depotid']);
		if(empty($goods['goodssn'])){
			show_json(0,"参数丢失无法同步");
		}
		//同步到保税超市60677
		$insert_data=array(
			'goods_id'=>$id,
			);
		
		$url="http://www.cnbuyers.cn/index.php?app=omssend&act=we7goods";
		load()->func('communication');
		$resp = ihttp_request($url, $insert_data);
		$content=(array)json_decode($resp['content'],true);
		if($content['error']<0){
			show_json(0,$content['msg']);
		}
		show_json(1,$content['msg']);
	}
	/*
 	 protected function bindValue(array $bind = [])
    {
    	 foreach ($bind as $key => $val) {
            // 占位符
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                }
                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
        }
    }
	private function get_cnbuyerDb(){
		
		//$cnbuyersdsn = "mysql:host=192.168.200.3;dbname=cnbuyers_cs";
  		//$cnbuerydb = new PDO($cnbuyersdsn, 'cgfx', 'cgfx123456',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
  		$cnbuyersdsn = "mysql:host=127.0.0.1;dbname=cnbuyers_cs";
  		$cnbuerydb = new PDO($cnbuyersdsn, 'root', 'root',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
  		$cnbuerydb->setAttribute(PDO::ATTR_PERSISTENT,true);

		$cnbuerydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  		//链接保税超市DB
  		return $cnbuerydb;
	}

	//创建生成 INSERT SQL 
	private function _cnbuyer_caeate_insertSql($table,$arrData) {
		$this->bind=array();
		$fields = array_keys($arrData);
		$values = array_values($arrData);

		$bindvalues=array();

		foreach ($fields as $key => &$value) {
			$key_bind="__dataparam__{$value}";
			$bindvalues[$key]=":__dataparam__{$value}";
			$this->bind($key_bind,$values[$key]);
			$value="`".$value."`";
		}
		unset($value);
        $field=implode(' , ', $fields);
        $valuefield=implode(' , ', $bindvalues);
        $insertSql="INSERT INTO {$table}  ({$field}) VALUES ({$valuefield})";
        return $insertSql;
	}
	private function bind($key, $value = false, $type = PDO::PARAM_STR)
    {
        if (is_array($key)) {
            $this->bind = array_merge($this->bind, $key);
        } else {
            $this->bind[$key] = [$value, $type];
        }
    }*/
 }