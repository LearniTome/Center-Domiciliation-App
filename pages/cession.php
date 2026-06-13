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
        'uploaded_docs' => [],
        'generated_files' => [],
    ];
}

$editingId = (int) ($_GET['id'] ?? 0);
$wizard = &$_SESSION['cession_wizard'];
$step = max(0, min(6, (int) ($_GET['step'] ?? 0)));

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
}

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
            $wizard['cession_date'] = $dbCession['cession_date'] ?? date('Y-m-d');
            $wizard['cession_motif'] = $dbCession['cession_motif'] ?? '';
            $wizard['mode'] = 'existante';
            $stmt2 = $pdo->prepare('SELECT * FROM cession_parts WHERE cession_id = :id ORDER BY id');
            $stmt2->execute(['id' => $editingId]);
            foreach ($stmt2->fetchAll() as $p) {
                $wizard['parts'][] = [
                    'cedant_type' => $p['cedant_type'],
                    'cedant_associe_id' => $p['cedant_associe_id'],
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
                    'parts_cedees' => (string) ($p['parts_cedees'] ?? ''),
                    'prix_unitaire' => (string) ($p['prix_unitaire'] ?? ''),
                    'prix_total' => (string) ($p['prix_total'] ?? ''),
                ];
            }
        }
    }
    $_SESSION['_cession_loaded'] = true;
}

