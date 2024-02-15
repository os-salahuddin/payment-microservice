<?php

namespace app\models;

use app\components\BKash;
use app\components\BracBank\iPayPipe;
use app\components\BracBank\payUtils;
use app\components\Checkout;
use app\components\CityBank;
use app\components\CyberSource;
use app\components\DeshiPay;
use app\components\EBLSkyPay;
use app\components\EBLTokenization;
use app\components\EkPay;
use app\components\EmailInvoice;
use app\components\LogBehavior;
use app\components\NagadPay;
use app\components\NexusPay;
use app\components\OkWallet;
use app\components\Pocket;
use app\components\SoutheastBank;
use app\components\SslcommerzPay;
use app\components\TAPay;
use app\components\TazaPay;
use app\components\TransactionLogDetailsUtil;
use app\components\UcbBank;
use app\components\UPay;
use app\components\Utils;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "transaction".
 *
 * @property int $id
 * @property string $uid
 * @property double $amount
 * @property string $bankResponse
 * @property string $rrn
 * @property string $bankMerchantTranId
 * @property string $orderId
 * @property string $customerId
 * @property int $status
 * @property int $createdBy
 * @property int $updatedBy
 * @property string $createdAt
 * @property string $updatedAt
 */
class Transaction extends ActiveRecord
{
    const MAX_QUEUE_TIME = 2400;
    const STATUS_CANCELLED = 0;
    const STATUS_CREATED = 1;
    const STATUS_PAID = 2;
    const STATUS_TIMEOUT = 3;
    const STATUS_DECLINED = 4;
    const STATUS_REFUND = 5;
    const STATUS_VOID = 6;
    const NOTIFICATION_NOT_SEND = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['uid', 'amount', 'description', 'bookingId', 'bankCode', 'status', 'currency', 'gateway', 'serviceType', 'charge'], 'required', 'on' => 'insert'],
            [['amount', 'emiInterestAmount', 'emiInterestRate', 'emiFee'], 'number'],
            [['binRestriction', 'binLength', 'tripCoinMultiply'], 'safe'],
            [['description', 'bankResponse', 'clientId', 'cardSeries', 'paymentReferenceId', 'customerId', 'customerName', 'name', 'email', 'phone', 'bookingCode', 'extraParams'], 'string'],

            [['cardLength', 'onEmi', 'emiRepaymentPeriod'], 'integer'],
            [['status', 'notify', 'binLength', 'tripCoinMultiply', 'binRestriction', 'isInvoiceEmailSend'], 'integer'],

            [['createdAt', 'updatedAt'], 'integer'],
            [['uid'], 'string', 'max' => 36],
            [['currency', 'gateway', 'cardPrefix'], 'string', 'max' => 36],
            [['orderId', 'sessionId', 'pan', 'cardHolderName', 'cardBrand', 'bankApprovalCode', 'bankApprovalCodeScr'], 'string', 'max' => 100],
            [['type', 'bankResponseCode'], 'string', 'max' => 20],
            [['rrn', 'bankOrderStatus', 'acqFee', 'bankOrderStatusScr', 'bankThreeDsvVerification', 'bankThreeDssStatus', 'serviceType'], 'string', 'max' => 50],
            [['bankTransactionDate'], 'string', 'max' => 100],
            [['bankMerchantTranId'], 'string', 'max' => 512],
            [['bankResponseDescription'], 'string', 'max' => 256],
            [['uid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'uid' => 'Code',
            'clientId' => 'Client',
            'amount' => 'Amount',
            'description' => 'Description',
            'bookingId' => 'BookingID',
            'orderId' => 'Order ID',
            'bankCode' => "Bank Code",
            'customerId' => "CID",
            'customerName' => "Customer",
            'name' => "Name",
            'email' => "Email",
            'phone' => "Phone",
            'bookingCode' => "Booking Code",
            'sessionId' => 'Session',
            'bankRequestUrl' => 'Bank Request Url',
            'bankResponse' => 'Bank Response',
            'type' => 'Type',
            'charge' => 'Charge',
            'rrn' => 'RRN',
            'pan' => 'PAN',
            'bankTransactionDate' => 'Bank Transaction Date',
            'bankResponseCode' => 'Bank Response Code',
            'bankResponseDescription' => 'Bank Response Description',
            'cardHolderName' => 'Card Holder Name',
            'cardBrand' => 'Card Brand',
            'bankOrderStatus' => 'Bank Order Status',
            'bankApprovalCode' => 'Bank Approval Code',
            'bankApprovalCodeScr' => 'Bank Approval Code Scr',
            'acqFee' => 'Acq Fee',
            'bankMerchantTranId' => 'Bank Merchant Tran ID',
            'bankOrderStatusScr' => 'Bank Order Status Scr',
            'bankThreeDsvVerification' => 'Bank Three Dsv Verification',
            'bankThreeDssStatus' => 'Bank Three Dss Status',
            'sessionVersion' => 'Session Version',
            'resultIndicator' => 'Result Indicator',
            'status' => 'Status',
            'notify' => 'Notify',
            'callbackUrl' => 'Callback Url',
            'currency' => 'Currency',
            'gateway' => 'Gateway',
            'cardSeries' => 'BIN',
            'cardPrefix' => 'Card Prefix',
            'cardLength' => 'Card length',
            'serviceType' => 'Service',
            'binRestriction' => 'Bin Restriction',
            'binLength' => 'Bin Length',
            'tripCoinMultiply' => 'Trip Coin Multiply',
            'emiInterestAmount' => 'Emi Interest Amount',
            'emiInterestRate' => 'Emi Interest Rate',
            'onEmi' => 'On Emi',
            'isInvoiceEmailSend' => 'Is Invoice Email Send',
            'emiRepaymentPeriod' => 'Emi Repayment Period',
            'createdAt' => 'Created',
            'updatedAt' => 'Updated At'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedClient(): ActiveQuery
    {
        return $this->hasOne(Client::class, ['uid' => 'clientId']);
    }

    public function getRelatedClientServiceBanks(): ActiveQuery
    {
        return $this->hasOne(ClientServiceBanks::class, ['bank' => 'bankCode']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedCurrency(): ActiveQuery
    {
        return $this->hasOne(Currency::class, ['uid' => 'currency']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedGateway(): ActiveQuery
    {
        return $this->hasOne(Gateway::class, ['uid' => 'gateway']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedBank(): ActiveQuery
    {
        return $this->hasOne(Bank::class, ['uid' => 'bankCode']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedRefund(): ActiveQuery
    {
        return $this->hasOne(RefundTransaction::class, ['transactionId' => 'uid']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedCardSeries(): ActiveQuery
    {
        return $this->hasOne(CardSeries::class, ['uid' => 'cardSeries']);
    }

    /**
     * @param $key
     * @param $val
     * @param $select
     * @return array|ActiveRecord|null
     */
    public static function getByKey($key, $val, $select = false)
    {
        $query = self::find();
        if (is_array($select)) {
            $options = $query->select($select);
        }
        $options = $query->where([$key => $val])->one();

        if ($options) {
            return $options;
        } else {
            return null;
        }
    }

    /**
     * @param $bookingId
     * @param $service
     * @return array
     */
    public static function getOrderDetails($bookingId, $service, $client): array
    {
        $condition = [
            'bookingId' => $bookingId,
            'serviceType' => strtoupper($service)
        ];

        if (!in_array(strtolower($client->name), ['ops', 'common'])) {
            $condition = array_merge($condition, ['clientId' => $client->uid]);
        }

        return Transaction::find()
            ->with([
                'relatedClient',
                'relatedGateway',
                'relatedCurrency',
                'relatedRefund'
            ])
            ->where($condition)
            ->all();
    }

    /**
     * @param $transactionId
     * @return array|ActiveRecord|null
     */
    public static function getPaymentDetails($transactionId)
    {
        return Transaction::find()
            ->select(['uid', 'orderId', 'bookingId', 'serviceType', 'amount', 'description', 'gateway', 'charge', 'bankCode', 'cardSeries', 'cardBrand', 'cardPrefix', 'cardLength', 'cardHolderName', 'name', 'phone', 'email', 'bookingCode', 'pan', 'bankOrderStatus', 'bankApprovalCode', 'rrn', 'onEmi', 'emiInterestAmount', 'emiInterestRate', 'emiFee', 'emiRepaymentPeriod', 'status', 'createdAt', 'updatedAt', 'cardPrefix', 'currency'])
            ->with([
                'relatedGateway',
                'relatedCurrency' => function($query) {
                    $query->select(['code']);
                },
                'relatedRefund' => function($query) {
                    $query->select(['updatedAt', 'charge']);
                }
            ])
            ->where(['uid' => $transactionId])
            ->one();
    }

    /**
     * @param $orderId
     * @return array|ActiveRecord|null
     */
    public static function getOrderDetailsWithOrder($orderId)
    {
        return Transaction::find()->select(['uid', 'bookingId', 'createdAt', 'description', 'bankApprovalCode', 'charge', 'orderId', 'bankCode', 'serviceType', 'clientId', 'gateway', 'amount', 'cardSeries',
        'binRestriction','binLength','tripCoinMultiply','cardPrefix','cardLength','cardHolderName','cardBrand', 'name', 'phone', 'email', 'bookingCode', 'bankOrderStatus', 'pan', 'status', 'currency', 'notify', 'bankMerchantTranId', 'extraParams', 'rrn', 'onEmi', 'emiInterestAmount', 'emiInterestRate', 'emiFee','emiRepaymentPeriod', 'isInvoiceEmailSend'])
            ->with([
                'relatedClient',
                'relatedGateway',
                'relatedGateway.conversion',
                'relatedCurrency',
                'relatedRefund',
                'relatedBank'
            ])
            ->where(['orderId' => $orderId])
            ->asArray()
            ->one();
    }

    /**
     * @return array
     */
    public static function getUnSendOrderDetailsWithOrder(): array
    {
        return Transaction::find()->select(['uid', 'bookingId', 'createdAt', 'description', 'bankApprovalCode', 'charge', 'orderId', 'bankCode', 'serviceType', 'clientId', 'gateway', 'amount', 'cardSeries',
        'binRestriction', 'binLength', 'tripCoinMultiply', 'cardPrefix', 'cardLength', 'cardHolderName', 'name', 'phone', 'email', 'bookingCode', 'cardBrand', 'bankOrderStatus', 'pan', 'status', 'currency', 'notify', 'bankMerchantTranId', 'extraParams', 'rrn', 'onEmi', 'emiInterestAmount', 'emiInterestRate', 'emiFee', 'emiRepaymentPeriod'])
            ->with([
                'relatedClient',
                'relatedGateway',
                'relatedGateway.conversion',
                'relatedCurrency',
                'relatedRefund',
                'relatedBank'
            ])
            ->where(['notify' => 0])
            ->asArray()
            ->all();
    }

    /**
     * @param $data
     * @return bool
     */
    public static function add($data): bool
    {
        $model = new Transaction();
        $model->setScenario('insert');
        $model->setAttributes($data);
        if ($model->save()) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $data
     */
    public static function updateByKeyValue($key, $data, $isLog = false)
    {
        try {
            $model = Transaction::findOne($key);
            $model->setAttributes($data);
            if (!$model->save()) {
                $content = $model->getErrors();
                $message = 'Transaction Update Save Error';
            } else {
                $content = $data;
                $message = 'Transaction Update';
            }

            if ($isLog && array_key_exists('orderId', $key)) {
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $key['orderId'],
                        'message' => $message,
                        'response' => $content
                    ]
                );
            }
        } catch (Exception $e) {
        }
    }

    /**
     * @param $transaction
     * @return array
     */
    public static function processCybersourceData($transaction, $gatewayCode): array
    {
        try {
            $payload = json_encode([
                'query' => 'clientReferenceInformation.code:' . $transaction->orderId . ' AND applicationInformation.applications.rFlag:SOK',
                'sort' => 'id:asc',
            ]);
            $cybersource = new CyberSource($gatewayCode);
            $searchRequest = $cybersource->createSearchRequest($payload);

            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'request' => [
                        'orderId' => $transaction->orderId
                    ],
                    'response' => $searchRequest,
                    'message' => 'Watcher processCybersouceData'
                ]
            );

            if (isset($searchRequest->count) &&
                (int)$searchRequest->count > 0
            ) {
                $orderInfos = $searchRequest;
                if (isset($orderInfos->_embedded->transactionSummaries) && count($orderInfos->_embedded->transactionSummaries)) {
                    foreach ($orderInfos->_embedded->transactionSummaries as $transactionSummary) {
                            if ((isset($transactionSummary->processorInformation->eventStatus)
                                    && (in_array($transactionSummary->processorInformation->eventStatus, ['Transmitted', 'Pending']))
                                ) &&
                                isset($transactionSummary->processorInformation->approvalCode) &&
                                isset($transactionSummary->processorInformation->retrievalReferenceNumber)
                            ) {
                                $transactionDetails = $cybersource->getTransactionDetails($transactionSummary->_links->transactionDetail->href);

                                if(isset($transactionDetails->processorInformation->approvalCode)) {
                                    $updateData = [
                                        'bankTransactionDate' => $transactionDetails->submitTimeUTC,
                                        'bankOrderStatus' => 'ACCEPT',
                                        'bankResponse' => json_encode($transactionDetails),
                                        'status' => self::STATUS_PAID,
                                        'bankApprovalCode' => $transactionDetails->processorInformation->approvalCode,
                                        'pan' => $transactionDetails->paymentInformation->card->prefix . 'xxxxxx' . $transactionDetails->paymentInformation->card->suffix,
                                        'cardBrand' => CyberSource::cardBrand[$transactionDetails->paymentInformation->card->type],
                                        'rrn' => $transactionDetails->processorInformation->retrievalReferenceNumber ?? null,
                                        'bankMerchantTranId' => $transactionDetails->transaction_id
                                    ];

                                    return ['success' => true, 'data' => $updateData];
                                }
                            }
                        }
                    }
            }

            $updateData['status'] = self::STATUS_TIMEOUT;
            return ['success' => false, 'data' => $updateData];
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'message' => 'Watcher processCybersouceData Exception',
                    'response' => $e->getMessage()
                ]
            );
            $updateData['status'] = self::STATUS_TIMEOUT;
            return ['success' => false, 'data' => $updateData];
        }
    }

    /**
     * @param $transaction
     * @return array
     */
    public static function processCheckoutData($transaction, $gatewayCode): array
    {
        try {
            $checkout = new Checkout($gatewayCode);
            $url = $checkout->getApiUrl() . '/payments?reference=' . $transaction->orderId;
            $paymentListResponse = $checkout->sendRequest($url, 'get');

            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'request' => [
                        'orderId' => $transaction->orderId
                    ],
                    'response' => $paymentListResponse,
                    'message' => 'Watcher processCheckoutData'
                ]
            );

            if (isset($paymentListResponse->total_count) &&
                (int)$paymentListResponse->total_count > 0
            ) {
                if (isset($paymentListResponse->data) && count($paymentListResponse->data)) {
                    foreach ($paymentListResponse->data as $item) {
                        if (isset($item->status)
                            && $item->status == 'Captured'
                        ) {
                            $paymentDetails = $checkout->sendRequest($item->_links->self->href, 'get');
                            if (isset($paymentDetails->status)
                                && $paymentDetails->status == 'Captured'
                                && isset($paymentDetails->approved)
                                && $paymentDetails->approved
                            ) {
                                $updateData = [
                                    'bankTransactionDate' => $paymentDetails->requested_on,
                                    'bankOrderStatus' => $paymentDetails->status,
                                    'bankResponse' => json_encode($paymentDetails),
                                    'status' => self::STATUS_PAID,
                                    'pan' => $paymentDetails->source->bin . 'xxxxxx' . $paymentDetails->source->last4,
                                    'cardBrand' => $paymentDetails->source->scheme,
                                    'cardHolderName' => $paymentDetails->source->name
                                ];

                                if (isset($paymentDetails->processing->partner_authorization_code)) {
                                    $updateData = array_merge($updateData, ['bankApprovalCode' => $paymentDetails->processing->partner_authorization_code]);
                                }

                                if (isset($paymentDetails->processing->retrieval_reference_number)) {
                                    $updateData = array_merge($updateData, ['rrn' => $paymentDetails->processing->retrieval_reference_number]);
                                }

                                if (isset($paymentDetails->processing->partner_authorization_response_code)) {
                                    $updateData = array_merge($updateData, ['bankResponseCode' => $paymentDetails->processing->partner_authorization_response_code]);
                                }

                                return ['success' => true, 'data' => $updateData];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'message' => 'Watcher processCheckoutData Exception',
                    'response' => $e->getMessage()
                ]
            );
            $updateData['status'] = self::STATUS_TIMEOUT;
            return ['success' => false, 'data' => $updateData];
        }

        $updateData['status'] = self::STATUS_TIMEOUT;
        return ['success' => false, 'data' => $updateData];
    }

    /**
     * @param $transaction
     * @return array
     */
    public static function processTazapayData($transaction, $gatewayCode): array
    {
        try {
            $tazapay = new TazaPay($gatewayCode);
            $response = $tazapay->getOrderDetails($transaction->bankMerchantTranId, $transaction->orderId);

            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'request' => [
                        'orderId' => $transaction->orderId
                    ],
                    'response' => $response,
                    'message' => 'Watcher processTazapayData'
                ]
            );

            if(isset($response['data']['payment_status']) && $response['data']['payment_status'] == 'paid') {
                foreach($response['data']['payment_attempts'] as $paymentAttempts) {
                    if($paymentAttempts['status'] == 'succeeded') {
                        $paymentMethodDetails = $paymentAttempts['payment_method_details'];
                        $updateData = [
                            'bankOrderStatus' => $response['data']['payment_status'],
                            'bankResponse' => json_encode($response),
                            'status' => self::STATUS_PAID,
                            'pan' => $paymentMethodDetails['card']['first6'] . 'xxxxxx' .  $paymentMethodDetails['card']['last4'],
                            'rrn' => $paymentAttempts['payin'],
                            'cardBrand' => $paymentMethodDetails['card']['scheme'],
                            'cardHolderName' => $paymentMethodDetails['card']['cardholder_name']
                        ];

                        return ['success' => true, 'data' => $updateData];
                    }
                }
            } else {
                $updateData['status'] = self::STATUS_TIMEOUT;
                return ['success' => false, 'data' => $updateData];
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'message' => 'Watcher processTazapayData Exception',
                    'response' => $e->getMessage()
                ]
            );
            $updateData['status'] = self::STATUS_TIMEOUT;
            return ['success' => false, 'data' => $updateData];
        }

        $updateData['status'] = self::STATUS_TIMEOUT;
        return ['success' => false, 'data' => $updateData];
    }

    /**
     * @param $transaction
     * @return array
     */
    public static function processSoutheastBankData($transaction): array
    {
        try {
            $sbl = new SoutheastBank($transaction->relatedGateway->code);
            $orderInfos = $sbl->getOrder($transaction->orderId);
            //Logging Watcher
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'request' => [
                        'orderId' => $transaction->orderId
                    ],
                    'response' => $orderInfos,
                    'message' => 'Watcher processSoutheastBankData'
                ]
            );

            if (isset($orderInfos['result']) && $orderInfos['result'] == 'SUCCESS') {
                $counter = 0;
                $bankApprovalCode = null;
                $bankOrderStatus = '';
                $bankMerchantTranId = '';
                $bankResponseCode = '';
                $rrn = '';
                $type = '';
                while (isset($orderInfos['transaction[' . $counter . '].result'])) {
                    $bankApprovalCode = $orderInfos['transaction[' . $counter . '].transaction.authorizationCode'] ?? null;
                    $bankOrderStatus = $orderInfos['transaction[' . $counter . '].order.status'];
                    $bankMerchantTranId = $orderInfos['transaction[' . $counter . '].transaction.id'];
                    $rrn = (isset($orderInfos['transaction[' . $counter . '].transaction.receipt'])) ?
                        $orderInfos['transaction[' . $counter . '].transaction.receipt'] : null;
                    $type = $orderInfos['transaction[' . $counter . '].transaction.type'];
                    $bankResponseCode = (isset($orderInfos['transaction[' . $counter . '].authorizationResponse.responseCode'])) ?
                        $orderInfos['transaction[' . $counter . '].authorizationResponse.responseCode'] : null;

                    $counter++;
                }
                $updateData = [
                    'amount' => $orderInfos['amount'],
                    'refundAmount' => $orderInfos['totalRefundedAmount'],
                    'bankResponse' => json_encode($orderInfos),
                    'type' => $type,
                    'rrn' => $rrn,
                    'pan' => $orderInfos['sourceOfFunds.provided.card.number'],
                    'bankTransactionDate' => $orderInfos['creationTime'],
                    'bankResponseCode' => $bankResponseCode,
                    'cardHolderName' => $orderInfos['sourceOfFunds.provided.card.nameOnCard'] ?? '',
                    'cardBrand' => (isset($orderInfos['sourceOfFunds.provided.card.brand'])) ?
                        $orderInfos['sourceOfFunds.provided.card.brand'] : null,
                    'bankOrderStatus' => $bankOrderStatus,
                    'bankApprovalCode' => (string)$bankApprovalCode,
                    'bankMerchantTranId' => (string)$bankMerchantTranId,
                ];

                if ($orderInfos['status'] == 'CAPTURED') {
                    $updateData['bankResponseDescription'] = 'Order: ' . $orderInfos['id'] . ' Payment Successful';
                    $updateData['status'] = self::STATUS_PAID;
                } elseif ($orderInfos['status'] == 'FAILED') {
                    $updateData['bankResponseDescription'] = 'Order: ' . $orderInfos['id'] . ' Payment Declined';
                    $updateData['status'] = self::STATUS_DECLINED;
                    return ['success' => false, 'data' => $updateData];
                } else {
                    $updateData = [
                        'bankOrderStatus' => 'TIMEOUT',
                        'bankResponseDescription' => 'Order: ' . $transaction->orderId . ' Payment Timeout',
                        'status' => self::STATUS_TIMEOUT
                    ];
                    return ['success' => false, 'data' => $updateData];
                }
            } else {
                $updateData = [
                    'bankOrderStatus' => 'TIMEOUT',
                    'bankResponseDescription' => 'Order: ' . $transaction->orderId . ' Payment Timeout',
                    'status' => self::STATUS_TIMEOUT
                ];
                return ['success' => false, 'data' => $updateData];
            }
            return ['success' => true, 'data' => $updateData];
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'message' => 'Watcher processSoutheastBankData Exception',
                    'response' => $e->getMessage()
                ]
            );
            $updateData['status'] = self::STATUS_TIMEOUT;
            return ['success' => false, 'data' => $updateData];
        }
    }

    /**
     * @param $orderId
     * @return bool
     */
    public static function watcherWorker($orderId): bool
    {
        try {
            $transaction = self::getByKey('orderId', $orderId);
            if ($transaction) {
                $currentTime = time();
                if (isset($transaction->createdAt))
                    $start = $transaction->createdAt;
                if (!empty($start))
                    $timeDifference = ($currentTime - $start);

                $updateData = [];
                if (isset($transaction->status) && $transaction->status == Transaction::STATUS_CREATED) {
                    if (isset($transaction->relatedGateway->code)) {
                        $relatedGateway = $transaction->relatedGateway->code;

                        if (!empty($timeDifference)) {
                            if ($timeDifference < self::MAX_QUEUE_TIME) {
                                //Cybersource Processor
                                if ($relatedGateway == Gateway::GATEWAY_BRAC_CYBERSOURCE ||
                                    $relatedGateway == Gateway::GATEWAY_EBL_CYBERSOURCE ||
                                    $relatedGateway == Gateway::GATEWAY_EBL_CYBERSOURCE_USD
                                ) {
                                    $cybersourceResponse = self::processCybersourceData($transaction, $transaction->relatedGateway->code);
                                    if ($cybersourceResponse['success']) {
                                        $updateData = $cybersourceResponse['data'];
                                    } else {
                                        return false;
                                    }
                                }
                                //Checkout Processor
                                if ($relatedGateway == Gateway::GATEWAY_CHECKOUT) {
                                    $checkoutResponse = self::processCheckoutData($transaction, $transaction->relatedGateway->code);
                                    if ($checkoutResponse['success']) {
                                        $updateData = $checkoutResponse['data'];
                                    } else {
                                        return false;
                                    }
                                }

                                //Tazapay Processor
                                if ($relatedGateway == Gateway::GATEWAY_TAZAPAY) {
                                    $checkoutResponse = self::processTazapayData($transaction, $transaction->relatedGateway->code);
                                    if ($checkoutResponse['success']) {
                                        $updateData = $checkoutResponse['data'];
                                    } else {
                                        return false;
                                    }
                                }

                                //SoutheastBank Processor
                                if ($relatedGateway == Gateway::GATEWAY_SBL) {
                                    $southeastBankResponse = self::processSoutheastBankData($transaction);
                                    if ($southeastBankResponse['success']) {
                                        $updateData = $southeastBankResponse['data'];
                                    } else {
                                        return false;
                                    }
                                }
                            } else {
                                //Cybersource processor
                                if ($relatedGateway == Gateway::GATEWAY_BRAC_CYBERSOURCE ||
                                    $relatedGateway == Gateway::GATEWAY_EBL_CYBERSOURCE ||
                                    $relatedGateway == Gateway::GATEWAY_EBL_CYBERSOURCE_USD
                                ) {
                                    $updateData = self::processCybersourceData($transaction, $transaction->relatedGateway->code)['data'];
                                }

                                //Checkout processor
                                if ($relatedGateway == Gateway::GATEWAY_CHECKOUT) {
                                    $updateData = self::processCheckoutData($transaction, $transaction->relatedGateway->code)['data'];
                                }

                                //Tazapay Processor
                                if ($relatedGateway == Gateway::GATEWAY_TAZAPAY) {
                                    $updateData = self::processTazapayData($transaction, $transaction->relatedGateway->code)['data'];
                                }

                                //Southeast Processor
                                if ($relatedGateway == Gateway::GATEWAY_SBL) {
                                    $updateData = self::processSoutheastBankData($transaction)['data'];
                                }
                            }
                        }
                        self::updateByKeyValue(['orderId' => $transaction->orderId], $updateData, true);
                        Transaction::pushToIpnQueue($transaction);
                    }
                }

                if (isset($transaction->uid) && count($updateData)) {
                    TransactionLogDetailsUtil::createLog(
                        [
                            'orderId' => $orderId,
                            'message' => 'Watcher',
                            'response' => $updateData
                        ]
                    );
                }

                return true;
            }
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $orderId,
                    'message' => 'Watcher Exception',
                    'response' => $e->getMessage()
                ]
            );

            return false;
        }

        return false;
    }

    public static function pushToIpnQueue($transaction)
    {
        if ($transaction->notify === Transaction::NOTIFICATION_NOT_SEND) {
            Yii::$app->ipnQueue->push(new IPNQueueJob(['orderId' => $transaction->orderId]));
        }
    }

    /**IPN DATA PROCESSOR*
     * @param $orderId
     */
    public static function sendIpnRequest($orderId)
    {
        $existingData = self::getOrderDetailsWithOrder($orderId);
        self::sendToIpnApi($existingData);
    }


    public static function sendToIpnApi($existingData)
    {
        if($existingData['notify'] != Transaction::NOTIFICATION_NOT_SEND) {
            return false;
        }

        $allowedStatuses = [
            Transaction::STATUS_PAID,
            Transaction::STATUS_DECLINED,
            Transaction::STATUS_CANCELLED,
            Transaction::STATUS_TIMEOUT
        ];

        if(!in_array($existingData['status'], $allowedStatuses)) {
            return false;
        }

        if ($existingData['status'] == Transaction::STATUS_CREATED) {
            return false;
        }

        $statusMap = [
            Transaction::STATUS_CANCELLED => 'Cancelled',
            Transaction::STATUS_PAID => 'Success',
            Transaction::STATUS_DECLINED => 'Declined',
            Transaction::STATUS_TIMEOUT => 'Cancelled' // Timeout
        ];

        $status = $statusMap[$existingData['status']];

        $header = ['Accept' => 'application/json', 'Content-Type' => 'application/json',
            'privateKey' => $existingData['relatedClient']['privateKey']];

        $orderDetailsArray = [
            "transaction_id" => $existingData['uid'],
            "orderId" => $existingData['orderId'],
            "amount" => $existingData['amount'],
            "bankOrderStatus" => (isset($existingData['bankOrderStatus'])) ? $existingData['bankOrderStatus'] : '',
            "pan" => (isset($existingData['pan'])) ? $existingData['pan'] : '',
            "bank_merchant_tran_id" => (isset($existingData['bankMerchantTranId'])) ? $existingData['bankMerchantTranId'] : '',
            "status" => $status,
            "gateway" => $existingData['gateway'],
            "rrn" => (!empty($existingData['rrn'])) ? $existingData['rrn'] : '',
        ];

        $ipnMessage = 'IPN';
        $clientIpnUrl = $existingData['relatedClient']['ipn'];

        try {
            $client = new HttpClient(['verify' => false]);
            $response = $client->request('POST', $clientIpnUrl, [
                'headers' => $header,
                'body' => json_encode($orderDetailsArray)
            ]);
            $responseBody = (string)$response->getBody();
            self::updateByKeyValue(['orderId' => $existingData['orderId']], ['notify' => 1]);
            $responseData = json_decode($responseBody);
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $existingData['orderId'],
                    'message' => $ipnMessage,
                    'request' => $orderDetailsArray,
                    'response' => $responseData
                ]
            );
        } catch (Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $existingData['orderId'],
                    'message' => $ipnMessage . ' sendToIpnApi Exception',
                    'request' => $orderDetailsArray,
                    'response' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @param $number
     * @return string
     */
    public static function statusByNumber($number): string
    {
        $list = [
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_CREATED => 'Captured',
            self::STATUS_PAID => 'Paid',
            self::STATUS_TIMEOUT => 'Timeout',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_REFUND => 'Refund',
            self::STATUS_VOID => 'Void',
        ];

        return $list[$number];
    }

    /**
     * @param $date
     * @return bool
     */
    public static function voidAbleTime($date): bool
    {
        $currentTime = time();
        $timeDifference = ($currentTime - $date);
        if ($timeDifference <= 86400) {
            return true;
        } else {
            return false;
        }
    }
}