<?php

declare(strict_types=1);

ob_start();

require __DIR__ . '/includes/bootstrap.php';

$allowedPages = [
    'creation',
    'configuration',
    'formes-juridiques',
    'tribunaux',
    'adresses',
    'villes',
    'nationalites',
    'lieux-naissance',
    'qualites-associe',
    'fonctions',
    'activites',
    'activites-ompic',
    'dashboard',
    'societe',
    'societes',
    'associe',
    'associes',
    'contrats',
    'collaborateur',
    'collaborateurs',
    'generation',
    'template',
    'template_edit',
    'templates',
    'documents',
    'download_all',
    'defaults',
    'analyse-couverture',
    'variables',
    'convert-word-pdf',
    'ai-assistant',
    'setup',
    'connexion',
    'deconnexion',
    'roles',
    'role',
    'activite',
    'notifications',
    'notifications-manage',
    'notif-ajax',
    'modifications',
    'cessions',
    'cession',
    'cession_dossier',
];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'not-found';
}

// JSON API endpoint — no HTML output at all
if ($page === 'notif-ajax') {
    ob_clean();
    require __DIR__ . '/pages/notif-ajax.php';
    ob_end_flush();
    exit;
}

$pageTitleMap = [
    'creation' => 'Nouveau dossier',
    'configuration' => 'Configuration',
    'formes-juridiques' => 'Formes juridiques',
    'tribunaux' => 'Tribunaux',
    'adresses' => 'Adresses',
    'villes' => 'Villes',
    'nationalites' => 'Nationalites',
    'lieux-naissance' => 'Lieux de naissance',
    'qualites-associe' => 'Qualites associe',
    'fonctions' => 'Fonctions',
    'activites' => 'Activites',
    'activites-ompic' => 'Activites Ompic',
    'dashboard' => 'Tableau de bord',
    'societe' => 'Fiche societe',
    'societes' => 'Societes',
    'associes' => 'Associes',
    'associe' => 'Fiche associe',
    'contrats' => 'Contrats',
    'collaborateur' => 'Fiche collaborateur',
    'collaborateurs' => 'Collaborateurs',
    'generation' => 'Generateur de dossiers',
    'download_all' => 'Telechargement...',
    'template' => 'Template',
    'template_edit' => 'Editeur de template',
    'templates' => 'Templates',
    'analyse-couverture' => 'Analyse de couverture',
    'variables' => 'Gestion des variables',
    'ai-assistant' => 'Assistant IA',
    'documents' => 'Documents generes',
    'defaults' => 'Valeurs par defaut',
    'convert-word-pdf' => 'Word to PDF',
    'setup' => 'Installation XAMPP',
    'not-found' => 'Page introuvable',
    'connexion' => 'Connexion',
    'deconnexion' => 'Deconnexion',
    'roles' => 'Gestion des roles',
    'role' => 'Fiche role',
    'activite' => 'Journal d\'activite',
    'notifications' => 'Notifications',
    'notifications-manage' => 'Gestion des notifications',
    'modifications' => 'Modifications juridiques',
    'cessions' => 'Cessions de parts sociales',
    'cession' => 'Cession de parts sociales',
    'cession_dossier' => 'Dossier de cession',
];

// Public pages without sidebar layout
$noLayoutPages = ['connexion', 'deconnexion'];

if (in_array($page, $noLayoutPages, true)) {
    $pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/pages/' . $page . '.php';
    require __DIR__ . '/includes/footer.php';
    ob_end_flush();
    exit;
}

$pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';

// Page-level permission check (except public pages)
$publicPages = ['setup', 'not-found'];
if (!in_array($page, $publicPages, true)) {
    require_page_access($page);
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';

ob_end_flush();
