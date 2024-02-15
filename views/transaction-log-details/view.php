<?php

use yii\helpers\Html;
use yii\web\YiiAsset;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\TransactionLogDetails */

$this->title = 'Transaction Log Details';
$this->params['breadcrumbs'][] = ['label' => 'Transaction Log Details', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);
?>
<div class="transaction-log-details-view">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h2 class="panel-title"><?= $this->title ?></h2>
        </div>
        <div class="panel-body">

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'orderId',
                    'bookingId',
                    'client',
                    'serviceType'
                ],
            ]) ?>

            <?= DetailView::widget([
                'model' => $transaction,
                'attributes' => [
                    [
                        'attribute' => 'status',
                        'label' => 'Status',
                        'value' => function($transaction) {
                            switch ($transaction->status) {
                                case 0:
                                    return 'Canceled';
                                    break;
                                case 1:
                                    return 'Created';
                                    break;
                                case 2:
                                    return 'Paid';
                                    break;
                                case 3:
                                    return 'Timeout';
                                    break;
                                case 4:
                                    return 'Declined';
                                    break;
                                case 5:
                                    return 'Refund'; 
                                    break;
                                case 6:
                                    return 'Void';                
                                    break;
                                default:
                                    break;
                            }
                        }
                    ]
                ],
            ]) ?>

        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h2 class="panel-title">Payload</h2>
        </div>


        <div class="panel-body">
            <?php if (!empty($model->payLoad)) : ?>
                <table class="payload table table-responsive table-bordered text-center">
                    <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Request</th>
                        <th>Response</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $data = $model->payLoad;
                    foreach ($data as $timestamp => $payload) { ?>
                            <tr>
                                <td style="width: 20%">
                                    <?= $payload['message'] ?>
                                    <br/>
                                    <br/>
                                    <?php $timestamp = str_replace('payload_', '', $timestamp); ?>
                                    <?php
                                    $microseconds = substr($timestamp, -6);?>
                                    <?= date('H:i:s\s', strtotime($timestamp)) . ' ' . $microseconds .'ms'; ?><br/>
                                    <?= date('F j, Y', strtotime($timestamp)); ?>
                                </td>
                                <td style="width: 40%; text-align: left">
                                    <div class="form-group">
                                        <?= isset($payload['request']) ? '<pre><code>' . json_encode($payload['request'], JSON_PRETTY_PRINT) . '</code></pre>': null ?>
                                    </div>
                                </td>
                                <td style="width: 40%; text-align: left">
                                    <div class="form-group">
                                        <?= isset($payload['response']) ? '<pre><code>' . json_encode($payload['response'], JSON_PRETTY_PRINT) . '</code></pre>' : null ?>
                                    </div>
                                </td>
                            </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php else : ?>
                <h3 class="text-center">not payload</h3>
            <?php endif ?>
        </div>

    </div>
</div>
