<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$canEdit = has_permission('associes.edit');

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

// Reference data for quick create / bulk edit
$societesOptions = [];
$qualitesOptions = [];
$lieuxNaissanceOptions = [];
$nationalitesOptions = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, societe_raison_sociale FROM societes ORDER BY societe_raison_sociale ASC');
    while ($row = $stmt->fetch()) {
        $societesOptions[(int)$row['id']] = $row['societe_raison_sociale'];
    }
    $qualitesOptions = fetch_reference_options($pdo, 'ref_qualites_associe', 'qualite_associe');
    $lieuxNaissanceOptions = fetch_reference_options($pdo, 'ref_lieux_naissance', 'lieu_naissance');
    $nationalitesOptions = fetch_reference_options($pdo, 'ref_nationalites', 'nationalite');
}
// JSON-encoded option arrays for inline editable selects
$qualitesJson = e(json_encode(array_values($qualitesOptions)));
$lieuxJson = e(json_encode(array_values($lieuxNaissanceOptions)));
$nationalitesJson = e(json_encode(array_values($nationalitesOptions)));
$civiliteJson = e(json_encode(['Mr', 'Mme', 'Mlle']));
$gerantJson = e(json_encode(['0' => 'Non', '1' => 'Oui']));

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM associes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'associe', (int) $_POST['id']);
        set_flash('success', 'Associe supprime avec succes.');
        redirect_to('associes');
    }
}

$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$userFilter = '';
$userParams = [];
if (!$isAdmin && $user) {
    $userFilter = ' AND societes.created_by = :user_id';
    $userParams['user_id'] = (int) $user['id'];
}

