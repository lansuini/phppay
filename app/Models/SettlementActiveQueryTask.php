<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementActiveQueryTask extends Model
{
    protected $table = 'settlement_active_query_task';

    protected $primaryKey = 'id';

    protected $fillable = [
        'retryCount',
        'failReason',
        'platformOrderNo',
        'status',
    ];
}
