<?php

declare(strict_types=1);

// ============ STEP 7 POST HANDLER ============
if (is_post() && $step === 7) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'generate';
    if ($navAction === 'back') {
        unset($_SESSION['_cession_overwrite_files'], $_SESSION['_cession_overwrite_docs']);
        $_SESSION['cession_wizard']['generated_files'] = [];
        redirect_to('cession', ['step' => 6]);
    }

    if ($navAction === 'create_dossier') {
        if (!(($pdo ?? null) instanceof PDO)) {
            set_flash('error', 'Connexion MySQL indisponible.');
            redirect_to('cession', ['step' => 7]);
        }

        try {
            $pdo->beginTransaction();

            // Create société if new
            if ($wizard['mode'] === 'nouvelle') {
                $wizard['societe_id'] = 0;
                $soc = $wizard['societe'];
$stmt = $pdo->prepare('INSERT INTO societes (societe_raison_sociale, societe_forme_juridique, societe_source, societe_ice, societe_date_ice, societe_date_exp_cert_neg, societe_rc, societe_if, societe_tp, societe_cnss, societe_capital, societe_part_social, societe_valeur_nominale, societe_adresse_siege, societe_ville, societe_tribunal, societe_tribunal_type, societe_email, societe_telephone, societe_dossier, societe_type_generation, societe_procedure_creation, societe_mode_depot, societe_activites_statuts, societe_activites_ompic, created_by) VALUES (:raison, :forme, :source, :ice, :date_ice, :date_exp_cert_neg, :rc, :ifis, :tp, :cnss, :capital, :parts, :vnom, :adr, :ville, :trib, :trib_type, :email, :tel, :dossier, :type_gen, :proc_creation, :mode_depot, :activites, :activites_ompic, :created_by)');
$stmt->execute([
    'raison' => $soc['societe_raison_sociale'] ?? '',
    'forme' => $soc['societe_forme_juridique'] ?? '',
    'source' => 'cession',
    'ice' => $soc['societe_ice'] ?? '',
    'date_ice' => $soc['societe_date_ice'] ?: null,
    'date_exp_cert_neg' => $soc['societe_date_exp_cert_neg'] ?: null,
    'rc' => $soc['societe_rc'] ?? '',
    'ifis' => $soc['societe_if'] ?? '',
    'tp' => $soc['societe_tp'] ?? '',
    'cnss' => $soc['societe_cnss'] ?? '',
    'capital' => !empty($soc['societe_capital']) ? parse_money($soc['societe_capital']) : null,
    'parts' => !empty($soc['societe_part_social']) ? (int) $soc['societe_part_social'] : null,
    'vnom' => !empty($soc['societe_valeur_nominale']) ? parse_money($soc['societe_valeur_nominale']) : null,
    'adr' => $soc['societe_adresse_siege'] ?? '',
    'ville' => $soc['societe_ville'] ?? '',
    'trib' => $soc['societe_tribunal'] ?? '',
    'trib_type' => $soc['societe_tribunal_type'] ?? '',
    'email' => $soc['societe_email'] ?? '',
    'tel' => $soc['societe_telephone'] ?? '',
    'dossier' => $soc['societe_dossier'] ?? '',
    'type_gen' => $soc['societe_type_generation'] ?? 'cession',
    'proc_creation' => $soc['societe_procedure_creation'] ?? '',
    'mode_depot' => $soc['societe_mode_depot'] ?? '',
    'activites' => $soc['societe_activites_statuts'] ?? '',
    'activites_ompic' => $soc['societe_activites_ompic'] ?? '',
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
            if ($societeId <= 0) {
                $pdo->rollBack();
                set_flash('error', 'Aucune societe selectionnee. Veuillez recommencer l\'assistant.');
                redirect_to('cession', ['step' => 1]);
            }
            if (!$selectedSociete && $societeId > 0 && ($pdo ?? null) instanceof PDO) {
                $selectedSociete = fetch_record($pdo, 'societes', $societeId);
            }
            if (!$selectedSociete) {
                $pdo->rollBack();
                set_flash('error', 'Societe introuvable (id ' . $societeId . ').');
                redirect_to('cession', ['step' => 1]);
            }
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
                'dat' => $wizard['cession_date'] ?: date('Y-m-d'),
                'motif' => $wizard['cession_motif'] ?: null,
                'status' => $wizard['cession_status'] ?? 'Valider',
                'cap' => $capitalAvant,
                'parts' => $partsAvant,
                'created_by' => ($user = current_user()) ? (int) $user['id'] : null,
            ]);
            $cessionId = (int) $pdo->lastInsertId();

            // Save pv_resolutions as JSON
            $pvJson = !empty($wizard['pv_resolutions']) ? json_encode($wizard['pv_resolutions'], JSON_UNESCAPED_UNICODE) : null;
            if ($pvJson !== null) {
                $pdo->prepare('UPDATE cessions SET pv_resolutions = :pv WHERE id = :id')->execute(['pv' => $pvJson, 'id' => $cessionId]);
            }

            // Auto-insert suivi etapes
            $suiviSteps = [
                ['redaction', 1], ['signature', 2], ['enregistrement', 3],
                ['legalisation', 4], ['depot_greffe', 5], ['publication_jal', 6],
                ['publication_bo', 7], ['rc_modificatif', 8], ['reglement', 9],
                ['remise', 10],
            ];
            $suiviStmt = $pdo->prepare('INSERT INTO cession_suivi_etapes (cession_id, etape, ordre) VALUES (:cid, :etape, :ordre)');
            foreach ($suiviSteps as $s) {
                $suiviStmt->execute(['cid' => $cessionId, 'etape' => $s[0], 'ordre' => $s[1]]);
            }

            // Insert cession_parts
            foreach ($wizard['parts'] as $p) {
                $stmt = $pdo->prepare('INSERT INTO cession_parts (cession_id, cedant_associe_id, cedant_nom_complet, cedant_cin, cedant_type, cessionnaire_associe_id, cessionnaire_nom_complet, cessionnaire_cin, cessionnaire_type, cessionnaire_civilite, cessionnaire_date_naissance, cessionnaire_lieu_naissance, cessionnaire_nationalite, cessionnaire_adresse, cessionnaire_telephone, cessionnaire_email, cessionnaire_qualite, cessionnaire_parts, cessionnaire_capital_detenu, cessionnaire_est_gerant, parts_cedees, prix_unitaire, prix_total, pourcentage, nommer_gerant) VALUES (:cid, :caid, :cnom, :ccin, :ctype, :csaid, :csnom, :cscin, :cstype, :csciv, :csdn, :csln, :csnat, :csadr, :cstel, :cseml, :csql, :csparts, :cscap, :csger, :parts, :pu, :pt, :pct, :ger)');
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
                    'cstel' => $p['cessionnaire_telephone'] ?? '',
                    'cseml' => $p['cessionnaire_email'] ?? '',
                    'csql' => $p['cessionnaire_qualite'] ?? '',
                    'csparts' => (int) ($p['cessionnaire_parts'] ?? 0),
                    'cscap' => $p['cessionnaire_capital_detenu'] ?? 0,
                    'csger' => $p['cessionnaire_est_gerant'] ?? 0,
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
                    $stmtA = $pdo->prepare('INSERT INTO associes (societe_id, associe_civilite, associe_nom_complet, associe_cin, associe_date_naissance, associe_lieu_naissance, associe_nationalite, associe_adresse, associe_telephone, associe_email, associe_qualite, associe_parts, associe_capital_detenu, associe_est_gerant) VALUES (:sid, :civ, :nom, :cin, :dn, :ln, :nat, :adr, :tel, :eml, :ql, :parts, :cap, :ger)');
                    $stmtA->execute([
                        'sid' => $societeId,
                        'civ' => $p['cessionnaire_civilite'] ?? 'M.',
                        'nom' => $p['cessionnaire_nom_complet'],
                        'cin' => $p['cessionnaire_cin'] ?: null,
                        'dn' => $p['cessionnaire_date_naissance'] ?: null,
                        'ln' => $p['cessionnaire_lieu_naissance'] ?: null,
                        'nat' => $p['cessionnaire_nationalite'] ?: null,
                        'adr' => $p['cessionnaire_adresse'] ?: null,
                        'tel' => $p['cessionnaire_telephone'] ?? '',
                        'eml' => $p['cessionnaire_email'] ?? '',
                        'ql' => $p['cessionnaire_qualite'] ?? '',
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
            redirect_to('cession', ['step' => 7]);
        }
        redirect_to('cession', ['step' => 7, 'id' => $cessionId, 'edit' => 1]);
    }

    // Generate documents
    if ($navAction === 'generate_start') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $cid = $wizard['cession_id'];
            $confirmed = ($_POST['confirmed'] ?? '') === '1';

            if (!$confirmed && $cid > 0 && ($pdo ?? null) instanceof PDO) {
                $existingDocs = $pdo->prepare('SELECT doc_type FROM documents_generes WHERE cession_id = :cid AND template_source = :src');
                $existingDocs->execute(['cid' => $cid, 'src' => 'cession']);
                $existing = $existingDocs->fetchAll(PDO::FETCH_COLUMN);

                $societeData = $selectedSociete ?: [];
                if (empty($societeData['societe_raison_sociale']) && !empty($wizard['societe']['societe_raison_sociale'])) {
                    $societeData = $wizard['societe'];
                }
                $socName = $societeData['societe_raison_sociale'] ?? 'Client';
                $forme = $societeData['societe_forme_juridique'] ?? 'PP';
                $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $socName)));
                $clientName = preg_replace('/-+/', '-', $clientName);
                $clientName = trim($clientName, '-');
                $folderName = $wizard['cession_date'] . '_' . $forme . '_' . $clientName;
                $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
                $outputDir = __DIR__ . '/../../../../dossiers_generer/dossiers_cession/' . $folderName;
                $sanitizedForme = str_replace(' ', '_', $forme);
                $today = date('Y-m-d');

                $existingFiles = [];
                foreach (['Acte-Cession-Parts', 'PV-AGE-Cession', 'Declaration-Modificative-RC', 'Annonce-Legale-Cession'] as $dt) {
                    $f = $outputDir . '/' . $sanitizedForme . '_' . $today . '_' . $dt . '_' . $clientName . '.docx';
                    if (file_exists($f)) {
                        $existingFiles[] = basename($f);
                    }
                }

                if (!empty($existing) || !empty($existingFiles)) {
                    echo json_encode(['confirm_required' => true, 'db_types' => $existing, 'files' => $existingFiles]);
                    exit;
                }
            }

            $_SESSION['cession_wizard']['generated_files'] = [];
            if ($cid > 0 && ($pdo ?? null) instanceof PDO) {
                $pdo->prepare('DELETE FROM documents_generes WHERE cession_id = :cid AND template_source = :src')->execute(['cid' => $cid, 'src' => 'cession']);
            }
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($navAction === 'generate_single') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $docType = $_POST['doc_type'] ?? '';
            $path = $_POST['template_path'] ?? '';
            if ($docType === '' || $path === '' || !file_exists($path)) {
                throw new \RuntimeException('Template invalide');
            }
            $cessionId = $wizard['cession_id'];

            $today = date('Y-m-d');
            $societeData = $selectedSociete ?: [];
            if (empty($societeData['societe_raison_sociale']) && !empty($wizard['societe']['societe_raison_sociale'])) {
                $societeData = $wizard['societe'];
            }
            $socName = $societeData['societe_raison_sociale'] ?? 'Client';
            $forme = $societeData['societe_forme_juridique'] ?? 'PP';

            $templateFolders = ['SARL AU' => '_Cession_SARLAU', 'SARL' => '_Cession_SARL'];
            $templateFolderName = $templateFolders[$forme] ?? '_Cession_SARL';
            $templateDir = __DIR__ . '/../../../../templates/' . $templateFolderName;
            $realTemplateDir = realpath($templateDir);
            $realPath = realpath($path);
            if (!$realTemplateDir || !$realPath || !str_starts_with($realPath, $realTemplateDir)) {
                throw new \RuntimeException('Template hors repertoire autorise');
            }
            $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $socName)));
            $clientName = preg_replace('/-+/', '-', $clientName);
            $clientName = trim($clientName, '-');
            $folderName = $wizard['cession_date'] . '_' . $forme . '_' . $clientName;
            $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
            $outputDir = __DIR__ . '/../../../../dossiers_generer/dossiers_cession/' . $folderName;
            if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

            $sanitizedForme = str_replace(' ', '_', $forme);
            $outName = $sanitizedForme . '_' . $today . '_' . $docType . '_' . $clientName . '.docx';

            if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../../../vendor/autoload.php';
            }
            require_once __DIR__ . '/../../../../src/analyseur_templates.php';
            require_once __DIR__ . '/../../../../src/rendu_document.php';
            $context = DocumentRenderer::buildContextFromCession($pdo, $cessionId);
            $pvResolutions = $wizard['pv_resolutions'] ?? [];
            // Auto-generate defaults if empty
            if (empty($pvResolutions) || !is_array($pvResolutions)) {
                $pvResolutions = [];
                $autoFirstPart = $wizard['parts'][0] ?? [];
                $autoCedant = $autoFirstPart['cedant_nom_complet'] ?? ($wizard['cession_metadata']['cedant_name'] ?? '');
                $autoCessionnaire = $autoFirstPart['cessionnaire_nom_complet'] ?? ($wizard['cession_metadata']['cessionnaire_name'] ?? '');
                $autoTotalParts = (int) ($societeData['societe_part_social'] ?? 0);
                $autoTotalPrix = 0;
                $autoTotalPartsCedees = 0;
                foreach ($wizard['parts'] ?? [] as $p) {
                    $autoTotalPrix += (float) ($p['prix_total'] ?? 0);
                    $autoTotalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
                }
                $autoCapitalFmt = number_format((float) ($societeData['societe_capital'] ?? 0), 2, ',', ' ');
                $autoPrixFmt = number_format($autoTotalPrix, 2, ',', ' ');
                $autoTotalPct = $autoTotalParts > 0 ? round($autoTotalPartsCedees / $autoTotalParts * 100, 1) : 0;
                if ($autoCedant || $autoCessionnaire) {
                    $pvResolutions[] = ['title' => 'Cession de parts sociales', 'content' => "Cession de $autoTotalPartsCedees parts sociales pour un montant total de $autoPrixFmt DH."];
                    $pvResolutions[] = ['title' => "Agrément du ou des nouveaux associés", 'content' => "Les associés agréent la cession et acceptent l'entrée du nouvel associé."];
                    $pvResolutions[] = ['title' => 'Modification des statuts', 'content' => "Modification de l'article 7 des statuts relatif au capital social (cession de $autoTotalPartsCedees parts, soit $autoTotalPct% du capital)."];
                    $pvResolutions[] = ['title' => 'Pouvoirs pour formalités', 'content' => "Tous pouvoirs sont donnés au porteur d'une copie du présent procès-verbal pour effectuer toutes formalités légales."];
                }
            }
            $docxBr = '</w:t></w:r><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:br/><w:t xml:space="preserve">';
            if (!empty($pvResolutions) && is_array($pvResolutions)) {
                $orderItems = [];
                foreach ($pvResolutions as $i => $r) {
                    $orderItems[] = ($i + 1) . '. ' . ($r['title'] ?? '');
                    $content = str_replace("\n", $docxBr, $r['content'] ?? '');
                    $context['PV_RESOLUTION_' . ($i + 1)] = $content;
                    $context['PV_TITLE_' . ($i + 1)] = $r['title'] ?? '';
                }
                $context['PV_ORDER_ITEMS'] = implode($docxBr, $orderItems);
            } else {
                $context['PV_ORDER_ITEMS'] = '';
            }
            for ($i = count($pvResolutions ?? []) + 1; $i <= 10; $i++) {
                $context['PV_RESOLUTION_' . $i] = '';
                $context['PV_TITLE_' . $i] = '';
            }
            $renderer = new DocumentRenderer($path, $outputDir);
            $docxPath = $renderer->render($context, $outName);
            $pdfPath = $renderer->tryConvertToPdf($docxPath);

            $stmtD = $pdo->prepare('INSERT INTO documents_generes (societe_id, cession_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko, valide) VALUES (:sid, :cid, :src, :type, :docx, :pdf, :taille, 1)');
            $stmtD->execute([
                'sid' => $wizard['societe_id'],
                'cid' => $cessionId,
                'src' => 'cession',
                'type' => $docType,
                'docx' => $docxPath,
                'pdf' => $pdfPath ?? '',
                'taille' => round(filesize($docxPath) / 1024, 2),
            ]);

            $result = ['name' => $outName, 'docx' => $docxPath, 'pdf' => $pdfPath ?? ''];
            if (!isset($_SESSION['cession_wizard']['generated_files'])) {
                $_SESSION['cession_wizard']['generated_files'] = [];
            }
            $_SESSION['cession_wizard']['generated_files'][] = $result;

            echo json_encode(['success' => true, 'name' => basename((string) $docxPath)]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($navAction === 'generate') {
        if (!isset($wizard['cession_id']) || $wizard['cession_id'] <= 0) {
            set_flash('error', 'Creez d abord le dossier avant de generer les documents.');
            redirect_to('cession', ['step' => 7]);
        }

        require_once __DIR__ . '/../../../../src/analyseur_templates.php';
        require_once __DIR__ . '/../../../../src/rendu_document.php';
        if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../../vendor/autoload.php';
        }

        $selectedDocs = $_POST['doc_types'] ?? [];
        if (empty($selectedDocs)) {
            set_flash('error', 'Selectionnez au moins un type de document.');
            redirect_to('cession', ['step' => 7]);
        }

        $cessionId = $wizard['cession_id'];
        $stmtDos = $pdo->prepare('SELECT societe_id FROM cessions WHERE id = :id');
        $stmtDos->execute(['id' => $cessionId]);

        $societeData = $selectedSociete ?: [];
        if (empty($societeData['societe_raison_sociale']) && !empty($wizard['societe']['societe_raison_sociale'])) {
            $societeData = $wizard['societe'];
        }
        $socName = $societeData['societe_raison_sociale'] ?? 'Client';
        $forme = $societeData['societe_forme_juridique'] ?? 'PP';
        $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $socName)));
        $clientName = preg_replace('/-+/', '-', $clientName);
        $clientName = trim($clientName, '-');
        $today = date('Y-m-d');
        $folderName = $wizard['cession_date'] . '_' . $forme . '_' . $clientName;
        $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
        $outputDir = __DIR__ . '/../../../../dossiers_generer/dossiers_cession/' . $folderName;
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

        $context = DocumentRenderer::buildContextFromCession($pdo, $cessionId);
        $pvResolutions = $wizard['pv_resolutions'] ?? [];
        if (empty($pvResolutions) || !is_array($pvResolutions)) {
            // Auto-generate default PV resolutions from wizard data if not saved
            $pvResolutions = [];
            $autoTotalParts = $societeData['societe_part_social'] ?? 0;
            $autoTotalCapital = $societeData['societe_capital'] ?? 0;
            $autoValeurNominale = $societeData['societe_valeur_nominale'] ?? 0;
            $autoTotalPrix = 0;
            $autoTotalPartsCedees = 0;
            foreach ($wizard['parts'] ?? [] as $p) {
                $autoTotalPrix += (float) ($p['prix_total'] ?? 0);
                $autoTotalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
            }
            $autoFirstPart = $wizard['parts'][0] ?? [];
            $autoCedant = $autoFirstPart['cedant_nom_complet'] ?? '';
            $autoCessionnaire = $autoFirstPart['cessionnaire_nom_complet'] ?? '';
            $autoCessionnaireCivilite = $autoFirstPart['cessionnaire_civilite'] ?? 'M.';
            $autoCessionnaireNat = $autoFirstPart['cessionnaire_nationalite'] ?? '';
            $autoCessionnaireAdr = $autoFirstPart['cessionnaire_adresse'] ?? '';
            $autoCapitalFmt = number_format((float) $autoTotalCapital, 2, ',', ' ');
            $autoPrixFmt = number_format($autoTotalPrix, 2, ',', ' ');
            $autoVnomFmt = number_format((float) $autoValeurNominale, 2, ',', ' ');
            $autoPartsRest = (int) $autoTotalParts - $autoTotalPartsCedees;
            $autoMeta = $wizard['cession_metadata'] ?? [];
            $autoIsSarlAu = $autoMeta['is_sarl_au'] ?? false;
            $autoLabel = $autoIsSarlAu ? "l'Associé Unique" : "les associés";
            $autoVDeclare = $autoIsSarlAu ? "déclare" : "déclarent";
            $autoVAccepte = $autoIsSarlAu ? "accepte" : "acceptent";
            $autoVAgree = $autoIsSarlAu ? "agrée" : "agréent";
            $autoVDecide = $autoIsSarlAu ? "décide" : "décident";
            $autoVPrend = $autoIsSarlAu ? "prend" : "prennent";
            $autoCessionnaireFull = "$autoCessionnaireCivilite $autoCessionnaire";
            
            $pvResolutions[] = [
                'title' => 'Cession de parts sociales',
                'content' => "$autoLabel « $autoCedant », $autoVDeclare céder à « $autoCessionnaireFull », de nationalité $autoCessionnaireNat, demeurant à $autoCessionnaireAdr, $autoTotalPartsCedees parts sociales de $autoVnomFmt DH chacune, pour un montant total de $autoPrixFmt DH.\n\n$autoLabel $autoVAccepte expressément cette cession et reconnaît que le prix de cession a été réglé entre les parties."
            ];
            $pvResolutions[] = [
                'title' => "Agrément du ou des nouveaux associés",
                'content' => "$autoLabel $autoVAgree la cession susmentionnée et accepte l'entrée du nouvel associé dans le capital social de la société."
            ];
            $pvResolutions[] = [
                'title' => 'Modification des statuts',
                'content' => "En conséquence de la cession, $autoLabel $autoVDecide de modifier l'article 7 des statuts relatif à la répartition du capital social, lequel sera désormais rédigé comme suit :\n\nArticle 7 — Capital Social\n\nLe capital social est fixé à la somme de $autoCapitalFmt DH, divisé en $autoTotalParts parts sociales de $autoVnomFmt DH chacune, réparties comme suit :\n\n- $autoCessionnaireFull : $autoTotalPartsCedees parts" . ($autoPartsRest > 0 ? "\n- $autoCedant : $autoPartsRest parts" : '')
            ];
            $autoHasResign = false;
            foreach (($autoMeta['cedants_gerant_map'] ?? []) as $gInfo) {
                if (!empty($gInfo['is_gerant']) && ($gInfo['action'] ?? 'stay') === 'resign') {
                    $autoHasResign = true;
                }
            }
            $autoHasNominate = !empty($autoMeta['new_gerant_cessionnaire_indices'] ?? []);
            $autoNeedsTransform = $autoMeta['needs_transformation'] ?? false;
            $autoCedantNomEscaped = $autoCedant;
            $autoNouveauGerantNomEscaped = $autoCessionnaire;
            $autoAncienGerantNomEscaped = ($wizard['gerants_list'][0]['associe_nom_complet'] ?? $autoCedant);
            
            if ($autoHasResign) {
                $pvResolutions[] = [
                    'title' => "Démission de l'ancien gérant",
                    'content' => "$autoLabel $autoVPrend acte de la démission de « $autoAncienGerantNomEscaped » de ses fonctions de gérant de la société, avec effet à compter de ce jour."
                ];
            }
            if ($autoHasNominate) {
                $pvResolutions[] = [
                    'title' => 'Nomination du nouveau gérant',
                    'content' => "$autoLabel $autoVDecide de nommer « $autoNouveauGerantNomEscaped » en qualité de nouveau gérant de la société, pour une durée indéterminée, avec tous les pouvoirs nécessaires à l'exercice de ses fonctions."
                ];
            }
            if ($autoNeedsTransform) {
                $pvResolutions[] = [
                    'title' => 'Transformation de la forme juridique',
                    'content' => "$autoLabel $autoVDecide de transformer la forme juridique de la société de SARL AU (SARL à Associé Unique) en SARL (Société à Responsabilité Limitée) à associés multiples, conformément aux dispositions de la loi 5-96 modifiée.\n\nEn conséquence, les statuts seront modifiés en conséquence pour tenir compte de la nouvelle forme sociale."
                ];
            }
            $pvResolutions[] = [
                'title' => 'Pouvoirs pour formalités',
                'content' => "Tous pouvoirs sont donnés à $autoCedant, pour effectuer toutes formalités de dépôt et d'inscription modificative auprès du greffe du tribunal de commerce, ainsi que toutes autres démarches requises par la loi."
            ];
        }
        $docxBr = '</w:t></w:r><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:br/><w:t xml:space="preserve">';
        $orderItems = [];
        foreach ($pvResolutions as $i => $r) {
            $orderItems[] = ($i + 1) . '. ' . ($r['title'] ?? '');
            $content = str_replace("\n", $docxBr, $r['content'] ?? '');
            $context['PV_RESOLUTION_' . ($i + 1)] = $content;
            $context['PV_TITLE_' . ($i + 1)] = $r['title'] ?? '';
        }
        $context['PV_ORDER_ITEMS'] = implode($docxBr, $orderItems);
        for ($i = count($pvResolutions) + 1; $i <= 10; $i++) {
            $context['PV_RESOLUTION_' . $i] = '';
            $context['PV_TITLE_' . $i] = '';
        }

        $templatesConfig = require __DIR__ . '/../../../../config/templates.php';
        $mapping = $templatesConfig['template_mapping']['cession'] ?? [];

        $templateFolders = ['SARL AU' => '_Cession_SARLAU', 'SARL' => '_Cession_SARL'];
        $templateFolderName = $templateFolders[$forme] ?? '_Cession_SARL';
        $templateDir = __DIR__ . '/../../../../templates/' . $templateFolderName;
        $generated = [];

        // Check for existing files before overwriting
        $confirmOverwrite = ($_POST['confirm_overwrite'] ?? '') === '1';
        $sanitizedForme = str_replace(' ', '_', $forme);
        $existingFiles = [];
        foreach ($mapping as $docType) {
            if (!in_array($docType, $selectedDocs, true)) continue;
            $outName = $sanitizedForme . '_' . $today . '_' . $docType . '_' . $clientName . '.docx';
            $outPath = $outputDir . '/' . $outName;
            if (file_exists($outPath)) {
                $existingFiles[] = basename($outPath);
            }
        }
        if (!empty($existingFiles) && !$confirmOverwrite) {
            $_SESSION['_cession_overwrite_files'] = $existingFiles;
            $_SESSION['_cession_overwrite_docs'] = $selectedDocs;
            set_flash('warning', 'Des documents existent deja. Voulez-vous les ecraser ?');
            redirect_to('cession', ['step' => 7, 'id' => $cessionId, 'edit' => 1]);
        }
        unset($_SESSION['_cession_overwrite_files'], $_SESSION['_cession_overwrite_docs']);

        // Delete old records for this cession before re-inserting
        $pdo->prepare('DELETE FROM documents_generes WHERE cession_id = :cid AND template_source = :src')->execute(['cid' => $cessionId, 'src' => 'cession']);

        foreach ($mapping as $docType) {
            if (!in_array($docType, $selectedDocs, true)) continue;
            $matches = glob($templateDir . '/*' . $docType . '*_Template.docx');
            if (empty($matches)) continue;
            try {
                $renderer = new DocumentRenderer($matches[0], $outputDir);
                $outName = $sanitizedForme . '_' . $today . '_' . $docType . '_' . $clientName . '.docx';
                $docxPath = $renderer->render($context, $outName);
                $pdfPath = $renderer->tryConvertToPdf($docxPath);

                $stmtD = $pdo->prepare('INSERT INTO documents_generes (societe_id, cession_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko, valide) VALUES (:sid, :cid, :src, :type, :docx, :pdf, :taille, 1)');
                $stmtD->execute([
                    'sid' => $wizard['societe_id'],
                    'cid' => $cessionId,
                    'src' => 'cession',
                    'type' => $docType,
                    'docx' => $docxPath,
                    'pdf' => $pdfPath ?? '',
                    'taille' => round(filesize($docxPath) / 1024, 2),
                ]);
                $generated[] = ['name' => $outName, 'docx' => $docxPath, 'pdf' => $pdfPath ?? ''];
            } catch (Throwable $e) {}
        }

            $_SESSION['cession_wizard']['generated_files'] = $generated;
            set_flash('success', count($generated) . ' document(s) genere(s).');
            redirect_to('cession', ['step' => 7, 'id' => $cessionId, 'edit' => 1]);
    }

    if ($navAction === 'terminer') {
        $societeId = $wizard['societe_id'] ?? 0;
        $cessionId = $wizard['cession_id'] ?? 0;
        unset($_SESSION['cession_wizard'], $_SESSION['_cession_loaded'], $_SESSION['_cession_editing_id'], $_SESSION['_cession_overwrite_files'], $_SESSION['_cession_overwrite_docs']);
        redirect_to('cession_dossier', ['id' => $cessionId]);
    }
}

