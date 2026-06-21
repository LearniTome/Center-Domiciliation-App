<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM cessions WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'cession', (int) $_POST['id']);
        set_flash('success', 'Cession supprimee avec succes.');
        redirect_to('cessions');
    }
}

if (($pdo ?? null) instanceof PDO) {
    $userFilter = '';
    $userParams = [];
    if (!$isAdmin && $user) {
        $userFilter = ' AND c.created_by = :user_id';
        $userParams['user_id'] = (int) $user['id'];
    }
    if ($query !== '') {
        $stmt = $pdo->prepare('
            SELECT c.*, s.societe_raison_sociale, s.societe_dossier AS ste_dossier,
                   (SELECT COUNT(*) FROM cession_parts cp WHERE cp.cession_id = c.id) AS nb_lignes,
                   (SELECT COALESCE(SUM(cp.parts_cedees), 0) FROM cession_parts cp WHERE cp.cession_id = c.id) AS total_parts
            FROM cessions c
            LEFT JOIN societes s ON s.id = c.societe_id
            WHERE (s.societe_raison_sociale LIKE :term OR c.cession_dossier LIKE :term OR c.cession_status LIKE :term)
            ' . $userFilter . '
            ORDER BY c.id DESC
        ');
        $params = ['term' => like_term($query)] + $userParams;
        $stmt->execute($params);
        $cessions = $stmt->fetchAll();
    } else {
        $sql = '
            SELECT c.*, s.societe_raison_sociale, s.societe_dossier AS ste_dossier,
                   (SELECT COUNT(*) FROM cession_parts cp WHERE cp.cession_id = c.id) AS nb_lignes,
                   (SELECT COALESCE(SUM(cp.parts_cedees), 0) FROM cession_parts cp WHERE cp.cession_id = c.id) AS total_parts
            FROM cessions c
            LEFT JOIN societes s ON s.id = c.societe_id
        ';
        if ($userFilter) {
            $sql .= ' WHERE c.created_by = :user_id';
        }
        $sql .= ' ORDER BY c.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($userParams);
        $cessions = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $row): array {
            return [
                $row['id'],
                $row['cession_dossier'] ?? '-',
                $row['societe_raison_sociale'] ?? '-',
                $row['cession_date'] ?? '-',
                $row['cession_status'] ?? 'brouillon',
                $row['nb_lignes'] ?? '0',
                $row['total_parts'] ?? '0',
            ];
        }, $cessions);

        $headers = [
            'ID', 'Dossier', 'Societe', 'Date', 'Statut', 'Nb lignes', 'Total parts',
        ];

        if ($exportType === 'csv') {
            export_csv('cessions.csv', $headers, $rows);
        } else {
            export_excel('cessions.xlsx', $headers, $rows);
        }
    }
} else {
    $cessions = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($cessions) ?> enregistrement(s)</span>
            <div class="table-actions">
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('cessions', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('cessions', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('cessions.import')): ?>
                <?php
                    $importTable = 'cessions';
                    $importPage = 'cessions';
                    $importLabel = 'cession';
                    $importColumnMap = [
                        'Dossier' => 'cession_dossier',
                        'Societe' => 'societe_id',
                        'Date' => 'cession_date',
                        'Statut' => 'cession_status',
                    ];
                    $importDefaults = [
                        'created_by' => ($user['id'] ?? 0),
                    ];
                    require __DIR__ . '/../../../includes/import_excel_section.php';
                ?>
                <?php endif; ?>
                <a class="btn btn-next" href="<?= e(app_url('cession')) ?>"><span class="material-symbols-outlined">note_add</span> Nouvelle cession</a>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="cessions">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par societe, dossier ou statut"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$cessions): ?>
            <p class="table-empty">Aucune cession pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable>
                <thead>
                <tr>
                    <th data-col="dossier">Dossier</th>
                    <th data-col="societe">Societe</th>
                    <th data-col="date">Date</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="lignes">Nb lignes</th>
                    <th data-col="parts">Total parts</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($cessions as $cession): ?>
                    <tr>
                        <td><?= e($cession['cession_dossier'] ?? '-') ?></td>
                        <td><a href="<?= e(app_url('cession_dossier', ['id' => (int) $cession['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500"><?= e($cession['societe_raison_sociale'] ?? '-') ?></a></td>
                        <td><?= e(format_date($cession['cession_date'] ?? null)) ?></td>
                        <td>
                            <?php if (($cession['cession_status'] ?? 'brouillon') === 'finalise'): ?>
                                <span style="color:var(--success);font-weight:500">Valider</span>
                            <?php else: ?>
                                <span style="color:var(--warning);font-weight:500">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) ($cession['nb_lignes'] ?? 0) ?></td>
                        <td><?= (int) ($cession['total_parts'] ?? 0) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon primary" href="<?= e(app_url('cession_dossier', ['id' => (int) $cession['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <?php if (has_permission('cessions.edit')): ?>
                            <a class="btn-icon info" href="<?= e(app_url('cession', ['id' => (int) $cession['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <?php endif; ?>
                            <?php if (has_permission('cessions.delete')): ?>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $cession['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette cession ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
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
