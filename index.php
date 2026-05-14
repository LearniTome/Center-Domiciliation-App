<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$allowedPages = [
    'creation',
    'configuration',
    'formes-juridiques',
    'adresses',
    'villes',
    'nationalites',
    'lieux-naissance',
    'qualites-associe',
    'dashboard',
    'societe',
    'societes',
    'associes',
    'contrats',
    'collaborateur',
    'collaborateurs',
    'generation',
    'template',
    'template_edit',
    'templates',
    'documents',
    'defaults',
    'convert-word-pdf',
    'setup',
];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'not-found';
}

$pageTitleMap = [
    'creation' => 'Nouveau dossier',
    'configuration' => 'Configuration',
    'formes-juridiques' => 'Formes juridiques',
    'adresses' => 'Adresses de reference',
    'villes' => 'Villes',
    'nationalites' => 'Nationalites',
    'lieux-naissance' => 'Lieux de naissance',
    'qualites-associe' => 'Qualites associe',
    'dashboard' => 'Tableau de bord',
    'societe' => 'Fiche societe',
    'societes' => 'Societes',
    'associes' => 'Associes',
    'contrats' => 'Contrats',
    'collaborateur' => 'Fiche collaborateur',
    'collaborateurs' => 'Collaborateurs',
    'generation' => 'Generation',
    'template' => 'Template',
    'template_edit' => 'Editeur de template',
    'templates' => 'Templates',
    'documents' => 'Documents',
    'defaults' => 'Valeurs par defaut',
    'convert-word-pdf' => 'Word to PDF',
    'setup' => 'Installation XAMPP',
    'not-found' => 'Page introuvable',
];

$pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
