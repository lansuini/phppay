<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmountPay extends Model
{
    protected $table = 'amount_pay';

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
        'balance',
        'payType',
    ];

}
