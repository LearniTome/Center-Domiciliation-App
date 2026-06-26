<?php
declare(strict_types=1);

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['cession_wizard'], $_SESSION['_cession_loaded']);
    set_flash('success', 'Assistant reinitialise.');
    redirect_to('cession');
}

// ============ SESSION INIT ============
if (!isset($_SESSION['cession_wizard']) || !is_array($_SESSION['cession_wizard'])) {
    $_SESSION['cession_wizard'] = [
        'mode' => '',
        'mode_nouvelle_sous' => '',
        'societe' => [],
        'associes' => [],
        'societe_id' => 0,
        'cession_date' => date('Y-m-d'),
        'cession_motif' => '',
        'parts' => [],
        'cession_metadata' => [],
        'uploaded_docs' => [],
        'generated_files' => [],
        'pv_resolutions' => [],
    ];
    unset($_SESSION['_cession_loaded']);
}

$editingId = (int) ($_GET['id'] ?? 0);
$wizard = &$_SESSION['cession_wizard'];
$step = max(0, min(7, (int) ($_GET['step'] ?? 0)));

// Force re-load from DB when entering edit mode
if ($editingId > 0 && isset($_GET['edit'])) {
    unset($_SESSION['_cession_loaded']);
    $_SESSION['_cession_editing_id'] = $editingId;
}

// ============ REFERENCE DATA ============
$societesList = [];
$formesJuridiques = [];
$tribunaux = [];
$villes = [];
$nationalitesOptions = [];
$lieuxNaissanceOptions = [];
$qualitesAssocieOptions = [];
$activitesOptions = [];
$adressesOptions = [];

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
    $adressesAll = fetch_adresses_all($pdo);
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
    'societe_type_generation' => 'cession',
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

// Associes de la société sélectionnée
$selectedAssocies = [];
if ($wizard['societe_id'] > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('SELECT * FROM associes WHERE societe_id = :id ORDER BY associe_nom_complet');
    $stmt->execute(['id' => $wizard['societe_id']]);
    $selectedAssocies = $stmt->fetchAll();
}

$selectedSociete = null;
if ($wizard['societe_id'] > 0 && ($pdo ?? null) instanceof PDO) {
    $selectedSociete = fetch_record($pdo, 'societes', $wizard['societe_id']);
}

// Load editing from DB
if ($editingId > 0 && !isset($_SESSION['_cession_loaded'])) {
    if (($pdo ?? null) instanceof PDO) {
        $stmt = $pdo->prepare('SELECT * FROM cessions WHERE id = :id');
        $stmt->execute(['id' => $editingId]);
        $dbCession = $stmt->fetch();
        if ($dbCession) {
            $wizard['societe_id'] = (int) $dbCession['societe_id'];
            $wizard['cession_id'] = (int) $dbCession['id'];
            $wizard['cession_date'] = $dbCession['cession_date'] ?? date('Y-m-d');
            $wizard['cession_motif'] = $dbCession['cession_motif'] ?? '';
            $wizard['mode'] = 'existante';
            $wizard['parts'] = [];
            $stmt2 = $pdo->prepare('SELECT * FROM cession_parts WHERE cession_id = :id ORDER BY id');
            $stmt2->execute(['id' => $editingId]);
            foreach ($stmt2->fetchAll() as $p) {
                $cedantAssocieId = (int) ($p['cedant_associe_id'] ?? 0);
                if ($cedantAssocieId === 0 && ($p['cedant_nom_complet'] ?? '') !== '' && $wizard['societe_id'] > 0) {
                    $s = $pdo->prepare('SELECT id FROM associes WHERE societe_id = :sid AND associe_nom_complet = :nom LIMIT 1');
                    $s->execute(['sid' => $wizard['societe_id'], 'nom' => $p['cedant_nom_complet']]);
                    $found = $s->fetch();
                    if ($found) { $cedantAssocieId = (int) $found['id']; }
                }
                $wizard['parts'][] = [
                    'cedant_type' => $p['cedant_type'],
                    'cedant_associe_id' => $cedantAssocieId,
                    'cedant_nom_complet' => $p['cedant_nom_complet'],
                    'cedant_cin' => $p['cedant_cin'],
                    'cessionnaire_type' => $p['cessionnaire_type'],
                    'cessionnaire_associe_id' => $p['cessionnaire_associe_id'],
                    'cessionnaire_nom_complet' => $p['cessionnaire_nom_complet'],
                    'cessionnaire_cin' => $p['cessionnaire_cin'],
                    'cessionnaire_civilite' => $p['cessionnaire_civilite'],
                    'cessionnaire_date_naissance' => $p['cessionnaire_date_naissance'],
                    'cessionnaire_lieu_naissance' => $p['cessionnaire_lieu_naissance'],
                    'cessionnaire_nationalite' => $p['cessionnaire_nationalite'],
                    'cessionnaire_adresse' => $p['cessionnaire_adresse'],
                    'cessionnaire_telephone' => $p['cessionnaire_telephone'] ?? '',
                    'cessionnaire_email' => $p['cessionnaire_email'] ?? '',
                    'cessionnaire_qualite' => $p['cessionnaire_qualite'] ?? '',
                    'cessionnaire_parts' => (int) ($p['cessionnaire_parts'] ?? 0),
                    'cessionnaire_capital_detenu' => $p['cessionnaire_capital_detenu'] ?? '',
                    'cessionnaire_est_gerant' => $p['cessionnaire_est_gerant'] ?? 0,
                    'parts_cedees' => (string) ($p['parts_cedees'] ?? ''),
                    'prix_unitaire' => (string) ($p['prix_unitaire'] ?? ''),
                    'prix_total' => (string) ($p['prix_total'] ?? ''),
                ];
            }
        }
    }
    $_SESSION['_cession_loaded'] = true;
    if ($step === 0) {
        redirect_to('cession', ['step' => 3, 'id' => $editingId, 'edit' => 1]);
    }
}

// ============ GUARD ============
if ($step >= 1 && $wizard['mode'] === '' && !is_post()) {
    redirect_to('cession', ['step' => 0]);
}

// ============ COMPUTED VARS ============
$totalPartsCedees = 0;
$totalPrix = 0;
foreach ($wizard['parts'] as $p) {
    $totalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
    $totalPrix += (float) ($p['prix_total'] ?? 0);
}
$socForTotals = $selectedSociete ?: ($wizard['societe'] ?? []);
$capitalAvant = (float) ($socForTotals['societe_capital'] ?? 0);
$partsAvant = (int) ($socForTotals['societe_part_social'] ?? 0);
$capitalApres = $capitalAvant;
$partsApres = max(0, $partsAvant - $totalPartsCedees);

$gerantsList = [];
foreach ($selectedAssocies as $a) {
    if ((string) ($a['associe_est_gerant'] ?? '0') === '1') {
        $gerantsList[] = $a;
    }
}

$stepLabels = ['Societe', 'Associes', 'Cession', 'Recap', 'PV Cession', 'Validation', 'Generation'];
