<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Uploader_EweiShopV2Page extends MobilePage
{

    function main()
    {
        global $_W, $_GPC;
        load()->func('file');
        $field = $_GPC['file'];


        if (!empty($_FILES[$field]['name'])) {

            if (is_array($_FILES[$field]['name'])) {
                $files = array();

                //多图
                foreach ($_FILES[$field]['name'] as $key => $name) {

                    $file = array(
                        'name' => $name,
                        'type' => $_FILES[$field]['type'][$key],
                        'tmp_name' => $_FILES[$field]['tmp_name'][$key],
                        'error' => $_FILES[$field]['error'][$key],
                        'size' => $_FILES[$field]['size'][$key],
                    );

                    $files[] = $this->upload($file);

                }
                $ret = array(
                    'status'=>'success',
                    'files'=>$files
                 );
                exit(json_encode($ret));

            } else {
                //单图
                $result  = $this->upload($_FILES[$field]);
                exit(json_encode($result));
            }

        } else {
            $result['message'] = '请选择要上传的图片！';
            exit(json_encode($result));
        }
    }

    protected function upload($uploadfile)
    {
        global $_W, $_GPC;
        $result['status'] = 'error';

        if ($uploadfile['error'] != 0) {
            $result['message'] = '上传失败，请重试！';
            return $result;
        }

        load()->func('file');

        $path = '/images/ewei_shop/' . $_W['uniacid'];
        if (!is_dir(ATTACHMENT_ROOT . $path)) {
            mkdirs(ATTACHMENT_ROOT . $path);
        }
        $_W['uploadsetting'] = array();
        $_W['uploadsetting']['image']['folder'] = $path;
        $_W['uploadsetting']['image']['extentions'] = $_W['config']['upload']['image']['extentions'];
        $_W['uploadsetting']['image']['limit'] = $_W['config']['upload']['image']['limit'];
        $file = file_upload($uploadfile, 'image');
        if (is_error($file)) {
            $result['message'] = $file['message'];
            return $result;
        }

        //判断远程
        if (function_exists('file_remote_upload')) {
            $remote = file_remote_upload($file['path']);
            if (is_error($remote)) {
                $result['message'] = $remote['message'];
                return $result;
            }
        }

        $result['status'] = "success";
        $result['url'] = $file['url'];
        $result['error'] = 0;
        $result['filename'] = $file['path'];
        $result['url'] = trim($_W['attachurl'] . $result['filename']);
        pdo_insert('core_attachment', array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'filename' => $uploadfile['name'],
            'attachment' => $result['filename'],
            'type' => 1,
            'createtime' => TIMESTAMP,
        ));
        return $result;

    }

    function remove()
    {
        global $_W, $_GPC;
        load()->func('file');
        $file = $_GPC['file'];
        file_delete($file);
        exit(json_encode(array('status' => 'success')));
    }

}
