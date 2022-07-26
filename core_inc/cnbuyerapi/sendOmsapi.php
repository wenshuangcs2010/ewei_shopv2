<?php

require_once("CryptAES.inc.php");
class SendOmsapi{
	private static $APP_ID="";
	private static $APP_SECRET="";
	private static $Send_ORDER_URL="http://oms.cnbuyers.cn/api/testorder/add";
	var $params=array();
	public  static function init($app_id,$app_secret){
		self::$APP_ID=$app_id;
		self::$APP_SECRET=$app_secret;
	}
	public  function sendorder($order,$ordergoods){
		$depotid=$order['depotid'];
		$sql="SELECT * from ".tablename("ewei_shop_depot")." where id =:id";
		$depot=pdo_fetch($sql,array(":id"=>$depotid));
		if(empty($depot['app_id']) || empty($depot['app_secret'])){
			return error(-1, 'not find app_id or not find app_sectet');
		}
		self::init($depot['app_id'],$depot['app_secret']);
		$type=$this->getType($order,$depot);
		$this->initPrarams($order,$type);
		$this->init_out_goods($ordergoods,$order['uniacid'],$order['isdisorder']);
        $goodsamount=0;
        foreach ($ordergoods as $goods){
            $goodsamount+=$goods['realprice'];
        }
        $this->params['discount']=intval($goodsamount*100)+intval($order['dispatchprice']*100)-intval($order['price']*100)-intval($order['deductcredit2']*100);
        $this->params['discount']= $this->params['discount']/100;
        if($this->params['discount']<0 || $this->params['discount']<0.03){
            $this->params['discount']=0;
        }
        if($order['deductcredit2']>0){
            $this->params['payment_name']="混合支付";
        }
        if($order['price']==0 && $order['disorderamount']==0){
            $this->params['price']=$order['price']+$order['deductcredit2'];
            $this->params['disoutorder_amount']=$order['price']+$order['deductcredit2'];
        }
        $this->update_out_goodsprice();
		$orders=json_encode($this->get_values());
		$postData=array(
			'app_id'=>self::$APP_ID,
			'orders'=>$orders,
			);
		$token=self::getToken();
		if(isset($token['error'])){
			return $token;
		}
		$postData['access_token']=$token;
		$postData['orders']=Security::encrypt($postData['orders'],self::$APP_SECRET);
		return $this->iHttpPost(self::$Send_ORDER_URL,$postData);
	}
	public function selectOrderShipping($depotid,$ordersn){
		$sql="SELECT * from ".tablename("ewei_shop_depot")." where id =:id";
		$depot=pdo_fetch($sql,array(":id"=>$depotid));
		if(empty($depot['app_id']) || empty($depot['app_secret'])){
			return error(-1, 'not find app_id or not find app_sectet');
		}
		self::init($depot['app_id'],$depot['app_secret']);
		$url="http://oms.cnbuyers.cn/api/shipping";
		$postData=array(
			'app_id'=>self::$APP_ID,
			'order_sn'=>$ordersn,
			'type'=>"order_sn",
			);
		$token=self::getToken();
		$postData['access_token']=self::getToken();
		if(isset($token['error'])){
			return $token;
		}
		return $this->iHttpPost($url,$postData);
	}
    //将商品优惠中的优惠去掉
    private function update_out_goodsprice(){
        $goodsamount=0;
        $discount=$this->params['discount'];

        if($discount>0){
            $shipping_fee=$this->params['shipping_fee'];//运费
            $orderprice=$this->params['order_amount'];//用户实付包含运费
            $order_goods_price=$orderprice-$shipping_fee;//商品时间收款金额
            foreach ( $this->params['goods'] as $good){
                $goodsamount+=$good['price']*$good['quantity'];
            }
            $rate=$order_goods_price/$goodsamount;//优惠比例

            foreach ($this->params['goods'] as &$good){
                $good['price']=$good['price']*$rate;
                $good['outprice']=$good['outprice']*$rate;
            }
            unset($good);
            $this->params['discount']=0;
            $this->params['goods_amount']=$order_goods_price;
        }
    }
	private function getPayment_name($payid){
		switch ($payid) {
			case 21:
				return "微信";
			case 22:
				return "支付宝";
			case 1:
				return "易宝";
			default:
				# code...
				return "开联通支付";
		}
	}
	//检查是用签约版还是非签约版
	private  function getType($order,$depot){

		if($order['deductcredit2']<=0){
			return "T";
		}
		return "F";
	}
	private function iHttpPost($url,$postData){
		load()->func('communication');
		$resp = ihttp_request($url, $postData);
		$content=(array)json_decode($resp['content'],true);
		if($content['error']==401 || $content['error']==403){
			$this->delectRedisKey();
		}
		return $content;
	}
	private  function initPrarams($params,$type){
		$params['address']=unserialize($params['address']);
		$this->params['order_sn']=$params['ordersn'];
		$this->params['payment_name']=$this->getPayment_name($params['paytype']);
		$this->params['tradeNum']=$params['paymentno'];
		$this->params['goods_amount']=$params['goodsprice'];
		$alldeduct=$params['deductenough']+$params['couponprice']+$params['buyagainprice']+$params['deductprice'];
		$this->params['discount']=$alldeduct;
		$this->params['order_amount']=$params['price']+$params['deductcredit2'];
		$this->params['shipping_fee']=$params['dispatchprice'];
		$this->params['province']=urlencode($params['address']['province']);
		$this->params['city']=urlencode($params['address']['city']);
		$this->params['county']=urlencode($params['address']['area']);
		$this->params['address']=urlencode($params['address']['address']);
		$this->params['phoneMob']=$params['address']['mobile'];
		$this->params['consignee']=urlencode($params['address']['realname']);
		$this->params['realName']=urlencode($params['realname']);
		$this->params['imId']=$params['imid'];
		$this->params['creattime']=$params['createtime'];
		$this->params['disType']=$type;
		$this->params['source']=1;
        //$this->params['payinfo']=m('realtimedataupload')->init($params['ordersn'],$params['paytype']);
		$this->params['disshipping_fee']=$params['dispatchprice'];
		//如果是宁波保税仓的商品快递方式
		if($params['depotid']==21){
			$this->params['shipping']=$params['express'];//指定配送方式
		}
		$this->params['disshipping_fee']=$params['dispatchprice'];
		$this->params['disoutorder_amount']=$params['price'];
		$this->params['deduction']=$params['ductcredit2'];//余额优惠
		if($params['isdisorder']==1){
			$this->params['disshipping_fee']=$params['dis_shipping_fee'];
			$this->params['disoutorder_amount']=$params['disorderamount'];

		}



	}
	private  function init_out_goods($goodslist,$uniacid,$isdisorder=0){
		$goodstemp1=array();
		foreach($goodslist as $goods){
			if($goods['goodstype']==4){
				$pgoodslist=json_decode($goods['content3'],true);
				$zpgoods=0;
				foreach($pgoodslist as $pgoods){
					$goodstemp['goods_name']=$pgoods['goods_name'];
					$goodstemp['only_sku']=$pgoods['goodssn'];
					$goodstemp['quantity']=$pgoods['total'];
					$goodstemp['price']=$pgoods['price'];
					$goodstemp['outprice']=$pgoods['price'];
					$goodstemp1[]=$goodstemp;
					if($isdisorder==1){
						$disprice=Dispage::get_disprice($pgoods['goodsid'],$uniacid,$goods['optionid']);
						$goodstemp['outprice']=$disprice;
					}
				}
			}else{

				$goodstemp['goods_name']=$goods['goods_name'];
				$goodstemp['only_sku']=$goods['goodssn'];
				$goodstemp['quantity']=$goods['total'];
				$goodstemp['price']=$goods['realprice']/$goods['total'];
				$goodstemp['outprice']=$goods['realprice']/$goods['total'];
				if($isdisorder==1){
					$goodstemp['outprice']=$goods['disprice'];
				}
				$goodstemp1[]=$goodstemp;
			}
		}
		
		$this->params['goods']=$goodstemp1;
	}
	public function get_values(){
		return $this->params;
	}
	public function delectRedisKey(){
		$key="token_accessToken_".self::$APP_ID;

		$open_redis = function_exists('redis') && !is_error(redis());
		if($open_redis){
			$redis = redis();
			$redis->delete($key);
		}
	}
	public  static  function getToken(){
		$url="http://oms.cnbuyers.cn/accessToken";
		//$url="http://localhost/oms/accessToken";
		$open_redis = function_exists('redis') && !is_error(redis());
		if($open_redis){
			$redis = redis();
			$key="token_accessToken_".self::$APP_ID;
			//$redis->delete($key);
			$token=$redis->get($key);

			if(empty($token) || $token==false){
				 $data=array(
                	'app_id'=>self::$APP_ID,
                	"v"=>"2.0",
                	'app_secret'=>self::$APP_SECRET,
                	"state"=>"Ze1123456",
                );
				$sig=self::getSig($data);
				$data['sign']=$sig;
				unset($data['app_secret']);
				load()->func('communication');
				$resp = ihttp_request($url, $data);
				
				$content=(array)json_decode($resp['content']);

				if($content['error']==0){
					$token=$content['access_token'];
					$redis->set($key,$token);
					$redis->expire($key,$content['expires']-200);
				}else{
					return $content;
				}
				return $token;
			}else{
				return $token;
			}

			return $token;
		}else{
			return  $open_redis;
		}
	}


	private static function paraFilter($data){
        ksort($data);
        return  $data;
    }
    private static function createLinkString($data){
       $str="";
       $data=self::paraFilter($data);
        foreach($data as $k=>$v){
            $str.="&{$k}=".$v;
        }
        $str=substr($str, 1);
        return $str;
    }
   private static function getSig($data){
        $str=self::createLinkString($data);
        return md5($str);
    }
}