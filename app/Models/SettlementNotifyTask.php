<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementNotifyTask extends Model
{
    protected $table = 'settlement_notify_task';

    protected $primaryKey = 'id';

    protected $fillable = [
        'params',
        'retryCount',
        'failReason',
        'platformOrderNo',
        'status',
        'requestAddr',
    ];
}
