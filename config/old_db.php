<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => getenv('OLD_MYSQL_DSN', true),
    'username' => getenv('OLD_MYSQL_USER'),
    'password' => getenv('OLD_MYSQL_PASSWORD'),
    'charset' => 'utf8',
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600, // Cache schema for 1 hour in seconds
    'schemaCache' => 'cache',
    'on afterOpen' => function ($event) {
        $event->sender->createCommand("SET time_zone='Asia/Dhaka';")->execute();
    },
];