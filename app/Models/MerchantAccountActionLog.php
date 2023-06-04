<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantAccountActionLog extends Model
{
    
    protected $table = 'merchant_account_action_log';

    protected $primaryKey = 'id';

    protected $fillable = [
        'actionBeforeData',
        'actionAfterData',
        'action',
        'status',
        'accountId',
        'ip',
        'ipDesc',
    ];

}