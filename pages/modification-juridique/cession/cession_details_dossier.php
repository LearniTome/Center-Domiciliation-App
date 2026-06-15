<?php

declare(strict_types=1);

$cessionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cession = null;
$societe = null;
$societeId = 0;
$cessionParts = [];
$documents = [];

if ($cessionId > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('
        SELECT c.*, s.societe_raison_sociale, s.societe_dossier AS ste_dossier,
               s.societe_forme_juridique, s.societe_ville, s.societe_capital, s.societe_part_social
        FROM cessions c
        LEFT JOIN societes s ON s.id = c.societe_id
        WHERE c.id = :id
    ');
    $stmt->execute(['id' => $cessionId]);
    $cession = $stmt->fetch();

    if ($cession) {
        $stmt = $pdo->prepare('SELECT * FROM cession_parts WHERE cession_id = :id ORDER BY id');
        $stmt->execute(['id' => $cessionId]);
        $cessionParts = $stmt->fetchAll();

        $societeId = (int) ($cession['societe_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, doc_type, fichier_docx, fichier_pdf, taille_ko, valide, created_at, template_source FROM documents_generes WHERE societe_id = :sid ORDER BY created_at DESC");
        $stmt->execute(['sid' => $societeId]);
        $documents = $stmt->fetchAll();
    }
}

if (!$cession) {
    http_response_code(404);
    ?>
    <section class="card stack">
        <h2>Cession introuvable</h2>
        <p>Le dossier de cession demande n'existe pas ou n'est plus disponible.</p>
        <a class="btn" href="<?= e(app_url('cessions')) ?>">Retour aux cessions</a>
    </section>
    <?php
    return;
}

if (is_post() && isset($_POST['validate_submit']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) === 0) {
        set_flash('error', 'Selectionnez au moins un document.');
        redirect_to('cession_dossier', ['id' => $cessionId]);
    }
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $stmt = $pdo->prepare("UPDATE documents_generes SET valide = 1 WHERE societe_id = ? AND id IN ($placeholders)");
    $stmt->execute(array_merge([$societeId], array_map('intval', $selected)));
    set_flash('success', count($selected) . ' document(s) valide(s).');
    redirect_to('cession_dossier', ['id' => $cessionId]);
}

if (is_post() && isset($_POST['delete_submit']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) > 0) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE societe_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$societeId], array_map('intval', $selected)));
        $docs = $stmt->fetchAll();
        foreach ($docs as $doc) {
            if (file_exists($doc['fichier_docx'])) unlink($doc['fichier_docx']);
            if ($doc['fichier_pdf'] && file_exists($doc['fichier_pdf'])) unlink($doc['fichier_pdf']);
        }
        $stmt = $pdo->prepare("DELETE FROM documents_generes WHERE id IN ($placeholders)");
        $stmt->execute(array_map('intval', $selected));
        set_flash('error', count($selected) . ' document(s) supprime(s).');
        redirect_to('cession_dossier', ['id' => $cessionId]);
    }
}

if (is_post() && isset($_POST['restore_submit']) && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $selected = $_POST['selected_files'] ?? [];
    if (count($selected) === 0) {
        set_flash('error', 'Selectionnez au moins un document.');
        redirect_to('cession_dossier', ['id' => $cessionId]);
    }
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $stmt = $pdo->prepare("UPDATE documents_generes SET valide = 0 WHERE societe_id = ? AND id IN ($placeholders)");
    $stmt->execute(array_merge([$societeId], array_map('intval', $selected)));
    set_flash('success', count($selected) . ' document(s) restaure(s) en brouillon.');
    redirect_to('cession_dossier', ['id' => $cessionId]);
}

$docTypeLabels = [
    'Acte-Cession-Parts' => "Acte de cession de parts",
    'PV-AGE-Cession' => "PV d'assemblee generale cession",
    'Declaration-Modificative-RC' => "Declaration modificative RC",
    'Annonce-Legale-Cession' => "Annonce legale cession",
];

$sourceLabels = [
    'cession' => 'Cession',
];
?>
<div class="section-title-row">
    <h2><?= e($cession['cession_dossier']) ?> — <?= e($cession['societe_raison_sociale'] ?? '-') ?></h2>
    <div class="table-actions">
        <a class="btn btn-info" href="<?= e(app_url('cession', ['id' => $cessionId, 'edit' => '1'])) ?>"><span class="material-symbols-outlined">edit</span> Modifier</a>
        <a class="btn btn-next" href="<?= e(app_url('cession', ['step' => 6, 'id' => $cessionId, 'edit' => '1'])) ?>"><span class="material-symbols-outlined">sync</span> <?= count($documents) > 0 ? 'Regenerer documents' : 'Generer documents' ?></a>
        <a class="btn btn-back" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
    </div>
