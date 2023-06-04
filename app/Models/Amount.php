<?php

namespace App\Models;

class Amount
{
    public function init($merchantId, $merchantNo)
    {

        $channels = (new MerchantChannel)->where('merchantId', $merchantId)->get();
        $channelSettlements = (new MerchantChannelSettlement)->where('merchantId', $merchantId)->get();

        foreach ($channels ?? [] as $channel) {
            $accountDate = date('Ymd');
            AmountPay::updateOrCreate([
                'merchantId' => $merchantId,
                'merchantNo' => $merchantNo,
                'channelMerchantId' => $channel->channelMerchantId,
                'channelMerchantNo' => $channel->channelMerchantNo,
                'accountDate' => $accountDate,
                'payType' => $channel->payType,
            ]);

            $accountDate = date('Ymd', time() + 86400);
            AmountPay::updateOrCreate([
                'merchantId' => $merchantId,
                'merchantNo' => $merchantNo,
                'channelMerchantId' => $channel->channelMerchantId,
                'channelMerchantNo' => $channel->channelMerchantNo,
                'accountDate' => $accountDate,
                'payType' => $channel->payType,
            ]);
        }

        foreach ($channelSettlements ?? [] as $channel) {
            $accountDate = date('Ymd');
            AmountSettlement::updateOrCreate([
                'merchantId' => $merchantId,
                'merchantNo' => $merchantNo,
                'accountDate' => $accountDate,
                'channelMerchantId' => $channel->channelMerchantId,
                'channelMerchantNo' => $channel->channelMerchantNo,
            ]);
            $accountDate = date('Ymd', time() + 86400);
            AmountSettlement::updateOrCreate([
                'merchantId' => $merchantId,
                'merchantNo' => $merchantNo,
                'accountDate' => $accountDate,
                'channelMerchantId' => $channel->channelMerchantId,
                'channelMerchantNo' => $channel->channelMerchantNo,
            ]);
        }

        MerchantAmount::updateOrCreate(['merchantId' => $merchantId,
            'merchantNo' => $merchantNo]);

    }

    public function batchUpdate($Merchants,$channelMerchantNo,$channelMerchantId){
        $accountDate = date('Ymd', time() + 86400);
        foreach ($Merchants ?? [] as $merchant) {
            AmountSettlement::updateOrCreate([
                'merchantId' => $merchant['merchantId'],
                'merchantNo' => $merchant['merchantNo'],
                'accountDate' => $accountDate,
                'channelMerchantId' => $channelMerchantId,
                'channelMerchantNo' => $channelMerchantNo,
            ]);
        }
    }

}
