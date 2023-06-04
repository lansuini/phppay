<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelBalanceQuery extends Model
{
    protected $table = 'channel_balance_query';
    protected $primaryKey = "bId";
    protected $fillable = [
        'channelId',
        'channelNo',
        'channel',
        'channelBalance',
        'merchantBalance',
        'diffValue'
    ];

//    public function get_day
}
