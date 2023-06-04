<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Models\AgentFinance;
use App\Models\AgentIncomeLog;
use App\Models\Finance;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FinanceController extends AgentController
{
    public function index(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'agent/finance.twig', [
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
        $selType = $request->getParam('selType');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $model = new AgentFinance();
        $model = $model->where('agentId',$_SESSION['userId']);
        $beginTime && ($model = $model->where('created_at','>=',$beginTime));
        $endTime && ($model = $model->where('created_at','<=',$endTime));
        $selType && ($model = $model->where('dealType','=',$selType));
        $platformOrderNo && ($model = $model->where('platformOrderNo','=',$platformOrderNo));
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->get([
            'created_at',
            'platformOrderNo',
            'dealType',
            'balance',
            'freezeBalance',
            'bailBalance',
            'optDesc',
            'desc',
        ]);
        foreach ($res as &$val){
            $val['allBalance'] = $val['balance'] + $val['freezeBalance'];
            $val['dealTypeDesc'] =  Finance::$type[$val['dealType']];
        }
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => $total,
        ]);
    }

    public function unsettledAmount(Request $request, Response $response, $args){
        $limit = (int) $request->getParam('limit', 20);
        $offset = (int) $request->getParam('offset', 0);
        $model = AgentIncomeLog::where('agentId',$_SESSION['userId'])->where('isSettle',0)->where('fee','>',0);
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->get([
            'platformOrderNo',
            'orderMoney',
            'fee',
            'type',
            'updated_at',
        ]);
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => $total,
        ]);
    }
}
