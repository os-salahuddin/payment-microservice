<?php

$apiRoutes         = require __DIR__ . '/_apiRoutes.php';

$open = [
    'admin/user/login',
    'admin/user/logout',
    'gii/*',
    'debug/*',
];

return array_merge($open, $apiRoutes);