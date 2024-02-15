<?php

namespace app\components;

use app\models\Service;
use app\models\Transaction;
use app\models\TransactionLog;
use DateTime;
use DateTimeZone;

class RedirectUtil
{
    public static function getSuccessUrl(Transaction $transaction): string
    {
        $extraParams = json_decode($transaction->extraParams);
        if ((isset($extraParams->successUrl)) && ($extraParams->successUrl != '')) {
            $successUrl = $extraParams->successUrl;
        } else {
            $serviceDetails = Service::findOne(['client' => $transaction->clientId, 'name' => $transaction->serviceType]);
            if ((isset($serviceDetails->successUrl)) && ($serviceDetails->successUrl != '')) {
                $successUrl = $serviceDetails->successUrl;
            }
            else {
                $successUrl = $transaction->relatedClient->successUrl;
            }
        }

        if (parse_url($successUrl, PHP_URL_QUERY)) {
            $successUrl .= '&amount=' . $transaction->amount .
                '&serviceType=' . $transaction->serviceType .
                '&order_id=' . $transaction->bookingId .
                '&currency=' . $transaction->relatedCurrency->code;
        } else {
            $successUrl .= '?amount=' . $transaction->amount .
                '&serviceType=' . $transaction->serviceType .
                '&order_id=' . $transaction->bookingId .
                '&currency=' . $transaction->relatedCurrency->code;
        }

        if(!empty($transaction->name)) {
            $successUrl .= '&name=' . $transaction->name;
        }

        if(!empty($transaction->email)) {
            $successUrl .= '&email=' . $transaction->email;
        }

        if(!empty($transaction->phone)) {
            $successUrl .= '&phone=' . $transaction->phone;
        }

        if(!empty($extraParams->extraParams)) {
            $successUrl .= '&' . http_build_query((array)$extraParams->extraParams);
        }

        return $successUrl;
    }

    public static function getCancelUrl(Transaction $transaction):string
    {
        $extraParams = json_decode($transaction->extraParams);
        if ((isset($extraParams->cancelUrl)) && ($extraParams->cancelUrl != '')) {
            $cancelUrl = $extraParams->cancelUrl;
        } else {
            $serviceDetails = Service::findOne(['client' => $transaction->clientId, 'name' => $transaction->serviceType]);
            if ((isset($serviceDetails->cancelUrl)) && ($serviceDetails->cancelUrl != '')) {
                $cancelUrl = $serviceDetails->cancelUrl;
            }
            else if (isset($transaction->clientId) && !empty($transaction->clientId)) {
                $cancelUrl = $transaction->relatedClient->cancelUrl;
            }
        }

        if (parse_url($cancelUrl, PHP_URL_QUERY)) {
            $cancelUrl .= '&' . http_build_query((array)$extraParams->extraParams);
        } else {
            $cancelUrl .= '?' . http_build_query((array)$extraParams->extraParams);
        }

        return $cancelUrl;
    }

    public static function getDeclineUrl(Transaction $transaction):string
    {
        $extraParams = json_decode($transaction->extraParams);

        if ((isset($extraParams->declineUrl)) && ($extraParams->declineUrl != '')) {
            $declineUrl = $extraParams->declineUrl;
        } else {
            $serviceDetails = Service::findOne(['client' => $transaction->clientId, 'name' => $transaction->serviceType]);
            if ((isset($serviceDetails->declineUrl)) && ($serviceDetails->declineUrl != '')) {
                $declineUrl = $serviceDetails->declineUrl;
            }
            else if (isset($transaction->clientId) && !empty($transaction->clientId)) {
                $declineUrl = $transaction->relatedClient->declineUrl;
            }
        }

        if (parse_url($declineUrl, PHP_URL_QUERY)) {
            $declineUrl .= '&' . http_build_query((array)$extraParams->extraParams);
        } else {
            $declineUrl .= '?' . http_build_query((array)$extraParams->extraParams);
        }

        return $declineUrl;
    }
}