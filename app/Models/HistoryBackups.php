<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HistoryBackups extends Model
{
    protected $table = 'history_backups';

    protected $primaryKey = 'id';

    protected $fillable = [
        'type',
        'content',
    ];

}