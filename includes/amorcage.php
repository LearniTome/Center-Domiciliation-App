<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/database.php';

require __DIR__ . '/fonctions.php';
require __DIR__ . '/base_donnees.php';

// Composer autoload (PhpSpreadsheet, PHPWord, Dompdf)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
}
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

// Dev auto-login: ?autologin=1 on localhost only
if (
    !is_logged_in()
    && isset($_GET['autologin'])
    && $_GET['autologin'] === '1'
    && in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    && $pdo instanceof PDO
) {
    $stmt = $pdo->prepare('SELECT id, nom_complet FROM collaborateurs WHERE email = :email AND can_login = 1 AND statut = \'actif\' LIMIT 1');
    $stmt->execute(['email' => 'admin@center.test']);
    $devUser = $stmt->fetch();
    if ($devUser) {
        $_SESSION['user_id'] = (int) $devUser['id'];
        log_activity($pdo, 'connexion', 'auth', (int) $devUser['id'], $devUser['nom_complet'] . ' (auto)');
        $pdo->prepare('UPDATE collaborateurs SET last_login = NOW() WHERE id = :id')->execute(['id' => (int) $devUser['id']]);
    }
}

if (!in_array($currentPage, $publicPages, true)) {
    require_auth();
}

