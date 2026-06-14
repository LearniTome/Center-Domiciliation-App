<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/database.php';

require __DIR__ . '/functions.php';
require __DIR__ . '/db.php';
require_once __DIR__ . '/../src/service_claude.php';

$flash = pull_flash();
$dbError = null;

try {
    $pdo = db();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

// Auto-run pending migrations
if ($pdo instanceof PDO) {
    require_once __DIR__ . '/migrations.php';
    $migrationResults = run_migrations($pdo);
    $migrationErrors = array_filter($migrationResults, fn($r) => str_starts_with((string) $r, 'ERROR'));
    if ($migrationErrors !== []) {
        $dbError = 'Migrations echouees: ' . implode('; ', array_map(
            fn($f, $e) => "$f: $e",
            array_keys($migrationErrors),
            $migrationErrors
        ));
    }
}

// Auth check: public pages don't require login
$publicPages = ['connexion', 'setup', 'not-found'];
$currentPage = $_GET['page'] ?? 'dashboard';

if (!in_array($currentPage, $publicPages, true)) {
    require_auth();
}

