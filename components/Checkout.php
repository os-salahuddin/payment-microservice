<?php

namespace app\components;

use Exception;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\helpers\Url;
use app\models\Gateway;
use app\models\CardSeries;
use app\models\Transaction;
use app\models\TransactionLog;

class Checkout
{
    /**
     * @var array|ActiveRecord|null
     */
    private $paymentGateway;
    /**
     * @var
     */
    private $publicKey;
    /**
     * @var
     */
    private $secretKey;
    /**
     * @var
     */
    private $apiUrl;
    /**
     * @var
     */
    private $returnUrl;
    /**
     * @var
     */
    private $secretApiKey;
    /**
     * @var
     */
    private $channelId;

    // Autoload Function

    /**
     * @param $gatewayCode
     */
    public function __construct($gatewayCode = Gateway::GATEWAY_CHECKOUT)
    {
        $this->paymentGateway = Gateway::find()->where(['code' => $gatewayCode, 'status' => Gateway::STATUS_ACTIVE])->one();
        $this->gatewayCode = $gatewayCode;

        $this->setConfig();
    }

    /**
     * @return void
     */
    public function setConfig()
    {
        $extraParams = (object)Json::decode($this->paymentGateway->extraParams);

        if ($this->paymentGateway->gatewayMode === Gateway::GATEWAY_MODE_TEST_INT) {
            $this->setApiUrl($extraParams->apiUrlSandbox);
            $this->setPublicKey($extraParams->merchantSandbox);
            $this->setSecretKey($extraParams->merchantPasswordSandbox);
            $this->setSecretApiKey($extraParams->secretApiKeySandbox);
            $this->setChannelId($extraParams->channelIdSandbox);
        } else {
            $this->setApiUrl($extraParams->apiUrl);
            $this->setPublicKey($this->paymentGateway->merchant);
            $this->setSecretKey($extraParams->merchantPassword);
            $this->setSecretApiKey($extraParams->secretApiKey);
            $this->setChannelId($extraParams->channelId);
        }
        $this->setReturnUrl(Url::to(['callback/checkout-callback'], getenv('PROTOCOL')));
    }

    /**
     * @param $apiUrl
     * @return string
     */
    public function setApiUrl($apiUrl): string
    {
        return $this->apiUrl = $apiUrl;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @param $publicKey
     * @return void
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param $secret
     * @return void
     */
    public function setSecretKey($secret)
    {
        $this->secretKey = $secret;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param $secretApiKey
     * @return void
     */
    public function setSecretApiKey($secretApiKey)
    {
        $this->secretApiKey = $secretApiKey;
    }

    /**
     * @return mixed
     */
    public function getSecretApiKey()
    {
        return $this->secretApiKey;
    }

    /**
     * @param $channelId
     * @return void
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param $returnUrl
     * @return void
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    /**
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
     * @param $emiDetails
     * @return array
     */
    public function CreateOrder($bookingId, $serviceType, $amount, $description, $cardSeries, $accessToken,
                                $bankCode, $orderId, $successUrl, $cancelUrl, $declineUrl, $customerId, $customerName, $name, $email, $phone, $bookingCode, $extraParams, $emiDetails): array
    {
        $uid = Utils::uniqueCode(36);
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
                'uid' => $uid,
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
                'bankOrderStatus' => '',
                'bankMerchantTranId' => '',
                'extraParams' => json_encode($extraPramsJson),
                'status' => Transaction::STATUS_CANCELLED,
                'callbackUrl' => $this->getReturnUrl(),
                'currency' => $this->paymentGateway->relatedCurrency->uid,
                'gateway' => $this->paymentGateway->uid,
                'cardSeries' => $cardSeries,
                'cardPrefix' => (isset($cardSeriesData->series)) ? $cardSeriesData->series : '',
                'cardLength' => (isset($cardSeriesData->length)) ? $cardSeriesData->length : '',
                'serviceType' => $serviceType,
                "binRestriction" => $relatedClientServiceBanks->binRestriction,
                "binLength" => Utils::getMaxBinLength($relatedClientServiceBanks->relatedBank->relatedCardSeries),
                "tripCoinMultiply" => $relatedClientServiceBanks->tripCoinMultiply
            ];

            $insert += $emiDetails;
            if (Transaction::add($insert)) {
                $response = $this->requestPayment($orderId, $amount, 'BDT');

                if ($response['success'] === true && !empty($response['url'])) {
                    $update = [
                        'bankOrderStatus' => 'OrderCreationSuccess',
                        'status' => Transaction::STATUS_CREATED,
                        'bankResponse' => json_encode($response),
                    ];

                    $response = ['success' => true, 'url' => $response['url'], 'orderId' => $orderId];
                } else {
                    $update = [
                        'orderId' => $orderId,
                        'bankResponse' => json_encode($response),
                        'bankOrderStatus' => 'OrderCreationFailed',
                    ];
                }

                Transaction::updateByKeyValue(['orderId' => $orderId], $update, true);

                $transactionLogData = [
                    'orderId' => $orderId,
                    'bookingId' => $bookingId,
                    'client' => $processedChargeClient['name'],
                    'serviceType' => $serviceType,
                    'message' => 'Checkout CreateOrder',
                    'request' => $insert,
                    'response' => $response
                ];

                TransactionLogDetailsUtil::createLog($transactionLogData);
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'message' => 'Checkout CreateOrder Exception',
                    'response' => $e->getMessage()
                ]
            );
        }

        return $response;
    }

    /**
     * @param $orderId
     * @param $amount
     * @return array
     */
    public function requestPayment($orderId, $amount, $currency='BDT')
    {
        try {
            $url = $this->getApiUrl() . '/hosted-payments';
            $payload = json_encode([
                'amount' => $amount * 100,
                'currency' => $currency,
                'payment_type' => 'Regular',
                'reference' => $orderId,
                'processing_channel_id' => $this->getChannelId(),
                'risk' => [
                    'enabled' => true,
                ],
                '3ds' => [
                    'enabled' => true
                ],
                'success_url' => $this->getReturnUrl(),
                'cancel_url' => $this->getReturnUrl() . '?ref=cancel&order_id=' . $orderId,
                'failure_url' => $this->getReturnUrl(),
                'billing' => [
                    'address' => [
                        'country' => 'BD'
                    ]
                ],
                'capture' => true
            ]);
            $response = $this->sendRequest($url, 'post', $payload);

            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'request' => $payload,
                    'response' => $response,
                    'message' => 'Checkout requestPayment'
                ]
            );
            if (isset($response->_links->redirect->href)) {
                return [
                    'success' => true,
                    'url' => $response->_links->redirect->href,
                    'message' => "Checkout requestPayment Successfully Executed!",
                    'errors' => ''
                ];
            }

            return [
                'success' => false,
                'url' => null,
                'message' => "Something went wrong in checkout payment gateway",
                'errors' => ''
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'url' => null,
                'message' => 'Checkout Error ' . $e->getMessage(),
                'errors' => $e->getMessage()
            ];
        }
    }

    /**
     * @param $url
     * @param $method
     * @param $payload
     * @return mixed
     */
    public function sendRequest($url, $method, $payload = null)
    {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getSecretApiKey()
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        $response = curl_exec($curl);
        $http_header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = substr($response, $http_header_size);
        return json_decode(strval($body));
    }
}