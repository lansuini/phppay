<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantAccountLoginLog extends Model
{
    public $timestamps = false;
    
    protected $table = 'merchant_account_login_log';

    protected $primaryKey = 'id';

    protected $fillable = [
        'ip',
        'ipDesc',
        'status',
        'accountId',
        'remark',
    ];

}