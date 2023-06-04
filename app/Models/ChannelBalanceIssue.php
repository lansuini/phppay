<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelBalanceIssue extends Model{
    protected $table = 'channel_balance_issue';
    protected $primaryKey = "issueId";
    protected $fillable = [
        'channelId',
        'channelNo',
        'bankCode',
        'cardNo',
        'userName',
        'issueAmount'
    ];
}
