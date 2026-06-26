<?php
declare(strict_types=1);

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['pv_ago_wizard'], $_SESSION['_pv_ago_loaded']);
    set_flash('success', 'Assistant reinitialise.');
    redirect_to('pv_ago');
}

if (!isset($_SESSION['pv_ago_wizard']) || !is_array($_SESSION['pv_ago_wizard'])) {
    $_SESSION['pv_ago_wizard'] = [
        'mode' => '',
        'societe' => [],
        'societe_id' => 0,
        'date_ago' => date('Y-m-d'),
        'heure_ago' => '10:00',
        'lieu_ago' => 'au siege social',
        'president_nom' => '',
        'president_qualite' => 'Gerant',
        'exercice_clos' => '31/12/' . (date('Y') - 1),
        'total_parts' => '',
        'parts_presentes' => '',
        'resultat_net' => '',
        'resultat_type' => 'benefice',
        'report_a_nouveau_debiteur' => '0',
        'reserve_legale_existante' => '0',
        'reserve_statutaire_existante' => '0',
        'reserve_facultative_existante' => '0',
        'affectation_option' => 'profit_distribution',
        'dividende_total' => '0',
        'reserve_statutaire_dotation' => '0',
        'reserve_facultative_dotation' => '0',
        'perte_reserve_prelevement' => '0',
        'resolutions' => [],
        'generated_files' => [],
    ];
    unset($_SESSION['_pv_ago_loaded']);
}

$editingId = (int) ($_GET['id'] ?? 0);
$wizard = &$_SESSION['pv_ago_wizard'];
$step = max(0, min(5, (int) ($_GET['step'] ?? 0)));

if ($editingId > 0 && isset($_GET['edit'])) {
    unset($_SESSION['_pv_ago_loaded']);
    $_SESSION['_pv_ago_editing_id'] = $editingId;
}

$societesList = [];
$formesJuridiques = [];
$tribunaux = [];
$villes = [];
$nationalitesOptions = [];
$lieuxNaissanceOptions = [];
$qualitesAssocieOptions = [];
$activitesOptions = [];
$adressesOptions = [];
$ompicOptions = [];
$tribunalTypes = [];
$allTribunaux = [];

if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, societe_raison_sociale, societe_dossier, societe_forme_juridique, societe_capital, societe_part_social, societe_ville FROM societes ORDER BY societe_raison_sociale');
    $societesList = $stmt->fetchAll();
    $stmt = $pdo->query('SELECT * FROM ref_formes_juridiques ORDER BY forme_juridique');
    $formesJuridiques = $stmt->fetchAll();
    $stmt = $pdo->query('SELECT * FROM ref_tribunaux ORDER BY tribunal');
    $tribunaux = $stmt->fetchAll();
    $villes = fetch_reference_options($pdo, 'ref_villes', 'ville');
    $nationalitesOptions = fetch_reference_options($pdo, 'ref_nationalites', 'nationalite');
    $lieuxNaissanceOptions = fetch_reference_options($pdo, 'ref_lieux_naissance', 'lieu_naissance');
    $qualitesAssocieOptions = fetch_reference_options($pdo, 'ref_qualites_associe', 'qualite_associe');
    $activitesOptions = fetch_reference_options($pdo, 'ref_activites', 'activite');
    $adressesOptions = fetch_reference_options($pdo, 'ref_ste_adresses', 'ste_adresse');
    $ompicOptions = fetch_activites_ompic_options($pdo);
    $tribunalTypes = fetch_tribunaux_types($pdo);
    $allTribunaux = fetch_tribunaux_all($pdo);
}

$societeData = array_merge([
    'societe_dossier' => '',
    'societe_raison_sociale' => '',
    'societe_forme_juridique' => '',
    'societe_ice' => '',
    'societe_date_ice' => '',
    'societe_rc' => '',
    'societe_if' => '',
    'societe_tp' => '',
    'societe_cnss' => '',
    'societe_activites_statuts' => '',
    'societe_activites_ompic' => '',
    'societe_part_social' => '',
    'societe_valeur_nominale' => '',
    'societe_date_exp_cert_neg' => '',
    'societe_adresse_siege' => '',
    'societe_ville' => '',
    'societe_tribunal' => '',
    'societe_tribunal_type' => '',
    'societe_email' => '',
    'societe_telephone' => '',
    'societe_capital' => '',
    'societe_type_generation' => 'pv_ago',
    'societe_procedure_creation' => '',
    'societe_mode_depot' => '',
], $wizard['societe'] ?? []);

