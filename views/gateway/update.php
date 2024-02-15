<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Gateway */

$this->title = 'Update Gateway: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Gateways', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="gateway-update">

    <?= $this->render('_form', [
    	'title' => $this->title,
        'model' => $model,
    ]) ?>

</div>
