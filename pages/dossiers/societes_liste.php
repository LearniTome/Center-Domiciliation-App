<?php

declare(strict_types=1);

$query = search_term();
$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$canEdit = has_permission('societes.edit');

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

// Reference data for quick create / bulk edit
$formesOptions = [];
$villesOptions = [];
$tribunauxOptions = [];
$adressesOptions = [];
$tribunalTypes = [];
if (($pdo ?? null) instanceof PDO) {
    $formesOptions = fetch_reference_options($pdo, 'ref_formes_juridiques', 'forme_juridique');
    $villesOptions = fetch_reference_options($pdo, 'ref_villes', 'ville');
    $tribunauxOptions = fetch_reference_options($pdo, 'ref_tribunaux', 'tribunal');
    $adressesOptions = fetch_reference_options($pdo, 'ref_ste_adresses', 'adresse');
    $tribunalTypes = fetch_tribunaux_types($pdo);
    $tribunauxAll = fetch_tribunaux_all($pdo);
    $adressesAll = fetch_adresses_all($pdo);
}

// JSON-encoded option arrays for inline editable selects
$formesJson = e(json_encode(array_values($formesOptions)));
$villesJson = e(json_encode(array_values($villesOptions)));
$tribunauxJson = e(json_encode(array_values($tribunauxOptions)));
$sourceOptions = ['creation', 'cession'];
$sourceJson = e(json_encode($sourceOptions));
$tribunalTypesJson = e(json_encode($tribunalTypes));

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM societes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'societe', (int) $_POST['id']);
        set_flash('success', 'Societe supprimee avec succes.');
        redirect_to('societes');
    }
}

