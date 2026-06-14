<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}
require_once __DIR__ . '/../../src/analyseur_templates.php';
require_once __DIR__ . '/../../src/rendu_document.php';

$templatesConfig = require __DIR__ . '/../../config/templates.php';
$templatesDir = __DIR__ . '/../../templates';
$outputDir = __DIR__ . '/../../dossiers_generer/dossiers_domiciliation';

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

if (is_post() && !isset($_POST['delete_submit']) && !isset($_POST['validate_submit']) && !isset($_POST['generate_pdf_submit']) && !isset($_POST['restore_submit']) && ($pdo ?? null) instanceof PDO && $selectedSociete) {
    verify_csrf();

    $selectedPaths = $_POST['templates'] ?? [];

    $existingValidatedTypes = [];
    $vStmt = $pdo->prepare("SELECT DISTINCT doc_type FROM documents_generes WHERE societe_id = ? AND valide = 1 AND doc_type IS NOT NULL AND doc_type != ''");
    $vStmt->execute([$societeId]);
    $existingValidatedTypes = array_column($vStmt->fetchAll(), 'doc_type');

    $skipped = [];

    $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);
    $forme = $selectedSociete['societe_forme_juridique'] ?? 'PP';
    $today = date('Y-m-d');
    $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $selectedSociete['societe_raison_sociale'] ?? 'Client')));
    $clientName = preg_replace('/-+/', '-', $clientName);
    $clientName = trim($clientName, '-');

    $folderDate = $context['contrat_date'] ?? $today;
    $folderName = $folderDate . '_' . $forme . '_' . $clientName;
    $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
    $outputDir = __DIR__ . '/../../dossiers_generer/dossiers_domiciliation/' . $folderName;
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    foreach ($selectedPaths as $path) {
        if (!file_exists($path)) continue;
        if (!str_starts_with(realpath($path), realpath($templatesDir))) continue;

        $filename = pathinfo($path, PATHINFO_FILENAME);
        $parts = explode('_', $filename);
        $docType = '';
        if (count($parts) >= 4) {
            $docType = preg_replace('/_?Template$/i', '', implode('_', array_slice($parts, 2)));
        } elseif (count($parts) === 3) {
            $docType = preg_replace('/_?Template$/i', '', $parts[1]);
        }

        if ($docType !== '' && in_array($docType, $existingValidatedTypes, true)) {
            $label = $templatesConfig['document_types'][$docType] ?? $docType;
            $skipped[] = $label;
            continue;
        }

        try {
            $renderer = new DocumentRenderer($path, $outputDir);

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

    $flashParts = [];
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
        $flashParts[] = count($generatedFiles) . ' document(s) genere(s).';
        log_activity($pdo, 'generate', 'document', $societeId, 'Génération — ' . count($generatedFiles) . ' doc(s)', json_encode(['doc_types' => array_map(fn($f) => $f['doc_type'] ?? '', $generatedFiles)]));
    }
    if (count($skipped) > 0) {
        $flashParts[] = count($skipped) . ' deja valide(s) ignore(s) : ' . implode(', ', $skipped) . '. Utilisez "Restaurer en brouillon" pour les modifier.';
    }
    if (count($flashParts) > 0) {
        $hasGenerated = count($generatedFiles) > 0;
        set_flash($hasGenerated ? 'info' : 'error', implode(' ', $flashParts));
    }
    if (count($generatedFiles) > 0 || count($skipped) > 0) {
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

            $updateStmt->execute([
                'fichier_docx' => $newDocx,
                'fichier_pdf' => $doc['fichier_pdf'],
                'id' => $doc['id'],
            ]);
            foreach ($_SESSION['gen_files'][$societeId] ?? [] as &$sf) {
                if ($sf['docx'] === $oldDocx) {
                    $sf['docx'] = $newDocx;
                    $sf['name'] = str_replace('_Brouillon.docx', '.docx', $sf['name']);
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
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT * FROM documents_generes WHERE id IN ($placeholders) AND societe_id = ? AND valide = 1");
        $stmt->execute(array_merge(array_map('intval', $selected), [$societeId]));
        $docs = $stmt->fetchAll();
        $generated = 0;
        $errors = 0;
        $docxRegenCount = 0;
        $updateStmt = $pdo->prepare("UPDATE documents_generes SET fichier_docx = :fichier_docx, fichier_pdf = :pdf WHERE id = :id");

        $today = date('Y-m-d');
        $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $selectedSociete['societe_raison_sociale'] ?? 'Client')));
        $clientName = preg_replace('/-+/', '-', $clientName);
        $clientName = trim($clientName, '-');
        $forme = $selectedSociete['societe_forme_juridique'] ?? 'PP';
        $folderDate = $context['contrat_date'] ?? $today;
        $folderName = $folderDate . '_' . $forme . '_' . $clientName;
        $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
        $subfolderDir = __DIR__ . '/../../dossiers_generer/dossiers_domiciliation/' . $folderName;
        if (!is_dir($subfolderDir)) {
            mkdir($subfolderDir, 0777, true);
        }

        foreach ($docs as $doc) {
            $docxPath = $doc['fichier_docx'];
            $docxDir = dirname($docxPath);
            if (!is_dir($docxDir)) { @mkdir($docxDir, 0777, true); }

            if (!file_exists($docxPath)) {
                $templateSource = $doc['template_source'] ?? '';
                if (($templateSource === '' || !file_exists($templateSource)) && !empty($doc['doc_type'])) {
                    foreach ($allTemplates as $tpl) {
                        if ($tpl['doc_type'] === $doc['doc_type']) {
                            $templateSource = $tpl['path'];
                            break;
                        }
                    }
                }
                if ($templateSource !== '' && file_exists($templateSource)) {
                    try {
                        $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);
                        $docxDir = $subfolderDir;
                        $outName = basename($docxPath);
                        $renderer = new DocumentRenderer($templateSource, $docxDir);
                        $regenerated = $renderer->render($context, $outName);
                        if ($regenerated && file_exists($regenerated)) {
                            $docxPath = $regenerated;
                            $docxRegenCount++;
                        } else {
                            $errors++; continue;
                        }
                    } catch (\Throwable $e) {
                        $errors++; continue;
                    }
                } else {
                    $errors++; continue;
                }
            }
            $pdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
            $renderer = new DocumentRenderer('', $docxDir);
            try {
                $pdfPath = $renderer->tryConvertToPdf($docxPath, $pdfName);
                if ($pdfPath && file_exists($pdfPath)) {
                    $updateStmt->execute(['fichier_docx' => $docxPath, 'pdf' => $pdfPath, 'id' => $doc['id']]);
                    foreach ($_SESSION['gen_files'][$societeId] ?? [] as &$sf) {
                        if (isset($sf['docx']) && $sf['docx'] === $doc['fichier_docx']) {
                            $sf['docx'] = $docxPath;
                            $sf['pdf'] = $pdfPath;
                        }
                    }
                    unset($sf);
                    $generated++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }
        }
        if ($docxRegenCount > 0) {
            set_flash('success', $docxRegenCount . ' DOCX regenere(s) et ' . ($generated - $docxRegenCount) . ' PDF genere(s).');
        } elseif ($generated > 0) {
            set_flash('success', $generated . ' PDF genere(s).');
        }
        if ($errors > 0) {
            set_flash('error', $errors . ' echec(s) PDF.');
        }
    }
    $params = ['societe_id' => $societeId];
    if ($statusFilter) $params['statut'] = $statusFilter;
    redirect_to('generation', $params);
}

