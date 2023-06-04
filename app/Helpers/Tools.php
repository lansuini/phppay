<?php

namespace App\Helpers;

use Hashids\Hashids;
use PHPQrcode\Qrcode;

class Tools
{
    public static function getPlatformOrderNo($tag = "P", $maxTryCount = 3)
    {
        global $app;
        $platformOrderNo = '';
        $redis = $app->getContainer()->redis;
        $platformOrderNoPrefix = date('YmdHis');
        while ($maxTryCount--) {
            $platformOrderNo = $tag . $platformOrderNoPrefix . self::getRandStr('0123456789', 6);
            if ($redis->sadd($tag . $platformOrderNoPrefix, $platformOrderNo) === false) {
                continue;
            } else {
                break;
            }
        }
        $redis->expire($tag . $platformOrderNoPrefix, 60);
        return $platformOrderNo;
    }

    public static function getRandStr($str, $len)
    {
        return substr(str_shuffle($str), 0, $len);
    }

    public static function checkSign($merchantData, $param)
    {
        if (!isset($param['sign'])) {
            return false;
        }
        $newParam = $param;
        $originSign = $newParam['sign'];
        unset($newParam['sign']);
        $sign = self::getSign($newParam, Tools::decrypt($merchantData['signKey']));
        // print_r($_GET);
        // print_r($newParam);
        // echo $merchantData['signKey'], PHP_EOL;
        // echo Tools::decrypt($merchantData['signKey']), PHP_EOL;
        // echo $sign, PHP_EOL;
        // echo $originSign, PHP_EOL;
        // exit;
        if ($originSign != $sign) {
            return false;
        }
        return true;
    }

    public static function getSign($param, $signKey)
    {
        $signKey = Tools::decrypt($signKey);
        $newParam = array_filter($param);
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . $signKey;
        } else {
            $originalString = $signKey;
        }
//        echo "\n";
//        echo $originalString;
//        echo "\n";
//        echo md5($originalString);exit;
        return md5($originalString);
    }

    public static function isAllowIPAccess($ip, $second = 5)
    {
        if (empty($ip)) {
            return true;
        }
        if (getenv('GATE_IP_PROTECT') !== 'true') {
            return true;
        }

        global $app;
        $redis = $app->getContainer()->redis;
        if ($redis->incr("ipaccess:" . $ip) > 1) {
            return false;
        }
        $redis->expire("ipaccess:" . $ip, $second);
        return true;
    }

    public static function getQR($value)
    {
        $errorCorrectionLevel = 'L';
        $matrixPointSize = 10;
        ob_start();
        QRCode::png($value, null);
        $imageString = base64_encode(ob_get_contents());
        ob_end_clean();
        return $imageString;
    }

    public static function getHashId($id)
    {
        $hids = new Hashids(getenv('IDHEX_SALT'), 12, 'abcdefghijklmnopqrstuvwxyz');
        // $hids->_lower_max_int_value = PHP_INT_MAX;
        return $hids->encode($id);
    }

    public static function getIdByHash($idHex)
    {
        $hids = new Hashids(getenv('IDHEX_SALT'), 12, 'abcdefghijklmnopqrstuvwxyz');
        // $hids->_lower_max_int_value = PHP_INT_MAX;
        return current($hids->decode($idHex));
    }

