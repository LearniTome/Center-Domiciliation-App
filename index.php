<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$allowedPages = [
    'dashboard',
    'societes',
    'associes',
    'contrats',
    'collaborateurs',
    'documents',
    'setup',
];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'not-found';
}

$pageTitleMap = [
    'dashboard' => 'Tableau de bord',
    'societes' => 'Societes',
    'associes' => 'Associes',
    'contrats' => 'Contrats',
    'collaborateurs' => 'Collaborateurs',
    'documents' => 'Documents',
    'setup' => 'Installation XAMPP',
    'not-found' => 'Page introuvable',
];

$pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';

