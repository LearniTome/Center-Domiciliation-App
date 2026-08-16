<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/env.php';

return [
    'host' => env_var('DB_HOST', '127.0.0.1'),
    'port' => (int) env_var('DB_PORT', '3306'),
    'dbname' => env_var('DB_NAME', 'center_domiciliation'),
    'username' => env_var('DB_USERNAME', 'root'),
    'password' => env_var('DB_PASSWORD', ''),
    'charset' => env_var('DB_CHARSET', 'utf8mb4'),
];
