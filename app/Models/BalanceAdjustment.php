<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceAdjustment extends Model
{
    protected $table = 'balance_adjustment';

    protected $primaryKey = 'adjustmentId';

    protected $fillable = [
        'amount',
        'applyPerson',
        'auditPerson',
        'auditTime',
        'bankrollDirection',
        'bankrollType',
        'status',
        'summary',
        'merchantId',
        'merchantNo',
        'platformOrderNo'
    ];

}