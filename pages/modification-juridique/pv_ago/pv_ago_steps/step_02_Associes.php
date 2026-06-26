<?php
declare(strict_types=1);

if (is_post() && $step === 2) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 1]);
    }

    $associes = $_POST['associes'] ?? [];
    $normalizedAssocies = [];

    if (is_array($associes)) {
        foreach ($associes as $associe) {
            if (!is_array($associe)) continue;
            $civilite = trim((string) ($associe['civilite'] ?? ''));
            $nom = trim((string) ($associe['nom'] ?? ''));
            $prenom = trim((string) ($associe['prenom'] ?? ''));
            $nomComplet = trim((string) ($associe['nom_complet'] ?? ''));
            if ($nomComplet === '' && $nom !== '' && $prenom !== '') {
                $nomComplet = $civilite !== '' ? "$civilite $prenom $nom" : "$prenom $nom";
            }
            $item = [
                'associe_civilite' => $civilite,
                'associe_nom' => $nom,
                'associe_prenom' => $prenom,
                'associe_nom_complet' => $nomComplet,
                'associe_cin' => trim((string) ($associe['cin'] ?? '')),
                'associe_date_validite_cin' => trim((string) ($associe['date_validite_cin'] ?? '')),
                'associe_adresse' => trim((string) ($associe['adresse'] ?? '')),
                'associe_date_naissance' => trim((string) ($associe['date_naissance'] ?? '')),
                'associe_lieu_naissance' => trim((string) ($associe['lieu_naissance'] ?? '')),
                'associe_nationalite' => trim((string) ($associe['nationalite'] ?? '')),
                'associe_telephone' => trim((string) ($associe['telephone'] ?? '')),
                'associe_email' => trim((string) ($associe['email'] ?? '')),
                'associe_qualite' => trim((string) ($associe['qualite'] ?? '')),
                'associe_parts' => trim((string) ($associe['parts'] ?? '')),
                'associe_capital_detenu' => trim((string) ($associe['capital_detenu'] ?? '')),
                'associe_part_percent' => trim((string) ($associe['part_percent'] ?? '')),
                'associe_est_gerant' => ((string) ($associe['est_gerant'] ?? '0') === '1') ? '1' : '0',
            ];
            $isEmpty = $item['associe_nom_complet'] === ''
                && $item['associe_cin'] === ''
                && $item['associe_adresse'] === ''
                && $item['associe_nationalite'] === ''
                && $item['associe_parts'] === '';
            if (!$isEmpty) {
                $normalizedAssocies[] = $item;
            }
        }
    }

    $wizard['associes'] = count($normalizedAssocies) > 0 ? $normalizedAssocies : $wizard['associes'];

    if (count($normalizedAssocies) === 0) {
        set_flash('error', 'Ajoutez au moins un associe.');
        redirect_to('pv_ago', ['step' => 2]);
    }

    redirect_to('pv_ago', ['step' => 3]);
}

if ($step === 2):
    $associesData = $wizard['associes'] ?? [];
