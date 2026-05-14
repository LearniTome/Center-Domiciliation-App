<?php

declare(strict_types=1);

function pdf_detect_engine(bool $force = false): ?string
{
    if (!$force && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['_pdf_engine'])) {
        return $_SESSION['_pdf_engine'];
    }

    $engine = null;

    if (pdf_shell_available('soffice') || pdf_shell_available('libreoffice')) {
        $engine = 'libreoffice';
    } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $where = shell_exec('where winword 2>NUL');
        if ($where !== null && $where !== '') {
            $engine = 'word';
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['_pdf_engine'] = $engine;
    }

    return $engine;
}

function pdf_shell_available(string $command): bool
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $where = shell_exec("where {$command} 2>NUL");
        return $where !== null && $where !== '';
    }
    $which = shell_exec("which {$command} 2>/dev/null");
    return $which !== null && $which !== '';
}

function pdf_cleanup_word_process(): void
{
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') return;
    $output = shell_exec('tasklist /FI "IMAGENAME eq WINWORD.EXE" /NH 2>NUL');
    if ($output !== null && $output !== '' && stripos($output, 'WINWORD.EXE') !== false) {
        shell_exec('taskkill /F /IM WINWORD.EXE 2>NUL');
    }
}

function pdf_convert_shell(string $docxPath): void
{
    $engine = pdf_detect_engine();

    if ($engine === 'word') {
        try {
            $absPath = realpath($docxPath);
            if ($absPath === false) {
                throw new RuntimeException("Fichier introuvable : {$docxPath}");
            }
            $word = new COM('Word.Application');
            $word->Visible = false;
            $word->DisplayAlerts = false;
            $doc = $word->Documents->Open($absPath);
            $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
            $doc->SaveAs($pdfPath, 17);
            $doc->Close(false);
            $word->Quit(false);
            unset($doc, $word);
            return;
        } catch (Throwable $e) {
            if (isset($word)) {
                try { $word->Quit(false); } catch (Throwable $ignore) {}
                unset($word);
            }
            pdf_cleanup_word_process();
            pdf_detect_engine(true);
            throw new RuntimeException('Erreur Word COM : ' . $e->getMessage());
        }
    }

    $dir = escapeshellarg(dirname($docxPath));
    $file = escapeshellarg($docxPath);

    if (pdf_shell_available('soffice')) {
        shell_exec("soffice --headless --convert-to pdf --outdir {$dir} {$file} 2>&1");
        return;
    }
    if (pdf_shell_available('libreoffice')) {
        shell_exec("libreoffice --headless --convert-to pdf --outdir {$dir} {$file} 2>&1");
        return;
    }
    throw new RuntimeException('Aucun moteur PDF disponible (Word ou LibreOffice requis).');
}

function pdf_create_zip(array $pdfPaths, string $zipPath): bool
{
    $paths = array_filter($pdfPaths, 'file_exists');
    if (!$paths) return false;

    $tmpDir = dirname($zipPath) . DIRECTORY_SEPARATOR . '_zip_tmp_' . bin2hex(random_bytes(4));
    @mkdir($tmpDir, 0777, true);

    foreach ($paths as $i => $p) {
        $name = sprintf('%02d_%s', $i + 1, basename($p));
        @copy($p, $tmpDir . DIRECTORY_SEPARATOR . $name);
    }

    $tmpDirEsc = str_replace("'", "''", $tmpDir);
    $zipPathEsc = str_replace("'", "''", $zipPath);
    $cmd = "powershell -NoProfile -Command \"Compress-Archive -Path '{$tmpDirEsc}\\*' -DestinationPath '{$zipPathEsc}' -Force 2>&1\"";
    $out = shell_exec($cmd);

    $dh = @opendir($tmpDir);
    if ($dh) { while (($f = readdir($dh)) !== false) { if ($f !== '.' && $f !== '..') @unlink("{$tmpDir}/{$f}"); } closedir($dh); }
    @rmdir($tmpDir);

    return file_exists($zipPath) && filesize($zipPath) > 0;
}

$templatesDir = __DIR__ . '/../templates';
$outputDir = __DIR__ . '/../output';

