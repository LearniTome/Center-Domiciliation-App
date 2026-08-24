<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/analyseur_templates.php';

$templatesConfig = require __DIR__ . '/../../config/templates.php';
$templatesDir = __DIR__ . '/../../templates';

$folderLabels = $templatesConfig['folder_labels'];
$docTypes = $templatesConfig['document_types'];
$templateSections = $templatesConfig['template_sections'] ?? [];

// Formes juridiques depuis la BD -> rattachées automatiquement a la section 'creation'
$dbFormeFolders = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query("SELECT forme_juridique, template_folder FROM ref_formes_juridiques ORDER BY id");
    foreach ($stmt->fetchAll() as $form) {
        $name = (string) $form['forme_juridique'];
        $tf = (string) ($form['template_folder'] ?? '');
        $key = $tf !== '' ? $tf : $name;
        $dbFormeFolders[] = $key;
        if (!isset($folderLabels[$key])) {
            $folderLabels[$key] = $name;
        }
    }
}

// Dossiers physiques presents sur le disque
$physicalFolders = [];
if (is_dir($templatesDir)) {
    $items = scandir($templatesDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($templatesDir . DIRECTORY_SEPARATOR . $item)) {
            $physicalFolders[] = $item;
        }
    }
    sort($physicalFolders);
}

// Union ordonnee de tous les dossiers affichables (selects upload/copie/backup)
$assigned = [];
foreach ($templateSections as $sec) {
    foreach (($sec['folders'] ?? []) as $k) {
        if (!in_array($k, $assigned, true)) {
            $assigned[] = $k;
        }
    }
}
foreach ($dbFormeFolders as $k) {
    if (!in_array($k, $assigned, true)) {
        $assigned[] = $k;
    }
}
foreach ($physicalFolders as $k) {
    if (!in_array($k, $assigned, true)) {
        $assigned[] = $k;
    }
}
$displayFolders = $assigned;

