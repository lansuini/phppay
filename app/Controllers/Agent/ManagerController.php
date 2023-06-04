<?php

namespace App\Controllers\Merchant;

use App\Helpers\GoogleAuthenticator;
use App\Helpers\Tools;
use App\Models\MerchantAccount;
use App\Models\MerchantAccountActionLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class ManagerController extends MerchantController
{
    public function changesecurepwd(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/changesecurepwd.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function bindgoogleauth(Request $request, Response $response, $args)
    {
        $secret = (new GoogleAuthenticator)->createSecret();
        $_SESSION['googleNewSecret'] = $secret;
        $name = $_SESSION['loginName'] . '@' . $_SERVER['HTTP_HOST'];
        $googleCaptcha = (new GoogleAuthenticator)->getQRCodeGoogleUrl($name, $secret, $title = null, $params = array());
        $isHaveGoogleCaptcha = !empty($_SESSION['googleAuthSecretKey']) ? true : false;
        return $this->c->view->render($response, 'merchant/bindgoogleauth.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
            'googleCaptcha' => $googleCaptcha,
            'isHaveGoogleCaptcha' => $isHaveGoogleCaptcha,
        ]);
    }

    public function doBindgoogleauth(Request $request, Response $response, $args)
    {
        $code = $request->getParam('code');
        $secret = $_SESSION['googleNewSecret'];
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $code, 2);
        if ($checkResult) {
            MerchantAccountActionLog::insert([
                'action' => 'BIND_GOOGLE_AUTH',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Success',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);

            $_SESSION['googleAuthSecretKey'] = Tools::encrypt($secret);
            MerchantAccount::where('accountId', $_SESSION['accountId'])
                ->update([
                    'googleAuthSecretKey' => $_SESSION['googleAuthSecretKey'],
                ]);

            (new MerchantAccount)->refreshCache(['accountId' => $_SESSION['accountId']]);
            return $response->withJson([
                'success' => 1,
                'result' => '绑定成功',
            ]);
        } else {
            return $response->withJson([
                'success' => 0,
                'result' => '验证失败',
            ]);
        }
    }

    public function changeloginpwd(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/changeloginpwd.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function doChangeloginpwd(Request $request, Response $response, $args)
    {
        $oldPwd = $request->getParam('oldPwd');
        $newPwd = $request->getParam('newPwd');

        $validator = $this->c->validator->validate($request, [
            'newPwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            'oldPwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        $merchantAccount = new MerchantAccount;
        $account = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if ($account->loginPwd != Tools::getHashPassword($oldPwd) || strlen($newPwd) < 6 || $oldPwd == $newPwd) {
            MerchantAccountActionLog::insert([
                'action' => 'UPDATE_PASSWORD',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Fail',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);
            return $response->withJson([
                'success' => 0,
                'result' => '旧密码不正确或新密码长度不够6位',
            ]);
        } else {
            $merchantAccount->where('accountId', $_SESSION['accountId'])->update([
                'loginPwd' => Tools::getHashPassword($newPwd),
                'loginPwdAlterTime' => date('YmdHis'),
                'loginFailNum' => 0,
            ]);
            $merchantAccount->refreshCache(['accountId' => $_SESSION['accountId']]);
            MerchantAccountActionLog::insert([
                'action' => 'UPDATE_PASSWORD',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Success',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);
            $_SESSION['loginName'] = null;
        }

        return $response->withJson([
            'success' => 1,
            'result' => '密码修改成功',
        ]);
    }

    public function doChangesecurepwd(Request $request, Response $response, $args)
    {
        $oldPwd = $request->getParam('oldPwd');
        $newPwd = $request->getParam('newPwd');

        $validator = $this->c->validator->validate($request, [
            'newPwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            'oldPwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        $merchantAccount = new MerchantAccount;
        $account = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if ($account->securePwd != Tools::getHashPassword($oldPwd) || strlen($newPwd) < 6 || $oldPwd == $newPwd) {
            MerchantAccountActionLog::insert([
                'action' => 'UPDATE_PAY_PASSWORD',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Fail',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);
            return $response->withJson([
                'success' => 0,
                'result' => '旧密码不正确或新密码长度不够6位',
            ]);
        } else {
            $merchantAccount->where('accountId', $_SESSION['accountId'])->update([
                'securePwd' => Tools::getHashPassword($newPwd),
            ]);
            $merchantAccount->refreshCache(['accountId' => $_SESSION['accountId']]);
            MerchantAccountActionLog::insert([
                'action' => 'UPDATE_PAY_PASSWORD',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Success',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);
        }

        return $response->withJson([
            'success' => 1,
            'result' => '密码修改成功',
        ]);
    }
}
