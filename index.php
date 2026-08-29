<?php

declare(strict_types=1);

ob_start();

require __DIR__ . '/includes/amorcage.php';

$allowedPages = [
    'creation', 'configuration', 'centre',
    'formes-juridiques', 'tribunaux', 'adresses', 'villes',
    'nationalites', 'lieux-naissance', 'qualites-associe', 'fonctions',
    'activites', 'activites-ompic',
    'dashboard',
    'societe', 'societes', 'creations', 'domiciliations', 'societe_suivi', 'associe', 'associes', 'contrats',
    'collaborateur', 'collaborateurs',
    'generation', 'templates',
    'documents', 'download_all', 'dossier_download', 'suivi_pdf',
    'defaults', 'analyse-couverture', 'variables',
    'convert-word-pdf', 'ai-assistant',
    'setup', 'connexion', 'deconnexion',
    'roles', 'role', 'activite',
    'notifications', 'notifications-manage', 'notif-ajax',
    'modifications', 'cessions', 'cession', 'cession_dossier', 'cession_suivi',
    'pv_ago', 'pv_ago_wizard',
    'pv-templates',
];

$pageDir = [
    // Accueil
    'dashboard' => 'accueil',
    'notifications' => 'accueil',
    // Dossiers
    'creation' => 'dossiers',
    'societes' => 'dossiers',
    'creations' => 'dossiers',
    'domiciliations' => 'dossiers',
    'societe' => 'dossiers',
    'dossier_download' => 'dossiers',
    'societe_suivi' => 'dossiers',
    'suivi_pdf' => 'dossiers',
    'associes' => 'dossiers',
    'associe' => 'dossiers',
    'contrats' => 'dossiers',
    'collaborateurs' => 'dossiers',
    'collaborateur' => 'dossiers',
    // Modification juridique
    // Auth
    'connexion' => 'auth',
    'deconnexion' => 'auth',
    'not-found' => 'auth',
    // Modification juridique
    'modifications' => 'modification-juridique',
    'cessions' => 'modification-juridique/cession',
    'cession' => 'modification-juridique/cession',
    'cession_dossier' => 'modification-juridique/cession',
    'cession_suivi' => 'modification-juridique/cession',
    'pv_ago' => 'modification-juridique/pv_ago',
    'pv_ago_wizard' => 'modification-juridique/pv_ago/pv_ago_steps',
    // Templates de documents
    'templates' => 'templates',
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
    'centre' => 'configuration',
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
    'pv-templates' => 'configuration',
];

// Custom filenames for pages where the file != the page name
$pageFile = [
    'societes' => 'societes_liste',
    'creations' => 'creations_liste',
    'domiciliations' => 'domiciliations_liste',
    'societe' => 'societe_details',
    'societe_suivi' => 'societe_suivi',
    'associes' => 'associes_liste',
    'associe' => 'associe_details',
    'collaborateurs' => 'collaborateurs_liste',
    'collaborateur' => 'collaborateur_details',
    'contrats' => 'contrats_liste',
    'creation' => 'creation_steps/_main',
    'cessions' => 'cessions_liste',
    'cession' => 'cession_steps/_main',
    'cession_dossier' => 'cession_details_dossier',
    'cession_suivi' => 'cession_suivi',
    'modifications' => 'modifications_juridiques',
    'pv_ago' => 'pv_details',
    'pv_ago_wizard' => '_main',
];

$page = $_GET['page'] ?? 'dashboard';

// Redirection anciennes pages templates → templates unifie
if ($page === 'template' || $page === 'template_edit') {
    $action = $page === 'template_edit' ? 'editeur' : 'inspecteur';
    $params = $_GET;
    unset($params['page']);
    redirect_to('templates', array_merge(['action' => $action], $params));
}

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
    'centre' => "Centre d'affaires",
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
    'societe_suivi' => 'Suivi administratif',
    'creations' => 'Creations',
    'domiciliations' => 'Domiciliations',
    'associes' => 'Associes',
    'associe' => 'Fiche associe',
    'contrats' => 'Contrats',
    'collaborateur' => 'Fiche collaborateur',
    'collaborateurs' => 'Collaborateurs',
    'generation' => 'Generateur Documents',
    'download_all' => 'Telechargement...',
    'dossier_download' => 'Telechargement du dossier...',
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
    'cession' => 'Formulaire de Cession Des Parts Sociales',
    'cession_dossier' => 'Dossier de cession',
    'cession_suivi' => 'Suivi administratif',
    'pv_ago' => 'PV Assemblee Generale Ordinaire',
    'pv_ago_wizard' => 'PV Assemblee Generale Ordinaire',
    'pv-templates' => 'Modèles de résolutions PV',
];

// Public pages without sidebar layout
$noLayoutPages = ['connexion', 'deconnexion'];

if (in_array($page, $noLayoutPages, true)) {
$pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';

// Title dynamique pour templates unifie
if ($page === 'templates') {
    $action = (string) ($_GET['action'] ?? 'gestionnaire');
    $pageTitle = match ($action) {
        'inspecteur' => 'Template',
        'editeur' => 'Editeur de template',
        default => 'Templates',
    };
}
    require __DIR__ . '/includes/entete.php';
    $dir = $pageDir[$page] ?? '';
    $file = $pageFile[$page] ?? $page;
    require __DIR__ . '/pages/' . ($dir ? $dir . '/' : '') . $file . '.php';
    require __DIR__ . '/includes/pied_page.php';
    ob_end_flush();
    exit;
}

$pageTitle = $pageTitleMap[$page] ?? 'Center Domiciliation App';

