<?php

namespace App\Models;

use App\Helpers\Tools;
use Illuminate\Database\Eloquent\Model;

class MerchantBankCard extends Model
{
    protected $table = 'merchant_bank_card';

    protected $primaryKey = 'id';

    protected $fillable = [
        'bankCode',
        'bankName',
        'bankCode',
        'province',
        'city',
        'district',
        'accountName',
        'accountNo',
        'merchantId',
        'merchantNo',
        'fullName',
    ];


}
