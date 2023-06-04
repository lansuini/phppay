<?php
namespace App\Channels\Settlement;

use App\Channels\ChannelsSettlement;
use Requests;

class Wuxingzhifu extends ChannelsSettlement
{
    protected $statusCode = [
        'S' => 'Success',
        'F' => 'Fail',
        'P' => 'Wait',
    ];

    protected $payType = [
        // 'webBank', //网银
        // 'webBankFast', //网银快捷
        // 'webBankScanCode', //银联扫码
        // 'webBankWAP', //网银WAP
        // 'weChatScanCode', //微信扫码
        // 'weChatWAP', //微信WAP
        // 'aliPayScanCode', //支付宝扫码
        // 'aliPayWAP', //支付宝WAP
        // 'qqScanCode', //QQ扫码
        // 'qqWAP', //QQWAP
        // 'jdScanCode', //京东钱包扫码
        // 'baiduScanCode', //百度钱包扫码WAP

        'EBank' => 'webBank',
        'Quick' => 'webBankFast',
        'OnlineWechatQR' => 'weChatScanCode',
        'OnlineAlipayQR' => 'aliPayScanCode',

        'OnlineWechatH5' => 'weChatWAP',
        'OnlineAlipayH5' => 'aliPayWAP',
        'QQPayQR' => 'qqScanCode',
        'UnionPayQR' => 'webBankScanCode',
        'JDPayQR' => 'jdScanCode',
        'EBankQR' => 'webBankScanCode',
    ];