// Titre dynamique du wizard selon le type (Nouvelle creation / Nouvelle domiciliation)
if ($page === 'creation') {
    $wizardType = $_GET['type'] ?? '';
    if (!in_array($wizardType, ['creation', 'domiciliation'], true)) {
        $wizardType = (string) ($_SESSION['creation_wizard']['societe']['societe_type_generation'] ?? '');
    }
    $pageTitle = $wizardType === 'creation' ? 'Nouvelle création' : 'Nouvelle domiciliation';
}

// Page-level permission check (except public pages)
$publicPages = ['setup', 'not-found'];
if (!in_array($page, $publicPages, true)) {
    require_page_access($page);
}

// Page header actions
$pageActions = '';
if (function_exists('has_permission')) {
    if ($page === 'pv_ago' && has_permission('pv_ago.create')) {
        $pageActions = '<a class="btn btn-next" href="' . e(app_url('pv_ago_wizard')) . '"><span class="material-symbols-outlined">add</span> Nouveau PV AGO</a>';
    }
    if ($page === 'roles' && has_permission('roles.create')) {
        $pageActions = '<a class="btn btn-next" href="' . e(app_url('role')) . '"><span class="material-symbols-outlined">add</span> Nouveau role</a>';
    }
    if ($page === 'cession') {
        $pageActions = '<a class="btn btn-cancel" href="' . e(app_url('cessions')) . '"><span class="material-symbols-outlined">close</span> Annuler</a>';
        $pageActions .= '<a class="btn btn-back" href="' . e(app_url('cession', ['reset' => '1'])) . '" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>';
    }
    if ($page === 'associe' && !empty($_GET['id'])) {
        $associeId = (int) $_GET['id'];
        if (!isset($pdo) || !$pdo) {
            $pageActions = '';
        } else {
            $stmt = $pdo->prepare('SELECT associe_nom_complet FROM associes WHERE id = :id');
            $stmt->execute(['id' => $associeId]);
            $associeForActions = $stmt->fetch();
            if ($associeForActions) {
                if (isset($_GET['edit'])) {
                    $pageActions = '<a class="btn btn-cancel" href="' . e(app_url('associe', ['id' => $associeId])) . '"><span class="material-symbols-outlined">close</span> Annuler</a>';
                } else {
                    $pageActions = '<a class="btn btn-info" href="' . e(app_url('associe', ['id' => $associeId, 'edit' => '1'])) . '"><span class="material-symbols-outlined">edit</span> Modifier</a>';
                    $pageActions .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Supprimer cet associe ? Cette action est irreversible.\');">' . csrf_input() . '<input type="hidden" name="_action" value="delete"><button class="btn btn-danger" type="submit"><span class="material-symbols-outlined">delete</span> Supprimer</button></form>';
                }
                $pageActions .= '<a class="btn btn-back" href="' . e(app_url('associes')) . '"><span class="material-symbols-outlined">arrow_back</span> Retour</a>';
            }
        }
        unset($associeForActions, $stmt);
    }
    if ($page === 'societe_suivi' && !empty($_GET['id'])) {
        $suiviId = (int) $_GET['id'];
        if (!isset($pdo) || !$pdo) {
            $pageActions = '';
        } else {
            $stmt = $pdo->prepare('SELECT societe_raison_sociale, societe_type_generation FROM societes WHERE id = :id');
            $stmt->execute(['id' => $suiviId]);
            $suiviSociete = $stmt->fetch();
            if ($suiviSociete) {
                $pageSubtitle = (string) ($suiviSociete['societe_raison_sociale'] ?? '');
                $suiviIsCreation = (string) ($suiviSociete['societe_type_generation'] ?? '') === 'creation';
                $suiviKanban = isset($_GET['view']) && $_GET['view'] === 'kanban';
                $pageActions = '<div class="view-toggle">'
                    . '<button class="' . (!$suiviKanban ? 'active' : '') . '" onclick="location.href=\'' . e(app_url('societe_suivi', ['id' => $suiviId])) . '\'"><span class="material-symbols-outlined" style="font-size:1rem">view_list</span> Detail</button>'
                    . '<button class="' . ($suiviKanban ? 'active' : '') . '" onclick="location.href=\'' . e(app_url('societe_suivi', ['id' => $suiviId, 'view' => 'kanban'])) . '\'"><span class="material-symbols-outlined" style="font-size:1rem">view_kanban</span> Pipeline</button>'
                    . '</div>';
                $pageActions .= '<a class="btn btn-info" href="' . e(app_url('suivi_pdf', ['id' => $suiviId])) . '" target="_blank"><span class="material-symbols-outlined">picture_as_pdf</span> PDF</a>';
                $pageActions .= '<a class="btn btn-info" href="' . e(app_url('societe', ['id' => $suiviId])) . '"><span class="material-symbols-outlined">info</span> Fiche</a>';
                $pageActions .= '<a class="btn btn-back" href="' . e(app_url($suiviIsCreation ? 'creations' : 'domiciliations')) . '"><span class="material-symbols-outlined">arrow_back</span> Retour</a>';
            }
        }
        unset($suiviSociete, $stmt, $suiviIsCreation, $suiviKanban);
    }
}

require __DIR__ . '/includes/entete.php';
$dir = $pageDir[$page] ?? '';
$file = $pageFile[$page] ?? $page;
require __DIR__ . '/pages/' . ($dir ? $dir . '/' : '') . $file . '.php';
require __DIR__ . '/includes/pied_page.php';

ob_end_flush();
