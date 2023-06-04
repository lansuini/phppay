<?php

namespace App\Controllers\GM;

// use App\Controllers\Controller;
use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\MerchantAmount;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AccountController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/account/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new MerchantAmount();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $shortName = $request->getParam('shortName');
        $merchantId = 0;

        $merchantNo = $request->getParam('merchantNo');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $model = new Merchant;
        $merchantNo && $model = $model->where('merchant.merchantNo', $merchantNo);
        $shortName && $model = $model->where('merchant.shortName', '=', $shortName);
        $date = date('Ymd');
        $model = $model->leftjoin('merchant_amount', 'merchant.merchantId', '=', 'merchant_amount.merchantId');
        $total = $model->count();
        $data = $model->selectRaw("
        merchant.merchantId,
        merchant.merchantNo,
        merchant.shortName,
        merchant_amount.updated_at,
        (select sum(amount) from amount_pay where accountDate='{$date}' and amount_pay.merchantId = merchant.merchantId) as todayPayAmount,
        (select sum(serviceCharge) from amount_pay where accountDate='{$date}' and amount_pay.merchantId = merchant.merchantId) as todayPayServiceCharge,
        (select sum(amount) from amount_settlement where accountDate='{$date}' and amount_settlement.merchantId = merchant.merchantId) as todaySettlementAmount,
        (select sum(serviceCharge) from amount_settlement where accountDate='{$date}' and amount_settlement.merchantId = merchant.merchantId) as todaySettlementServiceCharge
        ")
            ->limit($limit)
            ->offset($offset)
            ->get();
        $rows = [];
        $merchantAmount = new MerchantAmount;
        foreach ($data ?? [] as $k => $v) {

            $amount = $merchantAmount->getAmount($v->merchantId);

            $nv = [
                'merchantNo' => $v->merchantNo,
                'modTime' => Tools::getJSDatetime($v->updated_at),
                'settledAmount' => (float) $amount["settledAmount"],
                "shortName" => $v->shortName,
                "todaySettlementServiceCharge" => (float) $v->todaySettlementServiceCharge,
                "todayPayAmount" => (float) $v->todayPayAmount,
                "todayPayServiceCharge" => (float) $v->todayPayServiceCharge,
                "todaySettlementAmount" => (float) $v->todaySettlementAmount,

                "settlementAmount" => (float) $amount["settlementAmount"],
                "availableBalance" => (float) $amount["availableBalance"],
                "freezeAmount" => (float) $amount["freezeAmount"],
                "accountBalance" => (float) $amount["accountBalance"],
            ];
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }
}
