<?php

namespace app\components;

use Yii;
use Exception;
use yii\db\Expression;
use yii\helpers\Json;
use app\models\Client;
use app\models\Gateway;
use app\models\Service;
use app\models\EmiOption;
use app\models\CardSeries;
use app\models\Transaction;
use app\models\OrderQueueJob;
use app\models\TransactionLog;
use app\models\ClientServiceBanks;
use app\components\BracBank\payUtils;

class GatewayUtil
{
    public static function gateway($client): array
    {
        try {
            $gatewayList = [];
            $serviceType = strtoupper(Yii::$app->request->get('service'));
            $currencyCode = strtoupper(Yii::$app->request->get('currency'));
            $bankCode = Yii::$app->request->get('bankCode');
            $relatedBankConditions['client'] = $client->uid;
            if (!empty($serviceType)) {
                $service = Service::find()->where(['client' => $client->uid, 'name' => $serviceType, 'status' => Service::STATUS_ACTIVE])->one();
                if ($service) {
                    $relatedBankConditions['service'] = $service->uid;
                }
            }
            if (!empty($bankCode)) {
                $relatedBankConditions['bank'] = $bankCode;
            }
            $relatedBanks = ClientServiceBanks::find()->where($relatedBankConditions)
                ->with(
                    'relatedBank.relatedLogo',
                    'relatedBank.relatedCardSeries',
                    'relatedBank.gateways.relatedCurrency',
                    'relatedBank.gateways.conversion',
                    'relatedBank.relatedBankType'
                )
                ->addOrderBy(new Expression('priority IS NULL ASC, priority ASC, id ASC'))
                ->all();

            if (count($relatedBanks) > 0) {
                foreach ($relatedBanks as $clientAssignBank) {
                    $bank = $clientAssignBank->relatedBank;
                    if (!empty($bank) && $bank->status == 1 && isset($bank->gateways->status) && $bank->gateways->status == 1) {
                        $cards = [];
                        $logo = $bank->relatedLogo;
                        $cardSeries = $bank->relatedCardSeries;

                        $currency = $bank->gateways->relatedCurrency->code;
                        if (!empty($currencyCode) && $currency != $currencyCode) {
                            continue;
                        }

                        if ($clientAssignBank->showCardSeries == ClientServiceBanks::STATUS_ACTIVE) {
                            foreach ($cardSeries as $card) {
                                $cards[] = [
                                    'id' => $card->uid,
                                    'length' => $card->length,
                                    'series' => $card->series,
                                ];
                            }
                        }

                        $rate = $bank->gateways->conversion ? $bank->gateways->conversion->conversionRate : 1.00;
                        $toCurrencyCode = $bank->gateways->conversion ? $bank->gateways->conversion->relatedToCurrency->code : $bank->gateways->relatedCurrency->code;

                        if ($client->chargeStatus == Client::STATUS_ACTIVE) {
                            if ((isset($client->charge) && $client->charge !== null)) {
                                $charge = $client->charge;
                            } else if ((isset($clientAssignBank->charge) && $clientAssignBank->charge !== null)) {
                                $charge = $clientAssignBank->charge;
                            } else {
                                $charge = $bank->gateways->charge;
                            }
                        } else {
                            $charge = 0;
                        }

                        $gateway = [
                            'id' => $bank->uid,
                            'priority' => $clientAssignBank->priority,
                            'name' => $bank->name,
                            'code' => $bank->code,
                            "gatewayName" => $bank->gateways->name ?? null,
                            "gatewayCode" => $bank->gateways->code ?? null,
                            'type' => $bank->relatedBankType->title,
                            'currency' => [
                                'code' => $currency,
                                'conversion' => [
                                    'rate' => (float)$rate,
                                    'code' => $toCurrencyCode
                                ],
                            ],
                            "charge" => $charge,
                            'logo' => [
                                'small' => $logo->small,
                                'medium' => $logo->medium,
                                'large' => $logo->large,
                                'smallLogo' => $logo->smallLogo ?? null
                            ],
                            'bin' => [
                                'restriction' => (boolean)$clientAssignBank->binRestriction ?: false,
                                'length' => Utils::getMaxBinLength($cardSeries),
                            ],
                            'tripCoinMultiply' => Utils::getTripCoinMultiply($clientAssignBank->tripCoinMultiply),
                            'series' => $cards,
                        ];

                        $gateway['emi'] = [];
                        $gateway['emi']['enable'] = false;
                        $gateway['emi']['options'] = [];
                        $gateway['emi']['emiRemarks'] = "";
                        if ($client->extraParams == Client::STATUS_ACTIVE) {
                            if (!empty($serviceType)) {
                                if (isset($service) && ($clientAssignBank->extraParams != 0)) {
                                    if ($clientAssignBank->extraParams == 1 || $clientAssignBank->extraParams == 2) {
                                        $gateway['coupon_applicable'] = (bool)$clientAssignBank->coupon;
                                        $gateway['earn_tripcoin_applicable'] = (bool)$clientAssignBank->earnTripCoin;
                                        $gateway['redeem_tripcoin_applicable'] = (bool)$clientAssignBank->redeemTripCoin;
                                    }

                                    if ((($clientAssignBank->extraParams == 2) || ($clientAssignBank->extraParams == 3)) && ($clientAssignBank->params != null)) {
                                        $extraObject = Json::decode($clientAssignBank->params);
                                        foreach ($extraObject as $innerKey => $value) {
                                            $gateway[$innerKey] = $value;
                                        }
                                    }

                                    $gateway['emi']['enable'] = self::getEmiEnableOrDisable($clientAssignBank);
                                    $gateway['emi']['emiRemarks'] = !empty($clientAssignBank->emiRemarks) ? $clientAssignBank->emiRemarks : "";
                                    $gateway['emi']['options'] = self::getEmiOptions($clientAssignBank);
                                }
                            }
                        } else {
                            if ($clientAssignBank->extraParams != 0) {
                                if ($clientAssignBank->extraParams == 1 || $clientAssignBank->extraParams == 2) {
                                    $gateway['coupon_applicable'] = (bool)$clientAssignBank->coupon;
                                    $gateway['earn_tripcoin_applicable'] = (bool)$clientAssignBank->earnTripCoin;
                                    $gateway['redeem_tripcoin_applicable'] = (bool)$clientAssignBank->redeemTripCoin;
                                }

                                if ((($clientAssignBank->extraParams == 2) || ($clientAssignBank->extraParams == 3)) && ($clientAssignBank->params != null)) {
                                    $extraObject = Json::decode($clientAssignBank->params);
                                    foreach ($extraObject as $innerKey => $value) {
                                        $gateway[$innerKey] = $value;
                                    }
                                }
                                $gateway['emi']['enable'] = self::getEmiEnableOrDisable($clientAssignBank);
                                $gateway['emi']['options'] = self::getEmiOptions($clientAssignBank);
                                $gateway['emi']['emiRemarks'] = !empty($clientAssignBank->emiRemarks) ? $clientAssignBank->emiRemarks : "";
                            }
                        }

                        $gatewayList[] = $gateway;
                    }
                }
            }

            return [
                'success' => true,
                'code' => 200,
                'message' => 'success',
                'data' => $gatewayList
            ];
        } catch (Exception $e) {
            TransactionLog::createLog('Gateway Api Exception', '', $e->getMessage() . '-' . $e->getLine() . '-' . $e->getFile(), 0);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Something went wrong',
                'data' => []
            ];
        }
    }

    public static function getEmiEnableOrDisable($clientAssignBank)
    {
        return in_array($clientAssignBank->showEmi, [EmiOption::EMI_WITHOUT_DISCOUNT, EmiOption::EMI_WITH_DISCOUNT]);
    }

    public static function getEmiOptions($clientAssignBank)
    {
        $optionsProcessedArray = [];
        if (!in_array($clientAssignBank->showEmi, [EmiOption::EMI_WITHOUT_DISCOUNT, EmiOption::EMI_WITH_DISCOUNT])) {
            return $optionsProcessedArray;
        }

        $emiOptions = EmiOption::findAll(['bank' => $clientAssignBank->bank, 'status' => EmiOption::STATUS_ACTIVE]);
        foreach ($emiOptions as $option) {
            $optionsProcessedArray[] = [
                "code" => $option->uid,
                "withDiscount" => EmiOption::WITH_DISCOUNT[$clientAssignBank->showEmi],
                "repaymentPeriod" => $option->repaymentPeriod,
                "interestRate" => $option->interestRate
            ];
        }

        return $optionsProcessedArray;
    }

    public static function createOrder($gatewayCode, $amount, $successUrl, $cancelUrl, $declineUrl): array
    {
        $response = [];
        $orderId = Utils::getOrderId();

        if ($gatewayCode == 'CYBERSOURCE') {
            $cyberSource = new CyberSource($gatewayCode);
            $response = $cyberSource->createOrder($amount, $orderId, $successUrl, $cancelUrl, $declineUrl);
        } else if ($gatewayCode == Gateway::GATEWAY_TAZAPAY) {
            $tazaPay = new TazaPay($gatewayCode);
            $response = $tazaPay->createOrder($amount, $orderId, $successUrl, $cancelUrl, $declineUrl);
        } else if ($gatewayCode == Gateway::GATEWAY_SBL) {
            $southeastBank = new SoutheastBank($gatewayCode);
            $response = $southeastBank->CreateOrder($amount, $orderId, $successUrl, $cancelUrl, $declineUrl);
        } else if ($gatewayCode == Gateway::GATEWAY_CHECKOUT) {
            $checkout = new Checkout($gatewayCode);
            $response = $checkout->createOrder($amount,$orderId, $successUrl, $cancelUrl, $declineUrl);
        }

        if ($response['success']) {
            Yii::$app->watcher->delay(getenv('WATCHER_DELAY'))->push(new OrderQueueJob(['orderId' => $response['orderId']]));

            return [
                    'success' => true,
                    'code' => 200,
                    'message' => 'success',
                    'url' => $response['url'],
                ] + self::getRelatedRedirectUrls($response['orderId']);
        }

        return [
            'success' => false,
            'code' => 500,
            'message' => $response['message'] ?? 'Payment System Down!!!',
            'url' => null,
            'successUrl' => null,
            'cancelUrl' => null,
            'declineUrl' => null,
        ];
    }

    /**
     * @param $orderId
     * @return array
     */
    private static function getRelatedRedirectUrls($orderId): array
    {
        try {
            $transaction = Transaction::getByKey('orderId', $orderId);

            $successUrl = RedirectUtil::getSuccessUrl($transaction);
            $cancelUrl = RedirectUtil::getCancelUrl($transaction);
            $declineUrl = RedirectUtil::getDeclineUrl($transaction);

            return [
                'successUrl' => $successUrl ?? null,
                'cancelUrl' => $cancelUrl ?? null,
                'declineUrl' => $declineUrl ?? null,
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getOrderDetails($accessToken, $bookingId, $service): array
    {
        $orderDetailsArray = [];
        $client = Client::findOne(['secret' => $accessToken, 'status' => Client::STATUS_ACTIVE]);

        if ($client) {
            $orderDetails = Transaction::getOrderDetails($bookingId, $service, $client);

            if (!empty($orderDetails)) {
                foreach ($orderDetails as $orderDetail) {
                    $rate = $orderDetail->relatedGateway->conversion ? $orderDetail->relatedGateway->conversion->conversionRate : 1.00;
                    $toCurrency = $orderDetail->relatedGateway->conversion ?
                        $orderDetail->relatedGateway->conversion->relatedToCurrency->code :
                        $orderDetail->relatedGateway->conversion->code;

                    $binFromPan = explode('x', $orderDetail->pan);
                    $binFromPanDetails = CardSeries::find()->where(['series' => $binFromPan, 'bank' => $orderDetail->bankCode])->one();

                    $orderDetailObject = [
                        'transactionId' => $orderDetail->uid,
                        "orderId" => $orderDetail->orderId,
                        "bookingId" => $orderDetail->bookingId,
                        "serviceType" => $orderDetail->serviceType,
                        "amount" => $orderDetail->amount,
                        'currency' => [
                            'code' => $orderDetail->relatedCurrency->code,
                            'conversion' => [
                                'rate' => (float)$rate,
                                'code' => $toCurrency
                            ]
                        ],
                        "description" => $orderDetail->description,
                        "gateway" => [
                            "code" => $orderDetail->gateway,
                            "name" => $orderDetail->relatedGateway->name,
                            "charge" => $orderDetail->charge,
                        ],
                        "bank" => [
                            "code" => $orderDetail->bankCode,
                            "name" => $orderDetail->relatedBank->name
                        ],
                        "card" => [
                            "code" => $orderDetail->cardSeries,
                            "brand" => $orderDetail->cardBrand,
                            "prefix" => $orderDetail->cardPrefix,
                            "length" => $orderDetail->cardLength,
                        ],
                        "name" => $orderDetail->cardHolderName,
                        "pan" => $orderDetail->pan,
                        "bankOrderStatus" => $orderDetail->bankOrderStatus,
                        "bankApprovalCode" => $orderDetail->bankApprovalCode,
                        "rrn" => $orderDetail->rrn
                    ];

                    if($orderDetail->onEmi) {
                        $orderDetailObject["emi"] = [
                            "repaymentPeriod" => $orderDetail->emiInterestAmount,
                            "interestRate" => $orderDetail->emiInterestRate,
                        ];
                    }

                    $orderDetailObject["status"] = Transaction::statusByNumber($orderDetail->status);
                    $orderDetailObject["createdAt"] = Utils::getIntDateTime($orderDetail->createdAt);
                    $orderDetailObject["updatedAt"] = Utils::getIntDateTime($orderDetail->updatedAt);

                    if ($orderDetail->status == Transaction::STATUS_REFUND) {
                        $orderDetailObject['refund'] = [
                            "date" => Utils::getIntDateTime($orderDetail->relatedRefund->updatedAt),
                            "charge" => $orderDetail->relatedRefund->charge
                        ];
                    } else if ($orderDetail->status == Transaction::STATUS_VOID) {
                        $orderDetailObject['void'] = [
                            "date" => Utils::getIntDateTime($orderDetail->relatedRefund->updatedAt),
                            "charge" => $orderDetail->relatedRefund->charge

                        ];
                    }
                    $orderDetailObject['verification'] = [
                        "bank" => true,
                        "binNumber" => $orderDetail->cardPrefix == $binFromPan[0],
                        "withinBinSeries" => isset($binFromPanDetails->uid),
                        "verdict" => $orderDetail->cardPrefix == $binFromPan[0] && isset($binFromPanDetails->uid),
                        "issuerBank" => [
                            "name" => $orderDetail->relatedBank->name,
                            "code" => $orderDetail->bankCode
                        ],
                        "issuerCard" => [
                            "prefix" => $binFromPan[0],
                            "code" => (isset($binFromPanDetails->uid)) ? $binFromPanDetails->uid : null//'This CardSeries does not exist in the Payment Manager DB'
                        ]
                    ];
                    $orderDetailsArray[] = $orderDetailObject;
                }

                return [
                    'success' => true,
                    'code' => 200,
                    'message' => 'Order Details of BookingID:' . $bookingId,
                    'data' => $orderDetailsArray
                ];
            } else {
                return [
                    'success' => false,
                    'code' => 200,
                    'message' => 'Order Details of BookingID:' . $bookingId,
                    'data' => null
                ];
            }
        }
        return Utils::InvalidToken();
    }

    public static function getPaymentDetails($accessToken, $transactionId): array
    {
        $cacheKey = $accessToken . '_paymentDetails';
        $client = Yii::$app->cache->getOrSet($cacheKey, function () use ($accessToken) {
            return Client::find()->where(['secret' => $accessToken, 'status' => Client::STATUS_ACTIVE])->one();
        }, 86400);

        if (!$client) {
            return Utils::InvalidToken();
        }

        $paymentDetails = Transaction::getPaymentDetails($transactionId);

        if (empty($paymentDetails)) {
            return [
                'success' => false,
                'code' => 200,
                'message' => 'Order Details of Transaction ID: ' . $transactionId,
                'data' => null
            ];
        }

        $rate = $paymentDetails->relatedGateway->conversion ? $paymentDetails->relatedGateway->conversion->conversionRate : 1.00;
        $toCurrency = $paymentDetails->relatedGateway->conversion ?
            $paymentDetails->relatedGateway->conversion->relatedToCurrency->code :
            $paymentDetails->relatedGateway->conversion->code;

        $binFromPan = explode('x', $paymentDetails->pan);
        $binFromPanDetails = CardSeries::find()->where(['series' => $binFromPan, 'bank' => $paymentDetails->bankCode])->one();

        $orderDetailObject = [
            'transactionId' => $paymentDetails->uid,
            "orderId" => $paymentDetails->orderId,
            "bookingId" => $paymentDetails->bookingId,
            "serviceType" => $paymentDetails->serviceType,
            "amount" => $paymentDetails->amount,
            'currency' => [
                'code' => $paymentDetails->relatedCurrency->code,
                'conversion' => [
                    'rate' => (float)$rate,
                    'code' => $toCurrency
                ]
            ],
            "description" => $paymentDetails->description,
            "gateway" => [
                "code" => $paymentDetails->gateway,
                "name" => $paymentDetails->relatedGateway->name,
                "charge" => $paymentDetails->charge,
            ],
            "bank" => [
                "code" => $paymentDetails->bankCode,
                "name" => $paymentDetails->relatedBank->name
            ],
            "card" => [
                "code" => $paymentDetails->cardSeries,
                "brand" => $paymentDetails->cardBrand,
                "prefix" => $paymentDetails->cardPrefix,
                "length" => $paymentDetails->cardLength,
            ],

            "name" => $paymentDetails->cardHolderName,
            "pan" => $paymentDetails->pan,
            "bankOrderStatus" => $paymentDetails->bankOrderStatus,
            "bankApprovalCode" => $paymentDetails->bankApprovalCode,
            "rrn" => $paymentDetails->rrn
        ];

        if($paymentDetails->onEmi) {
            $orderDetailObject["emi"] = [
                "repaymentPeriod" => $paymentDetails->emiInterestAmount,
                "interestRate" => $paymentDetails->emiInterestRate,
            ];
        }

        $orderDetailObject["status"] = Transaction::statusByNumber($paymentDetails->status);
        $orderDetailObject["createdAt"] = Utils::getIntDateTime($paymentDetails->createdAt);
        $orderDetailObject["updatedAt"] = Utils::getIntDateTime($paymentDetails->updatedAt);

        if ($paymentDetails->status == Transaction::STATUS_REFUND || $paymentDetails->status == Transaction::STATUS_VOID) {
            $type = $paymentDetails->status == Transaction::STATUS_REFUND ? 'refund' : 'void';
            $orderDetailObject[$type] = [
                'date' => Utils::getIntDateTime($paymentDetails->relatedRefund->updatedAt),
                'charge' => $paymentDetails->relatedRefund->charge
            ];
        }

        $orderDetailObject['verification'] = [
            "bank" => true,
            "binNumber" => $paymentDetails->cardPrefix == $binFromPan[0],
            "withinBinSeries" => isset($binFromPanDetails->uid),
            "verdict" => $paymentDetails->cardPrefix == $binFromPan[0] && isset($binFromPanDetails->uid),
            "issuerBank" => [
                "name" => $paymentDetails->relatedBank->name,
                "code" => $paymentDetails->bankCode
            ],
            "issuerCard" => [
                "prefix" => $binFromPan[0],
                "code" => (isset($binFromPanDetails->uid)) ? $binFromPanDetails->uid : null//'This CardSeries does not exist in the Payment Manager DB'
            ]
        ];
        $orderDetailsArray = $orderDetailObject;

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Order Details of Transaction ID:' . $transactionId,
            'data' => $orderDetailsArray
        ];
    }
}