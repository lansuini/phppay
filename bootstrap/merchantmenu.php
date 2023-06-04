<?php
use App\Helpers\Tools;
return [
    1 => ['n' => '首页', 'u' => '/head'],
    2 => ['n' => '支付订单', 'u' => '/payorder'],
    3 => ['n' => '代付订单', 'u' => '/settlementorder'],
    7 => ['n' => '收支明细', 'u' => '/finance'],
    9 => ['n' => '每日报表', 'u' => '/report'],
    4 => ['n' => '代付', 'c' => [
        110 => ['n' => '创建银行卡代付订单', 'u' => '/settlementorder/create'],
//        112 => ['n' => '创建支付宝代付订单', 'u' => '/settlementorder/aliCreate'],
        113 => ['n' => '创建批量代付', 'u' => '/settlementbatchorder/create'],
        111 => ['n' => '商户银行卡列表', 'u' => '/settlementorder/cardlist'],
//        122 => ['n' => '支付宝账号', 'u' => '/settlementorder/account'],
//        113 => ['n' => '渠道列表', 'u' => '/settlementorder/settlementChannel'],
    ]],
    5 => ['n' => '管理功能', 'c' => [
//        100 => ['n' => '修改付款人信息', 'u' => '/changepayer'],
        101 => ['n' => '修改登录密码', 'u' => '/changeloginpwd'],
        102 => ['n' => '修改支付密码', 'u' => '/changesecurepwd'],
        109 => ['n' => '绑定Google验证码', 'u' => '/bindgoogleauth'],
    ]],
    6 => ['n' => '资源', 'c' => [
//        103 => ['n' => '接口文档', 'u' => (Tools::isHttps() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/resource/api_document/'.($_SERVER['HTTP_HOST'] == 'merchant.ddzf03.com' ? '商户支付宝代付接口文档':'商户支付接口文档').'.pdf'],
        103 => ['n' => '接口文档', 'u' => (Tools::isHttps() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/resource/api_document/讯宝代付商户支付接口文档.pdf'],
        // 104 => ['n' => 'JAVA DEMO', 'u' => '/merchant/user'],
        // 105 => ['n' => 'asp.net DEMO', 'u' => '/merchant/platform'],
    ]],
    8 => ['n' => '充值', 'c' => [
        120 => ['n' => '充值', 'u' => '/rechargeorder/create'],
        121 => ['n' => '充值订单', 'u' => '/rechargeorder'],
    ]],
    10 => ['n' => '消息公告', 'u'=>'/notice'],
    11 => ['n' => '充值', 'c' => [
        120 => ['n' => '充值', 'u' => '/rechargeorder/create'],
    ]],


];
