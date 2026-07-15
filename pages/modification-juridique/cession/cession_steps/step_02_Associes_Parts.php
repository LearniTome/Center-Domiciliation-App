<?php
declare(strict_types=1);

// ====== COMPUTED VARS (from step_03) ======
$socForPrix = $selectedSociete ?: ($wizard['societe'] ?? []);
$valeurNominaleCession = (float) ($socForPrix['societe_valeur_nominale'] ?? 0);
$totalPartsSociete = (int) ($socForPrix['societe_part_social'] ?? 0);
$totalCapitalSociete = (float) ($socForPrix['societe_capital'] ?? 0);

$cedantList = !empty($selectedAssocies) ? $selectedAssocies : ($wizard['associes'] ?? []);

// Build gerant ID set
$gerantIdSet = [];
foreach ($cedantList as $a) {
    if ((string) ($a['associe_est_gerant'] ?? '0') === '1') {
        $gerantIdSet[(int) ($a['id'] ?? 0)] = true;
    }
}
$cedIsGerant = fn($id) => isset($gerantIdSet[$id]);

// Re-edit: extract existing parts data to pre-fill form
$prefillCedantParts = [];
$prefillCessionnaires = [];
if (!empty($wizard['parts'])) {
    foreach ($wizard['parts'] as $p) {
        $cid = (int) ($p['cedant_associe_id'] ?? 0);
        $partsCedees = (int) ($p['parts_cedees'] ?? 0);
        if ($cid > 0) {
            $prefillCedantParts[$cid] = ($prefillCedantParts[$cid] ?? 0) + $partsCedees;
        }
        $cNom = trim($p['cessionnaire_nom_complet'] ?? '');
        if ($cNom !== '') {
            $key = $cNom . '|' . ($p['cessionnaire_cin'] ?? '');
            if (!isset($prefillCessionnaires[$key])) {
                $prefillCessionnaires[$key] = [
                    'civilite' => $p['cessionnaire_civilite'] ?? 'M.',
                    'prenom' => '',
                    'nom' => $cNom,
                    'cin' => $p['cessionnaire_cin'] ?? '',
                    'date_naissance' => $p['cessionnaire_date_naissance'] ?? '',
                    'lieu_naissance' => $p['cessionnaire_lieu_naissance'] ?? '',
                    'nationalite' => $p['cessionnaire_nationalite'] ?? '',
                    'telephone' => $p['cessionnaire_telephone'] ?? '',
                    'email' => $p['cessionnaire_email'] ?? '',
                    'qualite' => $p['cessionnaire_qualite'] ?? '',
                    'adresse' => $p['cessionnaire_adresse'] ?? '',
                    'parts' => (int) ($p['cessionnaire_parts'] ?? $partsCedees),
                    'capital' => $p['cessionnaire_capital_detenu'] ?? '',
                    'est_gerant' => !empty($p['cessionnaire_est_gerant']) ? 1 : 0,
                ];
                $sp = explode(' ', $cNom, 2);
                $prefillCessionnaires[$key]['prenom'] = $sp[0] ?? '';
                $prefillCessionnaires[$key]['nom'] = $sp[1] ?? '';
            }
        }
    }
}
$prefillCessionnaires = array_values($prefillCessionnaires);

// ====== POST HANDLER ======
if (is_post() && $step === 2) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    // 1. Save associates (from step 2)
    $wizard['associes'] = [];
    $noms = $_POST['associe_nom'] ?? [];
    $prenoms = $_POST['associe_prenom'] ?? [];
    foreach ($noms as $i => $nom) {
        $nom = trim((string) $nom);
        $prenom = trim((string) ($prenoms[$i] ?? ''));
        if ($nom === '' && $prenom === '') continue;
        $wizard['associes'][] = [
            'associe_civilite' => trim((string) ($_POST['associe_civilite'][$i] ?? 'M.')),
            'associe_nom_complet' => trim($prenom . ' ' . $nom),
            'associe_cin' => trim((string) ($_POST['associe_cin'][$i] ?? '')),
            'associe_date_naissance' => trim((string) ($_POST['associe_date_naissance'][$i] ?? '')),
            'associe_lieu_naissance' => trim((string) ($_POST['associe_lieu_naissance'][$i] ?? '')),
            'associe_nationalite' => trim((string) ($_POST['associe_nationalite'][$i] ?? '')),
            'associe_adresse' => trim((string) ($_POST['associe_adresse'][$i] ?? '')),
            'associe_telephone' => trim((string) ($_POST['associe_telephone'][$i] ?? '')),
            'associe_email' => trim((string) ($_POST['associe_email'][$i] ?? '')),
            'associe_qualite' => trim((string) ($_POST['associe_qualite'][$i] ?? 'Gérant')),
            'associe_parts' => (string) ($_POST['associe_parts'][$i] ?? ''),
            'associe_capital_detenu' => (string) ($_POST['associe_capital_detenu'][$i] ?? ''),
            'associe_est_gerant' => ($_POST['associe_est_gerant'][$i] ?? '0') === '1' ? '1' : '0',
            'associe_cede' => !empty($_POST['associe_cede'][$i]) ? '1' : '0',
            'associe_parts_a_ceder' => (string) ($_POST['associe_parts_a_ceder'][$i] ?? ''),
        ];
    }

    // 2. Validate associate totals (only for mode nouvelle)
    if ($wizard['mode'] === 'nouvelle' && empty($wizard['associes'])) {
        set_flash('error', 'Ajoutez au moins un associe.');
        redirect_to('cession', ['step' => 2]);
    }

    if ($wizard['mode'] === 'nouvelle') {
        $totalParts = 0;
        $totalCapital = 0.0;
        foreach ($wizard['associes'] as $a) {
            $totalParts += (int) ($a['associe_parts'] ?? 0);
            $totalCapital += (float) str_replace(',', '.', (string) ($a['associe_capital_detenu'] ?? '0'));
        }
        $socParts = (int) ($wizard['societe']['societe_part_social'] ?? 0);
        $socCapital = (float) str_replace(',', '.', (string) ($wizard['societe']['societe_capital'] ?? '0'));

        $hasError = false;
        if ($socParts > 0 && $totalParts !== $socParts) {
            $hasError = true;
            $_SESSION['_parts_error'] = true;
        }
        if ($socCapital > 0 && abs($totalCapital - $socCapital) > 0.01) {
            $hasError = true;
            $_SESSION['_capital_error'] = true;
        }
        if ($hasError) {
            $p = $socParts > 0 ? " parts ($totalParts/$socParts)" : '';
            $c = $socCapital > 0 ? ' capital ('.number_format($totalCapital, 2, ',', ' ').'/' . number_format($socCapital, 2, ',', ' ') . ' DH)' : '';
            set_flash('error', "Verifiez les associés : le total des$p$c ne correspond pas a la societe.");
            redirect_to('cession', ['step' => 2]);
        }
    }

    // 3. Save cession date
    $wizard['cession_date'] = field_value($_POST, 'cession_date');

    // 4. Save cedant parts + cessionnaires (from step 3)
    $cedantParts = $_POST['cedant_parts_a_ceder'] ?? [];
    $gerantActions = $_POST['gerant_action'] ?? [];
    $nommerGerant = $_POST['nommer_gerant'] ?? [];

    $cessionnairesJson = $_POST['cessionnaires_json'] ?? '[]';
    $cessionnaires = json_decode($cessionnairesJson, true);
    if (!is_array($cessionnaires)) $cessionnaires = [];

    $sellingCedants = [];
    foreach ($cedantList as $a) {
        $aid = (int) ($a['id'] ?? 0);
        $partsToCede = (int) ($cedantParts[$aid] ?? 0);
        if ($partsToCede > 0) {
            $sellingCedants[] = [
                'associe_id' => $aid,
                'nom_complet' => $a['associe_nom_complet'] ?? '',
                'cin' => $a['associe_cin'] ?? '',
                'parts_a_ceder' => $partsToCede,
                'parts_restant' => $partsToCede,
            ];
        }
    }
    // For mode "nouvelle", read cédants from associate cards with toggle "Céder des parts"
    if ($wizard['mode'] === 'nouvelle') {
        $associeCede = $_POST['associe_cede'] ?? [];
        $associePartsCeder = $_POST['associe_parts_a_ceder'] ?? [];
        foreach ($wizard['associes'] as $i => $assoc) {
            $isCede = !empty($associeCede[$i]);
            $partsToCede = (int) ($associePartsCeder[$i] ?? 0);
            if ($isCede && $partsToCede > 0) {
                $sellingCedants[] = [
                    'associe_id' => 'new_' . $i,
                    'nom_complet' => $assoc['associe_nom_complet'] ?? '',
                    'cin' => $assoc['associe_cin'] ?? '',
                    'parts_a_ceder' => $partsToCede,
                    'parts_restant' => $partsToCede,
                ];
            }
        }
    }

    $wizard['parts'] = [];
    $wizard['cession_metadata'] = [];
    $totalCedantParts = array_sum(array_column($sellingCedants, 'parts_a_ceder'));
    $totalCessionnaireParts = array_sum(array_column($cessionnaires, 'parts'));

    $errors = [];
    if (empty($sellingCedants) && empty($cessionnaires)) {
        $errors[] = 'Ajoutez au moins un cédant ou un cessionnaire.';
    }
    if ($totalCedantParts !== $totalCessionnaireParts) {
        $errors[] = 'Le total des parts cédées (' . $totalCedantParts . ') doit être égal au total des parts acquises (' . $totalCessionnaireParts . ').';
    }
    if ($totalCedantParts > $totalPartsSociete) {
        $errors[] = 'Le total des parts cédées (' . $totalCedantParts . ') dépasse le capital social (' . $totalPartsSociete . ').';
    }

    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        redirect_to('cession', ['step' => 2]);
    }

    // Distribute parts
    $cedPool = [];
    foreach ($sellingCedants as $sc) {
        $cedPool[] = $sc;
    }

    $cedIdx = 0;
    foreach ($cessionnaires as $cess) {
        $need = (int) ($cess['parts'] ?? 0);
        if ($need <= 0) continue;
        while ($need > 0 && $cedIdx < count($cedPool)) {
            $available = $cedPool[$cedIdx]['parts_restant'];
            if ($available <= 0) { $cedIdx++; continue; }
            $take = min($need, $available);
            $cedPool[$cedIdx]['parts_restant'] -= $take;

            $cessNom = trim(($cess['prenom'] ?? '') . ' ' . ($cess['nom'] ?? ''));
            $cessCivilite = $cess['civilite'] ?? 'M.';
            $prixUnitaire = $valeurNominaleCession > 0 ? $valeurNominaleCession : 0;
            $prixTotal = $prixUnitaire * $take;

            $cessionnaireCapital = '';
            if ($totalPartsSociete > 0 && $totalCapitalSociete > 0) {
                $cessionnaireCapital = number_format(($take / $totalPartsSociete) * $totalCapitalSociete, 2, '.', '');
            }

            $wizard['parts'][] = [
                'cedant_type' => 'existant',
                'cedant_associe_id' => $cedPool[$cedIdx]['associe_id'],
                'cedant_nom_complet' => $cedPool[$cedIdx]['nom_complet'],
                'cedant_cin' => $cedPool[$cedIdx]['cin'],
                'cessionnaire_type' => 'nouveau',
                'cessionnaire_associe_id' => 0,
                'cessionnaire_nom_complet' => $cessNom,
                'cessionnaire_cin' => $cess['cin'] ?? '',
                'cessionnaire_civilite' => $cessCivilite,
                'cessionnaire_date_naissance' => $cess['date_naissance'] ?? '',
                'cessionnaire_lieu_naissance' => $cess['lieu_naissance'] ?? '',
                'cessionnaire_nationalite' => $cess['nationalite'] ?? '',
                'cessionnaire_adresse' => $cess['adresse'] ?? '',
                'cessionnaire_telephone' => $cess['telephone'] ?? '',
                'cessionnaire_email' => $cess['email'] ?? '',
                'cessionnaire_qualite' => $cess['qualite'] ?? '',
                'cessionnaire_parts' => $take,
                'cessionnaire_capital_detenu' => $cessionnaireCapital,
                'cessionnaire_est_gerant' => !empty($cess['est_gerant']) ? 1 : 0,
                'pourcentage' => $totalPartsSociete > 0 ? round(($take / $totalPartsSociete) * 100, 2) : null,
                'parts_cedees' => $take,
                'prix_unitaire' => $prixUnitaire,
                'prix_total' => $prixTotal,
                'nommer_gerant' => !empty($cess['est_gerant']) ? 1 : 0,
            ];

            $need -= $take;
            if ($cedPool[$cedIdx]['parts_restant'] <= 0) $cedIdx++;
        }
    }

    // Build metadata
    $forme = $socForPrix['societe_forme_juridique'] ?? '';
    $isSarlAu = $forme === 'SARL AU';
    $allAssocies = $cedantList;

    $associeGerantMap = [];
    foreach ($allAssocies as $a) {
        $aid = (int) ($a['id'] ?? 0);
        $associeGerantMap[$aid] = $cedIsGerant($aid);
    }
    // For "nouvelle" mode, build gerant map from wizard['associes']
    if ($wizard['mode'] === 'nouvelle') {
        foreach ($wizard['associes'] as $i => $a) {
            $associeGerantMap['new_' . $i] = ($a['associe_est_gerant'] ?? '0') === '1';
        }
    }

    $cedantsGerantMap = [];
    foreach ($wizard['parts'] as $part) {
        $cid = $part['cedant_associe_id'] ?? 0;
        if (is_int($cid) && $cid > 0 && isset($associeGerantMap[$cid]) && $associeGerantMap[$cid]) {
            $action = ($gerantActions[$cid] ?? '') === 'resign' ? 'resign' : 'stay';
            $cedantsGerantMap[$cid] = ['is_gerant' => true, 'action' => $action];
        }
    }

    $newGerantCessionnaireIndices = [];
    foreach ($wizard['parts'] as $pi => $part) {
        if (!empty($part['nommer_gerant'])) {
            $newGerantCessionnaireIndices[] = $pi;
        }
    }

    $cessionTypes = [];
    $afterAssocieIds = [];
    foreach ($allAssocies as $a) {
        $afterAssocieIds[(int) ($a['id'] ?? 0)] = true;
    }
    // For "nouvelle" mode, include associate card cédants
    if ($wizard['mode'] === 'nouvelle') {
        foreach ($wizard['associes'] as $i => $a) {
            $afterAssocieIds['new_' . $i] = true;
        }
    }
    foreach ($wizard['parts'] as $pi => $part) {
        $cid = $part['cedant_associe_id'] ?? 0;
        if (is_int($cid) && $cid > 0) {
            $isTotal = true;
            foreach ($cedPool as $cp) {
                if ($cp['associe_id'] === $cid && $cp['parts_restant'] > 0) {
                    $isTotal = false;
                    break;
                }
            }
            $cessionTypes[$pi] = $isTotal ? 'total' : 'partial';
            if ($isTotal) {
                unset($afterAssocieIds[$cid]);
            }
        }
    }
    foreach ($wizard['parts'] as $part) {
        if ($part['cessionnaire_type'] === 'nouveau') {
            $afterAssocieIds['new_' . $part['cessionnaire_nom_complet']] = true;
        }
    }
    $totalAfter = count($afterAssocieIds);
    $needsTransform = $isSarlAu && $totalAfter > 1;

    $wizard['cession_metadata'] = [
        'forme_juridique' => $forme,
        'is_sarl_au' => $isSarlAu,
        'cedants_gerant_map' => $cedantsGerantMap,
        'new_gerant_cessionnaire_indices' => $newGerantCessionnaireIndices,
        'cession_types' => $cessionTypes,
        'total_associes_after' => $totalAfter,
        'needs_transformation' => $needsTransform,
    ];

    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 1]);
    }
    redirect_to('cession', ['step' => 3]);
}

