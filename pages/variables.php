<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesDir = __DIR__ . '/../templates';
$templates = TemplateAnalyzer::scanTemplates($templatesDir);
$contextKeys = TemplateAnalyzer::getExpectedContextKeys();
$contextKeySet = array_flip(array_map('strtoupper', $contextKeys));

$allVariables = [];
$variableTemplates = [];
$variableOccurrences = [];

foreach ($templates as $tpl) {
    $vars = $tpl['variables'] ?? [];
    foreach ($vars as $var) {
        $upper = strtoupper($var);
        $allVariables[$upper] = $var;
        $variableOccurrences[$upper] = ($variableOccurrences[$upper] ?? 0) + 1;
        if (!isset($variableTemplates[$upper])) {
            $variableTemplates[$upper] = [];
        }
        $variableTemplates[$upper][] = $tpl['path'];
    }
}

ksort($allVariables);

$mappedCount = 0;
$unmappedCount = 0;
foreach ($allVariables as $upper => $original) {
    if (isset($contextKeySet[$upper])) {
        $mappedCount++;
    } else {
        $unmappedCount++;
    }
}
$totalCount = count($allVariables);

if (is_post() && isset($_POST['apply_mapping'])) {
    verify_csrf();
    $selected = $_POST['selected_vars'] ?? [];
    $targets = $_POST['target_names'] ?? [];
    $totalRenamed = 0;
    $errors = [];

    foreach ($selected as $oldUpper) {
        $oldUpper = strtoupper(trim($oldUpper));
        $newName = trim($targets[$oldUpper] ?? '');
        if ($oldUpper === '' || $newName === '' || $oldUpper === strtoupper($newName)) {
            continue;
        }
        $result = TemplateAnalyzer::renameVariable($oldUpper, $newName, $templatesDir);
        $totalRenamed += $result['modified'];
        if (!empty($result['errors'])) {
            $errors = array_merge($errors, $result['errors']);
        }
    }

    $count = count($selected);
    $msg = "{$count} variable(s) traitee(s) dans {$totalRenamed} template(s).";
    if (!empty($errors)) {
        $msg .= ' Erreurs: ' . implode('; ', array_unique($errors));
    }
    set_flash('success', $msg);
    redirect_to('variables');
}

$filter = $_GET['filter'] ?? 'all';

