<?php

declare(strict_types=1);

$stats = [
    'Societes' => dashboard_count($pdo ?? null, 'societes'),
    'Associes' => dashboard_count($pdo ?? null, 'associes'),
    'Contrats' => dashboard_count($pdo ?? null, 'contrats'),
    'Collaborateurs' => dashboard_count($pdo ?? null, 'collaborateurs'),
];
?>
<section class="stats">
    <?php foreach ($stats as $label => $count): ?>
        <article class="stat">
            <span><?= e($label) ?></span>
            <strong><?= e((string) $count) ?></strong>
        </article>
    <?php endforeach; ?>
</section>

<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Vue d'ensemble</h2>
                <p class="help-text">Base de depart pour la migration web de l'application de domiciliation.</p>
            </div>
        </div>
        <div class="stack">
            <span class="pill">CRUD societes</span>
            <span class="pill">CRUD associes</span>
            <span class="pill">CRUD contrats</span>
            <span class="pill">CRUD collaborateurs</span>
            <span class="pill">Connexion PDO securisee</span>
            <span class="pill">Schema MySQL pour phpMyAdmin</span>
        </div>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Prochaines etapes</h2>
                <p class="help-text">Migration progressive des workflows metier restants.</p>
            </div>
        </div>
        <div class="stack">
            <p>1. Verifier le schema avec vos vraies donnees.</p>
            <p>2. Ajouter les champs supplementaires metier.</p>
            <p>3. Migrer la generation documentaire Word/PDF.</p>
            <p>4. Ajouter authentification, recherche et exports.</p>
        </div>
    </article>
</section>

