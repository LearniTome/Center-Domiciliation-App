<?php

declare(strict_types=1);

// ============ STEP 6 POST HANDLER ============
if (is_post() && $step === 6) {
    verify_csrf();
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

        require_once __DIR__ . '/../../../../src/analyseur_templates.php';
        require_once __DIR__ . '/../../../../src/rendu_document.php';
        if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../../vendor/autoload.php';
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
        $outputDir = __DIR__ . '/../../../../dossiers_generer/dossiers_cession/' . $folderName . '/' . $dossierCession;
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

        $context = DocumentRenderer::buildContextFromCession($pdo, $cessionId);

        $templatesConfig = require __DIR__ . '/../../../../config/templates.php';
        $mapping = $templatesConfig['template_mapping']['cession'] ?? [];

        $templateDir = __DIR__ . '/../../../../templates/_Cession';
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

// ============ STEP 6 HTML VIEW ============
if ($step === 6):
    $dossierCreated = isset($wizard['cession_id']) && $wizard['cession_id'] > 0;
    $cessionId = $wizard['cession_id'] ?? null;
    $templatesConfig = require __DIR__ . '/../../../../config/templates.php';
    $mapping = $templatesConfig['template_mapping']['cession'] ?? [];
    $docTypes = $templatesConfig['document_types'] ?? [];
    $generatedFiles = $wizard['generated_files'] ?? [];

    require_once __DIR__ . '/../../../../src/analyseur_templates.php';
    $cessionTemplateDir = __DIR__ . '/../../../../templates/_Cession';
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

<script>
document.getElementById('select-all-wizard')?.addEventListener('click', function(e) {
    e.preventDefault();
    var form = this.closest('form');
    if (!form) return;
    var checkboxes = form.querySelectorAll('.template-check');
    var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
    checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
});
</script>
<?php endif; ?>
