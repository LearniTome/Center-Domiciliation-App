<?php

declare(strict_types=1);

$pdo = $pdo ?? null;
$isConnected = $pdo instanceof PDO;

// Auto-generate system notifications on dashboard (once per session)
if ($isConnected && empty($_SESSION['_auto_notif_run'])) {
    $user = current_user();
    if ($user && (int) ($user['role_id'] ?? 0) === 1) {
        generate_auto_notifications($pdo, (int) $user['id']);
    }
    $_SESSION['_auto_notif_run'] = true;
}

// --- Stats ---
$totalSocietes = 0;
$contratsActifs = 0;
$contratsResilies = 0;
$collaborateursCount = 0;
$dossiersComplets = 0;
$revenuMensuel = 0;
$creationsMois = 0;
$cessionsCount = 0;
$pvAgoCount = 0;
$suiviCount = 0;

$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$userId = (!$isAdmin && $user) ? (int) $user['id'] : null;

if ($isConnected) {
    if ($userId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM societes WHERE created_by = :uid');
        $stmt->execute(['uid' => $userId]);
        $totalSocietes = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrats c INNER JOIN societes s ON s.id = c.societe_id WHERE c.contrat_statut = 'actif' AND s.created_by = :uid");
        $stmt->execute(['uid' => $userId]);
        $contratsActifs = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrats c INNER JOIN societes s ON s.id = c.societe_id WHERE c.contrat_statut = 'resilie' AND s.created_by = :uid");
        $stmt->execute(['uid' => $userId]);
        $contratsResilies = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(c.contrat_loyer_ttc), 0) FROM contrats c INNER JOIN societes s ON s.id = c.societe_id WHERE c.contrat_statut = 'actif' AND s.created_by = :uid");
        $stmt->execute(['uid' => $userId]);
        $revenuMensuel = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM societes WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND created_by = :uid");
        $stmt->execute(['uid' => $userId]);
        $creationsMois = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM societes s
            WHERE s.created_by = :uid
            AND EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
            AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
        ");
        $stmt->execute(['uid' => $userId]);
        $dossiersComplets = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM collaborateurs WHERE created_by = :uid');
        $stmt->execute(['uid' => $userId]);
        $collaborateursCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cessions WHERE created_by = :uid');
        $stmt->execute(['uid' => $userId]);
        $cessionsCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pv_ago WHERE created_by = :uid');
        $stmt->execute(['uid' => $userId]);
        $pvAgoCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT e.cession_id) FROM cession_suivi_etapes e INNER JOIN cessions c ON c.id = e.cession_id WHERE c.created_by = :uid');
        $stmt->execute(['uid' => $userId]);
        $suiviCount = (int) $stmt->fetchColumn();
    } else {
        $totalSocietes = (int) $pdo->query('SELECT COUNT(*) FROM societes')->fetchColumn();
        $contratsActifs = (int) $pdo->query("SELECT COUNT(*) FROM contrats WHERE contrat_statut = 'actif'")->fetchColumn();
        $contratsResilies = (int) $pdo->query("SELECT COUNT(*) FROM contrats WHERE contrat_statut = 'resilie'")->fetchColumn();
        $revenuMensuel = (float) $pdo->query("SELECT COALESCE(SUM(contrat_loyer_ttc), 0) FROM contrats WHERE contrat_statut = 'actif'")->fetchColumn();
        $creationsMois = (int) $pdo->query("SELECT COUNT(*) FROM societes WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
        $collaborateursCount = (int) $pdo->query("SELECT COUNT(*) FROM collaborateurs")->fetchColumn();

        $cessionsCount = (int) $pdo->query("SELECT COUNT(*) FROM cessions")->fetchColumn();
        $pvAgoCount = (int) $pdo->query("SELECT COUNT(*) FROM pv_ago")->fetchColumn();
        $suiviCount = (int) $pdo->query("SELECT COUNT(DISTINCT cession_id) FROM cession_suivi_etapes")->fetchColumn();

        $dossiersComplets = (int) $pdo->query("
            SELECT COUNT(*) FROM societes s
            WHERE EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
            AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
        ")->fetchColumn();
    }
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
    if ($userId !== null) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM contrats c
            INNER JOIN societes s ON s.id = c.societe_id
            WHERE c.contrat_statut = 'actif'
              AND c.contrat_date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND s.created_by = :uid
        ");
        $stmt->execute(['uid' => $userId]);
        $renouvelerCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM contrats c
            INNER JOIN societes s ON s.id = c.societe_id
            WHERE c.contrat_statut = 'resilie'
              AND MONTH(c.created_at) = MONTH(CURDATE())
              AND YEAR(c.created_at) = YEAR(CURDATE())
              AND s.created_by = :uid
        ");
        $stmt->execute(['uid' => $userId]);
        $resiliesMois = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM societes s
            WHERE s.created_by = :uid
              AND NOT (EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
                   AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id))
        ");
        $stmt->execute(['uid' => $userId]);
        $incompletsCount = (int) $stmt->fetchColumn();
    } else {
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
        $incompletsCount = (int) $pdo->query("
            SELECT COUNT(*) FROM societes s
            WHERE NOT (EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
                   AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id))
        ")->fetchColumn();
    }
    $collabMainType = (string) $pdo->query("
        SELECT collaborateur_type FROM collaborateurs
        GROUP BY collaborateur_type ORDER BY COUNT(*) DESC LIMIT 1
    ")->fetchColumn();
}
$templateCount = is_dir(__DIR__ . '/../../templates')
    ? count(array_diff(scandir(__DIR__ . '/../../templates'), ['.', '..']))
    : 0;
