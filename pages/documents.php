<?php

declare(strict_types=1);

$q = search_term();
$filterSociete = int_value($_GET, 'societe_id');
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';

if (is_post() && isset($_POST['delete_submit'])) {
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
        set_flash('error', count($selected) . ' document(s) supprime(s).');
        redirect_to('documents', $filterSociete ? ['societe_id' => $filterSociete] : []);
    }
}

if (is_post() && isset($_POST['validate_submit'])) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) > 0 && ($pdo ?? null) instanceof PDO) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE valide = 0 AND id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        $docs = $stmt->fetchAll();
        foreach ($docs as $doc) {
            $oldDocx = $doc['fichier_docx'];
            $newDocx = str_replace('_Brouillon.docx', '.docx', $oldDocx);
            if (file_exists($oldDocx) && $oldDocx !== $newDocx) {
                rename($oldDocx, $newDocx);
            }
            if ($doc['fichier_pdf'] && file_exists($doc['fichier_pdf'])) {
                $oldPdf = $doc['fichier_pdf'];
                $newPdf = str_replace('_Brouillon.pdf', '.pdf', $oldPdf);
                if ($oldPdf !== $newPdf) {
                    rename($oldPdf, $newPdf);
                }
            }
        }
        $stmt = $pdo->prepare("UPDATE documents_generes SET valide = 1 WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        set_flash('success', count($selected) . ' document(s) valide(s).');
        redirect_to('documents', $filterSociete ? ['societe_id' => $filterSociete] : []);
    }
}

$societesOptions = fetch_societes_options($pdo ?? null);
$documents = fetch_all_documents($pdo ?? null, $filterSociete, $q);

if ($exportCsv && count($documents) > 0) {
    $headers = ['ID', 'Societe', 'Type', 'Fichier DOCX', 'Fichier PDF', 'Taille (Ko)', 'Statut', 'Date generation'];
    $rows = [];
    foreach ($documents as $d) {
        $rows[] = [
            $d['id'],
            $d['raison_sociale'],
            $d['doc_type'] ?? '-',
            basename($d['fichier_docx']),
            $d['fichier_pdf'] ? basename($d['fichier_pdf']) : '',
            $d['taille_ko'] ?? '',
            $d['valide'] ? 'Valide' : 'Brouillon',
            $d['created_at'],
        ];
    }
    export_csv('documents-generes_' . date('Y-m-d') . '.csv', $headers, $rows);
}
?>
<section class="card">
    <div class="section-header">
        <div>
            <h2>Tous les documents generes</h2>
            <p class="help-text"><?= count($documents) ?> document(s)</p>
        </div>
        <div class="table-actions">
            <a class="btn btn-secondary" href="<?= e(app_url('documents', array_merge(['export' => 'csv'], $filterSociete ? ['societe_id' => $filterSociete] : []))) ?>">
                <span class="mdi mdi-download"></span> Exporter CSV
            </a>
        </div>
    </div>

    <form method="get" class="inline-form">
        <input type="hidden" name="page" value="documents">
        <input type="search" name="q" placeholder="Rechercher..." value="<?= e($q) ?>">
        <select name="societe_id">
            <option value="">Toutes les societes</option>
            <?php foreach ($societesOptions as $s): ?>
                <option value="<?= e((string) $s['id']) ?>" <?= $filterSociete === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['raison_sociale']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filtrer</button>
        <?php if ($q !== '' || $filterSociete !== null): ?>
            <a class="btn btn-secondary" href="<?= e(app_url('documents')) ?>">Reinitialiser</a>
        <?php endif; ?>
    </form>

    <?php if (count($documents) > 0): ?>
        <form method="post" id="documents-form">
            <?= csrf_input() ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="select-all"></th>
                            <th>Societe</th>
                            <th>Type</th>
                            <th>Document</th>
                            <th>Taille</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>"></td>
                                <td>
                                    <a href="<?= e(app_url('societe', ['id' => (int) $doc['societe_id']])) ?>">
                                        <?= e($doc['raison_sociale']) ?>
                                    </a>
                                </td>
                                <td><?= e($doc['doc_type'] ?? '-') ?></td>
                                <td><?= e(basename($doc['fichier_docx'])) ?></td>
                                <td><?= $doc['taille_ko'] ? number_format((float) $doc['taille_ko'], 1) . ' Ko' : '-' ?></td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $doc['created_at']))) ?></td>
                                <td>
                                    <span class="statut-badge <?= $doc['valide'] ? 'valide' : 'brouillon' ?>">
                                        <?= $doc['valide'] ? 'Valide' : 'Brouillon' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="mdi mdi-file-word"></span>
                                        </a>
                                        <?php if ($doc['fichier_pdf']): ?>
                                            <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                <span class="mdi mdi-file-pdf"></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-actions" style="margin-top:12px">
                <button class="btn btn-next" type="submit" name="validate_submit" value="1">
                    <span class="mdi mdi-file-check"></span> Valider la selection
                </button>
                <button class="btn btn-back" type="submit" name="delete_submit" value="1">
                    <span class="mdi mdi-delete"></span> Supprimer la selection
                </button>
            </div>
        </form>
        <script>
        document.getElementById('select-all')?.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('#documents-form input[name="selected_files[]"]');
            checkboxes.forEach(c => c.checked = this.checked);
        });
        </script>
    <?php else: ?>
        <div class="empty-state">
            <span class="mdi mdi-file-document-outline" style="font-size:2rem;color:var(--text-secondary)"></span>
            <p class="table-empty">Aucun document genere.</p>
            <a class="btn" href="<?= e(app_url('generation')) ?>">Generer des documents</a>
        </div>
    <?php endif; ?>
</section>
