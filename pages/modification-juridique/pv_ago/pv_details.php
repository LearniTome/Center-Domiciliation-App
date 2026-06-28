<?php
declare(strict_types=1);

if (!(($pdo ?? null) instanceof PDO)) {
    echo '<p class="table-empty">Base de donnees indisponible.</p>';
    return;
}

$viewId = (int) ($_GET['id'] ?? 0);

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv' && has_permission('pv_ago.export')) {
    $searchExp = trim($_GET['q'] ?? '');
    $whereExp = '';
    $paramsExp = [];
    if ($searchExp !== '') {
        $whereExp = 'WHERE p.dossier_numero LIKE :q OR s.societe_raison_sociale LIKE :q2 OR p.exercice_clos LIKE :q3';
        $paramsExp = ['q' => "%$searchExp%", 'q2' => "%$searchExp%", 'q3' => "%$searchExp%"];
    }
    $stmtExp = $pdo->prepare("SELECT p.*, s.societe_raison_sociale FROM pv_ago p LEFT JOIN societes s ON p.societe_id = s.id $whereExp ORDER BY p.created_at DESC");
    $stmtExp->execute($paramsExp);
    $rowsExp = [];
    while ($r = $stmtExp->fetch()) {
        $rowsExp[] = [
            'dossier_numero' => $r['dossier_numero'] ?? '',
            'societe' => $r['societe_raison_sociale'] ?? '',
            'date_ago' => $r['date_ago'] ?? '',
            'exercice_clos' => $r['exercice_clos'] ?? '',
            'resultat_net' => number_format((float) ($r['resultat_net'] ?? 0), 2, ',', ' '),
            'resultat_type' => $r['resultat_type'] ?? 'benefice',
            'statut' => $r['statut'] ?? 'brouillon',
            'president' => $r['president_nom'] ?? '',
            'affectation' => match ($r['affectation_option'] ?? '') {
                'profit_distribution' => 'Distribution de dividendes',
                'loss_carryforward' => 'Report a nouveau',
                'loss_reserves' => 'Imputation sur les reserves',
                default => $r['affectation_option'] ?? '',
            },
        ];
    }
    export_csv('pv_ago_' . date('Y-m-d') . '.csv', ['Dossier', 'Societe', 'Date AGO', 'Exercice clos', 'Resultat net', 'Type', 'Statut', 'President', 'Affectation'], $rowsExp);
}

// Delete PV AGO
if (is_post() && isset($_POST['delete_pv_ago'])) {
    verify_csrf();
    $delId = (int) ($_POST['delete_pv_ago'] ?? 0);
    if ($delId > 0 && has_permission('pv_ago.delete')) {
        $pdo->prepare('DELETE FROM pv_ago WHERE id = :id')->execute(['id' => $delId]);
        set_flash('success', 'PV AGO supprime.');
        log_activity($pdo, 'delete', 'pv_ago', $delId);
    }
    redirect_to('pv_ago');
}

