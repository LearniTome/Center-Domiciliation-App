<?php
declare(strict_types=1);

if (!isset($_SESSION['creation_wizard']) || !is_array($_SESSION['creation_wizard'])) {
    $defaults = load_defaults();

    $associeDefaults = $defaults['associe'] ?? [];

    $dossierNum = 1;
    $currentYear = date('Y');
    if (($pdo ?? null) instanceof PDO) {
        $maxNum = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(societe_dossier, '-', -1) AS UNSIGNED)), 0) FROM societes WHERE societe_dossier LIKE 'DOM-{$currentYear}-%'")->fetchColumn();
        $dossierNum = (int) $maxNum + 1;
    }
    $defaults['societe']['societe_dossier'] = sprintf('DOM-%s-%03d', $currentYear, $dossierNum);

    $_SESSION['creation_wizard'] = [
        'societe' => $defaults['societe'] ?? [],
        'associes' => [[
            'associe_nom_complet' => '',
            'associe_cin' => '',
            'associe_adresse' => '',
            'associe_date_naissance' => '',
            'associe_lieu_naissance' => '',
            'associe_nationalite' => $associeDefaults['associe_nationalite'] ?? '',
            'associe_telephone' => '',
            'associe_email' => '',
            'associe_qualite' => $associeDefaults['associe_qualite'] ?? '',
            'associe_parts' => $associeDefaults['associe_parts'] ?? '',
            'associe_est_gerant' => ($associeDefaults['associe_est_gerant'] ?? false) ? '1' : '0',
        ]],
        'contrat' => $defaults['contrat'] ?? [],
        'uploaded_docs' => [],
        'generated_files' => [],
    ];
}

$wizard = &$_SESSION['creation_wizard'];
$step = max(1, min(6, (int) ($_GET['step'] ?? 1)));
$adressesOptions = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');
$adressesAll = fetch_adresses_all($pdo ?? null);
$villesOptions = fetch_reference_options($pdo ?? null, 'ref_villes', 'ville');
$nationalitesOptions = fetch_reference_options($pdo ?? null, 'ref_nationalites', 'nationalite');
$lieuxNaissanceOptions = fetch_reference_options($pdo ?? null, 'ref_lieux_naissance', 'lieu_naissance');
$qualitesAssocieOptions = fetch_reference_options($pdo ?? null, 'ref_qualites_associe', 'qualite_associe');
$formesJuridiquesOptions = fetch_reference_options($pdo ?? null, 'ref_formes_juridiques', 'forme_juridique');
$activitesOptions = fetch_reference_options($pdo ?? null, 'ref_activites', 'activite');
$ompicOptions = fetch_activites_ompic_options($pdo ?? null);

