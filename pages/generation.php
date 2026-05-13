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

$generatedFiles = $_SESSION['gen_files'][$societeId] ?? [];

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
    $files = $_SESSION['gen_files'][$societeId] ?? [];

    if (count($selected) > 0) {
        $remaining = [];
        $deletedCount = 0;
        foreach ($files as $i => $file) {
            if (in_array((string) $i, $selected, true)) {
                if (file_exists($file['docx'])) unlink($file['docx']);
                if (isset($file['pdf']) && file_exists($file['pdf'])) unlink($file['pdf']);
                $deletedCount++;
            } else {
                $remaining[] = $file;
            }
        }
        $_SESSION['gen_files'][$societeId] = $remaining;
        set_flash('error', $deletedCount . ' document(s) supprime(s).');
        redirect_to('generation', ['societe_id' => $societeId]);
    }
}

if (is_post() && isset($_POST['validate_submit']) && $societeId > 0) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    $files = $_SESSION['gen_files'][$societeId] ?? [];

    if (count($selected) > 0) {
        foreach ($files as $i => &$file) {
            if (in_array((string) $i, $selected, true)) {
                $oldDocx = $file['docx'];
                $newName = str_replace('_Brouillon.docx', '.docx', $file['name']);
                $newDocx = dirname($oldDocx) . DIRECTORY_SEPARATOR . $newName;

                if (file_exists($oldDocx) && $oldDocx !== $newDocx) {
                    rename($oldDocx, $newDocx);
                    $file['docx'] = $newDocx;
                    $file['name'] = $newName;
                }

                if (isset($file['pdf']) && file_exists($file['pdf'])) {
                    $oldPdf = $file['pdf'];
                    $newPdf = str_replace('_Brouillon.pdf', '.pdf', $oldPdf);
                    if ($oldPdf !== $newPdf) {
                        rename($oldPdf, $newPdf);
                        $file['pdf'] = $newPdf;
                    }
                }
            }
        }
        unset($file);
        $_SESSION['gen_files'][$societeId] = $files;
        set_flash('success', count($selected) . ' document(s) valide(s).');
        redirect_to('generation', ['societe_id' => $societeId]);
    }
}

$templatesByType = [];
foreach ($filteredTemplates as $tpl) {
    $type = $tpl['doc_type'];
    $templatesByType[$type][] = $tpl;
}
?>
<section class="grid two">
    <article class="card stack">
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
                <form method="post" class="stack" id="gen-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="societe_id" value="<?= $societeId ?>">

                    <div class="section-header">
                        <h3 style="margin:0;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-secondary)">
                            Templates disponibles
                        </h3>
                        <div class="table-actions">
                            <a class="btn-icon" href="#" id="select-all" title="Tout selectionner"><span class="mdi mdi-check-all"></span></a>
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

                    <button type="submit" class="btn btn-next" style="margin-top:4px">
                        <span class="mdi mdi-file-sync"></span>
                        Generer les documents
                    </button>
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
    </article>

    <article class="card stack">
        <div class="section-header">
            <div>
                <h2>Documents generes</h2>
                <p class="help-text"><?= count($generatedFiles) > 0 ? count($generatedFiles) . ' fichier(s) genere(s)' : 'Aucune generation' ?></p>
            </div>
        </div>

        <?php if ($generatedFiles): ?>
            <form method="post" class="stack" id="files-form">
                <?= csrf_input() ?>
                <input type="hidden" name="societe_id" value="<?= $societeId ?>">
                <div class="section-header">
                    <div class="table-actions">
                        <a class="btn-icon" href="#" id="select-all-files" title="Selectionner tout"><span class="mdi mdi-check-all"></span></a>
                        <button type="submit" class="btn-icon" name="validate_submit" value="1" title="Valider les fichiers selectionnes"><span class="mdi mdi-file-check"></span></button>
                        <button type="submit" class="btn-icon danger" name="delete_submit" value="1" title="Supprimer les fichiers selectionnes"><span class="mdi mdi-delete"></span></button>
                    </div>
                </div>
                <div class="generated-list">
                    <?php foreach ($generatedFiles as $i => $file): ?>
                        <div class="generated-item">
                            <input type="checkbox" name="selected_files[]" value="<?= $i ?>" class="template-check">
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
                <p class="table-empty">Selectionnez une societe et lancez la generation.</p>
            </div>
        <?php endif; ?>
    </article>
</section>
