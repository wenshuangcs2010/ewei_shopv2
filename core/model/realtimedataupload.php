<?php

/*
 * 海关数据上传接口
 *
 *
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Realtimedataupload_EweiShopV2Model
{




    //public $realTimeDataUpload_url="https://swapptest.singlewindow.cn/ceb2grab/grab/realTimeDataUpload";
    public $realTimeDataUpload_url="https://customs.chinaport.gov.cn/ceb2grab/grab/realTimeDataUpload";
    private $paramentlist=array();
    private $ebpCode='3302462546';//电商平台的海关注册登记编号
    private $initdata=array();

    public function SetParams($key,$value){
        $this->initdata[$key]=$value;
    }
    public function get_params(){
        return $this->initdata;
    }
    public function initparament(){
        $params=array(
            21=>array(
                'payCode'=>"440316T004",
                'recpAccount'=>"1363662502",
                'recpCode'=>'91330103MA27XKP78J',
                'recpName'=>'浙江畅购天下电子商务有限公司',
            ),
            22=>array(
                'payCode'=>"312226T001",
                'recpAccount'=>"nbcgtx@163.com",//支付账号
                'recpCode'=>'91330201MA282A8G8F',
                'recpName'=>'宁波畅购天下电子商务有限公司',//收款企业名称
            )
        );
        $this->paramentlist=$params;
    }

    public function get_uuid(){
            if (function_exists ( 'com_create_guid' )) {
                return com_create_guid ();
            } else {
                mt_srand ( ( double ) microtime () * 10000 ); //optional for php 4.2.0 and up.随便数播种，4.2.0以后不需要了。
                $charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) ); //根据当前时间（微秒计）生成唯一id.
                $hyphen = chr ( 45 ); // "-"
                $uuid = '' . //chr(123)// "{"
                    substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 6 ) ;//. $hyphen . substr ( $charid, 20, 12 );
                //.chr(125);// "}"
                return $uuid;
            }
    }
    public function  checkdepotid($ordersn){
        $sql="select depotid from ".tablename("ewei_shop_order")." where ordersn=:ordersn";
        $depotid=pdo_fetchcolumn($sql,array(':ordersn'=>$ordersn));
    }
    public function create_initalRequestdata($ordersn,$postdata,$paytype){
        $id=pdo_fetchcolumn("select id from ".tablename("ewei_shop_pay_request")." where order_sn=:ordersn and pay_type=:pay_type",array(":ordersn"=>$ordersn,'pay_type'=>$paytype));
        $data['pay_type']=$paytype;
        $data['order_sn']=$ordersn;
        //检查是不是需要报关的仓库
        if($paytype==22){
            $data['initalRequest']=$postdata['url'];
        }
        if($paytype==21){
            $data['initalRequest']=$postdata['url'];
        }
        if(empty($id)){
            pdo_insert("ewei_shop_pay_request",$data);
        }else{
            pdo_update("ewei_shop_pay_request",$data,array("id"=>$id));
        }
    }
    public  function init($sessionid,$ordersn,$pay_type){
        $this->initparament();
        $pay_requst=pdo_fetch("select * from ".tablename('ewei_shop_pay_request')." where order_sn=:ordersn and status=1 and pay_type=:pay_type",array(":ordersn"=>$ordersn,':pay_type'=>$pay_type));

        if(empty($pay_requst)){
            return "";
        }
        $order_info=pdo_fetch("select * from ".tablename("ewei_shop_order")." where ordersn=:ordersn",array(":ordersn"=>$ordersn));
        $sql="select og.*,g.title from ".tablename("ewei_shop_order_goods")." as og LEFT JOIN ".tablename("ewei_shop_goods")." as g ON g.id=og.goodsid where og.orderid=:orderid";

        $order_goods=pdo_fetchall($sql,array(":orderid"=>$order_info['id']));

        $this->initdata=array(
            'sessionID'=>$sessionid,
            'payExchangeInfoHead'=>$this->set_PayExchangeInfoHead($pay_requst,$order_info),
            'payExchangeInfoLists'=>$this->set_PayExchangeInfoList($order_goods,$order_info['ordersn'],$pay_type),
            'serviceTime'=>time(),
        );
        return $this->initdata;
    }
    public function repl($str){
        return str_replace([".",":","*"],'',$str);
    }
    public function set_PayExchangeInfoHead($pay_requst,$order){
        $initalResponse=$pay_requst['initalResponse'];
        $initalResponse=json_decode($initalResponse,true);
        $initalResponse=json_encode($initalResponse,320);
        //$initalResponse=str_replace(["."],'',$initalResponse);
        $initalResponse=$initalResponse;
        $pay_requst['initalRequest']=$pay_requst['initalRequest'];
        $data=array(
            'guid'=>$this->get_uuid(),
            'initalRequest'=>$pay_requst['initalRequest'],
            // 'initalRequest'=>"https://openapi.alipay.com/gateway.do?timestamp=2013-01-0108:08:08&method=alipay.trade.pay&app_id=13580&sign_type=RSA2&sign=ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE&version=1.0&charset=GBK",
            //'initalResponse'=>"OK",
            'initalResponse'=>$initalResponse,
            'ebpCode'=>$this->ebpCode,
            'payCode'=>$this->paramentlist[$pay_requst['pay_type']]['payCode'],
            'payTransactionId'=>$order['paymentno'],
            'totalAmount'=>(float)$order['price'],
            'currency'=>"142",
            'verDept'=>$pay_requst['verDept'],
            'payType'=>"1",
            'tradingTime'=>empty($order['paytime']) ? date('YmdHis'): date('YmdHis',$order['paytime']),
            //'tradingTime'=>"20190104170644",
            'note'=>'',
        );
        return $data;
    }
    public function set_PayExchangeInfoList($order_goods,$ordersn,$pay_type){
        $payExchangeInfoLists[]=array(
            'orderNo'=>$ordersn,
            'goodsInfo'=>$this->get_goodslist($order_goods),
            'recpAccount'=>$this->paramentlist[$pay_type]['recpAccount'],//收款账号，
            'recpCode'=>$this->paramentlist[$pay_type]['recpCode'],
            'recpName'=>$this->paramentlist[$pay_type]['recpName'],
        );
        return $payExchangeInfoLists;
    }
    public function get_goodslist($order_goods){
        $goods=array();
        foreach ($order_goods as $goods_tem){
            $goods[]=array(
                'gname'=>$goods_tem['title'],
                //'gname'=>"lhy-gnsku2",
                'itemLink'=>"http://wx.lylife.com.cn/app/index.php?i=5&c=entry&m=ewei_shopv2&do=mobile&r=goods.detail&id={$goods_tem['id']}",
            );
        }

        return $goods;
    }
    function replace_specialChar($strParam){
        $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        return preg_replace($regex," ",$strParam);
    }
   private function trimall($str)//删除空格
    {
        $qian=array(" ","　","　 ","\t","\n","\r");$hou=array("","","","","");
        return str_replace($qian,$hou,$str);
    }
    /**
     *返回字符串的毫秒数时间戳
     */
    private function get_total_millisecond()
    {
        $temptime = explode (" ", microtime());
        $time = $temptime [1];
        $temptime=$temptime[0] * 1000;
        $newNumber = substr(strval($temptime+1000),1,3);

        $time=$time.$newNumber;

        return $time;
    }

    public function str_split($dat){
        $json="";
        foreach ($dat as $key=>$item){
            if($key=="payExchangeInfoHead" || $key=="payExchangeInfoLists"){
                $item=json_encode($item,320);
            }

            $json.='"'.$key.'":"'.$item.'"'."||";
    }
        $json=substr($json,0,strlen($json)-2);

        return $json;
    }

}