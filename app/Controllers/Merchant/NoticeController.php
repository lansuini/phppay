<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\BalanceAdjustment;
use App\Models\Merchant;
use App\Models\MerchantDailyStats;
use App\Models\MerchantNotice;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;


class NoticeController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/merchant_notice.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args){
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $data=MerchantNotice::from('merchant_notice')
            ->whereRaw("FIND_IN_SET('{$_SESSION['merchantNo']}',recipient)")
            ->orWhere('type','default')
            ->where('status','published');
        $total = $data->count();
        $data = $data->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        return $response->withJson([
            'result' => [],
            'rows' => $data,
            'success' => 1,
            'total' => $total,
        ]);
    }

}
