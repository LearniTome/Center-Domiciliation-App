<?php
declare(strict_types=1);

// PHP precompute
$socForPrix = $selectedSociete ?: ($wizard['societe'] ?? []);
$valeurNominaleCession = (float) ($socForPrix['societe_valeur_nominale'] ?? 0);

// POST handler
if (is_post() && $step === 3) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 2]);
    }

    $wizard['cession_date'] = field_value($_POST, 'cession_date');

    $cedantTypes = $_POST['cedant_type'] ?? [];
    $cedantAssocieIds = $_POST['cedant_associe_id'] ?? [];
    $cedantNoms = $_POST['cedant_nom_complet'] ?? [];
    $cedantCins = $_POST['cedant_cin'] ?? [];
    $cessionnaireTypes = $_POST['cessionnaire_type'] ?? [];
    $cessionnaireAssocieIds = $_POST['cessionnaire_associe_id'] ?? [];
    $cessionnaireNoms = $_POST['cessionnaire_nom'] ?? [];
    $cessionnairePrenoms = $_POST['cessionnaire_prenom'] ?? [];
    $cessionnaireCins = $_POST['cessionnaire_cin'] ?? [];
    $cessionnaireCivilites = $_POST['cessionnaire_civilite'] ?? [];
    $cessionnaireDates = $_POST['cessionnaire_date_naissance'] ?? [];
    $cessionnaireLieux = $_POST['cessionnaire_lieu_naissance'] ?? [];
    $cessionnaireNationalites = $_POST['cessionnaire_nationalite'] ?? [];
    $cessionnaireAdresses = $_POST['cessionnaire_adresse'] ?? [];
    $cessionnaireTelephones = $_POST['cessionnaire_telephone'] ?? [];
    $cessionnaireEmails = $_POST['cessionnaire_email'] ?? [];
    $cessionnaireQualites = $_POST['cessionnaire_qualite'] ?? [];
    $cessionnaireParts = $_POST['cessionnaire_parts'] ?? [];
    $cessionnaireCapitals = $_POST['cessionnaire_capital_detenu'] ?? [];
    $cessionnaireGerants = $_POST['cessionnaire_est_gerant'] ?? [];
    $pourcentages = $_POST['pourcentage'] ?? [];
    $partsCedees = $_POST['parts_cedees'] ?? [];
    $prixUnitaires = $_POST['prix_unitaire'] ?? [];
    $prixTotaux = $_POST['prix_total'] ?? [];
    $nommerGerant = $_POST['nommer_gerant'] ?? [];

    $totalParts = (int) ($socForPrix['societe_part_social'] ?? 0);

    $wizard['parts'] = [];
    $count = max(count($cedantNoms), count($cessionnaireNoms), count($partsCedees));
    for ($i = 0; $i < $count; $i++) {
        $cedType = $cedantTypes[$i] ?? 'existant';
        $cedAssocieId = (int) ($cedantAssocieIds[$i] ?? 0);
        $cedNom = trim((string) ($cedantNoms[$i] ?? ''));
        $cedCin = trim((string) ($cedantCins[$i] ?? ''));
        if ($cedNom === '' && $cedAssocieId > 0) {
            if (($pdo ?? null) instanceof PDO) {
                $a = fetch_record($pdo, 'associes', $cedAssocieId);
                if ($a) { $cedNom = $a['associe_nom_complet'] ?? ''; $cedCin = $a['associe_cin'] ?? ''; }
            }
            if ($cedNom === '' && !empty($wizard['associes'])) {
                foreach ($wizard['associes'] as $wa) {
                    if (((int) ($wa['id'] ?? 0)) === $cedAssocieId) {
                        $cedNom = $wa['associe_nom_complet'] ?? '';
                        $cedCin = $wa['associe_cin'] ?? '';
                        break;
                    }
                }
            }
        }

        $cessType = $cessionnaireTypes[$i] ?? 'existant';
        $cessAssocieId = (int) ($cessionnaireAssocieIds[$i] ?? 0);
        $cessNom = trim((string) ($cessionnairePrenoms[$i] ?? '') . ' ' . (string) ($cessionnaireNoms[$i] ?? ''));
        $cessCin = trim((string) ($cessionnaireCins[$i] ?? ''));
        if ($cessType === 'existant' && $cessAssocieId > 0 && $cessNom === '' && ($pdo ?? null) instanceof PDO) {
            $a = fetch_record($pdo, 'associes', $cessAssocieId);
            if ($a) { $cessNom = $a['associe_nom_complet'] ?? ''; $cessCin = $a['associe_cin'] ?? ''; }
        }
        if ($cessNom === '' && !empty($wizard['associes'])) {
            foreach ($wizard['associes'] as $wa) {
                if (((int) ($wa['id'] ?? 0)) === $cessAssocieId) {
                    $cessNom = $wa['associe_nom_complet'] ?? '';
                    $cessCin = $wa['associe_cin'] ?? '';
                    break;
                }
            }
        }

        $pct = money_value(['v' => $pourcentages[$i] ?? '0'], 'v');
        $parts = (int) ($partsCedees[$i] ?? 0);
        if ($pct > 0 && $totalParts > 0) {
            $parts = (int) round(($pct / 100) * $totalParts);
        }

        if ($cedNom === '' || $cessNom === '' || $parts <= 0) continue;

        $pu = money_value(['v' => $prixUnitaires[$i] ?? '0'], 'v');
        $pt = money_value(['v' => $prixTotaux[$i] ?? '0'], 'v');
        if ($pt === null || $pt <= 0) $pt = $pu * $parts;

        $cessionnairePartsVal = (int) ($cessionnaireParts[$i] ?? 0);
        $cessionnaireCapitalVal = trim((string) ($cessionnaireCapitals[$i] ?? ''));
        // Toujours recalculer parts acquises et capital a partir des parts cedees
        if ($parts > 0) {
            $cessionnairePartsVal = $parts;
            $totalCapitalSociete = (float) ($socForPrix['societe_capital'] ?? 0);
            if ($totalParts > 0 && $totalCapitalSociete > 0) {
                $cessionnaireCapitalVal = number_format(($parts / $totalParts) * $totalCapitalSociete, 2, '.', '');
            }
        }

        $wizard['parts'][] = [
            'cedant_type' => $cedType,
            'cedant_associe_id' => $cedAssocieId,
            'cedant_nom_complet' => $cedNom,
            'cedant_cin' => $cedCin,
            'cessionnaire_type' => $cessType,
            'cessionnaire_associe_id' => $cessAssocieId,
            'cessionnaire_nom_complet' => $cessNom,
            'cessionnaire_cin' => $cessCin,
            'cessionnaire_civilite' => trim((string) ($cessionnaireCivilites[$i] ?? 'M.')),
            'cessionnaire_date_naissance' => trim((string) ($cessionnaireDates[$i] ?? '')),
            'cessionnaire_lieu_naissance' => trim((string) ($cessionnaireLieux[$i] ?? '')),
            'cessionnaire_nationalite' => trim((string) ($cessionnaireNationalites[$i] ?? '')),
            'cessionnaire_adresse' => trim((string) ($cessionnaireAdresses[$i] ?? '')),
            'cessionnaire_telephone' => trim((string) ($cessionnaireTelephones[$i] ?? '')),
            'cessionnaire_email' => trim((string) ($cessionnaireEmails[$i] ?? '')),
            'cessionnaire_qualite' => trim((string) ($cessionnaireQualites[$i] ?? '')),
            'cessionnaire_parts' => $cessionnairePartsVal,
            'cessionnaire_capital_detenu' => $cessionnaireCapitalVal,
            'cessionnaire_est_gerant' => !empty($nommerGerant[$i]) ? 1 : 0,
            'pourcentage' => $pct > 0 ? $pct : null,
            'parts_cedees' => $parts,
            'prix_unitaire' => $pu,
            'prix_total' => $pt,
            'nommer_gerant' => !empty($nommerGerant[$i]) ? 1 : 0,
        ];
    }

    if (empty($wizard['parts'])) {
        set_flash('error', 'Ajoutez au moins une ligne de cession valide.');
        redirect_to('cession', ['step' => 3]);
    }
    redirect_to('cession', ['step' => 4]);
}

