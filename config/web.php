<?php

use yii\queue\db\Queue;
use yii\mutex\MysqlMutex;
use yii\queue\LogBehavior;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$oldDb = require __DIR__ . '/old_db.php';
$config = [
    'id' => 'basic',
    'name' => 'Pay Manager',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'watcher', 'ipnQueue', 'expiredCard', 'tokenRetrieve', 'panUpdate'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'L_mxBByUOVcirA8gk8jJ-kmlObQz3WjQ',
        ],
        'asm' => [
            'class' => 'app\modules\asm\components\ASM',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'database' => 0,
            'dataTimeout' => 60 * 60 * 1,
        ],
        'session' => [
            'class' => 'yii\redis\Session',
            'keyPrefix' => getenv('SESSION_REDIS_PREFIX'),
            'timeout' => 60 * 30 * 1
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
            'keyPrefix' => getenv('CACHE_REDIS_PREFIX'),
        ],
        'user' => [
            'identityClass' => 'app\modules\asm\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['asm/user/login']
        ],
        'errorHandler' => [
            'errorAction' => 'asm/permission/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'username' => getenv('AWS_MAIL_USER'),
                'password' => getenv('AWS_MAIL_SECRET'),
                'host' => getenv('AWS_MAIL_HOST'),
                'port' => getenv('AWS_MAIL_PORT'),
                'encryption' => 'tls',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => [null],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['abc'],
                    'logVars' => [null],
                    'logFile' => '@app/runtime/logs/abc.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
            ],
        ],
        'db' => $db,
        'old_db' => $oldDb,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [],
        ],
        'reCaptcha' => [
            'class' => 'himiklab\yii2\recaptcha\ReCaptchaConfig',
            'siteKey' => getenv('RECAPTCHASITEKEY'),
            'secret' => getenv('RECAPTCHASECRET'),
        ],
        'assetManager' => [
            'appendTimestamp' => true,
            'bundles' => [
                'dmstr\web\AdminLteAsset' => [
                    'skin' => 'skin-blue-light',
                ],
            ],
        ],
        'uploader' => [
            'class' => 'app\components\Uploader',
        ],
        's3' => [
            'class' => 'frostealth\yii2\aws\s3\Service',
            'credentials' => [ // Aws\Credentials\CredentialsInterface|array|callable
                'key' => getenv('AWS_S3_USER_KEY'),
                'secret' => getenv('AWS_S3_SECRET_KEY'),
            ],
            'region' => getenv('AWS_S3_REGION'),
            'defaultBucket' => getenv('AWS_S3_BUCKET'),
            'defaultAcl' => 'public-read',
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@app/views' => '@app/modules/asm/views'
                ],
            ],
        ],
        'expiredCard' => [
            'class' => Queue::class,
            'db' => $db, // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'expiredCardQueue', // Queue channel key
            'ttr' => 2 * 60, // Max time for job execution
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],
        'tokenRetrieve' => [
            'class' => Queue::class,
            'db' => $db, // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'tokenRetrieveQueue', // Queue channel key
            'ttr' => 2 * 60, // Max time for job execution
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],
        'watcher' => [
            'class' => Queue::class,
            'db' => $db, // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'watcherQueue', // Queue channel key
            'ttr' => 60, // Max time for job execution
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],
        'panUpdate' => [
            'class' => Queue::class,
            'db' => $db, // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'panUpdateQueue', // Queue channel key
            'ttr' => 60, // Max time for job execution
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],
        //Queue for IPN
        'ipnQueue' => [
            'class' => Queue::class,
            'as log' => LogBehavior::class,
            'db' => 'db', // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'ipnQueue', // Queue channel key
            'attempts' => 6,
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],
    ],
    'modules' => [
        'gridview' => ['class' => 'kartik\grid\Module'],

        'asm' => [
            'class' => 'app\modules\asm\Module',
            'layout' => '@app/modules/asm/views/layouts/main',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1', '*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1', '*'],
    ];

    function dd(...$args)
    {
        $numargs = count($args);
        echo '<pre>';
        if ($numargs > 0) {
            $isVarDump = false;
            if ($args[$numargs - 1] === true) {
                $isVarDump = true;
            }
            foreach ($args as $key => $arg) {
                if ($isVarDump) {
                    var_dump($arg);
                } else {
                    print_r($arg);
                }
                echo PHP_EOL;
            }
        }
        echo '</pre>';
        die();
    }
}

return $config;