$refTableCount = count(load_defaults());

// Online users & page stats
$onlineUsers = $isConnected ? get_online_users($pdo, 5) : [];
$mostVisitedPages = $isConnected && $isAdmin ? get_most_visited_pages($pdo, 8) : [];

$sf = $userId !== null ? " AND s.created_by = $userId" : '';

// --- Repartition ---
$repartitionFormes = [];
$repartitionContrats = [];
if ($isConnected) {
    if ($userId !== null) {
        $stmt = $pdo->prepare("
            SELECT societe_forme_juridique, COUNT(*) AS total
            FROM societes WHERE societe_forme_juridique != '' AND created_by = :uid
            GROUP BY societe_forme_juridique ORDER BY total DESC
        ");
        $stmt->execute(['uid' => $userId]);
        $repartitionFormes = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT c.contrat_type, COUNT(*) AS total
            FROM contrats c INNER JOIN societes s ON s.id = c.societe_id
            WHERE s.created_by = :uid
            GROUP BY c.contrat_type ORDER BY total DESC
        ");
        $stmt->execute(['uid' => $userId]);
        $repartitionContrats = $stmt->fetchAll();
    } else {
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
}

$donutSliceColors = ['var(--primary)', 'var(--success)', 'var(--warning)', 'var(--danger)', 'var(--info)', '#fd79a8', '#00cec9', '#e17055'];

function buildDonutGradient(array $items, string $countKey, array $colors): string {
    if (!$items) return 'conic-gradient(var(--line) 0% 100%)';
    $parts = [];
    $cur = 0;
    $total = array_sum(array_column($items, $countKey));
    foreach ($items as $i => $item) {
        $pct = $total > 0 ? ((int) $item[$countKey] / $total) * 100 : 0;
        $color = $colors[$i % count($colors)];
        $end = min(100, $cur + $pct);
        if ($end > $cur) $parts[] = "$color $cur% $end%";
        $cur = $end;
    }
    return $parts ? 'conic-gradient(' . implode(', ', $parts) . ')' : 'conic-gradient(var(--line) 0% 100%)';
}

$formesGradient = buildDonutGradient($repartitionFormes, 'total', $donutSliceColors);
$contratsGradient = buildDonutGradient($repartitionContrats, 'total', $donutSliceColors);

// --- Timeline (echeances 0-90 jours) ---
$echeances = [];
if ($isConnected) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.contrat_type, c.contrat_date_fin, s.societe_raison_sociale, s.id AS societe_id,
               DATEDIFF(c.contrat_date_fin, CURDATE()) AS jours_restants
        FROM contrats c
        INNER JOIN societes s ON s.id = c.societe_id
        WHERE c.contrat_statut = 'actif'
          AND c.contrat_date_fin IS NOT NULL
          AND c.contrat_date_fin >= CURDATE()
          AND c.contrat_date_fin <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
          $sf
        ORDER BY c.contrat_date_fin
        LIMIT 8
    ");
    $stmt->execute();
    $echeances = $stmt->fetchAll();
}

// --- Alertes ---
$sansAssocie = [];
$sansContrat = [];
$expirants = [];
$sansDocuments = [];
$cinExpire = [];
$alerteCount = 0;

if ($isConnected) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.societe_raison_sociale FROM societes s
        LEFT JOIN associes a ON a.societe_id = s.id
        WHERE a.id IS NULL
        $sf
        ORDER BY s.societe_raison_sociale LIMIT 10
    ");
    $stmt->execute();
    $sansAssocie = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT s.id, s.societe_raison_sociale FROM societes s
        LEFT JOIN contrats c ON c.societe_id = s.id
        WHERE c.id IS NULL
        $sf
        ORDER BY s.societe_raison_sociale LIMIT 10
    ");
    $stmt->execute();
    $sansContrat = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT c.id, c.contrat_type, c.contrat_date_fin, s.societe_raison_sociale
        FROM contrats c
        INNER JOIN societes s ON s.id = c.societe_id
        WHERE c.contrat_statut = 'actif'
          AND c.contrat_date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          $sf
        ORDER BY c.contrat_date_fin LIMIT 10
    ");
    $stmt->execute();
    $expirants = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT s.id, s.societe_raison_sociale FROM societes s
        WHERE EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
          AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
          AND NOT EXISTS (SELECT 1 FROM documents_generes d WHERE d.societe_id = s.id)
        $sf
        ORDER BY s.societe_raison_sociale LIMIT 10
    ");
    $stmt->execute();
    $sansDocuments = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT a.associe_nom_complet, s.societe_raison_sociale, s.id AS societe_id, a.associe_date_validite_cin
        FROM associes a
        INNER JOIN societes s ON s.id = a.societe_id
        WHERE a.associe_date_validite_cin IS NOT NULL
          AND a.associe_date_validite_cin < CURDATE()
          $sf
        ORDER BY a.associe_date_validite_cin LIMIT 10
    ");
    $stmt->execute();
    $cinExpire = $stmt->fetchAll();
}

