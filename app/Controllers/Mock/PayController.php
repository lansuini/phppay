<?php

namespace App\Controllers\Mock;

use App\Controllers\Controller;
use App\Helpers\Tools;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;

class PayController extends Controller
{
    public function successUrl(Request $request, Response $response, $args)
    {
        $redis = $this->c->redis;
        $params = $request->getParams();
        $orderNo = Tools::getRandStr("ABDSSDFSDF1325647650", 10);
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/page/' . $orderNo;
        $redis->setex("mock:{$orderNo}", 7200, json_encode($params, JSON_UNESCAPED_UNICODE));
        $redis->lpush('mockPay:queue', json_encode($params, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(200)->withJson([
            'orderNo' => $orderNo,
            'payUrl' => $url,
        ]);
    }

    public function successQR(Request $request, Response $response, $args)
    {
        $redis = $this->c->redis;
        $params = $request->getParams();
        $orderNo = Tools::getRandStr("ABDSSDFSDF1325647650", 10);
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/pay/page/' . $orderNo;
        $redis->setex("mock:{$orderNo}", 7200, json_encode($params, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(200)->withJson([
            'orderNo' => $orderNo,
            'code' => Tools::getQR($url),
        ]);
    }

    public function page(Request $request, Response $response, $args)
    {
        $orderNo = $args['orderNo'];
        return $this->c->view->render($response, 'mock/pay/page.twig', [
            'appName' => $this->c->settings['app']['name'] . ' Mock Test',
            'orderNo' => $orderNo,
            'notify' => 'http://' . $_SERVER['HTTP_HOST'] . '/pay/notify/' . $orderNo,
        ]);
    }

    public function notify(Request $request, Response $response, $args)
    {
        $redis = $this->c->redis;
        $data = json_decode($redis->get("mock:" . $args['orderNo']), true);
        if (empty($data)) {
            $redis->del("mock:" . $args['orderNo']);
            return $response->withStatus(200)->write("Order is not valid~~~~");
        }

        if (isset($data['cb'])) {
            $req = Requests::get($data['cb'], [], ['timeout' => 10]);
            return $response->withStatus(200)->write($req->body.':'.$data['cb']);
        }

        return $response->withStatus(200)->write("callback not defined");
    }

    public function error(Request $request, Response $response, $args)
    {
        throw new \Exception('ha ha error');
    }

    public function timeout(Request $request, Response $response, $args)
    {
        sleep(60);
    }
}
