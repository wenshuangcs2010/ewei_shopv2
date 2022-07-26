<?php

/*
 * 人人商城V2
 *
 * @author ewei 狸小狐 QQ:22185157
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Importgoods_EweiShopV2Page extends WebPage
{
    function main(){
        global $_W, $_GPC;

        if ($_W['ispost'])
        {
            $rows = m('excel')->importall('excelfile');
            $message='';
            pdo_begin();
            foreach ($rows as $rownum => $col )
            {
                if($rownum==0){
                    continue;
                }
                $title=trim($col[0]);
                $goodssn=trim($col[1]);
                $uniontitle=trim($col[2]);
                $depotid=trim($col[3]);
                $pron_price=trim($col[4]);
                $markprice=trim($col[5]);
                $total=trim($col[6]);
                $goods=pdo_fetch("select id from ".tablename("ewei_shop_goods")." where goodssn=:goodssn and uniacid=:uniacid ",array(':goodssn'=>$goodssn,":uniacid"=>$_W['uniacid']) );
                if(!empty($goods)){
                    $message.="商品:".$title."已存在"."goodsn:".$goodssn;
                    continue;
                }
                $data=array(
                    'title'=>$title,
                    'goodssn'=>$goodssn,
                    'depotid'=>$depotid,
                    'marketprice'=>$markprice,
                    'productprice'=>$pron_price,
                    'total'=>$total,
                    'uniacid'=>$_W['uniacid'],
                    'status'=>0,
                    'unit'=>$uniontitle,
                    'createtime'=>time(),
                );
                pdo_insert('ewei_shop_goods',$data);
            }
            pdo_commit();
            $this->message("导入数据成功",  webUrl("goods.importgoods"), '');
        }
        include $this->template();
    }
}