$currentTribunalType = $societeData['societe_tribunal_type'] ?? '';
$societeTribunal = $societeData['societe_tribunal'] ?? '';
if (!$currentTribunalType && $societeTribunal && !empty($allTribunaux)) {
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

$selectedSociete = null;
if ($wizard['societe_id'] > 0 && ($pdo ?? null) instanceof PDO) {
    $selectedSociete = fetch_record($pdo, 'societes', $wizard['societe_id']);
}

if ($editingId > 0 && !isset($_SESSION['_pv_ago_loaded'])) {
    if (($pdo ?? null) instanceof PDO) {
        $stmt = $pdo->prepare('SELECT * FROM pv_ago WHERE id = :id');
        $stmt->execute(['id' => $editingId]);
        $dbPv = $stmt->fetch();
        if ($dbPv) {
            $wizard['editing_pv_ago_id'] = (int) $dbPv['id'];
            $wizard['societe_id'] = (int) $dbPv['societe_id'];
            $wizard['mode'] = 'existante';
            $wizard['date_ago'] = $dbPv['date_ago'] ?? date('Y-m-d');
            $wizard['heure_ago'] = $dbPv['heure_ago'] ?? '10:00';
            $wizard['lieu_ago'] = $dbPv['lieu_ago'] ?? 'au siege social';
            $wizard['president_nom'] = $dbPv['president_nom'] ?? '';
            $wizard['president_qualite'] = $dbPv['president_qualite'] ?? 'Gerant';
            $wizard['exercice_clos'] = $dbPv['exercice_clos'] ?? '31/12/' . (date('Y') - 1);
            $wizard['total_parts'] = $dbPv['total_parts'] ?? '';
            $wizard['parts_presentes'] = $dbPv['parts_presentes'] ?? '';
            $wizard['resultat_net'] = $dbPv['resultat_net'] ?? '';
            $wizard['resultat_type'] = $dbPv['resultat_type'] ?? 'benefice';
            $wizard['report_a_nouveau_debiteur'] = $dbPv['report_a_nouveau_debiteur'] ?? '0';
            $wizard['reserve_legale_existante'] = $dbPv['reserve_legale_existante'] ?? '0';
            $wizard['reserve_statutaire_existante'] = $dbPv['reserve_statutaire_existante'] ?? '0';
            $wizard['reserve_facultative_existante'] = $dbPv['reserve_facultative_existante'] ?? '0';
            $wizard['affectation_option'] = $dbPv['affectation_option'] ?? 'profit_distribution';
            $wizard['dividende_total'] = $dbPv['dividende_total'] ?? '0';
            $wizard['reserve_statutaire_dotation'] = $dbPv['reserve_statutaire_dotation'] ?? '0';
            $wizard['reserve_facultative_dotation'] = $dbPv['reserve_facultative_dotation'] ?? '0';
            $wizard['perte_reserve_prelevement'] = $dbPv['perte_reserve_prelevement'] ?? '0';
            $wizard['resolutions'] = [];
            if (!empty($dbPv['resolutions'])) {
                $parsed = json_decode($dbPv['resolutions'], true);
                if (is_array($parsed)) {
                    $wizard['resolutions'] = $parsed;
                }
            }
        }
    }
    $_SESSION['_pv_ago_loaded'] = true;
}

if ($step >= 1 && $wizard['mode'] === '' && !is_post()) {
    redirect_to('pv_ago', ['step' => 0]);
}

$capitalSoc = (float) ($selectedSociete['societe_capital'] ?? $wizard['societe']['societe_capital'] ?? 0);
$totalPartsSoc = (int) ($selectedSociete['societe_part_social'] ?? $wizard['societe']['societe_part_social'] ?? 0);

$stepLabels = ['Assemblee', 'Finances', 'Resolutions', 'Recap', 'Generation'];
