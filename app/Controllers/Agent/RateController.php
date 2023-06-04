<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Models\AgentRate;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RateController extends AgentController
{
    public function index(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'agent/rate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args){
        $limit = (int) $request->getParam('limit', 20);
        $offset = (int) $request->getParam('offset', 0);
        $proType = $request->getParam('selProType');
        $payType = $request->getParam('selPayType');
        $rateType = $request->getParam('selRateType');
        $model = new AgentRate();
        $model = $model->where('agentId',$_SESSION['userId']);
        $proType && ($model = $model->where('productType','=',$proType));
        $payType && ($model = $model->where('payType','=',$payType));
        $rateType && ($model = $model->where('rateType','=',$rateType));
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->get([
            'productType',
            'payType',
            'bankCode',
            'cardType',
            'rateType',
            'rate',
            'minServiceCharge',
            'maxServiceCharge',
            'fixed',
        ]);
        foreach ($res as &$val){
            $val['productTypeDesc'] = $this->code['productType'][$val['productType']];
            $val['payTypeDesc'] = $this->code['payType'][$val['payType']];
            $val['rateTypeDesc'] = $this->code['rateType'][$val['rateType']];
            $val['cardTypeDesc'] = $this->code['cardType'][$val['cardType']];
            $val['bankCodeDesc'] = $this->code['bankCode'][$val['bankCode']];
        }
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => $total,
        ]);
    }

}
