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

$editing = isset($_GET['edit']) && $_GET['edit'] === '1';

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $stmt = $pdo->prepare('
        UPDATE societes SET
            dossier_domiciliation = :dossier_domiciliation,
            raison_sociale = :raison_sociale,
            forme_juridique = :forme_juridique,
            ice = :ice,
            date_ice = :date_ice,
            rc = :rc,
            if_number = :if_number,
            capital = :capital,
            part_social = :part_social,
            valeur_nominale = :valeur_nominale,
            date_exp_cert_neg = :date_exp_cert_neg,
            ste_adress = :ste_adress,
            ville = :ville,
            tribunal = :tribunal,
            email = :email,
            telephone = :telephone,
            type_generation = :type_generation,
            procedure_creation = :procedure_creation,
            mode_depot_creation = :mode_depot_creation
        WHERE id = :id
    ');
    $stmt->execute([
        'dossier_domiciliation' => field_value($_POST, 'dossier_domiciliation'),
        'raison_sociale' => field_value($_POST, 'raison_sociale'),
        'forme_juridique' => field_value($_POST, 'forme_juridique'),
        'ice' => field_value($_POST, 'ice'),
        'date_ice' => field_value($_POST, 'date_ice'),
        'rc' => field_value($_POST, 'rc'),
        'if_number' => field_value($_POST, 'if_number'),
        'capital' => money_value($_POST, 'capital'),
        'part_social' => int_value($_POST, 'part_social'),
        'valeur_nominale' => money_value($_POST, 'valeur_nominale'),
        'date_exp_cert_neg' => field_value($_POST, 'date_exp_cert_neg'),
        'ste_adress' => field_value($_POST, 'ste_adress'),
        'ville' => field_value($_POST, 'ville'),
        'tribunal' => field_value($_POST, 'tribunal'),
        'email' => field_value($_POST, 'email'),
        'telephone' => field_value($_POST, 'telephone'),
        'type_generation' => field_value($_POST, 'type_generation'),
        'procedure_creation' => field_value($_POST, 'procedure_creation'),
        'mode_depot_creation' => field_value($_POST, 'mode_depot_creation'),
        'id' => $societeId,
    ]);
    set_flash('success', 'Societe mise a jour.');
    redirect_to('societe', ['id' => $societeId]);
}

