<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\MerchantAccount;
use PSR\Container\ContainerInterface;

abstract class MerchantController
{
    protected $c;
    protected $logger;
    protected $session;
    protected $code;
    protected $menus;
    public function __construct(ContainerInterface $container)
    {
        ini_set('session.save_handler', 'redis');
        if(getenv('REDIS_PASSWORD')){
            ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT') . '?auth=' . getenv('REDIS_PASSWORD') . '&database=10');
        }else{
            ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT') . '?database=10');
        }
        ini_set('session.gc_maxlifetime', '1800');
        session_start();
        $this->c = $container;
        $this->logger = $container->logger;
        $this->code = $container->code;
        $request = $container['request'];
        $response = $container['response'];
        $path = $request->getUri()->getPath();

        if ($path == '/') {
            session_regenerate_id(true);
        }

        $publicPath = [
            '/',
            '/api/manager/login',
            '/logout',
        ];

        $loginName = $_SESSION['loginName'] ?? null;

        $redis = $this->c->redis;
        $login_status = -1;
        if (!empty($_SESSION['accountId'])) {
            $login_status = $redis->get('dadong_merchant_login_userid_'.$_SESSION['accountId']);
        }
        if (empty($loginName) && !in_array($path, $publicPath)) {
            Tools::getJsRedirect('/');
        } else {
            if ($login_status == 0) {
                $redis = $this->c->redis;
                $redis->del('dadong_merchant_login_userid_'.$_SESSION['accountId']);

                $_SESSION['merchantNo'] = null;
                $_SESSION['merchantId'] = null;
                $_SESSION['accountId'] = null;
                $_SESSION['loginName'] = null;
                $_SESSION['userName'] = null;
                $_SESSION['loginPwdAlterTime'] = null;
                $_SESSION['googleAuthSecretKey'] = null;
                $_SESSION['googleAuthCheck'] = null;
                Tools::getJsRedirect('/');
            }
        }

        $merchantId = $_SESSION['merchantId'] ?? null;
        $mrechant = new Merchant;
        $res = $mrechant->getCacheByMerchantId($merchantId);
        $ipWhite = [];
        if (!empty($res['loginIpWhite'])) {
            $ipWhite = explode(",", $res['loginIpWhite']);
        }
        if (!empty($loginName) && !empty($ipWhite) && !in_array(Tools::getIp(), $ipWhite) && $path != '/') {
            session_destroy();
            Tools::getJsRedirect('/');
        }

        $googleAuthCheck = isset($_SESSION['googleAuthCheck']) && !empty($loginName) ? $_SESSION['googleAuthCheck'] : false;
        $googlePath = [
            '/googleauth',
            '/api/manager/googleauth',
            '/api/manager/login',
            '/logout',
            '/',
        ];

        if ($googleAuthCheck && !in_array($path, $googlePath)) {
            Tools::getJsRedirect('/googleauth');
        }

        $forceGoogleAuthLogin = getenv('MERCHANT_FORCE_GOOGLE_AUTH_LOGIN');
        $isBindGoogleCode = (isset($_SESSION['googleAuthSecretKey']) && !empty($_SESSION['googleAuthSecretKey'])) ? true : false;
        $forcePath = [
            '/googleauth',
            '/api/manager/googleauth',
            '/api/manager/login',
            '/logout',
            '/',
            '/bindgoogleauth',
            '/api/manager/bindgoogleauth',
            '/api/index/tips',
        ];
        if ($forceGoogleAuthLogin === 'true' && !$isBindGoogleCode && !in_array($path, $forcePath)) {
            Tools::getJsRedirect('/bindgoogleauth');
        }
        $this->menus = require __DIR__ . '/../../../bootstrap/merchantmenu.php';
        if(isset($res['openManualSettlement'])&& !$res['openManualSettlement']){
            unset($this->menus[4]);
        }
        $merchantAccount=new MerchantAccount();
        $resAccount = $merchantAccount->getCacheByLoginName($loginName);
        if(isset($resAccount['userLevel'])&& $resAccount['userLevel']=='PlatformManager'){
            $menus[]=$this->menus[11];
            $this->menus = $menus;
        }else{
            unset($this->menus[11]);
        }

    }
}
