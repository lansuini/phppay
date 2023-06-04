<?php
/**
 * Created by PhpStorm.
 * User: luobinhan
 * Date: 2023/4/21
 * Time: 11:30
 */

namespace App\Controllers\Merchant;


class Test
{
    public function testSettle(){

        $merchantNo = "10000004";
        # 密钥
        $signKey = "9b759040321a408a5c7768b4511287a6";
        $merchantOrderNo = date('YmdHis') . rand(100000, 999999);
        $data = [
            'merchantNo' => $merchantNo,
            'merchantOrderNo' => $merchantOrderNo,
            'merchantReqTime' => date("YmdHis"),
            'orderAmount' => 100.00,
            'tradeSummary' => 'test',
            'bankCode' => 'Globe Gcash',
            'bankName' => '兴宁支行',
            'bankAccountNo' => '6222032007001334680',
            'bankAccountName' => 'helloworld',
            'province' => 'guangdong',
            'city' => 'meizhou',
            'orderReason' => '测试代付',
            'requestIp' => '159.138.86.177',
            'backNoticeUrl' => 'http://cb.luckyp666.com/pay/callback/' . $merchantOrderNo,
            'merchantParam' => 'fuck',
        ];
        $output = $this->getSettlementOrder($data,$signKey,$merchantNo);
        var_dump($output);
    }

    public function testPay(){

        $merchantNo = "10000004";
        # 密钥
        $signKey = "9b759040321a408a5c7768b4511287a6";
        $merchantOrderNo = date('YmdHis') . rand(100000, 999999);
        $params = [
            'merchantNo' => $merchantNo,
            'merchantOrderNo' => $merchantOrderNo,
            'merchantReqTime' => date("YmdHis"),
            'orderAmount' => 500.00,
            'tradeSummary' => 'test',
            'payModel' => 'Direct',
            'payType' => 'BPIA',
            'cardType' => 'DEBIT',
            'userTerminal' => 'Phone',
            'userIp' => '127.0.0.1',
            'backNoticeUrl' => 'http://cb.xddzfcsz.com/settlement/callback/'.$merchantOrderNo,
            'merchantParam' => 'abc1',
        ];

        $output = $this->getPayOrder($params,$signKey);
        var_dump($output);
    }

    public function testBalance(){

        $merchantNo = "10000004";
        # 密钥
        $signKey = "9b759040321a408a5c7768b4511287a6";
        $params = [
            'merchantNo' => $merchantNo,
        ];
        $url = 'http://gate.luckypay.mm'.'/paygateway/query/balance';

        $output = $this->query($params,$signKey,$url);
        var_dump($output);
    }

    public function testQueryPay(){

        $merchantNo = "10000004";
        # 密钥
        $signKey = "9b759040321a408a5c7768b4511287a6";
        $params = [
            'merchantNo' => $merchantNo,
            'merchantOrderNo' => "20230418120628979056",
        ];
        $url = 'http://gate.luckypay.mm'.'/paygateway/query/pay';

        $output = $this->query($params,$signKey,$url);
        var_dump($output);
    }

    public function testQuerySettle(){

        $merchantNo = "10000004";
        # 密钥
        $signKey = "9b759040321a408a5c7768b4511287a6";
        $params = [
            'merchantNo' => $merchantNo,
            'merchantOrderNo' => "20230418123153694729",
        ];
        $url = 'http://gate.luckypay.mm'.'/paygateway/query/settlement';

        $output = $this->query($params,$signKey,$url);
        var_dump($output);
    }

    public function query($param,$signKey,$url)
    {
        $sign = $this->getSign($param,$signKey);
        $param['sign'] = $sign;
        $data = $this->doPost($url, $param);
        return json_decode($data, true);
    }

    public function getPayOrder($param,$signKey)
    {
        $gateway = 'http://gate.luckypay.mm';
        $sign = $this->getSign($param,$signKey);
        $param['sign'] = $sign;
        $data = $this->doPost($gateway . '/paygateway/pay', $param);
        return json_decode($data, true);
    }

    public function getSettlementOrder($param,$signKey,$merchantNo)
    {
        $gateway = 'http://gate.luckypay.mm';
        $param['merchantNo'] = $merchantNo;
        $sign = $this->getSign($param,$signKey);
        $param['sign'] = $sign;
        $data = $this->doPost($gateway . '/paygateway/settlement', $param);
        return json_decode($data, true);
    }

    protected function doPost($url, $param)
    {
        $data_string = json_encode($param);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($data_string)]);
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
        echo $data_string;
//        print_r($output);
        var_dump($output);

        echo PHP_EOL;
        return $output;
    }

    protected function getSign($param,$signKey)
    {
        $newParam = array_filter($param);
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . $signKey;
        } else {
            $originalString = $signKey;
        }
        return md5($originalString);
    }
}