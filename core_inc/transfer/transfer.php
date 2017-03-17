<?php


class Transfer{
	public static function getPayment($paycode){
      require $paycode.'.inc.php';
      $classname=ucfirst($paycode);
      return new $classname($config);
    }
}