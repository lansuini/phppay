<?php

namespace App\Controllers\Mock;

use App\Controllers\Controller;
use App\Helpers\Tools;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;
class SettlementController extends Controller
{
    public function successUrl(Request $request, Response $response, $args)
    {
        $redis = $this->c->redis;
        $params = $request->getParams();
        $orderNo = Tools::getRandStr("ABDSSDFSDF1325647650", 10);
        // $url = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/page/' . $orderNo;
        // $redis->setex("mock:{$orderNo}", 7200, json_encode($params, JSON_UNESCAPED_UNICODE));
        $redis->lpush('mockSettlement:queue', json_encode($params, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(200)->withJson([
            'orderNo' => $orderNo,
        ]);
    }
}