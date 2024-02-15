<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use app\components\Utils;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\QueueSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Queues';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="queue-index">

<?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]);
    ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'panel' => [
            'type' => GridView::TYPE_DEFAULT,
            'heading' => $this->title. ' '
        ],
        'options'=>['style' => 'white-space:nowrap;'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'channel',
            'job',
            [
                'attribute' => 'pushed_at',
                'value' => function ($model) {
                    return (!empty($model->pushed_at)) ? Utils::getIntDateTime($model->pushed_at) : '';
                },
            ],
            'ttr',
            'delay',
            'priority',
            [
                'attribute' => 'reserved_at',
                'value' => function ($model) {
                    return (!empty($model->reserved_at)) ? Utils::getIntDateTime($model->reserved_at) : '';
                },
            ],
            'attempt',
            [
                'attribute' => 'done_at',
                'value' => function ($model) {
                    return (!empty($model->done_at)) ? Utils::getIntDateTime($model->done_at) : '';
                },
            ],
        ],
        'containerOptions' => ['style' => 'overflow: auto'],
        'toolbar' => [
            ['content' =>
            Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax' => 0, 'class' => 'btn btn-default',
                'title' => Yii::t('app', 'Reset Grid')])],
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

    <style>
        .empty{
            text-align: center;
        }
    </style>

</div>