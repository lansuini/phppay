<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Encrypt;
use App\Helpers\Tools;
use Requests;

/**
 * 印度支付
 * http://docs.wiwi888.cn/web/#/2?page_id=5
 * 密码：123
 * Class YinduPay
 * @package App\Channels\Lib
 */
class YinduPay extends Channels{

    protected $payType = [
//        'OnlineWechatPB' => 1801,
//        'OnlineWechatQR' => 1802,
//        'OnlineAlipayQR' => 1803,
//        'OnlineWechatH5' => 1801,
//        'OnlineAlipayH5' => 1804,
//        'QQPhonePay' => 1805,
//        'UnionPay' => 1807,
//        'QQPayQR' => 1808,
//        'BaiduWallet' => 1809,
//        'JDPayQR' => 1810,
    ];

    protected $error = [
        '0000'=> '支付订单生成成功',
        '6616'=> '订单不存在，无法执行操作',
        '6657'=> '订单已存在，无法重复创建',
        '6656'=> '请求参数错误',
        '6655'=> '订单已存在，无法重复创建',
        '8887'=> '方法不存在(方法名错误)',
        '8888'=> '未知异常',
        '9993'=> '入参校验失败,入参错误',
        '9994'=> '无权限访问该 url',
    ];

    //代付接口请求
    public function getSettlementOrder($orderData)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];

        $path = "/transferPay/{$this->params['cId']}";
        $params = [
            'beneName'=>$orderData['bankAccountName'],//收款方姓名
            'beneAccNo'=>Tools::decrypt($orderData['bankAccountNo']),//收款方银行账号
//            'bankName'=>$orderData['bankName'],//收款方银行名称
            'beneIFSC'=>$orderData['tradeSummary'],//收款方 IFSC
            'txnId'=>$orderData['platformOrderNo'],//商户订单ID
            'amount'=>$orderData['orderAmount'],//放贷金额
        ];

        $requestParams = $this->dataEncode($params);
        $this->logger->debug('向上游发起代付请求：' . $this->gateway . $path, $params);
        $response = Requests::post($this->gateway . $path, [], $requestParams, ['timeout' => $this->timeout, 'verify'=>false]);
        if ($response->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . $response->body;
            return $output;
        }
        //如果发生错误返回json字串，如果成功返回自动调转的html
        $rspBody = $response->body;
//        $rspBody = 'rfh5Ka6fd7ciiKwvTDpJ7mjN9lwmIDWu1iSmMkhK9FnhqjmiaJM+UTPk+pVlpinqVmGc4WkQSFqbvxl4LVz5I/wfL0hmclbFAztiO5WmO6yVE1f0BFQQZ31qP2QGkRZSzBOcxmjL7Iw1q1uMN8atfDvhlCkYiMfF/IHsSNQogl04GENvBOlKb9bAPOFEoVVi6z2ydMSSGoIlL9ZXICDaFfi3tkmp4nyVS95g/fyI66fqn2XgAt0DO8ISaDsC5MLNh/ynT+cFZmKIS8EM9Tf3x3YtW82h+4fj8e0or/oV8XZu9ne0J8q1AS89HLzxerBxgAI3NS36a8Zjc8lFubKKRB67XtU8pRDRoygSk2NynITckKTzZxTOimA6MnBuGTZ7';
        $rspStr = $this->dataDecode($rspBody);
