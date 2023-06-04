<?php

namespace App\Controllers;

use App\Helpers\Tools;
use App\Models\Agent;
use App\Models\Merchant;
use Psr\Container\ContainerInterface;

abstract class AgentController
{
    protected $c;
    protected $logger;
    protected $session;
    protected $code;
    protected $menus;
    public function __construct(ContainerInterface $container)
    {
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT') . '?auth=' . getenv('REDIS_PASSWORD') . '&database=12');
        ini_set('session.gc_maxlifetime', '1800');
        session_start();
        $this->c = $container;
        $this->logger = $container->logger;
        $this->code = $container->code;
        $request = $container['request'];
        $path = $request->getUri()->getPath();
        $publicPath = [
            '/',
            '/api/manager/login',
            '/logout',
        ];

        if ($path == '/') {
            session_regenerate_id(true);
        }

        $loginName = $_SESSION['loginName'] ?? null;
        $agentId = $_SESSION['userId'] ?? null;
        $redis = $this->c->redis;
        $login_status = -1;
        if (!empty($_SESSION['userId'])) {
            $login_status = $redis->get('dadong_agent_login_userid_'.$_SESSION['userId']);
        }

        if ($login_status == 0) {
            print_r($login_status);exit;
            $redis = $this->c->redis;
            $redis->del('dadong_agent_login_userid_'.$_SESSION['userId']);
            Tools::getJsRedirect('/');
        }
        $agent = new Agent();
        $res = $agent->getCacheByAgentId($agentId);

        if(!$res && !in_array($path,$publicPath)){
            Tools::getJsRedirect('/');
        }
        $ipWhite = [];
        if (!empty(trim($res['loginIpWhite']))) {
            $ipWhite = explode(",", trim($res['loginIpWhite']));
        }
        if (!empty($loginName) && !empty($ipWhite) && !in_array(Tools::getIp(), $ipWhite) && $path != '/') {
            session_destroy();
            Tools::getJsRedirect('/');
        }

        $this->menus = require __DIR__ . '/../../bootstrap/agentmenu.php';
    }
}
