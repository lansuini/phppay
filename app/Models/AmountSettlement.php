<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmountSettlement extends Model
{
    protected $table = 'amount_settlement';

    protected $primaryKey = 'id';

    protected $fillable = [
        'serviceCharge',
        'channelServiceCharge',
        'amount',
        'merchantId',
        'channelMerchantNo',
        'channelMerchantId',
        'merchantNo',
        'accountDate',
    ];

}
