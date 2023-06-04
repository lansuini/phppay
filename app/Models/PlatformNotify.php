<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformNotify extends Model
{
    protected $table = 'platform_notify';

    protected $primaryKey = 'id';

    protected $fillable = [
        'accountId',
        'title',
        'platform',
        'content',
        'type',
        'status',
    ];
}