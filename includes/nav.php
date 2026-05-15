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
    'Juridique' => [
        ['page' => 'configuration', 'label' => 'Formes juridiques', 'icon' => 'mdi-file-document-outline', 'params' => ['tab' => 'formes-juridiques']],
        ['page' => 'configuration', 'label' => 'Tribunaux', 'icon' => 'mdi-scale-balance', 'params' => ['tab' => 'tribunaux']],
    ],
    'Geographique' => [
        ['page' => 'configuration', 'label' => 'Villes', 'icon' => 'mdi-city', 'params' => ['tab' => 'villes']],
        ['page' => 'configuration', 'label' => 'Nationalites', 'icon' => 'mdi-flag', 'params' => ['tab' => 'nationalites']],
        ['page' => 'configuration', 'label' => 'Lieux naissance', 'icon' => 'mdi-map-marker', 'params' => ['tab' => 'lieux-naissance']],
        ['page' => 'configuration', 'label' => 'Adresses', 'icon' => 'mdi-home', 'params' => ['tab' => 'adresses']],
    ],
    'Associes' => [
        ['page' => 'configuration', 'label' => 'Qualites associe', 'icon' => 'mdi-account-tie', 'params' => ['tab' => 'qualites-associe']],
    ],
    'Activites' => [
        ['page' => 'configuration', 'label' => 'Activites', 'icon' => 'mdi-briefcase', 'params' => ['tab' => 'activites']],
        ['page' => 'configuration', 'label' => 'NMA2010', 'icon' => 'mdi-file-certificate', 'params' => ['tab' => 'certificat-negatif']],
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
                <span class="nav-section-label"><?= e($sectionLabel) ?></span>
            <?php endif; ?>
            <?php foreach ($items as $navKey => $item): ?>
                <?php
                    if (is_array($item) && isset($item['label'])) {
                        $itemPage = $item['page'];
                        $itemLabel = $item['label'];
                        $itemIcon = $item['icon'];
                        $itemParams = $item['params'] ?? [];
                        $href = app_url($itemPage, $itemParams);
                        $isActive = $page === $itemPage && (!$itemParams || ($_GET['tab'] ?? '') === ($itemParams['tab'] ?? ''));
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
        <?php endforeach; ?>
    </nav>
</aside>
