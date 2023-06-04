<?php

return [
    14 => ['n' => '首页', 'u' => '/head','pu' => [
        '/head',
    ]],
    1 => ['n' => '支付订单', 'u' => '/payorder', 'pu' => [
        '/payorder',
        '/api/payorder/search',
        '/payorder/detail',
        '/api/payorder/perfect',
        '/api/payorder/notify',
        '/payorder/makeupcheck',
        '/api/payorder/getMakeUp',
        '/api/payorder/domakeUpCheck',
        '/api/payorder/getMakeUpList',
        '/api/payorder/getsuborderno',
    ]],
    4 => ['n' => '账户列表', 'u' => '/account', 'pu' => [
        '/account',
        '/api/account/search',
    ]],
    7 => ['n' => '商户管理', 'c' => [
        103 => ['n' => '商户信息管理', 'u' => '/merchant', 'pu' => [
            '/merchant',
            '/api/merchant/search',
            '/api/merchant/getnextmerchantno',
            '/api/merchant/insert',
            '/api/merchant/update',
            '/api/merchant/resetset',
            '/api/merchant/detail',
        ]],
        104 => ['n' => '商户用户管理', 'u' => '/merchant/user', 'pu' => [
            '/merchant/user',
            '/api/merchant/user/search',
            '/api/merchant/user/update',
            '/api/merchant/user/resetloginpwd',
            '/api/merchant/user/resetsecurepwd',
            '/merchant/audit',
            '/api/merchant/audit/password',
            '/api/merchant/audit/resetpassword',
            '/api/merchant/audit/disagreepassword',
        ]],
        105 => ['n' => '电子商务平台管理', 'u' => '/merchant/platform', 'pu' => [
            '/merchant/platform',
            '/api/merchant/platform/search',
            '/api/merchant/platform/getnextmerchantno',
            '/api/merchant/platform/detail',
            '/api/merchant/platform/update',
            '/api/merchant/platform/getsignkey',
            '/api/merchant/platform/resetsignkey',
        ]],
        106 => ['n' => '商户费率配置', 'u' => '/merchant/rate', 'pu' => [
            '/merchant/rate',
            '/api/merchant/rate/search',
            '/api/merchant/rate/import',
            '/api/merchant/rate/export',
        ]],
        206 => ['n' => '商户转换费率配置', 'u' => '/transform/rate', 'pu' => [
            '/transform/rate',
            '/api/transform/rate/search',
            '/api/transform/rate/import',
            '/api/transform/rate/export',
        ]],
        107 => ['n' => '商户支付渠道配置', 'u' => '/merchant/paychannel', 'pu' => [
            '/merchant/paychannel',
            '/api/merchant/paychannel/search',
            '/api/merchant/paychannel/import',
            '/api/merchant/paychannel/export',
        ]],
        108 => ['n' => '商户代付渠道配置', 'u' => '/merchant/settlementchannel', 'pu' => [
            '/merchant/settlementchannel',
            '/api/merchant/settlementchannel/search',
            '/api/merchant/settlementchannel/import',
            '/api/merchant/settlementchannel/export',
        ]],
        118 => ['n' => '商户充值渠道配置', 'u' => '/merchant/rechargechannel', 'pu' => [
            '/merchant/rechargechannel',
            '/api/merchant/rechargechannel/search',
            '/api/merchant/rechargechannel/import',
            '/api/merchant/rechargechannel/export',
        ]],
        125 => ['n' => '消息公告', 'u' => '/merchant/notice', 'pu' => [
            '/merchant/notice',
            '/api/merchant/notice',
            '/api/merchant/notice/create',
            '/api/merchant/notice/publish',
            '/api/merchant/notice/delete',
            '/api/merchant/notice/edit',
        ]],


    ]],
    5 => ['n' => '财务明细', 'u' => '/finance', 'pu' => [
        '/finance',
        '/api/finance/search',
    ]],
    6 => ['n' => '金额统计', 'c' => [
        114 => ['n' => '营业报表', 'u' => '/chart/businessamount', 'pu' => [
            '/chart/businessamount',
            '/api/business//amount',
            '',
        ]],
        101 => ['n' => '支付订单金额统计', 'u' => '/chart/payorderamount', 'pu' => [
            '/chart/payorderamount',
            '/api/payorder//amount',
        ]],
        102 => ['n' => '代付明细表', 'u' => '/chart/settlementorderamount', 'pu' => [
            '/chart/settlementorderamount',
        ]],
        115 => ['n' => '代付汇总表', 'u' => '/chart/revenueChart', 'pu' => [
            '/chart/revenueChart',
        ]],
        // 115 => ['n' => '上游渠道统计', 'u' => '/chart/channelamount','pu'=>['/chart/channelamount','/api/channel//amount']],
        122 => ['n' => '渠道明细表', 'u' => '/chart/channeldaily', 'pu' => [
            '/chart/channeldaily',
            '/api/chart/getchanneldaily',
        ]],
    ]],
    3 => ['n' => '商户余额调整', 'u' => '/balanceadjustment', 'pu' => [
        '/balanceadjustment',
        '/api/balanceadjustment/search',
        '/api/balanceadjustment/insert',
    ]],
    2 => ['n' => '代付订单', 'u' => '/settlementorder', 'pu' => [
        '/settlementorder',
        '/settlementorder/detail',
        '/api/settlementorder/detail',
        '/api/settlementorder/notify',
        '/api/settlementorder/perfect',
        '/api/settlementorder/search',
        '/api/settlementorder/getsuborderno',
        '/settlementorder/makeupcheck',
        '/api/settlementorder/getMakeUp',
        '/api/settlementorder/domakeUpCheck',
        '/api/settlementorder/getMakeUpList',
        '/api/settlementorder/timeInterval',
    ]],
    11 => ['n' => '充值订单', 'u' => '/rechargeorder', 'pu' => [
        '/rechargeorder',
        '/api/rechargeorder/search',
    ]],

    9 => ['n' => '管理功能', 'c' => [
        111 => ['n' => '修改登陆账号', 'u' => '/manager/changeloginname', 'pu' => [
            '/manager/changeloginname',
            '/api/manager/changeloginname',
        ]],
        112 => ['n' => '修改登陆密码', 'u' => '/manager/changeloginpwd', 'pu' => [
            '/manager/changeloginpwd',
            '/api/manager/changeloginpwd',
        ]],
        113 => ['n' => '绑定Google验证码', 'u' => '/manager/bindgoogleauth', 'pu' => [
            '/api/manager/googleauth',
            '/manager/bindgoogleauth',
            '/api/manager/bindgoogleauth',
            '/googleauth',
        ]],
        126 => ['n' => '管理员列表', 'u' => '/manager/adminList', 'pu' => [
            '/manager/adminList',
            '/api/manager//getmanagerlist',
            '/api/manager/update',
            '/api/manager/delaccount',
        ]],
        123 =>['n' => '代付黑名单', 'u' => '/manager/blackUserSettlement', 'pu' => [
            '/manager/blackUserSettlement',
            '/api/manager/blackUserSettlement',
            '/api/manager/blackUserSettlement/create',
            '/api/manager/blackUserSettlement/update',
            '/api/manager/blackUserSettlement/delete',
        ]],
        124 =>['n' => '站内消息', 'u' => '/manager/notices', 'pu' => [
                '/manager/notices',
                '/api/manager/notices',
                '/api/manager/notice',
            ]
        ],
        125 =>['n' => '代付银行管理', 'u' => '/manager/bank', 'pu' => [
                '/manager/bank',
                '/api/manager/bank/search',
                '/api/manager/bank/edit',
            ]
        ],
        136 => ['n' => '留言管理', 'u' => '/manager/messageInfoPage', 'pu' => [
            '/manager/messageInfoPage',
            '/api/manager//editEmail',
            '/api/manager/messageInfo',
//            '/api/manager/message'
        ]],
    ]],
    10 => ['n' => '审核列表', 'c' => [
        118 => ['n' => '审核列表', 'u' => '/check', 'pu' => [
            '/check',
            '/api/check/search',
            '/check/makeup',
            '/api/check/getMakeUp',
        ]],
        135 => ['n' => '文档解密', 'u' => '/decrypt', 'pu' => [
            '/decrypt',
            '/api/decrypt/file',
        ]],
        /* 119 => ['n' => '补单历史列表' ,'u'=>'/check/makeup'], */
    ]],
    8 => ['n' => '上游渠道管理', 'c' => [
        109 => ['n' => '渠道商户信息管理', 'u' => '/channel/merchant', 'pu' => [
            '/channel/merchant',
            '/api/channel/merchant/search',
            '/api/channel/merchant/detail',
            '/api/channel/merchant/resetset',
            '/api/channel/merchant/insert',
            '/api/channel/merchant/update',
        ]],
        110 => ['n' => '渠道费率管理', 'u' => '/channel/rate', 'pu' => [
            '/channel/rate',
            '/api/channel/rate/search',
            '/api/channel/rate/import',
            '/api/channel/rate/export',
        ]],
        116 => ['n' => '支付渠道配置', 'u' => '/channel/paychannel', 'pu' => [
            '/channel/paychannel',
            '/api/channel/paychannel/search',
            '/api/channel/paychannel/import',
            '/api/channel/paychannel/export',
        ]],
        117 => ['n' => '代付渠道配置', 'u' => '/channel/settlementchannel', 'pu' => [
            '/channel/settlementchannel',
            '/api/channel/settlementchannel/search',
            '/api/channel/settlementchannel/import',
            '/api/channel/settlementchannel/export',
        ]],
        120 => ['n' => '代付余额下发', 'u' => '/channel/settlementbalance', 'pu' => [
            '/channel/settlementbalance',
            '/api/channel/settlementbalance/search',
        ]],
        121 => ['n' => '代付余额取款记录', 'u' => '/channel/issuerecord', 'pu' => [
            '/channel/issuerecord',
            '/api/channel/issuerecord/search',
        ]]
    ]],
    12 => ['n' => '代理体系', 'c'=>[
        130 => ['n' => '代理账户列表', 'u' => '/agent', 'pu' => [
            '/agent',
            '/api/agent/search',
        ]],
        131 => ['n' => '代理费率', 'u' => '/agent/rate', 'pu' => [
            '/agent/rate',
            '/api/agent/rate/search',
            '/api/agent/rate/import',
        ]],
        132 => ['n' => '代理数据报表', 'u' => '/agent/dataReport', 'pu' => [
            '/agent/dataReport',
            '/api/agent/dataReport/search',
        ]],
        133 => ['n' => '代理资金记录', 'u' => '/agent/agentFinance', 'pu' => [
            '/agent/agentFinance',
            '/api/agent/agentFinance/search',
        ]],
        134 => ['n' => '代理提款申请订单', 'u' => '/agent/withdrawOrder', 'pu' => [
            '/agent/withdrawOrder',
            '/api/agent/withdrawOrder/search',
        ]]
    ]],
    13 => ['n' => '管理员日志', 'c' => [
        140=>['n'=>'管理员登录日志','u'=>'/manager/getAccountLoginLogView','pu'=>[
            '/manager/getAccountLoginLogView',
            '/api/manager/getAccountLoginLog'
        ]],
        141=>['n'=>'管理员操作日志','u'=>'/manager/getAccountActionLogView','pu'=>[
            '/manager/getAccountActionLogView',
            '/api/manager/getAccountActionLog'
        ]],
    ]],

];
