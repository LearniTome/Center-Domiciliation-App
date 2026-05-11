<?php

declare(strict_types=1);

$societeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$societe = $societeId > 0 ? fetch_record($pdo ?? null, 'societes', $societeId) : null;

if (!$societe) {
    http_response_code(404);
    ?>
    <section class="card stack">
        <h2>Societe introuvable</h2>
        <p>La fiche demandee n'existe pas ou n'est plus disponible.</p>
        <a class="btn" href="<?= e(app_url('societes')) ?>">Retour aux societes</a>
    </section>
    <?php
    return;
}

$associes = ($pdo ?? null) instanceof PDO
    ? (function (PDO $pdo, int $societeId): array {
        $stmt = $pdo->prepare('SELECT nom_complet, cin, nationalite, parts, is_gerant FROM associes WHERE societe_id = :societe_id ORDER BY id DESC');
        $stmt->execute(['societe_id' => $societeId]);
        return $stmt->fetchAll();
    })($pdo, $societeId)
    : [];

$contrats = ($pdo ?? null) instanceof PDO
    ? (function (PDO $pdo, int $societeId): array {
        $stmt = $pdo->prepare('SELECT type_contrat, date_debut, date_fin, statut, loyer_mensuel_ttc FROM contrats WHERE societe_id = :societe_id ORDER BY id DESC');
        $stmt->execute(['societe_id' => $societeId]);
        return $stmt->fetchAll();
    })($pdo, $societeId)
    : [];

$collaborateurs = ($pdo ?? null) instanceof PDO
    ? (function (PDO $pdo, int $societeId): array {
        $stmt = $pdo->prepare('SELECT nom_complet, fonction, email, telephone, statut FROM collaborateurs WHERE societe_id = :societe_id ORDER BY id DESC');
        $stmt->execute(['societe_id' => $societeId]);
        return $stmt->fetchAll();
    })($pdo, $societeId)
    : [];
?>
<section class="grid two">
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2><?= e($societe['raison_sociale']) ?></h2>
                <p class="help-text">Fiche detaillee de la societe.</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('societes', ['edit' => $societeId])) ?>">Modifier</a>
                <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>">Retour</a>
            </div>
        </div>
        <div class="info-grid">
            <div><strong>Dossier domiciliation</strong><span><?= e($societe['dossier_domiciliation'] ?: '-') ?></span></div>
            <div><strong>Forme juridique</strong><span><?= e($societe['forme_juridique'] ?: '-') ?></span></div>
            <div><strong>Denomination interne</strong><span><?= e($societe['den_ste'] ?: '-') ?></span></div>
            <div><strong>ICE</strong><span><?= e($societe['ice'] ?: '-') ?></span></div>
            <div><strong>Date ICE</strong><span><?= e($societe['date_ice'] ?: '-') ?></span></div>
            <div><strong>RC</strong><span><?= e($societe['rc'] ?: '-') ?></span></div>
            <div><strong>IF</strong><span><?= e($societe['if_number'] ?: '-') ?></span></div>
            <div><strong>Ville</strong><span><?= e($societe['ville'] ?: '-') ?></span></div>
            <div><strong>Tribunal</strong><span><?= e($societe['tribunal'] ?: '-') ?></span></div>
            <div><strong>Telephone</strong><span><?= e($societe['telephone'] ?: '-') ?></span></div>
            <div><strong>Email</strong><span><?= e($societe['email'] ?: '-') ?></span></div>
            <div><strong>Capital</strong><span><?= e($societe['capital'] !== null ? (string) $societe['capital'] : '-') ?></span></div>
            <div><strong>Part social</strong><span><?= e($societe['part_social'] !== null ? (string) $societe['part_social'] : '-') ?></span></div>
            <div><strong>Valeur nominale</strong><span><?= e($societe['valeur_nominale'] !== null ? (string) $societe['valeur_nominale'] : '-') ?></span></div>
            <div><strong>Date exp. cert. neg.</strong><span><?= e($societe['date_exp_cert_neg'] ?: '-') ?></span></div>
            <div><strong>Type generation</strong><span><?= e($societe['type_generation'] ?: '-') ?></span></div>
            <div><strong>Procedure creation</strong><span><?= e($societe['procedure_creation'] ?: '-') ?></span></div>
            <div><strong>Mode depot creation</strong><span><?= e($societe['mode_depot_creation'] ?: '-') ?></span></div>
            <div class="full"><strong>Adresse</strong><span><?= e($societe['adresse'] ?: '-') ?></span></div>
            <div class="full"><strong>Adresse reference</strong><span><?= e($societe['ste_adress'] ?: '-') ?></span></div>
        </div>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Resume</h2>
                <p class="help-text">Vue consolidee autour de la societe.</p>
            </div>
        </div>
        <section class="stats compact">
            <article class="stat">
                <span>Associes</span>
                <strong><?= e((string) count($associes)) ?></strong>
            </article>
            <article class="stat">
                <span>Contrats</span>
                <strong><?= e((string) count($contrats)) ?></strong>
            </article>
            <article class="stat">
                <span>Collaborateurs</span>
                <strong><?= e((string) count($collaborateurs)) ?></strong>
            </article>
        </section>
    </article>
</section>

<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Associes lies</h2>
            </div>
        </div>
        <?php if (!$associes): ?>
            <p class="table-empty">Aucun associe lie a cette societe.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>CIN</th>
                    <th>Nationalite</th>
                    <th>Gerant</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['cin']) ?></td>
                        <td><?= e($associe['nationalite']) ?></td>
                        <td><?= (int) $associe['is_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Contrats lies</h2>
            </div>
        </div>
        <?php if (!$contrats): ?>
            <p class="table-empty">Aucun contrat lie a cette societe.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Periode</th>
                    <th>Statut</th>
                    <th>Loyer TTC</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e(($contrat['date_debut'] ?: '-') . ' -> ' . ($contrat['date_fin'] ?: '-')) ?></td>
                        <td><?= e($contrat['statut']) ?></td>
                        <td><?= e($contrat['loyer_mensuel_ttc'] !== null ? (string) $contrat['loyer_mensuel_ttc'] : '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Collaborateurs lies</h2>
        </div>
    </div>
    <?php if (!$collaborateurs): ?>
        <p class="table-empty">Aucun collaborateur lie a cette societe.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Nom</th>
                <th>Fonction</th>
                <th>Email</th>
                <th>Telephone</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($collaborateurs as $collaborateur): ?>
                <tr>
                    <td><?= e($collaborateur['nom_complet']) ?></td>
                    <td><?= e($collaborateur['fonction']) ?></td>
                    <td><?= e($collaborateur['email']) ?></td>
                    <td><?= e($collaborateur['telephone']) ?></td>
                    <td><?= e($collaborateur['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
