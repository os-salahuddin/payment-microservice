<?php

namespace app\components;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\helpers\Json;
use app\models\Gateway;
use app\models\CardSeries;
use app\models\Transaction;
use yii\helpers\Url;

class TazaPay
{
    private $paymentGateway;
    private $apiKey;
    private $secret;
    private $approvalUrl;
    private $failedUrl;
    private $webhookUrl;

    public function __construct($gatewayCode)
    {
        $this->paymentGateway = Gateway::find()->where(['code' => $gatewayCode, 'status' => Gateway::STATUS_ACTIVE])->one();
        $this->setConfig();
    }

    public function setConfig()
    {
        $extraParams = (object)Json::decode($this->paymentGateway->extraParams);

        if ($this->paymentGateway->gatewayMode === Gateway::GATEWAY_MODE_TEST_INT) {
            $this->setApprovalUrl(Url::to(['callback/tazapay-callback'], getenv('PROTOCOL')));
            $this->setFailedUrl(Url::to(['callback/tazapay-cancel'], getenv('PROTOCOL')));
            $this->setGatewayUrl($this->paymentGateway->sandboxUrl);
            $this->setApiKey($extraParams->merchantSandbox);
            $this->setSecret($extraParams->merchantPasswordSandbox);
            $this->setWebhookUrl(Url::to(['callback/tazapay-webhook'], getenv('PROTOCOL')));
        } else {
            $this->setApprovalUrl(Url::to(['callback/tazapay-callback'], getenv('PROTOCOL')));
            $this->setFailedUrl(Url::to(['callback/tazapay-cancel'], getenv('PROTOCOL')));
            $this->setGatewayUrl($this->paymentGateway->liveUrl);
            $this->setApiKey($this->paymentGateway->merchant);
            $this->setSecret($this->paymentGateway->merchantPassword);
            $this->setWebhookUrl(Url::to(['callback/tazapay-webhook'], getenv('PROTOCOL')));
        }
    }

    public function setApiKey($apiKey): string
    {
        return $this->apiKey = $apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setSecret($secret): string
    {
        return $this->secret = $secret;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getApprovalUrl(): string
    {
        return $this->approvalUrl;
    }

    public function setApprovalUrl($approvalUrl): string
    {
        return $this->approvalUrl = $approvalUrl;
    }

    public function getFailedUrl(): string
    {
        return $this->failedUrl;
    }

    public function setFailedUrl($failedUrl): string
    {
        return $this->failedUrl = $failedUrl;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl($webhookUrl): string
    {
        return $this->webhookUrl = $webhookUrl;
    }

    public function getGatewayUrl()
    {
        return $this->gatewayUrl;
    }

    public function setGatewayUrl($gatewayUrl)
    {
        $this->gatewayUrl = $gatewayUrl;
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
                'callbackUrl' => $this->getFailedUrl(),
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
                $checkout = $this->createCheckoutSession($orderId, $amount, $this->paymentGateway->relatedCurrency->code, $description, $customerName);
                $paymentUrl = $checkout['data']['data']['url'];
                if (isset($paymentUrl)) {
                    $insertData = [
                        'bankRequestUrl' => $paymentUrl,
                        'bankResponse' => json_encode($checkout['data']),
                        'bankMerchantTranId' => $checkout['data']['data']['id'],
                        'status' => Transaction::STATUS_CREATED
                    ];
                    $response = ['success' => true, 'url' => $paymentUrl, 'orderId' => $orderId];
                } else {
                    $insertData = [
                        'bankResponse' => json_encode($checkout['data']),
                        'bankOrderStatus' => 'ORDER CREATION FAILED',
                    ];
                }

                Transaction::updateByKeyValue(['orderId' => $orderId], $insertData, true);
                $response = ['success' => true, 'url' => $paymentUrl, 'orderId' => $orderId];

                $transactionLogData = [
                    'orderId' => $orderId,
                    'bookingId' => $bookingId,
                    'client' => $processedChargeClient['name'],
                    'serviceType' => $serviceType,
                    'message' => 'Tazapay CreateOrder',
                    'request' => $insert,
                    'response' => $checkout
                ];

                TransactionLogDetailsUtil::createLog($transactionLogData);
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'message' => 'Tazapay CreateOrder Exception',
                    'response' => $e->getMessage()
                ]);
        }

        return $response;
    }

