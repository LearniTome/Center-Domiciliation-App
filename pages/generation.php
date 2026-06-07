<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../src/TemplateAnalyzer.php';
require_once __DIR__ . '/../src/DocumentRenderer.php';

$templatesConfig = require __DIR__ . '/../config/templates.php';
$templatesDir = __DIR__ . '/../templates';
$outputDir = __DIR__ . '/../dossiers_dom';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$societeId = isset($_GET['societe_id']) ? (int) $_GET['societe_id'] : 0;
$legalForm = field_value($_GET, 'forme');

$genUser = current_user();
$genIsAdmin = $genUser && in_array((int) $genUser['role_id'], [1, 2], true);
$genUserId = (!$genIsAdmin && $genUser) ? (int) $genUser['id'] : null;

$societesOptions = fetch_societes_options($pdo ?? null, $genUserId);

$selectedSociete = null;
if ($societeId > 0) {
    $selectedSociete = fetch_record($pdo ?? null, 'societes', $societeId);
    if ($selectedSociete) {
        $legalForm = $selectedSociete['societe_forme_juridique'] ?? '';
    }
}

$allTemplates = TemplateAnalyzer::scanTemplates($templatesDir);

function filterTemplatesByLegalForm(array $templates, string $form, ?PDO $pdo = null): array
{
    $targetFolder = ($form !== '') ? fetch_legal_form_template_folder($pdo, $form) : '';
    if ($targetFolder !== '') {
        ensure_template_folder($targetFolder);
    }
    $matched = [];
    $generic = [];

    foreach ($templates as $tpl) {
        if ($targetFolder !== '' && $tpl['folder'] === $targetFolder) {
            $matched[] = $tpl;
        } elseif ($tpl['folder'] === '_Racine-Actifs') {
            $generic[] = $tpl;
        }
    }

    return count($matched) > 0 ? $matched : $generic;
}

$filteredTemplates = [];
$context = [];

if ($selectedSociete) {
    $filteredTemplates = filterTemplatesByLegalForm($allTemplates, $legalForm, $pdo ?? null);
}

$sessionFiles = $_SESSION['gen_files'][$societeId] ?? [];
$statusFilter = field_value($_GET, 'statut');

$dbDocs = [];
if (($pdo ?? null) instanceof PDO && $societeId > 0) {
    $allDbDocs = fetch_all_documents($pdo, $societeId);
    if ($statusFilter === 'valide') {
        $dbDocs = array_values(array_filter($allDbDocs, fn($d) => (int) $d['valide'] === 1));
    } elseif ($statusFilter === 'brouillon') {
        $dbDocs = array_values(array_filter($allDbDocs, fn($d) => (int) $d['valide'] === 0));
    } else {
        $dbDocs = $allDbDocs;
    }
}

if (is_post() && !isset($_POST['delete_submit']) && !isset($_POST['validate_submit']) && !isset($_POST['generate_pdf_submit']) && ($pdo ?? null) instanceof PDO && $selectedSociete) {
    verify_csrf();

    $selectedPaths = $_POST['templates'] ?? [];

    $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);
    $forme = $selectedSociete['societe_forme_juridique'] ?? 'PP';
    $today = date('Y-m-d');
    $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $selectedSociete['societe_raison_sociale'] ?? 'Client')));
    $clientName = preg_replace('/-+/', '-', $clientName);
    $clientName = trim($clientName, '-');

    $folderDate = $context['contrat_date'] ?? $today;
    $folderName = $folderDate . '_' . $forme . '_' . $clientName;
    $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
    $outputDir = __DIR__ . '/../dossiers_dom/' . $folderName;
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

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
                'name' => $outName,
            ];

            $generatedFiles[] = $result;
        } catch (\Throwable $e) {
            set_flash('error', 'Erreur sur ' . basename($path) . ' : ' . $e->getMessage());
        }
    }

    if (count($generatedFiles) > 0) {
        $_SESSION['gen_files'][$societeId] = $generatedFiles;
        $insertStmt = $pdo->prepare('INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko) VALUES (:societe_id, :template_source, :doc_type, :fichier_docx, :fichier_pdf, :taille_ko)');
        foreach ($generatedFiles as $gf) {
            $tplSource = null;
            $docType = null;
            foreach ($selectedPaths as $sp) {
                if (isset($gf['docx']) && str_contains((string) $gf['docx'], pathinfo($sp, PATHINFO_FILENAME))) {
                    $tplSource = $sp;
                    break;
                }
            }
            $parts = explode('_', basename((string) $gf['name']));
            $docType = $parts[2] ?? null;
            $insertStmt->execute([
                'societe_id' => $societeId,
                'template_source' => $tplSource,
                'doc_type' => $docType,
                'fichier_docx' => $gf['docx'],
                'fichier_pdf' => $gf['pdf'] ?? null,
                'taille_ko' => file_exists((string) $gf['docx']) ? round(filesize((string) $gf['docx']) / 1024, 1) : null,
            ]);
        }
        set_flash('success', count($generatedFiles) . ' document(s) genere(s).');
        log_activity($pdo, 'generate', 'document', $societeId, 'Génération — ' . count($generatedFiles) . ' doc(s)', json_encode(['doc_types' => array_map(fn($f) => $f['doc_type'] ?? '', $generatedFiles)]));
        redirect_to('generation', ['societe_id' => $societeId]);
    }
}

