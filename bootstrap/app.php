<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (getenv('APP_DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
}

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

$app = new Slim\App([
    'settings' => [
        'displayErrorDetails' => getenv('APP_DEBUG') === 'true',
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        // 'determineRouteBeforeAppMiddleware' => true,
        'app' => [
            'name' => getenv('APP_NAME'),
        ],

        'views' => [
            'cache' => getenv('VIEW_CACHE_DISABLED') === 'true' ? false : __DIR__ . '/../storage/views',
        ],

        'database' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT') ? getenv('DB_PORT') : 3306,
            'database' => getenv('DB_NAME'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => '',
        ],

        'redis' => [
            'schema' => 'tcp',
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'database' => 1,
            'password' => getenv('REDIS_PASSWORD'),
        ],

        'logger' => [
            'name' => 'app',
            'path' => is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') : __DIR__ . '/' . getenv('LOGGER_PATH'),
//            'level' => getenv('APP_DEBUG') !== 'true' ? \Monolog\Logger::ERROR : \Monolog\Logger::DEBUG,
             'level' => \Monolog\Logger::DEBUG,
        ],
    ],
]);

$container = $app->getContainer();

$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
        'cache' => $container->settings['views']['cache'],
    ]);

    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    $globalJsVer = '202211081708';
    $view->getEnvironment()->addGlobal('globalJsVer', $globalJsVer);

    return $view;
};

$container['validator'] = function ($container) {return new Awurth\SlimValidation\Validator;};

require_once __DIR__ . '/database.php';

$container['code'] = function ($container) {
    return [
        'channel' => require dirname(__FILE__) . '/codes/channelCode.php',
        'bankCode' => require dirname(__FILE__) . '/codes/bankCode.php',
        'bankType' => require dirname(__FILE__) . '/codes/bankTypeCode.php',
        'payType' => require dirname(__FILE__) . '/codes/payTypeCode.php',
        'rechargeType' => require dirname(__FILE__) . '/codes/rechargeTypeCode.php',
        'userTerminal' => require dirname(__FILE__) . '/codes/userTerminalCode.php',
        'status' => require dirname(__FILE__) . '/codes/statusCode.php',
        'payModel' => require dirname(__FILE__) . '/codes/payModelCode.php',
        'cardType' => require dirname(__FILE__) . '/codes/cardTypeCode.php',
        'payOrderStatus' => require dirname(__FILE__) . '/codes/payOrderStatusCode.php',
        'settlementOrderStatus' => require dirname(__FILE__) . '/codes/settlementOrderStatusCode.php',
        'recharge' => require dirname(__FILE__) . '/codes/rechargeCode.php',
        'settlementType' => require dirname(__FILE__) . '/codes/settlementTypeCode.php',
        'agentType' => require dirname(__FILE__) . '/codes/agentTypeCode.php',
        'switchType' => require dirname(__FILE__) . '/codes/switchTypeCode.php',
        'commonStatus' => require dirname(__FILE__) . '/codes/commonStatusCode.php',
        'commonStatus2' => require dirname(__FILE__) . '/codes/commonStatus2Code.php',
        'bankrollDirection' => require dirname(__FILE__) . '/codes/bankrollDirectionCode.php',
        'bankrollType' => require dirname(__FILE__) . '/codes/bankrollTypeCode.php',
        'accountType' => require dirname(__FILE__) . '/codes/accountTypeCode.php',
        'productType' => require dirname(__FILE__) . '/codes/productTypeCode.php',
        'rateType' => require dirname(__FILE__) . '/codes/rateTypeCode.php',
        'financeType' => require dirname(__FILE__) . '/codes/financeTypeCode.php',
        'dealType' => require dirname(__FILE__) . '/codes/agentFinanceTypeCode.php',
        'platformType' => require dirname(__FILE__) . '/codes/platformTypeCode.php',
        'openType' => require dirname(__FILE__) . '/codes/openTypeCode.php',
        'processType' => require dirname(__FILE__) . '/codes/processTypeCode.php',
        'merchantUserLevel' => require dirname(__FILE__) . '/codes/merchantUserLevelCode.php',
        'merchantUserStatus' => require dirname(__FILE__) . '/codes/merchantUserStatusCode.php',
        'settlementAccountType' => require dirname(__FILE__) . '/codes/settlementAccountTypeCode.php',
        'resetPwdCode' => require dirname(__FILE__) . '/codes/resetPwdCode.php',
        'systemAccountRoleCode' => require dirname(__FILE__) . '/codes/systemAccountRoleCode.php',
        'systemAccountstatusCode' => require dirname(__FILE__) . '/codes/systemAccountstatusCode.php',
        'accountPowerCode' => require dirname(__FILE__) . '/codes/accountPowerCode.php',
        'interceptUrl' => require dirname(__FILE__) . '/codes/interceptUrlCode.php',
        'checkStatusCode' => require dirname(__FILE__) . '/codes/checkStatusCode.php',
        'operateSource' => require dirname(__FILE__) . '/codes/financeOperateSourceCode.php',
        'withdrawOrderType' => require dirname(__FILE__) . '/codes/withdrawOrderTypeCode.php',
        'psqlBankCode' => require dirname(__FILE__) . '/codes/psqlBankCode.php',
    ];
};

