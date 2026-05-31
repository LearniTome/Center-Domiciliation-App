<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesConfig = require __DIR__ . '/../config/templates.php';
$templatesDir = __DIR__ . '/../templates';

$folderLabels = $templatesConfig['folder_labels'];
$docTypes = $templatesConfig['document_types'];

$displayFolders = ['_Racine-Actifs'];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query("SELECT forme_juridique, template_folder FROM ref_formes_juridiques ORDER BY id");
    $allForms = $stmt->fetchAll();
    foreach ($allForms as $form) {
        $name = (string) $form['forme_juridique'];
        $tf = (string) ($form['template_folder'] ?? '');
        $key = $tf !== '' ? $tf : $name;
        if (!in_array($key, $displayFolders, true)) {
            $displayFolders[] = $key;
        }
        if (!isset($folderLabels[$key])) {
            $folderLabels[$key] = $name;
        }
    }
}

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload' && !empty($_FILES['template_file']['name'])) {
        $file = $_FILES['template_file'];
        $folder = field_value($_POST, 'folder', '_Racine-Actifs');
        $targetDir = $templatesDir . DIRECTORY_SEPARATOR . $folder;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $dest = $targetDir . DIRECTORY_SEPARATOR . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            set_flash('success', 'Template ajoute avec succes.');
        } else {
            set_flash('error', 'Erreur lors de l\'upload.');
        }
        redirect_to('templates');
    }

    if ($action === 'delete') {
        $path = field_value($_POST, 'path');
        if ($path !== '' && file_exists($path) && str_starts_with(realpath($path), realpath($templatesDir))) {
            unlink($path);
            set_flash('success', 'Template supprime.');
        }
        redirect_to('templates');
    }

    if ($action === 'create_folder') {
        $folderName = field_value($_POST, 'folder_name');
        if ($folderName !== '') {
            $newDir = $templatesDir . DIRECTORY_SEPARATOR . $folderName;
            if (!is_dir($newDir)) {
                mkdir($newDir, 0777, true);
                set_flash('success', 'Dossier cree.');
            } else {
                set_flash('error', 'Ce dossier existe deja.');
            }
        }
        redirect_to('templates');
    }

    if ($action === 'copy_templates') {
        $source = field_value($_POST, 'source_folder');
        $dest = field_value($_POST, 'dest_folder');
        if ($source !== '' && $dest !== '' && $source !== $dest) {
            $sourceDir = $templatesDir . DIRECTORY_SEPARATOR . $source;
            $destDir = $templatesDir . DIRECTORY_SEPARATOR . $dest;
            if (is_dir($sourceDir)) {
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }
                $destPrefix = $dest;
                $destPrefix = str_replace(' ', '-', $destPrefix);
                $destPrefix = strtoupper($destPrefix);

                $count = 0;
                $files = glob($sourceDir . DIRECTORY_SEPARATOR . '*.docx');
                foreach ($files as $file) {
                    $filename = basename($file);
                    $base = preg_replace('/^.+?(\d{4}-\d{2}_)/', '$1', $filename);
                    if ($dest === '_Racine-Actifs') {
                        $newName = $base;
                    } else {
                        $newName = $destPrefix . '_' . $base;
                    }
                    $destFile = $destDir . DIRECTORY_SEPARATOR . $newName;
                    if (!file_exists($destFile)) {
                        copy($file, $destFile);
                        $count++;
                    }
                }
                set_flash('success', "$count template(s) copie(s) de " . ($folderLabels[$source] ?? $source) . " vers " . ($folderLabels[$dest] ?? $dest) . ".");
            } else {
                set_flash('error', 'Dossier source introuvable.');
            }
        }
        redirect_to('templates');
    }

    if ($action === 'backup_templates') {
        $folder = field_value($_POST, 'backup_folder');
        $backupDir = __DIR__ . '/../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $dateStr = date('Ymd_His');
        $zipName = $folder === '_ALL_' ? "tous_templates_$dateStr.zip" : "templates_${folder}_$dateStr.zip";
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $added = 0;
            if ($folder === '_ALL_') {
                $dirs = $displayFolders;
            } else {
                $dirs = [$folder];
            }
            foreach ($dirs as $d) {
                $dPath = $templatesDir . DIRECTORY_SEPARATOR . $d;
                if (!is_dir($dPath)) continue;
                $files = glob($dPath . DIRECTORY_SEPARATOR . '*.docx');
                foreach ($files as $f) {
                    $zip->addFile($f, $d . '/' . basename($f));
                    $added++;
                }
            }
            $zip->close();
            if ($added > 0) {
                set_flash('success', "Sauvegarde creee : <a href=\"backups/$zipName\">$zipName</a> ($added template(s)).");
            } else {
                unlink($zipPath);
                set_flash('error', 'Aucun template a sauvegarder.');
            }
        } else {
            set_flash('error', 'Erreur lors de la creation de l\'archive.');
        }
        redirect_to('templates');
    }
}

