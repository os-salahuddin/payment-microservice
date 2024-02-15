<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\daterange\DateRangePicker;

/* @var $this yii\web\View */
/* @var $model app\models\TransactionSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="transaction-search ">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <div class="col-md-4"><?= $form->field($model, 'bankApprovalCode') ?></div>
        <div class="col-md-4"><?php echo $form->field($model, 'customerName') ?></div>
        <div class="col-md-4"><?php echo $form->field($model, 'customerId')->label('CustomerId') ?></div>
    </div>

    <div class="row">

        <div class="col-md-4">
            <?= $form->field($model, 'clientId')->widget(Select2::classname(), [
                'data' => app\models\Client::dropDownList(),
                'language' => 'en',
                'options' => ['placeholder' => 'Client', 'id' => 'clientId'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
                'pluginEvents' => [
                    "select2:select" => "function() {
                        $.post('" . Yii::$app->urlManager->createUrl('service/client-wise-service?client=') . "'+$(this).val(),function( data )
                        {
                            $('select#serviceType').html(data);
                        })
                    }",
                ]
            ]) ?>
        </div>

        <div class="col-md-4">
            <?= $form->field($model, 'serviceType') ?>
        </div>

        <div class="col-md-4">
            <?= $form->field($model, 'gateway')->widget(Select2::classname(), [
                'data' => app\models\Gateway::dropDownList(),
                'language' => 'en',
                'options' => ['placeholder' => 'Gateway', 'id' => 'gateway'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
        </div>

    </div>

    <div class="row">
        <div class="col-md-4"><?php echo $form->field($model, 'bookingId')->label('BookingId') ?></div>
        <div class="col-md-4"><?php echo $form->field($model, 'orderId')->label('OrderId') ?></div>
        <div class="col-md-4"><?php echo $form->field($model, 'rrn') ?></div>
    </div>


    <div class="row">
        <div class="col-md-4"><?php echo $form->field($model, 'cardPrefix')->label('Card') ?></div>
        <div class="col-md-4">
            <?= $form->field($model, 'status')->widget(Select2::classname(), [
                'data' => ['0' => 'Canceled', '1' => 'Created', '2' => 'Paid', '3' => 'Timeout', '4' => 'Declined', '5' => 'Refund', '6' => 'Void'],
                'language' => 'en',
                'options' => ['placeholder' => 'Please select status', 'id' => 'status'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
        </div>
        <div class="col-md-4"><?php echo $form->field($model, 'type') ?></div>
    </div>
    <div class="row">
        <div class="col-md-4"><?= $form->field($model, 'amount') ?></div>
        <div class="col-md-4">
            <?= $form->field($model, 'bankCode')->widget(Select2::classname(), [
                'data' => app\models\Bank::dropDownList(),
                'language' => 'en',
                'options' => ['placeholder' => 'Bank', 'id' => 'bank'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ])->label('Bank') ?>
        </div>
        <div class="col-md-4">
            <label for="">Date</label>
            <?= DateRangePicker::widget([
                'name' => 'createdAt',
                'options' => ['placeholder' => 'Date ...', 'autocomplete' => 'off', 'class' => 'form-control'],
                'convertFormat' => true,
                'pluginOptions' => [
                    'format' => 'dd-M-yyyy',
                    'todayHighlight' => true
                ]
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'notify')->widget(Select2::classname(), [
                'data' => ['1' => 'Yes', '0' => 'No'],
                'language' => 'en',
                'options' => ['placeholder' => 'Please select notify', 'id' => 'notify'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
        </div>
        <br>
    </div>
    <br>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group pull-right">
                <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>