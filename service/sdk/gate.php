<?php

class DaDongPay
{

    protected $gateway;
    protected $merchantNo;
    protected $signKey;

    public function __construct($gateway, $merchantNo, $signKey)
    {
        $this->gateway = $gateway;
        $this->merchantNo = $merchantNo;
        $this->signKey = $signKey;
    }

    public function getPayOrder($param)
    {
        $param['merchantNo'] = $this->merchantNo;
        $sign = $this->getSign($param);
        $param['sign'] = $sign;

        $data = $this->doPost($this->gateway . '/paygateway/order', $param);
        return json_decode($data, true);
    }

    public function queryPayOrder($param)
    {
        $param['merchantNo'] = $this->merchantNo;
        $sign = $this->getSign($param);
        $param['sign'] = $sign;
        $data = $this->doPost($this->gateway . '/paygateway/queryPayOrder', $param);
        return json_decode($data, true);
    }

    public function querySettlementOrder($param)
    {
        $param['merchantNo'] = $this->merchantNo;
        $sign = $this->getSign($param);
        $param['sign'] = $sign;
        $data = $this->doPost($this->gateway . '/paygateway/querySettlementOrder', $param);
        return json_decode($data, true);
    }

    public function queryBalance($param)
    {
        $param['merchantNo'] = $this->merchantNo;
        $sign = $this->getSign($param);
        $param['sign'] = $sign;
        $data = $this->doPost($this->gateway . '/paygateway/queryBalance', $param);
        return json_decode($data, true);
    }

    public function getSettlementOrder($param)
    {
        $param['merchantNo'] = $this->merchantNo;
        $sign = $this->getSign($param);
        $param['sign'] = $sign;
        $data = $this->doPost($this->gateway . '/paygateway/settlementPhp', $param);
        return json_decode($data, true);
    }

    protected function getSign($param)
    {
        $newParam = array_filter($param);
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . $this->signKey;
        } else {
            $originalString = $this->signKey;
        }
        return md5($originalString);
    }

    protected function doGet($url, $param)
    {
        //初始化
        $ch = curl_init();
        // echo PHP_EOL;
        // echo $url . '?' . http_build_query($param), PHP_EOL;
        // print_r($param);
        // echo PHP_EOL;
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($param));
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

    protected function doPost($url, $param)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $output = curl_exec($ch);
        curl_close($ch);

        echo PHP_EOL;
        echo $url;
        echo PHP_EOL;
        echo json_encode($param);
        print_r($output);
        echo PHP_EOL;
        return $output;
    }
}
