<?php

namespace app\controllers;

use app\components\CyberSource;
use app\components\NagadPay;
use app\components\SoutheastBank;
use app\components\TazaPay;
use Yii;
use yii\web\Response;
use yii\web\Controller;
use app\models\Currency;
use app\components\Utils;
use app\components\BKash;
use app\models\Transaction;
use app\components\CityBank;
use app\components\DeshiPay;
use app\components\EBLSkyPay;
use app\components\SslcommerzPay;
use app\components\TransactionLogDetailsUtil;
use app\models\RefundTransaction;
use app\components\EBLTokenization;
use app\components\BracBank\payUtils;

class RefundController extends Controller
{
    public function beforeAction($action): bool
    {
        Yii::$app->asm->has();
        return parent::beforeAction($action);
    }

    /**--------------------CITY BANK REFUND---------------------**/
    public function actionCityRefund(): Response
    {
        $CityBank = new CityBank();
        $orderInfos = $CityBank->RefundOrder($_GET['orderId'], $_GET['refundAmount'], $_GET['refundDescription']);
        $transaction = Transaction::getByKey('orderId', $_GET["orderId"]);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($orderInfos, true),
            'charge' => 0,
            'amount' => $_GET['refundAmount'],
            'currency' => $transaction->relatedCurrency->code,
            'transactionType' => 1,
            'bankStatus' => 'FAILED',
            'status' => RefundTransaction::REFUND_FAILED,
        ];

        if ((isset($orderInfos['responseMessage'])) && ($orderInfos['responseMessage'] == RefundTransaction::CITY_REFUND_SUCCESS)) {
            $updateData['bankStatus'] = 'SUCCESS';
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        $refundTransaction = RefundTransaction::getByKey('transactionId', $transaction->uid);
        if (isset($refundTransaction->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $refundTransaction->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            $transaction->refundAmount = $updateData['amount'];
            $transaction->status = Transaction::STATUS_REFUND;
            $transaction->save();
            Yii::$app->session->setFlash('success', "Order successfully Refunded");
        } else {
            Yii::$app->session->setFlash('error', "Order Refund Failed");
        }

        TransactionLogDetailsUtil::createLog([
                'orderId' => $transaction->orderId,
                'message' => 'CBL Refund',
                'request' => $transaction->orderId,
                'response' => $orderInfos
            ]
        );


        return $this->redirect(['transaction/index']);
    }

    /**--------------------EBL VOID---------------------**/
    public function actionEblVoid(): Response
    {
        $transaction = Transaction::getByKey('orderId', $_GET["orderId"]);

        $EBLSkyPay = new EBLSkyPay($transaction->relatedGateway->code);
        $response = $EBLSkyPay->VoidTransaction($_GET["orderId"], $transaction->bankMerchantTranId);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($response, true),
            'amount' => $transaction->amount,
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => RefundTransaction::TRANSACTION_TYPE_VOID,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if ((isset($response['result']) && ($response['result'] == "SUCCESS") && ($response['order.status'] == 'CANCELLED')) && (!isset($response['error.cause']))) {
            $updateData['bankStatus'] = $response['transaction.type'];
            $updateData['charge'] = $response['order.chargeback.amount'];
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        $existingRefundData = RefundTransaction::getByKey('transactionId', $transaction->uid);
        if (isset($existingRefundData->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $existingRefundData->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            Transaction::updateByKeyValue(['orderId' => $transaction->orderId], ['refundAmount' => $updateData['amount'], 'status' => Transaction::STATUS_VOID]);
            Yii::$app->session->setFlash('success', "Order successfully Void.");
        } else {
            Yii::$app->session->setFlash('error', "Order Void Failed.");
        }

        TransactionLogDetailsUtil::createLog([
            'orderId' => $transaction->orderId,
            'message' => 'EBL VoidTransaction',
            'request' => $transaction->orderId,
            'response' => $response
            ]
        );


        return $this->redirect(['transaction/index']);
    }

    /**--------------------BKASH REFUND---------------------**/

    public function actionBkashRefund(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);
        $bkash = new BKash($transaction->relatedGateway->code);
        $responseRefund = $bkash->refundTransaction($transaction->bankMerchantTranId, $_GET['refundAmount'],
            $transaction->rrn, $transaction->orderId, $_GET['refundDescription']);

        if (isset($responseRefund->errorCode) && in_array($responseRefund->errorCode, BKash::REFUND_ERROR_CODES)) {
            Yii::$app->session->setFlash('error', $responseRefund->errorMessage);
            $updateData['status'] = RefundTransaction::REFUND_FAILED;
        } else {
            $updateData = [
                'uid' => Utils::uniqueCode(36),
                'transactionId' => $transaction->uid,
                'response' => json_encode($responseRefund, true),
                'amount' => $_GET['refundAmount'],
                'currency' => Currency::getByKey('code', $responseRefund->currency)->uid,
                'transactionType' => 1,
                'status' => RefundTransaction::REFUND_FAILED
            ];

            if (isset($responseRefund->transactionStatus) && ($responseRefund->transactionStatus == "Completed")) {
                $updateData['bankStatus'] = $responseRefund->transactionStatus;
                $updateData['charge'] = $responseRefund->charge;
                $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
            }

            if (isset($transaction->relatedRefund->uid)) {
                RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
            } else {
                RefundTransaction::add($updateData);
            }

            if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
                $transaction->refundAmount = $updateData['amount'];
                $transaction->status = Transaction::STATUS_REFUND;
                $transaction->save();
                Yii::$app->session->setFlash('success', "Order successfully Refunded");
            } else {
                Yii::$app->session->setFlash('error', "Order Refund Failed");
            }
        }

        TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'Bkash Refund',
                                'request' => $transaction->orderId,
                                'response' => $responseRefund
                            ]
                        );

