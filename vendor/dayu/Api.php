<?php
    include "TopSdk.php";
    date_default_timezone_set('Asia/Shanghai'); 

    $appkey = "23401710";
    $secret = "ba1c3170481cbdd84bac8b5b0af879dd";

    $c = new TopClient;
	$c->appkey = $appkey;
	$c->secretKey = $secret;
	$req = new AlibabaAliqinFcSmsNumSendRequest;
	//$req->setExtend("123456");
	$req->setSmsType("normal");
	$req->setSmsFreeSignName("活动验证");
	$req->setSmsParam("{\"code\":\"1234\",\"product\":\"rrshop\"}");
	$req->setRecNum("13553073705");
	$req->setSmsTemplateCode("SMS_6756301");
	$resp = $c->execute($req);

	print_r($resp);

?>