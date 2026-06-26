<?php
declare(strict_types=1);

function parse_pv_ago_money(string $val): ?float
{
    $val = trim(str_replace([' ', "\xc2\xa0"], '', str_replace(',', '.', $val)));
    if ($val === '' || !is_numeric($val)) return null;
    return (float) $val;
}

if (is_post() && $step === 5) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'back';
    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 4]);
    }

    if ($navAction === 'save') {
        if (!(($pdo ?? null) instanceof PDO)) {
            set_flash('error', 'Connexion MySQL indisponible.');
            redirect_to('pv_ago', ['step' => 5]);
        }
        try {
            $pdo->beginTransaction();

            // Create societe if new
            if ($wizard['mode'] === 'nouvelle' && $wizard['societe_id'] <= 0) {
                $soc = $wizard['societe'];
                $stmt = $pdo->prepare('INSERT INTO societes (societe_raison_sociale, societe_forme_juridique, societe_source, societe_ice, societe_rc, societe_if, societe_tp, societe_cnss, societe_capital, societe_part_social, societe_valeur_nominale, societe_adresse_siege, societe_ville, societe_tribunal, societe_tribunal_type, societe_email, societe_telephone, societe_activites_statuts, created_by) VALUES (:raison, :forme, :source, :ice, :rc, :ifis, :tp, :cnss, :capital, :parts, :vnom, :adr, :ville, :trib, :trib_type, :email, :tel, :activites, :created_by)');
                $stmt->execute([
                    'raison' => $soc['societe_raison_sociale'] ?? '',
                    'forme' => $soc['societe_forme_juridique'] ?? '',
                    'source' => 'pv_ago',
                    'ice' => $soc['societe_ice'] ?? '',
                    'rc' => $soc['societe_rc'] ?? '',
                    'ifis' => $soc['societe_if'] ?? '',
                    'tp' => $soc['societe_tp'] ?? '',
                    'cnss' => $soc['societe_cnss'] ?? '',
                    'capital' => !empty($soc['societe_capital']) ? parse_pv_ago_money((string) $soc['societe_capital']) : null,
                    'parts' => !empty($soc['societe_part_social']) ? (int) $soc['societe_part_social'] : null,
                    'vnom' => !empty($soc['societe_valeur_nominale']) ? parse_pv_ago_money((string) $soc['societe_valeur_nominale']) : null,
                    'adr' => $soc['societe_adresse_siege'] ?? '',
                    'ville' => $soc['societe_ville'] ?? '',
                    'trib' => $soc['societe_tribunal'] ?? '',
                    'trib_type' => $soc['societe_tribunal_type'] ?? '',
                    'email' => $soc['societe_email'] ?? '',
                    'tel' => $soc['societe_telephone'] ?? '',
                    'activites' => $soc['societe_activites_statuts'] ?? '',
                    'created_by' => ($user = current_user()) ? (int) $user['id'] : null,
                ]);
                $newSocId = (int) $pdo->lastInsertId();
                $wizard['societe_id'] = $newSocId;
            }

            $societeId = (int) $wizard['societe_id'];
            if ($societeId <= 0) {
                throw new RuntimeException('Aucune societe selectionnee.');
            }

            // Generate dossier number
            $currentYear = date('Y');
            $maxNum = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(dossier_numero, '-', -1) AS UNSIGNED)), 0) FROM pv_ago WHERE dossier_numero LIKE 'PVAGO-{$currentYear}-%'")->fetchColumn();
            $dossierNum = (int) $maxNum + 1;
            $dossier = sprintf('PVAGO-%s-%03d', $currentYear, $dossierNum);

            $capitalSocial = (float) ($selectedSociete['societe_capital'] ?? $wizard['societe']['societe_capital'] ?? 0);

            $stmt = $pdo->prepare('INSERT INTO pv_ago (societe_id, dossier_numero, statut, date_ago, heure_ago, lieu_ago, president_nom, president_qualite, exercice_clos, total_parts, parts_presentes, resultat_net, resultat_type, report_a_nouveau_debiteur, reserve_legale_existante, reserve_statutaire_existante, reserve_facultative_existante, capital_social, affectation_option, dividende_total, reserve_statutaire_dotation, reserve_facultative_dotation, perte_reserve_prelevement, resolutions, created_by) VALUES (:sid, :dos, :stat, :date, :heure, :lieu, :pres, :presq, :exo, :tp, :pp, :rn, :rtype, :rnd, :rle, :rse, :rfe, :cs, :aff, :div, :rsd, :rfd, :prp, :res, :cb)');
            $stmt->execute([
                'sid' => $societeId,
                'dos' => $dossier,
                'stat' => 'finalise',
                'date' => $wizard['date_ago'],
                'heure' => $wizard['heure_ago'] ?? '10:00',
                'lieu' => $wizard['lieu_ago'] ?? 'au siege social',
                'pres' => $wizard['president_nom'] ?? '',
                'presq' => $wizard['president_qualite'] ?? 'Gerant',
                'exo' => $wizard['exercice_clos'] ?? '',
                'tp' => !empty($wizard['total_parts']) ? (int) $wizard['total_parts'] : null,
                'pp' => !empty($wizard['parts_presentes']) ? (int) $wizard['parts_presentes'] : null,
                'rn' => parse_pv_ago_money((string) ($wizard['resultat_net'] ?? 0)),
                'rtype' => $wizard['resultat_type'] ?? 'benefice',
                'rnd' => parse_pv_ago_money((string) ($wizard['report_a_nouveau_debiteur'] ?? 0)),
                'rle' => parse_pv_ago_money((string) ($wizard['reserve_legale_existante'] ?? 0)),
                'rse' => parse_pv_ago_money((string) ($wizard['reserve_statutaire_existante'] ?? 0)),
                'rfe' => parse_pv_ago_money((string) ($wizard['reserve_facultative_existante'] ?? 0)),
                'cs' => $capitalSocial,
                'aff' => $wizard['affectation_option'] ?? 'profit_distribution',
                'div' => parse_pv_ago_money((string) ($wizard['dividende_total'] ?? 0)),
                'rsd' => parse_pv_ago_money((string) ($wizard['reserve_statutaire_dotation'] ?? 0)),
                'rfd' => parse_pv_ago_money((string) ($wizard['reserve_facultative_dotation'] ?? 0)),
                'prp' => parse_pv_ago_money((string) ($wizard['perte_reserve_prelevement'] ?? 0)),
                'res' => !empty($wizard['resolutions']) ? json_encode($wizard['resolutions'], JSON_UNESCAPED_UNICODE) : null,
                'cb' => ($user = current_user()) ? (int) $user['id'] : null,
            ]);
            $pvAgoId = (int) $pdo->lastInsertId();

            $pdo->commit();
            $wizard['pv_ago_id'] = $pvAgoId;
            $wizard['dossier_numero'] = $dossier;
            set_flash('success', 'PV AGO enregistre sous le numero ' . $dossier);
            log_activity($pdo, 'create', 'pv_ago', $pvAgoId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($wizard['mode'] === 'nouvelle') $wizard['societe_id'] = 0;
            set_flash('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
            redirect_to('pv_ago', ['step' => 5]);
        }
        redirect_to('pv_ago', ['step' => 5, 'id' => $pvAgoId, 'edit' => 1]);
    }

    if ($navAction === 'generate') {
        $pvAgoId = $wizard['pv_ago_id'] ?? 0;
        if ($pvAgoId <= 0) {
            set_flash('error', 'Enregistrez d abord le PV AGO avant de generer le document.');
            redirect_to('pv_ago', ['step' => 5]);
        }

        require_once __DIR__ . '/../../../../src/analyseur_templates.php';
        require_once __DIR__ . '/../../../../src/rendu_document.php';
        if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../../vendor/autoload.php';
        }

        $context = DocumentRenderer::buildContextFromPvAgo($pdo, $pvAgoId);
        if (empty($context)) {
            set_flash('error', 'Impossible de construire le contexte pour le PV AGO.');
            redirect_to('pv_ago', ['step' => 5]);
        }

        $societeId = (int) ($wizard['societe_id'] ?? 0);
        if ($societeId <= 0) {
            $stmtSoc = $pdo->prepare('SELECT societe_id FROM pv_ago WHERE id = :id');
            $stmtSoc->execute(['id' => $pvAgoId]);
            $rowSoc = $stmtSoc->fetchColumn();
            $societeId = (int) ($rowSoc ?: 0);
        }

        $societeData = $selectedSociete ?: $wizard['societe'] ?? [];
        $socName = $societeData['societe_raison_sociale'] ?? 'Client';
        $forme = $societeData['societe_forme_juridique'] ?? 'PP';
        $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $socName)));
        $clientName = preg_replace('/-+/', '-', $clientName);
        $clientName = trim($clientName, '-');
        $today = date('Y-m-d');
        $sanitizedForme = str_replace(' ', '_', $forme);
        $outputDir = __DIR__ . '/../../../../dossiers_generer/dossiers_pv_ago/' . $sanitizedForme . '_' . $today . '_' . $clientName;
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

        $templateDir = __DIR__ . '/../../../../templates/_PV_AGO';
        $matches = glob($templateDir . '/*PV-AGO*_Template.docx');
        if (empty($matches)) {
            set_flash('error', 'Aucun template PV-AGO trouve dans templates/_PV_AGO/.');
            redirect_to('pv_ago', ['step' => 5]);
        }

        $outName = $sanitizedForme . '_' . $today . '_PV-AGO_' . $clientName . '.docx';
        try {
            $renderer = new DocumentRenderer($matches[0], $outputDir);
            $docxPath = $renderer->render($context, $outName);
            $pdfPath = $renderer->tryConvertToPdf($docxPath);

            $stmtD = $pdo->prepare('INSERT INTO documents_generes (societe_id, pv_ago_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko, valide) VALUES (:sid, :pid, :src, :type, :docx, :pdf, :taille, 1)');
            $stmtD->execute([
                'sid' => $societeId,
                'pid' => $pvAgoId,
                'src' => 'pv_ago',
                'type' => 'PV-AGO',
                'docx' => $docxPath,
                'pdf' => $pdfPath ?? '',
                'taille' => round(filesize($docxPath) / 1024, 2),
            ]);

            $wizard['generated_files'] = [['name' => $outName, 'docx' => $docxPath, 'pdf' => $pdfPath ?? '']];
            set_flash('success', 'Document genere avec succes.');
        } catch (Throwable $e) {
            set_flash('error', 'Erreur de generation: ' . $e->getMessage());
        }
        redirect_to('pv_ago', ['step' => 5, 'id' => $pvAgoId, 'edit' => 1]);
    }

    if ($navAction === 'terminer') {
        $pvAgoId = $wizard['pv_ago_id'] ?? 0;
        unset($_SESSION['pv_ago_wizard'], $_SESSION['_pv_ago_loaded'], $_SESSION['_pv_ago_editing_id']);
        redirect_to('pvag', ['id' => $pvAgoId]);
    }
}

