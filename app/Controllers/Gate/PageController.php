<?php

namespace App\Controllers\Gate;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PageController extends Controller
{
    public function qr(Request $request, Response $response, $args)
    {
        // dump($request->getMethod() == 'GET');
        global $app;
        $redis = $app->getContainer()->redis;
        return $this->c->view->render($response, 'gate/page/qr.twig', [
            'qr' => $redis->get('qr:' . $args['platformOrderNo']),
        ]);
    }

    public function autoredirect(Request $request, Response $response, $args)
    {
        global $app;
        $redis = $app->getContainer()->redis;

        $html = $redis->get('html:' . $args['platformOrderNo']);
        if (!empty($html)) {
            echo $html;
        } else {
            echo '<!DOCTYPE html>
                  <html>
                    <head>
                        <meta charset="UTF-8">
                        <title>付款</title>
                    </head>
                    <body>
                        <strong>付款链接已过期！</strong>
                    </body>
                  </html>';
        }
    }

    public function jsqr(Request $request, Response $response, $args)
    {
        $qrcode = $request->getParam('qrcode', '');
        $type = $request->getParam('type', '');

        return $this->c->view->render($response, 'gate/page/jsqr.twig', [
            'qrcode' => $qrcode,
            'type' => $type,
        ]);
    }
}
