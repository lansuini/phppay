<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class NmPay extends Channels
{
    protected $bankType = [
        'CMB' => [
            'code' => 'CMB',
            'name' => '招商银行',
        ],
        'ICBC' => [
            'code' => 'ICBC',
            'name' => '工商银行',
        ],
        'ABC' => [
            'code' => 'ABC',
            'name' => '农业银行',
        ],
        'CCB' => [
            'code' => 'CCB',
            'name' => '建设银行',
        ],
        'BOC' => [
            'code' => 'BOC',
            'name' => '中国银行',
        ],
        'SPDB' => [
            'code' => 'SPDB',
            'name' => '浦发银行',
        ],
        'BCOM' => [
            'code' => 'COMM',
            'name' => '交通银行',
        ],
        'CMBC' => [
            'code' => 'CMBC',
            'name' => '民生银行',
        ],
        'GDB' => [
            'code' => 'GDB',
            'name' => '广发银行',
        ],
        'CITIC' => [
            'code' => 'CITIC',
            'name' => '中信银行',
        ],
        'HXB' => [
            'code' => 'HXB',
            'name' => '华夏银行',
        ],
        'CIB' => [
            'code' => 'CIB',
            'name' => '兴业银行',
        ],
        'GZCB' => [
            'code' => 'GZB',
            'name' => '广州银行',
        ],
        'UPOP' => [
            'code' => 'CHINAPAY',
            'name' => '银联在线',
        ],
        'JSB' => [
            'code' => 'JSCB',
            'name' => '江苏银行',
        ],
        'SRCB' => [
            'code' => 'SRCB',
            'name' => '上海农商银行',
        ],
        'BOB' => [
            'code' => 'BOB',
            'name' => '北京银行',
        ],
        'CBHB' => [
            'code' => 'BHB',
            'name' => '渤海银行',
        ],
        'BJRCB' => [
            'code' => 'BJRCB',
            'name' => '北京农商银行',
        ],
        'NJCB' => [
            'code' => 'NJCB',
            'name' => '南京银行',
        ],
        'CEB' => [
            'code' => 'CEB',
            'name' => '光大银行',
        ],
        'BEA' => [
            'code' => 'HKBEA',
            'name' => '东亚银行',
        ],
        'NBCB' => [
            'code' => 'NBBANK',
            'name' => '宁波银行',
        ],
        'HZB' => [
            'code' => 'HZCB',
            'name' => '杭州银行',
        ],
        'PAB' => [
            'code' => 'SPABANK',
            'name' => '平安银行',
        ],
        'HSB' => [
            'code' => 'HSBANK',
            'name' => '徽商银行',
        ],
        'CZB' => [
            'code' => 'ZSB',
            'name' => '浙商银行',
        ],
        'SHB' => [
            'code' => 'SHBANK',
            'name' => '上海银行',
        ],
        'PSBC' => [
            'code' => 'PSBC',
            'name' => '邮储银行',
        ],
        'DLB' => [
            'code' => 'DLB',
            'name' => '大连银行',
        ],
    ];

    protected function createSign($params, $signKey)
    {
        $newParams = array_filter($params);
        if (!empty($newParams)) {
            $fields = array_keys($newParams);
            $sortParams = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                $sortParams[] = $v . '=' . $newParams[$v];
            }
            $originalString = implode('&', $sortParams) . '&key=' . $signKey;
        } else {
            $originalString = 'key=' . $signKey;
        }

        return strtoupper(sha1($originalString));
    }

    public function successResponse($response)
    {
        return $response->write('success');
    }

    public function getPayOrder($orderData)
    {
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];

        $path = '/openapi-gateway/h5Pay';
        $params = [
            'merchant_id' => $this->params['cNo'],
            'order_time' => date("Y-m-d H:i:s"),
            'order_no' => $orderData['platformOrderNo'],
            'subject' => '商品描述',
            'order_amount' => $orderData['orderAmount'],
            'timeout_express' => '60m',
            'bank_type' => 'CUP',
            'notify_url' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'bank_accno' => '',
            'user_id' => '666888',
        ];

        $params['sign'] = $this->createSign($params, $this->params['apiKey']);
        $this->logger->debug('向上游发起支付请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug('上游支付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . $rsp->body;
            return $output;
        }

        //如果发生错误返回json字串，如果成功返回自动调转的html
        $rspBody = trim($rsp->body);
        if (Tools::isJsonString($rspBody)) {
            $arrRspBody = json_decode($rspBody, true);
            $failReason = '第三方返回创建支付订单失败';
            if (isset($arrRspBody['order_rsp']['return_code'])) {
                $failReason .= '，code：' . $arrRspBody['order_rsp']['return_code'];
            }

            if (isset($arrRspBody['order_rsp']['return_msg'])) {
                $failReason .= '，msg：' . $arrRspBody['order_rsp']['return_msg'];
            }

            $output['status'] = 'Fail';
            $output['failReason'] = $failReason;
            return $output;
        }

        if (!Tools::isHtmlString($rspBody)) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方返回参数错误，' . $rspBody;
            return $output;
        }

        $output['status'] = 'Success';
        $output['payUrl'] = $this->getHtmlToUrl($orderData['platformOrderNo'], $rspBody);
        return $output;
    }

    public function doPayCallback($orderData, $request)
    {
        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];
        $param = file_get_contents("php://input");
        $param = trim($param);
        if (!Tools::isJsonString($param)) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调返回参数格式错误';

            return $output;
        }

        $arrParam = json_decode($param, true);
        if (!isset($arrParam['sign']) || !isset($arrParam['notify_msg']) || !Tools::isJsonString($arrParam['notify_msg'])) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调请求参数缺少必要参数';

            return $output;
        }

        $sign = strtoupper(sha1($arrParam['notify_msg'] . '&key=' . $this->params['apiKey']));
        if ($sign != strtoupper($arrParam['sign'])) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调验签失败';

            return $output;
        }

        $arrNotifyMsg = json_decode($arrParam['notify_msg'], true);
        if (isset($arrNotifyMsg['merchant_id']) && $arrNotifyMsg['merchant_id'] != $this->params['cNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户号与订单不符';
            return $output;
        }

        if (isset($arrNotifyMsg['order_no']) && $arrNotifyMsg['order_no'] != $orderData['platformOrderNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户订单号与订单不符';
            return $output;
        }

        $output['status'] = 'Success';
        $output['orderStatus'] = (isset($arrNotifyMsg['trade_status']) && strtoupper($arrNotifyMsg['trade_status']) == 'SUCCESS') ? 'Success' : 'Fail';
        $output['orderNo'] = $arrNotifyMsg['trade_no'] ?? '';
        $output['orderAmount'] = $arrNotifyMsg['order_amount'] ?? 0;
        $output['failReason'] = '';

        return $output;
    }

    public function getSettlementOrder($orderData)
    {
        global $app;
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/openapi-gateway/remit';

        $bankCode = $app->getContainer()->code['bankCode'];
        $params = [
            'merchant_id' => $this->params['cNo'],
            'order_time' => date("Y-m-d H:i:s"),
            'order_no' => $orderData['platformOrderNo'],
            'notify_url' => $this->getSettlementCallbackUrl($orderData['platformOrderNo']),
            'subject' => empty($orderData['orderReason']) ? '转账' : $orderData['orderReason'],
            'order_amount' => $orderData['orderAmount'],
            'bank_account' => Tools::decrypt($orderData['bankAccountNo']),
            'bank_name' => $bankCode[$orderData['bankCode']] ?? '',
            'bank_branch' => $orderData['bankName'],
            'bank_province' => $orderData['province'],
            'bank_city' => $orderData['city'],
            'user_name' => $orderData['bankAccountName'],
            'card_type' => '01',
            'bank_telephone_no' => '13800000000',
            'bank_type' => $this->bankType[$orderData['bankCode']]['code'] ?? '',
        ];

        $params['sign'] = $this->createSign($params, $this->params['apiKey']);

        $this->logger->debug('向上游发起代付请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug('上游代付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Success';
            $output['failReason'] = '第三方请求失败：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body);
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        $res = json_decode($rsp->body, true);
        // $sign = strtoupper(sha1(json_encode($res['order_rsp'], JSON_UNESCAPED_UNICODE) . '&key=' . $this->params['apiKey']));
        // if ($res['sign'] != $sign) {
        //     $output['status'] = 'Exception';
        //     $output['failReason'] = '返回数据验签失败：' . trim($rsp->body);
        //     $output['pushChannelTime'] = date('YmdHis');

        //     return $output;
        // }

        if ($res['order_rsp']['return_code'] != '10000') {
            $output['status'] = 'Fail';
            $output['failReason'] = '代付失败，' . ($res['order_rsp']['return_code'] ?? '') . ':' . ($res['order_rsp']['return_msg'] ?? '');
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        if (strtoupper($res['order_rsp']['trade_status']) == 'FAILED') {
            $output['status'] = 'Fail';
            $output['failReason'] = '代付失败，' . ($res['order_rsp']['return_code'] ?? '') . ':' . ($res['order_rsp']['return_msg'] ?? '');
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        $output['status'] = 'Success';
        $output['orderNo'] = $res['order_rsp']['trade_no'] ?? '';
        $output['failReason'] = '';
        $output['orderAmount'] = $res['order_rsp']['order_amount'];
        $output['pushChannelTime'] = date('YmdHis');

        return $output;
    }

    public function doSettlementCallback($orderData, $request)
    {
        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];
        $param = file_get_contents("php://input");
        $param = trim($param);
        if (!Tools::isJsonString($param)) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调返回参数格式错误';

            return $output;
        }

        $arrParam = json_decode($param, true);
        if (!isset($arrParam['sign']) || !isset($arrParam['notify_msg']) || !Tools::isJsonString($arrParam['notify_msg'])) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调请求参数缺少必要参数';

            return $output;
        }

        $sign = strtoupper(sha1($arrParam['notify_msg'] . '&key=' . $this->params['apiKey']));
        if ($sign != strtoupper($arrParam['sign'])) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调验签失败';

            return $output;
        }

        $arrNotifyMsg = json_decode($arrParam['notify_msg'], true);
        if (isset($arrNotifyMsg['merchant_id']) && $arrNotifyMsg['merchant_id'] != $this->params['cNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户号与订单不符';
            return $output;
        }

        if (isset($arrNotifyMsg['order_no']) && $arrNotifyMsg['order_no'] != $orderData['platformOrderNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户订单号与订单不符';
            return $output;
        }

        if (isset($arrNotifyMsg['trade_status']) && strtoupper($arrNotifyMsg['trade_status']) == 'SUCCESS') {
            $output['status'] = 'Success';
            $output['orderStatus'] = 'Success';
        } elseif (isset($arrNotifyMsg['trade_status']) && strtoupper($arrNotifyMsg['trade_status']) == 'FAILUR') {
            $output['status'] = 'Success';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '失败原因未知，请联系第三方确认';
        } else {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调通知缺少trade_status或状态未知';
        }

        $output['orderNo'] = $arrNotifyMsg['trade_no'] ?? '';
        $output['orderAmount'] = $arrNotifyMsg['order_amount'] ?? 0;
        $output['channelServiceCharge'] = $arrNotifyMsg['fee'] ?? 0;

        return $output;
    }

    public function querySettlementOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/openapi-basis-gateway/queryDf';

        $params = [
            'merchant_id' => $this->params['cNo'],
            'order_no' => $platformOrderNo,
            'order_time' => date('Y-m-d H:i:s'),
        ];

        $params['sign'] = $this->createSign($params, $this->params['apiKey']);

        $this->logger->debug('向上游发起代付查询请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug('上游代付查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body;

            return $output;
        }

        $strBody = trim($rsp->body);
        $res = json_decode($strBody, true);
        if (!isset($res['order_rsp']['return_code']) || $res['order_rsp']['return_code'] != '10000') {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方代付查询返回失败:' . $strBody;

            return $output;
        }

        if (isset($res['order_rsp']['data']['tradeStatus']) && strtoupper($res['order_rsp']['data']['tradeStatus']) == 'SUCCESS') {
            $output['status'] = 'Success';
            $output['failReason'] = $res['order_rsp']['data']['tradeStatusDesc'] ?? '';
        } elseif (isset($res['order_rsp']['data']['tradeStatus']) && strtoupper($res['order_rsp']['data']['tradeStatus']) == 'FAILED') {
            $output['status'] = 'Fail';
            $output['failReason'] = ($res['order_rsp']['data']['tradeStatusDesc'] ?? '') . '，失败原因未知，请联系第三方确认';
        } else {
            $output['status'] = 'Execute';
            $output['failReason'] = $res['order_rsp']['data']['tradeStatusDesc'] ?? '';
        }

        $output['orderNo'] = $res['order_rsp']['data']['tradeNo'] ?? '';
        $output['orderAmount'] = $res['order_rsp']['data']['orderAmount'] ?? 0;

        return $output;
    }

    public function queryBalance()
    {
        $output = ['status' => '', 'balance' => 0, 'failReason' => ''];
        $path = '/openapi-basis-gateway/accountQuery';

        $params = [
            'merchant_id' => $this->params['cNo'],
            'order_time' => date('Y-m-d H:i:s'),
        ];

        $params['sign'] = $this->createSign($params, $this->params['apiKey']);

        $this->logger->debug('向上游发起余额查询请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug('上游余额查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body));
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body);

            return $output;
        }

        $strBody = trim($rsp->body);
        $res = json_decode($strBody, true);
        if (!isset($res['order_rsp']['return_code']) || $res['order_rsp']['return_code'] != '10000') {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方余额查询失败：' . $strBody;

            return $output;
        }

        $balance = 0;
        foreach ($res['order_rsp']['data'] ?? [] as $data) {
            if (isset($data['account_flag']) && strtoupper($data['account_flag']) == 'D0') {
                $balance = $data['available_amt'];
                break;
            }
        }

        //成功
        $output['status'] = 'Success';
        $output['balance'] = $balance;
        $output['failReason'] = '余额查询成功：' . $strBody;
        return $output;
    }
}
