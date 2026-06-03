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

// Auth check: public pages don't require login
$publicPages = ['connexion', 'setup', 'not-found'];
$currentPage = $_GET['page'] ?? 'dashboard';

if (!in_array($currentPage, $publicPages, true)) {
    require_auth();
}

