<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

/**
 * DC Wallet支付
 * Class DCPay
 * @package App\Channels\Lib
 */
class DCPay extends Channels
{
    protected $payType = [
        'EBank' => 'BANK_TRANSFER',
        'Quick' => null,
        'OnlineWechatQR' => null,
        'OnlineAlipayQR' => "ALIPAY_SCANPAY",
        'OnlineWechatH5' => 'WEIXIN_TRANSFER',
        'OnlineAlipayH5' => 'ALIPAY_TRANSFER',
        'QQPayQR' => null,
        'UnionPayQR' => null,
        'JDPayQR' => null,
        'EBankQR' => null,
    ];

    public function getPayOrder($orderData)
    {
//        $res=self::queryOrder('P20190920154011504691');
//        var_dump($res);die;
        $params = [
            'transId' => "TOKEN_USER_PAY_H5",
            'merNo' => $this->params['cId'],
            'merKey' => $this->params['appPublicKey'],//支付key
            'merOrderNo' => $orderData['platformOrderNo'],
            'orderDesc' => "GOODS",
            'transAmt' =>  sprintf("%.2f", $orderData['orderAmount']),
            'transTime' => date('Ymdhis'),
            'cardType' => $this->payType[$orderData['payType']],
            'notifyUrl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'returnUrl'=>!empty($orderData['frontNoticeUrl'])?$orderData['frontNoticeUrl']: 'https://www.baidu.com',
            'token'=>'USDT',
        ];

        $params['sign'] = $this->getSign($params,$this->params['apiKey']);

        $req = Requests::post($this->gateway, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug($this->gateway, ['params'=>$params,'result'=>json_decode($req->body, true)]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['status']) && $res['status'] == "PROCESSING") {
                return [
                    'status' => 'Success',
                    'payUrl' => $res['authCode'],
                    'orderNo' => $orderData['platformOrderNo'],
                    'failReason' => $req->body,
                ];
            } else {
                return [
                    'status' => 'Fail',
                    'payUrl' => '',
                    'orderNo' => '',
                    'failReason' => '第三方请求失败:' . $req->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'payUrl' => '',
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function successResponse($response)
    {
        return $response->write("success");
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        $this->logger->debug($this->gateway, ['params'=>[],'result'=>$params]);
        if ($params['status'] != 'SUCCESS') {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['merOrderNo'],
                'orderAmount' => $params['transAmt'],
                'failReason' => '付款失败！',
                'attr' => '',
            ];
        }


        if ($orderData['platformOrderNo'] != $params['merOrderNo']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['merOrderNo'],
                'orderAmount' => $params['transAmt'],
                'failReason' => '回调订单号异常:' . json_encode($params),
                'attr' => '',
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params['status'] == 'SUCCESS' ? 'Success' : 'Fail',
            'orderNo' => $params['merOrderNo'],
            'orderAmount' => $params['transAmt'],
            'failReason' => json_encode($params),
        ];
    }

    public function queryOrder($platformOrderNo)
    {
//        $merchantOrderNo=
        $params = [
            'transId'=>'TOKEN_USER_PAY_QUERY',
            'merNo'=>$this->params['cId'],
            'merKey'=>$this->params['appPublicKey'],
            'merOrderNo' => $platformOrderNo,
            'transTime' => date('ymdhis'),
        ];
        $params['sign']=$this->getSign($params,$this->params['apiKey']);
        $req = Requests::post($this->gateway , [], $params, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            return [
                'status' => $res['status'] == 'SUCCESS' ? 'Success' : 'Fail',
                'orderAmount' => $res['transAmt'],
                'orderNo' => $res['merOrderNo'],
                'failReason' => $req->body,
            ];
        } else {
            return [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    /*============================= 代付-- start ==============================*/
    public function getSettlementOrder($orderData)
    {
        $cardType='BANK_TRANSFER';
        if($orderData['bankCode']!=''){
            if($orderData['bankCode']=='ALIPAY'){
                $cardType='ALIPAY_TRANSFER';
            }else{
                $cardType='BANK_TRANSFER';
            }
        }

        $params=[
            'transId'=>'TOKEN_MERCHANT_AGENT_PAY',
            'merNo'=>$this->params['cId'],
            'merKey' => $this->params['appPublicKey'],//支付key
            'merOrderNo' => $orderData['platformOrderNo'],
            'cardType'=>$cardType,
            'transAmt' =>  sprintf("%.2f", $orderData['orderAmount']),
            'notifyUrl' => $this->getSettlementCallbackUrl($orderData['platformOrderNo']),
            'currency'=>'RMB',
            'token'=>'USDT',
            'payeeCardNo'=>  Tools::decrypt( $orderData['bankAccountNo']),
            'payeeIdName'=>$orderData['bankAccountName'],
            'payeeBankCode'=>$orderData['bankCode'],
            'transTime' => date('Ymdhis'),
            'payeeAddress'=>$orderData['bankName']
        ];
        $params['sign'] = $this->getSign($params,$this->params['apiKey']);
        $req = Requests::post($this->gateway, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug($this->gateway, ['params'=>$params,'result'=>json_decode($req->body, true)]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['status']) && ($res['status'] == "PROCESSING" || $res['status'] == "SUCCESS")) {
                return [
                    'status' => 'Success',
                    'orderAmount'=>$orderData['orderAmount'],
                    'orderNo' => $orderData['platformOrderNo'],
                    'failReason' => $req->body,
                ];
            } else {
                return [
                    'status' => 'Fail',
                    'orderAmount'=>$orderData['orderAmount'],
                    'orderNo' => '',
                    'failReason' => '第三方请求失败:' . $req->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'orderAmount'=>$orderData['orderAmount'],
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function queryBalance()
    {
        $output = ['status' => '',  'failReason' => '', 'balance' => 0];
        $params = [
            'transId'=>'TOKEN_MERCHANT_BALANCE_QUERY',
            'merNo'=>$this->params['cId'],
            'merKey'=>$this->params['appPublicKey'],
            'merOrderNo' =>  Tools::getPlatformOrderNo('S'),
            'token'=>'USDT',
            'transTime' => date('ymdhis'),
        ];
        $params['sign']=$this->getSign($params,$this->params['apiKey']);
        $req = Requests::post($this->gateway, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug($this->gateway, ['params'=>$params,'result'=>$req]);
        if($this->status_code=200){
            $res = json_decode($req->body, true);
            $this->logger->debug("查询余额回复 ：", $res);
            if($res['status'] && $res['status']=='SUCCESS'){
                $output['status'] = 'Success';
                $output['balance'] = $res['quantity'];
            }else{
                $output['status'] = 'Fail';
                $output['failReason'] = '查询失败：respCode:'.$res['respCode'].';respDesc：'.$res['respDesc'];
            }
        }else{
            $output['status'] = 'Fail';
            $output['failReason'] = '请求第三方失败'.$req->body;
        }
        return $output;
    }

    public function querySettlementOrder($platformOrderNo = '')
    {
//        $merchantOrderNo=
        $params = [
            'transId'=>'TOKEN_USER_PAY_QUERY',
            'merNo'=>$this->params['cId'],
            'merKey'=>$this->params['appPublicKey'],
            'merOrderNo' => $platformOrderNo,
            'transTime' => date('ymdhis'),
        ];
        $params['sign']=$this->getSign($params,$this->params['apiKey']);
        $req = Requests::post($this->gateway , [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug("查询代付订单 $platformOrderNo 回复 ：", json_decode($req->body, true));
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
           $arr= [
               'status' => 'Success',
               'orderAmount' => $res['transAmt'] ?? 0,
               'orderNo' => $res['merOrderNo'],
               'failReason' => $req->body,
           ];
            if($res['status']=='SUCCESS'){
                $arr['status']='Success' ;
            }
            if($res['status']=='PROCESSING'){
                $arr['status']='Execute' ;
                $arr['failReason']='订单处理中：status:' .$res['status'].'；返回内容：'.$req->body;
            }
            if($res['status']=='UNPAY'){
                $arr['status']='Fail' ;
                $arr['failReason']='订单处理失败：status:' .$res['status'].'；返回内容：'.$req->body;
            }
            return $arr;
        } else {
            return [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }
    /*============================= 代付-- end ==============================*/

   public function getSign($params,$apiKey){
       ksort($params);
       $queryStr = '';
       foreach ($params as $key=>$param){
           if("sign" != $key && "" != $param) {
               $queryStr .= $key . "=" . $param . "&";
           }

       }
       $queryStr .= "paySecret=$apiKey" ;
       return strtoupper(md5($queryStr));
   }

}
