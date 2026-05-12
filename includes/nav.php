<?php

declare(strict_types=1);

$navItems = [
    'creation' => ['Nouveau dossier', 'mdi-file-plus'],
    'dashboard' => ['Tableau de bord', 'mdi-view-dashboard'],
    'societes' => ['Societes', 'mdi-domain'],
    'associes' => ['Associes', 'mdi-account-group'],
    'contrats' => ['Contrats', 'mdi-file-document'],
    'collaborateurs' => ['Collaborateurs', 'mdi-briefcase'],
    'generation' => ['Generation', 'mdi-file-sync'],
    'templates' => ['Templates', 'mdi-file-document-outline'],
    'documents' => ['Documents', 'mdi-folder'],
    'configuration' => ['Configuration', 'mdi-cog'],
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
        <?php foreach ($navItems as $navPage => [$label, $icon]): ?>
            <a class="<?= $page === $navPage ? 'active' : '' ?>" href="<?= e(app_url($navPage)) ?>">
                <span class="mdi <?= e($icon) ?>"></span>
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