$templates = TemplateAnalyzer::scanTemplates($templatesDir);
$grouped = TemplateAnalyzer::groupByFolder($templates);

$templateFolders = [];
if (is_dir($templatesDir)) {
    $items = scandir($templatesDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($templatesDir . DIRECTORY_SEPARATOR . $item)) {
            $templateFolders[] = $item;
        }
    }
    sort($templateFolders);
}

// Ensure folder exists on disk for display (create if missing)
foreach ($displayFolders as $folder) {
    $path = $templatesDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}

$sortedFolders = [];
$nonEmpty = [];
$empty = [];
foreach ($displayFolders as $folder) {
    $items = $grouped[$folder] ?? [];
    if ($items) {
        $nonEmpty[] = $folder;
    } else {
        $empty[] = $folder;
    }
}
$sortedFolders = array_merge($nonEmpty, $empty);

?>
<section>
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2>Templates de documents</h2>
                <p class="help-text"><?= count($templates) ?> template(s) trouve(s)</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-next" href="#" onclick="document.getElementById('folder-form').classList.toggle('hidden'); return false;"><span class="mdi mdi-folder-plus"></span> Nouveau dossier</a>
                <a class="btn btn-next" href="#" onclick="document.getElementById('upload-form').classList.toggle('hidden'); return false;"><span class="mdi mdi-plus"></span> Ajouter un template</a>
                <a class="btn btn-info" href="#" onclick="document.getElementById('copy-form').classList.toggle('hidden'); return false;"><span class="mdi mdi-content-copy"></span> Copier</a>
                <a class="btn" href="#" onclick="document.getElementById('backup-form').classList.toggle('hidden'); return false;"><span class="mdi mdi-download"></span> Backup</a>
            </div>
        </div>

        <div id="folder-form" class="stack hidden">
            <form method="post" class="inline-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_folder">
                <input type="text" name="folder_name" placeholder="Nom du dossier (ex: SARL)" required>
                <button type="submit" class="btn">Creer</button>
            </form>
        </div>

        <div id="upload-form" class="stack hidden">
            <form method="post" enctype="multipart/form-data" class="inline-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="upload">
                <input type="file" name="template_file" accept=".docx" required>
                <select name="folder">
                    <?php foreach ($displayFolders as $folder): ?>
                        <option value="<?= e($folder) ?>"><?= e($folderLabels[$folder] ?? $folder) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Uploader</button>
            </form>
        </div>

        <div id="copy-form" class="stack hidden">
            <form method="post" class="inline-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="copy_templates">
                <select name="source_folder" required>
                    <option value="">-- Depuis --</option>
                    <?php foreach ($sortedFolders as $folder): ?>
                        <?php $cnt = count($grouped[$folder] ?? []); ?>
                        <?php if ($cnt > 0): ?>
                            <option value="<?= e($folder) ?>"><?= e($folderLabels[$folder] ?? $folder) ?> (<?= $cnt ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <span class="mdi mdi-arrow-right"></span>
                <select name="dest_folder" required>
                    <option value="">-- Vers --</option>
                    <?php foreach ($displayFolders as $folder): ?>
                        <option value="<?= e($folder) ?>"><?= e($folderLabels[$folder] ?? $folder) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-info">Copier les templates</button>
            </form>
        </div>

        <div id="backup-form" class="stack hidden">
            <form method="post" class="inline-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="backup_templates">
                <select name="backup_folder" required>
                    <option value="_ALL_">Tous les dossiers</option>
                    <?php foreach ($displayFolders as $folder): ?>
                        <option value="<?= e($folder) ?>"><?= e($folderLabels[$folder] ?? $folder) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Creer l'archive ZIP</button>
            </form>
        </div>

        <?php if ($displayFolders): ?>
            <?php foreach ($sortedFolders as $folder): ?>
                <?php $items = $grouped[$folder] ?? []; ?>
                <?php $hasItems = (bool) $items; ?>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button type="button" class="accordion-trigger" aria-expanded="<?= $hasItems ? 'true' : 'false' ?>" onclick="toggleAccordion(this)">
                            <span class="accordion-label"><?= e($folderLabels[$folder] ?? $folder) ?></span>
                            <span class="accordion-count"><?= count($items) ?></span>
                            <span class="mdi mdi-chevron-down accordion-chevron"></span>
                        </button>
                    </h3>
                    <div class="accordion-panel" <?= $hasItems ? '' : 'hidden' ?>>
                        <?php if ($hasItems): ?>
                        <div class="table-scroll">
                        <table>
                            <thead>
                            <tr>
                                <th>Document</th>
                                <th>Fichier</th>
                                <th>Variables</th>
                                <th>Taille</th>
                                <th>Modifie</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $tpl): ?>
                                <tr>
                                    <td><?= e($docTypes[$tpl['doc_type']] ?? $tpl['doc_type']) ?></td>
                                    <td><?= e(basename($tpl['path'])) ?></td>
                                    <td><?= e((string) count($tpl['variables'])) ?> vars</td>
                                    <td><?= e(number_format($tpl['size'] / 1024, 1)) ?> KB</td>
                                    <td><?= e(date('d/m/Y H:i', $tpl['modified'])) ?></td>
                                    <td class="table-actions">
                                        <a class="btn-icon" href="<?= e(app_url('template', ['path' => $tpl['path']])) ?>" title="Voir"><span class="mdi mdi-eye"></span></a>
                                        <form method="post">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="path" value="<?= e($tpl['path']) ?>">
                                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce template ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                            <p class="table-empty" style="margin:0">Aucun template dans ce dossier.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="table-empty">Aucun dossier de templates trouve. Creez un dossier dans <code>templates/</code> ou utilisez la page Configuration.</p>
        <?php endif; ?>
    </article>