// Generate PV AGO document directly
if (is_post() && isset($_POST['generate_pv_ago'])) {
    verify_csrf();
    $genId = (int) ($_POST['generate_pv_ago'] ?? 0);
    if ($genId > 0 && has_permission('pv_ago.create')) {
        require_once __DIR__ . '/../../../src/analyseur_templates.php';
        require_once __DIR__ . '/../../../src/rendu_document.php';
        if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../vendor/autoload.php';
        }
        $context = DocumentRenderer::buildContextFromPvAgo($pdo, $genId);
        if (empty($context)) {
            set_flash('error', 'Impossible de construire le contexte pour le PV AGO.');
            redirect_to('pv_ago', ['id' => $genId]);
        }
        $societeData = $context['societe'] ?? [];
        $socName = $societeData['societe_raison_sociale'] ?? 'Client';
        $forme = $societeData['societe_forme_juridique'] ?? 'PP';
        $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $socName)));
        $clientName = preg_replace('/-+/', '-', $clientName);
        $clientName = trim($clientName, '-');
        $today = date('Y-m-d');
        $sanitizedForme = str_replace(' ', '_', $forme);
        $outputDir = __DIR__ . '/../../../dossiers_generer/dossiers_pv_ago/' . $sanitizedForme . '_' . $today . '_' . $clientName;
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);
        $templateDir = __DIR__ . '/../../../templates/_PV_AGO';
        $matches = glob($templateDir . '/*PV-AGO*_Template.docx');
        if (empty($matches)) {
            set_flash('error', 'Aucun template PV-AGO trouve dans templates/_PV_AGO/. Ajoutez un fichier *PV-AGO*_Template.docx.');
            redirect_to('pv_ago', ['id' => $genId]);
        }
        $outName = $sanitizedForme . '_' . $today . '_PV-AGO_' . $clientName . '.docx';
        try {
            $renderer = new DocumentRenderer($matches[0], $outputDir);
            $docxPath = $renderer->render($context, $outName);
            $pdfPath = $renderer->tryConvertToPdf($docxPath);
            $stmtD = $pdo->prepare('INSERT INTO documents_generes (societe_id, pv_ago_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko, valide) VALUES (:sid, :pid, :src, :type, :docx, :pdf, :taille, 1)');
            $stmtD->execute([
                'sid' => (int) $societeData['id'],
                'pid' => $genId,
                'src' => 'pv_ago',
                'type' => 'PV-AGO',
                'docx' => $docxPath,
                'pdf' => $pdfPath ?? '',
                'taille' => round(filesize($docxPath) / 1024, 2),
            ]);
            set_flash('success', 'Document PV AGO genere avec succes.');
            log_activity($pdo, 'generate', 'pv_ago', $genId, $outName);
        } catch (Throwable $e) {
            set_flash('error', 'Erreur de generation: ' . $e->getMessage());
        }
    }
    redirect_to('pv_ago', ['id' => $genId]);
}

// Delete selected generated documents
if (is_post() && isset($_POST['delete_docs'])) {
    verify_csrf();
    $selected = $_POST['selected_docs'] ?? [];
    if (!empty($selected) && has_permission('pv_ago.delete')) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $params = array_merge(array_map('intval', $selected), [$viewId]);
        $stmt = $pdo->prepare("SELECT id, fichier_docx, fichier_pdf FROM documents_generes WHERE id IN ($placeholders) AND pv_ago_id = ?");
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $doc) {
            if (!empty($doc['fichier_docx']) && file_exists($doc['fichier_docx'])) unlink($doc['fichier_docx']);
            if (!empty($doc['fichier_pdf']) && file_exists($doc['fichier_pdf'])) unlink($doc['fichier_pdf']);
        }
        $pdo->prepare("DELETE FROM documents_generes WHERE id IN ($placeholders) AND pv_ago_id = ?")->execute($params);
        set_flash('success', count($selected) . ' document(s) supprime(s).');
    }
    redirect_to('pv_ago', ['id' => $viewId]);
}

// ============ DETAIL VIEW ============
if ($viewId > 0):
    $stmt = $pdo->prepare('SELECT * FROM pv_ago WHERE id = :id');
    $stmt->execute(['id' => $viewId]);
    $pv = $stmt->fetch();
    if (!$pv) {
        echo '<p class="table-empty">PV AGO introuvable.</p>';
        return;
    }
    $soc = fetch_record($pdo, 'societes', (int) $pv['societe_id']);
    $isBenefice = ($pv['resultat_type'] ?? 'benefice') === 'benefice';
    $resolutions = [];
    if (!empty($pv['resolutions'])) {
        $parsed = json_decode($pv['resolutions'], true);
        if (is_array($parsed)) $resolutions = $parsed;
    }
    $stmtDocs = $pdo->prepare('SELECT * FROM documents_generes WHERE pv_ago_id = :pid ORDER BY id');
    $stmtDocs->execute(['pid' => $viewId]);
    $docs = $stmtDocs->fetchAll();

    $hasDividende = !empty($pv['dividende_total']) && (float) $pv['dividende_total'] > 0;

    $affectationLabels = [
        'profit_distribution' => 'Distribution de dividendes',
        'loss_carryforward' => 'Report a nouveau',
        'loss_reserves' => 'Imputation sur les reserves',
    ];
    $statutBadgeClass = match ($pv['statut'] ?? 'brouillon') {
        'finalise' => 'valide',
        default => 'brouillon',
    };
