<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesConfig = require __DIR__ . '/../config/templates.php';
$templatesDir = __DIR__ . '/../templates';
$outputDir = __DIR__ . '/../output';

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
}

$templates = TemplateAnalyzer::scanTemplates($templatesDir);
$grouped = TemplateAnalyzer::groupByFolder($templates);
$legalForms = $templatesConfig['legal_forms'];
$docTypes = $templatesConfig['document_types'];

$showAnalysis = isset($_GET['analysis']);
$analysis = null;
if ($showAnalysis && $templates) {
    $analysis = TemplateAnalyzer::analyzeCoverage($templates);

    if (is_post() && isset($_POST['export_csv'])) {
        verify_csrf();
        $csvPath = $outputDir . DIRECTORY_SEPARATOR . 'analyse_templates_' . date('Y-m-d_His') . '.csv';
        TemplateAnalyzer::exportAnalysisCsv($analysis['variables'], $csvPath);
        set_flash('success', 'Analyse exportee dans output/');
        redirect_to('templates', ['analysis' => '1']);
    }
}
?>
<section>
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2>Templates de documents</h2>
                <p class="help-text"><?= count($templates) ?> template(s) trouve(s)</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-next" href="#" onclick="document.getElementById('upload-form').classList.toggle('hidden'); return false;"><span class="mdi mdi-plus"></span> Ajouter un template</a>
                <a class="btn btn-next" href="#" onclick="document.getElementById('folder-form').classList.toggle('hidden'); return false;"><span class="mdi mdi-folder-plus"></span> Nouveau dossier</a>
            </div>
        </div>

        <div id="upload-form" class="stack hidden">
            <form method="post" enctype="multipart/form-data" class="inline-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="upload">
                <input type="file" name="template_file" accept=".docx" required>
                <select name="folder">
                    <?php foreach ($legalForms as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Uploader</button>
            </form>
        </div>

        <div id="folder-form" class="stack hidden">
            <form method="post" class="inline-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_folder">
                <input type="text" name="folder_name" placeholder="Nom du dossier (ex: SARL)" required>
                <button type="submit" class="btn">Creer</button>
            </form>
        </div>

        <?php if (!$templates): ?>
            <p class="table-empty">Aucun template trouve. Ajoutez des fichiers .docx.</p>
        <?php else: ?>
            <?php foreach ($grouped as $folder => $items): ?>
                <h3 style="color:var(--text-secondary);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.04em;margin:1rem 0 0.5rem">
                    <?= e($legalForms[$folder] ?? $folder) ?>
                    <span style="font-weight:400">(<?= count($items) ?>)</span>
                </h3>
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
            <?php endforeach; ?>
        <?php endif; ?>
    </article>
</section>

<?php if ($showAnalysis && $analysis): ?>
<section class="card stack" style="margin-top:1rem">
    <div class="section-header">
        <div>
            <h2>Analyse de couverture des variables</h2>
            <p class="help-text">Variables trouvees dans les templates vs. variables disponibles dans le contexte de rendu.</p>
        </div>
        <div class="table-actions">
            <form method="post" style="display:inline">
                <?= csrf_input() ?>
                <button type="submit" name="export_csv" value="1" class="btn btn-info"><span class="mdi mdi-download"></span> Export CSV</button>
            </form>
            <a class="btn btn-back" href="<?= e(app_url('templates')) ?>"><span class="mdi mdi-close"></span> Fermer</a>
        </div>
    </div>

    <div class="stats compact">
        <article class="stat">
            <span>Templates</span>
            <strong><?= $analysis['summary']['total_templates'] ?></strong>
        </article>
        <article class="stat">
            <span>Variables distinctes</span>
            <strong><?= $analysis['summary']['total_variables'] ?></strong>
        </article>
        <article class="stat">
            <span>Couvertes</span>
            <strong style="color:var(--success)"><?= $analysis['summary']['covered_variables'] ?></strong>
        </article>
        <article class="stat">
            <span>Non couvertes</span>
            <strong style="color:var(--danger)"><?= $analysis['summary']['uncovered_variables'] ?></strong>
        </article>
    </div>

    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Variable</th>
                <th>Occurrences</th>
                <th>Templates</th>
                <th>Section</th>
                <th>Couverture</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($analysis['variables'] as $v): ?>
                <tr>
                    <td><code><?= e($v['variable']) ?></code></td>
                    <td><?= e((string) $v['occurrences']) ?></td>
                    <td><?= e((string) $v['templates_count']) ?> template(s)</td>
                    <td><span class="pill"><?= e($v['section']) ?></span></td>
                    <td>
                        <span class="statut-badge <?= $v['coverage'] === 'couvert' ? 'actif' : 'resilie' ?>">
                            <?= e($v['coverage']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php elseif ($templates): ?>
<section style="margin-top:0.5rem">
    <a class="btn btn-info" href="<?= e(app_url('templates', ['analysis' => '1'])) ?>">
        <span class="mdi mdi-chart-box-outline"></span> Analyser la couverture
    </a>
</section>
<?php endif; ?>

<style>
.hidden { display: none !important; }
</style>
<script>
document.querySelectorAll('[id$="-form"]').forEach(function(el) {
    el.classList.add('hidden');
});
</script>
