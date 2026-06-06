<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DocumentRenderer.php';

$q = search_term();
$filterSociete = int_value($_GET, 'societe_id');
$filterDocType = field_value($_GET, 'doc_type');
$filterStatut = field_value($_GET, 'statut');
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
        log_activity($pdo, 'delete', 'document', null, count($selected) . ' doc(s)');
        $delParams = $filterSociete ? ['societe_id' => $filterSociete] : [];
        if ($filterDocType) $delParams['doc_type'] = $filterDocType;
        if ($filterStatut) $delParams['statut'] = $filterStatut;
        redirect_to('documents', $delParams);
    }
}

if (is_post() && isset($_POST['validate_submit'])) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) === 0 || !($pdo ?? null) instanceof PDO) {
        set_flash('error', 'Selectionnez au moins un document.');
        $backParams = isset($_GET['societe_id']) ? ['societe_id' => (int) $_GET['societe_id']] : [];
        if ($filterDocType) $backParams['doc_type'] = $filterDocType;
        redirect_to('documents', $backParams);
    }
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $stmt = $pdo->prepare("SELECT id, societe_id, fichier_docx, fichier_pdf, doc_type FROM documents_generes WHERE valide = 0 AND id IN ($placeholders)");
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
        } elseif (!file_exists($newDocx)) {
            $newDocx = $oldDocx;
        }

        $pdfPath = $doc['fichier_pdf'];
        if (file_exists($newDocx)) {
            $pdfName = pathinfo($newDocx, PATHINFO_FILENAME) . '.pdf';
            $renderer = new DocumentRenderer('', dirname($newDocx));
            try {
                $generatedPdf = $renderer->tryConvertToPdf($newDocx, $pdfName);
                if ($generatedPdf && file_exists($generatedPdf)) {
                    $pdfPath = $generatedPdf;
                }
            } catch (\Throwable $e) {
            }
        }

        $updateStmt->execute([
            'fichier_docx' => $newDocx,
            'fichier_pdf' => $pdfPath,
            'id' => $doc['id'],
        ]);
    }
    $cleanTypes = array_unique(array_map(fn($d) => $d['doc_type'] ?? '', $docs));
    $cleanTypes = array_values(array_filter($cleanTypes, fn($v) => $v !== ''));
    if (!empty($cleanTypes)) {
        $typePlaceholders = implode(',', array_fill(0, count($cleanTypes), '?'));
        $delStmt = $pdo->prepare("DELETE FROM documents_generes WHERE id NOT IN ($placeholders) AND societe_id = ? AND valide = 0 AND doc_type IN ($typePlaceholders)");
        $societeIds = array_unique(array_map(fn($d) => (int) ($d['societe_id'] ?? 0), $docs));
        $sid = count($societeIds) === 1 ? $societeIds[0] : 0;
        if ($sid > 0) {
            $delStmt->execute(array_merge(array_map('intval', $selected), [$sid], $cleanTypes));
        }
    }
    set_flash('success', count($selected) . ' document(s) valide(s).');
    log_activity($pdo, 'validate', 'document', null, count($selected) . ' doc(s)');
    $valParams = $filterSociete ? ['societe_id' => $filterSociete] : [];
    if ($filterDocType) $valParams['doc_type'] = $filterDocType;
    if ($filterStatut) $valParams['statut'] = $filterStatut;
    redirect_to('documents', $valParams);
}

if (is_post() && isset($_POST['generate_pdf_submit'])) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) > 0 && ($pdo ?? null) instanceof PDO) {
        $docId = (int) $selected[0];
        $stmt = $pdo->prepare("SELECT * FROM documents_generes WHERE id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch();
        if ($doc && file_exists($doc['fichier_docx'])) {
            $docxPath = $doc['fichier_docx'];
            $pdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
            $renderer = new DocumentRenderer('', dirname($docxPath));
            try {
                $pdfPath = $renderer->tryConvertToPdf($docxPath, $pdfName);
                if ($pdfPath && file_exists($pdfPath)) {
                    $updateStmt = $pdo->prepare("UPDATE documents_generes SET fichier_pdf = :pdf WHERE id = :id");
                    $updateStmt->execute(['pdf' => $pdfPath, 'id' => $docId]);
                    if (isset($_SESSION['gen_files'][(int) $doc['societe_id']])) {
                        foreach ($_SESSION['gen_files'][(int) $doc['societe_id']] as &$sf) {
                            if (isset($sf['docx']) && $sf['docx'] === $doc['fichier_docx']) {
                                $sf['pdf'] = $pdfPath;
                            }
                        }
                        unset($sf);
                    }
                    set_flash('success', 'PDF genere avec succes.');
                } else {
                    set_flash('error', 'Impossible de generer le PDF.');
                }
            } catch (\Throwable $e) {
                set_flash('error', 'Erreur PDF : ' . $e->getMessage());
            }
        }
    }
    $backParams = $filterSociete ? ['societe_id' => $filterSociete] : [];
    if ($filterDocType) $backParams['doc_type'] = $filterDocType;
    if ($filterStatut) $backParams['statut'] = $filterStatut;
    redirect_to('documents', $backParams);
}