$alerteCount = count($sansAssocie) + count($sansContrat) + count($expirants) + count($sansDocuments) + count($cinExpire);
$hasAlerts = $alerteCount > 0;

// --- Activite recente ---
$collabActivity = [];
if ($isConnected) {
    if ($userId !== null) {
        $stmt = $pdo->prepare('SELECT * FROM activity_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10');
        $stmt->execute(['uid' => $userId]);
        $collabActivity = $stmt->fetchAll();
    } else {
        $collabActivity = $pdo->query("
            SELECT * FROM activity_logs
            ORDER BY created_at DESC LIMIT 10
        ")->fetchAll();
    }
}

// --- Fil d'activite ---
$activiteRecente = [];
if ($isConnected) {
    if ($userId !== null) {
        $stmt = $pdo->prepare("
            (SELECT 'societe' AS type, id, societe_raison_sociale AS libelle, id AS ref_id, created_at FROM societes WHERE created_by = :uid)
            UNION ALL
            (SELECT 'contrat', c.id, s.societe_raison_sociale, c.societe_id, c.created_at
             FROM contrats c JOIN societes s ON s.id = c.societe_id WHERE s.created_by = :uid2)
            UNION ALL
            (SELECT 'associe', a.id, s.societe_raison_sociale, a.societe_id, a.created_at
             FROM associes a JOIN societes s ON s.id = a.societe_id WHERE s.created_by = :uid3)
            ORDER BY created_at DESC LIMIT 3
        ");
        $stmt->execute(['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId]);
        $activiteRecente = $stmt->fetchAll();
    } else {
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
}

// --- Documents generes ---
$documentsRecents = [];
if ($isConnected) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.doc_type, d.created_at, d.taille_ko, d.valide, s.societe_raison_sociale, s.id AS societe_id
        FROM documents_generes d
        INNER JOIN societes s ON s.id = d.societe_id
        WHERE 1=1
        $sf
        ORDER BY d.created_at DESC LIMIT 5
    ");
    $stmt->execute();
    $documentsRecents = $stmt->fetchAll();
}

// --- Validation documents ---
$docsValides = 0;
$docsEnAttente = 0;
$docsTotal = 0;
$valPct = 0;
$docsAVerifier = [];
if ($isConnected) {
    if ($userId !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents_generes d INNER JOIN societes s ON s.id = d.societe_id WHERE d.valide = 1 AND s.created_by = :uid");
        $stmt->execute(['uid' => $userId]);
        $docsValides = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents_generes d INNER JOIN societes s ON s.id = d.societe_id WHERE d.valide = 0 AND s.created_by = :uid");
        $stmt->execute(['uid' => $userId]);
        $docsEnAttente = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("
            SELECT d.id, d.doc_type, d.created_at, d.valide, d.societe_id, s.societe_raison_sociale
            FROM documents_generes d
            INNER JOIN societes s ON s.id = d.societe_id
            WHERE d.valide = 0
              AND s.created_by = :uid
            ORDER BY d.created_at DESC LIMIT 10
        ");
        $stmt->execute(['uid' => $userId]);
        $docsAVerifier = $stmt->fetchAll();
    } else {
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
    $docsTotal = $docsValides + $docsEnAttente;
    $valPct = $docsTotal > 0 ? round(($docsValides / $docsTotal) * 100) : 0;
}
?>
<p class="dash-greeting" id="dash-greeting">👋 Bon retour, <?= e($user['nom_complet'] ?? '—') ?></p>

<?php if ($hasAlerts): ?>
<section class="dash-alert-bar" id="dash-alert-bar">
    <span class="material-symbols-outlined alert-bar-icon">warning</span>
    <span><?= $alerteCount ?> point<?= $alerteCount > 1 ? 's' : '' ?> necessitant attention</span>
</section>
<?php endif; ?>

<script>
(function(){
    var header = document.querySelector('.page-header');
    if (!header) return;
    // Move greeting into top-bar left, replace duplicate name/role
    var greeting = document.querySelector('.dash-greeting');
    var topBarLeft = document.querySelector('.top-bar-left');
    if (greeting && topBarLeft) {
        var nameEl = topBarLeft.querySelector('.top-bar-user');
        var roleEl = topBarLeft.querySelector('.top-bar-role');
        if (nameEl) nameEl.style.display = 'none';
        if (roleEl) roleEl.style.display = 'none';
        greeting.style.margin = '0 0 0 6px';
        greeting.style.fontSize = '0.72rem';
        greeting.style.display = 'inline';
        greeting.style.fontWeight = '600';
        greeting.style.color = 'var(--text-secondary)';
        topBarLeft.appendChild(greeting);
    }
    // Move alert bar into header actions
    var bar = document.getElementById('dash-alert-bar');
    if (bar) {
        var actions = header.querySelector('.table-actions');
        if (!actions) {
            actions = document.createElement('div');
            actions.className = 'table-actions';
            header.appendChild(actions);
        }
        actions.appendChild(bar);
    }
})();
</script>

<!-- Quick actions -->
<section class="dash-actions">
    <?php if ($isAdmin): ?>
    <a class="dash-action dash-action-new" href="<?= e(app_url('creation')) ?>">
        <span class="material-symbols-outlined">add_circle</span>
        <strong>Creer un dossier</strong>
        <small><?= $incompletsCount ?> dossiers incomplets</small>
    </a>
    <a class="dash-action dash-action-cession" href="<?= e(app_url('cession')) ?>">
        <span class="material-symbols-outlined">transfer_within_a_station</span>
        <strong>Ajouter une cession</strong>
        <small><?= $cessionsCount ?> existantes</small>
    </a>
    <a class="dash-action dash-action-pv" href="<?= e(app_url('pv_ago_wizard')) ?>">
        <span class="material-symbols-outlined">groups</span>
        <strong>Remplir un PV AGO</strong>
        <small><?= $pvAgoCount ?> existants</small>
    </a>
    <a class="dash-action dash-action-suivi" href="<?= e(app_url('cession_suivi')) ?>">
        <span class="material-symbols-outlined">checklist</span>
        <strong>Suivi administratif</strong>
        <small><?= $suiviCount ?> dossiers</small>
    </a>
    <?php endif; ?>
    <?php if (has_permission('collaborateurs.create')): ?>
    <a class="dash-action dash-action-collab" href="<?= e(app_url('collaborateur')) ?>">
        <span class="material-symbols-outlined">person_add</span>
        <strong>Collaborateur</strong>
        <small><?= $collaborateursCount ?> existants</small>
    </a>
    <?php endif; ?>
    <?php if (has_permission('templates.edit')): ?>
    <a class="dash-action dash-action-tpl" href="<?= e(app_url('templates', ['action' => 'editeur'])) ?>">
        <span class="material-symbols-outlined">edit_note</span>
        <strong>Editeur template</strong>
        <small><?= $templateCount ?> documents</small>
    </a>
    <?php endif; ?>
    <?php if (has_permission('configuration.view')): ?>
    <a class="dash-action dash-action-cfg" href="<?= e(app_url('configuration')) ?>">
        <span class="material-symbols-outlined">settings</span>
        <strong>Configuration</strong>
        <small><?= $refTableCount ?> tables</small>
    </a>
    <?php endif; ?>
</section>

<!-- Metrics -->
<section class="dash-metrics">
    <a class="dash-metric" href="<?= e(app_url('societes')) ?>">
        <div class="dm-icon dm-icon-soc"><span class="material-symbols-outlined">business</span></div>
        <div class="dm-body">
            <span class="dm-label">Societes</span>
            <strong class="dm-value"><?= $totalSocietes ?></strong>
            <span class="dm-delta up">+<?= $creationsMois ?> ce mois</span>
        </div>
    </a>
    <a class="dash-metric" href="<?= e(app_url('contrats')) ?>">
        <div class="dm-icon dm-icon-ctr"><span class="material-symbols-outlined">signature</span></div>
        <div class="dm-body">
            <span class="dm-label">Contrats actifs</span>
            <strong class="dm-value"><?= $contratsActifs ?></strong>
            <span class="dm-delta"><?= $contratsResilies ?> resilies</span>
        </div>
    </a>
    <a class="dash-metric" href="<?= e(app_url('documents')) ?>">
        <div class="dm-icon dm-icon-doc"><span class="material-symbols-outlined">description</span></div>
        <div class="dm-body">
            <span class="dm-label">Documents</span>
            <strong class="dm-value"><?= $docsTotal ?></strong>
            <span class="dm-delta up"><?= $docsValides ?> valides</span>
        </div>
    </a>
    <a class="dash-metric" href="<?= e(app_url('contrats')) ?>">
        <div class="dm-icon dm-icon-rev"><span class="material-symbols-outlined">payments</span></div>
        <div class="dm-body">
            <span class="dm-label">Revenu mensuel</span>
            <strong class="dm-value"><?= number_format($revenuMensuel, 0, ',', ' ') ?> DH</strong>
            <span class="dm-delta"><?= $renouvelerCount ?> a renouveler</span>
        </div>
    </a>
    <a class="dash-metric" href="<?= e(app_url('cessions')) ?>">
        <div class="dm-icon dm-icon-ces"><span class="material-symbols-outlined">transfer_within_a_station</span></div>
        <div class="dm-body">
            <span class="dm-label">Cessions</span>
            <strong class="dm-value"><?= $cessionsCount ?></strong>
            <span class="dm-delta up">en cours</span>
        </div>
    </a>
    <a class="dash-metric" href="<?= e(app_url('pv_ago')) ?>">
        <div class="dm-icon dm-icon-pv"><span class="material-symbols-outlined">groups</span></div>
        <div class="dm-body">
            <span class="dm-label">PV AGO</span>
            <strong class="dm-value"><?= $pvAgoCount ?></strong>
            <span class="dm-delta up">assemblees</span>
        </div>
    </a>
    <a class="dash-metric" href="<?= e(app_url('cession_suivi')) ?>">
        <div class="dm-icon dm-icon-sui"><span class="material-symbols-outlined">checklist</span></div>
        <div class="dm-body">
            <span class="dm-label">Suivi</span>
            <strong class="dm-value"><?= $suiviCount ?></strong>
            <span class="dm-delta up">dossiers</span>
        </div>
    </a>
</section>

<!-- Charts: Ring + 2 Donuts -->
<section class="dash-charts">
    <a class="card card-link" href="<?= e(app_url('generation')) ?>">
        <div class="section-header"><h3>Dossiers complets</h3></div>
        <div class="dash-donut-row">
            <div class="dash-donut" style="background: conic-gradient(var(--success) 0% <?= $pctComplets ?>%, rgba(255,255,255,0.06) <?= $pctComplets ?>% 100%)">
                <span class="dash-donut-center"><?= $pctComplets ?><small>%</small></span>
            </div>
            <div class="dash-dlegend">
                <div class="dash-dli">
                    <span class="legend-dot" style="background:var(--success)"></span>
                    <span class="dli-label">Complets</span>
                    <span class="dli-val"><?= $dossiersComplets ?></span>
                    <span class="dli-pct"><?= $pctComplets ?>%</span>
                </div>
                <div class="dash-dli">
                    <span class="legend-dot" style="background:var(--warning)"></span>
                    <span class="dli-label">Incomplets</span>
                    <span class="dli-val"><?= $dossiersIncomplets ?></span>
                    <span class="dli-pct"><?= 100 - $pctComplets ?>%</span>
                </div>
            </div>
        </div>
    </a>
    <a class="card card-link" href="<?= e(app_url('societes')) ?>">
        <div class="section-header"><h3>Formes juridiques</h3></div>
        <?php if (!$repartitionFormes): ?>
            <p class="table-empty">Aucune donnee</p>
        <?php else:
            $formesTotal = array_sum(array_column($repartitionFormes, 'total')); ?>
            <div class="dash-donut-row">
                <div class="dash-donut" style="background: <?= $formesGradient ?>">
                    <span class="dash-donut-center"><?= $formesTotal ?></span>
                </div>
                <div class="dash-dlegend">
                    <?php $ci = 0; foreach ($repartitionFormes as $r):
                        $fpct = $formesTotal > 0 ? round(((int) $r['total'] / $formesTotal) * 100) : 0; ?>
                        <div class="dash-dli">
                            <span class="legend-dot" style="background:<?= $donutSliceColors[$ci % count($donutSliceColors)] ?>"></span>
                            <span class="dli-label"><?= e($r['societe_forme_juridique'] ?: '-') ?></span>
                            <span class="dli-val"><?= (int) $r['total'] ?></span>
                            <span class="dli-pct"><?= $fpct ?>%</span>
                        </div>
                    <?php $ci++; endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </a>
    <a class="card card-link" href="<?= e(app_url('contrats')) ?>">
        <div class="section-header"><h3>Types de contrat</h3></div>
        <?php if (!$repartitionContrats): ?>
            <p class="table-empty">Aucune donnee</p>
        <?php else:
            $contratsTotal = array_sum(array_column($repartitionContrats, 'total')); ?>
            <div class="dash-donut-row">
                <div class="dash-donut" style="background: <?= $contratsGradient ?>">
                    <span class="dash-donut-center"><?= $contratsTotal ?></span>
                </div>
                <div class="dash-dlegend">
                    <?php $ci = 0; foreach ($repartitionContrats as $r):
                        $cpct = $contratsTotal > 0 ? round(((int) $r['total'] / $contratsTotal) * 100) : 0; ?>
                        <div class="dash-dli">
                            <span class="legend-dot" style="background:<?= $donutSliceColors[$ci % count($donutSliceColors)] ?>"></span>
                            <span class="dli-label"><?= e($r['contrat_type'] ?: '-') ?></span>
                            <span class="dli-val"><?= (int) $r['total'] ?></span>
                            <span class="dli-pct"><?= $cpct ?>%</span>
                        </div>
                    <?php $ci++; endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </a>
</section>

<!-- Alerts + Docs + Timeline -->
<section class="dash-cards" id="alertes-section">
    <?php if ($hasAlerts): ?>
    <article class="card dash-acard">
        <div class="section-header">
            <a class="dash-title-link" href="<?= e(app_url('societes')) ?>"><h4 style="color:var(--warning)"><span class="material-symbols-outlined" style="color:var(--warning)">warning</span> Alertes <span class="dash-badge"><?= $alerteCount ?></span></h4></a>
        </div>
        <table class="dash-table">
            <thead><tr><th>Type</th><th>Societe / Personne</th><th>Detail</th><th class="col-action"></th></tr></thead>
            <tbody>
                <?php foreach ($sansAssocie as $s): ?>
                <tr><td><span class="material-symbols-outlined" style="color:var(--danger)">person_remove</span> Sans associe</td><td><?= e($s['societe_raison_sociale']) ?></td><td>—</td><td class="col-action"><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td></tr>
                <?php endforeach; ?>
                <?php foreach ($sansContrat as $s): ?>
                <tr><td><span class="material-symbols-outlined" style="color:var(--warning)">contract</span> Sans contrat</td><td><?= e($s['societe_raison_sociale']) ?></td><td>—</td><td class="col-action"><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td></tr>
                <?php endforeach; ?>
                <?php foreach ($sansDocuments as $s): ?>
                <tr><td><span class="material-symbols-outlined" style="color:var(--info)">remove_selection</span> Sans documents</td><td><?= e($s['societe_raison_sociale']) ?></td><td>—</td><td class="col-action"><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td></tr>
                <?php endforeach; ?>
                <?php foreach ($cinExpire as $a): ?>
                <tr><td><span class="material-symbols-outlined" style="color:var(--danger)">badge</span> CIN expiree</td><td><?= e($a['associe_nom_complet']) ?></td><td><?= e($a['societe_raison_sociale']) ?> <small style="color:var(--text-muted)">exp. <?= e(format_date($a['associe_date_validite_cin'] ?? null)) ?></small></td><td class="col-action"><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $a['societe_id']])) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td></tr>
                <?php endforeach; ?>
                <?php foreach ($expirants as $c): ?>
                <tr><td><span class="material-symbols-outlined" style="color:var(--warning)">clock</span> Contrat expirant</td><td><?= e($c['societe_raison_sociale']) ?></td><td><?= e($c['contrat_type']) ?> <small style="color:var(--text-muted)">fin <?= e(format_date($c['contrat_date_fin'] ?? null)) ?></small></td><td class="col-action"><a class="btn-icon" href="<?= e(app_url('contrats')) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <a class="dash-title-link" href="<?= e(app_url('documents')) ?>"><h4 style="color:var(--primary)"><span class="material-symbols-outlined" style="color:var(--primary)">description</span> Derniers documents</h4></a>
        </div>
        <?php if (!$documentsRecents): ?>
            <p class="table-empty">Aucun document genere.</p>
        <?php else: ?>
        <table class="dash-table">
            <thead><tr><th>Document</th><th>Societe</th><th>Taille</th><th>Statut</th><th class="col-action"></th></tr></thead>
            <tbody>
            <?php foreach ($documentsRecents as $d):
                $ddt = date('d/m/Y H:i', strtotime($d['created_at']));
                $dsize = $d['taille_ko'] ? number_format((float) $d['taille_ko'], 1, ',', ' ') . ' Ko' : '—';
                $dvalide = (int) ($d['valide'] ?? 0);
            ?>
                <tr>
                    <td><strong><?= e($d['doc_type'] ?? 'Document') ?></strong></td>
                    <td><?= e($d['societe_raison_sociale']) ?></td>
                    <td><small style="color:var(--text-muted)"><?= $dsize ?></small></td>
                    <td><?php if ($dvalide): ?><span class="dash-badge-sm success">Valide</span><?php else: ?><span class="dash-badge-sm warning">En attente</span><?php endif; ?></td>
                    <td class="col-action"><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $d['societe_id']])) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </article>

    <?php if ($echeances): ?>
    <article class="card">
        <div class="section-header">
            <a class="dash-title-link" href="<?= e(app_url('contrats')) ?>"><h4 style="color:var(--warning)"><span class="material-symbols-outlined" style="color:var(--warning)">calendar_clock</span> Echeances</h4></a>
        </div>
        <div class="timeline-list">
            <?php foreach ($echeances as $e):
                $j = (int) $e['jours_restants'];
                $class = $j <= 15 ? 'urgent' : ($j <= 30 ? 'warning' : 'normal');
                $tdotIcon = $j <= 15 ? 'error' : ($j <= 30 ? 'schedule' : 'event');
            ?>
                <div class="timeline-item <?= $class ?>">
                    <span class="timeline-dot <?= $class ?>"><span class="material-symbols-outlined"><?= $tdotIcon ?></span></span>
                    <div class="timeline-content">
                        <strong><?= e($e['societe_raison_sociale']) ?></strong>
                        <span><?= e($e['contrat_type']) ?></span>
                    </div>
                    <span class="timeline-date"><?= e(format_date($e['contrat_date_fin'] ?? null)) ?> <span class="tl-jours">J-<?= $j ?></span></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <?php endif; ?>
