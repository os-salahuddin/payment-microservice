<?php

namespace app\components;

use kartik\export\ExportMenu;
use Yii;
use DateTime;
use DOMDocument;
use DateTimeZone;
use app\models\Bank;
use app\models\Client;
use yii\base\Exception;
use app\models\Gateway;
use app\models\Service;
use yii\helpers\VarDumper;
use app\models\CardSeries;
use app\models\ClientsBanks;
use app\models\TransactionLog;
use app\models\ClientServiceBanks;

class Utils
{
    public static function getOrderId(): string
    {
        return strtoupper(uniqid());
    }

    public static function getIntDateTime($timestamp)
    {
        date_default_timezone_set('Asia/Dhaka');
        return date("Y-m-d h:i:s", $timestamp);
    }

    public static function timestampToDateTimeTransaction($timestamp, $dateOnly = false)
    {
        date_default_timezone_set('Asia/Dhaka');

        if($dateOnly) {
            return date("Y-m-d", $timestamp);
        }
        return date("Y-m-d H:i:s", $timestamp);
    }

    public static function convertToTimestamp($dateTime)
    {
        return strtotime($dateTime);
    }

    /**
     * @param $dateTime
     * @return string
     * @throws \Exception
     */
    public static function getDateTime($dateTime): string
    {
        $datetime = new DateTime($dateTime);
        $datetime->setTimezone(new DateTimeZone('Asia/Dhaka'));
        return $datetime->format("d-M-Y h:i:s");
    }

    public static function InvalidRequest(): array
    {
        return [
            'success' => false,
            'code' => 405,
            'message' => 'Method Not Allowed',
            'data' => null
        ];
    }

    public static function InvalidToken(): array
    {
        return [
            'success' => false,
            'code' => 401,
            'message' => 'Unauthorized',
            'data' => null
        ];
    }

    public static function SomethingWrong()
    {
        return json_encode([
            'success' => false,
            'code' => 500,
            'message' => 'Something went wrong!'
        ]);
    }
    public static function getUserIP()
    {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '3.1.159.247';

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }
}