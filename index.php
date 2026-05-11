<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$allowedPages = [
    'creation',
    'dashboard',
    'societe',
    'societes',
    'associes',
    'contrats',
    'collaborateur',
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
    'creation' => 'Nouveau dossier',
    'dashboard' => 'Tableau de bord',
    'societe' => 'Fiche societe',
    'societes' => 'Societes',
    'associes' => 'Associes',
    'contrats' => 'Contrats',
    'collaborateur' => 'Fiche collaborateur',
    'collaborateurs' => 'Collaborateurs',
    'documents' => 'Documents',
    'setup' => 'Installation XAMPP',
    'not-found' => 'Page introuvable',
];

$pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
