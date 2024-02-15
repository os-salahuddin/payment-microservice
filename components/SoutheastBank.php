<?php

namespace app\components;

use Exception;
use yii\helpers\Url;
use app\models\Logo;
use yii\helpers\Json;
use app\models\Gateway;
use app\models\Currency;
use app\models\CardSeries;
use app\models\Transaction;
use app\models\TransactionLog;

class SoutheastBank
{
    private $paymentGateway;
    private $currency;
    private $paymentUrl;
    private $returnUrl;
    private $cancelUrl;
    private $timeoutUrl;
    private $webhookUrl;
    private $logo;
    private $gatewayUrl;
    private $merchantId;
    private $password;
    private $apiUsername;

    public function getGatewayUrl(): string
    {
        return $this->gatewayUrl;
    }

    public function setGatewayUrl($newGatewayUrl)
    {
        $this->gatewayUrl = $newGatewayUrl;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    public function getApiUsername(): string
    {
        return $this->apiUsername;
    }

    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = 'merchant.' . $apiUsername;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $currencyInfos = Currency::getByKey('uid', $currency);
        if (isset($currencyInfos->code)) {
            $this->currency = $currencyInfos->code;
        }
    }

    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl($paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    public function setCancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;
    }

    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function getTimeOutUrl()
    {
        return $this->timeoutUrl;
    }

    public function setTimeOutUrl($timeoutUrl)
    {
        $this->timeoutUrl = $timeoutUrl;
    }

