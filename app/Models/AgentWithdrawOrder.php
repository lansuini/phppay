<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentWithdrawOrder extends Model
{
    protected $table = 'agent_withdraw_order';

    protected $primaryKey = 'id';

    protected $fillable = [
        'agentId',
        'agentName',
        'bankId',
        'platformOrderNo',
        'dealMoney',
        'realMoney',
        'fee',
        'status',
        'optId',
        'optAdmin',
        'optIP',
        'optDesc',
        'appIP',
        'appDesc',
        'created_at',
        'updated_at',
    ];

}
