<?php

declare(strict_types=1);

$logs = [];
if (($pdo ?? null) instanceof PDO) {
    $q = search_term();
    $actionFilter = $_GET['action'] ?? '';
    $entityFilter = $_GET['entity'] ?? '';
    $userFilter = $_GET['user_id'] ?? '';
    $dateFrom = $_GET['from'] ?? '';
    $dateTo = $_GET['to'] ?? '';

    // Non-admin users can only see their own activity
    $currentUser = current_user();
    $isAdmin = $currentUser && in_array((int) $currentUser['role_id'], [1, 2], true);
    if (!$isAdmin && $currentUser) {
        $userFilter = (string) $currentUser['id'];
    }

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(user_nom LIKE :q1 OR entity_label LIKE :q2 OR action LIKE :q3 OR entity_type LIKE :q4)';
        $params['q1'] = like_term($q);
        $params['q2'] = like_term($q);
        $params['q3'] = like_term($q);
        $params['q4'] = like_term($q);
    }
    if ($actionFilter !== '') {
        $where[] = 'action = :action';
        $params['action'] = $actionFilter;
    }
    if ($entityFilter !== '') {
        $where[] = 'entity_type = :entity';
        $params['entity'] = $entityFilter;
    }
    if ($userFilter !== '') {
        $where[] = 'user_id = :uid';
        $params['uid'] = (int) $userFilter;
    }
    if ($dateFrom !== '') {
        $where[] = 'created_at >= :dfrom';
        $params['dfrom'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $where[] = 'created_at <= :dto';
        $params['dto'] = $dateTo . ' 23:59:59';
    }

    $sql = 'SELECT * FROM activity_logs';
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 500';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Distinct values for filters
    if (!$isAdmin && $currentUser) {
        $actionTypes = $pdo->prepare('SELECT DISTINCT action FROM activity_logs WHERE user_id = :uid ORDER BY action');
        $actionTypes->execute(['uid' => $currentUser['id']]);
        $actionTypes = $actionTypes->fetchAll(\PDO::FETCH_COLUMN);
        $entityTypes = $pdo->prepare('SELECT DISTINCT entity_type FROM activity_logs WHERE user_id = :uid ORDER BY entity_type');
        $entityTypes->execute(['uid' => $currentUser['id']]);
        $entityTypes = $entityTypes->fetchAll(\PDO::FETCH_COLUMN);
        $userList = [[ 'user_id' => $currentUser['id'], 'user_nom' => $currentUser['nom_complet'] ?? $currentUser['den_ste'] ]];
    } else {
        $actionTypes = $pdo->query('SELECT DISTINCT action FROM activity_logs ORDER BY action')->fetchAll(\PDO::FETCH_COLUMN);
        $entityTypes = $pdo->query('SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type')->fetchAll(\PDO::FETCH_COLUMN);
        $userList = $pdo->query('SELECT DISTINCT l.user_id, l.user_nom FROM activity_logs l WHERE l.user_id IS NOT NULL ORDER BY l.user_nom')->fetchAll();
    }

    // Stats
    $uidFilter = (!$isAdmin && $currentUser) ? (int) $currentUser['id'] : null;
    if ($uidFilter !== null) {
        $stToday = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE() AND user_id = :uid");
        $stToday->execute(['uid' => $uidFilter]);
        $totalToday = $stToday->fetchColumn();

        $stWeek = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND user_id = :uid");
        $stWeek->execute(['uid' => $uidFilter]);
        $totalWeek = $stWeek->fetchColumn();
    } else {
        $totalToday = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $totalWeek = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();
    }
}

$pageTitle = 'Journal d\'activite';
$pageSubtitle = $isAdmin ? 'Traçabilite de toutes les actions utilisateurs' : 'Votre activite recente';

