<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class TianciPay extends Channels
{

    protected function getSignStr($params)
    {
        ksort($params);
        $str='';
        foreach ($params as $key=>$val) {
            if( (is_numeric($val) && $val ===0) || ($val!='' && $val!=null)){
                $str.= $key .'='.$val . '&';
            }
        }

        return rtrim($str,'&');
    }

    public function getPayOrder($orderData)
    {
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];

        $path = '/api/transaction';
        $params = [
//            'merchantNo' => $this->params['merchantNo'],
            'out_trade_no' => $orderData['platformOrderNo'],
            'amount' => $orderData['orderAmount'],
            'callback_url' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            //            'paid_name' => 288876688,//实名电话验证用，非必要请勿填写

        ];
//        print_r($signStr);exit;
        $this->logger->debug('TianciPay支付请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);
//        print_r(json_decode($rsp->body, true));
        $this->logger->debug('TianciPay支付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = 'TianciPay支付请求失败:' . $rsp->body;
            return $output;
        }

        if ($rsp->status_code == 200) {
            $res = json_decode($rsp->body, true);
            if (isset($res['success']) && $res['success']  && isset($res['data'])) {

                return [
                    'status' => 'Success',
                    'payUrl' => $res['data']['uri'],
                    'orderNo' => $orderData['platformOrderNo'],
                    'failReason' => $rsp->body,
                ];

            } else {
                return [
                    'status' => 'Fail',
                    'payUrl' => '',
                    'orderNo' => '',
                    'failReason' => 'TianciPay支付请求失败:' . $rsp->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'payUrl' => '',
                'orderNo' => '',
                'failReason' => 'TianciPay支付请求失败:' . $rsp->body,
            ];
        }
    }

    public function queryPayOrder($platformOrderNo){

        $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/api/transaction/'.$platformOrderNo;

        $headers = $this->getHeaderParams();
        $req = Requests::get($this->gateway . $path, $headers, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($res['success'] && isset($res['data']['state']) && $res['data']['state'] == 'completed') {
                return [
                    'status' => 'Success',
                    'orderAmount' => $res['data']['amount'],
                    'orderNo' => $res['data']['trade_no'],
                    'failReason' => $res->body,
                ];
            } else {
                return [
                    'status' => 'Fail',
                    'orderAmount' => 0,
                    'orderNo' => '',
                    'failReason' => '第三方请求失败:' . $req->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function doPayCallback($orderData, $request)
    {
        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];

        $params = $request->getParams();

        if(!$params){
            $output['failReason'] = '回调数据为空';
            return $output;
        }


        if (isset($params['out_trade_no']) && $params['out_trade_no'] != $orderData['platformOrderNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户订单号与订单不符';
            return $output;
        }

        $callbackSign = $params['sign'];
        unset($params['sign']);
        $signStr = $this->getSignStr($params);

        $genSign = $this->getSign($signStr,$this->params['api_token'],$this->params['notify_token']);
        if ($genSign != $callbackSign) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调验签失败';

            return $output;
        }
        $output['status'] = 'Success';
        $output['orderStatus'] = (isset($params['state']) && $params['state'] == 'completed') ? 'Success' : 'Fail';
        $output['orderNo'] = $params['trade_no'] ?? '';
        $output['orderAmount'] = $params['amount'] ?? 0;
        $output['failReason'] = '';

        return $output;
    }

    public function getSettlementOrder($orderData)
    {
        global $app;
//        $bankCode = $app->getContainer()->code['bankCode'];
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/api/payment';
        $params = [
//            'merchantNo' => $this->params['merchantNo'],
            'out_trade_no' => $orderData['platformOrderNo'],
            'amount' => round($orderData['orderAmount'],2),
            'bank_id' => 'GCASH',
            'bank_owner' =>$orderData['bankAccountName'],
            'account_number' => Tools::decrypt($orderData['bankAccountNo']),
            'callback_url' => $this->getSettlementCallbackUrl($orderData['platformOrderNo']),

        ];
        $signStr = $this->getSignStr($params);

        $params['sign'] = $this->getSign($signStr, $this->params['api_token'], $this->params['notify_token']);

        $this->logger->debug('向TianciPay代付请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);
        $this->logger->debug('TianciPay代付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        $res = json_decode($rsp->body, true);
        if ($rsp->status_code != 200 && isset($res['success']) && !$res['success']) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败：[http_code]:' . $rsp->status_code  . ', [resp_body]:' . trim($rsp->body);
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        if ($res['success'] != '200') {
            $output['status'] = 'Fail';
            $output['failReason'] = 'TianciPay代付失败，' . ($res['status_code'] ?? '') . ':' . ($res['message'] ?? '');
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }


        $output['status'] = 'Success';
        $output['orderNo'] = $res['data']['trade_no'] ?? '';
        $output['failReason'] = '';
        $output['orderAmount'] = $res['data']['amount'];
        $output['pushChannelTime'] = date('YmdHis');

        return $output;
    }

    public function doSettlementCallback($orderData, $request)
    {
        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];
        $arrParam = $request->getParams();

        if (!isset($arrParam['sign']) || !isset($arrParam['out_trade_no']) || !isset($arrParam['amount'])) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调请求参数缺少必要参数';

            return $output;
        }

        if (isset($arrParam['out_trade_no']) && $arrParam['out_trade_no'] != $orderData['platformOrderNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户订单号与订单不符';
            return $output;
        }

        $callbackSign = $arrParam['sign'];
        unset($arrParam['sign']);
        $signStr = $this->getSignStr($arrParam);
        $genSign = $this->getSign($signStr,$this->params['api_token'],$this->params['notify_token']);
        if ($genSign != $callbackSign) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调验签失败';

            return $output;
        }

        if (isset($arrParam['state']) && $arrParam['state'] == 'completed') {
            $output['status'] = 'Success';
            $output['orderStatus'] = 'Success';
        } elseif (isset($arrParam['state']) && ($arrParam['state'] == 'failed' || $arrParam['state'] == 'reject' || $arrParam['state'] == 'refund')) {
            $output['status'] = 'Success';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '失败原因未知，请联系第三方确认';
        } else {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Execute';
            $output['failReason'] = '回调通知缺少state或处理中';
        }

        $output['orderNo'] = $arrParam['out_trade_no'] ?? '';
        $output['orderAmount'] = $arrParam['amount'] ?? 0;
        $output['channelServiceCharge'] = 0;

        return $output;
    }

    public function querySettlementOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = "/api/payment/{$platformOrderNo}";

        $this->logger->debug('Tianci代付查询请求：' . $this->gateway . $path, []);
        $rsp = Requests::get($this->gateway . $path ,$this->getHeaderParams() , ['timeout' => $this->timeout]);

        $this->logger->debug('Tianci代付订单查询回复：'. $platformOrderNo .'[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);

        if ($rsp->status_code != 200) {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body;

            return $output;
        }

        $strBody = trim($rsp->body);

        $res = json_decode($strBody, true);
        if (!isset($res['success']) ) {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方代付查询返回失败:' . $strBody;

            return $output;
        }
        if (isset($res['success']) && !$res['success'] && (isset($res['status_code']) && $res['status_code']==404) && (isset($res['message']) && $res['message']=="Not Found") ) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方代付查询返回:Not Found（订单不存在）';

            return $output;
        }


        if (isset($res['data']['state']) && ($res['data']['state'] == 'completed')) {
            $output['status'] = 'Success';
            $output['failReason'] = '';
        } elseif (isset($res['data']['state']) && ($res['data']['state'] == 'failed' || $res['data']['state']=='reject' || $res['data']['state']=='refund')) {
            $output['status'] = 'Fail';
            $output['failReason'] =  '失败原因未知，请联系第三方确认';
        } else {
            $output['status'] = 'Execute';
            $output['failReason'] = '';
        }

        $output['orderNo'] = $res['data']['trade_no'] ?? '';//上游订单号
        $output['orderAmount'] = $res['data']['amount'] ?? 0;

        return $output;
    }

    public function queryBalance()
    {
        $output = ['status' => '', 'balance' => 0, 'failReason' => ''];
        $path = '/api/balance/inquiry';

        $headers = $this->getHeaderParams();

        $this->logger->debug('向TianciPay发起余额查询请求：' . $this->gateway . $path);
        $rsp = Requests::get($this->gateway . $path, $headers, ['timeout' => $this->timeout]);
        $this->logger->debug('上游TianciPay余额查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body));
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body);

            return $output;
        }

        $strBody = trim($rsp->body);
        $res = json_decode($strBody, true);
        if (!isset($res['success']) || !$res['success'] || !isset($res['data']['balance'])) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方余额查询失败：' . $strBody;

            return $output;
        }

        $balance = isset($res['data']['balance']) ? $res['data']['balance'] : 0;
        //成功
        $output['status'] = 'Success';
        $output['balance'] = $balance;
        $output['failReason'] = '余额查询成功：' . $strBody;
        return $output;
    }

    public function getHeaderParams()
    {
        return ['Authorization' => 'Bearer ' . $this->params['api_token'], 'Content-Type' => 'application/json'];
    }

    private function getSign($signStr , $apiToken, $notifyToken){

        return md5($signStr.$apiToken.$notifyToken);
    }
}
