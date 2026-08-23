<?php

declare(strict_types=1);

if (is_post() && $step === 1) {
    $navAction = $_POST['nav_action'] ?? 'next';

    // Verrou serveur : le type impose par la liste d'appel ne peut pas etre contourné
    if ($typeLocked && (string) ($wizard['societe']['societe_type_generation'] ?? '') !== '') {
        $_POST['societe_type_generation'] = (string) $wizard['societe']['societe_type_generation'];
    }

    // Defaults mode creation : procedure normale + depot physique si non renseignes
    if (field_value($_POST, 'societe_type_generation') === 'creation') {
        if (field_value($_POST, 'societe_procedure_creation') === '') {
            $_POST['societe_procedure_creation'] = 'normal';
        }
        if (field_value($_POST, 'societe_mode_depot') === '') {
            $_POST['societe_mode_depot'] = 'depot_physique';
        }
    }

    $activitesStatuts = $_POST['societe_activites_statuts'] ?? [];
    $allStatuts = is_array($activitesStatuts) ? array_map('trim', $activitesStatuts) : [];
    $allStatuts = array_unique(array_filter($allStatuts));

    $activitesOmpic = field_value($_POST, 'societe_activites_ompic');

    $societe = [
        'societe_dossier_domiciliation_number' => field_value($_POST, 'societe_dossier_domiciliation_number'),
        'societe_dossier_creation_number' => (field_value($_POST, 'societe_type_generation') === 'creation') ? field_value($_POST, 'societe_dossier_creation_number') : '',
        'societe_raison_sociale' => field_value($_POST, 'societe_raison_sociale'),
        'societe_forme_juridique' => field_value($_POST, 'societe_forme_juridique'),
        'societe_ice' => field_value($_POST, 'societe_ice'),
        'societe_date_ice' => date_value($_POST, 'societe_date_ice'),
        'societe_rc' => field_value($_POST, 'societe_rc'),
        'societe_if' => field_value($_POST, 'societe_if'),
        'societe_activites_statuts' => implode(', ', $allStatuts),
        'societe_activites_ompic' => $activitesOmpic,
        'societe_part_social' => field_value($_POST, 'societe_part_social'),
        'societe_valeur_nominale' => field_value($_POST, 'societe_valeur_nominale'),
        'societe_date_exp_cert_neg' => date_value($_POST, 'societe_date_exp_cert_neg'),
        'societe_adresse_siege' => field_value($_POST, 'societe_adresse_siege'),
        'societe_ville' => field_value($_POST, 'societe_ville'),
        'societe_tribunal' => field_value($_POST, 'societe_tribunal'),
        'societe_email' => field_value($_POST, 'societe_email'),
        'societe_telephone' => field_value($_POST, 'societe_telephone'),
        'societe_capital' => field_value($_POST, 'societe_capital'),
        'societe_type_generation' => field_value($_POST, 'societe_type_generation'),
        'societe_procedure_creation' => field_value($_POST, 'societe_procedure_creation'),
        'societe_mode_depot' => field_value($_POST, 'societe_mode_depot'),
        'societe_tribunal_type' => field_value($_POST, 'tribunal_type'),
    ];

    $wizard['societe'] = $societe;
    if ($navAction === 'ai_fill') {
        if (ClaudeService::isAvailable()) {
            $suggestions = ClaudeService::autoFill($societe);
            $_SESSION['creation_wizard']['ai_suggestions'] = ['step1' => $suggestions];
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible. Configurez la cle API dans le fichier .env.");
        }
        redirect_to('creation', ['step' => 1]);
    }

    if ($societe['societe_raison_sociale'] === '') {
        set_flash('error', 'La raison sociale est obligatoire.');
        redirect_to('creation', ['step' => 1]);
    }

    redirect_to('creation', ['step' => 2]);
}

