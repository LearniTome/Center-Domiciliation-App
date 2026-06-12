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
$step = max(0, min(4, (int) ($_GET['step'] ?? 0)));

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
            $sous = $_POST['mode_nouvelle_sous'] ?? '';
            if (!in_array($sous, ['associe_existant', 'nouvel_associe'], true)) {
                set_flash('error', 'Veuillez choisir une option pour la nouvelle societe.');
                redirect_to('cession', ['step' => 0]);
            }
            $wizard['mode'] = 'nouvelle';
            $wizard['mode_nouvelle_sous'] = $sous;
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
                'societe_capital' => (string) ($_POST['societe_capital'] ?? ''),
                'societe_part_social' => (string) ($_POST['societe_part_social'] ?? ''),
                'societe_valeur_nominale' => (string) ($_POST['societe_valeur_nominale'] ?? ''),
                'societe_adresse_siege' => trim((string) ($_POST['societe_adresse_siege'] ?? '')),
                'societe_ville' => trim((string) ($_POST['societe_ville'] ?? '')),
                'societe_tribunal' => trim((string) ($_POST['societe_tribunal'] ?? '')),
                'societe_email' => trim((string) ($_POST['societe_email'] ?? '')),
                'societe_telephone' => trim((string) ($_POST['societe_telephone'] ?? '')),
                'societe_activites_statuts' => trim((string) ($_POST['societe_activites_statuts'] ?? '')),
                'societe_activites_ompic' => trim((string) ($_POST['societe_activites_ompic'] ?? '')),
            ];

            // Save associé info if mode_nouvelle_sous = associe_existant
            if ($wizard['mode_nouvelle_sous'] === 'associe_existant') {
                $associeId = (int) ($_POST['associe_existant_id'] ?? 0);
                if ($associeId <= 0) {
                    set_flash('error', 'Veuillez selectionner un associe existant.');
                    redirect_to('cession', ['step' => 1]);
                }
                $wizard['associes'] = [['associe_existant_id' => $associeId]];
            } else {
                $wizard['associes'] = [[
                    'associe_civilite' => trim((string) ($_POST['associe_civilite'] ?? 'M.')),
                    'associe_nom_complet' => trim((string) ($_POST['associe_nom_complet'] ?? '')),
                    'associe_cin' => trim((string) ($_POST['associe_cin'] ?? '')),
                    'associe_date_naissance' => trim((string) ($_POST['associe_date_naissance'] ?? '')),
                    'associe_lieu_naissance' => trim((string) ($_POST['associe_lieu_naissance'] ?? '')),
                    'associe_nationalite' => trim((string) ($_POST['associe_nationalite'] ?? '')),
                    'associe_adresse' => trim((string) ($_POST['associe_adresse'] ?? '')),
                    'associe_telephone' => trim((string) ($_POST['associe_telephone'] ?? '')),
                    'associe_email' => trim((string) ($_POST['associe_email'] ?? '')),
                    'associe_qualite' => trim((string) ($_POST['associe_qualite'] ?? 'Gerant')),
                    'associe_parts' => (string) ($_POST['associe_parts'] ?? ''),
                    'associe_capital_detenu' => (string) ($_POST['associe_capital_detenu'] ?? ''),
                    'associe_est_gerant' => !empty($_POST['associe_est_gerant']) ? '1' : '0',
                ]];
            }

            $wizard['parts'] = [];
            redirect_to('cession', ['step' => 2]);
        }

        set_flash('error', 'Mode de cession non defini.');
        redirect_to('cession', ['step' => 0]);
    }

    // Step 2: Cession parts
    if ($step === 2) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 1]);
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
            redirect_to('cession', ['step' => 2]);
        }
        redirect_to('cession', ['step' => 3]);
    }

    // Step 3: Recap + Upload
    if ($step === 3) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 2]);
        }

        $uploadDir = __DIR__ . '/../uploads';
        $tmpDir = $uploadDir . '/tmp/' . session_id();
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

        $uploadedDocs = $wizard['uploaded_docs'] ?? [];

        if (!empty($_FILES['certificat_negatif']['name']) && $_FILES['certificat_negatif']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['certificat_negatif']['name'], PATHINFO_EXTENSION);
            $stored = 'cession_cn_' . date('Ymd_His') . '.' . $ext;
            $dest = $tmpDir . '/' . $stored;
            if (move_uploaded_file($_FILES['certificat_negatif']['tmp_name'], $dest)) {
                $uploadedDocs['certificat_negatif'] = [
                    'original' => $_FILES['certificat_negatif']['name'],
                    'stored' => $stored,
                    'path' => $dest,
                    'taille_ko' => round(filesize($dest) / 1024, 1),
                ];
            }
        }

        if (!empty($_FILES['cin_gerants']['name'][0]) && is_array($_FILES['cin_gerants']['name'])) {
            $files = $_FILES['cin_gerants'];
            $associeIndexes = $_POST['cin_associe_index'] ?? [];
            foreach ($files['name'] as $idx => $name) {
                if ($name === '' || $files['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $stored = 'cession_cin_' . $idx . '_' . date('Ymd_His') . '.' . $ext;
                $dest = $tmpDir . '/' . $stored;
                if (move_uploaded_file($files['tmp_name'][$idx], $dest)) {
                    $associeIdx = $associeIndexes[$idx] ?? $idx;
                    $uploadedDocs['cin_gerants'][$associeIdx] = [
                        'original' => $name,
                        'stored' => $stored,
                        'path' => $dest,
                        'taille_ko' => round(filesize($dest) / 1024, 1),
                    ];
                }
            }
        }

        $wizard['uploaded_docs'] = $uploadedDocs;
        redirect_to('cession', ['step' => 4]);
    }

    // Step 4: Generation
    if ($step === 4) {
        $navAction = $_POST['nav_action'] ?? 'generate';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 3]);
        }

        if ($navAction === 'create_dossier') {
            if (!(($pdo ?? null) instanceof PDO)) {
                set_flash('error', 'Connexion MySQL indisponible.');
                redirect_to('cession', ['step' => 4]);
            }

            try {
                $pdo->beginTransaction();

                // Create société if new
                if ($wizard['mode'] === 'nouvelle' && $wizard['societe_id'] <= 0) {
                    $soc = $wizard['societe'];
                    $stmt = $pdo->prepare('INSERT INTO societes (societe_raison_sociale, societe_forme_juridique, societe_source, societe_ice, societe_rc, societe_if, societe_capital, societe_part_social, societe_valeur_nominale, societe_adresse_siege, societe_ville, societe_tribunal, societe_email, societe_telephone, societe_activites_statuts, created_by) VALUES (:raison, :forme, :source, :ice, :rc, :ifis, :capital, :parts, :vnom, :adr, :ville, :trib, :email, :tel, :activites, :created_by)');
                    $stmt->execute([
                        'raison' => $soc['societe_raison_sociale'] ?? '',
                        'forme' => $soc['societe_forme_juridique'] ?? '',
                        'source' => 'cession',
                        'ice' => $soc['societe_ice'] ?? '',
                        'rc' => $soc['societe_rc'] ?? '',
                        'ifis' => $soc['societe_if'] ?? '',
                        'capital' => !empty($soc['societe_capital']) ? parse_money($soc['societe_capital']) : null,
                        'parts' => !empty($soc['societe_part_social']) ? (int) $soc['societe_part_social'] : null,
                        'vnom' => !empty($soc['societe_valeur_nominale']) ? parse_money($soc['societe_valeur_nominale']) : null,
                        'adr' => $soc['societe_adresse_siege'] ?? '',
                        'ville' => $soc['societe_ville'] ?? '',
                        'trib' => $soc['societe_tribunal'] ?? '',
                        'email' => $soc['societe_email'] ?? '',
                        'tel' => $soc['societe_telephone'] ?? '',
                        'activites' => $soc['societe_activites_statuts'] ?? '',
                        'created_by' => ($user = current_user()) ? (int) $user['id'] : null,
                    ]);
                    $newSocieteId = (int) $pdo->lastInsertId();
                    $wizard['societe_id'] = $newSocieteId;

                    // Create associé
                    if ($wizard['mode_nouvelle_sous'] === 'associe_existant' && !empty($wizard['associes'][0]['associe_existant_id'])) {
                        // Use existing associé
                    } elseif (!empty($wizard['associes'][0])) {
                        $a = $wizard['associes'][0];
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
                redirect_to('cession', ['step' => 4]);
            }
            redirect_to('cession', ['step' => 4]);
        }

        // Generate documents
        if ($navAction === 'generate') {
            if (!isset($wizard['cession_id']) || $wizard['cession_id'] <= 0) {
                set_flash('error', 'Creez d abord le dossier avant de generer les documents.');
                redirect_to('cession', ['step' => 4]);
            }

            require_once __DIR__ . '/../src/TemplateAnalyzer.php';
            require_once __DIR__ . '/../src/DocumentRenderer.php';
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }

            $selectedDocs = $_POST['doc_types'] ?? [];
            if (empty($selectedDocs)) {
                set_flash('error', 'Selectionnez au moins un type de document.');
                redirect_to('cession', ['step' => 4]);
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
            redirect_to('cession', ['step' => 4]);
        }

        if ($navAction === 'terminer') {
            $societeId = $wizard['societe_id'] ?? 0;
            $cessionId = $wizard['cession_id'] ?? 0;
            unset($_SESSION['cession_wizard'], $_SESSION['_cession_loaded']);
            redirect_to('cession_dossier', ['id' => $cessionId]);
        }
    }
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

$stepLabels = ['Mode', 'Societe', 'Cession', 'Recapitulatif', 'Generation'];
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

        <div class="wizard-steps" id="wizard-steps-top">
            <?php for ($s = 0; $s <= 4; $s++): ?>
                <div class="wizard-step <?= $step > $s ? 'done' : ($step === $s ? 'active' : 'waiting') ?>">
                    <strong>Etape <?= $s + 1 ?></strong>
                    <span><?= $stepLabels[$s] ?></span>
                </div>
            <?php endfor; ?>
        </div>

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
            <div id="sous-options" style="display:none">
                <p class="help-text" style="margin-bottom:8px">Avec quel associe ?</p>
                <div class="grid two">
                    <label class="card choice-card" data-sous="associe_existant">
                        <input type="radio" name="mode_nouvelle_sous" value="associe_existant" id="sous-associe-exist" style="display:none">
                        <span class="material-symbols-outlined" style="font-size:1.5rem">person_search</span>
                        <h4 style="margin:4px 0">Associe existant</h4>
                        <p class="help-text">Lier a un associe deja enregistre</p>
                    </label>
                    <label class="card choice-card" data-sous="nouvel_associe">
                        <input type="radio" name="mode_nouvelle_sous" value="nouvel_associe" id="sous-nouvel-associe" style="display:none">
                        <span class="material-symbols-outlined" style="font-size:1.5rem">person_add</span>
                        <h4 style="margin:4px 0">Nouvel associe</h4>
                        <p class="help-text">Creer un nouvel associe</p>
                    </label>
                </div>
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
                    var isSous = this.closest('#sous-options') !== null;
                    var group = isSous ? this.closest('#sous-options').querySelectorAll('.choice-card') : document.querySelectorAll('#mode-choice-grid .choice-card');
                    group.forEach(function(x){ x.style.borderColor = 'var(--line)'; });
                    this.style.borderColor = isSous ? 'var(--success)' : (this.dataset.mode === 'nouvelle' ? 'var(--success)' : 'var(--primary)');
                    if (!isSous) {
                        document.getElementById('sous-options').style.display = this.dataset.mode === 'nouvelle' ? 'block' : 'none';
                    }
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
            <div class="table-actions">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <?php elseif ($wizard['mode'] === 'nouvelle'): ?>
        <!-- Mode nouvelle: full société form (like creation step 1) -->
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <div class="section-header">
                <div><h2>Informations sur la societe</h2><p class="help-text">Saisissez les details de la nouvelle societe</p></div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="societe_raison_sociale">Raison sociale *</label>
                    <input type="text" name="societe_raison_sociale" id="societe_raison_sociale" required value="<?= e($wizard['societe']['societe_raison_sociale'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="societe_forme_juridique">Forme juridique</label>
                    <select name="societe_forme_juridique" id="societe_forme_juridique">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($formesJuridiques as $fj): ?>
                            <option value="<?= e($fj['forme_juridique']) ?>" <?= ($wizard['societe']['societe_forme_juridique'] ?? '') === $fj['forme_juridique'] ? 'selected' : '' ?>><?= e($fj['forme_juridique']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="societe_ice">ICE</label>
                    <input type="text" name="societe_ice" id="societe_ice" value="<?= e($wizard['societe']['societe_ice'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="societe_rc">RC</label>
                    <input type="text" name="societe_rc" id="societe_rc" value="<?= e($wizard['societe']['societe_rc'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="societe_if">IF</label>
                    <input type="text" name="societe_if" id="societe_if" value="<?= e($wizard['societe']['societe_if'] ?? '') ?>">
                </div>
                <div class="field"></div>
                <div class="field">
                    <label for="societe_capital">Capital (DH)</label>
                    <input type="text" name="societe_capital" id="societe_capital" value="<?= e($wizard['societe']['societe_capital'] ?? '') ?>" placeholder="100000">
                </div>
                <div class="field">
                    <label for="societe_part_social">Nombre de parts</label>
                    <input type="number" name="societe_part_social" id="societe_part_social" value="<?= e($wizard['societe']['societe_part_social'] ?? '') ?>" placeholder="100">
                </div>
                <div class="field">
                    <label for="societe_valeur_nominale">Valeur nominale (DH)</label>
                    <input type="text" name="societe_valeur_nominale" id="societe_valeur_nominale" value="<?= e($wizard['societe']['societe_valeur_nominale'] ?? '') ?>" placeholder="1000">
                </div>
                <div class="field">
                    <label for="societe_ville">Ville</label>
                    <select name="societe_ville" id="societe_ville">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= e($v) ?>" <?= ($wizard['societe']['societe_ville'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="societe_tribunal">Tribunal</label>
                    <select name="societe_tribunal" id="societe_tribunal">
                        <option value="">-- Selectionnez --</option>
                        <?php foreach ($tribunaux as $t): ?>
                            <option value="<?= e($t['tribunal']) ?>" <?= ($wizard['societe']['societe_tribunal'] ?? '') === $t['tribunal'] ? 'selected' : '' ?>><?= e($t['tribunal']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="grid-column:span 2">
                    <label for="societe_adresse_siege">Adresse du siege</label>
                    <textarea name="societe_adresse_siege" id="societe_adresse_siege" rows="2"><?= e($wizard['societe']['societe_adresse_siege'] ?? '') ?></textarea>
                </div>
                <div class="field">
                    <label for="societe_email">Email</label>
                    <input type="email" name="societe_email" id="societe_email" value="<?= e($wizard['societe']['societe_email'] ?? '') ?>" placeholder="contact@exemple.com">
                </div>
                <div class="field">
                    <label for="societe_telephone">Telephone</label>
                    <input type="text" name="societe_telephone" id="societe_telephone" value="<?= e($wizard['societe']['societe_telephone'] ?? '') ?>" placeholder="05XX-XXXXXX">
                </div>
                <div class="field" style="grid-column:span 2">
                    <label for="societe_activites_statuts">Activites (statuts)</label>
                    <textarea name="societe_activites_statuts" id="societe_activites_statuts" rows="2" placeholder="Objet social de la societe..."><?= e($wizard['societe']['societe_activites_statuts'] ?? '') ?></textarea>
                </div>
            </div>

            <?php if ($wizard['mode_nouvelle_sous'] === 'associe_existant'): ?>
            <!-- Select existing associé -->
            <div class="card">
                <div class="section-header"><div><h3>Associe existant</h3><p class="help-text">Selectionnez l associe a lier a cette societe</p></div></div>
                <div class="field">
                    <select name="associe_existant_id">
                        <option value="">-- Selectionnez un associe --</option>
                        <?php
                        $allAssocies = [];
                        if (($pdo ?? null) instanceof PDO) {
                            $stmt = $pdo->query('SELECT id, associe_nom_complet, associe_cin FROM associes ORDER BY associe_nom_complet');
                            $allAssocies = $stmt->fetchAll();
                        }
                        foreach ($allAssocies as $aa): ?>
                            <option value="<?= (int) $aa['id'] ?>" <?= ($wizard['associes'][0]['associe_existant_id'] ?? 0) === (int) $aa['id'] ? 'selected' : '' ?>><?= e($aa['associe_nom_complet']) ?> (<?= e($aa['associe_cin'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php elseif ($wizard['mode_nouvelle_sous'] === 'nouvel_associe'): ?>
            <!-- New associé form -->
            <div class="card">
                <div class="section-header"><div><h3>Nouvel associe</h3><p class="help-text">Saisissez les informations du gerant/fondateur</p></div></div>
                <div class="form-grid">
                    <div class="field">
                        <label for="associe_civilite">Civilite</label>
                        <select name="associe_civilite" id="associe_civilite">
                            <option value="M." <?= ($wizard['associes'][0]['associe_civilite'] ?? 'M.') === 'M.' ? 'selected' : '' ?>>M.</option>
                            <option value="Mme" <?= ($wizard['associes'][0]['associe_civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                            <option value="Mlle" <?= ($wizard['associes'][0]['associe_civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="associe_nom_complet">Nom complet *</label>
                        <input type="text" name="associe_nom_complet" id="associe_nom_complet" required value="<?= e($wizard['associes'][0]['associe_nom_complet'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="associe_cin">CIN</label>
                        <input type="text" name="associe_cin" id="associe_cin" value="<?= e($wizard['associes'][0]['associe_cin'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="associe_date_naissance">Date de naissance</label>
                        <input type="date" name="associe_date_naissance" id="associe_date_naissance" value="<?= e($wizard['associes'][0]['associe_date_naissance'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="associe_lieu_naissance">Lieu de naissance</label>
                        <select name="associe_lieu_naissance" id="associe_lieu_naissance">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($lieuxNaissanceOptions as $ln): ?>
                                <option value="<?= e($ln) ?>" <?= ($wizard['associes'][0]['associe_lieu_naissance'] ?? '') === $ln ? 'selected' : '' ?>><?= e($ln) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="associe_nationalite">Nationalite</label>
                        <select name="associe_nationalite" id="associe_nationalite">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($nationalitesOptions as $nat): ?>
                                <option value="<?= e($nat) ?>" <?= ($wizard['associes'][0]['associe_nationalite'] ?? '') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" style="grid-column:span 2">
                        <label for="associe_adresse">Adresse</label>
                        <textarea name="associe_adresse" id="associe_adresse" rows="2"><?= e($wizard['associes'][0]['associe_adresse'] ?? '') ?></textarea>
                    </div>
                    <div class="field">
                        <label for="associe_telephone">Telephone</label>
                        <input type="text" name="associe_telephone" id="associe_telephone" value="<?= e($wizard['associes'][0]['associe_telephone'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="associe_email">Email</label>
                        <input type="email" name="associe_email" id="associe_email" value="<?= e($wizard['associes'][0]['associe_email'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="associe_qualite">Qualite</label>
                        <select name="associe_qualite" id="associe_qualite">
                            <option value="">-- Selectionnez --</option>
                            <?php foreach ($qualitesAssocieOptions as $qa): ?>
                                <option value="<?= e($qa) ?>" <?= ($wizard['associes'][0]['associe_qualite'] ?? '') === $qa ? 'selected' : '' ?>><?= e($qa) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="associe_parts">Nombre de parts</label>
                        <input type="number" name="associe_parts" id="associe_parts" value="<?= e($wizard['associes'][0]['associe_parts'] ?? '') ?>" placeholder="100">
                    </div>
                    <div class="field">
                        <label>
                            <input type="checkbox" name="associe_est_gerant" value="1" <?= ($wizard['associes'][0]['associe_est_gerant'] ?? '0') === '1' ? 'checked' : '' ?>>
                            Gerant
                        </label>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="footer-actions">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 0])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>
        <?php endif; ?>

        <!-- Step 2: Cession parts -->
        <?php elseif ($step === 2): ?>
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
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <!-- Step 3: Recap + Upload -->
        <?php elseif ($step === 3): ?>
        <div class="stack">
            <!-- Recap card -->
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

            <!-- Upload section (like creation step 5) -->
            <?php $uploadedDocs = $wizard['uploaded_docs'] ?? []; ?>
            <?php $hasCn = isset($uploadedDocs['certificat_negatif']); ?>
            <?php $hasCin = isset($uploadedDocs['cin_gerants']); ?>

            <form method="post" class="stack" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="next">

                <article class="card" style="border-color:<?= $hasCn ? 'var(--success)' : 'var(--danger)' ?>">
                    <div class="section-header">
                        <div>
                            <h3><span class="material-symbols-outlined">verified</span> Certificat Negatif</h3>
                            <p class="help-text">Document delivre par l OMPIC (format PDF).</p>
                        </div>
                        <?php if ($hasCn): ?>
                            <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined">check_circle</span> Telecharge</span>
                        <?php else: ?>
                            <span class="step-badge" style="color:var(--danger)"><span class="material-symbols-outlined">cancel</span> Manquant</span>
                        <?php endif; ?>
                    </div>
                    <label class="field" style="margin-top:8px">
                        <span>Fichier</span>
                        <input type="file" name="certificat_negatif" accept=".pdf" <?= $hasCn ? '' : '' ?>>
                        <?php if ($hasCn): ?>
                            <small style="color:var(--success)"><?= e($uploadedDocs['certificat_negatif']['original']) ?> deja uploade.</small>
                        <?php endif; ?>
                    </label>
                </article>

                <article class="card" style="border-color:<?= !empty($gerantsList) ? ($hasCin ? 'var(--success)' : 'var(--danger)') : 'var(--line)' ?>">
                    <div class="section-header">
                        <div>
                            <h3><span class="material-symbols-outlined">badge</span> CIN des Gerants</h3>
                            <p class="help-text">Carte d identite nationale des gerants.</p>
                        </div>
                        <?php if (empty($gerantsList)): ?>
                            <span class="step-badge"><span class="material-symbols-outlined">info</span> Aucun gerant</span>
                        <?php elseif ($hasCin): ?>
                            <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined">check_circle</span> Telecharge(s)</span>
                        <?php else: ?>
                            <span class="step-badge" style="color:var(--danger)"><span class="material-symbols-outlined">cancel</span> Manquant(s)</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($gerantsList)): ?>
                        <div class="stack" style="margin-top:8px;gap:12px">
                        <?php foreach ($gerantsList as $gIdx => $gerant): ?>
                            <label class="field">
                                <span><?= e('CIN de ' . ($gerant['associe_nom_complet'] ?? 'Gerant ' . ($gIdx + 1))) ?></span>
                                <input type="file" name="cin_gerants[]" accept=".pdf,.jpg,.jpeg,.png">
                                <input type="hidden" name="cin_associe_index[]" value="<?= $gIdx ?>">
                                <?php if (isset($uploadedDocs['cin_gerants'][$gIdx])): ?>
                                    <small style="color:var(--success)"><?= e($uploadedDocs['cin_gerants'][$gIdx]['original']) ?> deja uploade.</small>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="help-text" style="margin-top:8px;color:var(--warning)">
                            <span class="material-symbols-outlined">warning</span> Aucun gerant designe. Passez outre si non requis.
                        </p>
                    <?php endif; ?>
                </article>

                <div class="footer-actions" style="margin-top:12px">
                    <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
                </div>
            </form>
        </div>

        <!-- Step 4: Generation -->
        <?php elseif ($step === 4): ?>
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
                    <h2>Etape 4 — Generation des documents</h2>
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
});
</script>
