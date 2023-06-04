<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAccount extends Model
{
    protected $table = 'system_account';

    protected $primaryKey = 'id';

    protected $fillable = [
        'userName',
        'loginName',
        'loginPwd',
        'loginFailNum',
        'loginPwdAlterTime',
        'status',
        'googleAuthSecretKey'
    ];

}