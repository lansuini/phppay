<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DecryptController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/decrypt/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function file(Request $request, Response $response, $args)
    {
        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if($ext != 'csv') {
            return $response->withJson([
                'result' => '请上传csv格式的文档',
                'success' => 0,
            ]);
        }
        $cvs_file = fopen($file['tmp_name'],'r'); //开始读取csv文件数据
        $num = $i = 0;//记录cvs的行
        $decrypt = [];//记录需要解密的下标
        //边解密边边下载输出
        header("Content-Type: application/vnd.ms-excel; charset=GB2312");
        header('Content-Disposition: attachment;filename=decrypt.csv');
        header('Cache-Control: max-age=0');

        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');// 打开文件资源，不存在则创建
        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 20000;

        while ($file_data = fgetcsv($cvs_file))
        {
            $i++;
            $num++;
            if($i==1)
            {
                //表头 -- 记录需要解密的下标
                foreach ($file_data as $k=>$v){
                    $v = $this->tinyCode($v);
                    if(strpos($v,'decrypt') !== false){
                        $decrypt[] = $k;
                    }
                }
            }else {
                foreach ($file_data as $k=>&$v){
                    $v = $this->tinyCode($v);
                    if(in_array($k,$decrypt)){
                        $v = Tools::decrypt($v);
                    }
                    $v =  "\t".trim($v);
                }
            }
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            fputcsv($fp, $file_data);
        }
        fclose($cvs_file);
        fclose($fp);
        die();
    }

    public function tinyCode($_line) {
        $d = ',';
        $e = '"';
        $_csv_line = preg_replace('/(?: |[ ])?$/', $d, trim($_line));
        $_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
        preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
        $_csv_data = $_csv_matches[1];
        for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i++) {
            $_csv_data[$_csv_i] = preg_replace('/^' . $e . '(.*)' . $e . '$/s', '$1', $_csv_data[$_csv_i]);
            $_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
        }
        return is_array($_csv_data) ? current($_csv_data) : $_csv_data;
    }
}