    /**
     * @param $orderId
     * @param $amount
     * @param $currency
     * @param $description
     * @param $customerName
     * @return array
     */
    public function createCheckoutSession($orderId, $amount, $currency, $description, $customerName): array
    {
        try {
            $url = $this->getGatewayUrl() . '/v3/checkout';
            $method = 'POST';
            if (filter_var($customerName, FILTER_VALIDATE_EMAIL)) {
                $email = $customerName;
            } else {
                $email = 'account@sharetrip.net';
            }

            $postData = [
                'payment_methods'=> ['card'],
                'reference_id' => $orderId,
                'customer_details' => [
                    'email' => $email,
                    'country' => 'BD',
                    'name' => 'ShareTrip Customer',
                ],
                'success_url' => $this->getApprovalUrl() . '?order_id=' . $orderId,
                'webhook_url' => $this->getWebhookUrl(),
                'cancel_url' => $this->getFailedUrl() . '?order_id=' . $orderId,
                'transaction_description' => (string)$description,
                'amount' => $amount * 100,
                'invoice_currency' => $currency,
                'expire_at' => $this->getExpiredAt()
            ];
            $response = $this->sendRequest($orderId, $method, $url, $postData);

            TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Tazapay Checkout Session request',
                    'request' => $postData,
                    'response' => $response
                ]
            );

            return [
                "success" => true,
                "message" => "Tazapay createCheckoutSession() Successfully Executed!",
                "data" => $response,
                "errors" => ''
            ];
        } catch (RequestException $e) {
            TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Tazapay sendRequest() Exception',
                    'response' => $e->getMessage()
                ]
            );
            return [
                "success" => false,
                "message" => "Something Went Wrong In Tazapay sendRequest()",
                "data" => [],
                "errors" => $e->getMessage()
            ];
        }
    }

    public function getExpiredAt(): string
    {
        $currentDateTime = new \DateTime();

        /*
         *  Add 37 minutes for expire at
         */
        $currentDateTime->add(new \DateInterval('PT37M'));

        return $currentDateTime->format('Y-m-d\TH:i:s.u\Z');
    }

    /**
     * @param $txn_no
     * @param $orderId
     * @return array
     */
    public function getOrderDetails($txn_no, $orderId): array
    {
        try {
            $url = $this->getGatewayUrl() . '/v3/checkout/' . $txn_no;
            $method = 'GET';
            $response = $this->sendRequest($orderId, $method, $url);

            TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Tazapay getOrderDetails request',
                    'request' => '',
                    'response' => $response
                ]
            );

            return $response;
        } catch (RequestException $e) {
            TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Tazapay getOrderDetails() Exception',
                    'response' => $e->getMessage()
                ]
            );
            return [
                "success" => false,
                "message" => "Something Went Wrong In Tazapay getOrderDetails()",
                "data" => [],
                "errors" => $e->getMessage()
            ];
        }
    }

    /**
     * @param $orderId
     * @param $payload
     * @return array
     */
    public function refundTransaction($orderId, $payload): array
    {
        try {
            $url = $this->getGatewayUrl() . '/v3/refund/';
            $method = 'POST';

            return $this->sendRequest($orderId, $method, $url, $payload);
        } catch (RequestException $e) {
            TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Tazapay refundTransaction Exception',
                    'response' => $e->getMessage()
                ]
            );
            return [
                "success" => false,
                "message" => "Something Went Wrong In Tazapay refundTransaction",
                "data" => [],
                "errors" => $e->getMessage()
            ];
        }
    }

    /**
     * @param $orderId
     * @param $method
     * @param $url
     * @param $postData
     * @return mixed|void
     */
    public function sendRequest($orderId, $method, $url, $postData = null)
    {
        $client = new Client();
        try {
            $apiKey = $this->getApiKey();
            $apiSecret = $this->getSecret();
            $credentials = base64_encode("$apiKey:$apiSecret");

            $requestOptions = [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'accesskey' => $apiKey,
                    'x-session-token' => $apiSecret,
                    'authorization' => 'Basic ' . $credentials,
                ],
            ];
            if(!empty($postData)) {
                $requestOptions = array_merge($requestOptions, ['json' => $postData]);
            }

            $response = $client->request($method, $url, $requestOptions);
            $body = $response->getBody()->getContents();

            return json_decode($body, true);
        } catch (RequestException $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'message' => 'Tazapay sendRequest() Exception',
                    'response' => $e->getMessage()
                ]
            );
            return [
                "success" => false,
                "message" => "Something Went Wrong In Tazapay sendRequest()",
                "data" => [],
                "errors" => $e->getMessage()
            ];
        }
    }
}