?>
<div class="section-title-row">
    <h2>PV AGO n&deg;<?= e($pv['dossier_numero'] ?? '-') ?></h2>
    <div class="table-actions">
        <a class="btn btn-back" href="<?= e(app_url('pv_ago')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        <?php if (has_permission('pv_ago.create')): ?>
        <a class="btn btn-next" href="<?= e(app_url('pv_ago_wizard')) ?>"><span class="material-symbols-outlined">add</span> Nouveau PV AGO</a>
        <?php endif; ?>
    </div>
</div>

<section class="stats small stats-bottom-margin">
    <article class="stat">
        <span>Societe</span>
        <strong><?= e($soc['societe_raison_sociale'] ?? '-') ?></strong>
    </article>
    <article class="stat">
        <span>Forme juridique</span>
        <strong><?= e($soc['societe_forme_juridique'] ?? '-') ?></strong>
    </article>
    <article class="stat">
        <span>Capital social</span>
        <strong><?= format_money((float) ($pv['capital_social'] ?? $soc['societe_capital'] ?? 0)) ?></strong>
    </article>
    <article class="stat">
        <span>Date AGO</span>
        <strong><?= format_date($pv['date_ago'] ?? null) ?></strong>
    </article>
    <article class="stat">
        <span>Exercice clos</span>
        <strong><?= e($pv['exercice_clos'] ?? '-') ?></strong>
    </article>
    <article class="stat">
        <span>Statut</span>
        <strong><span class="statut-badge <?= $statutBadgeClass ?>"><?= e($pv['statut'] ?? 'brouillon') ?></span></strong>
    </article>
</section>

<article class="card stack">
    <h3 class="section-title">Assemblee</h3>
    <div class="info-grid">
        <div><span>Heure</span><strong><?= e($pv['heure_ago'] ?? '-') ?></strong></div>
        <div><span>Lieu</span><strong><?= e($pv['lieu_ago'] ?? '-') ?></strong></div>
        <div><span>President</span><strong><?= e($pv['president_nom'] ?? '-') ?> (<?= e($pv['president_qualite'] ?? '-') ?>)</strong></div>
        <div><span>Parts presentes</span><strong><?= (int) ($pv['parts_presentes'] ?? 0) ?> / <?= (int) ($pv['total_parts'] ?? 0) ?></strong></div>
    </div>
</article>

<?php
// Reuse calculations from buildContextFromPvAgo logic for display
$resultatNet = (float) ($pv['resultat_net'] ?? 0);
$rptDebiteur = (float) ($pv['report_a_nouveau_debiteur'] ?? 0);
$capital = (float) ($pv['capital_social'] ?? $soc['societe_capital'] ?? 0);
$rle = (float) ($pv['reserve_legale_existante'] ?? 0);
$rsd = (float) ($pv['reserve_statutaire_dotation'] ?? 0);
$rfd = (float) ($pv['reserve_facultative_dotation'] ?? 0);
$dividende = (float) ($pv['dividende_total'] ?? 0);
$tpa = $dividende * 0.10;
$dividendeNet = $dividende - $tpa;
$pertePrelevement = (float) ($pv['perte_reserve_prelevement'] ?? 0);
$plafondRL = $capital * 0.10;
$baseRL = max(0, $resultatNet - $rptDebiteur);
$dotationRLcalc = 0;
if ($isBenefice && $baseRL > 0) {
    $d = $baseRL * 0.05;
    $dotationRLcalc = min($d, max(0, $plafondRL - $rle));
}
$reportNv = 0;
if ($isBenefice) {
    $opt = $pv['affectation_option'] ?? 'profit_distribution';
    if ($opt === 'profit_distribution') {
        $reportNv = $baseRL - $dotationRLcalc - $rsd - $rfd - $dividende;
    }
}
?>

