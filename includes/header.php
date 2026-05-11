<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($config['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="shell">
    <?php require __DIR__ . '/nav.php'; ?>
    <main class="main">
        <header class="page-header">
            <div>
                <p class="eyebrow">XAMPP + PHP + MySQL</p>
                <h1><?= e($pageTitle) ?></h1>
            </div>
        </header>

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