//    public static function changeArrayFieldsToHashId($data, $fetchCallback = null, $fields = ['merchantId', 'id', 'rateId'])
//    {
//        foreach ($data ?? [] as $key => $val) {
//            foreach ($fields as $field) {
//                if (isset($val[$field])) {
//                    $val[$field] = self::getHashId($id);
//                }
//            }
//
//            if ($fetchCallback != null) {
//                $val = $fetchCallback($val);
//            }
//            $data[$key] = $val;
//        }
//        return $data;
//    }

    public static function getHashPassword($password)
    {
        return sha1($password . getenv('PASSWORD_SALT'));
    }

    public static function getIp()
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            $ips = explode(',',$ip);
            return trim(end($ips));
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = "0.0.0.0";
        }
        $ips = explode(',',$ip);
        return current($ips);
    }

    public static function getJSDatetime($datetime)
    {
        if (empty($datetime)) {
            return null;
        }
        $time = strtotime($datetime);
        return [
            'time' => $time * 1000,
        ];
    }

    public static function getAreaByIp($ip = null)
    {
        $loc = new IpLocation();
        $loc->init();
        $location = $loc->getlocation($ip);
        return $location;
    }

    public static function getIpDesc($ip = null)
    {
        $area = self::getAreaByIp($ip);
        return $area['country'] . '|' . $area['area'];
    }

    public static function getJsRedirect($url)
    {
        echo '<script>location.href="' . $url . '";</script>';
        exit;
    }

    public static function getAccountDate($settlementTime, $datetime = null)
    {
        $datetime = empty($datetime) ? date('YmdHis') : $datetime;
        $st = strtotime($datetime);
        $settlementTime = $settlementTime * 3600;
        $h = date('H', $st) * 3600 + date('i', $st) * 60 + date('s', $st);
        $accountDate = $h > $settlementTime ? $st : $st - 86400;
        return date('Ymd', $accountDate);
    }

    public static function encrypt($plain)
    {
        if (empty($plain)) {
            return '';
        }
        return (new Encrypt(getenv('DATA_SALT')))->encrypt($plain);
    }

    public static function decrypt($encrypted)
    {
        if (empty($encrypted)) {
            return '';
        }
        return (new Encrypt(getenv('DATA_SALT')))->decrypt($encrypted);
    }

    public static function isHttps()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }

    public static function isToday($date)
    {
        if (date('Ymd') == date('Ymd', strtotime($date))) {
            return true;
        }
        return false;
    }

    public static function isSameDay($dateA, $dateB)
    {
        if (empty($dateA) || empty($dateB)) {
            return false;
        }

        if (date('Ymd', strtotime($dateA)) == date('Ymd', strtotime($dateB))) {
            return true;
        }

        return false;
    }

    public static function utf8ToGbk($src)
    {
        if (is_array($src)) {
            $dest = array_map(array('\App\Helpers\Tools', 'utf8ToGbk'), $src);
            return $dest;
        }

        $dest = iconv("UTF-8", "GBK//TRANSLIT", (string) $src);
        return ($dest === false ? $src : $dest);
    }

    public static function gbkToUtf8($src)
    {
        if (is_array($src)) {
            $dest = array_map(array('\App\Helpers\Tools', 'gbkToUtf8'), $src);
            return $dest;
        }

        $dest = iconv("GB2312", "UTF-8//TRANSLIT", (string) $src);
        return ($dest === false ? $src : $dest);
    }

    public static function isIpWhite($merchantData)
    {
        if (empty($merchantData['ipWhite'])) {
            return false;
        }

        $reqIp = self::getIp();

        $arrayIpWhite = explode(",", $merchantData['ipWhite']);
        return in_array($reqIp, $arrayIpWhite);
    }

    public static function isJsonString($strUtf8Json)
    {
        if (!is_string($strUtf8Json)) {
            return false;
        }

        $arr = json_decode($strUtf8Json, true);
        if (is_array($arr)) {
            return true;
        }

        return false;
    }

    //粗略判断，不严格，不准确
    public static function isHtmlString($strHtml)
    {
        if (!is_string($strHtml)) {
            return false;
        }

        if ($strHtml != strip_tags($strHtml)) {
            return true;
        }

        return false;
    }

    public static function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        if (trim($value) === "") {
            return true;
        }

        return false;
    }

    /**
     * 导出excel(csv)
     * @data 导出数据
     * @headlist key => value   key 是字段名   value是标题
     * @fileName 输出Excel文件名
     * @except 需要特殊处理的字段      key字段，value  特殊处理调用的方法
     */
    public static function csv_export($data = array(), $headlist = array(), $fileName = 'export' , $except = array()) {

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fileName.'.csv"');
        header('Cache-Control: max-age=0');

        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');// 打开文件资源，不存在则创建
        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headlist);

        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 50000;

        //逐行取出数据，不浪费内存
        foreach ($data as $val) {
            $val = (array)$val;
            $row = [];
            foreach ($headlist as $key=>$t){
                $row[$key] =  $val[$key];
            }
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            fputcsv($fp, $row);
        }
    }

    //求二维数组的差集|比较二维数组的不同
    public static function array_diff_assoc2_deep($array1, $array2) {
        $ret = array();
        foreach ($array1 as $k => $v) {
            if (!isset($array2[$k])) $ret[$k] = $v;
            else if (is_array($v) && is_array($array2[$k])) $ret[$k] = self::array_diff_assoc2_deep($v, $array2[$k]);
            else if ($v !=$array2[$k]) $ret[$k] = $v;
            else
            {
                unset($array1[$k]);
            }

        }
        return $ret;
    }

    /**
     * 生成验证码图片
     * @param $location 验证码x,y轴坐标
     * @param $size 验证码的长宽
     */
    public static function generateVcodeIMG($location,$size,$src_img){
        $width = $size->getWidth();
        $height = $size->getHeight();
        $x = $location->getX();
        $y = $location->getY();

        $src = imagecreatefrompng($src_img);
        $dst = imagecreatetruecolor($width,$height);
        imagecopyresampled($dst,$src,0,0,$x,$y,$width,$height,$width,$height);
        imagejpeg($dst,$src_img);
//        chmod($src_img,0777);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * 判断元素是否存在
     * @param WebDriver $driver
     * @param WebDriverBy $locator
     */
    public static function isElementExsit($driver,$locator){
        try {
            $nextbtn = $driver->findElement($locator);
            return true;
        } catch (\Exception $e) {
            echo 'element is not found!';
            return false;
        }
    }
}
