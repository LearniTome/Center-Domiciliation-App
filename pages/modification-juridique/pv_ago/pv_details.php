<?php
declare(strict_types=1);

if (!(($pdo ?? null) instanceof PDO)) {
    echo '<p class="table-empty">Base de donnees indisponible.</p>';
    return;
}

$viewId = (int) ($_GET['id'] ?? 0);

// Delete action
if (is_post() && isset($_POST['delete_pv_ago'])) {
    verify_csrf();
    $delId = (int) ($_POST['delete_pv_ago'] ?? 0);
    if ($delId > 0 && has_permission('pv_ago.delete')) {
        $pdo->prepare('DELETE FROM pv_ago WHERE id = :id')->execute(['id' => $delId]);
        set_flash('success', 'PV AGO supprime.');
        log_activity($pdo, 'delete', 'pv_ago', $delId);
    }
    redirect_to('pvag');
}

// Detail view
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
    $rsFmt = fn($v) => number_format((float) $v, 2, ',', ' ');
    $resolutions = [];
    if (!empty($pv['resolutions'])) {
        $parsed = json_decode($pv['resolutions'], true);
        if (is_array($parsed)) $resolutions = $parsed;
    }
    // Get generated documents
    $stmtDocs = $pdo->prepare('SELECT * FROM documents_generes WHERE pv_ago_id = :pid ORDER BY id');
    $stmtDocs->execute(['pid' => $viewId]);
    $docs = $stmtDocs->fetchAll();