// Build form variables data (variables from the application forms, not templates)
$formFieldLabels = [
    'SOCIETE_RAISON_SOCIALE' => 'societe_raison_sociale',
    'SOCIETE_FORME_JURIDIQUE' => 'societe_forme_juridique',
    'SOCIETE_ICE' => 'societe_ice',
    'SOCIETE_RC' => 'societe_rc',
    'SOCIETE_IF' => 'societe_if',
    'SOCIETE_CAPITAL' => 'societe_capital',
    'SOCIETE_PART_SOCIAL' => 'societe_part_social',
    'SOCIETE_VALEUR_NOMINALE' => 'societe_valeur_nominale',
    'SOCIETE_VILLE' => 'societe_ville',
    'SOCIETE_TRIBUNAL' => 'societe_tribunal',
    'SOCIETE_TRIBUNAL_TYPE' => 'societe_tribunal_type',
    'SOCIETE_ADRESSE_SIEGE' => 'societe_adresse_siege',
    'SOCIETE_EMAIL' => 'societe_email',
    'SOCIETE_TELEPHONE' => 'societe_telephone',
    'SOCIETE_DOSSIER' => 'societe_dossier',
    'SOCIETE_TYPE_GENERATION' => 'societe_type_generation',
    'SOCIETE_PROCEDURE_CREATION' => 'societe_procedure_creation',
    'SOCIETE_MODE_DEPOT' => 'societe_mode_depot',
    'SOCIETE_DATE_ICE' => 'societe_date_ice',
    'SOCIETE_DATE_EXP_CERT_NEG' => 'societe_date_exp_cert_neg',
    'ASSOCIE_NOM_COMPLET' => 'associe_nom_complet',
    'ASSOCIE_NOM' => 'associe_nom',
    'ASSOCIE_PRENOM' => 'associe_prenom',
    'ASSOCIE_CIVILITE' => 'associe_civilite',
    'ASSOCIE_CIN' => 'associe_cin',
    'ASSOCIE_DATE_VALIDITE_CIN' => 'associe_date_validite_cin',
    'ASSOCIE_DATE_NAISSANCE' => 'associe_date_naissance',
    'ASSOCIE_LIEU_NAISSANCE' => 'associe_lieu_naissance',
    'ASSOCIE_NATIONALITE' => 'associe_nationalite',
    'ASSOCIE_ADRESSE' => 'associe_adresse',
    'ASSOCIE_TELEPHONE' => 'associe_telephone',
    'ASSOCIE_EMAIL' => 'associe_email',
    'ASSOCIE_QUALITE' => 'associe_qualite',
    'ASSOCIE_PARTS' => 'associe_parts',
    'ASSOCIE_CAPITAL_DETENU' => 'associe_capital_detenu',
    'ASSOCIE_EST_GERANT' => 'associe_est_gerant',
    'CONTRAT_TYPE' => 'contrat_type_domiciliation',
    'CONTRAT_TYPE_DOMICILIATION' => 'contrat_type_domiciliation',
    'CONTRAT_DATE' => 'contrat_date',
    'CONTRAT_DATE_DEBUT' => 'contrat_date_debut',
    'CONTRAT_DATE_FIN' => 'contrat_date_fin',
    'CONTRAT_DUREE_MOIS' => 'contrat_duree_mois',
    'CONTRAT_LOYER_TTC' => 'contrat_loyer_ttc',
    'CONTRAT_LOYER_HT' => 'contrat_loyer_ht',
    'CONTRAT_TVA_POURCENT' => 'contrat_tva_pourcent',
    'CONTRAT_TOTAL_HT' => 'contrat_total_ht',
    'CONTRAT_FRAIS_INTERMEDIAIRE' => 'contrat_frais_intermediaire',
    'CONTRAT_CAUTION' => 'contrat_caution',
    'CONTRAT_STATUT' => 'contrat_statut',
    'CONTRAT_MODE_SIGNATURE' => 'contrat_mode_signature',
    'CONTRAT_PACK_MONTANT_TTC' => 'contrat_pack_montant_ttc',
    'CONTRAT_PACK_LOYER_TTC' => 'contrat_pack_loyer_ttc',
    'CONTRAT_TYPE_RENOUVELLEMENT' => 'contrat_type_renouvellement',
    'CONTRAT_RENOUV_TVA_POURCENT' => 'contrat_renouv_tva_pourcent',
    'CONTRAT_RENOUV_LOYER_HT' => 'contrat_renouv_loyer_ht',
    'CONTRAT_RENOUV_LOYER_TTC' => 'contrat_renouv_loyer_ttc',
    'CONTRAT_RENOUV_ANNUEL_TTC' => 'contrat_renouv_annuel_ttc',
];

$formVariables = [];
foreach ($contextKeys as $ck) {
    $upper = strtoupper($ck);
    $section = TemplateAnalyzer::inferSection($ck);
    $fieldName = $formFieldLabels[$upper] ?? strtolower($ck);
    $inTemplates = isset($allVariables[$upper]);
    $occurrences = $inTemplates ? ($variableOccurrences[$upper] ?? 0) : 0;
    $formVariables[] = [
        'name' => $ck,
        'upper' => $upper,
        'section' => $section === 'autre' ? 'Date' : ucfirst($section),
        'field' => $fieldName,
        'in_templates' => $inTemplates,
        'occurrences' => $occurrences,
    ];
}
usort($formVariables, fn($a, $b) => [$a['section'], $a['name']] <=> [$b['section'], $b['name']]);