if (is_post() && isset($_POST['restore_submit']) && $societeId > 0) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) > 0 && ($pdo ?? null) instanceof PDO) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE valide = 1 AND id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $docs = $stmt->fetchAll();
        $updateStmt = $pdo->prepare("UPDATE documents_generes SET valide = 0, fichier_docx = :fichier_docx, fichier_pdf = NULL WHERE id = :id");
        foreach ($docs as $doc) {
            $oldDocx = $doc['fichier_docx'];
            $newDocx = preg_replace('/\.docx$/i', '_Brouillon.docx', $oldDocx);
            if ($oldDocx !== $newDocx && file_exists($oldDocx)) {
                rename($oldDocx, $newDocx);
            }
            $pdfPath = $doc['fichier_pdf'];
            if ($pdfPath !== null && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            $updateStmt->execute([
                'fichier_docx' => $newDocx,
                'id' => $doc['id'],
            ]);
            foreach ($_SESSION['gen_files'][$societeId] ?? [] as &$sf) {
                if ($sf['docx'] === $oldDocx) {
                    $sf['docx'] = $newDocx;
                    $sf['name'] = str_replace('.docx', '_Brouillon.docx', $sf['name']);
                }
            }
            unset($sf);
        }
        set_flash('success', count($selected) . ' document(s) restaure(s) en brouillon.');
        log_activity($pdo, 'restore', 'document_genere', $societeId, 'Restauration brouillon — ' . count($selected) . ' doc(s)');
        $params = ['societe_id' => $societeId];
        if ($statusFilter) $params['statut'] = $statusFilter;
        redirect_to('generation', $params);
    }
}