//        $rspStr = '{"status":"0000","message":"success","retBizParams":{"txnId":"S20190912111922971342","mpRefId":"59125840999","beneAccNo":"316278361248","beneIFSC":null,"partnerId":"Mct_test_10001_PartnerId","amount":"100.2","status":"TRANSACTION_FAILURE","createTime":null},"token":null,"sign":null}';
        $this->logger->debug('上游代付回复：[status_code]:' . $response->status_code . ', [resp_body]:' . $response->body.', [resp_json]:'.$rspStr);

        if (Tools::isJsonString($rspStr)) {
            $arrRspBody = json_decode($rspStr, true);
            if (isset($arrRspBody['status'])) {
                if($arrRspBody['status'] == '0000' && $arrRspBody['retBizParams']['status'] != 'TRANSACTION_FAILURE'){
                    $output['orderNo'] = $orderData['platformOrderNo'];
                    $output['status'] = 'Success';
                    $output['orderAmount'] = $arrRspBody['retBizParams']['amount'];
                    $output['failReason'] = $rspBody;
                }else{
                    $failReason = '第三方返回创建代付订单失败';
                    $failReason .= '，msg：' . $this->error[$arrRspBody['status']];
                    $output['status'] = 'Fail';
                    $output['failReason'] = $failReason;
                }
            }else{
                $output['status'] = 'Fail';
                $output['failReason'] = '第三方返回参数错误，' . $rspBody;
            }
            return $output;
        }else{
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方返回参数不合法，' . $rspBody;
            return $output;
        }
    }

    //代付查询请求
    public function querySettlementOrder($platformOrderNo){
        $output = ['status' => '', 'orderNo' => $platformOrderNo, 'failReason' => '', 'orderAmount' => 0];
        $path = "/queryTransferPay/{$this->params['cId']}";
        $params = [
            'txnId'=>$platformOrderNo,//商户订单ID
        ];
        $requestParams = $this->dataEncode($params);
        $this->logger->debug('向上游发起代付查询请求：' . $this->gateway . $path, $params);
        $response = Requests::post($this->gateway . $path, [], $requestParams, ['timeout' => $this->timeout, 'verify'=>false]);
        if ($response->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . $response->body;
            return $output;
        }
        //如果发生错误返回json字串，如果成功返回自动调转的html
        $rspBody = $response->body;
//        rfh5Ka6fd7ciiKwvTDpJ7mjN9lwmIDWu1iSmMkhK9FnhqjmiaJM+UTPk+pVlpinq4SdtA/2bJhyi9e/RC78dsNklJYQAgKnObT7FZBQqfxE9eNmjMdK7grPlIiRG26Hs
        $rspStr = $this->dataDecode($rspBody);
//        {"status":"0000","message":"success","retBizParams":{"txnId":null,"mpRefId":"59125841329","beneAccNo":"316278361248","beneIFSC":"SBOI14431","partnerId":"Mct_test_10001_PartnerId","amount":"100.20","status":"TRANSACTION_SUCCESS","createTime":"201909255092604"},"token":null,"sign":null}
        $this->logger->debug('上游代付回复：[status_code]:' . $response->status_code . ', [resp_body]:' . $response->body.', [resp_json]:'.$rspStr);

        if (Tools::isJsonString($rspStr)) {
            $arrRspBody = json_decode($rspStr, true);
            if (isset($arrRspBody['status'])) {
                if($arrRspBody['status'] == '0000' && !empty($arrRspBody['retBizParams'])){
                    $bizData = $arrRspBody['retBizParams'];
                    if($bizData['status'] == 'TRANSACTION_SUCCESS'){//代付成功
                        $output['orderAmount'] = $bizData['amount'];
                        $output['status'] = 'Success';
                        $output['failReason'] = '第三方代付查询返回代付成功，' .$rspStr;
                    }else if($bizData['status'] == 'TRANSACTION_FAILURE'){//代付失败
                        $output['status'] = 'Fail';//代付失败
                        $output['failReason'] = '第三方代付查询返回代付失败，' . $rspStr;
                    }else{
                        $output['status'] = 'Execute';//处理中
                        $output['failReason'] = '第三方代付查询返回处理中，' . $rspStr;
                    }
                }else if($arrRspBody['status'] == '6620'){//订单不存在, 查询失败
                    $output['status'] = 'Fail';//代付失败
                    $output['failReason'] = '第三方代付订单不存在，' . $rspStr;
                }else{
                    $failReason = '第三方代付查询请求失败，msg：' . $this->error[$arrRspBody['status']];
                    $output['status'] = 'Execute';//处理中
                    $output['failReason'] = $failReason;
                }
                return $output;
            }else{
                $output['status'] = 'Execute';//处理中
                $output['failReason'] = '第三方代付查询返回参数错误，' . $rspStr;
                return $output;
            }
        }else{
            $output['status'] = 'Execute';//处理中
            $output['failReason'] = '第三方代付查询请求失败，' . $rspStr;
            return $output;
        }
    }

    //支付接口请求
    public function getPayOrder($orderData){
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];
        if(empty($orderData['thirdUserId'])){
            $output['status'] = 'Fail';
            $output['failReason'] = 'thirdUserId不能为空';//即UPI账户
            return $output;
        }

        $path = "/upiCollect/{$this->params['cId']}";
        $params = [
            'payerVA' => $orderData['thirdUserId'],//用户付款 VPA 号
            'txnId' => $orderData['platformOrderNo'],//订单ID, 商户端生成
            'amount' => $orderData['orderAmount'],//收款金额,最多两位小数,大于 1.00
            'product' => '烟酒:'.date('YmdHis'),//商品单位
            'notifyUrl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),//回调地址
        ];

        $requestParams = $this->dataEncode($params);
        $this->logger->debug('向上游发起支付请求：' . $this->gateway . $path, $params);
        $response = Requests::post($this->gateway . $path, [], $requestParams, ['timeout' => $this->timeout, 'verify'=>false]);
        if ($response->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . $response->body;
            return $output;
        }

        //如果发生错误返回json字串，如果成功返回自动调转的html
        $rspBody = $response->body;
