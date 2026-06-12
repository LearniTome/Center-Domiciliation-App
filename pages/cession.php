<?php

declare(strict_types=1);

$editingId = (int) ($_GET['id'] ?? 0);

// Initialisation ou chargement du wizard
if (!isset($_SESSION['cession_wizard'])) {
    $_SESSION['cession_wizard'] = [
        'societe_id' => 0,
        'cession_date' => date('Y-m-d'),
        'cession_motif' => '',
        'cession_status' => 'brouillon',
        'parts' => [],
        'editing_id' => $editingId,
    ];
}
$wizard = &$_SESSION['cession_wizard'];

// Si édition, charger depuis la base
if ($editingId > 0 && !isset($_SESSION['_cession_loaded'])) {
    if (($pdo ?? null) instanceof PDO) {
        $stmt = $pdo->prepare('SELECT * FROM cessions WHERE id = :id');
        $stmt->execute(['id' => $editingId]);
        $dbCession = $stmt->fetch();
        if ($dbCession) {
            $wizard['societe_id'] = (int) $dbCession['societe_id'];
            $wizard['cession_date'] = $dbCession['cession_date'] ?? date('Y-m-d');
            $wizard['cession_motif'] = $dbCession['cession_motif'] ?? '';
            $wizard['cession_status'] = $dbCession['cession_status'] ?? 'brouillon';
            $wizard['editing_id'] = $editingId;

            $stmt2 = $pdo->prepare('SELECT * FROM cession_parts WHERE cession_id = :id ORDER BY id');
            $stmt2->execute(['id' => $editingId]);
            $dbParts = $stmt2->fetchAll();
            $wizard['parts'] = [];
            foreach ($dbParts as $p) {
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

$step = max(1, min(3, (int) ($_GET['step'] ?? 1)));

// Reference data
$societesList = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, societe_raison_sociale, societe_dossier, societe_forme_juridique, societe_capital, societe_part_social FROM societes ORDER BY societe_raison_sociale');
    $societesList = $stmt->fetchAll();
}
$nationalitesOptions = fetch_reference_options($pdo ?? null, 'ref_nationalites', 'nationalite');
$lieuxNaissanceOptions = fetch_reference_options($pdo ?? null, 'ref_lieux_naissance', 'lieu_naissance');
$formesJuridiques = [];
$tribunaux = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT * FROM ref_formes_juridiques ORDER BY forme_juridique');
    $formesJuridiques = $stmt->fetchAll();
    $stmt = $pdo->query('SELECT * FROM ref_tribunaux ORDER BY tribunal');
    $tribunaux = $stmt->fetchAll();
}
$villes = fetch_reference_options($pdo ?? null, 'ref_villes', 'ville');

// Associes de la société sélectionnée
$selectedAssocies = [];
if ($wizard['societe_id'] > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('SELECT * FROM associes WHERE societe_id = :id ORDER BY associe_nom_complet');
    $stmt->execute(['id' => $wizard['societe_id']]);
    $selectedAssocies = $stmt->fetchAll();
}

// POST handling
if (is_post()) {
    verify_csrf();

    // Création rapide d'une société
    if (isset($_POST['action']) && $_POST['action'] === 'create_societe') {
        $raison = trim((string) ($_POST['raison_sociale'] ?? ''));
        $forme = trim((string) ($_POST['forme_juridique'] ?? ''));
        $capital = money_value($_POST, 'capital');
        $parts = int_value($_POST, 'part_social');
        $ville = trim((string) ($_POST['ville'] ?? ''));
        $adresse = trim((string) ($_POST['adresse'] ?? ''));

        if ($raison === '') {
            set_flash('error', 'Veuillez saisir la raison sociale.');
            redirect_to('cession', ['step' => 1]);
        }

        try {
            $user = current_user();
            $ice = trim((string) ($_POST['ice'] ?? ''));
            $rc = trim((string) ($_POST['rc'] ?? ''));
            $ifiscal = trim((string) ($_POST['ifiscal'] ?? ''));
            $tribunal = trim((string) ($_POST['tribunal'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $telephone = trim((string) ($_POST['telephone'] ?? ''));

            $stmt = $pdo->prepare('INSERT INTO societes (societe_raison_sociale, societe_forme_juridique, societe_capital, societe_part_social, societe_ville, societe_adresse, societe_ice, societe_rc, societe_if, societe_tribunal, societe_email, societe_telephone, created_by) VALUES (:raison, :forme, :capital, :parts, :ville, :adresse, :ice, :rc, :ifiscal, :tribunal, :email, :telephone, :created_by)');
            $stmt->execute([
                'raison' => $raison,
                'forme' => $forme ?: null,
                'capital' => $capital,
                'parts' => $parts,
                'ville' => $ville ?: null,
                'adresse' => $adresse ?: null,
                'ice' => $ice ?: null,
                'rc' => $rc ?: null,
                'ifiscal' => $ifiscal ?: null,
                'tribunal' => $tribunal ?: null,
                'email' => $email ?: null,
                'telephone' => $telephone ?: null,
                'created_by' => $user ? (int) $user['id'] : null,
            ]);
            $newId = (int) $pdo->lastInsertId();
            $wizard['societe_id'] = $newId;
            set_flash('success', 'Societe creee avec succes.');
        } catch (Throwable $e) {
            set_flash('error', 'Erreur lors de la creation de la societe.');
        }
        redirect_to('cession', ['step' => 1]);
    }

    // Step 1: Société
    if ($step === 1) {
        $wizard['societe_id'] = (int) ($_POST['societe_id'] ?? 0);
        $wizard['parts'] = [];
        if ($wizard['societe_id'] > 0) {
            redirect_to('cession', ['step' => 2]);
        }
        redirect_to('cession', ['step' => 1]);
    }

    // Step 2: Parts configuration
    if ($step === 2) {
        $wizard['cession_date'] = field_value($_POST, 'cession_date');
        $wizard['cession_motif'] = field_value($_POST, 'cession_motif');

        $cedantTypes = $_POST['cedant_type'] ?? [];
        $cedantIds = $_POST['cedant_associe_id'] ?? [];
        $cedantNoms = $_POST['cedant_nom_complet'] ?? [];
        $cedantCins = $_POST['cedant_cin'] ?? [];
        $cessionnaireTypes = $_POST['cessionnaire_type'] ?? [];
        $cessionnaireIds = $_POST['cessionnaire_associe_id'] ?? [];
        $cessionnaireNoms = $_POST['cessionnaire_nom_complet'] ?? [];
        $cessionnaireCins = $_POST['cessionnaire_cin'] ?? [];
        $cessionnaireCivilites = $_POST['cessionnaire_civilite'] ?? [];
        $cessionnaireDates = $_POST['cessionnaire_date_naissance'] ?? [];
        $cessionnaireLieux = $_POST['cessionnaire_lieu_naissance'] ?? [];
        $cessionnaireNationalites = $_POST['cessionnaire_nationalite'] ?? [];
        $cessionnaireAdresses = $_POST['cessionnaire_adresse'] ?? [];
        $partPourcentages = $_POST['pourcentage'] ?? [];
        $partsCedees = $_POST['parts_cedees'] ?? [];
        $prixUnitaires = $_POST['prix_unitaire'] ?? [];
        $prixTotaux = $_POST['prix_total'] ?? [];
        $nommerGerant = $_POST['nommer_gerant'] ?? [];

        // Total parts société pour le calcul % → parts
        $totalSocieteParts = 0;
        if ($wizard['societe_id'] > 0 && ($pdo ?? null) instanceof PDO) {
            $stmt = $pdo->prepare('SELECT societe_part_social FROM societes WHERE id = :id');
            $stmt->execute(['id' => $wizard['societe_id']]);
            $totalSocieteParts = (int) ($stmt->fetchColumn() ?: 0);
        }

        $wizard['parts'] = [];
        $count = max(count($cedantNoms), count($cessionnaireNoms), count($partsCedees));
        for ($i = 0; $i < $count; $i++) {
            $cedType = $cedantTypes[$i] ?? 'existant';
            $cedAssocieId = (int) ($cedantIds[$i] ?? 0);
            $cedNom = trim((string) ($cedantNoms[$i] ?? ''));
            $cedCin = trim((string) ($cedantCins[$i] ?? ''));
            if ($cedType === 'existant' && $cedAssocieId > 0 && $cedNom === '') {
                $assoc = fetch_record($pdo, 'associes', $cedAssocieId);
                if ($assoc) {
                    $cedNom = $assoc['associe_nom_complet'] ?? '';
                    $cedCin = $assoc['associe_cin'] ?? '';
                }
            }

            $cessType = $cessionnaireTypes[$i] ?? 'existant';
            $cessAssocieId = (int) ($cessionnaireIds[$i] ?? 0);
            $cessNom = trim((string) ($cessionnaireNoms[$i] ?? ''));
            $cessCin = trim((string) ($cessionnaireCins[$i] ?? ''));
            if ($cessType === 'existant' && $cessAssocieId > 0 && $cessNom === '') {
                $assoc = fetch_record($pdo, 'associes', $cessAssocieId);
                if ($assoc) {
                    $cessNom = $assoc['associe_nom_complet'] ?? '';
                    $cessCin = $assoc['associe_cin'] ?? '';
                }
            }

            // Calcul des parts: priorité au %, sinon parts directes
            $pourcentage = money_value(['val' => $partPourcentages[$i] ?? '0'], 'val');
            $parts = (int) ($partsCedees[$i] ?? 0);
            if ($pourcentage > 0 && $totalSocieteParts > 0) {
                $parts = (int) round(($pourcentage / 100) * $totalSocieteParts);
            }

            if ($cedNom === '' || $cessNom === '' || $parts <= 0) {
                continue;
            }

            $pu = money_value(['val' => $prixUnitaires[$i] ?? '0'], 'val');
            $pt = money_value(['val' => $prixTotaux[$i] ?? '0'], 'val');
            if ($pt === null || $pt <= 0) {
                $pt = $pu * $parts;
            }

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
                'pourcentage' => $pourcentage > 0 ? $pourcentage : null,
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

    // Step 3: Finalisation
    if ($step === 3) {
        require_once __DIR__ . '/../src/DocumentRenderer.php';
        $action = $_POST['action'] ?? 'save';

        if ($action === 'back') {
            redirect_to('cession', ['step' => 2]);
        }

        $societe = null;
        if (($pdo ?? null) instanceof PDO && $wizard['societe_id'] > 0) {
            $societe = fetch_record($pdo, 'societes', $wizard['societe_id']);
        }

        if (!$societe) {
            set_flash('error', 'Societe introuvable.');
            redirect_to('cession', ['step' => 1]);
        }

        $capitalAvant = (float) ($societe['societe_capital'] ?? 0);
        $partsAvant = (int) ($societe['societe_part_social'] ?? 0);
        $totalPartsCedees = 0;
        $totalPrix = 0;
        foreach ($wizard['parts'] as $p) {
            $totalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
            $totalPrix += (float) ($p['prix_total'] ?? 0);
        }

        if ($editingId > 0) {
            $cessionId = $editingId;
            $stmt = $pdo->prepare('UPDATE cessions SET societe_id = :societe_id, cession_date = :date, cession_motif = :motif, cession_status = :status, capital_avant = :capital, parts_avant = :parts WHERE id = :id');
            $stmt->execute([
                'societe_id' => $wizard['societe_id'],
                'date' => $wizard['cession_date'],
                'motif' => $wizard['cession_motif'],
                'status' => $wizard['cession_status'],
                'capital' => $capitalAvant,
                'parts' => $partsAvant,
                'id' => $cessionId,
            ]);
            $stmt = $pdo->prepare('DELETE FROM cession_parts WHERE cession_id = :id');
            $stmt->execute(['id' => $cessionId]);
        } else {
            $dossierNum = 1;
            $currentYear = date('Y');
            $maxNum = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(cession_dossier, '-', -1) AS UNSIGNED)), 0) FROM cessions WHERE cession_dossier LIKE 'CES-{$currentYear}-%'")->fetchColumn();
            $dossierNum = (int) $maxNum + 1;
            $dossier = sprintf('CES-%s-%03d', $currentYear, $dossierNum);

            $stmt = $pdo->prepare('INSERT INTO cessions (societe_id, cession_dossier, cession_date, cession_motif, cession_status, capital_avant, parts_avant, created_by) VALUES (:societe_id, :dossier, :date, :motif, :status, :capital, :parts, :created_by)');
            $stmt->execute([
                'societe_id' => $wizard['societe_id'],
                'dossier' => $dossier,
                'date' => $wizard['cession_date'],
                'motif' => $wizard['cession_motif'],
                'status' => $wizard['cession_status'],
                'capital' => $capitalAvant,
                'parts' => $partsAvant,
                'created_by' => ($user = current_user()) ? (int) $user['id'] : null,
            ]);
            $cessionId = (int) $pdo->lastInsertId();
        }

        // Insert cession_parts
        foreach ($wizard['parts'] as $p) {
            $stmt = $pdo->prepare('INSERT INTO cession_parts (cession_id, cedant_associe_id, cedant_nom_complet, cedant_cin, cedant_type, cessionnaire_associe_id, cessionnaire_nom_complet, cessionnaire_cin, cessionnaire_type, cessionnaire_civilite, cessionnaire_date_naissance, cessionnaire_lieu_naissance, cessionnaire_nationalite, cessionnaire_adresse, parts_cedees, prix_unitaire, prix_total, pourcentage, nommer_gerant) VALUES (:cession_id, :cedant_associe_id, :cedant_nom, :cedant_cin, :cedant_type, :cess_associe_id, :cess_nom, :cess_cin, :cess_type, :cess_civilite, :cess_date_naiss, :cess_lieu_naiss, :cess_nationalite, :cess_adresse, :parts, :prix_u, :prix_t, :pourcentage, :nommer_gerant)');
            $stmt->execute([
                'cession_id' => $cessionId,
                'cedant_associe_id' => $p['cedant_associe_id'] ?: null,
                'cedant_nom' => $p['cedant_nom_complet'],
                'cedant_cin' => $p['cedant_cin'] ?: null,
                'cedant_type' => $p['cedant_type'] ?? 'existant',
                'cess_associe_id' => $p['cessionnaire_associe_id'] ?: null,
                'cess_nom' => $p['cessionnaire_nom_complet'],
                'cess_cin' => $p['cessionnaire_cin'] ?: null,
                'cess_type' => $p['cessionnaire_type'] ?? 'existant',
                'cess_civilite' => $p['cessionnaire_civilite'] ?? 'M.',
                'cess_date_naiss' => $p['cessionnaire_date_naissance'] ?: null,
                'cess_lieu_naiss' => $p['cessionnaire_lieu_naissance'] ?: null,
                'cess_nationalite' => $p['cessionnaire_nationalite'] ?: null,
                'cess_adresse' => $p['cessionnaire_adresse'] ?: null,
                'parts' => $p['parts_cedees'],
                'prix_u' => $p['prix_unitaire'] ?? 0,
                'prix_t' => $p['prix_total'] ?? 0,
                'pourcentage' => $p['pourcentage'] ?? null,
                'nommer_gerant' => $p['nommer_gerant'] ?? 0,
            ]);
            $cessionPartId = (int) $pdo->lastInsertId();

            // Si nouveau cessionnaire, le créer dans associes
            $nouvelAssocieId = null;
            if (($p['cessionnaire_type'] ?? 'existant') === 'nouveau' && ($p['cessionnaire_associe_id'] ?? 0) <= 0) {
                $stmtA = $pdo->prepare('INSERT INTO associes (societe_id, associe_civilite, associe_nom_complet, associe_cin, associe_date_naissance, associe_lieu_naissance, associe_nationalite, associe_adresse, associe_parts, associe_capital_detenu, associe_est_gerant) VALUES (:sid, :civ, :nom, :cin, :dn, :ln, :nat, :adr, :parts, :capital, :gerant)');
                $capitalDetenu = $partsAvant > 0 ? round(($p['parts_cedees'] / max($partsAvant, 1)) * $capitalAvant, 2) : 0;
                $isGerant = !empty($p['nommer_gerant']) ? 1 : 0;
                $stmtA->execute([
                    'sid' => $wizard['societe_id'],
                    'civ' => $p['cessionnaire_civilite'] ?? 'M.',
                    'nom' => $p['cessionnaire_nom_complet'],
                    'cin' => $p['cessionnaire_cin'] ?: null,
                    'dn' => $p['cessionnaire_date_naissance'] ?: null,
                    'ln' => $p['cessionnaire_lieu_naissance'] ?: null,
                    'nat' => $p['cessionnaire_nationalite'] ?: null,
                    'adr' => $p['cessionnaire_adresse'] ?: null,
                    'parts' => $p['parts_cedees'],
                    'capital' => $capitalDetenu,
                    'gerant' => $isGerant,
                ]);
                $nouvelAssocieId = (int) $pdo->lastInsertId();

                // Mettre à jour cession_parts avec le nouvel ID
                $stmtUp = $pdo->prepare('UPDATE cession_parts SET cessionnaire_associe_id = :aid WHERE id = :pid');
                $stmtUp->execute(['aid' => $nouvelAssocieId, 'pid' => $cessionPartId]);
            }

            // Si cessionnaire existant + nommer gérant, mettre à jour
            if (!empty($p['nommer_gerant']) && ($p['cessionnaire_associe_id'] ?? 0) > 0) {
                $stmtU = $pdo->prepare('UPDATE associes SET associe_est_gerant = 1 WHERE id = :id');
                $stmtU->execute(['id' => $p['cessionnaire_associe_id']]);
            }

            // Mise à jour des parts du cédant (réduction)
            if (($p['cedant_associe_id'] ?? 0) > 0) {
                $stmtU = $pdo->prepare('UPDATE associes SET associe_parts = GREATEST(COALESCE(associe_parts, 0) - :parts, 0), associe_capital_detenu = GREATEST(COALESCE(associe_capital_detenu, 0) - :capital_ded, 0) WHERE id = :id');
                $capitalDed = $partsAvant > 0 ? round(($p['parts_cedees'] / max($partsAvant, 1)) * $capitalAvant, 2) : 0;
                $stmtU->execute([
                    'parts' => $p['parts_cedees'],
                    'capital_ded' => $capitalDed,
                    'id' => $p['cedant_associe_id'],
                ]);
            }
        }

        // Generate documents if requested
        if (isset($_POST['generate_docs']) && ($_POST['generate_docs'] ?? '') === '1') {
            $selectedDocs = $_POST['doc_types'] ?? [];
            $generated = [];
            $stmtDos = $pdo->prepare('SELECT cession_dossier FROM cessions WHERE id = :id');
            $stmtDos->execute(['id' => $cessionId]);
            $dossierCession = $stmtDos->fetchColumn() ?: 'CES-' . $cessionId;
            $forme = $societe['societe_forme_juridique'] ?? 'PP';
            $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $societe['societe_raison_sociale'] ?? 'Client')));
            $clientName = preg_replace('/-+/', '-', $clientName);
            $clientName = trim($clientName, '-');
            $folderName = $wizard['cession_date'] . '_' . $forme . '_' . $clientName;
            $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
            $outputDir = __DIR__ . '/../dossiers_generer/dossiers_cession/' . $folderName . '/' . $dossierCession;
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $context = DocumentRenderer::buildContextFromCession($pdo, $cessionId);

            $templatesConfig = require __DIR__ . '/../config/templates.php';
            $docTypes = $templatesConfig['document_types'] ?? [];
            $mapping = $templatesConfig['template_mapping']['cession'] ?? [];

            $templateDir = __DIR__ . '/../templates/_Cession';

            foreach ($mapping as $docType) {
                if (!in_array($docType, $selectedDocs, true)) {
                    continue;
                }

                $templateFile = $templateDir . '/*' . $docType . '*_Template.docx';
                $matches = glob($templateFile);
                if (empty($matches)) {
                    continue;
                }

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
                } catch (Throwable $e) {
                    // Silently skip failed generation
                }
            }

            set_flash('success', 'Cession creee avec succes. ' . count($generated) . ' document(s) genere(s).');
        } else {
            set_flash('success', 'Cession enregistree avec succes.');
        }

        log_activity($pdo, $editingId > 0 ? 'update' : 'create', 'cession', $cessionId);
        unset($_SESSION['cession_wizard'], $_SESSION['_cession_loaded']);
        redirect_to('cessions');
    }
}

