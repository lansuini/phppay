<?php

namespace App\Controllers\GM;

use App\Helpers\GoogleAuthenticator;use App\Helpers\Tools;
use App\Models\SystemAccount;
use App\Models\SystemAccountLoginLog;

use Psr\Http\Message\ResponseInterface as Response;

use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/login.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
        ]);
    }

    public function head(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/head.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus
        ]);
    }

    public function login(Request $request, Response $response, $args)
    {
        $loginName = $request->getParam('loginName');
        $loginPwd = Tools::getHashPassword($request->getParam('loginPwd'));
        $data = SystemAccount::where('loginName', $loginName)
        // ->where('loginPwd', $loginPwd)
            /* ->where('status', 'Normal') */
            ->first();
        /* dump($data->status);exit; */
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'login';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });
        $logger->debug("gm管理后台登录请求");

        if (empty($data)) {
            return $response->withJson([
                'success' => 0,
                'result' => '用户名或者密码错误',
            ]);
        } else if($data->status == 'Close'){
            return $response->withJson([
                'success' => 0,
                'result' => '账号已被封',
            ]);
        } else if (!empty($data) && $data->loginFailNum > 5) {
            SystemAccountLoginLog::create([
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $data->id,
                'status' => 'Fail',
                'remark' => 'LOGIN_FAIL_COUNT',
            ]);
            $data->loginFailNum = $data->loginFailNum + 1;
            $data->save();
            return $response->withJson([
                'success' => 0,
                'result' => '用户名登录错误次数过多',
            ]);
        } else if (!empty($data) && $loginPwd != $data->loginPwd) {
            SystemAccountLoginLog::insert([
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $data->id,
                'status' => 'Fail',
                'remark' => 'PASSWORD_ERROR',
            ]);
            $data->loginFailNum = $data->loginFailNum + 1;
            $data->save();
            return $response->withJson([
                'success' => 0,
                'result' => '用户名或者密码错误',
            ]);
        } else {
            $_SESSION['accountId'] = $data->id;
            $_SESSION['loginName'] = $loginName;
            $_SESSION['userName'] = $data->userName;
            $_SESSION['loginPwdAlterTime'] = $data->loginPwdAlterTime;
            $_SESSION['googleAuthSecretKey'] = $data->googleAuthSecretKey;
            $_SESSION['googleAuthCheck'] = !empty($data->googleAuthSecretKey) ? true : false;
            $_SESSION['role'] = $data->role;
            $redis = $this->c->redis;
            $redis->setex('dadong_login_station_userid_'.$data->id,30*60, 1);
            $data->loginFailNum = 0;
            $data->save();
            SystemAccountLoginLog::insert([
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $data->id,
                'status' => 'Success',
                'remark' => '',
            ]);
            return $response->withJson([
                'success' => 1,
                'result' => '登录成功',
            ]);
        }
    }

    public function logout(Request $request, Response $response, $args)
    {
        $redis = $this->c->redis;
        $redis->del('dadong_login_station_userid_' . $_SESSION['accountId']);

        $_SESSION['accountId'] = null;
        $_SESSION['loginName'] = null;
        $_SESSION['userName'] = null;
        $_SESSION['loginPwdAlterTime'] = null;
        $_SESSION['googleAuthSecretKey'] = null;
        $_SESSION['googleAuthCheck'] = null;
        
        return $response->withRedirect('/');
    }

    public function googleAuth(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/googleauth.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
        ]);
    }

    public function doGoogleAuth(Request $request, Response $response, $args)
    {
        $code = $request->getParam('code');
        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $code, 2);
        if ($checkResult) {
            $_SESSION['googleAuthCheck'] = false;
            return $response->withJson([
                'success' => 1,
                'result' => '登录成功',
            ]);
        } else {
            return $response->withJson([
                'success' => 0,
                'result' => '验证失败',
            ]);
        }
    }
}