// ============ STEP 7 HTML VIEW ============
if ($step === 7):
    $dossierCreated = isset($wizard['cession_id']) && $wizard['cession_id'] > 0;
    $cessionId = $wizard['cession_id'] ?? null;
    $templatesConfig = require __DIR__ . '/../../../../config/templates.php';
    $mapping = $templatesConfig['template_mapping']['cession'] ?? [];
    $docTypes = $templatesConfig['document_types'] ?? [];
    $generatedFiles = $wizard['generated_files'] ?? [];

    require_once __DIR__ . '/../../../../src/analyseur_templates.php';
    $templateFolders = ['SARL AU' => '_Cession_SARLAU', 'SARL' => '_Cession_SARL'];
    $formeDir = $societeData['societe_forme_juridique'] ?: ($selectedSociete['societe_forme_juridique'] ?? '');
    $templateFolderName = $templateFolders[$formeDir] ?? '_Cession_SARL';
    $cessionTemplateDir = __DIR__ . '/../../../../templates/' . $templateFolderName;
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
        <h2>Etape 6 — Generation des documents</h2>
        <?php if ($dossierCreated): ?>
            <a class="btn btn-secondary" style="margin-left:auto" href="<?= e(app_url('cession_dossier', ['id' => $cessionId])) ?>">
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
                    <p class="help-text">Enregistrez la societe, les cedants et cessionnaires en base de donnees.</p>
                </div>
                <?php if ($dossierCreated): ?>
                    <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined" style="font-size:1.6rem">check_circle</span></span>
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
                    <p class="help-text">Selectionnez les types de documents a generer pour la cession.</p>
                </div>
                <?php if (!empty($generatedFiles)): ?>
                    <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined" style="font-size:1.6rem">check_circle</span></span>
                <?php endif; ?>
            </div>

            <?php if (!$dossierCreated): ?>
            <?php else: ?>

                <?php $overwriteFiles = $_SESSION['_cession_overwrite_files'] ?? []; $overwriteDocs = $_SESSION['_cession_overwrite_docs'] ?? []; ?>
                <?php if (!empty($overwriteFiles)): ?>
                <div class="card" style="border-color:var(--warning);background:rgba(255,107,53,0.06);margin-top:8px">
                    <div style="display:flex;align-items:flex-start;gap:12px">
                        <span class="material-symbols-outlined" style="color:var(--warning);font-size:28px">warning</span>
                        <div>
                            <strong>Des documents existent deja dans ce dossier :</strong>
                            <ul style="margin:4px 0 8px;padding-left:1.2rem">
                                <?php foreach ($overwriteFiles as $of): ?>
                                <li style="font-size:0.9rem"><?= e($of) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="post" style="display:inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="nav_action" value="generate">
                                <input type="hidden" name="confirm_overwrite" value="1">
                                <?php foreach ($overwriteDocs as $sd): ?>
                                <input type="hidden" name="doc_types[]" value="<?= e($sd) ?>">
                                <?php endforeach; ?>
                                <button class="btn btn-danger" type="submit">
                                    <span class="material-symbols-outlined">warning</span> Oui, ecraser les fichiers
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($overwriteFiles)): ?>
                <form method="post" class="stack" style="gap:8px;margin-top:8px" id="wizard-gen-form">
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
                                            <td class="col-check"><input type="checkbox" name="doc_types[]" value="<?= e($docType) ?>" data-template-path="<?= e($tpl['path']) ?>" checked class="template-check"></td>
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
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($generatedFiles)): ?>
    <div class="step-card done" style="margin-top:16px">
        <div class="step-card-header">
            <span class="step-num">3</span>
            <div>
                <h3>Documents generes</h3>
                <p class="help-text"><?= count($generatedFiles) ?> fichier(s) genere(s)</p>
            </div>
            <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined" style="font-size:1.6rem">check_circle</span></span>
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
                            <?= e(is_array($file) ? ($file['name'] ?? '') : $file) ?>
                        </td>
                        <td><?php if (is_array($file) && file_exists($file['docx'])): ?><?= number_format(filesize($file['docx']) / 1024, 1) ?> Ko<?php else: ?>-<?php endif; ?></td>
                        <td>
                            <div class="table-actions">
                            <?php if (is_array($file)): ?>
                                <a class="btn btn-secondary" href="<?= e(str_replace(dirname(__DIR__, 4) . '/', '', $file['docx'])) ?>" download>
                                    <span class="material-symbols-outlined">download</span> DOCX
                                </a>
                                <?php if (!empty($file['pdf']) && file_exists($file['pdf'])): ?>
                                <a class="btn" href="<?= e(str_replace(dirname(__DIR__, 4) . '/', '', $file['pdf'])) ?>" download>
                                    <span class="material-symbols-outlined">picture_as_pdf</span> PDF
                                </a>
                                <?php endif; ?>
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

    <form method="post" class="footer-actions" style="margin-top:0.75rem">
        <?= csrf_input() ?>
        <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
        <?php if ($dossierCreated): ?>
        <button class="btn btn-next" type="submit" name="nav_action" value="terminer"><span class="material-symbols-outlined">check_circle</span> Terminer</button>
        <?php endif; ?>
    </form>
