<?php

declare(strict_types=1);

ob_start();

$noSidebar = in_array($page ?? '', ['connexion', 'deconnexion'], true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($config['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <style>
        <?php if ($noSidebar): ?>
        .shell, .shell.collapsed { grid-template-columns: 1fr; }
        .sidebar, .sidebar-toggle { display: none; }
        .main { padding: 2rem; }
        .main::after { display: none !important; }
        <?php endif; ?>
    </style>
</head>
<body>
<div class="shell<?= $noSidebar ? '' : '' ?>">
    <?php if (!$noSidebar): ?>
    <?php require __DIR__ . '/navigation.php'; ?>
    <button class="sidebar-toggle" data-sidebar-toggle type="button" title="Reduire la barre de navigation">
        <span class="material-symbols-outlined">chevron_left</span>
    </button>
    <script>try{var r=localStorage.getItem('nav_sections');if(r){var s=JSON.parse(r);document.querySelectorAll('[data-nav-toggle]').forEach(function(b){var l=b.getAttribute('data-label');if(l&&s[l]){b.closest('.nav-section').classList.add('collapsed')}})}}catch(e){}
    try{var a=localStorage.getItem('sidebar_collapsed');if(a==='1'){document.querySelector('.shell').classList.add('collapsed')}}catch(e){}
    </script>
    <?php endif; ?>
    <div class="shell-body">
        <?php if (is_logged_in()):
            $_topUser = current_user();
            $_topNotifCount = 0;
            if ($_topUser && $pdo) {
                $_topNotifCount = count_unread_notifications($pdo, (int) $_topUser['id'], (int) ($_topUser['role_id'] ?? 0), $_topUser['collaborateur_type'] ?? null);
                // Track user session (current page, last active)
                update_user_session($pdo, $currentPage);
                // Log unique page views (once per session)
                log_page_view($pdo, $currentPage);
            }
        ?>
        <div class="top-bar">
            <div class="top-bar-left">
                <span class="material-symbols-outlined top-bar-avatar">account_circle</span>
                <span class="top-bar-user"><?= e($_topUser['nom_complet'] ?? '') ?></span>
                <span class="top-bar-role"><?= e(get_role_name()) ?></span>
            </div>
            <div class="top-bar-center">
                <span class="top-bar-clock" id="top-bar-clock"></span>
            </div>
            <div class="top-bar-right">
                <div class="notif-bell-wrap">
                    <button type="button" class="notif-bell" data-notif-bell title="Notifications">
                        <span class="material-symbols-outlined">notifications</span>
                        <?php if ($_topNotifCount > 0): ?>
                            <span class="notif-badge notif-badge-count"><?= $_topNotifCount > 99 ? '99+' : $_topNotifCount ?></span>
                        <?php endif; ?>
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
                <a href="<?= e(app_url('deconnexion')) ?>" class="top-bar-logout" title="Deconnexion">
                    <span class="material-symbols-outlined">logout</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <main class="main">
            <?php if (!is_logged_in()): ?>
            <header class="page-header">
                <div>
                    <h1><?= e($pageTitle) ?></h1>
                    <?php if (isset($pageSubtitle)): ?>
                    <p class="page-subtitle"><?= e($pageSubtitle) ?></p>
                    <?php endif; ?>
                    <span class="page-count-bar"></span>
                </div>
            </header>
            <?php else: ?>
            <header class="page-header">
                <div>
                    <h1><?= e($pageTitle) ?></h1>
                    <?php if (isset($pageSubtitle)): ?>
                    <p class="page-subtitle"><?= e($pageSubtitle) ?></p>
                    <?php endif; ?>
                    <span class="page-count-bar"></span>
                </div>
            </header>
            <?php endif; ?>

        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($dbError !== null): ?>
            <div class="flash flash-error">
                Connexion MySQL impossible. Verifiez XAMPP, phpMyAdmin et `config/database.php`.
                <br>
                <small><?= e($dbError) ?></small>
            </div>
        <?php endif; ?>