?>
<div class="stack">
    <div class="section-header">
        <h2>PV AGO n°<?= e($pv['dossier_numero'] ?? '-') ?></h2>
        <div class="table-actions">
            <a class="btn btn-secondary" href="<?= e(app_url('pvag')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour a la liste</a>
            <?php if (has_permission('pv_ago.create')): ?>
            <a class="btn btn-next" href="<?= e(app_url('pv_ago')) ?>"><span class="material-symbols-outlined">add</span> Nouveau PV AGO</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="stats" style="margin-bottom:16px">
        <article class="stat">
            <span class="stat-label">Societe</span>
            <strong><?= e($soc['societe_raison_sociale'] ?? '-') ?></strong>
        </article>
        <article class="stat">
            <span class="stat-label">Forme juridique</span>
            <strong><?= e($soc['societe_forme_juridique'] ?? '-') ?></strong>
        </article>
        <article class="stat">
            <span class="stat-label">Capital social</span>
            <strong><?= $rsFmt($pv['capital_social'] ?? $soc['societe_capital'] ?? 0) ?> DH</strong>
        </article>
        <article class="stat">
            <span class="stat-label">Date AGO</span>
            <strong><?php if (!empty($pv['date_ago'])): $dt = date_create($pv['date_ago']); echo $dt ? $dt->format('d/m/Y') : e($pv['date_ago']); endif; ?></strong>
        </article>
        <article class="stat">
            <span class="stat-label">Exercice clos</span>
            <strong><?= e($pv['exercice_clos'] ?? '-') ?></strong>
        </article>
        <article class="stat">
            <span class="stat-label">Statut</span>
            <strong><?= e($pv['statut'] ?? 'brouillon') ?></strong>
        </article>
    </section>

    <div class="grid two" style="margin-bottom:16px">
        <div class="card" style="padding:16px">
            <h4>Resultat de l'exercice</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Resultat net</span>
                    <span class="info-value <?= $isBenefice ? '' : 'text-danger' ?>"><?= $rsFmt($pv['resultat_net'] ?? 0) ?> DH</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Type</span>
                    <span class="info-value"><?= $isBenefice ? 'Benefice' : 'Perte' ?></span>
                </div>
                <?php if (!empty($pv['report_a_nouveau_debiteur']) && (float) $pv['report_a_nouveau_debiteur'] > 0): ?>
                <div class="info-item">
                    <span class="info-label">Report a nouveau debiteur</span>
                    <span class="info-value"><?= $rsFmt($pv['report_a_nouveau_debiteur']) ?> DH</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card" style="padding:16px">
            <h4>Assemblee</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Heure</span>
                    <span class="info-value"><?= e($pv['heure_ago'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lieu</span>
                    <span class="info-value"><?= e($pv['lieu_ago'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">President</span>
                    <span class="info-value"><?= e($pv['president_nom'] ?? '-') ?> (<?= e($pv['president_qualite'] ?? '-') ?>)</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Parts presentes</span>
                    <span class="info-value"><?= (int) ($pv['total_parts'] ?? 0) ?> / <?= (int) ($pv['parts_presentes'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isBenefice): ?>
    <div class="card" style="padding:16px;margin-bottom:16px">
        <h4>Affectation du resultat</h4>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Option</span>
                <span class="info-value"><?= $pv['affectation_option'] === 'profit_distribution' ? 'Distribution dividendes' : 'Report a nouveau' ?></span>
            </div>
            <?php $dotationRL = max(0, (float) ($pv['resultat_net'] ?? 0) * 0.05 - (float) ($pv['report_a_nouveau_debiteur'] ?? 0) * 0.05); ?>
            <div class="info-item">
                <span class="info-label">Dotation reserve legale</span>
                <span class="info-value"><?= $rsFmt($dotationRL) ?> DH</span>
            </div>
            <?php if (!empty($pv['reserve_statutaire_dotation']) && (float) $pv['reserve_statutaire_dotation'] > 0): ?>
            <div class="info-item">
                <span class="info-label">Reserve statutaire</span>
                <span class="info-value"><?= $rsFmt($pv['reserve_statutaire_dotation']) ?> DH</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($pv['reserve_facultative_dotation']) && (float) $pv['reserve_facultative_dotation'] > 0): ?>
            <div class="info-item">
                <span class="info-label">Reserve facultative</span>
                <span class="info-value"><?= $rsFmt($pv['reserve_facultative_dotation']) ?> DH</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($pv['dividende_total']) && (float) $pv['dividende_total'] > 0): ?>
            <div class="info-item">
                <span class="info-label">Dividende brut</span>
                <span class="info-value"><?= $rsFmt($pv['dividende_total']) ?> DH</span>
            </div>
            <div class="info-item">
                <span class="info-label">TPA (10%)</span>
                <span class="info-value"><?= $rsFmt((float) $pv['dividende_total'] * 0.10) ?> DH</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($resolutions)): ?>
    <div class="card" style="padding:16px;margin-bottom:16px">
        <h4>Resolutions (<?= count($resolutions) ?>)</h4>
        <?php foreach ($resolutions as $i => $r): ?>
        <div style="margin-bottom:10px;padding:8px;background:var(--bg-secondary);border-radius:6px">
            <strong>Resolution <?= $i + 1 ?> : <?= e($r['title'] ?? '') ?></strong>
            <p style="margin:4px 0 0;white-space:pre-wrap;font-size:0.85rem;line-height:1.5"><?= e($r['content'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($docs)): ?>
    <div class="card" style="padding:16px;margin-bottom:16px">
        <h4>Documents generes</h4>
        <div class="table-scroll" style="margin-top:8px">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>DOCX</th>
                        <th>PDF</th>
                        <th>Taille</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docs as $d): ?>
                    <tr>
                        <td><?= e($d['doc_type'] ?? '-') ?></td>
                        <td><?php if (!empty($d['fichier_docx']) && file_exists($d['fichier_docx'])): ?><a href="<?= e(str_replace(dirname(__DIR__, 3) . '/', '', $d['fichier_docx'])) ?>" download class="btn btn-secondary" style="padding:2px 8px;font-size:0.75rem"><span class="material-symbols-outlined" style="font-size:14px">download</span> DOCX</a><?php else: ?><span class="help-text">-</span><?php endif; ?></td>
                        <td><?php if (!empty($d['fichier_pdf']) && file_exists($d['fichier_pdf'])): ?><a href="<?= e(str_replace(dirname(__DIR__, 3) . '/', '', $d['fichier_pdf'])) ?>" download class="btn" style="padding:2px 8px;font-size:0.75rem"><span class="material-symbols-outlined" style="font-size:14px">picture_as_pdf</span> PDF</a><?php else: ?><span class="help-text">-</span><?php endif; ?></td>
                        <td><?= e($d['taille_ko'] ?? '-') ?> Ko</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('pv_ago.edit')): ?>
    <div class="table-actions" style="margin-top:8px">
        <a class="btn btn-back" href="<?= e(app_url('pv_ago', ['step' => 1, 'id' => $viewId, 'edit' => 1])) ?>">
            <span class="material-symbols-outlined">edit</span> Modifier
        </a>
    </div>
    <?php endif; ?>
</div>

<?php
return;
endif;
// ============ LIST VIEW ============
?>

<div class="stack">
    <div class="section-header">
        <h2>PV d'assemblee generale ordinaire</h2>
        <?php if (has_permission('pv_ago.create')): ?>
        <a class="btn btn-next" href="<?= e(app_url('pv_ago')) ?>">
            <span class="material-symbols-outlined">add</span> Nouveau PV AGO
        </a>
        <?php endif; ?>
    </div>

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

    <div class="card">
        <div class="section-header">
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="pvag">
                <input type="search" name="q" placeholder="Rechercher par dossier, societe, exercice..." value="<?= e($search) ?>" style="min-width:300px">
                <button class="btn btn-secondary" type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($search !== ''): ?>
                <a class="btn btn-cancel" href="<?= e(app_url('pvag')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </form>
            <?php if (count($list) > 0 && has_permission('pv_ago.export') && function_exists('export_csv')): ?>
            <a class="btn btn-info" href="<?= e(app_url('pvag', ['export' => 'csv'] + ($search ? ['q' => $search] : []))) ?>"><span class="material-symbols-outlined">download</span> Exporter CSV</a>
            <?php endif; ?>
        </div>

        <?php if (empty($list)): ?>
        <p class="table-empty">Aucun PV AGO trouve.</p>
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
                        <td><a href="<?= e(app_url('pvag', ['id' => $row['id']])) ?>"><?= e($row['dossier_numero'] ?? '-') ?></a></td>
                        <td><?= e($row['societe_raison_sociale'] ?? '-') ?></td>
                        <td><?php if (!empty($row['date_ago'])): $dt = date_create($row['date_ago']); echo $dt ? $dt->format('d/m/Y') : e($row['date_ago']); endif; ?></td>
                        <td><?= e($row['exercice_clos'] ?? '-') ?></td>
                        <td class="<?= ($row['resultat_type'] ?? 'benefice') === 'benefice' ? '' : 'text-danger' ?>"><?= number_format((float) ($row['resultat_net'] ?? 0), 2, ',', ' ') ?> DH</td>
                        <td><?= e($row['statut'] ?? 'brouillon') ?></td>
                        <td><?php if (!empty($row['created_at'])): $dt = date_create($row['created_at']); echo $dt ? $dt->format('d/m/Y H:i') : '-'; endif; ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn-icon" href="<?= e(app_url('pvag', ['id' => $row['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                                <?php if (has_permission('pv_ago.edit')): ?>
                                <a class="btn-icon" href="<?= e(app_url('pv_ago', ['step' => 1, 'id' => $row['id'], 'edit' => 1])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php endif; ?>
                                <?php if (has_permission('pv_ago.delete')): ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce PV AGO ?')">
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
        <div class="pagination" style="padding:12px;display:flex;gap:8px;justify-content:center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="btn <?= $i === $pageNum ? 'btn-next' : 'btn-secondary' ?>" href="<?= e(app_url('pvag', ['p' => $i] + ($search ? ['q' => $search] : []))) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
