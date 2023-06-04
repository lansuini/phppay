<?php
namespace App\Controllers\GM;

use App\Models\Banks;
use App\Helpers\Tools;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class BankController extends GMController{

    public function index(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/bank/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    // 代付银行列表
    public function search(Request $request, Response $response, $args){
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $bankName = $request->getParam('bankName');
        $status = $request->getParam('status');

        $model = Banks::select(['*']);

        $bankName && $model = $model->where('name', $bankName);
        $status && $model = $model->where('status', $status);

        $total = $model->count();

        $data = $model->orderBy('id', 'asc')->offset($offset)->limit($limit)->get();
        $rows = [];
        $code = ['enabled'=>'启用', 'disabled'=>'禁用'];
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                'code' => $v->code,
                'name' => $v->name,
                "status" => $v->status,
                "statusDesc" => $code[$v->status] ?? '',
                "start_time" => $v->start_time ? date('Y-m-d H:i:s', strtotime($v->start_time)) : '',
                "end_time" => $v->end_time ? date('Y-m-d H:i:s', strtotime($v->end_time)) : '',
                "created_at" => date('Y-m-d H:i:s', strtotime($v->created_at)),
                "updated_at" => date('Y-m-d H:i:s', strtotime($v->updated_at)),
//                "merchantId" => Tools::getHashId($v->merchantId),
            ];
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total
        ]);
    }

    // 银行编辑
    public function edit(Request $request, Response $response, $args){
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'banks';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $code = $request->getParam('code');
        $status = $request->getParam('status');
        $start_time = $request->getParam('startTime');
        $end_time = $request->getParam('endTime');
        $bank = new Banks();
        $bank_info = $bank->where('code', $code)->first();
        $logger->debug('代付银行调整 '. $_SESSION['loginName'].' BeforeUpdate', ['code'=>$bank_info->code, 'status'=>$bank_info->status, 'start_time'=>$bank_info->start_time, 'end_time'=>$bank_info->end_time]);
        if(empty($bank_info)){
            return $response->withJson([
                'result' => '银行信息不存在',
                'success' => 0,
            ]);
        }
        if($status == 'disabled'){
            if(empty($start_time) || empty($end_time)){
                return $response->withJson([
                    'result' => '请选择开始时间和结束时间',
                    'success' => 0,
                ]);
            }else if($start_time && $end_time && $start_time > $end_time){
                return $response->withJson([
                    'result' => '开始时间不能大于结束时间',
                    'success' => 0,
                ]);
            }
        }else{
            $status = 'enabled';
            $start_time = null;
            $end_time = null;
        }
        $bank_info->status = $status;
        $bank_info->start_time = $start_time;
        $bank_info->end_time = $end_time;
        $bank_info->save();
        $logger->debug('代付银行调整 '. $_SESSION['loginName'].' AfterUpdate', ['code'=>$bank_info->code, 'status'=>$bank_info->status, 'start_time'=>$bank_info->start_time, 'end_time'=>$bank_info->end_time]);
        return $response->withJson([
            'result' => '更新成功',
            'success' => 1,
        ]);
    }
}
