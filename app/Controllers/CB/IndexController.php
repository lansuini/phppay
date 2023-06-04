<?php

namespace App\Controllers\CB;

use App\Channels\ChannelProxy;
use App\Controllers\Controller;
use App\Helpers\Tools;
use App\Models\Message;
use App\Models\SystemConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\WebDriverExpectedCondition;
class IndexController extends Controller
{
    public $banks = [
        'default',//留白 0
        'fastPay-icbc105',//工商 1
        'fastPay-ccb103',// 中国建设银行 2
        'fastPay-abc101',//中国农业银行 3
        'fastPay-psbcnucc103',//中国邮政储蓄银行 4
        'fastPay-commnucc103',//交通银行 5
        'fastPay-cmb103',//招商银行
        'fastPay-boc102',//中国银行
        'fastPay-cebnucc103',//中国光大银行
        'fastPay-citicnucc103',//中信银行
        'fastPay-spdbnucc104',//浦发银行
        'fastPay-cib102',//兴业银行
        'fastPay-spabanknucc103',//平安银行
//        'fastPay-cebnucc103',//广发银行  无
        'fastPay-shrcbnucc103',//上海农商行
        'fastPay-shbanknucc103',//上海银行
        'fastPay-nbbanknucc103',//宁波银行
        'fastPay-hzcbnucc103',//杭州银行
        'fastPay-bjbanknucc103',//北京银行
        'fastPay-bjrcbnucc103',//北京农商银行
        'fastPay-fdbnucc103',//富滇银行
        'fastPay-wzcbnucc103',//温州银行
        'fastPay-cdcbnucc103',//成都银行
        'fastPay-csrcbnucc103',//常熟农商银行
        'fastPay-hxbanknucc103',//华夏银行
        'fastPay-njcbnucc103',//南京银行
//        'fastPay-bjrcbnucc103',//苏州农村商业银行
    ];

    public function pay(Request $request, Response $response, $args)
    {
        try {
            $logger = $this->c->logger;
            $logger->pushProcessor(function ($record) use ($request) {
                $record['extra']['a'] = 'pay';
                $record['extra']['i'] = Tools::getIp();
                $record['extra']['d'] = Tools::getIpDesc();
                $record['extra']['p'] = $request->getParams();
                return $record;
            });
            $logger->info($request->getUri());
            $platformOrderNo = $args['platformOrderNo'];
            if(strtolower($platformOrderNo) == 'loropay'){
                $params = $request->getParams();
                if(isset($params['merchantOrderNo']))
                $platformOrderNo = isset($params['merchantOrderNo']) ? $params['merchantOrderNo'] : 'loropay';
            }
            $resp = (new ChannelProxy)->doPayCallback($platformOrderNo, $request, $response);
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            $resp = $response->withStatus(500)->write('callback exception');
        } catch (\Throwable $e) {
            $logger->error('Throwable:' . $e->getMessage());
            $resp = $response->withStatus(500)->write('callback exception');
        }

        return $resp;
    }

    public function settlement(Request $request, Response $response, $args)
    {
        try {
            $logger = $this->c->logger;
            $chp = new ChannelProxy;
            $platformOrderNo = $args['platformOrderNo'];
            if(strtolower($platformOrderNo) == 'loropay'){
                $params = $request->getParams();
                if(isset($params['merchantOrderNo']))
                    $platformOrderNo = isset($params['merchantOrderNo']) ? $params['merchantOrderNo'] : 'loropay';
            }
            $channelApiCharset = $chp->getApiCharset($platformOrderNo);
            if ($channelApiCharset == 'gbk') {
                $reqParams = Tools::gbkToUtf8($request->getParams());
            } else {
                $reqParams = $request->getParams();
            }

            $logger->pushProcessor(function ($record) use ($reqParams) {
                $record['extra']['a'] = 'settlement';
                $record['extra']['i'] = Tools::getIp();
                $record['extra']['d'] = Tools::getIpDesc();
                $record['extra']['p'] = $reqParams;
                return $record;
            });
            $logger->info($request->getUri());

            $resp = $chp->doSettlementCallback($platformOrderNo, $request, $response);
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            $resp = $response->withStatus(500)->write('callback exception');
        } catch (\Throwable $e) {
            $logger->error('Throwable:' . $e->getMessage());
            $resp = $response->withStatus(500)->write('callback exception');
        }

        return $resp;
    }

