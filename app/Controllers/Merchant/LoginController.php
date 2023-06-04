<?php

namespace App\Controllers\Merchant;

use App\Helpers\GoogleAuthenticator;
use App\Helpers\Tools;
use App\Models\MerchantAccount;
use App\Models\Merchant;
use App\Models\MerchantAccountLoginLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/login.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
        ]);
    }

    public function login(Request $request, Response $response, $args)
    {
        $loginName = $request->getParam('loginName');
        $loginPwd = Tools::getHashPassword($request->getParam('loginPwd'));
        $model = new MerchantAccount;
        $mrechant = new Merchant;
        $data = $model->getCacheByLoginName($loginName);
        $arr = [];
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'login';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->debug("商户后台登录请求");
        if(!empty($data)){
            $res = $mrechant->getCacheByMerchantNo($data['merchantNo']);
            if(!empty($res['loginIpWhite'])){
                $arr = explode(",", $res['loginIpWhite']);
            }else{//强制绑定登录IP白名单
                return $response->withJson([
                    'success' => 0,
                    'result' => '请联系商务绑定登录IP白名单',
                ]);
            }
        }
        if (empty($data)) {
            return $response->withJson([
                'success' => 0,
                'result' => '用户名或者密码错误',
            ]);
        } else if(!empty($data) && !empty($arr) && !in_array(Tools::getIp(), $arr)){
            return $response->withJson([
                'success' => 0,
                'result' => 'ip限制，不允许登录，当前登录IP：'.Tools::getIp(),
            ]);
        } else if ($data['status'] != 'Normal') {
            return $response->withJson([
                'success' => 0,
                'result' => '账号异常',
            ]);
        } else if (!empty($data) && $data['loginFailNum'] > 5) {
            // MerchantAccountLoginLog::create([
            //     'ip' => Tools::getIp(),
            //     'ipDesc' => Tools::getIpDesc(),
            //     'accountId' => $data->id,
            //     'status' => 'Fail',
            //     'remark' => 'LOGIN_FAIL_COUNT',
            // ]);
            // $account = $model->where('accountId', $data['accountId'])->first();
            // $data->loginFailNum = $data['loginFailNum'] + 1;
            // $data->save();
            // $model->refreshCache(['accountId' => $data['accountId']]);
            return $response->withJson([
                'success' => 0,
                'result' => '用户名登录错误次数过多',
            ]);
        } else if (!empty($data) && $loginPwd != $data['loginPwd']) {
            MerchantAccountLoginLog::insert([
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $data['accountId'],
                'status' => 'Fail',
                'remark' => 'PASSWORD_ERROR',
            ]);
            $account = $model->where('accountId', $data['accountId'])->first();
            $account->loginFailNum = $account['loginFailNum'] + 1;
            if ($account->loginFailNum >= 5) {
                $account->status = 'Exception';
            }
            $account->save();
            $model->refreshCache(['accountId' => $data['accountId']]);
            return $response->withJson([
                'success' => 0,
                'result' => '用户名或者密码错误',
            ]);
        } else {
            $_SESSION['merchantNo'] = $data['merchantNo'];
            $_SESSION['merchantId'] = $data['merchantId'];
            $_SESSION['accountId'] = $data['accountId'];
            $_SESSION['loginName'] = $loginName;
            $_SESSION['userName'] = $data['userName'];
            $_SESSION['loginPwdAlterTime'] = $data['loginPwdAlterTime'];
            $_SESSION['googleAuthCheck'] = !empty($data['googleAuthSecretKey']) ? true : false;
            $_SESSION['googleAuthSecretKey'] = $data['googleAuthSecretKey'];

            $redis = $this->c->redis;
            $redis->set('dadong_merchant_login_userid_'.$data['accountId'], 1);

            $account = $model->where('accountId', $data['accountId'])->first();
            $account->loginFailNum = 0;
            $account->latestLoginTime = date('YmdHis');
            $account->save();
            MerchantAccountLoginLog::insert([
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $data['accountId'],
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
        $_SESSION['merchantNo'] = null;
        $_SESSION['merchantId'] = null;
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
