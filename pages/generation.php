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

$generatedFiles = [];

if (is_post() && ($pdo ?? null) instanceof PDO && $selectedSociete) {
    verify_csrf();

    $selectedPaths = $_POST['templates'] ?? [];
    $generatePdf = isset($_POST['pdf']);

    $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);

    foreach ($selectedPaths as $path) {
        if (!file_exists($path)) continue;
        if (!str_starts_with(realpath($path), realpath($templatesDir))) continue;

        try {
            $renderer = new DocumentRenderer($path, $outputDir);
            $docxPath = $renderer->render($context);

            $result = [
                'docx' => $docxPath,
                'pdf' => null,
                'name' => basename($path),
            ];

            if ($generatePdf) {
                $pdfPath = $renderer->tryConvertToPdf($docxPath);
                $result['pdf'] = $pdfPath;
            }

            $generatedFiles[] = $result;
        } catch (\Throwable $e) {
            set_flash('error', 'Erreur sur ' . basename($path) . ' : ' . $e->getMessage());
        }
    }

    if (count($generatedFiles) > 0) {
        set_flash('success', count($generatedFiles) . ' document(s) genere(s).');
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
            <div class="info-grid">
                <div><strong>Societe</strong><span><?= e($selectedSociete['raison_sociale']) ?></span></div>
                <div><strong>Forme</strong><span><?= e($selectedSociete['forme_juridique']) ?></span></div>
                <div><strong>ICE</strong><span><?= e($selectedSociete['ice'] ?: '-') ?></span></div>
                <div><strong>Ville</strong><span><?= e($selectedSociete['ville'] ?: '-') ?></span></div>
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
                            <label style="font-size:0.85rem;color:var(--text-secondary);display:flex;align-items:center;gap:4px">
                                <input type="checkbox" name="pdf" value="1" checked>
                                Convertir en PDF
                            </label>
                        </div>
                    </div>

                    <div class="stack" style="gap:4px">
                        <?php foreach ($filteredTemplates as $tpl): ?>
                            <label style="display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid var(--line);border-radius:var(--radius-sm);cursor:pointer">
                                <input type="checkbox" name="templates[]" value="<?= e($tpl['path']) ?>" checked>
                                <span class="mdi mdi-file-word" style="color:var(--primary)"></span>
                                <span style="flex:1"><?= e($templatesConfig['document_types'][$tpl['doc_type']] ?? $tpl['doc_type']) ?></span>
                                <span style="color:var(--text-secondary);font-size:0.8rem"><?= count($tpl['variables']) ?> vars</span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn">
                        <span class="mdi mdi-file-document-outline"></span>
                        Generer les documents
                    </button>
                </form>
            <?php else: ?>
                <p class="table-empty">Aucun template disponible pour cette forme juridique.</p>
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
            <?php foreach ($generatedFiles as $file): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius-sm)">
                    <span class="mdi mdi-file-word" style="color:var(--primary);font-size:1.2rem"></span>
                    <span style="flex:1;font-size:0.9rem"><?= e($file['name']) ?></span>
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
        <?php else: ?>
            <p class="table-empty">Selectionnez une societe et lancez la generation.</p>
        <?php endif; ?>
    </article>
</section>
