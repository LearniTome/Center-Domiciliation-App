<?php

declare(strict_types=1);

$associeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$associe = $associeId > 0 ? fetch_record($pdo ?? null, 'associes', $associeId) : null;

$societe = null;
$societeCapital = 0;
$societeTotalParts = 0;
$otherAssocies = [];
$totalAllocatedParts = 0;
if ($associe && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('SELECT societe_raison_sociale, societe_forme_juridique, societe_ice, societe_ville, societe_capital, societe_part_social FROM societes WHERE id = :id');
    $stmt->execute(['id' => $associe['societe_id']]);
    $societe = $stmt->fetch();
    if ($societe) {
        $societeCapital = (float) ($societe['societe_capital'] ?? 0);
        $societeTotalParts = (int) ($societe['societe_part_social'] ?? 0);
    }

    $stmt = $pdo->prepare('SELECT id, associe_nom_complet, associe_qualite, associe_parts, associe_capital_detenu, associe_part_percent, associe_est_gerant FROM associes WHERE societe_id = :sid ORDER BY associe_nom_complet');
    $stmt->execute(['sid' => $associe['societe_id']]);
    $otherAssocies = $stmt->fetchAll();

    $totalAllocatedParts = 0;
    foreach ($otherAssocies as $a) {
        $totalAllocatedParts += (int) ($a['associe_parts'] ?? 0);
    }
}

if (!$associe) {
    http_response_code(404);
    ?>
    <section class="card stack">
        <h2>Associe introuvable</h2>
        <p>La fiche demandee n'existe pas ou n'est plus disponible.</p>
        <a class="btn" href="<?= e(app_url('associes')) ?>">Retour aux associes</a>
    </section>
    <?php
    return;
}

$editing = isset($_GET['edit']) && $_GET['edit'] === '1';

$qualitesOptions = [];
if ($editing && ($pdo ?? null) instanceof PDO) {
    $qualitesOptions = fetch_reference_options($pdo, 'ref_qualites_associe', 'qualite_associe');
}

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $stmt = $pdo->prepare('
        UPDATE associes SET
            associe_civilite = :associe_civilite, associe_nom = :associe_nom, associe_prenom = :associe_prenom, associe_nom_complet = :associe_nom_complet,
            associe_cin = :associe_cin, associe_date_validite_cin = :associe_date_validite_cin,
            associe_date_naissance = :associe_date_naissance, associe_lieu_naissance = :associe_lieu_naissance, associe_nationalite = :associe_nationalite,
            associe_adresse = :associe_adresse, associe_telephone = :associe_telephone, associe_email = :associe_email,
            associe_qualite = :associe_qualite, associe_parts = :associe_parts,
            associe_capital_detenu = :associe_capital_detenu, associe_part_percent = :associe_part_percent, associe_est_gerant = :associe_est_gerant
        WHERE id = :id
    ');
    $stmt->execute([
        'associe_civilite' => field_value($_POST, 'associe_civilite'),
        'associe_nom' => field_value($_POST, 'associe_nom'),
        'associe_prenom' => field_value($_POST, 'associe_prenom'),
        'associe_nom_complet' => field_value($_POST, 'associe_nom_complet'),
        'associe_cin' => field_value($_POST, 'associe_cin'),
        'associe_date_validite_cin' => date_value($_POST, 'associe_date_validite_cin'),
        'associe_date_naissance' => date_value($_POST, 'associe_date_naissance'),
        'associe_lieu_naissance' => field_value($_POST, 'associe_lieu_naissance'),
        'associe_nationalite' => field_value($_POST, 'associe_nationalite'),
        'associe_adresse' => field_value($_POST, 'associe_adresse'),
        'associe_telephone' => field_value($_POST, 'associe_telephone'),
        'associe_email' => field_value($_POST, 'associe_email'),
        'associe_qualite' => field_value($_POST, 'associe_qualite'),
        'associe_parts' => int_value($_POST, 'associe_parts'),
        'associe_capital_detenu' => money_value($_POST, 'associe_capital_detenu'),
        'associe_part_percent' => money_value($_POST, 'associe_part_percent'),
        'associe_est_gerant' => (field_value($_POST, 'associe_est_gerant') === '1') ? 1 : 0,
        'id' => $associeId,
    ]);
    log_activity($pdo, 'update', 'associe', $associeId, field_value($_POST, 'associe_nom_complet'));
    set_flash('success', 'Associe mis a jour.');
    redirect_to('associe', ['id' => $associeId]);
}