    public function settlementRecharge(Request $request, Response $response, $args){
        try {
            $logger = $this->c->logger;
            $chp = new ChannelProxy;
            $channelApiCharset = $chp->getApiCharset($args['settlementRechargeOrderNo']);
            if ($channelApiCharset == 'gbk') {
                $reqParams = Tools::gbkToUtf8($request->getParams());
            } else {
                $reqParams = $request->getParams();
            }
            $record['extra']['a'] = 'settlementRecharge';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['p'] = $reqParams;
            $logger->info($request->getUri(),$record);
            $resp = $chp->doSettlementRechargeCallback($args['settlementRechargeOrderNo'], $request, $response);
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            return $response->withStatus(500)->write('callback exception');
        } catch (\Throwable $e) {
            $logger->error('Throwable:' . $e->getMessage());
            return $response->withStatus(500)->write('callback exception');
        }
        if($resp['outputType'] == 'string'){
            $resp = $response->withStatus(200)->write($resp['output']);
        }elseif($resp['outputType'] == 'json'){
            $resp = $response->withStatus(200)->withJson($resp['output']);
        }
        return $resp;
    }

    public function recharge(Request $request, Response $response, $args){
        try {
            $logger = $this->c->logger;
            $chp = new ChannelProxy;
            $channelApiCharset = $chp->getApiCharset($args['rechargeOrderNo']);
            if ($channelApiCharset == 'gbk') {
                $reqParams = Tools::gbkToUtf8($request->getParams());
            } else {
                $reqParams = $request->getParams();
            }
            $record['extra']['a'] = 'recharge';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['p'] = $reqParams;
            $logger->info($request->getUri(),$record);
            $resp = $chp->doRechargeCallback($args['rechargeOrderNo'], $request, $response);
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            return $response->withStatus(500)->write('callback exception');
        } catch (\Throwable $e) {
            $logger->error('Throwable:' . $e->getMessage());
            return $response->withStatus(500)->write('callback exception');
        }
        if($resp['outputType'] == 'string'){
            $resp = $response->withStatus(200)->write($resp['output']);
        }elseif($resp['outputType'] == 'json'){
            $resp = $response->withStatus(200)->withJson($resp['output']);
        }
        return $resp;
    }
    //设置留言邮箱
    public function message(Request $request, Response $response, $args){
        $bankCode = array_keys($this->c->code['bankCode']);
        echo json_encode($bankCode);exit;
        echo Tools::decrypt('TwdkoOg9/zv9QCHwvEkCVmwHUSiq/RsRxqrNjWzgVMehDR+oRL1IZorG6T+Pra07');exit;
        $nickName=$request->getParam('nickName');
        $whatAPP=$request->getParam('whatAPP');
        $telegram=$request->getParam('telegram');
        $email=$request->getParam('email');
        $skype=$request->getParam('skype');
        $message=$request->getParam('message');
        if(!$nickName){
            return $response->withJson([
                'result' => '名称不能为空！',
                'success' => 0,
            ]);
        }
        if(!$email){
            return $response->withJson([
                'result' => '邮箱不能为空！',
                'success' => 0,
            ]);
        }
        if(!$message){
            return $response->withJson([
                'result' => '留言内容不能为空！',
                'success' => 0,
            ]);
        }

        $res=Message::insert([
            'whatAPP'=>$whatAPP,
            'telegram'=>$telegram,
            'email'=>$email,
            'nickName'=>$nickName,
            'skype'=>$skype,
            'message'=>$message
        ]);

        if($res!==false){
            return $response->withJson([
                'result' => '成功',
                'success' => 1,
            ]);
        }
        return $response->withJson([
            'result' => '失败',
            'success' => 0,
        ]);
    }

    //获取留言邮箱
    public function getEmail(Request $request, Response $response, $args){

        $res=SystemConfig::where('key',"email")
            ->get(['value'])->first();
        return $response->withJson([
            'result' => $res['value'],
            'success' => 1,
        ]);
    }

