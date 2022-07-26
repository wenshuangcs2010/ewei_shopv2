<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
class RasUtil_EweiShopV2Model
{

    private  $priKey="MIIEogIBAAKCAQEAtkdqNWUH3J8c7jSHBf6QGAmWT6Qncmf3KHfLSBomPtrWu1Wg6MTx+zZo3UZPwjyL7Ru1IbYVsg8+BJFe+7HkX3iuQJET3Huz6uhTcou0jCP7gfTAX/b+GQkMJfeFy8oTrOLgeH9NXca1UYSKVUxebaKKRt9GLfSKVFwtmIQXZcccGA2oGUlTtERbNYbHv+C4xFYvtQT52o8Qd0y9O6T0YkFRM+fVadER7y7jca8O052L6uPUy7iSnIqcVUdcerLT8NY2CbGMQoDJZux7kzev/3KUXzz2d+7UrrRHkMh1n0D8EBqRvRaPoHP2lRTAcu1JHfEvMFfvnQPrNHsRiCEc6wIDAQABAoIBAE9mpHlF3TrZhLyu3PJbhWEzK+rdUuGfMCbMqBxsZ6SRp2BLWKCMyevoM53P9wZhYYKTwz9AFgtOYHGU8Y8qWpTL+PvfNougxyrYsSEAglFZi0F84B/tc5psOoJ/ZCT1jcSNv8I9kfH7kRhFaAzoC2oul5VvxR5Xm7pLvQDm6VpHuIw8Kw6heyYZQ9wVSLriIoDoGj7j6gRX9Vux14cH7tT1bU+RVQFwfMtZvU0fm7U+GV3DEl/c5hyBs2rZ0HC2i1puwB3S6OasAAkPIn/Uq6IIkpVbGWTpikkA1mHBolqLGk09+tLw+32p1D/tJNHqW1TIont0oEPVouJpM9EAOgECgYEA7St7dCBGNsAqWSNcYcUp6Fa7G3AjzCIaE3prTAbhImM5cmS1lWKnputjsF232L74LsDR7fPZGvzipkKNh4XXoGWD0VHAehsSZEAYXsALov8fVaKWY2s5Fyg/ALMdtx8I7hBRtUrXwsUCypBU9253dKV4o+PT+vy/PuZ7Zl/BHCsCgYEAxMBEFfX1W/GopKeouR+GvJnYYzoHFZ0ts1CtwuinpWcMum3JgZu4MbJrUJKl9abML+X8YPz36Zqi1SPcRdnIsS+e6nhs+a1JrSr/MhrPjGKbA6SIBl6lLQU6fguv8biFWH8ws9V2O4bHRvoC/3CT/CZduGv7HcOeUny8W4sx4kECgYAr6jw+wWce5jAxNyn49JAQ9FZK+1W1i672YlmRx0hSnLrbYqh4076lWrqnwoKzQJEl3xBAFkHiDGdPT81zBaZqjcF9tbyFH5QlOfUJPlgpQ0IjEir0l7sHfa1EzOW4radypVTr08LlzqL4rQb+ldbKo4UWG655r+kdlYHN7/cK5wKBgHTgTdHJ2SX0KR0ep4DI9I4Gyd8v8lNpmmNB0ubMtAHydEIuw5wld8a12T/0zXdezT7K3SB0RYTUolQAyHIKDEkNI9bfEMVEplajCxOlj5MyZClGzLMT+AUFbRjBMpRh63yFmdXKQUDdMHW+QJejNZV86QGLy5GDygHwLSgoSMrBAoGAe9T96KlwfQ0ngDkGikhKzfBHeZMYdWnEhZDVZLz8S13ZyZR4d7DWDMzCmLhYweMyKhFnQ+5R91bcTi54rsiciA9rwvucDs9jyd2ytX6faZj6jHACaACSZPjrYiSL+wV8wnaVbQQh0AlrEhayx3EaMMb90jNrdNP1WNWybwT1SDY=";
    private  $pubKey="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtkdqNWUH3J8c7jSHBf6QGAmWT6Qncmf3KHfLSBomPtrWu1Wg6MTx+zZo3UZPwjyL7Ru1IbYVsg8+BJFe+7HkX3iuQJET3Huz6uhTcou0jCP7gfTAX/b+GQkMJfeFy8oTrOLgeH9NXca1UYSKVUxebaKKRt9GLfSKVFwtmIQXZcccGA2oGUlTtERbNYbHv+C4xFYvtQT52o8Qd0y9O6T0YkFRM+fVadER7y7jca8O052L6uPUy7iSnIqcVUdcerLT8NY2CbGMQoDJZux7kzev/3KUXzz2d+7UrrRHkMh1n0D8EBqRvRaPoHP2lRTAcu1JHfEvMFfvnQPrNHsRiCEc6wIDAQAB";

