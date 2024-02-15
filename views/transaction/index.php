<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use app\models\Gateway;
use yii\bootstrap\Modal;
use app\components\Utils;
use kartik\grid\GridView;
use app\models\Transaction;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TransactionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Transactions';
$this->params['breadcrumbs'][] = $this->title;

Modal::begin([
    'header' => '<h4 style="margin:0; padding:0">Search</h4>',
    'id' => 'filter-search',
    'size' => 'modal-lg',
    'options' => [
        'id' => 'filter',
        'tabindex' => false // important for Select2 to work properly
    ],
]);

echo $this->render('_search', ['model' => $searchModel]);

Modal::end();

if (!Yii::$app->asm->canController('transaction', 'notify-all')) { ?>
    <style>
        .btn.btn-info.notify-all-class {
            display: none;
        }
    </style>
<?php }
?>
<div class="transaction-index">
    <?php $column = [
        ['class' => 'kartik\grid\SerialColumn'],
        [
            'attribute' => 'orderId',
            'label' => 'OrderID'
        ],
        'bookingId',
        'rrn',
        [
            'attribute' => 'bankApprovalCode',
            'label' => 'BAC'
        ],
        'customerId',
        'customerName',
        [
            'attribute' => 'clientId',
            'value' => 'relatedClient.name'
        ],
        [
            'attribute' => 'gateway',
            'value' => 'relatedGateway.name'
        ],
        [
            'attribute' => 'bank',
            'value' => 'relatedBank.name'
        ],
        [
            'attribute' => 'cardSeries',
            'value' => 'relatedCardSeries.series'
        ],
        'pan',
        'serviceType',
        [
            'attribute' => 'status',
            'value' => function ($searchModel) {
                if ($searchModel->status == Transaction::STATUS_CANCELLED)
                    return 'Canceled';
                elseif ($searchModel->status == Transaction::STATUS_CREATED)
                    return 'Created';
                elseif ($searchModel->status == Transaction::STATUS_PAID)
                    return 'Paid';
                elseif ($searchModel->status == Transaction::STATUS_TIMEOUT)
                    return 'Timeout';
                elseif ($searchModel->status == Transaction::STATUS_DECLINED)
                    return 'Declined';
                elseif ($searchModel->status == Transaction::STATUS_REFUND)
                    return 'Refunded';
                elseif ($searchModel->status == Transaction::STATUS_VOID)
                    return 'Void';
                else
                    return 'Pending';
            }
        ],
        [
            'attribute' => 'currency',
            'value' => 'relatedCurrency.code',
            'hAlign' => 'right',
            'pageSummary' => 'Total'
        ],
        [
            'attribute' => 'amount',
            'format' => 'decimal',
            'hAlign' => 'right',
            'pageSummary' => true,
        ],
        [
            'attribute' => 'createdAt',
            'value' => function ($searchModel) {
                return Utils::timestampToDateTimeTransaction($searchModel->createdAt);
            },
        ],
        [
            'class' => 'kartik\grid\ActionColumn',
            'contentOptions' => ['style' => 'width: 120px'],
            'template' => '{view}{refund}{void}{disburse}{ipn}',
            'buttons' => [
                'view' => function ($url, $model) {
                    if (Yii::$app->asm->can('view')) {
                        $options = [
                            'title' => 'View',
                            'aria-label' => 'View',
                            'data-pjax' => '0',
                            'class' => 'btn btn-default btn-xs custom_button',
                            'data-toggle' => "modal",
                            'data-target' => "#transactionsModal",
                            'onclick' => "loadTransactionsData('" . $model->uid . "')"
                        ];
                        return Html::label('<span class="glyphicon glyphicon-eye-open"></span>', "", $options);
                    }
                    return false;
                },
                'refund' => function ($url, $model) {
                    if (Yii::$app->asm->canController('refund', 'city-refund')) {
                        if ($model->relatedGateway->code == Gateway::GATEWAY_CBL && ($model->status == Transaction::STATUS_PAID)) {
                            if (isset($model->callbackUrl) && !empty($model->callbackUrl)) {
                                if (isset($model->relatedRefund)) {
                                    if ($model->relatedRefund->status == 0) {

                                        $options = [
                                            'title' => $model->relatedGateway->name,
                                            'aria-label' => 'Refund',
                                            'data-pjax' => '0',
                                            'class' => 'btn btn-default btn-xs custom_button',
                                            'data-toggle' => "modal",
                                            'data-target' => "#RefundModal",
                                            'data-action' => '/refund/city-refund',
                                            'data-id' => $model->orderId,
                                            'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                        ];
                                        return Html::label('Refund', "", $options);

                                    }
                                } else {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/city-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'brac-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_BRAC) && ($model->status == Transaction::STATUS_PAID)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/brac-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/brac-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'bkash-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_BKASH || $model->relatedGateway->code == Gateway::GATEWAY_BKASH_ST)
                            && ($model->status == Transaction::STATUS_PAID)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/bkash-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/bkash-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'deshi-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_DESHI_PAY || $model->relatedGateway->code == Gateway::GATEWAY_DESHI_PAY_B2C)
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/deshi-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/deshi-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'nagad-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_NAGAD)
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/nagad-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/nagad-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'cybersource-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_BRAC_CYBERSOURCE)
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/cybersource-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/cybersource-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'tazapay-refund')) {
                        if ($model->relatedGateway->code == Gateway::GATEWAY_TAZAPAY
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/tazapay-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/tazapay-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'ebl-token-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_EBL_TOKENIZATION)
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/ebl-token-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/ebl-token-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'sslcommerz-refund')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_SSLCOMMERZ)
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/sslcommerz-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/sslcommerz-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'sbl-refund')) {
                        if ($model->relatedGateway->code == Gateway::GATEWAY_SBL
                            && ($model->status == Transaction::STATUS_PAID)) {

                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Refund',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#RefundModal",
                                        'data-action' => '/refund/sbl-refund',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Refund', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Refund',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#RefundModal",
                                    'data-action' => '/refund/sbl-refund',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#refundOrderId').val($(this).data('id')); $('#refundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Refund', "", $options);
                            }
                        }
                    }
                    return false;
                },
                'void' => function ($url, $model) {
                    if (Yii::$app->asm->canController('refund', 'brac-void')) {
                        if ($model->relatedGateway->code == Gateway::GATEWAY_BRAC
                            && ($model->status == Transaction::STATUS_PAID)
                            && Transaction::voidAbleTime($model->createdAt)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    return Html::a('Void', ['refund/brac-void', 'orderId' => $model->orderId], [
                                        'title' => $model->relatedGateway->name,
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-pjax' => '0',
                                        'data-confirm' => 'Are you sure you want to void this transaction?'
                                    ]);
                                }
                            } else {
                                return Html::a('Void', ['refund/brac-void', 'orderId' => $model->orderId], [
                                    'title' => $model->relatedGateway->name,
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-pjax' => '0',
                                    'data-confirm' => 'Are you sure you want to void this transaction?'
                                ]);
                            }
                        }
                    }

                    if (Yii::$app->asm->canController('refund', 'cybersource-void')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_BRAC_CYBERSOURCE) &&
                            !empty($model->bankMerchantTranId) && ($model->status == Transaction::STATUS_PAID) &&
                            Transaction::voidAbleTime($model->createdAt)
                        ) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    return Html::a('Void', ['refund/cybersource-void', 'orderId' => $model->orderId], [
                                        'title' => $model->relatedGateway->name,
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-pjax' => '0',
                                        'data-confirm' => 'Are you sure you want to void this transaction?'
                                    ]);
                                }
                            } else {
                                return Html::a('Void', ['refund/cybersource-void', 'orderId' => $model->orderId], [
                                    'title' => $model->relatedGateway->name,
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-pjax' => '0',
                                    'data-confirm' => 'Are you sure you want to void this transaction?'
                                ]);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'ebl-void')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_EBL ||
                                $model->relatedGateway->code == Gateway::GATEWAY_EBL_USD) &&
                            !empty($model->bankMerchantTranId) && ($model->status == Transaction::STATUS_PAID) &&
                            Transaction::voidAbleTime($model->createdAt)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    return Html::a('Void', ['refund/ebl-void', 'orderId' => $model->orderId], [
                                        'title' => $model->relatedGateway->name,
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-pjax' => '0',
                                        'data-confirm' => 'Are you sure you want to void this transaction?'
                                    ]);
                                }
                            } else {
                                return Html::a('Void', ['refund/ebl-void', 'orderId' => $model->orderId], [
                                    'title' => $model->relatedGateway->name,
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-pjax' => '0',
                                    'data-confirm' => 'Are you sure you want to void this transaction?'
                                ]);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'ebl-token-void')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_EBL_TOKENIZATION ||
                                $model->relatedGateway->code == Gateway::GATEWAY_EBL_TOKENIZATION_USD) &&
                            !empty($model->bankMerchantTranId) && ($model->status == Transaction::STATUS_PAID) &&
                            Transaction::voidAbleTime($model->createdAt)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    return Html::a('Void', ['refund/ebl-token-void', 'orderId' => $model->orderId], [
                                        'title' => $model->relatedGateway->name,
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-pjax' => '0',
                                        'data-confirm' => 'Are you sure you want to void this transaction?'
                                    ]);
                                }
                            } else {
                                return Html::a('Void', ['refund/ebl-token-void', 'orderId' => $model->orderId], [
                                    'title' => $model->relatedGateway->name,
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-pjax' => '0',
                                    'data-confirm' => 'Are you sure you want to void this transaction?'
                                ]);
                            }
                        }
                    }
                    if (Yii::$app->asm->canController('refund', 'sbl-void')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_SBL) &&
                            !empty($model->bankMerchantTranId) && ($model->status == Transaction::STATUS_PAID) &&
                            Transaction::voidAbleTime($model->createdAt)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    return Html::a('Void', ['refund/sbl-void', 'orderId' => $model->orderId], [
                                        'title' => $model->relatedGateway->name,
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-pjax' => '0',
                                        'data-confirm' => 'Are you sure you want to void this transaction?'
                                    ]);
                                }
                            } else {
                                return Html::a('Void', ['refund/sbl-void', 'orderId' => $model->orderId], [
                                    'title' => $model->relatedGateway->name,
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-pjax' => '0',
                                    'data-confirm' => 'Are you sure you want to void this transaction?'
                                ]);
                            }
                        }
                    }
                    return false;
                },
                'disburse' => function ($url, $model) {
                    if (Yii::$app->asm->canController('refund', 'bkash-disbursement')) {
                        if (($model->relatedGateway->code == Gateway::GATEWAY_BKASH || $model->relatedGateway->code == Gateway::GATEWAY_BKASH_ST)
                            && ($model->status == Transaction::STATUS_PAID)) {
                            if (isset($model->relatedRefund)) {
                                if ($model->relatedRefund->status == 0) {
                                    $options = [
                                        'title' => $model->relatedGateway->name,
                                        'aria-label' => 'Disburse',
                                        'data-pjax' => '0',
                                        'class' => 'btn btn-default btn-xs custom_button',
                                        'data-toggle' => "modal",
                                        'data-target' => "#BkashRefundModal",
                                        'data-action' => '/refund/bkash-disbursement',
                                        'data-id' => $model->orderId,
                                        'onclick' => "$('#bkashRefundOrderId').val($(this).data('id')); $('#bkashRefundForm').attr('action',$(this).data('action'));"
                                    ];
                                    return Html::label('Disburse', "", $options);
                                }
                            } else {
                                $options = [
                                    'title' => $model->relatedGateway->name,
                                    'aria-label' => 'Disburse',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default btn-xs custom_button',
                                    'data-toggle' => "modal",
                                    'data-target' => "#BkashRefundModal",
                                    'data-action' => '/refund/bkash-disbursement',
                                    'data-id' => $model->orderId,
                                    'onclick' => "$('#bkashRefundOrderId').val($(this).data('id')); $('#bkashRefundForm').attr('action',$(this).data('action'));"
                                ];
                                return Html::label('Disburse', "", $options);
                            }
                        }
                    }
                    return false;
                },
                'ipn' => function ($url, $model) {
                    if (Yii::$app->asm->canController('api', 'post-ipn')) {
                        return Html::a('IPN', ['api/post-ipn', 'OrderId' => $model->orderId], [
                            'title' => 'IPN',
                            'class' => 'btn btn-default btn-xs custom_button',
                            'data-pjax' => '0',
                        ]);
                    }
                    return false;
                },
            ]
        ],
    ]; ?>
    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'showPageSummary' => true,
        'panel' => [
            'type' => GridView::TYPE_DEFAULT,
            'heading' => $this->title . ' '
        ],
        'options' => ['style' => 'white-space:nowrap;'],
        'columns' => $column,
        'showFooter' => true,
        'containerOptions' => ['style' => 'overflow: auto'], // only set when $responsive = false
        'toolbar' => [
            ['content' =>
                Html::a('<i class="glyphicon glyphicon-send"></i>',
                    ['notify-all'],
                    ['type' => 'button',
                        'title' => 'Send all Unsent Notification',
                        'class' => 'btn btn-info notify-all-class',
                        'data' => [
                            'confirm' => Yii::t('app', 'Are you sure you want to send all Unsent Notification?')
                        ],
                    ]) . ' ' .
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
        'export' => Utils::getExport($dataProvider, $column),
        'exportConfig' => [
            kartik\grid\GridView::PDF => [
                'label' => 'Save as PDF',
                'filename' => 'Payment Manager',
                'config' => [
                    'format' => 'A4',
                    'orientation' => 'L',
                    'cssInline' => '.kv-wrap{padding:16px}',
                    'options' => ['title' => 'Payment Manager'],
                    'methods' => [
                        'SetTitle' => ['Payment Manager'],
                        'SetHeader' => ['Transactions||Generated: ' . date("D, d-M-Y")],
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
    ]); ?>

    <?php Pjax::end(); ?>

    <style>
        .empty {
            text-align: center;
        }

        .btn-warning {
            background-color: #235fd8;
            border-color: #235fd8;
        }

        .bootstrap-dialog.type-warning .modal-header {
            background-color: #235fd8;
        }

        .bootstrap-dialog .bootstrap-dialog-message {
            font-size: 14px;
            text-align: center;
            text-transform: uppercase;
        }
    </style>

    <div class="modal fade" id="transactionsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" style="width: 1100px !important;" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary"
                     style="    border-top-right-radius: 5px; border-top-left-radius: 5px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Transaction</h4>
                </div>
                <div id="TransactionsLoading" style="text-align: center; padding: 10px; font-size: x-large;"> &nbsp;
                    Loading...
                </div>
                <div id="TransactionsModalResponse"></div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="RefundModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary"
                     style="border-top-right-radius: 5px; border-top-left-radius: 5px; background-color: #235fd8;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Refund/Void Confirmation</h4>
                </div>
                <form action="" method="get" id="refundForm">
                    <div class="modal-body">
                        <div class="modal-message"
                             style="font-size: 14px;text-align: center;text-transform: uppercase;">Are you sure you want
                            to refund this transaction?
                        </div>
                        <br>
                        <input type="hidden" name="orderId" id="refundOrderId" value=""/>

                        <div class="form-group row">
                            <div class="col-md-2"></div>
                            <label for="refundAmount" class="col-sm-3 col-form-label" style="padding-top: 5px;">Refund
                                Amount</label>
                            <div class="col-sm-5">
                                <input type="number" class="form-control" id="refundAmount" name="refundAmount"
                                       autocomplete="off">
                            </div>
                            <div class="col-md-2"></div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-2"></div>
                            <label for="refundDescription" class="col-sm-3 col-form-label" style="padding-top: 5px;">Reason</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="refundDescription" name="refundDescription"
                                       autocomplete="off">
                            </div>
                            <div class="col-md-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-default" data-dismiss="modal">
                            <span class="glyphicon glyphicon-ban-circle"></span> Cancel
                        </button>
                        <button class="btn" style="background-color: #2661d7;color: white">
                            <span class="glyphicon glyphicon-ok"></span> Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="BkashRefundModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary"
                     style="border-top-right-radius: 5px; border-top-left-radius: 5px; background-color: #235fd8;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Disbursement Confirmation</h4>
                </div>
                <form action="" method="get" id="bkashRefundForm">
                    <div class="modal-body">
                        <div class="modal-message"
                             style="font-size: 14px;text-align: center;text-transform: uppercase;">Are you sure you want
                            to Disburse amount?
                        </div>
                        <br>
                        <input type="hidden" name="bkashRefundOrderId" id="bkashRefundOrderId" value=""/>

                        <div class="form-group row">
                            <div class="col-md-2"></div>
                            <label for="refundAmount" class="col-sm-3 col-form-label"
                                   style="padding-top: 5px;">Amount</label>
                            <div class="col-sm-5">
                                <input type="number" class="form-control" id="bkashRefundAmount"
                                       name="bkashRefundAmount"
                                       autocomplete="off">
                            </div>
                            <div class="col-md-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-default" data-dismiss="modal"><span
                                    class="glyphicon glyphicon-ban-circle"></span> Cancel
                        </button>
                        <button class="btn" style="background-color: #2661d7;color: white">
                            <span class="glyphicon glyphicon-ok"></span> Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>