// Delete handler
if (is_post() && isset($_POST['_action']) && $_POST['_action'] === 'delete' && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM associes WHERE id = :id');
    $stmt->execute(['id' => $associeId]);
    log_activity($pdo, 'delete', 'associe', $associeId, $associe['associe_nom_complet']);
    set_flash('success', 'Associe supprime.');
    redirect_to('associes');
}
?>
<div class="section-title-row">
    <h2><?= e($associe['associe_nom_complet']) ?></h2>
    <div class="table-actions">
        <?php if ($editing): ?>
            <a class="btn btn-cancel" href="<?= e(app_url('associe', ['id' => $associeId])) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
        <?php else: ?>
            <a class="btn btn-info" href="<?= e(app_url('associe', ['id' => $associeId, 'edit' => '1'])) ?>"><span class="material-symbols-outlined">edit</span> Modifier</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cet associe ? Cette action est irreversible.');">
                <?= csrf_input() ?>
                <input type="hidden" name="_action" value="delete">
                <button class="btn btn-danger" type="submit"><span class="material-symbols-outlined">delete</span> Supprimer</button>
            </form>
        <?php endif; ?>
        <a class="btn btn-back" href="<?= e(app_url('associes')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
    </div>
</div>

<section class="stats small stats-bottom-margin">
    <article class="stat">
        <span>Societe</span>
        <strong><?= e($societe['societe_raison_sociale'] ?? '-') ?></strong>
    </article>
    <article class="stat">
        <span>Gerant</span>
        <strong><?= (int) $associe['associe_est_gerant'] === 1 ? 'Oui' : 'Non' ?></strong>
    </article>
    <article class="stat">
        <span>CIN</span>
        <strong><?= e($associe['associe_cin'] ?: '-') ?></strong>
    </article>
    <article class="stat">
        <span>Nationalite</span>
        <strong><?= e($associe['associe_nationalite'] ?: '-') ?></strong>
    </article>
</section>

<?php if ($societe): ?>
<section class="stats small stats-bottom-margin">
    <article class="stat">
        <span>Capital societe</span>
        <strong><?= format_money($societeCapital) ?></strong>
    </article>
    <article class="stat">
        <span>Parts sociales</span>
        <strong><?= e((string) $societeTotalParts) ?></strong>
    </article>
    <article class="stat">
        <span>Total alloue</span>
        <strong><?= e((string) $totalAllocatedParts) ?></strong>
    </article>
    <article class="stat" style="<?= $totalAllocatedParts === $societeTotalParts ? '' : 'color: var(--danger)' ?>">
        <span>Equilibre</span>
        <strong><?= $totalAllocatedParts === $societeTotalParts ? 'Equilibre' : 'Desequilibre' ?></strong>
    </article>
</section>
<?php endif; ?>