// HTML view
if ($step === 3):
?>
<form method="post" id="cession-form" data-valeur-nominale="<?= $valeurNominaleCession ?>">
    <?= csrf_input() ?>
    <input type="hidden" name="nav_action" value="next">

    <input type="hidden" id="total-societe-parts" value="<?= (int) ($socForPrix['societe_part_social'] ?? 0) ?>">
    <input type="hidden" id="total-societe-capital" value="<?= e(number_format((float) ($socForPrix['societe_capital'] ?? 0), 2, ',', '')) ?>">
    <input type="hidden" id="total-valeur-nominale" value="<?= e(number_format((float) ($socForPrix['societe_valeur_nominale'] ?? 0), 2, ',', '')) ?>">

    <div style="margin-top:12px">
        <div class="section-header" style="margin-bottom:12px">
            <strong>Date de la cession</strong>
            <input type="date" name="cession_date" id="cession_date" value="<?= e($wizard['cession_date'] ?? date('Y-m-d')) ?>" required style="max-width:200px">
        </div>

        <div class="section-header" style="margin-bottom:12px">
            <strong>Lignes de cession</strong>
            <button class="btn btn-info" type="button" data-fill-cession="3" style="margin-left:auto"><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        </div>
        <div id="cession-parts-container">
            <?php
            $cedantList = !empty($selectedAssocies) ? $selectedAssocies : ($wizard['associes'] ?? []);
            $socDataForPrix = $selectedSociete ?: ($wizard['societe'] ?? []);
            $defaultPrixUnitaire = (float) ($socDataForPrix['societe_valeur_nominale'] ?? 0);
            ?>
            <?php $partIndex = 0; ?>
            <?php if (!empty($wizard['parts'])): ?>
                <?php foreach ($wizard['parts'] as $pi => $part): ?>
                    <?php $partIndex = $pi;
                    $selectedCedantId = (int) ($part['cedant_associe_id'] ?? 0);
                    $selectedCedantData = null;
                    if ($selectedCedantId > 0) {
                        foreach ($cedantList as $a) {
                            if ((int) ($a['id'] ?? 0) === $selectedCedantId) { $selectedCedantData = $a; break; }
                        }
                    }
                    $nomComplet = $part['cessionnaire_nom_complet'] ?? '';
                    $cessPrenom = '';
                    $cessNom = '';
                    if ($nomComplet !== '') {
                        $sp = explode(' ', $nomComplet, 2);
                        $cessPrenom = $sp[0] ?? '';
                        $cessNom = $sp[1] ?? '';
                    }
                    ?>