$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$userId = (!$isAdmin && $user) ? (int) $user['id'] : null;

$societesOptions = fetch_societes_options($pdo ?? null, $userId);
$docTypes = fetch_all_doc_types($pdo ?? null);
$allDocuments = fetch_all_documents($pdo ?? null, $filterSociete, $q, $filterDocType, $userId);
$documents = $allDocuments;
if ($filterStatut === 'valide') {
    $documents = array_values(array_filter($allDocuments, fn($d) => (int) $d['valide'] === 1));
} elseif ($filterStatut === 'brouillon') {
    $documents = array_values(array_filter($allDocuments, fn($d) => (int) $d['valide'] === 0));
}

if ($exportCsv && count($documents) > 0) {
    $headers = ['ID', 'Societe', 'Type', 'Fichier DOCX', 'Fichier PDF', 'Taille (Ko)', 'Statut', 'Date generation'];
    $rows = [];
    foreach ($documents as $d) {
        $rows[] = [
            $d['id'],
            $d['societe_raison_sociale'],
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
        <span class="page-count"><?= count($documents) ?> document(s)</span>
        <div class="table-actions">
            <a class="btn <?= $filterStatut === '' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('documents', array_filter(['societe_id' => $filterSociete, 'doc_type' => $filterDocType, 'q' => $q], fn($v) => $v !== null && $v !== ''))) ?>">Tous</a>
            <a class="btn <?= $filterStatut === 'valide' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('documents', array_filter(['societe_id' => $filterSociete, 'doc_type' => $filterDocType, 'q' => $q, 'statut' => 'valide'], fn($v) => $v !== null && $v !== ''))) ?>">Valides</a>
            <a class="btn <?= $filterStatut === 'brouillon' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('documents', array_filter(['societe_id' => $filterSociete, 'doc_type' => $filterDocType, 'q' => $q, 'statut' => 'brouillon'], fn($v) => $v !== null && $v !== ''))) ?>">Brouillons</a>
            <a class="btn btn-info" href="<?= e(app_url('documents', array_filter(['export' => 'csv', 'societe_id' => $filterSociete, 'doc_type' => $filterDocType], fn($v) => $v !== null && $v !== ''))) ?>">
                <span class="material-symbols-outlined">download</span> Exporter CSV
            </a>
        </div>
    </div>

    <form method="get" class="inline-form" id="documents-filter-form">
        <input type="hidden" name="page" value="documents">
        <input type="hidden" name="statut" value="<?= e($filterStatut) ?>">
        <input type="search" name="q" placeholder="Rechercher..." value="<?= e($q) ?>">
        <select name="societe_id" onchange="this.form.submit()">
            <option value="">Toutes les societes</option>
            <?php foreach ($societesOptions as $s): ?>
                <option value="<?= e((string) $s['id']) ?>" <?= $filterSociete === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['societe_raison_sociale']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="doc_type" onchange="this.form.submit()">
            <option value="">Tous les types</option>
            <?php foreach ($docTypes as $dt): ?>
                <option value="<?= e($dt) ?>" <?= $filterDocType === $dt ? 'selected' : '' ?>>
                    <?= e($dt) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($q !== '' || $filterSociete !== null || $filterDocType !== '' || $filterStatut !== ''): ?>
            <a class="btn btn-cancel" href="<?= e(app_url('documents')) ?>"><span class="material-symbols-outlined">close</span> Reinitialiser</a>
        <?php endif; ?>
    </form>

    <?php if (count($documents) > 0): ?>
        <form method="post" id="documents-form">
            <?= csrf_input() ?>
            <div class="table-scroll">
                <table data-sortable class="table-nowrap">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="select-all"></th>
                            <th data-col="societe">Societe</th>
                            <th data-col="type">Type</th>
                            <th data-col="document">Document</th>
                            <th data-col="taille">Taille</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="date-creation">Date creation</th>
                            <th data-col="modification">Modification</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <?php $modifTime = file_exists($doc['fichier_docx']) ? filemtime($doc['fichier_docx']) : null; ?>
                            <tr>
                                <td><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>"></td>
                                <td>
                                    <a href="<?= e(app_url('societe', ['id' => (int) $doc['societe_id']])) ?>">
                                        <?= e($doc['societe_raison_sociale']) ?>
                                    </a>
                                </td>
                                <td><?= e($doc['doc_type'] ?? '-') ?></td>
                                <td><span class="help-text"><?= e(basename($doc['fichier_docx'])) ?></span></td>
                                <td><?= $doc['taille_ko'] ? number_format((float) $doc['taille_ko'], 1) . ' Ko' : '-' ?></td>
                                <td>
                                    <span class="statut-badge <?= $doc['valide'] ? 'valide' : 'brouillon' ?>">
                                        <?= $doc['valide'] ? 'Valide' : 'Brouillon' ?>
                                    </span>
                                </td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $doc['created_at']))) ?></td>
                                <td><span class="help-text"><?= $modifTime ? date('d/m/Y H:i', $modifTime) : '-' ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn-icon" href="<?= e(word_url($doc['fichier_docx'])) ?>" title="Ouvrir dans Word">
                                            <span class="material-symbols-outlined">article</span>
                                        </a>
                                        <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="material-symbols-outlined">download</span>
                                        </a>
                                        <?php if ($doc['valide']): ?>
                                            <?php if ($doc['fichier_pdf']): ?>
                                                <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                                </a>
                                            <?php else: ?>
                                                <a class="btn-icon" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('documents-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='generate_pdf_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Generation PDF en cours...'); f.submit();} })();" title="Generer PDF">
                                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (!$doc['valide']): ?>
                                            <a class="btn-icon" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('documents-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='validate_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Validation en cours...'); f.submit();} })();" title="Valider">
                                                <span class="material-symbols-outlined">task_alt</span>
                                            </a>
                                        <?php endif; ?>
                                        <a class="btn-icon danger" href="#" onclick="event.preventDefault(); if(!confirm('Supprimer ce document ?')) return; (function(){ var f=document.getElementById('documents-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='delete_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Suppression en cours...'); f.submit();} })();" title="Supprimer">
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
                <button class="btn btn-next" type="submit" name="validate_submit" value="1">
                    <span class="material-symbols-outlined">task_alt</span> Valider la selection
                </button>
                <button class="btn btn-back" type="submit" name="delete_submit" value="1">
                    <span class="material-symbols-outlined">delete</span> Supprimer la selection
                </button>
            </div>
            <script>
            document.getElementById('select-all')?.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('#documents-form input[name="selected_files[]"]');
                checkboxes.forEach(c => c.checked = this.checked);
            });
            </script>
        </form>
    <?php else: ?>
        <div class="empty-state">
            <span class="material-symbols-outlined">description</span>
            <p class="table-empty">Aucun document genere.</p>
            <a class="btn" href="<?= e(app_url('generation')) ?>">Generer des documents</a>
        </div>
    <?php endif; ?>
</section>


<div id="loading-overlay">
    <div class="loader-card">
        <div class="spinner"></div>
        <p id="loading-text">Traitement en cours...</p>
    </div>
</div>
<script>
(function(){
    var overlay = document.getElementById('loading-overlay');
    var text = document.getElementById('loading-text');
    window.showOverlay = function(msg){
        text.textContent = msg;
        overlay.classList.add('show');
    };
    document.getElementById('documents-form')?.addEventListener('submit', function(e){
        var btn = e.submitter;
        if(btn && btn.name === 'delete_submit'){
            window.showOverlay('Suppression en cours...');
        } else if(btn && btn.name === 'generate_pdf_submit'){
            window.showOverlay('Generation PDF en cours...');
        } else {
            window.showOverlay('Validation en cours...');
        }
    });
})();
</script>
