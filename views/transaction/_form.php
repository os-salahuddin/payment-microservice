<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Transaction */
/* @var $form yii\widgets\ActiveForm */
/* @var $title */
?>


<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title"><?= $title ?></h2>
    </div>
    <div class="panel-body">
        <div class="transaction-form">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'uid')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'amount')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

            <?= $form->field($model, 'bookingId')->textInput() ?>

            <?= $form->field($model, 'requestId')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'orderId')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'sessionId')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankRequestUrl')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankResponse')->textarea(['rows' => 6]) ?>

            <?= $form->field($model, 'type')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'rrn')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'pan')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankTransactionDate')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankResponseCode')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankResponseDescription')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'cardHolderName')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'cardBrand')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankOrderStatus')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankApprovalCode')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankApprovalCodeScr')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'acqFee')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankMerchantTranId')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankOrderStatusScr')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankThreeDsvVerification')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bankThreeDssStatus')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'sessionVersion')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'resultIndicator')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'status')->textInput() ?>

            <?= $form->field($model, 'notify')->textInput() ?>

            <?= $form->field($model, 'callbackUrl')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'currency')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'gateway')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'bin')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'service')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'createdAt')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'updatedAt')->textInput(['maxlength' => true]) ?>
            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