</div>

<section class="stats small stats-bottom-margin">
    <article class="stat">
        <span>Societe</span>
        <strong><?= e($cession['societe_raison_sociale'] ?? '-') ?></strong>
    </article>
    <article class="stat">
        <span>Statut</span>
        <strong><?= ($cession['cession_status'] ?? 'brouillon') === 'finalise' ? 'Finalise' : 'Brouillon' ?></strong>
    </article>
    <article class="stat">
        <span>Date</span>
        <strong><?= format_date($cession['cession_date'] ?? null) ?></strong>
    </article>
    <article class="stat">
        <span>Lignes</span>
        <strong><?= count($cessionParts) ?></strong>
    </article>
    <article class="stat">
        <span>Total parts</span>
        <strong><?= array_sum(array_map(fn($p) => (int) ($p['parts_cedees'] ?? 0), $cessionParts)) ?></strong>
    </article>
</section>

<article class="card stack">
    <div class="form-grid">
        <h3 class="section-title">Informations generales</h3>
        <div class="info-grid">
            <div><span>Dossier</span><strong><?= e($cession['cession_dossier'] ?? '-') ?></strong></div>
            <div><span>Societe</span><strong><?= e($cession['societe_raison_sociale'] ?? '-') ?></strong></div>
            <div><span>Forme juridique</span><strong><?= e($cession['societe_forme_juridique'] ?? '-') ?></strong></div>
            <div><span>Ville</span><strong><?= e($cession['societe_ville'] ?? '-') ?></strong></div>
            <div><span>Capital</span><strong><?= format_money($cession['societe_capital'] !== null ? (float) $cession['societe_capital'] : null) ?></strong></div>
            <div><span>Parts avant cession</span><strong><?= $cession['parts_avant'] ?? '-' ?></strong></div>
            <div><span>Capital avant cession</span><strong><?= format_money($cession['capital_avant'] !== null ? (float) $cession['capital_avant'] : null) ?></strong></div>
            <div><span>Date de cession</span><strong><?= format_date($cession['cession_date'] ?? null) ?></strong></div>
        </div>
    </div>
</article>

<article class="card">
    <div class="section-header">
        <h3>Lignes de cession (<?= count($cessionParts) ?>)</h3>
    </div>
    <?php if (!$cessionParts): ?>
        <p class="table-empty">Aucune ligne de cession.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="cedant">Cedant</th>
                        <th data-col="cedant-cin">CIN</th>
                        <th data-col="cessionnaire">Cessionnaire</th>
                        <th data-col="cessionnaire-cin">CIN</th>
                        <th data-col="pourcentage">%</th>
                        <th data-col="parts">Parts cedees</th>
                        <th data-col="prix-u">Prix unitaire</th>
                        <th data-col="prix-t">Prix total</th>
                        <th>Gerant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cessionParts as $p): ?>
                    <tr>
                        <td><?= e($p['cedant_nom_complet']) ?></td>
                        <td><?= e($p['cedant_cin'] ?: '-') ?></td>
                        <td><?= e($p['cessionnaire_nom_complet']) ?></td>
                        <td><?= e($p['cessionnaire_cin'] ?: '-') ?></td>
                        <td><?= $p['pourcentage'] ? number_format((float) $p['pourcentage'], 1, ',', ' ') . '%' : '-' ?></td>
                        <td><?= (int) ($p['parts_cedees'] ?? 0) ?></td>
                        <td><?= format_money((float) ($p['prix_unitaire'] ?? 0)) ?></td>
                        <td><?= format_money((float) ($p['prix_total'] ?? 0)) ?></td>
                        <td><?= !empty($p['nommer_gerant']) ? '<span class="material-symbols-outlined" style="color:var(--success);font-size:1.1rem">verified</span>' : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:600">
                        <td colspan="5">Total</td>
                        <td><?= array_sum(array_map(fn($p) => (int) ($p['parts_cedees'] ?? 0), $cessionParts)) ?></td>
                        <td></td>
                        <td><?= format_money(array_sum(array_map(fn($p) => (float) ($p['prix_total'] ?? 0), $cessionParts))) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</article>