</section>

<!-- Activity + Validation -->
<section class="grid two bottom-section">
    <article class="card">
        <div class="section-header">
            <a class="dash-title-link" href="<?= e(app_url('societes')) ?>"><h4 style="color:var(--info)">Activité recente</h4></a>
        </div>
        <?php if (!$activiteRecente): ?>
            <p class="table-empty">Aucune activité recente.</p>
        <?php else: ?>
        <table class="dash-table">
            <thead><tr><th>Action</th><th>Societe</th><th>Date</th><th class="col-action"></th></tr></thead>
            <tbody>
            <?php foreach ($activiteRecente as $a):
                $type = $a['type'];
                $icon = $type === 'societe' ? 'business' : ($type === 'contrat' ? 'signature' : 'person');
                $label = $type === 'societe' ? 'Societe creee' : ($type === 'contrat' ? 'Contrat ajoute' : 'Associe ajoute');
                $url = app_url('societe', ['id' => (int) $a['ref_id']]);
            ?>
                <tr>
                    <td><span class="material-symbols-outlined" style="color:var(--primary);font-size:0.9rem;vertical-align:middle;margin-right:4px"><?= $icon ?></span> <?= e($label) ?></td>
                    <td><?= e($a['libelle'] ?? '-') ?></td>
                    <td><small style="color:var(--text-muted)"><?= time_ago($a['created_at']) ?></small></td>
                    <td class="col-action"><a class="btn-icon" href="<?= e($url) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-header">
            <a class="dash-title-link" href="<?= e(app_url('documents')) ?>"><h4 style="color:var(--success)"><span class="material-symbols-outlined" style="color:var(--success)">fact_check</span> Validation</h4></a>
            <div class="dash-val-ring">
                <div class="donut-sm" style="background: conic-gradient(var(--success) 0% <?= $valPct ?>%, var(--warning) <?= $valPct ?>% 100%)">
                    <span class="donut-sm-value"><?= $valPct ?><small>%</small></span>
                </div>
                <div class="dash-val-text">
                    <strong><?= $docsValides ?></strong> valides
                    <span>sur <?= $docsTotal ?> documents</span>
                </div>
            </div>
        </div>
        <div class="dash-validation">
            <?php if ($docsAVerifier): ?>
            <table class="dash-table">
                <thead><tr><th>Document</th><th>Societe</th><th>Date</th><th class="col-action"></th></tr></thead>
                <tbody>
                <?php foreach ($docsAVerifier as $dv):
                    $dvdt = date('d/m/Y H:i', strtotime($dv['created_at']));
                ?>
                    <tr>
                        <td><span class="material-symbols-outlined" style="color:var(--warning);font-size:0.9rem;vertical-align:middle;margin-right:4px">pending</span> <?= e($dv['doc_type']) ?></td>
                        <td><?= e($dv['societe_raison_sociale']) ?></td>
                        <td><small style="color:var(--text-muted)"><?= $dvdt ?></small></td>
                        <td class="col-action"><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $dv['societe_id']])) ?>"><span class="material-symbols-outlined" style="color:var(--info)">visibility</span></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="table-empty" style="margin-top:0.75rem">Tous les documents sont valides.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php if ($collabActivity): ?>
