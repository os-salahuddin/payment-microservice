<?php

use app\components\Utils;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Transaction */

$this->title = $model->uid;
$this->params['breadcrumbs'][] = ['label' => 'Refunded Transaction', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="transaction-view">
    <style>
        .colBorder {
            background-color: #f7f7f7;
            border: 1px solid #d0d0d0;
        }

        .colBorder>h4 {
            color: black;
        }

        .bank-form>.col-md-12>.row {
            margin-bottom: 5px;
        }
    </style>
    <div class="panel panel-primary">
        <div class="panel-body">
            <div class="bank-form">
                <div class="col-md-12">
                    <div class="row text-center">

                        <div class="col-md-4 colBorder">
                            <h4>Code</h4>
                            <?= $model->uid ?>
                        </div>
                        <div class="col-md-2 colBorder">
                            <h4>Amount</h4>
                            <?= $model->amount ?>
                        </div>
                        <div class="col-md-3 colBorder">
                            <h4>Charge</h4>
                            <?= $model->charge ?? 'Not Set' ?>
                        </div>
                        <div class="col-md-3 colBorder">
                            <h4>Currency</h4>
                            <?= $model->relatedCurrency->code ?? 'Not Set' ?>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-md-2 colBorder">
                            <h4>Bank Status</h4>
                            <?= $model->bankStatus ?? 'Not Set' ?>
                        </div>
                        <div class="col-md-2 colBorder">
                            <h4>Type</h4>
                            <?php
                            if ($model->transactionType == 1)
                                echo 'Refund';
                            elseif ($model->transactionType == 02)
                                echo 'Void';
                            ?>
                        </div>
                        <div class="col-md-2 colBorder">
                            <h4>Status</h4>
                            <?php
                            if ($model->status == 1)
                                echo 'Success';
                            elseif ($model->status == 0)
                                echo 'Failed';
                            ?>
                        </div>
                        <div class="col-md-3 colBorder">
                            <h4>Created At</h4>
                            <?= Utils::getIntDateTime($model->createdAt) ?>
                        </div>
                        <div class="col-md-3 colBorder">
                            <h4>Updated At</h4>
                            <?= Utils::getIntDateTime($model->updatedAt) ?>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-md-12 colBorder">
                            <h4>Response</h4>
                            <?= $model->response == '' ? 'Not Found' : '<pre><code>' . $model->response . '</code></pre>' ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

</div>