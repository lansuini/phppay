<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelDailyStats extends Model
{
    protected $table = 'channel_daily_stats';
    protected $primaryKey = "dailyId";
    protected $fillable = [
        'channelMerchantId',
        'channelMerchantNo',
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
