<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Util_EweiShopV2Model {

    function getExpressList($express, $expresssn) {
        $kuaidi100key="rxPECPqf4944";
        $customer="F297D63BAF65F1EC2C03E2991C2F53BF";
        $express = $express=="jymwl" ? "jiayunmeiwuliu" : $express;
        $express = $express=="TTKD" ? "tiantian" : $express;
        $express = $express=="jjwl" ? "jiajiwuliu" : $express;

        //$url = "http://wap.kuaidi100.com/wap_result.jsp?rand=" . time() . "&id={$express}&fromWeb=null&postid={$expresssn}";
        $url = "http://poll.kuaidi100.com/poll/query.do";
        //$url = "https://www.kuaidi100.com/query?type={$express}&postid={$expresssn}&id=1&valicode=&temp=";
        // if($express=="shunfeng"){
        //   $url="http://baidu.kuaidi100.com/query?type={$express}&postid={$expresssn}&id=4&valicode=&temp=0.5553617616442275&sessionid=";
        // }
       
        $post_data=array(
            'customer'=>$customer,
            'param'=>json_encode(array("com"=>$express,'num'=>$expresssn))
            );
        $post_data["sign"] = md5($post_data["param"].$kuaidi100key.$post_data["customer"]);
        $post_data["sign"] = strtoupper($post_data["sign"]);
        load()->func('communication');
        $resp = ihttp_request($url,$post_data);
        $content = $resp['content'];
        if (empty($content)) {
            return array();
        }
        $info = json_decode($content, true);
        
        if(empty($info) || !is_array($info) || empty($info['data'])){
            return array();
        }
        $list = array();
        foreach ($info['data'] as $index=>$data){
            $list[] = array(
                'time' => trim($data['time']),
                'step' => trim($data['context'])
            );
        }

        return $list;
    }

    // 根据IP获取城市
    function getIpAddress() {
        $ipContent = file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js");
        $jsonData = explode("=", $ipContent);
        $jsonAddress = substr($jsonData[1], 0, -1);
        return $jsonAddress;
    }

    function checkRemoteFileExists($url) {
        $curl = curl_init($url);
        //不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        //发送请求
        $result = curl_exec($curl);
        $found = false;
        if ($result !== false) {
            //检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                $found = true;
            }
        }
        curl_close($curl);
        return $found;
    }

    /**
     * 计算两组经纬度坐标 之间的距离
     * params ：lat1 纬度1； lng1 经度1； lat2 纬度2； lng2 经度2； len_type （1:m or 2:km);
     * return m or km
     */
    function GetDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
    {
        $pi = 3.1415926;
        $er = 6378.137;

        $radLat1 = $lat1 * $pi / 180.0;
        $radLat2 = $lat2 * $pi / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * $pi / 180.0) - ($lng2 * $pi / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $s = $s * $er;
        $s = round($s * 1000);
        if ($len_type > 1)
        {
            $s /= 1000;
        }
        return round($s, $decimal);
    }

    function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC){
        if(is_array($multi_array)){
            foreach ($multi_array as $row_array){
                if(is_array($row_array)){
                    $key_array[] = $row_array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }

        array_multisort($key_array, $sort , $multi_array);

        return $multi_array;
    }


}
