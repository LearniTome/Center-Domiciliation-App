<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$canEdit = has_permission('contrats.edit');

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

// Reference data for quick create / bulk edit
$societesOptions = [];
if (($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->query('SELECT id, societe_raison_sociale FROM societes ORDER BY societe_raison_sociale ASC');
    while ($row = $stmt->fetch()) {
        $societesOptions[(int)$row['id']] = $row['societe_raison_sociale'];
    }
}

// JSON option arrays for inline editable selects
$contratTypeOptions = ['Domiciliation commerciale', 'Domiciliation professionnelle', 'Domiciliation simple', 'autre'];
$contratTypeDomiOptions = ['Personne Morale', 'Personne Physique', 'Association', 'Fondation', 'Autres'];
$contratStatutOptions = ['actif', 'expire', 'brouillon'];
$tvaOptions = ['7', '10', '14', '20'];
$renouvellementOptions = ['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'];
$contratTypeJson = e(json_encode($contratTypeOptions));
$contratTypeDomiJson = e(json_encode($contratTypeDomiOptions));
$contratStatutJson = e(json_encode($contratStatutOptions));
$tvaJson = e(json_encode($tvaOptions));
$renouvellementJson = e(json_encode($renouvellementOptions));

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM contrats WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'contrat', (int) $_POST['id']);
        set_flash('success', 'Contrat supprime avec succes.');
        redirect_to('contrats');
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
            SELECT contrats.*, societes.societe_raison_sociale
            FROM contrats
            INNER JOIN societes ON societes.id = contrats.societe_id
            WHERE (societes.societe_raison_sociale LIKE :term1
               OR contrats.contrat_type LIKE :term2
               OR contrats.contrat_statut LIKE :term3)
            ' . $userFilter . '
            ORDER BY contrats.id DESC
        ');
        $stmt->execute(['term1' => $likeTerm, 'term2' => $likeTerm, 'term3' => $likeTerm] + $userParams);
            $contrats = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('
            SELECT contrats.*, societes.societe_raison_sociale
            FROM contrats
            INNER JOIN societes ON societes.id = contrats.societe_id
            WHERE 1=1
            ' . $userFilter . '
            ORDER BY contrats.id DESC
        ');
        $stmt->execute($userParams);
        $contrats = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $c): array {
            return [
                $c['id'],
                $c['societe_raison_sociale'],
                $c['contrat_type'],
                format_date($c['contrat_date'] ?? null),
                $c['contrat_duree_mois'],
                $c['contrat_type_domiciliation'],
                format_date($c['contrat_date_debut'] ?? null),
                format_date($c['contrat_date_fin'] ?? null),
                $c['contrat_loyer_ttc'],
                $c['contrat_tva_pourcent'],
                $c['contrat_loyer_ht'],
                $c['contrat_total_ht'],
                $c['contrat_type_renouvellement'],
                $c['contrat_statut'],
            ];
        }, $contrats);

        $headers = [
            'ID', 'Societe', 'Type contrat', 'Date contrat', 'Duree (mois)',
            'Type domiciliation', 'Date debut', 'Date fin', 'Loyer TTC/mois',
            'TVA %', 'Loyer HT/mois', 'Total HT', 'Renouvellement', 'Statut',
        ];

        if ($exportType === 'csv') {
            export_csv('contrats.csv', $headers, $rows);
        } else {
            export_excel('contrats.xlsx', $headers, $rows);
        }
    }
} else {
    $contrats = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($contrats) ?> enregistrement(s)</span>
            <div class="table-actions">
                <?php if (has_permission('contrats.create')): ?>
                <button class="btn btn-next" type="button" data-quick-create-btn><span class="material-symbols-outlined">add</span> Nouveau contrat</button>
                <?php endif; ?>
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('contrats', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('contrats', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('contrats.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="contrats"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="contrats">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par societe, type ou statut" value="<?= e($query) ?>">
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('contrats')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$contrats): ?>
            <p class="table-empty">Aucun contrat pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable data-table="contrats" data-bulk>
                <thead>
                <tr>
                    <th data-bulk-col><input type="checkbox" data-bulk-select-all title="Tout selectionner"></th>
                    <th data-col="societe">Societe</th>
                    <th data-col="type-contrat">Type contrat</th>
                    <th data-col="date-contrat">Date contrat</th>
                    <th data-col="duree">Duree (mois)</th>
                    <th data-col="type-domiciliation">Type domiciliation</th>
                    <th data-col="date-debut">Date debut</th>
                    <th data-col="date-fin">Date fin</th>
                    <th data-col="loyer-ttc">Loyer mensuel TTC</th>
                    <th data-col="caution">Caution</th>
                    <th data-col="tva">TVA %</th>
                    <th data-col="loyer-ht">Loyer mensuel HT</th>
                    <th data-col="total-ht">Total HT</th>
                    <th data-col="pack-demarrage">Pack demarrage TTC</th>
                    <th data-col="renouvellement">Renouvellement</th>
                    <th data-col="statut">Statut</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr data-id="<?= (int) $contrat['id'] ?>">
                        <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                        <td><?= e($contrat['societe_raison_sociale']) ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_type" data-editable-options="' . $contratTypeJson . '"' : '' ?>><?= e($contrat['contrat_type']) ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_date"' : '' ?>><?= e(format_date($contrat['contrat_date'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_duree_mois"' : '' ?>><?= e((string) ($contrat['contrat_duree_mois'] ?? '-')) ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_type_domiciliation" data-editable-options="' . $contratTypeDomiJson . '"' : '' ?>><?= e($contrat['contrat_type_domiciliation'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_date_debut"' : '' ?>><?= e(format_date($contrat['contrat_date_debut'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_date_fin"' : '' ?>><?= e(format_date($contrat['contrat_date_fin'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_loyer_ttc"' : '' ?>><?= $contrat['contrat_loyer_ttc'] !== null ? e(number_format((float) $contrat['contrat_loyer_ttc'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_caution"' : '' ?>><?= $contrat['contrat_caution'] !== null ? e(number_format((float) $contrat['contrat_caution'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_tva_pourcent" data-editable-options="' . $tvaJson . '"' : '' ?>><?= $contrat['contrat_tva_pourcent'] !== null ? e(number_format((float) $contrat['contrat_tva_pourcent'], 2, ',', ' ') . ' %') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_loyer_ht"' : '' ?>><?= $contrat['contrat_loyer_ht'] !== null ? e(number_format((float) $contrat['contrat_loyer_ht'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_total_ht"' : '' ?>><?= $contrat['contrat_total_ht'] !== null ? e(number_format((float) $contrat['contrat_total_ht'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_pack_montant_ttc"' : '' ?>><?= $contrat['contrat_pack_montant_ttc'] !== null ? e(number_format((float) $contrat['contrat_pack_montant_ttc'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_type_renouvellement" data-editable-options="' . $renouvellementJson . '"' : '' ?>><?= e($contrat['contrat_type_renouvellement'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="contrat_statut" data-editable-options="' . $contratStatutJson . '"' : '' ?>><?= e($contrat['contrat_statut']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $contrat['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $contrat['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon primary" href="<?= e(app_url('contrat', ['id' => (int) $contrat['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <a class="btn-icon info" href="<?= e(app_url('contrat', ['id' => (int) $contrat['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $contrat['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce contrat ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <template data-row-template>
                <tr data-id="">
                    <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                    <td data-cell="societe_raison_sociale"></td>
                    <td data-cell="contrat_type"></td>
                    <td data-cell="contrat_date"></td>
                    <td data-cell="contrat_duree_mois"></td>
                    <td data-cell="contrat_type_domiciliation"></td>
                    <td data-cell="contrat_date_debut"></td>
                    <td data-cell="contrat_date_fin"></td>
                    <td data-cell="contrat_loyer_ttc"></td>
                    <td data-cell="contrat_caution"></td>
                    <td data-cell="contrat_tva_pourcent"></td>
                    <td data-cell="contrat_loyer_ht"></td>
                    <td data-cell="contrat_total_ht"></td>
                    <td data-cell="contrat_pack_montant_ttc"></td>
                    <td data-cell="contrat_type_renouvellement"></td>
                    <td data-cell="contrat_statut"></td>
                    <td data-cell="created_at"></td>
                    <td data-cell="updated_at"></td>
                    <td data-cell-actions>
                        <a class="btn-icon primary" href="" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                        <a class="btn-icon info" href="" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        <form method="post" action="index.php?page=contrats">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="_csrf_token" value="">
                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer ce contrat ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
            </template>
            </div>
        <?php endif; ?>
    </article>

    <?php
    $quickCreateTitle = 'Nouveau contrat';
    $quickCreateTable = 'contrats';
    $quickCreateFields = [
        ['type' => 'title', 'label' => 'Type de contrat'],
        ['name' => 'societe_id', 'label' => 'Societe', 'type' => 'select', 'options' => $societesOptions, 'required' => true],
        ['name' => 'contrat_type', 'label' => 'Type contrat', 'type' => 'select', 'options' => $contratTypeOptions, 'required' => true],
        ['name' => 'contrat_type_domiciliation', 'label' => 'Type domiciliation', 'type' => 'select', 'options' => $contratTypeDomiOptions],
        ['name' => 'contrat_date', 'label' => 'Date contrat', 'type' => 'date'],
        ['type' => 'title', 'label' => 'Periode'],
        ['name' => 'contrat_date_debut', 'label' => 'Date debut', 'type' => 'date'],
        ['name' => 'contrat_duree_mois', 'label' => 'Duree (mois)', 'type' => 'number'],
        ['name' => 'contrat_date_fin', 'label' => 'Date fin', 'type' => 'date'],
        ['name' => 'contrat_statut', 'label' => 'Statut', 'type' => 'select', 'options' => $contratStatutOptions],
        ['type' => 'title', 'label' => 'Loyer'],
        ['name' => 'contrat_loyer_ht', 'label' => 'Loyer HT/mois', 'type' => 'number'],
        ['name' => 'contrat_tva_pourcent', 'label' => 'TVA %', 'type' => 'select', 'options' => $tvaOptions],
        ['name' => 'contrat_loyer_ttc', 'label' => 'Loyer TTC/mois', 'type' => 'number'],
        ['name' => 'contrat_caution', 'label' => 'Caution', 'type' => 'number'],
        ['type' => 'title', 'label' => 'Renouvellement'],
        ['name' => 'contrat_type_renouvellement', 'label' => 'Type renouvellement', 'type' => 'select', 'options' => $renouvellementOptions],
        ['name' => 'contrat_renouv_loyer_ht', 'label' => 'Loyer HT renouv.', 'type' => 'number'],
        ['name' => 'contrat_renouv_tva_pourcent', 'label' => 'TVA renouv. %', 'type' => 'select', 'options' => $tvaOptions],
        ['name' => 'contrat_renouv_loyer_ttc', 'label' => 'Loyer TTC renouv.', 'type' => 'number'],
    ];
    require __DIR__ . '/../../includes/quick_create_modal.php';

    $bulkEditTitle = 'Modifier les contrats selectionnes';
    $bulkEditTable = 'contrats';
    $bulkEditFields = [
        ['name' => 'contrat_statut', 'label' => 'Statut', 'type' => 'select', 'options' => $contratStatutOptions],
    ];
    require __DIR__ . '/../../includes/bulk_edit_modal.php';
    ?>
</section>

<div class="bulk-toolbar" data-bulk-toolbar>
    <span class="bulk-info"><span class="bulk-count" data-bulk-count>0</span> enregistrement(s) selectionne(s)</span>
    <button class="btn btn-info" type="button" data-bulk-edit-btn><span class="material-symbols-outlined">edit_note</span> Modifier en masse</button>
</div>

<?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>

