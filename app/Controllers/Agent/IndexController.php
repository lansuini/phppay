<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Helpers\Tools;
use App\Models\Agent;
use App\Models\AgentIncomeLog;
use App\Models\AgentReport;
use App\Models\AgentWithdrawOrder;
use App\Models\MerchantDailyStats;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AgentController
{
    public function index(Request $request, Response $response, $args)
    {
        $model = new Agent();
        $user = $model->getCacheByAgentId($_SESSION['userId']);
        $user['loginIPDesc'] = Tools::getIpDesc($user['loginIP']);
        $user['totalFees'] = AgentIncomeLog::where('agentId',$_SESSION['userId'])->where('isSettle',1)->sum('fee') ?? 0;
        $t = AgentIncomeLog::where('agentId',$_SESSION['userId'])->where('isSettle',1)->where('fee','>',0)->orderBy('id','DESC')->first(['updated_at','fee']);
        $user['lastFees'] = $t['fee'] ?? 0;
        $user['unsettledAmount'] = AgentIncomeLog::where('agentId',$_SESSION['userId'])->where('isSettle',0)->where('fee','>',0)->orderBy('id','DESC')->sum('fee') ?? 0;
        $user['lastFeesDate'] = date('Y-m-d H:i:s',strtotime($t['updated_at'])) ?? '0000-00-00 00:00:00';
        $user['totalWithdraw'] = AgentWithdrawOrder::where('agentId',$_SESSION['userId'])->where('status','Complete')->sum('realMoney') ?? 0;
        return $this->c->view->render($response, 'agent/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'user' => $user,
            'menus' => $this->menus,
        ]);
    }

    public function searchChart(Request $request, Response $response, $args){
        $db = $this->c->database;
        $beginTime = date('Y-m-d H:i:s',strtotime("-7 day"));
        $endTime = date('Y-m-d H:i:s');
        $query = MerchantDailyStats::leftJoin('agent_merchant_relation', 'merchant_daily_stats.merchantId', '=', 'agent_merchant_relation.merchantId');
        $query->where('agent_merchant_relation.agentId','=',$_SESSION['userId']);
        $beginTime && $query->where('merchant_daily_stats.accountDate','>=',$beginTime);
        $endTime && $query->where('merchant_daily_stats.accountDate','<=',$endTime);
        $res = $query->groupBy('merchant_daily_stats.accountDate')->get([
            'merchant_daily_stats.accountDate',
            $db::raw('sum(merchant_daily_stats.payAmount) as payAmount'),
            $db::raw('sum(merchant_daily_stats.settlementAmount) as settlementAmount')
        ])->toArray();
        $data = [];
        foreach ($res as $val){
            $data['day'][] = $val['accountDate'];
            $data['pay'][] = $val['payAmount'];
            $data['set'][] = $val['settlementAmount'];
        }
        return $response->withJson([
            'result' => $data,
            'feeCon' => $this->code['agentType']['withdrawFee'] ?? ['WAY' => 'FixedValue','VALUE' => 10],
        ]);
    }

}