if (is_post() && isset($_POST['delete_submit']) && $societeId > 0) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];

    if (count($selected) > 0 && ($pdo ?? null) instanceof PDO) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $docs = $stmt->fetchAll();
        foreach ($docs as $doc) {
            if (file_exists($doc['fichier_docx'])) unlink($doc['fichier_docx']);
            if ($doc['fichier_pdf'] && file_exists($doc['fichier_pdf'])) unlink($doc['fichier_pdf']);
        }
        $stmt = $pdo->prepare("DELETE FROM documents_generes WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $_SESSION['gen_files'][$societeId] = array_values(array_filter($_SESSION['gen_files'][$societeId] ?? [], fn($f) => !in_array($f['docx'], array_column($docs, 'fichier_docx'))));
        set_flash('error', count($selected) . ' document(s) supprime(s).');
        log_activity($pdo, 'delete', 'document_genere', $societeId, 'Suppression — ' . count($selected) . ' doc(s)');
        $params = ['societe_id' => $societeId];
        if ($statusFilter) $params['statut'] = $statusFilter;
        redirect_to('generation', $params);
    }
}

if (is_post() && isset($_POST['validate_submit']) && $societeId > 0) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];

    if (count($selected) > 0 && ($pdo ?? null) instanceof PDO) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf, doc_type FROM documents_generes WHERE valide = 0 AND id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $docs = $stmt->fetchAll();
        $updateStmt = $pdo->prepare("UPDATE documents_generes SET valide = 1, fichier_docx = :fichier_docx, fichier_pdf = :fichier_pdf WHERE id = :id");
        foreach ($docs as $doc) {
            $oldDocx = $doc['fichier_docx'];
            $newDocx = str_replace('_Brouillon.docx', '.docx', $oldDocx);
            if ($oldDocx !== $newDocx && file_exists($oldDocx)) {
                rename($oldDocx, $newDocx);
                $oldPdf = str_replace('.docx', '.pdf', $oldDocx);
                if ($oldPdf !== str_replace('.docx', '.pdf', $newDocx) && file_exists($oldPdf)) {
                    unlink($oldPdf);
                }
            }

            // Generate PDF on validation
            $pdfPath = null;
            if (file_exists($newDocx)) {
                $pdfName = pathinfo($newDocx, PATHINFO_FILENAME) . '.pdf';
                $renderer = new DocumentRenderer('', dirname($newDocx));
                try {
                    $generatedPdf = $renderer->tryConvertToPdf($newDocx, $pdfName);
                    if ($generatedPdf && file_exists($generatedPdf)) {
                        $pdfPath = $generatedPdf;
                    }
                } catch (\Throwable $e) {
                }
            }

            $updateStmt->execute([
                'fichier_docx' => $newDocx,
                'fichier_pdf' => $pdfPath,
                'id' => $doc['id'],
            ]);
            foreach ($_SESSION['gen_files'][$societeId] ?? [] as &$sf) {
                if ($sf['docx'] === $oldDocx) {
                    $sf['docx'] = $newDocx;
                    $sf['name'] = str_replace('_Brouillon.docx', '.docx', $sf['name']);
                    $sf['pdf'] = $pdfPath;
                }
            }
            unset($sf);
        }
        $cleanTypes = array_unique(array_map(fn($d) => $d['doc_type'] ?? '', $docs));
        $cleanTypes = array_values(array_filter($cleanTypes, fn($v) => $v !== ''));
        if (!empty($cleanTypes)) {
            $typePlaceholders = implode(',', array_fill(0, count($cleanTypes), '?'));
            $delStmt = $pdo->prepare("DELETE FROM documents_generes WHERE id NOT IN ($placeholders) AND societe_id = ? AND valide = 0 AND doc_type IN ($typePlaceholders)");
            $delStmt->execute(array_merge(array_map('intval', $selected), [$societeId], $cleanTypes));
        }
        set_flash('success', count($selected) . ' document(s) valide(s).');
        log_activity($pdo, 'validate', 'document_genere', $societeId, 'Validation — ' . count($selected) . ' doc(s)');
        $params = ['societe_id' => $societeId];
        if ($statusFilter) $params['statut'] = $statusFilter;
        redirect_to('generation', $params);
    }
}