    public function doCreate(Request $request, Response $response, $args){

        $monery = $request->getParam('money');
        $amount = trim(trim($monery),'￥');
        error_reporting(E_ALL);

        $setId = 19513;

//        $amount = 500;
        $model = new \App\Models\MerchantChannelRecharge();
        $merchantChannelRecharge = $model->where('setId',$setId)->first();

        $rechargeOrder = new \App\Models\PlatformRechargeOrder();
        $rechargeOrderN0 = 'R'.date('YmdHis') . rand(10000,999999);

        $rechargeOrder->platformOrderNo = $rechargeOrderN0;
        $rechargeOrder->merchantNo = $merchantChannelRecharge['merchantNo'];
        $rechargeOrder->merchantId = $merchantChannelRecharge['merchantId'];
        $rechargeOrder->channelMerchantId = $merchantChannelRecharge['channelMerchantId'];
        $rechargeOrder->channelMerchantNo = $merchantChannelRecharge['channelMerchantNo'];
        $rechargeOrder->orderAmount = $amount;
        $rechargeOrder->realOrderAmount = $amount;
        $rechargeOrder->serviceCharge = 0;
        $rechargeOrder->channelServiceCharge = 0;
        $rechargeOrder->channel = $merchantChannelRecharge['channel'];
        $rechargeOrder->channelSetId = $merchantChannelRecharge['setId'];
        $rechargeOrder->orderStatus = 'Transfered';
        $rechargeOrder->payType = $merchantChannelRecharge->payType;
        $rechargeOrder->orderReason = 'test';
        $rechargeOrder->agentFee = 0;

        $merchantRateConfigTemp['rateType'] = 'FixedValue';
        $merchantRateConfigTemp['rate'] = '0';
        $merchantRateConfigTemp['fixed'] = '0';

        $channelRateConfigTemp['rateType'] = 'FixedValue';
        $channelRateConfigTemp['rate'] ='0';
        $channelRateConfigTemp['fixed'] = '0';
        $rechargeOrder->rateTemp = json_encode(['merchant'=>$merchantRateConfigTemp,'channel'=>$channelRateConfigTemp]);

        $rechargeOrder->save();
        $rechargeOrder->setCacheByPlatformOrderNo($rechargeOrderN0,$rechargeOrder->toArray());

        $res = (new \App\Channels\ChannelProxy())->getRechargeOrder($rechargeOrder->toArray());

        if(isset($res['payUrl'])){
            echo "
            <script>window.location.href = '{$res['payUrl']}'</script>
            ";
        }
        echo 'error', PHP_EOL;
    }

    public function alipayEbank(Request $request, Response $response, $args){
        $orderNumber = 123456789;
        return $this->c->view->render($response, 'cb/index/alipayEbank.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function getAlipayEbankLimit(Request $request, Response $response, $args){
        return $response->withJson([
            'code' => -1,
            'data' => null,
            'msg' => '暂无数据',
        ]);
    }

    public function specialPay(Request $request, Response $response, $args){
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'specialPayOrder';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });
        $redis = $this->c->redis;
        $data = $redis->get('specialPayOrder:' . $args['platformOrderNo']);
        $orderData = json_decode($data,true);
//        print_r($data);exit;
        if(!$orderData){
           exit('订单超时已销毁！');
        }
        if($orderData['orderStatus'] != 'Transfered'){
            exit('订单已处理！');
        }
        if(time() > (strtotime($orderData['created_at']) + (60 * 15))){
            exit('订单已超时！');
        }

        return $this->c->view->render($response, 'cb/index/alipayEbank.twig',['orderData'=>$orderData]);
    }

    public function comfirmRechargeOrder(Request $request, Response $response, $args){
        $logger = $this->c->logger;
        $redis = $this->c->redis;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'specialPayOrder';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });
        $logger->info($request->getUri());

        $return = ['code'=>400,'data'=>'','msg'=>'订单异常！'];

        $params  = $request->getParsedBody();
        if(!isset($params['order_no']) || !$params['order_no']){
            return $response->withJson($return);
        }
        if(!isset($params['bank_id']) || !$params['bank_id']){
            return $response->withJson($return);
        }
        $data = $redis->get('specialPayOrder:' . $params['order_no']);
        $orderData = json_decode($data,true);
//        print_r($data);exit;
        if(!$orderData){
            $return['msg'] = '订单不存在！';
            return $response->withJson($return);
        }

        if($orderData['orderStatus'] != 'Transfered'){
            $return['msg'] = '订单已处理！';
            return $response->withJson($return);
        }
        $return['code'] = 200;
        $return['msg'] = '获取成功！';
        $return['data'] = getenv('CB_DOMAIN') . '/waitRechargeOrder/'.$orderData['payType'].'/' . $orderData['platformOrderNo'];
        return $response->withJson($return);
    }

    public function waitRechargeOrder(Request $request, Response $response, $args){
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'specialPayOrder';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });
        $redis = $this->c->redis;
        $data = $redis->get('specialPayOrder:' . $args['platformOrderNo']);
        $orderData = json_decode($data,true);