if (($pdo ?? null) instanceof PDO) {
    $userFilter = '';
    $userParams = [];
    if (!$isAdmin && $user) {
        $userFilter = ' AND created_by = :user_id';
        $userParams['user_id'] = (int) $user['id'];
    }
    if ($query !== '') {
        $likeTerm = like_term($query);
        $stmt = $pdo->prepare('
            SELECT *
            FROM societes
            WHERE (societe_raison_sociale LIKE :term1 OR societe_forme_juridique LIKE :term2 OR societe_ice LIKE :term3 OR societe_ville LIKE :term4)
            ' . $userFilter . '
            ORDER BY id DESC
        ');
        $params = [
            'term1' => $likeTerm,
            'term2' => $likeTerm,
            'term3' => $likeTerm,
            'term4' => $likeTerm,
        ] + $userParams;
        $stmt->execute($params);
        $societes = $stmt->fetchAll();
    } else {
        $sql = 'SELECT * FROM societes';
        if ($userFilter) {
            $sql .= ' WHERE created_by = :user_id';
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($userParams);
        $societes = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $societe): array {
            return [
                $societe['id'],
                $societe['societe_raison_sociale'],
                $societe['societe_dossier'],
                $societe['societe_forme_juridique'],
                $societe['societe_source'] ?? 'creation',
                $societe['societe_ice'],
                format_date($societe['societe_date_ice'] ?? null),
                $societe['societe_rc'],
                $societe['societe_if'],
                $societe['societe_activites_statuts'] ?? '',
                $societe['societe_activites_ompic'] ? fetch_activites_ompic_display($pdo ?? null, (string) $societe['societe_activites_ompic']) : '',
                $societe['societe_tribunal'],
                $societe['societe_ville'],
                $societe['societe_email'],
                $societe['societe_telephone'],
                $societe['societe_capital'],
            ];
        }, $societes);

        $headers = [
            'ID', 'Raison sociale', 'Dossier domiciliation', 'Forme juridique',
            'Origine', 'ICE', 'Date cert. negatif', 'RC', 'IF',
            'Activites (Statuts)', 'Activites (OMPIC)', 'Tribunal', 'Ville',
            'Email', 'Telephone', 'Capital',
        ];

        if ($exportType === 'csv') {
            export_csv('societes.csv', $headers, $rows);
        } else {
            export_excel('societes.xlsx', $headers, $rows);
        }
    }
} else {
    $societes = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($societes) ?> enregistrement(s)</span>
            <div class="table-actions">
                <?php if (has_permission('societes.create')): ?>
                <button class="btn btn-next" type="button" data-quick-create-btn><span class="material-symbols-outlined">add</span> Nouvelle societe</button>
                <?php endif; ?>
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url('societes', ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url('societes', ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('societes.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="societes"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="societes">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par societe, ICE, forme ou ville"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('societes')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$societes): ?>
            <p class="table-empty">Aucune societe pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable data-table="societes" data-bulk>
                <thead>
                <tr>
                    <th data-bulk-col><input type="checkbox" data-bulk-select-all title="Tout selectionner"></th>
                    <th data-col="dossier">Dossier</th>
                    <th data-col="source">Origine</th>
                    <th data-col="raison-sociale">Raison sociale</th>
                    <th data-col="forme">Forme</th>
                    <th data-col="ice">ICE</th>
                    <th data-col="date-ompic">Date cert. OMPIC</th>
                    <th data-col="rc">RC</th>
                    <th data-col="if">IF</th>
                    <th data-col="capital">Capital</th>
                    <th data-col="ville">Ville</th>
                    <th data-col="tribunal">Tribunal</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="email">Email</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($societes as $societe): ?>
                    <tr data-id="<?= (int) $societe['id'] ?>">
                        <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                        <td<?= $canEdit ? ' data-editable="societe_dossier"' : '' ?>><?= e($societe['societe_dossier'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_source" data-editable-options="' . $sourceJson . '"' : '' ?>>
                            <?php $src = $societe['societe_source'] ?? 'creation'; ?>
                            <?php if ($src === 'cession'): ?>
                                <span class="badge badge-info" style="font-size:0.65rem">Cession</span>
                            <?php elseif ($src !== 'creation'): ?>
                                <span class="badge badge-info" style="font-size:0.65rem"><?= e(ucfirst($src)) ?></span>
                            <?php else: ?>
                                <span class="badge badge-success" style="font-size:0.65rem">Creation</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500"><?= e($societe['societe_raison_sociale']) ?></a></td>
                        <td<?= $canEdit ? ' data-editable="societe_forme_juridique" data-editable-options="' . $formesJson . '"' : '' ?>><?= e($societe['societe_forme_juridique']) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_ice"' : '' ?>><?= e($societe['societe_ice'] ?? '-') ?></td>
                        <td><?= e(format_date($societe['societe_date_ice'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_rc"' : '' ?>><?= e($societe['societe_rc'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_if"' : '' ?>><?= e($societe['societe_if'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_capital"' : '' ?>><?= $societe['societe_capital'] !== null ? e(number_format((float) $societe['societe_capital'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_ville" data-editable-options="' . $villesJson . '"' : '' ?>><?= e($societe['societe_ville']) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_tribunal" data-editable-options="' . $tribunauxJson . '"' : '' ?>><?= e($societe['societe_tribunal'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_telephone"' : '' ?>><?= e($societe['societe_telephone']) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_email"' : '' ?>><?= e($societe['societe_email'] ?? '-') ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $societe['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $societe['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon primary" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <a class="btn-icon info" href="<?= e(app_url('societe', ['id' => (int) $societe['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $societe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette societe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <template data-row-template>
                <tr data-id="">
                    <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                    <td data-cell="societe_dossier"></td>
                    <td><span class="badge badge-success" style="font-size:0.65rem">Creation</span></td>
                    <td data-cell-link="societe" data-cell-value="id" data-cell-label="societe_raison_sociale"></td>
                    <td data-cell="societe_forme_juridique"></td>
                    <td data-cell="societe_ice"></td>
                    <td data-cell="societe_date_ice"></td>
                    <td data-cell="societe_rc"></td>
                    <td data-cell="societe_if"></td>
                    <td data-cell="societe_capital"></td>
                    <td data-cell="societe_ville"></td>
                    <td data-cell="societe_tribunal"></td>
                    <td data-cell="societe_telephone"></td>
                    <td data-cell="societe_email"></td>
                    <td data-cell="created_at"></td>
                    <td data-cell="updated_at"></td>
                    <td data-cell-actions>
                        <a class="btn-icon primary" href="" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                        <a class="btn-icon info" href="" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        <form method="post" action="index.php?page=societes">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="_csrf_token" value="">
                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette societe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
            </template>
            </div>
        <?php endif; ?>
    </article>

    <?php
    $quickCreateTitle = 'Nouvelle societe';
    ?>
    <div class="modal-overlay" data-modal="quick-create">
        <div class="modal-panel">
            <div class="modal-header">
                <h3><?= e($quickCreateTitle) ?></h3>
                <button class="btn-icon" data-modal-close type="button" title="Fermer"><span class="material-symbols-outlined">close</span></button>
            </div>
            <form data-quick-create-form>
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="quick_create">
                <input type="hidden" name="table" value="societes">
                <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
                    <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <label class="field"><span>Raison sociale</span><input type="text" name="societe_raison_sociale" required></label>
                        <label class="field"><span>Forme juridique</span><select name="societe_forme_juridique" required><option value="">Selectionner</option><?php foreach ($formesOptions as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?></select></label>
                    </div>
                    <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <label class="field"><span>Date cert. negatif</span><input type="date" name="societe_date_ice"></label>
                        <label class="field"><span>Date exp. cert. negatif</span><input type="date" name="societe_date_exp_cert_neg"></label>
                    </div>
                    <label class="field"><span>ICE</span><input type="text" name="societe_ice"></label>
                    <label class="field"><span>RC</span><input type="text" name="societe_rc"></label>
                    <label class="field"><span>IF</span><input type="text" name="societe_if"></label>
                    <label class="field"><span>TP</span><input type="text" name="societe_tp"></label>
                    <label class="field"><span>CNSS</span><input type="text" name="societe_cnss"></label>
                    <label class="field"><span>Capital</span><input type="number" step="0.01" name="societe_capital"></label>
                    <label class="field"><span>Part social</span><input type="number" name="societe_part_social"></label>
                    <label class="field"><span>Valeur nominale</span><input type="number" step="0.01" name="societe_valeur_nominale"></label>
                    <label class="field"><span>Type tribunal</span><select name="societe_tribunal_type" data-tribunal-type><option value="">Selectionner</option><?php foreach ($tribunalTypes ?? [] as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select></label>
                    <label class="field"><span>Tribunal</span><select name="societe_tribunal"><option value="">Selectionner</option><?php foreach ($tribunauxAll ?? [] as $opt): ?><option value="<?= e($opt['tribunal']) ?>" data-type="<?= e($opt['tribunal_type'] ?? '') ?>"><?= e($opt['tribunal']) ?></option><?php endforeach; ?></select></label>
                    <label class="field"><span>Telephone</span><input type="text" name="societe_telephone"></label>
                    <label class="field"><span>Email</span><input type="email" name="societe_email"></label>
                    <label class="field" style="grid-column:1/-1"><span>Adresse siege</span><select name="societe_adresse_siege"><option value="">Selectionner</option><?php foreach ($adressesAll ?? [] as $opt): ?><option value="<?= e($opt['ste_adresse']) ?>" data-ville="<?= e($opt['ville'] ?? '') ?>"><?= e($opt['ste_adresse']) ?></option><?php endforeach; ?></select></label>
                    <label class="field" style="grid-column:1/-1"><span>Ville</span><select name="societe_ville" data-ville-filter><option value="">Selectionner</option><?php foreach ($villesOptions as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?></select></label>
                </div>
                <div class="form-actions" style="margin-top:1rem;display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn btn-cancel" data-modal-close><span class="material-symbols-outlined">close</span> Annuler</button>
                    <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">add</span> Creer</button>
                </div>
            </form>
        </div>
    </div>

    $bulkEditTitle = 'Modifier les societes selectionnees';
    $bulkEditTable = 'societes';
    $bulkEditFields = [
        ['name' => 'societe_forme_juridique', 'label' => 'Forme juridique', 'type' => 'select', 'options' => $formesOptions],
        ['name' => 'societe_ville', 'label' => 'Ville', 'type' => 'select', 'options' => $villesOptions],
        ['name' => 'societe_tribunal', 'label' => 'Tribunal', 'type' => 'select', 'options' => $tribunauxOptions],
    ];
    require __DIR__ . '/../../includes/bulk_edit_modal.php';
    ?>
</section>

<div class="bulk-toolbar" data-bulk-toolbar>
    <span class="bulk-info"><span class="bulk-count" data-bulk-count>0</span> enregistrement(s) selectionne(s)</span>
    <button class="btn btn-info" type="button" data-bulk-edit-btn><span class="material-symbols-outlined">edit_note</span> Modifier en masse</button>
</div>

<?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>
