<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\GatewaySearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="gateway-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'uid') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'code') ?>

    <?= $form->field($model, 'charge') ?>

    <?php // echo $form->field($model, 'sandboxUrl') ?>

    <?php // echo $form->field($model, 'liveUrl') ?>

    <?php // echo $form->field($model, 'merchant') ?>

    <?php // echo $form->field($model, 'merchantPassword') ?>

    <?php // echo $form->field($model, 'currency') ?>

    <?php // echo $form->field($model, 'logo') ?>

    <?php // echo $form->field($model, 'extraParams') ?>

    <?php // echo $form->field($model, 'active') ?>

    <?php // echo $form->field($model, 'createdAt') ?>

    <?php // echo $form->field($model, 'updatedAt') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
