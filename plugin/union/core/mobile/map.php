<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Map_EweiShopV2Page extends UnionMobilePage
{

    function main(){
        global $_W;
        global $_GPC;

        $id=intval($_GPC['id']);


        $view=pdo_fetch("select * from ".tablename("ewei_shop_union_memberactivity")." where id=:id and union_id=:union_id",array(":id"=>$id,":union_id"=>$_W['unionid']));
        if($view['lng']){
            //$re=$this->coordinate_switchf($view['lat'],$view['lng']);

            $address=array(
                'storename'=>$view['title'],
                'lng'=>$view['lng'],
                'lat'=>$view['lat'],
                'address'=>$view['address']
            );

        }



        include $this->template();
    }

    private function coordinate_switchf($a,$b){//腾讯转百度坐标转换  $a = Latitude , $b = Longitude


        $x = (double)$b ;
        $y = (double)$a;
        $x_pi = 3.14159265358979324;
        $z = sqrt($x * $x+$y * $y) + 0.00002 * sin($y * $x_pi);

        $theta = atan2($y,$x) + 0.000003 * cos($x*$x_pi);

        $gb = number_format($z * cos($theta) + 0.0065,6);
        $ga = number_format($z * sin($theta) + 0.006,6);


        return ['Latitude'=>$ga,'Longitude'=>$gb];

    }
}