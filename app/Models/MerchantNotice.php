<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantNotice extends Model
{
    protected $table = 'merchant_notice';

    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'createdAccountId',
        'publishedAccountId',
        'recipient',
        'content',
        'type',
        'status',
    ];
}