?>
<div class="stack">
    <form method="post" class="stack">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="2">

        <?php if ($selectedSociete): ?>
        <div class="soc-info">
            <div>
                <strong><?= e($selectedSociete['societe_raison_sociale']) ?></strong>
                <span class="soc-meta"><?= e($selectedSociete['societe_forme_juridique']) ?></span>
            </div>
            <a class="btn" href="<?= e(app_url('pv_ago', ['step' => 1])) ?>"><span class="material-symbols-outlined">edit</span> Modifier</a>
        </div>
        <?php endif; ?>

        <div class="section-header">
            <div>
                <h2>Associes de <?= e($selectedSociete['societe_raison_sociale'] ?? $societeData['societe_raison_sociale'] ?: 'la societe') ?></h2>
                <p class="help-text">Ajoutez autant d'associes que necessaire.</p>
            </div>
            <button class="btn" type="button" data-add-associe><span class="material-symbols-outlined">add</span> Ajouter un associé</button>
        </div>

        <div class="stack" data-associes-container>
            <?php foreach ($associesData as $index => $associe): ?>
            <div class="associe-card" data-associe-item>
                <div class="associe-card-header">
                    <strong data-associe-title>Associe <?= $index + 1 ?></strong>
                    <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                </div>
                <div class="form-grid">
                    <h3 class="section-title">Identite</h3>
                    <label class="field">
                        <span>Civilite</span>
                        <select data-field-name="civilite" name="associes[<?= $index ?>][civilite]">
                            <option value="">Selectionner</option>
                            <option value="Mr" <?= (string) ($associe['associe_civilite'] ?? '') === 'Mr' ? 'selected' : '' ?>>Mr</option>
                            <option value="Mme" <?= (string) ($associe['associe_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                            <option value="Mlle" <?= (string) ($associe['associe_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nom</span>
                        <input data-field-name="nom" name="associes[<?= $index ?>][nom]" value="<?= e((string) ($associe['associe_nom'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Prenom</span>
                        <input data-field-name="prenom" name="associes[<?= $index ?>][prenom]" value="<?= e((string) ($associe['associe_prenom'] ?? '')) ?>">
                    </label>
                    <input type="hidden" data-field-name="nom_complet" name="associes[<?= $index ?>][nom_complet]" value="<?= e((string) ($associe['associe_nom_complet'] ?? '')) ?>">
                    <label class="field">
                        <span>N CIN/Sejour/Passport</span>
                        <input data-field-name="cin" name="associes[<?= $index ?>][cin]" value="<?= e((string) ($associe['associe_cin'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Date validite CIN</span>
                        <input data-field-name="date_validite_cin" type="date" name="associes[<?= $index ?>][date_validite_cin]" value="<?= e((string) ($associe['associe_date_validite_cin'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Nationalite</span>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select data-field-name="nationalite" name="associes[<?= $index ?>][nationalite]" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($nationalitesOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= (string) ($associe['associe_nationalite'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= e(app_url('configuration', ['tab' => 'nationalites'])) ?>" target="_blank" title="Gerer les nationalites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                        </div>
                    </label>
                    <label class="field">
                        <span>Date naissance</span>
                        <input data-field-name="date_naissance" type="date" name="associes[<?= $index ?>][date_naissance]" value="<?= e((string) ($associe['associe_date_naissance'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Lieu naissance</span>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select data-field-name="lieu_naissance" name="associes[<?= $index ?>][lieu_naissance]" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($lieuxNaissanceOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= (string) ($associe['associe_lieu_naissance'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= e(app_url('configuration', ['tab' => 'lieux-naissance'])) ?>" target="_blank" title="Gerer les lieux de naissance" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                        </div>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Telephone</span>
                        <input data-field-name="telephone" name="associes[<?= $index ?>][telephone]" value="<?= e((string) ($associe['associe_telephone'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input data-field-name="email" type="email" name="associes[<?= $index ?>][email]" value="<?= e((string) ($associe['associe_email'] ?? '')) ?>">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea data-field-name="adresse" name="associes[<?= $index ?>][adresse]"><?= e((string) ($associe['associe_adresse'] ?? '')) ?></textarea>
                    </label>
                    <h3 class="section-title">Participation</h3>
                    <label class="field">
                        <span>Qualite associe</span>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select data-field-name="qualite" name="associes[<?= $index ?>][qualite]" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($qualitesAssocieOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= (string) ($associe['associe_qualite'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= e(app_url('configuration', ['tab' => 'qualites-associe'])) ?>" target="_blank" title="Gerer les qualites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                        </div>
                    </label>
                    <label class="field">
                        <span>Parts</span>
                        <input data-field-name="parts" type="number" name="associes[<?= $index ?>][parts]" value="<?= e((string) ($associe['associe_parts'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Capital detenu (DH)</span>
                        <input data-field-name="capital_detenu" type="number" step="0.01" name="associes[<?= $index ?>][capital_detenu]" value="<?= e((string) ($associe['associe_capital_detenu'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>% Capital social</span>
                        <input data-field-name="part_percent" type="number" step="1" min="0" max="100" name="associes[<?= $index ?>][part_percent]" value="<?= e((string) ($associe['associe_part_percent'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select data-field-name="est_gerant" name="associes[<?= $index ?>][est_gerant]">
                            <option value="0" <?= (string) ($associe['associe_est_gerant'] ?? '0') === '0' ? 'selected' : '' ?>>Non</option>
                            <option value="1" <?= (string) ($associe['associe_est_gerant'] ?? '0') === '1' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <template data-associe-template>
            <div class="associe-card" data-associe-item>
                <div class="associe-card-header">
                    <strong data-associe-title>Associe</strong>
                    <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                </div>
                <div class="form-grid">
                    <h3 class="section-title">Identite</h3>
                    <label class="field">
                        <span>Civilite</span>
                        <select data-field-name="civilite">
                            <option value="">Selectionner</option>
                            <option value="Mr">Mr</option>
                            <option value="Mme">Mme</option>
                            <option value="Mlle">Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nom</span>
                        <input data-field-name="nom" value="">
                    </label>
                    <label class="field">
                        <span>Prenom</span>
                        <input data-field-name="prenom" value="">
                    </label>
                    <input type="hidden" data-field-name="nom_complet" value="">
                    <label class="field">
                        <span>N CIN/Sejour/Passport</span>
                        <input data-field-name="cin" value="">
                    </label>
                    <label class="field">
                        <span>Date validite CIN</span>
                        <input data-field-name="date_validite_cin" type="date" value="">
                    </label>
                    <label class="field">
                        <span>Nationalite</span>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select data-field-name="nationalite" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($nationalitesOptions as $option): ?>
                                    <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= e(app_url('configuration', ['tab' => 'nationalites'])) ?>" target="_blank" title="Gerer les nationalites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                        </div>
                    </label>
                    <label class="field">
                        <span>Date naissance</span>
                        <input data-field-name="date_naissance" type="date" value="">
                    </label>
                    <label class="field">
                        <span>Lieu naissance</span>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select data-field-name="lieu_naissance" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($lieuxNaissanceOptions as $option): ?>
                                    <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= e(app_url('configuration', ['tab' => 'lieux-naissance'])) ?>" target="_blank" title="Gerer les lieux de naissance" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                        </div>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Telephone</span>
                        <input data-field-name="telephone" value="">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input data-field-name="email" type="email" value="">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea data-field-name="adresse"></textarea>
                    </label>
                    <h3 class="section-title">Participation</h3>
                    <label class="field">
                        <span>Qualite associe</span>
                        <div style="display:flex;gap:8px;align-items:center">
                            <select data-field-name="qualite" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($qualitesAssocieOptions as $option): ?>
                                    <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= e(app_url('configuration', ['tab' => 'qualites-associe'])) ?>" target="_blank" title="Gerer les qualites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                        </div>
                    </label>
                    <label class="field">
                        <span>Parts</span>
                        <input data-field-name="parts" type="number" value="">
                    </label>
                    <label class="field">
                        <span>Capital detenu (DH)</span>
                        <input data-field-name="capital_detenu" type="number" step="0.01" value="">
                    </label>
                    <label class="field">
                        <span>% Capital social</span>
                        <input data-field-name="part_percent" type="number" step="1" min="0" max="100" value="">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select data-field-name="est_gerant">
                            <option value="0" selected>Non</option>
                            <option value="1">Oui</option>
                        </select>
                    </label>
                </div>
            </div>
        </template>

        <div class="table-actions">
            <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </form>
</div>
<?php endif; ?>
