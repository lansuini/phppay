<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'message';

    protected $primaryKey = 'id';


    protected $fillable = [
        'nickName',
        'whatAPP',
        'telegram',
        'email',
        'skype',
        'message',
        'created_at',
        'updated_at',
        'remarks'
    ];
}