$engineName = pdf_detect_engine();
$engineLabels = ['word' => 'Microsoft Word', 'libreoffice' => 'LibreOffice'];
$engine = $engineName !== null ? ($engineLabels[$engineName] ?? $engineName) : null;

$result = null;

$uploadedDir = $outputDir . DIRECTORY_SEPARATOR . '_uploaded_convert';
if (!is_dir($uploadedDir)) {
    mkdir($uploadedDir, 0777, true);
}

$uploadResult = null;
$convertResult = null;

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload' && $engine) {
        $uploadedCount = 0;
        if (!empty($_FILES['docx_files']['name'][0])) {
            foreach ($_FILES['docx_files']['tmp_name'] as $i => $tmp) {
                if ($tmp && $_FILES['docx_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['docx_files']['name'][$i], PATHINFO_EXTENSION));
                    if ($ext === 'docx') {
                        move_uploaded_file($tmp, $uploadedDir . DIRECTORY_SEPARATOR . basename($_FILES['docx_files']['name'][$i]));
                        $uploadedCount++;
                    }
                }
            }
        }
        $uploadResult = ['ok' => $uploadedCount];
    }

    if ($action === 'clear-uploads') {
        $cleared = true;
        $handle = opendir($uploadedDir);
        if ($handle) {
            while (($entry = readdir($handle)) !== false) {
                if ($entry !== '.' && $entry !== '..') {
                    $path = $uploadedDir . DIRECTORY_SEPARATOR . $entry;
                    if (!@unlink($path) && $cleared) {
                        $cleared = false;
                    }
                }
            }
            closedir($handle);
        }
        $uploadResult = ['cleared' => $cleared ? true : false];
    }

    if ($action === 'convert' && $engine) {
        set_time_limit(120);
        pdf_cleanup_word_process();
        $source = $_POST['source'] ?? 'templates';
        $recursive = isset($_POST['recursive']);
        $selected = $_POST['selected_files'] ?? [];
        $files = [];
        $sourceDir = null;

        if ($source === 'templates') {
            $sourceDir = $templatesDir;
        } elseif ($source === 'output') {
            $sourceDir = $outputDir;
        } elseif ($source === 'uploaded') {
            $sourceDir = $uploadedDir;
        }

        if ($sourceDir && is_dir($sourceDir)) {
            $iterator = ($recursive && $source !== 'uploaded')
                ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS))
                : new DirectoryIterator($sourceDir);

            foreach ($iterator as $file) {
                if ($file->isFile() && str_ends_with(strtolower($file->getFilename()), '.docx')) {
                    $files[] = $file->getPathname();
                }
            }
            sort($files);
        }

        if ($selected) {
            $files = array_intersect($files, $selected);
        }

        $total = count($files);
        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $entries = [];
        $startTime = microtime(true);

        foreach ($files as $idx => $docxPath) {
            $fileStart = microtime(true);
            $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
            $entry = [
                'source_docx' => $docxPath,
                'out_pdf' => $pdfPath,
                'status' => 'ok',
                'error' => '',
                'duration_seconds' => 0,
            ];

            if (!file_exists($docxPath)) {
                $entry['status'] = 'error';
                $entry['error'] = 'Fichier source introuvable';
                $errorCount++;
            } elseif (file_exists($pdfPath) && filemtime($pdfPath) >= filemtime($docxPath)) {
                $entry['status'] = 'skipped';
                $skippedCount++;
            } else {
                try {
                    pdf_convert_shell($docxPath);
                    if (file_exists($pdfPath)) {
                        $entry['status'] = 'ok';
                        $successCount++;
                    } else {
                        $entry['status'] = 'error';
                        $entry['error'] = 'Conversion effectuee mais PDF introuvable.';
                        $errorCount++;
                    }
                } catch (Throwable $e) {
                    $entry['status'] = 'error';
                    $entry['error'] = $e->getMessage();
                    $errorCount++;
                }
            }

            $entry['duration_seconds'] = round(microtime(true) - $fileStart, 3);
            $entries[] = $entry;
        }

        $duration = round(microtime(true) - $startTime, 3);

        $zipPath = null;
        $successPdfs = array_filter(array_column($entries, 'out_pdf'), fn($p) => file_exists($p) && filesize($p) > 0);
        if ($successPdfs) {
            $zipName = 'conversions_' . date('Ymd_His') . '.zip';
            $zipPathFull = $outputDir . DIRECTORY_SEPARATOR . $zipName;
            if (pdf_create_zip($successPdfs, $zipPathFull)) {
                $zipPath = str_replace(__DIR__ . '/../', '', $zipPathFull);
            }
        }

        $convertResult = [
            'generated_at' => date('Y-m-d\TH:i:s'),
            'source_dir' => realpath($sourceDir) ?: $sourceDir,
            'engine' => $engine,
            'global_status' => $errorCount > 0 ? 'error' : 'ok',
            'total_files' => $total,
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'error_count' => $errorCount,
            'duration_seconds' => $duration,
            'files' => $entries,
            'zip_path' => $zipPath,
            'zip_name' => $zipPath ? $zipName : null,
        ];
    }
}

