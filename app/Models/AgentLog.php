<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentLog extends Model
{
    protected $table = 'agent_log';

    protected $primaryKey = 'id';

    protected $dealType = [
        'LOGIN' => '登陆' ,
        'LOGOUT' => '退出' ,
        'UPDATE_LOGINPWD'=>'修改登录密码',
        'UPDATE_SECUREPWD'=>'修改支付密码',
        'UPDATE_MONEY'=>'资金管理',
        'AGENT_ACCOUNT'=>'代理账号操作',
        'IMPORT_AGENT_RATE'=>'导入代理费率',
        'AGENT_RELATION_MERCH'=>'代理号和商户绑定',
        'WITHDRAW_ORDER'=>'提款订单操作',
        'MERCHANT_RATE_MODIFY'=>'修改商户费率',
    ];

    protected $fillable = [
        'action',
        'actionBeforeData',
        'actionAfterData',
        'optId',
        'optName',
        'status',
        'desc',
        'ipDesc',
        'ip',
        'created_at',
    ];

    public function _get($typeName){
        if(isset($this->$typeName))
        {
            return($this->$typeName);
        }else
        {
            return(NULL);
        }
    }

}