$genTypeIcons = [
    'creation' => 'post_add',
    'domiciliation' => 'location_city',
];
$genTypeMapping = $templatesConfig['template_mapping'];

$templatesByGenType = [];
foreach ($filteredTemplates as $tpl) {
    $matched = false;
    foreach ($genTypeMapping as $type => $docTypes) {
        if (in_array($tpl['doc_type'], $docTypes, true)) {
            $templatesByGenType[$type][] = $tpl;
            $matched = true;
        }
    }
    if (!$matched) {
        $templatesByGenType['creation'][] = $tpl;
    }
}

$genTypeOrder = $genUser && $genUser['collaborateur_type'] !== 'interne'
    ? ['domiciliation']
    : ['creation', 'domiciliation'];

$docTypesConfig = $templatesConfig['document_types'];

$validatedCount = 0;
$brouillonCount = 0;

// Priorite aux donnees BDD (plus fiable que la session)
if (!empty($dbDocs)) {
    foreach ($dbDocs as $d) {
        if ((int) $d['valide'] === 1) $validatedCount++;
        else $brouillonCount++;
    }
} else {
    foreach ($sessionFiles as $gf) {
        $isValide = !str_contains($gf['name'] ?? '', '_Brouillon');
        if ($isValide) $validatedCount++;
        else $brouillonCount++;
    }
}
$totalGenerated = count($sessionFiles) > 0 ? count($sessionFiles) : count($dbDocs);
$docxCount = $totalGenerated;
$pdfCount = 0;
if (!empty($dbDocs)) {
    foreach ($dbDocs as $d) {
        if (!empty($d['fichier_pdf'])) $pdfCount++;
    }
}

$hasValidatedDocs = false;
$hasPendingPdf = false;
$hasPdfDocs = false;
$dlWordCount = 0;
$dlPdfCount = 0;
if (($pdo ?? null) instanceof PDO && $societeId > 0) {
    $allDocs = fetch_all_documents($pdo, $societeId);
    $validatedDocs = array_filter($allDocs, fn($d) => (int) $d['valide'] === 1);
    $hasValidatedDocs = count($validatedDocs) > 0;
    $hasPendingPdf = count(array_filter($validatedDocs, fn($d) => empty($d['fichier_pdf']))) > 0;
    $hasPdfDocs = count(array_filter($validatedDocs, fn($d) => !empty($d['fichier_pdf']))) > 0;
    $dlWordCount = count(array_filter($validatedDocs, fn($d) => !empty($d['fichier_docx'])));
    $dlPdfCount = count(array_filter($validatedDocs, fn($d) => !empty($d['fichier_pdf'])));
}

?>

