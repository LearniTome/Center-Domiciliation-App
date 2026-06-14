<?php

declare(strict_types=1);

ob_start();

require __DIR__ . '/includes/bootstrap.php';

$allowedPages = [
    'creation', 'configuration',
    'formes-juridiques', 'tribunaux', 'adresses', 'villes',
    'nationalites', 'lieux-naissance', 'qualites-associe', 'fonctions',
    'activites', 'activites-ompic',
    'dashboard',
    'societe', 'societes', 'associe', 'associes', 'contrats',
    'collaborateur', 'collaborateurs',
    'generation', 'template', 'template_edit', 'templates',
    'documents', 'download_all',
    'defaults', 'analyse-couverture', 'variables',
    'convert-word-pdf', 'ai-assistant',
    'setup', 'connexion', 'deconnexion',
    'roles', 'role', 'activite',
    'notifications', 'notifications-manage', 'notif-ajax',
    'modifications', 'cessions', 'cession', 'cession_dossier',
];

$pageDir = [
    // Accueil
    'dashboard' => 'accueil',
    'notifications' => 'accueil',
    // Dossiers
    'creation' => 'dossiers',
    'societes' => 'dossiers',
    'societe' => 'dossiers',
    'associes' => 'dossiers',
    'associe' => 'dossiers',
    'contrats' => 'dossiers',
    'collaborateurs' => 'dossiers',
    'collaborateur' => 'dossiers',
    // Modification juridique
    'modifications' => 'modification-juridique',
    'cessions' => 'modification-juridique/cession',
    'cession' => 'modification-juridique/cession',
    'cession_dossier' => 'modification-juridique/cession',
    // Templates de documents
    'templates' => 'templates',
    'template' => 'templates',
    'template_edit' => 'templates',
    'generation' => 'templates',
    'documents' => 'templates',
    'download_all' => 'templates',
    // Outils
    'analyse-couverture' => 'outils',
    'defaults' => 'outils',
    'variables' => 'outils',
    'convert-word-pdf' => 'outils',
    'ai-assistant' => 'outils',
    // Configuration
    'configuration' => 'configuration',
    'formes-juridiques' => 'configuration',
    'tribunaux' => 'configuration',
    'adresses' => 'configuration',
    'villes' => 'configuration',
    'nationalites' => 'configuration',
    'lieux-naissance' => 'configuration',
    'qualites-associe' => 'configuration',
    'fonctions' => 'configuration',
    'activites' => 'configuration',
    'activites-ompic' => 'configuration',
    'roles' => 'configuration',
    'role' => 'configuration',
    'activite' => 'configuration',
    'notifications-manage' => 'configuration',
    'notif-ajax' => 'configuration',
    'setup' => 'configuration',
];

// Custom filenames for pages where the file != the page name
$pageFile = [
    'cessions' => 'list',
    'cession' => 'wizard',
    'cession_dossier' => 'detail',
];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'not-found';
}

// JSON API endpoint — no HTML output at all
if ($page === 'notif-ajax') {
    ob_clean();
    $dir = $pageDir[$page] ?? '';
    $file = $pageFile[$page] ?? $page;
    require __DIR__ . '/pages/' . ($dir ? $dir . '/' : '') . $file . '.php';
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
    $dir = $pageDir[$page] ?? '';
    $file = $pageFile[$page] ?? $page;
    require __DIR__ . '/pages/' . ($dir ? $dir . '/' : '') . $file . '.php';
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
$dir = $pageDir[$page] ?? '';
$file = $pageFile[$page] ?? $page;
require __DIR__ . '/pages/' . ($dir ? $dir . '/' : '') . $file . '.php';
require __DIR__ . '/includes/footer.php';

ob_end_flush();