if ($step === 1):
?>
<form method="post" class="stack" id="wizard-step1">
    <?= csrf_input() ?>
    <input type="hidden" name="step" value="1">
    <?php if ($aiSuggestions && isset($aiSuggestions['step1'])): ?>
    <div class="flash flash-info" style="margin-bottom:8px">
        <span class="material-symbols-outlined">smart_toy</span>
        Suggestions IA disponibles. <button type="button" class="btn btn-info" style="padding:2px 10px;font-size:0.8rem" data-apply-ai-fill="<?= e(json_encode($aiSuggestions['step1'], JSON_UNESCAPED_UNICODE)) ?>"><span class="material-symbols-outlined">auto_fix</span> Appliquer les suggestions</button>
    </div>
    <?php endif; ?>
    <div class="form-grid">
        <h3 class="section-title">Procedure</h3>
        <label class="field">
            <span>Type generation<?= $typeLocked ? ' (imposé)' : '' ?></span>
            <?php if ($typeLocked): ?>
            <input type="hidden" name="societe_type_generation" value="<?= e((string) $societeData['societe_type_generation']) ?>">
            <?php endif; ?>
            <select name="societe_type_generation" data-type-gen<?= $typeLocked ? ' disabled title="Type impose par la liste d\'appel"' : '' ?>>
                <option value="">Selectionner</option>
                <option value="creation" <?= (string) $societeData['societe_type_generation'] === 'creation' ? 'selected' : '' ?>>Création</option>
                <option value="domiciliation" <?= (string) $societeData['societe_type_generation'] === 'domiciliation' ? 'selected' : '' ?>>Domiciliation</option>
            </select>
        </label>
        <label class="field" data-depends-type-gen style="<?= (string) $societeData['societe_type_generation'] !== 'creation' ? 'display:none' : '' ?>">
            <span>Procedure creation</span>
            <select name="societe_procedure_creation">
                <option value="">Selectionner</option>
                <option value="normal" <?= (string) $societeData['societe_procedure_creation'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                <option value="acceleree" <?= (string) $societeData['societe_procedure_creation'] === 'acceleree' ? 'selected' : '' ?>>Accélérer</option>
            </select>
        </label>
        <label class="field" data-depends-type-gen style="<?= (string) $societeData['societe_type_generation'] !== 'creation' ? 'display:none' : '' ?>">
            <span>Mode depot creation</span>
            <select name="societe_mode_depot">
                <option value="">Selectionner</option>
                <option value="depot_physique" <?= (string) $societeData['societe_mode_depot'] === 'depot_physique' ? 'selected' : '' ?>>Dépôt Physique</option>
                <option value="depot_en_ligne" <?= (string) $societeData['societe_mode_depot'] === 'depot_en_ligne' ? 'selected' : '' ?>>Dépôt En Ligne</option>
            </select>
        </label>

        <h3 class="section-title">Identifiants</h3>
        <label class="field">
            <span>Dossier domiciliation</span>
            <input name="societe_dossier_domiciliation_number" value="<?= e((string) $societeData['societe_dossier_domiciliation_number']) ?>">
        </label>
        <label class="field" data-depends-type-gen style="<?= (string) $societeData['societe_type_generation'] !== 'creation' ? 'display:none' : '' ?>">
            <span>Dossier creation</span>
            <input name="societe_dossier_creation_number" value="<?= e((string) $societeData['societe_dossier_creation_number']) ?>">
        </label>
        <label class="field">
            <span>Raison sociale</span>
            <input name="societe_raison_sociale" required value="<?= e((string) $societeData['societe_raison_sociale']) ?>">
        </label>
        <label class="field">
            <span>Forme juridique</span>
            <div style="display:flex;gap:8px;align-items:center">
                <select name="societe_forme_juridique" style="flex:1">
                    <option value="">Selectionner</option>
                    <?php foreach ($formesJuridiquesOptions as $option): ?>
                        <option value="<?= e($option) ?>" <?= (string) $societeData['societe_forme_juridique'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-icon" data-quick-create-btn="formes-juridiques" title="Ajouter une forme juridique"><span class="material-symbols-outlined">add</span></button>
            </div>
        </label>
        <label class="field">
            <span>ICE</span>
            <input name="societe_ice" value="<?= e((string) $societeData['societe_ice']) ?>">
        </label>
        <label class="field">
            <span>Date de cert. negatif</span>
            <input type="date" name="societe_date_ice" placeholder="18/05/2026" value="<?= e((string) $societeData['societe_date_ice']) ?>">
        </label>
        <label class="field">
            <span>Date exp. cert. negatif</span>
            <input type="date" name="societe_date_exp_cert_neg" placeholder="18/05/2026" value="<?= e((string) $societeData['societe_date_exp_cert_neg']) ?>">
        </label>
        <label class="field" style="display:none">
            <span>RC</span>
            <input name="societe_rc" value="<?= e((string) $societeData['societe_rc']) ?>">
        </label>
        <label class="field" style="display:none">
            <span>IF</span>
            <input name="societe_if" value="<?= e((string) $societeData['societe_if']) ?>">
        </label>

        <h3 class="section-title">Activite (Certificat negatif)</h3>
        <label class="field full">
            <span>Activite pour le certificat negatif</span>
            <div style="display:flex;gap:8px;align-items:center">
                <select name="societe_activites_ompic" style="flex:1" data-ompic-select>
                    <option value="">Selectionner</option>
                    <?php foreach ($ompicOptions as $row): ?>
                        <option value="<?= e($row['code']) ?>" <?= ((string) $societeData['societe_activites_ompic']) === $row['code'] ? 'selected' : '' ?>><?= e($row['code'] . ' - ' . $row['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-info" data-add-activite-cn style="white-space:nowrap"><span class="material-symbols-outlined">add_circle</span> Nouvelle activite</button>
            </div>
        </label>

        <div data-statuts-section style="grid-column:1/-1">
        <h3 class="section-title">Activites (Statuts)</h3>
        <label class="field full">
            <span>Activites pour les statuts</span>
            <div data-activites-group="statuts">
                <div data-activites-container>
                    <?php
                    $wizStatuts = !empty($societeData['societe_activites_statuts']) ? array_map('trim', explode(',', (string) $societeData['societe_activites_statuts'])) : [];
                    if ($wizStatuts):
                        foreach ($wizStatuts as $act):
                    ?>
                        <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                            <select name="societe_activites_statuts[]" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($activitesOptions as $opt): ?>
                                    <option value="<?= e($opt) ?>" <?= $act === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                <?php endforeach; ?>
                                <?php if (!in_array($act, $activitesOptions)): ?>
                                    <option value="<?= e($act) ?>" selected><?= e($act) ?></option>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button>
                        </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                            <select name="societe_activites_statuts[]" style="flex:1">
                                <option value="">Selectionner</option>
                                <?php foreach ($activitesOptions as $opt): ?>
                                    <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                    <button type="button" class="btn" data-add-activite><span class="material-symbols-outlined">add</span> Ajouter une activite</button>
                    <button type="button" class="btn btn-info" data-add-activite-ref><span class="material-symbols-outlined">add_circle</span> Nouvelle activite</button>
                    <button type="button" class="btn btn-secondary" data-add-activites-multiple><span class="material-symbols-outlined">add_box</span> Ajouter plusieurs</button>
                </div>
                <template data-activite-template>
                    <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                        <select name="societe_activites_statuts[]" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($activitesOptions as $opt): ?>
                                <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button>
                    </div>
                </template>
            </div>
        </label>
        </div>
        <h3 class="section-title">Capital</h3>
        <label class="field">
            <span>Capital</span>
            <input type="number" step="0.01" name="societe_capital" value="<?= e((string) $societeData['societe_capital']) ?>">
        </label>
        <label class="field">
            <span>Part social</span>
            <input type="number" name="societe_part_social" value="<?= e((string) $societeData['societe_part_social']) ?>">
        </label>
        <label class="field">
            <span>Valeur nominale</span>
            <input type="number" step="0.01" name="societe_valeur_nominale" value="<?= e((string) $societeData['societe_valeur_nominale']) ?>">
        </label>

        <h3 class="section-title">Adresse</h3>
        <label class="field full">
            <span>Adresse de reference</span>
            <div style="display:flex;gap:8px;align-items:center">
                <select name="societe_adresse_siege" style="flex:1">
                    <option value="">Selectionner</option>
                    <?php foreach ($adressesAll as $opt): ?>
                        <option value="<?= e($opt['ste_adresse']) ?>" data-ville="<?= e($opt['ville'] ?? '') ?>" <?= (string) $societeData['societe_adresse_siege'] === $opt['ste_adresse'] ? 'selected' : '' ?>><?= e($opt['ste_adresse']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-icon" data-quick-create-btn="adresses" title="Ajouter une adresse"><span class="material-symbols-outlined">add</span></button>
            </div>
        </label>
        <label class="field">
            <span>Ville</span>
            <div style="display:flex;gap:8px;align-items:center">
<select name="societe_ville" data-ville-filter style="flex:1">
                    <option value="">Selectionner</option>
                    <?php foreach ($villesOptions as $option): ?>
                        <option value="<?= e($option) ?>" <?= $societeData['societe_ville'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-icon" data-quick-create-btn="villes" title="Ajouter une ville"><span class="material-symbols-outlined">add</span></button>
            </div>
        </label>
        <label class="field">
            <span>Type de tribunal</span>
            <select name="tribunal_type" data-tribunal-type>
                <option value="">Selectionner</option>
                <?php foreach ($tribunalTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= $currentTribunalType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Tribunal</span>
            <select name="societe_tribunal">
                <option value="">Selectionner</option>
                <?php foreach ($allTribunaux as $t): ?>
                    <option value="<?= e($t['tribunal']) ?>" data-type="<?= e($t['tribunal_type'] ?? '') ?>" <?= $defaultTribunal === $t['tribunal'] && $currentTribunalType === ($t['tribunal_type'] ?? '') ? 'selected' : '' ?>><?= e($t['tribunal']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <h3 class="section-title">Contact</h3>
        <label class="field">
            <span>Email</span>
            <input type="email" name="societe_email" value="<?= e((string) $societeData['societe_email']) ?>">
        </label>
        <label class="field">
            <span>Telephone</span>
            <input name="societe_telephone" value="<?= e((string) $societeData['societe_telephone']) ?>">
        </label>
    </div>
    <div class="table-actions">
        <button class="btn btn-info" type="button" data-fill-test><span class="material-symbols-outlined">auto_fix</span> Remplir automatiquement</button>
        <button class="btn btn-info" type="submit" name="nav_action" value="ai_fill" form="wizard-step1"><span class="material-symbols-outlined">smart_toy</span> Remplir avec IA</button>
        <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
    </div>
</form>

<?php
$quickCreateModalKey = 'formes-juridiques';
$quickCreateTitle = 'Nouvelle forme juridique';
$quickCreateTable = 'ref_formes_juridiques';
$quickCreateFields = [
    ['name' => 'forme_juridique', 'label' => 'Forme juridique', 'type' => 'text', 'required' => true],
    ['name' => 'template_folder', 'label' => 'Dossier template', 'type' => 'text', 'placeholder' => 'Optionnel'],
];
require __DIR__ . '/../../../includes/quick_create_modal.php';

$quickCreateModalKey = 'adresses';
$quickCreateTitle = 'Nouvelle adresse';
$quickCreateTable = 'ref_ste_adresses';
$quickCreateFields = [
    ['name' => 'adresse', 'label' => 'Adresse', 'type' => 'text', 'required' => true],
];
require __DIR__ . '/../../../includes/quick_create_modal.php';

$quickCreateModalKey = 'villes';
$quickCreateTitle = 'Nouvelle ville';
$quickCreateTable = 'ref_villes';
$quickCreateFields = [
    ['name' => 'ville', 'label' => 'Ville', 'type' => 'text', 'required' => true],
];
require __DIR__ . '/../../../includes/quick_create_modal.php';
?>
<?php endif; ?>
