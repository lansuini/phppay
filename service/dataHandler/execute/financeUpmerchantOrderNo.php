<?php

/*支付订单流水*/
UPDATE finance f, platform_pay_order p SET f.operateSource = 'ports', f.merchantOrderNo = p.merchantOrderNo, f.summary = p.tradeSummary WHERE f.platformOrderNo = p.platformOrderNo AND f.platformOrderNo LIKE 'P%';

/*接口发起代付订单流水*/
UPDATE finance f, platform_settlement_order p SET f.operateSource = 'ports', f.merchantOrderNo = p.merchantOrderNo WHERE f.platformOrderNo = p.platformOrderNo AND f.platformOrderNo LIKE 'S%' AND p.merchantOrderNo != '';

/*商户后台发起代付订单流水*/
UPDATE finance f, platform_settlement_order p SET f.operateSource = 'merchant', f.merchantOrderNo = p.merchantOrderNo WHERE f.platformOrderNo = p.platformOrderNo AND f.platformOrderNo LIKE 'S%' AND p.merchantOrderNo = '';

/*代付订单扣款时摘要，代付失败回滚的流水摘要不更新*/
UPDATE finance f, platform_settlement_order p SET f.summary = p.tradeSummary WHERE f.platformOrderNo = p.platformOrderNo AND f.platformOrderNo LIKE 'S%' AND f.financeType = 'PayOut';


/*管理后台余额调整的流水*/
UPDATE finance f, balance_adjustment b SET f.summary = b.summary, f.operateSource = 'admin' WHERE f.platformOrderNo = b.platformOrderNo AND f.platformOrderNo LIKE 'B%';
