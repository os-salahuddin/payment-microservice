<?php

use yii\queue\db\Queue;
use yii\mutex\MysqlMutex;
use yii\queue\LogBehavior;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$oldDb = require __DIR__ . '/old_db.php';
$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'watcher', 'ipnQueue', 'expiredCard', 'tokenRetrieve', 'panUpdate'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'class' => 'app\modules\asm\models\User'
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'database' => 0,
            'dataTimeout' => 60 * 60 * 1,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'old_db' => $oldDb,
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'hostInfo' => $_SERVER['BASE_URL'],
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'scriptUrl' => $_SERVER['SCRIPT_URL'],
            'baseUrl' => $_SERVER['BASE_URL'],
            'rules' => [],
        ],

        'expiredCard' => [
            'class' => Queue::class,
            'as log' => LogBehavior::class,
            'db' => 'db', // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'expiredCardQueue', // Queue channel key
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],

        'tokenRetrieve' => [
            'class' => Queue::class,
            'db' => 'db', // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'tokenRetrieveQueue', // Queue channel key
            'ttr' => 2 * 60, // Max time for job execution
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
        ],
        'watcher' => [
            'class' => Queue::class,
            'db' => 'db', // DB connection component or its config
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'watcherQueue', // Queue channel key
            'ttr' => 2 * 60, // Max time for job execution
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
            'mutex' => MysqlMutex::class, // Mutex used to sync queries
            'attempts' => 6,
        ],
    ],
    'params' => $params
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
