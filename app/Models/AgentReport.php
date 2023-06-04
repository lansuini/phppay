<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentReport extends Model
{
    protected $table = 'agent_report';

    protected $primaryKey = 'id';

    protected $fillable = [
        'agentId',
        'agentName',
        'addMerchant',
        'commCount',
        'commMoney',
        'settCommCount',
        'settCommMoney',
        'withdrewCount',
        'withdrewMoney',
        'withdrewFee',
        'commWays',
        'accountDate',
        'created_at',
        'updated_at',
    ];

}
