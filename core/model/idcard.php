<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Idcard_EweiShopV2Model
{

    private $realName='';
    private $imid="";
    function validation(){
        //防止接口被盗用的处理
        load()->func('communication');
        $headers=array('SIGN'=>$this->sign());
        $response = ihttp_request("https://m.cnbuyers.cn/addons/ewei_shopv2/core_inc/aliyunvalidation.php", [],$headers,5);
        if($response['code']==200){
            $responsecontent=json_decode($response['content'],true);
            return $responsecontent;
        }
        return null;
    }
    function init($realname,$imid){
        $this->realName=$realname;
        $this->imid=$imid;

        return $this;
    }
    function sign(){
        $data=array('realname'=>$this->realName,'imid'=>$this->imid);
        $sign=m('rasutil')->runBefore()->encrypt($data,'hex');
        return $sign;
    }
    private function  checkdate($dateNum){
        $returndata=array('year'=>'','month'=>'','day'=>'');
        $date1 = substr($dateNum, 0, 4);
        $date2 = substr($dateNum, 4, 2);
        $date3 = substr($dateNum, 6, 2);
        $returndata['year']=$date1;
        $returndata['month']=$date2;
        $returndata['day']=$date3;
        unset($date1);
        unset($date2);
        unset($date3);
        return $returndata;
    }
    private function checksex($sexNum){
        if($sexNum%2){
            return false; //男
        }
        return true;//女
    }
    public function splitIdcard($IDCard){
        if($this->validateIDCard($IDCard)){
            //如果是15位的身份证
            if (strlen($IDCard) == 15) {
                $IDCard = self::convertIDCard15to18($IDCard);
            }
            //省
            $areaprovince=substr($IDCard, 0, 2);
            //市
            $areapcity=substr($IDCard, 2, 2);
            // 省市县（6位）
            $areaNum = substr($IDCard, 0, 6);//
            // 出生年月（8位）
            $dateNum = substr($IDCard, 6, 8);
            // 性别（3位）
            $sexNum = substr($IDCard, 12, 3);
            // 校验码（1位）
            $endNum = substr($IDCard, 17, 1);
           // $citylist=$this->selectCity($areaprovince,$areapcity,$areaNum);

            $data=$this->checkdate($dateNum);
            $sex=$this->checksex($sexNum) ? 1:0;
            $returndata=array(
               // 'rovince'=>$citylist['rovince'],
               // 'city'=>$citylist['city'],
              //  'zone'=>$citylist['zone'],
                'year'=>$data['year'],
                'month'=>$data['month'],
                'day'=>$data['day'],
                'sex'=>$sex,
            );
            unset($citylist);
            unset($data);
            return $returndata;
        }
        return error(-1,'身份证错误:错误身份证号码:'.$IDCard);
    }

    //验证身份证是否有效
    public  function validateIDCard($IDCard) {
        if (strlen($IDCard) == 18) {
            return $this->check18IDCard($IDCard);
        } elseif ((strlen($IDCard) == 15)) {
            $IDCard = self::convertIDCard15to18($IDCard);
            return $this->check18IDCard($IDCard);
        } else {
            return false;
        }
    }
    // 将15位身份证升级到18位
    public  function convertIDCard15to18($IDCard) {
        if (strlen($IDCard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($IDCard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $IDCard = substr($IDCard, 0, 6) . '18' . substr($IDCard, 6, 9);
            } else {
                $IDCard = substr($IDCard, 0, 6) . '19' . substr($IDCard, 6, 9);
            }
        }
        $IDCard = $IDCard . $this->calcIDCardCode($IDCard);
        return $IDCard;
    }
    // 18位身份证校验码有效性检查
    public  function check18IDCard($IDCard) {
        if (strlen($IDCard) != 18) {
            return false;
        }
        $IDCardBody = substr($IDCard, 0, 17); //身份证主体
        $IDCardCode = strtoupper(substr($IDCard, 17, 1)); //身份证最后一位的验证码

        if ($this->calcIDCardCode($IDCardBody) != $IDCardCode) {
            return false;
        } else {
            return true;
        }
    }
    //计算身份证的最后一位验证码,根据国家标准GB 11643-1999
    public  function calcIDCardCode($IDCardBody) {
        if (strlen($IDCardBody) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $code = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($IDCardBody); $i++) {
            $checksum += substr($IDCardBody, $i, 1) * $factor[$i];
        }
        return $code[$checksum % 11];
    }

}