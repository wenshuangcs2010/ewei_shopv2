<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Index_EweiShopV2Page extends LyMobilePage
{
    public function main(){
        global $_W;
        global $_GPC;
        //获取幻灯片

        $sql="select * from ".tablename("ewei_shop_union_ly_adv")." where 1 and enabled=1  order by displayorder desc";

        $advlist = pdo_fetchall($sql);

        //获取广告

        //获取部分 疗养点
        $sql="select id,title,address,mobilephone,header_image,is_hasget,is_dscount,is_hot,evaluate from ".tablename("ewei_shop_union_ly_lyaddress").'order by createtime desc LIMIT 0,5';
        $ly_addresslist=pdo_fetchall($sql);


        //获取部分 精品线路

        $sql="select id,title,mobilephone,header_image,price,oldprice,unitname,evaluate from ".tablename("ewei_shop_union_ly_addressline").'order by createtime desc LIMIT 0,5';
        $ly_address_onlelist=pdo_fetchall($sql);


        include $this->template();
    }
    public  function xiujia(){
        $this->message("等待更新数据",'',"error");
    }
    public  function policy(){
        include $this->template();
    }
    public  function policyview1(){
        $enclosure_url="../addons/ewei_shopv2/plugin/union/template/mobile/default/lyhome/file/1_2020年职工疗休养新政解读.doc";
        header("Location: ".$enclosure_url);
    }
    public  function policyview2(){
        $enclosure_url="../addons/ewei_shopv2/plugin/union/template/mobile/default/lyhome/file/1_tpyrced_关于调整职工疗休养政策的通知.pdf";
         header("Location: ".$enclosure_url);
        exit;
    }

    public  function downloadfile(){
        $enclosure_url="../addons/ewei_shopv2/plugin/union/template/mobile/default/lyhome/file/jiesx.docx";
        header("Location: ".$enclosure_url);
        exit;
    }
}