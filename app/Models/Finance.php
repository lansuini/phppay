<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    protected $table = 'finance';

    protected $primaryKey = 'id';

    protected $fillable = [
        'platformOrderNo',
        'amount',
        'merchantId',
        'financeType',
        'platformOrderNo',
        'accountDate',
        'accountType',
        'summary',
        'transactionNo',
        'sourceId',
        'sourceDesc'
    ];
    public static $type = [
        'reduceBail' => '减少保证金',
        'addBail' => '增加保证金',
        'freeze' => '解结金额',
        'addFreeze' => '增加冻结金额',
        'commission' => '佣金提成',
        'extract' => '提款冻结',
        'extractSuc' => '提款成功',
        'extractFail' => '提款失败',
        'extractFee' => '减少保证金',
        'returnFee' => '减少保证金',
    ];

    public static function tinyType(){
        $type = Finance::$type;
        $res = [];
        foreach ($type as $k=>$v){
            $res[] = [
                'key' => $k,
                'value' => $v,
            ];
        }
        return $res;
    }
}