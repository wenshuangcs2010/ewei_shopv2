<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
define('QRCODE_ZIP_PATH', EWEI_SHOPV2_DATA .'temp/');
class Down_EweiShopV2Page extends WebPage {
	function main() {

	}

	function qrcode(){
		 global $_W, $_GPC;
		 $goodsid=$_GPC['id'];
		 $condition.=' and dm.status=1 ';
		 $sql = "select id,nickname,realname from " . tablename('ewei_shop_member')." where uniacid={$_W['uniacid']} and isagent =1 and status=1";
        $list = pdo_fetchall($sql);
		include $this->template();
	}
	function downqrcode(){
		 global $_W, $_GPC;
		 $goodsid=$_GPC['goods_id'];
		 $mids=$_GPC['cates'];
		 if(empty($mids)){
		 	show_json(0,"没有选择分销商");
		 }
		 $member=array("openid"=>"123456789",'realname'=>"总店");
		 $newfilepath=QRCODE_ZIP_PATH.$_W['uniacid']."/";//新文件地址
		 $this->deletefile($newfilepath);
		 foreach ($mids as $key => $value) {
		 	if($value!=0){
		 		$member=$this->getMember($value);
		 		$openid=$member['openid'];
		 	}
		 	
			$qr = pdo_fetch('select * from ' . tablename('ewei_shop_poster_qr') . ' where openid=:openid and acid=:acid and type=:type and goodsid=:goodsid limit 1', array(':openid' => $member['openid'], ':acid' => $_W['uniacid'], ':type' => 3, ':goodsid' => $goodsid));
			 	 if (empty($qr)) {
			 	 	$qrimg = m('qrcode')->createGoodsQrcode($value, $goodsid, $_W['uniacid']);
					$qr = array(
						'acid' => $_W['uniacid'],
						'openid' => $member['openid'],
						'type' => 3,
						'goodsid' => $goodsid,
						'qrimg' => $qrimg
					);
					pdo_insert('ewei_shop_poster_qr', $qr);
					$qr['id'] = pdo_insertid();
				}else{
					$qrimg=$qr['qrimg'];
				}
		 	$qr['current_qrimg'] = $qrimg;

		 	$filepath=EWEI_SHOPV2_DATA.'/qrcode/'.$_W['uniacid'].'/'.basename($qrimg);//原图片地址
			$filename=$goodsid.'_';
			$filename.=empty($member['realname']) ?$member['nickname'] :  $member['realname'];
			$filename.=".png";
			$filename=iconv("UTF-8","gb2312",$filename);
			
			$newfilename=$newfilepath.$filename;
			$this->makdirfile(QRCODE_ZIP_PATH);

			$this->copyimgfile($newfilepath,$filepath,$newfilename);

		 }
		 $this->out_put_zip($newfilepath);


		 show_json(0,"11111");
	}

	function getMember($mid){
		global $_W;
		return pdo_fetch("SELECT openid,realname,nickname from ".tablename("ewei_shop_member")." where id={$mid} and uniacid={$_W['uniacid']} ");
	}

	function list_dir($dir){
    	$result = array();
    	if (is_dir($dir)){
    		$file_dir = scandir($dir);
    		foreach($file_dir as $file){
    			if ($file == '.' || $file == '..'){
    				continue;
    			}
    			elseif (is_dir($dir.$file)){
    				$result = array_merge($result, list_dir($dir.$file.'/'));
    			}
    			else{
    				array_push($result, $dir.$file);
    			}
    		}
    	}
    	return $result;
    }
    function deletefile($path){
    	$datalist=$this->list_dir($path);
    	foreach($datalist as $val){
    		unlink($val);
    	}
    }
    function out_put_zip($path){
    	$datalist=$this->list_dir($path);
    	$filename=$path."gooods_qrcode.zip";
		
			$zip = new ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {   
        		exit('无法打开文件，或者文件创建失败');
    		}
	    	foreach($datalist as $val){   
		        if(file_exists($val)){   
		            $zip->addFile( $val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下   
		        }   
	    	}   
    		$zip->close();//关闭 
		
		header("Cache-Control: public"); 
		header("Content-Description: File Transfer"); 
		header('Content-disposition: attachment; filename='.basename($filename)); //文件名   
		header("Content-Type: application/zip"); //zip格式的   
		header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件    
		header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小   
		@readfile($filename);
    }

	/**
	 *  将生成的二维码拷贝到临时文件夹中
	 * @return [type] [description]
	 */
	function copyimgfile($path,$file,$newfile){
		$this->makdirfile($path);
		if (file_exists($file) == false)
		{
		   die ('文件不在,无法复制');
		}
		$result = copy($file, $newfile);
	}

	function makdirfile($path){
		if(!file_exists($path)){
			mkdir($path);
		}
	}
}

