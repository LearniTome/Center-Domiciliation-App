<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesConfig = require __DIR__ . '/../config/templates.php';
$templatesDir = __DIR__ . '/../templates';

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

<style>
.hidden { display: none !important; }
</style>
<script>
document.querySelectorAll('[id$="-form"]').forEach(function(el) {
    el.classList.add('hidden');
});
</script>