<article class="card">
    <div class="section-header">
        <h3>Documents generes (<?= count($documents) ?>)</h3>
    </div>
    <?php if (!$documents): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined">description</span>
            <p class="table-empty">Aucun document genere pour cette cession.</p>
        </div>
    <?php else: ?>
        <form method="post" id="docs-form">
            <?= csrf_input() ?>
            <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" id="select-all-docs"></th>
                        <th data-col="type">Type</th>
                        <th data-col="source">Source</th>
                        <th data-col="fichier">Fichier</th>
                        <th data-col="taille">Taille</th>
                        <th data-col="statut">Statut</th>
                        <th data-col="date">Date</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td class="col-check"><input type="checkbox" name="selected_files[]" value="<?= e((string) $doc['id']) ?>"></td>
                        <td><?= e($docTypeLabels[$doc['doc_type']] ?? $doc['doc_type']) ?></td>
                        <td><?= e($sourceLabels[$doc['template_source']] ?? 'Creation') ?></td>
                        <td><?= e(basename((string) ($doc['fichier_docx'] ?? '-'))) ?></td>
                        <td><?= $doc['taille_ko'] ? number_format((float) $doc['taille_ko'], 1) . ' Ko' : '-' ?></td>
                        <td>
                            <span class="statut-badge <?= $doc['valide'] ? 'valide' : 'brouillon' ?>">
                                <?= $doc['valide'] ? 'Valide' : 'Brouillon' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime((string) $doc['created_at'])) ?></td>
                        <td>
                            <div class="table-actions">
                                <?php if ($doc['fichier_docx'] && file_exists($doc['fichier_docx'])): ?>
                                <a class="btn-icon primary" href="<?= e(word_url($doc['fichier_docx'])) ?>" title="Ouvrir dans Word">
                                    <span class="material-symbols-outlined">article</span>
                                </a>
                                <a class="btn-icon success" href="<?= e(str_replace(dirname(__DIR__, 3) . '/', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                    <span class="material-symbols-outlined">download</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($doc['fichier_pdf'] && file_exists($doc['fichier_pdf'])): ?>
                                <a class="btn-icon danger" href="<?= e(str_replace(dirname(__DIR__, 3) . '/', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                </a>
                                <?php endif; ?>
                                <?php if (!$doc['valide']): ?>
                                <a class="btn-icon success" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('docs-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='validate_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Validation en cours...'); f.submit();} })();" title="Valider">
                                    <span class="material-symbols-outlined">task_alt</span>
                                </a>
                                <?php else: ?>
                                <a class="btn-icon warning" href="#" onclick="event.preventDefault(); (function(){ var f=document.getElementById('docs-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='restore_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Restauration en cours...'); f.submit();} })();" title="Restaurer en brouillon">
                                    <span class="material-symbols-outlined">restore</span>
                                </a>
                                <?php endif; ?>
                                <a class="btn-icon danger" href="#" onclick="event.preventDefault(); if(!confirm('Supprimer ce document ?')) return; (function(){ var f=document.getElementById('docs-form'); var c=f.querySelector('input[name=\'selected_files[]\'][value=\'<?= e((string) $doc['id']) ?>\']'); if(c){c.checked=true; var h=document.createElement('input'); h.type='hidden'; h.name='delete_submit'; h.value='1'; f.appendChild(h); window.showOverlay('Suppression en cours...'); f.submit();} })();" title="Supprimer">
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
                <?php $allValides = count($documents) > 0 && count(array_filter($documents, fn($d) => !$d['valide'])) === 0; ?>
                <?php if ($allValides): ?>
                    <button class="btn btn-back" type="submit" name="restore_submit" value="1">
                        <span class="material-symbols-outlined">restore</span> Restaurer en brouillons
                    </button>
                <?php else: ?>
                    <button class="btn btn-next" type="submit" name="validate_submit" value="1">
                        <span class="material-symbols-outlined">task_alt</span> Valider la selection
                    </button>
                <?php endif; ?>
                <button class="btn btn-danger" type="submit" name="delete_submit" value="1">
                    <span class="material-symbols-outlined">delete</span> Supprimer la selection
                </button>
            </div>
        </form>
        <script>
        document.getElementById('select-all-docs')?.addEventListener('click', function() {
            var checkboxes = document.querySelectorAll('#docs-form input[name="selected_files[]"]');
            checkboxes.forEach(function(c) { c.checked = this.checked; }.bind(this));
        });
        </script>
    <?php endif; ?>
</article>

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
    document.getElementById('docs-form')?.addEventListener('submit', function(e){
        var btn = e.submitter;
        if(btn && btn.name === 'delete_submit'){
            window.showOverlay('Suppression en cours...');
        } else {
            window.showOverlay('Validation en cours...');
        }
    });
})();
</script>
