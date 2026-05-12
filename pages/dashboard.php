<?php

declare(strict_types=1);

$stats = [
    'Societes' => dashboard_count($pdo ?? null, 'societes'),
    'Associes' => dashboard_count($pdo ?? null, 'associes'),
    'Contrats' => dashboard_count($pdo ?? null, 'contrats'),
    'Collaborateurs' => dashboard_count($pdo ?? null, 'collaborateurs'),
];

$recentSocietes = ($pdo ?? null) instanceof PDO
    ? $pdo->query('SELECT id, raison_sociale, forme_juridique, ville FROM societes ORDER BY id DESC LIMIT 5')->fetchAll()
    : [];

$recentContrats = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT contrats.id, contrats.type_contrat, contrats.statut, societes.raison_sociale
        FROM contrats
        INNER JOIN societes ON societes.id = contrats.societe_id
        ORDER BY contrats.id DESC
        LIMIT 5
    ')->fetchAll()
    : [];

$recentAssocies = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT associes.nom_complet, associes.cin, associes.qualite_associe, associes.is_gerant, societes.raison_sociale
        FROM associes
        INNER JOIN societes ON societes.id = associes.societe_id
        ORDER BY associes.id DESC
        LIMIT 5
    ')->fetchAll()
    : [];

$recentCollaborateurs = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT nom_complet, collaborateur_type, fonction, statut
        FROM collaborateurs
        ORDER BY id DESC
        LIMIT 5
    ')->fetchAll()
    : [];
?>
<section class="card hero-card stack">
    <div class="section-header">
        <div>
            <h2>Lancer un nouveau dossier</h2>
            <p class="help-text">Flux guide en 3 etapes: societe, associes, puis contrat, comme dans la version Tkinter.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(app_url('creation')) ?>">Creer un dossier</a>
    </div>
    <div class="stack">
        <span class="pill">Etape 1: informations societe</span>
        <span class="pill">Etape 2: un ou plusieurs associes</span>
        <span class="pill">Etape 3: contrat et validation</span>
    </div>
</section>

<section class="stats">
    <?php foreach ($stats as $label => $count): ?>
        <article class="stat">
            <span><?= e($label) ?></span>
            <strong><?= e((string) $count) ?></strong>
        </article>
    <?php endforeach; ?>
</section>

<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Dernieres societes</h2>
                <p class="help-text">Acces rapide aux fiches societes creees recemment.</p>
            </div>
            <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>">Voir tout</a>
        </div>
        <?php if (!$recentSocietes): ?>
            <p class="table-empty">Aucune societe disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Societe</th>
                    <th>Forme</th>
                    <th>Ville</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentSocietes as $societe): ?>
                    <tr>
                        <td><?= e($societe['raison_sociale']) ?></td>
                        <td><?= e($societe['forme_juridique']) ?></td>
                        <td><?= e($societe['ville']) ?></td>
                        <td><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" title="Ouvrir"><span class="mdi mdi-eye"></span></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Derniers contrats</h2>
                <p class="help-text">Suivi rapide des contrats les plus recents.</p>
            </div>
            <a class="btn btn-secondary" href="<?= e(app_url('contrats')) ?>">Voir tout</a>
        </div>
        <?php if (!$recentContrats): ?>
            <p class="table-empty">Aucun contrat disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Societe</th>
                    <th>Type</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentContrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['raison_sociale']) ?></td>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e($contrat['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Derniers associes</h2>
                <p class="help-text">Acces rapide aux associes recemment ajoutes.</p>
            </div>
            <a class="btn btn-secondary" href="<?= e(app_url('associes')) ?>">Voir tout</a>
        </div>
        <?php if (!$recentAssocies): ?>
            <p class="table-empty">Aucun associe disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Societe</th>
                    <th>Qualite</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentAssocies as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['raison_sociale']) ?></td>
                        <td><?= e($associe['qualite_associe'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Derniers collaborateurs</h2>
                <p class="help-text">Experts, comptables, coursiers, etc.</p>
            </div>
            <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>">Voir tout</a>
        </div>
        <?php if (!$recentCollaborateurs): ?>
            <p class="table-empty">Aucun collaborateur disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentCollaborateurs as $c): ?>
                    <tr>
                        <td><?= e($c['nom_complet']) ?></td>
                        <td><?= e($c['collaborateur_type'] ?? '-') ?></td>
                        <td><?= e($c['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>
