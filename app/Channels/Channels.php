<?php
namespace App\Channels;

abstract class Channels
{
    //生成二维码的前端域名后接需要生成二维码的URL
    public $qrcodeUrl='https://qr.tgqrcode.com/qrcode.html?qrcode=';

    protected $gateway;

    protected $timeout;

    protected $params;

    protected $emptyOrderNo = '';

    protected $name;

    protected $allowPayOrderOrderAmountNotEqualRealOrderAmount = false;

    protected $logger;

    public function __construct($params)
    {
        global $app;
        $this->params = $params;
        $this->gateway = $this->params['gateway'];
        $this->timeout = $this->params['timeout'];
        $this->name = $this->params['name'];
        $this->logger = $app->getContainer()->logger;
    }

    public function isAllowPayOrderOrderAmountNotEqualRealOrderAmount()
    {
        return $this->allowPayOrderOrderAmountNotEqualRealOrderAmount;
    }

    public function getPayOrder($orderData)
    {
        throw new ChannelsException("【{$this->name}】getPayOrder没有定义");
    }

    public function getSettlementOrder($orderData)
    {
        throw new ChannelsException("【{$this->name}】getSettlementOrder没有定义");
    }

    public function errorResponse($response)
    {
        return $response->withStatus(500)->write('ERROR');
    }

    public function successResponse($response)
    {
        return $response->write('SUCCESS');
    }

    public function doCallback($orderData, $request)
    {
        throw new ChannelsException("【{$this->name}】doCallback没有定义");
    }

    public function queryOrder($platformOrderNo)
    {
        throw new ChannelsException("【{$this->name}】queryOrder没有定义");
    }

    public function queryBalance()
    {
        throw new ChannelsException("【{$this->name}】queryBalance没有定义");
    }

    protected function getPayCallbackUrl($platformOrderNo)
    {
        return getenv('CB_DOMAIN') . '/pay/callback/' . $platformOrderNo;
    }

    protected function getSettlementCallbackUrl($platformOrderNo)
    {
        return getenv('CB_DOMAIN') . '/settlement/callback/' . $platformOrderNo;
    }

    protected function getSettlementRechargeCallbackUrl($platformOrderNo)
    {
        return getenv('CB_DOMAIN') . '/settlementRecharge/callback/' . $platformOrderNo;
    }

    protected function getRechargeCallbackUrl($platformOrderNo)
    {
        return getenv('CB_DOMAIN') . '/recharge/callback/' . $platformOrderNo;
    }

    //充值完成跳转页面
    protected function getRechargeReturnUrl()
    {
        return getenv('MERCHANT_DOMAIN') . '/rechargeorder';
    }

    protected function getHeaderParams()
    {
        return ['Content-Type' => 'application/json'];
    }

    protected function getQRToUrl($platformOrderNo, $qr)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex('qr:' . $platformOrderNo, 5 * 60, $qr);
        return getenv('GATE_DOMAIN') . '/page/qr/' . $platformOrderNo;
    }

    protected function getHtmlToUrl($platformOrderNo, $strHtml)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex('html:' . $platformOrderNo, 60 * 60, $strHtml);
        return getenv('GATE_DOMAIN') . '/page/autoredirect/' . $platformOrderNo;
    }

    protected function getSpecialPayUrl($orderData=[], $ext)
    {
        if(!$orderData) return [];
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex('specialPayOrder:' . $orderData['platformOrderNo'], 60 * 15, json_encode(array_merge($orderData,$ext)));
//        if(isset($ext['orderType']) && $ext['orderType'] == 'alipayEbank')
        return getenv('CB_DOMAIN') . '/specialPay/'.$ext['payType'].'/' . $orderData['platformOrderNo'];
    }

    /*
    $qrUrl  二维码链接
    $qrType 二维码类型，用于提示用什么app扫码二维码，{alipay->支付宝，wx->微信，yunpay->云闪付，unionpay->银联}，不传将不显示提示信息
     */
    protected function getJsQrToUrl($qrUrl, $qrType = '')
    {
        return getenv('GATE_DOMAIN') . '/page/jsqr?qrcode=' . $qrUrl . '&type=' . $qrType;
    }
}
