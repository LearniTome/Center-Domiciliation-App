<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/env.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
$defaultBaseUrl = $scheme . '://' . $host . $basePath . '/index.php';
$envUrl = env_var('APP_URL', '');
$baseUrl = $envUrl !== '' ? rtrim($envUrl, '/') . '/index.php' : $defaultBaseUrl;

return [
    'app_name' => env_var('APP_NAME', 'Center Domiciliation App'),
    'base_url' => $baseUrl,
];
