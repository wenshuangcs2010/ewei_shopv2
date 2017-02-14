<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends WebPage {
	function main() {
		global $_W, $_GPC;
		global $_W;
		if(cv('disseting.resellist')){
			header('location: '.webUrl('disseting/resellist'));
		}
	}

	function resellist(){

		global $_W;
		$resellerlist=pdo_fetchall("select * from " . tablename('ewei_shop_reseller'));
		include $this->template();
	}
	function reselleradd(){
		global $_W,$_GPC;
		$globalaction="add";
		if($_W['ispost']){
			$data['name']=trim($_GPC['resellername']);
			$re=pdo_insert('ewei_shop_reseller',$data);
			$id=pdo_insertid();
			plog('log.add', "新曾代理等级ID{$id},{$data['name']}");
			show_json(1,array('url'=>webUrl('disseting/resellist'),'message' => "添加成功"));
		}
		include $this->template("disseting/reselleradd");
	}
	function reselleredit(){
		global $_W,$_GPC;
		$globalaction="edit";
		$id=intval($_GPC['id']);
		if($_W['ispost']){
			$data['name']=trim($_GPC['resellername']);
			pdo_update("ewei_shop_reseller",$data,array("id"=>$id));
			plog('log.edit', "编辑代理等级 ID: {$id},{$data['name']}");
			show_json(1,array('url'=>webUrl('disseting/resellist'),'message' => "修改成功"));
		}
		$item=pdo_fetch("select * from ".tablename("ewei_shop_reseller")." where id=:id",array(":id"=>$id));
		include $this->template("disseting/reselleradd");
	}
	function resellerdelete(){
		global $_W,$_GPC;
		$id=intval($_GPC['id']);
		pdo_delete("ewei_shop_reseller",array("id"=>$id));
		plog('log.del', "删除代理等级 ID: {$id}");
		show_json(1, array('url' => referer()));
	}

	function reslleveldel(){
		global $_W,$_GPC;
		$id=intval($_GPC['id']);
		pdo_delete("ewei_shop_resellerlevel",array("id"=>$id));
		plog('log.del', "删除代理关系 ID: {$id}");
		show_json(1, array('url' => referer()));
	}

	function reslcom(){
		global $_W,$_GPC;
		$resellerlist=pdo_fetchall("select * from " . tablename('ewei_shop_reseller'));
		foreach($resellerlist as $row){
			$t[$row['id']]=$row['name'];
		}
		$resellerlevelList=pdo_fetchall("select * from " . tablename('ewei_shop_resellerlevel'));
		$accountList=pdo_fetchall("select acid,uniacid,name from " . tablename('account_wechats')." WHERE uniacid!=:uniacid",array(":uniacid"=>DIS_ACCOUNT));
		foreach ($accountList as $key => $value) {
			$a[$value['uniacid']]=$value['name'];
		}
		foreach ($resellerlevelList as &$row) {
			$row['Accountsid']=$a[$row['Accountsid']];
			$row['resellerid']=$t[$row['resellerid']];
			if($row['ifpayment']==0){
				$row['ifpayment']="公众号";
			}else{
				$row['ifpayment']="平台";
			}
			if($row['secondpay']==1){
				$row['secondpay']="是";
			}else{
				$row['secondpay']="否";
			}
			if($row['autoretainage']==1){
				$row['autoretainage']="是";
			}else{
				$row['autoretainage']="否";
			}
		}
		unset($row);
		include $this->template();
	}
	function reslcomadd(){
		global $_W,$_GPC;
		$globalaction="add";
		$resellerlist=pdo_fetchall("select * from " . tablename('ewei_shop_reseller'));
		$accountList=pdo_fetchall("select acid,uniacid,name from " . tablename('account_wechats')." WHERE uniacid!=:uniacid",array(":uniacid"=>DIS_ACCOUNT));
		$resellerlevelList=pdo_fetchall("select * from " . tablename('ewei_shop_resellerlevel'));
		foreach ($resellerlevelList as $key => $value) {
			$t[]=$value['Accountsid'];
		}
		foreach ($accountList as $key=>$val) {
			if(in_array($val['uniacid'], $t)){
				unset($accountList[$key]);
			}
		}

		if($_W['ispost']){
			$data=array();

			$data['Accountsid']=intval($_GPC['account']);//代理的公众号ID
			if($data['Accountsid']==0){
				show_json(0,array('message' => "公众号未找到"));
			}
			$data['resellerid']=intval($_GPC['resellerid']);//代理级别
			if($data['resellerid']==0){
				show_json(0,array('message' => "代理级别未设置"));
			}
			$data['ifpayment']=intval($_GPC['ifpayment']);//收款方式0公众号收款1主账号收款
			$data['distcode']=trim($_GPC['distcode']);//订单唯一标识
			$data['secondpay']=intval($_GPC['secondpay']);//是否需要二次支付
			$data['openid']=trim($_GPC['openid']);//二次支付的公众号
			$data['autoretainage']=intval($_GPC['autoretainage']);//二次支付是否自动结算
			$data['secondpaytype']=intval($_GPC['secondpaytype']);//二次支付是否自动结算
			$re=pdo_insert("ewei_shop_resellerlevel",$data);
			if($re){
				$id=pdo_insertid();
				plog('log.add', "新曾代理关系ID{$id},{$data['name']}");
			    show_json(1,array('url'=>webUrl('disseting/reslcom'),'message' => "添加成功"));
			}
		}
		include $this->template("disseting/resecomadd");
	}
	function reslcomedit(){
		global $_W,$_GPC;
		$globalaction="edit";
		$id=$_GPC['id'];
		$resellerlist=pdo_fetchall("select * from " . tablename('ewei_shop_reseller'));
		$accountList=pdo_fetchall("select acid,uniacid,name from " . tablename('account_wechats')." WHERE uniacid!=:uniacid",array(":uniacid"=>DIS_ACCOUNT));
		$resellerlevelrow=pdo_fetch("select * from " . tablename('ewei_shop_resellerlevel')." where id=:id",array(":id"=>$id));
		if($_W['ispost']){
			$data=array();
			$data['Accountsid']=intval($_GPC['account']);//代理的公众号ID
			$data['resellerid']=intval($_GPC['resellerid']);//代理级别
			$data['ifpayment']=intval($_GPC['ifpayment']);//收款方式0公众号收款1主账号收款
			$data['distcode']=trim($_GPC['distcode']);//订单唯一标识
			$data['secondpay']=intval($_GPC['secondpay']);//是否需要二次支付
			$data['openid']=trim($_GPC['openid']);//二次支付的公众号
			$data['autoretainage']=intval($_GPC['autoretainage']);//二次支付是否自动结算
			$data['secondpaytype']=intval($_GPC['secondpaytype']);//二次支付是否自动结算方式
			$re=pdo_update("ewei_shop_resellerlevel",$data,array("id"=>$id));
			if($re){
				plog('log.add', "修改代理关系ID{$id},{$data['name']}");
			    show_json(1,array('url'=>webUrl('disseting/reslcom'),'message' => "修改成功"));
			}
		}
		include $this->template("disseting/resecomadd");
	}
}