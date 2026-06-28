<?php

declare(strict_types=1);

if (is_post() && $step === 6) {
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($navAction === 'back') {
        $_SESSION['creation_wizard']['generated_files'] = [];
        redirect_to('creation', ['step' => 5]);
    }

    if ($navAction === 'create_dossier') {
        if (!(($pdo ?? null) instanceof PDO)) {
            set_flash('error', 'Connexion MySQL indisponible.');
            redirect_to('creation', ['step' => 6]);
        }

        try {
            $pdo->beginTransaction();

            $societeStmt = $pdo->prepare('
                INSERT INTO societes (
                    societe_dossier, societe_raison_sociale, societe_forme_juridique, societe_ice, societe_date_ice, societe_rc, societe_if,
                    societe_activites_statuts, societe_activites_ompic,
                    societe_capital, societe_part_social, societe_valeur_nominale, societe_date_exp_cert_neg, societe_adresse_siege, societe_ville, societe_tribunal, societe_email,
                    societe_telephone, societe_type_generation, societe_procedure_creation, societe_mode_depot, societe_tribunal_type, created_by
                ) VALUES (
                    :societe_dossier, :societe_raison_sociale, :societe_forme_juridique, :societe_ice, :societe_date_ice, :societe_rc, :societe_if,
                    :societe_activites_statuts, :societe_activites_ompic,
                    :societe_capital, :societe_part_social, :societe_valeur_nominale, :societe_date_exp_cert_neg, :societe_adresse_siege, :societe_ville, :societe_tribunal, :societe_email,
                    :societe_telephone, :societe_type_generation, :societe_procedure_creation, :societe_mode_depot, :societe_tribunal_type, :created_by
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
                'societe_type_generation' => $wizard['societe']['societe_type_generation'] ?? '',
                'societe_procedure_creation' => $wizard['societe']['societe_procedure_creation'] ?? '',
                'societe_mode_depot' => $wizard['societe']['societe_mode_depot'] ?? '',
                'societe_tribunal_type' => $wizard['societe']['societe_tribunal_type'] ?? '',
                'created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
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

            $pdo->commit();

            $_SESSION['creation_wizard']['societe_id'] = $societeId;

            $formeCrea = $wizard['societe']['societe_forme_juridique'] ?? 'PP';
            $raisonCrea = $wizard['societe']['societe_raison_sociale'] ?? 'Dossier-' . $societeId;
            $clientCrea = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $raisonCrea)));
            $clientCrea = trim(preg_replace('/-+/', '-', $clientCrea), '-');
            $folderDateCrea = $wizard['contrat']['contrat_date'] ?? date('Y-m-d');
            $dossierName = $folderDateCrea . '_' . $formeCrea . '_' . $clientCrea;
            $dossierName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $dossierName), '-');

            $uploadedDocs = $wizard['uploaded_docs'] ?? [];
            if ($uploadedDocs !== [] && ($pdo ?? null) instanceof PDO) {
                $dossierUploadDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation/' . $dossierName . '/_uploads';
                if (!is_dir($dossierUploadDir)) {
                    mkdir($dossierUploadDir, 0777, true);
                }

                $insertDocStmt = $pdo->prepare('
                    INSERT INTO uploaded_docs (societe_id, doc_type, associe_idx, filename_original, filename_stored, filepath, taille_ko)
                    VALUES (:societe_id, :doc_type, :associe_idx, :filename_original, :filename_stored, :filepath, :taille_ko)
                ');

                $socName = trim(preg_replace('/[^a-zA-Z0-9-]/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $wizard['societe']['societe_raison_sociale'] ?? 'Societe')));
                $socName = preg_replace('/_+/', '_', $socName);
                $socName = trim($socName, '_');
                $dateStr = date('Y-m-d');

                if (isset($uploadedDocs['certificat_negatif'])) {
                    $cn = $uploadedDocs['certificat_negatif'];
                    $ext = pathinfo($cn['original'], PATHINFO_EXTENSION);
                    $newFilename = $dateStr . '_CN_' . $socName . '.' . $ext;
                    $newPath = $dossierUploadDir . '/' . $newFilename;
                    if (file_exists($cn['path'])) {
                        rename($cn['path'], $newPath);
                        $insertDocStmt->execute([
                            'societe_id' => $societeId,
                            'doc_type' => 'certificat_negatif',
                            'associe_idx' => null,
                            'filename_original' => $newFilename,
                            'filename_stored' => $newFilename,
                            'filepath' => $newPath,
                            'taille_ko' => $cn['taille_ko'],
                        ]);
                    }
                }

                if (isset($uploadedDocs['cin_gerants']) && is_array($uploadedDocs['cin_gerants'])) {
                    foreach ($uploadedDocs['cin_gerants'] as $associeIdx => $cin) {
                        $ext = pathinfo($cin['original'], PATHINFO_EXTENSION);
                        $nom = trim(preg_replace('/[^a-zA-Z0-9-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $wizard['associes'][$associeIdx]['associe_nom'] ?? 'Nom')));
                        $prenom = trim(preg_replace('/[^a-zA-Z0-9-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $wizard['associes'][$associeIdx]['associe_prenom'] ?? 'Prenom')));
                        $newFilename = $dateStr . '_CIN_' . $nom . '_' . $prenom . '_' . $socName . '.' . $ext;
                        $newPath = $dossierUploadDir . '/' . $newFilename;
                        if (file_exists($cin['path'])) {
                            rename($cin['path'], $newPath);
                            $insertDocStmt->execute([
                                'societe_id' => $societeId,
                                'doc_type' => 'cin_gerant',
                                'associe_idx' => (int) $associeIdx,
                                'filename_original' => $newFilename,
                                'filename_stored' => $newFilename,
                                'filepath' => $newPath,
                                'taille_ko' => $cin['taille_ko'],
                            ]);
                        }
                    }
                }
            }

            $creaDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation/' . $dossierName;
            if (!is_dir($creaDir)) {
                mkdir($creaDir, 0777, true);
            }

            set_flash('success', 'Le dossier a ete cree avec succes.');
            log_activity($pdo, 'create', 'dossier', $societeId, $wizard['societe']['societe_raison_sociale'] ?? ('Dossier #' . $societeId), json_encode([
                'forme_juridique' => $formeCrea,
                'nb_associes' => count($wizard['associes'] ?? []),
                'type_generation' => $wizard['societe']['societe_type_generation'] ?? '',
            ]));
            redirect_to('creation', ['step' => 6]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash('error', 'Erreur lors de la creation du dossier: ' . $exception->getMessage());
            redirect_to('creation', ['step' => 6]);
        }
    }

    if ($navAction === 'generate') {
        if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../vendor/autoload.php';
        }
        require_once __DIR__ . '/../../../src/analyseur_templates.php';
        require_once __DIR__ . '/../../../src/rendu_document.php';

        $templatesDir = __DIR__ . '/../../../templates';
        $outputDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $selectedPaths = $_POST['templates'] ?? [];
        $forme = $wizard['societe']['societe_forme_juridique'] ?? 'PP';

        $context = DocumentRenderer::buildContextFromSession($wizard, $pdo ?? null);
        $today = date('Y-m-d');
        $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $wizard['societe']['societe_raison_sociale'] ?? 'Client')));
        $clientName = preg_replace('/-+/', '-', $clientName);
        $clientName = trim($clientName, '-');

        $folderDate = $wizard['contrat']['contrat_date'] ?? $today;
        $folderName = $folderDate . '_' . $forme . '_' . $clientName;
        $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
        $outputDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation/' . $folderName;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        $sanitizedForme = str_replace(' ', '_', $forme);
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
                $base = $sanitizedForme . '_' . $today . '_' . $docType . '_' . $clientName;
                $outName = $base . '_Brouillon.docx';
                $docxPath = $renderer->render($context, $outName);

                $result = [
                    'docx' => $docxPath,
                    'pdf' => null,
                    'name' => $outName,
                ];

                $generatedFiles[] = $result;
            } catch (\Throwable $e) {
                set_flash('error', 'Erreur sur ' . basename($path) . ' : ' . $e->getMessage());
            }
        }

        if (count($generatedFiles) > 0) {
            $_SESSION['creation_wizard']['generated_files'] = $generatedFiles;

            $societeId = $wizard['societe_id'] ?? null;
            if ($societeId && ($pdo ?? null) instanceof PDO) {
                $insertDocStmt = $pdo->prepare('INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko) VALUES (:societe_id, :template_source, :doc_type, :fichier_docx, :fichier_pdf, :taille_ko)');
                foreach ($generatedFiles as $gf) {
                    $parts = explode('_', basename((string) $gf['name']));
                    $docType = $parts[2] ?? null;
                    $insertDocStmt->execute([
                        'societe_id' => $societeId,
                        'template_source' => null,
                        'doc_type' => $docType,
                        'fichier_docx' => $gf['docx'],
                        'fichier_pdf' => null,
                        'taille_ko' => file_exists((string) $gf['docx']) ? round(filesize((string) $gf['docx']) / 1024, 1) : null,
                    ]);
                }
            }

            set_flash('success', count($generatedFiles) . ' document(s) genere(s).');
        }

        redirect_to('creation', ['step' => 6]);
    }

    if ($navAction === 'generate_single') {
        header('Content-Type: application/json');
        try {
            if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../../vendor/autoload.php';
            }
            require_once __DIR__ . '/../../../src/analyseur_templates.php';
            require_once __DIR__ . '/../../../src/rendu_document.php';

            $templatesDir = __DIR__ . '/../../../templates';
            $outputDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation';
            if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

            $path = $_POST['template_path'] ?? '';

            $realTpl = realpath($templatesDir);
            if (!file_exists($path) || !str_starts_with(realpath($path), $realTpl)) {
                throw new \RuntimeException('Template invalide ou introuvable');
            }

            $context = DocumentRenderer::buildContextFromSession($wizard, $pdo ?? null);
            $today = date('Y-m-d');
            $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $wizard['societe']['societe_raison_sociale'] ?? 'Client')));
            $clientName = preg_replace('/-+/', '-', $clientName);
            $clientName = trim($clientName, '-');
            $forme = $wizard['societe']['societe_forme_juridique'] ?? 'PP';

            $folderDate = $wizard['contrat']['contrat_date'] ?? $today;
            $folderName = $folderDate . '_' . $forme . '_' . $clientName;
            $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
            $outputDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation/' . $folderName;
            if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

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

            $result = ['docx' => $docxPath, 'pdf' => null, 'name' => $outName];
            if (!isset($_SESSION['creation_wizard']['generated_files'])) {
                $_SESSION['creation_wizard']['generated_files'] = [];
            }
            $_SESSION['creation_wizard']['generated_files'][] = $result;

            $societeId = $wizard['societe_id'] ?? null;
            if ($societeId && ($pdo ?? null) instanceof PDO) {
                $p2 = explode('_', $outName);
                $dt = $p2[2] ?? null;
                $ins = $pdo->prepare('INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko) VALUES (:societe_id, :template_source, :doc_type, :fichier_docx, :fichier_pdf, :taille_ko)');
                $ins->execute([
                    'societe_id' => $societeId,
                    'template_source' => basename($path),
                    'doc_type' => $dt,
                    'fichier_docx' => $docxPath,
                    'fichier_pdf' => null,
                    'taille_ko' => file_exists($docxPath) ? round(filesize($docxPath) / 1024, 1) : null,
                ]);
            }

            echo json_encode(['success' => true, 'name' => basename((string) $docxPath)]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($navAction === 'generate_clause') {
        $type = $_POST['clause_type'] ?? '';
        if ($type !== '' && ClaudeService::isAvailable()) {
            $dossierData = $wizard['societe'] ?? [];
            $dossierData['forme_juridique'] = $dossierData['societe_forme_juridique'] ?? '';
            $result = ClaudeService::generateClause($dossierData, $type);
            $_SESSION['creation_wizard']['clause_result'] = ['type' => $type, 'text' => $result ?? 'Erreur lors de la generation.'];
        } elseif ($type === '') {
            set_flash('error', 'Type de clause non specifie.');
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible.");
        }
        redirect_to('creation', ['step' => 6]);
    }

    if ($navAction === 'validate') {
        if (ClaudeService::isAvailable()) {
            $dossierData = [
                'societe' => $wizard['societe'] ?? [],
                'associes' => $wizard['associes'] ?? [],
                'contrat' => $wizard['contrat'] ?? [],
            ];
            $result = ClaudeService::validateDossier($dossierData);
            $_SESSION['creation_wizard']['validation_result'] = $result;
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible.");
        }
        redirect_to('creation', ['step' => 6]);
    }

    if ($navAction === 'terminer') {
        $societeId = $wizard['societe_id'] ?? null;
        _cleanup_tmp_uploads();
        unset($_SESSION['creation_wizard']);
        set_flash('success', 'Dossier cree avec succes.');
        redirect_to('societe', ['id' => (string) $societeId]);
    }
}

