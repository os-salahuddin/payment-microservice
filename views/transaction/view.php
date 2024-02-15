<?php

use yii\web\YiiAsset;
use app\components\Utils;

/* @var $this yii\web\View */
/* @var $model app\models\Transaction */

$this->title = $model->orderId;
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);
?>
<div class="transaction-view">
    <style>
        .colBorder {
            background-color: #f7f7f7;
            border: 1px solid #d0d0d0;
        }

        .colBorder > h4 {
            color: black;
        }

        .bank-form > .col-md-12 > .row {
            margin-bottom: 5px;
        }

        .border-left {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
        }

        .border-right {
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        .list-group-item {
            padding-bottom: 0 !important;
        }
    </style>
    <div class="panel panel-primary">
        <div class="panel-body">
            <div class="bank-form">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-5">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <p><strong>Session ID : </strong> <?= $model->sessionId ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Transaction Date
                                            : </strong> <?= $model->bankTransactionDate ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Response Code
                                            : </strong> <?= $model->bankResponseCode ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Response Description
                                            : </strong> <?= $model->bankResponseDescription ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Merchant Tran
                                            ID: </strong> <?= $model->bankMerchantTranId ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Approval Code Scr
                                            : </strong> <?= $model->bankApprovalCodeScr ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Order Status Scr
                                            : </strong> <?= $model->bankOrderStatusScr ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Three Dsv
                                            Verification: </strong> <?= $model->bankThreeDsvVerification ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Three Dss
                                            Status: </strong> <?= $model->bankThreeDssStatus ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Card Holder Name : </strong> <?= $model->cardHolderName ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Description : </strong> <?= $model->description ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Customer ID
                                            : </strong> <?= !empty($model->customerId) ? $model->customerId : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Customer Name
                                            : </strong> <?= !empty($model->customerName) ? $model->customerName : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Name
                                            : </strong> <?= !empty($model->name) ? $model->name : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Email
                                            : </strong> <?= !empty($model->email) ? $model->email : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Phone
                                            : </strong> <?= !empty($model->phone) ? $model->phone : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>BookingCode
                                            : </strong> <?= !empty($model->bookingCode) ? $model->bookingCode : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Is Invoice Email sent
                                            : </strong> <?= $model->isInvoiceEmailSend == 1 ? 'Yes' : 'Not Set' ?>
                                    </p>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <p><strong>Order ID : </strong> <?= $model->orderId ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Booking ID : </strong> <?= $model->bookingId ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Amount : </strong> <?= $model->amount ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Charge : </strong> <?= $model->charge ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Type : </strong> <?= $model->type ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Card Brand : </strong> <?= $model->cardBrand ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Card Prefix/Series
                                            : </strong> <?= !empty($model->cardPrefix) ? $model->cardPrefix : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Card length
                                            : </strong> <?= !empty($model->cardLength) ? $model->cardLength : 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Service Type : </strong> <?= $model->serviceType ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Session Version : </strong> <?= $model->sessionVersion ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Result Indicator : </strong> <?= $model->resultIndicator ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Acq Fee : </strong> <?= $model->acqFee ?? 'Not Set' ?></p>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-4">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <p><strong>Request ID : </strong> <?= $model->requestId ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>RRN : </strong> <?= $model->rrn ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>PAN : </strong> <?= $model->pan ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Status : </strong>
                                        <?php
                                        if ($model->status == 0)
                                            echo 'Canceled';
                                        elseif ($model->status == 1)
                                            echo 'Created';
                                        elseif ($model->status == 2)
                                            echo 'Paid';
                                        elseif ($model->status == 3)
                                            echo 'Timeout';
                                        elseif ($model->status == 4)
                                            echo 'Declined';
                                        elseif ($model->status == 5)
                                            echo 'Refund';
                                        elseif ($model->status == 6)
                                            echo 'Void';
                                        ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Order Status : </strong> <?= $model->bankOrderStatus ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bank Approval Code
                                            : </strong> <?= $model->bankApprovalCode ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Notify : </strong> <?= $model->notify == 1 ? 'Yes' : 'No' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Currency : </strong> <?= $model->relatedCurrency->code ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Gateway : </strong> <?= $model->relatedGateway->name ?? 'Not Set' ?></p>
                                </li>
                                <?php if($model->onEmi == 1): ?>
                                    <li class="list-group-item">
                                        <p><strong>On Emi : </strong> Yes </p>
                                    </li>
                                    <li class="list-group-item">
                                        <p><strong>Emi Fee: </strong> <?= $model->emiFee ?? null ?> </p>
                                    </li>
                                    <li class="list-group-item">
                                        <p><strong>Emi Repayment Period : </strong> <?= $model->emiRepaymentPeriod ?></p>
                                    </li>
                                    <li class="list-group-item">
                                        <p><strong>Emi Interest Rate : </strong> <?= $model->emiInterestRate ?></p>
                                    </li>
                                    <li class="list-group-item">
                                        <p><strong>Emi Interest Amount : </strong> <?= $model->emiInterestAmount ?></p>
                                    </li>
                                <?php endif ?>
                                <li class="list-group-item">
                                    <p><strong>Bin Restriction : </strong> <?= $model->binRestriction ?> </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Bin Length : </strong> <?= $model->binLength ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Trip Coin Multiply : </strong> <?= $model->tripCoinMultiply ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Created At
                                            : </strong> <?= Utils::timestampToDateTimeTransaction($model->createdAt) ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Updated At
                                            : </strong> <?= Utils::timestampToDateTimeTransaction($model->updatedAt) ?>
                                    </p>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-md-12 colBorder border-left border-right">
                            <h4>Bank Response</h4>
                            <?= $model->bankResponse == '' ? 'Not Found' : '<pre><code>' . $model->bankResponse . '</code></pre>' ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php if (isset($model->relatedRefund)) { ?>
        <div class="panel-body">
            <div class="bank-form">
                <div class="col-md-12">
                    <div class="row text-center">
                        <div class="col-md-12 colBorder border-left border-right">
                            <h2>Refund/Void Data</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <p><strong>Amount : </strong> <?= $model->relatedRefund->amount ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Charge : </strong> <?= $model->relatedRefund->charge ?? 'Not Set' ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Currency
                                            : </strong> <?= $model->relatedRefund->relatedCurrency->code ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Transaction Type : </strong> <?php
                                        if ($model->relatedRefund->transactionType == 1)
                                            echo 'Refund';
                                        elseif ($model->relatedRefund->transactionType == 2)
                                            echo 'Void';
                                        ?></p>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <p><strong>Bank Status
                                            : </strong> <?= $model->relatedRefund->bankStatus ?? 'Not Set' ?>
                                    </p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Status : </strong> <?php
                                        if ($model->relatedRefund->status == 1)
                                            echo 'Success';
                                        elseif ($model->relatedRefund->status == 0)
                                            echo 'Failed';
                                        ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Created At : </strong> <?= $model->relatedRefund->createdAt ?></p>
                                </li>
                                <li class="list-group-item">
                                    <p><strong>Updated At : </strong> <?= $model->relatedRefund->updatedAt ?></p>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-md-12 colBorder border-left border-right">
                            <h4>Response</h4>
                            <?= $model->relatedRefund->response == '' ? 'Not Found' : '<pre><code>' . $model->relatedRefund->response . '</code></pre>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>