//        print_r($data);exit;
        if(!$orderData){
            exit('订单超时已销毁！');
        }
        if($orderData['orderStatus'] != 'Transfered'){
            exit('订单已处理！');
        }
        if(time() > (strtotime($orderData['created_at']) + (60 * 15))){
            exit('订单已超时！');
        }
        return $this->c->view->render($response, 'cb/index/waitRechargeOrder.twig',['orderData'=>$orderData]);
    }


    public function testOrder(Request $request, Response $response, $args){
        global $app;
        $cache = $app->getContainer()->redis;
        $logger = $this->c->logger;
        $channelMerchantNo = 100004;
        $key = 'chrome_driver_100004';
        try{
            $caches = $cache->hgetAll($key);

            if($caches){
                $cache->expire($key,900);
                $host = 'http://localhost:4444/wd/hub';        // selenium-server地址，此处传入默认值
                echo '使用缓存session:';
//                var_dump($caches);
                $sessionId = $cache->hget($key,'sessionId');
                $driver = RemoteWebDriver::createBySessionID($sessionId);
//                $driver->quit();

                $driver->manage()->window()->maximize();    //将浏览器最大化
//                $driver->navigate()->refresh();
//                sleep(3);
                $currentUrl =  $driver->getCurrentURL();
//                if(strpos($currentUrl,'login') != false){
//                    echo '登录失败';
//                }
//                $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all2.png');
                echo $currentUrl;
//                  exit;
//                $sHtml = $driver->getPageSource();
//                $logger->info($sHtml);exit;
//                var_dump($sHtml);exit;
//                $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_login\"]"))->click();
//                $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all1.png');  //截取当前网页，该网页有我们需要的验证码
//                exit;
                echo '------------------------------------------------------------------------------------------------------';
//                var_dump($driver->manage()->getCookies());
//                $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_login\"]"))->click();
                $driver->navigate()->refresh();
                $driver->wait(5)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(
                        WebDriverBy::id('J-input-checkcode')
                    )
                );

                try{
                    $element = $driver->findElement(WebDriverBy::xpath("//div[@class=\"qrcode qrcode-modern  fn-hide\"]"));
                    $elementCheckCode = $driver->findElement(WebDriverBy::id('J-input-checkcode'));
                    $elementCheckCode->clear();
                    $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all1.png');
                    exit;
                    $checkcode = $caches['checkcode'];
                    $checkcode = '7fkt';
                    $elementCheckCode->sendKeys($checkcode);	//在输入框中输入内容

                }catch (\Exception $e){
                    $elementCheckCode = $driver->findElement(WebDriverBy::id('J-input-checkcode'));
                    $elementCheckCode->clear();
                    $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all1.png');
                    exit;
                    $checkcode = $caches['checkcode'];
                    $checkcode = '7fkt';
                    $elementCheckCode->sendKeys($checkcode);	//在输入框中输入内容
                    echo $e->getMessage();
                    echo 555;
                    echo '------------------------------------------------------------------';
                }
                sleep(1);
                $elementPassword = $driver->findElement(WebDriverBy::id('password_rsainput'));

                $elementPassword->sendKeys("aa12312a");	//在输入框中输入内容
                $loginBtn = $driver->findElement(WebDriverBy::id('J-login-btn'));
                $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all2.png');
                sleep(1);
                $loginBtn->click();	//点击按钮
                sleep(5);
                $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all3.png');  //截取当前网页，该网页有我们需要的验证码
                echo '-----------------------------------------------------------------------';
                echo $driver->getCurrentURL();
                exit;
//                $sHtml = $driver->getPageSource();
//                $this->getHtmlToUrl($rechargeData['platformOrderNo'], $sHtml);
                try{
                    $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_qr\"]"))->click();
                    $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all4.png');  //截取当前网页，该网页有我们需要的验证码
                    $element = $driver->findElement(WebDriverBy::id('J-qrcode-body'));
                    Tools::generateVcodeIMG($element->getLocation(), $element->getSize(),'/data/image/dzpay/'.$channelMerchantNo.'/qrcode2.png');
                }catch (\Exception $e){
                    echo 777;
                }


                exit;
                echo PHP_EOL;