    public function getVersionAndNpv(): string
    {
        return '/api/nvp/version/78';
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo($logo)
    {
        $this->logo = Logo::getByKey('uid', $logo)->large;
    }

    public function setConfig()
    {
        try {
            if (isset($this->paymentGateway->gatewayMode)) {
                if ($this->paymentGateway->gatewayMode === Gateway::GATEWAY_MODE_TEST_INT) {
                    $this->setGatewayUrl($this->paymentGateway->sandboxUrl);

                    $extraParams = (object)Json::decode($this->paymentGateway->extraParams);
                    $this->setMerchantId($extraParams->merchantSandbox);
                    $this->setApiUsername($extraParams->merchantSandbox);
                    $this->setPassword($extraParams->merchantPasswordSandbox);
                } else {
                    $this->setGatewayUrl($this->paymentGateway->liveUrl);
                    $this->setMerchantId($this->paymentGateway->merchant);
                    $this->setApiUsername($this->paymentGateway->merchant);
                    $this->setPassword($this->paymentGateway->merchantPassword);
                }
            }

            if (isset($this->paymentGateway->currency))
                $this->setCurrency($this->paymentGateway->currency);

            if (isset($this->paymentGateway->logo))
                $this->setLogo($this->paymentGateway->logo);

            $this->setReturnUrl(Url::to(['callback/sbl-success'], getenv('PROTOCOL')));
            $this->setCancelUrl(Url::to(['callback/sbl-cancel'], getenv('PROTOCOL')));
            $this->setWebhookUrl(Url::to(['callback/sbl-webhook'], getenv('PROTOCOL')));
            $this->setTimeOutUrl(Url::to(['callback/sbl-timeout'], getenv('PROTOCOL')));

        } catch (Exception $e) {
            TransactionLog::createLog('SBL Configuration', 'Exception', $e->getMessage(), 0);
        }
    }

    function __construct($gatewayCode)
    {
        $this->paymentGateway = Gateway::find()->where(['code' => $gatewayCode, 'status' => Gateway::STATUS_ACTIVE])->one();
        $this->setConfig();
    }
    /**-----------------------------------Configuration END-----------------------------------------------**/


    /**
     * Create Order
     * @param $bookingId
     * @param $serviceType
     * @param $amount
     * @param $description
     * @param $cardSeries
     * @param $accessToken
     * @param $bankCode
     * @param $orderId
     * @param $successUrl
     * @param $cancelUrl
     * @param $declineUrl
     * @param $customerId
     * @param $customerName
     * @param $extraParams
     * @return array
     */
    public function CreateOrder($bookingId, $serviceType, $amount, $description, $cardSeries, $accessToken,
                                $bankCode, $orderId, $successUrl, $cancelUrl, $declineUrl, $customerId, $customerName, $name, $email, $phone, $bookingCode,
                                $extraParams, $emiDetails): array
    {
        $response = ['success' => false, 'url' => null, 'orderId' => null];

        try {
            $cardSeriesData = CardSeries::getByKey('uid', $cardSeries);

            $extraPramsJson = [
                'successUrl' => $successUrl,
                'cancelUrl' => $cancelUrl,
                'declineUrl' => $declineUrl,
                'extraParams' => $extraParams
            ];

            $processedChargeClient = Utils::getCharge($accessToken, $bankCode, $serviceType, $this->paymentGateway->charge);
            $relatedClientServiceBanks = Utils::getRelatedClientServiceBanks($processedChargeClient['uid'], $processedChargeClient['service'], $bankCode);
            $insert = [
                'uid' => Utils::uniqueCode(36),
                'clientId' => $processedChargeClient['uid'],
                'bookingId' => $bookingId,
                'orderId' => $orderId,
                'bankCode' => $bankCode,
                'customerId' => $customerId,
                'customerName' => $customerName,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'bookingCode' => $bookingCode,
                'sessionId' => '',
                'amount' => $amount,
                "charge" => $processedChargeClient['charge'],
                'description' => $description,
                'bankRequestUrl' => '',
                'bankResponse' => json_encode([]),
                'type' => 'PURCHASE',
                'bankOrderStatus' => 'CREATED',
                'sessionVersion' => '',
                'resultIndicator' => '',
                'extraParams' => json_encode($extraPramsJson),
                'status' => Transaction::STATUS_CANCELLED,
                'callbackUrl' => $this->getReturnUrl(),
                'currency' => $this->paymentGateway->relatedCurrency->uid,
                'gateway' => $this->paymentGateway->uid ?? null,
                'cardSeries' => $cardSeries,
                'cardPrefix' => (isset($cardSeriesData->series)) ? $cardSeriesData->series : '',
                'cardLength' => (isset($cardSeriesData->length)) ? $cardSeriesData->length : '',
                'serviceType' => $serviceType,
                "binRestriction" => $relatedClientServiceBanks->binRestriction,
                "binLength" => Utils::getMaxBinLength($relatedClientServiceBanks->relatedBank->relatedCardSeries),
                "tripCoinMultiply" => Utils::getTripCoinMultiply($relatedClientServiceBanks->tripCoinMultiply),
            ];

            $insert += $emiDetails;

            if (Transaction::add($insert)) {
                $apiOperation = 'INITIATE_CHECKOUT';
                $requestArray = [
                    "apiOperation" => $apiOperation,
                    "order.id" => $orderId,
                    "order.description" => $description,
                    "order.currency" => $this->getCurrency(),
                    "order.amount" => $amount,
                    "order.notificationUrl" => $this->getWebhookUrl(),
                    "interaction.cancelUrl" => $this->getCancelUrl() . '?order=' . $orderId,
                    "interaction.returnUrl" => $this->getReturnUrl(),
                    "interaction.operation" => 'PURCHASE',
                    "interaction.timeout" => '600',
                    "interaction.timeoutUrl" => $this->getTimeOutUrl() . '?order=' . $orderId,
                    "interaction.merchant.name" => 'Share Trip Limited',
                    "interaction.merchant.logo" => $this->getLogo(),
                    "interaction.displayControl.billingAddress" => 'HIDE',
                    "interaction.country" => 'BGD'
                ];

                $checkout = $this->Request($requestArray);

                if ($checkout['result'] == 'SUCCESS') {
                    $absolutePath = $this->getGatewayUrl() . "/checkout/entry/" . $checkout["session.id"] . '?checkoutVersion=1.0.0';
                    $this->setPaymentUrl($absolutePath);

                    $insertData = [
                        'sessionId' => $checkout["session.id"],
                        'bankRequestUrl' => $absolutePath,
                        'bankResponse' => json_encode($checkout),
                        'sessionVersion' => $checkout['session.version'],
                        'resultIndicator' => $checkout['successIndicator'],
                        'status' => Transaction::STATUS_CREATED
                    ];

                    $response = ['success' => true, 'url' => $this->getPaymentUrl(), 'orderId' => $orderId];
                } else {
                    $insertData = [
                        'bankResponse' => json_encode($checkout),
                        'bankOrderStatus' => 'ORDER CREATION FAILED',
                    ];
                }

                Transaction::updateByKeyValue(['orderId' => $orderId], $insertData, true);
                $transactionLogData = [
                    'orderId' => $orderId,
                    'bookingId' => $bookingId,
                    'client' => $processedChargeClient['name'],
                    'serviceType' => $serviceType,
                    'message' => 'SBL CreateOrder',
                    'request' => $requestArray,
                    'response' => $checkout
                ];

                TransactionLogDetailsUtil::createLog($transactionLogData);
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'message' => 'SBL CreateOrder Exception',
                    'response' => $e->getMessage()
                ]
            );
        }

        return $response;
    }

