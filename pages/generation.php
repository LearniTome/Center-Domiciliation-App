<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';
require_once __DIR__ . '/../src/DocumentRenderer.php';

$templatesConfig = require __DIR__ . '/../config/templates.php';
$templatesDir = __DIR__ . '/../templates';
$outputDir = __DIR__ . '/../output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$societeId = isset($_GET['societe_id']) ? (int) $_GET['societe_id'] : 0;
$legalForm = field_value($_GET, 'forme');

$societesOptions = fetch_societes_options($pdo ?? null);

$selectedSociete = null;
if ($societeId > 0) {
    $selectedSociete = fetch_record($pdo ?? null, 'societes', $societeId);
    if ($selectedSociete) {
        $legalForm = $selectedSociete['forme_juridique'] ?? '';
    }
}

$allTemplates = TemplateAnalyzer::scanTemplates($templatesDir);

function filterTemplatesByLegalForm(array $templates, string $form): array
{
    $folderMap = [
        'SARL-AU' => 'SARL AU',
        'SARL' => 'SARL',
        'SA' => 'SA',
    ];

    $targetFolder = $folderMap[$form] ?? '';
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
    $filteredTemplates = filterTemplatesByLegalForm($allTemplates, $legalForm);
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

if (is_post() && !isset($_POST['delete_submit']) && !isset($_POST['validate_submit']) && ($pdo ?? null) instanceof PDO && $selectedSociete) {
    verify_csrf();

    $selectedPaths = $_POST['templates'] ?? [];
    $generatePdf = isset($_POST['pdf']);

    $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);
    $forme = $selectedSociete['forme_juridique'] ?? 'PP';
    $today = date('Y-m-d');
    $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $selectedSociete['raison_sociale'] ?? 'Client')));
    $clientName = preg_replace('/-+/', '-', $clientName);
    $clientName = trim($clientName, '-');

    $folderDate = $context['date_contrat'] ?? $today;
    $folderName = $folderDate . '_' . $forme . '_' . $clientName;
    $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
    $outputDir = __DIR__ . '/../output/' . $folderName;
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
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE valide = 0 AND id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $docs = $stmt->fetchAll();
        $updateStmt = $pdo->prepare("UPDATE documents_generes SET valide = 1, fichier_docx = :fichier_docx, fichier_pdf = :fichier_pdf WHERE id = :id");
        foreach ($docs as $doc) {
            $oldDocx = $doc['fichier_docx'];
            $newDocx = str_replace('_Brouillon.docx', '.docx', $oldDocx);
            if ($oldDocx !== $newDocx && file_exists($oldDocx)) {
                rename($oldDocx, $newDocx);
            }
            $newPdf = $doc['fichier_pdf'];
            if ($newPdf !== null) {
                $renamedPdf = str_replace('_Brouillon.pdf', '.pdf', $newPdf);
                if ($newPdf !== $renamedPdf && file_exists($newPdf)) {
                    rename($newPdf, $renamedPdf);
                    $newPdf = $renamedPdf;
                }
            }
            $updateStmt->execute([
                'fichier_docx' => $newDocx,
                'fichier_pdf' => $newPdf,
                'id' => $doc['id'],
            ]);
            foreach ($_SESSION['gen_files'][$societeId] ?? [] as &$sf) {
                if ($sf['docx'] === $oldDocx) {
                    $sf['docx'] = $newDocx;
                    $sf['name'] = str_replace('_Brouillon.docx', '.docx', $sf['name']);
                    if ($newPdf !== $doc['fichier_pdf']) $sf['pdf'] = $newPdf;
                }
            }
            unset($sf);
        }
        set_flash('success', count($selected) . ' document(s) valide(s).');
        $params = ['societe_id' => $societeId];
        if ($statusFilter) $params['statut'] = $statusFilter;
        redirect_to('generation', $params);
    }
}