if ($editing) {
    $villesOptions = fetch_reference_options($pdo ?? null, 'ref_villes', 'ville');
    $tribunauxOptions = fetch_reference_options($pdo ?? null, 'ref_tribunaux', 'tribunal');
    $adressesOptions = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');
    $formesJuridiquesOptions = fetch_reference_options($pdo ?? null, 'ref_formes_juridiques', 'forme_juridique');
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
        $stmt = $pdo->prepare('SELECT type_contrat, date_debut, date_fin, statut, montant_total_ht_contrat AS montant_total_loyer FROM contrats WHERE societe_id = :societe_id ORDER BY id DESC');
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
                <p class="help-text"><?= $editing ? 'Modifier la societe' : 'Fiche detaillee de la societe.' ?></p>
            </div>
            <div class="table-actions">
                <?php if ($editing): ?>
                    <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => $societeId])) ?>">Annuler</a>
                <?php else: ?>
                    <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => $societeId, 'edit' => '1'])) ?>">Modifier</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>">Retour</a>
            </div>
        </div>

        <?php if ($editing): ?>
            <form method="post" class="stack">
                <?= csrf_input() ?>
                <div class="form-grid">
                    <h3 class="section-title">Procedure</h3>
                    <label class="field">
                        <span>Type generation</span>
                        <select name="type_generation">
                            <option value="">Selectionner</option>
                            <option value="creation" <?= (string) $societe['type_generation'] === 'creation' ? 'selected' : '' ?>>Creation</option>
                            <option value="domiciliation" <?= (string) $societe['type_generation'] === 'domiciliation' ? 'selected' : '' ?>>Domiciliation</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Procedure creation</span>
                        <select name="procedure_creation">
                            <option value="">Selectionner</option>
                            <option value="normal" <?= (string) $societe['procedure_creation'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="acceleree" <?= (string) $societe['procedure_creation'] === 'acceleree' ? 'selected' : '' ?>>Acceleree</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Mode depot creation</span>
                        <select name="mode_depot_creation">
                            <option value="">Selectionner</option>
                            <option value="depot_physique" <?= (string) $societe['mode_depot_creation'] === 'depot_physique' ? 'selected' : '' ?>>Depot Physique</option>
                            <option value="depot_en_ligne" <?= (string) $societe['mode_depot_creation'] === 'depot_en_ligne' ? 'selected' : '' ?>>Depot En Ligne</option>
                        </select>
                    </label>
                    <h3 class="section-title">Identifiants</h3>
                    <label class="field">
                        <span>Dossier domiciliation</span>
                        <input name="dossier_domiciliation" value="<?= e((string) $societe['dossier_domiciliation']) ?>">
                    </label>
                    <label class="field">
                        <span>Raison sociale</span>
                        <input name="raison_sociale" required value="<?= e((string) $societe['raison_sociale']) ?>">
                    </label>
                    <label class="field">
                        <span>Forme juridique</span>
                        <select name="forme_juridique" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($formesJuridiquesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societe['forme_juridique'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>ICE</span>
                        <input name="ice" value="<?= e((string) $societe['ice']) ?>">
                    </label>
                    <label class="field">
                        <span>Date de cert. negatif</span>
                        <input type="date" name="date_ice" value="<?= e((string) $societe['date_ice']) ?>">
                    </label>
                    <label class="field">
                        <span>Date exp. cert. negatif</span>
                        <input type="date" name="date_exp_cert_neg" value="<?= e((string) $societe['date_exp_cert_neg']) ?>">
                    </label>
                    <label class="field">
                        <span>RC</span>
                        <input name="rc" value="<?= e((string) $societe['rc']) ?>">
                    </label>
                    <label class="field">
                        <span>IF</span>
                        <input name="if_number" value="<?= e((string) $societe['if_number']) ?>">
                    </label>
                    <h3 class="section-title">Capital</h3>
                    <label class="field">
                        <span>Capital</span>
                        <input type="number" step="0.01" name="capital" value="<?= e((string) ($societe['capital'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Part social</span>
                        <input type="number" name="part_social" value="<?= e((string) ($societe['part_social'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Valeur nominale</span>
                        <input type="number" step="0.01" name="valeur_nominale" value="<?= e((string) ($societe['valeur_nominale'] ?? '')) ?>">
                    </label>
                    <h3 class="section-title">Adresse</h3>
                    <label class="field full">
                        <span>Adresse de reference</span>
                        <select name="ste_adress" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($adressesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societe['ste_adress'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Ville</span>
                        <select name="ville" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($villesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societe['ville'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Tribunal</span>
                        <select name="tribunal">
                            <option value="">Selectionner</option>
                            <?php foreach ($tribunauxOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societe['tribunal'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= e((string) $societe['email']) ?>">
                    </label>
                    <label class="field">
                        <span>Telephone</span>
                        <input name="telephone" value="<?= e((string) $societe['telephone']) ?>">
                    </label>
                </div>
                <div class="table-actions">
                    <button class="btn btn-next" type="submit">Enregistrer</button>
                </div>
            </form>
        <?php else: ?>
            <div class="info-grid">
                <div><strong>Dossier domiciliation</strong><span><?= e($societe['dossier_domiciliation'] ?: '-') ?></span></div>
                <div><strong>Forme juridique</strong><span><?= e($societe['forme_juridique'] ?: '-') ?></span></div>
                <div><strong>ICE</strong><span><?= e($societe['ice'] ?: '-') ?></span></div>
                <div><strong>Date de cert. negatif</strong><span><?= e($societe['date_ice'] ?: '-') ?></span></div>
                <div><strong>RC</strong><span><?= e($societe['rc'] ?: '-') ?></span></div>
                <div><strong>IF</strong><span><?= e($societe['if_number'] ?: '-') ?></span></div>
                <div><strong>Ville</strong><span><?= e($societe['ville'] ?: '-') ?></span></div>
                <div><strong>Tribunal</strong><span><?= e($societe['tribunal'] ?: '-') ?></span></div>
                <div><strong>Telephone</strong><span><?= e($societe['telephone'] ?: '-') ?></span></div>
                <div><strong>Email</strong><span><?= e($societe['email'] ?: '-') ?></span></div>
                <div><strong>Capital</strong><span><?= format_money($societe['capital'] !== null ? (float) $societe['capital'] : null) ?></span></div>
                <div><strong>Part social</strong><span><?= format_number($societe['part_social'] !== null ? (float) $societe['part_social'] : null) ?></span></div>
                <div><strong>Valeur nominale</strong><span><?= format_money($societe['valeur_nominale'] !== null ? (float) $societe['valeur_nominale'] : null) ?></span></div>
                <div><strong>Date exp. cert. neg.</strong><span><?= e($societe['date_exp_cert_neg'] ?: '-') ?></span></div>
                <div><strong>Type generation</strong><span><?= e($societe['type_generation'] ?: '-') ?></span></div>
                <div><strong>Procedure creation</strong><span><?= e($societe['procedure_creation'] ?: '-') ?></span></div>
                <div><strong>Mode depot creation</strong><span><?= e($societe['mode_depot_creation'] ?: '-') ?></span></div>
                <div class="full"><strong>Adresse reference</strong><span><?= e($societe['ste_adress'] ?: '-') ?></span></div>
            </div>
        <?php endif; ?>
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
                    <th>Montant Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e(($contrat['date_debut'] ?: '-') . ' -> ' . ($contrat['date_fin'] ?: '-')) ?></td>
                        <td><?= e($contrat['statut']) ?></td>
                        <td><?= format_money($contrat['montant_total_loyer'] !== null ? (float) $contrat['montant_total_loyer'] : null) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>