// ============ POST HANDLERS ============
if (is_post()) {
    verify_csrf();

    // AJAX: add new activity reference
    if (!empty($_POST['add_activite_ref']) && ($pdo ?? null) instanceof PDO) {
        header('Content-Type: application/json');
        try {
            $name = trim((string) ($_POST['new_activite'] ?? ''));
            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Nom vide']);
                exit;
            }
            $stmt = $pdo->prepare('INSERT IGNORE INTO ref_activites (activite) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            echo json_encode(['success' => true, 'value' => $name]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Step 0: Mode choice
    if ($step === 0) {
        $mode = $_POST['mode'] ?? '';
        if ($mode === 'existante') {
            $wizard['mode'] = 'existante';
            $wizard['mode_nouvelle_sous'] = '';
            $wizard['societe'] = [];
            $wizard['associes'] = [];
            $wizard['societe_id'] = 0;
            redirect_to('cession', ['step' => 1]);
        } elseif ($mode === 'nouvelle') {
            $wizard['mode'] = 'nouvelle';
            $wizard['mode_nouvelle_sous'] = '';
            $wizard['societe'] = [];
            $wizard['associes'] = [];
            $wizard['societe_id'] = 0;
            $wizard['parts'] = [];
            redirect_to('cession', ['step' => 1]);
        }
        set_flash('error', 'Veuillez choisir un mode.');
        redirect_to('cession', ['step' => 0]);
    }

    // Step 1: Société
    if ($step === 1) {
        if ($wizard['mode'] === 'existante') {
            $wizard['societe_id'] = (int) ($_POST['societe_id'] ?? 0);
            if ($wizard['societe_id'] <= 0) {
                set_flash('error', 'Veuillez selectionner une societe.');
                redirect_to('cession', ['step' => 1]);
            }
            $wizard['parts'] = [];
            redirect_to('cession', ['step' => 2]);
        }

        if ($wizard['mode'] === 'nouvelle') {
            $raison = trim((string) ($_POST['societe_raison_sociale'] ?? ''));
            if ($raison === '') {
                set_flash('error', 'Veuillez saisir la raison sociale.');
                redirect_to('cession', ['step' => 1]);
            }

            $wizard['societe'] = [
                'societe_raison_sociale' => $raison,
                'societe_forme_juridique' => trim((string) ($_POST['societe_forme_juridique'] ?? '')),
                'societe_ice' => trim((string) ($_POST['societe_ice'] ?? '')),
                'societe_rc' => trim((string) ($_POST['societe_rc'] ?? '')),
                'societe_if' => trim((string) ($_POST['societe_if'] ?? '')),
                'societe_tp' => trim((string) ($_POST['societe_tp'] ?? '')),
                'societe_capital' => (string) ($_POST['societe_capital'] ?? ''),
                'societe_part_social' => (string) ($_POST['societe_part_social'] ?? ''),
                'societe_valeur_nominale' => (string) ($_POST['societe_valeur_nominale'] ?? ''),
                'societe_adresse_siege' => trim((string) ($_POST['societe_adresse_siege'] ?? '')),
                'societe_ville' => trim((string) ($_POST['societe_ville'] ?? '')),
                'societe_tribunal' => trim((string) ($_POST['societe_tribunal'] ?? '')),
                'societe_tribunal_type' => trim((string) ($_POST['societe_tribunal_type'] ?? '')),
                'societe_email' => trim((string) ($_POST['societe_email'] ?? '')),
                'societe_telephone' => trim((string) ($_POST['societe_telephone'] ?? '')),
                'societe_activites_statuts' => !empty($_POST['societe_activites_statuts']) && is_array($_POST['societe_activites_statuts']) ? implode(', ', array_unique(array_filter(array_map('trim', $_POST['societe_activites_statuts'])))) : '',
                'societe_activites_ompic' => trim((string) ($_POST['societe_activites_ompic'] ?? '')),
            ];

            $wizard['parts'] = [];
            redirect_to('cession', ['step' => 2]);
        }

        set_flash('error', 'Mode de cession non defini.');
        redirect_to('cession', ['step' => 0]);
    }

    // Step 2: Associés
    if ($step === 2) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 1]);
        }

        $wizard['associes'] = [];
        $noms = $_POST['associe_nom_complet'] ?? [];
        foreach ($noms as $i => $nom) {
            $nom = trim((string) $nom);
            if ($nom === '') continue;
            $wizard['associes'][] = [
                'associe_civilite' => trim((string) ($_POST['associe_civilite'][$i] ?? 'M.')),
                'associe_nom_complet' => $nom,
                'associe_cin' => trim((string) ($_POST['associe_cin'][$i] ?? '')),
                'associe_date_naissance' => trim((string) ($_POST['associe_date_naissance'][$i] ?? '')),
                'associe_lieu_naissance' => trim((string) ($_POST['associe_lieu_naissance'][$i] ?? '')),
                'associe_nationalite' => trim((string) ($_POST['associe_nationalite'][$i] ?? '')),
                'associe_adresse' => trim((string) ($_POST['associe_adresse'][$i] ?? '')),
                'associe_telephone' => trim((string) ($_POST['associe_telephone'][$i] ?? '')),
                'associe_email' => trim((string) ($_POST['associe_email'][$i] ?? '')),
                'associe_qualite' => trim((string) ($_POST['associe_qualite'][$i] ?? 'Gerant')),
                'associe_parts' => (string) ($_POST['associe_parts'][$i] ?? ''),
                'associe_capital_detenu' => (string) ($_POST['associe_capital_detenu'][$i] ?? ''),
                'associe_est_gerant' => !empty($_POST['associe_est_gerant'][$i]) ? '1' : '0',
            ];
        }

        if (empty($wizard['associes'])) {
            set_flash('error', 'Ajoutez au moins un associe.');
            redirect_to('cession', ['step' => 2]);
        }
        redirect_to('cession', ['step' => 3]);
    }

    // Step 3: Cession parts
    if ($step === 3) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 2]);
        }

        $wizard['cession_date'] = field_value($_POST, 'cession_date');
        $wizard['cession_motif'] = field_value($_POST, 'cession_motif');

        $cedantTypes = $_POST['cedant_type'] ?? [];
        $cedantAssocieIds = $_POST['cedant_associe_id'] ?? [];
        $cedantNoms = $_POST['cedant_nom_complet'] ?? [];
        $cedantCins = $_POST['cedant_cin'] ?? [];
        $cessionnaireTypes = $_POST['cessionnaire_type'] ?? [];
        $cessionnaireAssocieIds = $_POST['cessionnaire_associe_id'] ?? [];
        $cessionnaireNoms = $_POST['cessionnaire_nom_complet'] ?? [];
        $cessionnaireCins = $_POST['cessionnaire_cin'] ?? [];
        $cessionnaireCivilites = $_POST['cessionnaire_civilite'] ?? [];
        $cessionnaireDates = $_POST['cessionnaire_date_naissance'] ?? [];
        $cessionnaireLieux = $_POST['cessionnaire_lieu_naissance'] ?? [];
        $cessionnaireNationalites = $_POST['cessionnaire_nationalite'] ?? [];
        $cessionnaireAdresses = $_POST['cessionnaire_adresse'] ?? [];
        $pourcentages = $_POST['pourcentage'] ?? [];
        $partsCedees = $_POST['parts_cedees'] ?? [];
        $prixUnitaires = $_POST['prix_unitaire'] ?? [];
        $prixTotaux = $_POST['prix_total'] ?? [];
        $nommerGerant = $_POST['nommer_gerant'] ?? [];

        $totalParts = 0;
        if ($selectedSociete) {
            $totalParts = (int) ($selectedSociete['societe_part_social'] ?? 0);
        }

        $wizard['parts'] = [];
        $count = max(count($cedantNoms), count($cessionnaireNoms), count($partsCedees));
        for ($i = 0; $i < $count; $i++) {
            $cedType = $cedantTypes[$i] ?? 'existant';
            $cedAssocieId = (int) ($cedantAssocieIds[$i] ?? 0);
            $cedNom = trim((string) ($cedantNoms[$i] ?? ''));
            $cedCin = trim((string) ($cedantCins[$i] ?? ''));
            if ($cedType === 'existant' && $cedAssocieId > 0 && $cedNom === '' && ($pdo ?? null) instanceof PDO) {
                $a = fetch_record($pdo, 'associes', $cedAssocieId);
                if ($a) { $cedNom = $a['associe_nom_complet'] ?? ''; $cedCin = $a['associe_cin'] ?? ''; }
            }

            $cessType = $cessionnaireTypes[$i] ?? 'existant';
            $cessAssocieId = (int) ($cessionnaireAssocieIds[$i] ?? 0);
            $cessNom = trim((string) ($cessionnaireNoms[$i] ?? ''));
            $cessCin = trim((string) ($cessionnaireCins[$i] ?? ''));
            if ($cessType === 'existant' && $cessAssocieId > 0 && $cessNom === '' && ($pdo ?? null) instanceof PDO) {
                $a = fetch_record($pdo, 'associes', $cessAssocieId);
                if ($a) { $cessNom = $a['associe_nom_complet'] ?? ''; $cessCin = $a['associe_cin'] ?? ''; }
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

    // Step 4: Récap (just navigation)
    if ($step === 4) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 3]);
        }
        redirect_to('cession', ['step' => 5]);
    }

    // Step 5: Upload / Validation
    if ($step === 5) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 4]);
        }

        $uploadDir = __DIR__ . '/../uploads';
        $tmpDir = $uploadDir . '/tmp/' . session_id();
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

        $uploadedDocs = $wizard['uploaded_docs'] ?? [];

        $docFields = [
            'ancien_statuts' => 'cession_as',
            'cin_cedant' => 'cession_cinc',
            'cin_cessionnaire' => 'cession_cincs',
            'attestation_non_preemption' => 'cession_anp',
        ];

        foreach ($docFields as $field => $prefix) {
            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $stored = $prefix . '_' . date('Ymd_His') . '.' . $ext;
                $dest = $tmpDir . '/' . $stored;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
                    $uploadedDocs[$field] = [
                        'original' => $_FILES[$field]['name'],
                        'stored' => $stored,
                        'path' => $dest,
                        'taille_ko' => round(filesize($dest) / 1024, 1),
                    ];
                }
            }
        }

        $wizard['uploaded_docs'] = $uploadedDocs;
        redirect_to('cession', ['step' => 6]);
    }

    // Step 6: Generation
    if ($step === 6) {
        $navAction = $_POST['nav_action'] ?? 'generate';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 5]);
        }

        if ($navAction === 'create_dossier') {
            if (!(($pdo ?? null) instanceof PDO)) {
                set_flash('error', 'Connexion MySQL indisponible.');
                redirect_to('cession', ['step' => 6]);
            }

            try {
                $pdo->beginTransaction();

                // Create société if new
                if ($wizard['mode'] === 'nouvelle' && $wizard['societe_id'] <= 0) {
                    $soc = $wizard['societe'];
                    $stmt = $pdo->prepare('INSERT INTO societes (societe_raison_sociale, societe_forme_juridique, societe_source, societe_ice, societe_rc, societe_if, societe_tp, societe_capital, societe_part_social, societe_valeur_nominale, societe_adresse_siege, societe_ville, societe_tribunal, societe_tribunal_type, societe_email, societe_telephone, societe_activites_statuts, created_by) VALUES (:raison, :forme, :source, :ice, :rc, :ifis, :tp, :capital, :parts, :vnom, :adr, :ville, :trib, :trib_type, :email, :tel, :activites, :created_by)');
                    $stmt->execute([
                        'raison' => $soc['societe_raison_sociale'] ?? '',
                        'forme' => $soc['societe_forme_juridique'] ?? '',
                        'source' => 'cession',
                        'ice' => $soc['societe_ice'] ?? '',
                        'rc' => $soc['societe_rc'] ?? '',
                        'ifis' => $soc['societe_if'] ?? '',
                        'tp' => $soc['societe_tp'] ?? '',
                        'capital' => !empty($soc['societe_capital']) ? parse_money($soc['societe_capital']) : null,
                        'parts' => !empty($soc['societe_part_social']) ? (int) $soc['societe_part_social'] : null,
                        'vnom' => !empty($soc['societe_valeur_nominale']) ? parse_money($soc['societe_valeur_nominale']) : null,
                        'adr' => $soc['societe_adresse_siege'] ?? '',
                        'ville' => $soc['societe_ville'] ?? '',
                        'trib' => $soc['societe_tribunal'] ?? '',
                        'trib_type' => $soc['societe_tribunal_type'] ?? '',
                        'email' => $soc['societe_email'] ?? '',
                        'tel' => $soc['societe_telephone'] ?? '',
                        'activites' => $soc['societe_activites_statuts'] ?? '',
                        'created_by' => ($user = current_user()) ? (int) $user['id'] : null,
                    ]);
                    $newSocieteId = (int) $pdo->lastInsertId();
                    $wizard['societe_id'] = $newSocieteId;

                    // Create associés
                    foreach ($wizard['associes'] as $a) {
                        $capitalDetenu = 0;
                        if (!empty($soc['societe_capital']) && !empty($a['associe_parts']) && !empty($soc['societe_part_social'])) {
                            $capitalDetenu = round(((int) $a['associe_parts'] / (int) $soc['societe_part_social']) * parse_money($soc['societe_capital']), 2);
                        }
                        $stmt = $pdo->prepare('INSERT INTO associes (societe_id, associe_civilite, associe_nom_complet, associe_cin, associe_date_naissance, associe_lieu_naissance, associe_nationalite, associe_adresse, associe_telephone, associe_email, associe_qualite, associe_parts, associe_capital_detenu, associe_est_gerant) VALUES (:sid, :civ, :nom, :cin, :dn, :ln, :nat, :adr, :tel, :email, :qual, :parts, :capital, :gerant)');
                        $stmt->execute([
                            'sid' => $newSocieteId,
                            'civ' => $a['associe_civilite'] ?? 'M.',
                            'nom' => $a['associe_nom_complet'] ?? '',
                            'cin' => $a['associe_cin'] ?? '',
                            'dn' => $a['associe_date_naissance'] ?? null,
                            'ln' => $a['associe_lieu_naissance'] ?? '',
                            'nat' => $a['associe_nationalite'] ?? '',
                            'adr' => $a['associe_adresse'] ?? '',
                            'tel' => $a['associe_telephone'] ?? '',
                            'email' => $a['associe_email'] ?? '',
                            'qual' => $a['associe_qualite'] ?? 'Gerant',
                            'parts' => !empty($a['associe_parts']) ? (int) $a['associe_parts'] : null,
                            'capital' => $capitalDetenu ?: null,
                            'gerant' => ($a['associe_est_gerant'] ?? '0') === '1' ? 1 : 0,
                        ]);
                    }
                }

                $societeId = $wizard['societe_id'];
                $capitalAvant = (float) ($selectedSociete['societe_capital'] ?? 0);
                $partsAvant = (int) ($selectedSociete['societe_part_social'] ?? 0);

                // Create cession
                $currentYear = date('Y');
                $maxNum = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(cession_dossier, '-', -1) AS UNSIGNED)), 0) FROM cessions WHERE cession_dossier LIKE 'CES-{$currentYear}-%'")->fetchColumn();
                $dossierNum = (int) $maxNum + 1;
                $dossier = sprintf('CES-%s-%03d', $currentYear, $dossierNum);

                $stmt = $pdo->prepare('INSERT INTO cessions (societe_id, cession_dossier, cession_date, cession_motif, cession_status, capital_avant, parts_avant, created_by) VALUES (:sid, :dos, :dat, :motif, :status, :cap, :parts, :created_by)');
                $stmt->execute([
                    'sid' => $societeId,
                    'dos' => $dossier,
                    'dat' => $wizard['cession_date'],
                    'motif' => $wizard['cession_motif'] ?: null,
                    'status' => $wizard['cession_status'] ?? 'finalise',
                    'cap' => $capitalAvant,
                    'parts' => $partsAvant,
                    'created_by' => ($user = current_user()) ? (int) $user['id'] : null,
                ]);
                $cessionId = (int) $pdo->lastInsertId();

                // Insert cession_parts
                foreach ($wizard['parts'] as $p) {
                    $stmt = $pdo->prepare('INSERT INTO cession_parts (cession_id, cedant_associe_id, cedant_nom_complet, cedant_cin, cedant_type, cessionnaire_associe_id, cessionnaire_nom_complet, cessionnaire_cin, cessionnaire_type, cessionnaire_civilite, cessionnaire_date_naissance, cessionnaire_lieu_naissance, cessionnaire_nationalite, cessionnaire_adresse, parts_cedees, prix_unitaire, prix_total, pourcentage, nommer_gerant) VALUES (:cid, :caid, :cnom, :ccin, :ctype, :csaid, :csnom, :cscin, :cstype, :csciv, :csdn, :csln, :csnat, :csadr, :parts, :pu, :pt, :pct, :ger)');
                    $stmt->execute([
                        'cid' => $cessionId,
                        'caid' => $p['cedant_associe_id'] ?: null,
                        'cnom' => $p['cedant_nom_complet'],
                        'ccin' => $p['cedant_cin'] ?: null,
                        'ctype' => $p['cedant_type'] ?? 'existant',
                        'csaid' => $p['cessionnaire_associe_id'] ?: null,
                        'csnom' => $p['cessionnaire_nom_complet'],
                        'cscin' => $p['cessionnaire_cin'] ?: null,
                        'cstype' => $p['cessionnaire_type'] ?? 'existant',
                        'csciv' => $p['cessionnaire_civilite'] ?? 'M.',
                        'csdn' => $p['cessionnaire_date_naissance'] ?: null,
                        'csln' => $p['cessionnaire_lieu_naissance'] ?: null,
                        'csnat' => $p['cessionnaire_nationalite'] ?: null,
                        'csadr' => $p['cessionnaire_adresse'] ?: null,
                        'parts' => $p['parts_cedees'],
                        'pu' => $p['prix_unitaire'] ?? 0,
                        'pt' => $p['prix_total'] ?? 0,
                        'pct' => $p['pourcentage'] ?? null,
                        'ger' => $p['nommer_gerant'] ?? 0,
                    ]);
                    $cessionPartId = (int) $pdo->lastInsertId();

                    // Create new cessionnaire in associes if needed
                    if (($p['cessionnaire_type'] ?? 'existant') === 'nouveau' && ($p['cessionnaire_associe_id'] ?? 0) <= 0) {
                        $capDet = $partsAvant > 0 ? round(($p['parts_cedees'] / max($partsAvant, 1)) * $capitalAvant, 2) : 0;
                        $stmtA = $pdo->prepare('INSERT INTO associes (societe_id, associe_civilite, associe_nom_complet, associe_cin, associe_date_naissance, associe_lieu_naissance, associe_nationalite, associe_adresse, associe_parts, associe_capital_detenu, associe_est_gerant) VALUES (:sid, :civ, :nom, :cin, :dn, :ln, :nat, :adr, :parts, :cap, :ger)');
                        $stmtA->execute([
                            'sid' => $societeId,
                            'civ' => $p['cessionnaire_civilite'] ?? 'M.',
                            'nom' => $p['cessionnaire_nom_complet'],
                            'cin' => $p['cessionnaire_cin'] ?: null,
                            'dn' => $p['cessionnaire_date_naissance'] ?: null,
                            'ln' => $p['cessionnaire_lieu_naissance'] ?: null,
                            'nat' => $p['cessionnaire_nationalite'] ?: null,
                            'adr' => $p['cessionnaire_adresse'] ?: null,
                            'parts' => $p['parts_cedees'],
                            'cap' => $capDet,
                            'ger' => $p['nommer_gerant'] ?? 0,
                        ]);
                        $newAssocieId = (int) $pdo->lastInsertId();
                        $pdo->prepare('UPDATE cession_parts SET cessionnaire_associe_id = :aid WHERE id = :pid')->execute(['aid' => $newAssocieId, 'pid' => $cessionPartId]);
                    }

                    // Nommer gérant
                    if (!empty($p['nommer_gerant']) && ($p['cessionnaire_associe_id'] ?? 0) > 0) {
                        $pdo->prepare('UPDATE associes SET associe_est_gerant = 1 WHERE id = :id')->execute(['id' => $p['cessionnaire_associe_id']]);
                    }

                    // Reduce cedant parts
                    if (($p['cedant_associe_id'] ?? 0) > 0) {
                        $capDed = $partsAvant > 0 ? round(($p['parts_cedees'] / max($partsAvant, 1)) * $capitalAvant, 2) : 0;
                        $pdo->prepare('UPDATE associes SET associe_parts = GREATEST(COALESCE(associe_parts, 0) - :parts, 0), associe_capital_detenu = GREATEST(COALESCE(associe_capital_detenu, 0) - :cap, 0) WHERE id = :id')->execute(['parts' => $p['parts_cedees'], 'cap' => $capDed, 'id' => $p['cedant_associe_id']]);
                    }
                }

                $pdo->commit();
                $wizard['cession_id'] = $cessionId;
                set_flash('success', 'Dossier de cession cree avec succes.');
                log_activity($pdo, 'create', 'cession', $cessionId);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                set_flash('error', 'Erreur lors de la creation: ' . $e->getMessage());
                redirect_to('cession', ['step' => 6]);
            }
            redirect_to('cession', ['step' => 6]);
        }

        // Generate documents
        if ($navAction === 'generate') {
            if (!isset($wizard['cession_id']) || $wizard['cession_id'] <= 0) {
                set_flash('error', 'Creez d abord le dossier avant de generer les documents.');
                redirect_to('cession', ['step' => 6]);
            }

            require_once __DIR__ . '/../src/TemplateAnalyzer.php';
            require_once __DIR__ . '/../src/DocumentRenderer.php';
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }

            $selectedDocs = $_POST['doc_types'] ?? [];
            if (empty($selectedDocs)) {
                set_flash('error', 'Selectionnez au moins un type de document.');
                redirect_to('cession', ['step' => 6]);
            }

            $cessionId = $wizard['cession_id'];
            $stmtDos = $pdo->prepare('SELECT cession_dossier, societe_id FROM cessions WHERE id = :id');
            $stmtDos->execute(['id' => $cessionId]);
            $row = $stmtDos->fetch();
            $dossierCession = $row ? $row['cession_dossier'] : ('CES-' . $cessionId);

            $socName = $selectedSociete['societe_raison_sociale'] ?? 'Client';
            $forme = $selectedSociete['societe_forme_juridique'] ?? 'PP';
            $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $socName)));
            $clientName = preg_replace('/-+/', '-', $clientName);
            $clientName = trim($clientName, '-');
            $folderName = $wizard['cession_date'] . '_' . $forme . '_' . $clientName;
            $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
            $outputDir = __DIR__ . '/../dossiers_generer/dossiers_cession/' . $folderName . '/' . $dossierCession;
            if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

            $context = DocumentRenderer::buildContextFromCession($pdo, $cessionId);

            $templatesConfig = require __DIR__ . '/../config/templates.php';
            $mapping = $templatesConfig['template_mapping']['cession'] ?? [];

            $templateDir = __DIR__ . '/../templates/_Cession';
            $generated = [];

            foreach ($mapping as $docType) {
                if (!in_array($docType, $selectedDocs, true)) continue;
                $matches = glob($templateDir . '/*' . $docType . '*_Template.docx');
                if (empty($matches)) continue;
                try {
                    $renderer = new DocumentRenderer($matches[0], $outputDir);
                    $outName = $docType . '_' . $cessionId . '_' . date('Ymd') . '.docx';
                    $docxPath = $renderer->render($context, $outName);
                    $pdfPath = $renderer->tryConvertToPdf($docxPath);

                    $stmtD = $pdo->prepare('INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko, valide) VALUES (:sid, :src, :type, :docx, :pdf, :taille, 1)');
                    $stmtD->execute([
                        'sid' => $wizard['societe_id'],
                        'src' => 'cession',
                        'type' => $docType,
                        'docx' => $docxPath,
                        'pdf' => $pdfPath ?? '',
                        'taille' => round(filesize($docxPath) / 1024, 2),
                    ]);
                    $generated[] = $docType;
                } catch (Throwable $e) {}
            }

            set_flash('success', count($generated) . ' document(s) genere(s).');
            redirect_to('cession', ['step' => 6]);
        }

        if ($navAction === 'terminer') {
            $societeId = $wizard['societe_id'] ?? 0;
            $cessionId = $wizard['cession_id'] ?? 0;
            unset($_SESSION['cession_wizard'], $_SESSION['_cession_loaded']);
            redirect_to('cession_dossier', ['id' => $cessionId]);
        }
    }
}

