<?php

declare(strict_types=1);

$navSections = [
    '' => [
        'creation' => ['Nouveau dossier', 'mdi-file-plus'],
        'dashboard' => ['Tableau de bord', 'mdi-view-dashboard'],
    ],
    'Dossiers' => [
        'societes' => ['Societes', 'mdi-domain'],
        'associes' => ['Associes', 'mdi-account-group'],
        'contrats' => ['Contrats', 'mdi-file-document'],
        'collaborateurs' => ['Collaborateurs', 'mdi-briefcase'],
    ],
    'Templates de documents' => [
        'templates' => ['Liste des templates', 'mdi-file-document-edit'],
        'generation' => ['Generation', 'mdi-file-sync'],
        'documents' => ['Documents generes', 'mdi-file-word'],
    ],
    'Outils' => [
        'defaults' => ['Valeurs par defaut', 'mdi-tune'],
        'convert-word-pdf' => ['Word to PDF', 'mdi-file-pdf-box'],
    ],
    'Configuration' => [
        ['page' => 'formes-juridiques', 'label' => 'Formes juridiques', 'icon' => 'mdi-file-document-outline'],
        ['page' => 'tribunaux', 'label' => 'Tribunaux', 'icon' => 'mdi-scale-balance'],
        ['page' => 'villes', 'label' => 'Villes', 'icon' => 'mdi-city'],
        ['page' => 'nationalites', 'label' => 'Nationalites', 'icon' => 'mdi-flag'],
        ['page' => 'lieux-naissance', 'label' => 'Lieux naissance', 'icon' => 'mdi-map-marker'],
        ['page' => 'adresses', 'label' => 'Adresses', 'icon' => 'mdi-home'],
        ['page' => 'qualites-associe', 'label' => 'Qualites associe', 'icon' => 'mdi-account-tie'],
        ['page' => 'activites', 'label' => 'Activites', 'icon' => 'mdi-briefcase'],
        ['page' => 'certificat-negatif', 'label' => 'NMA2010', 'icon' => 'mdi-file-certificate'],
    ],
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-badge">
            <span class="mdi mdi-home-city"></span>
        </span>
        <div class="brand-text">
            <strong>Center Domiciliation</strong>
        </div>
        <button class="sidebar-toggle" data-sidebar-toggle type="button" title="Reduire la barre de navigation">
            <span class="mdi mdi-chevron-left"></span>
        </button>
    </div>

    <nav class="nav-links">
        <?php foreach ($navSections as $sectionLabel => $items): ?>
            <?php if ($sectionLabel): ?>
            <div class="nav-section">
                <button class="nav-section-toggle" type="button" data-nav-toggle>
                    <span class="mdi mdi-chevron-down"></span>
                    <?= e($sectionLabel) ?>
                </button>
                <div class="nav-section-items">
            <?php endif; ?>
            <?php foreach ($items as $navKey => $item): ?>
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
                    <span class="mdi <?= e($itemIcon) ?>"></span>
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
