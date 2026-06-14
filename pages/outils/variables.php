<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/TemplateAnalyzer.php';

$templatesDir = __DIR__ . '/../../templates';
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

$fieldLabels = [
    'societe_raison_sociale' => 'Raison sociale',
    'societe_forme_juridique' => 'Forme juridique',
    'societe_ice' => 'ICE',
    'societe_rc' => 'RC',
    'societe_if' => 'IF',
    'societe_capital' => 'Capital',
    'societe_part_social' => 'Part social',
    'societe_valeur_nominale' => 'Valeur nominale',
    'societe_ville' => 'Ville',
    'societe_tribunal' => 'Tribunal',
    'societe_tribunal_type' => 'Type de tribunal',
    'societe_adresse_siege' => 'Adresse de référence',
    'societe_email' => 'Email',
    'societe_telephone' => 'Téléphone',
    'societe_dossier' => 'Dossier domiciliation',
    'societe_type_generation' => 'Type génération',
    'societe_procedure_creation' => 'Procédure création',
    'societe_mode_depot' => 'Mode dépôt création',
    'societe_date_ice' => 'Date cert. négatif',
    'societe_date_exp_cert_neg' => 'Date exp. cert. négatif',
    'associe_nom_complet' => 'Nom complet',
    'associe_nom' => 'Nom',
    'associe_prenom' => 'Prénom',
    'associe_civilite' => 'Civilité',
    'associe_cin' => 'N° CIN/Séjour/Passeport',
    'associe_date_validite_cin' => 'Date validité CIN',
    'associe_date_naissance' => 'Date naissance',
    'associe_lieu_naissance' => 'Lieu naissance',
    'associe_nationalite' => 'Nationalité',
    'associe_adresse' => 'Adresse',
    'associe_telephone' => 'Téléphone',
    'associe_email' => 'Email',
    'associe_qualite' => 'Qualité associé',
    'associe_parts' => 'Parts',
    'associe_capital_detenu' => 'Capital détenu (DH)',
    'associe_est_gerant' => 'Gérant',
    'contrat_type_domiciliation' => 'Type contrat domiciliation',
    'contrat_date' => 'Date du contrat',
    'contrat_date_debut' => 'Date de début',
    'contrat_date_fin' => 'Date de fin',
    'contrat_duree_mois' => 'Durée (mois)',
    'contrat_loyer_ttc' => 'Loyer TTC (Mois)',
    'contrat_loyer_ht' => 'Loyer HT (Mois)',
    'contrat_tva_pourcent' => 'TVA %',
    'contrat_total_ht' => 'Montant Total du Loyer',
    'contrat_frais_intermediaire' => 'Frais intermédiaire',
    'contrat_caution' => 'Caution',
    'contrat_statut' => 'Statut',
    'contrat_mode_signature' => 'Mode signature',
    'contrat_pack_montant_ttc' => 'Pack montant TTC',
    'contrat_pack_loyer_ttc' => 'Pack loyer TTC',
    'contrat_type_renouvellement' => 'Type renouvellement',
    'contrat_renouv_tva_pourcent' => 'TVA % (Renouvellement)',
    'contrat_renouv_loyer_ht' => 'Loyer HT (Renouvellement)',
    'contrat_renouv_loyer_ttc' => 'Loyer TTC (Renouvellement)',
    'contrat_renouv_annuel_ttc' => 'Renouvellement annuel TTC',
];

$formVariables = [];
foreach ($contextKeys as $ck) {
    $upper = strtoupper($ck);
    $section = TemplateAnalyzer::inferSection($ck);
    $fieldName = $formFieldLabels[$upper] ?? strtolower($ck);
    $libelle = $fieldLabels[$fieldName] ?? '';
    $inTemplates = isset($allVariables[$upper]);
    $occurrences = $inTemplates ? ($variableOccurrences[$upper] ?? 0) : 0;
    $formVariables[] = [
        'name' => $ck,
        'upper' => $upper,
        'section' => $section === 'autre' ? 'Date' : ucfirst($section),
        'field' => $fieldName,
        'libelle' => $libelle,
        'in_templates' => $inTemplates,
        'occurrences' => $occurrences,
    ];
}
usort($formVariables, fn($a, $b) => [$a['section'], $a['name']] <=> [$b['section'], $b['name']]);

