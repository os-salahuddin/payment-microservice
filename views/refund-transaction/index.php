<?php

use app\components\Utils;
use app\models\Gateway;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;
use yii\widgets\Pjax;

$this->title = 'Refunded Transaction';
$this->params['breadcrumbs'][] = $this->title;
Modal::begin([
    'header' => '<h4 style="margin:0; padding:0">Search</h4>',
    'id' => 'filter-search',
    'size' => 'modal-lg',
    'options' => [
        'id' => 'filter',
        'tabindex' => false
    ],
]);
echo $this->render('_search', ['model' => $searchModel]);
Modal::end();
?>
<div class="transaction-index">

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'panel' => [
            'type' => GridView::TYPE_DEFAULT,
            'heading' => $this->title . ' '
        ],
        'options' => ['style' => 'white-space:nowrap;'],
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn'],
            [
                'attribute' => 'Order ID',
                'value' => 'relatedTransaction.orderId'
            ],
            [
                'attribute' => 'Client',
                'value' => 'relatedTransaction.relatedClient.name'
            ],
            [
                'attribute' => 'Gateway',
                'value' => 'relatedTransaction.relatedGateway.name'
            ],
            [
                'attribute' => 'Bank',
                'value' => 'relatedTransaction.relatedBank.name'
            ],
            [
                'attribute' => 'Card Series',
                'value' => 'relatedTransaction.relatedCardSeries.series'
            ],
            [
                'attribute' => 'Service Type',
                'value' => 'relatedTransaction.serviceType'
            ],
            [
                'attribute' => 'transactionType',
                'value' => function ($searchModel) {
                    if ($searchModel->transactionType == 1)
                        return 'Refund';
                    elseif ($searchModel->transactionType == 2)
                        return 'Void';
                },
                'filter' => ['1' => 'Refund', '2' => 'Void']
            ],
            'bankStatus',
            [
                'attribute' => 'status',
                'value' => function ($searchModel) {
                    if ($searchModel->status == 1)
                        return 'Success';
                    elseif ($searchModel->status == 0)
                        return 'Failed';
                },
                'filter' => ['1' => 'Success', '0' => 'Failed']
            ],
            [
                'attribute' => 'Currency',
                'value' => 'relatedCurrency.code'
            ],
            'amount',
            'charge',
            [
                'attribute' => 'updatedAt',
                'value' => function ($searchModel) {
                    return Utils::getIntDateTime($searchModel->updatedAt);
                },
            ],

            [
                'class' => 'kartik\grid\ActionColumn',
                'contentOptions' => ['style' => 'width: 120px'],
                'template' => '{view}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        if (Yii::$app->asm->can('view')) {
                            $options = [
                                'title' => 'View',
                                'aria-label' => 'View',
                                'data-pjax' => '0',
                                'class' => 'btn btn-default btn-xs custom_button',
                                'data-toggle' => "modal",
                                'data-target' => "#refundedTransactionsModal",
                                'onclick' => "loadRefundedTransactionsData('" . $model->uid . "')"
                            ];
                            return Html::label('<span class="glyphicon glyphicon-eye-open"></span>', "", $options);
                        }
                    }
                ]
            ],
        ],
        'containerOptions' => ['style' => 'overflow: auto'], // only set when $responsive = false
        'toolbar' => [
            ['content' =>
                Html::button('<i class="glyphicon glyphicon-filter"></i>', [
                    'type' => 'button',
                    'data-toggle' => 'modal',
                    'data-target' => '#filter',
                    'title' => Yii::t('app', 'Filter'),
                    'class' => 'btn btn-default'
                ]) . ' ' .
                Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => Yii::t('app', 'Reset Grid')])],
            '{export}',
            '{toggleData}'
        ],
        'export' => [
            'fontAwesome' => true
        ],
        'exportConfig' => [
            kartik\grid\GridView::PDF => [
                'label' => 'Save as PDF',
                'filename' => 'Payment Manager',
                'config' => [
                    'options' => ['title' => 'Payment Manager'],
                    'methods' => [
                        'SetTitle' => ['Payment Manager'],
                        'SetHeader' => ['Refunded Transaction||Generated: ' . date("D, d-M-Y")],
                        'SetFooter' => ['Page {PAGENO}'],
                    ]
                ],
            ],
            kartik\grid\GridView::EXCEL => [
                'label' => 'Save as EXCEL',
                'filename' => 'Payment Manager',
            ],
        ],
        'pjax' => true,
        'bordered' => true,
        'striped' => true,
        'condensed' => false,
        'responsive' => false,
        'hover' => true,
        'showPageSummary' => false
    ]); ?>

    <?php Pjax::end(); ?>

    <style>.empty {
            text-align: center;
        }</style>

    <div class="modal fade" id="refundedTransactionsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary"
                     style="    border-top-right-radius: 5px; border-top-left-radius: 5px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Refunded Transaction</h4>
                </div>
                <div id="RefundedTransactionsLoading" style="text-align: center; padding: 10px; font-size: x-large;">
                    &nbsp; Loading...
                </div>
                <div id="RefundedTransactionsModalResponse"></div>
            </div>
        </div>
    </div>

</div>