<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TransactionLogDetailsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Transaction Log Details';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="transaction-log-details-index">
    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'panel' => [
            'type' => GridView::TYPE_DEFAULT,
            'heading' => $this->title . ' '
        ],
        'options' => ['style' => 'white-space:nowrap;'],
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn'],
            'orderId',
            'bookingId',
            'client',
            'serviceType',
            [
                'class' => 'kartik\grid\ActionColumn',
                'contentOptions' => ['style' => 'width: 120px'],
                'template' => '{view}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                       // if (Yii::$app->asm->can('view')) {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', ['view', 'id' => $model->id], [
                                'title' => Yii::t('app', 'View'),
                                'class' => 'btn btn-default btn-xs custom_button',
                                'data-pjax' => '0',
                            ]);
                       // }
                    },
                ]
            ],
        ],
        'containerOptions' => ['style' => 'overflow: auto'],
        'toolbar' => [
            ['content' =>
                Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => Yii::t('app', 'Reset Grid')])],
                Html::a('<i class="glyphicon glyphicon-refresh"></i>', ['index?refresh=true'], ['data-pjax' => 0, 'class' => 'btn btn-primary', 'title' => 'Fetch latest log from redis into database']),
            '{export}',
            '{toggleData}'
        ],
        'export' => [
            'fontAwesome' => true
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
</div>