if (is_post() && isset($_POST['generate_pdf_submit']) && $societeId > 0) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) > 0 && ($pdo ?? null) instanceof PDO) {
        $docId = (int) $selected[0];
        $stmt = $pdo->prepare("SELECT * FROM documents_generes WHERE id = ? AND societe_id = ?");
        $stmt->execute([$docId, $societeId]);
        $doc = $stmt->fetch();
        if ($doc && file_exists($doc['fichier_docx'])) {
            $docxPath = $doc['fichier_docx'];
            $pdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
            $renderer = new DocumentRenderer('', dirname($docxPath));
            try {
                $pdfPath = $renderer->tryConvertToPdf($docxPath, $pdfName);
                if ($pdfPath && file_exists($pdfPath)) {
                    $updateStmt = $pdo->prepare("UPDATE documents_generes SET fichier_pdf = :pdf WHERE id = :id");
                    $updateStmt->execute(['pdf' => $pdfPath, 'id' => $docId]);
                    foreach ($_SESSION['gen_files'][$societeId] ?? [] as &$sf) {
                        if (isset($sf['docx']) && $sf['docx'] === $doc['fichier_docx']) {
                            $sf['pdf'] = $pdfPath;
                        }
                    }
                    unset($sf);
                    set_flash('success', 'PDF genere avec succes.');
                } else {
                    set_flash('error', 'Impossible de generer le PDF.');
                }
            } catch (\Throwable $e) {
                set_flash('error', 'Erreur PDF : ' . $e->getMessage());
            }
        }
    }
    $params = ['societe_id' => $societeId];
    if ($statusFilter) $params['statut'] = $statusFilter;
    redirect_to('generation', $params);
}

$genTypeIcons = [
    'creation' => 'post_add',
    'domiciliation' => 'location_city',
];
$genTypeMapping = $templatesConfig['template_mapping'];

$templatesByGenType = [];
foreach ($filteredTemplates as $tpl) {
    $gt = 'creation';
    foreach ($genTypeMapping as $type => $docTypes) {
        if (in_array($tpl['doc_type'], $docTypes, true)) {
            $gt = $type;
            break;
        }
    }
    $templatesByGenType[$gt][] = $tpl;
}

$genTypeOrder = $genUser && $genUser['collaborateur_type'] !== 'interne'
    ? ['domiciliation']
    : ['creation', 'domiciliation'];

$docTypesConfig = $templatesConfig['document_types'];

$validatedCount = 0;
$brouillonCount = 0;

foreach ($sessionFiles as $gf) {
    $isValide = !str_contains($gf['name'] ?? '', '_Brouillon');
    if ($isValide) $validatedCount++;
    else $brouillonCount++;
}
$totalGenerated = count($sessionFiles);
$docxCount = $totalGenerated;

?>