// Display current société
$selectedSociete = null;
if ($wizard['societe_id'] > 0 && ($pdo ?? null) instanceof PDO) {
    $selectedSociete = fetch_record($pdo, 'societes', $wizard['societe_id']);
}

?>
<section>
    <article class="card stack">
        <div class="section-header">
            <span style="display:flex;align-items:center;gap:8px">
                <span class="material-symbols-outlined" style="color:var(--primary)">transfer_within_a_station</span>
                <strong style="font-size:1.1rem">Cession de parts sociales</strong>
            </span>
            <span style="font-size:0.85rem;color:var(--text-secondary)">
                Étape <?= $step ?> / 3
            </span>
        </div>

        <div style="display:flex;gap:8px;padding:8px 0">
            <?php for ($s = 1; $s <= 3; $s++): ?>
                <div style="flex:1;height:4px;border-radius:2px;background:<?= $s <= $step ? 'var(--primary)' : 'var(--line)' ?>"></div>
            <?php endfor; ?>
        </div>

        <?php if ($step === 1): ?>
        <!-- Step 1: Société -->
        <form method="post">
            <?= csrf_input() ?>
            <div class="info-grid" style="grid-template-columns:1fr">
                <div class="field">
                    <label for="societe_id">Societe concernee</label>
                    <div style="display:flex;gap:8px">
                        <select name="societe_id" id="societe_id" style="flex:1" onchange="this.form.submit()">
                            <option value="">-- Selectionnez une societe --</option>
                            <?php foreach ($societesList as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= $wizard['societe_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['societe_raison_sociale']) ?> (<?= e($s['societe_dossier'] ?? '') ?>) - <?= e($s['societe_forme_juridique'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-back" id="btn-new-societe" style="white-space:nowrap">
                            <span class="material-symbols-outlined">add</span> Nouvelle
                        </button>
                    </div>
                </div>
            </div>
            <?php if ($selectedSociete): ?>
            <div class="info-grid" style="margin-top:16px">
                <div><strong>Forme juridique</strong><br><?= e($selectedSociete['societe_forme_juridique'] ?? '-') ?></div>
                <div><strong>Capital</strong><br><?= e(number_format((float) ($selectedSociete['societe_capital'] ?? 0), 2, ',', ' ') . ' DH') ?></div>
                <div><strong>Nombre de parts</strong><br><?= (int) ($selectedSociete['societe_part_social'] ?? 0) ?></div>
                <div><strong>Ville</strong><br><?= e($selectedSociete['societe_ville'] ?? '-') ?></div>
            </div>
            <?php if ($wizard['societe_id'] > 0 && !empty($selectedAssocies)): ?>
            <div style="margin-top:16px">
                <strong>Repartition actuelle des parts :</strong>
                <table data-sortable style="margin-top:8px">
                    <thead>
                        <tr>
                            <th data-col="associe">Associe</th>
                            <th data-col="cin">CIN</th>
                            <th data-col="parts">Parts</th>
                            <th data-col="capital">Capital detenu</th>
                            <th data-col="qualite">Qualite</th>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <!-- Modal nouvelle société -->
        <div id="modal-new-societe" class="modal-overlay">
            <div class="modal-content" style="max-width:580px;height:auto;width:auto;min-width:440px">
                <div class="modal-header">
                    <span class="modal-title">Nouvelle societe</span>
                    <button type="button" class="btn-icon" id="modal-new-societe-close">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="modal-body" style="padding:20px">
                    <form method="post" id="form-new-societe">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="create_societe">
                        <div class="field">
                            <label for="raison_sociale">Raison sociale *</label>
                            <input type="text" name="raison_sociale" id="raison_sociale" required style="width:100%">
                        </div>
                        <div class="field" style="margin-top:12px">
                            <label for="forme_juridique">Forme juridique</label>
                            <select name="forme_juridique" id="forme_juridique" style="width:100%">
                                <option value="">-- Selectionnez --</option>
                                <?php foreach ($formesJuridiques as $fj): ?>
                                    <option value="<?= e($fj['forme_juridique']) ?>"><?= e($fj['forme_juridique']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:12px">
                            <div class="field" style="flex:1">
                                <label for="capital">Capital (DH)</label>
                                <input type="text" name="capital" id="capital" style="width:100%" placeholder="100000">
                            </div>
                            <div class="field" style="flex:1">
                                <label for="part_social">Nombre de parts</label>
                                <input type="number" name="part_social" id="part_social" style="width:100%" placeholder="100">
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:12px">
                            <div class="field" style="flex:1">
                                <label for="ice">ICE</label>
                                <input type="text" name="ice" id="ice" style="width:100%" placeholder="Numero ICE">
                            </div>
                            <div class="field" style="flex:1">
                                <label for="rc">RC</label>
                                <input type="text" name="rc" id="rc" style="width:100%" placeholder="Numero RC">
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:12px">
                            <div class="field" style="flex:1">
                                <label for="ifiscal">IF</label>
                                <input type="text" name="ifiscal" id="ifiscal" style="width:100%" placeholder="Identifiant fiscal">
                            </div>
                            <div class="field" style="flex:1">
                                <label for="tribunal">Tribunal</label>
                                <select name="tribunal" id="tribunal" style="width:100%">
                                    <option value="">-- Selectionnez --</option>
                                    <?php foreach ($tribunaux as $t): ?>
                                        <option value="<?= e($t['tribunal']) ?>"><?= e($t['tribunal']) ?> (<?= e($t['tribunal_type']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="field" style="margin-top:12px">
                            <label for="ville">Ville</label>
                            <select name="ville" id="ville" style="width:100%">
                                <option value="">-- Selectionnez --</option>
                                <?php foreach ($villes as $v): ?>
                                    <option value="<?= e($v) ?>"><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" style="margin-top:12px">
                            <label for="adresse">Adresse</label>
                            <textarea name="adresse" id="adresse" rows="2" style="width:100%"></textarea>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:12px">
                            <div class="field" style="flex:1">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" style="width:100%" placeholder="contact@exemple.com">
                            </div>
                            <div class="field" style="flex:1">
                                <label for="telephone">Telephone</label>
                                <input type="text" name="telephone" id="telephone" style="width:100%" placeholder="05XX-XXXXXX">
                            </div>
                        </div>
                        <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end">
                            <button type="button" class="btn btn-cancel" id="modal-new-societe-cancel">
                                <span class="material-symbols-outlined">close</span> Annuler
                            </button>
                            <button type="submit" class="btn btn-next">
                                <span class="material-symbols-outlined">check</span> Creer la societe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        (function(){
            var modal = document.getElementById('modal-new-societe');
            var btnOpen = document.getElementById('btn-new-societe');
            var btnClose = document.getElementById('modal-new-societe-close');
            var btnCancel = document.getElementById('modal-new-societe-cancel');

            btnOpen.addEventListener('click', function(){ modal.classList.add('show'); });
            btnClose.addEventListener('click', function(){ modal.classList.remove('show'); });
            btnCancel.addEventListener('click', function(){ modal.classList.remove('show'); });
            modal.addEventListener('click', function(e){
                if (e.target === modal) modal.classList.remove('show');
            });
        })();
        </script>

        <?php elseif ($step === 2): ?>
        <!-- Step 2: Configuration des cessions -->
        <form method="post" id="cession-form">
            <?= csrf_input() ?>
            <div class="info-grid" style="grid-template-columns:1fr 1fr">
                <div class="field">
                    <label for="cession_date">Date de la cession</label>
                    <input type="date" name="cession_date" id="cession_date" value="<?= e($wizard['cession_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="field">
                    <label for="cession_motif">Motif de la cession</label>
                    <input type="text" name="cession_motif" id="cession_motif" value="<?= e($wizard['cession_motif'] ?? '') ?>" placeholder="Ex: Cession entre associes">
                </div>
            </div>

            <!-- Hidden input for JS pourcentage calculation -->
            <input type="hidden" id="total-societe-parts" value="<?= (int) ($selectedSociete['societe_part_social'] ?? 0) ?>">

            <div style="margin-top:20px">
                <strong>Lignes de cession</strong>
                <div id="cession-parts-container">
                    <?php $partIndex = 0; ?>
                    <?php if (!empty($wizard['parts'])): ?>
                        <?php foreach ($wizard['parts'] as $pi => $part): ?>
                            <?php include __DIR__ . '/_cession_part_row.php'; ?>
                            <?php $partIndex = $pi + 1; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                            $part = [
                                'cedant_type' => 'existant',
                                'cedant_associe_id' => 0,
                                'cedant_nom_complet' => '',
                                'cedant_cin' => '',
                                'cessionnaire_type' => 'existant',
                                'cessionnaire_associe_id' => 0,
                                'cessionnaire_nom_complet' => '',
                                'cessionnaire_cin' => '',
                                'cessionnaire_civilite' => 'M.',
                                'cessionnaire_date_naissance' => '',
                                'cessionnaire_lieu_naissance' => '',
                                'cessionnaire_nationalite' => '',
                                'cessionnaire_adresse' => '',
                                'parts_cedees' => '',
                                'prix_unitaire' => '',
                                'prix_total' => '',
                            ];
                            include __DIR__ . '/_cession_part_row.php';
                            $partIndex = 1;
                        ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-info" id="add-cession-part" style="margin-top:8px" data-part-index="<?= $partIndex ?>">
                    <span class="material-symbols-outlined">add</span> Ajouter une ligne
                </button>
            </div>

            <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
                <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
            </div>
        </form>

        <?php elseif ($step === 3): ?>
        <!-- Step 3: Récapitulatif -->
        <?php
            $totalParts = 0;
            $totalPrix = 0;
            foreach ($wizard['parts'] as $p) {
                $totalParts += (int) ($p['parts_cedees'] ?? 0);
                $totalPrix += (float) ($p['prix_total'] ?? 0);
            }
            $capitalAvant = (float) ($selectedSociete['societe_capital'] ?? 0);
            $partsAvant = (int) ($selectedSociete['societe_part_social'] ?? 0);
            $capitalApres = $capitalAvant;
            $partsApres = max(0, $partsAvant - $totalParts);
        ?>
        <form method="post">
            <?= csrf_input() ?>

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
            <table data-sortable style="margin-top:8px">
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
                        <td><?= !empty($p['nommer_gerant']) ? '<span class="material-symbols-outlined" style="color:var(--success);font-size:1.1rem">verified</span>' : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:600">
                        <td colspan="3">Total</td>
                        <td><?= $totalParts ?></td>
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

            <!-- Document generation -->
            <div class="field" style="margin-top:16px">
                <label>
                    <input type="checkbox" name="generate_docs" value="1" checked>
                    Generer les documents
                </label>
            </div>
            <div id="doc-types-container" style="margin:8px 0 0 24px">
                <?php
                    $templatesConfig = require __DIR__ . '/../config/templates.php';
                    $mapping = $templatesConfig['template_mapping']['cession'] ?? [];
                    $docTypes = $templatesConfig['document_types'] ?? [];
                ?>
                <?php foreach ($mapping as $dt): ?>
                <label style="display:block;margin:4px 0">
                    <input type="checkbox" name="doc_types[]" value="<?= e($dt) ?>" checked>
                    <?= e($docTypes[$dt] ?? $dt) ?>
                </label>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end">
                <button class="btn btn-back" type="submit" name="action" value="back" formnovalidate><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                <button class="btn btn-next" type="submit" name="action" value="save"><span class="material-symbols-outlined">check_circle</span> <?= $editingId > 0 ? 'Mettre a jour' : 'Creer la cession' ?></button>
            </div>
        </form>
        <?php endif; ?>
    </article>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle cedant fields based on type
    document.querySelectorAll('[name^="cedant_type["]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var row = this.closest('.cession-part-row');
            var existingFields = row.querySelector('.cedant-existing-fields');
            var newFields = row.querySelector('.cedant-new-fields');
            if (this.value === 'nouveau') {
                if (existingFields) existingFields.style.display = 'none';
                if (newFields) newFields.style.display = '';
            } else {
                if (existingFields) existingFields.style.display = '';
                if (newFields) newFields.style.display = 'none';
            }
        });
    });

    // Toggle cessionnaire fields
    document.querySelectorAll('[name^="cessionnaire_type["]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var row = this.closest('.cession-part-row');
            var existingFields = row.querySelector('.cessionnaire-existing-fields');
            var newFields = row.querySelector('.cessionnaire-new-fields');
            if (this.value === 'nouveau') {
                if (existingFields) existingFields.style.display = 'none';
                if (newFields) newFields.style.display = '';
            } else {
                if (existingFields) existingFields.style.display = '';
                if (newFields) newFields.style.display = 'none';
            }
        });
    });

    // Calculate prix_total from parts * prix_unitaire
    function bindPrixTotal(row) {
        var inputs = row.querySelectorAll('[name^="parts_cedees["], [name^="prix_unitaire["]');
        inputs.forEach(function(inp) {
            inp.removeEventListener('input', calcPrixTotal);
            inp.addEventListener('input', calcPrixTotal);
        });
    }
    function calcPrixTotal() {
        var row = this.closest('.cession-part-row');
        var partsInput = row.querySelector('[name^="parts_cedees["]');
        var puInput = row.querySelector('[name^="prix_unitaire["]');
        var ptInput = row.querySelector('[name^="prix_total["]');
        if (partsInput && puInput && ptInput) {
            var parts = parseFloat(partsInput.value.replace(',', '.')) || 0;
            var pu = parseFloat(puInput.value.replace(',', '.')) || 0;
            ptInput.value = (parts * pu).toFixed(2).replace('.', ',');
        }
    }
    document.querySelectorAll('.cession-part-row').forEach(function(row) {
        bindPrixTotal(row);
    });

    // Calculate parts from pourcentage
    function bindPourcentage(row) {
        var inp = row.querySelector('[name^="pourcentage["]');
        if (inp) {
            inp.removeEventListener('input', calcPartsFromPct);
            inp.addEventListener('input', calcPartsFromPct);
        }
    }
    function calcPartsFromPct() {
        var row = this.closest('.cession-part-row');
        var pct = parseFloat(this.value.replace(',', '.')) || 0;
        var totalParts = parseInt(document.getElementById('total-societe-parts')?.value) || 0;
        var partsInput = row.querySelector('[name^="parts_cedees["]');
        if (partsInput && pct > 0 && totalParts > 0) {
            partsInput.value = Math.round((pct / 100) * totalParts);
            calcPrixTotal.call(partsInput);
        }
    }
    document.querySelectorAll('.cession-part-row').forEach(function(row) {
        bindPourcentage(row);
    });

    // Add new part row
    document.getElementById('add-cession-part')?.addEventListener('click', function() {
        var container = document.getElementById('cession-parts-container');
        var index = parseInt(this.dataset.partIndex) || 0;
        var template = container.querySelector('.cession-part-row');
        if (!template) return;
        var clone = template.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(function(el) {
            var name = el.getAttribute('name') || '';
            el.name = name.replace(/\[\d+\]/g, '[' + index + ']');
            if (el.type === 'checkbox') {
                el.checked = false;
            } else if (el.tagName !== 'SELECT') {
                el.value = '';
            } else {
                el.selectedIndex = 0;
            }
        });
        // Reset display
        var cedantNew = clone.querySelector('.cedant-new-fields');
        var cedantExist = clone.querySelector('.cedant-existing-fields');
        if (cedantNew) cedantNew.style.display = 'none';
        if (cedantExist) cedantExist.style.display = '';
        var cessNew = clone.querySelector('.cessionnaire-new-fields');
        var cessExist = clone.querySelector('.cessionnaire-existing-fields');
        if (cessNew) cessNew.style.display = 'none';
        if (cessExist) cessExist.style.display = '';
        container.appendChild(clone);
        // Bind events on the new row
        bindPrixTotal(clone);
        bindPourcentage(clone);
        this.dataset.partIndex = index + 1;
    });
});
</script>
