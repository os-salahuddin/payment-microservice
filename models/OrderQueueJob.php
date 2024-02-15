<?php

namespace app\models;

use Yii;
use yii\queue\JobInterface;
use yii\queue\Queue;
use yii\base\BaseObject;

class OrderQueueJob extends BaseObject implements JobInterface
{
    public $orderId;

    public function execute($queue)
    {
        $isSuccess = Transaction::watcherWorker($this->orderId);
        if (!$isSuccess) {
            Yii::$app->watcher->on(Queue::EVENT_AFTER_EXEC, function ($event) {
                $queue = $event->sender;
                $job = $event->job;
                $queue->delay(getenv('WATCHER_DELAY'))->push($job);
            });
        }
    }
}