if ($step === 6):
    $dossierCreated = isset($wizard['societe_id']);
    $societeId = $wizard['societe_id'] ?? null;

    require_once __DIR__ . '/../../../src/analyseur_templates.php';

    $templatesConfig = require __DIR__ . '/../../../config/templates.php';
    $templatesDir = __DIR__ . '/../../../templates';
    $outputDir = __DIR__ . '/../../../dossiers_generer/dossiers_domiciliation';

    $legalForm = $societeData['societe_forme_juridique'] ?? '';
    $allTemplates = TemplateAnalyzer::scanTemplates($templatesDir);

    $targetFolder = ($legalForm !== '') ? fetch_legal_form_template_folder($pdo ?? null, $legalForm) : '';
    if ($targetFolder !== '') {
        ensure_template_folder($targetFolder);
    }
    $useRacine = ($_GET['use_racine'] ?? '') === '1';
    $filteredTemplates = [];
    $racineTemplates = [];
    foreach ($allTemplates as $tpl) {
        if ($tpl['folder'] === '_Racine-Actifs') {
            $racineTemplates[] = $tpl;
        }
    }
    if ($targetFolder !== '' && !$useRacine) {
        foreach ($allTemplates as $tpl) {
            if ($tpl['folder'] === $targetFolder) {
                $filteredTemplates[] = $tpl;
            }
        }
    } elseif ($targetFolder !== '' && $useRacine) {
        $filteredTemplates = $racineTemplates;
    } else {
        $filteredTemplates = $racineTemplates;
    }

    $templatesByType = [];
    foreach ($filteredTemplates as $tpl) {
        $type = $tpl['doc_type'];
        $templatesByType[$type][] = $tpl;
    }

    $generationType = $societeData['societe_type_generation'] ?? '';
    if ($generationType !== '' && isset($templatesConfig['template_mapping'][$generationType])) {
        $allowedTypes = $templatesConfig['template_mapping'][$generationType];
        $templatesByType = array_intersect_key($templatesByType, array_flip($allowedTypes));
    }

    $generatedFiles = $wizard['generated_files'] ?? [];
