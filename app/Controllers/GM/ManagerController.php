<?php

namespace App\Controllers\GM;

use App\Helpers\GoogleAuthenticator;use App\Helpers\Tools;
use App\Models\Message;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use App\Models\SystemAccountLoginLog;
use App\Models\SystemConfig;
use App\Models\BlackUserSettlement;
use App\Models\PlatformNotify;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class ManagerController extends GMController
{
    public function changeloginname(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/manager/changeloginname.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function doChangeloginname(Request $request, Response $response, $args)
    {
        $loginName = $request->getParam('loginName');

        $validator = $this->c->validator->validate($request, [
            'loginName' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
        ]);

        /*if (!$validator->isValid()) {
        return $response->withJson([
        'result' => '验证不通过',
        'success' => 0,
        ]);
        }*/

        $systemAccount = new SystemAccount;
        $count = $systemAccount->where('loginName', $loginName)->count();
        if ($count) {
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_LOGINNAME',
                'actionBeforeData' => $_SESSION['loginName'],
                'actionAfterData' => $loginName,
                'status' => 'Fail',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            return $response->withJson([
                'success' => 0,
                'result' => '账号已存在',
            ]);
        } else {
            $systemAccount->where('id', $_SESSION['accountId'])->update(['loginName' => $loginName]);
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_LOGINNAME',
                'actionBeforeData' => $_SESSION['loginName'],
                'actionAfterData' => $loginName,
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            $_SESSION['loginName'] = $loginName;
        }
        return $response->withJson([
            'success' => 1,
            'result' => '账号修改成功',
        ]);
    }

    public function bindgoogleauth(Request $request, Response $response, $args)
    {
        $secret = (new GoogleAuthenticator)->createSecret();
        $_SESSION['googleNewSecret'] = $secret;
        $name = $_SESSION['loginName'] . '@' . $_SERVER['HTTP_HOST'];
        $googleCaptcha = (new GoogleAuthenticator)->getQRCodeGoogleUrl($name, $secret, $title = null, $params = array());
        $isHaveGoogleCaptcha = !empty($_SESSION['googleAuthSecretKey']) ? true : false;
        return $this->c->view->render($response, 'gm/manager/bindgoogleauth.twig', [
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
            SystemAccountActionLog::insert([
                'action' => 'BIND_GOOGLE_AUTH',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Success',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);

            $_SESSION['googleAuthSecretKey'] = Tools::encrypt($secret);
            SystemAccount::where('id', $_SESSION['accountId'])
                ->update([
                    'googleAuthSecretKey' => $_SESSION['googleAuthSecretKey'],
                ]);

            // (new SystemAccount)->refreshCache(['accountId' => $_SESSION['accountId']]);
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
        return $this->c->view->render($response, 'gm/manager/changeloginpwd.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
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
        $systemAccount = new SystemAccount;
        $account = $systemAccount->where('id', $_SESSION['accountId'])->first();
        if ($account->loginPwd != Tools::getHashPassword($oldPwd) || strlen($newPwd) < 6 || $oldPwd == $newPwd) {
            SystemAccountActionLog::insert([
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
            $systemAccount->where('id', $_SESSION['accountId'])->update([
                'loginPwd' => Tools::getHashPassword($newPwd),
                'loginPwdAlterTime' => date('YmdHis'),
                'loginFailNum' => 0,
            ]);
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_PASSWORD',
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'status' => 'Success',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'accountId' => $_SESSION['accountId'],
            ]);
            $_SESSION['loginName'] = null;
            $redis = $this->c->redis;
            $redis->del('dadong_login_station_userid_' . $_SESSION['accountId']);

        }

        return $response->withJson([
            'success' => 1,
            'result' => '密码修改成功',
        ]);
    }

    public function adminList(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/manager/adminList.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function getmanagerlist(Request $request, Response $response, $args)
    {
        $userName = $request->getParam('merchantNo');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $model = new SystemAccount;

        $total = $model->count();
        $data = $model->orderBy('id', 'desc')->limit($limit)->offset($offset)->get();
        foreach ($data as $key=>$val){
            $data[$key]['googleBind'] = empty($val->googleAuthSecretKey) ? '未绑定' : '已绑定';
        }
        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $data,
        ]);
    }

    //修改管理员
    public function adminupdate(Request $request, Response $response, $args)
    {
        $txtRole = $request->getParam('role');
        $status = $request->getParam('status');
        $id = $request->getParam('id');
        $validator = $this->c->validator->validate($request, [
            'role' => Validator::noWhitespace()->notBlank(),
            'status' => Validator::noWhitespace()->notBlank(),
            'id' => Validator::noWhitespace()->notBlank(),
        ]);

        $systemAccount = new SystemAccount;

        $model = $systemAccount->where('id', $id)->first();


        //--------------------------------------谷歌验证码的验证---------------------start---------------------------
//        if($model->role != 5){
            $params = $request->getParams();
            $vailRes=$this->googleAuthVail($params);
            if($vailRes['success'] == 0){
                return $response->withJson([
                    'result' => $vailRes['result'],
                    'success' => 0,
                ]);
            }
//        }

        //--------------------------------------------------------------------------------end---------------------------

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $model->toJson();
        $model->role = $txtRole;
        $model->status = $status;
        $res = $model->save();
        if ($res) {
            $redis = $this->c->redis;
            $redis->set('dadong_login_station_userid_' . $id, 0);
        }

        /* $merchant->refreshCache(['id' => $model->id]); */
        SystemAccountActionLog::insert([
            [
                'action' => 'UPDATE_MERCHANT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ],
        ]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);

    }


    //管理员操作非管理员时需要谷歌验证码的验证
    private function googleAuthVail($params){
        $success=1;
        $result="";
        $logger = $this->c->logger;
        $model = new SystemAccount;
        $adminRole=$model->where('id',$_SESSION['accountId'])->first();

        if(!$adminRole){
            $logger->error("管理员不存在！");
            $success=0;
            $result='管理员不存在！';
            return ['result'=>$result,'success'=>$success];
        }

        if($adminRole->role  != 5){
            $logger->error("没有权限操作此功能！");
            $result = '风险操作！';
            $success = 0;
            return ['result'=>$result,'success'=>$success];
        }

        if(!$adminRole->googleAuthSecretKey){
            $logger->error("请绑定谷歌验证码后再操作！！");
            $result = '请绑定谷歌验证码后再操作！';
            $success = 0;
            return ['result'=>$result,'success'=>$success];
        }

        if(!isset($params['googleAuth']) || !$params['googleAuth']){
            $logger->error("请输入谷歌验证码 ！");
            $result = '请输入谷歌验证码！';
            $success = 0;
            return ['result'=>$result,'success'=>$success];
        }
        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $params['googleAuth'], 2);

        if(!$checkResult) {
            $logger->error("谷歌验证码错误，请重新输入 ！");
            $result = '谷歌验证码错误，请重新输入！';
            $success = 0;
            return ['result'=>$result,'success'=>$success];
        }

        return ['result'=>$result,'success'=>$success];
    }

    public function delaccount(Request $request, Response $response, $args)
    {
        $id = $request->getParam('id');
        $systemAccount = new SystemAccount;
        $model = $systemAccount->where('id', $id)->first();

        //--------------------------------------谷歌验证码的验证---------------------start---------------------------
//        if($model->role != 5){
            $params = $request->getParams();
            $vailRes=$this->googleAuthVail($params);
            if($vailRes['success'] == 0){
                return $response->withJson([
                    'result' => $vailRes['result'],
                    'success' => 0,
                ]);
            }
//        }
        //--------------------------------------------------------------------------------end---------------------------


        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $model->toJson();
        $model->id = $id;
        $res = $model->delete();
        if ($res) {
            $redis = $this->c->redis;
            $redis->set('dadong_login_station_userid_' . $id, 0);
        }

        SystemAccountActionLog::insert([
            [
                'action' => 'UPDATE_MERCHANT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ],
        ]);
        return $response->withJson([
            'result' => '删除成功',
            'success' => 1,
        ]);
    }

    //设置留言邮箱
    public function editEmail(Request $request, Response $response, $args){
        $email=$request->getParam('email');
        if(!$email ){
            return $response->withJson([
                'result' => '邮箱不能为空！',
                'success' => 0,
            ]);
        }

        $res=SystemConfig::where('key',"email")
            ->update(['value'=>$email]);
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

    //设置留言备注
    public function editRemarks(Request $request, Response $response, $args){
        $rem=$request->getParam('remarks');
        $id=$request->getParam('id');
        if(!$rem || !$id){
            return $response->withJson([
                'result' => '备注或id不能为空！',
                'success' => 0,
            ]);
        }

        $res=Message::where('id',$id)
            ->update(['remarks'=>$rem]);
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

    //展示留言信息页面
    public function messageInfoPage(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/manager/messageInfoPage.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    //获取留言信息
    public function messageInfo(Request $request, Response $response, $args){
        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', 20);

        $msg=new Message();
        $total=$msg->count();
        $rows = $msg->offset($offset)->limit($limit)->get()->toArray();
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    //代付黑名单
    public function blackUserSettlement(Request $request, Response $response, $args){

        return $this->c->view->render($response, 'gm/manager/blackUserSettlement.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);

    }

    public function blackUserSettlementList(Request $request, Response $response, $args){

        $blackUserName = $request->getParam('blackUserName');
        $blackUserAccount = $request->getParam('blackUserAccount');
        $blackUserType = $request->getParam('blackUserType');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');

        $model = new BlackUserSettlement;

        $blackUserName && $model = $model->where('blackUserName',$blackUserName);
        $blackUserAccount && $model = $model->where('blackUserAccount',$blackUserAccount);
        $blackUserType && $model = $model->where('blackUserType',$blackUserType);

        $data = $model->orderBy('blackUserId', 'desc')->limit($limit)->offset($offset)->get()->toArray();
        $total = $model->count();
        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $data,
        ]);
    }

    public function createBlackUserSettlement(Request $request, Response $response, $args){

        $logger = $this->c->logger;

        $validator = $this->c->validator->validate($request, [
            'blackUserName' => Validator::oneOf(
                Validator::stringType()->length(2,16),
                Validator::nullType()
            ),
//            'blackUserAccount' => Validator::stringType()->length(5, 40)->noWhitespace()->notBlank(),
            'blackUserAccount' => Validator::oneOf(
                Validator::length(5,40),
                Validator::nullType()
            ),
            'blackUserType' => Validator::in(['EBANK','ALIPAY'])->noWhitespace()->notBlank(),
            'blackUserStatus' => Validator::in(['enable','disable'])->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('valid', $validator->getErrors());
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        $blackUserName = $request->getParam('blackUserName');
        $blackUserAccount = $request->getParam('blackUserAccount');
        $blackUserType = $request->getParam('blackUserType');
        $blackUserStatus = $request->getParam('blackUserStatus');
        if(!$blackUserName && !$blackUserAccount){
            return $response->withJson([
                'result' => '用户名或账号至少填写一项！',
                'success' => 0,
            ]);
        }
        $blackUserSettlement = new BlackUserSettlement();
//        $data = $blackUserSettlement->where('merchantNo', $merchantNo)->first();
        $isblackUserExists = $blackUserSettlement->where('blackUserType',$blackUserType)
            ->where(function($query) use ($blackUserName,$blackUserAccount){
                $query->where('blackUserName',$blackUserName)
                    ->orWhere('blackUserAccount',$blackUserAccount);
            })
            ->get()
            ->toArray();
        if ($isblackUserExists) {
            return $response->withJson([
                'result' => '数据已存在',
                'success' => 0,
            ]);
        }

        $blackUserSettlement->blackUserName = $blackUserName;
        $blackUserSettlement->blackUserAccount = $blackUserAccount;
        $blackUserSettlement->blackUserType = $blackUserType;
        $blackUserSettlement->blackUserStatus = $blackUserStatus;

        $res = $blackUserSettlement->save();

        if(!$res){
            return $response->withJson([
                'result' => '添加失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '添加成功',
            'success' => 1,
        ]);

    }

    public function updateBlackUserSettlement(Request $request, Response $response, $args){

        $logger = $this->c->logger;

        $validator = $this->c->validator->validate($request, [
            'blackUserName' => Validator::oneOf(
                Validator::stringType()->length(2,16),
                Validator::nullType()
            ),
//            'blackUserAccount' => Validator::stringType()->length(5, 40)->noWhitespace()->notBlank(),
            'blackUserAccount' => Validator::oneOf(
                Validator::length(5,40),
                Validator::nullType()
            ),
            'blackUserType' => Validator::in(['EBANK','ALIPAY'])->noWhitespace()->notBlank(),
            'blackUserStatus' => Validator::in(['enable','disable'])->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('valid', $validator->getErrors());
            return $response->withJson([
                'result' => '验证不通过: '.$validator->getErrors(),
                'success' => 0,
            ]);
        }
        $blackUserId = $request->getParam('blackUserId');
        $blackUserName = $request->getParam('blackUserName');
        $blackUserAccount = $request->getParam('blackUserAccount');
        $blackUserType = $request->getParam('blackUserType');
        $blackUserStatus = $request->getParam('blackUserStatus');

        if(!$blackUserName && !$blackUserAccount){
            return $response->withJson([
                'result' => '用户名或账号至少填写一项！',
                'success' => 0,
            ]);
        }

        $blackUserSettlement = new BlackUserSettlement();
        $model = $blackUserSettlement->where('blackUserId', $blackUserId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $isblackUserExists = $blackUserSettlement->where('blackUserId','<>',$blackUserId)
            ->where('blackUserType',$blackUserType)
            ->where(function($query) use ($blackUserName,$blackUserAccount){
                $query->where('blackUserName',$blackUserName)
                    ->orWhere('blackUserAccount',$blackUserAccount);
            })
            ->get()
            ->toArray();
        if ($isblackUserExists) {
            return $response->withJson([
                'result' => '数据已存在',
                'success' => 0,
            ]);
        }

        $model->blackUserName = $blackUserName;
        $model->blackUserAccount = $blackUserAccount;
        $model->blackUserType = $blackUserType;
        $model->blackUserStatus = $blackUserStatus;

        $res = $model->save();

        if($res === false){
            return $response->withJson([
                'result' => '修改失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);
    }

    public function deleteBlackUserSettlement(Request $request, Response $response, $args){

        $blackUserId = $request->getParam('blackUserId');
        $blackUserSettlement = new BlackUserSettlement();
        $model = $blackUserSettlement->where('blackUserId', $blackUserId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $model->blackUserId = $blackUserId;
        $res = $model->delete();
        if ($res) {
            return $response->withJson([
                'result' => '删除成功',
                'success' => 1,
            ]);
        }
    }

    public function noticesView(Request $request, Response $response, $args){

        return $this->c->view->render($response, 'gm/manager/notices.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);

    }

    //消息列表
    public function notices(Request $request, Response $response, $args){

        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');

        $model = new PlatformNotify();
        $model = $model->where('platform', 'gm');
        if(!empty($request->getParam('status'))){
            $model = $model->where('status', $request->getParam('status'));
        }
        if(!empty($request->getParam('title'))){
            $model = $model->where('title', 'like', "%{$request->getParam('title')}%");
        }
        $total = $model->count();
        $notices = $model->orderBy('id','desc')->limit($limit)->offset($offset)
            ->get(['id','title','content','status','created_at','type'])->toArray();

        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $notices,
        ]);
    }

    //消息详情
    public function notice(Request $request, Response $response, $args){
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });
        $logger->debug("消息详情变更", ['accountId'=>$_SESSION['accountId'], 'userName'=>$_SESSION['userName']]);

        $noticeId = $request->getParam('id');
        $model = new PlatformNotify;
        $notice = $model->where('id', $noticeId)->first();
        if($notice && $notice->status == 'UNREAD'){
            $model->where('id',$noticeId)->update(['status'=>'READED']);
        }

        return $response->withJson([
            'success' => 1,
            'result' => '已读',
        ]);
    }

    //管理员登录日志
    public function getAccountLoginLogView(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/log/accountLoginLog.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }
    public function getAccountLoginLog(Request $request, Response $response, $args){
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $model = SystemAccountLoginLog::from('system_account_login_log as log')
            ->leftJoin('system_account as a','log.accountId','=','a.id')
            ->select(['a.loginName','a.userName','log.*']);
        if(!empty($request->getParam('loginName'))){
            $model = $model->where('a.loginName', $request->getParam('loginName'));
        }
        if(!empty($request->getParam('ip'))){
            $model = $model->where('log.ip', $request->getParam('ip'));
        }
        $total = $model->count();
        $notices = $model->orderBy('log.created_at','desc')->limit($limit)->offset($offset)
            ->get()->toArray();
        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $notices,
        ]);
    }

    //管理员操作日志
    public function getAccountActionLogView(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/log/accountActionLog.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }
    public function getAccountActionLog(Request $request, Response $response, $args){

        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $model = SystemAccountLoginLog::from('system_account_action_log as log')
            ->leftJoin('system_account as a','log.accountId','=','a.id')
            ->select(['a.loginName','a.userName','log.*']);
        if(!empty($request->getParam('loginName'))){
            $model = $model->where('a.loginName', $request->getParam('loginName'));
        }
        if(!empty($request->getParam('ip'))){
            $model = $model->where('log.ip', $request->getParam('ip'));
        }
        $total = $model->count();
        $notices = $model->orderBy('log.created_at','desc')->limit($limit)->offset($offset)
            ->get()->toArray();
        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $notices,
        ]);
    }

}
