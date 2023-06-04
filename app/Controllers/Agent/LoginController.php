<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Helpers\GoogleAuthenticator;
use App\Helpers\Tools;
use App\Models\Agent;
use App\Models\AgentLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends AgentController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'agent/login.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
        ]);
    }

    public function login(Request $request, Response $response, $args)
    {
        $loginName = $request->getParam('loginName');
        $loginPwd = Tools::getHashPassword($request->getParam('loginPwd'));
        $model = new Agent();
        $data = $model->refreshCache();
        $data = $model->getCacheByLoginName($loginName);
        $arr = [];
        $success = 1;
        if (empty($data)) {
            return $response->withJson([
                'success' => 0,
                'result' => '请输入用户名密码',
            ]);
        }
        if(!empty(trim($data['loginIpWhite']))){
            $arr = explode(",", $data['loginIpWhite']);
        }else{//强制绑定登录IP白名单
//            return $response->withJson([
//                'success' => 0,
//                'result' => '请联系商务绑定登录IP白名单',
//            ]);
        }
        if($success && !empty($arr) && !in_array(Tools::getIp(), $arr)){
            $success = 0;
            $res = [
                'success' => 0,
                'result' => 'ip限制，不允许登录',
            ];
        }
        if ($success && $data['status'] != 'Normal') {
            $success = 0;
            $res =  [
                'success' => 0,
                'result' => '账号停用，请联系商务人员',
            ];
        }

        if ($success && $model->getCacheLoginFailNum($data['id']) > 5) {
            $success = 0;
            $res = [
                'success' => 0,
                'result' => '登录错误次数过多,请24小时后重试',
            ];
        }
        if ($success && $loginPwd != $data['loginPwd']) {
            $model->setCacheLoginFailNum($data['id']);
            $success = 0;
            $res = [
                'success' => 0,
                'result' => '用户名或者密码错误',
            ];
        }
        if($success){
            $_SESSION['userId'] = $data['id'];
            $_SESSION['loginName'] = $loginName;
            $_SESSION['userName'] = $data['nickName'];

            $redis = $this->c->redis;
            $redis->set('dadong_agent_login_userid_'.$data['id'], 1);

            $account = $model->where('id', $data['id'])->first();
            $account->loginIP = Tools::getIp();
            $account->loginDate = date('Y-m-d H:i:s');
            $account->save();
            $res = [
                'success' => 1,
                'result' => '登录成功',
            ];
        }
        $log = new AgentLog();
        $log->action = 'LOGIN';
        $log->actionBeforeData = '';
        $log->actionAfterData = '';
        $log->optId = 0;
        $log->optName = $loginName;
        $log->status = 'Success';
        $log->desc = $res['result'];
        $log->ip = Tools::getIp();
        $log->ipDesc = Tools::getIpDesc(Tools::getIp());
        $log->save();
        return $response->withJson($res);
    }

    public function logout(Request $request, Response $response, $args)
    {
        $log = new AgentLog();
        $log->action = 'LOGOUT';
        $log->actionBeforeData = '';
        $log->actionAfterData = '';
        $log->optId = 0;
        $log->optName = $_SESSION['loginName'];
        $log->status = 'Success';
        $log->desc = '退出登陆';
        $log->ip = Tools::getIp();
        $log->ipDesc = Tools::getIpDesc(Tools::getIp());
        $log->save();
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

    public function modifyLoginPwd(Request $request, Response $response, $args){
        $oldPwd = Tools::getHashPassword($request->getParam('oldPwd'));
        $newPwd = Tools::getHashPassword($request->getParam('newPwd'));
        $model = new Agent();
        $loginPwd = $model->where('id',$_SESSION['userId'])->value('loginPwd');
        if(!$oldPwd || !$newPwd) {
            return $response->withJson([
                'result' => '新旧密码不能为空',
                'success' => 0,
            ]);
        }
        if($loginPwd != $oldPwd) {
            return $response->withJson([
                'result' => '旧密码不正确',
                'success' => 0,
            ]);
        }
        Agent::where('id',$_SESSION['userId'])->update(['loginPwd'=>$newPwd]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);
    }

    public function modifyPayPwd(Request $request, Response $response, $args){
        $oldPwd = Tools::getHashPassword($request->getParam('oldPwd'));
        $newPwd = Tools::getHashPassword($request->getParam('newPwd'));
        $model = new Agent();
        $payPwd = $model->where('id',$_SESSION['userId'])->value('securePwd');
        if(!$oldPwd || !$newPwd) {
            return $response->withJson([
                'result' => '新旧密码不能为空',
                'success' => 0,
            ]);
        }
        if($payPwd != $oldPwd) {
            return $response->withJson([
                'result' => '旧密码不正确',
                'success' => 0,
            ]);
        }
        Agent::where('id',$_SESSION['userId'])->update(['securePwd'=>$newPwd]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);
    }
}
