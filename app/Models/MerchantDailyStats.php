<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantDailyStats extends Model
{
    protected $table = 'merchant_daily_stats';
    protected $primaryKey = "dailyId";
    protected $fillable = [
        'merchantId',
        'merchantNo',
        'payCount',
        'payAmount',
        'payServiceFees',
        'settlementCount',
        'settlementAmount',
        'settlementServiceFees',
        'chargeCount',
        'chargeAmount',
        'chargeServiceFees',
        'accountDate',
    ];

//    public function get_day
}
