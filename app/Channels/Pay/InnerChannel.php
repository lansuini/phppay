<?php
namespace App\Channels\Pay;
use App\Channels\Channels;
use Requests;

class InnerChannel extends Channels
{
    protected function createParams($params)
    {
        return $params;
    }

    protected function createSign($params)
    {
        return 'I am sign';
    }

    protected function checkSign($params)
    {
        return true;
    }

    protected function doRequest($params, $sign)
    {
        // try {
        //     $req = Requests::get($this->gateway.'?cb='.$params['CB'], [], ['timeout' => $this->timeout]);
        //     $data = json_decode($req->body, true);
        // } catch (\Exception $e) {
        //     return [];
        // }

        return [
            'payUrl' => '',
            'orderNo' => '',
        ];
    }
}
