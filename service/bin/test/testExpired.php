<?php
use App\Models\PlatformSettlementOrder;
PlatformSettlementOrder::where('updated_at', '<=', date('YmdHis', time() - 86400))
    ->whereNotIn('orderStatus', ['Success', 'Fail'])
    ->orderBy('orderId', 'asc')
    ->chunk(200, function ($task) {
        foreach ($task ?? [] as $v) {
            // (new PlatformSettlementOrder)->fail($v->toArray(), 'Expired');
            $model = new PlatformSettlementOrder;
            $model->fail($v->toArray(), 'Expired');
            dump($v->toArray());
            dump($model->getErrorMessage());
            break;
        }
    });

// PlatformPayOrder::where('updated_at', '<=', date('YmdHis', time() - 86400))
//     ->whereNotIn('orderStatus', ['Success', 'Fail'])
//     ->orderBy('orderId', 'asc')
//     ->chunk(200, function ($task) {
//         foreach ($task ?? [] as $v) {
//             (new PlatformPayOrder)->fail($v->toArray(), 'Expired');
//         }
//     });

// $data = PlatformPayOrder::where('updated_at', '<=', date('YmdHis', time() - 86400))
//     ->whereNotIn('orderStatus', ['Success', 'Fail'])
//     ->orderBy('orderId', 'asc')->get()->toArray();

// PlatformPayOrder::where('updated_at', '<=', date('YmdHis', time() - 86400))
//     ->whereNotIn('orderStatus', ['Success', 'Fail'])
//     ->orderBy('orderId', 'asc')
//     ->chunk(200, function ($task) {
//         foreach ($task ?? [] as $v) {
//             $model = new PlatformPayOrder;
//             $model->fail($v->toArray(), 'Expired');
//             dump($v->toArray());
//             dump($model->getErrorMessage());
//             break;
//         }
//     });
// dump($data);