    protected $bankTypeCode = [
        'BOC' => "中国银行",
        'ICBC' => "工商银行",
        'ABC' => "农业银行",
        'BOCOM' => "交通银行",
        'BCM' => "交通银行",
        'GDB' => "广东发展银行",
        'SDB' => "深圳发展银行",
        'CCB' => "建设银行",
        'CMB' => "招商银行",
        'CMBC' => "中国民生银行",
        'CIB' => "兴业银行",
        'CITIC' => "中信银行",
        'CNCB' => "中信银行",
        'PAB' => "平安银行",
        'PINAN' => "平安银行",
        'HXB' => "华夏银行",
        'CEB' => "中国光大银行",
        'BCCB' => "北京银行",
        'BOS' => "上海银行",
        'TCCB' => "天津银行",
        'BODL' => "大连银行",
        'HCCB' => "杭州银行",
        'NBCB' => "宁波银行",
        'XMCCB' => "厦门银行",
        'GZCB' => "广州银行",
        'CZB' => "浙商银行",
        'SRCB' => "上海农村商业银行",
        'CQCB' => "重庆银行",
        'PSBC' => "中国邮政储蓄银行",
        'JSB' => "江苏银行",
        'BJRCB' => "北京农村商业银行",
        'SPDB' => "上海浦东发展银行",
        'ZJTLCB' => "浙江泰隆商业银行",
        'JNB' => "济宁银行",
        'ADBC' => "农业发展银行",
        'RCC' => "农村信用社",
        'CB' => "商业银行",
        'RCB' => "农村商业银行",
        'SHB' => "上海银行",
        'BOB' => "北京银行",
        'BOCD' => "成都银行",
        'NJCB' => "南京银行",
        'HZB' => "杭州银行",
        'EGB' => "恒丰银行",
        'QDCCB' => "青岛银行",
        'CBHB' => "渤海银行",
        'BSB' => "包商银行",
        'NCHCB' => "南昌银行",
        'HRBCB' => "哈尔滨银行",
        'HRXJB' => "华融湘江银行",
        'QLB' => "齐鲁银行",
        'MINTAIB' => "民泰商行",
        'SJB' => "盛京银行",
        'QSB' => "齐商银行",
        'DDB' => "丹东商行",
        'BOFS' => "抚顺银行",
        'FXB' => "阜新银行",
        'JZB' => "锦州银行",
        'BOLY' => "辽阳银行",
        'BOTL' => "铁岭银行",
        'BOYK' => "营口银行",
        'XTYB' => "邢台商行",
        'HDCB' => "邯郸银行",
        'HEBB' => "河北银行",
        'CZCCB' => "沧州银行",
        'LCCB' => "廊坊银行",
        'CDEB' => "承德银行",
        'PDSB' => "平顶山银行",
        'ZMDB' => "驻马店银行",
        'ZZB' => "郑州银行",
        'XXSSH' => "新乡银行",
        'BOLUOY' => "洛阳银行",
        'BOXC' => "许昌银行",
        'HKOUB' => "汉口银行",
        'CSCB' => "长沙银行",
        'DYCCB' => "德阳银行",
        'LSB' => "莱商银行",
        'WFCCB' => "潍坊银行",
        'YTAIB' => "烟台银行",
        'LSBC' => "临商银行",
        'DZB' => "德州银行",
        'BORZ' => "日照银行",
        'LONGJB' => "龙江银行",
        'BOIMC' => "内蒙古银行",
        'FJHXB' => "福建海峡银行",
        'QZCCB' => "泉州银行",
        'GZCCB' => "赣州银行",
        'JJCCB' => "九江银行",
        'SRB' => "上饶银行",
        'GYCCB' => "贵阳银行",
        'GUILB' => "桂林银行",
        'LZCCB' => "柳州银行",
        'BOBBG' => "广西北部湾银行",
        'HZCCB' => "湖州银行",
        'JXCCB' => "嘉兴银行",
        'JHCCB' => "金华银行",
        'SXCCB' => "绍兴银行",
        'TAIZB' => "台州银行",
        'WZCB' => "温州银行",
        'FUDB' => "富滇银行",
        'GDNYB' => "广东南粤银行",
        'DONGGB' => "东莞银行",
        'YCCCB' => "宁夏银行",
        'JSHB' => "晋商银行",
        'JCCB' => "晋城银行",
        'GSB' => "甘肃银行",
        'LZSB' => "兰州银行",
        'HSB' => "徽商银行",
        'JLB' => "吉林银行",
        'CCAB' => "长安银行",
        'XACB' => "西安银行",
        'CCQTGB' => "重庆三峡银行",
        'NCB' => "宁波通商银行",
        'ZHAOZB' => "枣庄银行",
        'BEEB' => "鄞州银行",
        'XTB' => "邢台银行",
        'KTHAIB' => "泰京银行",
        'SUZB' => "苏州银行",
        'BOQH' => "青海银行",
        'KLB' => "昆仑银行",
        'CHIYUB' => "集友银行",
        'HUBEIB' => "湖北银行",
        'BOHLD' => "葫芦岛银行",
        'HSHUIB' => "衡水银行",
        'ORDOSB' => "鄂尔多斯银行",
        'CHONGHINGB' => "创兴银行",
        'CHB' => "朝兴银行",
        'HBSB' => "中原银行",
        'CITIB' => "花旗银行",
        'HSBC' => "汇丰银行",
        'SCB' => "渣打银行",
        'HKBEA' => "东亚银行",
        'HANGSENGB' => "恒生银行",
        'DBS' => "星展银行",
        'WHBCN' => "永亨银行",
        'OCBC' => "华侨银行",
        'MSB' => "摩根士丹利国际银行",
        'JPMC' => "摩根大通银行",
        'WOORIB' => "友利银行",
        'NYCB' => "南洋商业银行",
        'UOB' => "大华银行",
        'HANAB' => "韩亚银行",
        'DEUTSCHE' => "德意志银行",
        'IBK' => "企业银行",
        'CMBCN' => "华商银行",
        'FSB' => "华一银行",
        'BNP' => "法国巴黎银行",
        'SOCIETE' => "法国兴业银行",
        'XIB' => "厦门国际银行",
        'SHINHAN' => "新韩银行",
        'DAHSING' => "大新银行",
        'KEB' => "外换银行",
        'BANGKOKB' => "盘谷银行",
        'METROB' => "首都银行",
        'CTIF' => "正信银行",
        'CVBF' => "村镇银行",
        'TZBANK' => "重庆渝北银座村镇银行",
        'QJYZB' => "重庆黔江银座村镇银行",
        'SMYZB' => "浙江三门银座村镇银行",
        'JNYZB' => "浙江景宁银座村镇银行",
        'FTYZB' => "深圳福田银座村镇银行",
        'GZYZB' => "江西赣州银座村镇银行",
        'DYLSB' => "东营莱商村镇银行",
        'SYYZB' => "北京顺义银座村镇银行",
        'TZB' => "台州银行",
    ];

    protected $codeType = [
        1001 => "签名错误",
        1003 => "订单号重复",
        1004 => "订单记录不存在",
        1005 => "订单重复请求",
        1006 => "签名时间不正确",
        1007 => "ip验证不通过",
        1008 => "余额不足",
    ];

