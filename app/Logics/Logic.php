<?php
/**
 * 逻辑层基类
 */
namespace App\Logics;
use Slim\Container;

abstract class Logic {
    protected $ci;

    public function __construct(Container $ci) {
        $this->ci = $ci;
    }

    public function __get($field) {
        if(isset($this->{$field})) {
            return $this->{$field};
        }
        return $this->ci->{$field};
    }

    /**
     * 打印完整的sql (\Illuminate\Database\Eloquent\Model 打印对于预处理的sql解析,参数绑定并未填充)
     *  抓包mysql的预处理,会有2次TCP包
     */
    public function getLastSql(){
        $queryLog = $this->db->getConnection()->getQueryLog();
        $sql_format = str_replace('?', '%s', $queryLog[0]['query']);
        $sql = $this->_sprintf_array($sql_format, $queryLog[0]['bindings']);
        return $sql;
    }

    protected function _sprintf_array($format, $arr){
        return call_user_func_array('sprintf', array_merge((array)$format, $arr));
    }
}