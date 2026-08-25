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
                <input name="associe_nom_complet" value="<?= e((string) $associe['associe_nom_complet']) ?>" type="hidden">
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
<style>
.fiche-a4 { display:flex; justify-content:center; padding:1rem 0; }
.fiche-a4 .a4-page { background:var(--panel); color:var(--text); border:1px solid var(--line); }
.fiche-a4-header { text-align:center; margin-bottom:20px; border-bottom:2px solid var(--primary); padding-bottom:10px; }
.fiche-a4-header h1 { font-size:18pt; margin:0; color:var(--primary); }
.fiche-a4-header p { margin:4px 0 0; font-size:12pt; font-weight:700; color:var(--text); text-transform:uppercase; }
.fiche-a4-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.fiche-a4-card { background:var(--panel-strong); border:1px solid var(--line); border-radius:6px; padding:16px; }
.fiche-a4-card h3 { margin:0 0 12px; font-size:13pt; color:var(--info); }
.fiche-a4-fields { display:grid; grid-template-columns:1fr 1fr; gap:8px 16px; font-size:10pt; }
.fiche-a4-fields .full { grid-column:1/-1; }
.fiche-a4-fields span.label { color:var(--text-secondary); font-size:8pt; display:block; }
.fiche-a4-badge-primary { display:inline-block; padding:2px 8px; border-radius:3px; font-size:8pt; background:rgba(0,144,231,0.15); color:var(--primary); font-weight:600; }
.fiche-a4-badge-success { display:inline-block; padding:2px 8px; border-radius:3px; font-size:8pt; background:rgba(0,210,91,0.15); color:var(--success); font-weight:600; }
.fiche-a4-badge-muted { display:inline-block; padding:2px 8px; border-radius:3px; font-size:8pt; background:rgba(136,146,160,0.15); color:var(--text-secondary); font-weight:600; }
.fiche-a4-table { width:100%; border-collapse:collapse; font-size:9pt; }
.fiche-a4-table th { text-align:left; padding:4px 6px; color:var(--primary); }
.fiche-a4-table thead tr { border-bottom:2px solid var(--primary); }
.fiche-a4-table td { padding:4px 6px; border-bottom:1px solid var(--line); }
.fiche-a4-table td.right { text-align:right; }
.fiche-a4-table td.center { text-align:center; }
.fiche-a4-table tr.current { background:var(--panel-strong); font-weight:600; }
.fiche-a4-chart-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:70px; height:70px; border-radius:50%; background:var(--panel); display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:8pt; color:var(--text-secondary); line-height:1.2; }
.fiche-a4-chart-center strong { font-size:14pt; font-weight:700; color:var(--text); }
.fiche-a4 a { color:var(--primary); text-decoration:none; }
.fiche-a4 a:hover { text-decoration:underline; }
.fiche-a4-mt { margin-top:20px; }
</style>

