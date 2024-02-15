<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Transaction */

$this->title = 'Update Transaction: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="transaction-update">

    <?= $this->render('_form', [
    	'title' => $this->title,
        'model' => $model,
    ]) ?>

</div>