<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Selectionnez une societe puis les templates a generer.</p>
        </div>
    </div>

    <form method="get" class="inline-form">
        <input type="hidden" name="page" value="generation">
        <select name="societe_id" onchange="this.form.submit()">
            <option value="">Choisir une societe...</option>
            <?php foreach ($societesOptions as $s): ?>
                <option value="<?= e((string) $s['id']) ?>" <?= $societeId === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['societe_raison_sociale']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($societeId > 0): ?>
            <a class="btn btn-cancel" href="<?= e(app_url('generation')) ?>"><span class="material-symbols-outlined">close</span></a>
        <?php endif; ?>
    </form>

    <?php if ($selectedSociete): ?>
        <div class="societe-summary">
            <div class="societe-summary-main">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.3rem">business</span>
                <div>
                    <strong><?= e($selectedSociete['societe_raison_sociale']) ?></strong>
                    <span class="help-text"><?= e($selectedSociete['societe_forme_juridique'] ?: '-') ?> — <?= e($selectedSociete['societe_ville'] ?: '-') ?></span>
                </div>
            </div>
            <div class="societe-summary-details">
                <span><span class="help-text">ICE</span> <?= e($selectedSociete['societe_ice'] ?: '-') ?></span>
                <span><span class="help-text">RC</span> <?= e($selectedSociete['societe_rc'] ?: '-') ?></span>
                <span><span class="help-text">IF</span> <?= e($selectedSociete['societe_if'] ?: '-') ?></span>
            </div>
        </div>

        <?php if ($filteredTemplates): ?>
            <form method="post" id="gen-form">
                <?= csrf_input() ?>
                <input type="hidden" name="societe_id" value="<?= $societeId ?>">

                <div class="table-scroll">
                    <table data-sortable style="white-space: nowrap">
                        <thead>
                            <tr>
                                <th class="col-check"><input type="checkbox" id="select-all" title="Tout selectionner"></th>
                                <th data-col="type">Type de document</th>
                                <th data-col="fichier">Fichier</th>
                                <th data-col="champs">Champs</th>
                                <th data-col="groupe">Groupe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genTypeOrder as $gt): if (empty($templatesByGenType[$gt])) continue; ?>
                                <?php foreach ($templatesByGenType[$gt] as $tpl): ?>
                                    <tr>
                                        <td class="col-check"><input type="checkbox" name="templates[]" value="<?= e($tpl['path']) ?>" checked></td>
                                        <td>
                                            <span class="material-symbols-outlined" style="color:var(--primary);margin-right:6px">article</span>
                                            <?= e($docTypesConfig[$tpl['doc_type']] ?? $tpl['doc_type']) ?>
                                        </td>
                                        <td><span class="help-text"><?= e(basename($tpl['path'])) ?></span></td>
                                        <td><?= count($tpl['variables']) ?></td>
                                        <td>
                                            <span class="material-symbols-outlined" style="color:var(--primary);margin-right:4px"><?= $genTypeIcons[$gt] ?? 'description' ?></span>
                                            <?= e($templatesConfig['generation_types'][$gt] ?? $gt) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-actions table-actions-top">
                <button type="submit" class="btn btn-next">
                    <span class="material-symbols-outlined">sync</span>
                    Generer
                </button>
            </div>
        </form>

        <script>
        document.getElementById('select-all')?.addEventListener('change', function() {
                const form = document.getElementById('gen-form');
                const checkboxes = form.querySelectorAll('input[name="templates[]"]');
                checkboxes.forEach(c => c.checked = this.checked);
            });
            </script>
        <?php else: ?>
            <div class="empty-state">
                <span class="material-symbols-outlined" style="font-size:2rem;color:var(--text-secondary)">description</span>
                <p class="table-empty">Aucun template disponible pour cette forme juridique.</p>
                <a class="btn btn-secondary" href="<?= e(app_url('templates')) ?>">Gerer les templates</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($totalGenerated > 0): ?>
<section class="stats small">
    <article class="stat">
        <span>Total generes</span>
        <strong><?= $totalGenerated ?></strong>
    </article>
    <article class="stat">
        <span>Valides</span>
        <strong style="color:var(--success)"><?= $validatedCount ?></strong>
    </article>
    <article class="stat">
        <span>Brouillons</span>
        <strong style="color:var(--warning)"><?= $brouillonCount ?></strong>
    </article>
    <article class="stat">
        <span><span class="material-symbols-outlined">article</span> Word</span>
        <strong><?= $docxCount ?></strong>
    </article>
</section>
<?php endif; ?>

