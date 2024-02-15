<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 * @since 2.0
 */
class AmChart extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [
        'js/amcharts4/core.js',
        'js/amcharts4/charts.js',
        'js/amcharts4/themes/dataviz.js',
        'js/amcharts4/themes/material.js',
        'js/amcharts4/themes/animated.js',

    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset'
    ];
}