</div>

<script>
document.getElementById('select-all-generated')?.addEventListener('change', function(e) {
    document.querySelectorAll('.gen-file-check').forEach(function(c) { c.checked = this.checked; }.bind(this));
});

document.getElementById('select-all-wizard')?.addEventListener('click', function(e) {
    e.preventDefault();
    var form = document.getElementById('wizard-gen-form');
    if (!form) return;
    var checkboxes = form.querySelectorAll('.template-check');
    var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
    checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
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
    var startGen = function (confirmed) {
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('nav_action', 'generate_start');
        if (confirmed) fd.append('confirmed', '1');
        fetch(window.location.href, { method: 'POST', body: fd }).then(function (r) {
            return r.json();
        }).then(function (data) {
            if (data.confirm_required) {
                var msg = 'Des documents existent deja :\n';
                if (data.db_types && data.db_types.length) msg += '- En base : ' + data.db_types.join(', ') + '\n';
                if (data.files && data.files.length) msg += '- Fichiers : ' + data.files.join(', ') + '\n';
                msg += '\nVoulez-vous les ecraser ?';
                if (confirm(msg)) {
                    startGen(true);
                } else {
                    if (overlay) overlay.classList.remove('show');
                }
                return;
            }
            if (!data.success) {
                if (statusText) statusText.textContent = 'Erreur: ' + (data.error || 'inconnue');
                return;
            }
            next();
        }).catch(function (err) {
            if (statusText) statusText.textContent = 'Erreur: ' + err.message;
        });
    };
    var next = function () {
        if (done >= total) {
            window.location.reload();
            return;
        }
        var cb = checkboxes[done];
        var docType = cb.value;
        var path = cb.getAttribute('data-template-path') || '';
        var pct = Math.round((done / total) * 100);
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressText) progressText.textContent = done + '/' + total + ' documents';
        if (statusText) statusText.textContent = 'Generation de : ' + docType;
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('nav_action', 'generate_single');
        fd.append('doc_type', docType);
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
    startGen();
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
