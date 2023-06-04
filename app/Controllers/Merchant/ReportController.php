<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\BalanceAdjustment;
use App\Models\Merchant;
use App\Models\MerchantDailyStats;
use App\Models\PlatformPayOrder;
use App\Models\Finance;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;


class ReportController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/report.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function getReport(Request $request, Response $response, $args){
        $balance = new BalanceAdjustment();
        $model = new MerchantDailyStats();
        
//        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');

        $model = $model->where('merchantId', $_SESSION['merchantId']);
        $beginDate && $model = $model->where('accountDate', '>=', $beginDate);
        $endDate && $model = $model->where('accountDate', '<=', $endDate);
        $total = $model->count();
        $data = $model->orderBy('dailyId', 'desc')->offset($offset)->limit($limit)->get();

        $rows = [];

        $sum_payAmount = $sum_payCount = $sum_payFees = $sum_settlementAmount = $sum_settlementCount = $sum_settlementFees = $sum_chargeAmount = $sum_chargeCount = $sum_chargeFees = 0;
        foreach ($data ?? [] as $k => $v) {
            $m = $balance->where('created_at', '>=', $v->accountDate.' 00:00:00')
                ->where('created_at', '<=', $v->accountDate.' 23:59:59')
                ->where('merchantId', $_SESSION['merchantId'])
                ->where('status', 'Success')
                ->selectRaw('bankrollDirection, count(adjustmentId) as pcount, sum(amount) as amount')->groupBy('bankrollDirection')->get();
            $chargeAmount = $chargeCount = $chargeServiceFees = 0;
            if(count($m) > 0){
                foreach ($m as $mk=>$mv){
                    if($mv->bankrollDirection == 'Restore'){//充值
                        $chargeCount = $mv->pcount;
                        $chargeAmount = $mv->amount;
                    }else if($mv->bankrollDirection == 'Retrieve'){//手续费
                        $chargeServiceFees = $mv->amount;
                    }
                }
            }
            $v->chargeAmount += $chargeAmount;
            $v->chargeCount += $chargeCount;
            $v->chargeServiceFees += $chargeServiceFees;
            $nv = [
                "account_date" => $v->accountDate,
                "pay_amount" => $v->payAmount,
                "pay_count" => $v->payCount,
                "pay_fee" => $v->payServiceFees,
                "settlement_amount" => $v->settlementAmount,
                "settlement_count" => $v->settlementCount,
                "settlement_fee" => $v->settlementServiceFees,
                "recharge_amount" => $v->chargeAmount,
                "recharge_count" => $v->chargeCount,
                "recharge_fee" => $v->chargeServiceFees,
            ];
            $sum_payAmount += $v->payAmount;
            $sum_payCount += $v->payCount;
            $sum_payFees += $v->payServiceFees;
            $sum_settlementAmount += $v->settlementAmount;
            $sum_settlementCount += $v->settlementCount;
            $sum_settlementFees += $v->settlementServiceFees;
            $sum_chargeAmount += $v->chargeAmount;
            $sum_chargeCount += $v->chargeCount;
            $sum_chargeFees += $v->chargeServiceFees;
            $rows[] = $nv;
        }
        $stat = [
            'sum_payAmount'=>number_format($sum_payAmount,2),
            'sum_payCount'=>$sum_payCount,
            'sum_payFees'=>number_format($sum_payFees,2),
            'sum_settlementAmount'=>number_format($sum_settlementAmount,2),
            'sum_settlementCount'=>$sum_settlementCount,
            'sum_settlementFees'=>number_format($sum_settlementFees,2),
            'sum_chargeAmount'=>number_format($sum_chargeAmount,2),
            'sum_chargeCount'=>$sum_chargeCount,
            'sum_chargeFees'=>number_format($sum_chargeFees,2),
        ];
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'stat' =>$stat,
            'total' => $total,
        ]);
    }

}
