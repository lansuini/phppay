<?php

namespace App\Controllers\GM;

use App\Controllers\Controller;
use Psr\Http\Message\{
    ServerRequestInterface as Request,
    ResponseInterface as Response
};
use App\Models\Finance;
use App\Models\Merchant;
use App\Helpers\Tools;
class FinanceController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/finance/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus
        ]);
    }

    private function exportFinanceHead(){
        return [
            'merchantNo' => '商户号',
            'shortName' => '商户简称',
            'sourceDesc' => '订单类型',
            'platformOrderNo' => '平台订单号',
            'orderAmount' => '订单金额',
            'accountDate' => '账户日期',
            'financeTypeDesc' => '收支类型',
            'amount' => '交易金额',
            'balance' => '余额',
            'summary' => '交易摘要',
            'insDate' => '交易时间',
        ];
    }
    public function search(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new Finance();
        $merchantData = [];
        
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        // $transactionNo = $request->getParam('transactionNo');
        $accountType = $request->getParam('accountType');
        $financeType = $request->getParam('financeType');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $sourceDesc = $request->getParam('sourceDesc');
        $export = $request->getParam('export');
        $merchantId = 0;
        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $merchantId && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $financeType && $model = $model->where('financeType', $financeType);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $accountType && $model = $model->where('accountType', $accountType);
        $sourceDesc && $model = $model->where('sourceDesc', $sourceDesc);
        if(!$export) {
            $total = $model->count();
            $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        }else{
            $data = $model->orderBy('id', 'desc')->get();
        }
        /* echo $model->toSql();exit; */
        $rows = [];

        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
            : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [
                'accountType' => $v->accountType,
                'accountTypeDesc' => $code['accountType'][$v->accountType] ?? '',
                'amount' => $v->amount,
                'balance' => $v->balance,
                'financeNo' => '',
                "financeType" => $v->financeType,
                "financeTypeDesc" => $code['financeType'][$v->financeType] ?? '',
                "insTime" => Tools::getJSDatetime($v->created_at),
                "merchantNo" => $v->merchantNo,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => $merchantData[$v->merchantId]['shortName'],
                "sourceDesc" => $v->sourceDesc,
                "sourceId" => $v->sourceId,
                "summary" => $v->summary,
                // "transactionNo" => $v->transactionNo,
                "transactionNo" => "",
                "accountDate" => $v->accountDate,
            ];
            $nv['insDate'] = date('Y-m-d H:i:s',strtotime($v->created_at));
            $rows[] = $nv;
        }
        if($export) {
            Tools::csv_export($rows, $this->exportFinanceHead(), 'rechargeOrderList');
            die();
        }
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    public function upMerchantOrderNo(){
        $merchant = new Merchant();
        $model = new Finance();
        $merchantData = [];
        
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        // $transactionNo = $request->getParam('transactionNo');
        $accountType = $request->getParam('accountType');
        $financeType = $request->getParam('financeType');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $merchantId = 0;

        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $merchantId && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $financeType && $model = $model->where('financeType', $financeType);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $accountType && $model = $model->where('accountType', $accountType);

        $total = $model->count();
        $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];

        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
            : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [
                'accountType' => $v->accountType,
                'accountTypeDesc' => $code['accountType'][$v->accountType] ?? '',
                'amount' => $v->amount,
                'balance' => $v->balance,
                'financeNo' => '',
                "financeType" => $v->financeType,
                "financeTypeDesc" => $code['financeType'][$v->financeType] ?? '',
                "insTime" => Tools::getJSDatetime($v->created_at),
                "merchantNo" => $v->merchantNo,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => $merchantData[$v->merchantId]['shortName'],
                "sourceDesc" => $v->sourceDesc,
                "sourceId" => $v->sourceId,
                "summary" => $v->summary,
                // "transactionNo" => $v->transactionNo,
                "transactionNo" => "",
                "accountDate" => $v->accountDate,
            ];
            $rows[] = $nv;
        }
    }
}
