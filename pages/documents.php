<?php

declare(strict_types=1);

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
        }
        $newPdf = $doc['fichier_pdf'];
        if ($newPdf !== null) {
            $renamedPdf = str_replace('_Brouillon.pdf', '.pdf', $newPdf);
            if ($newPdf !== $renamedPdf && file_exists($newPdf)) {
                rename($newPdf, $renamedPdf);
                $newPdf = $renamedPdf;
            }
        }
        $updateStmt->execute([
            'fichier_docx' => $newDocx,
            'fichier_pdf' => $newPdf,
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
    $valParams = $filterSociete ? ['societe_id' => $filterSociete] : [];
    if ($filterDocType) $valParams['doc_type'] = $filterDocType;
    if ($filterStatut) $valParams['statut'] = $filterStatut;
    redirect_to('documents', $valParams);
}

$societesOptions = fetch_societes_options($pdo ?? null);
$docTypes = fetch_all_doc_types($pdo ?? null);
$allDocuments = fetch_all_documents($pdo ?? null, $filterSociete, $q, $filterDocType);
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
        <div>
            <h2>Tous les documents generes</h2>
            <p class="help-text"><?= count($documents) ?> document(s)</p>
        </div>
        <div class="table-actions">
            <a class="btn <?= $filterStatut === '' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('documents', array_filter(['societe_id' => $filterSociete, 'doc_type' => $filterDocType, 'q' => $q], fn($v) => $v !== null && $v !== ''))) ?>">Tous</a>
            <a class="btn <?= $filterStatut === 'valide' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('documents', array_filter(['societe_id' => $filterSociete, 'doc_type' => $filterDocType, 'q' => $q, 'statut' => 'valide'], fn($v) => $v !== null && $v !== ''))) ?>">Valides</a>
            <a class="btn <?= $filterStatut === 'brouillon' ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('documents', array_filter(['societe_id' => $filterSociete, 'doc_type' => $filterDocType, 'q' => $q, 'statut' => 'brouillon'], fn($v) => $v !== null && $v !== ''))) ?>">Brouillons</a>
            <a class="btn btn-info" href="<?= e(app_url('documents', array_filter(['export' => 'csv', 'societe_id' => $filterSociete, 'doc_type' => $filterDocType], fn($v) => $v !== null && $v !== ''))) ?>">
                <span class="mdi mdi-download"></span> Exporter CSV
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
            <a class="btn btn-cancel" href="<?= e(app_url('documents')) ?>"><span class="mdi mdi-close"></span> Reinitialiser</a>
        <?php endif; ?>
    </form>

    <?php if (count($documents) > 0): ?>
        <form method="post" id="documents-form">
            <?= csrf_input() ?>
            <div class="table-scroll" style="overflow-x: auto">
                <table style="white-space: nowrap">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="select-all"></th>
                            <th>Societe</th>
                            <th>Type</th>
                            <th>Document</th>
                            <th>Taille</th>
                            <th>Statut</th>
                            <th>Date creation</th>
                            <th>Modification</th>
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
                                            <span class="mdi mdi-file-word"></span>
                                        </a>
                                        <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                            <span class="mdi mdi-download"></span>
                                        </a>
                                        <?php if ($doc['fichier_pdf']): ?>
                                            <a class="btn-icon" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                                <span class="mdi mdi-file-pdf"></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$doc['valide']): ?>
                                            <a class="btn-icon" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('documents-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='validate_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Validation en cours...'); f.submit();} })();" title="Valider">
                                                <span class="mdi mdi-file-check"></span>
                                            </a>
                                        <?php endif; ?>
                                        <a class="btn-icon danger" href="#" onclick="event.preventDefault(); if(!confirm('Supprimer ce document ?')) return; (function(){ var f=document.getElementById('documents-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='delete_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Suppression en cours...'); f.submit();} })();" title="Supprimer">
                                            <span class="mdi mdi-delete"></span>
                                        </a>
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

<style>
#loading-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;display:none;align-items:center;justify-content:center;flex-direction:column center}
#loading-overlay.show{display:flex}
#loading-overlay .loader-card{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius-lg);padding:2.5rem 3rem;display:flex;flex-direction:column;align-items:center;gap:1rem;box-shadow:0 8px 32px rgba(0,0,0,.5)}
#loading-overlay .spinner{width:40px;height:40px;border:3px solid var(--line);border-top-color:var(--primary);border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
#loading-overlay p{font-size:1rem;color:var(--text-secondary);margin:0}
</style>
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
        } else {
            window.showOverlay('Validation en cours...');
        }
    });
})();
</script>
