<?php

declare(strict_types=1);

if (is_post() && $step === 2) {
    $navAction = $_POST['nav_action'] ?? 'next';

    $associes = $_POST['associes'] ?? [];
    $normalizedAssocies = [];

    if (is_array($associes)) {
        foreach ($associes as $associe) {
            if (!is_array($associe)) {
                continue;
            }

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
                'associe_duree_gerance' => trim((string) ($associe['duree_gerance'] ?? '')),
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

    if ($navAction === 'ai_fill') {
        if (ClaudeService::isAvailable()) {
            $suggestions = ClaudeService::autoFill(current($normalizedAssocies) ?: []);
            $_SESSION['creation_wizard']['ai_suggestions'] = ['step2' => $suggestions];
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible. Configurez la cle API dans le fichier .env.");
        }
        redirect_to('creation', ['step' => 2]);
    }

    if ($navAction === 'back') {
        redirect_to('creation', ['step' => 1]);
    }

    if (count($normalizedAssocies) === 0) {
        set_flash('error', 'Ajoutez au moins un associe.');
        redirect_to('creation', ['step' => 2]);
    }

    $formeJuridique = $wizard['societe']['societe_forme_juridique'] ?? '';
    if ($formeJuridique === 'SARL' && count($normalizedAssocies) < 2) {
        $_SESSION['_sarl_modal'] = true;
        redirect_to('creation', ['step' => 2]);
    }

    redirect_to('creation', ['step' => 3]);
}

if ($step === 2):
?>
<form method="post" class="stack">
    <?= csrf_input() ?>
    <input type="hidden" name="step" value="2">
    <input type="hidden" id="societe-capital" value="<?= e((string) ($societeData['societe_capital'] ?? '')) ?>">
    <input type="hidden" id="societe-part-social" value="<?= e((string) ($societeData['societe_part_social'] ?? '')) ?>">
    <input type="hidden" name="forme_juridique" value="<?= e((string) ($societeData['societe_forme_juridique'] ?? '')) ?>">
    <?php if ($aiSuggestions && isset($aiSuggestions['step2'])): ?>
    <div class="flash flash-info" style="margin-bottom:8px">
        <span class="material-symbols-outlined">smart_toy</span>
        Suggestions IA disponibles. <button type="button" class="btn btn-info" style="padding:2px 10px;font-size:0.8rem" data-apply-ai-fill="<?= e(json_encode($aiSuggestions['step2'], JSON_UNESCAPED_UNICODE)) ?>"><span class="material-symbols-outlined">auto_fix</span> Appliquer les suggestions</button>
    </div>
    <?php endif; ?>
    <div class="section-header">
        <div>
            <h2>Associes de <?= e((string) ($societeData['societe_raison_sociale'] ?: 'la societe')) ?></h2>
            <p class="help-text">Ajoutez autant d'associes que necessaire.</p>
        </div>
        <div class="table-actions">
            <button class="btn" type="button" data-add-associe><span class="material-symbols-outlined">add</span> Ajouter un associé</button>
        </div>
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
                        <input data-field-name="date_validite_cin" type="date" name="associes[<?= $index ?>][date_validite_cin]" placeholder="18/05/2026" value="<?= e((string) ($associe['associe_date_validite_cin'] ?? '')) ?>">
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
                            <button type="button" class="btn-icon" data-quick-create-btn="nationalites" title="Ajouter une nationalite"><span class="material-symbols-outlined">add</span></button>
                        </div>
                    </label>
                    <label class="field">
                        <span>Date naissance</span>
                        <input data-field-name="date_naissance" type="date" name="associes[<?= $index ?>][date_naissance]" placeholder="18/05/2026" value="<?= e((string) ($associe['associe_date_naissance'] ?? '')) ?>">
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
                            <button type="button" class="btn-icon" data-quick-create-btn="lieux-naissance" title="Ajouter un lieu de naissance"><span class="material-symbols-outlined">add</span></button>
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
                            <button type="button" class="btn-icon" data-quick-create-btn="qualites-associe" title="Ajouter une qualite"><span class="material-symbols-outlined">add</span></button>
                        </div>
                    </label>
                    <label class="field" data-capital-field>
                        <span>Parts</span>
                        <input data-field-name="parts" type="number" name="associes[<?= $index ?>][parts]" value="<?= e((string) ($associe['associe_parts'] ?? '')) ?>">
                    </label>
                    <label class="field" data-capital-field>
                        <span>Capital detenu (DH)</span>
                        <input data-field-name="capital_detenu" type="number" step="0.01" name="associes[<?= $index ?>][capital_detenu]" data-capital-input value="<?= e((string) ($associe['associe_capital_detenu'] ?? '')) ?>">
                    </label>
                    <label class="field" data-capital-field>
                        <span>% Capital social</span>
                        <input data-field-name="part_percent" type="number" step="1" min="0" max="100" name="associes[<?= $index ?>][part_percent]" data-percent-input value="<?= e((string) ($associe['associe_part_percent'] ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select data-field-name="est_gerant" name="associes[<?= $index ?>][est_gerant]" data-gerant-toggle>
                            <option value="0" <?= (string) ($associe['associe_est_gerant'] ?? '0') === '0' ? 'selected' : '' ?>>Non</option>
                            <option value="1" <?= (string) ($associe['associe_est_gerant'] ?? '0') === '1' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </label>
                    <label class="field" data-duree-gerant-field <?= (string) ($associe['associe_est_gerant'] ?? '0') === '1' ? '' : 'style="display:none"' ?>>
                        <span>Duree de gerance</span>
                        <select data-field-name="duree_gerance" name="associes[<?= $index ?>][duree_gerance]">
                            <option value="Indeterminee" <?= ($associe['associe_duree_gerance'] ?? '') === 'Indeterminee' ? 'selected' : '' ?>>Indeterminee</option>
                            <option value="1 an" <?= ($associe['associe_duree_gerance'] ?? '') === '1 an' ? 'selected' : '' ?>>1 an</option>
                            <option value="2 ans" <?= ($associe['associe_duree_gerance'] ?? '') === '2 ans' ? 'selected' : '' ?>>2 ans</option>
                            <option value="3 ans" <?= ($associe['associe_duree_gerance'] ?? '') === '3 ans' ? 'selected' : '' ?>>3 ans</option>
                            <option value="5 ans" <?= ($associe['associe_duree_gerance'] ?? '') === '5 ans' ? 'selected' : '' ?>>5 ans</option>
                            <option value="Autre" <?= ($associe['associe_duree_gerance'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card" data-associe-summary style="display:none">
        <div class="section-header">
            <div>
                <h3>Repartition du capital</h3>
            </div>
        </div>
        <div class="form-grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="field">
                <span>Capital societe</span>
                <strong id="ref-capital" style="font-size:1.2rem">0,00 DH</strong>
            </div>
            <div class="field">
                <span>Part social societe</span>
                <strong id="ref-parts" style="font-size:1.2rem">0</strong>
            </div>
            <div></div>
            <div></div>
        </div>
        <div class="form-grid" style="grid-template-columns:repeat(4,1fr);margin-top:12px">
            <div class="field">
                <span>Total capital distribue</span>
                <strong id="total-capital" style="font-size:1.2rem">0,00 DH</strong>
            </div>
            <div class="field">
                <span>Total parts distribuees</span>
                <strong id="total-parts" style="font-size:1.2rem">0</strong>
            </div>
            <div class="field">
                <span>Total %</span>
                <strong id="total-percent" style="font-size:1.2rem">0,00 %</strong>
            </div>
            <div class="field">
                <span>Statut</span>
                <strong id="capital-status" style="font-size:1rem;color:var(--danger)">Incomplet</strong>
            </div>
        </div>
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
                    <input data-field-name="date_validite_cin" type="date" placeholder="18/05/2026" value="">
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
                        <button type="button" class="btn-icon" data-quick-create-btn="nationalites" title="Ajouter une nationalite"><span class="material-symbols-outlined">add</span></button>
                    </div>
                </label>
                <label class="field">
                    <span>Date naissance</span>
                    <input data-field-name="date_naissance" type="date" placeholder="18/05/2026" value="">
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
                        <button type="button" class="btn-icon" data-quick-create-btn="lieux-naissance" title="Ajouter un lieu de naissance"><span class="material-symbols-outlined">add</span></button>
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
                        <button type="button" class="btn-icon" data-quick-create-btn="qualites-associe" title="Ajouter une qualite"><span class="material-symbols-outlined">add</span></button>
                    </div>
                </label>
                <label class="field" data-capital-field>
                    <span>Parts</span>
                    <input data-field-name="parts" type="number" value="">
                </label>
                <label class="field" data-capital-field>
                    <span>Capital detenu (DH)</span>
                    <input data-field-name="capital_detenu" type="number" step="0.01" data-capital-input value="">
                </label>
                <label class="field" data-capital-field>
                    <span>% Capital social</span>
                    <input data-field-name="part_percent" type="number" step="1" min="0" max="100" data-percent-input value="">
                </label>
                <label class="field">
                    <span>Gerant</span>
                    <select data-field-name="est_gerant" data-gerant-toggle>
                        <option value="0" selected>Non</option>
                        <option value="1">Oui</option>
                    </select>
                </label>
                <label class="field" data-duree-gerant-field style="display:none">
                    <span>Duree de gerance</span>
                    <select data-field-name="duree_gerance">
                        <option value="Indeterminee">Indeterminee</option>
                        <option value="1 an">1 an</option>
                        <option value="2 ans">2 ans</option>
                        <option value="3 ans">3 ans</option>
                        <option value="5 ans">5 ans</option>
                        <option value="Autre">Autre</option>
                    </select>
                </label>
            </div>
        </div>
    </template>

    <div class="table-actions">
        <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
        <button class="btn btn-info" type="button" data-fill-test><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        <button class="btn btn-info" type="submit" name="nav_action" value="ai_fill"><span class="material-symbols-outlined">smart_toy</span> Remplir avec IA</button>
        <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>

<div class="dl-modal-overlay" id="sarl-modal">
    <div class="dl-modal-card">
        <div class="dl-modal-header">
            <span class="material-symbols-outlined">info</span>
            Forme juridique SARL
            <button type="button" class="dl-modal-close" id="sarl-modal-close">&times;</button>
        </div>
        <div class="dl-modal-body">
            <p style="margin:0;line-height:1.5">Pour une <strong>SARL</strong>, vous devez ajouter au moins <strong>deux associés</strong>.</p>
            <p style="margin:0;line-height:1.5">Ajoutez un autre associé ou <a href="<?= e(app_url('creation', ['step' => 1])) ?>" style="color:var(--primary);text-decoration:underline">changez la forme juridique</a> à l'étape 1.</p>
        </div>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('sarl-modal');
    var closeBtn = document.getElementById('sarl-modal-close');
    <?php if (!empty($_SESSION['_sarl_modal'])): unset($_SESSION['_sarl_modal']); ?>
    modal.classList.add('show');
    <?php endif; ?>
    closeBtn.addEventListener('click', function() { modal.classList.remove('show'); });
    modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.classList.remove('show');
    });
})();
</script>
<script>
(function(){
    document.querySelectorAll('[data-gerant-toggle]').forEach(function(sel){
        function toggle(){
            var row = sel.closest('[data-associe-row]') || sel.closest('.associe-row') || sel.closest('div');
            if (!row) return;
            var field = row.querySelector('[data-duree-gerant-field]');
            if (field) field.style.display = sel.value === '1' ? '' : 'none';
        }
        sel.addEventListener('change', toggle);
        toggle();
    });
})();
</script>

<?php
$quickCreateModalKey = 'nationalites';
$quickCreateTitle = 'Nouvelle nationalite';
$quickCreateTable = 'ref_nationalites';
$quickCreateFields = [
    ['name' => 'nationalite', 'label' => 'Nationalite', 'type' => 'text', 'required' => true],
];
require __DIR__ . '/../../../includes/quick_create_modal.php';

$quickCreateModalKey = 'lieux-naissance';
$quickCreateTitle = 'Nouveau lieu de naissance';
$quickCreateTable = 'ref_lieux_naissance';
$quickCreateFields = [
    ['name' => 'lieu_naissance', 'label' => 'Lieu de naissance', 'type' => 'text', 'required' => true],
];
require __DIR__ . '/../../../includes/quick_create_modal.php';

$quickCreateModalKey = 'qualites-associe';
$quickCreateTitle = 'Nouvelle qualite';
$quickCreateTable = 'ref_qualites_associe';
$quickCreateFields = [
    ['name' => 'qualite_associe', 'label' => 'Qualite associe', 'type' => 'text', 'required' => true],
];
require __DIR__ . '/../../../includes/quick_create_modal.php';
?>
<?php endif; ?>
