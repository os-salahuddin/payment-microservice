<?php

namespace app\models;

use yii\base\BaseObject;
use yii\queue\JobInterface;

class IPNQueueJob extends BaseObject implements JobInterface
{
    public $orderId;

    public function execute($queue)
    {
        Transaction::sendIpnRequest($this->orderId);
    }
}