<div style="margin-top:16px" data-part="<?= $partIndex ?>">
    <div style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-bottom:12px">
        <div class="section-header" style="margin-bottom:8px">
            <strong style="color:var(--danger)">Cédant</strong>
        </div>
        <input type="hidden" name="cedant_type[<?= $partIndex ?>]" value="existant">
        <input type="hidden" name="cedant_nom_complet[<?= $partIndex ?>]" class="cedant-nom-hidden" value="<?= e($part['cedant_nom_complet'] ?? '') ?>">
        <input type="hidden" name="cedant_cin[<?= $partIndex ?>]" class="cedant-cin-hidden" value="<?= e($part['cedant_cin'] ?? '') ?>">
        <select name="cedant_associe_id[<?= $partIndex ?>]" class="cedant-select">
            <option value="">-- Sélectionnez --</option>
            <?php foreach ($cedantList as $assoc): ?>
            <option value="<?= (int) ($assoc['id'] ?? 0) ?>"
                data-nom="<?= e($assoc['associe_nom_complet'] ?? '') ?>"
                data-cin="<?= e($assoc['associe_cin'] ?? '') ?>"
                data-parts="<?= (int) ($assoc['associe_parts'] ?? 0) ?>"
                data-capital="<?= e($assoc['associe_capital_detenu'] ?? '') ?>"
                <?= $selectedCedantId === (int) ($assoc['id'] ?? 0) ? 'selected' : '' ?>>
                <?= e($assoc['associe_nom_complet'] ?? '') ?> (<?= (int) ($assoc['associe_parts'] ?? 0) ?> parts)
            </option>
            <?php endforeach; ?>
        </select>
        <div class="cedant-info" style="margin-top:10px;display:<?= $selectedCedantData ? 'grid' : 'none' ?>;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;padding:8px;background:var(--bg-card);border-radius:4px">
            <div><small style="color:var(--text-muted)">Nom complet</small><br><strong class="cedant-display-nom"><?= e($selectedCedantData['associe_nom_complet'] ?? '-') ?></strong></div>
            <div><small style="color:var(--text-muted)">CIN</small><br><strong class="cedant-display-cin"><?= e($selectedCedantData['associe_cin'] ?? '-') ?></strong></div>
            <div><small style="color:var(--text-muted)">Parts détenues</small><br><strong class="cedant-display-parts"><?= (int) ($selectedCedantData['associe_parts'] ?? 0) ?></strong></div>
            <div><small style="color:var(--text-muted)">Capital (DH)</small><br><strong class="cedant-display-capital"><?= e($selectedCedantData['associe_capital_detenu'] ?? '0') ?></strong></div>
        </div>
    </div>
    <div style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-bottom:12px">
        <div class="section-header" style="margin-bottom:8px"><strong style="color:var(--success)">Cessionnaire</strong>
            <button type="button" class="btn-icon danger remove-part" style="margin-left:auto" title="Supprimer cette ligne"><span class="material-symbols-outlined">delete</span></button>
        </div>
        <input type="hidden" name="cessionnaire_type[<?= $partIndex ?>]" value="nouveau">
        <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;gap:8px">
            <div class="field"><label>Civilité</label><select name="cessionnaire_civilite[<?= $partIndex ?>]"><option value="M." <?= ($part['cessionnaire_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option><option value="Mme" <?= ($part['cessionnaire_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option><option value="Mlle" <?= ($part['cessionnaire_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option></select></div>
            <div class="field"><label>Prénom</label><input type="text" name="cessionnaire_prenom[<?= $partIndex ?>]" value="<?= e($cessPrenom) ?>"></div>
            <div class="field"><label>Nom</label><input type="text" name="cessionnaire_nom[<?= $partIndex ?>]" value="<?= e($cessNom) ?>"></div>
            <div class="field"><label>CIN</label><input type="text" name="cessionnaire_cin[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_cin'] ?? '') ?>"></div>
            <div class="field"><label>Date naissance</label><input type="date" name="cessionnaire_date_naissance[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_date_naissance'] ?? '') ?>"></div>
            <div class="field"><label>Lieu naissance</label><select name="cessionnaire_lieu_naissance[<?= $partIndex ?>]"><option value="">-- Sélectionnez --</option><?php foreach ($lieuxNaissanceOptions as $ln): ?><option value="<?= e($ln) ?>" <?= ($part['cessionnaire_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Nationalité</label><select name="cessionnaire_nationalite[<?= $partIndex ?>]"><option value="">-- Sélectionnez --</option><?php foreach ($nationalitesOptions as $nat): ?><option value="<?= e($nat) ?>" <?= ($part['cessionnaire_nationalite'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Téléphone</label><input type="text" name="cessionnaire_telephone[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_telephone'] ?? '') ?>"></div>
            <div class="field"><label>Email</label><input type="email" name="cessionnaire_email[<?= $partIndex ?>]" value="<?= e($part['cessionnaire_email'] ?? '') ?>"></div>
            <div class="field"><label>Qualité</label><select name="cessionnaire_qualite[<?= $partIndex ?>]"><option value="">-- Sélectionnez --</option><?php foreach ($qualitesAssocieOptions as $q): ?><option value="<?= e($q) ?>" <?= ($part['cessionnaire_qualite'] ?? '') === $q ? 'selected' : '' ?>><?= e($q) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="field" style="margin-top:8px"><label>Adresse</label><textarea name="cessionnaire_adresse[<?= $partIndex ?>]" rows="2"><?= e($part['cessionnaire_adresse'] ?? '') ?></textarea></div>
    </div>
    <div style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-top:12px">
        <div class="section-header" style="margin-bottom:8px"><strong style="color:var(--info)">Parts &amp; Prix</strong></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px">
            <div class="field"><label>%</label><input type="text" name="pourcentage[<?= $partIndex ?>]" class="pourcentage-input" placeholder="0,00" value="<?= e(isset($part['pourcentage']) ? number_format((float) $part['pourcentage'], 2, ',', '') : '') ?>"></div>
            <div class="field"><label>Parts cédées</label><input type="number" name="parts_cedees[<?= $partIndex ?>]" class="parts-cedees-input" value="<?= (int) ($part['parts_cedees'] ?? 0) ?>"></div>
            <div class="field"><label>Prix unitaire (DH)</label><input type="text" name="prix_unitaire[<?= $partIndex ?>]" class="prix-unitaire-input" placeholder="0,00" value="<?= e(isset($part['prix_unitaire']) && (float) $part['prix_unitaire'] > 0 ? number_format((float) $part['prix_unitaire'], 2, ',', '') : ($defaultPrixUnitaire > 0 ? number_format($defaultPrixUnitaire, 2, ',', '') : '')) ?>"></div>
            <div class="field"><label>Prix total (DH)</label><input type="text" name="prix_total[<?= $partIndex ?>]" class="prix-total-input" placeholder="0,00" value="<?= e(isset($part['prix_total']) ? number_format((float) $part['prix_total'], 2, ',', '') : '') ?>"></div>
            <div class="field"><label>Parts acquises</label><input type="number" name="cessionnaire_parts[<?= $partIndex ?>]" placeholder="0" value="<?= (int) ($part['cessionnaire_parts'] ?? 0) ?>"></div>
            <div class="field"><label>Capital après (DH)</label><input type="text" name="cessionnaire_capital_detenu[<?= $partIndex ?>]" placeholder="0,00" value="<?= e($part['cessionnaire_capital_detenu'] ?? '') ?>"></div>
            <div class="field"><label>Nommer Gérant</label><select name="nommer_gerant[<?= $partIndex ?>]"><option value="0" <?= empty($part['nommer_gerant']) ? 'selected' : '' ?>>Non</option><option value="1" <?= !empty($part['nommer_gerant']) ? 'selected' : '' ?>>Oui</option></select></div>
        </div>
    </div>
</div>
                    <?php $partIndex = $pi + 1; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                    $part = [
                        'cedant_type' => 'existant', 'cedant_associe_id' => 0, 'cedant_nom_complet' => '', 'cedant_cin' => '',
                        'cessionnaire_type' => 'nouveau', 'cessionnaire_associe_id' => 0, 'cessionnaire_nom_complet' => '', 'cessionnaire_cin' => '',
                        'cessionnaire_civilite' => 'M.', 'cessionnaire_date_naissance' => '', 'cessionnaire_lieu_naissance' => '',
                        'cessionnaire_nationalite' => '', 'cessionnaire_adresse' => '', 'cessionnaire_telephone' => '', 'cessionnaire_email' => '', 'cessionnaire_qualite' => '', 'cessionnaire_parts' => 0, 'cessionnaire_capital_detenu' => '', 'cessionnaire_est_gerant' => 0,
                        'parts_cedees' => '', 'prix_unitaire' => '', 'prix_total' => '',
                    ];
                    $partIndex = 0;
                    $selectedCedantId = 0;
                    $selectedCedantData = null;
                ?>
<div style="margin-top:16px" data-part="0">
    <div style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-bottom:12px">
        <div class="section-header" style="margin-bottom:8px"><strong style="color:var(--danger)">Cédant</strong></div>
        <input type="hidden" name="cedant_type[0]" value="existant">
        <input type="hidden" name="cedant_nom_complet[0]" class="cedant-nom-hidden" value="">
        <input type="hidden" name="cedant_cin[0]" class="cedant-cin-hidden" value="">
        <select name="cedant_associe_id[0]" class="cedant-select">
            <option value="">-- Sélectionnez --</option>
            <?php foreach ($cedantList as $assoc): ?>
            <option value="<?= (int) ($assoc['id'] ?? 0) ?>"
                data-nom="<?= e($assoc['associe_nom_complet'] ?? '') ?>"
                data-cin="<?= e($assoc['associe_cin'] ?? '') ?>"
                data-parts="<?= (int) ($assoc['associe_parts'] ?? 0) ?>"
                data-capital="<?= e($assoc['associe_capital_detenu'] ?? '') ?>">
                <?= e($assoc['associe_nom_complet'] ?? '') ?> (<?= (int) ($assoc['associe_parts'] ?? 0) ?> parts)
            </option>
            <?php endforeach; ?>
        </select>
        <div class="cedant-info" style="margin-top:10px;display:none;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;padding:8px;background:var(--bg-card);border-radius:4px">
            <div><small style="color:var(--text-muted)">Nom complet</small><br><strong class="cedant-display-nom">-</strong></div>
            <div><small style="color:var(--text-muted)">CIN</small><br><strong class="cedant-display-cin">-</strong></div>
            <div><small style="color:var(--text-muted)">Parts détenues</small><br><strong class="cedant-display-parts">0</strong></div>
            <div><small style="color:var(--text-muted)">Capital (DH)</small><br><strong class="cedant-display-capital">0</strong></div>
        </div>
    </div>
    <div style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-bottom:12px">
        <div class="section-header" style="margin-bottom:8px"><strong style="color:var(--success)">Cessionnaire</strong>
            <button type="button" class="btn-icon danger remove-part" style="margin-left:auto" title="Supprimer cette ligne"><span class="material-symbols-outlined">delete</span></button>
        </div>
        <input type="hidden" name="cessionnaire_type[0]" value="nouveau">
        <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;gap:8px">
            <div class="field"><label>Civilité</label><select name="cessionnaire_civilite[0]"><option value="M." selected>M.</option><option value="Mme">Mme</option><option value="Mlle">Mlle</option></select></div>
            <div class="field"><label>Prénom</label><input type="text" name="cessionnaire_prenom[0]" value=""></div>
            <div class="field"><label>Nom</label><input type="text" name="cessionnaire_nom[0]" value=""></div>
            <div class="field"><label>CIN</label><input type="text" name="cessionnaire_cin[0]" value=""></div>
            <div class="field"><label>Date naissance</label><input type="date" name="cessionnaire_date_naissance[0]" value=""></div>
            <div class="field"><label>Lieu naissance</label><select name="cessionnaire_lieu_naissance[0]"><option value="">-- Sélectionnez --</option><?php foreach ($lieuxNaissanceOptions as $ln): ?><option value="<?= e($ln) ?>"><?= e($ln) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Nationalité</label><select name="cessionnaire_nationalite[0]"><option value="">-- Sélectionnez --</option><?php foreach ($nationalitesOptions as $nat): ?><option value="<?= e($nat) ?>"><?= e($nat) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Téléphone</label><input type="text" name="cessionnaire_telephone[0]" value=""></div>
            <div class="field"><label>Email</label><input type="email" name="cessionnaire_email[0]" value=""></div>
            <div class="field"><label>Qualité</label><select name="cessionnaire_qualite[0]"><option value="">-- Sélectionnez --</option><?php foreach ($qualitesAssocieOptions as $q): ?><option value="<?= e($q) ?>"><?= e($q) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="field" style="margin-top:8px"><label>Adresse</label><textarea name="cessionnaire_adresse[0]" rows="2"></textarea></div>
    </div>
    <div style="border:1px solid var(--line);border-radius:6px;padding:12px;margin-top:12px">
        <div class="section-header" style="margin-bottom:8px"><strong style="color:var(--info)">Parts &amp; Prix</strong></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px">
            <div class="field"><label>%</label><input type="text" name="pourcentage[0]" class="pourcentage-input" placeholder="0,00" value=""></div>
            <div class="field"><label>Parts cédées</label><input type="number" name="parts_cedees[0]" class="parts-cedees-input" value=""></div>
            <div class="field"><label>Prix unitaire (DH)</label><input type="text" name="prix_unitaire[0]" class="prix-unitaire-input" placeholder="0,00" value="<?= $defaultPrixUnitaire > 0 ? number_format($defaultPrixUnitaire, 2, ',', '') : '' ?>"></div>
            <div class="field"><label>Prix total (DH)</label><input type="text" name="prix_total[0]" class="prix-total-input" placeholder="0,00" value=""></div>
            <div class="field"><label>Parts acquises</label><input type="number" name="cessionnaire_parts[0]" placeholder="0" value="0"></div>
            <div class="field"><label>Capital après (DH)</label><input type="text" name="cessionnaire_capital_detenu[0]" placeholder="0,00" value=""></div>
            <div class="field"><label>Nommer Gérant</label><select name="nommer_gerant[0]"><option value="0" selected>Non</option><option value="1">Oui</option></select></div>
        </div>
    </div>
</div>
                <?php $partIndex = 1; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-next" id="add-cession-part" style="margin-top:8px" data-part-index="<?= $partIndex ?>">
            <span class="material-symbols-outlined">add</span> Ajouter une ligne
        </button>
    </div>

    <div class="footer-actions">
        <div style="display:flex;gap:8px;margin-right:auto">
            <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 2])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
        <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
        <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
    </div>
</form>

<script>
(function(){
    function randFrom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
    function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function randDate(start, end) {
        var d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
    }
    var hommes = ['ALAOUI', 'BENALI', 'CHERKAOUI', 'DAHMANI', 'EL FASSI', 'FAHIMI', 'GHAZI', 'HAMMADI'];
    var prenoms = ['Mohamed', 'Ahmed', 'Hassan', 'Omar', 'Youssef', 'Karim', 'Mehdi', 'Said'];

    // Sync cedant hidden fields and info display when selection changes
    function syncCedant(sel) {
        var row = sel.closest('[data-part]');
        if (!row) return;
        var opt = sel.options[sel.selectedIndex];
        var nh = row.querySelector('.cedant-nom-hidden');
        if (nh) nh.value = opt ? (opt.getAttribute('data-nom') || '') : '';
        var ch = row.querySelector('.cedant-cin-hidden');
        if (ch) ch.value = opt ? (opt.getAttribute('data-cin') || '') : '';
        var infoDiv = row.querySelector('.cedant-info');
        if (infoDiv) {
            if (opt && opt.value) {
                infoDiv.style.display = 'grid';
                var dn = infoDiv.querySelector('.cedant-display-nom');
                if (dn) dn.textContent = opt.getAttribute('data-nom') || '-';
                var dc = infoDiv.querySelector('.cedant-display-cin');
                if (dc) dc.textContent = opt.getAttribute('data-cin') || '-';
                var dp = infoDiv.querySelector('.cedant-display-parts');
                if (dp) dp.textContent = opt.getAttribute('data-parts') || '0';
                var dk = infoDiv.querySelector('.cedant-display-capital');
                if (dk) dk.textContent = opt.getAttribute('data-capital') || '0';
            } else {
                infoDiv.style.display = 'none';
            }
        }
    }

    document.querySelectorAll('.cedant-select').forEach(function(sel) {
        sel.addEventListener('change', function() { syncCedant(sel); });
        syncCedant(sel);
    });

    // Calculate prix_total
    function calcPrixTotal() {
        var row = this.closest('[data-part]');
        if (!row) return;
        var parts = parseFloat(row.querySelector('.parts-cedees-input')?.value.replace(',', '.')) || 0;
        var pu = parseFloat(row.querySelector('.prix-unitaire-input')?.value.replace(',', '.')) || 0;
        var ptInput = row.querySelector('.prix-total-input');
        if (ptInput) ptInput.value = (parts * pu).toFixed(2).replace('.', ',');
    }

    function calcPourcentage() {
        var row = this.closest('[data-part]');
        if (!row) return;
        var parts = parseFloat(row.querySelector('.parts-cedees-input')?.value.replace(',', '.')) || 0;
        var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
        var pctInput = row.querySelector('.pourcentage-input');
        if (pctInput && totalParts > 0) {
            pctInput.value = ((parts / totalParts) * 100).toFixed(2).replace('.', ',');
        }
    }

    function calcCessionnaireFields() {
        var row = this.closest('[data-part]');
        if (!row) return;
        var parts = parseFloat(row.querySelector('.parts-cedees-input')?.value.replace(',', '.')) || 0;
        var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
        var totalCapital = parseFloat(document.getElementById('total-societe-capital')?.value.replace(',', '.')) || 0;
        var idx = row.getAttribute('data-part');
        var partsInput = row.querySelector('input[name="cessionnaire_parts[' + idx + ']"]');
        if (partsInput) partsInput.value = parts;
        var capInput = row.querySelector('input[name="cessionnaire_capital_detenu[' + idx + ']"]');
        if (capInput && totalParts > 0) {
            capInput.value = ((parts / totalParts) * totalCapital).toFixed(2).replace('.', ',');
        }
    }

    function calcAllFromPct(pctInput) {
        var row = pctInput.closest('[data-part]');
        if (!row) return;
        var pct = parseFloat(pctInput.value.replace(',', '.')) || 0;
        var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
        var partsInput = row.querySelector('.parts-cedees-input');
        if (!partsInput) return;
        if (pct > 0 && totalParts > 0) {
            partsInput.value = Math.round((pct / 100) * totalParts);
        } else {
            partsInput.value = '';
        }
        // Auto-fill prix unitaire from valeur nominale if empty
        var puInput = row.querySelector('.prix-unitaire-input');
        if (puInput && !puInput.value) {
            var vn = parseFloat(document.getElementById('total-valeur-nominale')?.value.replace(',', '.')) || 0;
            if (vn > 0) puInput.value = vn.toFixed(2).replace('.', ',');
        }
        calcPrixTotal.call(partsInput);
        calcCessionnaireFields.call(partsInput);
    }

    document.querySelectorAll('.parts-cedees-input').forEach(function(inp) {
        inp.addEventListener('input', function() { calcPrixTotal.call(this); calcPourcentage.call(this); calcCessionnaireFields.call(this); });
    });
    document.querySelectorAll('.prix-unitaire-input').forEach(function(inp) {
        inp.addEventListener('input', calcPrixTotal);
    });

    // Calculate parts from pourcentage
    document.querySelectorAll('.pourcentage-input').forEach(function(inp) {
        inp.addEventListener('input', function() { calcAllFromPct(this); });
    });

    // Add new part row
    document.getElementById('add-cession-part')?.addEventListener('click', function() {
        var container = document.getElementById('cession-parts-container');
        var index = parseInt(this.dataset.partIndex) || 0;
        var template = container.querySelector('[data-part]');
        if (!template) return;
        var clone = template.cloneNode(true);
        var suffix = '[' + index + ']';
        clone.querySelectorAll('[name]').forEach(function(el) {
            var name = el.getAttribute('name') || '';
            el.name = name.replace(/\[\d+\]/g, suffix);
            if (el.type === 'checkbox') {
                el.checked = false;
            } else if (el.tagName !== 'SELECT') {
                el.value = '';
            } else {
                el.selectedIndex = 0;
            }
        });
        clone.setAttribute('data-part', String(index));
        container.appendChild(clone);

        // Bind events
        clone.querySelectorAll('.cedant-select').forEach(function(el) {
            el.addEventListener('change', function() { syncCedant(el); });
            syncCedant(el);
        });
        clone.querySelectorAll('.parts-cedees-input').forEach(function(el) {
            el.addEventListener('input', function() { calcPrixTotal.call(this); calcPourcentage.call(this); calcCessionnaireFields.call(this); });
        });
        clone.querySelectorAll('.prix-unitaire-input').forEach(function(el) {
            el.addEventListener('input', calcPrixTotal);
        });
        clone.querySelectorAll('.pourcentage-input').forEach(function(el) {
            el.addEventListener('input', function() { calcAllFromPct(this); });
        });
        this.dataset.partIndex = index + 1;
    });

    // Remove part row
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-part');
        if (btn) {
            var row = btn.closest('[data-part]');
            if (row && confirm('Supprimer cette ligne de cession ?')) {
                row.remove();
            }
        }
    });

    // Auto-fill step 3
    document.querySelectorAll('[data-fill-cession]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var step = parseInt(btn.getAttribute('data-fill-cession') || '0', 10);
            var form = btn.closest('form');
            if (!form) return;

            if (step === 3) {
                var rows = form.querySelectorAll('#cession-parts-container [data-part]');
                rows.forEach(function(row) {
                    var idx = row.getAttribute('data-part');
                    // Pick an existing associate as cedant
                    var cedantSelect = row.querySelector('select[name="cedant_associe_id[' + idx + ']"]');
                    if (cedantSelect) {
                        var optns = Array.from(cedantSelect.options).filter(function(o) { return o.value; });
                        if (optns.length) cedantSelect.value = randFrom(optns).value;
                        syncCedant(cedantSelect);
                    }

                    // Set cessionnaire type to "nouveau" and fill fields
                    var cessCivilite = row.querySelector('select[name="cessionnaire_civilite[' + idx + ']"]');
                    if (cessCivilite) {
                        var civOpts = Array.from(cessCivilite.options).filter(function(o) { return o.value; });
                        if (civOpts.length) cessCivilite.value = randFrom(civOpts).value;
                    }
                    row.querySelector('input[name="cessionnaire_prenom[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_prenom[' + idx + ']"]').value = randFrom(prenoms));
                    row.querySelector('input[name="cessionnaire_nom[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_nom[' + idx + ']"]').value = randFrom(hommes));
                    row.querySelector('input[name="cessionnaire_cin[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_cin[' + idx + ']"]').value = 'CD' + randInt(100000, 999999));
                    row.querySelector('input[name="cessionnaire_date_naissance[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_date_naissance[' + idx + ']"]').value = randDate(new Date(1960,0,1), new Date(1995,11,31)));
                    var cessLn = row.querySelector('select[name="cessionnaire_lieu_naissance[' + idx + ']"]');
                    if (cessLn) {
                        var lnOpts = Array.from(cessLn.options).filter(function(o) { return o.value; });
                        if (lnOpts.length) cessLn.value = randFrom(lnOpts).value;
                    }
                    var cessNat = row.querySelector('select[name="cessionnaire_nationalite[' + idx + ']"]');
                    if (cessNat) {
                        var natOpts = Array.from(cessNat.options).filter(function(o) { return o.value; });
                        if (natOpts.length) cessNat.value = randFrom(natOpts).value;
                    }
                    row.querySelector('textarea[name="cessionnaire_adresse[' + idx + ']"]') && (row.querySelector('textarea[name="cessionnaire_adresse[' + idx + ']"]').value = randInt(1, 200) + ' ' + randFrom(['Avenue', 'Rue', 'Boulevard']) + ' ' + randFrom(['Liberte', 'FAR', 'Hassan II', 'Mohammed VI', 'Resistance']));
                    row.querySelector('input[name="cessionnaire_telephone[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_telephone[' + idx + ']"]').value = '06' + randInt(10000000, 99999999));
                    row.querySelector('input[name="cessionnaire_email[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_email[' + idx + ']"]').value = 'cession.' + randInt(100, 999) + '@email.ma');
                    var cessQl = row.querySelector('select[name="cessionnaire_qualite[' + idx + ']"]');
                    if (cessQl) {
                        var qOpts = Array.from(cessQl.options).filter(function(o) { return o.value; });
                        if (qOpts.length) cessQl.value = randFrom(qOpts).value;
                    }
                    row.querySelector('input[name="cessionnaire_parts[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_parts[' + idx + ']"]').value = String(randInt(100, 5000)));
                    row.querySelector('input[name="cessionnaire_capital_detenu[' + idx + ']"]') && (row.querySelector('input[name="cessionnaire_capital_detenu[' + idx + ']"]').value = String(randInt(10000, 500000)));

                    // Parts & price
                    row.querySelector('input[name="parts_cedees[' + idx + ']"]') && (row.querySelector('input[name="parts_cedees[' + idx + ']"]').value = String(randInt(50, 1000)));
                    var vnCession = parseFloat(form.getAttribute('data-valeur-nominale'));
                    row.querySelector('input[name="prix_unitaire[' + idx + ']"]') && (row.querySelector('input[name="prix_unitaire[' + idx + ']"]').value = vnCession > 0 ? String(vnCession) : String(randInt(100, 2000)));
                });
            }

            // Trigger input/change events
            form.querySelectorAll('input, select, textarea').forEach(function(f) {
                f.dispatchEvent(new Event('input', { bubbles: true }));
                f.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
})();
</script>
<?php endif; ?>