<article class="card stack">
    <h3 class="section-title">Calcul detaille</h3>
    <div class="calc-detail-line"><span class="calc-detail-label">Resultat net <strong class="<?= $isBenefice ? 'text-success' : 'text-danger' ?>">(<?= $isBenefice ? 'Benefice' : 'Deficit' ?>)</strong></span><span class="calc-detail-value <?= $isBenefice ? 'text-success' : 'text-danger' ?>"><?= format_money($resultatNet) ?></span></div>
    <?php if ($rptDebiteur > 0): ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Report a nouveau debiteur anterieur</span><span class="calc-detail-value text-danger"><?= format_money($rptDebiteur) ?></span></div>
    <div class="calc-detail-line"><span class="calc-detail-label">Base de calcul reserve legale</span><span class="calc-detail-value"><?= format_money($baseRL) ?></span></div>
    <?php endif; ?>
    <?php if ($isBenefice): ?>
    <?php if ($dotationRLcalc > 0): ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Reserve legale (5%) <span class="help-text">(plafond <?= format_money($plafondRL) ?>)</span></span><span class="calc-detail-value text-success"><?= format_money($dotationRLcalc) ?></span></div>
    <?php else: ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Reserve legale</span><span class="calc-detail-value help-text">Plafond atteint ou non applicable</span></div>
    <?php endif; ?>
    <?php if ($rsd > 0): ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Reserve statutaire</span><span class="calc-detail-value"><?= format_money($rsd) ?></span></div>
    <?php endif; ?>
    <?php if ($rfd > 0): ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Reserve facultative</span><span class="calc-detail-value"><?= format_money($rfd) ?></span></div>
    <?php endif; ?>
    <?php if ($dividende > 0): ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Dividendes a distribuer</span><span class="calc-detail-value"><?= format_money($dividende) ?></span></div>
    <div class="calc-detail-line"><span class="calc-detail-label">TPA (10%)</span><span class="calc-detail-value"><?= format_money($tpa) ?></span></div>
    <div class="calc-detail-line"><span class="calc-detail-label">Dividendes nets verses</span><span class="calc-detail-value text-success"><?= format_money($dividendeNet) ?></span></div>
    <?php endif; ?>
    <div class="calc-detail-line calc-detail-total"><span class="calc-detail-label"><strong style="color:var(--text)">Report a nouveau crediteur (solde)</strong></span><span class="calc-detail-value"><?= format_money(max(0, $reportNv)) ?></span></div>
    <?php else: ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Perte de l'exercice</span><span class="calc-detail-value text-danger"><?= format_money(abs($resultatNet)) ?></span></div>
    <?php if ($pertePrelevement > 0): ?>
    <div class="calc-detail-line"><span class="calc-detail-label">Imputee sur reserves facultatives</span><span class="calc-detail-value"><?= format_money($pertePrelevement) ?></span></div>
    <?php endif; ?>
    <?php endif; ?>
</article>

<article class="card stack">
    <h3 class="section-title">Affectation du resultat</h3>
    <div class="info-grid">
        <div><span>Option d&apos;affectation</span><strong><?= e($affectationLabels[$pv['affectation_option'] ?? ''] ?? $pv['affectation_option'] ?? '-') ?></strong></div>
        <?php if ($isBenefice): ?>
        <div><span>Dotation reserve legale (5%)</span><strong class="text-success"><?= format_money($dotationRLcalc) ?></strong></div>
        <?php if ($rsd > 0): ?>
        <div><span>Reserve statutaire</span><strong><?= format_money($rsd) ?></strong></div>
        <?php endif; ?>
        <?php if ($rfd > 0): ?>
        <div><span>Reserve facultative</span><strong><?= format_money($rfd) ?></strong></div>
        <?php endif; ?>
        <?php if ($dividende > 0): ?>
        <div><span>Dividende brut</span><strong><?= format_money($dividende) ?></strong></div>
        <div><span>TPA (10%)</span><strong><?= format_money($tpa) ?></strong></div>
        <div><span>Dividende net</span><strong><?= format_money($dividendeNet) ?></strong></div>
        <?php endif; ?>
        <div><span>Report a nouveau crediteur</span><strong><?= format_money(max(0, $reportNv)) ?></strong></div>
        <?php else: ?>
        <?php if ($pertePrelevement > 0): ?>
        <div><span>Prelevement sur reserves</span><strong class="text-danger"><?= format_money($pertePrelevement) ?></strong></div>
        <?php endif; ?>
        <?php if ((float) $pv['report_a_nouveau_debiteur'] > 0): ?>
        <div><span>Report a nouveau debiteur</span><strong class="text-danger"><?= format_money((float) $pv['report_a_nouveau_debiteur']) ?></strong></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</article>