?>
<div class="stack">
    <div class="section-header">
        <div>
            <h2>Etape 6 — Generation des documents</h2>
            <p class="help-text">Creez d'abord le dossier, puis generez les documents.</p>
        </div>
        <?php if ($dossierCreated): ?>
            <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => $societeId])) ?>">
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
                    <p class="help-text">Enregistrez la societe, les associes et le contrat en base de donnees.</p>
                </div>
                <?php if ($dossierCreated): ?>
                    <span class="step-badge">Fait</span>
                <?php endif; ?>
            </div>
            <?php
            $validationResult = $_SESSION['creation_wizard']['validation_result'] ?? null;
            $clauseResult = $_SESSION['creation_wizard']['clause_result'] ?? null;
            if ($validationResult) {
                unset($_SESSION['creation_wizard']['validation_result']);
            }
            if ($clauseResult) {
                unset($_SESSION['creation_wizard']['clause_result']);
            }
            ?>
            <?php if (!$dossierCreated): ?>
                <div class="table-actions" style="margin-top:8px;flex-wrap:wrap">
                    <form method="post" style="display:inline">
                        <?= csrf_input() ?>
                        <input type="hidden" name="step" value="6">
                        <button class="btn btn-next" type="submit" name="nav_action" value="create_dossier">
                            <span class="material-symbols-outlined">create_new_folder</span> Creer le dossier
                        </button>
                    </form>
                    <form method="post" style="display:inline">
                        <?= csrf_input() ?>
                        <input type="hidden" name="step" value="6">
                        <button class="btn btn-info" type="submit" name="nav_action" value="validate">
                            <span class="material-symbols-outlined">smart_toy</span> Valider avec IA
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($validationResult && is_array($validationResult)): ?>
                <div class="card" style="margin-top:12px;padding:12px">
                    <div class="section-header">
                        <h4><span class="material-symbols-outlined" style="color:var(--info)">smart_toy</span> Validation IA</h4>
                        <span style="font-weight:600;color:<?= ($validationResult['valide'] ?? false) ? 'var(--success)' : 'var(--danger)' ?>">
                            <?= ($validationResult['valide'] ?? false) ? 'Dossier valide' : 'Dossier Non valide' ?>
                        </span>
                    </div>
                    <?php if (isset($validationResult['points']) && is_array($validationResult['points'])): ?>
                        <ul style="margin:8px 0 0;padding-left:1rem">
                        <?php foreach ($validationResult['points'] as $point): ?>
                            <?php $ptype = $point['type'] ?? 'info'; ?>
                            <li style="color:<?= $ptype === 'error' ? 'var(--danger)' : ($ptype === 'warning' ? 'var(--warning)' : 'var(--text)') ?>">
                                <?= e($point['message'] ?? '') ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="step-card <?= $dossierCreated ? ($generatedFiles ? 'done' : 'active') : 'waiting' ?>">
            <div class="step-card-header">
                <span class="step-num">2</span>
                <div>
                    <h3 style="display:none">Generer les documents</h3>
                    <p class="help-text">Selectionnez les templates a generer pour <?= e($societeData['societe_raison_sociale'] ?: 'la societe') ?>.</p>
                </div>
            </div>

            <?php if (!$dossierCreated): ?>
                <p class="help-text" style="margin:12px 0 0;font-style:italic">Creez d'abord le dossier pour acceder aux templates.</p>
            <?php elseif ($filteredTemplates): ?>
                <form method="post" class="stack" id="wizard-gen-form" style="gap:8px;margin-top:8px">
                    <?= csrf_input() ?>
                    <input type="hidden" name="step" value="6">
                    <input type="hidden" name="nav_action" value="generate">

                    <div style="display:flex;align-items:center;gap:8px">
                        <label class="pdf-toggle" style="margin:0">
                            <span class="material-symbols-outlined">picture_as_pdf</span> PDF
                        </label>
                        <a class="btn-icon" href="#" id="select-all-wizard" title="Tout selectionner"><span class="material-symbols-outlined">select_all</span></a>
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
                                    <?php $typeLabel = $templatesConfig['document_types'][$docType] ?? $docType; ?>
                                    <?php $tplCount = count($typeTemplates); ?>
                                    <?php foreach ($typeTemplates as $i => $tpl): ?>
                                        <tr>
                                            <td class="col-check"><input type="checkbox" name="templates[]" value="<?= e($tpl['path']) ?>" checked class="template-check"></td>
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

                    <div style="display:flex;justify-content:flex-end;margin-top:4px">
                        <?php if ($generatedFiles): ?>
                        <button class="btn btn-next" type="submit" data-confirm="ATTENTION : Les documents existants seront ecrases. Voulez-vous continuer ?"><span class="material-symbols-outlined">sync</span> Regenerer les documents</button>
                        <?php else: ?>
                        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">sync</span> Generer les documents</button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php elseif ($targetFolder !== '' && !$useRacine): ?>
                <div class="empty-state" style="margin-top:8px">
                    <span class="material-symbols-outlined" style="font-size:2rem;color:var(--text-secondary)">description</span>
                    <p class="table-empty">Aucun template dans le dossier <strong><?= e($targetFolder) ?></strong> pour cette forme juridique.</p>
                    <?php if ($racineTemplates): ?>
                    <a class="btn btn-back" href="<?= e(app_url('creation', ['step' => $step, 'use_racine' => 1])) ?>" style="margin-top:8px">
                        <span class="material-symbols-outlined">folder_open</span> Utiliser les templates Racine par defaut (<?= count($racineTemplates) ?>)
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="margin-top:8px">
                    <span class="material-symbols-outlined" style="font-size:2rem;color:var(--text-secondary)">description</span>
                    <p class="table-empty">Aucun template disponible pour cette forme juridique.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($dossierCreated): ?>
        <div class="step-card <?= $clauseResult ? 'done' : 'active' ?>" style="margin-top:16px">
            <div class="step-card-header">
                <span class="step-num">3</span>
                <div>
                    <h3>Clauses juridiques</h3>
                    <p class="help-text">Generez des clauses juridiques avec l'assistant IA.</p>
                </div>
            </div>
            <div class="table-actions" style="margin-top:8px">
                <form method="post" style="display:inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="step" value="6">
                    <input type="hidden" name="clause_type" value="objet_social">
                    <button class="btn btn-info" type="submit" name="nav_action" value="generate_clause"><span class="material-symbols-outlined">smart_toy</span> Objet social</button>
                </form>
                <form method="post" style="display:inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="step" value="6">
                    <input type="hidden" name="clause_type" value="mention_legale">
                    <button class="btn btn-info" type="submit" name="nav_action" value="generate_clause"><span class="material-symbols-outlined">smart_toy</span> Mentions legales</button>
                </form>
                <form method="post" style="display:inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="step" value="6">
                    <input type="hidden" name="clause_type" value="clause_siege">
                    <button class="btn btn-info" type="submit" name="nav_action" value="generate_clause"><span class="material-symbols-outlined">smart_toy</span> Siege social</button>
                </form>
            </div>
            <?php if ($clauseResult): ?>
                <div class="card" style="margin-top:12px;padding:12px">
                    <div class="section-header">
                        <h4><span class="material-symbols-outlined" style="color:var(--info)">description</span> <?= e(ucfirst(str_replace('_', ' ', $clauseResult['type']))) ?></h4>
                    </div>
                    <div style="margin-top:8px;padding:12px;background:var(--panel-strong);border-radius:6px;font-size:0.9rem;line-height:1.6;white-space:pre-wrap"><?= e($clauseResult['text']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($generatedFiles): ?>
        <div class="step-card done" style="margin-top:16px">
            <div class="step-card-header">
                <span class="step-num">4</span>
                <div>
                    <h3>Documents generes</h3>
                    <p class="help-text"><?= count($generatedFiles) ?> fichier(s) genere(s)</p>
                </div>
            </div>
            <div class="table-scroll" style="overflow-x:auto;margin-top:8px">
                <table style="white-space:nowrap">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="select-all-generated"></th>
                            <th>Fichier</th>
                            <th>Taille</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generatedFiles as $i => $file): ?>
                            <tr>
                                <td><input type="checkbox" name="generated_files[]" value="<?= $i ?>" class="gen-file-check"></td>
                                <td>
                                    <span class="material-symbols-outlined" style="color:var(--primary);vertical-align:middle;margin-right:6px">article</span>
                                    <?= e($file['name']) ?>
                                </td>
                                <td><?php if (file_exists($file['docx'])): ?><?= number_format(filesize($file['docx']) / 1024, 1) ?> Ko<?php else: ?>-<?php endif; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn btn-secondary" href="<?= e(str_replace(dirname(__DIR__, 3) . '/', '', $file['docx'])) ?>" download>
                                            <span class="material-symbols-outlined">download</span> DOCX
                                        </a>
                                        <?php if ($file['pdf']): ?>
                                            <a class="btn" href="<?= e(str_replace(dirname(__DIR__, 3) . '/', '', $file['pdf'])) ?>" download>
                                                <span class="material-symbols-outlined">picture_as_pdf</span> PDF
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" class="table-actions" style="margin-top:0.75rem">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="6">
        <button class="btn btn-next" type="submit" name="nav_action" value="terminer"><span class="material-symbols-outlined">check_circle</span> Terminer</button>
        <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
    </form>
