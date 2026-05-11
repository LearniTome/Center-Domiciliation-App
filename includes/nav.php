<?php

declare(strict_types=1);

$navItems = [
    'creation' => 'Nouveau dossier',
    'dashboard' => 'Tableau de bord',
    'societes' => 'Societes',
    'associes' => 'Associes',
    'contrats' => 'Contrats',
    'collaborateurs' => 'Collaborateurs',
    'documents' => 'Documents',
    'setup' => 'Installation',
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-badge">CD</span>
        <div>
            <strong>Center Domiciliation</strong>
            <p>Migration PHP</p>
        </div>
    </div>

    <nav class="nav-links">
        <?php foreach ($navItems as $navPage => $label): ?>
            <a class="<?= $page === $navPage ? 'active' : '' ?>" href="<?= e(app_url($navPage)) ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <button class="theme-toggle" id="theme-toggle" type="button">
        <span id="theme-icon">&#9790;</span>
        <span id="theme-label">Mode sombre</span>
    </button>

    <div class="sidebar-note">
        <p>Version de depart pour la migration depuis l'application desktop vers une interface web XAMPP.</p>
    </div>
</aside>
