<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

/**
 * 多钱支付
 * Class DQPay
 * @package App\Channels\Lib
 */
class Dqpay extends Channels
{
    protected $supportRechargeType = [
        'EnterpriseEBank' => 'getEpEBankRechargeOrder',//企业网银
        'PersonalEBank' => 'getPsEBankRechargeOrder',//个人网银
    ];

    public function getPayOrder($orderData)
    {
    }

    public function successResponse($response)
    {
        return $response->write("success");
    }

    public function doCallback($orderData, $request)
    {
    }

    public function queryOrder($platformOrderNo)
    {
    }

    /*============================= 代付-- start ==============================*/
    public function getSettlementOrder($orderData)
    {
        $params = [
            'merchant_no' => $this->params['cId'],
            'merchant_order' => $orderData['platformOrderNo'],
            'amount' => sprintf("%.2f", $orderData['orderAmount']),
            'bank_code' => $this->getBankCode($orderData['bankCode']),
            'receiver_type' => '1',
            'receiver_account' => Tools::decrypt($orderData['bankAccountNo']),
            'receiver_name' => $orderData['bankAccountName'],
            'cert_type' => "1",
            'cert_no' => "440032198700001687",//身份证号码
            'phone_no' => "13000000550",//手机号码
            'bank_branch' => $orderData['bankName'],
            'province' => $orderData['province'],
            'city' => $orderData['city'],
//            'notify_url' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
        ];
        $params['sign'] = $this->getSign($params, $this->params['apiKey']);

        $result = [
            'status' => 'Success',
            'orderAmount' => $orderData['orderAmount'],
            'orderNo' => '',
            'failReason' => '',
        ];


        $req = Requests::post($this->gateway . '/v1/trade/remit', [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug($this->gateway, ['params' => $params, 'result' => json_decode($req->body, true)]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);

            if (isset($res['result_code']) && ($res['result_code'] == '200')) {
                switch ($res['status']) {
                    case 0:
                    case 4:
                        $result = [
                            'status' => 'Fail',
                            'orderAmount' => $res['amount'],
                            'orderNo' => '',
                            'failReason' => '第三方返回失败:' . $req->body,
                        ];
                        break;
                    default:
                        $result['orderNo']=$res['merchant_order'];
                        $result['failReason']=$req->body;
                }
            } else {
                $result['status']= 'Fail';
                $result['failReason']= '第三方请求失败:' . $req->body;
            }
        } else {
            $result['status']= 'Fail';
            $result['failReason']= '第三方请求失败:' . $req->body;
        }
        return $result;
    }

    public function queryBalance()
    {
        $output = ['status' => '', 'failReason' => '', 'balance' => 0];
        $params = [
            'merchant_no' => $this->params['cId'],
            'query_time' => date('Y-m-d H:i:s'),
        ];
        $params['sign'] = $this->getSign($params, $this->params['apiKey']);
        $req = Requests::post($this->gateway.'/v1/trade/balance', [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug($this->gateway, ['params' => $params, 'result' => $req]);
        if ($this->status_code = 200) {
            $res = json_decode($req->body, true);
            $this->logger->debug("查询余额回复 ：", $res);
            if ($res['result_code'] && $res['result_code'] == '200') {
                $output['status'] = 'Success';
                $output['balance'] = $res['balance'];
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '查询失败：result_code:' . $res['result_code'] . ';respDesc：' . $res['result_message'];
            }
        } else {
            $output['status'] = 'Fail';
            $output['failReason'] = '请求第三方失败' . $req->body;
        }
        return $output;
    }

    public function querySettlementOrder($platformOrderNo = '')
    {
//        $merchantOrderNo=
        $params = [
            'merchant_no' => $this->params['cId'],
            'merchant_order' => $platformOrderNo,
            'query_time' => date('Y-m-d H:i:s'),
        ];
        $params['sign'] = $this->getSign($params, $this->params['apiKey']);
        $req = Requests::post($this->gateway . '/v1/trade/query_remit', [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug("查询代付订单 $platformOrderNo 回复 ：", json_decode($req->body, true));
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['result_code']) && $res['result_code'] == '200') {
                $arr = [
                    'status' => 'Success',
                    'orderAmount' => $res['amount'] ?? 0,
                    'orderNo' => $platformOrderNo,
                    'failReason' => $req->body,
                ];
                switch ($res['status']) {
                    case '1':
                    case '2':
                        $arr['status'] = 'Execute';
                        $arr['failReason'] = '订单处理中：status:' . $res['status'] . '；返回内容：' . $req->body;
                        break;
                    case '3':
                        $arr['status'] = 'Success';
                        break;
                    case '0':
                    case '4':
                        $arr['status'] = 'Fail';
                        $arr['failReason'] = '订单处理失败：status:' . $res['status'] . '；返回内容：' . $req->body;
                        break;
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

    public function getSign($params, $apiKey)
    {
        ksort($params);
        $queryStr = '';
        foreach ($params as $key => $param) {
            if ("sign" != $key && "" != $param) {
                $queryStr .= $key . "=" . $param . "&";
            }

        }
        $queryStr .= "secret=$apiKey";
        return strtoupper(md5($queryStr));
    }


    public function getBankCode($code)
    {
        $arr = [
            'ICBC' => 'ICBC',
            'ABC' => 'ABC',
            'CCB' => 'CCB',
            'BOC' => 'BOC',
            'CMB' => 'CMB',
            'BCOM' => 'COMM',//不一样
            'CMBC' => 'CMBC',
            'CEB' => 'CEB',
            'SPDB' => 'SPDB',
            'CIB' => 'CIB',
            'CITIC' => 'CITIC',
            'PSBC' => 'PSBC',
            'PAB' => 'PAB',
            'GDB' => 'CGB',//不一样
            'SHB' => 'SHBANK',//不一样
            'BOB' => 'BJBANK',
            'HXB' => 'HXBANK',
            'SRCB' => 'SHRCB',
            'BJRCB' => 'BJRCB',
            'CBHB' => 'BOHAIB',
            'GZCB' => 'GCB',
            'JSB' => 'JSBANK',
            'NJCB' => 'NJCB',
            'BEA' => 'HKBEA',
            'NBCB' => 'NBBANK',
            'HZB' => 'HZCB',
            'HSB' => 'HSBANK',
            'CZB' => 'CZBANK',
            'DLB' => 'DLB',
            'UPOP' => 'UNION'
        ];

        return isset($arr[$code]) ? $arr[$code] : 'UNKNOWN';
    }

    public function getRechargeOrder($orderData){

        $return = ['status'=>'Fail','payUrl'=>'','failReason'=>'','orderNo'=>''];

        if(!isset($this->supportRechargeType[$orderData['payType']])){
            $return['status'] = 'Exception';
            $return['failReason'] = "渠道不支持{$orderData['payType']}";
            return $return;
        }

        $rechargeAction = $this->supportRechargeType[$orderData['payType']];
        try{
            $return['payUrl'] = $this->$rechargeAction($orderData);
            $return['status'] = 'Success';
            $this->logger->debug("充值发起成功：", $orderData);
        }catch (\Exception $e){
            $this->logger->debug("充值发起失败：", $orderData);
            $return['status'] = 'Exception';
        }
        return $return ;
    }

    //个人网银
    public function getPsEBankRechargeOrder($rechargeData){
        return $this->getEBankRechargeOrder($rechargeData,'Ps');
    }

    //企业网银
    public function getEpEBankRechargeOrder($rechargeData){
        return $this->getEBankRechargeOrder($rechargeData,'Ep');
    }

    /**
     * 拼接form表单
     * @param array $rechargeData
     * @param string $type
     */
    private function getEBankRechargeOrder($rechargeData = [] , $type = 'Ps'){


        $data = [];

        $data['merchant_no'] = $this->params['cId'];
        $data['sett_type'] = 'T0';
        $data['trade_way'] = $type == 'Ps' ? 'b2c' : 'b2b' ;
        $data['merchant_order'] = $rechargeData['platformOrderNo'];
        $data['amount'] = $rechargeData['orderAmount'];
        $data['bank_code'] = 'T0';
        $data['return_url'] = '';
        $data['notify_url'] = $this->getRechargeCallbackUrl($rechargeData['platformOrderNo']);
        $data['additional'] = '';
        $data['sign'] = $this->getSign($data, $this->params['apiKey']);

        //建立请求
        $url = $this->params['gateway'] . '/v1/trade/ebank';
        $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $sHtml .= "<form id='paysubmit' name='paysubmit' action='".$url."' method='post'>";
        foreach ($data as $key => $value){
            $sHtml.= "<input type='hidden' name='".$key."' value='".$value."'/>";
        }
        $sHtml = $sHtml."<input type='submit' value='' style='display:none;'></form>";
        $sHtml = $sHtml."loading...";
        $sHtml = $sHtml."<script>document.forms['paysubmit'].submit();</script>";

        return  $this->getHtmlToUrl($rechargeData['platformOrderNo'], $sHtml);

    }

    public function doRechargeCallBack($orderData, $request){

        $params = $request->getParams();
        $return = [
            'status' => 'Fail',
            'orderStatus' => '',
            'orderNo' => $params['merchant_order'],
            'orderAmount' => $params['paid'],
            'failReason' => '',
            'outputType' => 'string',
            'output' => 'SUCCESS'
        ];

        $this->logger->debug('doRechargeCallBack：',$params);
        $this->logger->debug('doRechargeCallBack：',$orderData);

        if ($params['sign'] != $this->getSign($params,$this->params['apiKey'])) {

            $return['orderStatus'] = '';
            $return['failReason'] = '验签失败';
            $return['orderNo'] = $params['merchant_order'];
            return $return ;
        }

        if ($orderData['orderStatus'] == 'Success' || $orderData['orderStatus'] == 'Fail') {
            $return['failReason'] = $orderData['platformOrderNo']. '已回调！';
            $return['orderStatus'] = $orderData['orderStatus'];
            return $return;
        }

        if (in_array($params['status'],['1','3'])) {

            $return['orderStatus'] = '';
            $return['failReason'] = '待支付';
            $return['orderNo'] = $params['merchant_order'];
            return $return ;
        }


        if($params['status'] == '2' ) {

            $return['status'] = 'Success';
            $return['orderStatus'] = 'Success';
            $return['failReason'] = '支付成功';
            $return['orderNo'] = $params['merchant_order'];
            return $return ;
        }

        $return['failReason'] = $params['status'];
        $return['status'] = 'Fail';
        $return['orderStatus'] = 'Fail';

        return $return ;

    }

}
