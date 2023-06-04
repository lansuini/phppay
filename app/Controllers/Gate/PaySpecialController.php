<?php

namespace App\Controllers\Gate;

use App\Channels\ChannelProxy;
use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;

use Psr\Http\Message\ServerRequestInterface as Request;

class PaySpecialController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $re = (new ChannelProxy())->trendsAction($args['platformOrderNo'],$args['action'],$request->getParams());
        if(isset($re['view']) && $re['view']){
            return $this->c->view->render($response, "gate/paySpecial/bank.twig", [
                'data' => $re['data'],
            ]);
        }
        return $response->withJson($re);
    }

}
