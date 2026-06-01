<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/database.php';

require __DIR__ . '/functions.php';
require __DIR__ . '/db.php';
require_once __DIR__ . '/../src/ClaudeService.php';

$flash = pull_flash();
$dbError = null;

try {
    $pdo = db();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