</div>

<script>
document.getElementById('select-all-generated')?.addEventListener('change', function(e) {
    document.querySelectorAll('.gen-file-check').forEach(c => c.checked = this.checked);
});

document.getElementById('select-all-wizard')?.addEventListener('click', function(e) {
    e.preventDefault();
    const form = document.getElementById('wizard-gen-form');
    const checkboxes = form.querySelectorAll('input[name="templates[]"]');
    const allChecked = Array.from(checkboxes).every(c => c.checked);
    checkboxes.forEach(c => c.checked = !allChecked);
});

document.getElementById('wizard-gen-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var checkboxes = Array.from(form.querySelectorAll('.template-check:checked'));
    if (checkboxes.length === 0) return;
    var csrf = form.querySelector('[name="csrf_token"]')?.value || '';
    var overlay = document.getElementById('gen-loading-overlay');
    var progressFill = overlay?.querySelector('.gen-progress-fill');
    var progressText = overlay?.querySelector('.gen-progress-text');
    var statusText = overlay?.querySelector('.gen-status-text');
    var total = checkboxes.length;
    var done = 0;
    if (overlay) overlay.classList.add('show');
    var next = function () {
        if (done >= total) {
            window.location.reload();
            return;
        }
        var cb = checkboxes[done];
        var path = cb.value;
        var pct = Math.round((done / total) * 100);
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressText) progressText.textContent = done + '/' + total + ' documents';
        if (statusText) statusText.textContent = 'Generation de : ' + path.split(/[\\/]/).pop();
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('step', '6');
        fd.append('nav_action', 'generate_single');
        fd.append('template_path', path);
        fetch(window.location.href, { method: 'POST', body: fd }).then(function (r) {
            return r.json();
        }).then(function (data) {
            done++;
            if (!data.success) {
                if (statusText) statusText.textContent = 'Erreur: ' + (data.error || 'inconnue');
            }
            next();
        }).catch(function (err) {
            done++;
            if (statusText) statusText.textContent = 'Erreur: ' + err.message;
            next();
        });
    };
    next();
});
</script>

<div id="gen-loading-overlay">
    <div class="loader-card">
        <div class="spinner"></div>
        <div class="gen-progress-text">0/0 documents</div>
        <div class="gen-progress-bar"><div class="gen-progress-fill"></div></div>
        <p class="gen-status-text">Preparation...</p>
    </div>
</div>
<?php endif; ?>
