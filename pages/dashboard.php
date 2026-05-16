<?php

declare(strict_types=1);

$pdo = $pdo ?? null;
$isConnected = $pdo instanceof PDO;

function time_ago(string $date): string {
    $sec = time() - strtotime($date);
    if ($sec < 60) return 'a l\'instant';
    if ($sec < 3600) return 'il y a ' . intdiv($sec, 60) . ' min';
    if ($sec < 86400) return 'il y a ' . intdiv($sec, 3600) . 'h';
    $j = intdiv($sec, 86400);
    if ($j === 1) return 'hier';
    if ($j < 7) return 'il y a ' . $j . ' jours';
    return date('d/m/Y', strtotime($date));
}

// --- Stats ---
$totalSocietes = 0;
$contratsActifs = 0;
$contratsResilies = 0;
$collaborateursCount = 0;
$dossiersComplets = 0;
$revenuMensuel = 0;
$creationsMois = 0;

if ($isConnected) {
    $totalSocietes = (int) $pdo->query('SELECT COUNT(*) FROM societes')->fetchColumn();
    $contratsActifs = (int) $pdo->query("SELECT COUNT(*) FROM contrats WHERE contrat_statut = 'actif'")->fetchColumn();
    $contratsResilies = (int) $pdo->query("SELECT COUNT(*) FROM contrats WHERE contrat_statut = 'resilie'")->fetchColumn();
    $collaborateursCount = (int) $pdo->query('SELECT COUNT(*) FROM collaborateurs')->fetchColumn();
    $revenuMensuel = (float) $pdo->query("SELECT COALESCE(SUM(contrat_loyer_ttc), 0) FROM contrats WHERE contrat_statut = 'actif'")->fetchColumn();
    $creationsMois = (int) $pdo->query("SELECT COUNT(*) FROM societes WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

    $dossiersComplets = (int) $pdo->query("
        SELECT COUNT(*) FROM societes s
        WHERE EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
        AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
    ")->fetchColumn();
}

$dossiersIncomplets = max(0, $totalSocietes - $dossiersComplets);
$pctComplets = $totalSocietes > 0 ? round(($dossiersComplets / $totalSocietes) * 100) : 0;
$pctClass = $pctComplets >= 80 ? '' : ($pctComplets >= 50 ? 'warning' : 'danger');

$renouvelerCount = 0;
$resiliesMois = 0;
$collabMainType = '';
$incompletsCount = 0;
$templateCount = 0;
$refTableCount = 0;
if ($isConnected) {
    $renouvelerCount = (int) $pdo->query("
        SELECT COUNT(*) FROM contrats
        WHERE contrat_statut = 'actif'
          AND contrat_date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetchColumn();
    $resiliesMois = (int) $pdo->query("
        SELECT COUNT(*) FROM contrats
        WHERE contrat_statut = 'resilie'
          AND MONTH(created_at) = MONTH(CURDATE())
          AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetchColumn();
    $collabMainType = (string) $pdo->query("
        SELECT collaborateur_type FROM collaborateurs
        GROUP BY collaborateur_type ORDER BY COUNT(*) DESC LIMIT 1
    ")->fetchColumn();
    $incompletsCount = (int) $pdo->query("
        SELECT COUNT(*) FROM societes s
        WHERE NOT (EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
               AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id))
    ")->fetchColumn();
}
$templateCount = is_dir(__DIR__ . '/../templates')
    ? count(array_diff(scandir(__DIR__ . '/../templates'), ['.', '..']))
    : 0;
$refTableCount = count(load_defaults());

// --- Repartition ---
$repartitionFormes = [];
$repartitionContrats = [];
if ($isConnected) {
    $repartitionFormes = $pdo->query("
        SELECT societe_forme_juridique, COUNT(*) AS total
        FROM societes WHERE societe_forme_juridique != ''
        GROUP BY societe_forme_juridique ORDER BY total DESC
    ")->fetchAll();

    $repartitionContrats = $pdo->query("
        SELECT contrat_type, COUNT(*) AS total
        FROM contrats GROUP BY contrat_type ORDER BY total DESC
    ")->fetchAll();
}

// --- Timeline (echeances 0-90 jours) ---
$echeances = [];
if ($isConnected) {
    $echeances = $pdo->query("
        SELECT c.id, c.contrat_type, c.contrat_date_fin, s.societe_raison_sociale, s.id AS societe_id,
               DATEDIFF(c.contrat_date_fin, CURDATE()) AS jours_restants
        FROM contrats c
        INNER JOIN societes s ON s.id = c.societe_id
        WHERE c.contrat_statut = 'actif'
          AND c.contrat_date_fin IS NOT NULL
          AND c.contrat_date_fin >= CURDATE()
          AND c.contrat_date_fin <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY c.contrat_date_fin
        LIMIT 8
    ")->fetchAll();
}

// --- Alertes ---
$sansAssocie = [];
$sansContrat = [];
$expirants = [];
$sansDocuments = [];
$cinExpire = [];
$alerteCount = 0;

if ($isConnected) {
    $sansAssocie = $pdo->query("
        SELECT s.id, s.societe_raison_sociale FROM societes s
        LEFT JOIN associes a ON a.societe_id = s.id
        WHERE a.id IS NULL
        ORDER BY s.societe_raison_sociale LIMIT 10
    ")->fetchAll();

    $sansContrat = $pdo->query("
        SELECT s.id, s.societe_raison_sociale FROM societes s
        LEFT JOIN contrats c ON c.societe_id = s.id
        WHERE c.id IS NULL
        ORDER BY s.societe_raison_sociale LIMIT 10
    ")->fetchAll();

    $expirants = $pdo->query("
        SELECT c.id, c.contrat_type, c.contrat_date_fin, s.societe_raison_sociale
        FROM contrats c
        INNER JOIN societes s ON s.id = c.societe_id
        WHERE c.contrat_statut = 'actif'
          AND c.contrat_date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY c.contrat_date_fin LIMIT 10
    ")->fetchAll();

    $sansDocuments = $pdo->query("
        SELECT s.id, s.societe_raison_sociale FROM societes s
        WHERE EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
          AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
          AND NOT EXISTS (SELECT 1 FROM documents_generes d WHERE d.societe_id = s.id)
        ORDER BY s.societe_raison_sociale LIMIT 10
    ")->fetchAll();

    $cinExpire = $pdo->query("
        SELECT a.associe_nom_complet, s.societe_raison_sociale, s.id AS societe_id
        FROM associes a
        INNER JOIN societes s ON s.id = a.societe_id
        WHERE a.associe_date_validite_cin IS NOT NULL
          AND a.associe_date_validite_cin < CURDATE()
        ORDER BY a.associe_date_validite_cin LIMIT 10
    ")->fetchAll();
}

$alerteCount = count($sansAssocie) + count($sansContrat) + count($expirants) + count($sansDocuments) + count($cinExpire);
$hasAlerts = $alerteCount > 0;

// --- Fil d'activite ---
$activiteRecente = [];
if ($isConnected) {
    $activiteRecente = $pdo->query("
        (SELECT 'societe' AS type, id, societe_raison_sociale AS libelle, id AS ref_id, created_at FROM societes)
        UNION ALL
        (SELECT 'contrat', c.id, s.societe_raison_sociale, c.societe_id, c.created_at
         FROM contrats c JOIN societes s ON s.id = c.societe_id)
        UNION ALL
        (SELECT 'associe', a.id, s.societe_raison_sociale, a.societe_id, a.created_at
         FROM associes a JOIN societes s ON s.id = a.societe_id)
        ORDER BY created_at DESC LIMIT 3
    ")->fetchAll();
}

// --- Documents generes ---
$documentsRecents = [];
if ($isConnected) {
    $documentsRecents = $pdo->query("
        SELECT d.id, d.doc_type, d.created_at, d.taille_ko, d.valide, s.societe_raison_sociale, s.id AS societe_id
        FROM documents_generes d
        INNER JOIN societes s ON s.id = d.societe_id
        ORDER BY d.created_at DESC LIMIT 5
    ")->fetchAll();
}

// --- Validation documents ---
$docsValides = 0;
$docsEnAttente = 0;
$docsAVerifier = [];
if ($isConnected) {
    $docsValides = (int) $pdo->query("SELECT COUNT(*) FROM documents_generes WHERE valide = 1")->fetchColumn();
    $docsEnAttente = (int) $pdo->query("SELECT COUNT(*) FROM documents_generes WHERE valide = 0")->fetchColumn();
    $docsAVerifier = $pdo->query("
        SELECT d.id, d.doc_type, d.created_at, d.valide, d.societe_id, s.societe_raison_sociale
        FROM documents_generes d
        INNER JOIN societes s ON s.id = d.societe_id
        WHERE d.valide = 0
        ORDER BY d.created_at DESC LIMIT 10
    ")->fetchAll();
}
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:0.75rem">
    <a class="btn btn-next" href="<?= e(app_url('creation')) ?>"><span class="mdi mdi-plus-circle"></span> Creer un dossier</a>
</div>

<!-- Stats row -->
<section class="stats small">
    <article class="stat primary">
        <span>Societes</span>
        <strong><?= $totalSocietes ?></strong>
        <small style="font-size:0.65rem;color:var(--text-secondary)">+<?= $creationsMois ?> ce mois</small>
    </article>
    <article class="stat success">
        <span>Contrats actifs</span>
        <strong><?= $contratsActifs ?></strong>
        <small style="font-size:0.65rem;color:var(--text-secondary)"><?= $renouvelerCount ?> a renouveler</small>
    </article>
    <article class="stat danger">
        <span>Contrats resilies</span>
        <strong><?= $contratsResilies ?></strong>
        <small style="font-size:0.65rem;color:var(--text-secondary)"><?= $resiliesMois ?> ce mois</small>
    </article>
    <article class="stat success">
        <span>Revenu mensuel</span>
        <strong><?= number_format($revenuMensuel, 2, ',', ' ') ?> DH</strong>
    </article>
    <article class="stat">
        <span>Collaborateurs</span>
        <strong><?= $collaborateursCount ?></strong>
        <?php if ($collabMainType): ?>
        <small style="font-size:0.65rem;color:var(--text-secondary)">Principal: <?= e($collabMainType) ?></small>
        <?php endif; ?>
    </article>
    <article class="stat" style="grid-column:span 2">
        <span>Dossiers complets</span>
        <strong><?= $dossiersComplets ?>/<?= $totalSocietes ?></strong>
        <div class="progress-bar">
            <div class="progress-track">
                <div class="progress-fill <?= $pctClass ?>" style="width:<?= $pctComplets ?>%"></div>
            </div>
            <span class="progress-label"><?= $pctComplets ?>%</span>
        </div>
    </article>
</section>

<section class="quick-actions extra">
    <a class="card quick-action" href="<?= e(app_url('creation')) ?>">
        <span class="mdi mdi-plus-circle quick-icon" style="color:var(--success)"></span>
        <div>
            <strong>Creer un dossier</strong>
            <span class="help-text">Nouvelle societe + contrat</span>
            <span class="quick-count"><?= $incompletsCount ?> dossiers incomplets</span>
        </div>
    </a>
    <a class="card quick-action" href="<?= e(app_url('collaborateur')) ?>">
        <span class="mdi mdi-account-plus quick-icon" style="color:var(--primary)"></span>
        <div>
            <strong>Nouveau collaborateur</strong>
            <span class="help-text">Ajouter un expert, coursier...</span>
            <span class="quick-count"><?= $collaborateursCount ?> collaborateurs</span>
        </div>
    </a>
    <a class="card quick-action" href="<?= e(app_url('template_edit', ['path' => ''])) ?>">
        <span class="mdi mdi-file-document-edit quick-icon" style="color:var(--info)"></span>
        <div>
            <strong>Editeur de template</strong>
            <span class="help-text">Modifier les documents Word</span>
            <span class="quick-count"><?= $templateCount ?> templates</span>
        </div>
    </a>
    <a class="card quick-action" href="<?= e(app_url('configuration')) ?>">
        <span class="mdi mdi-cog quick-icon" style="color:var(--warning)"></span>
        <div>
            <strong>Configuration</strong>
            <span class="help-text">Tables de reference</span>
            <span class="quick-count"><?= $refTableCount ?> tables</span>
        </div>
    </a>
</section>

<?php if ($hasAlerts): ?>
<section>
    <article class="card">
        <div class="section-header">
            <h2>
                <span class="mdi mdi-alert" style="color:var(--warning);margin-right:6px"></span>Alertes
                <span class="alert-badge"><?= $alerteCount ?></span>
            </h2>
        </div>
        <div class="alerts-list">
            <?php if ($sansAssocie): ?>
                <div class="alert-group">
                    <span class="alert-label">Societes sans associe</span>
                    <?php foreach ($sansAssocie as $s): ?>
                        <a class="alert-item" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>">
                            <span class="mdi mdi-account-remove" style="color:var(--danger)"></span>
                            <?= e($s['societe_raison_sociale']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($sansContrat): ?>
                <div class="alert-group">
                    <span class="alert-label">Societes sans contrat</span>
                    <?php foreach ($sansContrat as $s): ?>
                        <a class="alert-item" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>">
                            <span class="mdi mdi-file-remove" style="color:var(--warning)"></span>
                            <?= e($s['societe_raison_sociale']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($sansDocuments): ?>
                <div class="alert-group">
                    <span class="alert-label">Dossiers complets sans documents generes</span>
                    <?php foreach ($sansDocuments as $s): ?>
                        <a class="alert-item" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>">
                            <span class="mdi mdi-file-document-remove-outline" style="color:var(--info)"></span>
                            <?= e($s['societe_raison_sociale']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($cinExpire): ?>
                <div class="alert-group">
                    <span class="alert-label">CIN associe expiree</span>
                    <?php foreach ($cinExpire as $a): ?>
                        <a class="alert-item" href="<?= e(app_url('societe', ['id' => (int) $a['societe_id']])) ?>">
                            <span class="mdi mdi-card-account-details-outline" style="color:var(--danger)"></span>
                            <?= e($a['associe_nom_complet']) ?> (<?= e($a['societe_raison_sociale']) ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($expirants): ?>
                <div class="alert-group">
                    <span class="alert-label">Contrats expirant dans < 30 jours</span>
                    <?php foreach ($expirants as $c): ?>
                        <a class="alert-item" href="<?= e(app_url('contrats')) ?>">
                            <span class="mdi mdi-clock-alert" style="color:var(--warning)"></span>
                            <?= e($c['societe_raison_sociale']) ?> — <?= e($c['contrat_type']) ?> (<?= e($c['contrat_date_fin']) ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</section>
<?php endif; ?>

<?php if ($echeances): ?>
<section>
    <article class="card">
        <div class="section-header">
            <h2><span class="mdi mdi-calendar-clock" style="margin-right:6px;color:var(--warning)"></span>Echeances (90 jours)</h2>
            <a class="btn btn-info" href="<?= e(app_url('contrats')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <div class="timeline-list">
            <?php foreach ($echeances as $e):
                $j = (int) $e['jours_restants'];
                $class = $j <= 15 ? 'urgent' : ($j <= 30 ? 'warning' : 'normal');
                $icon = $j <= 15 ? 'mdi-alert-circle' : ($j <= 30 ? 'mdi-clock-alert' : 'mdi-calendar-clock');
            ?>
                <div class="timeline-item <?= $class ?>">
                    <span class="mdi <?= $icon ?>" style="color:<?= $j <= 15 ? 'var(--danger)' : ($j <= 30 ? 'var(--warning)' : 'var(--success)') ?>"></span>
                    <div class="timeline-content">
                        <strong><?= e($e['societe_raison_sociale']) ?></strong>
                        <span><?= e($e['contrat_type']) ?></span>
                    </div>
                    <span class="timeline-date"><?= e($e['contrat_date_fin']) ?> (J-<?= $j ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php endif; ?>

<!-- Activity feed -->
<section>
    <article class="card">
        <div class="section-header">
            <h2>Activite recente</h2>
            <a class="btn btn-info" href="<?= e(app_url('societes')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <?php if (!$activiteRecente): ?>
            <p class="table-empty">Aucune activite recente.</p>
        <?php else: ?>
            <div class="activity-feed">
                <?php foreach ($activiteRecente as $a):
                    $type = $a['type'];
                    $icon = $type === 'societe' ? 'mdi-domain' : ($type === 'contrat' ? 'mdi-file-sign' : 'mdi-account');
                    $label = $type === 'societe' ? 'Societe creee' : ($type === 'contrat' ? 'Contrat ajoute' : 'Associe ajoute');
                    $url = app_url('societe', ['id' => (int) $a['ref_id']]);
                    $dt = date('d/m/Y H:i', strtotime($a['created_at']));
                ?>
                    <a class="activity-item" href="<?= e($url) ?>">
                        <span class="activity-icon <?= $type ?>"><span class="mdi <?= $icon ?>"></span></span>
                        <span class="activity-text"><strong><?= e($label) ?></strong> <?= e($a['libelle'] ?? '-') ?></span>
                        <span class="activity-meta"><?= $dt ?><br><span class="meta-ago"><?= time_ago($a['created_at']) ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<!-- Documents generes -->
<section>
    <article class="card">
        <div class="section-header">
            <h2><span class="mdi mdi-file-document-check" style="margin-right:6px;color:var(--success)"></span>Derniers documents generes</h2>
            <a class="btn btn-info" href="<?= e(app_url('documents')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <?php if (!$documentsRecents): ?>
            <p class="table-empty">Aucun document genere.</p>
        <?php else: ?>
            <div class="activity-feed">
                <?php foreach ($documentsRecents as $d):
                    $ddt = date('d/m/Y H:i', strtotime($d['created_at']));
                    $dsize = $d['taille_ko'] ? number_format((float) $d['taille_ko'], 1, ',', ' ') . ' Ko' : '';
                    $dvalide = (int) ($d['valide'] ?? 0);
                ?>
                    <a class="activity-item" href="<?= e(app_url('societe', ['id' => (int) $d['societe_id']])) ?>">
                        <span class="activity-icon document"><span class="mdi mdi-file-document-outline"></span></span>
                        <span class="activity-text">
                            <strong><?= e($d['doc_type']) ?></strong> <?= e($d['societe_raison_sociale']) ?>
                            <?php if ($dsize): ?><span class="doc-size"><?= $dsize ?></span><?php endif; ?>
                            <?php if (!$dvalide): ?><span class="doc-pending">En attente</span><?php endif; ?>
                        </span>
                        <span class="activity-meta"><?= $ddt ?><br><span class="meta-ago"><?= time_ago($d['created_at']) ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<!-- Validation documents -->
<?php if ($isConnected): ?>
<section>
    <article class="card">
        <div class="section-header">
            <h2><span class="mdi mdi-check-circle" style="margin-right:6px;color:var(--primary)"></span>Validation des documents</h2>
            <a class="btn btn-info" href="<?= e(app_url('documents')) ?>"><span class="mdi mdi-eye"></span> Gerer</a>
        </div>
        <div class="stats small" style="grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem">
            <article class="stat">
                <span>Valides</span>
                <strong style="color:var(--success)"><?= $docsValides ?></strong>
            </article>
            <article class="stat">
                <span>En attente</span>
                <strong style="color:var(--warning)"><?= $docsEnAttente ?></strong>
            </article>
        </div>
        <?php if ($docsAVerifier): ?>
        <div class="activity-feed">
            <?php foreach ($docsAVerifier as $dv):
                $dvdt = date('d/m/Y H:i', strtotime($dv['created_at']));
            ?>
                <a class="activity-item" href="<?= e(app_url('societe', ['id' => (int) $dv['societe_id']])) ?>">
                    <span class="activity-icon document"><span class="mdi mdi-file-document-outline"></span></span>
                    <span class="activity-text"><strong><?= e($dv['doc_type']) ?></strong> <?= e($dv['societe_raison_sociale']) ?></span>
                    <span class="activity-meta"><?= $dvdt ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="table-empty">Tous les documents sont valides.</p>
        <?php endif; ?>
    </article>
</section>
<?php endif; ?>

<!-- Repartition -->
<section class="grid two">
    <article class="card">
        <div class="section-header">
            <h2>Formes juridiques</h2>
        </div>
        <?php if (!$repartitionFormes): ?>
            <p class="table-empty">Aucune donnee disponible.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Forme</th><th style="width:50px">Nb</th><th style="width:40px">%</th></tr></thead>
                <tbody>
                <?php $ci = 0; $formesTotal = array_sum(array_column($repartitionFormes, 'total')); foreach ($repartitionFormes as $r):
                    $dotClass = 'c' . (($ci % 8) + 1); $ci++;
                    $fpct = $formesTotal > 0 ? round(((int) $r['total'] / $formesTotal) * 100) : 0; ?>
                    <tr>
                        <td><span class="repartition-dot <?= $dotClass ?>"></span><span class="repartition-label"><?= e($r['societe_forme_juridique'] ?: '-') ?><span class="mini-bar"><span class="mini-fill" style="width:<?= $fpct ?>%"></span></span></span></td>
                        <td><strong><?= (int) $r['total'] ?></strong></td>
                        <td class="text-muted"><?= $fpct ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-header">
            <h2>Types de contrat</h2>
        </div>
        <?php if (!$repartitionContrats): ?>
            <p class="table-empty">Aucune donnee disponible.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Type</th><th style="width:50px">Nb</th><th style="width:40px">%</th></tr></thead>
                <tbody>
                <?php $ci = 0; $contratsTotal = array_sum(array_column($repartitionContrats, 'total')); foreach ($repartitionContrats as $r):
                    $dotClass = 'c' . (($ci % 8) + 1); $ci++;
                    $cpct = $contratsTotal > 0 ? round(((int) $r['total'] / $contratsTotal) * 100) : 0; ?>
                    <tr>
                        <td><span class="repartition-dot <?= $dotClass ?>"></span><span class="repartition-label"><?= e($r['contrat_type'] ?: '-') ?><span class="mini-bar"><span class="mini-fill" style="width:<?= $cpct ?>%"></span></span></span></td>
                        <td><strong><?= (int) $r['total'] ?></strong></td>
                        <td class="text-muted"><?= $cpct ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>
