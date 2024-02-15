<?php

namespace app\components;

use Exception;
use yii\helpers\Json;
use yii\helpers\Url;
use app\models\Gateway;
use app\models\CardSeries;
use app\models\Transaction;

class CyberSource
{
    private $paymentGateway;
    private $profileId;
    private $accessKey;
    private $paymentUrl;
    private $approvalUrl;
    private $failedUrl;
    private $secret;
    private $gatewayCode;
    private $apiUrl;
    private $organizationId;
    private $accessKeyApi;
    private $secretApi;
    private $host;

    const cardBrand = [
        '001' => 'Visa',
        '002' => 'Mastercard',
        '003' => 'American Express',
        '004' => 'Discover',
        '005' => 'Diners Club',
        '006' => 'Carte Blanche',
        '007' => 'JCB',
        '014' => 'Enroute',
        '021' => 'JAL',
        '031' => 'Delta',
        '033' => 'Visa Electron',
        '034' => 'Dankort',
        '036' => 'Cartes Bancaires',
        '037' => 'Carta Si',
        '039' => 'Encoded account number',
        '040' => 'UATP',
        '042' => 'Maestro',
        '050' => 'Hipercard',
        '051' => 'Aura',
        '054' => 'Elo',
        '062' => 'China UnionPay',
        '070' => 'EFTPOS',
    ];

    // Autoload Function
    public function __construct($gatewayCode)
    {
        $this->paymentGateway = Gateway::find()->where(['code' => $gatewayCode, 'status' => Gateway::STATUS_ACTIVE])->one();
        $this->gatewayCode = $gatewayCode;

        $this->setConfig();
    }

    public function setConfig()
    {
        $extraParams = (object)Json::decode($this->paymentGateway->extraParams);

        if ($this->paymentGateway->gatewayMode === Gateway::GATEWAY_MODE_TEST_INT) {
            $this->setApprovalUrl($extraParams->approvalUrl);
            $this->setApiUrl($extraParams->apiUrlSandbox);
            $this->setFailedUrl($extraParams->failedUrl);
            $this->setPaymentUrl($this->paymentGateway->sandboxUrl);
            $this->setProfileId($extraParams->merchantSandbox);
            $this->setAccessKey($extraParams->merchantPasswordSandbox);
            $this->setSecret($extraParams->secretSandbox);
            $this->setOrganizationId($extraParams->organizationIdSandbox);
            $this->setAccessKeyApi($extraParams->merchantApiSandbox);
            $this->setSecretApi($extraParams->secretApiSandbox);
            $this->setHost($extraParams->hostSandbox);
        } else {
            $this->setApprovalUrl($extraParams->approvalUrl);
            $this->setApiUrl($extraParams->apiUrl);
            $this->setFailedUrl($extraParams->failedUrl);
            $this->setPaymentUrl($this->paymentGateway->liveUrl);
            $this->setProfileId($this->paymentGateway->merchant);
            $this->setAccessKey($this->paymentGateway->merchantPassword);
            $this->setSecret($extraParams->secret);
            $this->setOrganizationId($extraParams->organizationId);
            $this->setAccessKeyApi($extraParams->merchantApi);
            $this->setSecretApi($extraParams->secretApi);
            $this->setHost($extraParams->host);
        }
    }

    public function setAccessKeyApi($accessKeyApi): string
    {
        return $this->accessKeyApi = $accessKeyApi;
    }

    public function getAccessKeyApi(): string
    {
        return $this->accessKeyApi;
    }

    public function setSecretApi($secretApi): string
    {
        return $this->secretApi = $secretApi;
    }

    public function getSecretApi(): string
    {
        return $this->secretApi;
    }

    public function getApprovalUrl(): string
    {
        return $this->approvalUrl;
    }

