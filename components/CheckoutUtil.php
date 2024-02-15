<?php

namespace app\components;

use app\models\IPNQueueJob;
use app\models\Transaction;
use app\models\TransactionLog;
use Yii;

class CheckoutUtil
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
        /**
         * if User clicks on cancel from gateway page. Then transaction is updated as Canceled
         * Otherwise handle case for Success or Declined
         */
        if (array_key_exists('ref', $response) && $response['ref'] == 'cancel') {
            $transaction->status = Transaction::STATUS_CANCELLED;
        } else {
            $transaction->bankResponse = json_encode($response);
            $transaction->bankOrderStatus = $response->status;
            $transaction->bankTransactionDate = $response->requested_on;
            $transaction->cardHolderName = $response->source->name;
            $transaction->cardBrand = $response->source->scheme;

            if (isset($response->source->bin) && isset($response->source->last4)) {
                $transaction->pan = $response->source->bin . 'xxxxxx' . $response->source->last4;
            }

            if (isset($response->processing->retrieval_reference_number)) {
                $transaction->rrn = $response->processing->retrieval_reference_number;
            }
            /**
             * if status == Captured && approve === true. Then transaction updated as paid
             */
            if ($response->status == 'Captured' && $response->approved === true) {
                if (isset($response->processing->partner_authorization_code)) {
                    $transaction->bankApprovalCode = $response->processing->partner_authorization_code;
                }

                if (isset($response->processing->partner_authorization_response_code)) {
                    $transaction->bankResponseCode = $response->processing->partner_authorization_response_code;
                }

                $transaction->status = Transaction::STATUS_PAID;
            }
            /**
             * if status == Declined && approve === false. Then transaction updated as Declined
             */
            elseif ($response->status == 'Declined' && $response->approved === false) {
                $transaction->status = Transaction::STATUS_DECLINED;
            }
        }

        if (!$transaction->save()) {
            TransactionLogDetailsUtil::createLog(
                [
                    'orderId' => $response->reference,
                    'message' => 'checkout update transaction store error',
                    'response' => $transaction->getErrors()
                ]
            );
            return false;
        }

        Transaction::pushToIpnQueue($transaction);

        return true;
    }
}