<?php if ($editing): ?>
    <section class="card stack">
        <form method="post" class="stack" id="associe-form">
            <?= csrf_input() ?>
            <input type="hidden" id="societe_capital" value="<?= e((string) $societeCapital) ?>">
            <input type="hidden" id="societe_total_parts" value="<?= e((string) $societeTotalParts) ?>">
            <div class="form-grid">
                <h3 class="section-title">Identite</h3>
                <label class="field">
                    <span>Civilite</span>
                    <select name="associe_civilite" data-auto-nom>
                        <option value="">Selectionner</option>
                        <option value="Mr" <?= (string) $associe['associe_civilite'] === 'Mr' ? 'selected' : '' ?>>Mr</option>
                        <option value="Mme" <?= (string) $associe['associe_civilite'] === 'Mme' ? 'selected' : '' ?>>Mme</option>
                        <option value="Mlle" <?= (string) $associe['associe_civilite'] === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                    </select>
                </label>
                <label class="field">
                    <span>Nom <span class="field-required">*</span></span>
                    <input name="associe_nom" value="<?= e((string) ($associe['associe_nom'] ?: $associe['associe_nom_complet'])) ?>" data-auto-nom required>
                </label>
                <label class="field">
                    <span>Prenom <span class="field-required">*</span></span>
                    <input name="associe_prenom" value="<?= e((string) ($associe['associe_prenom'] ?: '')) ?>" data-auto-nom required>
                </label>
                <label class="field">
                    <span>Nom complet</span>
                    <input name="associe_nom_complet" value="<?= e((string) $associe['associe_nom_complet']) ?>" readonly>
                </label>
                <label class="field">
                    <span>CIN</span>
                    <input name="associe_cin" value="<?= e((string) $associe['associe_cin']) ?>">
                </label>
                <label class="field">
                    <span>Date validite CIN</span>
                    <input type="date" name="associe_date_validite_cin" value="<?= e((string) $associe['associe_date_validite_cin']) ?>">
                </label>
                <label class="field">
                    <span>Date naissance</span>
                    <input type="date" name="associe_date_naissance" value="<?= e((string) $associe['associe_date_naissance']) ?>">
                </label>
                <label class="field">
                    <span>Lieu naissance</span>
                    <input name="associe_lieu_naissance" value="<?= e((string) $associe['associe_lieu_naissance']) ?>" list="lieux-naissance-datalist">
                </label>
                <label class="field">
                    <span>Nationalite</span>
                    <input name="associe_nationalite" value="<?= e((string) $associe['associe_nationalite']) ?>" list="nationalites-datalist">
                </label>
                <h3 class="section-title">Contact</h3>
                <label class="field">
                    <span>Telephone</span>
                    <input type="tel" name="associe_telephone" value="<?= e((string) $associe['associe_telephone']) ?>" placeholder="+212XXXXXXXXX">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="associe_email" value="<?= e((string) $associe['associe_email']) ?>">
                </label>
                <label class="field full">
                    <span>Adresse</span>
                    <textarea name="associe_adresse" rows="2"><?= e((string) $associe['associe_adresse']) ?></textarea>
                </label>
                <h3 class="section-title">Statut</h3>
                <label class="field">
                    <span>Qualite associe</span>
                    <select name="associe_qualite">
                        <option value="">Selectionner</option>
                        <?php foreach ($qualitesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $associe['associe_qualite'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>% Capital social</span>
                    <input type="number" step="0.01" name="associe_part_percent" value="<?= e((string) $associe['associe_part_percent']) ?>" readonly>
                </label>
                <label class="field">
                    <span>Parts</span>
                    <input type="number" name="associe_parts" value="<?= e((string) $associe['associe_parts']) ?>" data-capital-calc>
                    <small class="field-hint">Total: <?= e((string) $societeTotalParts) ?></small>
                </label>
                <label class="field">
                    <span>Capital detenu (DH)</span>
                    <input type="number" step="0.01" name="associe_capital_detenu" value="<?= e((string) $associe['associe_capital_detenu']) ?>" readonly>
                </label>
                <label class="field">
                    <span>Gerant</span>
                    <select name="associe_est_gerant">
                        <option value="0" <?= (string) $associe['associe_est_gerant'] === '0' ? 'selected' : '' ?>>Non</option>
                        <option value="1" <?= (string) $associe['associe_est_gerant'] === '1' ? 'selected' : '' ?>>Oui</option>
                    </select>
                </label>
            </div>
            <div class="section-title-row" style="justify-content: flex-start; gap: 8px;">
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">check</span> Enregistrer</button>
                <a class="btn btn-cancel" href="<?= e(app_url('associe', ['id' => $associeId])) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
            </div>
        </form>
    </section>

    <?php if (count($otherAssocies) > 1): ?>
    <article class="card">
        <div class="section-title-row">
            <h3>Autres associes de la societe</h3>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('societe', ['id' => (int) $associe['societe_id']])) ?>"><span class="material-symbols-outlined">visibility</span> Voir la societe</a>
            </div>
        </div>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="nom">Nom complet</th>
                        <th data-col="qualite">Qualite</th>
                        <th data-col="parts">Parts</th>
                        <th data-col="capital">Capital detenu</th>
                        <th data-col="pct">%</th>
                        <th data-col="gerant">Gerant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($otherAssocies as $a): ?>
                    <tr<?= (int) $a['id'] === $associeId ? ' style="background:var(--primary-bg);font-weight:600"' : '' ?>>
                        <td><?= e($a['associe_nom_complet']) ?></td>
                        <td><?= e($a['associe_qualite'] ?: '-') ?></td>
                        <td><?= $a['associe_parts'] !== null ? e((string) $a['associe_parts']) : '-' ?></td>
                        <td><?= $a['associe_capital_detenu'] !== null ? format_money((float) $a['associe_capital_detenu']) : '-' ?></td>
                        <td><?= $a['associe_part_percent'] !== null ? e(number_format((float) $a['associe_part_percent'], 2, ',', ' ') . ' %') : '-' ?></td>
                        <td><?= (int) $a['associe_est_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td>
                            <?php if ((int) $a['id'] !== $associeId): ?>
                            <a class="btn-icon" href="<?= e(app_url('associe', ['id' => (int) $a['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <?php endif; ?>

    <style>
    .field-required { color: var(--danger); }
    .field-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
    </style>
    <script>
    (function(){
        var totalParts = <?= json_encode($societeTotalParts) ?>;
        var societeCapital = <?= json_encode($societeCapital) ?>;

        function autoFillNomComplet() {
            var civ = document.querySelector('[name="associe_civilite"]').value;
            var nom = document.querySelector('[name="associe_nom"]').value;
            var prenom = document.querySelector('[name="associe_prenom"]').value;
            var parts = [];
            if (civ) parts.push(civ);
            if (nom) parts.push(nom);
            if (prenom) parts.push(prenom);
            document.querySelector('[name="associe_nom_complet"]').value = parts.join(' ');
            recalcCapital();
        }

        function recalcCapital() {
            var parts = parseFloat(document.querySelector('[name="associe_parts"]').value) || 0;
            if (totalParts > 0 && parts > 0) {
                var pct = (parts / totalParts) * 100;
                var capital = (parts / totalParts) * societeCapital;
                document.querySelector('[name="associe_part_percent"]').value = pct.toFixed(2);
                document.querySelector('[name="associe_capital_detenu"]').value = capital.toFixed(2);
            } else {
                document.querySelector('[name="associe_part_percent"]').value = '';
                document.querySelector('[name="associe_capital_detenu"]').value = '';
            }
        }

        document.querySelectorAll('[data-auto-nom]').forEach(function(el) {
            el.addEventListener('input', autoFillNomComplet);
            el.addEventListener('change', autoFillNomComplet);
        });

        document.querySelector('[data-capital-calc]').addEventListener('input', recalcCapital);

        document.getElementById('associe-form').addEventListener('submit', function(e) {
            var nom = document.querySelector('[name="associe_nom"]').value.trim();
            var prenom = document.querySelector('[name="associe_prenom"]').value.trim();
            if (!nom || !prenom) {
                e.preventDefault();
                alert('Veuillez saisir le nom et le prenom de l\'associe.');
            }
        });

        autoFillNomComplet();
    })();
    </script>
<?php else: ?>
    <article class="card stack">
        <div class="form-grid">
            <h3 class="section-title">Identite</h3>
            <div class="info-grid">
                <div><span>Civilite</span><strong><?= e($associe['associe_civilite'] ?: '-') ?></strong></div>
                <div><span>Nom</span><strong><?= e($associe['associe_nom'] ?: '-') ?></strong></div>
                <div><span>Prenom</span><strong><?= e($associe['associe_prenom'] ?: '-') ?></strong></div>
                <div><span>Nom complet</span><strong><?= e($associe['associe_nom_complet']) ?></strong></div>
                <div><span>CIN</span><strong><?= e($associe['associe_cin'] ?: '-') ?></strong></div>
                <div><span>Date validite CIN</span><strong><?= format_date($associe['associe_date_validite_cin'] ?? null) ?></strong></div>
                <div><span>Date naissance</span><strong><?= format_date($associe['associe_date_naissance'] ?? null) ?></strong></div>
                <div><span>Lieu naissance</span><strong><?= e($associe['associe_lieu_naissance'] ?: '-') ?></strong></div>
                <div><span>Nationalite</span><strong><?= e($associe['associe_nationalite'] ?: '-') ?></strong></div>
            </div>

            <h3 class="section-title">Contact</h3>
            <div class="info-grid">
                <div><span>Telephone</span><strong><?= e($associe['associe_telephone'] ?: '-') ?></strong></div>
                <div><span>Email</span><strong><?= e($associe['associe_email'] ?: '-') ?></strong></div>
                <div class="full"><span>Adresse</span><strong><?= e($associe['associe_adresse'] ?: '-') ?></strong></div>
            </div>

            <h3 class="section-title">Statut</h3>
            <div class="info-grid">
                <div><span>Qualite</span><strong><?= e($associe['associe_qualite'] ?: '-') ?></strong></div>
                <div><span>Parts</span><strong><?= $associe['associe_parts'] !== null ? e((string) $associe['associe_parts']) : '-' ?></strong></div>
                <div><span>Capital detenu</span><strong><?= format_money($associe['associe_capital_detenu'] !== null ? (float) $associe['associe_capital_detenu'] : null) ?></strong></div>
                <div><span>% Capital social</span><strong><?= $associe['associe_part_percent'] !== null ? e(number_format((float) $associe['associe_part_percent'], 2, ',', ' ') . ' %') : '-' ?></strong></div>
                <div><span>Gerant</span><strong><?= (int) $associe['associe_est_gerant'] === 1 ? 'Oui' : 'Non' ?></strong></div>
            </div>
        </div>
    </article>

    <?php if ($societe): ?>
    <article class="card">
        <div class="section-header">
            <a href="<?= e(app_url('societe', ['id' => (int) $associe['societe_id']])) ?>" class="section-link"><h3>Societe liee</h3></a>
        </div>
        <div class="info-grid">
            <div><span>Raison sociale</span><strong><?= e($societe['societe_raison_sociale']) ?></strong></div>
            <div><span>Forme juridique</span><strong><?= e($societe['societe_forme_juridique'] ?: '-') ?></strong></div>
            <div><span>Capital</span><strong><?= format_money($societeCapital) ?></strong></div>
            <div><span>Parts sociales</span><strong><?= e((string) $societeTotalParts) ?></strong></div>
            <div><span>ICE</span><strong><?= e($societe['societe_ice'] ?: '-') ?></strong></div>
            <div><span>Ville</span><strong><?= e($societe['societe_ville'] ?: '-') ?></strong></div>
        </div>
    </article>
    <?php endif; ?>

    <?php if (count($otherAssocies) > 1): ?>
    <article class="card">
        <div class="section-title-row">
            <h3>Repartition des parts</h3>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('societe', ['id' => (int) $associe['societe_id']])) ?>"><span class="material-symbols-outlined">visibility</span> Voir la societe</a>
            </div>
        </div>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="nom">Associe</th>
                        <th data-col="qualite">Qualite</th>
                        <th data-col="parts">Parts</th>
                        <th data-col="capital">Capital</th>
                        <th data-col="pct">%</th>
                        <th data-col="gerant">Gerant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($otherAssocies as $a): ?>
                    <tr<?= (int) $a['id'] === $associeId ? ' style="background:var(--primary-bg);font-weight:600"' : '' ?>>
                        <td><a href="<?= e(app_url('associe', ['id' => (int) $a['id']])) ?>"><?= e($a['associe_nom_complet']) ?></a></td>
                        <td><?= e($a['associe_qualite'] ?: '-') ?></td>
                        <td><?= $a['associe_parts'] !== null ? e((string) $a['associe_parts']) : '-' ?></td>
                        <td><?= $a['associe_capital_detenu'] !== null ? format_money((float) $a['associe_capital_detenu']) : '-' ?></td>
                        <td><?= $a['associe_part_percent'] !== null ? e(number_format((float) $a['associe_part_percent'], 2, ',', ' ') . ' %') : '-' ?></td>
                        <td><?= (int) $a['associe_est_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <?php endif; ?>
<?php endif; ?>
