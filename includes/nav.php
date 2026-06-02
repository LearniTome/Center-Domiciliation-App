<?php

declare(strict_types=1);

$navSections = [
    '' => [
        'creation' => ['Nouveau dossier', 'note_add'],
        'dashboard' => ['Tableau de bord', 'dashboard'],
    ],
    'Dossiers' => [
        'societes' => ['Societes', 'business'],
        'associes' => ['Associes', 'group'],
        'contrats' => ['Contrats', 'description'],
        'collaborateurs' => ['Collaborateurs', 'work'],
    ],
    'Templates de documents' => [
        'templates' => ['Templates', 'edit_note'],
        'template_edit' => ['Editeur de template', 'edit'],
        'generation' => ['Generateur de dossiers', 'sync'],
        'documents' => ['Documents generes', 'article'],
    ],
    'Outils' => [
        'analyse-couverture' => ['Analyse de couverture', 'bar_chart'],
        'defaults' => ['Valeurs par defaut', 'tune'],
        'variables' => ['Gestion des variables', 'code'],
        'convert-word-pdf' => ['Word to PDF', 'picture_as_pdf'],
        'ai-assistant' => ['Assistant IA', 'smart_toy'],
    ],
    'Configuration' => [
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
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-badge">
            <span class="material-symbols-outlined">location_city</span>
        </span>
        <div class="brand-text">
            <strong>Center Domiciliation</strong>
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
        <?php foreach ($navSections as $sectionLabel => $items): ?>
            <?php if ($sectionLabel): ?>
            <div class="nav-section">
                <button class="nav-section-toggle" type="button" data-nav-toggle>
                    <span class="material-symbols-outlined">expand_more</span>
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
