<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Transaction */

$this->title = 'Create Transaction';
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="transaction-create">

    <?= $this->render('_form', [
    	'title' => $this->title,
        'model' => $model,
    ]) ?>

</div>
