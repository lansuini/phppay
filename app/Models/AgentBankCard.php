<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentBankCard extends Model
{
    protected $table = 'agent_bank_card';

    protected $primaryKey = 'id';

    protected $fillable = [
        'bankCode',
        'bankName',
        'province',
        'city',
        'district',
        'accountName',
        'accountNo',
        'agentId',
        'status',
        'created_at',
        'updated_at',
    ];
}
