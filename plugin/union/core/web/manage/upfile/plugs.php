<?php

/**
 * 插件助手控制器
 * Class Plugs
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/21
 */
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Plugs_EweiShopV2Page extends UnionWebPage
{

    /**
     * 默认检查用户登录状态
     * @var bool
     */
    public $checkLogin = false;
    public $savefileextlist=array("jpg","png","jpeg","gif",'bmp','ico');
    /**
     * 默认检查节点访问权限
     * @var bool
     */
    public $checkAuth = false;

    /**
     * 文件上传
     * @return
     */
    public function upfile()
    {
        global $_W;
        global $_GPC;
        $uptype=trim($_GPC['uptype']);

        if (!in_array(($uptype), ['local', 'qiniu', 'oss'])) {
            $uptype = "local";
        }
        $nvs=false;
        $types =empty($_GPC['type']) ? 'jpg,png':trim($_GPC['type']);

        //处理是否显示图片库
        $typeslist=explode(",",$types);
        $typeslist= array_map("strtolower",$typeslist);

        $ar=array_intersect($typeslist,$this->savefileextlist);
        if($ar){
            $nvs=true;
            $year=date("Y");


        }

        $mode =empty($_GPC['mode']) ? 'one':trim($_GPC['mode']);

        $mimes= $this->model->getFileMine($types);

        $field=empty($_GPC['field']) ? 'file':trim($_GPC['field']);
        include $this->template("upfile/plugs.upfile");
    }

    public function getimagelist(){
        global $_W;
        global $_GPC;

        $condition = ' WHERE uniacid = :uniacid  and type=:type and union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'], ':type' =>1,":union_id"=>$_W['unionid']);
        $year = intval($_GPC['year']);
        $month = intval($_GPC['month']);
        if($year > 0 || $month > 0) {
            if($month > 0 && !$year) {
                $year = date('Y');
                $starttime = strtotime("{$year}-{$month}-01");
                $endtime = strtotime("+1 month", $starttime);
            } elseif($year > 0 && !$month) {
                $starttime = strtotime("{$year}-01-01");
                $endtime = strtotime("+1 year", $starttime);
            } elseif($year > 0 && $month > 0) {
                $year = date('Y');
                $starttime = strtotime("{$year}-{$month}-01");
                $endtime = strtotime("+1 month", $starttime);
            }
            $condition .= ' AND createtime >= :starttime AND createtime <= :endtime';
            $params[':starttime'] = $starttime;
            $params[':endtime'] = $endtime;
        }
        $page = intval($_GPC['page']);
        $page = max(1, $page);
        $size = $_GPC['pagesize'] ? intval($_GPC['pagesize']) : 32;
        $sql = 'SELECT * FROM '.tablename('ewei_shop_union_attachment')." {$condition} ORDER BY id DESC LIMIT ".(($page-1)*$size).','.$size;
        $list = pdo_fetchall($sql, $params, 'id');

        foreach ($list as &$item) {
            $item['url'] = tomedia($item['imgurl']);
            $item['createtime'] = date('Y-m-d', $item['createtime']);

        }
        $total = pdo_fetchcolumn('SELECT count(*) FROM '.tablename('ewei_shop_union_attachment') ." {$condition}", $params);
        message(array('page'=> pagination($total, $page, $size, '', array('before' => '2', 'after' => '2', 'ajaxcallback'=>'null')), 'items' => $list), '', 'ajax');
    }

    public function addimgdatabaselist($filename,$path,$ext){
        global $_W;
        if(in_array($ext,$this->savefileextlist)){
            $type=1;
        }
        $data=array(
            'createtime'=>TIMESTAMP,
            'imgurl'=>$path,
            'union_id'=>$_W['unionid'],
            'uniacid'=>$_W['uniacid'],
            'type'=>$type,
            'filename'=>$filename,
        );
        pdo_insert("ewei_shop_union_attachment",$data);
    }
    /**
     * 通用文件上传
     * @return \think\response\Json
     */
    public function upload()
    {
        global $_W;
        global $_GPC;
        $file = $_FILES["file"];
        $token=trim($_GPC['token']);
        $md5s = str_split($_GPC['md5'], 16);
        $ext = pathinfo($file["name"], 4);
        $year=Date("Y")."/";
        $m=Date("m")."/";
        $ext = strtolower($ext);

        if($file['size']<=0){
            $this->model->json(['code' => 'ERROR', '文件上传失败']);
        }
        $filename = $year.$m.$md5s[0] . ".{$ext}";
        // 文件上传Token验证
        if ($token !== md5($filename . session_id())) {
            $this->model->json(['code' => 'ERROR', '文件上传验证失败']);
        }
        load()->func('file');
        $path=dirname(ATTACHMENT_ROOT . 'union/' .$_W['uniacid'].'/'. $filename);
        mkdirs($path);
        // 文件上传处理
        if (!file_move($file['tmp_name'],ATTACHMENT_ROOT . 'union/'.$_W['uniacid'].'/' . $filename)) {
            $this->model->json(['code' => 'ERROR', '保存上传文件失败']);
        }

        $this->addimgdatabaselist($md5s[0].".".$ext,'union/'.$_W['uniacid'].'/' . $filename,$ext);

        $result['success'] = true;
        $site_url=$this->model->getBaseUriLocal().$filename;
        $this->model->json(['data' => ['site_url' => $site_url], 'code' => 'SUCCESS', 'msg' => '文件上传成功']);
    }

    /**
     * 文件状态检查
     */
    public function upstate()
    {
        global $_W;
        global $_GPC;
        $post = $_GPC;
        $md5s=str_split($post['md5'], 16);
        $year=Date("Y")."/";
        $m=Date("m")."/";
      
        $filename =  $year.$m.$md5s[0]. '.' . pathinfo($post['filename'], PATHINFO_EXTENSION);

        // 检查文件是否已上传
        $site_url = $this->model->getFileUrl($filename);
        //处理文件上传路径问题
        if ($site_url) {
        //处理文件上传路径问题
            if(strtolower($post['uptype'])=="local"){
               $site= parse_url($site_url);
               $site_url=$site['path'];
            }
            $this->model->result(['site_url' => "$site_url"], 'IS_FOUND');
        }
        
        // 需要上传文件，生成上传配置参数
        $config = ['uptype' => $post['uptype'], 'file_url' => $filename];
        switch (strtolower($post['uptype'])) {
//            case 'qiniu':
//                $config['server'] = FileService::getUploadQiniuUrl(true);
//                $config['token'] = $this->_getQiniuToken($filename);
//                break;
            case 'local':
                $config['server'] = $this->model->getUploadLocalUrl();
                $config['token'] = md5($filename . session_id());
                break;
//            case 'oss':
//                $time = time() + 3600;
//                $policyText = [
//                    'expiration' => date('Y-m-d', $time) . 'T' . date('H:i:s', $time) . '.000Z',
//                    'conditions' => [['content-length-range', 0, 1048576000]],
//                ];
//                $config['policy'] = base64_encode(json_encode($policyText));
//                $config['server'] = FileService::getUploadOssUrl();
//                $config['site_url'] = FileService::getBaseUriOss() . $filename;
//                $config['signature'] = base64_encode(hash_hmac('sha1', $config['policy'], sysconf('storage_oss_secret'), true));
//                $config['OSSAccessKeyId'] = sysconf('storage_oss_keyid');
        }
        $this->model->result($config, 'NOT_FOUND');
    }

    /**
     * 生成七牛文件上传Token
     * @param string $key
     * @return string
     */
    protected function _getQiniuToken($key)
    {
        $accessKey = sysconf('storage_qiniu_access_key');
        $secretKey = sysconf('storage_qiniu_secret_key');
        $bucket = sysconf('storage_qiniu_bucket');
        $host = sysconf('storage_qiniu_domain');
        $protocol = sysconf('storage_qiniu_is_https') ? 'https' : 'http';
        $params = [
            "scope"      => "{$bucket}:{$key}",
            "deadline"   => 3600 + time(),
            "returnBody" => "{\"data\":{\"site_url\":\"{$protocol}://{$host}/$(key)\",\"file_url\":\"$(key)\"}, \"code\": \"SUCCESS\"}",
        ];
        $data = str_replace(['+', '/'], ['-', '_'], base64_encode(json_encode($params)));
        return $accessKey . ':' . str_replace(['+', '/'], ['-', '_'], base64_encode(hash_hmac('sha1', $data, $secretKey, true))) . ':' . $data;
    }

    /**
     * 字体图标选择器
     * @return \think\response\View
     */
    public function icon()
    {
        $field = $this->request->get('field', 'icon');
        return view('', ['field' => $field]);
    }

    /**
     * 区域数据
     * @return \think\response\Json
     */
    public function region()
    {
        return json(Db::name('DataRegion')->where('status', '1')->column('code,name'));
    }

}
