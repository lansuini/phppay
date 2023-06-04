<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayPushTask extends Model
{
    protected $table = 'pay_push_task';

    protected $primaryKey = 'id';

    protected $fillable = [
        'thirdParams',
        'standardParams',
        'retryCount',
        'failReason',
        'platformOrderNo',
        'status',
        'ipDesc',
        'ip'
    ];
}