    public function getMerchantCharge($orderData)
    {
        return false;
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/merchant/charge';
        $params = [
            'amount' => sprintf('%.2f', $orderData['orderAmount']),
            'bankCode' => empty($orderData['bankCode']) ? 'BOC' : $orderData['bankCode'],
            'chargeType' => $this->payType[$orderData['payType']] ?? '',
            'notifyUrl' => getenv('CB_DOMAIN') . '/pay/callback/' . $orderData['platformOrderNo'],
            'orderId' => $orderData['platformOrderNo'],
            'remark' => '',
            'retUrl' => '',
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        print_r($params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($this->checkSign($res)) {
                $output['status'] = 'Success';
                $output['payUrl'] = $res['url'];
                $output['orderNo'] = '-------orderNo--------';
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败';
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }
        return $output;
    }

    public function getMerchantOfflineCharge($orderData)
    {
        return false;
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/merchant/offline_charge';
        $params = [
            'accountName' => sprintf('%.2f', $orderData['orderAmount']),
            'amount' => $orderData['orderAmount'],
            'bankCode' => $orderData['bankCode'] ?? 'BOC',
            'bankcardNumber' => $orderData['bankCode'],
            'notifyUrl' => getenv('CB_DOMAIN') . '/pay/callback/' . $orderData['platformOrderNo'],
            'orderId' => $orderData['platformOrderNo'],
            'remark' => '',
            'retUrl' => '',
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        print_r($params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($this->checkSign($res)) {
                $output['status'] = 'Success';
                $output['payUrl'] = $res['url'];
                $output['orderNo'] = '-------orderNo--------';
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败';
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }
        return $output;
    }

    public function getMerchantOrderQuery($orderData) {
        $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/merchant/order_query';
        $params = [
            'orderId' => $orderData['platformOrderNo'],
            'orderType' => '',
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        print_r($params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($this->checkSign($res)) {
                $output['status'] = $this->statusCode[$res['status']] ?? null;
                $output['orderAmount'] = $res['amount'] ?? null;
                $output['orderNo'] = $res['orderNo'] ?? null;
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败';
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }
        return $output;
    }

    public function getMerchantPay($orderData) {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/merchant/pay';
        $params = [
            'amount' => $orderData['orderAmount'],
            'charset' => 'utf-8',
            'currency' => '156',
            'notifyUrl' => getenv('CB_DOMAIN') . '/settlement/callback/' . $orderData['platformOrderNo'],
            'orderId' => $orderData['platformOrderNo'],
            'remark' => '',
            'toBankAccName' => $orderData['bankAccountName'],
            'toBankAccNumber' => $orderData['bankAccountNo'],
            'toBankBranch' => $orderData['bankName'],
            'toBankCity' => $orderData['city'],
            'toBankCode' => $orderData['bankCode'],
            'toBankProvince' => $orderData['province'],
            'version' => '1.00',
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        print_r($params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($this->checkSign($res)) {
                $output['status'] = 'Success';
                $output['orderNo'] = '-------orderNo--------';
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败';
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }
        return $output;
    }

    public function getMerchantBalanceQuery($orderData) {
        $output = ['status' => '', 'balance' => '', 'failReason' => ''];
        $path = '/merchant/balance_query';
        $params = [
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        print_r($params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($this->checkSign($res)) {
                $output['status'] = 'Success';
                $output['balance'] = $res['balance'];
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败';
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }
        return $output;
    }

    public function getHeaderParams()
    {
        return ['api_key' => $this->params['apiKey'], 'Content-Type' => 'application/json'];
    }

    protected function createParams($params)
    {
        return $params;
    }

    protected function createSign($params)
    {
        // $newParam = array_filter($param);
        $newParam = $params;
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam);
        } else {
            $originalString = '';
        }
        // $c = hash_hmac('sha256', $originalString, $key);
        return hash_hmac('sha256', $originalString, $this->params['apiSerect']);
    }

    protected function checkSign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $origin = $this->createSign($params);
        return $origin == $sign ? true : false;
    }

    public function getStandardParam($orderData, $param)
    {
        // String merchantId;                  //商户ID
        // String merchantOrderId;             //商户订单号
        // String orderId;                     //系统订单号
        // String createTime;                  //订单创建时间 格式yyyy-MM-dd HH:mm:ss
        // String amount;                      //订单金额保留2位小数
        // String status;                      //订单状态, S:成功，F：失败，P：处理中

        // String signTime;                    //签名时间
        // String sign;                        //签名
        return ['status' => 'Success', 'orderAmount' => $orderData['orderAmount'], 'failReason' => ''];
    }

    protected function doRequest($params, $sign)
    {
        try {
            $req = Requests::get($this->gateway . '/settlement/successUrl?cb=' . $params['CB'], [], ['timeout' => $this->timeout]);
            echo $this->gateway . '/settlement/successUrl?cb=' . $params['CB'] . PHP_EOL;
            // exit;
            $data = json_decode($req->body, true);
        } catch (\Exception $e) {
            return [];
        }

        return [
            'status' => 'Success',
            'orderNo' => $data['orderNo'],
            'failReason' => '',
        ];

        // return [
        //     'status' => 'Fail',
        //     'orderNo' => ',
        //     'failReason' => '啊啊',
        // ];

        // return [
        //     'status' => 'WaitTransfer',
        //     'orderNo' => '',
        //     'failReason' => '啊啊',
        // ];
    }

    public function queryBalance()
    {
        return 99999999;
    }
}
