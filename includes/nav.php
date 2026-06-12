<?php

declare(strict_types=1);

// Map each nav item to a required permission (null = always visible)
$navPermissions = [
    'creation' => 'wizard.create',
    'notifications' => null,
    'notifications-manage' => 'roles.manage',
    'dashboard' => 'dashboard.view',
    'societes' => 'societes.view',
    'associes' => 'associes.view',
    'contrats' => 'contrats.view',
    'collaborateurs' => 'collaborateurs.view',
    'templates' => 'templates.view',
    'template_edit' => 'templates.edit',
    'cessions' => 'cessions.view',
    'cession' => 'cessions.create',
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
    'tribunaux' => 'configuration.view',
    'villes' => 'configuration.view',
    'nationalites' => 'configuration.view',
    'lieux-naissance' => 'configuration.view',
    'adresses' => 'configuration.view',
    'qualites-associe' => 'configuration.view',
    'fonctions' => 'configuration.view',
    'activites' => 'configuration.view',
    'activites-ompic' => 'configuration.view',
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
            'creation' => ['Nouveau dossier', 'note_add'],
            'dashboard' => ['Tableau de bord', 'dashboard'],
            'notifications' => ['Notifications', 'notifications'],
        ],
    ],
    'Dossiers' => [
        'icon' => 'folder',
        'items' => [
            'societes' => ['Societes', 'business'],
            'associes' => ['Associes', 'group'],
            'contrats' => ['Contrats', 'description'],
            'collaborateurs' => ['Collaborateurs', 'work'],
        ],
    ],
    'Modification juridique' => [
        'icon' => 'swap_horiz',
        'items' => [
            'cessions' => ['Cession de parts sociales', 'transfer_within_a_station'],
        ],
    ],
    'Templates de documents' => [
        'icon' => 'article',
        'items' => [
            'templates' => ['Templates', 'edit_note'],
            'template_edit' => ['Editeur de template', 'edit'],
            'generation' => ['Generateur de dossiers', 'sync'],
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
        ],
    ],
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-badge">
            <span class="material-symbols-outlined">location_city</span>
        </span>
        <div class="brand-text">
            <strong>Center Domiciliation</strong>
            <?php if (is_logged_in()): ?>
                <small style="display:block;font-size:0.6rem;color:var(--text-secondary);margin-top:2px;">
                    <?= e(get_role_name()) ?>
                </small>
            <?php endif; ?>
        </div>
    </div>

    <?php
        $notifCount = 0;
        if (is_logged_in()) {
            $u = current_user();
            global $pdo;
            if ($u && $pdo) {
                $notifCount = count_unread_notifications($pdo, (int) $u['id'], (int) ($u['role_id'] ?? 0), $u['collaborateur_type'] ?? null);
            }
        }
    ?>
    <div class="notif-bell-wrap">
        <button type="button" class="notif-bell" data-notif-bell title="Notifications">
            <span class="material-symbols-outlined">notifications</span>
            <?php if ($notifCount > 0): ?>
                <span class="notif-badge notif-badge-count"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>
            <?php endif; ?>
            <span data-nav-label>Notifications</span>
        </button>
        <div class="notif-dropdown" data-notif-dropdown>
            <div class="notif-dropdown-header">
                <strong>Notifications</strong>
                <span class="notif-dropdown-count" data-notif-dropdown-count></span>
            </div>
            <div class="notif-dropdown-list" data-notif-dropdown-list>
                <div class="notif-dropdown-loading">
                    <span class="material-symbols-outlined">sync</span> Chargement...
                </div>
            </div>
            <div class="notif-dropdown-footer">
                <button type="button" class="btn btn-info" data-notif-mark-all style="width:100%;padding:4px 10px;font-size:0.72rem;">
                    <span class="material-symbols-outlined" style="font-size:0.85rem">done_all</span> Tout marquer comme lu
                </button>
                <a href="<?= e(app_url('notifications')) ?>" class="btn btn-next" style="width:100%;padding:4px 10px;font-size:0.72rem;">
                    <span class="material-symbols-outlined" style="font-size:0.85rem">visibility</span> Voir tout
                </a>
            </div>
        </div>
    </div>

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
                    if (nav_item_visible($itemPage, $navPermissions)) {
                        $visibleItems[$navKey] = $item;
                    }
                }
            ?>
            <?php if (empty($visibleItems)) { continue; } ?>
            <?php if ($sectionLabel): ?>
            <div class="nav-section">
                <button class="nav-section-toggle" type="button" data-nav-toggle data-label="<?= e($sectionLabel) ?>">
                    <span class="material-symbols-outlined section-icon"><?= e($section['icon']) ?></span>
                    <span class="nav-section-label"><?= e($sectionLabel) ?></span>
                    <span class="material-symbols-outlined section-chevron">expand_more</span>
                </button>
                <div class="nav-section-items">
            <?php endif; ?>
            <?php foreach ($visibleItems as $navKey => $item): ?>
                <?php
                    if (is_array($item) && isset($item['label'])) {
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
                <a class="<?= $isActive ? 'active' : '' ?>" href="<?= e($href) ?>" data-nav-link>
                    <span class="material-symbols-outlined"><?= e($itemIcon) ?></span>
                    <span data-nav-label><?= e($itemLabel) ?></span>
                    <?php if ($navKey === 'notifications'): ?>
                        <span class="notif-badge" id="nav-notif-badge" style="position:static;margin-left:auto;margin-right:8px;<?= $notifCount > 0 ? '' : 'display:none' ?>"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <?php if ($sectionLabel): ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= e(app_url('deconnexion')) ?>" class="nav-logout" data-nav-link>
            <span class="material-symbols-outlined">logout</span>
            <span data-nav-label>Deconnexion</span>
        </a>
    </div>
</aside>