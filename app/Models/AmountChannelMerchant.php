<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmountChannelMerchant extends Model
{
    protected $table = 'amount_channel_merchant';

    protected $primaryKey = 'id';

    protected $fillable = [
        'payAmount',
        'settlementAmount',
        'merchantId',
        'merchantNo',
        'accountDate',
        'channelMerchantId',
        'channelMerchantNo',
        'payServiceCharge',
        'payChannelServiceCharge',
        'settlementServiceCharge',
        'settlementChannelServiceCharge',
    ];

}
