<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_config';
    protected $primaryKey = "id";
    protected $fillable = [
        'module',
        'name',
        'type',
        'key',
        'value',
        'desc',
        'state',
        'created_at',
        'updated_at',
    ];

//    public function get_day
}
