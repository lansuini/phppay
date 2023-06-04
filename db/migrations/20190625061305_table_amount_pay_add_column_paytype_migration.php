<?php

use Phinx\Migration\AbstractMigration;

class TableAmountPayAddColumnPaytypeMigration extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $amountPay = $this->table('amount_pay');

        $amountPay->removeIndexByName('idx_merchantId_channelMerchantId_accountDate');
        $amountPay->addColumn('payType', 'enum', ['values' => ['EBank', 'Quick', 'OfflineWechatQR',
            'OfflineAlipayQR', 'OnlineWechatQR', 'OnlineAlipayQR', 'OnlineWechatH5', 'OnlineAlipayH5',
            'UnionPayQR', 'EntrustSettlement', 'AdvanceSettlement', 'HolidaySettlement'],
            'comment' => '支付方式(EBank=网银,Quick=快捷,OfflineWechatQR=线下微信扫码,OfflineAlipayQR=线下支付宝扫码,
    OnlineWechatQR=线上微信扫码,OnlineAlipayQR=线上支付宝扫码,OnlineWechatH5=线上微信H5,OnlineAlipayH5=线上支付宝H5,
    UnionPayQR=银联扫码,EntrustSettlement=委托代付,AdvanceSettlement=垫资结算,HolidaySettlement=节假日结算)',
            'after' => 'channelMerchantNo'])
            ->addIndex(['merchantId', 'channelMerchantId', 'payType', 'accountDate'], ['unique' => true, 'name' => 'idx_merchantId_channelMerchantId_payType_accountDate'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $amountPay = $this->table('amount_pay');
        $amountPay->removeIndexByName('idx_merchantId_channelMerchantId_payType_accountDate');
        $amountPay->removeColumn('payType')
            ->addIndex(['merchantId', 'channelMerchantId', 'accountDate'], ['unique' => true, 'name' => 'idx_merchantId_channelMerchantId_accountDate'])
            ->save();
    }
}
