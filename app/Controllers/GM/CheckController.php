<?php

namespace App\Controllers\GM;

// use App\Controllers\Controller;
use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use App\Models\SystemCheckLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CheckController extends GMController {
	public function index(Request $request, Response $response, $args) {
        $model = new SystemCheckLog();
        $data = $model->selectRaw("DISTINCT type")->orderBy('id', 'desc')->get();
        /* dump($data);exit; */
		return $this->c->view->render($response, 'gm/check/index.twig', [
			'appName' => $this->c->settings['app']['name'],
			'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
            'data' => $data,
		]);
	}

	public function search(Request $request, Response $response, $args) {
		$array = array(
            '支付补单' => '/payorder/makeupcheck',
            '代付补单' => '/settlementorder/makeupcheck',
        );

		$model = new SystemCheckLog();
		$account = new SystemAccount();
		$admin = $account::all();
		$list = [];
		foreach ($admin as $k => $v) {
			$list[$v->id] = $v->userName;
		}

        $code = $this->c->code;
        $relevance = $request->getParam('relevance');
        /* $status = $request->getParam('checkStatus'); */
        $type = $request->getParam('checkType');

		$limit = $request->getParam('limit', 20);
		$offset = $request->getParam('offset', 0);
        $status = $request->getParam('checkStatus', "0");
        if($status != '-1'){
            isset($status) && $model = $model->where('status', $status);
        }
        $relevance && $model = $model->where('relevance',"like", "%".$relevance."%");
        $type && $model = $model->where('type', $type);
		$total = $model->count() != 0 ? $model->count() : 1;
		$data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        $merchant = new Merchant();
		foreach ($data ?? [] as $k => $v) {
            $url = isset($array[$v->type]) ? $array[$v->type] : '';
            $content = json_decode($v->content, true);
            $content['orderStatus'] = !empty($content['orderStatus']) ? $content['orderStatus'] : '';
            if($v['type'] == '支付补单'){
                $content['orderStatus'] = !empty($content['orderStatus']) ? $code['payOrderStatus'][$content['orderStatus']] : '';
            }
            $content['pic_url'] = '/api/check/getPic?id='.$v['id'];
			$nv = [
				'admin_id' => isset($list[$v['admin_id']]) ? $list[$v['admin_id']] : '',
				'commiter_id' => isset($list[$v['commiter_id']]) ? $list[$v['commiter_id']] : '',
				'url' => $url,
                'created_at' => Tools::getJSDatetime($v['created_at']),
				'ip' => $v['ip'],
				'ipDesc' => $v['ipDesc'],
				'check_time' => $v['check_time'],
				'check_ip' => $v['check_ip'],
				'status' => $code['checkStatusCode'][$v->status],
				'type' => $v['type'],
				'id' => $v['id'],
                'content' => $content,
                'desc' => $v['desc'],
                'relevance' => $v['relevance'],
                'merchantShortName' => $merchant->getCacheByMerchantNo($v['relevance'])['shortName'] ?? '',
			];

			$rows[] = $nv;
        }
        /* print_t($rows);exit; */

		return $response->withJson([
			'result' => [],
			'rows' => $rows,
			'success' => 1,
			'total' => $total,
		]);
	}

	public function makeup(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/check/makeup.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }


    public function getmakeUp(Request $request, Response $response)
    {
        $model = new SystemCheckLog();
        $account = new SystemAccount();
        $admin = $account::all();
        $list = [];
        foreach($admin as $k => $v){
            $list[$v->id] = $v->userName;
        }

        $code = $this->c->code;

        $platformOrderNo = $request->getParam('platformOrderNo');
        $status = $request->getParam('checkStatus');

        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);     
        /* $status = $request->getParam('status', "0"); */
        isset($status) && $model = $model->where('status', $status);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $total = $model->count();
        $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        $status = ['0' => '未审核', '1' => '审核通过', 2 => '审核不通过'];
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            /* $url = isset($array[$v->type]) ? $array[$v->type] : ''; */
            $content = json_decode($v->content,true);
            $content['channel'] = $code['channel'][$content['channel']]['name'];
			$content['orderStatus'] = $code['payOrderStatus'][$content['orderStatus']] ?? null;
			$admin_name = '';
			if($v['admin_id'] != 0){
				$admin_name = isset($adminList[$v['admin_id']]) ? $adminList[$v['admin_id']] : '';
			}
            $nv  = [
                'admin_id' => $admin_name,
                'commiter_id' => isset($list[$v['commiter_id']]) ? $list[$v['commiter_id']] : '',
                'created_at' => Tools::getJSDatetime($v['created_at']),
                'ip' => $v['ip'],
                'ipDesc' => $v['ipDesc'],
                'status' => $status[$v['status']],
                'type' => $v['type'],
                'id' => $v['id'],
                'desc' => $v['desc'],
                'content' => $content,
            ];

            $rows[] = $nv;
        }
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    public function modifyCheckPwd(Request $request, Response $response, $args){
        $oldPwd = Tools::getHashPassword($request->getParam('oldPwd'));
        $newPwd = Tools::getHashPassword($request->getParam('newPwd'));
        $model = new SystemAccount();
        $checkPwd = $model->where('id',$_SESSION['accountId'])->value('check_pwd');
        if(!$oldPwd || !$newPwd) {
            return $response->withJson([
                'result' => '新旧密码不能为空',
                'success' => 0,
            ]);
        }
        if($newPwd == $oldPwd) {
            return $response->withJson([
                'result' => '新旧密码一致',
                'success' => 0,
            ]);
        }
        if($checkPwd != $oldPwd) {
            return $response->withJson([
                'result' => '旧密码不正确',
                'success' => 0,
            ]);
        }
        SystemAccountActionLog::insert([
            'action' => 'UPDATE_PASSWORD',
            'actionBeforeData' => json_encode(['action'=>'modifyCheckPwd','pwd' => $oldPwd]),
            'actionAfterData' => json_encode(['action'=>'modifyCheckPwd','pwd' => $newPwd]),
            'status' => 'Success',
            'accountId' => $_SESSION['accountId'],
            'ip' => Tools::getIp(),
            'ipDesc' => Tools::getIpDesc(),
        ]);
        SystemAccount::where('id',$_SESSION['accountId'])->update(['check_pwd'=>$newPwd]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);
    }

}