$container['redis'] = function ($c) {
    $settings = $c->get('settings')['redis'];
    $config = [
        'schema' => $settings['schema'],
        'host' => $settings['host'],
        'port' => $settings['port'],
        'database' => $settings['database'],
        // 'password' => $settings['password']
    ];

    if (!empty($settings['password'])) {
        $config['password'] = $settings['password'];
    }
    return new Predis\Client($config,array(
        'profile' => function ($options) {
            $profile = $options->getDefault('profile');
            $profile->defineCommand('redis_keys', 'App\Helpers\KeyKeys');

            return $profile;
        },
    ));
};

$container['cache'] = function ($c) {
    $settings = $c->get('settings')['redis'];
    $config = [
        'schema' => $settings['schema'],
        'host' => $settings['host'],
        'port' => $settings['port'],
        'database' => $settings['database'],
    ];

    if (!empty($settings['password'])) {
        $config['password'] = $settings['password'];
    }

    $connection = new Predis\Client($config);
    return new Symfony\Component\Cache\Adapter\RedisAdapter($connection);
};

if (isset($_SERVER['HTTP_HOST'])) {
    if (getenv('GM_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('GM_DOMAIN')) == $_SERVER['HTTP_HOST']
        || getenv('GM_ENABLE_BAK') === 'true' && str_replace(['http://', 'https://'], '', getenv('GM_DOMAIN_BAK')) == $_SERVER['HTTP_HOST']
    ) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'gm.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'gm.log', 0, $settings['level']));//按天生成文件
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/gm.php';
    }

    if (getenv('MERCHANT_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('MERCHANT_DOMAIN')) == $_SERVER['HTTP_HOST']
        || getenv('MERCHANT_ENABLE_BAK') === 'true' && str_replace(['http://', 'https://'], '', getenv('MERCHANT_DOMAIN_BAK')) == $_SERVER['HTTP_HOST']
    ) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'merchant.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'merchant.log', 0, $settings['level']));//按天生成文件
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };

        require_once __DIR__ . '/../routes/merchant.php';
    }

    if (getenv('GATE_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('GATE_DOMAIN')) == $_SERVER['HTTP_HOST']) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'gate.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'gate.log', 0, $settings['level']));//按天生成文件
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/gate.php';
    }

    if (getenv('CB_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('CB_DOMAIN')) == $_SERVER['HTTP_HOST']) {
        header('Access-Control-Allow-Origin: *');
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'cb.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'cb.log', 0, $settings['level']));//按天生成文件
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/cb.php';
    }

    if (getenv('APP_DEBUG') === 'true' && getenv('MOCK_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('MOCK_DOMAIN')) == $_SERVER['HTTP_HOST']) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'mock.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/mock.php';
    }

    if (getenv('APP_DEBUG') === 'true' && getenv('MOCK_MERCHANT_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('MOCK_MERCHANT_DOMAIN')) == $_SERVER['HTTP_HOST']) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'mockMerchant.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/mockMerchant.php';
    }
    if (getenv('AGENT_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('AGENT_DOMAIN')) == $_SERVER['HTTP_HOST']) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'agent.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/agent.php';
    }
    if (getenv('AGENT_ENABLE') === 'true' && str_replace(['http://', 'https://'], '', getenv('AGENT_DOMAIN')) == $_SERVER['HTTP_HOST']) {
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'agent.log', $settings['level']));
            $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
            return $logger;
        };
        require_once __DIR__ . '/../routes/agent.php';
    }
}
