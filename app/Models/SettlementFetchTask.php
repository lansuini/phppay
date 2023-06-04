<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementFetchTask extends Model
{
    protected $table = 'settlement_fetch_task';

    protected $primaryKey = 'id';

    protected $fillable = [
        // 'thirdParams',
        'retryCount',
        'failReason',
        'platformOrderNo',
        'status',
    ];
}