$formVarTotal = count($formVariables);
$formVarMapped = count(array_filter($formVariables, fn($v) => $v['in_templates']));
$formVarUnmapped = $formVarTotal - $formVarMapped;
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Mapper les variables des templates vers les variables de l'application</p>
        </div>
        <div class="table-actions">
            <button type="button" id="apply-btn" class="btn btn-next" disabled><span class="material-symbols-outlined">select_all</span> Appliquer la selection</button>
        </div>
    </div>

    <div class="stats compact">
        <article class="stat">
            <span>Variables trouvees</span>
            <strong><?= $totalCount ?></strong>
        </article>
        <article class="stat stat-success">
            <span>Mappees</span>
            <strong><?= $mappedCount ?></strong>
        </article>
        <article class="stat stat-danger">
            <span>Non mappees</span>
            <strong><?= $unmappedCount ?></strong>
        </article>
        <article class="stat">
            <span>Templates analyses</span>
            <strong><?= count($templates) ?></strong>
        </article>
    </div>

    <div class="variables-filter-bar">
        <a class="btn <?= $filter === 'all' ? 'btn-next' : '' ?>" href="?page=variables&filter=all">Tous</a>
        <a class="btn <?= $filter === 'unmapped' ? 'btn-next' : '' ?>" href="?page=variables&filter=unmapped">Non mappes <span class="badge bg-danger"><?= $unmappedCount ?></span></a>
        <a class="btn <?= $filter === 'mapped' ? 'btn-next' : '' ?>" href="?page=variables&filter=mapped">Mappes <span class="badge bg-success"><?= $mappedCount ?></span></a>
        <input type="text" id="var-search" class="var-search" placeholder="Rechercher une variable...">
    </div>

    <?php if (!$totalCount): ?>
        <p class="table-empty">Aucune variable trouvee. Ajoutez des templates sur la page <a href="<?= e(app_url('templates')) ?>">Templates</a>.</p>
    <?php else: ?>
    <form method="post" id="mapping-form">
        <?= csrf_input() ?>
        <input type="hidden" name="apply_mapping" value="1">
        <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th class="var-th-checkbox"><input type="checkbox" id="select-all" title="Tout cocher"></th>
                    <th>Variable dans les templates</th>
                    <th>Occurrences</th>
                    <th>Templates</th>
                    <th>Mapper vers</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allVariables as $upper => $original): ?>
                    <?php
                    $isMapped = isset($contextKeySet[$upper]);
                    if ($filter === 'mapped' && !$isMapped) continue;
                    if ($filter === 'unmapped' && $isMapped) continue;
                    $tplPaths = $variableTemplates[$upper] ?? [];
                    $tplCount = count($tplPaths);
                    $tplNames = array_map('basename', $tplPaths);
                    $firstTpl = $tplPaths[0] ?? null;
                    ?>
                    <tr class="<?= $isMapped ? 'row-mapped' : 'row-unmapped' ?>">
                        <td><input type="checkbox" class="var-checkbox" value="<?= e($upper) ?>" <?= $isMapped ? 'disabled' : '' ?>></td>
                        <td><code class="var-code-primary">{{ <?= e($original) ?> }}</code></td>
                        <td><?= $variableOccurrences[$upper] ?></td>
                        <td title="<?= e(implode(', ', $tplNames)) ?>" class="tpl-list"><?= $tplCount ?> template(s)</td>
                        <td>
                            <select name="target_names[<?= e($upper) ?>]" class="select-mapping" <?= $isMapped ? 'disabled' : '' ?>>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($contextKeys as $ck): ?>
                                <option value="<?= e($ck) ?>" <?= $isMapped && $upper === strtoupper($ck) ? 'selected' : '' ?>><?= e($ck) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <?php if ($isMapped): ?>
                                <span class="statut-badge actif">Mappee</span>
                            <?php else: ?>
                                <span class="statut-badge resilie">Non mappee</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($firstTpl): ?>
                            <div class="action-links">
                                <a class="btn-icon" href="<?= e(app_url('template', ['path' => $firstTpl])) ?>" title="Voir le template"><span class="material-symbols-outlined">visibility</span></a>
                                <?php if ($tplCount > 1): ?>
                                <div class="action-more">
                                    <button type="button" class="btn-icon toggle-dropdown" title="Tous les templates">
                                        <span class="material-symbols-outlined">more_horiz</span>
                                    </button>
                                    <div class="action-dropdown hidden">
                                        <?php foreach ($tplPaths as $tplPath): ?>
                                        <a href="<?= e(app_url('template', ['path' => $tplPath])) ?>" class="action-link"><?= e(basename($tplPath)) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </form>
    <?php endif; ?>
