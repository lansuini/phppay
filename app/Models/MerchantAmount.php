<?php

namespace App\Models;

use App\Helpers\Tools;
use App\Models\NationalHoliday;
use Illuminate\Database\Eloquent\Model;

class MerchantAmount extends Model
{
    protected $table = 'merchant_amount';

    protected $primaryKey = 'id';

    protected $fillable = [
        'merchantId',
        'merchantNo',
        'settlementAmount',
    ];

    public function getCacheByMerchantNo($merchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantAmount:n:" . $merchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantId($merchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantAmount:i:" . $merchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (!empty($param)) {
            $merchantAmount = self::where($param)->get();
        } else {
            $merchantAmount = self::get();
        }

        foreach ($merchantAmount as $v) {
            $data = $v->toArray();
            $redis->setex("merchantAmount:n:" . $v->merchantNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            $redis->setex("merchantAmount:i:" . $v->merchantId, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }

    public function getAmount($merchantId, $amountData = '', $merchantData = '')
    {
        global $app;
        $logger = $app->getContainer()->logger;

        try {
            $merchantData = empty($merchantData) ? (new Merchant)->getCacheByMerchantId($merchantId) : $merchantData;
            $amountData = empty($amountData) ? (new self)->getCacheByMerchantId($merchantId) : $amountData;
            $accountDate = Tools::getAccountDate($merchantData['settlementTime']);
            $todaySettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
            $todaySettlement = empty($todaySettlement) ? 0 : current($todaySettlement);

            $holiday = new NationalHoliday;
//            $accountDate['settlementAmount'] = $accountDate['settlementAmount'] ?? 0;
//            $accountDate['freezeAmount'] = $accountDate['freezeAmount'] ?? 0;

            $availableBalance = 0.00;
            $settlementType = $merchantData['settlementType'];
            if ($settlementType == 'D0') {
                $availableBalance = $amountData['settlementAmount'] - $todaySettlement + $todaySettlement * $merchantData['D0SettlementRate'];
            } elseif ($settlementType == 'D1') {
                $availableBalance = $amountData['settlementAmount'] - $todaySettlement;

                $accountDate = \date('Ymd', \strtotime("$accountDate -1 day"));
                $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);

                $availableBalance = $availableBalance - $accountDateSettlement + $accountDateSettlement * $merchantData['D0SettlementRate'];
            } elseif ($settlementType == 'T0') {
                if ($holiday->isWorkDay($accountDate)) {
                    $needSettleAmount = $todaySettlement;

                    do {
                        $accountDate = \date('Ymd', \strtotime("$accountDate -1 day"));
                        if ($holiday->isWorkDay($accountDate)) {
                            break;
                        }

                        $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                        $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                        $needSettleAmount += $accountDateSettlement;
                    } while (true);

                    $availableBalance = $amountData['settlementAmount'] - $needSettleAmount + $needSettleAmount * $merchantData['D0SettlementRate'];
                } else {
                    $availableBalance = $amountData['settlementAmount'] - $todaySettlement;
                    do {
                        $accountDate = \date('Ymd', \strtotime("$accountDate -1 day"));
                        if ($holiday->isWorkDay($accountDate)) {
                            $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                            $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);

                            $availableBalance -= ($accountDateSettlement - $accountDateSettlement * $merchantData['D0SettlementRate']);
                            break;
                        }

                        $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                        $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                        $availableBalance -= $accountDateSettlement;
                    } while (true);
                }
            } elseif ($settlementType == 'T1') {
                $availableBalance = $amountData['settlementAmount'] - $todaySettlement;
                if ($holiday->isWorkDay($accountDate)) {
                    $needSettleAmount = 0.00;
                    do {
                        $accountDate = \date('Ymd', \strtotime("$accountDate -1 day"));
                        if ($holiday->isWorkDay($accountDate)) {
                            $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                            $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                            $needSettleAmount += $accountDateSettlement;
                            break;
                        }

                        $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                        $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                        $needSettleAmount += $accountDateSettlement;
                    } while (true);

                    $availableBalance = $availableBalance - $needSettleAmount + $needSettleAmount * $merchantData['D0SettlementRate'];
                } else {
                    do {
                        $accountDate = \date('Ymd', \strtotime("$accountDate -1 day"));
                        if ($holiday->isWorkDay($accountDate)) {
                            $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                            $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                            $availableBalance -= $accountDateSettlement;

                            //上一个结算日按比例结算后剩余的金额，非工作日不到账
                            $needSettleAmount = 0.00;
                            do {
                                $accountDate = \date('Ymd', \strtotime("$accountDate -1 day"));
                                if ($holiday->isWorkDay($accountDate)) {
                                    $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                                    $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                                    $needSettleAmount += $accountDateSettlement;
                                    break;
                                }

                                $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                                $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                                $needSettleAmount += $accountDateSettlement;
                            } while (true);

                            $availableBalance -= ($needSettleAmount - $needSettleAmount * $merchantData['D0SettlementRate']);
                            break;
                        }

                        $accountDateSettlement = AmountPay::where('merchantId', $merchantId)->where('accountDate', $accountDate)->selectRaw('(sum(amount) - sum(serviceCharge)) as s')->first()->toArray();
                        $accountDateSettlement = empty($accountDateSettlement) ? 0 : current($accountDateSettlement);
                        $availableBalance -= $accountDateSettlement;
                    } while (true);
                }
            } else {
                throw new \Exception("商户（" . $merchantData['merchantNo'] . "）结算方式未定义：" . $settlementType);
            }

            $availableBalance = ($availableBalance > 0 ? $availableBalance : 0.00);
            return [
                'settlementAmount' => isset($amountData['settlementAmount']) ? $amountData['settlementAmount'] - $availableBalance : 0.00,
                'accountBalance' => isset($amountData['settlementAmount']) ? ($amountData['settlementAmount'] + (isset($amountData['freezeAmount']) ? $amountData['freezeAmount'] : 0.00)) : 0.00,
                'freezeAmount' => isset($amountData['freezeAmount']) ? $amountData['freezeAmount'] : 0.00,
                'availableBalance' => $availableBalance,
                'settledAmount' => $availableBalance + (isset($amountData['freezeAmount']) ? $amountData['freezeAmount'] : 0.00),
            ];
        } catch (\Exception $e) {
            $logger->error('getAmount exception:' . $e->getMessage());

            return [
                'settlementAmount' => 0.00,
                'accountBalance' => 0.00,
                'freezeAmount' => 0.00,
                'availableBalance' => 0.00,
                'settledAmount' => 0.00,
            ];
        }
    }
}
