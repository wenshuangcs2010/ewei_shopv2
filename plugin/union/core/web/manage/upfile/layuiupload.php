<?php

require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';

class Layuiupload_EweiShopV2Page extends UnionWebPage
{
    function main(){
        header('Content-Type:application/json; charset=utf-8');
        global $_W;
        global $_GPC;
        $return=array(
            'code'=>200,
            'msg'=>'上传失败',
            'data'=>''
        );

        $file = $_FILES["file"];
        $ext = pathinfo($file["name"], 4);
        $year=Date("Y")."/";
        $m=Date("m")."/";
        $ext = strtolower($ext);
        $filename =  $year.$m.md5($file['name']). '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

        if(file_exists(ATTACHMENT_ROOT . '/union/'.$_W['uniacid'].'/'. $_W["unionid"] .'/'. $filename)){
            $return['code']=0;
            $return['data']['src']=tomedia( '/union/'.$_W['uniacid'] .'/'. $_W["unionid"] .'/'. $filename);
            $return['data']['style']="display: inline-block;height: auto;max-width: 100%;";
            $return['data']['title']=$filename;
            die(json_encode($return));
        }

        if($file['size']<=0){

            $return['msg']='文件上传失败';
            die(json_encode($return));
        }

        $path=dirname(ATTACHMENT_ROOT . 'union/' .$_W['uniacid'].'/'. $filename);
        load()->func('file');
        mkdirs($path);
        // 文件上传处理
        if (!file_move($file['tmp_name'],ATTACHMENT_ROOT . 'union/'.$_W['uniacid'].'/'. $_W["unionid"] .'/' . $filename)) {
            $return['msg']='文件上传失败';
            die(json_encode($return));
        }
        $return['code']=0;
        $return['msg']='文件上传成功';
        $return['data']['src']=tomedia('/union/'.$_W['uniacid'] .'/'. $_W["unionid"] .'/'. $filename);
        $return['data']['style']="display: inline-block;height: auto;max-width: 100%;";
        $return['data']['title']=$filename;
        die(json_encode($return));


    }
}