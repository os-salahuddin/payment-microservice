<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use app\models\Gateway;

/* @var $this yii\web\View */
/* @var $searchModel app\models\GatewaySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Gateways';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="gateway-index">
    <?php if (Yii::$app->asm->can('create')) {?>
    <p>
        <?= Html::a('Create Gateway', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php }?>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

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

            'uid',
            'name',
            'code',
            'charge',
            [
                'attribute' => 'currency',
                'value' => 'relatedCurrency.code'
            ],
            [
                'attribute' => 'logo',
                'format' => ['image', ['width' => '40', 'height' => '40']],
                'value' => 'relatedLogo.small'
            ],
            [
                'attribute' => 'gatewayMode',
                'value' => function ($searchModel) {
                    // Any Format you want
                    return $searchModel->gatewayMode == Gateway::STATUS_ACTIVE ? 'Live' : 'Test';
                },
                'filter' => ['0' => 'Test', '1' => 'Live']
            ],
            [
                'attribute' => 'status',
                'value' => function ($searchModel) {
                    // Any Format you want
                    return $searchModel->status == Gateway::STATUS_ACTIVE ? 'Active' : 'Inactive';
                },
                'filter' => ['1' => 'Active', '0' => 'Inactive']
            ],

            [
                'class' => 'kartik\grid\ActionColumn',
                'contentOptions' => ['style' => 'width: 120px'],
                'template' => '{view}{edit}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        if (Yii::$app->asm->can('view')) {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', ['view', 'id' => $model->uid], ['title' => Yii::t('app', 'Edit'),
                                'class' => 'btn btn-default btn-xs custom_button',
                                'data-pjax' => '0',
                            ]);
                        }
                    },
                    'edit' => function ($url, $model, $key) {
                        if (Yii::$app->asm->can('update')) {
                            return Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->uid], ['title' => Yii::t('app', 'Edit'),
                                'class' => 'btn btn-default btn-xs custom_button',
                                'data-pjax' => '0',
                            ]);
                        }
                    },
                    'delete' => function ($url, $model, $key) {
                        if (Yii::$app->asm->can('delete')) {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $key], [
                                'title' => Yii::t('app', 'Delete'),
                                'class' => 'btn btn-default btn-xs custom_button',
                                'data' => [
                                    'confirm' => 'Are you absolutely sure to delete this package?',
                                    'method' => 'post',
                                ],
                            ]);
                        }
                    }
                ]
            ],
        ],
        'containerOptions' => ['style' => 'overflow: auto'], // only set when $responsive = false
        'toolbar' => [
            ['content' =>
                Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => Yii::t('app', 'Reset Grid')])
            ],
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
