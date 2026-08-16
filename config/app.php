<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/env.php';

$defaultBaseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/') . '/index.php';
$envUrl = env_var('APP_URL', '');
$baseUrl = $envUrl !== '' ? rtrim($envUrl, '/') . '/index.php' : $defaultBaseUrl;

return [
    'app_name' => env_var('APP_NAME', 'Center Domiciliation App'),
    'base_url' => $baseUrl,
];
