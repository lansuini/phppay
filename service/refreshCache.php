<?php
use App\Models\Amount;
use App\Models\ChannelMerchant;
use App\Models\ChannelMerchantRate;
use App\Models\ChannelPayConfig;
use App\Models\ChannelSettlementConfig;
use App\Models\Merchant;
use App\Models\MerchantAccount;
use App\Models\MerchantAmount;
use App\Models\MerchantChannel;
use App\Models\MerchantChannelSettlement;
use App\Models\MerchantChannelRecharge;
use App\Models\MerchantRate;
require_once __DIR__ . '/../bootstrap/app.php';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'ManualRefreshCache.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};
// $app->run();

(new Merchant)->refreshCache();
(new MerchantRate)->refreshCache();
(new MerchantChannel)->refreshCache();
(new MerchantChannelSettlement)->refreshCache();
(new MerchantChannelRecharge)->refreshCache();
(new MerchantAccount)->refreshCache();
(new ChannelMerchant)->refreshCache();
(new ChannelMerchantRate)->refreshCache();
(new MerchantAmount)->refreshCache();
(new ChannelPayConfig)->refreshCache();
(new ChannelSettlementConfig)->refreshCache();
// (new MerchantAmount)->moveTodayToYesterday();
$data = Merchant::get();
$amount = new Amount;
foreach ($data as $v) {
    $amount->init($v->merchantId, $v->merchantNo);
}
echo 'finish', PHP_EOL;
