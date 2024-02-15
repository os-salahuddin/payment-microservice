<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\QueueSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="queue-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'channel') ?>

    <?= $form->field($model, 'job') ?>

    <?= $form->field($model, 'pushed_at') ?>

    <?= $form->field($model, 'ttr') ?>

    <?php // echo $form->field($model, 'delay') ?>

    <?php // echo $form->field($model, 'priority') ?>

    <?php // echo $form->field($model, 'reserved_at') ?>

    <?php // echo $form->field($model, 'attempt') ?>

    <?php // echo $form->field($model, 'done_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