<?php if (!empty($resolutions)): ?>
<article class="card stack">
    <h3 class="section-title">Resolutions (<?= count($resolutions) ?>)</h3>
    <?php foreach ($resolutions as $i => $r): ?>
    <div class="resolution-card">
        <span class="resolution-card-title">Resolution <?= $i + 1 ?> : <?= e($r['title'] ?? '') ?></span>
        <p class="resolution-card-body"><?= e($r['content'] ?? '') ?></p>
    </div>
    <?php endforeach; ?>
</article>
<?php endif; ?>

<article class="card">
    <div class="section-header">
        <h3>Documents generes (<?= count($docs) ?>)</h3>
        <?php if (has_permission('pv_ago.create')): ?>
        <form method="post" class="inline-form" id="gen-form-header">
            <?= csrf_input() ?>
            <input type="hidden" name="generate_pv_ago" value="<?= $viewId ?>">
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">sync</span> <?= empty($docs) ? 'Generer le document' : 'Regenerer' ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php if (empty($docs)): ?>
    <div class="empty-state">
        <span class="material-symbols-outlined">description</span>
        <p class="table-empty">Aucun document genere pour ce PV AGO.</p>
        <?php if (has_permission('pv_ago.create')): ?>
        <form method="post" id="gen-form-empty">
            <?= csrf_input() ?>
            <input type="hidden" name="generate_pv_ago" value="<?= $viewId ?>">
            <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">description</span> Generer le document</button>
        </form>
        <?php endif; ?>
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
                        <th data-col="docx">DOCX</th>
                        <th data-col="pdf">PDF</th>
                        <th data-col="taille">Taille</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docs as $d): ?>
                    <tr>
                        <td class="col-check"><input type="checkbox" name="selected_docs[]" value="<?= (int) $d['id'] ?>"></td>
                        <td><?= e($d['doc_type'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($d['fichier_docx']) && file_exists($d['fichier_docx'])): ?>
                            <a class="btn-icon success" href="<?= e(download_url($d['fichier_docx'])) ?>" download title="Telecharger DOCX"><span class="material-symbols-outlined">download</span></a>
                            <?php else: ?>
                            <span class="help-text">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($d['fichier_pdf']) && file_exists($d['fichier_pdf'])): ?>
                            <a class="btn-icon danger" href="<?= e(download_url($d['fichier_pdf'])) ?>" download title="Telecharger PDF"><span class="material-symbols-outlined">picture_as_pdf</span></a>
                            <?php else: ?>
                            <span class="help-text">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($d['taille_ko'] ?? '-') ?> Ko</td>
                        <td class="col-actions">
                            <div class="table-actions">
                                <?php if (!empty($d['fichier_docx']) && file_exists($d['fichier_docx'])): ?>
                                <a class="btn-icon primary" href="<?= e(word_url($d['fichier_docx'])) ?>" title="Ouvrir dans Word"><span class="material-symbols-outlined">article</span></a>
                                <?php endif; ?>
                                <a class="btn-icon danger" href="#" onclick="event.preventDefault();if(!confirm('Supprimer ce document ?'))return;(function(){var f=document.getElementById('docs-form');var c=f.querySelector('input[name=\'selected_docs[]\'][value=\'<?= (int) $d['id'] ?>\']');if(c){c.checked=true;window.showOverlay('Suppression en cours...');var h=document.createElement('input');h.type='hidden';h.name='delete_docs';h.value='1';f.appendChild(h);f.submit();}})();" title="Supprimer"><span class="material-symbols-outlined">delete</span></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-actions table-actions-top">
            <button class="btn btn-danger" type="submit" name="delete_docs" value="1"><span class="material-symbols-outlined">delete</span> Supprimer la selection</button>
        </div>
    </form>
    <?php endif; ?>
</article>

<?php if (has_permission('pv_ago.edit')): ?>
<div class="section-title-row">
    <div></div>
    <a class="btn btn-info" href="<?= e(app_url('pv_ago_wizard', ['step' => 1, 'id' => $viewId, 'edit' => 1])) ?>">
        <span class="material-symbols-outlined">edit</span> Modifier le PV AGO
    </a>