    public function Request($requestArray): array
    {
        $request = $this->ParseRequest($requestArray);

        if ($request == '') {
            return [
                'success' => true,
                'code' => 412,
                'message' => 'No Post Data Received',
                'data' => null
            ];
        }
        $response = $this->SendTransaction($request);
        return $this->ParseResponse($response);
    }

    public function ParseRequest($formData): string
    {
        if (count($formData) == 0) return "";

        $request = "";
        foreach ($formData as $fieldName => $fieldValue) {
            if (strlen($fieldValue) > 0 && $fieldName != "merchant" && $fieldName != "apiPassword" && $fieldName != "apiUsername") {
                for ($i = 0; $i < strlen($fieldName); $i++) {
                    if ($fieldName[$i] == '_')
                        $fieldName[$i] = '.';
                }
                $request .= $fieldName . "=" . urlencode($fieldValue) . "&";
            }
        }

        $request .= "merchant=" . urlencode($this->getMerchantId()) . "&";
        $request .= "apiPassword=" . urlencode($this->getPassword()) . "&";
        $request .= "apiUsername=" . urlencode($this->getApiUsername());

        return $request;
    }

    public function SendTransaction($request)
    {
        $url = $this->getGatewayUrl() . $this->getVersionAndNpv();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Length: " . strlen($request)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded;charset=UTF-8"));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            $response = "cURL Error: " . curl_errno($curl) . " - " . curl_error($curl);
        }

        return $response;
    }

    public function ParseResponse($string): array
    {
        $response = [];
        if (strlen($string) != 0) {
            $pairArray = explode("&", $string);
            foreach ($pairArray as $pair) {
                $param = explode("=", $pair);
                $response[urldecode($param[0])] = urldecode($param[1]);
            }
        }
        return $response;
    }

    public function getOrder($orderId): array
    {
        $requestArray = array(
            "apiOperation" => "RETRIEVE_ORDER",
            "order.id" => $orderId
        );
        return $this->Request($requestArray);
    }

    /**
     * Retrieve Order
     * @param $orderId
     * @param string $callBack
     * @return array
     */
    public function retrieveOrder($orderId, string $callBack = 'SUCCESS'): array
    {
        try {
            $requestArray = array(
                "apiOperation" => "RETRIEVE_ORDER",
                "order.id" => $orderId
            );

            $orderResponse = $this->Request($requestArray);

            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'request' => $requestArray,
                    'message' => 'SBL retrieveOrder',
                    'response' => $orderResponse
                ]
            );

            if ($callBack == 'SUCCESS' && (isset($orderResponse['result']) && $orderResponse['result'] == 'SUCCESS')) {
                $counter = 0;
                $bankApprovalCode = null;
                $bankOrderStatus = '';
                $bankMerchantTranId = '';
                $bankResponseCode = '';
                $rrn = '';
                $type = '';
                while (isset($orderResponse['transaction[' . $counter . '].result'])) {
                    if (!isset($orderResponse['transaction[' . $counter . '].result'])) {
                        break;
                    }

                    $bankApprovalCode = $orderResponse['transaction[' . $counter . '].transaction.authorizationCode'] ?? null;

                    $bankOrderStatus = $orderResponse['transaction[' . $counter . '].order.status'];
                    $bankMerchantTranId = $orderResponse['transaction[' . $counter . '].transaction.id'];

                    $rrn = (isset($orderResponse['transaction[' . $counter . '].transaction.receipt']))
                        ? $orderResponse['transaction[' . $counter . '].transaction.receipt'] : null;

                    $type = $orderResponse['transaction[' . $counter . '].transaction.type'];

                    $bankResponseCode = (isset($orderResponse['transaction[' . $counter . '].authorizationResponse.responseCode']))
                        ? $orderResponse['transaction[' . $counter . '].authorizationResponse.responseCode'] : null;

                    $counter++;
                }

                $data = [
                    'orderId' => $orderResponse['id'],
                    'amount' => $orderResponse['amount'],
                    'refundAmount' => $orderResponse['totalRefundedAmount'],
                    'description' => $orderResponse['description'],
                    'bankResponse' => json_encode($orderResponse),
                    'type' => $type,
                    'rrn' => $rrn,
                    'pan' => $orderResponse['sourceOfFunds.provided.card.number'],
                    'bankTransactionDate' => $orderResponse['creationTime'], //Order Time
                    'bankResponseCode' => $bankResponseCode,
                    'bankResponseDescription' => 'Order: ' . $orderResponse['id'] . ' Payment Successful',
                    'cardHolderName' => $orderResponse['sourceOfFunds.provided.card.nameOnCard'], // CardHolder Name
                    'cardBrand' => $orderResponse['sourceOfFunds.provided.card.brand'], //Payment Brand
                    'bankOrderStatus' => $bankOrderStatus,
                    'bankApprovalCode' => (string)$bankApprovalCode,
                    'bankMerchantTranId' => (string)$bankMerchantTranId,
                    'status' => Transaction::STATUS_PAID
                ];
            } else if ($callBack == 'CANCEL') {
                $data = [
                    'bankOrderStatus' => 'CANCEL',
                    'bankResponseDescription' => 'Order: ' . $orderId . ' Payment Canceled',
                    'status' => 0,
                    'callbackUrl' => $this->getCancelUrl() . '?order=' . $orderId
                ];
            } else {
                $data = [
                    'bankOrderStatus' => 'TIMEOUT',
                    'bankResponseDescription' => 'Order: ' . $orderId . ' Payment Timeout',
                    'status' => 3,
                    'callbackUrl' => $this->getTimeOutUrl() . '?order=' . $orderId
                ];
            }
            return $data;
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'message' => 'SBL retrieveOrder Exception',
                    'response' => $e->getMessage()
                ]
            );
            return [];
        }
    }

    public function refundTransaction($orderId, $amount, $transactionId): array
    {
        try {
            $requestArray = array(
                "apiOperation" => "REFUND",
                "order.id" => $orderId,
                "merchant" => $this->getMerchantId(),
                "transaction.amount" => $amount,
                "transaction.id" => 'REFUND-' . $transactionId,
                "transaction.currency" => $this->paymentGateway->relatedCurrency->code
            );

            return $this->Request($requestArray);

        } catch (Exception $e) {
            return [];
        }
    }

    public function voidTransaction($orderId, $transactionId): array
    {
        try {
            $requestArray = array(
                "apiOperation" => "VOID",
                "order.id" => $orderId,
                "transaction.targetTransactionId" => $transactionId,
                "transaction.id" => 'VOID-' . $transactionId
            );
            return $this->Request($requestArray);

        } catch (Exception $e) {
            return [];
        }
    }
}