if ($step === 5):
    $saved = isset($wizard['pv_ago_id']) && $wizard['pv_ago_id'] > 0;
    $generatedFiles = $wizard['generated_files'] ?? [];
?>
<div class="stack">
    <div class="two-step-flow">
        <div class="step-card <?= $saved ? 'done' : 'active' ?>">
            <div class="step-card-header">
                <span class="step-num">1</span>
                <div>
                    <h3>Enregistrer le PV AGO</h3>
                    <p class="help-text">Sauvegarder les donnees en base de donnees.</p>
                </div>
                <?php if ($saved): ?>
                <span class="step-badge text-success"><span class="material-symbols-outlined" style="font-size:2rem">check_circle</span></span>
                <?php else: ?>
                <span class="step-badge text-danger"><span class="material-symbols-outlined" style="font-size:2rem">cancel</span></span>
                <?php endif; ?>
            </div>
            <?php if (!$saved): ?>
            <form method="post" class="inline-save">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="save">
                <button class="btn btn-next" type="submit">
                    <span class="material-symbols-outlined">save</span> Enregistrer le PV AGO
                </button>
            </form>
            <?php endif; ?>
        </div>

        <div class="step-card <?= $saved ? ($generatedFiles ? 'done' : 'active') : 'waiting' ?>">
            <div class="step-card-header">
                <span class="step-num">2</span>
                <div>
                    <h3>Generer le document</h3>
                    <p class="help-text">Generation du PV AGO au format DOCX + PDF.</p>
                </div>
                <?php if ($generatedFiles): ?>
                <span class="step-badge text-success"><span class="material-symbols-outlined" style="font-size:2rem">check_circle</span></span>
                <?php else: ?>
                <span class="step-badge text-danger"><span class="material-symbols-outlined" style="font-size:2rem">cancel</span></span>
                <?php endif; ?>
            </div>
            <?php if ($saved && empty($generatedFiles)): ?>
            <form method="post" class="inline-save">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="generate">
                <button class="btn btn-next" type="submit" id="btn-generate-pvago">
                    <span class="material-symbols-outlined">description</span> Generer le document
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($generatedFiles)): ?>
    <article class="card stack">
        <div class="section-header">
            <h2><span class="material-symbols-outlined" style="font-size:1.2rem;vertical-align:middle;margin-right:6px">article</span>Document genere</h2>
        </div>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="fichier">Fichier</th>
                        <th data-col="taille">Taille</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($generatedFiles as $file): ?>
                    <tr>
                        <td>
                            <span class="material-symbols-outlined doc-icon">article</span>
                            <?= e($file['name'] ?? basename($file['docx'] ?? '')) ?>
                        </td>
                        <td><?= file_exists($file['docx'] ?? '') ? number_format(filesize($file['docx']) / 1024, 1) . ' Ko' : '-' ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary" href="<?= e(str_replace(dirname(__DIR__, 4) . '/', '', $file['docx'])) ?>" download>
                                    <span class="material-symbols-outlined">download</span> DOCX
                                </a>
                                <?php if (!empty($file['pdf']) && file_exists($file['pdf'])): ?>
                                <a class="btn" href="<?= e(str_replace(dirname(__DIR__, 4) . '/', '', $file['pdf'])) ?>" download>
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
    </article>

    <form method="post" class="footer-actions" style="margin-top:0.75rem">
        <?= csrf_input() ?>
        <button class="btn btn-back" type="submit" name="nav_action" value="back">
            <span class="material-symbols-outlined">arrow_back</span> Retour
        </button>
        <?php if ($saved): ?>
        <button class="btn btn-next" type="submit" name="nav_action" value="terminer">
            <span class="material-symbols-outlined">check_circle</span> Terminer
        </button>
        <?php endif; ?>
    </form>
    <?php if ($saved): ?>
    <div style="display:flex;justify-content:flex-end;margin-top:8px">
        <a class="btn btn-secondary" href="<?= e(app_url('pvag', ['id' => $wizard['pv_ago_id']])) ?>">
            <span class="material-symbols-outlined">visibility</span> Voir le dossier
        </a>
    </div>
    <?php endif; ?>
</div>

<div id="gen-loading-overlay" class="gen-loading-overlay">
    <div class="loader-card">
        <div class="spinner"></div>
        <p>Generation du document en cours...</p>
        <div class="gen-progress-bar">
            <div class="gen-progress-fill" style="width:60%"></div>
        </div>
        <div class="gen-progress-text">Preparation du PV AGO</div>
        <div class="gen-status-text">Veuillez patienter, cette operation peut prendre quelques instants.</div>
    </div>
</div>
<script>
document.querySelectorAll('.inline-save button[type="submit"]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var overlay = document.getElementById('gen-loading-overlay');
        if (overlay) overlay.classList.add('show');
    });
});
</script>
<?php endif; ?>