<div class="fiche-a4">
<div class="a4-page">
    <div class="fiche-a4-header">
        <h1>FICHE ASSOCIE</h1>
        <p><?= e($associe['associe_nom_complet']) ?></p>
    </div>
    <div class="fiche-a4-grid">
        <div class="fiche-a4-card">
            <h3>Identite</h3>
            <div class="fiche-a4-fields">
                <div><span class="label">Civilite</span><strong><?= e($associe['associe_civilite'] ?: '-') ?></strong></div>
                <div><span class="label">Nom</span><strong><?= e($associe['associe_nom'] ?: '-') ?></strong></div>
                <div><span class="label">Prenom</span><strong><?= e($associe['associe_prenom'] ?: '-') ?></strong></div>
                <div><span class="label">CIN</span><strong><?= e($associe['associe_cin'] ?: '-') ?></strong></div>
                <div><span class="label">Date validite CIN</span><strong><?= e(format_date($associe['associe_date_validite_cin'] ?? null)) ?></strong></div>
                <div><span class="label">Date naissance</span><strong><?= e(format_date($associe['associe_date_naissance'] ?? null)) ?></strong></div>
                <div><span class="label">Lieu naissance</span><strong><?= e($associe['associe_lieu_naissance'] ?: '-') ?></strong></div>
                <div><span class="label">Nationalite</span><strong><?= e($associe['associe_nationalite'] ?: '-') ?></strong></div>
            </div>
        </div>
        <div class="fiche-a4-card">
            <h3>Contact</h3>
            <div class="fiche-a4-fields">
                <div><span class="label">Telephone</span><strong><a href="tel:<?= e($associe['associe_telephone']) ?>"><?= e($associe['associe_telephone'] ?: '-') ?></a></strong></div>
                <div><span class="label">Email</span><strong><a href="mailto:<?= e($associe['associe_email']) ?>"><?= e($associe['associe_email'] ?: '-') ?></a></strong></div>
                <div class="full"><span class="label">Adresse</span><strong><?= e($associe['associe_adresse'] ?: '-') ?></strong></div>
            </div>
        </div>
    </div>
    <div class="fiche-a4-grid fiche-a4-mt">
        <div class="fiche-a4-card">
            <h3>Statut</h3>
            <div class="fiche-a4-fields">
                <div><span class="label">Qualite</span><strong><span class="fiche-a4-badge-primary"><?= e($associe['associe_qualite'] ?: '-') ?></span></strong></div>
                <div><span class="label">Gerant</span><strong><?php if ((int) $associe['associe_est_gerant'] === 1): ?><span class="fiche-a4-badge-success">Gerant</span><?php else: ?><span class="fiche-a4-badge-muted">Associe</span><?php endif; ?></strong></div>
                <div><span class="label">Parts</span><strong><?= $associe['associe_parts'] !== null ? e((string) $associe['associe_parts']) : '-' ?></strong></div>
                <div><span class="label">Capital detenu</span><strong><?= e(format_money($associe['associe_capital_detenu'] !== null ? (float) $associe['associe_capital_detenu'] : null)) ?></strong></div>
                <div><span class="label">% Capital social</span><strong><?= $associe['associe_part_percent'] !== null ? e(number_format((float) $associe['associe_part_percent'], 2, ',', ' ') . ' %') : '-' ?></strong></div>
            </div>
        </div>
        <?php if ($societe): ?>
        <div class="fiche-a4-card">
            <h3>Societe liee</h3>
            <div class="fiche-a4-fields">
                <div><span class="label">Raison sociale</span><strong><a href="<?= e(app_url('societe', ['id' => (int) $associe['societe_id']])) ?>"><?= e($societe['societe_raison_sociale']) ?></a></strong></div>
                <div><span class="label">Forme juridique</span><strong><?= e($societe['societe_forme_juridique'] ?: '-') ?></strong></div>
                <div><span class="label">Capital</span><strong><?= format_money($societeCapital) ?></strong></div>
                <div><span class="label">Parts sociales</span><strong><?= e((string) $societeTotalParts) ?></strong></div>
                <div><span class="label">ICE</span><strong><?= e($societe['societe_ice'] ?: '-') ?></strong></div>
                <div><span class="label">Ville</span><strong><?= e($societe['societe_ville'] ?: '-') ?></strong></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php if (count($otherAssocies) > 1): ?>
    <div class="fiche-a4-card fiche-a4-mt">
        <h3>Repartition des parts</h3>
        <table class="fiche-a4-table">
            <thead>
                <tr>
                    <th>Associe</th>
                    <th>Qualite</th>
                    <th style="text-align:right">Parts</th>
                    <th style="text-align:right">Capital</th>
                    <th style="text-align:right">%</th>
                    <th style="text-align:center">Gerant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($otherAssocies as $a): ?>
                <tr<?= (int) $a['id'] === $associeId ? ' class="current"' : '' ?>>
                    <td><a href="<?= e(app_url('associe', ['id' => (int) $a['id']])) ?>"><?= e($a['associe_nom_complet']) ?></a></td>
                    <td><span class="fiche-a4-badge-primary"><?= e($a['associe_qualite'] ?: '-') ?></span></td>
                    <td class="right"><?= $a['associe_parts'] !== null ? e((string) $a['associe_parts']) : '-' ?></td>
                    <td class="right"><?= $a['associe_capital_detenu'] !== null ? format_money((float) $a['associe_capital_detenu']) : '-' ?></td>
                    <td class="right"><?= $a['associe_part_percent'] !== null ? e(number_format((float) $a['associe_part_percent'], 2, ',', ' ') . ' %') : '-' ?></td>
                    <td class="center"><?php if ((int) $a['associe_est_gerant'] === 1): ?><span class="fiche-a4-badge-success">Gerant</span><?php else: ?><span class="fiche-a4-badge-muted">Associe</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($societeTotalParts > 0):
            $barColors = ['#4a6cf7', '#00d25b', '#8f5fe8', '#ff6b35', '#e8a838'];
            $segments = [];
            $cumPct = 0;
            $ci = 0;
            foreach ($otherAssocies as $a):
                $pct = (int) ($a['associe_parts'] ?? 0);
                if ($pct <= 0) continue;
                $segPct = round(($pct / $societeTotalParts) * 100, 2);
                $color = $barColors[$ci % count($barColors)];
                $ci++;
                $segments[] = [
                    'name' => $a['associe_nom_complet'],
                    'parts' => $pct,
                    'pct' => $segPct,
                    'color' => $color,
                    'start' => $cumPct,
                    'end' => $cumPct + $segPct,
                ];
                $cumPct += $segPct;
            endforeach;
            $gradientStops = [];
            foreach ($segments as $s):
                $gradientStops[] = "{$s['color']} {$s['start']}% {$s['end']}%";
            endforeach;
            $gradientStr = 'conic-gradient(' . implode(', ', $gradientStops) . ')';
        ?>
        <div style="margin-top:12px">
            <div style="display:flex;flex-direction:column;align-items:center">
                <div style="position:relative;width:160px;height:160px">
                    <div style="width:100%;height:100%;border-radius:50%;background:<?= $gradientStr ?>"></div>
                    <?php
                    $labelR = 52;
                    $cx = 80; $cy = 80;
                    foreach ($segments as $s):
                        $midDeg = (($s['start'] + $s['end']) / 2) * 3.6;
                        $midRad = deg2rad($midDeg);
                        $lx = $cx + $labelR * sin($midRad);
                        $ly = $cy - $labelR * cos($midRad);
                    ?>
                    <div style="position:absolute;top:<?= $ly ?>px;left:<?= $lx ?>px;transform:translate(-50%,-50%);font-size:9pt;font-weight:700;color:var(--text);text-shadow:0 1px 3px rgba(0,0,0,0.6);pointer-events:none;line-height:1.1;text-align:center">
                        <?= number_format($s['pct'], 1, ',', ' ') ?>%
                    </div>
                    <?php endforeach; ?>
                    <div class="fiche-a4-chart-center">
                        <strong>100%</strong>
                        <span>Total</span>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:10px;font-size:8pt">
                    <?php foreach ($segments as $s): ?>
                    <span style="display:inline-flex;align-items:center;gap:4px">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $s['color'] ?>"></span>
                        <span><strong><?= e($s['name']) ?></strong> — <?= $s['parts'] ?> parts (<?= number_format($s['pct'], 1, ',', ' ') ?>%)</span>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>