if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $likeTerm = like_term($query);
        $stmt = $pdo->prepare('
            SELECT associes.*, societes.societe_raison_sociale
            FROM associes
            INNER JOIN societes ON societes.id = associes.societe_id
            WHERE (associes.associe_nom_complet LIKE :term1
               OR societes.societe_raison_sociale LIKE :term2
               OR associes.associe_cin LIKE :term3)
            ' . $userFilter . '
            ORDER BY associes.id DESC
        ');
        $stmt->execute(['term1' => $likeTerm, 'term2' => $likeTerm, 'term3' => $likeTerm] + $userParams);
        $associes = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('
            SELECT associes.*, societes.societe_raison_sociale
            FROM associes
            INNER JOIN societes ON societes.id = associes.societe_id
            WHERE 1=1
            ' . $userFilter . '
            ORDER BY associes.id DESC
        ');
        $stmt->execute($userParams);
        $associes = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $a): array {
            return [
                $a['id'],
                $a['associe_nom_complet'],
                $a['societe_raison_sociale'],
                $a['associe_cin'],
                format_date($a['associe_date_naissance'] ?? null),
                $a['associe_lieu_naissance'],
                $a['associe_nationalite'],
                $a['associe_telephone'],
                $a['associe_email'],
                $a['associe_qualite'],
                $a['associe_parts'],
                (int) $a['associe_est_gerant'] === 1 ? 'Oui' : 'Non',
            ];
        }, $associes);

        $headers = [
            'ID', 'Nom complet', 'Societe', 'CIN', 'Date naissance',
            'Lieu naissance', 'Nationalite', 'Telephone', 'Email',
            'Qualite', 'Parts', 'Gerant',
        ];

        if ($exportType === 'csv') {
            export_csv('associes.csv', $headers, $rows);
        } else {
            export_excel('associes.xlsx', $headers, $rows);
        }
    }
} else {
    $associes = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($associes) ?> enregistrement(s)</span>
            <div class="table-actions">
                <?php if (has_permission('associes.create')): ?>
                <button class="btn btn-next" type="button" data-quick-create-btn><span class="material-symbols-outlined">add</span> Nouvel associe</button>
                <?php endif; ?>
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('associes', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('associes', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('associes.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="associes"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="associes">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par nom, societe ou CIN" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('associes')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$associes): ?>
            <p class="table-empty">Aucun associe pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable data-table="associes" data-bulk>
                <thead>
                <tr>
                    <th data-bulk-col><input type="checkbox" data-bulk-select-all title="Tout selectionner"></th>
                    <th data-col="nom-complet">Nom complet</th>
                    <th data-col="societe">Societe</th>
                    <th data-col="cin">CIN</th>
                    <th data-col="date-naiss">Date naissance</th>
                    <th data-col="lieu-naiss">Lieu naissance</th>
                    <th data-col="nationalite">Nationalite</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="email">Email</th>
                    <th data-col="qualite">Qualite</th>
                    <th data-col="parts">Parts</th>
                    <th data-col="gerant">Gerant</th>
                    <th data-col="capital-detenu">Capital detenu</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr data-id="<?= (int) $associe['id'] ?>">
                        <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                        <td><a href="<?= e(app_url('associe', ['id' => (int) $associe['id']])) ?>" class="table-link"><?= e($associe['associe_nom_complet']) ?></a></td>
                        <td><?= e($associe['societe_raison_sociale']) ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_cin"' : '' ?>><?= e($associe['associe_cin'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_date_naissance"' : '' ?>><?= e(format_date($associe['associe_date_naissance'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_lieu_naissance" data-editable-options="' . $lieuxJson . '"' : '' ?>><?= e($associe['associe_lieu_naissance'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_nationalite" data-editable-options="' . $nationalitesJson . '"' : '' ?>><?= e($associe['associe_nationalite'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_telephone"' : '' ?>><?= e($associe['associe_telephone'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_email"' : '' ?>><?= e($associe['associe_email'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_qualite" data-editable-options="' . $qualitesJson . '"' : '' ?>><?= e($associe['associe_qualite'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_parts"' : '' ?>><?= $associe['associe_parts'] !== null ? e((string) $associe['associe_parts']) : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_est_gerant" data-editable-options="' . $gerantJson . '"' : '' ?>><?= (int) $associe['associe_est_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td<?= $canEdit ? ' data-editable="associe_capital_detenu"' : '' ?>><?= $associe['associe_capital_detenu'] !== null ? e(number_format((float) $associe['associe_capital_detenu'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $associe['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $associe['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon primary" href="<?= e(app_url('associe', ['id' => (int) $associe['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <a class="btn-icon info" href="<?= e(app_url('associe', ['id' => (int) $associe['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $associe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet associe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <template data-row-template>
                <tr data-id="">
                    <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                    <td data-cell-link="associe" data-cell-value="id" data-cell-label="associe_nom_complet"></td>
                    <td data-cell="societe_raison_sociale"></td>
                    <td data-cell="associe_cin"></td>
                    <td data-cell="associe_date_naissance"></td>
                    <td data-cell="associe_lieu_naissance"></td>
                    <td data-cell="associe_nationalite"></td>
                    <td data-cell="associe_telephone"></td>
                    <td data-cell="associe_email"></td>
                    <td data-cell="associe_qualite"></td>
                    <td data-cell="associe_parts"></td>
                    <td data-cell="associe_est_gerant"></td>
                    <td data-cell="associe_capital_detenu"></td>
                    <td data-cell="created_at"></td>
                    <td data-cell="updated_at"></td>
                    <td data-cell-actions>
                        <a class="btn-icon primary" href="" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                        <a class="btn-icon info" href="" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        <form method="post" action="index.php?page=associes">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="_csrf_token" value="">
                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet associe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
            </template>
            </div>
        <?php endif; ?>
    </article>

    <?php
    $quickCreateTitle = 'Nouvel associe';
    $quickCreateTable = 'associes';
    $quickCreateFields = [
        ['name' => 'associe_nom_complet', 'label' => 'Nom complet', 'type' => 'text', 'required' => true],
        ['name' => 'societe_id', 'label' => 'Societe', 'type' => 'select', 'options' => $societesOptions, 'required' => true],
        ['name' => 'associe_civilite', 'label' => 'Civilite', 'type' => 'select', 'options' => ['Mr', 'Mme', 'Mlle']],
        ['name' => 'associe_nom', 'label' => 'Nom', 'type' => 'text'],
        ['name' => 'associe_prenom', 'label' => 'Prenom', 'type' => 'text'],
        ['name' => 'associe_cin', 'label' => 'CIN', 'type' => 'text'],
        ['name' => 'associe_date_naissance', 'label' => 'Date naissance', 'type' => 'date'],
        ['name' => 'associe_lieu_naissance', 'label' => 'Lieu naissance', 'type' => 'select', 'options' => $lieuxNaissanceOptions],
        ['name' => 'associe_nationalite', 'label' => 'Nationalite', 'type' => 'select', 'options' => $nationalitesOptions],
        ['name' => 'associe_telephone', 'label' => 'Telephone', 'type' => 'text'],
        ['name' => 'associe_email', 'label' => 'Email', 'type' => 'email'],
        ['name' => 'associe_qualite', 'label' => 'Qualite associe', 'type' => 'select', 'options' => $qualitesOptions],
        ['name' => 'associe_parts', 'label' => 'Parts', 'type' => 'number'],
        ['name' => 'associe_est_gerant', 'label' => 'Gerant', 'type' => 'select', 'options' => ['0' => 'Non', '1' => 'Oui']],
    ];
    require __DIR__ . '/../../includes/quick_create_modal.php';

    $bulkEditTitle = 'Modifier les associes selectionnes';
    $bulkEditTable = 'associes';
    $bulkEditFields = [
        ['name' => 'associe_qualite', 'label' => 'Qualite associe', 'type' => 'select', 'options' => $qualitesOptions],
        ['name' => 'associe_nationalite', 'label' => 'Nationalite', 'type' => 'select', 'options' => $nationalitesOptions],
    ];
    require __DIR__ . '/../../includes/bulk_edit_modal.php';
    ?>
</section>

<div class="bulk-toolbar" data-bulk-toolbar>
    <span class="bulk-info"><span class="bulk-count" data-bulk-count>0</span> enregistrement(s) selectionne(s)</span>
    <button class="btn btn-info" type="button" data-bulk-edit-btn><span class="material-symbols-outlined">edit_note</span> Modifier en masse</button>
</div>

<?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>

