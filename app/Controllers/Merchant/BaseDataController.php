<?php

namespace App\Controllers\Merchant;

// use App\Controllers\Controller;

use App\Models\MerchantChannelSettlement;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BaseDataController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        $result = [];
        $items = $request->getParam('requireItems');
        $items = explode(',', $items);
        foreach ($items ?? [] as $item) {
            if (isset($this->c->code[$item])) {
                $result[$item] = [];
                if ($item == 'channel') {
                    foreach ($this->c->code[$item] as $k => $v) {
                        if (!$v['open']) {
                            continue;
                        }
                        $result[$item][] = ['key' => $k, 'value' => $v['name']];
                    }
                } else {
                    foreach ($this->c->code[$item] as $k => $v) {
                        $result[$item][] = ['key' => $k, 'value' => $v];
                    }
                }

            } else {
                return $response->withJson([
                    'result' => $item,
                    'success' => 0,
                ]);
            }
        }
//        if(isset($result['bankCode'])) {
//            foreach ($result['bankCode'] as $k => $bank) {
//                if($bank['key'] == 'ALIPAY') {
//                    unset($result['bankCode'][$k]);
//                }
//            }
//        }
        return $response->withJson([
            'result' => $result,
            'success' => 1,
        ]);
    }

    public function merchantChannel(Request $request, Response $response, $args){

        $code = $this->c->code;
        $model = new MerchantChannelSettlement();


        $result = $model->where('merchantNo', $_SESSION['merchantNo'])
            ->get(['setId','merchantId','merchantNo','channel as key','channelMerchantId','channelMerchantNo','accountBalance'])
            ->toArray();
        foreach ($result as &$v){
            $v['value'] = isset($code['channel'][$v['key']]) ? $code['channel'][$v['key']]['name'] : $v['key'];
        }
        return $response->withJson([
            'result' => ['channel'=>$result],
            'success' => 1,
        ]);
    }

    public function rechargeOrder(Request $request, Response $response, $args)
    {
        $result['rechargeOrderStatus'] = [
            ['key'=> "Transfered", 'value'=> "待支付"],
            ['key'=> "Success", 'value'=> "充值成功"],
            ['key'=> "Fail", 'value'=> "充值失败"],
            ['key'=> "Exception", 'value'=>"异常"]
        ];

        $result['rechargeOrderPayType'] = [

            ['key'=> "EnterpriseEBank", 'value'=> "企业网银"],
            ['key'=> "PersonalEBank", 'value'=> "个人网银"],
            ['key'=> "PersonalEBankDNA", 'value'=> "个人网银DNA"],
            ['key'=> "AlipayEBank", 'value'=> "支付宝网银"],
        ];


        return $response->withJson([
            'result' => $result,
            'success' => 1,
        ]);
    }
}
