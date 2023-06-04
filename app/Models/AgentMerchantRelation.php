<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentMerchantRelation extends Model
{
    protected $table = 'agent_merchant_relation';

    protected $primaryKey = 'id';

    protected $fillable = [
        'agentId',
        'merchantId'
    ];

}
