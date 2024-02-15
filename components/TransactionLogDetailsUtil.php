<?php

namespace app\components;

use app\models\Transaction;
use app\models\TransactionLog;
use app\models\TransactionLogDetails;
use DateTime;
use DateTimeZone;
use Exception;
use Yii;

class TransactionLogDetailsUtil
{
    public static function createLog($data)
    {
        try {
            if (!isset($data['orderId'])) {
                return false;
            }

            $prefix = getenv('CACHE_REDIS_PREFIX');
            $redisKey = $prefix . 'TransactionLogDetails_' . $data['orderId'];
            $redisHelper = RedisHelper::getInstance();

            $fieldsToSet = ['orderId', 'bookingId', 'client', 'serviceType'];
            foreach ($fieldsToSet as $field) {
                if (isset($data[$field])) {
                    $redisHelper->hSet($redisKey, $field, $data[$field]);
                }
            }

            $payload = [];
            $payloadKeys = ['request', 'response', 'message'];
            foreach ($payloadKeys as $payloadKey) {
                if (isset($data[$payloadKey])) {
                    $payload[$payloadKey] = is_object($data[$payloadKey]) ? json_encode($data[$payloadKey]) : $data[$payloadKey];
                }
            }

            $now = DateTime::createFromFormat('U.u', microtime(true));
            $now->setTimezone(new DateTimeZone('Asia/Dhaka'));
            $date = $now->format("Y-m-d H:i:s.u");

            $redisHelper->hSet($redisKey, 'payload_' . $date, $payload);
        } catch (\Exception $e) {
            TransactionLog::createLog('Exception log exception', '', $e->getMessage() . '-' . $e->getFile() . '-' . $e->getLine());
        }
    }

    public static function getLog()
    {
        $prefix = getenv('CACHE_REDIS_PREFIX');
        $keyPrefix = $prefix . 'TransactionLogDetails_';
        $redisHelper = RedisHelper::getInstance();
        $keys = $redisHelper->keys($keyPrefix . '*'); // Get all keys that match the prefix

        $logData = [];

        foreach ($keys as $key) {
            $hashData = $redisHelper->hGetAll($key);
            $logData[$key] = $hashData;
        }

        return $logData;
    }

    public static function storeTransactionLogData()
    {
        try {
            $logDetails = TransactionLogDetailsUtil::getLog();

            if(empty($logDetails)) {
               return false;
            }

            foreach (array_chunk($logDetails, 10) as $chunk) {
                foreach ($chunk as $logDetail) {
                    $result = self::formatLogDetail($logDetail);
                    $fields = $result['fields'];
                    $payload = $result['payload'];

                    /**
                     * Ensure that 'orderId' exists in the fields array
                     */
                    if(!isset($fields['orderId'])) {
                        continue;
                    }

                    $orderId = $fields['orderId'];
                    $transactionLogDetail = TransactionLogDetails::findOne(['orderId' => $fields['orderId']]);

                    if(isset($transactionLogDetail->orderId)) {
                        /**
                         * If the transaction log detail exists, update the payload
                         */
                        $transactionLogDetail->payLoad = (object)array_merge((array)$transactionLogDetail->payLoad, (array)$payload);
                    } else {
                        /**
                         * If the transaction log detail doesn't exist, create a new one
                         */
                        $transactionLogDetail = new TransactionLogDetails();
                        $transactionLogDetail->orderId = $orderId;
                        $transactionLogDetail->payLoad = $payload;

                        if(isset($fields['bookingId']) && isset($fields['client']) && isset($fields['serviceType'])) {
                            /**
                             * If bookingId, client and serviceType exist in cache then use it
                             */
                            $transactionLogDetail->bookingId = $fields['bookingId'];
                            $transactionLogDetail->client = $fields['client'];
                            $transactionLogDetail->serviceType = $fields['serviceType'];
                        } else {
                            /**
                             * If bookingId, client and serviceType doesn't exist in cache then get it from transaction table
                             */
                            $transaction = Transaction::find()->where(['orderId' => $orderId])->one();
                            if(!empty($transaction)) {
                                $transactionLogDetail->bookingId = $transaction->bookingId;
                                $transactionLogDetail->client = $transaction->relatedClient->name;
                                $transactionLogDetail->serviceType = $transaction->serviceType;
                            }
                        }
                    }

                    if(!$transactionLogDetail->save()) {
                        TransactionLog::createLog('transactionLogDetail log create or update error', $orderId, $transactionLogDetail->getErrors());
                    }
                }
            }

            return self::clearTransactionLogData();
        } catch (Exception $exception) {
            TransactionLog::createLog('Exception log writing', '', $exception->getMessage() . '-' . $exception->getLine() . '-' . $exception->getFile());
        }
    }

    public static function formatLogDetail($logDetail): array
    {
        $fields = [];
        $payload = new \stdClass();
        for ($i = 0; $i < count($logDetail); $i += 2) {
            $key = $logDetail[$i];
            $value = $logDetail[$i + 1];
            $fields[$key] = str_replace('"', '', $value);
            if(strpos($key, 'payload') === 0) {
                $payload->$key = json_decode($value);
            }
        }

        return [
            'payload' => $payload,
            'fields' => $fields
        ];
    }

    public static function clearTransactionLogData()
    {
        try {
            $redisHelper = RedisHelper::getInstance();
            $prefix = getenv('CACHE_REDIS_PREFIX');
            $pattern = $prefix . 'TransactionLogDetails_';

            $keysMatchingPattern = $redisHelper->keys($pattern . '*');
            if(empty($keysMatchingPattern)) {
                return false;
            }

            foreach (array_chunk($keysMatchingPattern, 10) as $chunk) {
                foreach ($chunk as $key) {
                    $redisHelper->del($key);
                }
            }
        } catch (Exception $exception) {
            TransactionLog::createLog('Exception log writing', '', $exception->getMessage() . '-' . $exception->getLine() . '-' . $exception->getFile());
        }
    }
}