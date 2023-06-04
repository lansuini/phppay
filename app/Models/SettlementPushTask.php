<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementPushTask extends Model
{
    protected $table = 'settlement_push_task';

    protected $primaryKey = 'id';

    protected $fillable = [
        'thirdParams',
        'standardParams',
        'retryCount',
        'failReason',
        'platformOrderNo',
        'status',
        'ip',
        'ipDesc'
    ];
}