<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Documents generes</h2>
            <p class="help-text"><?= count($dbDocs) ?> fichier(s)</p>
        </div>
        <div class="table-actions">
            <a class="btn <?= $statusFilter === '' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('generation', ['societe_id' => $societeId])) ?>">Tous</a>
            <a class="btn <?= $statusFilter === 'valide' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('generation', ['societe_id' => $societeId, 'statut' => 'valide'])) ?>">Valides</a>
            <a class="btn <?= $statusFilter === 'brouillon' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('generation', ['societe_id' => $societeId, 'statut' => 'brouillon'])) ?>">Brouillons</a>
        </div>
    </div>

    <?php if ($dbDocs): ?>
        <form method="post" id="files-form">
            <?= csrf_input() ?>
            <input type="hidden" name="societe_id" value="<?= $societeId ?>">
            <div class="table-scroll">
                <table data-sortable style="white-space: nowrap">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="select-all-files" title="Selectionner tout"></th>
                            <th data-col="type">Type de document</th>
                            <th data-col="fichier">Fichier</th>
                            <th data-col="taille">Taille</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="date-creation">Date creation</th>
                            <th data-col="modification">Modification</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbDocs as $doc): ?>
                            <?php $modifTime = file_exists($doc['fichier_docx']) ? filemtime($doc['fichier_docx']) : null; ?>
                            <tr>
                                <td class="col-check"><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>"></td>
                                <td>
                                    <span class="material-symbols-outlined" style="color:var(--primary);margin-right:6px">article</span>
                                    <?= e($docTypesConfig[$doc['doc_type']] ?? $doc['doc_type']) ?>
                                </td>
                                <td><span class="help-text"><?= e(basename($doc['fichier_docx'])) ?></span></td>
                                <td><?= $doc['taille_ko'] ? number_format((float) $doc['taille_ko'], 1) . ' Ko' : '-' ?></td>
                                <td>
                                    <span class="statut-badge <?= $doc['valide'] ? 'valide' : 'brouillon' ?>">
                                        <?= $doc['valide'] ? 'Valide' : 'Brouillon' ?>
                                    </span>
                                </td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $doc['created_at']))) ?></td>
                                <td><span class="help-text"><?= $modifTime ? date('d/m/Y H:i', $modifTime) : '-' ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn-icon" href="<?= e(word_url($doc['fichier_docx'])) ?>" title="Ouvrir dans Word">
                                            <span class="material-symbols-outlined">article</span>
                                        </a>
                                        <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="material-symbols-outlined">download</span>
                                        </a>
                                        <?php if ($doc['valide'] && $doc['fichier_pdf']): ?>
                                            <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                <span class="material-symbols-outlined">picture_as_pdf</span>
                                            </a>
                                        <?php elseif ($doc['valide']): ?>
                                            <a class="btn-icon" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('files-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='generate_pdf_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Generation PDF en cours...'); f.submit();} })();" title="Generer PDF">
                                                <span class="material-symbols-outlined">picture_as_pdf</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$doc['valide']): ?>
                                            <a class="btn-icon" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('files-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='validate_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Validation en cours...'); f.submit();} })();" title="Valider">
                                                <span class="material-symbols-outlined">task_alt</span>
                                            </a>
                                        <?php endif; ?>
                                        <a class="btn-icon danger" href="#" onclick="event.preventDefault(); if(!confirm('Supprimer ce document ?')) return; (function(){ var f=document.getElementById('files-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='delete_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Suppression en cours...'); f.submit();} })();" title="Supprimer">
                                            <span class="material-symbols-outlined">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-actions table-actions-top">
                <button type="submit" class="btn btn-next" name="validate_submit" value="1">
                    <span class="material-symbols-outlined">task_alt</span> Valider
                </button>
                <button type="submit" class="btn btn-back" name="delete_submit" value="1">
                    <span class="material-symbols-outlined">delete</span> Supprimer
                </button>
            </div>
        </form>
        <script>
        document.getElementById('select-all-files')?.addEventListener('change', function() {
            const form = document.getElementById('files-form');
            const checkboxes = form.querySelectorAll('input[name="selected_files[]"]');
            checkboxes.forEach(c => c.checked = this.checked);
        });
        </script>
    <?php else: ?>
        <div class="empty-state">
            <span class="material-symbols-outlined" style="font-size:2rem;color:var(--text-secondary)">description</span>
            <p class="table-empty">Aucun document genere pour cette societe.</p>
        </div>
    <?php endif; ?>
</section>

<div id="loading-overlay">
    <div class="loader-card">
        <div class="spinner"></div>
        <p id="loading-text">Traitement en cours...</p>
    </div>
</div>
<script>
(function(){
    var overlay = document.getElementById('loading-overlay');
    var text = document.getElementById('loading-text');
    window.showOverlay = function(msg){
        text.textContent = msg;
        overlay.classList.add('show');
    };
    document.getElementById('gen-form')?.addEventListener('submit', function(){
        window.showOverlay('Génération en cours...');
    });
    document.getElementById('files-form')?.addEventListener('submit', function(e){
        var btn = e.submitter;
        if(btn && btn.name === 'delete_submit'){
            window.showOverlay('Suppression en cours...');
        } else {
            window.showOverlay('Validation en cours...');
        }
    });
})();
</script>