// ====== HTML VIEW ======
if ($step === 2):
?>
<style>
    .cession-wizard input,
    .cession-wizard select,
    .cession-wizard textarea { padding: 6px 10px; font-size: 0.82rem; }
    .cession-wizard .field { gap: 3px; }
    .cession-wizard .field label { font-weight: 600; }
    .cession-wizard .field span { font-size: 0.7rem; }
    .cession-wizard .form-grid { gap: 8px; }
    /* Override global .modal-overlay conflict (opacity/pointer-events hidden) */
    #cession-party-modal.modal-overlay { display: none; opacity: 1; pointer-events: all; }
</style>
<form method="post" class="stack" id="merged-cession-form">
    <?= csrf_input() ?>
    <input type="hidden" name="nav_action" value="next">

    <div style="margin-top:12px">

    <?php
    $hasPartsError = !empty($_SESSION['_parts_error']);
    $hasCapitalError = !empty($_SESSION['_capital_error']);
    if ($hasPartsError || $hasCapitalError):
        $msgs = [];
        if ($hasPartsError) $msgs[] = 'Le total des parts doit etre ' . ((int) ($wizard['societe']['societe_part_social'] ?? 0));
        if ($hasCapitalError) $msgs[] = 'Le total du capital doit etre ' . number_format((float) ($wizard['societe']['societe_capital'] ?? 0), 2, ',', ' ') . ' DH';
    ?>
    <article class="card" style="border-color:var(--danger);margin-bottom:12px">
        <p style="color:var(--danger);margin:0"><?= e(implode(' — ', $msgs)) ?>.</p>
    </article>
    <?php unset($_SESSION['_parts_error'], $_SESSION['_capital_error']); endif; ?>

    <!-- ====== SECTION 1: ASSOCIES ====== -->
    <article class="card" style="margin-bottom:16px">
        <div class="section-header">
            <strong><span class="material-symbols-outlined" style="font-size:1.1rem;vertical-align:text-bottom">group</span> Associés</strong>
            <?php if ($wizard['mode'] === 'nouvelle'): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-left:auto">
                <button class="btn btn-info" type="button" data-fill-cession="2"><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
                <button class="btn btn-info" type="button" id="add-associe-step2"><span class="material-symbols-outlined">playlist_add</span> Ajouter un associé</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($selectedAssocies) && $wizard['mode'] === 'existante'): ?>
        <article class="card" style="margin-top:8px">
            <div class="section-header">
                <div><h3>Associés existants</h3></div>
            </div>
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="associe">Associé</th>
                        <th data-col="cin">CIN</th>
                        <th data-col="parts">Parts</th>
                        <th data-col="capital">Capital</th>
                        <th data-col="qualite">Qualité</th>
                        <th>Gérant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedAssocies as $a): ?>
                    <tr>
                        <td><?= e($a['associe_nom_complet']) ?></td>
                        <td><?= e($a['associe_cin'] ?? '-') ?></td>
                        <td><?= (int) ($a['associe_parts'] ?? 0) ?></td>
                        <td><?= e(number_format((float) ($a['associe_capital_detenu'] ?? 0), 2, ',', ' ') . ' DH') ?></td>
                        <td><?= e($a['associe_qualite'] ?? '-') ?></td>
                        <td><?= ((string) ($a['associe_est_gerant'] ?? '0') === '1') ? '<span class="material-symbols-outlined" style="color:var(--success)">verified</span>' : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </article>
        <?php endif; ?>

        <div class="stack" id="cession-associes-container" style="margin-top:8px">
            <?php
            $savedAssocies = $wizard['associes'] ?? [];
            if (!empty($savedAssocies) && $wizard['mode'] === 'nouvelle'): ?>
                <?php foreach ($savedAssocies as $ai => $assoc): ?>
                <div class="associe-card" data-associe-item>
                    <div class="associe-card-header">
                        <strong data-associe-title>Associé <?= $ai + 1 ?></strong>
                        <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                    </div>
                    <div class="form-grid">
                        <h3 class="section-title">Identité</h3>
                        <label class="field">
                            <span>Civilite</span>
                            <select name="associe_civilite[<?= $ai ?>]">
                                <option value="M." <?= ($assoc['associe_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option>
                                <option value="Mme" <?= ($assoc['associe_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                                <option value="Mlle" <?= ($assoc['associe_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                            </select>
                        </label>
                        <?php $_np = explode(' ', $assoc['associe_nom_complet'] ?? '', 2); ?>
                        <label class="field">
                            <span>Prénom *</span>
                            <input type="text" name="associe_prenom[<?= $ai ?>]" required value="<?= e($_np[0] ?? '') ?>">
                        </label>
                        <label class="field">
                            <span>Nom *</span>
                            <input type="text" name="associe_nom[<?= $ai ?>]" required value="<?= e($_np[1] ?? $_np[0] ?? '') ?>">
                        </label>
                        <label class="field">
                            <span>CIN</span>
                            <input type="text" name="associe_cin[<?= $ai ?>]" value="<?= e($assoc['associe_cin'] ?? '') ?>">
                        </label>
                        <label class="field">
                            <span>Date de naissance</span>
                            <input type="date" name="associe_date_naissance[<?= $ai ?>]" value="<?= e($assoc['associe_date_naissance'] ?? '') ?>">
                        </label>
                        <label class="field">
                            <span>Lieu de naissance</span>
                            <select name="associe_lieu_naissance[<?= $ai ?>]">
                                <option value="">-- Sélectionnez --</option>
                                <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                    <option value="<?= e($ln) ?>" <?= ($assoc['associe_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Nationalité</span>
                            <select name="associe_nationalite[<?= $ai ?>]">
                                <option value="">-- Sélectionnez --</option>
                                <?php foreach ($nationalitesOptions as $nat): ?>
                                    <option value="<?= e($nat) ?>" <?= ($assoc['associe_nationalite'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <h3 class="section-title">Contact</h3>
                        <label class="field">
                            <span>Téléphone</span>
                            <input type="text" name="associe_telephone[<?= $ai ?>]" value="<?= e($assoc['associe_telephone'] ?? '') ?>">
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input type="email" name="associe_email[<?= $ai ?>]" value="<?= e($assoc['associe_email'] ?? '') ?>">
                        </label>
                        <label class="field full">
                            <span>Adresse</span>
                            <textarea name="associe_adresse[<?= $ai ?>]" rows="2" style="min-height:2.8em;padding:4px 8px"><?= e($assoc['associe_adresse'] ?? '') ?></textarea>
                        </label>
                        <h3 class="section-title">Participation</h3>
                        <label class="field">
                            <span>Qualité</span>
                            <select name="associe_qualite[<?= $ai ?>]">
                                <option value="">-- Sélectionnez --</option>
                                <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                    <option value="<?= e($qa) ?>" <?= ($assoc['associe_qualite'] ?? '') === $qa ? 'selected' : '' ?>><?= e($qa) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field <?= ($hasPartsError || $hasCapitalError) ? 'field-error' : '' ?>">
                            <span>Nombre de parts</span>
                            <input type="number" name="associe_parts[<?= $ai ?>]" value="<?= e($assoc['associe_parts'] ?? '') ?>" placeholder="100" class="<?= ($hasPartsError || $hasCapitalError) ? 'input-error' : '' ?>">
                        </label>
                        <label class="field <?= $hasCapitalError ? 'field-error' : '' ?>">
                            <span>Capital détenu (DH)</span>
                            <input type="number" step="0.01" name="associe_capital_detenu[<?= $ai ?>]" value="<?= e($assoc['associe_capital_detenu'] ?? '') ?>" placeholder="50000" class="<?= $hasCapitalError ? 'input-error' : '' ?>">
                        </label>
                        <label class="field">
                            <span>Gérant</span>
                            <select name="associe_est_gerant[<?= $ai ?>]">
                                <option value="0" <?= ($assoc['associe_est_gerant'] ?? '0') === '0' ? 'selected' : '' ?>>Non</option>
                                <option value="1" <?= ($assoc['associe_est_gerant'] ?? '0') === '1' ? 'selected' : '' ?>>Oui</option>
                            </select>
                        </label>
                        <label class="field" style="grid-column:1/-1">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
                                <input type="checkbox" name="associe_cede[<?= $ai ?>]" value="1" class="associe-cede" data-associe-cede <?= !empty($assoc['associe_cede']) ? 'checked' : '' ?>>
                                <span>Céder des parts</span>
                            </label>
                        </label>
                        <label class="field associe-parts-ceder-field" style="<?= empty($assoc['associe_cede']) ? 'display:none;' : '' ?>grid-column:1/-1">
                            <span>Parts à céder</span>
                            <input type="number" name="associe_parts_a_ceder[<?= $ai ?>]" class="associe-parts-ceder" placeholder="0" min="0" value="<?= e($assoc['associe_parts_a_ceder'] ?? '') ?>">
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php elseif ($wizard['mode'] === 'nouvelle'): ?>
            <div class="associe-card" data-associe-item>
                <div class="associe-card-header">
                    <strong data-associe-title>Associé 1</strong>
                    <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                </div>
                <div class="form-grid">
                    <h3 class="section-title">Identité</h3>
                    <label class="field">
                        <span>Civilite</span>
                        <select name="associe_civilite[0]">
                            <option value="M." selected>M.</option>
                            <option value="Mme">Mme</option>
                            <option value="Mlle">Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Prénom *</span>
                        <input type="text" name="associe_prenom[0]" required>
                    </label>
                    <label class="field">
                        <span>Nom *</span>
                        <input type="text" name="associe_nom[0]" required>
                    </label>
                    <label class="field">
                        <span>CIN</span>
                        <input type="text" name="associe_cin[0]">
                    </label>
                    <label class="field">
                        <span>Date de naissance</span>
                        <input type="date" name="associe_date_naissance[0]">
                    </label>
                    <label class="field">
                        <span>Lieu de naissance</span>
                        <select name="associe_lieu_naissance[0]">
                            <option value="">-- Sélectionnez --</option>
                            <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                <option value="<?= e($ln) ?>"><?= e($ln) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nationalité</span>
                        <select name="associe_nationalite[0]">
                            <option value="">-- Sélectionnez --</option>
                            <?php foreach ($nationalitesOptions as $nat): ?>
                                <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Téléphone</span>
                        <input type="text" name="associe_telephone[0]">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="associe_email[0]">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea name="associe_adresse[0]" rows="2" style="min-height:2.8em;padding:4px 8px"></textarea>
                    </label>
                    <h3 class="section-title">Participation</h3>
                    <label class="field">
                        <span>Qualité</span>
                        <select name="associe_qualite[0]">
                            <option value="">-- Sélectionnez --</option>
                            <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                <option value="<?= e($qa) ?>"><?= e($qa) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nombre de parts</span>
                        <input type="number" name="associe_parts[0]" placeholder="100">
                    </label>
                    <label class="field">
                        <span>Capital détenu (DH)</span>
                        <input type="number" step="0.01" name="associe_capital_detenu[0]" placeholder="50000">
                    </label>
                    <label class="field">
                        <span>Gérant</span>
                        <select name="associe_est_gerant[0]">
                            <option value="0" selected>Non</option>
                            <option value="1">Oui</option>
                        </select>
                    </label>
                    <label class="field" style="grid-column:1/-1">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
                            <input type="checkbox" name="associe_cede[0]" value="1" class="associe-cede" data-associe-cede>
                            <span>Céder des parts</span>
                        </label>
                    </label>
                    <label class="field associe-parts-ceder-field" style="display:none;grid-column:1/-1">
                        <span>Parts à céder</span>
                        <input type="number" name="associe_parts_a_ceder[0]" class="associe-parts-ceder" placeholder="0" min="0">
                    </label>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Metrics dashboard (from step 2) -->
        <?php if ($wizard['mode'] === 'nouvelle'): ?>
        <div class="dash-metrics" style="margin:12px 0 0">
            <div class="dash-metric">
                <div class="dm-icon dm-icon-soc"><span class="material-symbols-outlined">token</span></div>
                <div class="dm-body">
                    <span class="dm-label">Parts société</span>
                    <strong class="dm-value" style="font-size:1rem"><?= (int) ($wizard['societe']['societe_part_social'] ?? 0) ?></strong>
                </div>
            </div>
            <div class="dash-metric">
                <div class="dm-icon dm-icon-ctr"><span class="material-symbols-outlined">account_balance</span></div>
                <div class="dm-body">
                    <span class="dm-label">Capital société</span>
                    <strong class="dm-value" style="font-size:1rem"><?= e(number_format((float) ($wizard['societe']['societe_capital'] ?? 0), 2, ',', ' ')) ?> DH</strong>
                </div>
            </div>
            <div class="dash-metric">
                <div class="dm-icon dm-icon-doc"><span class="material-symbols-outlined">group</span></div>
                <div class="dm-body">
                    <span class="dm-label">Parts associés</span>
                    <strong class="dm-value" style="font-size:1rem"><span id="total-parts-display">0</span></strong>
                </div>
            </div>
            <div class="dash-metric">
                <div class="dm-icon dm-icon-doc"><span class="material-symbols-outlined">payments</span></div>
                <div class="dm-body">
                    <span class="dm-label">Capital associés</span>
                    <strong class="dm-value" style="font-size:1rem"><span id="total-capital-display">0,00</span> DH</strong>
                </div>
            </div>
        </div>
        <div id="parts-status" style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:0.85rem">&nbsp;</div>
        <?php endif; ?>
    </article>

    <!-- ====== SECTION 2: CÉDANTS (parts à céder) ====== -->
    <article class="card" style="margin-bottom:16px">
        <div class="section-header">
            <strong><span class="material-symbols-outlined" style="font-size:1.1rem;vertical-align:text-bottom">logout</span> Cédants (associés existants)</strong>
        </div>
        <div id="cedants-list">
            <?php if (empty($cedantList)): ?>
            <p class="table-empty" style="margin:8px 0"><?= $wizard['mode'] === 'nouvelle' ? 'Ajoutez des associés ci-dessus pour définir les parts à céder.' : 'Aucun associé trouvé pour cette société.' ?></p>
            <?php else: ?>
            <div style="display:grid;gap:10px">
                <?php foreach ($cedantList as $ced):
                $aid = (int) ($ced['id'] ?? 0);
                $isGerant = $cedIsGerant($aid);
                $cedParts = (int) ($ced['associe_parts'] ?? 0);
                $prefillParts = $prefillCedantParts[$aid] ?? 0;
                $prefillParts = min($prefillParts, $cedParts);
                $gerantAction = '';
                if ($isGerant && !empty($wizard['parts'])) {
                    foreach ($wizard['cession_metadata']['cedants_gerant_map'] ?? [] as $cid => $gm) {
                        if ($cid === $aid) { $gerantAction = $gm['action'] ?? ''; break; }
                    }
                }
                ?>
                <div class="cedant-row" data-cedant-id="<?= $aid ?>" data-cedant-parts="<?= $cedParts ?>">
                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1.5fr auto;gap:10px;align-items:center;padding:10px;background:var(--bg-card);border:1px solid var(--line);border-radius:6px">
                        <div>
                            <strong><?= e($ced['associe_nom_complet'] ?? '-') ?></strong>
                            <?php if ($isGerant): ?>
                            <span style="color:var(--warning);font-size:0.75rem;margin-left:6px"><span class="material-symbols-outlined" style="font-size:0.8rem;vertical-align:text-bottom">admin_panel_settings</span> Gérant</span>
                            <?php endif; ?>
                        </div>
                        <div><small style="color:var(--text-muted)">CIN</small><br><?= e($ced['associe_cin'] ?? '-') ?></div>
                        <div><small style="color:var(--text-muted)">Parts</small><br><?= $cedParts ?></div>
                        <div><small style="color:var(--text-muted)">Capital</small><br><?= e(number_format((float) ($ced['associe_capital_detenu'] ?? 0), 2, ',', '')) ?></div>
                        <div>
                            <small style="color:var(--text-muted)">Parts à céder</small>
                            <div style="display:flex;gap:4px;align-items:center">
                                <input type="number" name="cedant_parts_a_ceder[<?= $aid ?>]"
                                       class="cedant-parts-input"
                                       value="<?= $prefillParts ?>"
                                       min="0" max="<?= $cedParts ?>"
                                       style="width:90px" data-max="<?= $cedParts ?>">
                                <button type="button" class="btn btn-info btn-ceder-tout" style="padding:2px 6px;font-size:0.7rem;white-space:nowrap" title="Céder toutes les parts"><span class="material-symbols-outlined" style="font-size:0.8rem">sell</span> Tout</button>
                            </div>
                        </div>
                    </div>
                    <?php if ($isGerant): ?>
                    <div class="gerant-management" style="margin-top:6px;padding:8px 12px;background:rgba(255,107,53,0.06);border-radius:4px;border:1px solid rgba(255,107,53,0.2);display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                        <span class="material-symbols-outlined" style="color:var(--warning);font-size:1rem">admin_panel_settings</span>
                        <strong style="font-size:0.85rem">Gestion de la gérance</strong>
                        <div class="radio-group" style="margin-bottom:0">
                            <label><input type="radio" name="gerant_action[<?= $aid ?>]" value="stay" class="gerant-action-radio" <?= $gerantAction !== 'resign' ? 'checked' : '' ?>> Rester gérant</label>
                            <label><input type="radio" name="gerant_action[<?= $aid ?>]" value="resign" class="gerant-action-radio" <?= $gerantAction === 'resign' ? 'checked' : '' ?>> Démissionner</label>
                        </div>
                        <div class="nominate-field" style="display:<?= $gerantAction === 'resign' ? 'inline-flex' : 'none' ?>;align-items:center;gap:6px;margin-left:auto">
                            <small>Nouveau gérant :</small>
                            <select name="nommer_gerant[<?= $aid ?>]" style="width:auto;padding:2px 6px;font-size:0.8rem">
                                <option value="0">Choisir un cessionnaire</option>
                                <?php foreach ($prefillCessionnaires as $pc): ?>
                                <option value="1" <?= !empty($pc['est_gerant']) ? 'selected' : '' ?>><?= e($pc['prenom'] . ' ' . $pc['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="gerant_action[<?= $aid ?>]" value="stay">
                    <input type="hidden" name="nommer_gerant[<?= $aid ?>]" value="0">
                    <?php endif; ?>
                    <small class="cedant-warning" style="color:var(--danger);display:none;margin-top:2px">⚠️ Dépasse les parts détenues</small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </article>

    <!-- ====== BOUTON CESSIONNAIRES + MODALE ====== -->
    <div style="margin-bottom:16px">
        <button type="button" class="btn btn-next" id="btn-renseigner-parties">
            <span class="material-symbols-outlined">group_add</span> Renseigner les cessionnaires
        </button>
    </div>

    <!-- Modal overlay for cessionnaires (carte type "Nouvel associé") -->
    <div id="parties-modal" class="modal-overlay" style="display:none">
        <div style="background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:20px 24px;width:800px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 4px 20px rgba(0,0,0,0.3)">
            <div class="section-header" style="margin-bottom:16px">
                <strong style="font-size:1.05rem">Nouveaux cessionnaires</strong>
                <button type="button" class="btn btn-back" id="modal-close" style="margin-left:auto;padding:4px 10px;font-size:0.8rem"><span class="material-symbols-outlined">close</span> Fermer</button>
            </div>
            <div id="cessionnaires-modal-body" style="max-height:60vh;overflow-y:auto">
                <!-- Cards injected by JS -->
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
                <button type="button" class="btn btn-next" id="add-cessionnaire-row" style="padding:4px 10px;font-size:0.8rem"><span class="material-symbols-outlined" style="font-size:0.9rem">add</span> Ajouter un cessionnaire</button>
                <button type="button" class="btn btn-info" id="fill-cessionnaires-btn" style="padding:4px 10px;font-size:0.8rem"><span class="material-symbols-outlined" style="font-size:0.9rem">auto_fix</span> Remplir automatiquement</button>
                <button type="button" class="btn btn-info" id="validate-cessionnaires" style="padding:4px 14px;font-size:0.85rem"><span class="material-symbols-outlined" style="font-size:0.9rem">check</span> Valider les cessionnaires</button>
                <span id="modal-cessionnaire-count" style="margin-left:auto;font-size:0.85rem;color:var(--text-muted)">0 cessionnaire(s)</span>
            </div>
        </div>
    </div>

    <!-- ====== TABLEAU TOUTES LES PARTIES ====== -->
    <article class="card" style="margin-bottom:16px">
        <div class="section-header">
            <strong><span class="material-symbols-outlined" style="font-size:1.1rem;vertical-align:text-bottom">groups</span> Toutes les parties</strong>
        </div>
        <div class="table-scroll" style="margin-top:4px">
            <table id="parties-table" data-sortable>
                <thead>
                    <tr>
                        <th data-col="type">Type</th>
                        <th data-col="nom">Nom</th>
                        <th data-col="prenom">Prénom</th>
                        <th data-col="cin">CIN</th>
                        <th data-col="pct">%</th>
                        <th data-col="parts_av">Parts avant</th>
                        <th data-col="parts_ap">Parts après</th>
                        <th data-col="capital">Capital (DH)</th>
                        <th data-col="qualite">Qualité</th>
                        <th data-col="gerant">Gérant</th>
                        <th data-col="acquis_par">Acquis par</th>
                    </tr>
                </thead>
                <tbody id="parties-table-body">
                    <?php foreach ($cedantList as $ced):
                    $aid = (int) ($ced['id'] ?? 0);
                    $cedParts = (int) ($ced['associe_parts'] ?? 0);
                    $cedPct = $totalPartsSociete > 0 ? round(($cedParts / $totalPartsSociete) * 100, 2) : 0;
                    $prefillPartsCed = $prefillCedantParts[$aid] ?? 0;
                    $cedPartsApres = max(0, $cedParts - $prefillPartsCed);
                    ?>
                    <tr data-party-type="cedant" data-party-id="<?= $aid ?>" data-parts-av="<?= $cedParts ?>" data-parts-ap="<?= $cedPartsApres ?>">
                        <td><span class="badge" style="background:rgba(252,66,74,0.1);color:var(--danger)">Cédant</span></td>
                        <td><?= e($ced['associe_nom_complet'] ?? '-') ?></td>
                        <td><?= e(explode(' ', $ced['associe_nom_complet'] ?? '', 2)[0] ?? '') ?></td>
                        <td><?= e($ced['associe_cin'] ?? '-') ?></td>
                        <td><?= number_format($cedPct, 2, ',', '') ?>%</td>
                        <td><?= $cedParts ?></td>
                        <td class="parts-apres-cell"><?= $cedPartsApres ?></td>
                        <td><?= e(number_format((float) ($ced['associe_capital_detenu'] ?? 0), 2, ',', '')) ?></td>
                        <td><?= e($ced['associe_qualite'] ?? '-') ?></td>
                        <td><?= $cedIsGerant($aid) ? '✅' : '❌' ?></td>
                        <td class="acquis-par-cell">-</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <!-- ====== SUMMARY BAR ====== -->
    <div id="cession-summary" style="display:none;margin-bottom:16px;padding:10px 14px;background:var(--bg-card);border:1px solid var(--line);border-radius:6px">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;text-align:center">
            <div>
                <small style="color:var(--text-muted);display:block">Total parts cédées</small>
                <strong id="summary-total-parts" style="font-size:1.1rem">0</strong>
                <small style="color:var(--text-muted)"> / <span id="summary-max-parts"><?= $totalPartsSociete ?></span></small>
            </div>
            <div>
                <small style="color:var(--text-muted);display:block">Total parts acquises</small>
                <strong id="summary-total-acquired" style="font-size:1.1rem">0</strong>
            </div>
            <div>
                <small style="color:var(--text-muted);display:block">Parties</small>
                <strong id="summary-total-parties" style="font-size:1.1rem"><?= count($cedantList) ?></strong>
            </div>
            <div>
                <small id="summary-status-label" style="color:var(--text-muted);display:block">Statut</small>
                <strong id="summary-status" style="font-size:0.85rem;color:var(--success)">✅ Équilibré</strong>
            </div>
        </div>
    </div>

    </div>

    <div class="footer-actions" style="margin-top:12px">
        <div style="display:flex;gap:8px;margin-left:auto">
            <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </div>
    <input type="hidden" name="cessionnaires_json" id="cessionnaires-json" value="<?= e(json_encode($prefillCessionnaires)) ?>">
</form>

<?php if ($wizard['mode'] === 'nouvelle'): ?>
<template id="associe-step2-template">
    <div class="associe-card" data-associe-item>
        <div class="associe-card-header">
            <strong data-associe-title>Associé</strong>
            <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
        </div>
        <div class="form-grid">
            <h3 class="section-title">Identité</h3>
            <label class="field">
                <span>Civilite</span>
                <select data-field-name="associe_civilite">
                    <option value="M.">M.</option>
                    <option value="Mme">Mme</option>
                    <option value="Mlle">Mlle</option>
                </select>
            </label>
            <label class="field">
                <span>Prénom</span>
                <input data-field-name="associe_prenom" required>
            </label>
            <label class="field">
                <span>Nom</span>
                <input data-field-name="associe_nom" required>
            </label>
            <label class="field">
                <span>CIN</span>
                <input data-field-name="associe_cin">
            </label>
            <label class="field">
                <span>Date de naissance</span>
                <input data-field-name="associe_date_naissance" type="date">
            </label>
            <label class="field">
                <span>Lieu de naissance</span>
                <select data-field-name="associe_lieu_naissance">
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                    <option value="<?= e($ln) ?>"><?= e($ln) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Nationalité</span>
                <select data-field-name="associe_nationalite">
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach ($nationalitesOptions as $nat): ?>
                    <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <h3 class="section-title">Contact</h3>
            <label class="field">
                <span>Téléphone</span>
                <input data-field-name="associe_telephone">
            </label>
            <label class="field">
                <span>Email</span>
                <input data-field-name="associe_email" type="email">
            </label>
            <label class="field full">
                <span>Adresse</span>
                <textarea data-field-name="associe_adresse" rows="2" style="min-height:2.8em;padding:4px 8px"></textarea>
            </label>
            <h3 class="section-title">Participation</h3>
            <label class="field">
                <span>Qualité</span>
                <select data-field-name="associe_qualite">
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach ($qualitesAssocieOptions as $qa): ?>
                    <option value="<?= e($qa) ?>"><?= e($qa) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Nombre de parts</span>
                <input data-field-name="associe_parts" type="number" placeholder="100">
            </label>
            <label class="field">
                <span>Capital détenu (DH)</span>
                <input data-field-name="associe_capital_detenu" type="number" step="0.01" placeholder="50000">
            </label>
            <label class="field">
                <span>Gérant</span>
                <select data-field-name="associe_est_gerant">
                    <option value="0" selected>Non</option>
                    <option value="1">Oui</option>
                </select>
            </label>
            <label class="field" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
                    <input type="checkbox" data-field-name="associe_cede" value="1" class="associe-cede" data-associe-cede>
                    <span>Céder des parts</span>
                </label>
            </label>
            <label class="field associe-parts-ceder-field" style="display:none;grid-column:1/-1">
                <span>Parts à céder</span>
                <input type="number" data-field-name="associe_parts_a_ceder" class="associe-parts-ceder" placeholder="0" min="0">
            </label>
        </div>
    </div>
</template>
<?php endif; ?>

<input type="hidden" id="total-societe-parts" value="<?= $totalPartsSociete ?>">
<input type="hidden" id="total-societe-capital" value="<?= e(number_format($totalCapitalSociete, 2, ',', '')) ?>">
<input type="hidden" id="total-valeur-nominale" value="<?= e(number_format($valeurNominaleCession, 2, ',', '')) ?>">

<script>
(function(){
    'use strict';

    // ====== STEP 2: Auto-fill helpers ======
    function randFrom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
    function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function randDate(start, end) {
        var d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    var hommes = ['ALAOUI', 'BENALI', 'CHERKAOUI', 'DAHMANI', 'EL FASSI', 'FAHIMI', 'GHAZI', 'HAMMADI'];
    var prenoms = ['Mohamed', 'Ahmed', 'Hassan', 'Omar', 'Youssef', 'Karim', 'Mehdi', 'Said'];

    // ====== ASSOCIE PARTS/CAPITAL TOTAL TRACKING ======
    var totalPartsExpected = <?= (int) ($wizard['societe']['societe_part_social'] ?? 0) ?>;
    var totalCapitalExpected = <?= (float) ($wizard['societe']['societe_capital'] ?? 0) ?>;
    var isModeNouvelle = <?= $wizard['mode'] === 'nouvelle' ? 'true' : 'false' ?>;

    var totalPartsDisplay = document.getElementById('total-parts-display');
    var totalCapitalDisplay = document.getElementById('total-capital-display');
    var partsStatus = document.getElementById('parts-status');

    function updateAssocieTotals() {
        if (!isModeNouvelle) return;
        var partInputs = document.querySelectorAll('[name^="associe_parts"]');
        var capInputs = document.querySelectorAll('[name^="associe_capital_detenu"]');
        var totalP = 0, totalC = 0;
        partInputs.forEach(function(inp) {
            if (inp.name.indexOf('associe_parts_a_ceder') === 0) return;
            totalP += parseInt(inp.value) || 0;
        });
        capInputs.forEach(function(inp) { totalC += parseFloat((inp.value || '0').replace(',', '.')) || 0; });
        if (totalPartsDisplay) totalPartsDisplay.textContent = totalP;
        if (totalCapitalDisplay) totalCapitalDisplay.textContent = totalC.toFixed(2).replace('.', ',');
        if (partsStatus) {
            var ok = true;
            if (totalPartsExpected > 0 && totalP !== totalPartsExpected) ok = false;
            if (totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01) ok = false;
            if (ok) {
                partsStatus.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;color:var(--success);vertical-align:text-bottom;margin-right:4px">check_circle</span> <span style="color:var(--success);font-weight:600">Le total des parts et du capital correspond à la société.</span>';
            } else {
                var msgs = [];
                if (totalPartsExpected > 0 && totalP !== totalPartsExpected) msgs.push('parts: ' + totalP + '/' + totalPartsExpected);
                if (totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01) msgs.push('capital: ' + totalC.toFixed(0) + '/' + totalCapitalExpected.toFixed(0));
                partsStatus.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;color:var(--danger);vertical-align:text-bottom;margin-right:4px">error</span> <span style="color:var(--danger);font-weight:600">Le total des ' + msgs.join(' et ') + ' des associés ne correspond pas à la société.</span>';
            }
        }
        partInputs.forEach(function(f) {
            var hasErr = totalPartsExpected > 0 && totalP !== totalPartsExpected;
            f.classList.toggle('input-error', hasErr);
            var label = f.closest('.field');
            if (label) label.classList.toggle('field-error', hasErr);
        });
        capInputs.forEach(function(f) {
            var hasErr = totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01;
            f.classList.toggle('input-error', hasErr);
            var label = f.closest('.field');
            if (label) label.classList.toggle('field-error', hasErr);
        });
    }

    // Auto-calc capital from parts
    function recalcCapitalFromParts(partsInput) {
        if (!totalPartsExpected || !totalCapitalExpected) return;
        var card = partsInput.closest('[data-associe-item]');
        if (!card) return;
        var capInput = card.querySelector('[name^="associe_capital_detenu"]');
        if (!capInput) return;
        var parts = parseFloat(partsInput.value.replace(',', '.')) || 0;
        if (parts > 0) {
            capInput.value = ((parts / totalPartsExpected) * totalCapitalExpected).toFixed(2);
        }
    }

    document.getElementById('cession-associes-container')?.addEventListener('input', function(e) {
        if (e.target && e.target.name) {
            if (e.target.name.indexOf('associe_parts') === 0 && e.target.name.indexOf('associe_parts_a_ceder') !== 0) {
                recalcCapitalFromParts(e.target);
                updateAssocieTotals();
                syncToPartiesTable();
                updateSummary();
            } else if (e.target.name.indexOf('associe_capital_detenu') === 0) {
                updateAssocieTotals();
            }
        }
    });

    // Input on associe-parts-ceder → sync table
    document.getElementById('cession-associes-container')?.addEventListener('input', function(e) {
        if (e.target && e.target.matches('.associe-parts-ceder')) {
            syncToPartiesTable();
            updateSummary();
        }
    });

    // Toggle "Céder des parts" visibility
    document.getElementById('cession-associes-container')?.addEventListener('change', function(e) {
        if (e.target && e.target.matches('.associe-cede')) {
            var card = e.target.closest('[data-associe-item]');
            if (!card) return;
            var field = card.querySelector('.associe-parts-ceder-field');
            if (field) field.style.display = e.target.checked ? 'block' : 'none';
            var input = card.querySelector('.associe-parts-ceder');
            if (e.target.checked && input) {
                var partsInput = card.querySelector('[name^="associe_parts"]');
                if (partsInput && partsInput.value && !input.value) {
                    input.value = partsInput.value;
                }
                input.setAttribute('max', partsInput ? (partsInput.value || '0') : '0');
            } else if (!e.target.checked && input) {
                input.value = '0';
            }
            updateAssocieTotals();
            syncToPartiesTable();
            updateSummary();
        }
    });

    if (isModeNouvelle) updateAssocieTotals();

    // ====== ASSOCIE DYNAMIC ADD/REMOVE ======
    var associeContainer = document.getElementById('cession-associes-container');
    var associeTemplate = document.getElementById('associe-step2-template');

    function reindexAssocies() {
        var cards = associeContainer.querySelectorAll('[data-associe-item]');
        cards.forEach(function(card, idx) {
            var title = card.querySelector('[data-associe-title]');
            if (title) title.textContent = 'Associé ' + (idx + 1);
            card.querySelectorAll('[name]').forEach(function(el) {
                var name = el.getAttribute('name') || '';
                el.name = name.replace(/\[\d+\]/g, '[' + idx + ']');
            });
        });
    }

    document.getElementById('add-associe-step2')?.addEventListener('click', function() {
        if (!associeTemplate) return;
        var clone = associeTemplate.content.cloneNode(true);
        clone.querySelectorAll('[data-field-name]').forEach(function(el) {
            var field = el.getAttribute('data-field-name');
            el.name = field + '[0]';
            el.removeAttribute('data-field-name');
        });
        associeContainer.appendChild(clone);
        reindexAssocies();
        updateAssocieTotals();
    });

    associeContainer?.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-remove-associe]');
        if (btn) {
            var card = btn.closest('[data-associe-item]');
            if (card && confirm('Retirer cet associé ?')) {
                card.remove();
                reindexAssocies();
                updateAssocieTotals();
            }
        }
    });

    // ====== AUTO-FILL FOR ASSOCIES ======
    document.querySelectorAll('[data-fill-cession="2"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var form = btn.closest('form');
            if (!form) return;
            var associeCards = form.querySelectorAll('[data-associe-item]');
            if (associeCards.length === 0) {
                var addBtn = document.getElementById('add-associe-step2');
                if (addBtn) { addBtn.click(); }
                associeCards = form.querySelectorAll('[data-associe-item]');
            }
            var partBase = totalPartsExpected > 0 ? Math.floor(totalPartsExpected / associeCards.length) : randInt(100, 1000);
            var partRem = totalPartsExpected > 0 ? totalPartsExpected - (partBase * associeCards.length) : 0;
            var capBase = totalCapitalExpected > 0 ? Math.floor((totalCapitalExpected * 100 / associeCards.length)) / 100 : randInt(10000, 500000);
            var capRem = totalCapitalExpected > 0 ? Math.round((totalCapitalExpected - (capBase * associeCards.length)) * 100) / 100 : 0;

            associeCards.forEach(function(card, idx) {
                var civ = card.querySelector('select[name^="associe_civilite"]');
                if (civ) {
                    var civOpts = Array.from(civ.options).filter(function(o) { return o.value; });
                    if (civOpts.length) civ.value = randFrom(civOpts).value;
                }
                var nom = randFrom(hommes);
                var prenom = randFrom(prenoms);
                card.querySelector('[name^="associe_prenom"]') && (card.querySelector('[name^="associe_prenom"]').value = prenom);
                card.querySelector('[name^="associe_nom"]') && (card.querySelector('[name^="associe_nom"]').value = nom);
                card.querySelector('[name^="associe_cin"]') && (card.querySelector('[name^="associe_cin"]').value = 'AB' + randInt(100000, 999999));
                card.querySelector('[name^="associe_date_naissance"]') && (card.querySelector('[name^="associe_date_naissance"]').value = randDate(new Date(1960, 0, 1), new Date(1995, 11, 31)));
                var ln = card.querySelector('[name^="associe_lieu_naissance"]');
                if (ln) {
                    var lnOpts = Array.from(ln.options).filter(function(o) { return o.value; });
                    if (lnOpts.length) ln.value = randFrom(lnOpts).value;
                }
                var nat = card.querySelector('[name^="associe_nationalite"]');
                if (nat) {
                    var natOpts = Array.from(nat.options).filter(function(o) { return o.value; });
                    if (natOpts.length) nat.value = randFrom(natOpts).value;
                }
                card.querySelector('[name^="associe_telephone"]') && (card.querySelector('[name^="associe_telephone"]').value = '06' + randInt(10000000, 99999999));
                card.querySelector('[name^="associe_email"]') && (card.querySelector('[name^="associe_email"]').value = prenom.toLowerCase() + '.' + nom.toLowerCase() + '@email.ma');
                card.querySelector('[name^="associe_adresse"]') && (card.querySelector('[name^="associe_adresse"]').value = randInt(1, 200) + ' ' + randFrom(['Avenue', 'Rue', 'Boulevard']) + ' ' + randFrom(['Liberté', 'FAR', 'Hassan II', 'Mohammed VI', 'Résistance']));
                var ql = card.querySelector('[name^="associe_qualite"]');
                if (ql) {
                    var qOpts = Array.from(ql.options).filter(function(o) { return o.value; });
                    if (qOpts.length) ql.value = randFrom(qOpts).value;
                }
                var parts = partBase + (idx === associeCards.length - 1 ? partRem : 0);
                card.querySelector('[name^="associe_parts"]') && (card.querySelector('[name^="associe_parts"]').value = String(parts));
                card.querySelector('[name^="associe_est_gerant"]') && (card.querySelector('[name^="associe_est_gerant"]').value = Math.random() > 0.5 ? '1' : '0');
                // Auto-check "Céder des parts" for ~70% of associates
                var cedeCb = card.querySelector('.associe-cede');
                if (cedeCb && Math.random() < 0.7) {
                    cedeCb.checked = true;
                    var pf = card.querySelector('.associe-parts-ceder-field');
                    if (pf) pf.style.display = 'block';
                    var pi = card.querySelector('.associe-parts-ceder');
                    if (pi) {
                        var maxParts = parseInt(parts) || 0;
                        pi.value = String(Math.floor(maxParts * (0.3 + Math.random() * 0.5)));
                        pi.setAttribute('max', String(maxParts));
                    }
                }
            });
            updateAssocieTotals();
            form.querySelectorAll('input, select, textarea').forEach(function(f) {
                f.dispatchEvent(new Event('input', { bubbles: true }));
                f.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    // ====== STEP 3 CESSION DATA ======
    var prefillCessionnaires = <?= json_encode($prefillCessionnaires) ?> || [];
    var totalParts = <?= $totalPartsSociete ?>;
    var totalCapital = <?= $totalCapitalSociete ?>;
    var valeurNominale = <?= $valeurNominaleCession ?>;

    var modal = document.getElementById('parties-modal');
    var modalBody = document.getElementById('cessionnaires-modal-body');
    var modalCount = document.getElementById('modal-cessionnaire-count');
    var jsonInput = document.getElementById('cessionnaires-json');
    var partiesTbody = document.getElementById('parties-table-body');

    var lieuxNaissance = <?= json_encode(array_values($lieuxNaissanceOptions)) ?>;
    var nationalites = <?= json_encode(array_values($nationalitesOptions)) ?>;
    var qualites = <?= json_encode(array_values($qualitesAssocieOptions)) ?>;

    function openModal() {
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'all';
    }
    function closeModal() {
        modal.style.display = 'none';
        modal.style.opacity = '';
        modal.style.pointerEvents = '';
        syncToPartiesTable();
        syncToHiddenJson();
        updateSummary();
    }

    document.getElementById('btn-renseigner-parties')?.addEventListener('click', function() {
        if (modalBody.children.length === 0 && prefillCessionnaires.length > 0) {
            prefillCessionnaires.forEach(function(c) { addModalRow(c); });
        } else if (modalBody.children.length === 0) {
            addModalRow({});
        }
        updateModalCount();
        openModal();
    });

    document.getElementById('modal-close')?.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    document.getElementById('validate-cessionnaires')?.addEventListener('click', function() {
        var rows = modalBody.querySelectorAll('[data-cess-idx]');
        var valid = true;
        rows.forEach(function(r) {
            var nom = r.querySelector('.cess-nom').value.trim();
            var parts = parseInt(r.querySelector('.cess-parts').value) || 0;
            if (!nom || parts <= 0) valid = false;
        });
        if (!valid) {
            alert('Chaque cessionnaire doit avoir un nom et au moins 1 part.');
            return;
        }
        closeModal();
    });

    var cessCounter = 0;

    function createSelect(options, selected, cls) {
        var s = document.createElement('select');
        if (cls) s.className = cls;
        options.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o;
            opt.textContent = o;
            if (o === selected) opt.selected = true;
            s.appendChild(opt);
        });
        return s;
    }

    function createField(label, content) {
        var lbl = document.createElement('label');
        lbl.className = 'field';
        var span = document.createElement('span');
        span.textContent = label;
        lbl.appendChild(span);
        if (typeof content === 'string') {
            lbl.insertAdjacentHTML('beforeend', content);
        } else {
            lbl.appendChild(content);
        }
        return lbl;
    }

    function styleSmall(el) {
        el.style.fontSize = '0.78rem';
        el.style.padding = '4px 6px';
        return el;
    }

    function addModalRow(data) {
        data = data || {};
        var idx = cessCounter++;
        var card = document.createElement('div');
        card.className = 'associe-card';
        card.setAttribute('data-cess-idx', idx);
        card.style.marginBottom = '12px';

        // Header
        var header = document.createElement('div');
        header.className = 'associe-card-header';
        var title = document.createElement('strong');
        title.setAttribute('data-cess-title', '');
        title.textContent = 'Cessionnaire ' + (idx + 1);
        header.appendChild(title);
        var delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn btn-secondary';
        delBtn.style.cssText = 'padding:2px 8px;font-size:0.75rem';
        delBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.85rem">delete</span> Retirer';
        delBtn.addEventListener('click', function() { card.remove(); updateModalCount(); });
        header.appendChild(delBtn);
        card.appendChild(header);

        // Form grid
        var grid = document.createElement('div');
        grid.className = 'form-grid';

        // === IDENTITÉ ===
        var idTitle = document.createElement('h3');
        idTitle.className = 'section-title';
        idTitle.textContent = 'Identité';
        grid.appendChild(idTitle);

        var selCiv = createSelect(['M.', 'Mme', 'Mlle'], data.civilite || 'M.', 'cess-civilite');
        styleSmall(selCiv);
        grid.appendChild(createField('Civilité', selCiv));

        var inpPrenom = document.createElement('input');
        inpPrenom.className = 'cess-prenom'; inpPrenom.type = 'text'; inpPrenom.value = data.prenom || '';
        styleSmall(inpPrenom);
        grid.appendChild(createField('Prénom', inpPrenom));

        var inpNom = document.createElement('input');
        inpNom.className = 'cess-nom'; inpNom.type = 'text'; inpNom.value = data.nom || '';
        styleSmall(inpNom);
        grid.appendChild(createField('Nom', inpNom));

        var inpCin = document.createElement('input');
        inpCin.className = 'cess-cin'; inpCin.type = 'text'; inpCin.value = data.cin || '';
        styleSmall(inpCin);
        grid.appendChild(createField('CIN', inpCin));

        var inpDate = document.createElement('input');
        inpDate.className = 'cess-date'; inpDate.type = 'date'; inpDate.value = data.date_naissance || '';
        styleSmall(inpDate);
        grid.appendChild(createField('Date naissance', inpDate));

        var selLieu = createSelect(lieuxNaissance, data.lieu_naissance || '', 'cess-lieu');
        styleSmall(selLieu);
        grid.appendChild(createField('Lieu naissance', selLieu));

        var selNat = createSelect(nationalites, data.nationalite || '', 'cess-nationalite');
        styleSmall(selNat);
        grid.appendChild(createField('Nationalité', selNat));

        // === CONTACT ===
        var ctTitle = document.createElement('h3');
        ctTitle.className = 'section-title';
        ctTitle.textContent = 'Contact';
        grid.appendChild(ctTitle);

        var inpTel = document.createElement('input');
        inpTel.className = 'cess-tel'; inpTel.type = 'text'; inpTel.value = data.telephone || '';
        styleSmall(inpTel);
        grid.appendChild(createField('Téléphone', inpTel));

        var inpEmail = document.createElement('input');
        inpEmail.className = 'cess-email'; inpEmail.type = 'email'; inpEmail.value = data.email || '';
        styleSmall(inpEmail);
        grid.appendChild(createField('Email', inpEmail));

        var taAdr = document.createElement('textarea');
        taAdr.className = 'cess-adresse'; taAdr.value = data.adresse || '';
        styleSmall(taAdr);
        taAdr.style.resize = 'vertical';
        var adrField = createField('Adresse', taAdr);
        adrField.className = 'field full';
        grid.appendChild(adrField);

        // === PARTICIPATION ===
        var ptTitle = document.createElement('h3');
        ptTitle.className = 'section-title';
        ptTitle.textContent = 'Participation';
        grid.appendChild(ptTitle);

        var selQual = createSelect(qualites, data.qualite || '', 'cess-qualite');
        styleSmall(selQual);
        grid.appendChild(createField('Qualité', selQual));

        var inpParts = document.createElement('input');
        inpParts.className = 'cess-parts'; inpParts.type = 'number'; inpParts.value = data.parts || '';
        styleSmall(inpParts);
        grid.appendChild(createField('Parts', inpParts));

        var inpCap = document.createElement('input');
        inpCap.className = 'cess-capital'; inpCap.type = 'text'; inpCap.readOnly = true; inpCap.value = data.capital || '';
        styleSmall(inpCap);
        grid.appendChild(createField('Capital (DH)', inpCap));

        var selGer = createSelect(['Non', 'Oui'], data.est_gerant ? 'Oui' : 'Non', 'cess-gerant');
        styleSmall(selGer);
        grid.appendChild(createField('Gérant', selGer));

        card.appendChild(grid);
        modalBody.appendChild(card);

        // Parts → capital auto-fill
        if (inpParts && inpCap) {
            inpParts.addEventListener('input', function() {
                var p = parseInt(this.value) || 0;
                if (totalParts > 0 && totalCapital > 0) {
                    inpCap.value = ((p / totalParts) * totalCapital).toFixed(2).replace('.', ',');
                } else {
                    inpCap.value = '';
                }
            });
        }

        updateModalCount();
    }

    document.getElementById('add-cessionnaire-row')?.addEventListener('click', function() {
        addModalRow({});
    });

    function updateModalCount() {
        var count = modalBody.children.length;
        if (modalCount) modalCount.textContent = count + ' cessionnaire(s)';
    }

    // ====== AUTO-FILL CESSIONNAIRE CARDS ======
    function fillCessionnaireCards() {
        var names = ['ALAOUI', 'BENNANI', 'TOUIMI', 'EL FASSI', 'IDRISSI', 'TAZI', 'ZIANI', 'EL AMRANI', 'BOUDCHICHE', 'KABBAJ'];
        var prenoms = ['Sara', 'Omar', 'Fatima', 'Youssef', 'Amina', 'Karim', 'Nadia', 'Hassan', 'Imane', 'Rachid'];
        var parts = [500, 300, 200, 100];
        var cards = modalBody.querySelectorAll('[data-cess-idx]');
        cards.forEach(function(card, i) {
            var nameIdx = i % names.length;
            var prenomIdx = i % prenoms.length;
            var partIdx = i % parts.length;
            var seed = '' + (i + 1);

            setEl(card, '.cess-civilite', 'M.');
            setEl(card, '.cess-prenom', prenoms[prenomIdx]);
            setEl(card, '.cess-nom', names[nameIdx]);
            setEl(card, '.cess-cin', 'AB' + (123456 + i));
            setEl(card, '.cess-date', '199' + (seed % 9 + 1) + '-' + String( (i % 12) + 1 ).padStart(2,'0') + '-' + String( (i % 28) + 1 ).padStart(2,'0'));
            setEl(card, '.cess-lieu', 'Casablanca');
            setEl(card, '.cess-nationalite', 'Marocaine');
            setEl(card, '.cess-tel', '06' + String(11111111 + i).slice(0,8));
            setEl(card, '.cess-email', (prenoms[prenomIdx] + '.' + names[nameIdx] + i + '@test.ma').toLowerCase());
            setEl(card, '.cess-qualite', 'Associe');
            setEl(card, '.cess-parts', parts[partIdx]);
            setEl(card, '.cess-gerant', i === 0 ? 'Oui' : 'Non');
            setEl(card, '.cess-adresse', (i + 1) + ' Rue Mohammed V, Casablanca');

            var pi = card.querySelector('.cess-parts');
            var ci = card.querySelector('.cess-capital');
            if (pi && ci) {
                var p = parseInt(pi.value) || 0;
                if (totalParts > 0 && totalCapital > 0) {
                    ci.value = ((p / totalParts) * totalCapital).toFixed(2).replace('.', ',');
                }
            }
        });
    }
    function setEl(container, sel, val) {
        var el = container.querySelector(sel);
        if (!el) return;
        el.value = val;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.getElementById('fill-cessionnaires-btn')?.addEventListener('click', function() {
        if (modalBody.children.length === 0) addModalRow({});
        fillCessionnaireCards();
    });

    function syncToPartiesTable() {
        partiesTbody.querySelectorAll('[data-party-type="cessionnaire"]').forEach(function(r) { r.remove(); });
        // Remove previously dynamically-added cedant rows (from associate cards)
        partiesTbody.querySelectorAll('[data-party-type="cedant"][data-party-dynamic="1"]').forEach(function(r) { r.remove(); });
        // Add cedant rows from associate cards (nouvelle mode)
        var associeCards = document.querySelectorAll('[data-associe-item]');
        associeCards.forEach(function(card) {
            var cedeCb = card.querySelector('.associe-cede');
            if (!cedeCb || !cedeCb.checked) return;
            var partsInput = card.querySelector('[name^="associe_parts"]');
            var cederInput = card.querySelector('.associe-parts-ceder');
            var partsTotal = parseInt(partsInput ? partsInput.value : 0) || 0;
            var partsCeder = parseInt(cederInput ? cederInput.value : 0) || 0;
            var partsApres = Math.max(0, partsTotal - partsCeder);
            var nom = card.querySelector('[name^="associe_nom"]')?.value || '';
            var prenom = card.querySelector('[name^="associe_prenom"]')?.value || '';
            var cin = card.querySelector('[name^="associe_cin"]')?.value || '';
            var qualite = card.querySelector('[name^="associe_qualite"]')?.value || '';
            var capital = card.querySelector('[name^="associe_capital_detenu"]')?.value || '0';
            var gerant = card.querySelector('[name^="associe_est_gerant"]')?.value === '1';
            var capitalFmt = parseFloat(capital.replace(',', '.')).toFixed(2).replace('.', ',');
            var pct = totalParts > 0 ? (partsTotal / totalParts * 100).toFixed(2).replace('.', ',') : '0,00';
            var tr = document.createElement('tr');
            tr.setAttribute('data-party-type', 'cedant');
            tr.setAttribute('data-party-dynamic', '1');
            tr.setAttribute('data-parts-av', String(partsTotal));
            tr.setAttribute('data-parts-ap', String(partsApres));
            tr.innerHTML = '<td><span class="badge" style="background:rgba(252,66,74,0.1);color:var(--danger)">Cédant</span></td>' +
                '<td>' + escHtml(nom) + '</td>' +
                '<td>' + escHtml(prenom) + '</td>' +
                '<td>' + escHtml(cin) + '</td>' +
                '<td>' + pct + '%</td>' +
                '<td>' + partsTotal + '</td>' +
                '<td class="parts-apres-cell">' + partsApres + '</td>' +
                '<td>' + capitalFmt + '</td>' +
                '<td>' + escHtml(qualite) + '</td>' +
                '<td>' + (gerant ? '✅' : '❌') + '</td>' +
                '<td class="acquis-par-cell">-</td>';
            partiesTbody.appendChild(tr);
        });

        var rows = modalBody.querySelectorAll('[data-cess-idx]');
        rows.forEach(function(r) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-party-type', 'cessionnaire');
            var civilite = r.querySelector('.cess-civilite')?.value || 'M.';
            var prenom = r.querySelector('.cess-prenom')?.value || '';
            var nom = r.querySelector('.cess-nom')?.value || '';
            var cin = r.querySelector('.cess-cin')?.value || '';
            var parts = parseInt(r.querySelector('.cess-parts')?.value) || 0;
            var capital = r.querySelector('.cess-capital')?.value || '0,00';
            var qualite = r.querySelector('.cess-qualite')?.value || '';
            var estGerant = r.querySelector('.cess-gerant')?.value === 'Oui';
            var pct = totalParts > 0 ? (parts / totalParts * 100).toFixed(2).replace('.', ',') : '0,00';
            tr.innerHTML = '<td><span class="badge" style="background:rgba(0,184,148,0.1);color:var(--success)">Cessionnaire</span></td>' +
                '<td>' + escHtml(nom) + '</td>' +
                '<td>' + escHtml(prenom) + '</td>' +
                '<td>' + escHtml(cin) + '</td>' +
                '<td>' + pct + '%</td>' +
                '<td>0</td>' +
                '<td>' + parts + '</td>' +
                '<td>' + escHtml(capital) + '</td>' +
                '<td>' + escHtml(qualite) + '</td>' +
                '<td>' + (estGerant ? '✅' : '❌') + '</td>' +
                '<td>-</td>';
            partiesTbody.appendChild(tr);
        });
    }

    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function syncToHiddenJson() {
        var rows = modalBody.querySelectorAll('[data-cess-idx]');
        var data = [];
        rows.forEach(function(r) {
            data.push({
                civilite: r.querySelector('.cess-civilite')?.value || 'M.',
                prenom: r.querySelector('.cess-prenom')?.value || '',
                nom: r.querySelector('.cess-nom')?.value || '',
                cin: r.querySelector('.cess-cin')?.value || '',
                date_naissance: r.querySelector('.cess-date')?.value || '',
                lieu_naissance: r.querySelector('.cess-lieu')?.value || '',
                nationalite: r.querySelector('.cess-nationalite')?.value || '',
                telephone: r.querySelector('.cess-tel')?.value || '',
                email: r.querySelector('.cess-email')?.value || '',
                qualite: r.querySelector('.cess-qualite')?.value || '',
                adresse: r.querySelector('.cess-adresse')?.value || '',
                parts: parseInt(r.querySelector('.cess-parts')?.value) || 0,
                capital: r.querySelector('.cess-capital')?.value || '',
                est_gerant: r.querySelector('.cess-gerant')?.value === 'Oui' ? 1 : 0,
            });
        });
        jsonInput.value = JSON.stringify(data);
    }

    // ====== CÉDANT PARTS INPUTS ======
    document.querySelectorAll('.cedant-parts-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            validateCedantPart(this);
            updateSummary();
        });
    });

    document.querySelectorAll('.btn-ceder-tout').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var row = this.closest('.cedant-row');
            if (!row) return;
            var input = row.querySelector('.cedant-parts-input');
            if (input) {
                input.value = input.getAttribute('data-max') || input.max || 0;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });

    function validateCedantPart(inp) {
        var row = inp.closest('.cedant-row');
        if (!row) return;
        var max = parseInt(inp.getAttribute('data-max')) || 0;
        var val = parseInt(inp.value) || 0;
        var warning = row.querySelector('.cedant-warning');
        if (val > max) {
            inp.style.borderColor = 'var(--danger)';
            if (warning) warning.style.display = 'block';
        } else {
            inp.style.borderColor = '';
            if (warning) warning.style.display = 'none';
        }
    }

    // ====== GÉRANT MANAGEMENT ======
    document.querySelectorAll('.gerant-action-radio').forEach(function(r) {
        r.addEventListener('change', function() {
            var row = this.closest('.cedant-row');
            if (!row) return;
            var nominateField = row.querySelector('.nominate-field');
            if (nominateField) {
                nominateField.style.display = this.value === 'resign' ? 'inline-flex' : 'none';
            }
        });
    });

    // ====== UPDATE CEDANT PARTS APRÈS ======
    function updateCedantPartsApres() {
        // For "existante" mode: server-rendered cedants
        var cedantInputs = document.querySelectorAll('.cedant-parts-input');
        cedantInputs.forEach(function(inp) {
            var row = inp.closest('.cedant-row');
            if (!row) return;
            var aid = row.getAttribute('data-cedant-id');
            if (!aid) return;
            var partyRow = partiesTbody.querySelector('tr[data-party-id="' + aid + '"]');
            if (!partyRow) return;
            var max = parseInt(inp.getAttribute('data-max')) || 0;
            var val = parseInt(inp.value) || 0;
            var partsApres = Math.max(0, max - val);
            var cells = partyRow.querySelectorAll('td');
            if (cells.length >= 7) {
                cells[6].textContent = partsApres;
            }
        });
        // For "nouvelle" mode: associate cards with toggle
        if (!isModeNouvelle) return;
        var associeCards = document.querySelectorAll('[data-associe-item]');
        associeCards.forEach(function(card) {
            var cedeCb = card.querySelector('.associe-cede');
            if (!cedeCb || !cedeCb.checked) return;
            var partsInput = card.querySelector('[name^="associe_parts"]');
            var cederInput = card.querySelector('.associe-parts-ceder');
            var partsTotal = parseInt(partsInput ? partsInput.value : 0) || 0;
            var partsCeder = parseInt(cederInput ? cederInput.value : 0) || 0;
            var partsApres = Math.max(0, partsTotal - partsCeder);
            var nom = card.querySelector('[name^="associe_nom"]')?.value || '';
            var prenom = card.querySelector('[name^="associe_prenom"]')?.value || '';
            // Find the matching row by nom+prenom
            var partyRow = null;
            partiesTbody.querySelectorAll('tr[data-party-type="cedant"][data-party-dynamic="1"]').forEach(function(tr) {
                var rn = tr.querySelector('td:nth-child(2)')?.textContent || '';
                var rp = tr.querySelector('td:nth-child(3)')?.textContent || '';
                if (rn === nom && rp === prenom) partyRow = tr;
            });
            if (partyRow) {
                var cells = partyRow.querySelectorAll('td');
                if (cells.length >= 7) {
                    cells[6].textContent = partsApres;
                }
            }
        });
    }

    // ====== COMPUTE ACQUIS PAR (qui achéte les parts de chaque cédant) ======
    function computeAcquisPar() {
        // Build cedant pool from inputs
        var cedantPool = [];
        var cedantInputs = document.querySelectorAll('.cedant-parts-input');
        cedantInputs.forEach(function(inp) {
            var row = inp.closest('.cedant-row');
            if (!row) return;
            var aid = row.getAttribute('data-cedant-id');
            if (!aid) return;
            var partsACeder = parseInt(inp.value) || 0;
            if (partsACeder <= 0) return;
            var partyRow = partiesTbody.querySelector('tr[data-party-id="' + aid + '"]');
            if (!partyRow) return;
            var nom = partyRow.querySelector('td:nth-child(2)')?.textContent || '';
            cedantPool.push({ id: aid, nom: nom, remaining: partsACeder });
        });
        // Also include cedants from associate cards (nouvelle mode)
        if (isModeNouvelle) {
            var associeCards = document.querySelectorAll('[data-associe-item]');
            associeCards.forEach(function(card, idx) {
                var cedeCb = card.querySelector('.associe-cede');
                if (!cedeCb || !cedeCb.checked) return;
                var cederInput = card.querySelector('.associe-parts-ceder');
                var partsCeder = parseInt(cederInput ? cederInput.value : 0) || 0;
                if (partsCeder <= 0) return;
                var aid = 'new_' + idx;
                var nom = card.querySelector('[name^="associe_nom"]')?.value || '';
                // Find the matching dynamic cedant row
                var partyRow = null;
                partiesTbody.querySelectorAll('tr[data-party-type="cedant"][data-party-dynamic="1"]').forEach(function(tr) {
                    var rn = tr.querySelector('td:nth-child(2)')?.textContent || '';
                    if (rn === nom) partyRow = tr;
                });
                if (!partyRow) {
                    // Fallback: find by index in the dynamic rows list
                    var dynRows = partiesTbody.querySelectorAll('tr[data-party-type="cedant"][data-party-dynamic="1"]');
                    if (idx < dynRows.length) partyRow = dynRows[idx];
                }
                if (partyRow) {
                    var pnom = partyRow.querySelector('td:nth-child(2)')?.textContent || '';
                    cedantPool.push({ id: aid, nom: pnom, remaining: partsCeder });
                }
            });
        }

        // Get cessionnaires
        var cessionnaires = [];
        try { cessionnaires = JSON.parse(jsonInput.value || '[]'); } catch(e) {}

        // Distribute: assign cessionnaire parts to cedants (same algorithm as PHP)
        var cedToCess = {};
        var poolIdx = 0;
        cessionnaires.forEach(function(cess) {
            var need = parseInt(cess.parts) || 0;
            if (need <= 0) return;
            var cessNom = ((cess.prenom || '') + ' ' + (cess.nom || '')).trim() || 'Cessionnaire';
            while (need > 0 && poolIdx < cedantPool.length) {
                var available = cedantPool[poolIdx].remaining;
                if (available <= 0) { poolIdx++; continue; }
                var take = Math.min(need, available);
                var cid = cedantPool[poolIdx].id;
                if (!cedToCess[cid]) cedToCess[cid] = [];
                cedToCess[cid].push({ nom: cessNom, parts: take });
                cedantPool[poolIdx].remaining -= take;
                need -= take;
                if (cedantPool[poolIdx].remaining <= 0) poolIdx++;
            }
        });

        // Update cedant rows in parties table
        var allCedRows = partiesTbody.querySelectorAll('tr[data-party-type="cedant"]');
        allCedRows.forEach(function(partyRow) {
            var aid = partyRow.getAttribute('data-party-id');
            if (!aid) return;
            var cells = partyRow.querySelectorAll('td');
            if (cells.length < 11) return;
            var acquis = cedToCess[aid];
            if (acquis && acquis.length > 0) {
                cells[10].innerHTML = acquis.map(function(a) {
                    return '<span style="white-space:nowrap;display:inline-block;margin:1px 0">' + escHtml(a.parts) + ' p. → ' + escHtml(a.nom) + '</span>';
                }).join('<br>');
            } else {
                cells[10].textContent = '-';
            }
        });
    }

    // ====== SUMMARY ======
    function updateSummary() {
        syncToPartiesTable();
        var summary = document.getElementById('cession-summary');
        var cedantInputs = document.querySelectorAll('.cedant-parts-input');
        var totalCeded = 0, hasCedantData = false;
        cedantInputs.forEach(function(inp) {
            var v = parseInt(inp.value) || 0;
            totalCeded += v;
            if (v > 0) hasCedantData = true;
        });
        // Also count associate card cedants (nouvelle mode)
        if (isModeNouvelle) {
            document.querySelectorAll('[data-associe-item]').forEach(function(card) {
                var cedeCb = card.querySelector('.associe-cede');
                if (!cedeCb || !cedeCb.checked) return;
                var cederInput = card.querySelector('.associe-parts-ceder');
                var v = parseInt(cederInput ? cederInput.value : 0) || 0;
                totalCeded += v;
                if (v > 0) hasCedantData = true;
            });
        }

        var cessionnaires = [];
        try { cessionnaires = JSON.parse(jsonInput.value || '[]'); } catch(e) {}
        var totalAcquired = 0;
        cessionnaires.forEach(function(c) { totalAcquired += parseInt(c.parts) || 0; });

        var totalParties = partiesTbody.querySelectorAll('tr').length;

        updateCedantPartsApres();
        computeAcquisPar();

        if (!hasCedantData && cessionnaires.length === 0) {
            if (summary) summary.style.display = 'none';
            return;
        }

        if (summary) summary.style.display = 'block';
        var elTotalParts = document.getElementById('summary-total-parts');
        var elTotalAcquired = document.getElementById('summary-total-acquired');
        var elTotalParties = document.getElementById('summary-total-parties');
        var elStatus = document.getElementById('summary-status');
        var elStatusLabel = document.getElementById('summary-status-label');

        if (elTotalParts) elTotalParts.textContent = totalCeded;
        if (elTotalAcquired) elTotalAcquired.textContent = totalAcquired;
        if (elTotalParties) elTotalParties.textContent = totalParties;

        if (!elStatus) return;

        if (totalCeded !== totalAcquired || totalCeded > totalParts || totalAcquired > totalParts) {
            elStatus.innerHTML = '❌ Déséquilibré (cédées: ' + totalCeded + ', acquises: ' + totalAcquired + ')';
            elStatus.style.color = 'var(--danger)';
            if (elStatusLabel) elStatusLabel.textContent = 'Statut';
        } else if (totalCeded === 0 && totalAcquired === 0) {
            elStatus.textContent = '⚠️ Aucune part saisie';
            elStatus.style.color = 'var(--warning)';
            if (elStatusLabel) elStatusLabel.textContent = 'Statut';
        } else if (totalCeded === totalAcquired && totalCeded === totalParts) {
            elStatus.textContent = '✅ Équilibré (' + totalCeded + '/' + totalParts + ')';
            elStatus.style.color = 'var(--success)';
            if (elStatusLabel) elStatusLabel.textContent = 'Statut';
        } else if (totalCeded === totalAcquired) {
            elStatus.textContent = '⚠️ Partiel (' + totalCeded + '/' + totalParts + ')';
            elStatus.style.color = 'var(--warning)';
            if (elStatusLabel) elStatusLabel.textContent = 'Statut';
        }
    }

    // ====== FORM VALIDATION ======
    document.getElementById('merged-cession-form')?.addEventListener('submit', function(e) {
        syncToHiddenJson();

        // 1. Validate associate totals (mode nouvelle)
        if (isModeNouvelle) {
            var partInputs = document.querySelectorAll('[name^="associe_parts"]');
            var capInputs = document.querySelectorAll('[name^="associe_capital_detenu"]');
            var totalP = 0, totalC = 0;
            partInputs.forEach(function(inp) {
                if (inp.name.indexOf('associe_parts_a_ceder') === 0) return;
                totalP += parseInt(inp.value) || 0;
            });
            capInputs.forEach(function(inp) { totalC += parseFloat((inp.value || '0').replace(',', '.')) || 0; });
            var errors = [];
            if (totalPartsExpected > 0 && totalP !== totalPartsExpected) {
                errors.push('parts (' + totalP + '/' + totalPartsExpected + ')');
            }
            if (totalCapitalExpected > 0 && Math.abs(totalC - totalCapitalExpected) > 0.01) {
                errors.push('capital (' + totalC.toFixed(0) + '/' + totalCapitalExpected.toFixed(0) + ' DH)');
            }
            if (errors.length > 0) {
                e.preventDefault();
                alert('Le total des ' + errors.join(' et ') + ' des associés doit correspondre à la société.');
                return;
            }
        }

        // 2. Validate cession parts balance
        var hasCedant = false;
        document.querySelectorAll('.cedant-parts-input').forEach(function(inp) {
            if (parseInt(inp.value) > 0) hasCedant = true;
        });
        if (isModeNouvelle) {
            document.querySelectorAll('[data-associe-item]').forEach(function(card) {
                var cedeCb = card.querySelector('.associe-cede');
                if (!cedeCb || !cedeCb.checked) return;
                var cederInput = card.querySelector('.associe-parts-ceder');
                if (parseInt(cederInput ? cederInput.value : 0) > 0) hasCedant = true;
            });
        }

        var cessionnaires = [];
        try { cessionnaires = JSON.parse(jsonInput.value || '[]'); } catch(e) {}

        if (!hasCedant && cessionnaires.length === 0) {
            e.preventDefault();
            alert('Ajoutez au moins un cédant (parts à céder > 0) ou un cessionnaire.');
            return;
        }

        var totalCeded = 0;
        document.querySelectorAll('.cedant-parts-input').forEach(function(inp) {
            totalCeded += parseInt(inp.value) || 0;
        });
        if (isModeNouvelle) {
            document.querySelectorAll('[data-associe-item]').forEach(function(card) {
                var cedeCb = card.querySelector('.associe-cede');
                if (!cedeCb || !cedeCb.checked) return;
                var cederInput = card.querySelector('.associe-parts-ceder');
                totalCeded += parseInt(cederInput ? cederInput.value : 0) || 0;
            });
        }
        var totalAcquired = 0;
        cessionnaires.forEach(function(c) { totalAcquired += parseInt(c.parts) || 0; });

        if (totalCeded !== totalAcquired) {
            e.preventDefault();
            alert('Le total des parts cédées (' + totalCeded + ') doit être égal au total des parts acquises (' + totalAcquired + ').');
            return;
        }
    });

    // ====== INIT ======
    document.querySelectorAll('.cedant-parts-input').forEach(function(inp) {
        validateCedantPart(inp);
    });

    // Init "Céder des parts" toggles: show parts-ceder field if checked
    if (isModeNouvelle) {
        document.querySelectorAll('[data-associe-item]').forEach(function(card) {
            var cedeCb = card.querySelector('.associe-cede');
            if (!cedeCb) return;
            var field = card.querySelector('.associe-parts-ceder-field');
            if (field) field.style.display = cedeCb.checked ? 'block' : 'none';
            var input = card.querySelector('.associe-parts-ceder');
            if (cedeCb.checked && input) {
                var partsInput = card.querySelector('[name^="associe_parts"]');
                if (partsInput && partsInput.value && !input.value) {
                    input.value = partsInput.value;
                }
            }
        });
    }

    if (prefillCessionnaires.length > 0) {
        prefillCessionnaires.forEach(function(c) { addModalRow(c); });
        syncToPartiesTable();
        syncToHiddenJson();
    }

    updateSummary();
})();
</script>
<?php endif; ?>
