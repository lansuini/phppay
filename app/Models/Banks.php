<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banks extends Model{

    protected $table = 'banks';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
        'status',
        'start_time',
        'end_time',
        'created_at',
        'updated_at',
    ];

    // 是否开放
    public function is_open($code){
        $bank = self::where('code', $code)->first();
        $date = date('Y-m-d H:i:s');
        if($bank && $bank->status == 'disabled' && $bank->start_time < $date && $bank->end_time > $date){
            return false;
        }else{
            return true;
        }
    }
}
