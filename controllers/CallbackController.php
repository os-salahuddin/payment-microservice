<?php

namespace app\controllers;

use app\components\Checkout;
use app\components\CheckoutUtil;
use app\components\CybersourceUtil;
use app\components\EkPay;
use app\components\OkWallet;
use app\components\Pocket;
use app\components\RedirectUtil;
use app\components\SoutheastBank;
use app\components\TazaPay;
use app\components\TransactionLogDetailsUtil;
use Yii;
use Exception;
use yii\base\Action;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\Controller;
use app\components\UPay;
use app\components\BKash;
use app\components\TAPay;
use app\components\Utils;
use app\components\UcbBank;
use app\models\IPNQueueJob;
use app\models\Transaction;
use app\components\CityBank;
use app\components\DeshiPay;
use app\components\NagadPay;
use app\components\NexusPay;
use app\components\EBLSkyPay;
use app\models\TransactionLog;
use app\models\RefundTransaction;
use app\components\SslcommerzPay;
use app\components\EBLTokenization;
use yii\web\BadRequestHttpException;
use app\components\BracBank\iPayPipe;
use app\components\BracBank\payUtils;

class CallbackController extends Controller
{
    /**
     * @param Action $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**--------------------Cybersource CALLBACK URLS--------------------**/
    /**
     * actionCybersourceCallback handles callback for success, decline or cancel transaction of cybersource gateway.
     * The function checks if req_reference_number exists and then retrieve transaction details for the req_reference_number.
     * @return Response
     */
    public function actionCybersourceCallback()
    {
        if(Yii::$app->request->isPost) {
            $response = Yii::$app->request->post();
            try {
                $redirectUrl = null;
                if(isset($response['req_reference_number'])) {
                    TransactionLogDetailsUtil::createLog([
                        'orderId' => $response['req_reference_number'],
                        'message' => 'Cybersource Callback',
                        'response' => $_POST,
                    ]);

                    $transaction = Transaction::getByKey('orderId', $response['req_reference_number']);
                    if(isset($response['decision']) && $response['decision'] == 'ACCEPT') {
                        $redirectUrl = RedirectUtil::getSuccessUrl($transaction);
                    } else if (isset($response['decision']) && in_array($response['decision'], ['ERROR', 'DECLINE'])) {
                        $redirectUrl = RedirectUtil::getDeclineUrl($transaction);
                    } else if (isset($response['decision']) && $response['decision'] == 'CANCEL') {
                        $redirectUrl = RedirectUtil::getCancelUrl($transaction);
                    }

                    CybersourceUtil::updateTransaction($transaction, $response);
                    return $this->redirect($redirectUrl);
                }
            } catch (Exception $e) {
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $response['req_reference_number'],
                        'message' => 'Cybersource Callback exception',
                        'response' => $e->getMessage()
                    ]
                );
            }
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionCybersourceWebhook()
    {
        $raw = file_get_contents('php://input');
        parse_str($raw, $output);

        try {
            $orderId = $output['req_reference_number'];
            $transaction = Transaction::findOne(['orderId' => $orderId]);
            if(!empty($transaction)) {
                TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Cybersource Webhook',
                    'response' => $raw,
                ]);
                CybersourceUtil::updateTransaction($transaction, $output);
            }
        } catch (\Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $output['req_reference_number'],
                    'message' => 'Cybersource Webhook exception',
                    'response' => $e->getMessage()
                ]
            );
        }
    }

    /**--------------------Checkout CALLBACK URLS--------------------**/
    /**
     * actionCheckoutCallback handles callback for success, decline or cancel transaction of checkout gateway.
     * The function checks if cko-payment-id exists and then retrieve transaction details for the cko-payment-id.
     *
     * @return false|string
     * @throws NotFoundHttpException
     */
    public function actionCheckoutCallback()
    {
        if(Yii::$app->request->isGet) {
            $redirectUrl = null;
            try {
                $requestData = Yii::$app->request->get();
                if (isset($requestData['cko-payment-id'])) {
                    $paymentIdentifier = $requestData['cko-payment-id'];
                    $checkout = new Checkout();
                    $url = $checkout->getApiUrl() . '/payments/' . $paymentIdentifier;
                    $response = $checkout->sendRequest($url, 'get');

                    if (isset($response->status) && isset($response->approved)) {
                        $transaction = Transaction::findOne(['orderId' => $response->reference]);
                        TransactionLogDetailsUtil::createLog([
                            'orderId' => $response->reference,
                            'message' => 'Checkout Callback',
                            'response' => $requestData,
                        ]);
                        if(!empty($transaction)) {
                            if ($response->status == 'Captured' && $response->approved) {
                                $redirectUrl = RedirectUtil::getSuccessUrl($transaction);
                            }

                            if ($response->status == 'Declined' && !$response->approved) {
                                $redirectUrl = RedirectUtil::getDeclineUrl($transaction);
                            }
                            CheckoutUtil::updateTransaction($transaction, $response);
                        }
                    }
                } else {
                    if (isset($requestData['ref']) && $requestData['ref'] == 'cancel' && isset($requestData['order_id'])) {
                        TransactionLogDetailsUtil::createLog([
                            'orderId' => $requestData['order_id'],
                            'message' => 'Checkout Callback Cancel',
                            'response' => $requestData,
                        ]);
                        $transaction = Transaction::findOne(['orderId' => $requestData['order_id']]);
                        if(!empty($transaction)) {
                            $redirectUrl = RedirectUtil::getCancelUrl($transaction);
                            CheckoutUtil::updateTransaction($transaction, $requestData);
                        }
                    }
                }

                return $this->redirect($redirectUrl);

            } catch (Exception $e) {
            
            }
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionCheckoutWebhook()
    {
        try {
            $output = json_decode(file_get_contents('php://input'), true);
            $orderId = $output['data']['reference'];
            $transaction = Transaction::findOne(['orderId' => $orderId]);
            if(!empty($transaction)) {
                TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Checkout Webhook',
                    'response' => $output,
                ]);

                $paymentIdentifier = $output['data']['id'];
                $checkout = new Checkout();
                $url = $checkout->getApiUrl() . '/payments/' . $paymentIdentifier;
                $response = $checkout->sendRequest($url, 'get');

                if ((isset($response->status) && $response->status == 'Captured') &&
                    (isset($response->approved) && $response->approved)) {
                    $transaction = Transaction::findOne(['orderId' => $response->reference]);
                    if(!empty($transaction)) {
                        CheckoutUtil::updateTransaction($transaction, $response);
                    }
                }
            }
        } catch(Exception $e) {
            
        }
    }

    /**--------------------Tazapay CALLBACK URLS--------------------**/
    /**
     * actionTazapayCallback handles callback for success transaction of Tazapay gateway.
     * @return false
     * @throws NotFoundHttpException
     */
    public function actionTazapayCallback()
    {
        try {
            $transaction = Transaction::getByKey('orderId', $_GET['order_id']);
            if (!empty($transaction->orderId)) {
                $tazapay = new TazaPay($transaction->relatedGateway->code);
                $response = $tazapay->getOrderDetails($transaction->bankMerchantTranId, $transaction->orderId);
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $_GET['order_id'],
                        'message' => 'Tazapay Callback Success',
                        'response' => $response
                    ]
                );
                if(isset($response['data']['payment_status']) && $response['data']['payment_status'] == 'paid') {
                    $transaction->status = Transaction::STATUS_PAID;
                    $transaction->bankResponse = json_encode($response);
                    $transaction->bankOrderStatus = $response['data']['payment_status'];
                    $transaction->bankResponseDescription = $response['data']['payment_status_description'];
                    foreach($response['data']['payment_attempts'] as $paymentAttempts) {
                        if($paymentAttempts['status'] == 'succeeded') {
                            $paymentMethodDetails = $paymentAttempts['payment_method_details'];
                            $transaction->cardBrand = $paymentMethodDetails['card']['scheme'];
                            $transaction->pan = $paymentMethodDetails['card']['first6'] . 'xxxxxx' .  $paymentMethodDetails['card']['last4'];
                            $transaction->rrn = $paymentAttempts['payin'];
                            $transaction->bankApprovalCode = $paymentMethodDetails['card']['authorizationCode'];
                            $transaction->bankResponseCode = $paymentMethodDetails['card']['processorResponseCode'];
                            $transaction->cardHolderName = $paymentMethodDetails['card']['cardholder_name'];
                            break;
                        }
                    }
                    if (!$transaction->save()) {
                        TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'Tazapay update transaction store error in success callback',
                                'response' => $transaction->getErrors()
                            ]
                        );
                        return false;
                    }

                    Transaction::pushToIpnQueue($transaction);
                }

                Utils::redirect(RedirectUtil::getSuccessUrl($transaction));
            }
            //ORDER DOES NOT EXIST
        } catch (Exception $e) {
            TransactionLog::createLog('actionTazapayCallback Callback Exception', $_GET, $e->getMessage(), 0);
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * actionTazapayCancel handles callback for cancel or decline of Tazapay gateway.
     * @return false
     * @throws NotFoundHttpException
     */
    public function actionTazapayCancel()
    {
        try {
            $transaction = Transaction::getByKey('orderId', $_GET['order_id']);
            if (!empty($transaction->orderId)) {
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $_GET['order_id'],
                        'message' => 'Tazapay Callback Cancel',
                        'response' => $_GET
                    ]
                );

                Utils::redirect(RedirectUtil::getCancelUrl($transaction));
            }
            //ORDER DOES NOT EXIST
        } catch (Exception $e) {
            TransactionLog::createLog('actionTazapayCancel Callback Exception', $_GET, $e->getMessage(), 0);
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * actionTazapayWebhook receives webhook data of Tazapay gateway.
     * @return false|void
     */
    public function actionTazapayWebhook()
    {
        $response = json_decode(file_get_contents('php://input'), true);
        TransactionLog::createLog('actionTazapayWebhook in', '', $response, 0);
        try {
            /**
             * It checks if payment_status == paid. Then transaction is updated as paid
             */
            if (isset($response['data']['payment_status']) && isset($response['data']['reference_id'])) {
                $orderId = $response['data']['reference_id'];
                TransactionLogDetailsUtil::createLog([
                    'orderId' => $orderId,
                    'message' => 'Tazapay Webhook',
                    'response' => $response,
                ]);

                $transaction = Transaction::findOne(['orderId' => $orderId]);
                if (!empty($transaction)) {
                    if($response['data']['payment_status'] == 'paid') {
                        $transaction->bankResponse = json_encode($response);
                        $transaction->status = Transaction::STATUS_PAID;
                        $transaction->bankOrderStatus = $response['data']['payment_status'];
                        foreach ($response['data']['payment_attempts'] as $paymentAttempts) {
                            if ($paymentAttempts['status'] == 'succeeded') {
                                $paymentMethodDetails = $paymentAttempts['payment_method_details'];
                                $transaction->cardBrand = $paymentMethodDetails['card']['scheme'];
                                $transaction->pan = $paymentMethodDetails['card']['first6'] . 'xxxxxx' . $paymentMethodDetails['card']['last4'];
                                $transaction->rrn = $paymentAttempts['payin'];
                                $transaction->bankApprovalCode = $paymentMethodDetails['card']['authorizationCode'];
                                $transaction->bankResponseCode = $paymentMethodDetails['card']['processorResponseCode'];
                                $transaction->cardHolderName = $paymentMethodDetails['card']['cardholder_name'];
                                break;
                            }
                        }

                        if (!$transaction->save()) {
                            TransactionLogDetailsUtil::createLog(
                                [
                                    'orderId' => $orderId,
                                    'message' => 'Tazapay update transaction store error in webhook',
                                    'response' => $transaction->getErrors()
                                ]
                            );
                            return false;
                        }

                        Transaction::pushToIpnQueue($transaction);
                    }
                }
            }
        } catch (\Exception $e) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $response['data']['reference_id'],
                    'message' => 'Tazapay Webhook exception',
                    'response' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @return false|string
     */
    public function actionSblSuccess()
    {
        try {
            $resultIndicator = (isset($_GET["resultIndicator"])) ? $_GET["resultIndicator"] : "";
            $transaction = Transaction::getByKey('resultIndicator', $resultIndicator);

            if (!empty($transaction->resultIndicator)) {
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $transaction->orderId,
                        'response' => $_GET,
                        'message' => 'Sbl Callback Success'
                    ]
                );

                $sbl = new SoutheastBank($transaction->relatedGateway->code);
                $response = $sbl->retrieveOrder($transaction->orderId);

                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $transaction->orderId,
                        'request' => $transaction->orderId,
                        'response' => $response,
                        'message' => 'Sbl Callback Success Retrieve Order'
                    ]
                );

                Transaction::updateByKeyValue(['orderId' => $transaction->orderId], $response, true);

                if ($transaction->notify === Transaction::NOTIFICATION_NOT_SEND) {
                    Transaction::pushToIpnQueue($transaction);
                }

                $redirectUrl = RedirectUtil::getSuccessUrl($transaction);
                Utils::redirect($redirectUrl);
            }
        } catch (Exception $e) {
            TransactionLog::createLog('SBL Callback Success', 'Exception', $e->getMessage(), 0);
        }
        return Utils::SomethingWrong();
    }

    /**
     * @return false|string
     */
    public function actionSblTimeout()
    {
        try {
            $transaction = Transaction::getByKey('orderId', $_GET['order']);

            if (!empty($transaction->orderId)) {
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $transaction->orderId,
                        'response' => $_GET,
                        'message' => 'Sbl Callback Timeout'
                    ]
                );

                $sbl = new SoutheastBank($transaction->relatedGateway->code);
                $response = $sbl->retrieveOrder($transaction->orderId, 'TIMEOUT');

                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $transaction->orderId,
                        'request' => $transaction->orderId,
                        'response' => $response,
                        'message' => 'Sbl Callback Timeout Retrieve Order'
                    ]
                );

                Transaction::updateByKeyValue(['orderId' => $transaction->orderId], $response, true);
                Transaction::pushToIpnQueue($transaction);
                $redirectUrl = RedirectUtil::getDeclineUrl($transaction);
                Utils::redirect($redirectUrl);
            }
        } catch (Exception $e) {
            TransactionLog::createLog('SBL Callback Timeout', 'Exception', $e->getMessage(), 0);
        }
        return Utils::SomethingWrong();
    }

    /**
     * @return false|string
     */
    public function actionSblWebhook()
    {
        try {
            if(Yii::$app->request->isPost) {
                TransactionLog::createLog('SBL actionSblWebhook', 'Post', $_POST, 0);
            } else if(Yii::$app->request->isGet) {
                TransactionLog::createLog('SBL actionSblWebhook', 'Get', $_GET, 0);
            } else {
                $raw = file_get_contents('php://input');
                TransactionLog::createLog('SBL actionSblWebhook', 'Raw', $raw, 0);
                $output = json_decode($raw, true);

                if(isset($output['order']['id']) && $output['result'] == 'SUCCESS') {
                    $transaction = Transaction::getByKey('orderId', $output['order']['id']);
                    TransactionLogDetailsUtil::createLog([
                            'orderId' => $transaction->orderId,
                            'request' => $transaction->orderId,
                            'response' => $output,
                            'message' => 'SBL actionSblWebhook'
                        ]
                    );
                    $response = [
                        'orderId' => $output['order']['id'],
                        'amount' => $output['order']['amount'],
                        'refundAmount' => $output['order']['totalRefundedAmount'],
                        'description' => $output['order']['description'],
                        'bankResponse' => $raw,
                        'bankTransactionDate' => $output['order']['creationTime'],
                        'pan' => $output['sourceOfFunds']['provided']['card']['number'],
                        'cardHolderName' => $output['sourceOfFunds']['provided']['card']['nameOnCard'], // CardHolder Name
                        'cardBrand' => $output['sourceOfFunds']['provided']['card']['brand'], // Payment Brand
                        'status' => $output['order']['status'] == 'CAPTURED' ? Transaction::STATUS_PAID : Transaction::STATUS_DECLINED,
                        'bankResponseDescription' => $output['order']['status'] == 'CAPTURED' ?
                            'Order: ' . $output['order']['id'] . ' Payment Successful' :
                            'Order: ' . $output['order']['id'] . ' Payment Failed',
                        'bankApprovalCode' => $output['transaction']['authorizationCode'] ?? null,
                        'bankOrderStatus' => $output['order']['status'],
                        'bankMerchantTranId' => $output['transaction']['id'],
                        'rrn' => $output['transaction']['receipt'] ?? null,
                        'type' => $output['transaction']['type'],
                        'bankResponseCode' => $output['authorizationResponse']['responseCode'] ?? null
                    ];

                    TransactionLogDetailsUtil::createLog(
                        [
                            'orderId' => $transaction->orderId,
                            'request' => $transaction->orderId,
                            'response' => $response,
                            'message' => 'SBL actionSblWebhook Transaction update'
                        ]
                    );

                    Transaction::updateByKeyValue(['orderId' => $transaction->orderId], $response, true);

                    if ($transaction->notify === Transaction::NOTIFICATION_NOT_SEND) {
                        Transaction::pushToIpnQueue($transaction);
                    }
                }
            }
        } catch (Exception $e) {
            TransactionLog::createLog('SBL Callback actionSblWebhook', 'Exception', $e->getMessage(), 0);
        }
        return Utils::SomethingWrong();
    }

    /**
     * @return false|string
     */
    public function actionSblCancel()
    {
        try {
            $transaction = Transaction::getByKey('orderId', $_GET['order']);
            if (!empty($transaction->orderId)) {
                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $transaction->orderId,
                        'response' => $_GET,
                        'message' => 'Sbl Callback Cancel'
                    ]
                );

                $sbl = new SoutheastBank($transaction->relatedGateway->code);
                $response = $sbl->retrieveOrder($transaction->orderId, 'CANCEL');

                TransactionLogDetailsUtil::createLog(
                    [
                        'orderId' => $transaction->orderId,
                        'request' => $transaction->orderId,
                        'response' => $response,
                        'message' => 'Sbl Callback Cancel Retrieve Order'
                    ]
                );

                Transaction::updateByKeyValue(['orderId' => $transaction->orderId], $response, true);
                Transaction::pushToIpnQueue($transaction);

                $redirectUrl = RedirectUtil::getCancelUrl($transaction);
                Utils::redirect($redirectUrl);
            }
        } catch (Exception $e) {
            TransactionLog::createLog('SBL Callback Cancel', '', $e->getMessage(), 0);
        }
        return Utils::SomethingWrong();
    }
}