$uploadedFiles = [];
$handle = opendir($uploadedDir);
if ($handle) {
    while (($entry = readdir($handle)) !== false) {
        if ($entry !== '.' && $entry !== '..' && str_ends_with(strtolower($entry), '.docx')) {
            $uploadedFiles[] = $entry;
        }
    }
    closedir($handle);
    sort($uploadedFiles);
}

$scanFiles = [];
foreach (['templates' => $templatesDir, 'output' => $outputDir, 'uploaded' => $uploadedDir] as $key => $dir) {
    $scanFiles[$key] = [];
    if (is_dir($dir)) {
        $it = $key === 'uploaded'
            ? new DirectoryIterator($dir)
            : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with(strtolower($f->getFilename()), '.docx')) {
                $scanFiles[$key][] = ['name' => $f->getFilename(), 'path' => $f->getPathname(), 'size' => number_format($f->getSize() / 1024, 1) . ' Ko'];
            }
        }
    }
    sort($scanFiles[$key]);
}

$recentConversions = [];
if (is_dir($outputDir)) {
    $pdfFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($outputDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($pdfFiles as $f) {
        if ($f->isFile() && str_ends_with(strtolower($f->getFilename()), '.pdf')) {
            $recentConversions[] = [
                'path' => $f->getPathname(),
                'name' => $f->getFilename(),
                'size' => number_format($f->getSize() / 1024, 1) . ' Ko',
                'mtime' => $f->getMTime(),
            ];
        }
    }
    usort($recentConversions, fn($a, $b) => $b['mtime'] - $a['mtime']);
    $recentConversions = array_slice($recentConversions, 0, 20);
}
?>
<section class="stack">
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2>Word to PDF</h2>
                <p class="help-text">Convertissez vos fichiers .docx en PDF avec le moteur disponible.</p>
            </div>
            <div class="table-actions">
                <span class="engine-badge <?= $engine ? 'active' : '' ?>">
                    <span class="mdi mdi-<?= $engine ? 'check-circle' : 'alert-circle' ?>"></span>
                    <?= $engine ? e($engine) : 'Aucun moteur' ?>
                </span>
            </div>
        </div>

        <?php if (!$engine): ?>
            <div class="alert alert-warning">
                <span class="mdi mdi-alert"></span>
                Aucun moteur PDF detecte. Installez <strong>Microsoft Word</strong> ou <strong>LibreOffice</strong> pour utiliser cet outil.
            </div>
        <?php endif; ?>

        <div class="convert-tabs" style="display:flex;gap:6px;margin-bottom:1rem">
            <button type="button" class="btn btn-next convert-tab active" data-tab="upload">
                <span class="mdi mdi-upload"></span> Upload fichiers
            </button>
            <button type="button" class="btn btn-secondary convert-tab" data-tab="browse">
                <span class="mdi mdi-folder-open"></span> Parcourir dossiers
            </button>
        </div>

        <div class="convert-panel" id="panel-upload">
            <form method="post" enctype="multipart/form-data" class="stack" id="upload-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="upload">

                <div class="drop-zone" id="drop-zone">
                    <div class="drop-zone-content">
                        <span class="mdi mdi-cloud-upload" style="font-size:2.5rem;color:var(--primary)"></span>
                        <p style="margin:0.5rem 0;font-weight:500">Glissez-deposez vos fichiers .docx ici</p>
                        <p class="help-text" style="margin:0">ou cliquez pour parcourir</p>
                    </div>
                    <input type="file" name="docx_files[]" accept=".docx" multiple class="drop-zone-input" id="file-input">
                </div>

                <div id="file-preview" style="display:none">
                    <div class="section-header" style="margin-top:0.5rem">
                        <span style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase">
                            <span id="file-count">0</span> fichier(s) selectionne(s)
                        </span>
                    </div>
                    <div class="file-list" id="file-list"></div>
                </div>

                <button type="submit" class="btn btn-next" <?= !$engine ? 'disabled' : '' ?>>
                    <span class="mdi mdi-upload"></span> Uploader et placer dans la file
                </button>
            </form>

            <?php if ($uploadedFiles): ?>
            <div class="section-header" style="margin-top:1rem">
                <span style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase">
                    <span id="upload-selected-count"><?= count($uploadedFiles) ?></span> fichier(s) selectionne(s)
                </span>
                <div class="table-actions">
                    <button type="button" class="btn-icon" data-select-all-upload title="Tout selectionner">
                        <span class="mdi mdi-check-all"></span>
                    </button>
                    <button type="button" class="btn-icon" data-deselect-all-upload title="Tout deselectionner">
                        <span class="mdi mdi-checkbox-blank-off-outline"></span>
                    </button>
                    <form method="post" style="display:inline" onsubmit="return confirm('Vider tous les fichiers uploades ?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="clear-uploads">
                        <button type="submit" class="btn-icon danger" title="Vider"><span class="mdi mdi-delete-sweep"></span></button>
                    </form>
                </div>
            </div>
            <form method="post" id="upload-convert-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="convert">
                <input type="hidden" name="source" value="uploaded">
            <div class="file-list">
                <?php foreach ($uploadedFiles as $uf): ?>
                    <?php $ufPath = $uploadedDir . DIRECTORY_SEPARATOR . $uf; ?>
                    <div class="file-item selected">
                        <input type="checkbox" name="selected_files[]" value="<?= e($ufPath) ?>" checked style="margin:0">
                        <span class="mdi mdi-file-word" style="color:var(--primary);font-size:1.1rem"></span>
                        <span class="file-item-name"><?= e($uf) ?></span>
                        <span class="file-item-size"><?= e(number_format(filesize($ufPath) / 1024, 1)) ?> Ko</span>
                    </div>
                <?php endforeach; ?>
            </div>
                <button type="submit" class="btn btn-next" style="margin-top:0.75rem" <?= !$engine ? 'disabled' : '' ?>>
                    <span class="mdi mdi-file-pdf-box"></span> Convertir la selection en PDF
                </button>
            </form>
            <?php endif; ?>
        </div>

        <div class="convert-panel" id="panel-browse" style="display:none">
            <form method="post" class="stack" id="browse-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="convert">

                <div class="inline-form">
                    <label style="display:flex;align-items:center;gap:6px;flex:1">
                        <span class="mdi mdi-folder"></span>
                        <select name="source" style="flex:1" id="source-select">
                            <option value="templates">Dossier templates</option>
                            <option value="output">Dossier output</option>
                            <option value="uploaded">Fichiers uploades</option>
                        </select>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;white-space:nowrap">
                        <input type="checkbox" name="recursive" value="1" checked id="recursive-check">
                        Recursif
                    </label>
                </div>

                <div id="file-browser" style="margin-top:0.5rem" data-source="templates">
                    <div class="browser-header">
                        <span style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase">
                            <span id="browse-count"><?= count($scanFiles['templates']) ?></span> fichier(s) trouve(s)
                        </span>
                        <div class="table-actions">
                            <button type="button" class="btn-icon" data-select-all title="Tout selectionner">
                                <span class="mdi mdi-check-all"></span>
                            </button>
                            <button type="button" class="btn-icon" data-deselect-all title="Tout deselectionner">
                                <span class="mdi mdi-checkbox-blank-off-outline"></span>
                            </button>
                        </div>
                    </div>
                    <div class="file-list" id="browse-list">
                        <?php $currentFiles = $scanFiles['templates'] ?? []; ?>
                        <?php foreach ($currentFiles as $sf): ?>
                            <div class="file-item selected" data-path="<?= e($sf['path']) ?>">
                                <input type="checkbox" name="selected_files[]" value="<?= e($sf['path']) ?>" checked style="margin:0">
                                <span class="mdi mdi-file-word" style="color:var(--primary);font-size:1.1rem"></span>
                                <span class="file-item-name"><?= e($sf['name']) ?></span>
                                <span class="file-item-size"><?= e($sf['size']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$currentFiles): ?>
                            <p class="table-empty" style="margin:0.5rem 0">Aucun fichier .docx trouve.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-next" <?= !$engine ? 'disabled' : '' ?>>
                    <span class="mdi mdi-file-pdf"></span>
                    Convertir la selection en PDF
                </button>
            </form>
        </div>

        <?php if ($uploadResult): ?>
            <div class="flash flash-success" style="margin-top:0.75rem">
                <span class="mdi mdi-check-circle"></span>
                <?= e((string) ($uploadResult['ok'] ?? '0')) ?> fichier(s) uploades avec succes.
            </div>
        <?php endif; ?>
        <?php if (isset($uploadResult['cleared']) && $uploadResult['cleared']): ?>
            <div class="flash flash-success" style="margin-top:0.75rem">
                <span class="mdi mdi-check-circle"></span>
                Fichiers uploades supprimes.
            </div>
        <?php elseif (isset($uploadResult['cleared']) && !$uploadResult['cleared']): ?>
            <div class="flash flash-error" style="margin-top:0.75rem">
                <span class="mdi mdi-alert"></span>
                Certains fichiers sont verrouilles par un autre processus (Word). Fermez Word et reessayez.
            </div>
        <?php endif; ?>

        <?php if ($convertResult): ?>
        <div class="convert-result">
            <div class="section-header" style="margin-top:1rem">
                <h3 style="margin:0;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-secondary)">
                    <span class="mdi mdi-file-check"></span> Resultat de la conversion
                </h3>
            </div>

            <div class="stats compact">
                <article class="stat">
                    <span>Fichiers</span>
                    <strong><?= $convertResult['total_files'] ?></strong>
                </article>
                <article class="stat">
                    <span>Convertis</span>
                    <strong style="color:var(--success)"><?= $convertResult['success_count'] ?></strong>
                </article>
                <article class="stat">
                    <span>Ignores</span>
                    <strong style="color:var(--warning)"><?= $convertResult['skipped_count'] ?></strong>
                </article>
                <article class="stat">
                    <span>Erreurs</span>
                    <strong style="color:var(--danger)"><?= $convertResult['error_count'] ?></strong>
                </article>
                <article class="stat">
                    <span>Duree</span>
                    <strong><?= $convertResult['duration_seconds'] ?>s</strong>
                </article>
            </div>

            <?php if ($convertResult['zip_path']): ?>
            <div style="margin:0.75rem 0">
                <a class="btn btn-next" href="<?= e($convertResult['zip_path']) ?>">
                    <span class="mdi mdi-download"></span> Tout telecharger en ZIP
                </a>
            </div>
            <?php endif; ?>

            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fichier source</th>
                        <th>PDF</th>
                        <th>Statut</th>
                        <th>Erreur</th>
                        <th>Duree</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($convertResult['files'] as $e): ?>
                        <?php
                            $badge = match ($e['status']) {
                                'ok' => '<span class="statut-badge actif">Converti</span>',
                                'skipped' => '<span class="statut-badge" style="background:var(--warning-bg,transparent);color:var(--warning)">Ignore</span>',
                                default => '<span class="statut-badge resilie">Erreur</span>',
                            };
                        ?>
                        <tr>
                            <td><?= e(basename($e['source_docx'])) ?></td>
                            <td>
                                <?php if ($e['status'] === 'ok' && file_exists($e['out_pdf'])): ?>
                                    <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $e['out_pdf'])) ?>" target="_blank" title="Voir le PDF">
                                        <span class="mdi mdi-file-eye"></span>
                                    </a>
                                    <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $e['out_pdf'])) ?>" download title="Telecharger le PDF">
                                        <span class="mdi mdi-download"></span>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= $badge ?></td>
                            <td style="color:var(--danger);font-size:0.8rem"><?= e($e['error']) ?: '-' ?></td>
                            <td style="font-size:0.8rem"><?= e((string) $e['duration_seconds']) ?>s</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($recentConversions): ?>
        <article class="card stack" style="margin-top:1rem">
            <div class="section-header">
                <h3 style="margin:0;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-secondary)">
                    <span class="mdi mdi-history"></span> Conversions recentes
                </h3>
            </div>
            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fichier PDF</th>
                        <th>Taille</th>
                        <th>Converti le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentConversions as $rc): ?>
                        <tr>
                            <td><?= e($rc['name']) ?></td>
                            <td><?= e($rc['size']) ?></td>
                            <td style="font-size:0.8rem"><?= e(date('d/m/Y H:i', $rc['mtime'])) ?></td>
                            <td>
                                <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $rc['path'])) ?>" target="_blank" title="Voir le PDF">
                                    <span class="mdi mdi-file-eye"></span>
                                </a>
                                <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $rc['path'])) ?>" download title="Telecharger le PDF">
                                    <span class="mdi mdi-download"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </article>
        <?php endif; ?>
    </article>