//                $driver->quit();
                echo 123123;exit;
            }else{
                echo '开始创建新应用：';
//                $chrome_driver = $cache->getItem($key);
                $host = 'http://localhost:4444/wd/hub';        // selenium-server地址，此处传入默认值
                $codeDst = '/data/image/dzpay/'.$channelMerchantNo.'/qrcode.png';
                $waitSeconds = 5;
                $options = new ChromeOptions();
//            $options->setBinary('/usr/bin/chromedriver');  //指定浏览器程序路径
                $options->addArguments(
                    array(
                        '--no-sandbox',                        // 解决DevToolsActivePort文件不存在的报错
//                '--whitelisted-ips',
                        'window-size=1080x1920',               // 指定浏览器分辨率
//                        '--disable-gpu',                       // 谷歌文档提到需要加上这个属性来规避bug
                        '--hide-scrollbars',                   // 隐藏滚动条, 应对一些特殊页面
                        'blink-settings=imagesEnabled=true',  // 不加载图片, 提升速度
                        '--headless',                          // 浏览器不提供可视化页面
//                        '--disable-dev-shm-usage',
                    )
                );

                $capabilities = DesiredCapabilities::chrome();
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

                $driver = RemoteWebDriver::create($host, $capabilities,10000);
//            self::$driver->get('http://www.yimuhe.com');
//                $driver->get('https://auth.alipay.com/login/index.htm?goto=https://mrchportalweb.alipay.com/user/home');
                $driver->get('https://business.alipay.com/user/home');
                $js = "window.scrollTo(0,document.body.scrollHeight)";	//滚动至底部
                //$js = "window.scrollBy(0,100000000);";  //也可以把值设大一点，达到底部的效果
                $driver->executeScript($js);
                $driver->manage()->window()->maximize();    //将浏览器最大化
                $driver->takeScreenshot($codeDst);  //截取当前网页，该网页有我们需要的验证码

                //J-qrcode-body,J-barcode-container，J-qrcode-img
                $element = $driver->findElement(WebDriverBy::id('J-qrcode-body'));
                Tools::generateVcodeIMG($element->getLocation(), $element->getSize(),$codeDst);

                $driver->wait($waitSeconds)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(
                        WebDriverBy::id('J-loginMethod-tabs')
                    )
                );
                $cookies = $driver->manage()->getCookies();
                $sessionId = $driver->getSessionID();
//                echo "获取应用session：".$sessionId;
//                echo "获取应用cookie：";
//                print_r($cookies);
                $cache->hset($key,'sessionId',$sessionId);
                $cache->hset($key,'cookies',serialize($cookies));
                $cache->hset($key,'checkcode','');
                $cache->expire($key,900);
                //点击账号密码登录,账号密码登录被限制，可能是云服务器ip的原因
//                $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_login\"]"))->click();
                $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all0.png');
                $url = $driver->getCurrentUrl();
                echo $url;exit;
                echo '-------------------------------------------------------------------------------';
//                exit;
                if(!$driver->findElement(WebDriverBy::id('J-input-user'))){
                    echo '没有找到元素';
                    $sHtml = $driver->getPageSource();
                    return  $this->getHtmlToUrl(12314131312313, $sHtml);
                }
                $elementUserName = $driver->findElement(WebDriverBy::id('J-input-user'));
                $elementUserName->sendKeys("lllsssxxx8956@163.com");	//在输入框中输入内容
                $elementPassword = $driver->findElement(WebDriverBy::id('password_rsainput'));
                $elementPassword->sendKeys("aa12312a");	//在输入框中输入内容
                $loginBtn = $driver->findElement(WebDriverBy::id('J-login-btn'));

                $loginBtn->click();	//点击按钮
                sleep(5);
                try{
                    $element = $driver->findElement(WebDriverBy::xpath("//div[@class=\"qrcode qrcode-modern  fn-hide\"]"));
                    $vcodeDst = '/data/image/dzpay/'.$channelMerchantNo.'/vcode.png';
                    $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/all.png');
                    $driver->manage()->window()->maximize();    //将浏览器最大化
                    $driver->takeScreenshot($vcodeDst);  //截取当前网页，该网页有我们需要的验证码

                    $elementVode = $driver->findElement(WebDriverBy::id('J-checkcode-img'));
                    Tools::generateVcodeIMG($elementVode->getLocation(), $elementVode->getSize(),$vcodeDst);
                }catch (\Exception $e){
                    echo $e->getMessage();
                    echo 555;
                }
                $url = $driver->getCurrentUrl();
                echo $url;