<section class="card stack">
    <div class="section-header">
        <div>
            <p class="warning-text" style="color:var(--warning);font-weight:600;font-size:0.95rem;margin:0"><span class="material-symbols-outlined" style="font-size:1.1rem;vertical-align:middle">warning</span> Selectionnez une societe puis les templates a generer.</p>
        </div>
    </div>

    <form method="get" class="inline-form" style="gap:6px;flex-wrap:nowrap;margin-top:-12px">
        <input type="hidden" name="page" value="generation">
        <select name="societe_id" onchange="this.form.submit()" style="flex:1 1 280px;min-width:180px">
            <option value="">Choisir une societe...</option>
            <?php foreach ($societesOptions as $s): ?>
                <option value="<?= e((string) $s['id']) ?>" <?= $societeId === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['societe_raison_sociale']) ?> — <?= e($s['societe_ice'] ?: $s['societe_ville'] ?: '') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($societeId > 0): ?>
            <a class="btn btn-cancel" href="<?= e(app_url('generation')) ?>" title="Effacer la selection" style="padding:4px 10px;flex-shrink:0;line-height:1;height:42px;box-sizing:border-box;display:inline-flex;align-items:center;gap:4px"><span class="material-symbols-outlined" style="font-size:1.1rem">filter_alt</span><span class="material-symbols-outlined" style="font-size:1.1rem">close</span></a>
        <?php endif; ?>
    </form>

    <?php if ($selectedSociete): ?>
        <div class="societe-summary">
            <div class="societe-summary-main">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.2rem;line-height:1">business</span>
                <strong><?= e($selectedSociete['societe_raison_sociale']) ?></strong>
                <span class="help-text"><?= e($selectedSociete['societe_forme_juridique'] ?: '-') ?></span>
            </div>
            <div class="societe-summary-details">
                <div class="detail-item"><span class="detail-label">Dossier</span><span class="detail-value"><?= e($selectedSociete['societe_dossier'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">E-mail</span><span class="detail-value"><?= e($selectedSociete['societe_email'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">ICE</span><span class="detail-value"><?= e($selectedSociete['societe_ice'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tél</span><span class="detail-value"><?= e($selectedSociete['societe_telephone'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">RC</span><span class="detail-value"><?= e($selectedSociete['societe_rc'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tribunal</span><span class="detail-value"><?= e($selectedSociete['societe_tribunal'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">IF</span><span class="detail-value"><?= e($selectedSociete['societe_if'] ?: '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Capital</span><span class="detail-value"><?= $selectedSociete['societe_capital'] ? number_format((float) $selectedSociete['societe_capital'], 0, ',', ' ') . ' DH' : '-' ?></span></div>
                <div class="detail-item"><span class="detail-label">Type</span><span class="detail-value"><?= e($selectedSociete['societe_type_generation'] ?: '-') ?></span></div>
                <div class="detail-item full-width"><span class="detail-label">Adresse</span><span class="detail-value"><?= e($selectedSociete['societe_adresse_siege'] ?: $selectedSociete['societe_adresse'] ?: '-') ?></span></div>
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
    <article class="stat">
        <span><span class="material-symbols-outlined">picture_as_pdf</span> PDF</span>
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
            <div class="table-scroll">
                <table data-sortable style="white-space: nowrap">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="select-all-files" title="Selectionner tout"></th>
                            <th data-col="type">Type de document</th>
                            <th data-col="fichier">Fichier</th>
                            <th data-col="taille">Taille</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="pdf">PDF</th>
                            <th data-col="date-creation">Date creation</th>
                            <th data-col="modification">Modification</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbDocs as $doc): ?>
                            <?php $modifTime = file_exists($doc['fichier_docx']) ? filemtime($doc['fichier_docx']) : null; ?>
                            <tr>
                                <td class="col-check"><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>" data-doc-name="<?= e($doc['doc_type'] ?? 'Document') ?>"></td>
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
                                <td class="col-pdf">
                                    <?php if ($doc['valide']): ?>
                                        <?php if ($doc['fichier_pdf']): ?>
                                            <span class="material-symbols-outlined pdf-ok" title="PDF genere">check_circle</span>
                                        <?php else: ?>
                                            <span class="material-symbols-outlined pdf-pending" title="En attente">cancel</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="help-text">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $doc['created_at']))) ?></td>
                                <td><span class="help-text"><?= $modifTime ? date('d/m/Y H:i', $modifTime) : '-' ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn-icon primary" href="<?= e(word_url($doc['fichier_docx'])) ?>" title="Ouvrir dans Word">
                                            <span class="material-symbols-outlined">article</span>
                                        </a>
                                        <a class="btn-icon success" href="<?= e(str_replace(__DIR__ . '/../../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="material-symbols-outlined">download</span>
                                        </a>
                                        <?php if ($doc['valide'] && $doc['fichier_pdf']): ?>
                                            <a class="btn-icon danger" href="<?= e(str_replace(__DIR__ . '/../../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                <span class="material-symbols-outlined">picture_as_pdf</span>
                                            </a>
                                        <?php elseif ($doc['valide']): ?>
                                            <a class="btn-icon warning" href="#" onclick="event.preventDefault(); window.generateSinglePdf(<?= (int) $doc['id'] ?>)" title="Generer PDF">
                                                <span class="material-symbols-outlined">picture_as_pdf</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($doc['valide']): ?>
                                            <a class="btn-icon warning" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('files-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='restore_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Restauration en cours...'); f.submit();} })();" title="Restaurer en brouillon">
                                                <span class="material-symbols-outlined">restore</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$doc['valide']): ?>
                                            <a class="btn-icon success" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('files-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='validate_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Validation en cours...'); f.submit();} })();" title="Valider">
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
            <?php if ($brouillonCount > 0): ?>
                <button type="submit" class="btn btn-next" name="validate_submit" value="1">
                    <span class="material-symbols-outlined">task_alt</span> Valider
                </button>
            <?php endif; ?>
                <?php if ($hasValidatedDocs): ?>
                <?php if ($hasPendingPdf): ?>
                <button type="submit" class="btn btn-info" name="generate_pdf_submit" value="1">
                    <span class="material-symbols-outlined">picture_as_pdf</span> Generer PDF
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-cancel" name="restore_submit" value="1">
                    <span class="material-symbols-outlined">restore</span> Restaurer en brouillon
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-back" name="delete_submit" value="1">
                    <span class="material-symbols-outlined">delete</span> Supprimer
                </button>
                <?php if ($hasValidatedDocs && $hasPdfDocs): ?>
                <button type="button" class="btn btn-next" onclick="showDownloadModal()">
                    <span class="material-symbols-outlined">download</span> Telecharger tous
                </button>
                <?php endif; ?>
            </div>
        </form>
        <script>
        document.getElementById('select-all-files')?.addEventListener('change', function() {
            const form = document.getElementById('files-form');
            const checkboxes = form.querySelectorAll('input[name="selected_files[]"]');
            checkboxes.forEach(c => c.checked = this.checked);
        });
        function showDownloadModal() {
            document.getElementById('dl-modal-overlay').classList.add('show');
        }
        function hideDownloadModal() {
            document.getElementById('dl-modal-overlay').classList.remove('show');
        }
        </script>
    <?php else: ?>
        <div class="empty-state">
            <span class="material-symbols-outlined" style="font-size:2rem;color:var(--text-secondary)">description</span>
            <p class="table-empty">Aucun document genere pour cette societe.</p>
        </div>
    <?php endif; ?>
</section>

<div id="dl-modal-overlay" class="dl-modal-overlay" onclick="if(event.target===this)hideDownloadModal()">
    <div class="dl-modal-card">
        <div class="dl-modal-header">
            <span class="material-symbols-outlined">download</span>
            <span>Telecharger tous les documents</span>
            <button type="button" class="dl-modal-close" onclick="hideDownloadModal()">&times;</button>
        </div>
        <div class="dl-modal-body">
            <a class="dl-modal-btn btn btn-primary" href="<?= e(app_url('download_all', ['societe_id' => $societeId, 'type' => 'word'])) ?>" onclick="hideDownloadModal()">
                <span class="material-symbols-outlined">description</span> Word <span class="dl-count">(<?= $dlWordCount ?>)</span>
            </a>
            <a class="dl-modal-btn btn btn-danger" href="<?= e(app_url('download_all', ['societe_id' => $societeId, 'type' => 'pdf'])) ?>" onclick="hideDownloadModal()">
                <span class="material-symbols-outlined">picture_as_pdf</span> PDF <span class="dl-count">(<?= $dlPdfCount ?>)</span>
            </a>
            <a class="dl-modal-btn btn btn-info" href="<?= e(app_url('download_all', ['societe_id' => $societeId, 'type' => 'both'])) ?>" onclick="hideDownloadModal()">
                <span class="material-symbols-outlined">folder_zip</span> Word &amp; PDF <span class="dl-count">(<?= $dlWordCount + $dlPdfCount ?>)</span>
            </a>
        </div>
    </div>
</div>

<div id="loading-overlay">
    <div class="loader-card">
        <div class="spinner"></div>
        <p id="loading-text">Traitement en cours...</p>
    </div>
</div>

<div id="gen-progress-overlay">
    <div class="progress-modal">
        <div class="progress-header">
            <span class="material-symbols-outlined" style="color:var(--primary)">sync</span>
            <h3>Generation en cours...</h3>
            <span class="progress-pct" id="progress-pct">0%</span>
        </div>
        <div class="progress-total-bar">
            <div class="progress-total-fill" id="progress-total-fill"></div>
        </div>
        <ul class="progress-file-list" id="progress-file-list"></ul>
        <div class="progress-footer">
            <div class="spinner-sm"></div>
            <span id="progress-status">Preparation...</span>
        </div>
    </div>
</div>

<script>
(function(){
    /* ---- Simple overlay for quick actions ---- */
    var overlay = document.getElementById('loading-overlay');
    var text = document.getElementById('loading-text');
    window.showOverlay = function(msg){
        text.textContent = msg;
        overlay.classList.add('show');
    };

    /* ---- Detailed progress overlay ---- */
    var progressOverlay = document.getElementById('gen-progress-overlay');
    var progressFileList = document.getElementById('progress-file-list');
    var progressTotalFill = document.getElementById('progress-total-fill');
    var progressPct = document.getElementById('progress-pct');
    var progressStatus = document.getElementById('progress-status');

    function getCsrfToken() {
        var input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function addProgressFile(name) {
        var li = document.createElement('li');
        li.className = 'progress-file-item';
        li.innerHTML =
            '<span class="pfi-icon"><span class="material-symbols-outlined">description</span></span>' +
            '<span class="pfi-name">' + escapeHtml(name) + '</span>' +
            '<div class="pfi-bar"><div class="pfi-fill waiting" style="width:0%"></div></div>' +
            '<span class="pfi-pct">0%</span>';
        progressFileList.appendChild(li);
        return li;
    }

    function updateProgressFile(idx, pct, status) {
        var items = progressFileList.querySelectorAll('.progress-file-item');
        var item = items[idx];
        if (!item) return;
        var fill = item.querySelector('.pfi-fill');
        var pctSpan = item.querySelector('.pfi-pct');
        fill.style.width = pct + '%';
        fill.className = 'pfi-fill ' + status;
        pctSpan.textContent = pct + '%';
        item.classList.remove('done', 'error', 'active');
        if (status === 'done') item.classList.add('done');
        if (status === 'error') item.classList.add('error');
        if (status === 'active') item.classList.add('active');
        updateTotals();
    }

    function updateTotals() {
        var items = progressFileList.querySelectorAll('.progress-file-item');
        var done = 0;
        var total = items.length;
        items.forEach(function(item) {
            if (item.classList.contains('done') || item.classList.contains('error')) done++;
        });
        var pct = total > 0 ? Math.round(done / total * 100) : 0;
        progressTotalFill.style.width = pct + '%';
        progressPct.textContent = pct + '%';
        progressStatus.textContent = done + '/' + total + ' traite(s) -- ' + (total - done) + ' restant(s)';
    }

    function showProgressOverlay(title) {
        progressFileList.innerHTML = '';
        progressTotalFill.style.width = '0%';
        progressPct.textContent = '0%';
        progressStatus.textContent = 'Preparation...';
        document.querySelector('#gen-progress-overlay .progress-header h3').textContent = title;
        progressOverlay.classList.add('show');
    }

    function hideProgressOverlay() {
        progressOverlay.classList.remove('show');
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /* ---- DOCX Generation via AJAX ---- */
    document.getElementById('gen-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var checkboxes = form.querySelectorAll('input[name="templates[]"]:checked');
        if (checkboxes.length === 0) return;

        var societeId = form.querySelector('input[name="societe_id"]').value;
        var csrf = getCsrfToken();
        var total = checkboxes.length;

        showProgressOverlay('Generation des documents...');

        checkboxes.forEach(function(cb) {
            var name = cb.value.split('/').pop().split('\\').pop();
            addProgressFile(name);
        });

        function processNext(idx) {
            if (idx >= total) {
                progressStatus.textContent = 'Termine -- ' + total + '/' + total;
                setTimeout(function() { hideProgressOverlay(); window.location.reload(); }, 800);
                return;
            }

            var cb = checkboxes[idx];
            var tplPath = cb.value;
            updateProgressFile(idx, 20, 'active');
            progressStatus.textContent = (idx + 1) + '/' + total + ' -- Generation DOCX...';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/generation.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.success) {
                            updateProgressFile(idx, 100, 'done');
                        } else if (resp.skipped) {
                            updateProgressFile(idx, 100, 'done');
                            var items = progressFileList.querySelectorAll('.pfi-name');
                            if (items[idx]) items[idx].textContent += ' (deja valide)';
                        } else {
                            updateProgressFile(idx, 100, 'error');
                        }
                    } catch(e) {
                        updateProgressFile(idx, 100, 'error');
                    }
                } else {
                    updateProgressFile(idx, 100, 'error');
                }
                setTimeout(function() { processNext(idx + 1); }, 300);
            };
            xhr.onerror = function() {
                updateProgressFile(idx, 100, 'error');
                setTimeout(function() { processNext(idx + 1); }, 300);
            };
            xhr.send(
                'action=generate_docx' +
                '&societe_id=' + encodeURIComponent(societeId) +
                '&template_path=' + encodeURIComponent(tplPath) +
                '&csrf_token=' + encodeURIComponent(csrf)
            );
        }

        setTimeout(function() { processNext(0); }, 400);
    });

    /* ---- Detect action from submitter or hidden inputs ---- */
    document.getElementById('files-form')?.addEventListener('submit', function(e) {
        var action = null;
        var btn = e.submitter;
        if (btn) {
            action = btn.name;
        } else {
            // Programmatic submit (per-row inline code)
            if (this.querySelector('input[name="generate_pdf_submit"]')) action = 'generate_pdf_submit';
            else if (this.querySelector('input[name="validate_submit"]')) action = 'validate_submit';
            else if (this.querySelector('input[name="restore_submit"]')) action = 'restore_submit';
            else if (this.querySelector('input[name="delete_submit"]')) action = 'delete_submit';
        }

        if (action === 'generate_pdf_submit') {
            e.preventDefault();
            var societeId = this.querySelector('input[name="societe_id"]').value;
            var csrf = getCsrfToken();
            var checkboxes = this.querySelectorAll('input[name="selected_files[]"]:checked');
            if (checkboxes.length === 0) return;
            var total = checkboxes.length;

            showProgressOverlay('Generation PDF...');
            checkboxes.forEach(function(cb) {
                var name = cb.getAttribute('data-doc-name') || 'Document PDF';
                addProgressFile(name);
            });

            function processPdf(idx) {
                if (idx >= total) {
                    progressStatus.textContent = 'Termine -- ' + total + '/' + total;
                    setTimeout(function() { hideProgressOverlay(); window.location.reload(); }, 800);
                    return;
                }
                var cb = checkboxes[idx];
                var docId = cb.value;
                updateProgressFile(idx, 10, 'active');
                progressStatus.textContent = (idx + 1) + '/' + total + ' -- Conversion PDF...';

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/generation.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try { var resp = JSON.parse(xhr.responseText); updateProgressFile(idx, 100, resp.success ? 'done' : 'error'); }
                        catch(e) { updateProgressFile(idx, 100, 'error'); }
                    } else { updateProgressFile(idx, 100, 'error'); }
                    setTimeout(function() { processPdf(idx + 1); }, 300);
                };
                xhr.onerror = function() { updateProgressFile(idx, 100, 'error'); setTimeout(function() { processPdf(idx + 1); }, 300); };
                xhr.send('action=generate_pdf&societe_id=' + encodeURIComponent(societeId) + '&doc_id=' + encodeURIComponent(docId) + '&csrf_token=' + encodeURIComponent(csrf));
            }
            setTimeout(function() { processPdf(0); }, 400);
        } else if (action === 'delete_submit') {
            window.showOverlay('Suppression en cours...');
        } else if (action === 'restore_submit') {
            e.preventDefault();
            var societeId = this.querySelector('input[name="societe_id"]').value;
            var csrf = getCsrfToken();
            var checkboxes = this.querySelectorAll('input[name="selected_files[]"]:checked');
            if (checkboxes.length === 0) return;
            var total = checkboxes.length;
            showProgressOverlay('Restauration en brouillon...');
            checkboxes.forEach(function(cb) {
                var name = cb.getAttribute('data-doc-name') || 'Document';
                addProgressFile(name);
            });
            var formData = new FormData(this);
            formData.append('restore_submit', '1');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    progressStatus.textContent = 'Termine -- ' + total + '/' + total;
                    setTimeout(function() { window.location.reload(); }, 600);
                } else {
                    progressStatus.textContent = 'Erreur lors de la restauration';
                    setTimeout(function() { hideProgressOverlay(); }, 2000);
                }
            };
            xhr.onerror = function() {
                progressStatus.textContent = 'Erreur reseau';
                setTimeout(function() { hideProgressOverlay(); }, 2000);
            };
            xhr.send(formData);
        } else if (action === 'validate_submit') {
            e.preventDefault();
            var societeId = this.querySelector('input[name="societe_id"]').value;
            var checkboxes = this.querySelectorAll('input[name="selected_files[]"]:checked');
            if (checkboxes.length === 0) return;
            var total = checkboxes.length;
            showProgressOverlay('Validation en cours...');
            checkboxes.forEach(function(cb) {
                var name = cb.getAttribute('data-doc-name') || 'Document';
                addProgressFile(name);
            });
            var formData = new FormData(this);
            formData.append('validate_submit', '1');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    progressStatus.textContent = 'Termine -- ' + total + '/' + total;
                    setTimeout(function() { window.location.reload(); }, 600);
                } else {
                    progressStatus.textContent = 'Erreur lors de la validation';
                    setTimeout(function() { hideProgressOverlay(); }, 2000);
                }
            };
            xhr.onerror = function() {
                progressStatus.textContent = 'Erreur reseau';
                setTimeout(function() { hideProgressOverlay(); }, 2000);
            };
            xhr.send(formData);
        }
    });

    /* ---- Single PDF generation (per-row) ---- */
    window.generateSinglePdf = function(docId) {
        var form = document.getElementById('files-form');
        form.querySelectorAll('input[name="selected_files[]"]').forEach(function(c) { c.checked = false; });
        var cb = form.querySelector('input[name="selected_files[]"][value="' + docId + '"]');
        if (!cb) return;
        cb.checked = true;
        // Add hidden input and submit programmatically -> will be caught by submit handler
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = 'generate_pdf_submit'; h.value = '1';
        form.appendChild(h);
        form.submit();
    };
})();
</script>
