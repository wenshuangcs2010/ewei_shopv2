<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Scan_EweiShopV2Page extends PluginWebPage {

	function main() {

		global $_W, $_GPC;

		$id=$_GPC['memberid'];
		$sql="SELECT * FROM ".tablename("ewei_shop_member_group")." where uniacid=:uniacid";
		$grouplist=pdo_fetchall($sql,array(":uniacid"=>$_W['uniacid']));
		
		include $this->template();
	}
	function update(){
		global $_W, $_GPC;
		$uniaccount = WeAccount::create($acid);
		$composterid=$_GPC['composterid'];
		$sql="SELECT *  from ".tablename("ewei_shop_composteruser")." where id=:id";
		$item=pdo_fetch($sql,array(":id"=>$composterid));
		
		$groupid=$_GPC['groupid'];
		$member=m("member")->getMember($item['openid']);
		$type=$_GPC['qrtype'];
		$data=array(
			'uniacid'=>$_W['uniacid'],
			'createtimes'=>time(),
			'qrtype'=>$type,
			'groupid'=>$groupid,
			'scentid'=>'',
			'ticket'=>'',
			'qrimg'=>'',
			'openid'=>$member['openid'],
			'composterid'=>$composterid,
			);
		//检查当期分组是否生成过二维码
		$sql="SELECT * from ".tablename("ewei_shop_composter_qr")." where openid=:openid and groupid=:groupid and uniacid=:uniacid";
		$comqr=pdo_fetch($sql,array(":openid"=>$member['openid'],":groupid"=>$groupid,":uniacid"=>$_W['uniacid']));
		if(!empty($comqr)){
			show_json(1,"二维码已经生成请勿重复");
		}else{
			$ticket=$this->model->getFixedTicket($composterid,$member,$uniaccount,$groupid,$type);

			$data['expire_seconds']=$ticket['barcode']['expire_seconds'];
			$data['action_name']=$ticket['barcode']['action_name'];
			$ims_qrcode = array(
					'uniacid' => $_W['uniacid'],
					'acid' => $_W['acid'],
					//'qrcid' => $barcode['action_info']['scene']['scene_id'],
					'scene_str' => "",
					//'type'=>'scene',
					"model" => 2,
					"name" => "EWEI_SHOPV2_COMPOSTER_QRCODE",
					"keyword" => 'EWEI_SHOPV2_COMPOSTER',
					"expire" => 0,
					"createtime" => time(),
					"status" => 1,
					'url' => $ticket['url'],
					"ticket" => $ticket['ticket']
				);
			if($type==0){
				$data['scentid']=$ticket['barcode']['action_info']['scene']['scene_str'];
				$ims_qrcode['scene_str']=$data['scentid'];
			}else{
				$data['scentid']=$ticket['barcode']['action_info']['scene']['scene_id'];
				$ims_qrcode['expire']=$data['expire_seconds'];
				$ims_qrcode['qrcid']=$data['scentid'];
				$ims_qrcode['model']=1;
			}
			$data['ticket']=$ticket['ticket'];
			if(empty($data['ticket'])){
				//var_dump($ticket);
				show_json(0,"远程链接失败");
			}
			pdo_insert('qrcode', $ims_qrcode);
			
			$ret=pdo_insert("ewei_shop_composter_qr",$data);
			if($ret){
				show_json(1,"添加成功");
			}
			show_json(0,"失败");	
		}
	}
}
