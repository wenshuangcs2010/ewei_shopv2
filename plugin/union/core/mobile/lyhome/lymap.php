<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_ly.php';
class Lymap_EweiShopV2Page extends LyMobilePage
{

    function main(){
        global $_W;
        global $_GPC;
        $title=$_GPC['title'];
         $lng=$_GPC['lng'];
         $lat=$_GPC['lat'];
         $address=$_GPC['address'];

            $address=array(
                'storename'=>$title,
                'lng'=>$lng,
                'lat'=>$lat,
                'address'=>$address
            );

        include $this->template("union/lyhome/map");
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