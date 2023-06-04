<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAccountLoginLog extends Model
{
    public $timestamps = false;

    protected $table = 'system_account_login_log';

    protected $primaryKey = 'id';

    protected $fillable = [
        'ip',
        'ipDesc',
        'status',
        'accountId',
        'remark',
    ];

    // public function getUpdatedAtColumn() {
    //     return null;
    // }
}