// Ensure folder exists on disk for display (create if missing)
foreach ($displayFolders as $folder) {
    $path = $templatesDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
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
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $dateStr = date('Ymd_His');
        $zipName = $folder === '_ALL_' ? "tous_templates_$dateStr.zip" : "templates_{$folder}_$dateStr.zip";
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
                set_flash('success', "Sauvegarde creee : $zipName ($added template(s)).");
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

// Assemblage des sections : dossiers explicites + formes juridiques (creation) + regex + restants
$usedKeys = [];
$sectionsRender = [];
foreach ($templateSections as $sec) {
    $keys = [];
    $push = function (string $k) use (&$keys, &$usedKeys): void {
        if (!in_array($k, $usedKeys, true)) {
            $keys[] = $k;
            $usedKeys[] = $k;
        }
    };
    foreach (($sec['folders'] ?? []) as $k) {
        $push((string) $k);
    }
    if (($sec['key'] ?? '') === 'creation') {
        foreach ($dbFormeFolders as $k) {
            $push($k);
        }
    }
    foreach ($physicalFolders as $k) {
        if (in_array($k, $usedKeys, true)) continue;
        foreach (($sec['match'] ?? []) as $pat) {
            if (@preg_match($pat, $k)) {
                $push($k);
                break;
            }
        }
    }
    // Dossiers non vides d'abord
    usort($keys, function ($a, $b) use ($grouped) {
        $ca = count($grouped[$a] ?? []);
        $cb = count($grouped[$b] ?? []);
        return $cb <=> $ca ?: strcasecmp($a, $b);
    });
    $sectionsRender[] = [
        'key' => (string) ($sec['key'] ?? 'section'),
        'label' => (string) ($sec['label'] ?? 'Section'),
        'description' => (string) ($sec['description'] ?? ''),
        'icon' => (string) ($sec['icon'] ?? 'folder'),
        'folders' => $keys,
    ];
}

// Dossiers physiques non classes -> section "Autres"
$leftovers = array_values(array_filter(
    $physicalFolders,
    fn(string $k): bool => !in_array($k, $usedKeys, true)
));
if ($leftovers !== []) {
    usort($leftovers, fn($a, $b) => count($grouped[$b] ?? []) <=> count($grouped[$a] ?? []) ?: strcasecmp($a, $b));
    $sectionsRender[] = [
        'key' => 'autres',
        'label' => 'Autres dossiers',
        'description' => 'Ressources complementaires (guides, references).',
        'icon' => 'folder_open',
        'folders' => $leftovers,
    ];
}

?>
<section>
    <article class="card stack">
        <div class="section-header">
            <span class="page-count"><?= count($templates) ?> template(s) trouve(s)</span>
            <div class="table-actions">
                <a class="btn btn-next" href="#" onclick="document.getElementById('folder-form').classList.toggle('hidden'); return false;"><span class="material-symbols-outlined">create_new_folder</span> Nouveau dossier</a>
                <a class="btn btn-next" href="#" onclick="document.getElementById('upload-form').classList.toggle('hidden'); return false;"><span class="material-symbols-outlined">add</span> Ajouter un template</a>
                <a class="btn btn-info" href="#" onclick="document.getElementById('copy-form').classList.toggle('hidden'); return false;"><span class="material-symbols-outlined">content_copy</span> Copier</a>
                <a class="btn" href="#" onclick="document.getElementById('backup-form').classList.toggle('hidden'); return false;"><span class="material-symbols-outlined">download</span> Backup</a>
                <a class="btn btn-info" href="#" onclick="expandAllTemplates(); return false;" title="Ouvrir tous les dossiers"><span class="material-symbols-outlined">unfold_more</span> Tout déplier</a>
                <a class="btn btn-cancel" href="#" onclick="collapseAllTemplates(); return false;" title="Replier tous les dossiers"><span class="material-symbols-outlined">unfold_less</span> Tout replier</a>
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
                    <?php foreach ($displayFolders as $folder): ?>
                        <?php $cnt = count($grouped[$folder] ?? []); ?>
                        <?php if ($cnt > 0): ?>
                            <option value="<?= e($folder) ?>"><?= e($folderLabels[$folder] ?? $folder) ?> (<?= $cnt ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <span class="material-symbols-outlined">arrow_forward</span>
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

    </article>
</section>

<?php if (!$sectionsRender): ?>
<section>
    <article class="card">
        <p class="table-empty">Aucun dossier de templates trouve. Creez un dossier dans <code>templates/</code> ou utilisez la page Configuration.</p>
    </article>
</section>
<?php endif; ?>

<?php foreach ($sectionsRender as $sec): ?>
    <?php
    $secCount = 0;
    foreach ($sec['folders'] as $f) {
        $secCount += count($grouped[$f] ?? []);
    }
    ?>
<section>
    <article class="card stack">
        <div class="section-title-row">
            <h2><span class="material-symbols-outlined"><?= e($sec['icon']) ?></span> <?= e($sec['label']) ?></h2>
            <div class="table-actions">
                <span class="page-count"><?= $secCount ?> template(s) &middot; <?= count($sec['folders']) ?> dossier(s)</span>
            </div>
        </div>
        <?php if ($sec['description'] !== ''): ?>
            <p class="section-description"><?= e($sec['description']) ?></p>
        <?php endif; ?>

        <?php foreach ($sec['folders'] as $folder): ?>
            <?php $items = $grouped[$folder] ?? []; ?>
            <?php $hasItems = (bool) $items; ?>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button type="button" class="accordion-trigger" aria-expanded="false" onclick="toggleAccordion(this)">
                        <span class="accordion-label"><?= e($folderLabels[$folder] ?? $folder) ?></span>
                        <span class="accordion-count"><?= count($items) ?></span>
                        <span class="material-symbols-outlined accordion-chevron">expand_more</span>
                    </button>
                </h3>
                <div class="accordion-panel" hidden>
                        <?php if ($hasItems): ?>
                        <div class="table-scroll">
                        <table data-sortable>
                            <thead>
                            <tr>
                                <th data-col="document">Document</th>
                                <th data-col="fichier">Fichier</th>
                                <th data-col="variables">Variables</th>
                                <th data-col="taille">Taille</th>
                                <th data-col="modifie">Modifie</th>
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
                                        <a class="btn-icon primary" href="<?= e(app_url('templates', ['action' => 'inspecteur', 'path' => $tpl['path']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                                        <?php if (has_permission('templates.edit')): ?>
                                        <a class="btn-icon primary" href="<?= e(app_url('templates', ['action' => 'editeur', 'path' => $tpl['path']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                        <?php endif; ?>
                                        <form method="post">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="path" value="<?= e($tpl['path']) ?>">
                                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce template ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                            <p class="table-empty accordion-table-empty">Aucun template dans ce dossier.</p>
                        <?php endif; ?>
                    </div>
                </div>
        <?php endforeach; ?>
    </article>
</section>
<?php endforeach; ?>



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

function setAllTemplatesPanels(expanded) {
    document.querySelectorAll('.accordion-item .accordion-trigger').forEach(function(btn) {
        btn.setAttribute('aria-expanded', String(expanded));
        var panel = btn.closest('.accordion-item').querySelector('.accordion-panel');
        if (panel) {
            panel.hidden = !expanded;
        }
    });
}

function expandAllTemplates() {
    setAllTemplatesPanels(true);
}

function collapseAllTemplates() {
    setAllTemplatesPanels(false);
}
</script>
