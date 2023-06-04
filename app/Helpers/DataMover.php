<?php

namespace App\Helpers;

use App\Models\HistoryBackups;

class DataMover
{
    protected $config;

    protected $logger;

    protected $db;

    public function __construct($config)
    {
        global $app;
        $this->db = $app->getContainer()->database;
        $this->logger = $app->getContainer()->logger;
        $this->config = $config;
        return $this;
    }

    public function run()
    {
        foreach ($this->config as $conf) {
            $insertData = [];
            $model = new $conf['m'];
            $type = $conf['t'];
            $params = $conf['p'] ?? null;
            $count = $conf['c'] ?? 300;
            $timeLimit = $conf['l'];
            $primaryKey = $model->getKeyName();
            $model = $model->where('created_at', '<=', date('YmdHis', time() - $timeLimit));
            $params && $model = $model->where($params);
            $db = $this->db;
            $logger = $this->logger;
            $m = new $conf['m'];
            $model->chunk($count, function ($result) use ($db, $type, $primaryKey, $logger, $m) {
                $ids = [];
                $insertData = [];
                foreach ($result ?? [] as $v) {
                    $ids[] = $v->$primaryKey;
                    $insertData[] = [
                        'type' => $type,
                        'text' => $v->toJson(),
                    ];
                }

                if (empty($ids)) {
                    return false;
                }

                try {
                    $db->getConnection()->beginTransaction();
                    HistoryBackups::insert($insertData);
                    $m->whereIn($primaryKey, $ids)->delete();
                    $db->getConnection()->commit();
                } catch (\Exception $e) {
                    $logger->debug($e->getMessage());
                    $db->getConnection()->rollback();
                    return false;
                }
            });
        }
    }
}
