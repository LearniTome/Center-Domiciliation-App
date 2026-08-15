<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);

// Map modification types to display info
$modTypes = [
    'cession' => ['label' => 'Cession de parts', 'icon' => 'transfer_within_a_station', 'color' => 'var(--info)'],
];

$currentType = $_GET['type'] ?? '';
if ($currentType !== '' && !isset($modTypes[$currentType])) {
    $currentType = '';
}

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';
    $modType = $_POST['mod_type'] ?? 'cession';

    if ($action === 'delete' && $modType === 'cession') {
        $stmt = $pdo->prepare('DELETE FROM cessions WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'cession', (int) $_POST['id']);
        set_flash('success', 'Cession supprimee avec succes.');
        redirect_to('modifications');
    }
}

$rows = [];
if (($pdo ?? null) instanceof PDO) {
    if ($currentType === '' || $currentType === 'cession') {
        $userFilter = '';
        $userParams = [];
        if (!$isAdmin && $user) {
            $userFilter = ' AND c.created_by = :user_id';
            $userParams['user_id'] = (int) $user['id'];
        }

        $sql = '
            SELECT
                \'cession\' AS mod_type,
                c.id,
                c.cession_dossier AS dossier,
                c.cession_date AS mod_date,
                c.cession_status AS mod_status,
                c.created_at,
                c.updated_at,
                s.societe_raison_sociale,
                s.societe_dossier_domiciliation_number AS ste_dossier,
                (SELECT COUNT(*) FROM cession_parts cp WHERE cp.cession_id = c.id) AS nb_lignes
            FROM cessions c
            LEFT JOIN societes s ON s.id = c.societe_id
        ';
        $where = [];
        if ($query !== '') {
            $likeTerm = like_term($query);
            $where[] = '(s.societe_raison_sociale LIKE :term1 OR c.cession_dossier LIKE :term2 OR c.cession_status LIKE :term3)';
            $userParams['term1'] = $likeTerm;
            $userParams['term2'] = $likeTerm;
            $userParams['term3'] = $likeTerm;
        }
        if ($userFilter) {
            $where[] = 'c.created_by = :user_id';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($userParams);
        $rows = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $headers = ['Type', 'ID', 'Dossier', 'Societe', 'Date', 'Statut'];
        $csvRows = array_map(static function (array $row) use ($modTypes): array {
            $type = $modTypes[$row['mod_type']]['label'] ?? $row['mod_type'];
            return [
                $type,
                $row['id'],
                $row['dossier'] ?? '-',
                $row['societe_raison_sociale'] ?? '-',
                $row['mod_date'] ?? '-',
                $row['mod_status'] ?? 'brouillon',
            ];
        }, $rows);

        if ($exportType === 'csv') {
            export_csv('modifications_juridiques.csv', $headers, $csvRows);
        } else {
            export_excel('modifications_juridiques.xlsx', $headers, $csvRows);
        }
    }
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($rows) ?> enregistrement(s)</span>
            <div class="table-actions">
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('modifications', ['export' => 'csv', 'q' => $query, 'type' => $currentType])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('modifications', ['export' => 'xlsx', 'q' => $query, 'type' => $currentType])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <div class="tabs" style="margin-bottom:12px">
            <a class="tab <?= $currentType === '' ? 'active' : '' ?>" href="<?= e(app_url('modifications')) ?>">Toutes</a>
            <?php foreach ($modTypes as $key => $info): ?>
            <a class="tab <?= $currentType === $key ? 'active' : '' ?>" href="<?= e(app_url('modifications', ['type' => $key])) ?>">
                <span class="material-symbols-outlined" style="font-size:1rem"><?= e($info['icon']) ?></span>
                <?= e($info['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="modifications">
            <?php if ($currentType): ?>
            <input type="hidden" name="type" value="<?= e($currentType) ?>">
            <?php endif; ?>
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par societe, dossier ou statut"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('modifications', $currentType ? ['type' => $currentType] : [])) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$rows): ?>
            <p class="table-empty">Aucune modification juridique pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable>
                <thead>
                <tr>
                    <th data-col="type">Type</th>
                    <th data-col="dossier">Dossier</th>
                    <th data-col="societe">Societe</th>
                    <th data-col="date">Date</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="lignes">Nb lignes</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $mt = $modTypes[$row['mod_type']] ?? ['label' => $row['mod_type'], 'icon' => 'swap_horiz', 'color' => 'var(--text-secondary)']; ?>
                    <tr>
                        <td>
                            <span class="badge" style="color:<?= $mt['color'] ?>;border:1px solid <?= $mt['color'] ?>;background:transparent">
                                <span class="material-symbols-outlined" style="font-size:0.75rem"><?= e($mt['icon']) ?></span>
                                <?= e($mt['label']) ?>
                            </span>
                        </td>
                        <td><?= e($row['dossier'] ?? '-') ?></td>
                        <td><a href="<?= e(app_url($row['mod_type'] === 'cession' ? 'cession_dossier' : 'societe', ['id' => (int) $row['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500"><?= e($row['societe_raison_sociale'] ?? '-') ?></a></td>
                        <td><?= e(format_date($row['mod_date'] ?? null)) ?></td>
                        <td>
                            <?php if (($row['mod_status'] ?? 'brouillon') === 'Valider'): ?>
                                <span style="color:var(--success);font-weight:500">Valider</span>
                            <?php else: ?>
                                <span style="color:var(--warning);font-weight:500">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) ($row['nb_lignes'] ?? 0) ?></td>
                        <td class="table-actions">
                            <?php if ($row['mod_type'] === 'cession'): ?>
                            <a class="btn-icon primary" href="<?= e(app_url('cession_dossier', ['id' => (int) $row['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <?php if (has_permission('cessions.edit')): ?>
                            <a class="btn-icon info" href="<?= e(app_url('cession', ['id' => (int) $row['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <?php endif; ?>
                            <?php if (has_permission('cessions.delete')): ?>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="mod_type" value="cession">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette modification ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>