$genTypeIcons = [
    'creation' => 'mdi-file-document-plus',
    'domiciliation' => 'mdi-home-city',
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

$genTypeOrder = ['creation', 'domiciliation'];

$docTypesConfig = $templatesConfig['document_types'];

$validatedCount = 0;
$brouillonCount = 0;
$pdfCount = 0;

foreach ($sessionFiles as $gf) {
    $isValide = !str_contains($gf['name'] ?? '', '_Brouillon');
    if ($isValide) $validatedCount++;
    else $brouillonCount++;
    if ($gf['pdf'] !== null) $pdfCount++;
}
$totalGenerated = count($sessionFiles);
$docxCount = $totalGenerated;

?>
<style>.main { overflow-x: hidden; }</style>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Generateur de dossiers</h2>
            <p class="help-text">Selectionnez une societe puis les templates a generer.</p>
        </div>
    </div>

    <form method="get" class="inline-form">
        <input type="hidden" name="page" value="generation">
        <select name="societe_id" onchange="this.form.submit()">
            <option value="">Choisir une societe...</option>
            <?php foreach ($societesOptions as $s): ?>
                <option value="<?= e((string) $s['id']) ?>" <?= $societeId === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['raison_sociale']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($societeId > 0): ?>
            <a class="btn btn-cancel" href="<?= e(app_url('generation')) ?>"><span class="mdi mdi-close"></span></a>
        <?php endif; ?>
    </form>

    <?php if ($selectedSociete): ?>
        <div class="societe-summary">
            <div class="societe-summary-main">
                <span class="mdi mdi-domain" style="color:var(--primary);font-size:1.3rem"></span>
                <div>
                    <strong><?= e($selectedSociete['raison_sociale']) ?></strong>
                    <span class="help-text"><?= e($selectedSociete['forme_juridique'] ?: '-') ?> — <?= e($selectedSociete['ville'] ?: '-') ?></span>
                </div>
            </div>
            <div class="societe-summary-details">
                <span><span class="help-text">ICE</span> <?= e($selectedSociete['ice'] ?: '-') ?></span>
                <span><span class="help-text">RC</span> <?= e($selectedSociete['rc'] ?: '-') ?></span>
                <span><span class="help-text">IF</span> <?= e($selectedSociete['if_number'] ?: '-') ?></span>
            </div>
        </div>

        <?php if ($filteredTemplates): ?>
            <form method="post" id="gen-form">
                <?= csrf_input() ?>
                <input type="hidden" name="societe_id" value="<?= $societeId ?>">

                <div class="section-header">
                    <div class="table-actions">
                        <a class="btn-icon" href="#" id="select-all" title="Tout selectionner"><span class="mdi mdi-check-all"></span></a>
                        <label class="pdf-toggle">
                            <input type="checkbox" name="pdf" value="1" checked>
                            <span class="mdi mdi-file-pdf"></span> PDF
                        </label>
                        <button type="submit" class="btn btn-next">
                            <span class="mdi mdi-file-sync"></span>
                            Generer
                        </button>
                    </div>
                </div>

                <div class="table-scroll" style="overflow-x: auto; margin-left: -1.25rem; margin-right: 0; padding-right: 24px;">
                    <table style="white-space: nowrap">
                        <thead>
                            <tr>
                                <th class="col-check"></th>
                                <th>Type de document</th>
                                <th>Fichier source</th>
                                <th>Champs</th>
                                <th>Groupe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genTypeOrder as $gt): if (empty($templatesByGenType[$gt])) continue; ?>
                                <?php foreach ($templatesByGenType[$gt] as $tpl): ?>
                                    <tr>
                                        <td><input type="checkbox" name="templates[]" value="<?= e($tpl['path']) ?>" checked></td>
                                        <td>
                                            <span class="mdi mdi-file-word" style="color:var(--primary);margin-right:6px"></span>
                                            <?= e($docTypesConfig[$tpl['doc_type']] ?? $tpl['doc_type']) ?>
                                        </td>
                                        <td><span class="help-text"><?= e(basename($tpl['path'])) ?></span></td>
                                        <td><?= count($tpl['variables']) ?></td>
                                        <td>
                                            <span class="mdi <?= $genTypeIcons[$gt] ?? 'mdi-file-document' ?>" style="color:var(--primary);margin-right:4px"></span>
                                            <?= e($templatesConfig['generation_types'][$gt] ?? $gt) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <script>
            document.getElementById('select-all')?.addEventListener('click', function(e) {
                e.preventDefault();
                const form = document.getElementById('gen-form');
                const checkboxes = form.querySelectorAll('input[name="templates[]"]');
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                checkboxes.forEach(c => c.checked = !allChecked);
            });
            </script>
        <?php else: ?>
            <div class="empty-state">
                <span class="mdi mdi-file-document-outline" style="font-size:2rem;color:var(--text-secondary)"></span>
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
        <span><span class="mdi mdi-file-word"></span> Word</span>
        <strong><?= $docxCount ?></strong>
    </article>
    <article class="stat">
        <span><span class="mdi mdi-file-pdf"></span> PDF</span>
        <strong><?= $pdfCount ?></strong>
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
            <div class="section-header">
                <div class="table-actions">
                    <a class="btn-icon" href="#" id="select-all-files" title="Selectionner tout"><span class="mdi mdi-check-all"></span></a>
                    <button type="submit" class="btn btn-next" name="validate_submit" value="1">
                        <span class="mdi mdi-file-check"></span> Valider
                    </button>
                    <button type="submit" class="btn btn-back" name="delete_submit" value="1">
                        <span class="mdi mdi-delete"></span> Supprimer
                    </button>
                </div>
            </div>
            <div class="table-scroll" style="overflow-x: auto; margin-left: -1.25rem; margin-right: 0; padding-right: 24px;">
                <table style="white-space: nowrap">
                    <thead>
                        <tr>
                            <th class="col-check"></th>
                            <th>Type de document</th>
                            <th>Fichier</th>
                            <th>Taille</th>
                            <th>Statut</th>
                            <th>Date creation</th>
                            <th>Modification</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbDocs as $doc): ?>
                            <?php $modifTime = file_exists($doc['fichier_docx']) ? filemtime($doc['fichier_docx']) : null; ?>
                            <tr>
                                <td><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>"></td>
                                <td>
                                    <span class="mdi mdi-file-word" style="color:var(--primary);margin-right:6px"></span>
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
                                            <span class="mdi mdi-file-word"></span>
                                        </a>
                                        <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="mdi mdi-download"></span>
                                        </a>
                                        <?php if ($doc['fichier_pdf']): ?>
                                            <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                <span class="mdi mdi-file-pdf"></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$doc['valide']): ?>
                                            <a class="btn-icon" href="#" onclick="event.preventDefault(); document.querySelector('#files-form input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']').checked=true; document.querySelector('button[name=\'validate_submit\']').click();" title="Valider">
                                                <span class="mdi mdi-file-check"></span>
                                            </a>
                                        <?php endif; ?>
                                        <a class="btn-icon danger" href="#" onclick="if(!confirm('Supprimer ce document ?')){event.preventDefault();return false;} event.preventDefault(); document.querySelector('#files-form input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']').checked=true; document.querySelector('button[name=\'delete_submit\']').click();" title="Supprimer">
                                            <span class="mdi mdi-delete"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <script>
        document.getElementById('select-all-files')?.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('files-form');
            const checkboxes = form.querySelectorAll('input[name="selected_files[]"]');
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            checkboxes.forEach(c => c.checked = !allChecked);
        });
        </script>
    <?php else: ?>
        <div class="empty-state">
            <span class="mdi mdi-file-document-outline" style="font-size:2rem;color:var(--text-secondary)"></span>
            <p class="table-empty">Aucun document genere pour cette societe.</p>
        </div>
    <?php endif; ?>
</section>
