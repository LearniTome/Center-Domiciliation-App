<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$canEdit = has_permission('cessions.edit');

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

// Reference data for quick create
$societesOptions = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, societe_raison_sociale FROM societes ORDER BY societe_raison_sociale ASC');
    while ($row = $stmt->fetch()) {
        $societesOptions[(int)$row['id']] = $row['societe_raison_sociale'];
    }
}
$cessionStatusOptions = ['brouillon', 'Valider'];
$cessionStatusJson = e(json_encode($cessionStatusOptions));

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
        $likeTerm = like_term($query);
        $stmt = $pdo->prepare('
            SELECT c.*, s.societe_raison_sociale, s.societe_dossier AS ste_dossier,
                   (SELECT COUNT(*) FROM cession_parts cp WHERE cp.cession_id = c.id) AS nb_lignes,
                   (SELECT COALESCE(SUM(cp.parts_cedees), 0) FROM cession_parts cp WHERE cp.cession_id = c.id) AS total_parts
            FROM cessions c
            LEFT JOIN societes s ON s.id = c.societe_id
            WHERE (s.societe_raison_sociale LIKE :term1 OR c.cession_dossier LIKE :term2 OR c.cession_status LIKE :term3)
            ' . $userFilter . '
            ORDER BY c.id DESC
        ');
        $params = ['term1' => $likeTerm, 'term2' => $likeTerm, 'term3' => $likeTerm] + $userParams;
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
                format_date($row['cession_date'] ?? null),
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
                <?php if (has_permission('cessions.create')): ?>
                <a class="btn btn-next" href="<?= e(app_url('cession')) ?>"><span class="material-symbols-outlined">add</span> Nouvelle cession</a>
                <?php endif; ?>
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('cessions', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('cessions', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('cessions.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="cessions"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
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
            <table data-col-toggle data-sortable data-table="cessions" data-bulk>
                <thead>
                <tr>
                    <th data-bulk-col><input type="checkbox" data-bulk-select-all title="Tout selectionner"></th>
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
                    <tr data-id="<?= (int) $cession['id'] ?>">
                        <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                        <td<?= $canEdit ? ' data-editable="cession_dossier"' : '' ?>><?= e($cession['cession_dossier'] ?? '-') ?></td>
                        <td><a href="<?= e(app_url('cession_dossier', ['id' => (int) $cession['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500"><?= e($cession['societe_raison_sociale'] ?? '-') ?></a></td>
                        <td<?= $canEdit ? ' data-editable="cession_date"' : '' ?>><?= e(format_date($cession['cession_date'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="cession_status" data-editable-options="' . $cessionStatusJson . '"' : '' ?>>
                            <?php if (($cession['cession_status'] ?? 'brouillon') === 'Valider'): ?>
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
            <template data-row-template>
                <tr data-id="">
                    <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                    <td data-cell="cession_dossier"></td>
                    <td data-cell="societe_raison_sociale"></td>
                    <td data-cell="cession_date"></td>
                    <td data-cell="cession_status"></td>
                    <td data-cell="nb_lignes"></td>
                    <td data-cell="total_parts"></td>
                    <td data-cell-actions>
                        <a class="btn-icon primary" href="" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                        <a class="btn-icon info" href="" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        <form method="post" action="index.php?page=cessions">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="_csrf_token" value="">
                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette cession ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
            </template>
            </div>
        <?php endif; ?>
    </article>

    <?php
    $quickCreateTitle = 'Nouvelle cession';
    $quickCreateTable = 'cessions';
    $quickCreateFields = [
        ['name' => 'societe_id', 'label' => 'Societe', 'type' => 'select', 'options' => $societesOptions, 'required' => true],
        ['name' => 'cession_dossier', 'label' => 'Dossier', 'type' => 'text'],
        ['name' => 'cession_date', 'label' => 'Date', 'type' => 'date'],
        ['name' => 'cession_status', 'label' => 'Statut', 'type' => 'select', 'options' => $cessionStatusOptions],
    ];
    require __DIR__ . '/../../../includes/quick_create_modal.php';

    $bulkEditTitle = 'Modifier les cessions selectionnees';
    $bulkEditTable = 'cessions';
    $bulkEditFields = [
        ['name' => 'cession_status', 'label' => 'Statut', 'type' => 'select', 'options' => $cessionStatusOptions],
    ];
    require __DIR__ . '/../../../includes/bulk_edit_modal.php';
    ?>
</section>

<div class="bulk-toolbar" data-bulk-toolbar>
    <span class="bulk-info"><span class="bulk-count" data-bulk-count>0</span> enregistrement(s) selectionne(s)</span>
    <button class="btn btn-info" type="button" data-bulk-edit-btn><span class="material-symbols-outlined">edit_note</span> Modifier en masse</button>
</div>

<?php require __DIR__ . '/../../../includes/import_excel_modal.php'; ?>
