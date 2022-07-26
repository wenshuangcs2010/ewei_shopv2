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


        $category_id=intval($_GPC['id']);
        if(empty($category_id)){
            $this->message("分类数据错误");
        }

        //查询当前分类下还有没有下级分类
        $params=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':id'=>$category_id);
        $category=pdo_fetch("select * from ".tablename("ewei_shop_union_document_category")." where id=:id and uniacid =:uniacid and union_id=:union_id and enable=1",$params);
        $_W['union']['title']=$category['catename'];
        if($category){
            $categorylist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_document_category') . ' WHERE 1 and parent_id=:id  and union_id=:union_id and uniacid=:uniacid  ORDER BY displayorder desc, id DESC  ', $params,"id");
        }
        $indexid=intval($_GPC['indexid']);
        if($indexid>0){
            $uniontitle=pdo_fetchcolumn("select title from ".tablename("ewei_shop_union_menu")." where id=:indexid",array(":indexid"=>$indexid));
            $_W['union']['title']=$uniontitle;
        }
        $_W['shopshare'] = array(
            'title' =>$category['catename'],
            'imgUrl' => !empty($category['images'])? tomedia($category['images']) :tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $category['catename'],
            'link' => mobileUrl('union/document',array('id'=>$category_id),true)
        );
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
            'category_id'=>intval($_GPC['category_id'])
        );


        $list= $this->model->get_document_list($args);

        foreach ($list['list'] as &$value){
            if(empty($value['link'])){
                $value['link']=mobileUrl('union/document/view',array('id'=>$value['id']));;
            }
        }
        unset($value);
        show_json(1,$list);
    }

    public function view(){
        global $_W;
        global $_GPC;
        $id=intval($_GPC['id']);
        $info=$this->model->get_document_info($id);
        $article=$info['info'];
        $_W['union']['title']="公文详情";

        if(empty($article)){
            $this->message("当前文章您没有查看权限,或者文章不存在", mobileUrl('union',null,true),"error");
        }
        if(isset($article['peoplevale']) && !empty($article['peoplevale'])){
            $memberlist=explode(",",$article['peoplevale']);

            if(!in_array($this->member['id'],$memberlist)){
                $this->message("当前文章您没有查看权限", mobileUrl('union',null,true),"error");
            }
        }



        if($article){
            pdo_update("ewei_shop_union_document",array("read_count"=>$article['read_count']+1),array("id"=>$article['id']));
        }
        $this->model->readmember_insert($_W['openid'],1,$article['id']);
        $readmember= $this->model->readcount(1,$article['peoplevale'],$article['id']);
        $article['description']=p("article")->mid_replace(htmlspecialchars_decode($article['description']));


        if(isset($article['peoplevale']) && !empty($article['peoplevale'])){
            $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and id in(".$article['peoplevale'].") and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        }else{
            $allcount=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and status=1 and activate=1 ",array(':uniacid'=>$_W['uniacid'],":union_id"=>$_W['unionid']));

        }

        $notreadcount=$allcount-$readmember['count'];

        $_W['shopshare'] = array(
            'title' =>$article['title'],
            'imgUrl' => !empty($article['header_image'])? tomedia($article['header_image']) :tomedia("addons/ewei_shopv2/plugin/union/static/images/desc.jpg"),
            'desc' => $article['title'],
            'link' => mobileUrl('union/document/view',array('id'=>$article['id']),true)
        );


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
            $enclosure_url=tomedia($enclosure_urllist[0]);
        }

        header("Location: ".$enclosure_url);
        exit;
    }




}