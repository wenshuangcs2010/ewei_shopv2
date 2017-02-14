<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Show_EweiShopV2Page extends MobileLoginPage {

    public function main(){
        include $this->template("sale/coupon/my/showcoupons");
    }

    public function main2(){
        include $this->template("sale/coupon/my/showcoupons2");
    }


}