$actionLabels = [
    'connexion' => 'Connexion',
    'deconnexion' => 'Deconnexion',
    'create' => 'Creation',
    'update' => 'Modification',
    'delete' => 'Suppression',
    'generate' => 'Generation',
    'validate' => 'Validation',
    'upload' => 'Upload',
    'rename' => 'Renommage',
    'export' => 'Export',
    'reset' => 'Reinitialisation',
    'cancel' => 'Annulation',
    'restore' => 'Restauration',
    'bulk_rename' => 'Renommage groupe',
    'bulk_delete' => 'Suppression groupe',
    'ai_suggest' => 'Suggestion IA',
];
$actionIcons = [
    'connexion' => 'login',
    'deconnexion' => 'logout',
    'create' => 'add_circle',
    'update' => 'edit',
    'delete' => 'delete',
    'generate' => 'description',
    'validate' => 'check_circle',
    'upload' => 'upload_file',
    'rename' => 'drive_file_rename_outline',
    'export' => 'file_download',
    'reset' => 'restart_alt',
    'cancel' => 'cancel',
    'restore' => 'restore',
    'bulk_rename' => 'drive_file_rename_outline',
    'bulk_delete' => 'delete_sweep',
    'ai_suggest' => 'smart_toy',
];
?>
<section class="stats">
    <article class="stat">
        <span class="stat-value"><?= $totalToday ?? 0 ?></span>
        <span class="stat-label">Actions aujourd'hui</span>
    </article>
    <article class="stat">
        <span class="stat-value"><?= $totalWeek ?? 0 ?></span>
        <span class="stat-label">Cette semaine</span>
    </article>
    <article class="stat">
        <span class="stat-value"><?= count($logs) ?></span>
        <span class="stat-label">Affichés</span>
    </article>
</section>

<section class="card">
    <form method="get" class="inline-form" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1rem">
        <input type="hidden" name="page" value="activite">
        <input name="q" placeholder="Rechercher..." value="<?= e(search_term()) ?>" style="flex:1;min-width:150px;padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
        <select name="action" style="padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
            <option value="">Toutes actions</option>
            <?php foreach ($actionTypes as $a): ?>
                <option value="<?= e($a) ?>" <?= $actionFilter === $a ? 'selected' : '' ?>><?= e($actionLabels[$a] ?? $a) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="entity" style="padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
            <option value="">Toutes entites</option>
            <?php foreach ($entityTypes as $e): ?>
                <option value="<?= e($e) ?>" <?= $entityFilter === $e ? 'selected' : '' ?>><?= e($e) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($isAdmin || !$currentUser): ?>
        <select name="user_id" style="padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
            <option value="">Tous utilisateurs</option>
            <?php foreach ($userList as $u): ?>
                <option value="<?= (int) $u['user_id'] ?>" <?= $userFilter === (string) $u['user_id'] ? 'selected' : '' ?>><?= e((string) $u['user_nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input type="date" name="from" value="<?= e($dateFrom) ?>" style="padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
        <input type="date" name="to" value="<?= e($dateTo) ?>" style="padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
        <button type="submit" class="btn btn-info"><span class="material-symbols-outlined">search</span> Filtrer</button>
        <a class="btn btn-cancel" href="<?= e(app_url('activite')) ?>"><span class="material-symbols-outlined">clear</span> Effacer</a>
    </form>

    <?php if (!empty($logs)): ?>
        <div class="table-scroll">
        <table data-sortable>
            <thead>
                <tr>
                    <th data-col="date">Date</th>
                    <th data-col="user">Utilisateur</th>
                    <th data-col="action">Action</th>
                    <th data-col="entity">Entite</th>
                    <th data-col="label">Description</th>
                    <th data-col="ip">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="font-size:0.75rem;white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
                    <td><strong><?= e((string) ($log['user_nom'] ?? '—')) ?></strong></td>
                    <td>
                        <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px;color:var(--primary)"><?= e($actionIcons[$log['action']] ?? 'radio_button_unchecked') ?></span>
                        <?= e($actionLabels[$log['action']] ?? $log['action']) ?>
                    </td>
                    <td style="font-size:0.8125rem"><?= e($log['entity_type']) ?></td>
                    <td style="font-size:0.8125rem">
                        <?php if ($log['entity_label']): ?>
                            <?= e($log['entity_label']) ?>
                        <?php elseif ($log['details']): ?>
                            <code style="font-size:0.7rem;color:var(--text-secondary)"><?= e(mb_substr($log['details'], 0, 80)) ?><?= mb_strlen((string) $log['details']) > 80 ? '...' : '' ?></code>
                        <?php else: ?>
                            <span class="help-text">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.7rem;color:var(--text-secondary)"><?= e($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
        <p class="table-empty">Aucune activite enregistree.</p>
    <?php endif; ?>
</section>
