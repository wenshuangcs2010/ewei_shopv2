<?php


if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Document_EweiShopV2Page extends UnionMobilePage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        $_W['union']['title']="公文查询";
        include $this->template();
    }

    public function get_document_list(){
        global $_W;
        global $_GPC;

        $args = array(
            'pagesize' => 10,
            'page' => intval($_GPC['page']),
            'order' => trim($_GPC['order']),
            'keywords'=>trim($_GPC['keywords']),
            'by' => trim($_GPC['by']),
        );

        $list= $this->model->get_document_list($args);
        show_json(1,$list);
    }

    public function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $info=$this->model->get_document_info($id);
        $article=$info['info'];
        $_W['union']['title']="公文详情";

        if($article){
            pdo_update("ewei_shop_union_document",array("read_count"=>$article['read_count']+1),array("id"=>$article['id']));
        }
        $this->model->readmember_insert($_W['openid'],1);
        $readmember= $this->model->readcount(1);
        $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        $notreadcount=$allcount-$readmember['count'];
        include $this->template();
    }
    public function down(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $info=$this->model->get_document_info($id);
        $article=$info['info'];
        $enclosure_urllist=explode('|',$article['enclosure_url']);

        if(count($enclosure_urllist)>1){
            //压缩下载
            $md5filename="";
            foreach ($enclosure_urllist as &$value){
                $value_temp= explode("attachment",$value);
                $value=ATTACHMENT_ROOT.$value_temp[1];
                if(is_file($value)){
                    $md5filename.=md5_file($value);
                }
            }
            unset($value);
            $md5filename=md5($md5filename);
            $filename= $this->model->down_phpzip($enclosure_urllist,$md5filename);
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename='.basename($filename)); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小
            @readfile($filename);

        }else{
            $enclosure_url=$enclosure_urllist[0];

        }
        header("Location: ".$enclosure_url);
        exit;
    }




}