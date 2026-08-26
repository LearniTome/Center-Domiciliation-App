<?php

declare(strict_types=1);

// Map each nav item to a required permission (null = always visible)
$navPermissions = [
    'creation' => 'wizard.create',
    'notifications' => null,
    'notifications-manage' => 'roles.manage',
    'dashboard' => 'dashboard.view',
    'societes' => 'societes.view',
    'creations' => 'societes.view',
    'domiciliations' => 'societes.view',
    'associes' => 'associes.view',
    'contrats' => 'contrats.view',
    'collaborateurs' => 'collaborateurs.view',
    'templates' => 'templates.view',
    'modifications' => 'modifications.view',
    'cessions' => 'cessions.view',
    'cession' => 'cessions.create',
    'cession_suivi' => 'cessions.suivi',
    'pv_ago' => 'pv_ago.view',
    'pv_ago' => 'pv_ago.create',
    'generation' => 'generation.use',
    'documents' => 'documents.view',
    'analyse-couverture' => 'analyse.view',
    'defaults' => 'defaults.edit',
    'variables' => 'variables.view',
    'convert-word-pdf' => 'convert.use',
    'ai-assistant' => 'ai.use',
    'roles' => 'roles.manage',
    'activite' => 'roles.manage',
    'formes-juridiques' => 'configuration.view',
    'centre' => 'configuration.view',
    'tribunaux' => 'configuration.view',
    'villes' => 'configuration.view',
    'nationalites' => 'configuration.view',
    'lieux-naissance' => 'configuration.view',
    'adresses' => 'configuration.view',
    'qualites-associe' => 'configuration.view',
    'fonctions' => 'configuration.view',
    'activites' => 'configuration.view',
    'activites-ompic' => 'configuration.view',
    'pv-templates' => 'pv_resolutions.view',
];

function nav_item_visible(string $page, array $permMap): bool
{
    $perm = $permMap[$page] ?? null;
    if ($perm === null) {
        return true;
    }
    return has_permission($perm);
}