//        $rspBody = 'XMey/I2XLifaHAdWL7BrWu7voWwYXSC2pQ2GmEsF1mzlnU9CIs3A0BIGGEjn1GEUCP5DwO8uVuboz+NT8zuVfw503dFSqN8k+gokX5znSvuOf9OkgHeyWn5eF19brdQm';
//        {"status":"8888","message":"unknown error","retBizParams":null,"token":null,"sign":null}
//        {"status":"0000","message":"success","retBizParams":{"txnId":"P20190909101545928306","txnStatus":"PENDING","mpQueryId":"11125997217"},"token":null,"sign":null}
        $rspStr = $this->dataDecode($rspBody);
        $this->logger->debug('上游支付回复：[status_code]:' . $response->status_code . ', [resp_body]:' . $response->body.', [resp_json]:'.$rspStr);

        if (Tools::isJsonString($rspStr)) {
            $arrRspBody = json_decode($rspStr, true);
            if (isset($arrRspBody['status'])) {
                if($arrRspBody['status'] == '0000'){
                    $output['orderNo'] = $orderData['platformOrderNo'];
                    $output['status'] = 'Success';
                    $output['payUrl'] = $this->getHtmlToUrl($orderData['platformOrderNo'], $rspStr);
                    $output['failReason'] = $rspStr;
                }else{
                    $failReason = '第三方返回创建支付订单失败';
                    $failReason .= '，msg：' . $this->error[$arrRspBody['status']];
                    $output['status'] = 'Fail';
                    $output['failReason'] = $failReason;
                }
            }else{
                $output['status'] = 'Fail';
                $output['failReason'] = '第三方返回参数错误，' . $rspStr;
            }
            return $output;
        }else{
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败，' . $rspStr;
            return $output;
        }
    }

    public function successResponse($response){
        return $response->write("success");
    }

    //支付异步回调
    public function doCallback($orderData, $request){
//        $params = $request->getParams();
        $params_str = file_get_contents('php://input');
        if(empty($params_str)){
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => '',
                'orderAmount' => '',
                'failReason' => '回调报文为空',
            ];
        }
        $params_json = $this->dataDecode($params_str);
        if(empty($params_json)){
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => '',
                'orderAmount' => '',
                'failReason' => '解密失败',
            ];
        }
        $this->logger->debug('上游回调报文 enstr:'.$params_str.', params:'.$params_json);
        $params = json_decode($params_json, true);

        if ($params['txnStatus'] != 'SUCCESS') {//支付失败
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['txnId'],
                'orderAmount' => $params['payerAmount'],
                'failReason' => '付款失败！',
            ];
        }

        if ($orderData['platformOrderNo'] != $params['txnId']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['txnId'],
                'orderAmount' => $params['payerAmount'],
                'failReason' => '回调订单号异常:' . json_encode($params),
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => 'Success',
            'orderNo' => $params['txnId'],
            'orderAmount' => $params['payerAmount'],
            'failReason' => json_encode($params),
        ];
    }

    //订单主动查询功能
    public function queryOrder($platformOrderNo){
        // $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = "/queryUPICollectByTxnId/{$this->params['cId']}";

        $params = [
            'txnId' => $platformOrderNo,
        ];

        $requestParams = $this->dataEncode($params);
        $this->logger->debug('向上游发起支付请求：' . $this->gateway . $path, $params);
        $response = Requests::post($this->gateway . $path, [], $requestParams, ['timeout' => $this->timeout, 'verify'=>false]);
        if ($response->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方订单查询失败:' . $response->body;
            return $output;
        }

        $rspBody = $response->body;
        $rspStr = $this->dataDecode($rspBody);
        $this->logger->debug('上游支付回复：[status_code]:' . $response->status_code . ', [resp_body]:' . $response->body.', [resp_json]:'.$rspStr);
        if (Tools::isJsonString($rspStr)) {
            $res = json_decode($rspStr, true);
            if(!empty($res["retBizParams"])){
                $biz = $res["retBizParams"];
                return [
                    'status' => $biz['txnStatus'] == 'SUCCESS' ? 'Success' : 'Fail',
                    'orderAmount' => $biz['amount'],
                    'orderNo' => $biz['txnId'],
                    'failReason' => '第三方订单查询状态成功:'.$rspStr,
                ];
            }else{
                return [
                    'status' => 'Fail',
                    'orderAmount' => 0,
                    'orderNo' => '',
                    'failReason' => '支付订单状态未知:' . $rspStr,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方订单查询失败:' . $rspStr,
            ];
        }
    }

    //参数加密
    public function dataEncode($params){
        return base64_encode(openssl_encrypt(json_encode($params), 'aes-256-ecb', hex2bin($this->params['apiKey']), OPENSSL_RAW_DATA));
    }

    //参数解密
    public function dataDecode($str){
        $encrypted = base64_decode($str);
        return openssl_decrypt($encrypted, 'aes-256-ecb', hex2bin($this->params['apiKey']), OPENSSL_RAW_DATA);
    }

}
