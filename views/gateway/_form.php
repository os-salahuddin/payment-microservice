<?php

use app\models\Transaction;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Gateway */
/* @var $form yii\widgets\ActiveForm */
/* @var $title */
?>

<div class="panel panel-<?= $model->isNewRecord ? 'success':'info' ?>">
    <div class="panel-heading">
        <h2 class="panel-title"><?= $title ?></h2>
    </div>
    <div class="panel-body">
        <div class="gateway-form">

            <?php $form = ActiveForm::begin(); ?>

                <div class="row">
                    <div class="col-md-4"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
                    <?php if(Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username)) : ?>
                        <div class="col-md-4"><?= $form->field($model, 'code')->textInput(['maxlength' => true, 'readonly' => !$model->isNewRecord]) ?></div>
                    <?php endif; ?>
                    <div class="col-md-4"><?= $form->field($model, 'charge')->textInput() ?></div>
                </div>
                <?php if(Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username)) : ?>
                    <div class="row">
                        <div class="col-md-6"> <?= $form->field($model, 'sandboxUrl')->textInput(['maxlength' => true]) ?></div>
                        <div class="col-md-6"> <?= $form->field($model, 'liveUrl')->textInput(['maxlength' => true]) ?></div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php if(Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username)) : ?>
                        <div class="col-md-3">   <?= $form->field($model, 'merchant')->textInput(['maxlength' => true]) ?></div>
                        <div class="col-md-3">  <?= $form->field($model, 'merchantPassword')->textInput(['maxlength' => true]) ?></div>
                        <div class="col-md-3">
                            <?= $form->field($model, 'currency')->widget(Select2::classname(), [
                                'initValueText' => $model->currency,
                                'value' => $model->currency, // initial value
                                'data' => app\models\Currency::dropDownList(),
                                'language' => 'en',
                                'options' => ['placeholder' => 'Please select currency', 'id' => 'currency'],
                                'pluginOptions' => [
                                    'allowClear' => true
                                ],
                            ])?>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-3">     
                        <?= $form->field($model, 'logo')->widget(Select2::classname(), [
                        'initValueText' => $model->logo,
                        'value' => $model->logo, // initial value
                            'data' => app\models\Logo::dropDownList(),
                            'language' => 'en',
                            'options' => ['placeholder' => 'Please select logo', 'id' => 'logo'],
                            'pluginOptions' => [
                                'allowClear' => true
                            ],
                        ])?>
                    </div>
                </div>
                <div class="row">
                    <?php if(Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username)) : ?>
                        <div class="col-md-4"><?= $form->field($model, 'extraParams')->textarea() ?></div>
                        <div class="col-md-4"><?= $form->field($model, 'gatewayMode')->dropDownList([ '1' => 'Live', '0' => 'Test' ]) ?></div>
                    <?php endif; ?>
                    <div class="col-md-4"><?= $form->field($model, 'status')->dropDownList([ '1' => 'Active', '0' => 'Inactive', ], ['prompt' => 'Select']) ?></div>
                </div>
              


            <div class="form-group">
		        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Add Gateway') : Yii::t('app', 'Update Gateway'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
		    </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>
