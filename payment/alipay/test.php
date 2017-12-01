<?php
/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */

error_reporting(1);
require dirname(__FILE__).'/../../../../framework/bootstrap.inc.php';
//echo dirname(__FILE__).'/../../../../framework/bootstrap.inc.php'
class Test
{
    public $post;

    public function __construct()
    {
        global $_W;
        $this->post = $_POST;
       	echo "SUCCESS";
        WeUtility::logging('alypay_log',var_export($this->post,true));

    }
}
new Test();