        return $this->redirect(['transaction/index']);
    }

    public function actionBkashDisbursement(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["bkashRefundOrderId"]]);
        if ($_GET['bkashRefundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        if ($transaction->status === Transaction::STATUS_PAID) {
            $bankResponse = json_decode($transaction->bankResponse);
            if (!isset($bankResponse->customerMsisdn)) {
                Yii::$app->session->setFlash('error', "Customer Bkash Account Not Found!");
                return $this->redirect(['transaction/index']);
            }

            $bkashAccountNo = $bankResponse->customerMsisdn;
        } else {
            Yii::$app->session->setFlash('error', "This is an Unpaid Transaction Already!");
            return $this->redirect(['transaction/index']);
        }

        $bkash = new BKash($transaction->relatedGateway->code);
        $responseRefund = $bkash->b2cRefund($transaction->orderId, $_GET['bkashRefundAmount'], $bkashAccountNo);

        if (isset($responseRefund->errorCode) && isset($responseRefund->errorMessage)) {
            Yii::$app->session->setFlash('error', $responseRefund->errorMessage);
            $updateData['status'] = RefundTransaction::REFUND_FAILED;
        } else {
            $updateData = [
                'uid' => Utils::uniqueCode(36),
                'transactionId' => $transaction->uid,
                'response' => json_encode($responseRefund, true),
                'amount' => $_GET['bkashRefundAmount'],
                'currency' => Currency::getByKey('code', 'BDT')->uid,
                'transactionType' => 1,
                'status' => RefundTransaction::REFUND_FAILED
            ];

            if (isset($responseRefund->transactionStatus) && ($responseRefund->transactionStatus == "Completed")) {
                $updateData['amount'] = $responseRefund->amount;
                $updateData['bankStatus'] = $responseRefund->transactionStatus;
                $updateData['charge'] = $responseRefund->b2cFee ?? 0;
                $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
            }

            if (isset($transaction->relatedRefund->uid)) {
                RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
            } else {
                RefundTransaction::add($updateData);
            }

            if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
                $transaction->refundAmount = $updateData['amount'];
                $transaction->status = Transaction::STATUS_REFUND;
                $transaction->save();
                Yii::$app->session->setFlash('success', "Order Amount successfully Disbursed");
            } else {
                Yii::$app->session->setFlash('error', "Order Disbursement Failed");
            }
        }

        TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'Bkash Disbursement',
                                'request' => $transaction->orderId,
                                'response' => $responseRefund
                            ]);

        return $this->redirect(['transaction/index']);
    }

    /**--------------------NAGAD REFUND---------------------**/

    public function actionNagadRefund(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);

        if ($_GET['refundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        $nagad = new NagadPay();
        $responseRefund = $nagad->refundTransaction($transaction->orderId, $_GET['refundAmount']);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($responseRefund, true),
            'amount' => $_GET['refundAmount'],
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => 1,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if (isset($responseRefund->status) && ($responseRefund->status == "PartialCancelled" || $responseRefund->status == "Cancelled" || $responseRefund->status == "Refunded" || $responseRefund->status == "PartialRefunded")) {
            $updateData['amount'] = $responseRefund->cancelAmount;
            $updateData['bankStatus'] = 'Success';
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        if (isset($transaction->relatedRefund->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            $transaction->refundAmount = $updateData['amount'];
            $transaction->status = Transaction::STATUS_REFUND;
            $transaction->save();
            Yii::$app->session->setFlash('success', "Order successfully Refunded");
        } else {
            Yii::$app->session->setFlash('error', "Order Refund Failed");
        }


        TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'Nagad Refund',
                                'request' => $transaction->orderId,
                                'response' => $responseRefund
                            ]);
        
        return $this->redirect(['transaction/index']);
    }

    /**--------------------BRAC BANK REFUND---------------------**/
    public function actionBracRefund()
    {
        if (isset($_GET['orderId'])) {
            $initiateRefund = payUtils::bracRefund($_GET['orderId'], $_GET['refundAmount'], $_GET['refundDescription']);
            if ($initiateRefund) {
                header('Location:' . $initiateRefund); //Redirect to Payment Gateway page
                exit();
            }
        }
        Utils::SomethingWrong();
    }

    /**--------------------BRAC BANK VOID---------------------**/
    public function actionBracVoid()
    {
        if (isset($_GET['orderId'])) {
            $initiateRefund = payUtils::bracVoid($_GET['orderId']);
            header('Location:' . $initiateRefund); //Redirect to Payment Gateway page
            exit();
        }
        Utils::SomethingWrong();
    }

    /**--------------------EBL TOKENIZATION VOID---------------------**/
    public function actionEblTokenVoid()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $transaction = Transaction::getByKey('orderId', $_GET["orderId"]);

        $EBLTokenization = new EBLTokenization($transaction->gateway);
        $response = $EBLTokenization->voidOrder(['customer' => $transaction->customerId . '-' . $transaction->relatedClient->name, 'orderId' => $transaction->orderId]);
        $orderResponse = $response['data'];
        if (!$response['success']) return $response;

        if ((isset($orderResponse['result']) && ($orderResponse['result'] == "SUCCESS")
                && ($orderResponse['response']['status'] == 'CANCELLED')) && (!isset($orderResponse['response']['error.cause']))) {
            $counter = 0;
            $type = '';

            while (true) {
                if (!isset($orderResponse['response']['transaction[' . $counter . '].result'])) {
                    break;
                }
                $type = $orderResponse['response']['transaction[' . $counter . '].transaction.type'];
                $counter++;
            }

            $updateData = [
                'uid' => Utils::uniqueCode(36),
                'transactionId' => $transaction->uid,
                'response' => json_encode($orderResponse, true),
                'amount' => $transaction->amount,
                'currency' => $transaction->relatedCurrency->uid,
                'transactionType' => RefundTransaction::TRANSACTION_TYPE_VOID,
                'status' => RefundTransaction::REFUND_FAILED
            ];


            $updateData['bankStatus'] = $type;
            $updateData['charge'] = $orderResponse['response']['chargeback.amount'];
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        } else {
            $updateData = [
                'uid' => Utils::uniqueCode(36),
                'transactionId' => $transaction->uid,
                'response' => json_encode($orderResponse, true),
                'amount' => $transaction->amount,
                'currency' => $transaction->relatedCurrency->uid,
                'transactionType' => RefundTransaction::TRANSACTION_TYPE_VOID,
                'status' => RefundTransaction::REFUND_FAILED
            ];
        }
        $existingRefundData = RefundTransaction::getByKey('transactionId', $transaction->uid);
        if (isset($existingRefundData->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $existingRefundData->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            Transaction::updateByKeyValue(['orderId' => $transaction->orderId], ['refundAmount' => $updateData['amount'], 'status' => Transaction::STATUS_VOID]);
            Yii::$app->session->setFlash('success', "Order successfully Void.");
        } else {
            Yii::$app->session->setFlash('error', "Order Void Failed.");
        }

        TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'EBLTokenization VoidTransaction',
                                'request' => $transaction->orderId,
                                'response' => $orderResponse
                            ]
                        );


        return $this->redirect(['transaction/index']);
    }

    /**--------------------EBL TOKENIZATION VOID---------------------**/
    public function actionEblTokenRefund()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $transaction = Transaction::getByKey('orderId', $_GET["orderId"]);

            if ($_GET['refundAmount'] > $transaction->amount) {
                Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
                return $this->redirect(['transaction/index']);
            }

            $EBLTokenization = new EBLTokenization($transaction->gateway);
            $response = $EBLTokenization->refundOrder(['customer' => $transaction->customerId . '-' . $transaction->relatedClient->name, 'orderId' => $transaction->orderId, 'amount' => $transaction->amount, 'currency' => $transaction->relatedCurrency->code]);
            $orderResponse = $response['data'];

            if (!$response['success']) return $response;

            if ((isset($orderResponse['result']) && ($orderResponse['result'] == "SUCCESS")
                && ($orderResponse['response']['order.status'] == 'REFUNDED'))) {
                $updateData = [
                    'uid' => Utils::uniqueCode(36),
                    'transactionId' => $transaction->uid,
                    'response' => json_encode($orderResponse, true),
                    'amount' => $transaction->amount,
                    'currency' => $transaction->relatedCurrency->uid,
                    'transactionType' => RefundTransaction::TRANSACTION_TYPE_REFUND,
                    'status' => RefundTransaction::REFUND_FAILED
                ];


                $updateData['bankStatus'] = $orderResponse['response']['order.status'];
                $updateData['charge'] = $orderResponse['response']['order.chargeback.amount'];
                $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
            } else {
                $updateData = [
                    'uid' => Utils::uniqueCode(36),
                    'transactionId' => $transaction->uid,
                    'response' => json_encode($orderResponse, true),
                    'amount' => $transaction->amount,
                    'currency' => $transaction->relatedCurrency->uid,
                    'transactionType' => RefundTransaction::TRANSACTION_TYPE_REFUND,
                    'status' => RefundTransaction::REFUND_FAILED
                ];
            }
            $existingRefundData = RefundTransaction::getByKey('transactionId', $transaction->uid);
            if (isset($existingRefundData->uid)) {
                RefundTransaction::updateByKeyValue(['uid' => $existingRefundData->uid], $updateData);
            } else {
                RefundTransaction::add($updateData);
            }

            if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
                Transaction::updateByKeyValue(['orderId' => $transaction->orderId], ['refundAmount' => $updateData['amount'], 'status' => Transaction::STATUS_REFUND]);
                Yii::$app->session->setFlash('success', "Order successfully Refunded.");
            } else {
                Yii::$app->session->setFlash('error', "Order refund Failed.");
            }

            TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'EBLTokenization RefundTransaction',
                                'request' => ['customer' => $transaction->customerId . '-' . $transaction->relatedClient->name, 'orderId' => $transaction->orderId, 'amount' => $transaction->amount, 'currency' => $transaction->relatedCurrency->code],
                                'response' => $orderResponse
                            ]
                        );


            return $this->redirect(['transaction/index']);
        } catch (\Exception $e) {
            TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'EBLTokenization RefundTransaction exception',
                                'response' => $e->getMessage()
                            ]
                        );

        }
    }

    /**--------------------DeshiPay REFUND---------------------**/

    public function actionDeshiRefund(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);
        if ($_GET['refundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        if ($transaction->status === Transaction::STATUS_PAID) {
            $bankResponse = json_decode($transaction->bankResponse);
            if (!isset($bankResponse->data->customer_mobile_number)) {
                Yii::$app->session->setFlash('error', "Customer DeshiPay Account Not Found!");
                return $this->redirect(['transaction/index']);
            }
            $deshiPayAccountNo = $bankResponse->data->customer_mobile_number;
        } else {
            Yii::$app->session->setFlash('error', "This is an Unpaid Transaction!");
            return $this->redirect(['transaction/index']);
        }

        if (empty($_GET['refundDescription'])) {
            Yii::$app->session->setFlash('error', "Please provide reason of the refund");
            return $this->redirect(['transaction/index']);
        }

        $deshiPay = new DeshiPay($transaction->relatedGateway->code);
        $isRefundedAlready = $deshiPay->validateRefund($transaction->orderId);
        TransactionLogDetailsUtil::createLog([
            'orderId' => $transaction->orderId,
            'message' => 'DeshiPay Refund validateRefund to check isRefundedAlready',
            'request' => $transaction->orderId,
            'response' => $isRefundedAlready
        ]);
        if (!$isRefundedAlready['data']['data']['refund_status']) {
            $refund = $deshiPay->refundPayment($transaction->orderId, $deshiPayAccountNo, $_GET['refundAmount'], $_GET['refundDescription']);
            TransactionLogDetailsUtil::createLog([
                'orderId' => $transaction->orderId,
                'message' => 'DeshiPay Refund',
                'request' => $transaction->orderId,
                'response' => $refund
            ]);
            if ($refund['success']) {
                $responseRefund = $deshiPay->validateRefund($transaction->orderId);
                TransactionLogDetailsUtil::createLog([
                    'orderId' => $transaction->orderId,
                    'message' => 'DeshiPay Refund validateRefund after success',
                    'request' => $transaction->orderId,
                    'response' => $responseRefund
                ]);

                if ($refund['data']['code'] === 200) {
                    $updateData = [
                        'uid' => Utils::uniqueCode(36),
                        'transactionId' => $transaction->uid,
                        'response' => json_encode($refund['data'], true),
                        'amount' => $_GET['refundAmount'],
                        'currency' => Currency::getByKey('code', 'BDT')->uid,
                        'transactionType' => 1,
                        'charge' => 0,
                        'status' => RefundTransaction::REFUND_FAILED
                    ];

                    if (isset($responseRefund['data']['data']['refund_status']) && ($responseRefund['data']['data']['refund_status'])) {
                        $updateData['bankStatus'] = 'Success';
                        $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
                    }

                    if (isset($transaction->relatedRefund->uid)) RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
                    else RefundTransaction::add($updateData);

                    if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
                        $transaction->refundAmount = $updateData['amount'];
                        $transaction->status = Transaction::STATUS_REFUND;
                        $transaction->save();
                        Yii::$app->session->setFlash('success', "Order Amount successfully Disbursed");
                    } else {
                        Yii::$app->session->setFlash('error', "Order Disbursement Failed");
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Refund Failed');
                    $updateData['status'] = RefundTransaction::REFUND_FAILED;
                }
            } else {
                Yii::$app->session->setFlash('error', 'Refund Failed For This DeshiPay Transaction');
                $updateData['status'] = RefundTransaction::REFUND_FAILED;
            }

        } else {
            Yii::$app->session->setFlash('error', 'This DeshiPay Transaction Is Already Refunded!');
            $updateData['status'] = RefundTransaction::REFUND_FAILED;
        }


        return $this->redirect(['transaction/index']);
    }

    /**--------------------SSLCommerz REFUND---------------------**/

    public function actionSslcommerzRefund(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);

        if ($_GET['refundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        $sslCommerz = new SslcommerzPay();
        $refund = $sslCommerz->refundPayment($transaction->orderId, $transaction->bankMerchantTranId, $_GET['refundAmount']);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($refund, true),
            'amount' => $_GET['refundAmount'],
            'currency' => Currency::getByKey('code', 'BDT')->uid,
            'transactionType' => 1,
            'charge' => 0,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if (isset($refund['status']) && ($refund['status'] == 'success')) {
            $updateData['bankStatus'] = 'Success';
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        if (isset($transaction->relatedRefund->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            $transaction->refundAmount = $updateData['amount'];
            $transaction->status = Transaction::STATUS_REFUND;
            $transaction->save();
            Yii::$app->session->setFlash('success', "Order Amount successfully Disbursed");
        } else {
            Yii::$app->session->setFlash('error', "Order Disbursement Failed");
        }

        TransactionLogDetailsUtil::createLog(
                            [
                                'orderId' => $transaction->orderId,
                                'message' => 'SSLCommerz Refund',
                                'request' => $transaction->orderId,
                                'response' => $refund
                            ]);

        return $this->redirect(['transaction/index']);
    }

    /**--------------------Cybersource REFUND---------------------**/

    public function actionCybersourceRefund(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);

        if ($_GET['refundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        $payload = json_encode([
            "clientReferenceInformation" => [
                "code" => $transaction->orderId
            ],
            "processingInformation" => [
                "refundOptions" => [
                    "reason" => $_GET["refundDescription"]
                ],
            ],
            "orderInformation" => [
                "amountDetails" => [
                    "totalAmount" => $_GET['refundAmount'],
                    "currency" => $transaction->relatedCurrency->code
                ]
            ]
        ]);

        $cybersource = new CyberSource($transaction->relatedGateway->code);
        $responseRefund = $cybersource->refundTransaction($transaction->bankMerchantTranId, $payload);
        TransactionLogDetailsUtil::createLog(
            [
                'orderId' => $transaction->orderId,
                'message' => 'cybersource Refund',
                'request' => $payload,
                'response' => $responseRefund
            ]);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($responseRefund, true),
            'amount' => $_GET['refundAmount'],
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => 1,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if (
            isset($responseRefund->status) && ($responseRefund->status == 'PENDING') &&
            isset($responseRefund->reconciliationId) &&
            isset($responseRefund->processorInformation->approvalCode) &&
            isset($responseRefund->processorInformation->retrievalReferenceNumber)
        ) {
            $cybersource = new CyberSource($transaction->relatedGateway->code);
            $responseRefundDetails = $cybersource->retrieveRefundDetails($responseRefund->_links->self->href);
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $transaction->orderId,
                    'message' => 'cybersource Refund Details',
                    'request' => $transaction->orderId,
                    'response' => $responseRefundDetails
                ]);

            if(isset($responseRefundDetails->statusInformation->reason) &&
                (strtolower($responseRefundDetails->statusInformation->reason) == 'success')) {
                $updateData['amount'] = $responseRefundDetails->refundAmountDetails->refundAmount;
                $updateData['bankStatus'] = 'Success';
                $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
            }
        }

        if (isset($transaction->relatedRefund->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            $transaction->refundAmount = $updateData['amount'];
            $transaction->status = Transaction::STATUS_REFUND;
            $transaction->save();
            Yii::$app->session->setFlash('success', "Order successfully Refunded");
        } else {
            Yii::$app->session->setFlash('error', "Order Refund Failed");
        }

        return $this->redirect(['transaction/index']);
    }

    public function actionCybersourceVoid(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);
        $cybersource = new CyberSource($transaction->relatedGateway->code);

        $payload = json_encode([
            "clientReferenceInformation" => [
                "code" => $transaction->orderId
            ]
        ]);
        $responseRefund = $cybersource->voidTransaction($transaction->bankMerchantTranId, $payload);
        TransactionLogDetailsUtil::createLog(
            [
                'orderId' => $transaction->orderId,
                'message' => 'cybersource Void',
                'request' => $payload,
                'response' => $responseRefund
            ]);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($responseRefund, true),
            'amount' => $transaction->amount,
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => 1,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if (isset($responseRefund->status) && $responseRefund->status == "VOIDED") {
            $updateData['amount'] = $responseRefund->voidAmountDetails->voidAmount;
            $updateData['bankStatus'] = 'Success';
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        if (isset($transaction->relatedRefund->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            $transaction->refundAmount = $updateData['amount'];
            $transaction->status = Transaction::STATUS_REFUND;
            $transaction->save();
            Yii::$app->session->setFlash('success', "Order successfully Void");
        } else {
            Yii::$app->session->setFlash('error', "Order Void Failed");
        }

        return $this->redirect(['transaction/index']);
    }

    /**--------------------Tazapy REFUND---------------------**/

    public function actionTazapayRefund(): Response
    {
        $transaction = Transaction::findOne(['orderId' => $_GET["orderId"]]);

        if ($_GET['refundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        $tazapay = new TazaPay($transaction->relatedGateway->code);
        $payload = [
            'payin'=> $transaction->bankMerchantTranId,
            'amount' => trim($_GET['refundAmount']) * 100,
            'reason' => $_GET["refundDescription"],
            'webhook_url' => $tazapay->getWebhookUrl()
        ];

        $responseRefund = $tazapay->refundTransaction($transaction->orderId, $payload);
        TransactionLogDetailsUtil::createLog(
            [
                'orderId' => $transaction->orderId,
                'message' => 'Tazapay Refund',
                'request' => $payload,
                'response' => $responseRefund
            ]);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($responseRefund, true),
            'amount' => $_GET['refundAmount'],
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => 1,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if (
            isset($responseRefund['status']) &&
            $responseRefund['status'] == 'success' &&
            isset($responseRefund['data']['status']) &&
            $responseRefund['data']['status'] == 'initiated'
        ) {
                $updateData['amount'] = $responseRefund['data']['amount'];
                $updateData['bankStatus'] = 'Success';
                $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        if (isset($transaction->relatedRefund->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $transaction->relatedRefund->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            $transaction->refundAmount = $updateData['amount'];
            $transaction->status = Transaction::STATUS_REFUND;
            $transaction->save();
            Yii::$app->session->setFlash('success', "Order successfully Refunded");
        } else {
            Yii::$app->session->setFlash('error', "Order Refund Failed");
        }

        return $this->redirect(['transaction/index']);
    }

    /**--------------------SBL Refund---------------------**/
    public function actionSblRefund(): Response
    {
        $transaction = Transaction::getByKey('orderId', $_GET["orderId"]);

        if ($_GET['refundAmount'] > $transaction->amount) {
            Yii::$app->session->setFlash('error', "Your Refund Amount is higher the the actual amount!");
            return $this->redirect(['transaction/index']);
        }

        $southeastBank = new SoutheastBank($transaction->relatedGateway->code);
        $response = $southeastBank->refundTransaction($_GET["orderId"], $_GET['refundAmount'], $transaction->bankMerchantTranId);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($response, true),
            'amount' => $transaction->amount,
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => RefundTransaction::TRANSACTION_TYPE_REFUND,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if ((isset($response['result']) && ($response['result'] == "SUCCESS") && ($response['order.status'] == 'REFUNDED'))) {
            $updateData['bankStatus'] = $response['transaction.type'];
            $updateData['charge'] = $response['order.chargeback.amount'];
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        $existingRefundData = RefundTransaction::getByKey('transactionId', $transaction->uid);
        if (isset($existingRefundData->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $existingRefundData->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            Transaction::updateByKeyValue(['orderId' => $transaction->orderId], ['refundAmount' => $updateData['amount'], 'status' => Transaction::STATUS_REFUND]);
            Yii::$app->session->setFlash('success', "Order successfully Refunded.");
        } else {
            Yii::$app->session->setFlash('error', "Order Refund Failed.");
        }

        TransactionLogDetailsUtil::createLog([
                'orderId' => $transaction->orderId,
                'message' => 'SBL RefundTransaction',
                'request' => $transaction->orderId,
                'response' => $response
            ]
        );
        return $this->redirect(['transaction/index']);
    }

    /**--------------------SBL VOID---------------------**/
    public function actionSblVoid(): Response
    {
        $transaction = Transaction::getByKey('orderId', $_GET["orderId"]);

        $southeastBank = new SoutheastBank($transaction->relatedGateway->code);
        $response = $southeastBank->voidTransaction($_GET["orderId"], $transaction->bankMerchantTranId);

        $updateData = [
            'uid' => Utils::uniqueCode(36),
            'transactionId' => $transaction->uid,
            'response' => json_encode($response, true),
            'amount' => $transaction->amount,
            'currency' => $transaction->relatedCurrency->uid,
            'transactionType' => RefundTransaction::TRANSACTION_TYPE_VOID,
            'status' => RefundTransaction::REFUND_FAILED
        ];

        if (isset($response['result']) && ($response['result'] == "SUCCESS") && ($response['order.status'] == 'CANCELLED')) {
            $updateData['bankStatus'] = $response['transaction.type'];
            $updateData['charge'] = $response['order.chargeback.amount'];
            $updateData['status'] = RefundTransaction::REFUND_SUCCESS;
        }

        $existingRefundData = RefundTransaction::getByKey('transactionId', $transaction->uid);
        if (isset($existingRefundData->uid)) {
            RefundTransaction::updateByKeyValue(['uid' => $existingRefundData->uid], $updateData);
        } else {
            RefundTransaction::add($updateData);
        }

        if ($updateData['status'] == RefundTransaction::REFUND_SUCCESS) {
            Transaction::updateByKeyValue(['orderId' => $transaction->orderId], ['refundAmount' => $updateData['amount'], 'status' => Transaction::STATUS_VOID]);
            Yii::$app->session->setFlash('success', "Order successfully Void.");
        } else {
            Yii::$app->session->setFlash('error', "Order Void Failed.");
        }

        TransactionLogDetailsUtil::createLog([
                'orderId' => $transaction->orderId,
                'message' => 'SBL VoidTransaction',
                'request' => $transaction->orderId,
                'response' => $response
            ]
        );

        return $this->redirect(['transaction/index']);
    }
}