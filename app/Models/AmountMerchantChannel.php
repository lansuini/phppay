<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AmountMerchantChannel extends Model
{
    protected $table = 'amount_merchant_channel';

    protected $primaryKey = 'id';

    protected $fillable = [
        'serviceCharge',
        'amount',
        'merchantId',
        'merchantNo',
        'channelMerchantId',
        'channelMerchantNo',
        'accountDate',
    ];

}