    public function setApprovalUrl($approvalUrl): string
    {
        return $this->approvalUrl = $approvalUrl;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function setApiUrl($apiUrl): string
    {
        return $this->apiUrl = $apiUrl;
    }

    public function getFailedUrl(): string
    {
        return $this->failedUrl;
    }

    public function setFailedUrl($failedUrl): string
    {
        return $this->failedUrl = $failedUrl;
    }

    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl($paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;
    }

    public function getProfileId()
    {
        return $this->profileId;
    }

    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    }

    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setOrganizationId($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    public function getAccessKey()
    {
        return $this->accessKey;
    }

    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param $amount
     * @param $orderId
     * @param $successUrl
     * @param $cancelUrl
     * @param $declineUrl
     * @return array
     */
    public function CreateOrder($amount, $orderId, $successUrl, $cancelUrl, $declineUrl): array
    {
        $response = ['success' => false, 'url' => null, 'orderId' => null];

        try {
            $transaction = [
                'uid' => 1,
                'orderId' => $orderId,
                'amount' => $amount,
                'bankResponse' => json_encode([]),
                'status' => Transaction::STATUS_CANCELLED,
                'gateway' => 'CYBERSOURCE',
            ];

            if (Transaction::add($transaction)) {
                $update = [
                    'bankOrderStatus' => 'OrderCreationSuccess',
                    'status' => Transaction::STATUS_CREATED
                ];
                Transaction::updateByKeyValue(['orderId' => $orderId], $update, true);
                $response = ['success' => true, 'url' => Url::to(['/cybersource?orderId=' . $orderId], getenv('PROTOCOL')), 'orderId' => $orderId];

                $transactionLogData = [
                    'orderId' => $orderId,
                    'message' => 'Cybersource CreateOrder',
                    'request' => $transaction,
                    'response' => $response
                ];

                TransactionLogDetailsUtil::createLog($transactionLogData);
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $orderId,
                                'message' => 'Cybersource CreateOrder Exception',
                                'response' => $e->getMessage()
                            ]);
        }

        return $response;
    }

    public static function sign($requests, $secret): string
    {
        return self::signData(self::buildDataToSign($requests), $secret);
    }

    public static function signData($data, $secretKey): string
    {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    public static function buildDataToSign($requests): string
    {
        $signedFieldNames = explode(",", $requests["signed_field_names"]);
        $dataToSign = [];
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $requests[$field];
        }
        return self::commaSeparate($dataToSign);
    }

    public static function commaSeparate($dataToSign): string
    {
        return implode(",", $dataToSign);
    }

    public function GenerateDigest($requestPayload)
    {
        $utf8EncodedString = utf8_encode($requestPayload);
        $digestEncode = hash("sha256", $utf8EncodedString, true);
        return base64_encode($digestEncode);
    }

    public function getHttpSignature($resourcePath, $httpMethod, $currentDate, $payload): array
    {
        $request_host = $this->getHost();
        $merchant_id = $this->getOrganizationId();
        $merchant_secret_key = $this->getSecretApi();
        $merchant_key_id = $this->getAccessKeyApi();

        $headerString = "host date request-target v-c-merchant-id";
        $signatureString = "host: " . $request_host . "\ndate: " . $currentDate . "\nrequest-target: " . $httpMethod . " " . $resourcePath . "\nv-c-merchant-id: " . $merchant_id;
        if ($httpMethod == "post") {
            $digest = $this->GenerateDigest($payload);
            $headerString .= " digest";
            $signatureString .= "\ndigest: SHA-256=" . $digest;
        }

        $signatureByteString = utf8_encode($signatureString);
        $decodeKey = base64_decode($merchant_secret_key);
        $signature = base64_encode(hash_hmac("sha256", $signatureByteString, $decodeKey, true));
        $signatureHeader = array(
            'keyid="' . $merchant_key_id . '"',
            'algorithm="HmacSHA256"',
            'headers="' . $headerString . '"',
            'signature="' . $signature . '"'
        );

        $signatureToken = "Signature:" . implode(", ", $signatureHeader);

        $host = "Host:" . $request_host;
        $vcMerchant = "v-c-merchant-id:" . $merchant_id;
        $headers = array(
            $vcMerchant,
            $signatureToken,
            $host,
            'Date:' . $currentDate
        );

        if ($httpMethod == "post") {
            $digestArray = array("Digest: SHA-256=" . $digest);
            $headers = array_merge($headers, $digestArray);
        }

        return $headers;
    }