</section>

<style>
.engine-badge {
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;
    background:rgba(255,255,255,0.05);border:1px solid var(--border);
}
.engine-badge.active { border-color:var(--success);color:var(--success); }
.engine-badge:not(.active) { border-color:var(--danger);color:var(--danger); }

.drop-zone {
    position:relative;border:2px dashed var(--border);
    border-radius:12px;padding:2rem;text-align:center;
    cursor:pointer;transition:all var(--transition);
    background:rgba(255,255,255,0.02);
}
.drop-zone:hover, .drop-zone.drag-over { border-color:var(--primary);background:rgba(74,108,247,0.05); }
.drop-zone-input {
    position:absolute;inset:0;opacity:0;cursor:pointer;
}

.file-list { display:flex;flex-direction:column;gap:2px;margin-top:0.25rem; }
.file-item {
    display:flex;align-items:center;gap:8px;
    padding:6px 10px;border-radius:6px;
    background:rgba(255,255,255,0.03);border:1px solid var(--border);
    font-size:0.85rem;
}
.file-item.selected { border-color:var(--success);background:rgba(0,184,148,0.05); }
.file-item input[type="checkbox"] { width:14px;height:14px;margin:0;flex-shrink:0;cursor:pointer; }
.file-item-name { flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.file-item-size { font-size:0.75rem;color:var(--text-secondary);white-space:nowrap;flex-shrink:0; }

.browser-header {
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:0.25rem;padding:0 2px;
}

.convert-tabs .btn.active { background:rgba(0,184,148,0.12) !important;border-color:var(--success) !important;color:var(--success) !important; }
.convert-panel { transition:opacity 0.2s; }
</style>

<script>
(function() {
    const fileInput = document.getElementById('file-input');
    const filePreview = document.getElementById('file-preview');
    const fileList = document.getElementById('file-list');
    const fileCount = document.getElementById('file-count');
    const dropZone = document.getElementById('drop-zone');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const files = this.files;
            if (files.length === 0) { filePreview.style.display = 'none'; return; }
            filePreview.style.display = 'block';
            fileCount.textContent = files.length;
            fileList.innerHTML = '';
            Array.from(files).forEach(function(f) {
                const div = document.createElement('div');
                div.className = 'file-item selected';
                div.innerHTML = '<span class="mdi mdi-file-word" style="color:var(--primary);font-size:1.1rem"></span>'
                    + '<span class="file-item-name">' + f.name + '</span>'
                    + '<span class="file-item-size">' + (f.size / 1024).toFixed(1) + ' Ko</span>'
                    + '<span class="mdi mdi-check-circle" style="color:var(--success);font-size:0.9rem"></span>';
                fileList.appendChild(div);
            });
        });

        dropZone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', function() { this.classList.remove('drag-over'); });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault(); this.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                const dt = new DataTransfer();
                for (let f of e.dataTransfer.files) {
                    if (f.name.endsWith('.docx')) dt.items.add(f);
                }
                fileInput.files = dt.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }

    const tabs = document.querySelectorAll('.convert-tab');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) { t.classList.remove('active'); t.classList.remove('btn-next'); t.classList.add('btn-secondary'); });
            this.classList.add('active'); this.classList.remove('btn-secondary'); this.classList.add('btn-next');
            document.querySelectorAll('.convert-panel').forEach(function(p) { p.style.display = 'none'; });
            document.getElementById('panel-' + this.getAttribute('data-tab')).style.display = '';
        });
    });

    const sourceSelect = document.getElementById('source-select');
    const browseContainer = document.getElementById('file-browser');
    const browseList = document.getElementById('browse-list');
    const browseCount = document.getElementById('browse-count');

    if (sourceSelect && browseList) {
        function updateBrowseList() {
            var source = sourceSelect.value;
            var recursive = document.getElementById('recursive-check').checked;
            var allFiles = <?= json_encode($scanFiles, JSON_UNESCAPED_UNICODE) ?>;
            var files = allFiles[source] || [];
            browseCount.textContent = files.length;
            browseList.setAttribute('data-source', source);
            browseList.innerHTML = '';
            if (files.length === 0) {
                browseList.innerHTML = '<p class="table-empty" style="margin:0.5rem 0">Aucun fichier .docx trouve.</p>';
                return;
            }
            files.forEach(function(f) {
                var div = document.createElement('div');
                div.className = 'file-item selected';
                div.innerHTML = '<input type="checkbox" name="selected_files[]" value="' + eAttr(f.path) + '" checked style="margin:0">'
                    + '<span class="mdi mdi-file-word" style="color:var(--primary);font-size:1.1rem"></span>'
                    + '<span class="file-item-name">' + eHtml(f.name) + '</span>'
                    + '<span class="file-item-size">' + eHtml(f.size) + '</span>';
                div.querySelector('input').addEventListener('change', function() {
                    div.classList.toggle('selected', this.checked);
                });
                browseList.appendChild(div);
            });
        }
        sourceSelect.addEventListener('change', updateBrowseList);
        document.getElementById('recursive-check').addEventListener('change', updateBrowseList);
        updateBrowseList();
    }

    document.querySelectorAll('[data-select-all]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var container = this.closest('.browser-header').nextElementSibling;
            container.querySelectorAll('input[type="checkbox"]').forEach(function(c) { c.checked = true; c.closest('.file-item').classList.add('selected'); });
        });
    });
    document.querySelectorAll('[data-deselect-all]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var container = this.closest('.browser-header').nextElementSibling;
            container.querySelectorAll('input[type="checkbox"]').forEach(function(c) { c.checked = false; c.closest('.file-item').classList.remove('selected'); });
        });
    });

    document.querySelectorAll('.file-item input[type="checkbox"]').forEach(function(c) {
        c.addEventListener('change', function() {
            this.closest('.file-item').classList.toggle('selected', this.checked);
            updateUploadCount();
        });
    });

    var uploadForm = document.getElementById('upload-convert-form');
    function updateUploadCount() {
        if (!uploadForm) return;
        var checked = uploadForm.querySelectorAll('input[name="selected_files[]"]:checked').length;
        var el = document.getElementById('upload-selected-count');
        if (el) el.textContent = checked;
    }

    document.querySelectorAll('[data-select-all-upload]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var list = this.closest('.section-header').nextElementSibling;
            list.querySelectorAll('input[type="checkbox"]').forEach(function(c) { c.checked = true; c.closest('.file-item').classList.add('selected'); });
            updateUploadCount();
        });
    });
    document.querySelectorAll('[data-deselect-all-upload]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var list = this.closest('.section-header').nextElementSibling;
            list.querySelectorAll('input[type="checkbox"]').forEach(function(c) { c.checked = false; c.closest('.file-item').classList.remove('selected'); });
            updateUploadCount();
        });
    });

    function eAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    function eHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
</script>