//                $driver->quit();
//                $sHtml = $driver->getPageSource();
//                echo $this->getHtmlToUrl($rechargeData['platformOrderNo'], $sHtml);
                exit;
                return  $this->getHtmlToUrl($rechargeData['platformOrderNo'], $sHtml);
            }
        }catch (\Exception $e){
            echo 666;
            print_r($e->getMessage());exit;
        }


    }

    public function testLogin(Request $request, Response $response, $args){
        global $app;
        $cache = $app->getContainer()->redis;
        $key = 'chrome_driver_100004';
        $channelMerchantNo = 100004;
        echo '登录调试';
//                var_dump($caches);
        $sessionId = $cache->hget($key,'sessionId');
        $cache->expire($key,900);
        $driver = RemoteWebDriver::createBySessionID($sessionId);
        $driver->switchTo()->window($driver->getWindowHandles()[0]);
        $driver->navigate()->refresh();
        sleep(5);
        $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/login0.png');
        echo $driver->getCurrentURL();exit;
//        $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_login\"]"))->click();
//        $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/login0.png');
//        exit;
//        $elementcheckcodeImg = $driver->findElement(WebDriverBy::id('J-checkcode-img'))->click();
        $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_qr\"]"))->click();
        sleep(2);
        $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/loginImg.png');exit;
//            $elementUserName = $driver->findElement(WebDriverBy::id('J-input-user'));
//            $elementUserName->clear();
//            $elementUserName->sendKeys("lllsssxxx8956@163.com");	//在输入框中输入内容
//            $elementPassword = $driver->findElement(WebDriverBy::id('password_rsainput'));
//            $elementPassword->clear();
//            $elementPassword->sendKeys("aa12312a");	//在输入框中输入内容
//            $elementCheckCode = $driver->findElement(WebDriverBy::id('J-input-checkcode'));
//            $elementCheckCode->clear();
//            $elementCheckCode->sendKeys('5ufh');
            $loginBtn = $driver->findElement(WebDriverBy::id('J-login-btn'));
//            $driver->getMouse()->mouseDown();
//            $action = new WebDriverActions($driver);
//            $action->moveToElement($loginBtn);
//            $location = $loginBtn->getLocation();
//            var_dump($location->getY(),$location->getX());exit;
//            var_dump($location);exit;
            $loginBtn->click();	//点击按钮
