<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayNotifyTask extends Model
{
    protected $table = 'pay_notify_task';

    protected $primaryKey = 'id';

    protected $fillable = [
        'retryCount',
        'failReason',
        'platformOrderNo',
        'requestAddr',
        'status',
    ];
}