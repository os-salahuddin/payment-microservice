<?php

use app\components\Utils;
use app\models\Transaction;
use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Gateway;
/* @var $this yii\web\View */
/* @var $model app\models\Gateway */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Gateways', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="gateway-view">

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h2 class="panel-title"><?= $this->title ?></h2>
        </div>
        <div class="panel-body">
            <div class="bank-form">
                <p>
                    <?= Html::a('Update', ['update', 'id' => $model->uid], ['class' => 'btn btn-primary']) ?>
                </p>

                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'uid',
                        'name',
                        'code',
                        'charge',
                        'sandboxUrl',
                        'liveUrl',
                        [
                            'attribute' => 'merchant',
                            'value' => $model->merchant,
                            'visible' => Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username),
                        ],
                        [
                            'attribute' => 'merchantPassword',
                            'value' => $model->merchantPassword,
                            'visible' => Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username),
                        ],
                        [
                            'attribute' => 'currency',
                            'value' => $model->relatedCurrency->code
                        ],
                        [
                            'attribute' => 'logo',
                            'format' => ['image', ['width' => '50', 'height' => '50']],
                            'value' => $model->relatedLogo->small,
                        ],
                        [
                            'attribute' => 'extraParams',
                            'value' => empty($model->extraParams) ? 'None' : '<pre><code>' . $model->extraParams . '</code></pre>',
                            'format' => 'html',
                            'visible' => Transaction::allowedUserToGatewayConfig(Yii::$app->user->identity->username),
                        ],
                        [
                            'attribute' => 'gatewayMode',
                            'value' => $model->gatewayMode == 0 ? 'Test' : 'Live',
                        ],
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->status == Gateway::STATUS_ACTIVE ? 'Active' : 'Inactive';
                            }
                        ],
                        [
                            'attribute' => 'createdBy',
                            'value' => $model->creator->username
                        ],
                        [
                            'attribute' => 'updatedBy',
                            'value' => $model->updater->username
                        ],
                        [
                            'attribute' => 'createdAt',
                            'value' => function ($model) {
                                return Utils::getDateTime($model->createdAt);
                            }
                        ],
                        [
                            'attribute' => 'updatedAt',
                            'value' => function ($model) {
                                return Utils::getDateTime($model->createdAt);
                            }
                        ],
                    ],
                ]) ?>

            </div>
        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h2 class="panel-title">Connected Bank</h2>
        </div>

        <div class="panel-body">
            <?php if (!empty($model->gatewayRelatedBank)) : ?>
                <table class="table table-responsive table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Bank Name</th>
                            <th>Bank Status</th>
                            <th>Created By</th>
                            <th>Updated By</th>
                            <th>Updated At</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($model->gatewayRelatedBank as $bank){ ?>
                        <tr>
                            <td><?= $bank->name ?></td>
                            <td><?= $bank->status == 1 ? 'Active' : 'Inactive' ?></td>
                            <td><?= ucfirst($bank->creator->username) ?></td>
                            <td><?= ucfirst($bank->updater->username) ?></td>
                            <td><?= Utils::getDateTime($bank->updatedAt) ?></td>
                            <td>
                                <a href="<?= yii\helpers\Url::toRoute(['bank/view', 'id' => $bank->uid]) ?>" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>
                                <a href="<?= yii\helpers\Url::toRoute(['bank/update', 'id' => $bank->uid]) ?>" class="btn btn-default btn-xs"><i class="fa fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php else : ?>
                <h3 class="text-center">Bank not connected</h3>
            <?php endif ?>
        </div>

    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h2 class="panel-title">Connected Card Type</h2>
        </div>

        <div class="panel-body">
            <?php if (!empty($model->relatedCardType)) : ?>
                <table class="table table-responsive table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Card Type</th>
                            <th>Card Type Status</th>
                            <th>Created By</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $model->relatedCardType->code ?></td>
                            <td><?= $model->relatedCardType->status == 1 ? 'Active' : 'Inactive' ?></td>
                            <td><?= ucfirst($model->relatedCardType->creator->username) ?></td>
                            <td><?= Utils::getDateTime($model->relatedCardType->updatedAt) ?></td>
                            <td>
                                <a href="<?= yii\helpers\Url::toRoute(['card-type/view', 'id' => $model->relatedCardType->uid]) ?>" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>
                                <a href="<?= yii\helpers\Url::toRoute(['card-type/update', 'id' => $model->relatedCardType->uid]) ?>" class="btn btn-default btn-xs"><i class="fa fa-edit"></i></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else : ?>
                <h3 class="text-center">Card Type not connected</h3>
            <?php endif ?>
        </div>

    </div>

</div>
