<?php

declare(strict_types=1);

/**
 * Section d'import Excel réutilisable.
 * 
 * Variables attendues avant include :
 *   $importTable    — nom de la table DB
 *   $importPage     — page de redirection
 *   $importLabel    — libellé (ex: "societes")
 *   $importColumnMap — [en_tete_excel => colonne_db]
 *   $importDefaults  — [colonne_db => valeur] valeurs par défaut optionnelles
 *   $importExtra     — callback optionnel (array $mapped, PDO $pdo): void
 */

if (!isset($importTable, $importPage, $importLabel, $importColumnMap)) {
    return;
}

$importDefaults = $importDefaults ?? [];
$importExtra = $importExtra ?? null;
$importPreview = $_SESSION['_import_preview'] ?? null;
$importFile = $_SESSION['_import_file'] ?? null;

// Handle import actions
if (is_post() && ($pdo ?? null) instanceof PDO) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_import') {
        verify_csrf();

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Veuillez selectionner un fichier Excel valide.');
            redirect_to($importPage);
        }

        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            set_flash('error', 'Format de fichier non supporte. Utilisez .xlsx ou .xls.');
            redirect_to($importPage);
        }

        // Save to temp
        $tmpDir = __DIR__ . '/../uploads/tmp/import/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        $tmpFile = $tmpDir . uniqid('import_', true) . '.' . $ext;
        move_uploaded_file($_FILES['import_file']['tmp_name'], $tmpFile);

        // Parse preview
        $preview = import_excel_preview($tmpFile);
        if (isset($preview['error'])) {
            unlink($tmpFile);
            set_flash('error', $preview['error']);
            redirect_to($importPage);
        }

        // Validate expected headers
        $expectedHeaders = array_keys($importColumnMap);
        $missingHeaders = array_diff($expectedHeaders, $preview['headers']);
        if ($missingHeaders !== []) {
            unlink($tmpFile);
            set_flash('error', 'Colonnes manquantes dans le fichier : ' . implode(', ', $missingHeaders));
            redirect_to($importPage);
        }

        $_SESSION['_import_preview'] = $preview;
        $_SESSION['_import_file'] = $tmpFile;
        set_flash('success', 'Fichier analyse. ' . count($preview['rows']) . ' ligne(s) trouvee(s). Verifiez l\'apercu avant de confirmer.');
        redirect_to($importPage);
    }

    if ($action === 'confirm_import') {
        verify_csrf();

        $file = $_SESSION['_import_file'] ?? null;
        if (!$file || !file_exists($file)) {
            set_flash('error', 'Fichier d\'import introuvable. Veuillez re-telecharger le fichier.');
            unset($_SESSION['_import_preview'], $_SESSION['_import_file']);
            redirect_to($importPage);
        }

        $result = import_excel_confirm($file, $importColumnMap, function (array $mapped, int $idx) use ($pdo, $importTable, $importDefaults, $importExtra) {
            $data = array_merge($importDefaults, $mapped);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $stmt = $pdo->prepare("INSERT INTO {$importTable} ({$columns}) VALUES ({$placeholders})");

            $params = [];
            foreach ($data as $key => $value) {
                $params[$key] = $value !== '' ? $value : null;
            }
            $stmt->execute($params);

            if ($importExtra !== null) {
                $importExtra($mapped, $pdo);
            }
        });

        // Nettoyage
        @unlink($file);
        unset($_SESSION['_import_preview'], $_SESSION['_import_file']);

        if (!empty($result['errors'])) {
            $msg = $result['imported'] . ' ligne(s) importee(s). Erreurs :<br>' . implode('<br>', array_slice($result['errors'], 0, 10));
            set_flash('error', $msg);
        } else {
            log_activity($pdo, 'import', $importLabel, 0);
            set_flash('success', $result['imported'] . ' ' . $importLabel . ' importee(s) avec succes.');
        }

        redirect_to($importPage);
    }
}

?>
<button class="btn btn-secondary" type="button" data-toggle-import>
    <span class="material-symbols-outlined">upload_file</span> Importer Excel
</button>

<div class="import-section"<?= $importPreview ? '' : ' style="display:none"' ?>>
    <?php if ($importPreview && isset($importFile)): ?>
        <div class="card import-preview">
            <div class="section-header">
                <h3>Apercu de l'import <small style="font-weight:400;color:var(--text-secondary)">(<?= count($importPreview['rows']) ?> ligne(s))</small></h3>
            </div>
            <div class="table-scroll">
                <table data-sortable>
                    <thead>
                        <tr>
                            <?php foreach ($importPreview['headers'] as $h): ?>
                                <th data-col="<?= e($h) ?>"><?= e($h) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($importPreview['rows'], 0, 15) as $row): ?>
                            <tr>
                                <?php foreach ($importPreview['headers'] as $h): ?>
                                    <td><?= e($row[$h] ?? '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($importPreview['rows']) > 15): ?>
                <p class="help-text" style="margin-top:0.5rem;">
                    ... et <?= count($importPreview['rows']) - 15 ?> ligne(s) supplementaire(s)
                </p>
            <?php endif; ?>
            <form method="post" style="margin-top:1rem" data-confirm="Confirmer l'import de <?= count($importPreview['rows']) ?> ligne(s) ?">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="confirm_import">
                <button type="submit" class="btn btn-next">
                    <span class="material-symbols-outlined">check</span> Confirmer l'import
                </button>
                <a href="<?= e(app_url($importPage)) ?>" class="btn btn-cancel">
                    <span class="material-symbols-outlined">close</span> Annuler
                </a>
            </form>
        </div>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data" class="import-form">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="upload_import">
            <div class="inline-form" style="gap:8px">
                <input type="file" name="import_file" accept=".xlsx,.xls" required style="font-size:0.85rem">
                <button type="submit" class="btn btn-info">
                    <span class="material-symbols-outlined">preview</span> Previsualiser
                </button>
            </div>
            <p class="help-text" style="margin-top:4px">
                Format : .xlsx. La premiere ligne doit contenir les en-tetes de colonnes.
            </p>
        </form>
    <?php endif; ?>
</div>
