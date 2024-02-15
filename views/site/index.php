<?php

use app\assets\amChart;
use yii\helpers\Url;
use yii\web\View;
use yii\web\JqueryAsset;

$this->title = 'Dashboard';
AmChart::register(Yii::$app->view);
$this->registerCssFile("@web/css/dashboard.css");
$this->registerJs("var basePath='". Url::base(getenv('PROTOCOL'))."'; var reloadFrequency=60000", View::POS_BEGIN);
$this->registerJsFile('@web/js/dashboard.js', [
    'depends' => [JqueryAsset::className()],
    'position' => View::POS_END
]);
?>

<style>
    #transaction, #clientTransaction{
        width: 100%;
        height: 500px;
    }

    #gatewayPie, #clientPie{
        width: 100%;
        height: 400px;
    }

    .servicePie{
        width: 100%;
        height: 350px;
    }
</style>

<div class="dashboard">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="shadow info-box">
                <div class="row">
                    <div class="new-info-box-content">
                        <span class="info-box-text text-muted info-title"> Main</span>
                    </div>
                    <div class="icon-box bg-blue icon-box-blue">
                        <span class=""><i class="fa fa-users"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix visible-sm-block"></div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="shadow info-box">
                <div class="row">
                    <div class="new-info-box-content">
                        <span class="info-box-text text-muted info-title"> Gateway</span>
                    </div>
                    <div class="icon-box bg-blue icon-box-blue">
                        <span class=""><i class="fa fa-link"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix visible-sm-block"></div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="shadow info-box">
                <div class="row">
                    <div class="new-info-box-content">
                        <span class="info-box-text text-muted info-title"> Transaction</span>
                    </div>
                    <div class="icon-box bg-blue icon-box-blue">
                        <span class=""><i class="fa fa-random"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