    /**
     * 构造函数
     *
     * @param string 公钥文件（验签和加密时传入）
     * @param string 私钥文件（签名和解密时传入 ）
     */
    public function init($public_key_file = '', $private_key_file = ''){
        if ($public_key_file) {
            $this->_getPublicKey($public_key_file);
        }
        if ($private_key_file) {
            $this->_getPrivateKey($private_key_file);
        }
        return $this;
    }

    private function _getPublicKey($file)
    {
        $key_content = $this->_readFile($file);
        if ($key_content) {
            $this->pubKey = openssl_get_publickey($key_content);
        }
    }

    private function _getPrivateKey($file)
    {
        $key_content = $this->_readFile($file);
        if ($key_content) {
            $this->priKey = openssl_get_privatekey($key_content);
        }
    }
    // 私有方法
    /**
     * 自定义错误处理
     */
    private function _error($msg)
    {
      error(-1,'RSA Error:' . $msg); //TODO
    }
    /**
     * 检测填充类型
     * 加密只支持PKCS1_PADDING
     * 解密支持PKCS1_PADDING和NO_PADDING
     *
     * @param int 填充模式
     * @param string 加密en/解密de
     * @return bool
     */
    private function _checkPadding($padding, $type)
    {
        if ($type == 'en') {
            switch ($padding) {
                case OPENSSL_PKCS1_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        } else {
            switch ($padding) {
                case OPENSSL_PKCS1_PADDING:
                case OPENSSL_NO_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        }
        return $ret;
    }

    private function _readFile($file)
    {
        $ret = false;
        if (!file_exists($file)) {
            $this->_error("The file {$file} is not exists");
        } else {
            $ret = file_get_contents($file);
        }
        return $ret;
    }
    private function _hex2bin($hex = false)
    {
        $ret = $hex !== false && preg_match('/^[0-9a-fA-F]+$/i', $hex) ? pack("H*", $hex) : false;
        return $ret;
    }
    private function _encode($data, $code)
    {
        switch (strtolower($code)) {
            case 'base64':
                $data = base64_encode('' . $data);
                break;
            case 'hex':
                $data = bin2hex($data);
                break;
            case 'bin':
            default:
        }
        return $data;
    }

    private function _decode($data, $code)
    {
        switch (strtolower($code)) {
            case 'base64':
                $data = base64_decode($data);
                break;
            case 'hex':
                $data = $this->_hex2bin($data);
                break;
            case 'bin':
            default:
        }
        return $data;
    }

    /**
     * 加密
     *
     * @param string 明文
     * @param string 密文编码（base64/hex/bin）
     * @param int 填充方式（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING）
     * @return string 密文
     */
    public function encrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING)
    {
        if(is_array($data)){
            $data=json_encode($data,320);
        }
        $ret = false;
        if (!$this->_checkPadding($padding, 'en')) $this->_error('padding error');

        if (openssl_public_encrypt($data, $result, $this->pubKey, $padding)) {
            $ret = $this->_encode($result, $code);
        }
        return $ret;
    }
    /**
     * 解密
     *
     * @param string 密文
     * @param string 密文编码（base64/hex/bin）
     * @param int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @param bool 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
     * @return string 明文
     */
    public function decrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false)
    {
        $ret = false;
        $data = $this->_decode($data, $code);
        if (!$this->_checkPadding($padding, 'de')) $this->_error('padding error');
        if ($data !== false) {
            if (openssl_private_decrypt($data, $result, $this->priKey, $padding)) {

                $ret = $rev ? rtrim(strrev($result), "\0") : '' . $result;
            }
        }
        return $ret;
    }
    public  function runBefore(){
        if (strpos($this->pubKey, 'PUBLIC') === false) {

            $this->pubKey="-----BEGIN PUBLIC KEY-----\r\n".  wordwrap($this->pubKey, 64, "\r\n", true)."\r\n-----END PUBLIC KEY-----";
        }
        if (strpos($this->priKey, 'PRIVATE') === false) {
            $this->priKey="-----BEGIN RSA PRIVATE KEY-----\r\n".  wordwrap($this->priKey, 64, "\r\n", true)."\r\n-----END RSA PRIVATE KEY-----";
        }
        return $this;
    }

}