<section class="grid two bottom-section" style="margin-bottom:0;">
    <article class="card">
        <div class="section-header">
            <a class="dash-title-link" href="<?= e(app_url('activite')) ?>"><h4 style="color:var(--primary)"><span class="material-symbols-outlined" style="color:var(--primary)">work_history</span> Journal d'activite</h4></a>
        </div>
        <table class="dash-table dash-table-journal">
            <thead><tr><th>Utilisateur</th><th>Action</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($collabActivity, 0, 7) as $ca):
                $caAction = (string) ($ca['action'] ?? '');
                $caIcon = match ($caAction) {
                    'create', 'ajout' => 'add_circle',
                    'update' => 'edit',
                    'delete', 'suppression' => 'delete',
                    'connexion' => 'login',
                    'deconnexion' => 'logout',
                    'generate' => 'description',
                    default => 'radio_button_unchecked',
                };
                $caColor = match ($caAction) {
                    'delete', 'suppression' => 'var(--danger)',
                    'create', 'ajout' => 'var(--success)',
                    'connexion' => 'var(--info)',
                    default => 'var(--primary)',
                };
                $caDt = date('d/m/Y H:i', strtotime($ca['created_at']));
            ?>
                <tr>
                    <td><span class="material-symbols-outlined" style="color:<?= $caColor ?>;font-size:0.9rem;vertical-align:middle;margin-right:4px"><?= $caIcon ?></span> <?= e((string) ($ca['user_nom'] ?? '—')) ?></td>
                    <td class="col-action-text"><?= e($caAction) ?><?= $ca['entity_label'] ? ' — ' . e($ca['entity_label']) : '' ?></td>
                    <td><small style="color:var(--text-muted)"><?= $caDt ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
    <article class="card">
        <div class="section-header">
            <h4 style="color:var(--success)"><span class="material-symbols-outlined" style="color:var(--success)">group</span> Utilisateurs en ligne</h4>
        </div>
        <?php if (!empty($onlineUsers)): ?>
            <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($onlineUsers as $_ou): ?>
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--success);flex-shrink:0;"></span>
                    <strong style="font-size:0.85rem;"><?= e($_ou['nom_complet']) ?></strong>
                    <small style="color:var(--text-secondary);font-size:0.7rem;"><?= e($_ou['role_nom'] ?? '') ?></small>
                    <span style="margin-left:auto;font-size:0.7rem;color:var(--text-muted);">
                        <?= e(page_display_name($_ou['current_page'] ?? '')) ?>
                    </span>
                </div>
            <?php endforeach; ?>
            </div>
            <p style="font-size:0.7rem;color:var(--text-muted);margin-top:8px;">
                <?= count($onlineUsers) ?> utilisateur<?= count($onlineUsers) > 1 ? 's' : '' ?> actif<?= count($onlineUsers) > 1 ? 's' : '' ?> (5 min)
            </p>
        <?php else: ?>
            <p class="table-empty">Aucun utilisateur en ligne.</p>
        <?php endif; ?>
    </article>
</section>

<?php if (!empty($mostVisitedPages)): ?>
<section class="card bottom-section">
    <div class="section-header">
        <h4 style="color:var(--info)"><span class="material-symbols-outlined" style="color:var(--info)">trending_up</span> Pages les plus visitées</h4>
        <a class="btn btn-info" href="<?= e(app_url('activite', ['entity' => 'page'])) ?>"><span class="material-symbols-outlined">visibility</span> Voir tout</a>
    </div>
    <table class="dash-table">
        <thead><tr><th>Page</th><th>Visites</th><th>Dernière visite</th></tr></thead>
        <tbody>
        <?php foreach ($mostVisitedPages as $_mvp): ?>
            <tr>
                <td><span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;margin-right:4px;color:var(--primary)">web</span> <?= e(page_display_name($_mvp['page'])) ?></td>
                <td><strong><?= (int) $_mvp['visits'] ?></strong></td>
                <td><small style="color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($_mvp['last_visit'])) ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>

<?php endif; ?>
