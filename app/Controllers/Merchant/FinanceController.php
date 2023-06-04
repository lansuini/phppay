<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\PlatformPayOrder;
use App\Models\Finance;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;


class FinanceController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/finance.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }
    private function exportFinanceHead(){
        return [
            'sourceDesc' => '订单类型',
            'merchantOrderNo' => '商户订单号',
            'platformOrderNo' => '平台订单号',
            'accountDate' => '账务日期',
            'financeTypeDesc' => '收支类型',
            'operateSource' => '操作来源',
            'amount' => '交易金额',
            'balance' => '余额',
            'summary' => '交易摘要',
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
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $operateSource = $request->getParam('operate_source');
        $financeType = $request->getParam('financeType');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $export = $request->getParam('export');
        $merchantId = $_SESSION['merchantId'];

        $merchantId && $model = $model->where('merchantId', $merchantId);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $financeType && $model = $model->where('financeType', $financeType);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $operateSource && $model = $model->where('operateSource', $operateSource);
        if(!$export) {
            $total = $model->count();
            $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        }else {
            $data = $model->orderBy('id', 'desc')->get();
        }
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
                "operateSource" => $code['operateSource'][$v->operateSource] ?? '',
                "merchantOrderNo" => $v->merchantOrderNo,
            ];
            $rows[] = $nv;
        }
        if($export) {
            Tools::csv_export($rows, $this->exportFinanceHead(), 'FinanceList');
            die();
        }
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

}
