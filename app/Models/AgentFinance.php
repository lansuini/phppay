<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentFinance extends Model
{
    protected $table = 'agent_finance';

    protected $primaryKey = 'id';

    protected $fillable = [
        'agentId',
        'agentName',
        'platformOrderNo',
        'dealMoney',
        'balance',
        'freezeBalance',
        'bailBalance',
        'inferisorNum',
        'dealType',
        'status',
        'optId',
        'optAdmin',
        'optIP',
        'optDesc',
        'updated_at',
        'created_at',
    ];

}
