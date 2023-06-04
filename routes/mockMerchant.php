<?php
use App\Controllers\Mock\MerchantController;

$app->post('/merchant/success', MerchantController::class . ':success');
$app->post('/merchant/error', MerchantController::class . ':error');