// ============ GUARD: redirect to mode if no mode set ============
if ($step >= 1 && $wizard['mode'] === '' && !is_post()) {
    redirect_to('cession', ['step' => 0]);
}

// ============ COMPUTE TOTALS FOR RECAP ============
$totalPartsCedees = 0;
$totalPrix = 0;
foreach ($wizard['parts'] as $p) {
    $totalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
    $totalPrix += (float) ($p['prix_total'] ?? 0);
}
$capitalAvant = (float) ($selectedSociete['societe_capital'] ?? 0);
$partsAvant = (int) ($selectedSociete['societe_part_social'] ?? 0);
$capitalApres = $capitalAvant;
$partsApres = max(0, $partsAvant - $totalPartsCedees);

// Gérants list for upload step
$gerantsList = [];
foreach ($selectedAssocies as $a) {
    if ((string) ($a['associe_est_gerant'] ?? '0') === '1') {
        $gerantsList[] = $a;
    }
}

$stepLabels = ['Societe', 'Associes', 'Cession', 'Recap', 'Validation', 'Generation'];
?>
<!-- ============ HTML ============ -->
<section>
    <article class="card stack">
        <div class="section-header">
            <h2 style="display:flex;align-items:center;gap:8px;margin:0">
                <span class="material-symbols-outlined" style="color:var(--primary)">transfer_within_a_station</span>
                Cession de parts sociales
            </h2>
            <div style="display:flex;gap:8px">
                <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
            </div>
        </div>

        <?php if ($step >= 1): ?>
        <div class="wizard-steps" id="wizard-steps-top">
            <?php for ($s = 1; $s <= 6; $s++): ?>
                <div class="wizard-step <?= $step > $s ? 'done' : ($step === $s ? 'active' : 'waiting') ?>">
                    <strong>Etape <?= $s ?></strong>
                    <span><?= $stepLabels[$s - 1] ?></span>
                </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- Step 0: Mode choice -->
        <?php if ($step === 0): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <p class="help-text">Comment souhaitez-vous proceder ?</p>
            <div class="grid two" id="mode-choice-grid">
                <label class="card choice-card" data-mode="existante">
                    <input type="radio" name="mode" value="existante" id="mode-existante" style="display:none">
                    <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--primary)">business</span>
                    <h3 style="margin:8px 0 4px">Societe existante</h3>
                    <p class="help-text">Selectionnez une societe deja enregistree</p>
                </label>
                <label class="card choice-card" data-mode="nouvelle">
                    <input type="radio" name="mode" value="nouvelle" id="mode-nouvelle" style="display:none">
                    <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--success)">add_business</span>
                    <h3 style="margin:8px 0 4px">Nouvelle societe</h3>
                    <p class="help-text">Creer une nouvelle societe pour cette cession</p>
                </label>
            </div>
            <div class="table-actions" style="margin-top:20px">
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <script>
        (function(){
            var cards = document.querySelectorAll('.choice-card');
            cards.forEach(function(c){
                c.addEventListener('click', function(){
                    var radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                    var group = this.closest('#mode-choice-grid') ? document.querySelectorAll('#mode-choice-grid .choice-card') : [];
                    group.forEach(function(x){ x.style.borderColor = 'var(--line)'; });
                    this.style.borderColor = this.dataset.mode === 'nouvelle' ? 'var(--success)' : 'var(--primary)';
                });
            });
        })();
        </script>

        <!-- Step 1: Société -->
        <?php elseif ($step === 1): ?>

        <?php if ($wizard['mode'] === 'existante'): ?>
        <!-- Mode existante: dropdown -->
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <div class="field">
                <label for="societe_id">Societe concernee</label>
                <select name="societe_id" id="societe_id">
                    <option value="">-- Selectionnez une societe --</option>
                    <?php foreach ($societesList as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= $wizard['societe_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['societe_raison_sociale']) ?> (<?= e($s['societe_dossier'] ?? '') ?>) - <?= e($s['societe_forme_juridique'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selectedSociete): ?>
            <div class="info-grid">
                <div><strong>Forme juridique</strong><br><?= e($selectedSociete['societe_forme_juridique'] ?? '-') ?></div>
                <div><strong>Capital</strong><br><?= e(number_format((float) ($selectedSociete['societe_capital'] ?? 0), 2, ',', ' ') . ' DH') ?></div>
                <div><strong>Nombre de parts</strong><br><?= (int) ($selectedSociete['societe_part_social'] ?? 0) ?></div>
                <div><strong>Ville</strong><br><?= e($selectedSociete['societe_ville'] ?? '-') ?></div>
            </div>
            <?php if (!empty($selectedAssocies)): ?>
            <div>
                <strong>Associes actuels :</strong>
                <table data-sortable>
                    <thead>
                        <tr>
                            <th data-col="associe">Associe</th>
                            <th data-col="cin">CIN</th>
                            <th data-col="parts">Parts</th>
                            <th data-col="capital">Capital</th>
                            <th data-col="qualite">Qualite</th>
                            <th>Gerant</th>
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
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <div class="footer-actions">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <?php elseif ($wizard['mode'] === 'nouvelle'): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <article class="card">
                <div class="section-header">
                    <div style="display:flex;align-items:center;gap:8px"><h2>Informations sur la societe</h2><p class="help-text" style="margin:0">Saisissez les details de la nouvelle societe</p></div>
                </div>
                <div class="form-grid">
                    <h3 class="section-title">Identifiants</h3>
                    <label class="field">
                        <span>Raison sociale *</span>
                        <input type="text" name="societe_raison_sociale" required value="<?= e($wizard['societe']['societe_raison_sociale'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Forme juridique</span>
                        <select name="societe_forme_juridique">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($formesJuridiques as $fj): ?>
                                <option value="<?= e($fj['forme_juridique']) ?>" <?= ($wizard['societe']['societe_forme_juridique'] ?? '') === $fj['forme_juridique'] ? 'selected' : '' ?>><?= e($fj['forme_juridique']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>ICE</span>
                        <input type="text" name="societe_ice" value="<?= e($wizard['societe']['societe_ice'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>RC</span>
                        <input type="text" name="societe_rc" value="<?= e($wizard['societe']['societe_rc'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>IF</span>
                        <input type="text" name="societe_if" value="<?= e($wizard['societe']['societe_if'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>TP</span>
                        <input type="text" name="societe_tp" value="<?= e($wizard['societe']['societe_tp'] ?? '') ?>">
                    </label>
                    <h3 class="section-title">Capital</h3>
                    <label class="field">
                        <span>Capital (DH)</span>
                        <input type="text" name="societe_capital" value="<?= e($wizard['societe']['societe_capital'] ?? '') ?>" placeholder="100000">
                    </label>
                    <label class="field">
                        <span>Nombre de parts</span>
                        <input type="number" name="societe_part_social" value="<?= e($wizard['societe']['societe_part_social'] ?? '') ?>" placeholder="100">
                    </label>
                    <label class="field">
                        <span>Valeur nominale (DH)</span>
                        <input type="text" name="societe_valeur_nominale" value="<?= e($wizard['societe']['societe_valeur_nominale'] ?? '') ?>" placeholder="1000">
                    </label>
                    <h3 class="section-title">Localisation</h3>
                    <label class="field">
                        <span>Ville</span>
                        <select name="societe_ville">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($villes as $v): ?>
                                <option value="<?= e($v) ?>" <?= ($wizard['societe']['societe_ville'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Tribunal</span>
                        <select name="societe_tribunal">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($tribunaux as $t): ?>
                                <option value="<?= e($t['tribunal']) ?>" <?= ($wizard['societe']['societe_tribunal'] ?? '') === $t['tribunal'] ? 'selected' : '' ?>><?= e($t['tribunal']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Type de tribunal</span>
                        <select name="societe_tribunal_type">
                            <option value="">-- Selectionnez --</option>
                            <option value="Tribunal de commerce" <?= ($wizard['societe']['societe_tribunal_type'] ?? '') === 'Tribunal de commerce' ? 'selected' : '' ?>>Tribunal de commerce</option>
                            <option value="Tribunal de Première Instance" <?= ($wizard['societe']['societe_tribunal_type'] ?? '') === 'Tribunal de Première Instance' ? 'selected' : '' ?>>Tribunal de Première Instance</option>
                        </select>
                    </label>
                    <label class="field full">
                        <span>Adresse du siege</span>
                        <textarea name="societe_adresse_siege" rows="2"><?= e($wizard['societe']['societe_adresse_siege'] ?? '') ?></textarea>
                    </label>
                    <h3 class="section-title">Contact</h3>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="societe_email" value="<?= e($wizard['societe']['societe_email'] ?? '') ?>" placeholder="contact@exemple.com">
                    </label>
                    <label class="field">
                        <span>Telephone</span>
                        <input type="text" name="societe_telephone" value="<?= e($wizard['societe']['societe_telephone'] ?? '') ?>" placeholder="05XX-XXXXXX">
                    </label>
                    <h3 class="section-title">Activite</h3>
                    <div class="field full" style="flex-direction:column;align-items:stretch;gap:8px">
                        <span>Activites (statuts)</span>
                        <div style="overflow:visible">
                            <table id="activites-table">
                                <thead>
                                    <tr>
                                        <th>Activite</th>
                                        <th style="width:50px">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="activites-container">
                                    <?php
                                    $wizStatuts = !empty($wizard['societe']['societe_activites_statuts']) ? array_map('trim', explode(',', (string) $wizard['societe']['societe_activites_statuts'])) : [];
                                    if ($wizStatuts):
                                        foreach ($wizStatuts as $act):
                                    ?>
                                        <tr data-activite-row>
                                            <td>
                                                <div class="autocomplete-wrap" style="position:relative">
                                                    <input type="text" name="societe_activites_statuts[]" style="width:100%" value="<?= e($act) ?>" placeholder="Saisissez ou selectionnez une activite" autocomplete="off">
                                                    <div class="autocomplete-dropdown" style="position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--panel);border:1px solid var(--line);border-radius:4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.5)"></div>
                                                </div>
                                            </td>
                                            <td><button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button></td>
                                        </tr>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <tr data-activite-row>
                                            <td>
                                                <div class="autocomplete-wrap" style="position:relative">
                                                    <input type="text" name="societe_activites_statuts[]" style="width:100%" placeholder="Saisissez ou selectionnez une activite" autocomplete="off">
                                                    <div class="autocomplete-dropdown" style="position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--panel);border:1px solid var(--line);border-radius:4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.5)"></div>
                                                </div>
                                            </td>
                                            <td><button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="button" class="btn" id="add-activite-row"><span class="material-symbols-outlined">add</span> Ajouter une activite</button>
                        </div>
                        <template id="activite-row-template">
                            <tr data-activite-row>
                                <td>
                                    <div class="autocomplete-wrap" style="position:relative">
                                        <input type="text" name="societe_activites_statuts[]" style="width:100%" placeholder="Saisissez ou selectionnez une activite" autocomplete="off">
                                        <div class="autocomplete-dropdown" style="position:absolute;top:100%;left:0;right:0;z-index:100;background:var(--panel);border:1px solid var(--line);border-radius:4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.5)"></div>
                                    </div>
                                </td>
                                <td><button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="material-symbols-outlined">close</span></button></td>
                            </tr>
                        </template>
                    </div>
                </div>
            </article>

            <div class="footer-actions">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>
        <?php endif; ?>

        <!-- Step 2: Associés -->
        <?php elseif ($step === 2): ?>
        <form method="post" class="stack" id="associe-step-form">
            <?= csrf_input() ?>
            <input type="hidden" name="nav_action" value="next">

            <div class="section-header">
                <div style="display:flex;align-items:center;gap:8px"><h2>Associes</h2><p class="help-text" style="margin:0">Ajoutez les associes de la societe</p></div>
                <button class="btn btn-info" type="button" id="add-associe-step2"><span class="material-symbols-outlined">add</span> Ajouter un associe</button>
            </div>

            <div class="stack" id="cession-associes-container">
                <?php if (!empty($selectedAssocies) && $wizard['mode'] === 'existante'): ?>
                <article class="card">
                    <div class="section-header">
                        <div><h3>Associes existants</h3></div>
                    </div>
                    <table data-sortable>
                        <thead>
                            <tr>
                                <th data-col="associe">Associe</th>
                                <th data-col="cin">CIN</th>
                                <th data-col="parts">Parts</th>
                                <th data-col="capital">Capital</th>
                                <th data-col="qualite">Qualite</th>
                                <th>Gerant</th>
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

                <?php
                $savedAssocies = $wizard['associes'] ?? [];
                if (!empty($savedAssocies)): ?>
                    <?php foreach ($savedAssocies as $ai => $assoc): ?>
                    <div class="associe-card" data-associe-item>
                        <div class="associe-card-header">
                            <strong data-associe-title>Associe <?= $ai + 1 ?></strong>
                            <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                        </div>
                        <div class="form-grid">
                            <h3 class="section-title">Identite</h3>
                            <label class="field">
                                <span>Civilite</span>
                                <select name="associe_civilite[<?= $ai ?>]">
                                    <option value="M." <?= ($assoc['associe_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option>
                                    <option value="Mme" <?= ($assoc['associe_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                                    <option value="Mlle" <?= ($assoc['associe_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nom complet *</span>
                                <input type="text" name="associe_nom_complet[<?= $ai ?>]" required value="<?= e($assoc['associe_nom_complet'] ?? '') ?>">
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
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                        <option value="<?= e($ln) ?>" <?= ($assoc['associe_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nationalite</span>
                                <select name="associe_nationalite[<?= $ai ?>]">
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($nationalitesOptions as $nat): ?>
                                        <option value="<?= e($nat) ?>" <?= ($assoc['associe_nationalite'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <h3 class="section-title">Contact</h3>
                            <label class="field">
                                <span>Telephone</span>
                                <input type="text" name="associe_telephone[<?= $ai ?>]" value="<?= e($assoc['associe_telephone'] ?? '') ?>">
                            </label>
                            <label class="field">
                                <span>Email</span>
                                <input type="email" name="associe_email[<?= $ai ?>]" value="<?= e($assoc['associe_email'] ?? '') ?>">
                            </label>
                            <label class="field full">
                                <span>Adresse</span>
                                <textarea name="associe_adresse[<?= $ai ?>]" rows="2"><?= e($assoc['associe_adresse'] ?? '') ?></textarea>
                            </label>
                            <h3 class="section-title">Participation</h3>
                            <label class="field">
                                <span>Qualite</span>
                                <select name="associe_qualite[<?= $ai ?>]">
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                        <option value="<?= e($qa) ?>" <?= ($assoc['associe_qualite'] ?? '') === $qa ? 'selected' : '' ?>><?= e($qa) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nombre de parts</span>
                                <input type="number" name="associe_parts[<?= $ai ?>]" value="<?= e($assoc['associe_parts'] ?? '') ?>" placeholder="100">
                            </label>
                            <label class="field" style="justify-content:center">
                                <label style="display:flex;align-items:center;gap:6px;padding:6px 0">
                                    <input type="checkbox" name="associe_est_gerant[<?= $ai ?>]" value="1" <?= ($assoc['associe_est_gerant'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    Gerant
                                </label>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="associe-card" data-associe-item>
                        <div class="associe-card-header">
                            <strong data-associe-title>Associe 1</strong>
                            <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                        </div>
                        <div class="form-grid">
                            <h3 class="section-title">Identite</h3>
                            <label class="field">
                                <span>Civilite</span>
                                <select name="associe_civilite[0]">
                                    <option value="M." selected>M.</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nom complet *</span>
                                <input type="text" name="associe_nom_complet[0]" required>
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
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                        <option value="<?= e($ln) ?>"><?= e($ln) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nationalite</span>
                                <select name="associe_nationalite[0]">
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($nationalitesOptions as $nat): ?>
                                        <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <h3 class="section-title">Contact</h3>
                            <label class="field">
                                <span>Telephone</span>
                                <input type="text" name="associe_telephone[0]">
                            </label>
                            <label class="field">
                                <span>Email</span>
                                <input type="email" name="associe_email[0]">
                            </label>
                            <label class="field full">
                                <span>Adresse</span>
                                <textarea name="associe_adresse[0]" rows="2"></textarea>
                            </label>
                            <h3 class="section-title">Participation</h3>
                            <label class="field">
                                <span>Qualite</span>
                                <select name="associe_qualite[0]">
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                        <option value="<?= e($qa) ?>"><?= e($qa) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nombre de parts</span>
                                <input type="number" name="associe_parts[0]" placeholder="100">
                            </label>
                            <label class="field" style="justify-content:center">
                                <label style="display:flex;align-items:center;gap:6px;padding:6px 0">
                                    <input type="checkbox" name="associe_est_gerant[0]" value="1">
                                    Gerant
                                </label>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <template id="associe-step2-template">
                <div class="associe-card" data-associe-item>
                    <div class="associe-card-header">
                        <strong data-associe-title>Associe</strong>
                        <button class="btn btn-secondary btn-remove" type="button" data-remove-associe>Retirer</button>
                    </div>
                    <div class="form-grid">
                        <h3 class="section-title">Identite</h3>
                        <label class="field">
                            <span>Civilite</span>
                            <select data-field-name="associe_civilite">
                                <option value="M.">M.</option>
                                <option value="Mme">Mme</option>
                                <option value="Mlle">Mlle</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Nom complet</span>
                            <input data-field-name="associe_nom_complet" required>
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
                                <option value="">-- Selectionnez --</option>
                                <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                <option value="<?= e($ln) ?>"><?= e($ln) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Nationalite</span>
                            <select data-field-name="associe_nationalite">
                                <option value="">-- Selectionnez --</option>
                                <?php foreach ($nationalitesOptions as $nat): ?>
                                <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <h3 class="section-title">Contact</h3>
                        <label class="field">
                            <span>Telephone</span>
                            <input data-field-name="associe_telephone">
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input data-field-name="associe_email" type="email">
                        </label>
                        <label class="field full">
                            <span>Adresse</span>
                            <textarea data-field-name="associe_adresse" rows="2"></textarea>
                        </label>
                        <h3 class="section-title">Participation</h3>
                        <label class="field">
                            <span>Qualite</span>
                            <select data-field-name="associe_qualite">
                                <option value="">-- Selectionnez --</option>
                                <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                <option value="<?= e($qa) ?>"><?= e($qa) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Nombre de parts</span>
                            <input data-field-name="associe_parts" type="number" placeholder="100">
                        </label>
                        <label class="field" style="justify-content:center">
                            <label style="display:flex;align-items:center;gap:6px;padding:6px 0">
                                <input data-field-name="associe_est_gerant" type="checkbox" value="1">
                                Gerant
                            </label>
                        </label>
                    </div>
                </div>
            </template>

            <div class="footer-actions" style="margin-top:12px">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <!-- Step 3: Cession parts -->
        <?php elseif ($step === 3): ?>
        <form method="post" id="cession-form">
            <?= csrf_input() ?>
            <input type="hidden" name="nav_action" value="next">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="field">
                    <label for="cession_date">Date de la cession</label>
                    <input type="date" name="cession_date" id="cession_date" value="<?= e($wizard['cession_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="field">
                    <label for="cession_motif">Motif de la cession</label>
                    <input type="text" name="cession_motif" id="cession_motif" value="<?= e($wizard['cession_motif'] ?? '') ?>" placeholder="Ex: Cession entre associes">
                </div>
            </div>

            <input type="hidden" id="total-societe-parts" value="<?= (int) ($selectedSociete['societe_part_social'] ?? 0) ?>">

            <div style="margin-top:20px">
                <strong>Lignes de cession</strong>
                <div id="cession-parts-container">
                    <?php $partIndex = 0; ?>
                    <?php if (!empty($wizard['parts'])): ?>
                        <?php foreach ($wizard['parts'] as $pi => $part): ?>
                            <?php $partIndex = $pi; include __DIR__ . '/_cession_part_row.php'; ?>
                            <?php $partIndex = $pi + 1; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                            $part = [
                                'cedant_type' => 'existant', 'cedant_associe_id' => 0, 'cedant_nom_complet' => '', 'cedant_cin' => '',
                                'cessionnaire_type' => 'existant', 'cessionnaire_associe_id' => 0, 'cessionnaire_nom_complet' => '', 'cessionnaire_cin' => '',
                                'cessionnaire_civilite' => 'M.', 'cessionnaire_date_naissance' => '', 'cessionnaire_lieu_naissance' => '',
                                'cessionnaire_nationalite' => '', 'cessionnaire_adresse' => '',
                                'parts_cedees' => '', 'prix_unitaire' => '', 'prix_total' => '',
                            ];
                            $partIndex = 0; include __DIR__ . '/_cession_part_row.php';
                            $partIndex = 1;
                        ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-info" id="add-cession-part" style="margin-top:8px" data-part-index="<?= $partIndex ?>">
                    <span class="material-symbols-outlined">add</span> Ajouter une ligne
                </button>
            </div>

            <div class="footer-actions">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 2])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <!-- Step 4: Recap -->
        <?php elseif ($step === 4): ?>
        <div class="stack">
            <div class="stats" style="margin-bottom:16px">
                <article class="stat">
                    <span class="stat-value"><?= e($selectedSociete['societe_raison_sociale'] ?? '-') ?></span>
                    <span class="stat-label">Societe concernee</span>
                </article>
                <article class="stat">
                    <span class="stat-value"><?= e($wizard['cession_date'] ?? date('Y-m-d')) ?></span>
                    <span class="stat-label">Date de cession</span>
                </article>
                <article class="stat">
                    <span class="stat-value"><?= e($wizard['cession_motif'] ?: '-') ?></span>
                    <span class="stat-label">Motif</span>
                </article>
            </div>

            <strong>Details de la cession :</strong>
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="cedant">Cedant</th>
                        <th data-col="cessionnaire">Cessionnaire</th>
                        <th data-col="pourcentage">%</th>
                        <th data-col="parts">Parts cedees</th>
                        <th data-col="prix-u">Prix unitaire</th>
                        <th data-col="prix-t">Prix total</th>
                        <th>Gerant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wizard['parts'] as $p): ?>
                    <tr>
                        <td><?= e($p['cedant_nom_complet']) ?></td>
                        <td><?= e($p['cessionnaire_nom_complet']) ?></td>
                        <td><?= isset($p['pourcentage']) ? number_format((float) $p['pourcentage'], 1, ',', ' ') . '%' : '-' ?></td>
                        <td><?= (int) ($p['parts_cedees'] ?? 0) ?></td>
                        <td><?= e(number_format((float) ($p['prix_unitaire'] ?? 0), 2, ',', ' ') . ' DH') ?></td>
                        <td><?= e(number_format((float) ($p['prix_total'] ?? 0), 2, ',', ' ') . ' DH') ?></td>
                        <td><?= !empty($p['nommer_gerant']) ? '<span class="material-symbols-outlined" style="color:var(--success)">verified</span>' : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:600">
                        <td colspan="3">Total</td>
                        <td><?= $totalPartsCedees ?></td>
                        <td></td>
                        <td><?= e(number_format($totalPrix, 2, ',', ' ') . ' DH') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div class="stats" style="margin:16px 0">
                <article class="stat">
                    <span class="stat-value"><?= e(number_format($capitalAvant, 2, ',', ' ') . ' DH') ?></span>
                    <span class="stat-label">Capital avant cession</span>
                </article>
                <article class="stat">
                    <span class="stat-value"><?= $partsAvant ?></span>
                    <span class="stat-label">Parts avant cession</span>
                </article>
                <article class="stat">
                    <span class="stat-value" style="color:var(--primary)"><?= e(number_format($capitalApres, 2, ',', ' ') . ' DH') ?></span>
                    <span class="stat-label">Capital apres cession</span>
                </article>
                <article class="stat">
                    <span class="stat-value" style="color:var(--primary)"><?= $partsApres ?></span>
                    <span class="stat-label">Parts apres cession</span>
                </article>
            </div>

            <form method="post" class="stack">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="next">
                <div class="footer-actions">
                    <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
                </div>
            </form>
        </div>

        <!-- Step 5: Validation / Upload -->
        <?php elseif ($step === 5): ?>
        <?php $uploadedDocs = $wizard['uploaded_docs'] ?? []; ?>
        <div class="stack">
            <form method="post" class="stack" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="next">

                <?php
                $docItems = [
                    'ancien_statuts' => [
                        'label' => 'Anciens statuts',
                        'icon' => 'description',
                        'accept' => '.pdf',
                    ],
                    'cin_cedant' => [
                        'label' => 'CIN Cédant',
                        'icon' => 'badge',
                        'accept' => '.pdf,.jpg,.jpeg,.png',
                    ],
                    'cin_cessionnaire' => [
                        'label' => 'CIN Cessionnaire',
                        'icon' => 'badge',
                        'accept' => '.pdf,.jpg,.jpeg,.png',
                    ],
                    'attestation_non_preemption' => [
                        'label' => 'Attestation non prépondérance immobilière',
                        'icon' => 'gavel',
                        'accept' => '.pdf',
                    ],
                ];
                ?>

                <article class="card">
                    <div class="section-header">
                        <h3>Documents à fournir</h3>
                    </div>
                    <div class="grid two" style="gap:16px;margin-top:8px">
                    <?php foreach ($docItems as $field => $info): ?>
                    <?php $hasDoc = isset($uploadedDocs[$field]); ?>
                        <label class="field" style="border:1px solid <?= $hasDoc ? 'var(--success)' : 'var(--danger)' ?>;border-radius:6px;padding:10px 12px;cursor:pointer">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <span class="material-symbols-outlined" style="font-size:18px;color:<?= $hasDoc ? 'var(--success)' : 'var(--danger)' ?>"><?= $info['icon'] ?></span>
                                <strong style="flex:1;font-size:0.9rem"><?= $info['label'] ?></strong>
                                <?php if ($hasDoc): ?>
                                    <span style="color:var(--success);font-size:0.8rem"><span class="material-symbols-outlined" style="font-size:16px">check_circle</span></span>
                                <?php else: ?>
                                    <span style="color:var(--danger);font-size:0.8rem"><span class="material-symbols-outlined" style="font-size:16px">cancel</span></span>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="<?= $field ?>" accept="<?= $info['accept'] ?>" style="font-size:0.85rem">
                            <?php if ($hasDoc): ?>
                                <div style="font-size:0.75rem;color:var(--success);margin-top:2px"><?= e($uploadedDocs[$field]['original']) ?></div>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </article>

                <div class="footer-actions" style="margin-top:12px">
                    <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
                </div>
            </form>
        </div>

        <!-- Step 6: Generation -->
        <?php elseif ($step === 6): ?>
        <?php
            $dossierCreated = isset($wizard['cession_id']) && $wizard['cession_id'] > 0;
            $cessionId = $wizard['cession_id'] ?? null;
            $templatesConfig = require __DIR__ . '/../config/templates.php';
            $mapping = $templatesConfig['template_mapping']['cession'] ?? [];
            $docTypes = $templatesConfig['document_types'] ?? [];
            $generatedFiles = $wizard['generated_files'] ?? [];

            require_once __DIR__ . '/../src/TemplateAnalyzer.php';
            $cessionTemplateDir = __DIR__ . '/../templates/_Cession';
            $templatesByType = [];
            foreach ($mapping as $docType) {
                $matches = glob($cessionTemplateDir . '/*' . $docType . '*_Template.docx');
                if (!empty($matches)) {
                    try {
                        $variables = TemplateAnalyzer::extractVariables($matches[0]);
                    } catch (Throwable $e) {
                        $variables = [];
                    }
                    $templatesByType[$docType][] = [
                        'path' => $matches[0],
                        'variables' => $variables,
                    ];
                }
            }
        ?>
        <div class="stack">
            <div class="section-header">
                <div>
                    <h2>Etape 6 — Generation des documents</h2>
                    <p class="help-text">Creez d abord le dossier, puis selectionnez les documents a generer.</p>
                </div>
                <?php if ($dossierCreated): ?>
                    <a class="btn btn-secondary" href="<?= e(app_url('cession_dossier', ['id' => $cessionId])) ?>">
                        <span class="material-symbols-outlined">visibility</span> Voir le dossier
                    </a>
                <?php endif; ?>
            </div>

            <div class="two-step-flow">
                <div class="step-card <?= $dossierCreated ? 'done' : 'active' ?>">
                    <div class="step-card-header">
                        <span class="step-num">1</span>
                        <div>
                            <h3>Creer le dossier</h3>
                            <p class="help-text">Enregistrez la cession en base de donnees.</p>
                        </div>
                        <?php if ($dossierCreated): ?>
                            <span class="step-badge" style="color:var(--success)">Fait</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$dossierCreated): ?>
                        <form method="post" style="margin-top:8px">
                            <?= csrf_input() ?>
                            <input type="hidden" name="nav_action" value="create_dossier">
                            <button class="btn btn-next" type="submit">
                                <span class="material-symbols-outlined">create_new_folder</span> Creer le dossier complet
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="step-card <?= $dossierCreated ? ($generatedFiles ? 'done' : 'active') : 'waiting' ?>">
                    <div class="step-card-header">
                        <span class="step-num">2</span>
                        <div>
                            <h3>Generer les documents</h3>
                            <p class="help-text">Selectionnez les types de documents a generer.</p>
                        </div>
                    </div>

                    <?php if (!$dossierCreated): ?>
                        <p class="help-text" style="margin:12px 0 0;font-style:italic">Creez d abord le dossier pour acceder aux templates.</p>
                    <?php else: ?>
                        <form method="post" class="stack" style="gap:8px;margin-top:8px">
                            <?= csrf_input() ?>
                            <input type="hidden" name="nav_action" value="generate">

                            <?php if (!empty($templatesByType)): ?>
                            <div style="display:flex;align-items:center;gap:8px">
                                <a class="btn-icon" href="#" id="select-all-wizard" title="Tout selectionner">
                                    <span class="material-symbols-outlined">select_all</span>
                                </a>
                            </div>
                            <div class="table-scroll" style="overflow-x:auto">
                                <table style="white-space:nowrap">
                                    <thead>
                                        <tr>
                                            <th class="col-check"></th>
                                            <th>Type</th>
                                            <th>Template</th>
                                            <th>Variables</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($templatesByType as $docType => $typeTemplates): ?>
                                            <?php $typeLabel = $docTypes[$docType] ?? $docType; ?>
                                            <?php $tplCount = count($typeTemplates); ?>
                                            <?php foreach ($typeTemplates as $i => $tpl): ?>
                                                <tr>
                                                    <td class="col-check"><input type="checkbox" name="doc_types[]" value="<?= e($docType) ?>" checked class="template-check"></td>
                                                    <?php if ($i === 0): ?>
                                                        <td rowspan="<?= $tplCount ?>" style="vertical-align:middle"><?= e($typeLabel) ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <span class="material-symbols-outlined" style="color:var(--primary);vertical-align:middle;margin-right:4px">article</span>
                                                        <?= e(basename($tpl['path'])) ?>
                                                    </td>
                                                    <td><span class="help-text"><?= count($tpl['variables']) ?> variable(s)</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="footer-actions" style="margin-top:8px">
                                <button class="btn btn-next" type="submit">
                                    <span class="material-symbols-outlined">sync</span> Generer les documents
                                </button>
                            </div>
                            <?php else: ?>
                                <p class="help-text" style="color:var(--warning)">
                                    <span class="material-symbols-outlined">warning</span> Aucun template de cession configure dans config/templates.php.
                                </p>
                            <?php endif; ?>
                        </form>

                        <?php if (!empty($generatedFiles)): ?>
                        <div class="card" style="margin-top:12px;border-color:var(--success)">
                            <h4 style="color:var(--success)"><span class="material-symbols-outlined">check_circle</span> Documents generes</h4>
                            <ul style="margin:8px 0 0;padding-left:1rem">
                            <?php foreach ($generatedFiles as $gf): ?>
                                <li><?= e(basename((string) ($gf['docx'] ?? ($gf['name'] ?? '')))) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($dossierCreated): ?>
            <form method="post" style="margin-top:16px">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="terminer">
                <div class="footer-actions">
                    <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                    <button class="btn btn-next" type="submit">
                        <span class="material-symbols-outlined">check_circle</span> Terminer
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle cedant fields
    document.querySelectorAll('.cedant-type').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var row = this.closest('.cession-part-row');
            if (!row) return;
            row.querySelector('.cedant-existing-fields').style.display = this.value === 'nouveau' ? 'none' : '';
            row.querySelector('.cedant-new-fields').style.display = this.value === 'nouveau' ? '' : 'none';
        });
    });

    // Toggle cessionnaire fields
    document.querySelectorAll('.cessionnaire-type').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var row = this.closest('.cession-part-row');
            if (!row) return;
            row.querySelector('.cessionnaire-existing-fields').style.display = this.value === 'nouveau' ? 'none' : '';
            row.querySelector('.cessionnaire-new-fields').style.display = this.value === 'nouveau' ? '' : 'none';
        });
    });

    // Calculate prix_total
    function calcPrixTotal() {
        var row = this.closest('.cession-part-row');
        if (!row) return;
        var parts = parseFloat(row.querySelector('.parts-cedees-input')?.value.replace(',', '.')) || 0;
        var pu = parseFloat(row.querySelector('.prix-unitaire-input')?.value.replace(',', '.')) || 0;
        var ptInput = row.querySelector('.prix-total-input');
        if (ptInput) ptInput.value = (parts * pu).toFixed(2).replace('.', ',');
    }

    document.querySelectorAll('.parts-cedees-input, .prix-unitaire-input').forEach(function(inp) {
        inp.addEventListener('input', calcPrixTotal);
    });

    // Calculate parts from pourcentage
    document.querySelectorAll('.pourcentage-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            var row = this.closest('.cession-part-row');
            if (!row) return;
            var pct = parseFloat(this.value.replace(',', '.')) || 0;
            var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
            var partsInput = row.querySelector('.parts-cedees-input');
            if (partsInput && pct > 0 && totalParts > 0) {
                partsInput.value = Math.round((pct / 100) * totalParts);
                calcPrixTotal.call(partsInput);
            }
        });
    });

    // Add new part row
    document.getElementById('add-cession-part')?.addEventListener('click', function() {
        var container = document.getElementById('cession-parts-container');
        var index = parseInt(this.dataset.partIndex) || 0;
        var template = container.querySelector('.cession-part-row');
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
        // Reset display
        var cedNew = clone.querySelector('.cedant-new-fields');
        var cedExist = clone.querySelector('.cedant-existing-fields');
        if (cedNew) cedNew.style.display = 'none';
        if (cedExist) cedExist.style.display = '';
        var cessNew = clone.querySelector('.cessionnaire-new-fields');
        var cessExist = clone.querySelector('.cessionnaire-existing-fields');
        if (cessNew) cessNew.style.display = 'none';
        if (cessExist) cessExist.style.display = '';
        // Update part number
        var pn = clone.querySelector('.part-number');
        if (pn) pn.textContent = index + 1;
        container.appendChild(clone);

        // Bind events
        clone.querySelectorAll('.cedant-type').forEach(function(el) {
            el.addEventListener('change', function() {
                var r = this.closest('.cession-part-row');
                if (!r) return;
                r.querySelector('.cedant-existing-fields').style.display = this.value === 'nouveau' ? 'none' : '';
                r.querySelector('.cedant-new-fields').style.display = this.value === 'nouveau' ? '' : 'none';
            });
        });
        clone.querySelectorAll('.cessionnaire-type').forEach(function(el) {
            el.addEventListener('change', function() {
                var r = this.closest('.cession-part-row');
                if (!r) return;
                r.querySelector('.cessionnaire-existing-fields').style.display = this.value === 'nouveau' ? 'none' : '';
                r.querySelector('.cessionnaire-new-fields').style.display = this.value === 'nouveau' ? '' : 'none';
            });
        });
        clone.querySelectorAll('.parts-cedees-input, .prix-unitaire-input').forEach(function(el) {
            el.addEventListener('input', calcPrixTotal);
        });
        clone.querySelectorAll('.pourcentage-input').forEach(function(el) {
            el.addEventListener('input', function() {
                var r = this.closest('.cession-part-row');
                if (!r) return;
                var pct = parseFloat(this.value.replace(',', '.')) || 0;
                var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
                var pi = r.querySelector('.parts-cedees-input');
                if (pi && pct > 0 && totalParts > 0) {
                    pi.value = Math.round((pct / 100) * totalParts);
                    calcPrixTotal.call(pi);
                }
            });
        });
        this.dataset.partIndex = index + 1;
    });

    // Remove part row
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-part');
        if (btn) {
            var row = btn.closest('.cession-part-row');
            if (row && confirm('Supprimer cette ligne de cession ?')) {
                row.remove();
            }
        }
    });

    // Select-all for generation table
    document.getElementById('select-all-wizard')?.addEventListener('click', function(e) {
        e.preventDefault();
        var form = this.closest('form');
        if (!form) return;
        var checkboxes = form.querySelectorAll('.template-check');
        var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
        checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
    });

    // ========== Step 2: Associés dynamic add/remove ==========
    var associeContainer = document.getElementById('cession-associes-container');
    var associeTemplate = document.getElementById('associe-step2-template');

    function reindexAssocies() {
        var cards = associeContainer.querySelectorAll('[data-associe-item]');
        cards.forEach(function(card, idx) {
            var title = card.querySelector('[data-associe-title]');
            if (title) title.textContent = 'Associe ' + (idx + 1);
            card.querySelectorAll('[name]').forEach(function(el) {
                var name = el.getAttribute('name') || '';
                el.name = name.replace(/\[\d+\]/g, '[' + idx + ']');
            });
        });
    }

    document.getElementById('add-associe-step2')?.addEventListener('click', function() {
        if (!associeTemplate) return;
        var clone = associeTemplate.content.cloneNode(true);
        associeContainer.appendChild(clone);
        reindexAssocies();
    });

    associeContainer?.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-remove-associe]');
        if (btn) {
            var card = btn.closest('[data-associe-item]');
            if (card && confirm('Retirer cet associe ?')) {
                card.remove();
                reindexAssocies();
            }
        }
    });

    // ========== Activités autocomplete + add/remove ==========
    var activitesContainer = document.getElementById('activites-container');
    var activiteTemplate = document.getElementById('activite-row-template');
    var allActivitesOptions = <?= json_encode(array_values($activitesOptions)) ?>;

    function setupAutocomplete(wrap) {
        var inp = wrap.querySelector('input');
        var dd = wrap.querySelector('.autocomplete-dropdown');
        if (!inp || !dd) return;

        function normalizeStr(s) { return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }

        function updateDropdown() {
            var val = normalizeStr(inp.value.trim());
            var matches = val === '' ? [] : allActivitesOptions.filter(function(o) { return normalizeStr(o).indexOf(val) !== -1; });
            var exactMatch = matches.some(function(m) { return normalizeStr(m) === val; });

            var selected = [];
            activitesContainer.querySelectorAll('[data-activite-row] input').forEach(function(other) {
                if (other !== inp) {
                    var v = other.value.trim();
                    if (v !== '') selected.push(normalizeStr(v));
                }
            });

            dd.innerHTML = '';
            if (matches.length === 0 && val === '') {
                dd.style.display = 'none';
                return;
            }

            matches.forEach(function(m) {
                if (selected.indexOf(normalizeStr(m)) !== -1) return;
                var item = document.createElement('div');
                item.textContent = m;
                item.style.cssText = 'padding:8px 10px;cursor:pointer;font-size:0.85rem;color:var(--text);background:transparent';
                item.addEventListener('mouseenter', function() { this.style.background = 'rgba(255,255,255,0.06)'; });
                item.addEventListener('mouseleave', function() { this.style.background = ''; });
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    inp.value = m;
                    dd.style.display = 'none';
                    inp.focus();
                    checkAutoAdd();
                });
                dd.appendChild(item);
            });

            if (!exactMatch && val !== '') {
                var divider = document.createElement('div');
                divider.style.cssText = 'border-top:1px solid var(--line);margin:4px 0';
                dd.appendChild(divider);
                var createItem = document.createElement('div');
                createItem.textContent = '+ Creer l activite "' + inp.value.trim() + '"';
                createItem.style.cssText = 'padding:8px 10px;cursor:pointer;font-size:0.85rem;color:var(--primary);font-weight:500;background:transparent';
                createItem.addEventListener('mouseenter', function() { this.style.background = 'rgba(255,255,255,0.06)'; });
                createItem.addEventListener('mouseleave', function() { this.style.background = ''; });
                createItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    var name = inp.value.trim();
                    var form = inp.closest('form');
                    var csrf = form ? form.querySelector('input[name="csrf_token"]') : null;
                    if (!csrf) return;
                    this.textContent = 'Creation en cours...';
                    this.style.pointerEvents = 'none';
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ csrf_token: csrf.value, add_activite_ref: '1', new_activite: name })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            allActivitesOptions.push(data.value);
                            inp.value = data.value;
                            dd.style.display = 'none';
                            checkAutoAdd();
                        }
                    })
                    .catch(function() {});
                });
                dd.appendChild(createItem);
            }

            dd.style.display = dd.children.length > 0 ? 'block' : 'none';
        }

        function checkAutoAdd() {
            var rows = activitesContainer.querySelectorAll('[data-activite-row]');
            var lastRow = rows[rows.length - 1];
            var lastInp = lastRow ? lastRow.querySelector('input') : null;
            if (lastInp && lastInp === inp && inp.value.trim() !== '') {
                addActiviteRow();
            }
        }

        var debounceTimer;
        inp.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updateDropdown, 80);
        });
        inp.addEventListener('focus', function() {
            if (inp.value.trim() !== '') updateDropdown();
        });
        inp.addEventListener('blur', function() {
            setTimeout(function() { dd.style.display = 'none'; }, 200);
        });
        inp.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') dd.style.display = 'none';
            if (e.key === 'Enter' && dd.style.display === 'block') {
                e.preventDefault();
                var first = dd.querySelector('div');
                if (first) { first.click(); }
            }
        });
    }

    function addActiviteRow(value) {
        if (!activiteTemplate) return;
        var clone = activiteTemplate.content.cloneNode(true);
        var newRow = clone.querySelector('[data-activite-row]');
        activitesContainer.appendChild(clone);
        if (value) {
            var inp = newRow.querySelector('input');
            if (inp) inp.value = value;
        }
        var wrap = newRow.querySelector('.autocomplete-wrap');
        if (wrap) setupAutocomplete(wrap);
    }

    document.getElementById('add-activite-row')?.addEventListener('click', function() { addActiviteRow(''); });

    // Init existing rows
    activitesContainer?.querySelectorAll('.autocomplete-wrap').forEach(setupAutocomplete);

    // Remove row
    activitesContainer?.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-remove-activite]');
        if (btn) {
            var rows = activitesContainer.querySelectorAll('[data-activite-row]');
            if (rows.length <= 1) return;
            var row = btn.closest('[data-activite-row]');
            if (row) row.remove();
        }
    });
});
</script>
