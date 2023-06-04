<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackUserSettlement extends Model
{

    protected $table = 'black_user_settlement';

    protected $primaryKey = 'blackUserId';

    public function checkBlackUser($bankCode, $bankAccountName, $bankAccountNo)
    {
        if($bankCode != 'ALIPAY'){
            $bankCode = 'EBANK';
        }
        $res = self::where('blackUserType',$bankCode)
                    ->where('blackUserStatus','enable')
                    ->where(function($query) use ($bankAccountName,$bankAccountNo){
                        $query->where('blackUserName',$bankAccountName)
                        ->orWhere('blackUserAccount',$bankAccountNo);
                    })
                    ->get()
                    ->toArray();

        return $res ;
    }


}
