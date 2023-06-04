<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class Lefutong extends Channels
{
    protected $forceBankBranch = [
        'CMB' => [
            'name' => '招商银行',
            'identifyCode' => '308124000011',
            'bankBranch' => '招商银行股份有限公司唐山分行',
        ],
        'ICBC' => [
            'name' => '工商银行',
            'identifyCode' => '102110005002',
            'bankBranch' => '中国工商银行股份有限公司天津市分行',
        ],
        'ABC' => [
            'name' => '农业银行',
            'identifyCode' => '103171069998',
            'bankBranch' => '中国农业银行股份有限公司忻州分行',
        ],
        'CCB' => [
            'name' => '建设银行',
            'identifyCode' => '105702080859',
            'bankBranch' => '中国建设银行股份有限公司六盘水市分行',
        ],
        'BOC' => [
            'name' => '中国银行',
            'identifyCode' => '104626180805',
            'bankBranch' => '中国银行广西百色分行',
        ],
        'SPDB' => [
            'name' => '浦发银行',
            'identifyCode' => '310305000013',
            'bankBranch' => '上海浦东发展银行苏州分行',
        ],
        'BCOM' => [
            'name' => '交通银行',
            'identifyCode' => '301164000018',
            'bankBranch' => '交通银行股份有限公司长治分行',
        ],
        'CMBC' => [
            'name' => '民生银行',
            'identifyCode' => '305302032013',
            'bankBranch' => '中国民生银行股份有限公司无锡分行',
        ],
        'GDB' => [
            'name' => '广发银行',
            'identifyCode' => '306501000019',
            'bankBranch' => '广发银行股份有限公司焦作分行',
        ],
        'CITIC' => [
            'name' => '中信银行',
            'identifyCode' => '302301032106',
            'bankBranch' => '中信银行南京分行',
        ],
        'HXB' => [
            'name' => '华夏银行',
            'identifyCode' => '304290042316',
            'bankBranch' => '华夏银行上海分行',
        ],
        'CIB' => [
            'name' => '兴业银行',
            'identifyCode' => '309205001034',
            'bankBranch' => '兴业银行股份有限公司鄂尔多斯分行',
        ],
        'GZCB' => [
            'name' => '广州银行',
            'identifyCode' => '313581003022',
            'bankBranch' => '广州银行股份有限公司黄沙大道支行',
        ],
        'UPOP' => [
            'name' => '中国银联',
            'identifyCode' => '905290000008',
            'bankBranch' => '中国银联股份有限公司',
        ],
        'JSB' => [
            'name' => '江苏银行',
            'identifyCode' => '313584008031',
            'bankBranch' => '江苏银行股份有限公司深圳宝安支行',
        ],
        'SRCB' => [
            'name' => '上海农村商业银行',
            'identifyCode' => '322290023019',
            'bankBranch' => '上海农村商业银行股份有限公司闸北支行',
        ],
        'BOB' => [
            'name' => '北京银行',
            'identifyCode' => '313110010182',
            'bankBranch' => '北京银行股份有限公司天津武清支行',
        ],
        'CBHB' => [
            'name' => '渤海银行',
            'identifyCode' => '318303000012',
            'bankBranch' => '渤海银行股份有限公司徐州分行',
        ],
        'BJRCB' => [
            'name' => '北京农商银行',
            'identifyCode' => '402100007307',
            'bankBranch' => '北京农村商业银行股份有限公司宣武支行',
        ],
        'NJCB' => [
            'name' => '南京银行',
            'identifyCode' => '313312508074',
            'bankBranch' => '南京银行股份有限公司江都支行',
        ],
        'CEB' => [
            'name' => '光大银行',
            'identifyCode' => '303591052771',
            'bankBranch' => '中国光大银行股份有限公司湛江分行',
        ],
        'BEA' => [
            'name' => '东亚银行',
            'identifyCode' => '502290000006',
            'bankBranch' => '东亚银行（中国）有限公司',
        ],
        'NBCB' => [
            'name' => '宁波银行',
            'identifyCode' => '313290010140',
            'bankBranch' => '宁波银行股份有限公司上海宝山支行',
        ],
        'HZB' => [
            'name' => '杭州银行',
            'identifyCode' => '313332020041',
            'bankBranch' => '杭州银行股份有限公司宁波科技支行',
        ],
        'PAB' => [
            'name' => '平安银行',
            'identifyCode' => '307584007980',
            'bankBranch' => '平安银行深圳分行',
        ],
        'HSB' => [
            'name' => '徽商银行',
            'identifyCode' => '319376074439',
            'bankBranch' => '徽商银行股份有限公司六安叶集支行',
        ],
        'CZB' => [
            'name' => '浙商银行',
            'identifyCode' => '316331000122',
            'bankBranch' => '浙商银行股份有限公司杭州萧东支行',
        ],
        'SHB' => [
            'name' => '上海银行',
            'identifyCode' => '325651056025',
            'bankBranch' => '上海银行股份有限公司成都分行',
        ],
        'PSBC' => [
            'name' => '中国邮政储蓄银行',
            'identifyCode' => '403161011006',
            'bankBranch' => '中国邮政储蓄银行股份有限公司太原市分行',
        ],
        'DLB' => [
            'name' => '大连银行',
            'identifyCode' => '313651084041',
            'bankBranch' => '大连银行股份有限公司成都九眼桥支行',
        ],
    ];

    protected function isBankSupported($bankCode)
    {
        return array_key_exists($bankCode, $this->forceBankBranch);
    }

    protected function getSettlementGatewayUrl()
    {
        $delimiter = '//';
        $len = \strlen($delimiter);
        $gatewayPrefix = 'settle.';

        $pos = strpos($this->gateway, $delimiter);
        if ($pos === false) {
            $gatewayUrl = $gatewayPrefix . $this->gateway;
        } else {
            $gatewayUrl = \substr($this->gateway, 0, $pos + $len) . $gatewayPrefix . \substr($this->gateway, $pos + $len);
        }

        return $gatewayUrl;
    }

    protected function getPayGatewayUrl()
    {
        $delimiter = '//';
        $len = \strlen($delimiter);
        $gatewayPrefix = 'api.';

        $pos = strpos($this->gateway, $delimiter);
        if ($pos === false) {
            $gatewayUrl = $gatewayPrefix . $this->gateway;
        } else {
            $gatewayUrl = \substr($this->gateway, 0, $pos + $len) . $gatewayPrefix . \substr($this->gateway, $pos + $len);
        }

        return $gatewayUrl;
    }

    protected function checkSign($params, $signKey)
    {
        if (!isset($params['signature'])) {
            return false;
        }

        $originSign = $params['signature'];
        $newParams = $params;
        unset($newParams['signature']);
        $sign = $this->createSign($newParams, $signKey);
        if (strtolower($originSign) != strtolower($sign)) {
            return false;
        }
        return true;
    }

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
            $originalString = implode('&', $sortParams) . '&' . $signKey;
        } else {
            $originalString = $signKey;
        }

        return md5($originalString);
    }

    public function getSettlementOrder($orderData)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/virtPay.do';

        if (!$this->isBankSupported($orderData['bankCode'])) {
            $output['status'] = 'Fail';
            $output['failReason'] = '该银行的银行卡不支持';

            return $output;
        }

        $params = [
            'notifyUrl' => $this->getSettlementCallbackUrl($orderData['platformOrderNo']),
            'cardno' => $this->params['virtualCardNo'],
            'traceno' => $orderData['platformOrderNo'],
            'amount' => $orderData['orderAmount'],
            'accountno' => Tools::decrypt($orderData['bankAccountNo']),
            'accountName' => $orderData['bankAccountName'],
            'mobile' => '13800000000',
            'certno' => '000000000000000000',
            'bankno' => $this->forceBankBranch[$orderData['bankCode']]['identifyCode'],
            'bankName' => $this->forceBankBranch[$orderData['bankCode']]['bankBranch'],
            'bankType' => $this->forceBankBranch[$orderData['bankCode']]['name'],
            'remark' => '实时提现',
        ];
        $paramsGbk = Tools::utf8ToGbk($params);

        $sign = md5('cardno=' . $paramsGbk['cardno'] . '&traceno=' . $paramsGbk['traceno'] . '&amount=' . $paramsGbk['amount'] . '&accountno=' . $paramsGbk['accountno'] . '&mobile=' . $paramsGbk['mobile'] . '&bankno=' . $paramsGbk['bankno'] . '&key=' . $this->params['apiSettlementKey']);
        $params['signature'] = $sign;
        $paramsGbk['signature'] = $sign;

        $this->logger->debug('向上游发起代付请求：' . $this->getSettlementGatewayUrl() . $path, $params);
        $rsp = Requests::post($this->getSettlementGatewayUrl() . $path, [], $paramsGbk, ['timeout' => $this->timeout]);
        $utf8RespBody = Tools::gbkToUtf8($rsp->body);
        $this->logger->debug('上游代付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $utf8RespBody);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Exception';
            $output['failReason'] = '第三方请求失败：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $utf8RespBody;
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        $res = json_decode($utf8RespBody, true);
        if ($res['respCode'] != '00') {
            $output['status'] = 'Fail';
            $output['failReason'] = '代付失败，' . (isset($res['respCode']) ? $res['respCode'] : '') . ':' . (isset($res['message']) ? $res['message'] : '') . ':' . (isset($res['payMsg']) ? $res['payMsg'] : '');
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        $resGbk = Tools::utf8ToGbk($res);
        if (!$this->checkSign($resGbk, $this->params['apiSettlementKey'])) {
            $output['status'] = 'Exception';
            $output['failReason'] = '返回数据验签失败：' . $utf8RespBody;
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        if ($res['transStatus'] == '3' || $res['payStatus'] == '3') {
            $output['status'] = 'Fail';
            $output['failReason'] = '代付失败，' . (isset($res['respCode']) ? $res['respCode'] : '') . ':' . (isset($res['message']) ? $res['message'] : '') . ':' . (isset($res['payMsg']) ? $res['payMsg'] : '');
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        $output['status'] = 'Success';
        $output['orderNo'] = $res['orderno'];
        $output['failReason'] = '';
        $output['orderAmount'] = $res['amount'];
        $output['pushChannelTime'] = date('YmdHis');

        return $output;
    }

    public function querySettlementOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/virtOrder.do';

        $params = [
            'cardno' => $this->params['virtualCardNo'],
            'traceno' => $platformOrderNo,
        ];

        $sign = md5('cardno=' . $params['cardno'] . '&traceno=' . $params['traceno'] . '&key=' . $this->params['apiSettlementKey']);
        $params['signature'] = $sign;

        $this->logger->debug('向上游发起代付查询请求：' . $this->getSettlementGatewayUrl() . $path, $params);
        $rsp = Requests::post($this->getSettlementGatewayUrl() . $path, [], $params, ['timeout' => $this->timeout]);
        $utf8RespBody = Tools::gbkToUtf8($rsp->body);
        $this->logger->debug('上游代付查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $utf8RespBody);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Exception';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $utf8RespBody;

            return $output;
        }

        $res = json_decode($utf8RespBody, true);
        if ($res['respCode'] != '00') {
            $output['status'] = 'Exception';
            $output['failReason'] = '第三方代付查询返回失败:' . $utf8RespBody;

            return $output;
        }

        $resGbk = Tools::utf8ToGbk($res);
        if (!$this->checkSign($resGbk, $this->params['apiSettlementKey'])) {
            $output['status'] = 'Exception';
            $output['failReason'] = '返回数据验签失败：' . $utf8RespBody;

            return $output;
        }

        //代付成功
        if ($res['transStatus'] == '2' && $res['payStatus'] == '2') {
            $output['status'] = 'Success';
            $output['orderNo'] = $res['orderno'];
            $output['failReason'] = '';
            $output['orderAmount'] = $res['amount'];

            return $output;
        }

        //代付失败
        if ($res['transStatus'] == '3' || $res['payStatus'] == '3') {
            $output['status'] = 'Fail';
            $output['orderNo'] = $res['orderno'];
            $output['failReason'] = '代付失败，' . (isset($res['respCode']) ? $res['respCode'] : '') . ':' . (isset($res['message']) ? $res['message'] : '') . ':' . (isset($res['payMsg']) ? $res['payMsg'] : '');
            $output['orderAmount'] = $res['amount'];

            return $output;
        }

        //否则仍需要确认
        $output['status'] = 'Execute';
        return $output;
    }

    public function doSettlementCallback($orderData, $request)
    {
        $params = $request->getParams();
        $paramsUtf8 = Tools::gbkToUtf8($params);
        if (!$this->checkSign($params, $this->params['apiSettlementKey'])) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => '',
                'orderAmount' => 0,
                'failReason' => '验签失败:' . json_encode($paramsUtf8),
            ];
        }

        if ($paramsUtf8['merchno'] != $this->params['cNo'] || $paramsUtf8['traceno'] != $orderData['platformOrderNo']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => '',
                'orderAmount' => 0,
                'failReason' => '回调订单号异常:' . json_encode($paramsUtf8),
            ];
        }

        //代付成功
        if ($paramsUtf8['transStatus'] == '2' && $paramsUtf8['payStatus'] == '2') {
            return [
                'status' => 'Success',
                'orderStatus' => 'Success',
                'orderNo' => $paramsUtf8['orderno'],
                'orderAmount' => $paramsUtf8['amount'],
                'channelServiceCharge' => $paramsUtf8['fee'],
                'failReason' => '代付回调代付成功：' . json_encode($paramsUtf8),
            ];
        }

        //代付失败
        if ($paramsUtf8['transStatus'] == '3' || $paramsUtf8['payStatus'] == '3') {
            return [
                'status' => 'Success',
                'orderStatus' => 'Fail',
                'orderNo' => $paramsUtf8['orderno'],
                'orderAmount' => $paramsUtf8['amount'],
                'channelServiceCharge' => $paramsUtf8['fee'],
                'failReason' => '代付失败，' . ($paramsUtf8['message'] ?? ''),
            ];
        }

        return [
            'status' => 'Fail',
            'orderStatus' => 'Fail',
            'orderNo' => '',
            'orderAmount' => 0,
            'failReason' => '代付回调代付状态未知：' . json_encode($paramsUtf8),
        ];
    }

    public function queryBalance()
    {
        $output = ['status' => '', 'balance' => 0, 'failReason' => ''];
        $path = '/balance.do';

        $params = [
            'cardno' => $this->params['virtualCardNo'],
        ];
        $sign = md5('cardno=' . $params['cardno'] . '&key=' . $this->params['apiSettlementKey']);
        $params['signature'] = $sign;

        $this->logger->debug('向上游发起余额查询请求：' . $this->getSettlementGatewayUrl() . $path, $params);
        $rsp = Requests::post($this->getSettlementGatewayUrl() . $path, [], $params, ['timeout' => $this->timeout]);
        $utf8RespBody = Tools::gbkToUtf8($rsp->body);
        $this->logger->debug('上游余额查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $utf8RespBody);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $utf8RespBody;

            return $output;
        }

        $res = json_decode($utf8RespBody, true);
        if ($res['respCode'] != '00') {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方余额查询失败：' . $utf8RespBody;

            return $output;
        }

        $resGbk = Tools::utf8ToGbk($res);
        if (!$this->checkSign($resGbk, $this->params['apiSettlementKey'])) {
            $output['status'] = 'Fail';
            $output['failReason'] = '返回数据验签失败：' . $utf8RespBody;

            return $output;
        }

        //成功
        $output['status'] = 'Success';
        $output['balance'] = $res['balance'];
        $output['failReason'] = '余额查询成功：' . $utf8RespBody;
        return $output;
    }
}
