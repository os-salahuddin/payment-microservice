<?php

namespace app\controllers;

use app\components\CyberSource;
use app\components\TransactionLogDetailsUtil;
use app\components\Utils;
use app\models\Gateway;
use app\models\Transaction;
use app\models\TransactionLog;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class CybersourceController extends Controller
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

    /**
     * @return false|string
     */
    public function actionIndex($orderId)
    {
        $this->layout = false;

        if (\Yii::$app->request->isGet && !empty($orderId)) {
            $bankResponse = Transaction::findOne(['orderId' => $orderId]);
            if (isset($bankResponse->orderId)) {
                if ($bankResponse->status == Transaction::STATUS_CREATED) {
                    $cybersource = new CyberSource('CYBERSOURCE');
                    $request = [
                        'access_key' => $cybersource->getAccessKey(),
                        'profile_id' => $cybersource->getProfileId(),
                        'reference_number' => $_GET['orderId'],
                        'transaction_uuid' => uniqid(),
                        'signed_field_names' => 'access_key,profile_id,reference_number,transaction_uuid,signed_field_names,transaction_type,currency,unsigned_field_names,signed_date_time,locale,amount,bill_to_forename,bill_to_surname,bill_to_email,bill_to_address_city,bill_to_address_line1,bill_to_address_line2,bill_to_address_country,bill_to_address_postal_code,payer_authentication_specification_version',
                        'transaction_type' => 'sale',
                        'currency' => $bankResponse->relatedCurrency->code,
                        'unsigned_field_names' => '',
                        'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"),
                        'locale' => 'en',
                        'amount' => $bankResponse->amount,
                        'bill_to_forename' => 'Tech',
                        'bill_to_surname' => 'Info',
                        'bill_to_email' => 'salah_cse_mbstu@yahoo.com',
                        'bill_to_address_city' => 'Dhaka',
                        'bill_to_address_line1' => 'Tower, 4th Floor, House, Rangs Pearl',
                        'bill_to_address_line2' => '76 Rd 12',
                        'bill_to_address_country' => 'BD',
                        'bill_to_address_postal_code' => '1213',
                        'payer_authentication_specification_version' => '2.2.0'
                    ];

                    $requests = array_merge($request, ['signature' => CyberSource::sign($request, $cybersource->getSecret())]);
                    TransactionLogDetailsUtil::createLog(
                        [
                            'orderId' => $bankResponse->orderId,
                            'message' => 'Cybersource Order Creation request',
                            'request' => $requests
                        ]
                    );

                    return $this->render('/api/cybersource/index', [
                        'paymentUrl' => $cybersource->getPaymentUrl(),
                        'requests' => $requests
                    ]);
                }
            }
        }

        return json_encode(Utils::InvalidRequest());
    }
}
