<?php

use yii\helpers\Html;
use \app\modules\asm\models\User;

/* @var $this \yii\web\View */
/* @var $content string */


if (Yii::$app->controller->action->id === 'login') {
    /**
     * Do not use this code in your template. Remove it.
     * Instead, use the code  $this->layout = '//main-login'; in your controller.
     */
    echo $this->render(
        'main-login',
        ['content' => $content]
    );
} else {

    dmstr\web\AdminLteAsset::register($this);

    if (class_exists('backend\assets\AppAsset')) {
        backend\assets\AppAsset::register($this);
    } else {
        app\assets\AppAsset::register($this);
    }


    $directoryAsset = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
    ?>
    <?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>


        <?php $this->head() ?>
    </head>
    <body class="<?= \dmstr\helpers\AdminLteHelper::skinClass() ?>">
    <?php $this->beginBody() ?>
    <div class="wrapper">
        <?= $this->render(
            '@app/modules/admin/views/layouts/header.php',
            ['directoryAsset' => $directoryAsset]
        ) ?>

        <?= $this->render(
            '@app/modules/admin/views/layouts/left.php',
            ['directoryAsset' => $directoryAsset]
        )
        ?>

        <?= $this->render(
            '@app/modules/admin/views/layouts/content.php',
            ['content' => $content, 'directoryAsset' => $directoryAsset]
        ) ?>

    </div>
    <?php $this->endBody() ?>

    </body>
    </html>
    <?php $this->endPage() ?>
<?php } ?>