//            $driver->getMouse()->mouseMove($loginBtn->getCoordinates(),$location->getX(),$location->getY());
//            $driver->findElement(WebDriverBy::xpath("//li[@data-status=\"show_qr\"]"))->click();
            sleep(5);
        $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/login.png');
    }

    public function testRecharge(Request $request, Response $response, $args){
        global $app;
        $cache = $app->getContainer()->redis;
        $channelMerchantNo = 100004;
        $amount = 500;
        $bankId = 2;//建设
        if(!isset($this->banks[$bankId]) || $this->banks[$bankId] == 'default'){
            echo '无此银行选项！';
            exit;
        }

        $key = 'chrome_driver_100004';
        echo '充值调试';
//                var_dump($caches);
        $sessionId = $cache->hget($key,'sessionId');
        $cache->expire($key,900);
        try{
            $driver = RemoteWebDriver::createBySessionID($sessionId);
//            $driver->manage()->timeouts()->implicitlyWait(5);    //隐性等待设置15秒
//                $driver->quit();
//            $driver->manage()->window()->maximize();    //将浏览器最大化
//            $driver->switchTo()->window($driver->getWindowHandles()[0]);
//            $driver->navigate()->refresh();
//            sleep(5);
//            $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge0.png');
//            echo $driver->getCurrentURL();exit;
            $this->switchToEndWindow($driver);
//            //1,点击充值按钮
//            $this->recharge1($driver,$channelMerchantNo);
//            $currentUrl =  $driver->getCurrentURL();
//            echo '链接1：' . $currentUrl;
//            //2,选择网银充值页面
//            $this->recharge2($driver,$currentUrl);
//            $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge2.png');
//            $currentUrl =  $driver->getCurrentURL();
//            echo '链接2：' . $currentUrl;
//            $this->recharge2($driver,$currentUrl);
//            $driver->findElement(WebDriverBy::id('J_bankListLink'))->click();//打开银行选项
//            $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge3.png');exit;
            $bankType = $this->banks[$bankId];
            $banklabel = 'for="'. $bankType .'"';

            $driver->switchTo()->frame($driver->findElement(WebDriverBy::xpath("//label[@$banklabel]")));
//            $selectBank = $driver->findElement(WebDriverBy::id($bankType));//选择相应支付银行
            $selectBank = $driver->findElement(WebDriverBy::xpath("//label[@$banklabel]"))->click();
            if(!$selectBank->isSelected()){
                $selectBank->click();
            }
            $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge4.png');exit;
            sleep(1);
            $driver->findElement(WebDriverBy::id("J_amountInput"))->sendKeys($amount);//填写金额
            $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge4.png');exit;
            $driver->findElement(WebDriverBy::id("J_submit"))->click();//确认
            sleep(5);
            $this->switchToEndWindow($driver);
            $currentUrl =  $driver->getCurrentURL();
            $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge5.png');
            echo '支付链接_blank：' . $currentUrl;
        }catch (\Exception $e){
            echo '运行错误：'. $e->getMessage().PHP_EOL;
            echo '错误行：'. $e->getLine().PHP_EOL;
            echo '错误代码：'. $e->getCode().PHP_EOL;
        }

    }

    public function test123(){
        $channelMerchantNo = 100004;
        echo '开始创建新应用：';
//                $chrome_driver = $cache->getItem($key);
        $host = 'http://localhost:4444/wd/hub';        // selenium-server地址，此处传入默认值
        $codeDst = '/data/image/dzpay/'.$channelMerchantNo.'/qrcode.png';
        $waitSeconds = 5;
        $options = new ChromeOptions();
//            $options->setBinary('/usr/bin/chromedriver');  //指定浏览器程序路径
        $options->addArguments(
            array(
                '--no-sandbox',                        // 解决DevToolsActivePort文件不存在的报错
//                '--whitelisted-ips',
                'window-size=1080x1920',               // 指定浏览器分辨率
//                        '--disable-gpu',                       // 谷歌文档提到需要加上这个属性来规避bug
                '--hide-scrollbars',                   // 隐藏滚动条, 应对一些特殊页面
                'blink-settings=imagesEnabled=true',  // 不加载图片, 提升速度
                '--headless',                          // 浏览器不提供可视化页面
//                        '--disable-dev-shm-usage',
            )
        );

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $driver = RemoteWebDriver::create($host, $capabilities,10000);
//            self::$driver->get('http://www.yimuhe.com');
//                $driver->get('https://auth.alipay.com/login/index.htm?goto=https://mrchportalweb.alipay.com/user/home');
        $driver->get('https://business.alipay.com/user/home');
//        $driver->get('https://www.baidu.com');
        var_dump($driver->getPageSource());
        $js = "window.scrollTo(0,document.body.scrollHeight)";	//滚动至底部
        //$js = "window.scrollBy(0,100000000);";  //也可以把值设大一点，达到底部的效果
        $driver->executeScript($js);
        $driver->manage()->window()->maximize();    //将浏览器最大化
        $driver->takeScreenshot($codeDst);  //截取当前网页，该网页有我们需要的验证码
//        $driver->quit();
    }

    public function switchToEndWindow($driver){

    $arr = $driver->getWindowHandles();
    foreach ($arr as $k=>$v){
        if($k == (count($arr)-1)){
            $driver->switchTo()->window($v);
            }
        }
    }

    private function recharge1($driver,$channelMerchantNo){
        $rechargeBtn = $driver->findElement(WebDriverBy::xpath("//button[@data-aspm-click=\"c68941.d141971\"]"));
        $rechargeBtn->click();	//点击按钮
        sleep(3);
        $this->switchToEndWindow($driver);
        $driver->takeScreenshot('/data/image/dzpay/'.$channelMerchantNo.'/recharge1.png');
    }

    private function recharge2($driver,$currentUrl){
        if(strpos($currentUrl,'templateFlow') === false){
            $driver->findElement(WebDriverBy::linkText('网银充值'))->click();
        }
        $driver->wait(5)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::id('J_bankListLink')
            )
        );
    }

}
