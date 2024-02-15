<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Gateway */

$this->title = 'Create Gateway';
$this->params['breadcrumbs'][] = ['label' => 'Gateways', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="gateway-create">

    <?= $this->render('_form', [
    	'title' => $this->title,
        'model' => $model,
    ]) ?>

</div>
