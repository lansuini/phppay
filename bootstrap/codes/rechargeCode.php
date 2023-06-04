<?php
return [
    'orderStatus'=>[
        // 'WaitTransfer' => '等待划款',
        'Transfered' => '充值中',
        'Success' => '充值成功',
        'Fail' => '充值失败',
        'Exception' => '订单异常',
    ],
    'orderType'=>[
        'insideRecharge'=>'快捷充值',
        'outsideRecharge'=>'网银充值',
    ],
    'payType'=>[
        'EnterpriseEBank' => '企业网银',
        'PersonalEBank' => '个人网银',
        'PersonalEBankDNA' => '个人网银DNA',
        'AlipayEBank' => '支付宝网银',
    ]
];
