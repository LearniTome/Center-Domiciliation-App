<?php

declare(strict_types=1);

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$baseUrl = rtrim(dirname($scriptName), '/') . '/index.php';

return [
    'app_name' => 'Center Domiciliation App',
    'base_url' => $baseUrl,
];

