<?php

namespace App\Controllers\Mock;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MerchantController extends Controller
{
    public function success(Request $request, Response $response, $args)
    {
        $logger = $this->c->logger;
        $logger->info('notify success', $request->getParams());
        return $response->write('SUCCESS');
    }

    public function error(Request $request, Response $response, $args)
    {
        $logger = $this->c->logger;
        $logger->info('notify error', $request->getParams());
        return $response->write('asdfasdfasd');
    }
}
