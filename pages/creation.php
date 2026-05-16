<?php

declare(strict_types=1);

if (!isset($_SESSION['creation_wizard']) || !is_array($_SESSION['creation_wizard'])) {
    $defaults = load_defaults();

    $associeDefaults = $defaults['associe'] ?? [];

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
    ];
}

$wizard = &$_SESSION['creation_wizard'];
$step = max(1, min(5, (int) ($_GET['step'] ?? 1)));
$adressesOptions = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');
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

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['creation_wizard']);
    set_flash('success', 'Assistant reinitialise.');
    redirect_to('creation');
}

if (isset($_GET['cancel']) && $_GET['cancel'] === '1') {
    unset($_SESSION['creation_wizard']);
    set_flash('success', 'Creation annulee.');
    redirect_to('societes');
}

if (is_post()) {
    verify_csrf();
    $postedStep = max(1, min(5, (int) ($_POST['step'] ?? $step)));
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($postedStep === 1) {
        $activitesStatuts = $_POST['societe_activites_statuts'] ?? [];
        $allStatuts = is_array($activitesStatuts) ? array_map('trim', $activitesStatuts) : [];
        $allStatuts = array_unique(array_filter($allStatuts));

        $activitesOmpic = field_value($_POST, 'societe_activites_ompic');

        $societe = [
            'societe_dossier' => field_value($_POST, 'societe_dossier'),
            'societe_raison_sociale' => field_value($_POST, 'societe_raison_sociale'),
            'societe_forme_juridique' => field_value($_POST, 'societe_forme_juridique'),
            'societe_ice' => field_value($_POST, 'societe_ice'),
            'societe_date_ice' => field_value($_POST, 'societe_date_ice'),
            'societe_rc' => field_value($_POST, 'societe_rc'),
            'societe_if' => field_value($_POST, 'societe_if'),
            'societe_activites_statuts' => implode(', ', $allStatuts),
            'societe_activites_ompic' => $activitesOmpic,
            'societe_part_social' => field_value($_POST, 'societe_part_social'),
            'societe_valeur_nominale' => field_value($_POST, 'societe_valeur_nominale'),
            'societe_date_exp_cert_neg' => field_value($_POST, 'societe_date_exp_cert_neg'),
            'societe_adresse_siege' => field_value($_POST, 'societe_adresse_siege'),
            'societe_ville' => field_value($_POST, 'societe_ville'),
            'societe_tribunal' => field_value($_POST, 'societe_tribunal'),
            'societe_email' => field_value($_POST, 'societe_email'),
            'societe_telephone' => field_value($_POST, 'societe_telephone'),
            'societe_capital' => field_value($_POST, 'societe_capital'),
            'type_generation' => field_value($_POST, 'type_generation'),
            'procedure_creation' => field_value($_POST, 'procedure_creation'),
            'mode_depot_creation' => field_value($_POST, 'mode_depot_creation'),
        ];

        $wizard['societe'] = $societe;
        if ($societe['societe_raison_sociale'] === '') {
            set_flash('error', 'La raison sociale est obligatoire.');
            redirect_to('creation', ['step' => 1]);
        }

        redirect_to('creation', ['step' => 2]);
    }

    if ($postedStep === 2) {
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
                    'associe_date_naissance' => trim((string) ($associe['date_naiss'] ?? '')),
                    'associe_lieu_naissance' => trim((string) ($associe['lieu_naiss'] ?? '')),
                    'associe_nationalite' => trim((string) ($associe['nationalite'] ?? '')),
                    'associe_telephone' => trim((string) ($associe['phone'] ?? '')),
                    'associe_email' => trim((string) ($associe['societe_email'] ?? '')),
                    'associe_qualite' => trim((string) ($associe['qualite_associe'] ?? '')),
                    'associe_parts' => trim((string) ($associe['parts'] ?? '')),
                    'associe_capital_detenu' => trim((string) ($associe['capital_detenu'] ?? '')),
                    'associe_part_percent' => trim((string) ($associe['part_percent'] ?? '')),
                    'associe_est_gerant' => ((string) ($associe['is_gerant'] ?? '0') === '1') ? '1' : '0',
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

        if ($navAction === 'back') {
            redirect_to('creation', ['step' => 1]);
        }

        if (count($normalizedAssocies) === 0) {
            set_flash('error', 'Ajoutez au moins un associe.');
            redirect_to('creation', ['step' => 2]);
        }

        redirect_to('creation', ['step' => 3]);
    }

    if ($postedStep === 3) {
        $typeContratVal = field_value($_POST, 'contrat_type');
        $typeContratAutre = field_value($_POST, 'contrat_type_autre');
        if ($typeContratVal === 'autre' && $typeContratAutre !== '') {
            $typeContratVal = $typeContratAutre;
        }
        $contrat = [
            'contrat_type' => $typeContratVal,
            'contrat_date' => field_value($_POST, 'contrat_date'),
            'contrat_duree_mois' => field_value($_POST, 'contrat_duree_mois'),
            'contrat_type_domiciliation' => field_value($_POST, 'contrat_type_domiciliation'),
            'contrat_type_domiciliation_autre' => field_value($_POST, 'contrat_type_domiciliation_autre'),
            'contrat_date_debut' => field_value($_POST, 'contrat_date_debut'),
            'contrat_date_fin' => field_value($_POST, 'contrat_date_fin'),
            'contrat_tva_pourcent' => field_value($_POST, 'contrat_tva_pourcent'),
            'contrat_loyer_ht' => field_value($_POST, 'contrat_loyer_ht'),
            'contrat_loyer_ttc' => field_value($_POST, 'contrat_loyer_ttc'),
            'contrat_total_ht' => field_value($_POST, 'contrat_total_ht'),
            'contrat_type_renouvellement' => field_value($_POST, 'contrat_type_renouvellement'),
            'contrat_renouv_tva_pourcent' => field_value($_POST, 'contrat_renouv_tva_pourcent'),
            'contrat_renouv_loyer_ht' => field_value($_POST, 'contrat_renouv_loyer_ht'),
            'contrat_renouv_loyer_ttc' => field_value($_POST, 'contrat_renouv_loyer_ttc'),
            'contrat_renouv_total_ht' => field_value($_POST, 'contrat_renouv_total_ht'),
            'contrat_statut' => field_value($_POST, 'contrat_statut', 'actif'),
            'contrat_notes' => field_value($_POST, 'contrat_notes'),
        ];

        $wizard['contrat'] = $contrat;

        if ($navAction === 'back') {
            redirect_to('creation', ['step' => 2]);
        }

        if ($contrat['contrat_type'] === '') {
            set_flash('error', 'Le type de contrat est obligatoire.');
            redirect_to('creation', ['step' => 3]);
        }

        redirect_to('creation', ['step' => 4]);
    }

    if ($postedStep === 4) {
        if ($navAction === 'back') {
            redirect_to('creation', ['step' => 3]);
        }

        redirect_to('creation', ['step' => 5]);
    }

    if ($postedStep === 5) {
        if ($navAction === 'back') {
            redirect_to('creation', ['step' => 4]);
        }

        if ($navAction === 'generate') {
            require_once __DIR__ . '/../src/TemplateAnalyzer.php';
            require_once __DIR__ . '/../src/DocumentRenderer.php';

            $templatesDir = __DIR__ . '/../templates';
            $outputDir = __DIR__ . '/../output';
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $selectedPaths = $_POST['templates'] ?? [];
            $generatePdf = isset($_POST['pdf']);
            $forme = $wizard['societe']['societe_forme_juridique'] ?? 'PP';

            $context = DocumentRenderer::buildContextFromSession($wizard, $pdo ?? null);
            $today = date('Y-m-d');
            $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $wizard['societe']['societe_raison_sociale'] ?? 'Client')));
            $clientName = preg_replace('/-+/', '-', $clientName);
            $clientName = trim($clientName, '-');
            $generatedFiles = [];

            foreach ($selectedPaths as $path) {
                if (!file_exists($path)) continue;
                if (!str_starts_with(realpath($path), realpath($templatesDir))) continue;

                try {
                    $renderer = new DocumentRenderer($path, $outputDir);
                    $filename = pathinfo($path, PATHINFO_FILENAME);
                    $parts = explode('_', $filename);
                    $docType = '';
                    if (count($parts) >= 4) {
                        $docType = preg_replace('/_?Template$/i', '', implode('_', array_slice($parts, 2)));
                    } elseif (count($parts) === 3) {
                        $docType = preg_replace('/_?Template$/i', '', $parts[1]);
                    }
                    $base = $forme . '_' . $today . '_' . $docType . '_' . $clientName;
                    $outName = $base . '_Brouillon.docx';
                    $docxPath = $renderer->render($context, $outName);

                    $result = [
                        'docx' => $docxPath,
                        'pdf' => null,
                        'name' => $outName,
                    ];

                    if ($generatePdf) {
                        $pdfName = $base . '_Brouillon.pdf';
                        $pdfPath = $renderer->tryConvertToPdf($docxPath, $pdfName);
                        $result['pdf'] = $pdfPath;
                    }

                    $generatedFiles[] = $result;
                } catch (\Throwable $e) {
                    set_flash('error', 'Erreur sur ' . basename($path) . ' : ' . $e->getMessage());
                }
            }

            if (count($generatedFiles) > 0) {
                $_SESSION['creation_wizard']['generated_files'] = $generatedFiles;
                set_flash('success', count($generatedFiles) . ' document(s) genere(s).');
            }

            redirect_to('creation', ['step' => 5]);
        }

        if ($navAction === 'finish') {
            if (!(($pdo ?? null) instanceof PDO)) {
                set_flash('error', 'Connexion MySQL indisponible.');
                redirect_to('creation', ['step' => 5]);
            }

            try {
                $pdo->beginTransaction();

                $societeStmt = $pdo->prepare('
                    INSERT INTO societes (
                        societe_dossier, societe_raison_sociale, societe_forme_juridique, societe_ice, societe_date_ice, societe_rc, societe_if,
                        societe_activites_statuts, societe_activites_ompic,
                        societe_capital, societe_part_social, societe_valeur_nominale, societe_date_exp_cert_neg, societe_adresse_siege, societe_ville, societe_tribunal, societe_email,
                        societe_telephone, societe_type_generation, societe_procedure_creation, societe_mode_depot
                    ) VALUES (
                        :societe_dossier, :societe_raison_sociale, :societe_forme_juridique, :societe_ice, :societe_date_ice, :societe_rc, :societe_if,
                        :societe_activites_statuts, :societe_activites_ompic,
                        :societe_capital, :societe_part_social, :societe_valeur_nominale, :societe_date_exp_cert_neg, :societe_adresse_siege, :societe_ville, :societe_tribunal, :societe_email,
                        :societe_telephone, :societe_type_generation, :societe_procedure_creation, :societe_mode_depot
                    )
                ');
                $societeStmt->execute([
                    'societe_dossier' => $wizard['societe']['societe_dossier'] ?? null,
                    'societe_raison_sociale' => $wizard['societe']['societe_raison_sociale'] ?? '',
                    'societe_forme_juridique' => $wizard['societe']['societe_forme_juridique'] ?? '',
                    'societe_ice' => $wizard['societe']['societe_ice'] ?? '',
                    'societe_date_ice' => ($wizard['societe']['societe_date_ice'] ?? '') !== '' ? $wizard['societe']['societe_date_ice'] : null,
                    'societe_rc' => $wizard['societe']['societe_rc'] ?? '',
                    'societe_if' => $wizard['societe']['societe_if'] ?? '',
                    'societe_activites_statuts' => $wizard['societe']['societe_activites_statuts'] ?? '',
                    'societe_activites_ompic' => $wizard['societe']['societe_activites_ompic'] ?? '',
                    'societe_adresse_siege' => $wizard['societe']['societe_adresse_siege'] ?? '',
                    'societe_ville' => $wizard['societe']['societe_ville'] ?? '',
                    'societe_tribunal' => $wizard['societe']['societe_tribunal'] ?? '',
                    'societe_email' => $wizard['societe']['societe_email'] ?? '',
                    'societe_telephone' => $wizard['societe']['societe_telephone'] ?? '',
                    'societe_capital' => ($wizard['societe']['societe_capital'] ?? '') !== '' ? parse_money((string) $wizard['societe']['societe_capital']) : null,
                    'societe_part_social' => ($wizard['societe']['societe_part_social'] ?? '') !== '' ? (int) $wizard['societe']['societe_part_social'] : null,
                    'societe_valeur_nominale' => ($wizard['societe']['societe_valeur_nominale'] ?? '') !== '' ? parse_money((string) $wizard['societe']['societe_valeur_nominale']) : null,
                    'societe_date_exp_cert_neg' => ($wizard['societe']['societe_date_exp_cert_neg'] ?? '') !== '' ? $wizard['societe']['societe_date_exp_cert_neg'] : null,
                    'societe_type_generation' => $wizard['societe']['type_generation'] ?? '',
                    'societe_procedure_creation' => $wizard['societe']['procedure_creation'] ?? '',
                    'societe_mode_depot' => $wizard['societe']['mode_depot_creation'] ?? '',
                ]);

                $societeId = (int) $pdo->lastInsertId();

                $associeStmt = $pdo->prepare('
                    INSERT INTO associes (societe_id, associe_civilite, associe_nom, associe_prenom, associe_nom_complet, associe_cin, associe_date_validite_cin, associe_date_naissance, associe_lieu_naissance, associe_nationalite, associe_adresse, associe_telephone, associe_email, associe_qualite, associe_parts, associe_capital_detenu, associe_part_percent, associe_est_gerant)
                    VALUES (:societe_id, :associe_civilite, :associe_nom, :associe_prenom, :associe_nom_complet, :associe_cin, :associe_date_validite_cin, :associe_date_naissance, :associe_lieu_naissance, :associe_nationalite, :associe_adresse, :associe_telephone, :associe_email, :associe_qualite, :associe_parts, :associe_capital_detenu, :associe_part_percent, :associe_est_gerant)
                ');

                foreach ($wizard['associes'] as $associe) {
                    $associeStmt->execute([
                        'societe_id' => $societeId,
                        'associe_civilite' => $associe['associe_civilite'] ?? '',
                        'associe_nom' => $associe['associe_nom'] ?? '',
                        'associe_prenom' => $associe['associe_prenom'] ?? '',
                        'associe_nom_complet' => $associe['associe_nom_complet'] ?? '',
                        'associe_cin' => $associe['associe_cin'] ?? '',
                        'associe_date_validite_cin' => ($associe['associe_date_validite_cin'] ?? '') !== '' ? $associe['associe_date_validite_cin'] : null,
                        'associe_date_naissance' => ($associe['associe_date_naissance'] ?? '') !== '' ? $associe['associe_date_naissance'] : null,
                        'associe_lieu_naissance' => $associe['associe_lieu_naissance'] ?? '',
                        'associe_nationalite' => $associe['associe_nationalite'] ?? '',
                        'associe_adresse' => $associe['associe_adresse'] ?? '',
                        'associe_telephone' => $associe['associe_telephone'] ?? '',
                        'associe_email' => $associe['associe_email'] ?? '',
                        'associe_qualite' => $associe['associe_qualite'] ?? '',
                        'associe_parts' => ($associe['associe_parts'] ?? '') !== '' ? (int) $associe['associe_parts'] : null,
                        'associe_capital_detenu' => ($associe['associe_capital_detenu'] ?? '') !== '' ? parse_money((string) $associe['associe_capital_detenu']) : null,
                        'associe_part_percent' => ($associe['associe_part_percent'] ?? '') !== '' ? parse_money((string) $associe['associe_part_percent']) : null,
                        'associe_est_gerant' => ((string) ($associe['associe_est_gerant'] ?? '0') === '1') ? 1 : 0,
                    ]);
                }

                $contratStmt = $pdo->prepare('
                    INSERT INTO contrats (
                        societe_id, contrat_type, contrat_date, contrat_duree_mois, contrat_type_domiciliation,
                        contrat_type_domiciliation_autre, contrat_date_debut, contrat_date_fin,
                        contrat_tva_pourcent, contrat_loyer_ht, contrat_loyer_ttc, contrat_total_ht,
                        contrat_type_renouvellement, contrat_renouv_tva_pourcent, contrat_renouv_loyer_ht,
                        contrat_renouv_loyer_ttc, contrat_renouv_total_ht,
                        contrat_statut, contrat_notes
                    ) VALUES (
                        :societe_id, :contrat_type, :contrat_date, :contrat_duree_mois, :contrat_type_domiciliation,
                        :contrat_type_domiciliation_autre, :contrat_date_debut, :contrat_date_fin,
                        :contrat_tva_pourcent, :contrat_loyer_ht, :contrat_loyer_ttc, :contrat_total_ht,
                        :contrat_type_renouvellement, :contrat_renouv_tva_pourcent, :contrat_renouv_loyer_ht,
                        :contrat_renouv_loyer_ttc, :contrat_renouv_total_ht,
                        :contrat_statut, :contrat_notes
                    )
                ');
                $contratStmt->execute([
                    'societe_id' => $societeId,
                    'contrat_type' => $wizard['contrat']['contrat_type'] ?? '',
                    'contrat_date' => ($wizard['contrat']['contrat_date'] ?? '') !== '' ? $wizard['contrat']['contrat_date'] : null,
                    'contrat_duree_mois' => ($wizard['contrat']['contrat_duree_mois'] ?? '') !== '' ? (int) $wizard['contrat']['contrat_duree_mois'] : null,
                    'contrat_type_domiciliation' => $wizard['contrat']['contrat_type_domiciliation'] ?? '',
                    'contrat_type_domiciliation_autre' => ($wizard['contrat']['contrat_type_domiciliation_autre'] ?? '') !== '' ? $wizard['contrat']['contrat_type_domiciliation_autre'] : null,
                    'contrat_date_debut' => ($wizard['contrat']['contrat_date_debut'] ?? '') !== '' ? $wizard['contrat']['contrat_date_debut'] : null,
                    'contrat_date_fin' => ($wizard['contrat']['contrat_date_fin'] ?? '') !== '' ? $wizard['contrat']['contrat_date_fin'] : null,
                    'contrat_tva_pourcent' => ($wizard['contrat']['contrat_tva_pourcent'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_tva_pourcent']) : null,
                    'contrat_loyer_ht' => ($wizard['contrat']['contrat_loyer_ht'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_loyer_ht']) : null,
                    'contrat_loyer_ttc' => ($wizard['contrat']['contrat_loyer_ttc'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_loyer_ttc']) : null,
                    'contrat_total_ht' => ($wizard['contrat']['contrat_total_ht'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_total_ht']) : null,
                    'contrat_type_renouvellement' => $wizard['contrat']['contrat_type_renouvellement'] ?? '',
                    'contrat_renouv_tva_pourcent' => ($wizard['contrat']['contrat_renouv_tva_pourcent'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_renouv_tva_pourcent']) : null,
                    'contrat_renouv_loyer_ht' => ($wizard['contrat']['contrat_renouv_loyer_ht'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_renouv_loyer_ht']) : null,
                    'contrat_renouv_loyer_ttc' => ($wizard['contrat']['contrat_renouv_loyer_ttc'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_renouv_loyer_ttc']) : null,
                    'contrat_renouv_total_ht' => ($wizard['contrat']['contrat_renouv_total_ht'] ?? '') !== '' ? parse_money((string) $wizard['contrat']['contrat_renouv_total_ht']) : null,
                    'contrat_statut' => $wizard['contrat']['contrat_statut'] ?? 'actif',
                    'contrat_notes' => $wizard['contrat']['contrat_notes'] ?? '',
                ]);

                $generatedFiles = $wizard['generated_files'] ?? [];
                if (count($generatedFiles) > 0) {
                    $insertDocStmt = $pdo->prepare('INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko) VALUES (:societe_id, :template_source, :doc_type, :fichier_docx, :fichier_pdf, :taille_ko)');
                    foreach ($generatedFiles as $gf) {
                        $docType = null;
                        $parts = explode('_', basename((string) $gf['name']));
                        $docType = $parts[2] ?? null;
                        $insertDocStmt->execute([
                            'societe_id' => $societeId,
                            'template_source' => null,
                            'doc_type' => $docType,
                            'fichier_docx' => $gf['docx'],
                            'fichier_pdf' => $gf['pdf'] ?? null,
                            'taille_ko' => file_exists((string) $gf['docx']) ? round(filesize((string) $gf['docx']) / 1024, 1) : null,
                        ]);
                    }
                }

                $pdo->commit();
                unset($_SESSION['creation_wizard']);
                set_flash('success', 'Le dossier a ete cree avec succes.');
                redirect_to('societe', ['id' => $societeId]);
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                set_flash('error', 'Erreur lors de la creation du dossier: ' . $exception->getMessage());
                redirect_to('creation', ['step' => 5]);
            }
        }
    }
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
    'type_generation' => '',
    'procedure_creation' => '',
    'mode_depot_creation' => '',
], $wizard['societe']);

$tribunalTypes = fetch_tribunaux_types($pdo ?? null);
$allTribunaux = fetch_tribunaux_all($pdo ?? null);
$currentTribunalType = '';
$societeTribunal = $societeData['societe_tribunal'] ?? '';
if ($societeTribunal) {
    foreach ($allTribunaux as $t) {
        if ($t['societe_tribunal'] === $societeTribunal && ($t['tribunal_type'] ?? '')) {
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
    'contrat_type' => '',
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
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Assistant de creation d'un dossier</h2>
            <p class="help-text">Parcours guide: societe, associes, puis contrat, dans un seul flux.</p>
        </div>
        <a class="btn btn-cancel" href="<?= e(app_url('creation', ['cancel' => '1'])) ?>" data-confirm="Annuler la creation ?"><span class="mdi mdi-close-circle"></span> Annuler</a>
        <a class="btn btn-back" href="<?= e(app_url('creation', ['reset' => '1'])) ?>" data-confirm="Reinitialiser cet assistant ?"><span class="mdi mdi-restart"></span> Reinitialiser</a>
    </div>

    <div class="wizard-steps">
        <div class="wizard-step <?= $step === 1 ? 'active' : '' ?>">
            <strong>Etape 1</strong>
            <span>Societe</span>
        </div>
        <div class="wizard-step <?= $step === 2 ? 'active' : '' ?>">
            <strong>Etape 2</strong>
            <span>Associes</span>
        </div>
        <div class="wizard-step <?= $step === 3 ? 'active' : '' ?>">
            <strong>Etape 3</strong>
            <span>Contrat</span>
        </div>
        <div class="wizard-step <?= $step === 4 ? 'active' : '' ?>">
            <strong>Etape 4</strong>
            <span>Recapitulatif</span>
        </div>
        <div class="wizard-step <?= $step === 5 ? 'active' : '' ?>">
            <strong>Etape 5</strong>
            <span>Generation</span>
        </div>
    </div>

    <?php if ($step === 1): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="step" value="1">
            <div class="form-grid">
                <h3 class="section-title">Procedure</h3>
                <label class="field">
                    <span>Type generation</span>
                    <select name="type_generation">
                        <option value="">Selectionner</option>
                        <option value="creation" <?= (string) $societeData['type_generation'] === 'creation' ? 'selected' : '' ?>>Création</option>
                        <option value="domiciliation" <?= (string) $societeData['type_generation'] === 'domiciliation' ? 'selected' : '' ?>>Domiciliation</option>
                    </select>
                </label>
                <label class="field">
                    <span>Procedure creation</span>
                    <select name="procedure_creation">
                        <option value="">Selectionner</option>
                        <option value="normal" <?= (string) $societeData['procedure_creation'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="acceleree" <?= (string) $societeData['procedure_creation'] === 'acceleree' ? 'selected' : '' ?>>Accélérer</option>
                    </select>
                </label>
                <label class="field">
                    <span>Mode depot creation</span>
                    <select name="mode_depot_creation">
                        <option value="">Selectionner</option>
                        <option value="depot_physique" <?= (string) $societeData['mode_depot_creation'] === 'depot_physique' ? 'selected' : '' ?>>Dépôt Physique</option>
                        <option value="depot_en_ligne" <?= (string) $societeData['mode_depot_creation'] === 'depot_en_ligne' ? 'selected' : '' ?>>Dépôt En Ligne</option>
                    </select>
                </label>

                <h3 class="section-title">Identifiants</h3>
                <label class="field">
                    <span>Dossier domiciliation</span>
                    <input name="societe_dossier" value="<?= e((string) $societeData['societe_dossier']) ?>">
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
                        <a href="<?= e(app_url('configuration', ['tab' => 'formes-juridiques'])) ?>" target="_blank" title="Gerer les formes juridiques" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                    </div>
                </label>
                <label class="field">
                    <span>ICE</span>
                    <input name="societe_ice" value="<?= e((string) $societeData['societe_ice']) ?>">
                </label>
                <label class="field">
                    <span>Date de cert. negatif</span>
                    <input type="date" name="societe_date_ice" value="<?= e((string) $societeData['societe_date_ice']) ?>">
                </label>
                <label class="field">
                    <span>Date exp. cert. negatif</span>
                    <input type="date" name="societe_date_exp_cert_neg" value="<?= e((string) $societeData['societe_date_exp_cert_neg']) ?>">
                </label>
                <label class="field">
                    <span>RC</span>
                    <input name="societe_rc" value="<?= e((string) $societeData['societe_rc']) ?>">
                </label>
                <label class="field">
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
                        <button type="button" class="btn btn-info" data-add-activite-cn style="white-space:nowrap"><span class="mdi mdi-plus-circle"></span> Nouvelle activite</button>
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
                                    <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="mdi mdi-close"></span></button>
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
                                    <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="mdi mdi-close"></span></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                            <button type="button" class="btn" data-add-activite><span class="mdi mdi-plus"></span> Ajouter une activite</button>
                            <button type="button" class="btn btn-info" data-add-activite-ref><span class="mdi mdi-plus-circle"></span> Nouvelle activite</button>
                            <button type="button" class="btn btn-secondary" data-add-activites-multiple><span class="mdi mdi-plus-box-multiple"></span> Ajouter plusieurs</button>
                        </div>
                        <template data-activite-template>
                            <div data-activite-item style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                                <select name="societe_activites_statuts[]" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($activitesOptions as $opt): ?>
                                        <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-icon danger" data-remove-activite title="Retirer"><span class="mdi mdi-close"></span></button>
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
                            <?php foreach ($adressesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societeData['societe_adresse_siege'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= e(app_url('configuration', ['tab' => 'adresses'])) ?>" target="_blank" title="Gerer les adresses" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                    </div>
                </label>
                <label class="field">
                    <span>Ville</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="societe_ville" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($villesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= $defaultVille === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= e(app_url('configuration', ['tab' => 'villes'])) ?>" target="_blank" title="Gerer les villes" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
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
                            <option value="<?= e($t['societe_tribunal']) ?>" data-type="<?= e($t['tribunal_type'] ?? '') ?>" <?= $defaultTribunal === $t['societe_tribunal'] && $currentTribunalType === ($t['tribunal_type'] ?? '') ? 'selected' : '' ?>><?= e($t['societe_tribunal']) ?></option>
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
                <button class="btn btn-info" type="button" data-fill-test><span class="mdi mdi-auto-fix"></span> Remplir automatiquement</button>
                <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="mdi mdi-arrow-right"></span> Suivant</button>
            </div>
        </form>

    <?php elseif ($step === 2): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="step" value="2">
            <input type="hidden" id="societe-capital" value="<?= e((string) ($societeData['societe_capital'] ?? '')) ?>">
            <input type="hidden" id="societe-part-social" value="<?= e((string) ($societeData['societe_part_social'] ?? '')) ?>">
            <input type="hidden" name="forme_juridique" value="<?= e((string) ($societeData['societe_forme_juridique'] ?? '')) ?>">
            <div class="section-header">
                <div>
                    <h2>Associes de <?= e((string) ($societeData['societe_raison_sociale'] ?: 'la societe')) ?></h2>
                    <p class="help-text">Ajoutez autant d'associes que necessaire.</p>
                </div>
                <button class="btn" type="button" data-add-associe><span class="mdi mdi-plus"></span> Ajouter un associé</button>
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
                                <input data-field-name="date_validite_cin" type="date" name="associes[<?= $index ?>][date_validite_cin]" value="<?= e((string) ($associe['associe_date_validite_cin'] ?? '')) ?>">
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
                                    <a href="<?= e(app_url('configuration', ['tab' => 'nationalites'])) ?>" target="_blank" title="Gerer les nationalites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                                </div>
                            </label>
                            <label class="field">
                                <span>Date naissance</span>
                                <input data-field-name="date_naiss" type="date" name="associes[<?= $index ?>][date_naiss]" value="<?= e((string) ($associe['associe_date_naissance'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Lieu naissance</span>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <select data-field-name="lieu_naiss" name="associes[<?= $index ?>][lieu_naiss]" style="flex:1">
                                        <option value="">Selectionner</option>
                                        <?php foreach ($lieuxNaissanceOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= (string) ($associe['associe_lieu_naissance'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="<?= e(app_url('configuration', ['tab' => 'lieux-naissance'])) ?>" target="_blank" title="Gerer les lieux de naissance" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                                </div>
                            </label>
                            <h3 class="section-title">Contact</h3>
                            <label class="field">
                                <span>Telephone</span>
                                <input data-field-name="phone" name="associes[<?= $index ?>][phone]" value="<?= e((string) ($associe['associe_telephone'] ?? '')) ?>">
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
                                    <select data-field-name="qualite_associe" name="associes[<?= $index ?>][qualite_associe]" style="flex:1">
                                        <option value="">Selectionner</option>
                                        <?php foreach ($qualitesAssocieOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= (string) ($associe['associe_qualite'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="<?= e(app_url('configuration', ['tab' => 'qualites-associe'])) ?>" target="_blank" title="Gerer les qualites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
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
                                <select data-field-name="is_gerant" name="associes[<?= $index ?>][is_gerant]">
                                    <option value="0" <?= (string) ($associe['associe_est_gerant'] ?? '0') === '0' ? 'selected' : '' ?>>Non</option>
                                    <option value="1" <?= (string) ($associe['associe_est_gerant'] ?? '0') === '1' ? 'selected' : '' ?>>Oui</option>
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
                            <input data-field-name="date_validite_cin" type="date" value="">
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
                                <a href="<?= e(app_url('configuration', ['tab' => 'nationalites'])) ?>" target="_blank" title="Gerer les nationalites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                            </div>
                        </label>
                        <label class="field">
                            <span>Date naissance</span>
                            <input data-field-name="date_naiss" type="date" value="">
                        </label>
                        <label class="field">
                            <span>Lieu naissance</span>
                            <div style="display:flex;gap:8px;align-items:center">
                                <select data-field-name="lieu_naiss" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($lieuxNaissanceOptions as $option): ?>
                                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= e(app_url('configuration', ['tab' => 'lieux-naissance'])) ?>" target="_blank" title="Gerer les lieux de naissance" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                            </div>
                        </label>
                        <h3 class="section-title">Contact</h3>
                        <label class="field">
                            <span>Telephone</span>
                            <input data-field-name="phone" value="">
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
                                <select data-field-name="qualite_associe" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($qualitesAssocieOptions as $option): ?>
                                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= e(app_url('configuration', ['tab' => 'qualites-associe'])) ?>" target="_blank" title="Gerer les qualites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
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
                            <select data-field-name="is_gerant">
                                <option value="0" selected>Non</option>
                                <option value="1">Oui</option>
                            </select>
                        </label>
                    </div>
                </div>
            </template>

            <div class="table-actions">
                <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="mdi mdi-arrow-left"></span> Retour</button>
                <button class="btn btn-info" type="button" data-fill-test><span class="mdi mdi-auto-fix"></span> Remplir automatiquement</button>
                <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="mdi mdi-arrow-right"></span> Suivant</button>
            </div>
        </form>
    <?php elseif ($step === 3): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="step" value="3">
            <div class="form-grid">
                <h3 class="section-title">Type de contrat</h3>
                <label class="field">
                    <span>Type de contrat</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="contrat_type" style="flex:1" data-calc-trigger>
                            <option value="">Selectionner</option>
                            <option value="Domiciliation commerciale" <?= (string) $contratData['contrat_type'] === 'Domiciliation commerciale' ? 'selected' : '' ?>>Domiciliation commerciale</option>
                            <option value="Domiciliation professionnelle" <?= (string) $contratData['contrat_type'] === 'Domiciliation professionnelle' ? 'selected' : '' ?>>Domiciliation professionnelle</option>
                            <option value="Domiciliation simple" <?= (string) $contratData['contrat_type'] === 'Domiciliation simple' ? 'selected' : '' ?>>Domiciliation simple</option>
                            <option value="autre" <?= (string) $contratData['contrat_type'] === 'autre' ? 'selected' : '' ?>>Autre (specifier)</option>
                        </select>
                    </div>
                </label>
                <label class="field" data-show-if="contrat_type" data-show-value="autre">
                    <span>Autre type</span>
                    <input name="contrat_type_autre" value="<?= e((string) ($contratData['contrat_type_autre'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Date du contrat</span>
                    <input type="date" name="contrat_date" value="<?= e((string) ($contratData['contrat_date'] ?: date('Y-m-d'))) ?>">
                </label>
                <label class="field">
                    <span>Type contrat domiciliation</span>
                    <select name="contrat_type_domiciliation">
                        <option value="">Selectionner</option>
                        <?php foreach (['Personne Morale', 'Personne Physique', 'Association', 'Fondation', 'Autres'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $contratData['contrat_type_domiciliation'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <h3 class="section-title">Periode</h3>
                <label class="field">
                    <span>Date de debut</span>
                    <input type="date" name="contrat_date_debut" data-date-debut value="<?= e((string) ($contratData['contrat_date_debut'] ?: date('Y-m-d'))) ?>">
                </label>
                <label class="field">
                    <span>Duree (mois)</span>
                    <input type="number" name="contrat_duree_mois" data-duree-mois value="<?= e((string) $contratData['contrat_duree_mois']) ?>">
                </label>
                <label class="field">
                    <span>Date de fin</span>
                    <input type="date" name="contrat_date_fin" data-date-fin value="<?= e((string) $contratData['contrat_date_fin']) ?>" readonly>
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="contrat_statut">
                        <?php foreach (['actif', 'expire', 'brouillon'] as $st): ?>
                            <option value="<?= e($st) ?>" <?= (string) $contratData['contrat_statut'] === $st ? 'selected' : '' ?>>
                                <?= e(ucfirst($st)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <h3 class="section-title">Loyer (Initial)</h3>
                <label class="field">
                    <span>Loyer HT (Mois)</span>
                    <input type="number" step="0.01" name="contrat_loyer_ht" data-loyer-ht value="<?= e((string) $contratData['contrat_loyer_ht']) ?>">
                </label>
                <label class="field">
                    <span>TVA %</span>
                    <select name="contrat_tva_pourcent" data-tva-pourcent>
                        <option value="">Selectionner</option>
                        <option value="7" <?= (string) $contratData['contrat_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                        <option value="10" <?= (string) $contratData['contrat_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                        <option value="14" <?= (string) $contratData['contrat_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                        <option value="20" <?= (string) $contratData['contrat_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                    </select>
                </label>
                <label class="field">
                    <span>Loyer TTC (Mois)</span>
                    <input type="text" name="contrat_loyer_ttc" data-loyer-ttc-mois value="<?= e((string) ($contratData['contrat_loyer_ttc'] ?? '')) ?>" readonly>
                </label>
                <label class="field">
                    <span>Montant Total du Loyer</span>
                    <input type="text" name="contrat_total_ht" data-montant-total-loyer value="<?= e((string) ($contratData['contrat_total_ht'] ?? '')) ?>" readonly>
                </label>

                <h3 class="section-title">Renouvellement</h3>
                <label class="field">
                    <span>Type renouvellement</span>
                    <select name="contrat_type_renouvellement">
                        <option value="">Selectionner</option>
                        <?php foreach (['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $contratData['contrat_type_renouvellement'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Loyer HT (Mois)</span>
                    <input type="number" step="0.01" name="contrat_renouv_loyer_ht" data-loyer-ht-renouvellement value="<?= e((string) ($contratData['contrat_renouv_loyer_ht'] ?: '166.67')) ?>">
                </label>
                <label class="field">
                    <span>TVA %</span>
                    <select name="contrat_renouv_tva_pourcent" data-tva-renouvellement-pourcent>
                        <option value="">Selectionner</option>
                        <option value="7" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                        <option value="10" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                        <option value="14" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                        <option value="20" <?= (string) $contratData['contrat_renouv_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                    </select>
                </label>
                <label class="field">
                    <span>Loyer TTC (Mois)</span>
                    <input type="text" name="contrat_renouv_loyer_ttc" data-loyer-ttc-renouvellement-mois value="<?= e((string) ($contratData['contrat_renouv_loyer_ttc'] ?? '')) ?>" readonly>
                </label>
                <label class="field">
                    <span>Montant Total du Loyer</span>
                    <input type="text" name="contrat_renouv_total_ht" data-montant-total-renouvellement value="<?= e((string) ($contratData['contrat_renouv_total_ht'] ?? '')) ?>" readonly>
                </label>

                <label class="field full">
                    <span>Notes</span>
                    <textarea name="contrat_notes"><?= e((string) $contratData['contrat_notes']) ?></textarea>
                </label>
            </div>
            <div class="table-actions">
                <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="mdi mdi-arrow-left"></span> Retour</button>
                <button class="btn btn-info" type="button" data-fill-test><span class="mdi mdi-auto-fix"></span> Remplir automatiquement</button>
                <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="mdi mdi-arrow-right"></span> Suivant</button>
            </div>
        </form>
    <?php elseif ($step === 4): ?>
        <div class="stack">
            <div class="section-header">
                <div>
                    <h2>Recapitulatif du dossier</h2>
                    <p class="help-text">Verifiez les informations avant de generer les documents.</p>
                </div>
            </div>

            <article class="card">
                <div class="section-header">
                    <h3>Societe</h3>
                    <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => 1])) ?>"><span class="mdi mdi-pencil"></span> Modifier</a>
                </div>
                <div class="info-grid">
                    <div><span>Raison sociale</span><strong><?= e($societeData['societe_raison_sociale'] ?: '-') ?></strong></div>
                    <div><span>Forme juridique</span><strong><?= e($societeData['societe_forme_juridique'] ?: '-') ?></strong></div>
                    <div><span>Dossier domiciliation</span><strong><?= e($societeData['societe_dossier'] ?: '-') ?></strong></div>
                    <div><span>ICE</span><strong><?= e($societeData['societe_ice'] ?: '-') ?></strong></div>
                    <div><span>RC</span><strong><?= e($societeData['societe_rc'] ?: '-') ?></strong></div>
                    <div><span>IF</span><strong><?= e($societeData['societe_if'] ?: '-') ?></strong></div>
                    <div><span>Capital</span><strong><?= e($societeData['societe_capital'] ?: '-') ?></strong></div>
                    <div><span>Part social</span><strong><?= e($societeData['societe_part_social'] ?: '-') ?></strong></div>
                    <div><span>Valeur nominale</span><strong><?= e($societeData['societe_valeur_nominale'] ?: '-') ?></strong></div>
                    <div><span>Adresse</span><strong><?= e($societeData['societe_adresse_siege'] ?: '-') ?></strong></div>
                    <div><span>Ville</span><strong><?= e($societeData['societe_ville'] ?: '-') ?></strong></div>
                    <div><span>Tribunal</span><strong><?= e($societeData['societe_tribunal'] ?: '-') ?><?= $currentTribunalType ? ' ('.e($currentTribunalType).')' : '' ?></strong></div>
                    <div><span>Email</span><strong><?= e($societeData['societe_email'] ?: '-') ?></strong></div>
                    <div><span>Telephone</span><strong><?= e($societeData['societe_telephone'] ?: '-') ?></strong></div>
                    <div class="full"><span>Activites (Statuts)</span><strong><?= e(!empty($societeData['societe_activites_statuts']) ? (string) $societeData['societe_activites_statuts'] : '-') ?></strong></div>
                    <div class="full"><span>Activites (OMPIC)</span><strong><?= e(!empty($societeData['societe_activites_ompic']) ? fetch_activites_ompic_display($pdo ?? null, (string) $societeData['societe_activites_ompic']) : '-') ?></strong></div>
                    <div><span>Type generation</span><strong><?= e($societeData['type_generation'] ?: '-') ?></strong></div>
                    <div><span>Procedure</span><strong><?= e($societeData['procedure_creation'] ?: '-') ?></strong></div>
                    <div><span>Mode depot</span><strong><?= e($societeData['mode_depot_creation'] ?: '-') ?></strong></div>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <h3>Associes (<?= count($associesData) ?>)</h3>
                    <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => 2])) ?>"><span class="mdi mdi-pencil"></span> Modifier</a>
                </div>
                <?php foreach ($associesData as $i => $associe): ?>
                    <div class="info-grid" style="border-bottom:1px solid var(--border);padding-bottom:0.75rem;margin-bottom:0.75rem">
                        <div><span>Nom complet</span><strong><?= e($associe['associe_nom_complet'] ?: '-') ?></strong></div>
                        <div><span>CIN</span><strong><?= e($associe['associe_cin'] ?: '-') ?></strong></div>
                        <div><span>Nationalite</span><strong><?= e($associe['associe_nationalite'] ?: '-') ?></strong></div>
                        <div><span>Date naissance</span><strong><?= e($associe['associe_date_naissance'] ?: '-') ?></strong></div>
                        <div><span>Lieu naissance</span><strong><?= e($associe['associe_lieu_naissance'] ?: '-') ?></strong></div>
                        <div><span>Qualite</span><strong><?= e($associe['associe_qualite'] ?: '-') ?></strong></div>
                        <div><span>Parts</span><strong><?= e((string) ($associe['associe_parts'] ?? '-')) ?></strong></div>
                        <div><span>Capital detenu</span><strong><?= e((string) ($associe['associe_capital_detenu'] ?? '-')) ?></strong></div>
                        <div><span>Gerant</span><strong><?= ((string) ($associe['associe_est_gerant'] ?? '0') === '1') ? 'Oui' : 'Non' ?></strong></div>
                    </div>
                <?php endforeach; ?>
            </article>

            <article class="card">
                <div class="section-header">
                    <h3>Contrat</h3>
                    <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => 3])) ?>"><span class="mdi mdi-pencil"></span> Modifier</a>
                </div>
                <div class="info-grid">
                    <div><span>Type contrat</span><strong><?= e($contratData['contrat_type'] ?: '-') ?></strong></div>
                    <div><span>Type domiciliation</span><strong><?= e($contratData['contrat_type_domiciliation'] ?: '-') ?></strong></div>
                    <div><span>Date contrat</span><strong><?= e($contratData['contrat_date'] ?: '-') ?></strong></div>
                    <div><span>Date debut</span><strong><?= e($contratData['contrat_date_debut'] ?: '-') ?></strong></div>
                    <div><span>Date fin</span><strong><?= e($contratData['contrat_date_fin'] ?: '-') ?></strong></div>
                    <div><span>Duree (mois)</span><strong><?= e((string) ($contratData['contrat_duree_mois'] ?: '-')) ?></strong></div>
                    <div><span>Loyer HT</span><strong><?= e($contratData['contrat_loyer_ht'] ?: '-') ?></strong></div>
                    <div><span>TVA %</span><strong><?= e((string) ($contratData['contrat_tva_pourcent'] ?: '-')) ?></strong></div>
                    <div><span>Loyer TTC/mois</span><strong><?= e($contratData['contrat_loyer_ttc'] ?: '-') ?></strong></div>
                    <div><span>Total loyer</span><strong><?= e($contratData['contrat_total_ht'] ?: '-') ?></strong></div>
                    <div><span>Renouvellement</span><strong><?= e($contratData['contrat_type_renouvellement'] ?: '-') ?></strong></div>
                    <div><span>Statut</span><strong><?= e($contratData['contrat_statut'] ?: '-') ?></strong></div>
                </div>
            </article>

            <form method="post" class="table-actions" style="margin-top:1rem">
                <?= csrf_input() ?>
                <input type="hidden" name="step" value="4">
                <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="mdi mdi-arrow-left"></span> Retour</button>
                <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="mdi mdi-arrow-right"></span> Suivant</button>
            </form>
        </div>
    <?php elseif ($step === 5): ?>
        <?php
        require_once __DIR__ . '/../src/TemplateAnalyzer.php';

        $templatesConfig = require __DIR__ . '/../config/templates.php';
        $templatesDir = __DIR__ . '/../templates';
        $outputDir = __DIR__ . '/../output';

        $legalForm = $societeData['societe_forme_juridique'] ?? '';
        $allTemplates = TemplateAnalyzer::scanTemplates($templatesDir);

        $folderMap = [
            'SARL-AU' => 'SARL AU',
            'SARL' => 'SARL',
            'SA' => 'SA',
        ];
        $targetFolder = $folderMap[$legalForm] ?? '';
        $filteredTemplates = [];
        foreach ($allTemplates as $tpl) {
            if ($targetFolder !== '' && $tpl['folder'] === $targetFolder) {
                $filteredTemplates[] = $tpl;
            } elseif ($tpl['folder'] === '_Racine-Actifs') {
                $filteredTemplates[] = $tpl;
            }
        }

        $templatesByType = [];
        foreach ($filteredTemplates as $tpl) {
            $type = $tpl['doc_type'];
            $templatesByType[$type][] = $tpl;
        }

        $generatedFiles = $wizard['generated_files'] ?? [];
        ?>
        <div class="stack">
            <div class="section-header">
                <div>
                    <h2>Generation des documents</h2>
                    <p class="help-text">Selectionnez les templates a generer pour <?= e($societeData['societe_raison_sociale'] ?: 'la societe') ?>.</p>
                </div>
            </div>

            <?php if ($filteredTemplates): ?>
                <form method="post" class="stack" id="wizard-gen-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="step" value="5">
                    <input type="hidden" name="nav_action" value="generate">

                    <div class="section-header">
                        <h3 style="margin:0;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-secondary)">
                            Templates disponibles
                        </h3>
                        <div class="table-actions">
                            <a class="btn-icon" href="#" id="select-all-wizard" title="Tout selectionner"><span class="mdi mdi-check-all"></span></a>
                            <label class="pdf-toggle">
                                <input type="checkbox" name="pdf" value="1" checked>
                                <span class="mdi mdi-file-pdf"></span> PDF
                            </label>
                        </div>
                    </div>

                    <?php foreach ($templatesByType as $docType => $typeTemplates): ?>
                        <div class="template-group">
                            <span class="template-group-label"><?= e($templatesConfig['document_types'][$docType] ?? $docType) ?></span>
                            <?php foreach ($typeTemplates as $tpl): ?>
                                <label class="template-item">
                                    <input type="checkbox" name="templates[]" value="<?= e($tpl['path']) ?>" checked class="template-check">
                                    <span class="mdi mdi-file-word template-item-icon"></span>
                                    <div class="template-item-body">
                                        <span class="template-item-name"><?= e(basename($tpl['path'])) ?></span>
                                        <span class="template-item-meta"><?= count($tpl['variables']) ?> variable(s)</span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="table-actions" style="margin-top:1rem">
                        <button class="btn btn-next" type="submit"><span class="mdi mdi-file-sync"></span> Generer les documents</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <span class="mdi mdi-file-document-outline" style="font-size:2rem;color:var(--text-secondary)"></span>
                    <p class="table-empty">Aucun template disponible pour cette forme juridique.</p>
                </div>
            <?php endif; ?>

            <?php if ($generatedFiles): ?>
                <article class="card stack">
                    <div class="section-header">
                        <h3>Documents generes</h3>
                        <p class="help-text"><?= count($generatedFiles) ?> fichier(s) genere(s)</p>
                    </div>
                    <div class="generated-list">
                        <?php foreach ($generatedFiles as $file): ?>
                            <div class="generated-item">
                                <div class="generated-item-info">
                                    <span class="mdi mdi-file-word" style="color:var(--primary);font-size:1.2rem"></span>
                                    <div>
                                        <strong><?= e($file['name']) ?></strong>
                                        <?php if (file_exists($file['docx'])): ?>
                                            <span class="help-text"><?= number_format(filesize($file['docx']) / 1024, 1) ?> Ko</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="table-actions">
                                    <a class="btn btn-secondary" href="<?= e(str_replace(__DIR__ . '/../', '', $file['docx'])) ?>" download>
                                        <span class="mdi mdi-download"></span> DOCX
                                    </a>
                                    <?php if ($file['pdf']): ?>
                                        <a class="btn" href="<?= e(str_replace(__DIR__ . '/../', '', $file['pdf'])) ?>" download>
                                            <span class="mdi mdi-file-pdf"></span> PDF
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endif; ?>

            <form method="post" class="table-actions" style="margin-top:1rem">
                <?= csrf_input() ?>
                <input type="hidden" name="step" value="5">
                <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="mdi mdi-arrow-left"></span> Retour</button>
                <button class="btn btn-next" type="submit" name="nav_action" value="finish"><span class="mdi mdi-check-circle"></span> Creer le dossier complet</button>
            </form>
        </div>

        <script>
        document.getElementById('select-all-wizard')?.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('wizard-gen-form');
            const checkboxes = form.querySelectorAll('input[name="templates[]"]');
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            checkboxes.forEach(c => c.checked = !allChecked);
        });
        </script>
    <?php endif; ?>
</section>
