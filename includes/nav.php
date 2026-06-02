<?php

declare(strict_types=1);

// Map each nav item to a required permission (null = always visible)
$navPermissions = [
    'creation' => 'wizard.create',
    'dashboard' => 'dashboard.view',
    'societes' => 'societes.view',
    'associes' => 'associes.view',
    'contrats' => 'contrats.view',
    'collaborateurs' => 'collaborateurs.view',
    'templates' => 'templates.view',
    'template_edit' => 'templates.edit',
    'generation' => 'generation.use',
    'documents' => 'documents.view',
    'analyse-couverture' => 'analyse.view',
    'defaults' => 'defaults.edit',
    'variables' => 'variables.view',
    'convert-word-pdf' => 'convert.use',
    'ai-assistant' => 'ai.use',
    'roles' => 'roles.manage',
    // Configuration sub-pages use configuration.view
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
            ['page' => 'formes-juridiques', 'label' => 'Formes juridiques', 'icon' => 'description'],
            ['page' => 'tribunaux', 'label' => 'Tribunaux', 'icon' => 'balance'],
            ['page' => 'villes', 'label' => 'Villes', 'icon' => 'location_city'],
            ['page' => 'nationalites', 'label' => 'Nationalites', 'icon' => 'flag'],
            ['page' => 'lieux-naissance', 'label' => 'Lieux de naissance', 'icon' => 'location_on'],
            ['page' => 'adresses', 'label' => 'Adresses', 'icon' => 'home'],
            ['page' => 'qualites-associe', 'label' => 'Qualites associe', 'icon' => 'badge'],
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
                </a>
            <?php endforeach; ?>
            <?php if ($sectionLabel): ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>