$formVarTotal = count($formVariables);
$formVarMapped = count(array_filter($formVariables, fn($v) => $v['in_templates']));
$formVarUnmapped = $formVarTotal - $formVarMapped;

$allSections = ['Societe', 'Associe', 'Contrat', 'Date'];
$formSection = $_GET['form_section'] ?? '';
$formUsed = $_GET['form_used'] ?? '';
$filteredFormVars = $formVariables;
if ($formSection) {
    $filteredFormVars = array_filter($filteredFormVars, fn($v) => $v['section'] === $formSection);
}
if ($formUsed === 'used') {
    $filteredFormVars = array_filter($filteredFormVars, fn($v) => $v['in_templates']);
} elseif ($formUsed === 'unused') {
    $filteredFormVars = array_filter($filteredFormVars, fn($v) => !$v['in_templates']);
}
?>
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

    <div class="filter-bar">
        <a class="btn btn-info" href="?page=variables&form_section=&form_used=">Total <span class="badge"><?= $formVarTotal ?></span></a>
        <?php foreach ($allSections as $sec): ?>
        <a class="btn <?= $formSection === $sec && $formUsed === '' ? 'btn-next' : '' ?>" href="?page=variables&form_section=<?= e($sec) ?>&form_used=<?= e($formUsed) ?>"><?= e($sec) ?></a>
        <?php endforeach; ?>
        <span class="filter-sep"></span>
        <a class="btn btn-next" href="?page=variables&form_section=<?= e($formSection) ?>&form_used=used">Utilisees <span class="badge bg-success"><?= $formVarMapped ?></span></a>
        <a class="btn btn-danger" href="?page=variables&form_section=<?= e($formSection) ?>&form_used=unused">Non utilisees <span class="badge bg-danger"><?= $formVarUnmapped ?></span></a>
    </div>
    <div class="search-bar" style="margin-top:4px">
        <input type="text" id="fv-search" class="var-search" placeholder="Rechercher une variable..." style="width:100%;padding:6px 10px;background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-sm);color:var(--text);font-size:0.8rem">
    </div>

    <?php if (!count($filteredFormVars)): ?>
        <p class="table-empty">Aucune variable pour cette section.</p>
    <?php else: ?>
    <div class="table-scroll">
    <table data-sortable>
        <thead>
            <tr>
                <th data-col="variable">Variable</th>
                <th data-col="section">Section</th>
                <th data-col="field">Champ formulaire</th>
                <th data-col="libelle">Libell&eacute;</th>
                <th data-col="used" style="width:100px;text-align:center">Utilisee</th>
                <th data-col="occurrences" style="width:100px;text-align:center">Occurrences</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filteredFormVars as $fv): ?>
            <tr class="<?= $fv['in_templates'] ? 'row-mapped' : 'row-unmapped' ?>">
                <td><code class="var-code-primary">{{ <?= e($fv['name']) ?> }}</code></td>
                <td><?= e($fv['section']) ?></td>
                <td><code><?= e($fv['field']) ?></code></td>
                <td><?= $fv['libelle'] ? e($fv['libelle']) : '<span class="text-muted">&mdash;</span>' ?></td>
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
    <?php endif; ?>
</section>

<script>
(function(){
    var searchInput = document.getElementById('fv-search');
    if (!searchInput) return;
    var table = document.querySelector('.table-scroll table');
    if (!table) return;
    searchInput.addEventListener('input', function(){
        var q = this.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function(row){
            var code = row.querySelector('code');
            if (!code) return;
            row.style.display = code.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
})();
</script>


