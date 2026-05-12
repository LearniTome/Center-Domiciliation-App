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
    'Systeme' => [
        'configuration' => ['Configuration', 'mdi-cog'],
    ],
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-badge">
            <span class="mdi mdi-home-city"></span>
        </span>
        <div>
            <strong>Center Domiciliation</strong>
        </div>
    </div>

    <nav class="nav-links">
        <?php foreach ($navSections as $sectionLabel => $items): ?>
            <?php if ($sectionLabel): ?>
                <span class="nav-section-label"><?= e($sectionLabel) ?></span>
            <?php endif; ?>
            <?php foreach ($items as $navPage => [$label, $icon]): ?>
                <a class="<?= $page === $navPage ? 'active' : '' ?>" href="<?= e(app_url($navPage)) ?>">
                    <span class="mdi <?= e($icon) ?>"></span>
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
</aside>
