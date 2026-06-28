<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$canEdit = has_permission('collaborateurs.edit');

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

// Reference data for quick create / bulk edit
$rolesOptions = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, nom FROM roles ORDER BY nom ASC');
    while ($row = $stmt->fetch()) {
        $rolesOptions[(int)$row['id']] = $row['nom'];
    }
}
$collabTypeOptions = ['interne', 'externe-pm', 'externe-pp'];
$collabStatutOptions = ['actif', 'inactif', 'suspendu'];
$collabTypeJson = e(json_encode($collabTypeOptions));
$collabStatutJson = e(json_encode($collabStatutOptions));
$accesJson = e(json_encode(['0', '1']));

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $delStmt = $pdo->prepare('SELECT nom_complet, collaborateur_email FROM collaborateurs WHERE id = :id');
        $delStmt->execute(['id' => (int) $_POST['id']]);
        $delRecord = $delStmt->fetch();
        $stmt = $pdo->prepare('DELETE FROM collaborateurs WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'collaborateur', (int) $_POST['id'], $delRecord['nom_complet'] ?? '');
        set_flash('success', 'Collaborateur supprime avec succes.');
        redirect_to('collaborateurs');
    }
}

$collaborateurs = [];
if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $likeTerm = like_term($query);
        $stmt = $pdo->prepare("
            SELECT c.*, r.nom AS role_nom, r.is_internal
            FROM collaborateurs c
            LEFT JOIN roles r ON r.id = c.role_id
            WHERE c.nom_complet LIKE :term1
               OR c.den_ste LIKE :term2
               OR c.collaborateur_ice LIKE :term3
               OR c.fonction LIKE :term4
               OR r.nom LIKE :term5
            ORDER BY c.id DESC
        ");
        $stmt->execute(['term1' => $likeTerm, 'term2' => $likeTerm, 'term3' => $likeTerm, 'term4' => $likeTerm, 'term5' => $likeTerm]);
        $collaborateurs = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query('
            SELECT c.*, r.nom AS role_nom, r.is_internal
            FROM collaborateurs c
            LEFT JOIN roles r ON r.id = c.role_id
            ORDER BY c.id DESC
        ');
        $collaborateurs = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $c): array {
            return [
                $c['id'],
                $c['role_nom'] ?? '-',
                (static function () use ($c): string {
    $t = $c['collaborateur_type'] ?? '';
    if (in_array($t, ['interne', 'externe-pm', 'externe-pp'], true)) return $t;
    $ds = $c['den_ste'] ?? '';
    return ((int) ($c['can_login'] ?? 0)) ? 'interne' : (($ds && $ds !== 'NULL') ? 'externe-pm' : 'externe-pp');
})(),
                (int) ($c['can_login'] ?? 0) ? 'Oui' : 'Non',
                $c['den_ste'],
                $c['nom_complet'],
                $c['fonction'],
                $c['collaborateur_code'],
                $c['collaborateur_ice'],
                $c['collaborateur_tp'],
                $c['collaborateur_rc'],
                $c['collaborateur_if'],
                $c['collaborateur_tel_fixe'],
                $c['collaborateur_tel_mobile'],
                $c['collaborateur_email'],
                $c['collaborateur_adresse'],
                $c['statut'],
            ];
        }, $collaborateurs);

        $headers = [
            'ID',
            'Role',
            'Type',
            'Acces app',
            'Cabinet',
            'Nom complet',
            'Fonction',
            'Code',
            'ICE',
            'TP',
            'RC',
            'IF',
            'Tel fixe',
            'Tel mobile',
            'Email',
            'Adresse',
            'Statut',
        ];

        if ($exportType === 'csv') {
            export_csv('collaborateurs.csv', $headers, $rows);
        } else {
            export_excel('collaborateurs.xlsx', $headers, $rows);
        }
    }
}
?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($collaborateurs) ?> enregistrement(s)</span>
            <div class="table-actions">
                <?php if (has_permission('collaborateurs.create')): ?>
                <button class="btn btn-next" type="button" data-quick-create-btn><span class="material-symbols-outlined">add</span> Nouveau collaborateur</button>
                <?php endif; ?>
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('collaborateurs', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('collaborateurs', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('collaborateurs.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="collaborateurs"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="collaborateurs">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par nom, role, ICE ou cabinet"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('collaborateurs')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$collaborateurs): ?>
            <p class="table-empty">Aucun collaborateur pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable data-table="collaborateurs" data-bulk>
                <thead>
                <tr>
                    <th data-bulk-col><input type="checkbox" data-bulk-select-all title="Tout selectionner"></th>
                    <th data-col="role">Role</th>
                    <th data-col="type">Type</th>
                    <th data-col="acces">Acces app</th>
                    <th data-col="cabinet">Cabinet</th>
                    <th data-col="nom-complet">Nom complet</th>
                    <th data-col="fonction">Fonction</th>
                    <th data-col="ice">ICE</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="derniere-connexion">Derniere connexion</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($collaborateurs as $c): ?>
                    <tr data-id="<?= (int) $c['id'] ?>">
                        <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                        <td>
                            <?php
                                $isInternal = (int) ($c['is_internal'] ?? 0);
                                $roleName = $c['role_nom'] ?: '—';
                            ?>
                            <span class="badge <?= $isInternal ? 'badge-info' : 'badge-secondary' ?>">
                                <?= e($roleName) ?>
                            </span>
                        </td>
                        <td<?= $canEdit ? ' data-editable="collaborateur_type" data-editable-options="' . $collabTypeJson . '"' : '' ?>>
                            <?php
                                $ct = $c['collaborateur_type'] ?? '';
                                if (!in_array($ct, ['interne', 'externe-pm', 'externe-pp'], true)) {
                                    $denSte = $c['den_ste'] ?? '';
                                    $ct = ((int) ($c['can_login'] ?? 0)) ? 'interne' : (($denSte && $denSte !== 'NULL') ? 'externe-pm' : 'externe-pp');
                                }
                                $typeLabels = ['interne' => 'Interne', 'externe-pm' => 'PM', 'externe-pp' => 'PP'];
                                $typeClass = ['interne' => 'badge-info', 'externe-pm' => 'badge-secondary', 'externe-pp' => 'badge-warning'];
                            ?>
                            <span class="badge <?= $typeClass[$ct] ?? 'badge' ?>"><?= $typeLabels[$ct] ?? '-' ?></span>
                        </td>
                        <td<?= $canEdit ? ' data-editable="can_login" data-editable-options="' . $accesJson . '"' : '' ?>>
                            <?php if ((int) ($c['can_login'] ?? 0)): ?>
                                <span class="badge badge-success" title="Derniere connexion: <?= e(format_date($c['last_login'] ?? null)) ?>">Connectable</span>
                            <?php else: ?>
                                <span class="badge">Aucun acces</span>
                            <?php endif; ?>
                        </td>
                        <td<?= $canEdit ? ' data-editable="den_ste"' : '' ?>><?= e($c['den_ste'] ?? '-') ?></td>
                        <td><a href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500;"><?= e($c['nom_complet']) ?></a></td>
                        <td<?= $canEdit ? ' data-editable="fonction"' : '' ?>><?= e($c['fonction'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="collaborateur_ice"' : '' ?>><?= e($c['collaborateur_ice'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="collaborateur_tel_mobile"' : '' ?>><?= e($c['collaborateur_tel_mobile'] ?: $c['collaborateur_tel_fixe'] ?: $c['telephone'] ?: '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="statut" data-editable-options="' . $collabStatutJson . '"' : '' ?>><?= e($c['statut']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $c['created_at']))) ?></td>
                        <td><?= e($c['last_login'] ? date('d/m/Y H:i', strtotime($c['last_login'])) : '-') ?></td>
                        <td class="table-actions">
                            <a class="btn-icon primary" href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <a class="btn-icon info" href="<?= e(app_url('collaborateur', ['id' => (int) $c['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $c['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce collaborateur ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <template data-row-template>
                <tr data-id="">
                    <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                    <td data-cell="role_nom"></td>
                    <td data-cell="collaborateur_type"></td>
                    <td data-cell="can_login"></td>
                    <td data-cell="den_ste"></td>
                    <td data-cell-link="collaborateur" data-cell-value="id" data-cell-label="nom_complet"></td>
                    <td data-cell="fonction"></td>
                    <td data-cell="collaborateur_ice"></td>
                    <td data-cell="collaborateur_tel_mobile"></td>
                    <td data-cell="statut"></td>
                    <td data-cell="created_at"></td>
                    <td data-cell="last_login"></td>
                    <td data-cell-actions>
                        <a class="btn-icon primary" href="" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                        <a class="btn-icon info" href="" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        <form method="post" action="index.php?page=collaborateurs">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="_csrf_token" value="">
                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce collaborateur ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
            </template>
            </div>
        <?php endif; ?>
    </article>

    <?php
    $quickCreateTitle = 'Nouveau collaborateur';
    $quickCreateTable = 'collaborateurs';
    $quickCreateFields = [
        ['type' => 'title', 'label' => 'Identite & Role'],
        ['name' => 'collaborateur_type', 'label' => 'Type', 'type' => 'select', 'options' => $collabTypeOptions, 'required' => true],
        ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'options' => $rolesOptions],
        ['name' => 'nom_complet', 'label' => 'Nom complet', 'type' => 'text', 'required' => true],
        ['name' => 'den_ste', 'label' => 'Cabinet', 'type' => 'text'],
        ['name' => 'fonction', 'label' => 'Fonction', 'type' => 'text'],
        ['type' => 'title', 'label' => 'Contact'],
        ['name' => 'collaborateur_email', 'label' => 'Email', 'type' => 'email'],
        ['name' => 'collaborateur_tel_mobile', 'label' => 'Telephone mobile', 'type' => 'text'],
        ['name' => 'collaborateur_tel_fixe', 'label' => 'Telephone fixe', 'type' => 'text'],
        ['name' => 'collaborateur_adresse', 'label' => 'Adresse', 'type' => 'textarea', 'full' => true],
        ['type' => 'title', 'label' => 'Identifiants legaux'],
        ['name' => 'collaborateur_ice', 'label' => 'ICE', 'type' => 'text'],
        ['name' => 'collaborateur_code', 'label' => 'Code', 'type' => 'text'],
        ['name' => 'collaborateur_tp', 'label' => 'TP', 'type' => 'text'],
        ['name' => 'collaborateur_rc', 'label' => 'RC', 'type' => 'text'],
        ['name' => 'collaborateur_if', 'label' => 'IF', 'type' => 'text'],
        ['type' => 'title', 'label' => 'Informations'],
        ['name' => 'statut', 'label' => 'Statut', 'type' => 'select', 'options' => $collabStatutOptions],
    ];
    require __DIR__ . '/../../includes/quick_create_modal.php';

    $bulkEditTitle = 'Modifier les collaborateurs selectionnes';
    $bulkEditTable = 'collaborateurs';
    $bulkEditFields = [
        ['name' => 'statut', 'label' => 'Statut', 'type' => 'select', 'options' => $collabStatutOptions],
        ['name' => 'collaborateur_type', 'label' => 'Type', 'type' => 'select', 'options' => $collabTypeOptions],
    ];
    require __DIR__ . '/../../includes/bulk_edit_modal.php';
    ?>
</section>

<div class="bulk-toolbar" data-bulk-toolbar>
    <span class="bulk-info"><span class="bulk-count" data-bulk-count>0</span> enregistrement(s) selectionne(s)</span>
    <button class="btn btn-info" type="button" data-bulk-edit-btn><span class="material-symbols-outlined">edit_note</span> Modifier en masse</button>
</div>

<?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>