$navSections = [
    '' => [
        'icon' => null,
        'items' => [
            'dashboard' => ['Tableau de bord', 'dashboard'],
            'notifications' => ['Notifications', 'notifications'],
        ],
    ],
    'Dossiers' => [
        'icon' => 'folder',
        'items' => [
            'creations' => ['Creations', 'rocket_launch'],
            'domiciliations' => ['Domiciliations', 'business'],
            'societes' => ['Societes', 'domain'],
            'associes' => ['Associes', 'group'],
            'contrats' => ['Contrats', 'description'],
            'collaborateurs' => ['Collaborateurs', 'work'],
            'societe_suivi' => ['Suivi administratif', 'checklist'],
        ],
    ],
    'Modification juridique' => [
        'icon' => 'swap_horiz',
        'items' => [
            'modifications' => ['Toutes les modifications', 'list_alt'],
            'cessions' => ['Cession de parts', 'transfer_within_a_station'],
            'cession_suivi' => ['Suivi administratif', 'checklist'],
            'pv_ago' => ['PV Assemblee Generale', 'groups'],
        ],
    ],
    'Templates de documents' => [
        'icon' => 'article',
        'items' => [
            'templates' => ['Templates', 'edit_note'],
            'generation' => ['Generateur Documents', 'sync'],
            'documents' => ['Documents generes', 'article'],
        ],
    ],
    'Outils' => [
        'icon' => 'build',
        'items' => [
            'analyse-couverture' => ['Analyse de couverture', 'bar_chart'],
            'defaults' => ['Valeurs par defaut', 'tune'],
            'variables' => ['Gestion des variables', 'code'],
            'convert-word-pdf' => ['Word to PDF', 'picture_as_pdf'],
            'ai-assistant' => ['Assistant IA', 'smart_toy'],
        ],
    ],
    'Configuration' => [
        'icon' => 'settings',
        'items' => [
            ['page' => 'roles', 'label' => 'Gestion des roles', 'icon' => 'admin_panel_settings'],
            ['page' => 'activite', 'label' => 'Journal d\'activite', 'icon' => 'history'],
            ['page' => 'notifications-manage', 'label' => 'Gestion des notifications', 'icon' => 'notifications'],
            ['page' => 'centre', 'label' => 'Centre d\'affaires', 'icon' => 'apartment'],
            ['page' => 'formes-juridiques', 'label' => 'Formes juridiques', 'icon' => 'description'],
            ['page' => 'tribunaux', 'label' => 'Tribunaux', 'icon' => 'balance'],
            ['page' => 'villes', 'label' => 'Villes', 'icon' => 'location_city'],
            ['page' => 'nationalites', 'label' => 'Nationalites', 'icon' => 'flag'],
            ['page' => 'lieux-naissance', 'label' => 'Lieux de naissance', 'icon' => 'location_on'],
            ['page' => 'adresses', 'label' => 'Adresses', 'icon' => 'home'],
            ['page' => 'qualites-associe', 'label' => 'Qualites associe', 'icon' => 'badge'],
            ['page' => 'fonctions', 'label' => 'Fonctions', 'icon' => 'assignment'],
            ['page' => 'activites', 'label' => 'Activites', 'icon' => 'work'],
            ['page' => 'activites-ompic', 'label' => 'Activites Ompic', 'icon' => 'verified'],
            ['page' => 'pv-templates', 'label' => 'Modèles de résolutions PV', 'icon' => 'playlist_add_check'],
        ],
    ],
];
?>
<aside class="sidebar">
    <?php
        $_navNotifCount = 0;
        if (is_logged_in()) {
            $_navUser = current_user();
            global $pdo;
            if ($_navUser && $pdo) {
                $_navNotifCount = count_unread_notifications($pdo, (int) $_navUser['id'], (int) ($_navUser['role_id'] ?? 0), $_navUser['collaborateur_type'] ?? null);
            }
        }
    ?>
    <div class="sidebar-scroll">
        <div class="nav-toggle-all">
            <button type="button" title="Tout réduire" data-collapse-all>
                <span class="material-symbols-outlined">collapse_all</span>
            </button>
            <button type="button" title="Tout déployer" data-expand-all>
                <span class="material-symbols-outlined">expand_all</span>
            </button>
        </div>

        <nav class="nav-links">
            <?php foreach ($navSections as $sectionLabel => $section): ?>
                <?php $items = $section['items']; ?>
                <?php
                    // Filter items by permission
                    $visibleItems = [];
                    foreach ($items as $navKey => $item) {
                        if (is_array($item) && isset($item['page'])) {
                            $itemPage = $item['page'];
                        } else {
                            $itemPage = $navKey;
                        }
                        $perm = $navPermissions[$itemPage] ?? null;
                        if ($perm !== null && !has_permission($perm)) continue;
                        $visibleItems[$navKey] = $item;
                    }
                ?>
                <?php if (empty($visibleItems)) { continue; } ?>
                <?php if ($sectionLabel): ?>
                <div class="nav-section">
                    <button class="nav-section-toggle" type="button" data-nav-toggle data-label="<?= e($sectionLabel) ?>" title="<?= e($sectionLabel) ?>">
                        <span class="material-symbols-outlined section-icon"><?= e($section['icon']) ?></span>
                        <span class="nav-section-label"><?= e($sectionLabel) ?></span>
                        <span class="material-symbols-outlined section-chevron">expand_more</span>
                    </button>
                    <div class="nav-section-items">
                <?php endif; ?>
                <?php foreach ($visibleItems as $navKey => $item): ?>
                    <?php
                        if (is_array($item) && isset($item['page'])) {
                            $itemPage = $item['page'];
                            $itemLabel = $item['label'];
                            $itemIcon = $item['icon'];
                            $href = app_url($itemPage);
                            $isActive = $page === $itemPage;
                        } else {
                            $itemLabel = $item[0];
                            $itemIcon = $item[1];
                            $href = app_url($navKey);
                            $isActive = $page === $navKey;
                        }
                    ?>
                    <a class="<?= $isActive ? 'active' : '' ?>" href="<?= e($href) ?>" data-nav-link title="<?= e($itemLabel) ?>">
                        <span class="material-symbols-outlined"><?= e($itemIcon) ?></span>
                        <span data-nav-label><?= e($itemLabel) ?></span>
                        <?php if ($navKey === 'notifications'): ?>
                            <span class="notif-badge" id="nav-notif-badge" style="position:static;margin-left:auto;margin-right:8px;<?= $_navNotifCount > 0 ? '' : 'display:none' ?>"><?= $_navNotifCount > 99 ? '99+' : $_navNotifCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if ($sectionLabel): ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

</aside>