</div>
<?php endif; ?>

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
    document.querySelectorAll('form[id^="gen-form"]').forEach(function(f){
        f.addEventListener('submit', function(e){
            window.showOverlay('Generation du document en cours...');
        });
    });
    document.getElementById('docs-form')?.addEventListener('submit', function(e){
        window.showOverlay('Suppression en cours...');
    });
    document.getElementById('select-all-docs')?.addEventListener('click', function(){
        var checkboxes = document.querySelectorAll('#docs-form input[name="selected_docs[]"]');
        checkboxes.forEach(function(c){ c.checked = this.checked; }.bind(this));
    });
})();
</script>

<?php
return;
endif;
// ============ LIST VIEW ============
?>

<?php
$search = trim($_GET['q'] ?? '');
$pageNum = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($pageNum - 1) * $perPage;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE p.dossier_numero LIKE :q OR s.societe_raison_sociale LIKE :q2 OR p.exercice_clos LIKE :q3';
    $params = ['q' => "%$search%", 'q2' => "%$search%", 'q3' => "%$search%"];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pv_ago p LEFT JOIN societes s ON p.societe_id = s.id $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT p.*, s.societe_raison_sociale FROM pv_ago p LEFT JOIN societes s ON p.societe_id = s.id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$list = $stmt->fetchAll();
?>

<article class="card">
    <div class="section-header">
        <span class="page-count"><?= $total ?> enregistrement(s)</span>
        <?php if (has_permission('pv_ago.export') && function_exists('export_csv')): ?>
        <div class="table-actions">
            <a class="btn btn-info" href="<?= e(app_url('pv_ago', ['export' => 'csv'] + ($search ? ['q' => $search] : []))) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
        </div>
        <?php endif; ?>
    </div>
    <form method="get" class="stack search-bar">
        <input type="hidden" name="page" value="pv_ago">
        <div class="inline-form">
            <input type="search" name="q" placeholder="Rechercher par dossier, societe, exercice..." value="<?= e($search) ?>">
            <button class="btn btn-secondary" type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
            <?php if ($search !== ''): ?>
            <a class="btn btn-cancel" href="<?= e(app_url('pv_ago')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($list)): ?>
    <div class="empty-state">
        <span class="material-symbols-outlined">description</span>
        <p class="table-empty">Aucun PV AGO trouve.</p>
    </div>
    <?php else: ?>
    <div class="table-scroll">
        <table data-sortable>
            <thead>
                <tr>
                    <th data-col="dossier">Dossier</th>
                    <th data-col="societe">Societe</th>
                    <th data-col="date">Date AGO</th>
                    <th data-col="exercice">Exercice</th>
                    <th data-col="resultat">Resultat</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="created">Cree le</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                <tr>
                    <td><a href="<?= e(app_url('pv_ago', ['id' => $row['id']])) ?>"><?= e($row['dossier_numero'] ?? '-') ?></a></td>
                    <td><?= e($row['societe_raison_sociale'] ?? '-') ?></td>
                    <td><?= format_date($row['date_ago'] ?? null) ?></td>
                    <td><?= e($row['exercice_clos'] ?? '-') ?></td>
                    <td class="<?= ($row['resultat_type'] ?? 'benefice') === 'benefice' ? 'text-success' : 'text-danger' ?>"><?= format_money((float) ($row['resultat_net'] ?? 0)) ?></td>
                    <td><span class="statut-badge <?= ($row['statut'] ?? 'brouillon') === 'finalise' ? 'valide' : 'brouillon' ?>"><?= e($row['statut'] ?? 'brouillon') ?></span></td>
                    <td><?= format_date($row['created_at'] ?? null) ?></td>
                    <td class="col-actions">
                        <div class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('pv_ago', ['id' => $row['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <?php if (has_permission('pv_ago.edit')): ?>
                            <a class="btn-icon" href="<?= e(app_url('pv_ago_wizard', ['step' => 1, 'id' => $row['id'], 'edit' => 1])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <?php endif; ?>
                            <?php if (has_permission('pv_ago.delete')): ?>
                            <form method="post" style="display:inline" data-confirm="Supprimer ce PV AGO ?">
                                <?= csrf_input() ?>
                                <input type="hidden" name="delete_pv_ago" value="<?= (int) $row['id'] ?>">
                                <button class="btn-icon danger" type="submit" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="btn <?= $i === $pageNum ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('pv_ago', ['p' => $i] + ($search ? ['q' => $search] : []))) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</article>