    public function getTransactionDetails($transactionUrl)
    {
        try {
            $queryParts = explode("/", $transactionUrl);
            $resource = "/tss/v2/transactions/" . end($queryParts);
            $method = "get";
            $url = $transactionUrl;
            $resource = utf8_encode($resource);
            $date = date("D, d M Y G:i:s ") . "GMT";

            return $this->sendRequest($url, $method, $resource, $date);
        } catch (Exception $e) {
            
        }
    }

    public function createSearchRequest($payload)
    {
        try {
            $request_host = $this->getHost();
            $resource = "/tss/v2/searches";
            $method = "post";
            $url = "https://" . $request_host . $resource;
            $resource = utf8_encode($resource);
            $date = date("D, d M Y G:i:s ") . "GMT";

            return $this->sendRequest($url, $method, $resource, $date, $payload);
        } catch (Exception $e) {
        
        }
    }

    public function getSearchRequest($searchId)
    {
        try {
            $request_host = $this->getHost();
            $resource = "/tss/v2/searches/" . $searchId;
            $method = "get";
            $url = "https://" . $request_host . $resource;
            $resource = utf8_encode($resource);
            $date = date("D, d M Y G:i:s ") . "GMT";

            return $this->sendRequest($url, $method, $resource, $date);
        } catch (Exception $e) {
            
        }
    }

    public function refundTransaction($paymentId, $payload)
    {
        try {
            $request_host = $this->getHost();
            $resource = "/pts/v2/payments/" . $paymentId . "/refunds";
            $method = "post";
            $url = "https://" . $request_host . $resource;
            $resource = utf8_encode($resource);
            $date = date("D, d M Y G:i:s ") . "GMT";

            return $this->sendRequest($url, $method, $resource, $date, $payload);
        } catch (Exception $e) {
            TransactionLog::createLog('Cybersource refundRequest exception', '', $e->getMessage(), 0);
        }
    }

    public function retrieveRefundDetails($resource)
    {
        try {
            $request_host = $this->getHost();
            $method = "get";
            $url = "https://" . $request_host . $resource;
            $resource = utf8_encode($resource);
            $date = date("D, d M Y G:i:s ") . "GMT";

            return $this->sendRequest($url, $method, $resource, $date);
        } catch (Exception $e) {
            TransactionLog::createLog('Cybersource refundRequest exception', '', $e->getMessage(), 0);
        }
    }

    public function voidTransaction($paymentId, $payload)
    {
        try {
            $request_host = $this->getHost();
            $resource = "/pts/v2/payments/" . $paymentId . "/voids";
            $method = "post";
            $url = "https://" . $request_host . $resource;
            $resource = utf8_encode($resource);
            $date = date("D, d M Y G:i:s ") . "GMT";

            return $this->sendRequest($url, $method, $resource, $date, $payload);
        } catch (Exception $e) {
            TransactionLog::createLog('Cybersource voidRequest exception', '', $e->getMessage(), 0);
        }
    }

    public function sendRequest($url, $method, $resource, $date, $payload = null)
    {
        $headerParams = [];
        $headers = [];
        $headerParams['Accept'] = '*/*';
        $headerParams['Content-Type'] = 'application/json;charset=utf-8';
        foreach ($headerParams as $key => $val) {
            $headers[] = "$key: $val";
        }
        $authHeaders = $this->getHttpSignature($resource, $method, $date, $payload);
        $headerParams = array_merge($headers, $authHeaders);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerParams);
        curl_setopt($curl, CURLOPT_CAINFO, \Yii::getAlias('@app/web/uploads/cybersource-resource') . '/cacert.pem');
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0");
        $response = curl_exec($curl);
        $http_header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $http_body = substr($response, $http_header_size);
        return json_decode(strval($http_body));
    }
}