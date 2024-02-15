<?php

namespace app\components;

use app\models\IPNQueueJob;
use app\models\PanUpdateQueueJob;
use app\models\Transaction;
use Yii;

class CybersourceUtil
{
    /**
     * This function handles transaction update for success, decline or cancel transaction
     *
     * @param $transaction
     * @param $response
     * @return bool
     */
    public static function updateTransaction(&$transaction, &$response): bool
    {
        $transaction->amount = $response['req_amount'];
        $transaction->bankResponse = json_encode($response);
        $transaction->bankOrderStatus = $response['decision'];
        $transaction->bankResponseDescription = $response['message'];
        /**
         * checks from checkAcceptanceCriteriaForPaid() to see if paid Then transaction updated as paid
         */
        if (self::checkAcceptanceCriteriaForPaid($response)) {
            $transaction->type = $response['req_transaction_type'];
            if(isset($response['auth_reconciliation_reference_number'])) {
                $transaction->rrn = $response['auth_reconciliation_reference_number'];
            }
            $transaction->bankTransactionDate = $response['auth_time'];
            $transaction->bankMerchantTranId = $response['transaction_id'];
            $transaction->bankResponseCode = $response['reason_code'];
            $transaction->cardBrand = $response['card_type_name'];
            $transaction->bankApprovalCode = $response['auth_code'];

            $pattern = '/^\d{6}/';
            if(isset($response['req_card_number']) && preg_match($pattern, $response['req_card_number'], $matches)) {
                $transaction->pan = $response['req_card_number'];
            }
            $transaction->status = Transaction::STATUS_PAID;
        }
        /**
         * it checks if descision == ERROR or  descision == DECLINE. Then transaction updated as Declined
         */
        else if (isset($response['decision']) && in_array($response['decision'], ['ERROR', 'DECLINE'])) {
            $transaction->status = Transaction::STATUS_DECLINED;
        }
        /**
         * if user clicks on CANCEL from the gateway page, then transaction updated as Canceled
         */
        else if (isset($response['decision']) && $response['decision'] == 'CANCEL') {
            $transaction->status = Transaction::STATUS_CANCELLED;
        }

        if (!$transaction->save()) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $response->reference,
                    'message' => 'cybersource update transaction store error',
                    'response' => $transaction->getErrors()
                ]
            );
            return false;
        }

        /**
         * For PAID transaction Calling PanQueueJob queue to update pan and ipn hit with a 10 seconds delay.
         * The job is responsible for updating the PAN number from the transactionDetails API.
         */
        if(isset($response['decision']) && $response['decision'] == 'ACCEPT' && empty($transaction->pan)) {
            Yii::$app->panUpdate->delay(getenv('PAN_UPDATE_DELAY'))->push(new PanUpdateQueueJob(['orderId' => $transaction->orderId, 'transactionId' => $transaction->bankMerchantTranId]));
        } else {
            if ($transaction->notify === Transaction::NOTIFICATION_NOT_SEND) {
                //@todo need to push to ipn queue for both
                if(Utils::getEnvironment() == 'DEV') {
                    Transaction::sendIpnRequest($transaction->orderId);
                } else {
                    Transaction::pushToIpnQueue($transaction);
                }
            }
        }

        return true;
    }

    public static function checkAcceptanceCriteriaForPaid($response)
    {
        /**
        reason_code = 100
        & decision = ACCEPT
        & auth_response = 00
        & auth_code not NULL
        & auth_reconciliation_reference_number not NULL
         */

        $isPaid = false;

        if(
            isset($response['decision']) && $response['decision'] == 'ACCEPT' &&
            isset($response['reason_code']) && $response['reason_code'] == '100' &&
            isset($response['auth_response']) && $response['auth_response'] == '00' &&
            !empty($response['auth_code']) &&
            (
                (Utils::getEnvironment() == 'PROD' && !empty($response['auth_reconciliation_reference_number'])) ||
                (Utils::getEnvironment() == 'DEV')
            )
        ) {
            $isPaid = true;
        }

        return $isPaid;
    }
}