if (is_post() && isset($_POST['add_activite_ref']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $newActivite = field_value($_POST, 'new_activite');
    $type = field_value($_POST, 'type', 'statuts');
    if ($newActivite !== '') {
        if ($type === 'cert_neg') {
            $ompicCode = field_value($_POST, 'ompic_code');
            if ($ompicCode === '') {
                echo json_encode(['success' => false]);
                exit;
            }
            $nmaLibelle = field_value($_POST, 'nma_libelle');
            if ($nmaLibelle === '') {
                $nmaLibelle = $newActivite;
            }
            $stmt = $pdo->prepare("INSERT IGNORE INTO ref_activites_ompic (code, libelle, sort_order) VALUES (:code, :libelle, :so)");
            $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ref_activites_ompic")->fetchColumn();
            $stmt->execute(['code' => $ompicCode, 'libelle' => $nmaLibelle, 'so' => $max]);
            echo json_encode(['success' => true, 'code' => $ompicCode, 'libelle' => $nmaLibelle]);
        } else {
            $table = 'ref_activites';
            $column = 'activite';
            $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")->fetchColumn();
            $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} ({$column}, sort_order) VALUES (:val, :so)");
            $stmt->execute(['val' => $newActivite, 'so' => $max]);
            echo json_encode(['success' => true, 'value' => $newActivite]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

function _cleanup_tmp_uploads(): void
{
    $tmpDir = __DIR__ . '/../../../uploads/tmp/' . session_id();
    if (is_dir($tmpDir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            $f->isDir() ? rmdir((string) $f) : unlink((string) $f);
        }
        rmdir($tmpDir);
    }
}

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    _cleanup_tmp_uploads();
    unset($_SESSION['creation_wizard']);
    log_activity($pdo, 'reset', 'wizard');
    set_flash('success', 'Assistant reinitialise.');
    redirect_to('creation');
}

if (isset($_GET['cancel']) && $_GET['cancel'] === '1') {
    _cleanup_tmp_uploads();
    unset($_SESSION['creation_wizard']);
    log_activity($pdo, 'cancel', 'wizard');
    set_flash('success', 'Creation annulee.');
    redirect_to('societes');
}

$societeData = array_merge([
    'societe_dossier' => '',
    'societe_raison_sociale' => '',
    'societe_forme_juridique' => '',
    'societe_ice' => '',
    'societe_date_ice' => '',
    'societe_rc' => '',
    'societe_if' => '',
    'societe_activites_statuts' => '',
    'societe_activites_ompic' => '',
    'societe_part_social' => '',
    'societe_valeur_nominale' => '',
    'societe_date_exp_cert_neg' => '',
    'societe_adresse_siege' => '',
    'societe_ville' => '',
    'societe_tribunal' => '',
    'societe_email' => '',
    'societe_telephone' => '',
    'societe_capital' => '',
    'societe_type_generation' => 'domiciliation',
    'societe_procedure_creation' => '',
    'societe_mode_depot' => '',
], $wizard['societe']);

$wizardUser = current_user();
$isExterne = $wizardUser && $wizardUser['collaborateur_type'] !== 'interne' && ($wizardUser['role_id'] ?? 0) !== 1;
if ($isExterne) {
    $societeData['societe_type_generation'] = 'domiciliation';
}

$tribunalTypes = fetch_tribunaux_types($pdo ?? null);
$allTribunaux = fetch_tribunaux_all($pdo ?? null);
$currentTribunalType = $societeData['societe_tribunal_type'] ?? '';
$societeTribunal = $societeData['societe_tribunal'] ?? '';
if (!$currentTribunalType && $societeTribunal) {
    foreach ($allTribunaux as $t) {
        if ($t['tribunal'] === $societeTribunal && ($t['tribunal_type'] ?? '')) {
            $currentTribunalType = $t['tribunal_type'];
            break;
        }
    }
}
if (!$currentTribunalType) {
    $currentTribunalType = 'Tribunal de commerce';
}
$defaultTribunal = $societeTribunal ?: 'Casablanca';
$defaultVille = ($societeData['societe_ville'] ?? '') ?: 'Casablanca';

$associesData = $wizard['associes'];
if (!is_array($associesData) || $associesData === []) {
    $associeDefaults = load_defaults('associe');
    $associesData = [[
        'associe_civilite' => '',
        'associe_nom' => '',
        'associe_prenom' => '',
        'associe_nom_complet' => '',
        'associe_cin' => '',
        'associe_date_validite_cin' => '',
        'associe_adresse' => '',
        'associe_date_naissance' => '',
        'associe_lieu_naissance' => '',
        'associe_nationalite' => $associeDefaults['associe_nationalite'] ?? '',
        'associe_telephone' => '',
        'associe_email' => '',
        'associe_qualite' => $associeDefaults['associe_qualite'] ?? '',
        'associe_parts' => $associeDefaults['associe_parts'] ?? '',
        'associe_capital_detenu' => '',
        'associe_part_percent' => '',
        'associe_est_gerant' => ($associeDefaults['associe_est_gerant'] ?? false) ? '1' : '0',
    ]];
}

$contratData = array_merge([
    'contrat_type' => 'Domiciliation simple',
    'contrat_type_autre' => '',
    'contrat_date' => '',
    'contrat_duree_mois' => '',
    'contrat_type_domiciliation' => '',
    'contrat_type_domiciliation_autre' => '',
    'contrat_date_debut' => '',
    'contrat_date_fin' => '',
    'contrat_tva_pourcent' => '20',
    'contrat_loyer_ht' => '',
    'contrat_loyer_ttc' => '',
    'contrat_total_ht' => '',
    'contrat_type_renouvellement' => '',
    'contrat_renouv_tva_pourcent' => '20',
    'contrat_renouv_loyer_ht' => '',
    'contrat_renouv_total_ht' => '',
    'contrat_renouv_loyer_ttc' => '',
    'contrat_statut' => 'actif',
    'contrat_notes' => '',
], $wizard['contrat']);
