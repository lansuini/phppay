<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Models\MerchantDailyStats;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StatsController extends AgentController
{
    public function index(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'agent/stats.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args){
        $limit = (int) $request->getParam('limit', 20);
        $offset = (int) $request->getParam('offset', 0);
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $merchantNo = $request->getParam('merchantNo');
        $model = new MerchantDailyStats();
        $model = $model->leftJoin('agent_merchant_relation','agent_merchant_relation.merchantId','=','merchant_daily_stats.merchantId');
        $model = $model->leftJoin('merchant_amount','merchant_amount.merchantId','=','merchant_daily_stats.merchantId');
        $model = $model->leftJoin('merchant','merchant.merchantId','=','merchant_daily_stats.merchantId');
        $model = $model->where('agent_merchant_relation.agentId',$_SESSION['userId']);
        $beginTime && ($model = $model->where('merchant_daily_stats.accountDate','>=',$beginTime));
        $endTime && ($model = $model->where('merchant_daily_stats.accountDate','<=',$endTime));
        $merchantNo && ($model = $model->where('merchant_daily_stats.merchantNo','=',$merchantNo));
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->orderBy('dailyId','desc')->get([
            'merchant_daily_stats.accountDate',
            'merchant_daily_stats.merchantNo',
            'merchant.shortName',
            'merchant_amount.settlementAmount as merchantBalance',
            'merchant_daily_stats.payCount',
            'merchant_daily_stats.payAmount',
            'merchant_daily_stats.payServiceFees',
            'merchant_daily_stats.settlementCount',
            'merchant_daily_stats.settlementAmount',
            'merchant_daily_stats.settlementServiceFees',
            'merchant_daily_stats.chargeCount',
            'merchant_daily_stats.chargeAmount',
            'merchant_daily_stats.chargeServiceFees',
            'merchant_daily_stats.agentPayFees',
            'merchant_daily_stats.agentsettlementFees',
            'merchant_daily_stats.agentchargeFees',
        ]);
        foreach ($res as &$val){
            $val['merchantFees'] = $val['settlementServiceFees'] + $val['chargeServiceFees'] + $val['payServiceFees'];
            $val['profit'] = $val['agentPayFees'] + $val['agentsettlementFees'] + $val['agentchargeFees'];
            $val['fees'] = $val['merchantFees'] - $val['profit'];

        }
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => $total,
        ]);
    }


}
