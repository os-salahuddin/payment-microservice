<?php

use kartik\daterange\DateRangePicker;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\TransactionSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="transaction-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <div class="col-md-4"><?= $form->field($model, 'uid') ?></div>
        <div class="col-md-4"><?= $form->field($model, 'amount') ?></div>
        <div class="col-md-4">
            <label for="gateway">Gateway</label>
            <?= Select2::widget([
                'model' => $model,
                'name' => 'gateway',
                'data' => app\models\Gateway::dropDownList(),
                'language' => 'en',
                'options' => ['placeholder' => 'Please select gateway', 'id' => 'gateway'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'currency')->widget(Select2::classname(), [
                'initValueText' => $model->currency,
                'value' => $model->currency, // initial value
                'data' => app\models\Currency::dropDownList(),
                'language' => 'en',
                'options' => ['placeholder' => 'Please select currency', 'id' => 'currency'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
        </div>
        <div class="col-md-4">
            <label for="client">Client</label>
            <?= Select2::widget([
                'model' => $model,
                'name' => 'client',
                'data' => app\models\Client::dropDownList(),
                'language' => 'en',
                'options' => ['placeholder' => 'Please select client', 'id' => 'clientId'],
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
            <label for="serviceType">Service Type</label>
            <?= Select2::widget([
                'model' => $model,
                'name' => 'serviceType',
                'data' => [],
                'language' => 'en',
                'options' => ['id' => 'serviceType'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
            <br>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4"><?= $form->field($model, 'charge') ?></div>
        <div class="col-md-4"><?php echo $form->field($model, 'bankStatus') ?></div>
        <div class="col-md-4">
            <?= $form->field($model, 'status')->widget(Select2::classname(), [
                'data' => ['1' => 'Success', '0' => 'Failed'],
                'language' => 'en',
                'options' => ['placeholder' => 'Please select status', 'id' => 'status'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="orderId">Order ID</label>
                <input id="orderId" class="form-control" type="text" name="orderId">
            </div>
        </div>
        <div class="col-md-6">
            <label for="">Select Date</label>
            <?= DateRangePicker::widget([
                'name' => 'createdAt',
                'options' => ['placeholder' => 'Select Date ...', 'autocomplete' => 'off', 'class' => 'form-control'],
                'convertFormat' => true,
                'startAttribute' => 'createdAt',
                'endAttribute' => 'updatedAt',
                'pluginOptions' => [
                    'format' => 'dd-M-yyyy',
                    'todayHighlight' => true
                ]
            ]) ?>
        </div>
        <br>
    </div>
    <br>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
    <script>
        function ClientList() {
            $.post("<?= Yii::$app->urlManager->createUrl('service/client-wise-service?client=') ?>" + $('#client :selected').val(), function (data) {
                $("select#serviceType").html(data);
            });
        }
    </script>
</div>