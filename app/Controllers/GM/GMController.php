<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use PSR\Container\ContainerInterface;

abstract class GMController
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
            ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT') . '?auth=' . getenv('REDIS_PASSWORD') . '&database=11');
        }else{
            ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT') . '?database=11');
        }
        ini_set('session.gc_maxlifetime', '1800');
        session_start();
        $this->c = $container;
        $this->logger = $container->logger;
        // $this->session = $container->session;
        $this->code = $container->code;
        $request = $container['request'];
        $response = $container['response'];
        $path = $request->getUri()->getPath();

        if ($path == '/') {
            session_regenerate_id(true);
        }

        $publicPath = [
            '/',
            '/ip/permission',
            '/api/manager/login',
            '/logout',
        ];

        $ipWhite = getenv('GM_IPWHITE');

        if (!empty($ipWhite) && !in_array(Tools::getIp(), explode(',', $ipWhite)) && $path != '/ip/permission') {
            Tools::getJsRedirect('/ip/permission');
        }

        $loginName = $_SESSION['loginName'] ?? null;
        $redis = $this->c->redis;
        $login_status = -1;
        if (!empty($_SESSION['accountId'])) {
            $login_status = $redis->get('dadong_login_station_userid_' . $_SESSION['accountId']);
        }
        if (empty($loginName) && !in_array($path, $publicPath)) {
            Tools::getJsRedirect('/');
        } else {
            if ($login_status == 0) {
                $redis = $this->c->redis;
                $redis->del('dadong_login_station_userid_' . $_SESSION['accountId']);

                $_SESSION['accountId'] = null;
                $_SESSION['loginName'] = null;
                $_SESSION['userName'] = null;
                $_SESSION['loginPwdAlterTime'] = null;
                $_SESSION['googleAuthSecretKey'] = null;
                $_SESSION['googleAuthCheck'] = null;
                Tools::getJsRedirect('/');
            }
        }
        $googleAuthCheck = isset($_SESSION['googleAuthCheck']) && !empty($loginName) ? $_SESSION['googleAuthCheck'] : false;
        $googlePath = [
            '/googleauth',
            '/api/manager/googleauth',
            '/api/manager/login',
            '/logout',
            '/ip/permission',
            '/',
        ];

        if ($googleAuthCheck && !in_array($path, $googlePath)) {
            Tools::getJsRedirect('/googleauth');
        }

        $forceGoogleAuthLogin = getenv('GM_FORCE_GOOGLE_AUTH_LOGIN');
        $isBindGoogleCode = (isset($_SESSION['googleAuthSecretKey']) && !empty($_SESSION['googleAuthSecretKey'])) ? true : false;
        $forcePath = [
            '/googleauth',
            '/api/manager/googleauth',
            '/api/manager/login',
            '/logout',
            '/ip/permission',
            '/',
            '/manager/bindgoogleauth',
            '/api/manager/bindgoogleauth',
        ];
        if ($forceGoogleAuthLogin === 'true' && !$isBindGoogleCode && !in_array($path, $forcePath)) {
            Tools::getJsRedirect('/manager/bindgoogleauth');
        }

        $account_Power = '';
        if (isset($_SESSION['role']) && isset($this->code['accountPowerCode'][$_SESSION['role']])) {
            $account_Power = $this->code['accountPowerCode'][$_SESSION['role']];
        }

        $publicPath[] = "/api/getbasedata";
        if ($account_Power != '') {
            $account_Power = explode(',', $account_Power);
            $menus = require __DIR__ . '/../../../bootstrap/gmmenu.php';
//            $request_uri = $_SERVER['REQUEST_URI'];
            $tmpMenus = [];
            foreach ($menus as $key => &$val) {
//                $val['id'] = $key;
//                array_push($tmpMenus,$val);
                if (!in_array($key, $account_Power)) {
                    unset($menus[$key]);
                } else {
                    if (isset($val['pu'])) {
                        foreach ($val['pu'] as $k => $v) {
                            $publicPath[] = $v;
                        }
                    }
                }

                if (isset($val['c'])) {
                    foreach ($val['c'] as $k => $v) {

                        if (!in_array($k, $account_Power)) {
                            unset($menus[$key]['c'][$k]);
                        } else {
                            if (isset($v['pu'])) {
                                foreach ($v['pu'] as $value) {
                                    $publicPath[] = $value;
                                }
                            }
                        }
                    }
                }
            }
//            foreach ($tmpMenus as &$M){
//                if(isset($M['c'])){
//                    $cm = [];
//                    foreach ($M['c'] as $kid => $c){
//                        $c['id'] = $kid;
//                        array_push($cm,$c);
//                    }
//                    $M['c'] = $cm;
//                }
//            }
//            print_r(json_encode($tmpMenus));exit;
            $this->menus = $menus;

            /* $interceptUrl = $this->code['interceptUrl']; */
            if (strpos($path, 'api/') == false) {
                if (!empty($loginName) && !in_array($path, $publicPath)) {
                    Tools::getJsRedirect('/');
                }
            }

        } else {
            if (!empty($loginName) && !in_array($path, $publicPath)) {
                Tools::getJsRedirect('/');
            }
        }
        //定时请求的接口不刷新最后登陆时间
        $timeInterval = [
            '/api/notify',
            '/api/settlementorder/timeInterval',
        ];
        if(!in_array($path,$timeInterval) && isset($_SESSION['accountId'])){
            $redis->setex('dadong_login_station_userid_'.$_SESSION['accountId'],30*60, 1);
        }
    }

}