</section>

<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Variables disponibles dans les formulaires de l'application</p>
        </div>
    </div>

    <div class="stats compact">
        <article class="stat">
            <span>Variables formulaire</span>
            <strong><?= $formVarTotal ?></strong>
        </article>
        <article class="stat stat-success">
            <span>Utilisees dans les templates</span>
            <strong><?= $formVarMapped ?></strong>
        </article>
        <article class="stat stat-danger">
            <span>Non utilisees</span>
            <strong><?= $formVarUnmapped ?></strong>
        </article>
    </div>

    <div class="table-scroll">
    <table data-sortable>
        <thead>
            <tr>
                <th data-col="variable">Variable</th>
                <th data-col="section">Section</th>
                <th data-col="field">Champ formulaire</th>
                <th data-col="used" style="width:100px;text-align:center">Utilisee</th>
                <th data-col="occurrences" style="width:100px;text-align:center">Occurrences</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($formVariables as $fv): ?>
            <tr class="<?= $fv['in_templates'] ? 'row-mapped' : 'row-unmapped' ?>">
                <td><code class="var-code-primary">{{ <?= e($fv['name']) ?> }}</code></td>
                <td><?= e($fv['section']) ?></td>
                <td><code><?= e($fv['field']) ?></code></td>
                <td style="text-align:center">
                    <?php if ($fv['in_templates']): ?>
                        <span class="statut-badge actif">Oui</span>
                    <?php else: ?>
                        <span class="statut-badge resilie">Non</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center"><?= $fv['occurrences'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>

<script>
(function(){
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.toggle-dropdown');
        document.querySelectorAll('.action-dropdown').forEach(function(d){
            if (!btn || !btn.closest('.action-more').contains(d)) {
                d.classList.add('hidden');
            }
        });
        if (btn) {
            var dd = btn.closest('.action-more').querySelector('.action-dropdown');
            if (dd) dd.classList.toggle('hidden');
        }
    });

    var searchInput = document.getElementById('var-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(){
            var q = this.value.toLowerCase();
            document.querySelectorAll('#mapping-form tbody tr').forEach(function(row){
                var code = row.querySelector('code');
                if (!code) return;
                row.style.display = code.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    var selectAll = document.getElementById('select-all');
    var checkboxes = document.querySelectorAll('.var-checkbox:not([disabled])');
    var applyBtn = document.getElementById('apply-btn');

    function updateApplyBtn() {
        var checked = document.querySelectorAll('.var-checkbox:not([disabled]):checked');
        applyBtn.disabled = checked.length === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function(){
            checkboxes.forEach(function(cb){
                cb.checked = selectAll.checked;
            });
            updateApplyBtn();
        });
    }

    checkboxes.forEach(function(cb){
        cb.addEventListener('change', updateApplyBtn);
    });

    applyBtn.addEventListener('click', function(){
        var checked = document.querySelectorAll('.var-checkbox:not([disabled]):checked');
        if (checked.length === 0) {
            alert('Selectionnez au moins une variable non mappee.');
            return;
        }
        var hasEmpty = false;
        checked.forEach(function(cb){
            var select = cb.closest('tr').querySelector('select[name^="target_names"]');
            if (select && select.value === '') {
                hasEmpty = true;
            }
        });
        if (hasEmpty) {
            if (!confirm('Certaines variables n\'ont pas de destination choisie. Les variables sans destination seront ignorees. Continuer ?')) {
                return;
            }
        }
        document.getElementById('mapping-form').submit();
    });
})();
</script>