</section>



<style>
.hidden { display: none !important; }

.accordion-item {
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
}

.accordion-header {
    margin: 0;
}

.accordion-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    cursor: pointer;
    font: inherit;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-secondary);
    transition: background 0.15s;
}

.accordion-trigger:hover {
    filter: brightness(1.2);
}

.accordion-trigger[aria-expanded="true"] {
    background: rgba(0, 144, 231, 0.07);
}

.accordion-trigger[aria-expanded="false"] {
    background: rgba(255, 107, 53, 0.06);
}

.accordion-label {
    flex: 1;
    text-align: left;
}

.accordion-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 11px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: none;
    letter-spacing: normal;
    color: #fff;
}

.accordion-item:has(.accordion-trigger[aria-expanded="true"]) .accordion-count {
    background: var(--primary);
}

.accordion-item:has(.accordion-trigger[aria-expanded="false"]) .accordion-count {
    background: #ff6b35;
}

.accordion-chevron {
    font-size: 1.2rem;
    transition: transform 0.2s;
    color: var(--text-muted);
}

.accordion-trigger[aria-expanded="true"] .accordion-chevron {
    transform: rotate(0deg);
}

.accordion-trigger[aria-expanded="false"] .accordion-chevron {
    transform: rotate(-90deg);
}

.accordion-panel {
    border-top: 1px solid var(--border);
    padding: 16px;
}

.accordion-panel[hidden] {
    display: none;
}
</style>
<script>
document.querySelectorAll('[id$="-form"]').forEach(function(el) {
    el.classList.add('hidden');
});

function toggleAccordion(btn) {
    var expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', String(!expanded));
    var panel = btn.closest('.accordion-item').querySelector('.accordion-panel');
    if (panel) {
        panel.hidden = expanded;
    }
}
</script>
